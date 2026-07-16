<?php

namespace App\Http\Controllers;

use App\Models\PagoForma;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class PagoFormaController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:pago_formas_list')->only(['index']);
        $this->middleware('can:pago_formas_create')->only(['store']);
        $this->middleware('can:pago_formas_edit')->only(['show', 'update']);
        $this->middleware('can:pago_formas_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PagoForma::select(['codigo', 'descripcion', 'dias', 'activo']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('pago_formas_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->codigo])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('pago_formas_delete')) {
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

        return view('pago-formas.index');
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
        PagoForma::create($data);

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
            $registro = PagoForma::where('codigo', $id)->firstOrFail();

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
            'activo' => $request->has('activo') ? 1 : 0,
        ]);
        $data = $this->validateData($request, $id);
        $registro = PagoForma::where('codigo', $id)->firstOrFail();
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
            $registro = PagoForma::findOrFail($id);
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
                'max:2',
                Rule::unique('pago_formas', 'codigo')->ignore($id, 'codigo'),
            ],
            'descripcion' => 'required|string|max:100',
            'dias' => 'required|integer|min:0',
            'activo' => 'sometimes|boolean',
        ]);
    }

    public function select(Request $request)
    {
        $formas = PagoForma::select('codigo', 'descripcion', 'dias')->where('activo', true)->get();

        return response()->json($formas);
    }
}
