<?php

namespace App\Http\Controllers;

use App\Exports\FormulaAlimentoExport;
use App\Models\FormulaAlimento;
use App\Models\FormulaAlimentoDetalle;
use App\Models\IngredienteDatoNutricional;
use App\Services\FormulaAlimentoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class FormulaAlimentoController extends Controller
{
    public function __construct(
        protected FormulaAlimentoService $service
    ) {
        $this->middleware('can:formulas_alimento_list')->only(['index', 'datatable', 'show', 'detalleDatatable', 'exportarExcel', 'exportarPdf']);
        $this->middleware('can:formulas_alimento_create')->only(['store', 'detalleStore']);
        $this->middleware('can:formulas_alimento_edit')->only(['update', 'detalleUpdate']);
        $this->middleware('can:formulas_alimento_delete')->only(['destroy', 'detalleDestroy']);

        $this->middleware('can:ration_datos_list')->only(['datos', 'datosDatatable', 'datosIndex', 'datosShow']);
        $this->middleware('can:ration_datos_create')->only(['datosStore']);
        $this->middleware('can:ration_datos_edit')->only(['datosUpdate']);
        $this->middleware('can:ration_datos_delete')->only(['datosDestroy']);
    }

    // =====================================================
    // Datos Nutricionales (módulo ration-formulation)
    // =====================================================

    public function datos()
    {
        return view('ration-formulation.datos');
    }

    public function datosDatatable(Request $request)
    {
        $query = IngredienteDatoNutricional::query();

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $edit = '';
                $delete = '';
                if (auth()->user()->can('ration_datos_edit')) {
                    $edit = '<button class="btn btn-sm btn-primary btn-action-edit" data-id="'.$row->id.'" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>';
                }
                if (auth()->user()->can('ration_datos_delete')) {
                    $delete = '<button class="btn btn-sm btn-danger btn-action-delete" data-id="'.$row->id.'" data-texto="'.e($row->ingrediente).'" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>';
                }

                return '<div class="btn-group">'.$edit.$delete.'</div>';
            })
            ->editColumn('ingrediente', fn ($row) => '<strong>'.e($row->ingrediente).'</strong>')
            ->editColumn('materia_seca', fn ($row) => $row->materia_seca !== null ? '<span class="text-end d-block">'.number_format((float) $row->materia_seca, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('proteina_cruda', fn ($row) => $row->proteina_cruda !== null ? '<span class="text-end d-block">'.number_format((float) $row->proteina_cruda, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('enl', fn ($row) => $row->enl !== null ? '<span class="text-end d-block">'.number_format((float) $row->enl, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('em', fn ($row) => $row->em !== null ? '<span class="text-end d-block">'.number_format((float) $row->em, 3).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('fdn', fn ($row) => $row->fdn !== null ? '<span class="text-end d-block">'.number_format((float) $row->fdn, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('fibra', fn ($row) => $row->fibra !== null ? '<span class="text-end d-block">'.number_format((float) $row->fibra, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('fda', fn ($row) => $row->fda !== null ? '<span class="text-end d-block">'.number_format((float) $row->fda, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('grasa', fn ($row) => $row->grasa !== null ? '<span class="text-end d-block">'.number_format((float) $row->grasa, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('calcio', fn ($row) => $row->calcio !== null ? '<span class="text-end d-block">'.number_format((float) $row->calcio, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('fosforo', fn ($row) => $row->fosforo !== null ? '<span class="text-end d-block">'.number_format((float) $row->fosforo, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('magnesio', fn ($row) => $row->magnesio !== null ? '<span class="text-end d-block">'.number_format((float) $row->magnesio, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('almidon', fn ($row) => $row->almidon !== null ? '<span class="text-end d-block">'.number_format((float) $row->almidon, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('azucar', fn ($row) => $row->azucar !== null ? '<span class="text-end d-block">'.number_format((float) $row->azucar, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('ceniza', fn ($row) => $row->ceniza !== null ? '<span class="text-end d-block">'.number_format((float) $row->ceniza, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('lactosa', fn ($row) => $row->lactosa !== null ? '<span class="text-end d-block">'.number_format((float) $row->lactosa, 2).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('lisina', fn ($row) => $row->lisina !== null ? '<span class="text-end d-block">'.number_format((float) $row->lisina, 3).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('metionina', fn ($row) => $row->metionina !== null ? '<span class="text-end d-block">'.number_format((float) $row->metionina, 3).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('treonina', fn ($row) => $row->treonina !== null ? '<span class="text-end d-block">'.number_format((float) $row->treonina, 3).'</span>' : '<span class="text-end d-block text-muted">—</span>')
            ->editColumn('activo', function ($row) {
                $color = $row->activo ? 'success' : 'secondary';
                $texto = $row->activo ? 'Activo' : 'Inactivo';

                return '<span class="badge bg-'.$color.'">'.$texto.'</span>';
            })
            ->rawColumns(['action', 'ingrediente', 'materia_seca', 'proteina_cruda', 'enl', 'em',
                'fdn', 'fibra', 'fda', 'grasa', 'calcio', 'fosforo', 'magnesio',
                'almidon', 'azucar', 'ceniza', 'lactosa',
                'lisina', 'metionina', 'treonina', 'activo'])
            ->make(true);
    }

    public function datosIndex(): JsonResponse
    {
        $items = IngredienteDatoNutricional::where('activo', true)
            ->orderBy('ingrediente')
            ->orderBy('procedencia')
            ->get(['id', 'ingrediente', 'procedencia', 'clasificacion']);

        return response()->json($items);
    }

    public function datosShow(int $id): JsonResponse
    {
        $dato = IngredienteDatoNutricional::findOrFail($id);

        return response()->json(['success' => true, 'data' => $dato]);
    }

    public function datosStore(Request $request): JsonResponse
    {
        try {
            $data = $this->validateDato($request);
            $dato = IngredienteDatoNutricional::create($data);

            return response()->json(['success' => true, 'data' => $dato]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creando dato nutricional', ['msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function datosUpdate(Request $request, int $id): JsonResponse
    {
        $dato = IngredienteDatoNutricional::findOrFail($id);

        try {
            $data = $this->validateDato($request, $id);
            $dato->update($data);

            return response()->json(['success' => true, 'data' => $dato]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error actualizando dato nutricional', ['id' => $id, 'msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function datosDestroy(int $id): JsonResponse
    {
        $dato = IngredienteDatoNutricional::findOrFail($id);

        try {
            if ($dato->detallesFormula()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: el dato nutricional está siendo usado por una o más fórmulas.',
                ], 422);
            }
            $dato->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error eliminando dato nutricional', ['id' => $id, 'msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function validateDato(Request $request, ?int $id = null): array
    {
        $validator = Validator::make($request->all(), [
            'ingrediente' => 'required|string|max:100',
            'procedencia' => 'nullable|string|max:50',
            'clasificacion' => 'nullable|string|max:50',
            'nutriente' => 'nullable|string|max:100',
            'materia_seca' => 'nullable|numeric|min:0|max:100',
            'proteina_cruda' => 'nullable|numeric|min:0',
            'enl' => 'nullable|numeric|min:0',
            'em' => 'nullable|numeric|min:0',
            'fdn' => 'nullable|numeric|min:0|max:100',
            'fibra' => 'nullable|numeric|min:0|max:100',
            'fda' => 'nullable|numeric|min:0|max:100',
            'grasa' => 'nullable|numeric|min:0|max:100',
            'calcio' => 'nullable|numeric|min:0|max:100',
            'fosforo' => 'nullable|numeric|min:0|max:100',
            'magnesio' => 'nullable|numeric|min:0|max:100',
            'almidon' => 'nullable|numeric|min:0|max:100',
            'azucar' => 'nullable|numeric|min:0|max:100',
            'ceniza' => 'nullable|numeric|min:0|max:100',
            'lactosa' => 'nullable|numeric|min:0|max:100',
            'lisina' => 'nullable|numeric|min:0',
            'metionina' => 'nullable|numeric|min:0',
            'treonina' => 'nullable|numeric|min:0',
            'activo' => 'nullable',
        ]);

        if ($validator->fails()) {
            Log::warning('Validacion fallo en DatoNutricional', [
                'id' => $id,
                'errors' => $validator->errors()->toArray(),
            ]);
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $data['activo'] = $request->boolean('activo');

        return $data;
    }

    public function index()
    {
        return view('formulas-alimento.index');
    }

    public function datatable(Request $request)
    {
        $query = FormulaAlimento::query()->withCount('detalles');

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $ver = '<button class="btn btn-sm btn-info btn-view-formula" data-id="'.$row->id.'" title="Ver">
                    <i class="bi bi-eye"></i>
                </button>';

                $edit = '';
                if (auth()->user()->can('formulas_alimento_edit')) {
                    $edit = '<button class="btn btn-sm btn-primary btn-edit-formula" data-id="'.$row->id.'" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>';
                }

                $delete = '';
                if (auth()->user()->can('formulas_alimento_delete')) {
                    $delete = '<button class="btn btn-sm btn-danger btn-delete-formula" data-id="'.$row->id.'" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>';
                }

                $export = '<button class="btn btn-sm btn-warning btn-export-formula" data-id="'.$row->id.'" data-nombre="'.e($row->nombre).'" title="Exportar">
                    <i class="bi bi-box-arrow-up-right"></i>
                </button>';

                return '<div class="btn-group">'.$ver.$edit.$delete.$export.'</div>';
            })
            ->editColumn('activo', function ($row) {
                $color = $row->activo ? 'success' : 'secondary';
                $texto = $row->activo ? 'Activa' : 'Inactiva';

                return '<span class="badge bg-'.$color.'">'.$texto.'</span>';
            })
            ->editColumn('precio_venta', fn ($row) => '<span class="text-end d-block">'.number_format((float) $row->precio_venta, 2).'</span>')
            ->editColumn('detalles_count', fn ($row) => '<span class="badge bg-secondary">'.$row->detalles_count.'</span>')
            ->editColumn('fecha', fn ($row) => $row->fecha ? $row->fecha->format('d/m/Y') : '—')
            ->rawColumns(['action', 'activo', 'precio_venta', 'detalles_count', 'fecha'])
            ->make(true);
    }

    public function show(int $id): JsonResponse
    {
        $formula = FormulaAlimento::with('detalles.ingrediente')->findOrFail($id);
        $data = $this->service->calcular($formula);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->validateData($request);
            $detalles = $request->input('detalles', []);

            // Si vienen detalles, crear fórmula + ingredientes en una transacción
            if (! empty($detalles)) {
                $formula = $this->service->createWithDetalles($data, $detalles);
            } else {
                $formula = $this->service->create($data);
            }

            // Recalcular para devolver datos completos
            $calculado = $this->service->calcular($formula);

            return response()->json(['success' => true, 'data' => $calculado]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creando formula', ['msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $formula = FormulaAlimento::findOrFail($id);

        try {
            $data = $this->validateData($request, $id);
            $detalles = $request->input('detalles', []);

            // Si vienen detalles, actualizar fórmula + sincronizar ingredientes
            if ($request->has('detalles')) {
                $formula = $this->service->updateWithDetalles($formula, $data, $detalles);
            } else {
                $formula = $this->service->update($formula, $data);
            }

            // Recalcular para devolver datos completos
            $calculado = $this->service->calcular($formula);

            return response()->json(['success' => true, 'data' => $calculado]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error actualizando formula', ['id' => $id, 'msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $formula = FormulaAlimento::findOrFail($id);

        try {
            $this->service->delete($formula);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error eliminando formula', ['id' => $id, 'msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $uniqueRule = $id
            ? 'unique:formulas_alimento,nombre,'.$id
            : 'unique:formulas_alimento,nombre';

        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:100', $uniqueRule],
            'descripcion' => 'nullable|string',
            'fecha' => 'nullable|date',
            'activo' => 'nullable',
            'kg_saco' => 'required|numeric|min:0.01',
            'saco_vacio' => 'required|numeric|min:0',
            'mano_obra' => 'required|numeric|min:0',
            'energia' => 'required|numeric|min:0',
            'merma' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::warning('Validacion fallo en FormulaAlimento', [
                'id' => $id,
                'errors' => $validator->errors()->toArray(),
            ]);
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    // =====================================================
    // Detalles (ingredientes de la fórmula)
    // =====================================================

    public function detalleDatatable(Request $request, int $id)
    {
        $query = FormulaAlimentoDetalle::with('ingrediente')
            ->where('formula_alimento_id', $id);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $edit = '';
                $delete = '';
                if (auth()->user()->can('formulas_alimento_edit')) {
                    $edit = '<button class="btn btn-sm btn-primary btn-edit-detalle" data-id="'.$row->id.'" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>';
                }
                if (auth()->user()->can('formulas_alimento_delete')) {
                    $delete = '<button class="btn btn-sm btn-danger btn-delete-detalle" data-id="'.$row->id.'" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>';
                }

                return '<div class="btn-group">'.$edit.$delete.'</div>';
            })
            ->addColumn('ingrediente_nombre', fn ($row) => '<strong>'.($row->ingrediente->ingrediente ?? '—').'</strong>')
            ->addColumn('clasificacion', fn ($row) => '<small class="text-muted">'.($row->ingrediente->clasificacion ?? '—').'</small>')
            ->addColumn('procedencia', fn ($row) => '<span class="badge bg-secondary">'.($row->ingrediente->procedencia ?? '—').'</span>')
            ->addColumn('precio_kg', fn ($row) => '<span class="text-end d-block">'.number_format((float) $row->precio_kg, 4).'</span>')
            ->addColumn('cantidad_kg', fn ($row) => '<span class="text-end d-block fw-bold">'.number_format((float) $row->cantidad_kg, 4).'</span>')
            ->addColumn('costo', fn ($row) => '<span class="text-end d-block fw-bold">'.number_format((float) $row->cantidad_kg * (float) $row->precio_kg, 4).'</span>')
            ->rawColumns(['action', 'ingrediente_nombre', 'clasificacion', 'procedencia', 'precio_kg', 'cantidad_kg', 'costo'])
            ->make(true);
    }

    public function detalleStore(Request $request, int $id): JsonResponse
    {
        $formula = FormulaAlimento::findOrFail($id);

        $validated = $request->validate([
            'ingrediente_id' => 'required|exists:ingrediente_datos_nutricionales,id',
            'cantidad_kg' => 'required|numeric|min:0',
            'precio_kg' => 'required|numeric|min:0',
        ]);

        $detalle = FormulaAlimentoDetalle::updateOrCreate(
            [
                'formula_alimento_id' => $formula->id,
                'ingrediente_id' => $validated['ingrediente_id'],
            ],
            [
                'cantidad_kg' => $validated['cantidad_kg'],
                'precio_kg' => $validated['precio_kg'],
            ]
        );

        return response()->json(['success' => true, 'data' => $detalle->load('ingrediente')]);
    }

    public function detalleUpdate(Request $request, int $id, int $detalleId): JsonResponse
    {
        $detalle = FormulaAlimentoDetalle::where('formula_alimento_id', $id)
            ->where('id', $detalleId)
            ->firstOrFail();

        $validated = $request->validate([
            'cantidad_kg' => 'required|numeric|min:0',
            'precio_kg' => 'required|numeric|min:0',
        ]);

        $detalle->update($validated);

        return response()->json(['success' => true, 'data' => $detalle->load('ingrediente')]);
    }

    public function detalleDestroy(int $id, int $detalleId): JsonResponse
    {
        $detalle = FormulaAlimentoDetalle::where('formula_alimento_id', $id)
            ->where('id', $detalleId)
            ->firstOrFail();
        $detalle->delete();

        return response()->json(['success' => true]);
    }

    // =====================================================
    // Live search de ingredientes (para el modal de agregar detalle)
    // =====================================================

    public function ingredientesBuscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $query = IngredienteDatoNutricional::query()->where('activo', true);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('ingrediente', 'like', "%{$q}%")
                    ->orWhere('clasificacion', 'like', "%{$q}%")
                    ->orWhere('procedencia', 'like', "%{$q}%");
            });
        }

        $items = $query->orderBy('ingrediente')->orderBy('procedencia')->limit(30)->get();

        return response()->json($items);
    }

    public function exportarExcel(int $id)
    {
        $formula = FormulaAlimento::findOrFail($id);
        $fileName = 'formula_vacuno_'.str_replace(' ', '_', $formula->nombre).'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new FormulaAlimentoExport($id), $fileName);
    }

    public function exportarPdf(int $id)
    {
        $formula = FormulaAlimento::with('detalles.ingrediente')->findOrFail($id);
        $data = $this->service->calcular($formula);

        $pdf = Pdf::loadView('formulas-alimento.formula-pdf', ['data' => $data])
            ->setPaper('letter', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans']);

        $fileName = 'formula_vacuno_'.str_replace(' ', '_', $formula->nombre).'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->stream($fileName);
    }
}
