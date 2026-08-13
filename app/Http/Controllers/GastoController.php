<?php

namespace App\Http\Controllers;

use App\Helpers\NumeroALetras;
use App\Models\Gasto;
use App\Support\NumericStringOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class GastoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:gastos_list')->only(['index', 'view', 'printTicket']);
        $this->middleware('can:gastos_create')->only(['store']);
        $this->middleware('can:gastos_edit')->only(['show', 'update']);
        $this->middleware('can:gastos_delete')->only(['destroy']);
        $this->middleware('can:gastos_report')->only([
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
            $data = Gasto::with(['gastoTipo', 'categoriaGasto'])->select(['id', 'fecha_gasto', 'user_nombre', 'descripcion', 'responsable', 'responsable_dni', 'categoria_gasto_id', 'numero_recibo', 'monto', 'gasto_tipo_id']);

            return DataTables::of($data)
                // Personalización de filtrado para las relaciones de tipo y categoría de gasto
                ->filterColumn('gasto_tipo.nombre', function ($query, $keyword) {
                    $query->whereHas('gastoTipo', function ($q) use ($keyword) {
                        $q->where('nombre', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('gastoTipo.nombre', function ($query, $keyword) {
                    $query->whereHas('gastoTipo', function ($q) use ($keyword) {
                        $q->where('nombre', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('categoria_gasto.nombre', function ($query, $keyword) {
                    $query->whereHas('categoriaGasto', function ($q) use ($keyword) {
                        $q->where('nombre', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('categoriaGasto.nombre', function ($query, $keyword) {
                    $query->whereHas('categoriaGasto', function ($q) use ($keyword) {
                        $q->where('nombre', 'like', "%{$keyword}%");
                    });
                })
                // Ordenamiento personalizado por nombre de tipo y categoría
                ->orderColumn('gasto_tipo.nombre', function ($query, $direction) {
                    $query->orderBy(
                        \App\Models\GastoTipo::select('nombre')->whereColumn('gasto_tipos.id', 'gastos.gasto_tipo_id'),
                        $direction
                    );
                })
                ->orderColumn('categoria_gasto.nombre', function ($query, $direction) {
                    $query->orderBy(
                        \App\Models\GastoCategoria::select('nombre')->whereColumn('categoria_gastos.id', 'gastos.categoria_gasto_id'),
                        $direction
                    );
                })
                ->orderColumn('numero_recibo', function ($query, $direction) {
                    NumericStringOrder::apply($query, 'gastos.numero_recibo', 'gastos.id', $direction);
                })
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
        $data['importe_c'] = $data['consorc'] ?? 0;
        $data['responsable_dni'] = $data['responsable_dni'] ?? null;
        $data['categoria_gasto_id'] = $data['categoria_gasto_id'] ?? null;

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
            $registro = Gasto::with(['gastoTipo', 'categoriaGasto'])->where('id', $id)->firstOrFail();

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
            'responsable_dni' => 'nullable|string|max:8',
            'numero_interno' => 'nullable|string|max:10',
            'fecha_gasto' => 'required|date',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
            'total_cobranza' => 'nullable|numeric|min:0.01',
            'gasto_tipo_id' => 'nullable|exists:gasto_tipos,id',
            'categoria_gasto_id' => 'nullable|exists:categoria_gastos,id',
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

    public function selectCategorias(Request $request)
    {
        $query = \App\Models\GastoCategoria::select('id', 'nombre')
            ->where('activo', true);

        if ($request->has('q') && $request->q !== '') {
            $query->where('nombre', 'like', '%'.$request->q.'%');
        }

        return response()->json($query->get());
    }

    public function selectTipos(Request $request)
    {
        $query = \App\Models\GastoTipo::select('id', 'nombre', 'categoria_gasto_id')
            ->where('activo', true);

        if ($request->has('categoria_id') && $request->categoria_id !== '' && $request->categoria_id !== null) {
            $query->where('categoria_gasto_id', $request->categoria_id);
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
            $gastoCategorias = \App\Models\GastoCategoria::where('activo', true)->orderBy('nombre')->get();

            return view('reportes.gastos.index', compact('gastoCategorias'));
        }

        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'gasto_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->gasto_tipo_id;

        $query = Gasto::with(['gastoTipo', 'categoriaGasto'])
            ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_gasto_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('gasto_tipo_id', $tipoId))
            ->orderBy('categoria_gasto_id')
            ->orderBy('gasto_tipo_id');

        $reportes = $query->get();

        return view('reportes.gastos.general', compact('reportes', 'fechaInicio', 'fechaFin', 'categoriaId', 'tipoId'));
    }

    public function reporteDetallado(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'gasto_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->gasto_tipo_id;

        $query = Gasto::with(['gastoTipo', 'categoriaGasto'])
            ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_gasto_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('gasto_tipo_id', $tipoId))
            ->orderBy('fecha_gasto', 'desc');

        $reportes = $query->get();

        return view('reportes.gastos.detallado', compact('reportes', 'fechaInicio', 'fechaFin', 'categoriaId', 'tipoId'));
    }

    public function exportarGeneral(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'gasto_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->gasto_tipo_id;

        $query = Gasto::with(['gastoTipo', 'categoriaGasto'])
            ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_gasto_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('gasto_tipo_id', $tipoId))
            ->orderBy('categoria_gasto_id')
            ->orderBy('gasto_tipo_id');

        $reportes = $query->get();

        $fileName = 'gastos_general_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new \App\Exports\GastosGeneralExport($reportes), $fileName);
    }

    public function exportarDetallado(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'gasto_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->gasto_tipo_id;

        $query = Gasto::with(['gastoTipo', 'categoriaGasto'])
            ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_gasto_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('gasto_tipo_id', $tipoId))
            ->orderBy('fecha_gasto', 'desc');

        $reportes = $query->get();

        $fileName = 'gastos_detallado_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new \App\Exports\GastosDetalladoExport($reportes), $fileName);
    }

    public function imprimirGeneral(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'gasto_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->gasto_tipo_id;

        $query = Gasto::with(['gastoTipo', 'categoriaGasto'])
            ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_gasto_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('gasto_tipo_id', $tipoId))
            ->orderBy('categoria_gasto_id')
            ->orderBy('gasto_tipo_id');

        $reportes = $query->get();

        $pdf = Pdf::loadView('reportes.gastos.general_pdf', [
            'reportes' => $reportes,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'categoriaId' => $categoriaId,
            'tipoId' => $tipoId,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('gastos_general.pdf');
    }

    public function imprimirDetallado(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'categoria_id' => 'nullable|string',
            'gasto_tipo_id' => 'nullable|string',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = \Carbon\Carbon::parse($request->fecha_fin)->endOfDay();
        $categoriaId = $request->categoria_id;
        $tipoId = $request->gasto_tipo_id;

        $query = Gasto::with(['gastoTipo', 'categoriaGasto'])
            ->whereBetween('fecha_gasto', [$fechaInicio, $fechaFin])
            ->when($categoriaId && $categoriaId !== 'Todos', fn ($q) => $q->where('categoria_gasto_id', $categoriaId))
            ->when($tipoId && $tipoId !== 'Todos', fn ($q) => $q->where('gasto_tipo_id', $tipoId))
            ->orderBy('fecha_gasto', 'desc');

        $reportes = $query->get();

        $pdf = Pdf::loadView('reportes.gastos.detallado_pdf', [
            'reportes' => $reportes,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'categoriaId' => $categoriaId,
            'tipoId' => $tipoId,
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('gastos_detallado.pdf');
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
