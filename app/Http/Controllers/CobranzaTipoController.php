<?php

namespace App\Http\Controllers;

use App\Models\CobranzaTipo;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CobranzaTipoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:cobranza_tipos_list')->only(['index']);
        $this->middleware('can:cobranza_tipos_create')->only(['store']);
        $this->middleware('can:cobranza_tipos_edit')->only(['show', 'update']);
        $this->middleware('can:cobranza_tipos_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CobranzaTipo::select(['id', 'nombre', 'activo']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('cobranza_tipos_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('cobranza_tipos_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->nombre])->render();
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

        return view('cobranza-tipos.index');
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
        CobranzaTipo::create($data);

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
            $registro = CobranzaTipo::where('id', $id)->firstOrFail();

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
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
        $registro = CobranzaTipo::where('id', $id)->firstOrFail();
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
            $registro = CobranzaTipo::findOrFail($id);
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
            'nombre' => 'required|string|max:50',
            'activo' => 'sometimes|boolean',
        ]);
    }

    public function select(Request $request)
    {
        $query = CobranzaTipo::select('id', 'nombre')
            ->where('activo', true);

        return response()->json($query->get());
    }
}
