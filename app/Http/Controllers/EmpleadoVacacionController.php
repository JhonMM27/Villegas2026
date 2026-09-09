<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\EmpleadoVacacion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

/**
 * Controlador para la gestión de vacaciones de empleados.
 *
 * Cada empleado genera 15 días de vacaciones por año de servicio.
 * Los días pueden fraccionarse en múltiples tramos/registros dentro del mismo año.
 * El campo `dias_tomados` se auto-calcula desde `fecha_inicio` y `fecha_fin`.
 */
class EmpleadoVacacionController extends Controller
{
    /** Máximo de días de vacaciones por año */
    private const MAX_DIAS_ANUALES = 15;

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

    /**
     * DataTable server-side: muestra cada tramo de vacación con los días pendientes
     * GLOBALES del año (no del registro individual).
     */
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
            ->addColumn('dias_tramo', fn ($row) => $row->dias_tomados)
            ->addColumn('dias_pendientes_anio', function ($row) {
                // Sumar todos los días tomados del mismo empleado en el mismo año
                $totalTomadosAnio = EmpleadoVacacion::where('empleado_id', $row->empleado_id)
                    ->where('anio_generado', $row->anio_generado)
                    ->sum('dias_tomados');

                return self::MAX_DIAS_ANUALES - (int) $totalTomadosAnio;
            })
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

    /**
     * Retorna los datos de un registro de vacación para edición.
     */
    public function edit($id): JsonResponse
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

    /**
     * Registra un nuevo tramo de vacación.
     * Los días tomados se auto-calculan desde fecha_inicio y fecha_fin.
     */
    public function store(Request $request): JsonResponse
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

    /**
     * Actualiza un tramo de vacación existente.
     * Los días tomados se re-calculan desde las fechas actualizadas.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $vacacion = EmpleadoVacacion::findOrFail($id);
            $data = $this->validateData($request, $id);
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

    public function destroy($id): JsonResponse
    {
        $vacacion = EmpleadoVacacion::findOrFail($id);
        $vacacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vacación eliminada correctamente',
        ]);
    }

    /**
     * Obtiene el resumen de días de vacaciones de un empleado para un año dado.
     * Soporta el parámetro `excluir_id` para excluir un registro específico
     * (útil al editar, para no contar los días del propio registro en el total).
     */
    public function getResumenEmpleado(Request $request, $empleadoId): JsonResponse
    {
        $anio = $request->get('anio');
        $excluirId = $request->get('excluir_id');

        $query = EmpleadoVacacion::where('empleado_id', $empleadoId);

        if ($anio) {
            $query->where('anio_generado', $anio);
        }

        // Excluir el registro actual al editar para no contar sus días en el total
        if ($excluirId) {
            $query->where('id', '!=', $excluirId);
        }

        $vacaciones = $query->get();

        $totalDiasTomados = (int) $vacaciones->sum('dias_tomados');
        $diasPendientes = self::MAX_DIAS_ANUALES - $totalDiasTomados;

        if ($anio && $vacaciones->isEmpty() && !$excluirId) {
            return response()->json([
                'empleado_id' => $empleadoId,
                'anio' => (int) $anio,
                'total_dias_generados' => self::MAX_DIAS_ANUALES,
                'total_dias_tomados' => 0,
                'dias_pendientes' => self::MAX_DIAS_ANUALES,
                'es_nuevo_periodo' => true,
            ]);
        }

        return response()->json([
            'empleado_id' => $empleadoId,
            'anio' => $anio ? (int) $anio : null,
            'total_dias_generados' => self::MAX_DIAS_ANUALES,
            'total_dias_tomados' => $totalDiasTomados,
            'dias_pendientes' => $diasPendientes,
            'es_nuevo_periodo' => false,
        ]);
    }

    /**
     * Retorna los empleados elegibles para vacaciones (más de 1 año de servicio).
     */
    public function getEmpleadosElegibles(Request $request): JsonResponse
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

    /**
     * Valida los datos del formulario y auto-calcula `dias_tomados` desde las fechas.
     *
     * @param Request $request  Datos del formulario
     * @param int|null $id      ID del registro al editar (null al crear)
     * @return array            Datos validados con dias_tomados calculado
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateData(Request $request, $id = null): array
    {
        $rules = [
            'empleado_id' => 'required|exists:empleados,id',
            'anio_generado' => 'required|integer|min:2000|max:2100',
            'dias_generados' => 'nullable|integer|min:1|max:15',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'observaciones' => 'nullable|string',
        ];

        $data = $request->validate($rules);

        // Auto-calcular días tomados desde las fechas (fecha_fin - fecha_inicio + 1)
        $diasTomadosCalculados = (int) Carbon::parse($data['fecha_inicio'])
            ->diffInDays(Carbon::parse($data['fecha_fin'])) + 1;

        // Limitar a máximo 15 días por tramo
        $data['dias_tomados'] = min($diasTomadosCalculados, self::MAX_DIAS_ANUALES);

        // Asegurar que dias_generados siempre sea 15
        $data['dias_generados'] = self::MAX_DIAS_ANUALES;

        // Validar contra el límite anual sumando los otros tramos del mismo año
        $anioGenerado = $data['anio_generado'];
        $empleadoId = $data['empleado_id'];

        $queryExistentes = EmpleadoVacacion::where('empleado_id', $empleadoId)
            ->where('anio_generado', $anioGenerado);

        if ($id) {
            $queryExistentes->where('id', '!=', $id);
        }

        $diasTomadosExistentes = (int) $queryExistentes->sum('dias_tomados');
        $diasTomadosTotal = $diasTomadosExistentes + $data['dias_tomados'];

        if ($diasTomadosTotal > self::MAX_DIAS_ANUALES) {
            $diasRestantes = self::MAX_DIAS_ANUALES - $diasTomadosExistentes;
            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Validation\ValidationException::withMessages([
                    'fecha_fin' => ["El tramo abarca {$data['dias_tomados']} días, pero solo quedan {$diasRestantes} días disponibles en {$anioGenerado}. (Ya usados: {$diasTomadosExistentes} días en otros tramos)"],
                ])
            );
        }

        return $data;
    }
}
