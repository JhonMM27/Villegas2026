<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Services\EmpleadoService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Yajra\DataTables\DataTables;

class EmpleadoController extends Controller
{
    public function __construct(
        protected EmpleadoService $empleadoService
    ) {
        $this->middleware('can:empleados_list')->only(['index']);
        $this->middleware('can:empleados_edit')->only(['edit']);
        $this->middleware('can:empleados_create')->only(['store']);
        $this->middleware('can:empleados_edit')->only(['update']);
        $this->middleware('can:empleados_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Empleado::with('sueldoActual')
                ->select(['id', 'nombre', 'dni', 'telefono', 'estado']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<button class="btn btn-sm btn-info me-1" onclick="empleadoManager.verDetalle('.$row->id.')">
                        <i class="bi bi-eye"></i>
                    </button>';

                    if (auth()->user()->can('empleados_edit')) {
                        $buttons .= '<button class="btn btn-sm btn-warning me-1" onclick="empleadoManager.showEditModal('.$row->id.')"><i class="bi bi-pencil"></i></button>';
                    }

                    if (auth()->user()->can('empleados_delete')) {
                        $buttons .= '<button class="btn btn-sm btn-danger me-1" onclick="empleadoManager.confirmDelete('.$row->id.')"><i class="bi bi-trash"></i></button>';
                    }

                    return '<div class="btn-group">'.$buttons.'</div>';
                })
                ->addColumn('sueldo_base', fn ($row) => 'S/'.number_format((float) $row->sueldo_base, 2))
                ->addColumn('sueldo_planilla', fn ($row) => 'S/'.number_format((float) $row->sueldo_planilla, 2))
                ->addColumn('sueldo_real', fn ($row) => 'S/'.number_format((float) $row->sueldo_real, 2))
                ->editColumn('estado', fn ($row) => $row->estado === 'activo'
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-secondary">Inactivo</span>')
                ->rawColumns(['action', 'estado'])
                ->make(true);
        }

        return view('planilla.empleados.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $this->empleadoService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Empleado registrado correctamente',
        ]);
    }

    public function update(Request $request, $id)
    {
        $empleado = $this->empleadoService->findById((int) $id);
        if (! $empleado) {
            return response()->json(['success' => false, 'message' => 'Empleado no encontrado'], 404);
        }

        $data = $this->validateData($request, $id);

        if (isset($data['estado']) && $data['estado'] === 'inactivo' && ! $empleado->fecha_salida) {
            $data['fecha_salida'] = now()->toDateString();
        }

        try {
            $this->empleadoService->update($empleado, $data);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => ['vigente_desde' => [$exception->getMessage()]],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Empleado actualizado correctamente',
        ]);
    }

    public function edit($id)
    {
        $empleado = $this->empleadoService->findById((int) $id);
        if (! $empleado) {
            return response()->json(['success' => false, 'message' => 'Empleado no encontrado'], 404);
        }

        return response()->json([
            'empleado' => [
                'id' => $empleado->id,
                'nombre' => $empleado->nombre,
                'dni' => $empleado->dni,
                'telefono' => $empleado->telefono,
                'correo' => $empleado->correo,
                'sueldo_base' => $empleado->sueldo_base,
                'sueldo_planilla' => $empleado->sueldo_planilla,
                'sueldo_real' => $empleado->sueldo_real,
                'vigente_desde' => $empleado->sueldoActual?->vigente_desde?->format('Y-m-d'),
                'estado' => $empleado->estado,
                'fecha_ingreso' => $empleado->fecha_ingreso?->format('Y-m-d'),
                'fecha_salida' => $empleado->fecha_salida?->format('Y-m-d'),
                'observaciones' => $empleado->observaciones,
            ],
        ]);
    }

    public function show($id)
    {
        $empleado = $this->empleadoService->findById((int) $id);
        if (! $empleado) {
            return response()->json(['success' => false, 'message' => 'Empleado no encontrado'], 404);
        }

        return response()->json(['empleado' => $empleado]);
    }

    public function destroy($id)
    {
        $empleado = $this->empleadoService->findById((int) $id);
        if (! $empleado) {
            return response()->json(['success' => false, 'message' => 'Empleado no encontrado'], 404);
        }

        $this->empleadoService->delete($empleado);

        return response()->json([
            'success' => true,
            'message' => 'Empleado inactivado correctamente; su historial fue conservado',
        ]);
    }

    public function buscar(Request $request)
    {
        if ($request->filled('id')) {
            $empleado = $this->empleadoService->findById((int) $request->get('id'));

            return response()->json($empleado ? [$empleado] : []);
        }

        $termino = $request->get('q', '');
        $empleados = $this->empleadoService->buscar($termino);

        return response()->json($empleados);
    }

    protected function validateData(Request $request, $id = null)
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'dni' => 'required|string|max:8|unique:empleados,dni,'.$id,
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'sueldo_base' => 'required|numeric|min:0',
            'sueldo_planilla' => 'required|numeric|min:0',
            'sueldo_real' => 'required|numeric|min:0',
            'vigente_desde' => 'required|date',
            'motivo' => 'nullable|string|max:255',
            'observaciones_sueldo' => 'nullable|string|max:1000',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:activo,inactivo',
            'fecha_ingreso' => 'nullable|date|before_or_equal:today',
            'fecha_salida' => 'nullable|date|after_or_equal:fecha_ingreso|before_or_equal:today',
        ];

        return $request->validate($rules);
    }
}
