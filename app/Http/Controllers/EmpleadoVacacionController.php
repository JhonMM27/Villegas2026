<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoVacacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class EmpleadoVacacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:empleado_vacaciones_list')->only(['index', 'dataTable']);
        $this->middleware('can:empleado_vacaciones_create')->only(['store']);
        $this->middleware('can:empleado_vacaciones_edit')->only(['update']);
        $this->middleware('can:empleado_vacaciones_delete')->only(['destroy']);
    }

    public function index()
    {
        return view('empleado-vacaciones.index');
    }

    public function dataTable(Request $request)
    {
        $query = EmpleadoVacacion::with('empleado')
            ->select(['id', 'empleado_id', 'anio_generado', 'dias_generados', 'dias_tomados', 'fecha_inicio', 'fecha_fin', 'observaciones']);

        if ($request->has('anio') && $request->anio) {
            $query->where('anio_generado', $request->anio);
        }

        if ($request->has('empleado_id') && $request->empleado_id) {
            $query->where('empleado_id', $request->empleado_id);
        }

        return DataTables::of($query)
            ->addColumn('empleado_nombre', fn ($row) => $row->empleado?->nombre ?? 'N/A')
            ->addColumn('dias_pendientes', fn ($row) => $row->dias_pendientes)
            ->addColumn('fecha_inicio_formatted', fn ($row) => $row->fecha_inicio?->format('d/m/Y') ?? '-')
            ->addColumn('fecha_fin_formatted', fn ($row) => $row->fecha_fin?->format('d/m/Y') ?? '-')
            ->addColumn('fechas', fn ($row) => $row->fecha_inicio
                ? $row->fecha_inicio->format('d/m/Y').' - '.$row->fecha_fin->format('d/m/Y')
                : '-')
            ->addColumn('action', function ($row) {
                $buttons = '';

                if (auth()->user()->can('empleado_vacaciones_edit')) {
                    $buttons .= '<button class="btn btn-sm btn-warning me-1" onclick="vacacionManager.showEditModal('.$row->id.')">
                        <i class="bi bi-pencil"></i>
                    </button>';
                }

                if (auth()->user()->can('empleado_vacaciones_delete')) {
                    $buttons .= '<button class="btn btn-sm btn-danger me-1" onclick="vacacionManager.confirmDelete('.$row->id.')">
                        <i class="bi bi-trash"></i>
                    </button>';
                }

                return '<div class="btn-group">'.$buttons.'</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit($id)
    {
        $vacacion = EmpleadoVacacion::with('empleado')->findOrFail($id);

        return response()->json([
            'empleado_id' => $vacacion->empleado_id,
            'empleado_nombre' => $vacacion->empleado?->nombre ?? '',
            'anio_generado' => $vacacion->anio_generado,
            'dias_generados' => $vacacion->dias_generados,
            'dias_tomados' => $vacacion->dias_tomados,
            'fecha_inicio' => $vacacion->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $vacacion->fecha_fin?->format('Y-m-d'),
            'observaciones' => $vacacion->observaciones,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validateData($request);
            EmpleadoVacacion::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Vacación registrada correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $errors,
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $vacacion = EmpleadoVacacion::findOrFail($id);
            $data = $this->validateData($request, $id);

            if (isset($data['fecha_inicio']) && isset($data['fecha_fin'])) {
                $diasTomados = Carbon::parse($data['fecha_inicio'])->diffInDays(Carbon::parse($data['fecha_fin'])) + 1;
                $data['dias_tomados'] = min($diasTomados, 15);
            }

            $vacacion->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Vacación actualizada correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $errors,
            ], 422);
        }
    }

    public function destroy($id)
    {
        $vacacion = EmpleadoVacacion::findOrFail($id);
        $vacacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vacación eliminada correctamente',
        ]);
    }

    public function getResumenEmpleado(Request $request, $empleadoId)
    {
        $anio = $request->get('anio');

        $query = EmpleadoVacacion::where('empleado_id', $empleadoId);

        if ($anio) {
            $query->where('anio_generado', $anio);
        }

        $vacaciones = $query->get();

        if ($anio && $vacaciones->isEmpty()) {
            return response()->json([
                'empleado_id' => $empleadoId,
                'anio' => (int) $anio,
                'total_dias_generados' => 15,
                'total_dias_tomados' => 0,
                'dias_pendientes' => 15,
                'es_nuevo_periodo' => true,
            ]);
        }

        return response()->json([
            'empleado_id' => $empleadoId,
            'anio' => $anio ? (int) $anio : null,
            'total_dias_generados' => $vacaciones->sum('dias_generados'),
            'total_dias_tomados' => $vacaciones->sum('dias_tomados'),
            'dias_pendientes' => $vacaciones->sum('dias_generados') - $vacaciones->sum('dias_tomados'),
            'es_nuevo_periodo' => false,
        ]);
    }

    public function getEmpleadosElegibles(Request $request)
    {
        $anioActual = (int) $request->get('anio', now()->year);
        $search = $request->get('q', '');

        $query = Empleado::select('id', 'nombre', 'dni', 'fecha_ingreso')
            ->where('estado', 'activo')
            ->whereNotNull('fecha_ingreso');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        $elegibles = $query->get()
            ->filter(function ($empleado) {
                $anosServicio = (int) $empleado->fecha_ingreso->diffInYears(now());

                return $anosServicio >= 1;
            })
            ->map(function ($empleado) {
                $anosServicio = (int) $empleado->fecha_ingreso->diffInYears(now());

                return [
                    'id' => $empleado->id,
                    'nombre' => $empleado->nombre,
                    'dni' => $empleado->dni,
                    'fecha_ingreso' => $empleado->fecha_ingreso->format('d/m/Y'),
                    'anos_servicio' => $anosServicio,
                ];
            })
            ->values();

        return response()->json($elegibles);
    }

    protected function validateData(Request $request, $id = null)
    {
        $rules = [
            'empleado_id' => 'required|exists:empleados,id',
            'anio_generado' => 'required|integer|min:2000|max:2100',
            'dias_generados' => 'nullable|integer|min:1|max:15',
            'dias_tomados' => 'nullable|integer|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'observaciones' => 'nullable|string',
        ];

        $data = $request->validate($rules);

        $anioGenerado = $data['anio_generado'];
        $empleadoId = $data['empleado_id'];
        $diasTomadosPropuestos = (int) ($data['dias_tomados'] ?? 0);

        $queryExistentes = EmpleadoVacacion::where('empleado_id', $empleadoId)
            ->where('anio_generado', $anioGenerado);

        if ($id) {
            $queryExistentes->where('id', '!=', $id);
        }

        $diasTomadosExistentes = (int) $queryExistentes->sum('dias_tomados');

        $maxDiasAnuales = 15;
        $diasTomadosTotal = $diasTomadosExistentes + $diasTomadosPropuestos;

        if ($diasTomadosTotal > $maxDiasAnuales) {
            $diasRestantes = $maxDiasAnuales - $diasTomadosExistentes;
            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Validation\ValidationException::withMessages([
                    'dias_tomados' => ["El empleado ya tiene {$diasTomadosExistentes} días tomados en {$anioGenerado}. Días restantes: {$diasRestantes}"],
                ])
            );
        }

        return $data;
    }
}
