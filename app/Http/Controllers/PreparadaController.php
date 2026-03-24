<?php

/**
 * Controlador de Preparadas (Producción).
 *
 * Maneja la entrada/salida HTTP del módulo de preparadas.
 * - Listado con DataTables (index)
 * - Consulta de registro para edición (show)
 * - CRUD delegado a PreparadaService (store, update, destroy)
 * - Generación de PDF para ticket (printTicket)
 * - Vista detallada parcial (view)
 */

namespace App\Http\Controllers;

use App\Models\Formulacion;
use App\Models\Preparada;
use App\Models\PreparadaDetalle;
use App\Models\Cliente;
use App\Services\PreparadaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;

class PreparadaController extends Controller
{

    /**
     * Constructor: inyecta el servicio de preparadas y define los middleware de permisos.
     *
     * @param PreparadaService $preparadaService Servicio con la lógica de negocio de preparadas
     */
    public function __construct(
        protected PreparadaService $preparadaService
    ){
        $this->middleware('can:preparadas_list')->only(['index', 'view','printTicket']);
        $this->middleware('can:preparadas_create')->only(['store']);
        $this->middleware('can:preparadas_edit')->only(['show', 'update']);
        $this->middleware('can:preparadas_delete')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Preparada::with('detalles')
            ->select([
                'id',
                'fecha',
                'numero_interno',
                'producto_nombre',
                'cliente_nombre',
                'ingreso_kg',
                'ingreso_soles',
                'ingreso_saco',
                'producto_empaque',
                'formulacion_id',
                'user_nombre',
                'items',
                'estado',
            ])
            ->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = [];

                    /* 
                    // El usuario solicitó comentar iconos Editar/Delete
                    if (auth()->user()->can('preparadas_edit')) {
                         $buttons[] = '<button class="btn btn-sm btn-info btn-edit-preparada" data-id="' . $row->id . '"><i class="bi bi-pencil"></i></button>';
                    }
                    if (auth()->user()->can('preparadas_delete')) {
                         $buttons[] = '<button class="btn btn-sm btn-danger btn-delete-preparada" data-id="' . $row->id . '"><i class="bi bi-trash"></i></button>';
                    }
                    */

                    // Botón Anular (solo si está registrada)
                    if ($row->estado === 'registrada' && auth()->user()->can('preparadas_delete')) {
                        $buttons[] = '<button class="btn btn-sm btn-danger btn-anular-preparada" data-id="' . $row->id . '" title="Anular Preparada">
                            <i class="bi bi-x-circle"></i>
                         </button>';
                    }

                    // Botón Rectificar (solo si está anulada)
                    if ($row->estado === 'anulada' && auth()->user()->can('preparadas_edit')) {
                        $buttons[] = '<button class="btn btn-sm btn-warning btn-rectificar-preparada" data-id="' . $row->id . '" title="Rectificar Preparada">
                            <i class="bi bi-arrow-repeat"></i>
                         </button>';
                    }

                    // Botón Imprimir
                    $buttons[] = '<a href="' . route('preparadas.imprimir', $row->id) . '" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir"><i class="bi bi-printer"></i></a>';

                    // Botón Ver
                    $buttons[] = '<button class="btn btn-sm btn-info btn-view-preparada" data-id="' . $row->id . '" title="Ver Preparada"><i class="bi bi-eye"></i></button>';

                    return '<div class="btn-group">' . implode('', $buttons) . '</div>';
                })
                ->rawColumns(['action', 'estado'])
                ->editColumn('estado', function ($row) {
                    $color = match($row->estado) {
                        'anulada' => 'danger',
                        'rectificada' => 'info',
                        default => 'success'
                    };
                    return '<span class="badge bg-' . $color . '">' . $row->estado . '</span>';
                })
                ->addColumn('item', fn($row) => $row->detalles->count())
                ->editColumn('fecha', function ($row) {
                    return \Carbon\Carbon::parse($row->fecha)->format('Y-m-d');
                })
                ->addColumn('usuario', fn($row) => $row->user_nombre ?? '')
                ->make(true);
        }

        return view('preparadas.index');
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
     *
     * Convierte fechas, valida datos y delega la creación al PreparadaService.
     */
    public function store(Request $request)
    {
        // Convertir fechas antes de validar
        if ($request->filled('fecha')) {
            $request->merge([
                'fecha' => str_replace('T', ' ', $request->fecha) . ':00'
            ]);
        }

        $data = $this->validateData($request);

        try {
            if ($request->input('es_rectificacion') == '1') {
                $preparadaAnuladaId = $request->input('preparada_anulada_id');
                $result = $this->preparadaService->rectificarPreparada(
                    $preparadaAnuladaId,
                    $data
                );
                $preparada = $result['preparada'];
                $message = 'Preparada rectificada satisfactoriamente';
            } else {
                $preparada = $this->preparadaService->createPreparada($data);
                $message = 'Registro creado satisfactoriamente';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'preparada_id' => $preparada->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = Preparada::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'codigo', 'nombre', 'costo_unitario');
                },
                'cliente' => function ($query) {
                    $query->select('id', 'razon_social');
                }
            ])->findOrFail($id);

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Preparada $preparada)
    {
        //
    }

    /**
     * Anula una preparada existente.
     * Revierte insumos (INGRESO) y producto final (SALIDA) en kardex
     * y recalcula el CPP en cascada para todos los productos afectados.
     *
     * @param  int $id ID de la preparada a anular
     * @return JsonResponse
     */
    public function anular(int $id): JsonResponse
    {
        try {
            $this->preparadaService->anularPreparada($id);
            return response()->json([
                'success' => true,
                'message' => 'Preparada #' . $id . ' anulada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /* ============================================================
     * COMENTADO: Kardex Valorizado — Preparadas inmutables.
     * ============================================================ */
    // public function update(Request $request, $id)
    // {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Las preparadas no se pueden editar. Use corrección por recálculo de kardex.'
    //     ], 403);
    // }

    // public function destroy($id)
    // {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Las preparadas no se pueden eliminar. Use corrección por recálculo de kardex.'
    //     ], 403);
    // }

    /**
     * Valida los datos del request para una preparada (cabecera + detalles).
     *
     * @param  Request  $request Datos de la petición HTTP
     * @param  int|null $id      ID de la preparada (para validación en edición)
     * @return array             Datos validados
     */
    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'fecha' => 'required|date_format:Y-m-d H:i:s',
            'formulacion_id' => 'required|exists:formulaciones,id',
            'ingreso_saco' => 'required|numeric',
            'ingreso_kg' => 'required|numeric',
            'ingreso_soles' => 'required|numeric',
            'numero_interno' => 'required',

            'detalles' => 'required|array|min:1', // Al menos un detalle
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.salida_saco' => 'required|numeric|min:0',
            'detalles.*.salida_kg' => 'required|numeric|min:0',
            'detalles.*.salida_soles' => 'required|numeric|min:0',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);
    }

    /**
     * Genera y devuelve el ticket PDF de una preparada.
     *
     * @param  int $id ID de la preparada
     * @return \Illuminate\Http\Response PDF streamed
     */
    public function printTicket($id){
        $preparada = Preparada::with([
                'detalles.producto' => function ($query) {
                    $query->select('id','nombre','codigo','costo_unitario');
                },
                'cliente' => function($query) {
                    $query->select('id','razon_social','documento_numero');
                }
            ])->findOrFail($id);

        $empresa = (object)[
            'razon_social' => 'Consorcios Villegas E.I.R.L.',
            'direccion' => 'Cal. Inca Roca Nro. 1210 - La Victoria - Chiclayo',
            'ruc' => '20538937321'
        ];

        $pdf = Pdf::loadView('preparadas.ticket', compact('preparada','empresa'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("preparada_{$preparada->id}.pdf");
    }

    /**
     * Devuelve la vista parcial con el detalle de una preparada.
     *
     * @param  int $id ID de la preparada
     * @return \Illuminate\Contracts\View\View|JsonResponse
     */
    public function view($id)
    {
        try {
            $preparada = Preparada::with([
                'detalles.producto' => function ($query) {
                    $query->select('id','nombre','codigo','costo_unitario','linea_id')
                        ->with('linea:id,nombre');
                },
                'cliente' => function($query) {
                    $query->select('id','razon_social','documento_numero');
                }
            ])->findOrFail($id);

            // Devolver vista parcial
            return view('preparadas.view', compact('preparada'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }
}