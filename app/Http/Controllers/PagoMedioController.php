<?php

namespace App\Http\Controllers;

use App\Models\PagoMedio;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;

class PagoMedioController extends Controller
{
    public function __construct(){
        $this->middleware('can:pago_medios_list')->only(['index']);
        $this->middleware('can:pago_medios_create')->only(['store']);
        $this->middleware('can:pago_medios_edit')->only(['show', 'update']);
        $this->middleware('can:pago_medios_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PagoMedio::select(['id','codigo','nombre', 'descripcion', 'activo']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if(auth()->user()->can('pago_medios_edit')){
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if(auth()->user()->can('pago_medios_delete')){
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->descripcion])->render();
                    }
                    return '<div class="btn-group">' . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['action', 'activo'])
                ->editColumn('activo', function ($row) {
                    return $row->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->make(true);
        }

        return view('pago-medios.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0
        ]);
        $data = $this->validateData($request);
        PagoMedio::create($data);

        return response()->json([
            'success'=> true,
            'message'=>'Registro creado satisfactoriamente'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = PagoMedio::where('id', $id)->firstOrFail();
            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->merge([
            'activo' => $request->has('activo') ? 1 : 0
        ]);
        $data = $this->validateData($request, $id);
        $registro = PagoMedio::where('id', $id)->firstOrFail();
        $registro->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente'
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $registro = PagoMedio::findOrFail($id);
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
            /*
            'codigo' => [
                'required',
                'string',
                'max:3',
                Rule::unique('pago_medios', 'codigo')->ignore($id, 'id')
            ],*/
            'codigo' => [
                'required',
                'string',
                'max:3'
            ],
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pago_medios', 'nombre')->ignore($id, 'id')
            ],
            'descripcion' => 'nullable|string|max:70',
            'activo' => 'sometimes|boolean'
        ]);
    }

    public function select(Request $request)
    {
        $formas = PagoMedio::select('id', 'nombre')->where('activo', true)->get();
        return response()->json($formas);
    }
}
