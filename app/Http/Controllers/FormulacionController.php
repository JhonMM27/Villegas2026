<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Formulacion;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class FormulacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:formulaciones_list')->only(['index', 'view', 'printTicket']);
        $this->middleware('can:formulaciones_create')->only(['store']);
        $this->middleware('can:formulaciones_edit')->only(['show', 'update']);
        $this->middleware('can:formulaciones_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Formulacion::with('detalles')
                ->select([
                    'id',
                    'fecha',
                    'producto_nombre',
                    'producto_empaque',
                    'cliente_nombre',
                    'salida_kg',
                    'activo',
                    'user_nombre',
                ])
                ->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('formulaciones_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('formulaciones_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->producto_nombre])->render();
                    }
                    $ticketButton = '<a href="'.route('formulaciones.imprimir', $row->id).'" 
                        target="_blank" 
                        class="btn btn-sm btn-secondary" 
                        title="Ver Comprobante">
                        <i class="bi bi-printer"></i>
                    </a>';
                    $ver = '<button class="btn btn-sm btn-info btn-view-formulacion" data-id="'.$row->id.'" title="Ver Formulación">
                        <i class="bi bi-eye"></i>
                    </button>';

                    return '<div class="btn-group">'.$editButton.$deleteButton.$ver.$ticketButton.'</div>';
                })
                ->addColumn('item', fn ($row) => $row->detalles->count())
                ->editColumn('fecha', function ($row) {
                    return \Carbon\Carbon::parse($row->fecha)->format('Y-m-d');
                })
                ->editColumn('activo', function ($row) {
                    return $row->activo
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->addColumn('usuario', fn ($row) => $row->user_nombre ?? '')
                ->rawColumns(['action', 'activo'])
                ->make(true);
        }

        return view('formulaciones.index');
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
        if ($request->filled('fecha')) {
            $request->merge([
                'fecha' => str_replace('T', ' ', $request->fecha).':00',
            ]);
        }

        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);

        $data = $this->validateData($request);

        DB::beginTransaction();
        try {
            $formulacionData = $this->proccessFormulacionData($data, true);

            $formulacion = Formulacion::create($formulacionData['formulacion']);
            $formulacion->detalles()->createMany($formulacionData['detalles']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'formulacion_id' => $formulacion->id,
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
            // $registro = Venta::with(['detalles.producto.afectacionTipo', 'cliente'])->findOrFail($id);
            $registro = Formulacion::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'codigo', 'nombre');
                },
                'cliente' => function ($query) {
                    $query->select('id', 'razon_social');
                },
            ])->findOrFail($id);

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Formulacion $formulacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $formulacion = Formulacion::findOrFail($id);
        // Convertir fechas antes de validar
        if ($request->filled('fecha')) {
            $request->merge([
                'fecha' => str_replace('T', ' ', $request->fecha).':00',
            ]);
        }

        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);
        $request->merge([
            'producto_id' => $request->producto_id_preparada,
        ]);
        $data = $this->validateData($request, $id);

        DB::beginTransaction();
        try {
            $formulacionData = $this->proccessFormulacionData($data, false);

            $formulacion->update($formulacionData['formulacion']);
            $formulacion->detalles()->delete();
            $formulacion->detalles()->createMany($formulacionData['detalles']);

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
            $registro = Formulacion::findOrFail($id);
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

    private function proccessFormulacionData(array $data, bool $isNew = true)
    {
        // Obtener proveedor desde el modelo
        $productoFormulacion = Producto::find($data['producto_id_preparada']);
        $data['producto_empaque'] = $producto->empaque ?? 0;

        $cliente = Cliente::find($data['cliente_id']);

        $productos = Producto::whereIn('id', collect($data['detalles'])->pluck('producto_id'))->get()->keyBy('id');

        $detallesCalculados = [];

        $totales = [ // inicializar el array
            'salida_kg' => 0,
        ];

        foreach ($data['detalles'] as $detalle) {
            $producto = $productos[$detalle['producto_id']];
            $detallesCalculados[] = $this->calculateDetail(
                $producto,
                $detalle['salida_kg'],
                $totales
            );
        }

        $formulacionData = [
            'cliente_id' => $data['cliente_id'],
            'cliente_nombre' => $cliente->razon_social ?? '',
            'fecha' => $data['fecha'] ?? now(),
            'producto_id' => $data['producto_id_preparada'] ?? null,
            'producto_nombre' => $productoFormulacion->nombre ?? '',
            'producto_empaque' => $productoFormulacion->empaque ?? 0,

            'salida_kg' => array_sum(array_column($detallesCalculados, 'salida_kg')),
        ];

        if ($isNew) {
            $formulacionData['activo'] = true;
            $formulacionData['user_id'] = auth()->id();
            $formulacionData['user_nombre'] = auth()->user()->name;
        }

        return [
            'formulacion' => $formulacionData,
            'detalles' => $detallesCalculados,
        ];
    }

    private function calculateDetail($producto, $salida_kg, array &$totales)
    {
        $totales['salida_kg'] += $salida_kg;

        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'producto_empaque' => $producto->empaque ?? 0,
            'producto_linea' => $producto->linea->nombre ?? '',
            'salida_kg' => $salida_kg,
        ];
    }

    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'fecha' => 'required|date_format:Y-m-d H:i:s',
            'producto_id_preparada' => [
                'required',
            ],
            'cliente_id' => 'required|exists:clientes,id',
            'salida_kg' => 'required|numeric',
            'activo' => 'sometimes|boolean',

            'detalles' => 'required|array|min:1', // Al menos un detalle
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.salida_saco' => 'required|numeric|min:0.0',
            'detalles.*.salida_kg' => 'required|numeric|min:0.0',
        ]);
    }

    public function printTicket($id)
    {
        $formulacion = Formulacion::findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'Consorcios Villegas E.I.R.L.',
            'direccion' => 'Cal. Inca Roca Nro. 1210 - La Victoria - Chiclayo',
            'ruc' => '20538937321',
        ];

        $pdf = Pdf::loadView('formulaciones.ticket', compact('formulacion', 'empresa'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("formulacion_{$formulacion->id}.pdf");
    }

    public function view($id)
    {
        try {
            $formulacion = Formulacion::findOrFail($id);

            // Devolver vista parcial
            return view('formulaciones.view', compact('formulacion'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function buscar(Request $request)
    {
        $q = $request->input('q');

        return Formulacion::with([
            'detalles.producto:id,costo_unitario',
        ])
            ->where('activo', true)
            ->where(function ($query) use ($q) {
                $query->where('id', '=', $q)
                    ->orWhere('producto_nombre', 'like', "%{$q}%");
            })
            ->select('id', 'producto_nombre', 'producto_empaque', 'salida_kg', 'cliente_id', 'cliente_nombre')
            ->limit(10)
            ->get();
    }
}
