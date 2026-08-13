<?php

namespace App\Http\Controllers;

use App\Models\Nucleo;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class NucleoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:nucleos_list')->only(['index', 'view', 'printTicket']);
        $this->middleware('can:nucleos_create')->only(['store']);
        $this->middleware('can:nucleos_edit')->only(['show', 'update']);
        $this->middleware('can:nucleos_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Nucleo::select([
                'id',
                'nombre',
                'unidad_codigo',
                'unidad_nombre',
                'empaque',
                'cantidad_porcentaje',
                'items',
                'activo',
            ]);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('nucleos_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('nucleos_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->nombre])->render();
                    }
                    $ticketButton = '<a href="'.route('nucleos.imprimir', $row->id).'" 
                        target="_blank" 
                        class="btn btn-sm btn-secondary" 
                        title="Núcleo (Ticket)">
                        <i class="bi bi-printer"></i>
                    </a>';
                    $ver = '<button class="btn btn-sm btn-info btn-view-nucleo" data-id="'.$row->id.'" title="Ver Núcleo">
                        <i class="bi bi-eye"></i>
                    </button>';

                    return '<div class="btn-group">'.$editButton.$deleteButton.$ver.$ticketButton.'</div>';
                })
                ->editColumn('activo', function ($row) {
                    return $row->activo
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->rawColumns(['action', 'activo'])
                ->make(true);
        }

        return view('nucleos.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Convertir fechas antes de validar
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        $data = $this->validateData($request);

        DB::beginTransaction();
        try {
            $nucleoData = $this->proccessNucleoData($data, true);

            $nucleo = Nucleo::create($nucleoData['nucleo']);
            $nucleo->detalles()->createMany($nucleoData['detalles']);
            // Producto::updateStock(true,$nucleoData['detalles']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'nucleo_id' => $nucleo->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = Nucleo::with([
                'producto:id,activo',
                'detalles.producto:id,activo',
            ])->findOrFail($id);

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Nucleo $nucleo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $nucleo = Nucleo::findOrFail($id);

        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        $data = $this->validateData($request, $id);

        DB::beginTransaction();
        try {
            $nucleoData = $this->proccessNucleoData($data, false);

            $nucleo->update($nucleoData['nucleo']);
            // Producto::updateStock(false,$nucleo->detalles->toArray());
            $nucleo->detalles()->delete();
            $nucleo->detalles()->createMany($nucleoData['detalles']);
            // Producto::updateStock(true,$nucleoData['detalles']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado satisfactoriamente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $registro = Nucleo::findOrFail($id);
            // Producto::updateStock(false,$registro->detalles->toArray());
            $registro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el registro',
            ], 500);
        }
    }

    private function proccessNucleoData(array $data, bool $isNew = true)
    {
        // Obtener proveedor desde el modelo
        $productoNucleo = Producto::find($data['producto_id_nucleo']);

        $productos = Producto::whereIn('id', collect($data['detalles'])->pluck('producto_id'))->get()->keyBy('id');

        $detallesCalculados = [];

        $cantidadTotal = 0;

        foreach ($data['detalles'] as $detalle) {
            $producto = $productos[$detalle['producto_id']];
            $detallesCalculados[] = $this->calculateDetail(
                $producto,
                $detalle['unidad_codigo'],
                $detalle['cantidad']
            );

            $cantidadTotal += (float) $detalle['cantidad'];
        }

        $nucleoData = [
            'id' => $productoNucleo->id,
            'nombre' => $productoNucleo->nombre,
            'unidad_codigo' => $productoNucleo->unidad_codigo ?? '',
            'unidad_nombre' => $productoNucleo->unidad->descripcion ?? '',
            'empaque' => $productoNucleo->empaque ?? '',
            'cantidad_porcentaje' => $cantidadTotal,
            'items' => count($detallesCalculados),
            'activo' => (bool) $data['activo'],
        ];

        return [
            'nucleo' => $nucleoData,
            'detalles' => $detallesCalculados,
        ];
    }

    private function calculateDetail($producto, $unidad_codigo, $cantidad)
    {

        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'unidad_codigo' => $unidad_codigo,
            'cantidad' => $cantidad,
        ];
    }

    protected function validateData(Request $request, $id = null)
    {
        $productosHistoricos = $id
            ? DB::table('nucleo_detalles')
                ->where('nucleo_id', $id)
                ->pluck('producto_id')
                ->map(fn ($productoId) => (int) $productoId)
                ->all()
            : [];

        $productoNucleoActivo = Rule::exists('productos', 'id')
            ->where(function ($query) use ($id) {
                $query->where('activo', true);

                if ($id !== null) {
                    $query->orWhere('id', $id);
                }
            });

        $productoDetalleActivo = Rule::exists('productos', 'id')
            ->where(function ($query) use ($productosHistoricos) {
                $query->where('activo', true);

                if (! empty($productosHistoricos)) {
                    $query->orWhereIn('id', $productosHistoricos);
                }
            });

        return $request->validate([
            'producto_id_nucleo' => [
                'required',
                $productoNucleoActivo,
                Rule::unique('nucleos', 'id')->ignore($id),
            ],
            'activo' => 'required|boolean',

            'detalles' => 'required|array|min:1', // Al menos un detalle
            'detalles.*.producto_id' => [
                'required',
                $productoDetalleActivo,
            ],
            'detalles.*.unidad_codigo' => 'required',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ]);
    }

    public function printTicket($id)
    {
        $nucleo = Nucleo::findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'Consorcios Villegas E.I.R.L.',
            'direccion' => 'Cal. Inca Roca Nro. 1210 - La Victoria - Chiclayo',
            'ruc' => '20538937321',
        ];

        $pdf = Pdf::loadView('nucleos.ticket', compact('nucleo', 'empresa'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("nucleo_{$nucleo->id}.pdf");
    }

    public function view($id)
    {
        try {
            $nucleo = Nucleo::findOrFail($id);

            // Devolver vista parcial
            return view('nucleos.view', compact('nucleo'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function buscar(Request $request)
    {
        $q = $request->input('q');

        return Nucleo::with([
            'producto:id,costo_unitario,empaque',
            'detalles:nucleo_id,producto_id,producto_nombre,unidad_codigo,cantidad',
            'detalles.producto:id,costo_unitario,empaque',
        ])
            ->where('nucleos.activo', true)
            ->whereHas('producto', function ($query) {
                $query->where('productos.activo', true);
            })
            ->where(function ($query) use ($q) {
                $query->where('nucleos.id', $q)
                    ->orWhere('nucleos.nombre', 'like', "%{$q}%");
            })
            ->select('nucleos.id', 'nombre', 'unidad_codigo', 'unidad_nombre', 'empaque', 'cantidad_porcentaje', 'items', 'activo')
            ->limit(10)
            ->get();
    }
}
