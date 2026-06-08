<?php

namespace App\Http\Controllers;

use App\Models\CostoTipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class CostoTipoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:costo_tipos_list')->only(['index', 'show']);
        $this->middleware('can:costo_tipos_create')->only(['store']);
        $this->middleware('can:costo_tipos_edit')->only(['update']);
        $this->middleware('can:costo_tipos_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CostoTipo::with('categoriaCosto')->select(['id', 'nombre', 'activo', 'categoria_costo_id']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('costo_tipos_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('costo_tipos_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->nombre])->render();
                    }

                    return '<div class="btn-group">'.$editButton.$deleteButton.'</div>';
                })
                ->editColumn('activo', function ($row) {
                    return $row->activo
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-secondary">Inactivo</span>';
                })
                ->rawColumns(['action', 'activo'])
                ->make(true);
        }

        return view('costo-tipos.index');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->merge(['activo' => filter_var($request->activo, FILTER_VALIDATE_BOOLEAN)]);
        $data = $this->validateData($request);
        $data['activo'] = $request->boolean('activo');

        CostoTipo::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de costo creado correctamente',
        ]);
    }

    public function show($id)
    {
        try {
            $registro = CostoTipo::with('categoriaCosto')->findOrFail($id);

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        $request->merge(['activo' => filter_var($request->activo, FILTER_VALIDATE_BOOLEAN)]);
        $data = $this->validateData($request, $id);
        $data['activo'] = $request->boolean('activo');

        $registro = CostoTipo::where('id', $id)->firstOrFail();
        $registro->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de costo actualizado correctamente',
        ]);
    }

    public function destroy($id)
    {
        try {
            $registro = CostoTipo::findOrFail($id);
            $registro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de costo eliminado correctamente',
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
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('costo_tipos', 'nombre')->ignore($id),
            ],
            'activo' => 'sometimes|boolean',
            'categoria_costo_id' => 'nullable|exists:categoria_costos,id',
        ];

        return $request->validate($rules);
    }

    public function select(Request $request)
    {
        $query = CostoTipo::select('id', 'nombre', 'categoria_costo_id')
            ->where('activo', true);

        if ($request->has('categoria_id') && $request->categoria_id !== '' && $request->categoria_id !== null) {
            $query->where('categoria_costo_id', $request->categoria_id);
        }

        if ($request->has('q') && $request->q !== '') {
            $query->where('nombre', 'like', '%'.$request->q.'%');
        }

        return response()->json($query->get());
    }
}
