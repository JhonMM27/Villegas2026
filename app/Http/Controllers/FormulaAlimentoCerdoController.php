<?php

namespace App\Http\Controllers;

use App\Exports\FormulaAlimentoCerdoExport;
use App\Models\FormulaAlimentoCerdo;
use App\Models\FormulaAlimentoCerdoDetalle;
use App\Models\IngredienteDatoNutricional;
use App\Services\FormulaAlimentoCerdoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class FormulaAlimentoCerdoController extends Controller
{
    public function __construct(
        protected FormulaAlimentoCerdoService $service
    ) {
        $this->middleware('can:formulas_alimento_cerdo_list')->only(['index', 'datatable', 'show', 'detalleDatatable', 'exportarExcel', 'exportarPdf']);
        $this->middleware('can:formulas_alimento_cerdo_create')->only(['store', 'detalleStore']);
        $this->middleware('can:formulas_alimento_cerdo_edit')->only(['update', 'detalleUpdate']);
        $this->middleware('can:formulas_alimento_cerdo_delete')->only(['destroy', 'detalleDestroy']);
    }

    public function index()
    {
        return view('formulas-alimento-cerdo.index');
    }

    public function datatable(Request $request)
    {
        $query = FormulaAlimentoCerdo::query()->withCount('detalles');

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $ver = '<button class="btn btn-sm btn-info btn-view-formula" data-id="'.$row->id.'" title="Ver">
                    <i class="bi bi-eye"></i>
                </button>';

                $edit = '';
                if (auth()->user()->can('formulas_alimento_cerdo_edit')) {
                    $edit = '<button class="btn btn-sm btn-primary btn-edit-formula" data-id="'.$row->id.'" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>';
                }

                $delete = '';
                if (auth()->user()->can('formulas_alimento_cerdo_delete')) {
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
        $formula = FormulaAlimentoCerdo::with('detalles.ingrediente')->findOrFail($id);
        $data = $this->service->calcular($formula);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->validateData($request);
            $detalles = $request->input('detalles', []);

            if (! empty($detalles)) {
                $formula = $this->service->createWithDetalles($data, $detalles);
            } else {
                $formula = $this->service->create($data);
            }

            $calculado = $this->service->calcular($formula);

            return response()->json(['success' => true, 'data' => $calculado]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creando formula cerdo', ['msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $formula = FormulaAlimentoCerdo::findOrFail($id);

        try {
            $data = $this->validateData($request, $id);
            $detalles = $request->input('detalles', []);

            if ($request->has('detalles')) {
                $formula = $this->service->updateWithDetalles($formula, $data, $detalles);
            } else {
                $formula = $this->service->update($formula, $data);
            }

            $calculado = $this->service->calcular($formula);

            return response()->json(['success' => true, 'data' => $calculado]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error actualizando formula cerdo', ['id' => $id, 'msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $formula = FormulaAlimentoCerdo::findOrFail($id);

        try {
            $this->service->delete($formula);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error eliminando formula cerdo', ['id' => $id, 'msg' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $uniqueRule = $id
            ? 'unique:formulas_alimento_cerdos,nombre,'.$id
            : 'unique:formulas_alimento_cerdos,nombre';

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
            Log::warning('Validacion fallo en FormulaAlimentoCerdo', [
                'id' => $id,
                'errors' => $validator->errors()->toArray(),
            ]);
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function detalleDatatable(Request $request, int $id)
    {
        $query = FormulaAlimentoCerdoDetalle::with('ingrediente')
            ->where('formula_alimento_cerdo_id', $id);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $edit = '';
                $delete = '';
                if (auth()->user()->can('formulas_alimento_cerdo_edit')) {
                    $edit = '<button class="btn btn-sm btn-primary btn-edit-detalle" data-id="'.$row->id.'" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>';
                }
                if (auth()->user()->can('formulas_alimento_cerdo_delete')) {
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
        $formula = FormulaAlimentoCerdo::findOrFail($id);

        $validated = $request->validate([
            'ingrediente_id' => 'required|exists:ingrediente_datos_nutricionales,id',
            'cantidad_kg' => 'required|numeric|min:0',
            'precio_kg' => 'required|numeric|min:0',
        ]);

        $detalle = FormulaAlimentoCerdoDetalle::updateOrCreate(
            [
                'formula_alimento_cerdo_id' => $formula->id,
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
        $detalle = FormulaAlimentoCerdoDetalle::where('formula_alimento_cerdo_id', $id)
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
        $detalle = FormulaAlimentoCerdoDetalle::where('formula_alimento_cerdo_id', $id)
            ->where('id', $detalleId)
            ->firstOrFail();
        $detalle->delete();

        return response()->json(['success' => true]);
    }

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
        $formula = FormulaAlimentoCerdo::findOrFail($id);
        $fileName = 'formula_cerdo_'.str_replace(' ', '_', $formula->nombre).'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new FormulaAlimentoCerdoExport($id), $fileName);
    }

    public function exportarPdf(int $id)
    {
        $formula = FormulaAlimentoCerdo::with('detalles.ingrediente')->findOrFail($id);
        $data = $this->service->calcular($formula);

        $pdf = Pdf::loadView('formulas-alimento-cerdo.formula-pdf', ['data' => $data])
            ->setPaper('letter', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans']);

        $fileName = 'formula_cerdo_'.str_replace(' ', '_', $formula->nombre).'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->stream($fileName);
    }
}
