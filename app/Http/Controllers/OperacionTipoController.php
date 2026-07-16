<?php

namespace App\Http\Controllers;

use App\Models\OperacionTipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class OperacionTipoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:operacion_tipos_list')->only(['index']);
        $this->middleware('can:operacion_tipos_create')->only(['store']);
        $this->middleware('can:operacion_tipos_edit')->only(['show', 'update']);
        $this->middleware('can:operacion_tipos_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = OperacionTipo::select(['codigo', 'descripcion', 'activo']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('operacion_tipos_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->codigo])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('operacion_tipos_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->codigo, 'texto' => $row->descripcion])->render();
                    }

                    return '<div class="btn-group">'.$editButton.$deleteButton.'</div>';
                })
                ->rawColumns(['action', 'activo'])
                ->editColumn('activo', function ($row) {
                    return $row->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->make(true);
        }

        return view('operacion-tipos.index');
    }

    public function store(Request $request)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);
        $data = $this->validateData($request);
        OperacionTipo::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro creado satisfactoriamente',
        ]);
    }

    public function show($id)
    {
        try {
            $registro = OperacionTipo::where('codigo', $id)->firstOrFail();

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);
        $data = $this->validateData($request, $id);
        $registro = OperacionTipo::where('codigo', $id)->firstOrFail();
        $registro->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente',
        ]);
    }

    public function destroy($id)
    {
        try {
            $registro = OperacionTipo::findOrFail($id);
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
                'max:4',
                Rule::unique('operacion_tipos', 'codigo')->ignore($id, 'codigo'),
            ],
            'descripcion' => 'required|string|max:150',
            'activo' => 'sometimes|boolean',
        ]);
    }

    public function select(Request $request)
    {
        $items = OperacionTipo::select('codigo', 'descripcion')->where('activo', true)->get();

        return response()->json($items);
    }
}
