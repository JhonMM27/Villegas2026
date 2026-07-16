<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GastoTipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class GastoTipoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:gasto_tipos_list')->only(['index']);
        $this->middleware('can:gasto_tipos_create')->only(['store']);
        $this->middleware('can:gasto_tipos_edit')->only(['show', 'update']);
        $this->middleware('can:gasto_tipos_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = GastoTipo::with('categoriaGasto')->select(['id', 'nombre', 'activo', 'categoria_gasto_id']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('gasto_tipos_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('gasto_tipos_delete')) {
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

        return view('gasto-tipos.index');
    }

    public function store(Request $request)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0,
        ]);
        $data = $this->validateData($request);
        GastoTipo::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro creado satisfactoriamente',
        ]);
    }

    public function show($id)
    {
        try {
            $registro = GastoTipo::with('categoriaGasto')->where('id', $id)->firstOrFail();

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
        $registro = GastoTipo::where('id', $id)->firstOrFail();
        $registro->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente',
        ]);
    }

    public function destroy($id)
    {
        try {
            $registro = GastoTipo::findOrFail($id);
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
            'nombre' => ['required', 'string', 'max:50', Rule::unique('gasto_tipos', 'nombre')->ignore($id)],
            'activo' => 'sometimes|boolean',
            'categoria_gasto_id' => 'nullable|exists:categoria_gastos,id',
        ];

        return $request->validate($rules);
    }

    public function select(Request $request)
    {
        $query = GastoTipo::select('id', 'nombre', 'categoria_gasto_id')
            ->where('activo', true);

        if ($request->has('categoria_id') && $request->categoria_id !== '' && $request->categoria_id !== null) {
            $query->where('categoria_gasto_id', $request->categoria_id);
        }

        if ($request->has('q') && $request->q !== '') {
            $query->where('nombre', 'like', '%'.$request->q.'%');
        }

        return response()->json($query->get());
    }
}
