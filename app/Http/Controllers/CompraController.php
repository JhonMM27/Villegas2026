<?php

/**
 * Controlador de Compras.
 *
 * Maneja la entrada/salida HTTP del módulo de compras.
 * - Listado con DataTables (index)
 * - Consulta de registro para edición (show)
 * - CRUD delegado a CompraService (store, update, destroy)
 * - Generación de PDF para ticket (printTicket)
 * - Vista detallada parcial (view)
 * - Consulta de serie/correlativo (getSerie)
 */

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\ComprobanteSerie;
use App\Services\CompraService;
use App\Support\PagoInicial;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CompraController extends Controller
{
    /**
     * Constructor: inyecta el servicio de compras y define los middleware de permisos.
     *
     * @param  CompraService  $compraService  Servicio con la lógica de negocio de compras
     */
    public function __construct(
        protected CompraService $compraService
    ) {
        $this->middleware('can:compras_list')->only(['index', 'view', 'printTicket', 'getSerie']);
        $this->middleware('can:compras_create')->only(['store']);
        $this->middleware('can:compras_edit')->only(['show', 'update']);
        $this->middleware('can:compras_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Compra::with(['proveedor', 'user', 'comprobanteTipo', 'pagoForma', 'pagoMedio'])
                ->select([
                    'id', 'user_id', 'user_nombre', 'proveedor_id', 'comprobante_tipo_codigo',
                    'pago_forma_codigo', 'serie', 'correlativo',
                    DB::raw('DATE(fecha_compra) as fecha_compra'), 'fecha_vencimiento', 'moneda', 'op_gravada',
                    'op_exonerada', 'op_inafecta', 'impuesto', 'total', 'abonos', 'saldo', 'estado',
                    'rectificacion_count',
                ])->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $ticketButton = '<a href="'.route('compras.imprimir', $row->id).'" 
                        target="_blank" 
                        class="btn btn-sm btn-secondary" 
                        title="Ver Comprobante">
                        <i class="bi bi-printer"></i>
                     </a>';
                    $ver = '<button class="btn btn-sm btn-info btn-view-compra" data-id="'.$row->id.'" title="Ver Compra">
                        <i class="bi bi-eye"></i>
                     </button>';

                    /*
                    // El usuario solicitó comentar iconos Editar/Delete
                    $edit = "";
                    if (auth()->user()->can('compras_edit')) {
                        $edit = '<button class="btn btn-sm btn-info btn-edit-compra" data-id="' . $row->id . '"><i class="bi bi-pencil"></i></button>';
                    }
                    $delete = "";
                    if (auth()->user()->can('compras_delete')) {
                        $delete = '<button class="btn btn-sm btn-danger btn-delete-compra" data-id="' . $row->id . '"><i class="bi bi-trash"></i></button>';
                    }
                    */

                    // Botón Anular: solo si no está anulada
                    $anularButton = '';
                    $rectificarButton = '';

                    if ($row->estado === 'registrada' && auth()->user()->can('compras_delete') && \Carbon\Carbon::parse($row->fecha_compra)->format('Y-m-d') >= '2026-03-24') {
                        $anularButton = '<button class="btn btn-sm btn-danger btn-anular-compra" data-id="'.$row->id.'" title="Anular Compra">
                            <i class="bi bi-x-circle"></i>
                        </button>';
                    } elseif ($row->estado === 'rectificada' && $row->rectificacion_count < 3 && auth()->user()->can('compras_delete') && \Carbon\Carbon::parse($row->fecha_compra)->format('Y-m-d') >= '2026-03-24') {
                        // Botón Anular: si está rectificada y aún puede rectificar más, para volver a 'anulada'
                        $anularButton = '<button class="btn btn-sm btn-danger btn-anular-compra" data-id="'.$row->id.'" title="Anular para rectificar">
                            <i class="bi bi-x-circle"></i>
                        </button>';
                    } elseif ($row->estado === 'anulada' && $row->rectificacion_count < 3 && auth()->user()->can('compras_create') && \Carbon\Carbon::parse($row->fecha_compra)->format('Y-m-d') >= '2026-03-24') {
                        // Botón Rectificar: solo si ESTÁ anulada y rectificacion_count < 3
                        $rectificarButton = '<button class="btn btn-sm btn-warning btn-rectificar-compra" data-id="'.$row->id.'" title="Rectificar Compra">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>';
                    }

                    // Combinar ambos botones en una cadena y devolverla
                    return '<div class="btn-group">'.$ver.$ticketButton.$anularButton.$rectificarButton.'</div>';
                })
                ->filterColumn('proveedor', function ($query, $keyword) {
                    $query->whereHas('proveedor', function ($q) use ($keyword) {
                        $q->where('razon_social', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('usuario', fn ($row) => $row->user_nombre ?? '')
                ->addColumn('proveedor', fn ($row) => optional($row->proveedor)->razon_social)
                ->addColumn('tipo_comprobante', fn ($row) => optional($row->comprobanteTipo)->codigo)
                ->addColumn('pago_forma', fn ($row) => optional($row->pagoForma)->descripcion)
                ->rawColumns(['action', 'estado'])
                ->editColumn('estado', function ($row) {
                    $color = match ($row->estado) {
                        'anulada' => 'danger',
                        'registrada' => 'success',
                        'rectificada' => 'warning text-dark',
                        default => 'primary',
                    };

                    return '<span class="badge bg-'.$color.'">'.ucfirst($row->estado).'</span>';
                })
                ->editColumn('fecha_compra', function ($row) {
                    return \Carbon\Carbon::parse($row->fecha_compra)->format('Y-m-d');
                })
                ->editColumn('fecha_vencimiento', function ($row) {
                    if (! $row->fecha_vencimiento) {
                        return '';
                    }

                    return \Carbon\Carbon::parse($row->fecha_vencimiento)->format('Y-m-d');
                })
                ->make(true);
        }

        return view('compras.index');
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
     * Convierte fechas, valida datos y delega la creación al CompraService.
     */
    public function store(Request $request)
    {
        // Convertir fechas antes de validar
        if ($request->filled('fecha_compra')) {
            $request->merge([
                'fecha_compra' => \Carbon\Carbon::parse($request->fecha_compra)->format('Y-m-d H:i:s'),
            ]);
        }

        if ($request->filled('fecha_vencimiento')) {
            $request->merge([
                'fecha_vencimiento' => Carbon::parse($request->fecha_vencimiento)
                    ->format('Y-m-d'),
            ]);
        }
        $data = $this->validateData($request);

        // Si es rectificación, el estado será rectificada, en caso contrario, registrada.
        $estadoInicial = $request->boolean('es_rectificacion') ? 'rectificada' : 'registrada';

        try {
            if ($request->boolean('es_rectificacion') && $request->filled('compra_anulada_id')) {
                $compra = $this->compraService->rectificarCompra(
                    (int) $request->compra_anulada_id,
                    $data,
                    $request->comprobante_tipo_codigo,
                    $request->serie,
                    (int) $request->correlativo
                );
            } else {
                $compra = $this->compraService->createCompra(
                    $data,
                    $request->comprobante_tipo_codigo,
                    $request->serie,
                    (int) $request->correlativo,
                    $estadoInicial
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'compra_id' => $compra->id,
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
            $registro = Compra::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'afectacion_tipo_codigo', 'codigo', 'nombre', 'costo_unitario', 'unidad_codigo');
                },
                'detalles.producto.afectacionTipo' => function ($query) {
                    $query->select('codigo', 'descripcion', 'porcentaje');
                },
                'detalles.producto.fracciones' => function ($query) {
                    $query->select('producto_id', 'unidad_codigo', 'empaque', 'precio_lista');
                },
                'proveedor' => function ($query) {
                    $query->select('id', 'razon_social');
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
    public function edit(Compra $compra)
    {
        //
    }

    /* ============================================================
     * COMENTADO: Kardex Valorizado — Compras inmutables.
     * ============================================================ */
    // public function update(Request $request, $id)
    // {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Las compras no se pueden editar. Use corrección por recálculo de kardex.'
    //     ], 403);
    // }

    // public function destroy($id)
    // {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Las compras no se pueden eliminar. Use corrección por recálculo de kardex.'
    //     ], 403);
    // }

    /**
     * Anula una compra registrando movimientos de reversión
     * y recalculando el kardex en cascada.
     *
     * @param  int  $id  ID de la compra a anular
     * @return \Illuminate\Http\JsonResponse
     */
    public function anular($id)
    {
        try {
            $compra = $this->compraService->anularCompra((int) $id);

            return response()->json([
                'success' => true,
                'message' => 'Compra anulada correctamente. El kardex fue recalculado.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valida los datos del request para una compra (cabecera + detalles).
     *
     * @param  Request  $request  Datos de la petición HTTP
     * @param  int|null  $id  ID de la compra (para validación en edición)
     * @return array Datos validados
     */
    protected function validateData(Request $request, $id = null)
    {
        $data = $request->validate([
            // Cabecera de la compra
            'proveedor_id' => 'required|exists:proveedores,id',
            'comprobante_tipo_codigo' => 'required|exists:comprobante_tipos,codigo',
            'serie' => 'required|string|max:4',
            'correlativo' => 'required|integer|min:1',
            'pago_forma_codigo' => 'required|exists:pago_formas,codigo',
            'moneda' => 'nullable|string|size:3',
            'fecha_compra' => 'required|date_format:Y-m-d H:i:s',
            'fecha_vencimiento' => 'nullable|date_format:Y-m-d',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
            'total_cobranza' => 'nullable|numeric|min:0',
            'op_gravada' => 'required|numeric',
            'op_exonerada' => 'required|numeric',
            'op_inafecta' => 'required|numeric',
            'impuesto' => 'required|numeric',
            'total' => 'required|numeric|min:0',

            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.unidad_codigo' => 'required|exists:unidades,codigo',
            'detalles.*.empaque' => 'required|numeric',
            'detalles.*.cantidad' => 'required|numeric',
            'detalles.*.precio_unitario' => 'required|numeric',
            'detalles.*.precio_unitario_servicio' => 'nullable|numeric|min:0|max:9',
            'detalles.*.total' => 'required|numeric',
        ]);

        return $this->validatePaymentDistribution($data);
    }

    /**
     * Normaliza y valida el pago inicial sin confiar en el total calculado por el navegador.
     */
    private function validatePaymentDistribution(array $data): array
    {
        return PagoInicial::normalizarYValidar($data, 'compra');
    }

    /**
     * Genera y devuelve el ticket PDF de una compra.
     *
     * @param  int  $id  ID de la compra
     * @return \Illuminate\Http\Response PDF streamed
     */
    public function printTicket($id)
    {
        $compra = Compra::with([
            'detalles:id,compra_id,producto_nombre,cantidad,costo_unitario,total',
            'proveedor:id,razon_social,documento_tipo_codigo',
            'proveedor.documentoTipo:codigo,descripcion',
        ])->findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3'."\n".'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $pdf = Pdf::loadView('compras.ticket', compact('compra', 'empresa'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("ticket_{$compra->id}.pdf");
    }

    /**
     * Devuelve la vista parcial con el detalle de una compra.
     *
     * @param  int  $id  ID de la compra
     * @return \Illuminate\Contracts\View\View|JsonResponse
     */
    public function view($id)
    {
        try {
            $compra = Compra::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'nombre', 'afectacion_tipo_codigo', 'codigo', 'costo_unitario');
                },
                'proveedor' => function ($query) {
                    $query->select('id', 'razon_social', 'documento_numero');
                },
            ])->findOrFail($id);

            // Devolver vista parcial
            return view('compras.view', compact('compra'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Consulta la serie y correlativo actual para un tipo de comprobante.
     *
     * @param  Request  $request  Requiere 'comprobante_tipo_codigo'
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSerie(Request $request)
    {
        $request->validate([
            'comprobante_tipo_codigo' => 'required|exists:comprobante_tipos,codigo',
        ]);

        $codigo = $request->comprobante_tipo_codigo;

        // Buscar directamente en comprobante_series
        $serieConfig = ComprobanteSerie::where('comprobante_tipo_codigo', $codigo)->first();

        // Si no existe, devolver null
        if (! $serieConfig) {
            return response()->json([
                'serie' => null,
                'numero' => null,
            ]);
        }

        // Devolver los valores almacenados en la tabla
        return response()->json([
            'serie' => $serieConfig->serie,
            'numero' => $serieConfig->correlativo,
        ]);
    }
}
