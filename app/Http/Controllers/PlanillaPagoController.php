<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\EmpleadoService;
use App\Services\PlanillaPagoService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PlanillaPagoController extends Controller
{
    public function __construct(
        protected PlanillaPagoService $pagoService,
        protected EmpleadoService $empleadoService
    ) {
        $this->middleware('can:planilla_pagos_list')->only(['index']);
        $this->middleware('can:planilla_pagos_create')->only(['store', 'procesarStore']);
        $this->middleware('can:planilla_pagos_edit')->only(['show', 'update']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->pagoService->getAll();

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';
                    $buttons .= '<button class="btn btn-sm btn-secondary me-1" data-id="' . $row->id . '" onclick="window.pagoManager.verDetalle(' . $row->id . ')">
                        <i class="bi bi-eye"></i>
                    </button>';
                    if ($row->estado === 'pendiente') {
                        $buttons .= '<button class="btn btn-sm btn-primary me-1" data-id="' . $row->id . '" onclick="window.pagoManager.showEditModal(' . $row->id . ')">
                            <i class="bi bi-pencil"></i>
                        </button>';
                        $buttons .= '<button class="btn btn-sm btn-success" data-id="' . $row->id . '" onclick="window.pagoManager.marcarPagado(' . $row->id . ')">
                            <i class="bi bi-check-circle"></i>
                        </button>';
                    }

                    return '<div class="btn-group">' . $buttons . '</div>';
                })
                ->editColumn('empleado_id', fn($row) => $row->empleado->nombre)
                ->editColumn('mes', fn($row) => $this->getNombreMes($row->mes) . ' ' . $row->anio)
                ->editColumn('sueldo_base', fn($row) => 'S/' . number_format((float) $row->sueldo_base, 2))
                ->editColumn('horas_extras', fn($row) => 'S/' . number_format((float) $row->horas_extras, 2))
                ->editColumn('total_pagar', fn($row) => 'S/' . number_format((float) $row->total_pagar, 2))
                ->editColumn('estado', fn($row) => $row->estado === 'pagado'
                    ? '<span class="badge bg-primary">Pagado</span>'
                    : '<span class="badge bg-warning">Pendiente</span>')
                ->editColumn('fecha_pago', fn($row) => $row->fecha_pago
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
        if (!$pago) {
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
                'observaciones' => 'nullable|string',
            ]);

            $pago = $this->pagoService->findById((int) $id);
            if (!$pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            if ($pago->estado === 'pagado') {
                return response()->json(['success' => false, 'message' => 'No se puede editar un pago ya pagado'], 422);
            }

            $this->pagoService->update($pago, $data);

            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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
                'observaciones' => 'nullable|string',
                'principal' => 'nullable|numeric|min:0',
                'deposito' => 'nullable|numeric|min:0',
                'consorcio' => 'nullable|numeric|min:0',
            ]);

            if ($this->pagoService->existePagoMes((int) $data['empleado_id'], (int) $data['mes'], (int) $data['anio'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un pago registrado para este empleado en el mes seleccionado'
                ], 422);
            }

            $pago = $this->pagoService->procesarPago($data);

            return response()->json([
                'success' => true,
                'message' => 'Pago procesado correctamente',
                'pago_id' => $pago->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function marcarPagado(Request $request, $id)
    {
        try {
            $pago = $this->pagoService->findById((int) $id);
            if (!$pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            $this->pagoService->marcarPagado($pago);

            return response()->json([
                'success' => true,
                'message' => 'Pago marcado como pagado'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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

        $empleado = $this->empleadoService->findById($empleadoId);
        if ($empleado) {
            $sueldoPlanilla = (float) $empleado->sueldo_planilla;
            $sueldoReal = (float) $empleado->sueldo_real;
        }

        return response()->json([
            'disponible' => $disponible,
            'sueldo_planilla' => $sueldoPlanilla,
            'sueldo_real' => $sueldoReal
        ]);
    }

    private function getNombreMes(int $mes): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Setiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $meses[$mes] ?? $mes;
    }
}
