<?php

/**
 * Servicio de Préstamos.
 *
 * Concentra toda la lógica de negocio asociada a la gestión de préstamos:
 * - Creación con reserva de correlativo (lockForUpdate)
 * - Cálculo de totales por detalle
 * - Registro de movimientos en el kardex (MovimientoService)
 * - Eliminación con reversión via recálculo de kardex
 *
 * Tipos de movimiento y su impacto en stock:
 * - PA (Préstamo A)    → Yo presto     → Stock DISMINUYE → registrarSalida()
 * - DA (Devolución A)  → Yo devuelvo   → Stock DISMINUYE → registrarSalida()
 * - PD (Préstamo De)   → Me prestan    → Stock AUMENTA   → registrarIngreso()
 * - DD (Devolución De)  → Me devuelven → Stock AUMENTA   → registrarIngreso()
 *
 * NOTA: Desde esta versión, todos los movimientos pasan por MovimientoService
 * para que aparezcan en el kardex valorizado con CPP correcto.
 */

namespace App\Services;

use App\Models\ComprobanteSerie;
use App\Models\ComprobanteTipo;
use App\Models\Movimiento;
use App\Models\Prestamo;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class PrestamoService
{
    /** ID del cliente que representa la empresa en la tabla clientes */
    private const EMPRESA_CLIENTE_ID = 11;

    /**
     * Inyección del servicio de movimientos para registrar
     * entradas/salidas en el kardex valorizado.
     */
    public function __construct(
        protected MovimientoService $movimientoService
    ) {}

    /**
     * Crea un préstamo completo dentro de una transacción:
     * 1. Reserva correlativo con bloqueo pesimista (si aplica)
     * 2. Procesa cabecera y detalles calculados
     * 3. Crea el registro Prestamo + PrestamoDetalles
     * 4. Registra movimientos en el kardex (ingreso o salida según tipo)
     * 5. Incrementa correlativo de la serie (si aplica)
     *
     * @param  array  $data  Datos validados del request
     * @return array ['prestamo' => Prestamo, 'correlativo' => int|null]
     *
     * @throws \Illuminate\Database\QueryException Si hay conflicto de correlativo
     * @throws \Exception Si ocurre cualquier otro error
     */
    public function createPrestamo(array $data, ?string $estado = null): array
    {
        return DB::transaction(function () use ($data, $estado) {

            // 1) Determinar si el tipo de comprobante usa serie/correlativo
            $usaSerie = in_array($data['comprobante_tipo_codigo'], ['SD', 'IP', 'SP', 'DP']);

            $serieConfig = null;
            $correlativo = null;

            // 2) Reservar correlativo con bloqueo pesimista (si aplica)
            if ($usaSerie) {
                $serieConfig = ComprobanteSerie::where('comprobante_tipo_codigo', $data['comprobante_tipo_codigo'])
                    ->where('serie', $data['serie'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $correlativo = (int) $serieConfig->correlativo;
                $data['correlativo'] = $correlativo;
            }

            // 3) Procesar datos de cabecera y detalles
            $prestamoData = $this->processPrestamoData($data, true, $estado);

            // 3.1) Validar que no se devuelva más de lo pendiente (si es devolución)
            if ($prestamoData['prestamo']['prestamo_referencia_id']) {
                $this->validarCantidadesDevolucion(
                    $prestamoData['prestamo']['prestamo_referencia_id'],
                    $prestamoData['detalles']
                );
            }

            // 4) Persistir préstamo y sus detalles
            $prestamo = Prestamo::create($prestamoData['prestamo']);
            $detallesCreados = $prestamo->detalles()->createMany($prestamoData['detalles']);

            // 5) Registrar movimientos en el kardex por cada detalle
            $movimientoTipo = $prestamoData['prestamo']['movimiento_tipo'];
            $increase = $this->isIncreaseStock($movimientoTipo);

            foreach ($detallesCreados as $detalle) {
                $this->registrarMovimientoDetalle($prestamo, $detalle, $increase);
            }

            // 6) Incrementar correlativo al final (dentro de la transacción)
            if ($usaSerie && $serieConfig) {
                $serieConfig->update([
                    'correlativo' => $correlativo + 1,
                ]);
            }

            // Actualizar estado del préstamo original si este registro es una devolución
            if ($prestamo->prestamo_referencia_id) {
                $this->actualizarEstadoPrestamoReferencia($prestamo->prestamo_referencia_id);
            }

            return [
                'prestamo' => $prestamo,
                'correlativo' => $correlativo,
            ];
        });
    }

    /**
     * Actualiza un préstamo existente dentro de una transacción:
     * 1. Recoge IDs de movimientos asociados al préstamo anterior
     * 2. Recalcula cabecera y detalles con los nuevos datos
     * 3. Reemplaza los detalles (delete + createMany)
     * 4. Elimina movimientos anteriores y recalcula kardex (excluyendo)
     * 5. Registra nuevos movimientos según el nuevo tipo
     *
     * @param  int  $id  ID del préstamo a actualizar
     * @param  array  $data  Datos validados del request
     * @return Prestamo El préstamo actualizado
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function updatePrestamo(int $id, array $data): Prestamo
    {
        return DB::transaction(function () use ($id, $data) {
            $prestamo = Prestamo::with('detalles')->findOrFail($id);

            // 1) Recoger IDs de movimientos anteriores para recálculo
            $movimientosAnteriores = Movimiento::where('transaccion_tipo', 'prestamos')
                ->where('transaccion_id', $prestamo->id)
                ->get();

            // Agrupar por producto_id para recalcular cada producto afectado
            $productosAfectados = $movimientosAnteriores->pluck('producto_id')->unique()->toArray();
            $movIdsExcluir = $movimientosAnteriores->pluck('id')->toArray();

            // 2) Procesar nuevos datos
            $prestamoData = $this->processPrestamoData($data, false);

            // 3) Actualizar cabecera y recrear detalles
            $prestamo->update($prestamoData['prestamo']);
            $prestamo->detalles()->delete();
            $detallesCreados = $prestamo->detalles()->createMany($prestamoData['detalles']);

            // 4) Excluir (neutralizar) movimientos anteriores y recalcular kardex
            foreach ($productosAfectados as $productoId) {
                $idsProducto = $movimientosAnteriores
                    ->where('producto_id', $productoId)
                    ->pluck('id')
                    ->toArray();

                $this->movimientoService->recalcularKardexExcluyendo(
                    $productoId,
                    $idsProducto
                );
            }

            // 5) Registrar nuevos movimientos según el nuevo tipo
            $newMovimientoTipo = $prestamoData['prestamo']['movimiento_tipo'];
            $newIncrease = $this->isIncreaseStock($newMovimientoTipo);

            foreach ($detallesCreados as $detalle) {
                $this->registrarMovimientoDetalle($prestamo, $detalle, $newIncrease);
            }

            foreach ($productosAfectados as $prodId) {
                $this->movimientoService->recalcularKardexProducto($prodId);
            }

            // Actualizar estado del préstamo original si este registro es una devolución
            if ($prestamo->prestamo_referencia_id) {
                $this->actualizarEstadoPrestamoReferencia($prestamo->prestamo_referencia_id);
            }

            // Si el préstamo modificado TENÍA una referencia anterior y cambió, actualizar la vieja
            $prestamoOriginalDB = Prestamo::find($id); // Refetch o usar find
            // Omitimos esto si no manejamos cambios de préstamo_referencia_id en update
            // Asumimos que no cambian el comprobante al que devuelven.

            return $prestamo;
        });
    }

    /**
     * Elimina un préstamo dentro de una transacción:
     * 1. Recoge IDs de movimientos asociados
     * 2. Neutraliza movimientos y recalcula kardex
     * 3. Elimina el registro (cascade elimina detalles)
     *
     * @param  int  $id  ID del préstamo a eliminar
     *
     * @throws \Exception Si ocurre cualquier error
     */
    public function deletePrestamo(int $id): void
    {
        DB::transaction(function () use ($id) {
            $registro = Prestamo::with('detalles')->findOrFail($id);

            // 1) Recoger movimientos asociados
            $movimientos = Movimiento::where('transaccion_tipo', 'prestamos')
                ->where('transaccion_id', $registro->id)
                ->get();

            $productosAfectados = $movimientos->pluck('producto_id')->unique()->toArray();

            // 2) Neutralizar movimientos y recalcular kardex por producto
            foreach ($productosAfectados as $productoId) {
                $idsProducto = $movimientos
                    ->where('producto_id', $productoId)
                    ->pluck('id')
                    ->toArray();

                $this->movimientoService->recalcularKardexExcluyendo(
                    $productoId,
                    $idsProducto
                );
            }

            // 3) Eliminar préstamo (cascade elimina detalles)
            $registro->delete();

            // Actualizar estado del préstamo original si este registro era una devolución
            if ($registro->prestamo_referencia_id) {
                $this->actualizarEstadoPrestamoReferencia($registro->prestamo_referencia_id);
            }
        });
    }

    /**
     * Anula un préstamo existente dentro de una transacción.
     * Cambia el estado a 'anulada' y neutraliza sus movimientos.
     *
     * @throws \Exception
     */
    public function anularPrestamo(int $id): Prestamo
    {
        return DB::transaction(function () use ($id) {
            $prestamo = Prestamo::with('detalles')->findOrFail($id);

            if ($prestamo->estado === 'anulada') {
                throw new \Exception('Este préstamo ya fue anulado.');
            }

            // Si está rectificada, permitir anular para volver a rectificar (si count < 3)
            if ($prestamo->estado === 'rectificada') {
                if ($prestamo->rectificacion_count >= 3) {
                    throw new \Exception('Este préstamo ya no puede ser rectificado. Máximo 3 rectificaciones permitidas.');
                }
                // Permitir: se volverá a 'anulada' para poder rectificar de nuevo
            }

            $prestamo->update(['estado' => 'anulada']);

            $productosAfectados = $prestamo->detalles->pluck('producto_id')->unique()->toArray();

            foreach ($productosAfectados as $productoId) {
                $movIds = Movimiento::where('transaccion_tipo', 'prestamos')
                    ->where('transaccion_id', $prestamo->id)
                    ->where('producto_id', $productoId)
                    ->pluck('id')
                    ->toArray();

                if (! empty($movIds)) {
                    $this->movimientoService->recalcularKardexExcluyendo(
                        $productoId,
                        $movIds
                    );
                }
            }

            // Si esto era una devolución de otro préstamo, debemos recalcular el original
            if ($prestamo->prestamo_referencia_id) {
                $this->actualizarEstadoPrestamoReferencia($prestamo->prestamo_referencia_id);
            }

            return $prestamo;
        });
    }

    /**
     * Rectifica un préstamo previamente anulado.
     * Restaura los movimientos neutralizados para mantener la historia.
     *
     * @param  int  $prestamoAnuladoId
     * @param  string  $serie
     * @param  string  $correlativo
     */
    /**
     * Rectifica un préstamo previamente anulado (Actualización in-situ).
     * Modifica el registro existente en lugar de crear uno nuevo.
     *
     * @param  string  $serie  (Ignorado para forzar 01-R)
     * @param  int  $correlativo  (Ignorado para mantener original)
     */
    public function rectificarPrestamo(
        int $prestamoId,
        array $data,
        string $comprobanteTipoCodigo,
        string $serie_ignore,
        int $correlativo_ignore
    ): array {
        return DB::transaction(function () use (
            $prestamoId, $data, $comprobanteTipoCodigo
        ) {
            $prestamo = Prestamo::findOrFail($prestamoId);

            if ($prestamo->estado !== 'anulada') {
                throw new \Exception('Solo se pueden rectificar préstamos en estado anulada.');
            }

            if ($prestamo->rectificacion_count >= 3) {
                throw new \Exception('Este préstamo ya no puede ser rectificado. Máximo 3 rectificaciones permitidas.');
            }

            $prestamoDataRaw = $this->processPrestamoData($data, false);
            $prestamoData = $prestamoDataRaw['prestamo'];

            // Forzamos serie de rectificación, estado y trazabilidad
            $prestamoData['comprobante_tipo_codigo'] = $comprobanteTipoCodigo;
            $prestamoData['serie'] = '01';
            $prestamoData['estado'] = 'rectificada';
            $prestamoData['nota'] = trim(($data['nota'] ?? $prestamo->nota).' | Rectificada el '.now()->format('d/m/Y H:i'));

            if ($prestamoData['prestamo_referencia_id']) {
                $this->validarCantidadesDevolucion(
                    $prestamoData['prestamo_referencia_id'],
                    $prestamoDataRaw['detalles']
                );
            }

            // 1) Actualizar la cabecera del registro existente
            $prestamo->update($prestamoData);

            // Incrementar contador de rectificaciones
            $prestamo->update(['rectificacion_count' => $prestamo->rectificacion_count + 1]);

            // 2) Reemplazar detalles
            $prestamo->detalles()->delete();
            $detallesNuevos = $prestamo->detalles()->createMany($prestamoDataRaw['detalles']);

            $productosAfectados = [];
            $newIncrease = $this->isIncreaseStock($prestamo->movimiento_tipo);

            // 3) Procesar movimientos
            foreach ($detallesNuevos as $detalle) {
                $movNeutralizado = Movimiento::where('transaccion_tipo', 'prestamos')
                    ->where('transaccion_id', $prestamoId)
                    ->where('producto_id', $detalle->producto_id)
                    ->where('entrada', 0)
                    ->where('salida', 0)
                    ->first();

                if ($movNeutralizado) {
                    $producto = Producto::find($detalle->producto_id);
                    $empaqueBase = (float) ($producto->empaque > 0 ? $producto->empaque : 1);
                    $empaqueFinal = (float) ($detalle->producto_empaque ?? 1);
                    $cantidadBase = round($detalle->cantidad * ($empaqueFinal / $empaqueBase), 4);
                    $costoTotalMov = $detalle->total ?? ($detalle->cantidad * $detalle->valor_unitario);
                    $costoUnitarioBase = $cantidadBase > 0 ? $costoTotalMov / $cantidadBase : (float) $detalle->valor_unitario;

                    $updateParams = [
                        'detalle_id' => $detalle->id,
                        'fecha' => $prestamo->fecha_prestamo,
                        'empaque' => $empaqueFinal,
                        'unidad_codigo' => $detalle->unidad_codigo,
                        'cantidad' => $detalle->cantidad,
                        'cantidad_kg' => $detalle->cantidad_kgm,
                        'costo_unitario' => round($costoUnitarioBase, 4),
                        'costo_total' => round($costoTotalMov, 4),
                        'comentario' => 'Rectificación de préstamo (Actualizado)',
                    ];

                    if ($newIncrease) {
                        $updateParams['entrada'] = $cantidadBase;
                        $updateParams['salida'] = 0;
                    } else {
                        $updateParams['salida'] = $cantidadBase;
                        $updateParams['entrada'] = 0;
                    }

                    $movNeutralizado->update($updateParams);
                    $productosAfectados[] = $detalle->producto_id;
                } else {
                    $this->registrarMovimientoDetalle($prestamo, $detalle, $newIncrease);
                    $productosAfectados[] = $detalle->producto_id;
                }
            }

            // 4) Recalcular Kardex
            foreach (array_unique($productosAfectados) as $productoId) {
                $primerMov = Movimiento::where('transaccion_id', $prestamo->id)
                    ->where('producto_id', $productoId)
                    ->orderBy('id', 'asc')
                    ->first();

                if ($primerMov) {
                    $this->movimientoService->recalcularKardexProducto($productoId, $primerMov->id);
                } else {
                    $this->movimientoService->recalcularKardexProducto($productoId);
                }
            }

            if ($prestamo->prestamo_referencia_id) {
                $this->actualizarEstadoPrestamoReferencia($prestamo->prestamo_referencia_id);
            }

            return [
                'prestamo' => $prestamo,
                'correlativo' => $prestamo->correlativo,
            ];
        });
    }

    // ─── Métodos privados ───────────────────────────────────

    /**
     * Registra un movimiento individual en el kardex para un detalle de préstamo.
     *
     * Si $increase es true (PD, DD) → registrarIngreso (stock sube)
     * Si $increase es false (PA, DA) → registrarSalida (stock baja)
     *
     * @param  Prestamo  $prestamo  El préstamo cabecera
     * @param  mixed  $detalle  El detalle creado (modelo PrestamoDetalle)
     * @param  bool  $increase  true=ingreso, false=salida
     */
    private function registrarMovimientoDetalle(Prestamo $prestamo, $detalle, bool $increase): void
    {
        $params = [
            'tipo' => $increase
                                    ? MovimientoService::TIPO_PRESTAMO_INGRESO
                                    : MovimientoService::TIPO_PRESTAMO_SALIDA,
            'fecha' => $prestamo->fecha_prestamo,
            'transaccion_tipo' => 'prestamos',
            'transaccion_id' => $prestamo->id,
            'detalle_id' => $detalle->id,
            'producto_id' => $detalle->producto_id,
            'producto_nombre' => $detalle->producto_nombre,
            'empaque' => $detalle->producto_empaque,
            'unidad_codigo' => $detalle->unidad_codigo,
            'cantidad' => $detalle->cantidad,
            'cantidad_kg' => $detalle->cantidad_kgm,
            'costo_unitario' => $detalle->valor_unitario,
        ];

        // Si es una devolución, forzamos que use el costo histórico original
        if ($prestamo->prestamo_referencia_id) {
            $params['usar_costo_exacto'] = true;
        }

        if ($increase) {
            $this->movimientoService->registrarIngreso($params);
        } else {
            $this->movimientoService->registrarSalida($params);
        }
    }

    /**
     * Determina si el tipo de movimiento aumenta o disminuye el stock.
     *
     * DD (Devolución De) y PD (Préstamo De) → AUMENTAN stock
     * PA (Préstamo A) y DA (Devolución A)   → DISMINUYEN stock
     *
     * @param  string  $movimientoTipo  Tipo de movimiento (PA, PD, DD, DA)
     * @return bool true si el stock aumenta, false si disminuye
     */
    private function isIncreaseStock(string $movimientoTipo): bool
    {
        return in_array($movimientoTipo, ['DD', 'PD'], true);
    }

    /**
     * Procesa y construye los datos del préstamo (cabecera + detalles calculados).
     *
     * Consulta las entidades relacionadas (comprobante, productos) y genera
     * los arrays listos para Prestamo::create() y detalles()->createMany().
     * Determina cliente origen/destino según el tipo de movimiento.
     * Ahora acepta unidad_codigo y empaque desde el frontend (selector de fracción).
     *
     * @param  array  $data  Datos validados del request
     * @param  bool  $isNew  Define si se agregan campos de auditoría (user_id, user_nombre)
     * @param  string|null  $estado  Estado explícito (ej: 'registrada')
     * @return array [ 'prestamo' => array, 'detalles' => array ]
     */
    private function processPrestamoData(array $data, bool $isNew = false, ?string $estado = null): array
    {
        // Obtener comprobante tipo
        $comprobanteTipo = ComprobanteTipo::where('codigo', $data['comprobante_tipo_codigo'])->first();

        // Cargar productos involucrados con sus relaciones
        $productos = Producto::with('afectacionTipo', 'unidad')
            ->whereIn('id', collect($data['detalles'])->pluck('producto_id'))
            ->get()
            ->keyBy('id');

        // Inicializar totales acumulados
        $totales = ['total' => 0];

        // Calcular cada línea de detalle
        $detallesCalculados = [];
        foreach ($data['detalles'] as $detalle) {
            $precioUnitario = $detalle['precio_unitario'];

            // Si es una devolución, forzamos el precio original del préstamo referenciado ajustado al empaque usado
            if (! empty($data['prestamo_referencia_id'])) {
                $originalDetalle = \App\Models\PrestamoDetalle::where('prestamo_id', $data['prestamo_referencia_id'])
                    ->where('producto_id', $detalle['producto_id'])
                    ->first();
                if ($originalDetalle) {
                    $precioOriginal = (float) $originalDetalle->valor_unitario;
                    $empaqueOriginal = (float) ($originalDetalle->producto_empaque > 0 ? $originalDetalle->producto_empaque : 1);
                    $precioPorKg = $precioOriginal / $empaqueOriginal;

                    $empaqueDevolucion = (float) ($detalle['empaque'] ?? $empaqueOriginal);
                    $precioUnitario = $precioPorKg * $empaqueDevolucion;
                }
            } else {
                // Si es un PA (préstamo nuevo hacia otros), el precio inicial debe ser el CPP vigente
                if ($data['movimiento_tipo'] === 'PA') {
                    $productoEnBD = $productos[$detalle['producto_id']];
                    // Si el empaque es diferente a 1, el precio_unitario (por unidad enviada)
                    // debe ser ajustado en caso de que se preste por empaque.
                    // El costo_unitario en BD corresponde al empaque base del producto.
                    $empaqueBase = (float) ($productoEnBD->empaque > 0 ? $productoEnBD->empaque : 1);
                    $empaqueFrontend = (float) ($detalle['empaque'] ?? $empaqueBase);

                    // Obtenemos el precio por Kg actual
                    $costoPorKg = (float) $productoEnBD->costo_unitario / $empaqueBase;

                    // Calculamos el precio unitario final para la unidad seleccionada
                    $precioUnitario = $costoPorKg * $empaqueFrontend;
                }
            }

            $detallesCalculados[] = $this->calculateDetail(
                $productos[$detalle['producto_id']],
                $detalle['cantidad'],
                $precioUnitario,
                $detalle['unidad_codigo'] ?? null,
                $detalle['empaque'] ?? null,
                $totales
            );
        }

        // Determinar cliente origen y destino según tipo de movimiento
        $clienteId = $data['cliente_id'];
        [$clienteOrigenId, $clienteDestinoId] = match ($data['movimiento_tipo']) {
            'PA', 'DA' => [self::EMPRESA_CLIENTE_ID, $clienteId],
            'PD', 'DD' => [$clienteId, self::EMPRESA_CLIENTE_ID],
        };

        // Construir array de la cabecera
        $prestamoData = [
            'movimiento_tipo' => $data['movimiento_tipo'],
            'cliente_origen_id' => $clienteOrigenId,
            'cliente_destino_id' => $clienteDestinoId,
            'prestamo_referencia_id' => $data['prestamo_referencia_id'] ?? null,
            'comprobante_tipo_codigo' => $data['comprobante_tipo_codigo'],
            'comprobante_tipo_nombre' => $comprobanteTipo->codigo ?? '',
            'serie' => $data['serie'],
            'correlativo' => $data['correlativo'],
            'fecha_prestamo' => $data['fecha_prestamo'] ?? now(),
            'total' => round($totales['total'], 2),
        ];

        // Campos exclusivos de creación
        if ($isNew) {
            $prestamoData['estado'] = $estado ?? 'registrada';
            $prestamoData['user_id'] = auth()->id();
            $prestamoData['user_nombre'] = auth()->user()->name;
        }

        return [
            'prestamo' => $prestamoData,
            'detalles' => $detallesCalculados,
        ];
    }

    /**
     * Obtiene las cantidades devueltas y pendientes para un préstamo original.
     *
     * @param  int  $id  ID del préstamo original
     * @return array [producto_id => ['devuelto' => float, 'pendiente' => float]]
     */
    public function obtenerSaldosPendientes(int $id): array
    {
        $prestamoOriginal = Prestamo::with('detalles')->findOrFail($id);

        // Obtenemos todas las devoluciones activas associadas a este préstamo
        $devoluciones = Prestamo::with('detalles')
            ->where('prestamo_referencia_id', $id)
            ->whereIn('estado', ['registrada', 'rectificada'])
            ->get();

        $cantidadesDevueltasKgm = [];
        foreach ($devoluciones as $devolucion) {
            foreach ($devolucion->detalles as $det) {
                $cantidadesDevueltasKgm[$det->producto_id] = ($cantidadesDevueltasKgm[$det->producto_id] ?? 0) + $det->cantidad_kgm;
            }
        }

        $saldos = [];
        foreach ($prestamoOriginal->detalles as $det) {
            $devuelto = $cantidadesDevueltasKgm[$det->producto_id] ?? 0;
            $pendiente = max(0, $det->cantidad_kgm - $devuelto);

            $saldos[$det->producto_id] = [
                'devuelto' => round($devuelto, 4),
                'pendiente' => round($pendiente, 4),
            ];
        }

        return $saldos;
    }

    /**
     * Actualiza el estado del préstamo original (PA/PD) evaluando si lo devuelto
     * alcanza o no a lo prestado inicialmente.
     */
    private function actualizarEstadoPrestamoReferencia(int $referenciaId): void
    {
        $prestamoOriginal = Prestamo::with('detalles')->findOrFail($referenciaId);

        // Obtenemos todas las devoluciones activas (no anuladas) asociadas a este préstamo
        $devoluciones = Prestamo::with('detalles')
            ->where('prestamo_referencia_id', $referenciaId)
            ->whereIn('estado', ['registrada', 'rectificada']) // Consideramos las registradas y rectificadas
            ->get();

        $cantidadesDevueltas = [];
        foreach ($devoluciones as $devolucion) {
            foreach ($devolucion->detalles as $det) {
                $cantidadesDevueltas[$det->producto_id] = ($cantidadesDevueltas[$det->producto_id] ?? 0) + $det->cantidad_kgm;
            }
        }

        $esParcial = false;
        $esCompleta = true;

        foreach ($prestamoOriginal->detalles as $detOriginal) {
            $devuelto = $cantidadesDevueltas[$detOriginal->producto_id] ?? 0;

            // Usamos round a 4 decimales para evitar problemas de precisión flotante
            if (round($devuelto, 4) < round($detOriginal->cantidad_kgm, 4)) {
                $esCompleta = false;
            }
            if (round($devuelto, 4) > 0) {
                $esParcial = true;
            }
        }

        if ($esCompleta) {
            $nuevoEstado = 'devuelto';
        } elseif ($esParcial) {
            $nuevoEstado = 'parcial';
        } else {
            $nuevoEstado = 'registrada';
        }

        if ($prestamoOriginal->estado !== $nuevoEstado) {
            $prestamoOriginal->update(['estado' => $nuevoEstado]);
        }
    }

    /**
     * Valida que las cantidades a devolver no superen el saldo pendiente del préstamo original.
     *
     * @param  int  $referenciaId  ID del préstamo original
     * @param  array  $detallesNuevos  Detalles calculados de la devolución
     *
     * @throws \Exception Si alguna cantidad excede el saldo pendiente
     */
    private function validarCantidadesDevolucion(int $referenciaId, array $detallesNuevos): void
    {
        $saldos = $this->obtenerSaldosPendientes($referenciaId);

        foreach ($detallesNuevos as $det) {
            $productoId = $det['producto_id'];
            $nombre = $det['producto_nombre'];
            $cantidadKgm = $det['cantidad_kgm'];

            if (! isset($saldos[$productoId])) {
                throw new \Exception("El producto '{$nombre}' no forma parte del préstamo original.");
            }

            $pendiente = $saldos[$productoId]['pendiente'];

            // Usamos round para evitar problemas de precisión decimal
            if (round($cantidadKgm, 4) > round($pendiente, 4)) {
                throw new \Exception("La cantidad a devolver de '{$nombre}' ({$cantidadKgm} Kg) excede el saldo pendiente ({$pendiente} Kg).");
            }
        }
    }

    /**
     * Calcula los valores de un detalle de préstamo individual.
     *
     * Determina: total por línea, nombre del producto, unidad y empaque.
     * Si se reciben unidad_codigo y empaque del frontend (selector de fracción),
     * esos valores se usan. Si no, se usan los del producto base.
     *
     * @param  Producto  $producto  Producto con su unidad cargada
     * @param  float  $cantidad  Cantidad prestada
     * @param  float  $precio_unitario  Precio unitario del producto
     * @param  string|null  $unidadCodigo  Código de unidad del frontend (nullable)
     * @param  float|null  $empaque  Empaque del frontend (nullable)
     * @param  array  &$totales  Array de totales acumulados (referencia)
     * @return array Detalle listo para createMany()
     */
    private function calculateDetail(
        $producto,
        $cantidad,
        $precio_unitario,
        ?string $unidadCodigo,
        ?float $empaque,
        array &$totales
    ): array {
        // Usar valores del frontend si existen, sino del producto base
        $unidadCodigoFinal = $unidadCodigo ?? ($producto->unidad->codigo ?? '');
        $unidadNombre = $producto->unidad->descripcion ?? '';
        $empaqueFinal = $empaque ?? ($producto->empaque ?? 0);
        $empaqueBase = (float) ($producto->empaque ?? 1);

        $precioCalculado = (float) $precio_unitario;

        $detalleTotal = $precioCalculado * $cantidad;
        $totales['total'] += $detalleTotal;

        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'unidad_codigo' => $unidadCodigoFinal,
            'unidad_nombre' => $unidadNombre,
            'producto_empaque' => $empaqueFinal,
            'cantidad' => $cantidad,
            'cantidad_kgm' => $cantidad * $empaqueFinal,
            'valor_unitario' => round($precioCalculado, 4),
            'total' => round($detalleTotal, 2),
        ];
    }
}
