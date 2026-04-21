<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlanillaInasistencia;
use App\Services\EmpleadoService;
use App\Services\PlanillaInasistenciaService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PlanillaInasistenciaController extends Controller
{
    public function __construct(
        protected PlanillaInasistenciaService $inasistenciaService,
        protected EmpleadoService $empleadoService
    ) {
        $this->middleware('can:planilla_inasistencias_list')->only(['index', 'edit', 'show']);
        $this->middleware('can:planilla_inasistencias_create')->only(['store']);
        $this->middleware('can:planilla_inasistencias_edit')->only(['update']);
        $this->middleware('can:planilla_inasistencias_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PlanillaInasistencia::with('empleado')
                ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
                ->select(['id', 'empleado_id', 'fecha', 'medio_dia', 'observacion'])
                ->orderByDesc('fecha')
                ->orderByDesc('id');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';

                    if (auth()->user()->can('planilla_inasistencias_edit')) {
                        $buttons .= '<button class="btn btn-sm btn-warning me-1" onclick="inasistenciaManager.showEditModal('.$row->id.')"><i class="bi bi-pencil"></i></button>';
                    }

                    if (auth()->user()->can('planilla_inasistencias_delete')) {
                        $buttons .= '<button class="btn btn-sm btn-danger me-1" onclick="inasistenciaManager.confirmDelete('.$row->id.')">
                            <i class="bi bi-trash"></i>
                        </button>';
                    }

                    return '<div class="btn-group">'.$buttons.'</div>';
                })
                ->editColumn('empleado_id', fn ($row) => $row->empleado->nombre)
                ->editColumn('fecha', fn ($row) => $row->fecha->format('d/m/Y'))
                ->editColumn('medio_dia', fn ($row) => $row->medio_dia
                    ? '<span class="badge bg-warning">Medio Día</span>'
                    : '<span class="badge bg-danger">Día Completo</span>')
                ->rawColumns(['action', 'medio_dia'])
                ->make(true);
        }

        return view('planilla.inasistencias.index');
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validateData($request);

            if ($this->inasistenciaService->existeInasistencia((int) $data['empleado_id'], $data['fecha'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una inasistencia registrada para este empleado en esta fecha',
                ], 422);
            }

            $this->inasistenciaService->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Inasistencia registrada correctamente',
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
        $inasistencia = $this->inasistenciaService->findById((int) $id);
        if (! $inasistencia) {
            return response()->json(['success' => false, 'message' => 'Inasistencia no encontrada'], 404);
        }

        return response()->json(['inasistencia' => $inasistencia]);
    }

    public function show($id)
    {
        $inasistencia = $this->inasistenciaService->findById((int) $id);
        if (! $inasistencia) {
            return response()->json(['success' => false, 'message' => 'Inasistencia no encontrada'], 404);
        }

        return response()->json(['inasistencia' => $inasistencia]);
    }

    public function update(Request $request, $id)
    {
        $inasistencia = $this->inasistenciaService->findById((int) $id);
        if (! $inasistencia) {
            return response()->json(['success' => false, 'message' => 'Inasistencia no encontrada'], 404);
        }

        try {
            $data = $this->validateData($request, $id);

            $fechaOriginal = $inasistencia->fecha->format('Y-m-d');
            if ($fechaOriginal !== $data['fecha']) {
                if ($this->inasistenciaService->existeInasistencia((int) $data['empleado_id'], $data['fecha'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe una inasistencia registrada para este empleado en esta fecha',
                    ], 422);
                }
            }

            $this->inasistenciaService->update($inasistencia, $data);

            return response()->json([
                'success' => true,
                'message' => 'Inasistencia actualizada correctamente',
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
        $inasistencia = $this->inasistenciaService->findById((int) $id);
        if (! $inasistencia) {
            return response()->json(['success' => false, 'message' => 'Inasistencia no encontrada'], 404);
        }

        $this->inasistenciaService->delete($inasistencia);

        return response()->json([
            'success' => true,
            'message' => 'Inasistencia eliminada correctamente',
        ]);
    }

    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha' => 'required|date',
            'medio_dia' => 'nullable|boolean',
            'observacion' => 'nullable|string|max:255',
        ]);
    }
}
