<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PlanillaPrestamoService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PlanillaPrestamoController extends Controller
{
    public function __construct(
        protected PlanillaPrestamoService $prestamoService
    ) {
        $this->middleware('can:planilla_prestamos_list')->only(['index', 'view', 'edit']);
        $this->middleware('can:planilla_prestamos_create')->only(['store']);
        $this->middleware('can:planilla_prestamos_edit')->only(['update']);
        $this->middleware('can:planilla_prestamos_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->prestamoService->getAll();

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<button class="btn btn-sm btn-info me-1" data-id="'.$row->id.'" onclick="window.prestamoManager.verDetalle('.$row->id.')">
                        <i class="bi bi-eye"></i>
                    </button>';

                    if (auth()->user()->can('planilla_prestamos_edit')) {
                        $buttons .= '<button class="btn btn-sm btn-warning me-1" data-id="'.$row->id.'" onclick="window.prestamoManager.showEditModal('.$row->id.')">
                            <i class="bi bi-pencil"></i>
                        </button>';
                    }

                    if (auth()->user()->can('planilla_prestamos_delete')) {
                        $buttons .= '<button class="btn btn-sm btn-danger me-1" data-id="'.$row->id.'" onclick="window.prestamoManager.confirmDelete('.$row->id.')">
                            <i class="bi bi-trash"></i>
                        </button>';
                    }

                    if ($row->estado === 'activo') {
                        $buttons .= '<button class="btn btn-sm btn-success me-1" data-id="'.$row->id.'" onclick="window.prestamoManager.mostrarPago('.$row->id.')">
                            <i class="bi bi-cash"></i>
                        </button>';
                    }

                    return '<div class="btn-group">'.$buttons.'</div>';
                })
                ->editColumn('empleado_id', fn ($row) => $row->empleado->nombre)
                ->editColumn('monto_original', fn ($row) => 'S/'.number_format((float) $row->monto_original, 2))
                ->editColumn('saldo_pendiente', fn ($row) => 'S/'.number_format((float) $row->saldo_pendiente, 2))
                ->editColumn('fecha_prestamo', fn ($row) => $row->fecha_prestamo->format('d/m/Y'))
                ->editColumn('estado', fn ($row) => match ($row->estado) {
                    'activo' => '<span class="badge bg-success">Activo</span>',
                    'pagado' => '<span class="badge bg-primary">Pagado</span>',
                    'anulado' => '<span class="badge bg-secondary">Anulado</span>',
                })
                ->rawColumns(['action', 'estado'])
                ->make(true);
        }

        return view('planilla.prestamos.index');
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validateData($request);
            $prestamo = $this->prestamoService->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Préstamo registrado correctamente',
                'prestamo_id' => $prestamo->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function edit($id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        return response()->json(['prestamo' => $prestamo]);
    }

    public function show($id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        return response()->json(['prestamo' => $prestamo]);
    }

    public function update(Request $request, $id)
    {
        $prestamo = $this->prestamoService->findById($id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        try {
            $data = $this->validateData($request, $id);
            $this->prestamoService->update($prestamo, $data);

            return response()->json([
                'success' => true,
                'message' => 'Préstamo actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $prestamo = $this->prestamoService->findById($id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        $this->prestamoService->delete($prestamo);

        return response()->json([
            'success' => true,
            'message' => 'Préstamo eliminado correctamente',
        ]);
    }

    public function view($id)
    {
        $prestamo = $this->prestamoService->findById($id);
        if (! $prestamo) {
            return response()->json(['error' => 'Préstamo no encontrado'], 404);
        }

        return view('planilla.prestamos.view', compact('prestamo'));
    }

    public function registrarPago(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'monto_pagado' => 'required|numeric|min:0.01',
                'fecha_pago' => 'required|date',
                'observaciones' => 'nullable|string',
            ]);

            $pago = $this->prestamoService->registrarPago((int) $id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'pago_id' => $pago->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getCuotas($id)
    {
        $prestamo = $this->prestamoService->findById($id);
        if (! $prestamo) {
            return response()->json(['error' => 'Préstamo no encontrado'], 404);
        }

        return response()->json($prestamo->pagos);
    }

    protected function validateData(Request $request, $id = null)
    {
        $rules = [
            'empleado_id' => 'required|exists:empleados,id',
            'monto_original' => 'required|numeric|min:0.01',
            'fecha_prestamo' => 'required|date',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:activo,pagado,anulado',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
        ];

        return $request->validate($rules);
    }
}
