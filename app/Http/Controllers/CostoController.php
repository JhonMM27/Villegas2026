<?php

namespace App\Http\Controllers;

use App\Helpers\NumeroALetras;
use App\Models\Costo;
use App\Support\NumericStringOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CostoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:costos_list')->only(['index', 'view', 'printTicket']);
        $this->middleware('can:costos_create')->only(['store']);
        $this->middleware('can:costos_edit')->only(['show', 'update']);
        $this->middleware('can:costos_delete')->only(['destroy']);
        $this->middleware('can:costos_report')->only([
            'reporteGeneral', 'reporteDetallado',
            'exportarGeneral', 'exportarDetallado',
            'imprimirGeneral', 'imprimirDetallado',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Costo::with(['costoTipo', 'categoriaCosto'])->select(['id', 'fecha_costo', 'user_nombre', 'descripcion', 'responsable', 'responsable_dni', 'categoria_costo_id', 'numero_recibo', 'monto', 'costo_tipo_id']);

            return DataTables::of($data)
                ->orderColumn('numero_recibo', function ($query, $direction) {
                    NumericStringOrder::apply($query, 'costos.numero_recibo', 'costos.id', $direction);
                })
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('costos_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('costos_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->numero_recibo])->render();
                    }

                    $ver = '<button class="btn btn-sm btn-info btn-view-costo" data-id="'.$row->id.'" title="Ver Costo">
                         <i class="bi bi-eye"></i>
                      </button>';
                    $ticketButton = '<a href="'.route('costos.imprimir', $row->id).'"
                         target="_blank"
                         class="btn btn-sm btn-secondary"
                         title="Ver Comprobante">
                         <i class="bi bi-printer"></i>
                      </a>';

                    return '<div class="btn-group">'.$editButton.$deleteButton.$ver.$ticketButton.'</div>';
                })
                ->make(true);
        }

        return view('costos.index');
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
        if ($request->filled('fecha_costo')) {
            $request->merge([
                'fecha_costo' => \Carbon\Carbon::parse($request->fecha_costo)->format('Y-m-d H:i:s'),
            ]);
        }

        $data = $this->validateData($request);
        $data['user_id'] = auth()->id();
        $data['user_nombre'] = auth()->user()->name;
        $data['categoria_costo_id'] = $data['categoria_costo_id'] ?? null;

        $ultimoRecibo = Costo::whereRaw("numero_recibo REGEXP '^[0-9]+$'")
            ->selectRaw('MAX(CAST(numero_recibo AS UNSIGNED)) as max_recibo')
            ->value('max_recibo');
        $siguienteRecibo = $ultimoRecibo ? ((int) $ultimoRecibo + 1) : 1;
        $data['numero_recibo'] = $siguienteRecibo;
        $data['monto'] = $data['total_cobranza'];
        $data['importe_p'] = $data['principal'];
        $data['importe_d'] = $data['deposito'];
        $data['importe_c'] = $data['consorcio'];

        Costo::create($data);

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
            $registro = Costo::with(['costoTipo', 'categoriaCosto'])->where('id', $id)->firstOrFail();

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
        if ($request->filled('fecha_costo')) {
            $request->merge([
                'fecha_costo' => \Carbon\Carbon::parse($request->fecha_costo)->format('Y-m-d H:i:s'),
            ]);
        }
        $data = $this->validateData($request, $id);
        $data['monto'] = $data['total_cobranza'];
        $data['importe_p'] = $data['principal'];
        $data['importe_d'] = $data['deposito'];
        $data['importe_c'] = $data['consorcio'];
        $registro = Costo::where('id', $id)->firstOrFail();
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
            $registro = Costo::findOrFail($id);
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
            'descripcion' => 'required|string|max:100',
            'responsable' => 'required|string|max:100',
            'responsable_dni' => 'nullable|string|max:8',
            'numero_interno' => 'nullable|string|max:10',
            'fecha_costo' => 'required|date',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
            'total_cobranza' => 'required|numeric|min:0.01',
            'costo_tipo_id' => 'required|exists:costo_tipos,id',
            'categoria_costo_id' => 'nullable|exists:categoria_costos,id',
        ]);
    }

    public function view($id)
    {
        try {
            $costo = Costo::findOrFail($id);

            return view('costos.view', compact('costo'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function printTicket($id)
    {
        $costo = Costo::findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3'."\n".'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $formatter = new NumeroALetras;
        $total_letras = $formatter->convertir($costo->monto);

        $pdf = Pdf::loadView('costos.ticket', compact('costo', 'empresa', 'total_letras'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("costo_{$costo->id}.pdf");
    }

    public function selectCategorias(Request $request)
    {
        $query = \App\Models\CostoCategoria::select('id', 'nombre')
            ->where('activo', true);

        if ($request->has('q') && $request->q !== '') {
            $query->where('nombre', 'like', '%'.$request->q.'%');
        }

        return response()->json($query->get());
    }

    public function selectTipos(Request $request)
    {
        $query = \App\Models\CostoTipo::select('id', 'nombre', 'categoria_costo_id')
            ->where('activo', true);

        if ($request->has('categoria_id') && $request->categoria_id !== '' && $request->categoria_id !== null) {
            $query->where('categoria_costo_id', $request->categoria_id);
        }

        if ($request->has('q') && $request->q !== '') {
            $query->where('nombre', 'like', '%'.$request->q.'%');
        }

        return response()->json($query->get());
    }

    public function reporteGeneral(Request $request)
    {
        $esAjax = $request->ajax();

        if (! $esAjax) {
            $costoCategorias = \App\Models\CostoCategoria::where('activo', true)->orderBy('nombre')->get();

            return view('reportes.costos.index', compact('costoCategorias'));
        }

        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'costo_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->costo_tipo_id;

        $query = Costo::with(['costoTipo', 'categoriaCosto'])
            ->whereBetween('fecha_costo', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_costo_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('costo_tipo_id', $tipoId))
            ->orderBy('categoria_costo_id')
            ->orderBy('costo_tipo_id');

        $reportes = $query->get();

        return view('reportes.costos.general', compact('reportes', 'fechaInicio', 'fechaFin', 'categoriaId', 'tipoId'));
    }

    public function reporteDetallado(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'costo_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->costo_tipo_id;

        $query = Costo::with(['costoTipo', 'categoriaCosto'])
            ->whereBetween('fecha_costo', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_costo_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('costo_tipo_id', $tipoId))
            ->orderBy('fecha_costo', 'desc');

        $reportes = $query->get();

        return view('reportes.costos.detallado', compact('reportes', 'fechaInicio', 'fechaFin', 'categoriaId', 'tipoId'));
    }

    public function exportarGeneral(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'costo_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->costo_tipo_id;

        $query = Costo::with(['costoTipo', 'categoriaCosto'])
            ->whereBetween('fecha_costo', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_costo_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('costo_tipo_id', $tipoId))
            ->orderBy('categoria_costo_id')
            ->orderBy('costo_tipo_id');

        $reportes = $query->get();

        $fileName = 'costos_general_'.now()->format('Ymd_His').'.xlsx';

        return \Excel::download(new \App\Exports\CostosGeneralExport($reportes), $fileName);
    }

    public function exportarDetallado(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'costo_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->costo_tipo_id;

        $query = Costo::with(['costoTipo', 'categoriaCosto'])
            ->whereBetween('fecha_costo', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_costo_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('costo_tipo_id', $tipoId))
            ->orderBy('fecha_costo', 'desc');

        $reportes = $query->get();

        $fileName = 'costos_detallado_'.now()->format('Ymd_His').'.xlsx';

        return \Excel::download(new \App\Exports\CostosDetalladoExport($reportes), $fileName);
    }

    public function imprimirGeneral(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'costo_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->costo_tipo_id;

        $query = Costo::with(['costoTipo', 'categoriaCosto'])
            ->whereBetween('fecha_costo', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_costo_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('costo_tipo_id', $tipoId))
            ->orderBy('categoria_costo_id')
            ->orderBy('costo_tipo_id');

        $reportes = $query->get();

        $pdf = Pdf::loadView('reportes.costos.general_pdf', [
            'reportes' => $reportes,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'categoriaId' => $categoriaId,
            'tipoId' => $tipoId,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('costos_general.pdf');
    }

    public function imprimirDetallado(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'costo_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->costo_tipo_id;

        $query = Costo::with(['costoTipo', 'categoriaCosto'])
            ->whereBetween('fecha_costo', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_costo_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('costo_tipo_id', $tipoId))
            ->orderBy('fecha_costo', 'desc');

        $reportes = $query->get();

        $pdf = Pdf::loadView('reportes.costos.detallado_pdf', [
            'reportes' => $reportes,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'categoriaId' => $categoriaId,
            'tipoId' => $tipoId,
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('costos_detallado.pdf');
    }

    public function reporteResumen(Request $request)
    {
        return $this->reporteGeneral($request);
    }

    public function exportarResumen(Request $request)
    {
        return $this->exportarGeneral($request);
    }

    public function imprimirResumen(Request $request)
    {
        return $this->imprimirGeneral($request);
    }
}
