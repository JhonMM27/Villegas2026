<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class ClienteController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:clientes_list')->only(['index', 'imprimir']);
        $this->middleware('can:clientes_create')->only(['store']);
        $this->middleware('can:clientes_edit')->only(['show', 'update']);
        $this->middleware('can:clientes_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Cliente::with('documentoTipo')
                ->select(['id', 'documento_tipo_codigo', 'documento_numero', 'razon_social',
                    'direccion', 'telefono', 'email']);

            return DataTables::of($data)
                ->addColumn('documento_tipo', function ($row) {
                    // Mostrar la descripción de documentoTipo
                    return $row->documentoTipo ? $row->documentoTipo->descripcion : '';
                })
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('clientes_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('clientes_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->razon_social])->render();
                    }

                    // Combinar ambos botones en una cadena y devolverla
                    return '<div class="btn-group">'.$editButton.$deleteButton.'</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('clientes.index');
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
        $data = $this->validateData($request);
        $data['saldo_credito'] = $request->saldo_credito ?? 0;
        $registro = Cliente::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro creado satisfactoriamente',
            'cliente' => $registro,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = Cliente::findOrFail($id);

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cliente $cliente)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $this->validateData($request, $id);
        $registro = Cliente::findOrFail($id);
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
            $registro = Cliente::findOrFail($id);
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
            'documento_tipo_codigo' => [
                'required',
                'exists:documento_tipos,codigo',
            ],
            'documento_numero' => [
                'nullable',
                'string',
                'max:20',
                // única combinación por tipo de documento (excepto en update)
                Rule::unique('clientes')
                    ->where(function ($query) use ($request) {
                        return $query->where('documento_tipo_codigo', $request->documento_tipo_codigo);
                    })
                    ->ignore($id),
            ],
            'razon_social' => [
                'required',
                'string',
                'max:100',
                Rule::unique('clientes', 'razon_social')
                    ->where(function ($query) use ($request) {
                        return $query->where('razon_social', $request->razon_social);
                    })
                    ->ignore($id),
            ],
            'direccion' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'saldo_credito' => 'nullable|numeric|min:0',
        ]);
    }

    public function buscar(Request $request)
    {
        $q = $request->input('q');
        $clienteIdsParam = $request->input('cliente_ids');

        $query = Cliente::query()->select('id', 'documento_numero', 'razon_social');

        if (! empty($clienteIdsParam)) {
            $ids = is_array($clienteIdsParam) ? $clienteIdsParam : explode(',', $clienteIdsParam);
            $query->whereIn('id', array_map('intval', $ids));
        } elseif ($q) {
            if (is_numeric($q)) {
                $query->where('id', $q);
            } else {
                $query->where(function ($inner) use ($q) {
                    $inner->where('razon_social', 'like', "%{$q}%")
                        ->orWhere('documento_numero', 'like', "%{$q}%");
                });
            }
        }

        return $query->limit(20)->get();
    }

    /*
    public function exportar(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $fileName = 'compras_detalladas_proveedor_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ComprasDetalladasProveedorExport($fechaInicio, $fechaFin),
            $fileName
        );
    }
    */
    public function imprimir(Request $request)
    {
        $query = Cliente::with('documentoTipo')
            ->select(['id', 'documento_tipo_codigo', 'documento_numero', 'razon_social',
                'direccion', 'telefono', 'email']);

        $reportes = $query
            ->orderBy('razon_social')  // orden por proveedor
            ->get();

        $pdf = Pdf::loadView(
            'clientes.reporte_pdf',
            compact('reportes')
        )->setPaper('letter', 'portrait')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('clientes.pdf');
    }
}
