<?php

/**
 * Controlador de Ventas.
 *
 * Maneja la entrada/salida HTTP del módulo de ventas.
 * - Listado con DataTables (index)
 * - Consulta de registro para edición (show)
 * - CRUD delegado a VentaService (store, update, destroy)
 * - Generación de PDF para ticket (printTicket)
 * - Vista detallada parcial (view)
 * - Consulta de serie/correlativo (getSerie)
 */

namespace App\Http\Controllers;

use App\Helpers\NumeroALetras;
use App\Models\ComprobanteSerie;
use App\Models\Venta;
use App\Services\VentaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class VentaController extends Controller
{
    /**
     * Constructor: inyecta el servicio de ventas y define los middleware de permisos.
     *
     * @param  VentaService  $ventaService  Servicio con la lógica de negocio de ventas
     */
    public function __construct(
        protected VentaService $ventaService
    ) {
        $this->middleware('can:ventas_list')->only(['index', 'view', 'printTicket']);
        $this->middleware('can:ventas_create')->only(['store']);
        $this->middleware('can:ventas_edit')->only(['show', 'update']);
        $this->middleware('can:ventas_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Venta::select([
                'id', 'user_nombre', 'cliente_nombre', 'comprobante_tipo_nombre',
                'pago_forma_nombre', 'serie', 'correlativo',
                DB::raw('DATE(fecha_venta) as fecha_venta'), 'fecha_vencimiento', 'moneda', 'op_gravada',
                'op_exonerada', 'op_inafecta', 'impuesto', 'total', 'estado', 'abonos', 'saldo',
                'importe_p', 'importe_d', 'importe_c', 'acuenta', 'docpagoi',
            ])->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = [];

                    /*
                    // El usuario solicitó comentar iconos Editar/Eliminar
                    if (auth()->user()->can('ventas_edit')) {
                         $buttons[] = '<button class="btn btn-sm btn-info btn-edit-venta" data-id="' . $row->id . '"><i class="bi bi-pencil"></i></button>';
                    }
                    if (auth()->user()->can('ventas_delete')) {
                         $buttons[] = '<button class="btn btn-sm btn-danger btn-delete-venta" data-id="' . $row->id . '"><i class="bi bi-trash"></i></button>';
                    }
                    */

                    // Botón Anular (solo si está registrada)
                    if ($row->estado === 'registrada' && auth()->user()->can('ventas_delete') && \Carbon\Carbon::parse($row->fecha_venta)->format('Y-m-d') >= '2026-03-24') {
                        $buttons[] = '<button class="btn btn-sm btn-danger btn-anular-venta" data-id="'.$row->id.'" title="Anular Venta">
                            <i class="bi bi-x-circle"></i>
                         </button>';
                    }

                    // Botón Rectificar (solo si está anulada)
                    if ($row->estado === 'anulada' && auth()->user()->can('ventas_edit') && \Carbon\Carbon::parse($row->fecha_venta)->format('Y-m-d') >= '2026-03-24') {
                        $buttons[] = '<button class="btn btn-sm btn-warning btn-rectificar-venta" data-id="'.$row->id.'" title="Rectificar Venta">
                            <i class="bi bi-arrow-repeat"></i>
                         </button>';
                    }

                    // Botón Imprimir (ticket)
                    $buttons[] = '<a href="'.route('ventas.imprimir', $row->id).'" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir Ticket"><i class="bi bi-printer"></i></a>';

                    // Botón Ver
                    $buttons[] = '<button class="btn btn-sm btn-primary btn-view-venta" data-id="'.$row->id.'" title="Ver Venta"><i class="bi bi-eye"></i></button>';

                    return '<div class="btn-group">'.implode('', $buttons).'</div>';
                })
                ->rawColumns(['action', 'estado'])
                ->editColumn('estado', function ($row) {
                    $color = match ($row->estado) {
                        'anulada' => 'danger',
                        'rectificada' => 'info',
                        default => 'success'
                    };

                    return '<span class="badge bg-'.$color.'">'.$row->estado.'</span>';
                })
                ->editColumn('fecha_venta', function ($row) {
                    return \Carbon\Carbon::parse($row->fecha_venta)->format('Y-m-d');
                })
                ->make(true);
        }

        return view('ventas.index');
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
     * Convierte fechas, valida datos y delega la creación al VentaService.
     */
    public function store(Request $request)
    {
        // Convertir fechas antes de validar
        if ($request->filled('fecha_venta')) {
            $request->merge([
                'fecha_venta' => str_replace('T', ' ', $request->fecha_venta).':00',
            ]);
        }

        if ($request->filled('fecha_vencimiento')) {
            $request->merge([
                'fecha_vencimiento' => Carbon::parse($request->fecha_vencimiento)
                    ->format('Y-m-d'),
            ]);
        }
        $data = $this->validateData($request);

        try {
            $venta = $this->ventaService->createVenta(
                $data,
                $request->filled('cotizacion_ref_id') ? (int) $request->cotizacion_ref_id : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'venta_id' => $venta->id,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo reservar correlativo. Intente nuevamente.',
            ], 409);
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
            $registro = Venta::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'afectacion_tipo_codigo', 'codigo', 'nombre', 'costo_unitario', 'stock_almacen', 'unidad_codigo');
                },
                'detalles.producto.afectacionTipo' => function ($query) {
                    $query->select('codigo', 'descripcion', 'porcentaje');
                },
                'detalles.producto.fracciones' => function ($query) {
                    $query->select('producto_id', 'unidad_codigo', 'empaque', 'precio_lista');
                },
                'cliente' => function ($query) {
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
    public function edit(Venta $venta)
    {
        //
    }

    /**
     * Anula una venta existente.
     * Revierte el stock vendido (INGRESO en kardex) y recalcula
     * el CPP en cascada desde el movimiento original de la venta.
     *
     * @param  int  $id  ID de la venta a anular
     */
    public function anular(int $id): JsonResponse
    {
        try {
            $venta = $this->ventaService->anularVenta($id);

            return response()->json([
                'success' => true,
                'message' => 'Venta #'.$venta->serie.'-'.$venta->correlativo.' anulada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /* ============================================================
     * COMENTADO: Kardex Valorizado — Ventas inmutables.
     * ============================================================ */
    // public function update(Request $request, $id)
    // {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Las ventas no se pueden editar. Use corrección por recálculo de kardex.'
    //     ], 403);
    // }

    // public function destroy($id)
    // {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Las ventas no se pueden eliminar. Use corrección por recálculo de kardex.'
    //     ], 403);
    // }

    /**
     * Valida los datos del request para una venta (cabecera + detalles).
     *
     * @param  Request  $request  Datos de la petición HTTP
     * @param  int|null  $id  ID de la venta (para validación en edición)
     * @return array Datos validados
     */
    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            // Cabecera de la venta
            'cliente_id' => 'required|exists:clientes,id',
            'comprobante_tipo_codigo' => 'required|exists:comprobante_tipos,codigo',
            'serie' => 'required|string|max:4',
            'correlativo' => 'required|integer|min:1',
            'pago_forma_codigo' => 'required|exists:pago_formas,codigo',
            'moneda' => 'nullable|string|size:3',
            'docpagoi' => 'nullable|string|max:20',
            'fecha_venta' => 'required|date_format:Y-m-d H:i:s',
            'fecha_vencimiento' => 'nullable|date_format:Y-m-d',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
            'total_cobranza' => 'nullable|numeric|min:0',
            'op_gravada' => 'required|numeric',
            'op_exonerada' => 'required|numeric',
            'op_inafecta' => 'required|numeric',
            'impuesto' => 'required|numeric',
            'total' => 'required|numeric',

            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.unidad_codigo' => 'required|exists:unidades,codigo',
            'detalles.*.empaque' => 'required|numeric',
            'detalles.*.cantidad' => 'required|numeric',
            'detalles.*.precio_unitario' => 'required|numeric',
            'detalles.*.entrega' => 'nullable|numeric',
            'detalles.*.total' => 'required|numeric',
        ]);
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

    /**
     * Genera y devuelve el ticket PDF de una venta.
     *
     * @param  int  $id  ID de la venta
     * @return \Illuminate\Http\Response PDF streamed
     */
    public function printTicket($id)
    {
        $venta = Venta::with(['cliente'])->findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3'."\n".'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $formatter = new NumeroALetras;
        $total_letras = $formatter->convertir($venta->total);

        $pdf = Pdf::loadView('ventas.ticket', compact('venta', 'empresa', 'total_letras'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("ticket_{$venta->id}.pdf");
    }

    /**
     * Rectifica una venta anulada creando una nueva.
     *
     * @param  int  $id  ID de la venta anulada
     */
    public function rectificar(Request $request, $id): JsonResponse
    {
        // Convertir fechas antes de validar
        if ($request->filled('fecha_venta')) {
            $request->merge([
                'fecha_venta' => str_replace('T', ' ', $request->fecha_venta).':00',
            ]);
        }

        if ($request->filled('fecha_vencimiento') && $request->fecha_vencimiento != 'null' && $request->fecha_vencimiento != '') {
            $request->merge([
                'fecha_vencimiento' => \Carbon\Carbon::parse($request->fecha_vencimiento)->format('Y-m-d'),
            ]);
        }

        $data = $this->validateData($request);

        try {
            $venta = $this->ventaService->rectificarVenta((int) $id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Venta rectificada correctamente',
                'venta_id' => $venta->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Devuelve la vista parcial con el detalle de una venta.
     *
     * @param  int  $id  ID de la venta
     */
    public function view($id)
    {
        try {
            $venta = Venta::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'nombre', 'afectacion_tipo_codigo', 'codigo', 'costo_unitario');
                },
                'cliente' => function ($query) {
                    $query->select('id', 'razon_social', 'documento_numero', 'direccion');
                },
            ])->findOrFail($id);

            // Devolver vista parcial
            return view('ventas.view', compact('venta'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }
}
