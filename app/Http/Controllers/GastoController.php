<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class GastoController extends Controller
{
    public function __construct(){
        $this->middleware('can:gastos_list')->only(['index','view']);
        $this->middleware('can:gastos_create')->only(['store']);
        $this->middleware('can:gastos_edit')->only(['show', 'update']);
        $this->middleware('can:gastos_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Gasto::select(['id','fecha_gasto','user_nombre', 'descripcion','responsable','numero_recibo','monto']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton ='';
                    if(auth()->user()->can('gastos_edit')){
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if(auth()->user()->can('gastos_delete')){
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->numero_recibo])->render();
                    }

                    $ver='<button class="btn btn-sm btn-info btn-view-gasto" data-id="'.$row->id.'" title="Ver Gasto">
                        <i class="bi bi-eye"></i>
                     </button>';
                    // Combinar ambos botones en una cadena y devolverla
                    return '<div class="btn-group">' . $editButton . $deleteButton . $ver . '</div>';
                })
                ->make(true);
        }

        return view('gastos.index');
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
        if ($request->filled('fecha_gasto')) {
            $request->merge([
                'fecha_gasto' => str_replace('T', ' ', $request->fecha_gasto) . ':00'
            ]);
        }

        $data = $this->validateData($request);
        $data['user_id'] = auth()->id();
        $data['user_nombre'] = auth()->user()->name;

        $ultimoRecibo = Gasto::whereRaw("numero_recibo REGEXP '^[0-9]+$'")
                ->selectRaw("MAX(CAST(numero_recibo AS UNSIGNED)) as max_recibo")
                ->value('max_recibo');
        $siguienteRecibo = $ultimoRecibo ? ((int)$ultimoRecibo + 1) : 1;
        $data['numero_recibo'] = $siguienteRecibo;
        $data['monto']= $data['total_cobranza'];
        $data['importe_p']= $data['principal'];
        $data['importe_d']= $data['deposito'];
        $data['importe_c']= $data['consorcio'];

        Gasto::create($data);
        
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
            $registro = Gasto::where('id', $id)->firstOrFail();
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
        if ($request->filled('fecha_gasto')) {
            $request->merge([
                'fecha_gasto' => str_replace('T', ' ', $request->fecha_gasto) . ':00'
            ]);
        }
        $data = $this->validateData($request, $id);
        $data['monto']= $data['total_cobranza'];
        $data['importe_p']= $data['principal'];
        $data['importe_d']= $data['deposito'];
        $data['importe_c']= $data['consorcio'];
        $registro = Gasto::where('id', $id)->firstOrFail();
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
            $registro = Gasto::findOrFail($id);
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
            'descripcion'     => 'nullable|string|max:100',
            'responsable'     => 'nullable|string|max:100',
            'numero_interno'     => 'nullable|string|max:10',
            'fecha_gasto'  => 'required|date',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
            'total_cobranza' => 'nullable|numeric|min:0.01',
        ]);
    }

     public function view($id)
    {
        try {
            $gasto = Gasto::findOrFail($id);

            // Devolver vista parcial
            return view('gastos.view', compact('gasto'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }
}
