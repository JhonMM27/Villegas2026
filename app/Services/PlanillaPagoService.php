<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Gasto;
use App\Models\GastoCategoria;
use App\Models\GastoTipo;
use App\Models\PlanillaPago;
use App\Models\PlanillaPagoDetalle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlanillaPagoService
{
    public function __construct(
        protected EmpleadoService $empleadoService,
        protected PlanillaAsistenciaService $asistenciaService,
        protected PlanillaCalculoService $calculoService,
        protected PlanillaElegibilidadService $elegibilidadService
    ) {}

    public function getAll(): Collection
    {
        return PlanillaPago::with(['empleado.sueldoActual', 'sueldoAplicado'])
            ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
            ->noAnulados()
            ->orderByDesc('id')
            ->get();
    }

    public function getByMes(int $mes, int $anio): Collection
    {
        return PlanillaPago::with(['empleado.sueldoActual', 'sueldoAplicado'])
            ->whereIn('empleado_id', $this->elegibilidadService->idsEmpleadosElegibles($mes, $anio))
            ->noAnulados()
            ->delMes($mes, $anio)
            ->get();
    }

    public function getByEmpleado(int $empleadoId): Collection
    {
        return PlanillaPago::where('empleado_id', $empleadoId)
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();
    }

    public function findById(int $id): ?PlanillaPago
    {
        return PlanillaPago::with(['empleado', 'sueldoAplicado', 'detalles', 'adelantosRecords'])->find($id);
    }

    public function existePagoMes(int $empleadoId, int $mes, int $anio): bool
    {
        return PlanillaPago::where('empleado_id', $empleadoId)
            ->delMes($mes, $anio)
            ->exists();
    }

    public function procesarPago(array $data): PlanillaPago
    {
        return DB::transaction(function () use ($data) {
            $empleado = $this->empleadoService->findById((int) $data['empleado_id']);
            if (! $empleado) {
                throw new \Exception('Empleado no encontrado');
            }
            if (! $this->elegibilidadService->esElegible($empleado, (int) $data['mes'], (int) $data['anio'])) {
                throw new \Exception('El empleado no es elegible para el período seleccionado');
            }

            $sueldo = $this->empleadoService->sueldoParaPeriodo(
                (int) $data['empleado_id'],
                (int) $data['mes'],
                (int) $data['anio']
            );
            if (! $sueldo) {
                throw new \Exception('El empleado no tiene un sueldo vigente para el período seleccionado');
            }

            $horasExtras = (float) ($data['horas_extras'] ?? 0);
            $this->asistenciaService->syncDiasFaltadosFromInasistencias(
                (int) $data['empleado_id'],
                (int) $data['mes'],
                (int) $data['anio']
            );
            $asistencia = $this->asistenciaService->getByEmpleadoMes(
                (int) $data['empleado_id'],
                (int) $data['mes'],
                (int) $data['anio']
            );
            $diasFaltados = $asistencia ? (float) $asistencia->dias_faltados : 0.0;
            $ctsSueldoReal = (float) ($data['cts_sueldo_real'] ?? 0);
            $desglose = $this->calculoService->calcular(
                $empleado,
                $sueldo,
                (int) $data['mes'],
                (int) $data['anio'],
                $horasExtras,
                $diasFaltados,
                $ctsSueldoReal
            );

            $pago = PlanillaPago::create([
                'empleado_id' => (int) $data['empleado_id'],
                'empleado_sueldo_id' => $sueldo->id,
                'mes' => (int) $data['mes'],
                'anio' => (int) $data['anio'],
                'sueldo_base' => $desglose['disponible'],
                'horas_extras' => $horasExtras,
                'dias_faltados' => $diasFaltados,
                'descuento_faltas' => $desglose['descuento_faltas'],
                'cts_planilla' => (float) ($data['cts_planilla'] ?? 0) ?: null,
                'cts_sueldo_real' => $ctsSueldoReal ?: null,
                'adelantos' => $desglose['adelantos'],
                'total_pagar' => $desglose['total_pagar'],
                'estado' => 'pendiente',
                'observaciones' => $data['observaciones'] ?? null,
                'importe_p' => $data['principal'] ?? 0,
                'importe_d' => $data['deposito'] ?? 0,
                'importe_c' => $data['consorcio'] ?? 0,
            ]);

            if ($horasExtras > 0) {
                PlanillaPagoDetalle::create([
                    'planilla_pago_id' => $pago->id,
                    'concepto' => 'Horas Extras',
                    'monto' => $horasExtras,
                    'tipo' => 'ingreso',
                ]);
            }

            PlanillaPagoDetalle::create([
                'planilla_pago_id' => $pago->id,
                'concepto' => 'Sueldo base disponible',
                'monto' => $desglose['disponible'],
                'tipo' => 'ingreso',
            ]);

            if ($desglose['descuento_faltas'] > 0) {
                PlanillaPagoDetalle::create([
                    'planilla_pago_id' => $pago->id,
                    'concepto' => 'Descuento por Faltas',
                    'monto' => $desglose['descuento_faltas'],
                    'tipo' => 'descuento',
                ]);
            }

            return $pago;
        });
    }

    public function marcarPagado(PlanillaPago $pago, ?string $fechaPago = null): bool
    {
        return DB::transaction(function () use ($pago, $fechaPago) {
            $pago = PlanillaPago::query()->lockForUpdate()->findOrFail($pago->id);
            if ($pago->estado === 'anulado') {
                throw new \RuntimeException('No se puede confirmar un pago anulado.');
            }
            if (! $this->elegibilidadService->esElegible($pago->empleado, (int) $pago->mes, (int) $pago->anio)) {
                throw new \RuntimeException('El empleado no es elegible para este período.');
            }

            $this->aplicarConfirmacion($pago, $fechaPago);
            $this->sincronizarGastoPlanilla((int) $pago->mes, (int) $pago->anio);

            return true;
        });
    }

    public function update(PlanillaPago $pago, array $data): bool
    {
        return DB::transaction(function () use ($pago, $data) {
            $periodoAnterior = [(int) $pago->mes, (int) $pago->anio];
            $sueldo = $pago->sueldoAplicado
                ?? $this->empleadoService->sueldoParaPeriodo($pago->empleado_id, (int) $pago->mes, (int) $pago->anio);
            $sueldoReal = $sueldo ? (float) $sueldo->sueldo_real : 0;

            $horasExtras = (float) ($data['horas_extras'] ?? 0);
            $diasFaltados = (float) ($data['dias_faltados'] ?? 0);
            $ctsSueldoReal = (float) ($data['cts_sueldo_real'] ?? 0);
            $descuentoFaltas = $this->calculoService->descuentoFaltas($sueldoReal, $diasFaltados);
            $newTotal = (float) $pago->sueldo_base + $horasExtras - $descuentoFaltas + $ctsSueldoReal;

            $oldTotal = (float) $pago->total_pagar;

            $pago->mes = (int) ($data['mes'] ?? $pago->mes);
            $pago->anio = (int) ($data['anio'] ?? $pago->anio);
            $pago->horas_extras = $horasExtras;
            $pago->dias_faltados = $diasFaltados;
            $pago->descuento_faltas = $descuentoFaltas;
            $pago->cts_planilla = isset($data['cts_planilla']) ? ((float) $data['cts_planilla'] ?: null) : $pago->cts_planilla;
            $pago->cts_sueldo_real = $ctsSueldoReal ?: null;
            $pago->total_pagar = $newTotal;
            $pago->observaciones = $data['observaciones'] ?? $pago->observaciones;

            if ($oldTotal > 0 && abs($newTotal - $oldTotal) > 0.01) {
                $ratio = $newTotal / $oldTotal;
                $pago->importe_p = round((float) $pago->importe_p * $ratio, 2);
                $pago->importe_d = round((float) $pago->importe_d * $ratio, 2);
                $pago->importe_c = round((float) $pago->importe_c * $ratio, 2);
            } else {
                $pago->importe_p = (float) ($data['principal'] ?? 0);
                $pago->importe_d = (float) ($data['deposito'] ?? 0);
                $pago->importe_c = (float) ($data['consorcio'] ?? 0);
            }

            $result = $pago->save();

            if ($pago->estado === 'pagado') {
                $this->sincronizarGastoPlanilla((int) $pago->mes, (int) $pago->anio);
                if ($periodoAnterior !== [(int) $pago->mes, (int) $pago->anio]) {
                    $this->sincronizarGastoPlanilla(...$periodoAnterior);
                }
            }

            return $result;
        });
    }

    public function recalcularPago(PlanillaPago $pago): bool
    {
        if ($pago->estado !== 'pendiente') {
            return false;
        }

        return DB::transaction(function () use ($pago): bool {
            $pago = PlanillaPago::query()->lockForUpdate()->find($pago->id);
            if (! $pago || $pago->estado !== 'pendiente') {
                return false;
            }
            $empleado = $this->empleadoService->findById($pago->empleado_id);
            if (! $empleado) {
                return false;
            }

            $sueldo = $this->empleadoService->sueldoParaPeriodo($pago->empleado_id, $pago->mes, $pago->anio);
            if (! $sueldo) {
                return false;
            }

            $asistencia = $this->asistenciaService->getByEmpleadoMes($pago->empleado_id, $pago->mes, $pago->anio);
            $diasFaltados = $asistencia ? (float) $asistencia->dias_faltados : 0.0;
            $desglose = $this->calculoService->calcular(
                $empleado,
                $sueldo,
                (int) $pago->mes,
                (int) $pago->anio,
                (float) $pago->horas_extras,
                $diasFaltados,
                (float) ($pago->cts_sueldo_real ?? 0)
            );
            $oldTotal = (float) $pago->total_pagar;

            $pago->sueldo_base = $desglose['disponible'];
            $pago->empleado_sueldo_id = $sueldo->id;
            $pago->adelantos = $desglose['adelantos'];
            $pago->dias_faltados = $diasFaltados;
            $pago->descuento_faltas = $desglose['descuento_faltas'];
            $pago->total_pagar = $desglose['total_pagar'];
            $this->ajustarDistribucionCaja($pago, $oldTotal, $desglose['total_pagar']);

            return $pago->save();
        });
    }

    public function getResumenMensual(int $mes, int $anio): array
    {
        $pagos = $this->getByMes($mes, $anio);

        return [
            'total_pagar' => $pagos->sum('total_pagar'),
            'total_horas_extras' => $pagos->sum('horas_extras'),
            'total_adelantos' => $pagos->sum('adelantos'),
            'total_descuento_faltas' => $pagos->sum('descuento_faltas'),
            'cantidad_empleados' => $pagos->count(),
            'pendientes' => $pagos->where('estado', 'pendiente')->count(),
            'pagados' => $pagos->where('estado', 'pagado')->count(),
        ];
    }

    public function generarPagosDelMes(int $mes, int $anio): array
    {
        return DB::transaction(function () use ($mes, $anio): array {
            $empleados = $this->elegibilidadService->empleadosElegibles($mes, $anio);
            $resultado = [
                'elegibles' => $empleados->count(),
                'creados' => 0,
                'existentes' => 0,
                'anulados' => 0,
                'sin_sueldo' => [],
            ];

            foreach ($empleados as $empleado) {
                $existente = PlanillaPago::query()
                    ->where('empleado_id', $empleado->id)
                    ->delMes($mes, $anio)
                    ->lockForUpdate()
                    ->first();

                if ($existente) {
                    $resultado[$existente->estado === 'anulado' ? 'anulados' : 'existentes']++;

                    continue;
                }

                $sueldo = $this->empleadoService->sueldoParaPeriodo($empleado->id, $mes, $anio);
                if (! $sueldo) {
                    $resultado['sin_sueldo'][] = $empleado->nombre;

                    continue;
                }

                $this->asistenciaService->syncDiasFaltadosFromInasistencias($empleado->id, $mes, $anio);
                $asistencia = $this->asistenciaService->getByEmpleadoMes($empleado->id, $mes, $anio);
                $diasFaltados = $asistencia ? (float) $asistencia->dias_faltados : 0.0;
                $desglose = $this->calculoService->calcular($empleado, $sueldo, $mes, $anio, 0, $diasFaltados);

                PlanillaPago::create([
                    'empleado_id' => $empleado->id,
                    'empleado_sueldo_id' => $sueldo->id,
                    'mes' => $mes,
                    'anio' => $anio,
                    'sueldo_base' => $desglose['disponible'],
                    'horas_extras' => 0,
                    'adelantos' => $desglose['adelantos'],
                    'dias_faltados' => $diasFaltados,
                    'descuento_faltas' => $desglose['descuento_faltas'],
                    'total_pagar' => $desglose['total_pagar'],
                    'estado' => 'pendiente',
                    'importe_p' => $desglose['total_pagar'],
                    'importe_d' => 0,
                    'importe_c' => 0,
                ]);

                $resultado['creados']++;
            }

            return $resultado;
        });
    }

    public function confirmarPagosDelMes(int $mes, int $anio): int
    {
        return DB::transaction(function () use ($mes, $anio): int {
            $pagos = PlanillaPago::query()
                ->whereIn('empleado_id', $this->elegibilidadService->idsEmpleadosElegibles($mes, $anio))
                ->delMes($mes, $anio)
                ->pendientes()
                ->lockForUpdate()
                ->get();

            foreach ($pagos as $pago) {
                $this->aplicarConfirmacion($pago);
            }

            $this->sincronizarGastoPlanilla($mes, $anio);

            return $pagos->count();
        });
    }

    public function revertirPagosDelMes(int $mes, int $anio): int
    {
        return DB::transaction(function () use ($mes, $anio): int {
            $pagos = PlanillaPago::query()
                ->whereIn('empleado_id', $this->elegibilidadService->idsEmpleadosElegibles($mes, $anio))
                ->delMes($mes, $anio)
                ->pagados()
                ->lockForUpdate()
                ->get();

            foreach ($pagos as $pago) {
                $this->aplicarReversion($pago, 'Reversión masiva del período');
            }

            $this->sincronizarGastoPlanilla($mes, $anio);

            return $pagos->count();
        });
    }

    public function existenPagosDelMes(int $mes, int $anio): bool
    {
        return PlanillaPago::whereIn('empleado_id', $this->elegibilidadService->idsEmpleadosElegibles($mes, $anio))
            ->delMes($mes, $anio)
            ->noAnulados()
            ->exists();
    }

    public function getCantidadPagosDelMes(int $mes, int $anio): array
    {
        $idsElegibles = $this->elegibilidadService->idsEmpleadosElegibles($mes, $anio);
        $base = PlanillaPago::query()->whereIn('empleado_id', $idsElegibles)->delMes($mes, $anio)->noAnulados();
        $total = (clone $base)->count();
        $pendientes = (clone $base)->pendientes()->count();
        $pagados = (clone $base)->pagados()->count();
        $excluidos = PlanillaPago::query()
            ->whereNotIn('empleado_id', $idsElegibles ?: [0])
            ->delMes($mes, $anio)
            ->noAnulados()
            ->count();

        return [
            'total' => $total,
            'pendientes' => $pendientes,
            'pagados' => $pagados,
            'elegibles' => count($idsElegibles),
            'faltantes' => max(count($idsElegibles) - $total, 0),
            'excluidos' => $excluidos,
        ];
    }

    public function revertirPago(PlanillaPago $pago): bool
    {
        if ($pago->estado !== 'pagado') {
            return false;
        }

        return DB::transaction(function () use ($pago): bool {
            $pago = PlanillaPago::query()->lockForUpdate()->findOrFail($pago->id);
            $this->aplicarReversion($pago, 'Reversión individual');
            $this->sincronizarGastoPlanilla((int) $pago->mes, (int) $pago->anio);

            return true;
        });
    }

    public function anularPago(PlanillaPago $pago, string $motivo): bool
    {
        return DB::transaction(function () use ($pago, $motivo): bool {
            $pago = PlanillaPago::query()->lockForUpdate()->findOrFail($pago->id);
            if ($pago->estado === 'anulado') {
                return false;
            }

            $estadoAnterior = (string) $pago->estado;
            $fechaAnterior = $pago->fecha_pago?->toDateString();
            $pago->fecha_pago_original ??= $pago->fecha_pago;
            $pago->estado = 'anulado';
            $pago->fecha_pago = null;
            $pago->anulado_at = now();
            $pago->anulado_por = auth()->id();
            $pago->motivo_anulacion = trim($motivo);
            $pago->save();
            $this->registrarMovimiento($pago, 'anulacion', $estadoAnterior, 'anulado', $fechaAnterior, null, $motivo);
            $this->sincronizarGastoPlanilla((int) $pago->mes, (int) $pago->anio);

            return true;
        });
    }

    public function rectificarFechaPago(PlanillaPago $pago, string $fechaPago, string $motivo): bool
    {
        return DB::transaction(function () use ($pago, $fechaPago, $motivo): bool {
            $pago = PlanillaPago::query()->lockForUpdate()->findOrFail($pago->id);
            if ($pago->estado !== 'pagado') {
                throw new \RuntimeException('Solo se puede rectificar la fecha de un pago confirmado.');
            }

            $fechaAnterior = $pago->fecha_pago?->toDateString();
            $pago->fecha_pago = $fechaPago;
            $pago->fecha_pago_original = $fechaPago;
            $pago->save();
            $this->registrarMovimiento(
                $pago,
                'rectificacion_fecha',
                'pagado',
                'pagado',
                $fechaAnterior,
                $fechaPago,
                $motivo
            );
            $this->sincronizarGastoPlanilla((int) $pago->mes, (int) $pago->anio);

            return true;
        });
    }

    public function registrarGastoIndividualPago(PlanillaPago $pago): void
    {
        $empleado = $this->empleadoService->findById($pago->empleado_id);
        if (! $empleado) {
            return;
        }

        $totalPagar = (float) $pago->total_pagar;
        $importeP = (float) $pago->importe_p;
        $importeD = (float) $pago->importe_d;
        $importeC = (float) $pago->importe_c;
        $montoGasto = $totalPagar;

        $categoria = GastoCategoria::where('nombre', 'Gastos_Personal')->first();
        if (! $categoria) {
            $categoria = GastoCategoria::create([
                'nombre' => 'Gastos_Personal',
                'activo' => true,
            ]);
        }

        $tipo = GastoTipo::where('nombre', 'Sueldos')
            ->where('categoria_gasto_id', $categoria->id)
            ->first();
        if (! $tipo) {
            $tipo = GastoTipo::create([
                'nombre' => 'Sueldos',
                'activo' => true,
                'categoria_gasto_id' => $categoria->id,
            ]);
        }

        $gastoExistente = Gasto::where('empleado_id', $pago->empleado_id)
            ->where('planilla_mes', $pago->mes)
            ->where('planilla_anio', $pago->anio)
            ->first();

        $userId = auth()->id() ?? 1;
        $userNombre = auth()->user()->name ?? 'Sistema';
        $nombreMes = $this->getNombreMes((int) $pago->mes);

        $siguienteRecibo = $this->siguienteNumeroGasto('numero_recibo');
        $siguienteInterno = $this->siguienteNumeroGasto('numero_interno');

        if (! $gastoExistente) {
            Gasto::create([
                'user_id' => $userId,
                'user_nombre' => $userNombre,
                'fecha_gasto' => $pago->fecha_pago ?? now(),
                'descripcion' => "Pago {$empleado->nombre} - {$nombreMes}/{$pago->anio}",
                'responsable' => $empleado->nombre,
                'responsable_dni' => $empleado->dni,
                'empleado_id' => $empleado->id,
                'categoria_gasto_id' => $categoria->id,
                'gasto_tipo_id' => $tipo->id,
                'numero_recibo' => $siguienteRecibo,
                'numero_interno' => (string) $siguienteInterno,
                'monto' => $montoGasto,
                'importe_p' => $importeP,
                'importe_d' => $importeD,
                'importe_c' => $importeC,
                'planilla_mes' => $pago->mes,
                'planilla_anio' => $pago->anio,
            ]);
        } else {
            $gastoExistente->update([
                'fecha_gasto' => $pago->fecha_pago ?? now(),
                'descripcion' => "Pago {$empleado->nombre} - {$nombreMes}/{$pago->anio}",
                'responsable' => $empleado->nombre,
                'responsable_dni' => $empleado->dni,
                'monto' => $montoGasto,
                'importe_p' => $importeP,
                'importe_d' => $importeD,
                'importe_c' => $importeC,
            ]);
        }
    }

    public function eliminarGastoIndividualPago(PlanillaPago $pago): void
    {
        Gasto::where('empleado_id', $pago->empleado_id)
            ->where('planilla_mes', $pago->mes)
            ->where('planilla_anio', $pago->anio)
            ->delete();
    }

    public function recalcularPagosDelEmpleado(int $empleadoId): int
    {
        $pagos = PlanillaPago::where('empleado_id', $empleadoId)
            ->pendientes()
            ->get();

        $contador = 0;
        foreach ($pagos as $pago) {
            if ($this->recalcularPago($pago)) {
                $contador++;
            }
        }

        return $contador;
    }

    private function ajustarDistribucionCaja(PlanillaPago $pago, float $oldTotal, float $newTotal): void
    {
        $sumaCaja = (float) $pago->importe_p + (float) $pago->importe_d + (float) $pago->importe_c;
        if ($oldTotal > 0 && $sumaCaja > 0) {
            $ratio = $newTotal / $oldTotal;
            $pago->importe_p = round((float) $pago->importe_p * $ratio, 2);
            $pago->importe_d = round((float) $pago->importe_d * $ratio, 2);
            $pago->importe_c = round((float) $pago->importe_c * $ratio, 2);
            $diferencia = round($newTotal - ((float) $pago->importe_p + (float) $pago->importe_d + (float) $pago->importe_c), 2);
            $pago->importe_p = round((float) $pago->importe_p + $diferencia, 2);

            return;
        }

        $pago->importe_p = $newTotal;
        $pago->importe_d = 0;
        $pago->importe_c = 0;
    }

    private function aplicarConfirmacion(PlanillaPago $pago, ?string $fechaPago = null): void
    {
        $estadoAnterior = (string) $pago->estado;
        $fechaAnterior = $pago->fecha_pago?->toDateString();
        $restauraFechaOriginal = $pago->fecha_pago_original !== null;
        $fechaEfectiva = $pago->fecha_pago_original?->toDateString()
            ?? $fechaPago
            ?? now()->toDateString();

        $pago->estado = 'pagado';
        $pago->fecha_pago = $fechaEfectiva;
        $pago->fecha_pago_original ??= $fechaEfectiva;
        $pago->save();

        $this->registrarMovimiento(
            $pago,
            'confirmacion',
            $estadoAnterior,
            'pagado',
            $fechaAnterior,
            $fechaEfectiva,
            $restauraFechaOriginal
                ? 'Confirmación conservando la fecha original'
                : 'Confirmación de pago'
        );
    }

    private function aplicarReversion(PlanillaPago $pago, string $motivo): void
    {
        $fechaAnterior = $pago->fecha_pago?->toDateString();
        $pago->fecha_pago_original ??= $pago->fecha_pago;
        $pago->estado = 'pendiente';
        $pago->fecha_pago = null;
        $pago->save();

        $this->registrarMovimiento($pago, 'reversion', 'pagado', 'pendiente', $fechaAnterior, null, $motivo);
    }

    private function registrarMovimiento(
        PlanillaPago $pago,
        string $accion,
        string $estadoAnterior,
        string $estadoNuevo,
        ?string $fechaAnterior,
        ?string $fechaNueva,
        ?string $motivo = null
    ): void {
        $pago->movimientos()->create([
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'fecha_pago_anterior' => $fechaAnterior,
            'fecha_pago_nueva' => $fechaNueva,
            'motivo' => $motivo,
            'user_id' => auth()->id(),
            'user_nombre' => (string) (auth()->user()?->name ?? 'Sistema'),
        ]);
    }

    public function sincronizarGastoPlanilla(int $mes, int $anio): void
    {
        $pagos = PlanillaPago::with('sueldoAplicado')
            ->where('planilla_pagos.mes', $mes)
            ->where('planilla_pagos.anio', $anio)
            ->where('planilla_pagos.estado', 'pagado')
            ->get();
        $totalSueldos = (float) $pagos->sum(fn (PlanillaPago $pago): float => (float) $pago->total_pagar);
        $importeP = (float) $pagos->sum(fn (PlanillaPago $pago): float => (float) $pago->importe_p);
        $importeD = (float) $pagos->sum(fn (PlanillaPago $pago): float => (float) $pago->importe_d);
        $importeC = (float) $pagos->sum(fn (PlanillaPago $pago): float => (float) $pago->importe_c);
        $fechaUltimoPago = $pagos->max('fecha_pago') ?? now();

        if ($totalSueldos <= 0) {
            Gasto::where('planilla_mes', $mes)
                ->where('planilla_anio', $anio)
                ->whereNull('empleado_id')
                ->delete();

            return;
        }

        $categoria = GastoCategoria::where('nombre', 'Gastos_Personal')->first();
        if (! $categoria) {
            $categoria = GastoCategoria::create([
                'nombre' => 'Gastos_Personal',
                'activo' => true,
            ]);
        }

        $tipo = GastoTipo::where('nombre', 'Sueldos')
            ->where('categoria_gasto_id', $categoria->id)
            ->first();
        if (! $tipo) {
            $tipo = GastoTipo::create([
                'nombre' => 'Sueldos',
                'activo' => true,
                'categoria_gasto_id' => $categoria->id,
            ]);
        }

        $gasto = Gasto::where('planilla_mes', $mes)
            ->where('planilla_anio', $anio)
            ->whereNull('empleado_id')
            ->first();

        $userId = auth()->id() ?? 1;
        $userNombre = auth()->user()->name ?? 'Sistema';

        $siguienteRecibo = $this->siguienteNumeroGasto('numero_recibo');
        $siguienteInterno = $this->siguienteNumeroGasto('numero_interno');

        $nombreMes = $this->getNombreMes($mes);

        if (! $gasto) {
            Gasto::create([
                'user_id' => $userId,
                'user_nombre' => $userNombre,
                'fecha_gasto' => $fechaUltimoPago,
                'descripcion' => "Pago Empleados mes {$nombreMes}/{$anio}",
                'responsable' => 'Consorcios Villegas',
                'responsable_dni' => null,
                'categoria_gasto_id' => $categoria->id,
                'gasto_tipo_id' => $tipo->id,
                'numero_recibo' => $siguienteRecibo,
                'numero_interno' => (string) $siguienteInterno,
                'monto' => $totalSueldos,
                'importe_p' => $importeP,
                'importe_d' => $importeD,
                'importe_c' => $importeC,
                'planilla_mes' => $mes,
                'planilla_anio' => $anio,
            ]);
        } else {
            $gasto->update([
                'fecha_gasto' => $fechaUltimoPago,
                'importe_p' => $importeP,
                'importe_d' => $importeD,
                'importe_c' => $importeC,
                'monto' => $totalSueldos,
                'descripcion' => "Pago Empleados mes {$nombreMes}/{$anio}",
            ]);
        }
    }

    private function getNombreMes(int $mes): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Setiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $meses[$mes] ?? $mes;
    }

    private function siguienteNumeroGasto(string $columna): int
    {
        if (! in_array($columna, ['numero_recibo', 'numero_interno'], true)) {
            throw new \InvalidArgumentException('Columna de correlativo no permitida.');
        }

        $query = Gasto::query()->whereNotNull($columna)->where($columna, '!=', '');
        if (DB::connection()->getDriverName() === 'sqlite') {
            $query->whereRaw("{$columna} GLOB '[0-9]*' AND {$columna} NOT GLOB '*[^0-9]*'");
            $ultimo = $query->selectRaw("MAX(CAST({$columna} AS INTEGER)) AS maximo")->value('maximo');
        } else {
            $query->whereRaw("{$columna} REGEXP '^[0-9]+$'");
            $ultimo = $query->selectRaw("MAX(CAST({$columna} AS UNSIGNED)) AS maximo")->value('maximo');
        }

        return $ultimo ? (int) $ultimo + 1 : 1;
    }
}
