<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\ProductoFraccion;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function __construct(){
        $this->middleware('can:productos_list')->only(['index','view']);
        $this->middleware('can:productos_create')->only(['store']);
        $this->middleware('can:productos_edit')->only(['show', 'update']);
        $this->middleware('can:productos_delete')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Producto::with(['afectacionTipo:codigo,descripcion',
             'unidad:codigo,descripcion', 'linea:id,nombre'])
             ->select([
                'id',
                'unidad_codigo',
                'afectacion_tipo_codigo',
                'linea_id', 
                'nombre',
                'empaque',
                'stock_almacen',
                'stock_minimo',
                'costo_unitario',
                'activo'
            ])->orderBy('id','desc');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {                 
                    $editButton ='';
                    if(auth()->user()->can('productos_edit')){
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if(auth()->user()->can('productos_delete')){
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->nombre])->render();
                    }
                    $ver='<button class="btn btn-sm btn-info btn-view-producto" data-id="'.$row->id.'" title="Ver producto">
                        <i class="bi bi-eye"></i>
                     </button>';
                    return '<div class="btn-group">' . $editButton . $deleteButton . $ver. '</div>';
                })
                ->addColumn('unidad', fn($row) => $row->unidad?->descripcion ?? '-')
                ->addColumn('afectacion', fn($row) => $row->afectacionTipo?->descripcion ?? '-')
                ->addColumn('linea', fn($row) => $row->linea?->nombre ?? '-')
                ->rawColumns(['action','activo'])
                ->editColumn('activo', function ($row) {
                    return $row->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->make(true);
        }
        return view('productos.index');
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
        try {
            DB::beginTransaction();
            $request->merge([
                'activo' => $request->has('activo') ? 1 : 0
            ]);
            $data = $this->validateData($request);

            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                //$filename = time() . '_' . $file->getClientOriginalName();
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/productos/'), $filename);
                $data['imagen'] = $filename;
            }

            $producto = Producto::create($data);
            // Insertar las fracciones
            $this->storeFracciones($producto, $request);

            DB::commit(); // ← AGREGAR

            return response()->json([
                'success'=> true,
                'message'=>'Registro creado satisfactoriamente'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack(); // ← AGREGAR
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = Producto::with([
                'fracciones' => function($query) {
                    $query->select('id', 'producto_id', 'producto_nombre', 'unidad_codigo', 'empaque', 'codigo_detalle', 'precio_lista', 'activo')
                        ->with('unidad:codigo,descripcion'); // ← Cargar relación unidad dentro de fracciones
                },
                'unidad:codigo,descripcion',
                'afectacionTipo:codigo,nombre',
                'linea:id,nombre'
            ])->findOrFail($id);
            //$registro = Producto::findOrFail($id);
            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction(); // ← AGREGAR
            
            $request->merge([
                'activo' => $request->has('activo') ? 1 : 0
            ]);
            $data = $this->validateData($request, $id);
            $registro = Producto::findOrFail($id);
            
            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');            
                //$filename = time() . '_' . $file->getClientOriginalName();
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/productos/'), $filename);
                $data['imagen'] = $filename;
                
                $old_image = 'uploads/productos/'.$registro->imagen;
                if (file_exists($old_image)) {
                    @unlink($old_image);
                }
            }
            $registro->update($data);
            // Actualizar las fracciones
            $this->storeFracciones($registro, $request, true);
            DB::commit(); // ← AGREGAR

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack(); // ← AGREGAR
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro: ' . $e->getMessage()
            ], 500);
        }        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $registro = Producto::findOrFail($id);
            $old_image = 'uploads/productos/'.$registro->imagen;
            if (file_exists($old_image)) {
                @unlink($old_image);
            }

            $registro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el registro.'
            ], 500);
        }
    }

    protected function validateData(Request $request,  $id = null)
    {
        return $request->validate([
            'unidad_codigo' => 'required|string|max:3',
            'afectacion_tipo_codigo' => 'required|string|max:2',
            'linea_id' => 'required|integer|exists:lineas,id',
            'codigo' => 'nullable|string|max:50',
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('productos', 'nombre')->ignore($id, 'id')  // usar Rule para mayor claridad
            ],
            'empaque' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string|max:255',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'stock_almacen' => 'nullable|numeric|min:0',
            'stock_minimo' => 'nullable|numeric|min:0',
            'costo_unitario' => 'nullable|numeric|min:0',
            'activo' => 'sometimes|boolean',
            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.unidad_codigo_det' => 'required|exists:unidades,codigo',
            'detalles.*.empaque' => 'required|numeric|min:0.01',
            'detalles.*.precio_lista' => 'required|numeric|min:0'
        ]);
    }

    public function buscar(Request $request)
    {
        $q = $request->input('q');
        return Producto::with('afectacionTipo:codigo,porcentaje','unidad:codigo,descripcion', 'fracciones:id,producto_id,unidad_codigo,empaque,precio_lista')
                    ->where('id', '=', $q)
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%")
                    ->select('id','codigo', 'nombre', 'costo_unitario','stock_almacen', 'afectacion_tipo_codigo', 'unidad_codigo','empaque')
                    ->limit(10)
                    ->get();
    }
    public function buscarFormulacion(Request $request) { 
        $q = $request->input('q'); 
        return Producto::with('linea:id,nombre') 
            ->where('id', '=', $q) 
            ->orWhere('nombre', 'like', "%{$q}%") 
            ->orWhere('codigo', 'like', "%{$q}%") 
            ->select('id','codigo', 'nombre', 'costo_unitario', 'empaque', 'linea_id')
            ->limit(10) ->get();
    }

    public function buscarFormulacionPreparada(Request $request)
    {
        $q = $request->input('q');

        return Producto::with('linea:id,nombre')
            ->whereHas('linea', function ($query) {
                $query->where('nombre', 'CONCENTRADO');
            })
            ->where(function ($query) use ($q) {
                $query->where('id', $q)
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%");
            })
            ->select('id', 'codigo', 'nombre', 'costo_unitario', 'empaque', 'linea_id')
            ->limit(10)
            ->get();
    }

    public function buscarProductoNucleo(Request $request)
    {
        $q = $request->input('q');

        return Producto::with('linea:id,nombre')
            ->whereHas('linea', function ($query) {
                $query->where('nombre', 'NUCLEO');
            })
            ->where(function ($query) use ($q) {
                $query->where('id', $q)
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%");
            })
            ->select('id', 'codigo', 'nombre', 'costo_unitario', 'unidad_codigo', 'empaque', 'linea_id')
            ->limit(10)
            ->get();
    }

    public function buscarProductoAditivo(Request $request)
    {
        $q = $request->input('q');

        return Producto::with('linea:id,nombre')
            ->whereHas('linea', function ($query) {
                $query->where('nombre', 'ADITIVO');
            })
            ->where(function ($query) use ($q) {
                $query->where('id', $q)
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%");
            })
            ->select('id', 'codigo', 'nombre', 'costo_unitario', 'unidad_codigo', 'empaque', 'linea_id')
            ->limit(10)
            ->get();
    }

    public function view($id)
    {
        try {
            $producto = Producto::with([
                'fracciones' => function($query) {
                    $query->select('id', 'producto_id', 'producto_nombre', 'unidad_codigo', 'empaque', 'codigo_detalle', 'precio_lista', 'activo')
                        ->with('unidad:codigo,descripcion'); // ← Cargar relación unidad dentro de fracciones
                },
                'unidad:codigo,descripcion',
                'afectacionTipo:codigo,nombre',
                'linea:id,nombre'
            ])->findOrFail($id);

            // Devolver vista parcial
            return view('productos.view', compact('producto'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }
    private function storeFracciones(Producto $producto, Request $request, bool $isUpdate = false)
    {
        // Si es actualización, eliminar fracciones anteriores
        if ($isUpdate) {
            $producto->fracciones()->delete();
        }

        // Insertar nuevas fracciones si existen en el request
        if ($request->has('detalles') && is_array($request->detalles)) {
            $codigoDetalle = 1;
            
            foreach ($request->detalles as $fraccion) {
                // Validar que tenga los campos mínimos requeridos
                if (empty($fraccion['unidad_codigo_det']) || empty($fraccion['empaque'])) {
                    continue; // Saltar fracciones incompletas
                }

                ProductoFraccion::create([
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'unidad_codigo' => $fraccion['unidad_codigo_det'], // ← CORREGIDO
                    'empaque' => $fraccion['empaque'],
                    'codigo_detalle' => $codigoDetalle,
                    'precio_lista' => $fraccion['precio_lista'] ?? 0,
                    'activo' => true,
                ]);
                
                $codigoDetalle++; 
            }
        }
    }
}