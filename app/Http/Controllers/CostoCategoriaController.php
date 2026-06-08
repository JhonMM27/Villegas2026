<?php

namespace App\Http\Controllers;

use App\Models\CostoCategoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class CostoCategoriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:costo_categorias_list')->only(['index']);
        $this->middleware('can:costo_categorias_create')->only(['store']);
        $this->middleware('can:costo_categorias_edit')->only(['show', 'update']);
        $this->middleware('can:costo_categorias_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CostoCategoria::select(['id', 'nombre', 'activo']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('costo_categorias_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('costo_categorias_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->nombre])->render();
                    }

                    return '<div class="btn-group">'.$editButton.$deleteButton.'</div>';
                })
                ->rawColumns(['action', 'activo'])
                ->editColumn('activo', function ($row) {
                    return $row->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->make(true);
        }

        return view('costo-categorias.index');
    }

    public function store(Request $request)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);
        $data = $this->validateData($request);
        CostoCategoria::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro creado satisfactoriamente',
        ]);
    }

    public function show($id)
    {
        try {
            $registro = CostoCategoria::where('id', $id)->firstOrFail();

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
        $registro = CostoCategoria::where('id', $id)->firstOrFail();
        $registro->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente',
        ]);
    }

    public function destroy($id)
    {
        try {
            $registro = CostoCategoria::findOrFail($id);
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
        $rules = [
            'nombre' => ['required', 'string', 'max:50', Rule::unique('categoria_costos', 'nombre')->ignore($id)],
            'activo' => 'sometimes|boolean',
        ];

        return $request->validate($rules);
    }

    public function select(Request $request)
    {
        $query = CostoCategoria::select('id', 'nombre')
            ->where('activo', true);

        return response()->json($query->get());
    }
}
