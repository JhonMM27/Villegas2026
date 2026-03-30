<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlanillaAdelanto;
use App\Services\EmpleadoService;
use App\Services\PlanillaAdelantoService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PlanillaAdelantoController extends Controller
{
    public function __construct(
        protected PlanillaAdelantoService $adelantoService,
        protected EmpleadoService $empleadoService
    ) {
        $this->middleware('can:planilla_adelantos_list')->only(['index', 'edit']);
        $this->middleware('can:planilla_adelantos_create')->only(['store']);
        $this->middleware('can:planilla_adelantos_edit')->only(['update']);
        $this->middleware('can:planilla_adelantos_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PlanillaAdelanto::with('empleado')
                ->select(['id', 'empleado_id', 'monto', 'fecha', 'observaciones'])
                ->orderByDesc('id');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<button class="btn btn-sm btn-info me-1" onclick="adelantoManager.verDetalle('.$row->id.')">
                        <i class="bi bi-eye"></i>
                    </button>';

                    if (auth()->user()->can('planilla_adelantos_edit')) {
                        $buttons .= '<button class="btn btn-sm btn-warning me-1" onclick="adelantoManager.showEditModal('.$row->id.')"><i class="bi bi-pencil"></i></button>';
                    }

                    if (auth()->user()->can('planilla_adelantos_delete')) {
                        $buttons .= view('components.button-delete', [
                            'id' => $row->id,
                            'texto' => $row->empleado->nombre.' - S/'.$row->monto,
                        ])->render();
                    }

                    return '<div class="btn-group">'.$buttons.'</div>';
                })
                ->editColumn('empleado_id', fn ($row) => $row->empleado->nombre)
                ->editColumn('monto', fn ($row) => 'S/'.number_format((float) $row->monto, 2))
                ->editColumn('fecha', fn ($row) => $row->fecha->format('d/m/Y'))
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('planilla.adelantos.index');
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validateData($request);
            $this->adelantoService->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Adelanto registrado correctamente',
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
        $adelanto = $this->adelantoService->findById((int) $id);
        if (! $adelanto) {
            return response()->json(['success' => false, 'message' => 'Adelanto no encontrado'], 404);
        }

        return response()->json(['adelanto' => $adelanto]);
    }

    public function show($id)
    {
        $adelanto = $this->adelantoService->findById((int) $id);
        if (! $adelanto) {
            return response()->json(['success' => false, 'message' => 'Adelanto no encontrado'], 404);
        }

        return response()->json(['adelanto' => $adelanto]);
    }

    public function update(Request $request, $id)
    {
        $adelanto = $this->adelantoService->findById($id);
        if (! $adelanto) {
            return response()->json(['success' => false, 'message' => 'Adelanto no encontrado'], 404);
        }

        try {
            $data = $this->validateData($request, $id);
            $this->adelantoService->update($adelanto, $data);

            return response()->json([
                'success' => true,
                'message' => 'Adelanto actualizado correctamente',
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
        $adelanto = $this->adelantoService->findById((int) $id);
        if (! $adelanto) {
            return response()->json(['success' => false, 'message' => 'Adelanto no encontrado'], 404);
        }

        $this->adelantoService->delete($adelanto);

        return response()->json([
            'success' => true,
            'message' => 'Adelanto eliminado correctamente',
        ]);
    }

    public function getDisponible(Request $request)
    {
        $empleadoId = $request->get('empleado_id');
        $disponible = $this->empleadoService->calcularDisponible((int) $empleadoId);

        return response()->json(['disponible' => $disponible]);
    }

    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
        ]);
    }
}
