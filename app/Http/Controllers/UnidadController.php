<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class UnidadController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:unidades_list')->only(['index']);
        $this->middleware('can:unidades_create')->only(['store']);
        $this->middleware('can:unidades_edit')->only(['show', 'update']);
        $this->middleware('can:unidades_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Unidad::select(['codigo', 'descripcion', 'activo']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('unidades_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->codigo])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('unidades_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->codigo, 'texto' => $row->descripcion])->render();
                    }

                    // Combinar ambos botones en una cadena y devolverla
                    return '<div class="btn-group">'.$editButton.$deleteButton.'</div>';
                })
                ->rawColumns(['action', 'activo'])
                ->editColumn('activo', function ($row) {
                    return $row->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->make(true);
        }

        return view('unidades.index');
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
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);
        $data = $this->validateData($request);
        Unidad::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro creado satisfactoriamente',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = Unidad::where('codigo', $id)->firstOrFail();

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unidad $unidad)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);
        $data = $this->validateData($request, $id);
        $registro = Unidad::where('codigo', $id)->firstOrFail();
        $registro->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente',
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $registro = Unidad::findOrFail($id);
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

    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:3',
                Rule::unique('unidades', 'codigo')->ignore($id, 'codigo'),  // usar Rule para mayor claridad
            ],
            'descripcion' => 'required|string|max:50',
            'activo' => 'sometimes|boolean',
        ]);
    }

    public function select(Request $request)
    {
        $unidades = Unidad::select('codigo', 'descripcion')->where('activo', true)->get();

        return response()->json($unidades);
    }

    public function buscar(Request $request)
    {
        $q = $request->input('q');

        return Unidad::where('codigo', 'like', "%{$q}%")
            ->orWhere('descripcion', 'like', "%{$q}%")
            ->select('codigo', 'descripcion')
            ->limit(10)
            ->get();
    }
}
