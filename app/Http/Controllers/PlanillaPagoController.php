<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EmpleadoService;
use App\Services\PlanillaAsistenciaService;
use App\Services\PlanillaPagoService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PlanillaPagoController extends Controller
{
    public function __construct(
        protected PlanillaPagoService $pagoService,
        protected EmpleadoService $empleadoService,
        protected PlanillaAsistenciaService $asistenciaService
    ) {
        $this->middleware('can:planilla_pagos_list')->only(['index']);
        $this->middleware('can:planilla_pagos_create')->only(['store', 'procesarStore']);
        $this->middleware('can:planilla_pagos_edit')->only(['show', 'update']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $mes = $request->get('mes');
            $anio = $request->get('anio');

            if ($mes && $anio) {
                $data = $this->pagoService->getByMes((int) $mes, (int) $anio);
            } else {
                $data = $this->pagoService->getAll();
            }

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';
                    $buttons .= '<button class="btn btn-sm btn-secondary me-1" data-id="'.$row->id.'" onclick="window.pagoManager.verDetalle('.$row->id.')">
                        <i class="bi bi-eye"></i>
                    </button>';
                    if ($row->estado === 'pendiente') {
                        $buttons .= '<button class="btn btn-sm btn-primary me-1" data-id="'.$row->id.'" onclick="window.pagoManager.showEditModal('.$row->id.')">
                            <i class="bi bi-pencil"></i>
                        </button>';
                        $buttons .= '<button class="btn btn-sm btn-success" data-id="'.$row->id.'" onclick="window.pagoManager.marcarPagado('.$row->id.')">
                            <i class="bi bi-check-circle"></i>
                        </button>';
                    } elseif ($row->estado === 'pagado') {
                        $buttons .= '<button class="btn btn-sm btn-warning" data-id="'.$row->id.'" onclick="window.pagoManager.revertirPago('.$row->id.')">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>';
                    }

                    return '<div class="btn-group">'.$buttons.'</div>';
                })
                ->editColumn('empleado_id', fn ($row) => $row->empleado->nombre)
                ->editColumn('mes', fn ($row) => $this->getNombreMes($row->mes).' '.$row->anio)
                ->editColumn('sueldo_base', fn ($row) => 'S/'.number_format((float) $row->sueldo_base, 2))
                ->editColumn('horas_extras', fn ($row) => 'S/'.number_format((float) $row->horas_extras, 2))
                ->editColumn('total_pagar', fn ($row) => 'S/'.number_format((float) $row->total_pagar, 2))
                ->editColumn('estado', fn ($row) => $row->estado === 'pagado'
                    ? '<span class="badge bg-primary">Pagado</span>'
                    : '<span class="badge bg-warning">Pendiente</span>')
                ->editColumn('fecha_pago', fn ($row) => $row->fecha_pago
                    ? $row->fecha_pago->format('d/m/Y')
                    : '-')
                ->rawColumns(['action', 'estado'])
                ->make(true);
        }

        return view('planilla.pagos.index');
    }

    public function store(Request $request)
    {
        return $this->procesarStore($request);
    }

    public function show($id)
    {
        $pago = $this->pagoService->findById((int) $id);
        if (! $pago) {
            return response()->json(['error' => 'Pago no encontrado'], 404);
        }

        $pago->load('empleado');

        return response()->json($pago);
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'horas_extras' => 'nullable|numeric|min:0',
                'dias_faltados' => 'nullable|numeric|min:0|max:30',
                'cts_planilla' => 'nullable|numeric|min:0',
                'cts_sueldo_real' => 'nullable|numeric|min:0',
                'observaciones' => 'nullable|string',
                'principal' => 'nullable|numeric|min:0',
                'deposito' => 'nullable|numeric|min:0',
                'consorcio' => 'nullable|numeric|min:0',
            ]);

            $pago = $this->pagoService->findById((int) $id);
            if (! $pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            if ($pago->estado === 'pagado') {
                return response()->json(['success' => false, 'message' => 'No se puede editar un pago ya pagado'], 422);
            }

            $this->pagoService->update($pago, $data);

            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function procesarStore(Request $request)
    {
        try {
            $data = $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'mes' => 'required|integer|min:1|max:12',
                'anio' => 'required|integer|min:2020',
                'horas_extras' => 'nullable|numeric|min:0',
                'dias_faltados' => 'nullable|numeric|min:0|max:30',
                'cts_planilla' => 'nullable|numeric|min:0',
                'cts_sueldo_real' => 'nullable|numeric|min:0',
                'observaciones' => 'nullable|string',
                'principal' => 'nullable|numeric|min:0',
                'deposito' => 'nullable|numeric|min:0',
                'consorcio' => 'nullable|numeric|min:0',
            ]);

            if ($this->pagoService->existePagoMes((int) $data['empleado_id'], (int) $data['mes'], (int) $data['anio'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un pago registrado para este empleado en el mes seleccionado',
                ], 422);
            }

            $pago = $this->pagoService->procesarPago($data);

            return response()->json([
                'success' => true,
                'message' => 'Pago procesado correctamente',
                'pago_id' => $pago->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function marcarPagado(Request $request, $id)
    {
        try {
            $pago = $this->pagoService->findById((int) $id);
            if (! $pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            $this->pagoService->marcarPagado($pago);

            return response()->json([
                'success' => true,
                'message' => 'Pago marcado como pagado',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function revertirPago(Request $request, $id)
    {
        try {
            $pago = $this->pagoService->findById((int) $id);
            if (! $pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            if ($pago->estado !== 'pagado') {
                return response()->json(['success' => false, 'message' => 'Solo se pueden revertir pagos confirmados'], 422);
            }

            $this->pagoService->revertirPago($pago);

            return response()->json([
                'success' => true,
                'message' => 'Pago revertedo a pendiente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getDisponibleEmpleado(Request $request)
    {
        $empleadoId = (int) $request->get('empleado_id');
        $mes = (int) $request->get('mes', date('m'));
        $anio = (int) $request->get('anio', date('Y'));

        $disponible = $this->empleadoService->calcularDisponible($empleadoId, $mes, $anio);
        $sueldoPlanilla = 0;
        $sueldoReal = 0;
        $diasFaltados = 0;
        $descuentoFaltas = 0;

        $empleado = $this->empleadoService->findById($empleadoId);
        if ($empleado) {
            $sueldoPlanilla = (float) $empleado->sueldo_planilla;
            $sueldoReal = (float) $empleado->sueldo_real;

            $asistencia = $this->asistenciaService->getByEmpleadoMes($empleadoId, $mes, $anio);
            if ($asistencia) {
                $diasFaltados = (float) $asistencia->dias_faltados;
                $descuentoFaltas = $this->asistenciaService->calcularDescuentoFaltas($sueldoReal, $diasFaltados);
            }
        }

        return response()->json([
            'disponible' => $disponible,
            'sueldo_planilla' => $sueldoPlanilla,
            'sueldo_real' => $sueldoReal,
            'dias_faltados' => $diasFaltados,
            'descuento_faltas' => $descuentoFaltas,
        ]);
    }

    public function guardarFaltas(Request $request)
    {
        try {
            $data = $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'mes' => 'required|integer|min:1|max:12',
                'anio' => 'required|integer|min:2020',
                'dias_faltados' => 'required|numeric|min:0|max:30',
            ]);

            $asistencia = $this->asistenciaService->createOrUpdate(
                (int) $data['empleado_id'],
                (int) $data['mes'],
                (int) $data['anio'],
                (float) $data['dias_faltados']
            );

            $empleado = $this->empleadoService->findById((int) $data['empleado_id']);
            $descuentoFaltas = 0;
            if ($empleado) {
                $descuentoFaltas = $this->asistenciaService->calcularDescuentoFaltas(
                    (float) $empleado->sueldo_real,
                    (float) $data['dias_faltados']
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Faltas guardadas correctamente',
                'dias_faltados' => $asistencia->dias_faltados,
                'descuento_faltas' => $descuentoFaltas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function generarPagosMes(Request $request)
    {
        try {
            $mes = (int) $request->get('mes');
            $anio = (int) $request->get('anio');

            $anioActual = (int) date('Y');
            $mesActual = (int) date('n');

            if ($anio < 2026 || ($anio === 2026 && $mes < 4)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sistema no iniciado para este período',
                ], 422);
            }

            if ($this->pagoService->existenPagosDelMes($mes, $anio)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existen pagos generados para este mes',
                ], 422);
            }

            $cantidad = $this->pagoService->generarPagosDelMes($mes, $anio);

            return response()->json([
                'success' => true,
                'message' => "Se generaron {$cantidad} pagos para {$this->getNombreMes($mes)} {$anio}",
                'cantidad' => $cantidad,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmarPagosMes(Request $request)
    {
        try {
            $mes = (int) $request->get('mes');
            $anio = (int) $request->get('anio');

            $cantidad = $this->pagoService->confirmarPagosDelMes($mes, $anio);

            return response()->json([
                'success' => true,
                'message' => "Se confirmaron {$cantidad} pagos para {$this->getNombreMes($mes)} {$anio}",
                'cantidad' => $cantidad,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function revertirPagosMes(Request $request)
    {
        try {
            $mes = (int) $request->get('mes');
            $anio = (int) $request->get('anio');

            $cantidad = $this->pagoService->revertirPagosDelMes($mes, $anio);

            return response()->json([
                'success' => true,
                'message' => "Se revirtieron {$cantidad} pagos para {$this->getNombreMes($mes)} {$anio}",
                'cantidad' => $cantidad,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function estadoPagosMes(Request $request)
    {
        $mes = (int) $request->get('mes');
        $anio = (int) $request->get('anio');

        $existe = $this->pagoService->existenPagosDelMes($mes, $anio);

        if (! $existe) {
            return response()->json([
                'existe' => false,
                'mensaje' => 'Pagos no generados',
            ]);
        }

        $cantidades = $this->pagoService->getCantidadPagosDelMes($mes, $anio);

        return response()->json([
            'existe' => true,
            'total' => $cantidades['total'],
            'pendientes' => $cantidades['pendientes'],
            'pagados' => $cantidades['pagados'],
            'mensaje' => $cantidades['pendientes'] > 0
                ? "{$cantidades['pendientes']} pagos pendientes de {$cantidades['total']}"
                : 'Todos los pagos confirmados',
        ]);
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
}
