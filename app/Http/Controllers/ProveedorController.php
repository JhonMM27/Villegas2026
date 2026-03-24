<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class ProveedorController extends Controller
{
    public function __construct(){
        $this->middleware('can:proveedores_list')->only(['index', 'imprimir']);
        $this->middleware('can:proveedores_create')->only(['store']);
        $this->middleware('can:proveedores_edit')->only(['show', 'update']);
        $this->middleware('can:proveedores_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Proveedor::with('documentoTipo')
                ->select([
                    'id',
                    'documento_tipo_codigo',
                    'documento_numero as documento_numero',
                    'razon_social',
                    'direccion',
                    'telefono',
                    'email',
                    'representante',
                    'representante_telefono',
                    'cuenta_bancaria'
                ]);

            return DataTables::of($data)
                ->addColumn('documento_tipo', function ($row) {
                    // Mostrar la descripción de documentoTipo
                    return $row->documentoTipo ? $row->documentoTipo->descripcion : '';
                })
                ->addColumn('action', function ($row) {
                    $editButton ='';
                    if(auth()->user()->can('proveedores_edit')){
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if(auth()->user()->can('proveedores_delete')){
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->razon_social])->render();
                    }
                    return '<div class="btn-group">' . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('proveedores.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);        
        $registro = Proveedor::create($data);

        return response()->json([
            'success'=> true,
            'message'=>'Registro creado satisfactoriamente',
            'proveedor'=> $registro
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = Proveedor::findOrFail($id);
            // map DB column documento_numero to documento_numero for the frontend
            $data = $registro->toArray();
            if (array_key_exists('documento_numero', $data)) {
                $data['documento_numero'] = $data['documento_numero'];
            }
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $this->validateData($request, $id);
        // map incoming documento_numero to DB column documento_numero
        if (isset($data['documento_numero'])) {
            $data['documento_numero'] = $data['documento_numero'];
            unset($data['documento_numero']);
        }
        $registro = Proveedor::findOrFail($id);
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
            $registro = Proveedor::findOrFail($id);
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
            'documento_tipo_codigo' => [
                'required',
                'exists:documento_tipos,codigo',
            ],
            'documento_numero' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('proveedores', 'documento_numero')
                    ->where(function ($query) use ($request) {
                        return $query->where('documento_tipo_codigo', $request->documento_tipo_codigo);
                    })
                    ->ignore($id),
            ],
            'razon_social' => [
                'required',
                'string',
                'max:100',
                Rule::unique('proveedores', 'razon_social')
                    ->where(function ($query) use ($request) {
                        return $query->where('razon_social', $request->razon_social);
                    })
                    ->ignore($id),
            ],
            'direccion' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'representante' => 'nullable|string|max:50',
            'representante_telefono' => 'nullable|string|max:20',
            'cuenta_bancaria' => 'nullable|string|max:20',
        ]);
    }

    public function buscar(Request $request)
    {
        $q = $request->input('q');
        return Proveedor::where('id', $q)
                    ->orWhere('razon_social', 'like', "%{$q}%")
                    ->orWhere('documento_numero', 'like', "%{$q}%")
                    ->select('id', 'documento_numero as documento_numero', 'razon_social')
                    ->limit(10)
                    ->get();
    }

    public function imprimir(Request $request)
    {
        $query = Proveedor::with('documentoTipo')
                ->select(['id', 'documento_tipo_codigo', 'documento_numero','razon_social',
                'direccion', 'telefono', 'email']);

        $reportes = $query
            ->orderBy('razon_social')  // orden por proveedor
            ->get();

        $pdf = Pdf::loadView(
            'proveedores.reporte_pdf',
            compact('reportes')
        )->setPaper('letter', 'portrait')
        ->setOptions([
            'defaultFont' => 'Courier',
        ]);
        return $pdf->stream('proveedores.pdf');
    }

}
