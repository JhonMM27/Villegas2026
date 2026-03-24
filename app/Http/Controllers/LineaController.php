<?php

namespace App\Http\Controllers;

use App\Models\Linea;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;

class LineaController extends Controller
{
    public function __construct(){
        $this->middleware('can:lineas_list')->only(['index']);
        $this->middleware('can:lineas_create')->only(['store']);
        $this->middleware('can:lineas_edit')->only(['show', 'update']);
        $this->middleware('can:lineas_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Linea::select(['id', 'nombre', 'activo']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if(auth()->user()->can('lineas_edit')){
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if(auth()->user()->can('lineas_delete')){
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->nombre])->render();
                    }
                    return '<div class="btn-group">' . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['action', 'activo'])
                ->editColumn('activo', function ($row) {
                    return $row->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->make(true);
        }

        return view('lineas.index');
    }

    public function store(Request $request)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0
        ]);

        $data = $this->validateData($request);
        Linea::create($data);

        return response()->json([
            'success'=> true,
            'message'=>'Registro creado satisfactoriamente'
        ]);
    }

    public function show($id)
    {
        try {
            $registro = Linea::where('id', $id)->firstOrFail();
            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0
        ]);

        $data = $this->validateData($request, $id);
        $registro = Linea::where('id', $id)->firstOrFail();
        $registro->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente'
        ]);
    }

    public function destroy($id)
    {
        try {
            $registro = Linea::findOrFail($id);
            $registro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el registro'
            ], 500);
        }
    }

    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:50', 
                Rule::unique('lineas', 'nombre')->ignore($id, 'id')
            ],
            'activo' => 'sometimes|boolean'
        ]);
    }

    public function select(Request $request)
    {
        $items = Linea::select('id', 'nombre')->where('activo', true)->get();
        return response()->json($items);
    }
}
