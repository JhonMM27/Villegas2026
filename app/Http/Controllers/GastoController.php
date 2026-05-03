<?php

namespace App\Http\Controllers;

use App\Helpers\NumeroALetras;
use App\Models\Gasto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class GastoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:gastos_list')->only(['index', 'view', 'printTicket']);
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
            $data = Gasto::with('gastoTipo')->select(['id', 'fecha_gasto', 'user_nombre', 'descripcion', 'responsable', 'numero_recibo', 'monto', 'gasto_tipo_id']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('gastos_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('gastos_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->numero_recibo])->render();
                    }

                    $ver = '<button class="btn btn-sm btn-info btn-view-gasto" data-id="'.$row->id.'" title="Ver Gasto">
                         <i class="bi bi-eye"></i>
                      </button>';
                    $ticketButton = '<a href="'.route('gastos.imprimir', $row->id).'" 
                         target="_blank" 
                         class="btn btn-sm btn-secondary" 
                         title="Ver Comprobante">
                         <i class="bi bi-printer"></i>
                      </a>';

                    return '<div class="btn-group">'.$editButton.$deleteButton.$ver.$ticketButton.'</div>';
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
                'fecha_gasto' => str_replace('T', ' ', $request->fecha_gasto).':00',
            ]);
        }

        $data = $this->validateData($request);
        $data['user_id'] = auth()->id();
        $data['user_nombre'] = auth()->user()->name;

        $ultimoRecibo = Gasto::whereRaw("numero_recibo REGEXP '^[0-9]+$'")
            ->selectRaw('MAX(CAST(numero_recibo AS UNSIGNED)) as max_recibo')
            ->value('max_recibo');
        $siguienteRecibo = $ultimoRecibo ? ((int) $ultimoRecibo + 1) : 1;
        $data['numero_recibo'] = $siguienteRecibo;
        $data['monto'] = $data['total_cobranza'];
        $data['importe_p'] = $data['principal'];
        $data['importe_d'] = $data['deposito'];
        $data['importe_c'] = $data['consorcio'];

        Gasto::create($data);

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
            $registro = Gasto::with('gastoTipo')->where('id', $id)->firstOrFail();

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
                'fecha_gasto' => str_replace('T', ' ', $request->fecha_gasto).':00',
            ]);
        }
        $data = $this->validateData($request, $id);
        $data['monto'] = $data['total_cobranza'];
        $data['importe_p'] = $data['principal'];
        $data['importe_d'] = $data['deposito'];
        $data['importe_c'] = $data['consorcio'];
        $registro = Gasto::where('id', $id)->firstOrFail();
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
            $registro = Gasto::findOrFail($id);
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
            'descripcion' => 'nullable|string|max:100',
            'responsable' => 'nullable|string|max:100',
            'numero_interno' => 'nullable|string|max:10',
            'fecha_gasto' => 'required|date',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
            'total_cobranza' => 'nullable|numeric|min:0.01',
            'gasto_tipo_id' => 'nullable|exists:gasto_tipos,id',
        ]);
    }

    public function view($id)
    {
        try {
            $gasto = Gasto::findOrFail($id);

            return view('gastos.view', compact('gasto'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function printTicket($id)
    {
        $gasto = Gasto::findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3'."\n".'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $formatter = new NumeroALetras;
        $total_letras = $formatter->convertir($gasto->monto);

        $pdf = Pdf::loadView('gastos.ticket', compact('gasto', 'empresa', 'total_letras'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("gasto_{$gasto->id}.pdf");
    }

    public function selectTipos(Request $request)
    {
        $query = \App\Models\GastoTipo::select('id', 'nombre')
            ->where('activo', true);

        return response()->json($query->get());
    }

    public function reporteResumen(Request $request)
    {
        $esAjax = $request->ajax();

        if (! $esAjax) {
            // Primera carga: mostrar vista con formulario vacío
            return view('reportes.gastos.index');
        }

        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'tipo' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $tipo = $request->tipo;

        $query = Gasto::whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($tipo && $tipo !== 'Todos', fn ($q) => $q->where('tipo', $tipo))
            ->orderBy('tipo')
            ->orderBy('fecha_gasto', 'desc');

        $reportes = $query->get();

        return view('reportes.gastos.resumen', compact('reportes', 'fechaInicio', 'fechaFin', 'tipo'));
    }

    public function exportarResumen(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'tipo' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $tipo = $request->tipo;

        $query = Gasto::whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($tipo && $tipo !== 'Todos', fn ($q) => $q->where('tipo', $tipo))
            ->orderBy('tipo')
            ->orderBy('fecha_gasto', 'desc');

        $reportes = $query->get();

        $fileName = 'gastos_resumen_'.now()->format('Ymd_His').'.xlsx';

        return \Excel::download(new \App\Exports\GastosExport($reportes), $fileName);
    }

    public function imprimirResumen(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'tipo' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $tipo = $request->tipo;

        $query = Gasto::whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($tipo && $tipo !== 'Todos', fn ($q) => $q->where('tipo', $tipo))
            ->orderBy('tipo')
            ->orderBy('fecha_gasto', 'desc');

        $reportes = $query->get();

        $pdf = Pdf::loadView('reportes.gastos.resumen_pdf', [
            'reportes' => $reportes,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'tipo' => $tipo,
        ])->setPaper('letter', 'landscape')
            ->setOptions([
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream('gastos_resumen.pdf');
    }
}
