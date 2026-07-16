<?php

/**
 * Controlador de NucleoPreparada (Producción de Núcleos).
 *
 * Maneja la entrada/salida HTTP del módulo de nucleo_preparadas.
 * - Listado con DataTables (index)
 * - Consulta de registro (show)
 * - CRUD delegado a NucleoPreparadaService
 * - Generación de PDF para ticket (printTicket)
 * - Vista detallada parcial (view)
 * - Anulación y rectificación
 */

namespace App\Http\Controllers;

use App\Models\NucleoPreparada;
use App\Services\NucleoPreparadaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class NucleoPreparadaController extends Controller
{
    /**
     * Constructor: inyecta el servicio y define los middleware de permisos.
     */
    public function __construct(
        protected NucleoPreparadaService $service
    ) {
        $this->middleware('can:nucleo_preparadas_list')->only(['index', 'view', 'printTicket']);
        $this->middleware('can:nucleo_preparadas_create')->only(['store']);
        $this->middleware('can:nucleo_preparadas_edit')->only(['show', 'update']);
        $this->middleware('can:nucleo_preparadas_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = NucleoPreparada::select([
                'id',
                'user_nombre',
                'fecha',
                'numero_interno',
                'nucleo_id',
                'nucleo_nombre',
                'unidad_nombre',
                'producto_empaque',
                'cantidad_porcentaje',
                'ingreso_kg',
                'items',
                'ingreso_soles',
                'estado',
                'rectificacion_count',
            ])
                ->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = [];

                    // Botón Anular (solo si está registrada)
                    if ($row->estado === 'registrada' && auth()->user()->can('nucleo_preparadas_delete') && \Carbon\Carbon::parse($row->fecha)->format('Y-m-d') >= '2026-03-24') {
                        $buttons[] = '<button class="btn btn-sm btn-danger btn-anular-nucleo-preparada" data-id="'.$row->id.'" title="Anular">
                            <i class="bi bi-x-circle"></i>
                         </button>';
                    }

                    // Botón Anular (si está rectificada y aún puede rectificar más)
                    if ($row->estado === 'rectificada' && $row->rectificacion_count < 3 && auth()->user()->can('nucleo_preparadas_delete') && \Carbon\Carbon::parse($row->fecha)->format('Y-m-d') >= '2026-03-24') {
                        $buttons[] = '<button class="btn btn-sm btn-danger btn-anular-nucleo-preparada" data-id="'.$row->id.'" title="Anular para rectificar">
                            <i class="bi bi-x-circle"></i>
                         </button>';
                    }

                    // Botón Rectificar (solo si está anulada y rectificacion_count < 3)
                    if ($row->estado === 'anulada' && $row->rectificacion_count < 3 && auth()->user()->can('nucleo_preparadas_edit') && \Carbon\Carbon::parse($row->fecha)->format('Y-m-d') >= '2026-03-24') {
                        $buttons[] = '<button class="btn btn-sm btn-warning btn-rectificar-nucleo-preparada" data-id="'.$row->id.'" title="Rectificar">
                            <i class="bi bi-arrow-repeat"></i>
                         </button>';
                    }

                    // Botón Imprimir
                    $buttons[] = '<a href="'.route('nucleo-preparadas.imprimir', $row->id).'" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir">
                        <i class="bi bi-printer"></i>
                    </a>';

                    // Botón Ver
                    $buttons[] = '<button class="btn btn-sm btn-info btn-view-nucleo-preparada" data-id="'.$row->id.'" title="Ver">
                        <i class="bi bi-eye"></i>
                    </button>';

                    return '<div class="btn-group">'.implode('', $buttons).'</div>';
                })
                ->editColumn('estado', function ($row) {
                    $color = match ($row->estado) {
                        'anulada' => 'danger',
                        'rectificada' => 'info',
                        default => 'success'
                    };

                    return '<span class="badge bg-'.$color.'">'.ucfirst($row->estado).'</span>';
                })
                ->addColumn('item', fn ($row) => $row->detalles->count())
                ->editColumn('fecha', function ($row) {
                    return \Carbon\Carbon::parse($row->fecha)->format('Y-m-d');
                })
                ->addColumn('usuario', fn ($row) => $row->user_nombre ?? '')
                ->rawColumns(['action', 'estado'])
                ->make(true);
        }

        return view('nucleo-preparadas.index');
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
        if ($request->filled('fecha')) {
            $request->merge([
                'fecha' => \Carbon\Carbon::parse($request->fecha)->format('Y-m-d H:i:s'),
            ]);
        }

        $data = $this->validateData($request);

        try {
            $esRectificacion = $request->input('es_rectificacion') == '1';
            $preparadaAnuladaId = $request->input('preparada_anulada_id');

            if ($esRectificacion
                && ! empty($preparadaAnuladaId)
                && NucleoPreparada::where('id', $preparadaAnuladaId)->exists()
            ) {
                $result = $this->service->rectificarNucleoPreparada(
                    $preparadaAnuladaId,
                    $data
                );
                $preparada = $result['preparada'];
                $message = 'Preparación de Núcleo rectificada satisfactoriamente';
            } else {
                $preparada = $this->service->createNucleoPreparada($data);
                $message = 'Registro creado satisfactoriamente';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'nucleo_preparada_id' => $preparada->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = NucleoPreparada::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'codigo', 'nombre', 'costo_unitario');
                },
            ])->findOrFail($id);

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NucleoPreparada $nucleoPreparada)
    {
        //
    }

    /**
     * Anula una nucleo_preparada existente.
     *
     * @param  int  $id  ID de la nucleo_preparada a anular
     */
    public function anular(int $id): JsonResponse
    {
        try {
            $this->service->anularNucleoPreparada($id);

            return response()->json([
                'success' => true,
                'message' => 'Preparación de Núcleo #'.$id.' anulada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /* ============================================================
     * Las Preparaciones de Núcleo son inmutables.
     * Solo se permite rectificación de registros anulados.
     * ============================================================ */
    public function update(Request $request, $id)
    {
        $preparada = NucleoPreparada::findOrFail($id);

        if ($preparada->estado !== 'registrada') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden editar Preparaciones de Núcleo en estado registrada.',
            ], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'Las Preparaciones de Núcleo no se pueden editar directamente. Use rectificación.',
        ], 403);
    }

    // public function destroy($id)
    // {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Las Preparaciones de Núcleo no se pueden eliminar.'
    //     ], 403);
    // }

    /**
     * Valida los datos del request.
     *
     * @param  int|null  $id
     * @return array
     */
    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'fecha' => 'required|date_format:Y-m-d H:i:s',
            'nucleo_id' => 'required|exists:nucleos,id',
            'proporcion' => 'required|numeric',
            'numero_interno' => 'nullable',
            'ingreso_saco' => 'nullable|numeric',
            'ingreso_soles' => 'nullable|numeric',
            'costo_saco' => 'nullable|numeric',

            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.unidad_codigo' => 'required',
            'detalles.*.cantidad_porcentaje' => 'required|numeric|min:0.0',
            'detalles.*.salida_kg' => 'required|numeric|min:0.0',
            'detalles.*.costo_unitario' => 'required|numeric|min:0',
            'detalles.*.salida_soles' => 'required|numeric|min:0',
        ]);
    }

    /**
     * Genera y devuelve el ticket PDF.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function printTicket($id)
    {
        $preparada = NucleoPreparada::with([
            'detalles.producto' => function ($query) {
                $query->select('id', 'nombre', 'codigo', 'costo_unitario');
            },
        ])->findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3'."\n".'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $pdf = Pdf::loadView('nucleo-preparadas.ticket', ['nucleoPreparada' => $preparada, 'empresa' => $empresa])
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("nucleo_preparada_{$preparada->id}.pdf");
    }

    /**
     * Devuelve la vista parcial con el detalle.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\View|JsonResponse
     */
    public function view($id)
    {
        try {
            $preparada = NucleoPreparada::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'nombre', 'codigo', 'costo_unitario', 'linea_id')
                        ->with('linea:id,nombre');
                },
                'nucleo.producto.linea',
            ])->findOrFail($id);

            return view('nucleo-preparadas.view', ['nucleoPreparada' => $preparada]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }
}
