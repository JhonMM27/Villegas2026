<?php

declare(strict_types=1);

/**
 * Controlador de Préstamos.
 *
 * Controlador delgado que delega la lógica de negocio a PrestamoService.
 * Solo maneja: validación, invocación del servicio y respuestas HTTP.
 *
 * Métodos de lectura (index, show, view, printTicket, getSerie)
 * permanecen en el controlador por ser lógica de presentación.
 */

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ComprobanteSerie;
use App\Models\ComprobanteTipo;
use App\Models\Prestamo;
use App\Models\Producto;
use App\Services\PrestamoService;
use App\Services\TicketPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PrestamoController extends Controller
{
    /** ID del cliente que representa la empresa en la tabla clientes */
    protected $empresa_cliente_id = 11;

    /**
     * Inyección del servicio de préstamos para delegar
     * la lógica de negocio (crear, actualizar, eliminar).
     */
    public function __construct(
        protected PrestamoService $prestamoService
    ) {
        $this->middleware('can:prestamos_list')->only(['index', 'view', 'printTicket', 'getSerie']);
        $this->middleware('can:prestamos_create')->only(['store', 'rectificar']);
        $this->middleware('can:prestamos_edit')->only(['show', 'update']);
        $this->middleware('can:prestamos_delete')->only(['destroy']);
        $this->middleware('can:prestamos_delete')->only(['anular']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Prestamo::with([
                'clienteOrigen',
                'clienteDestino',
                'comprobanteTipo',
            ])->select([
                'id',
                'user_id',
                'user_nombre',
                'movimiento_tipo',
                'cliente_origen_id',
                'cliente_destino_id',
                'comprobante_tipo_codigo',
                'serie',
                'correlativo',
                'fecha_prestamo',
                'total',
                'estado',
                'rectificacion_count',
            ])->orderBy('id', 'desc');

            // 🔹 Filtro por menú
            // ID del cliente de la empresa en la tabla clientes
            if ($request->filled('tipo')) {
                switch ($request->tipo) {
                    // PRÉSTAMO A → Yo presto
                    case 'PA':
                        $data->where('movimiento_tipo', 'PA')
                            ->where('cliente_origen_id', $this->empresa_cliente_id);
                        break;

                        // PRÉSTAMO DE → Me prestaron / me deben
                    case 'PD':
                        $data->where('movimiento_tipo', 'PD')
                            ->where('cliente_destino_id', $this->empresa_cliente_id);
                        break;

                        // DEVOLUCIÓN DE → Me devuelven
                    case 'DD':
                        $data->where('movimiento_tipo', 'DD')
                            ->where('cliente_destino_id', $this->empresa_cliente_id);
                        break;

                        // DEVOLUCIÓN A → Yo devuelvo
                    case 'DA':
                        $data->where('movimiento_tipo', 'DA')
                            ->where('cliente_origen_id', $this->empresa_cliente_id);
                        break;
                }

            }

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = [];

                    /*
                    // El usuario solicitó comentar iconos Editar/Delete
                    if (auth()->user()->can('prestamos_edit')) {
                         $buttons[] = '<button class="btn btn-sm btn-info btn-edit-prestamo" data-id="' . $row->id . '"><i class="bi bi-pencil"></i></button>';
                    }
                    if (auth()->user()->can('prestamos_delete')) {
                         $buttons[] = '<button class="btn btn-sm btn-danger btn-delete-prestamo" data-id="' . $row->id . '"><i class="bi bi-trash"></i></button>';
                    }
                    */

                    // Botón Anular (solo si está registrada)
                    if ($row->estado === 'registrada' && auth()->user()->can('prestamos_delete') && \Carbon\Carbon::parse($row->fecha_prestamo)->format('Y-m-d') >= '2026-03-24') {
                        $buttons[] = '<button class="btn btn-sm btn-danger btn-anular-prestamo" data-id="'.$row->id.'" title="Anular Préstamo">
                            <i class="bi bi-x-circle"></i>
                         </button>';
                    }

                    // Botón Anular (si está rectificada y aún puede rectificar más)
                    if ($row->estado === 'rectificada' && $row->rectificacion_count < 3 && auth()->user()->can('prestamos_delete') && \Carbon\Carbon::parse($row->fecha_prestamo)->format('Y-m-d') >= '2026-03-24') {
                        $buttons[] = '<button class="btn btn-sm btn-danger btn-anular-prestamo" data-id="'.$row->id.'" title="Anular para rectificar">
                            <i class="bi bi-x-circle"></i>
                         </button>';
                    }

                    // Botón Rectificar (solo si está anulada y rectificacion_count < 3)
                    if ($row->estado === 'anulada' && $row->rectificacion_count < 3 && auth()->user()->can('prestamos_edit') && \Carbon\Carbon::parse($row->fecha_prestamo)->format('Y-m-d') >= '2026-03-24') {
                        $buttons[] = '<button class="btn btn-sm btn-warning btn-rectificar-prestamo" data-id="'.$row->id.'" title="Rectificar Préstamo">
                            <i class="bi bi-arrow-repeat"></i>
                         </button>';
                    }

                    // Botón Imprimir (ticket/comprobante)
                    $buttons[] = '<a href="'.route('prestamos.imprimir', $row->id).'" target="_blank" class="btn btn-sm btn-secondary" title="Ver Comprobante"><i class="bi bi-printer"></i></a>';

                    // Botón Ver
                    $buttons[] = '<button class="btn btn-sm btn-info btn-view-prestamo" data-id="'.$row->id.'" title="Ver Préstamo"><i class="bi bi-eye"></i></button>';

                    return '<div class="btn-group">'.implode('', $buttons).'</div>';
                })
                // ->addColumn('usuario', fn($row) => optional($row->user)->name)
                ->addColumn('usuario', fn ($row) => $row->user_nombre ?? '')
                ->addColumn('origen', fn ($r) => optional($r->clienteOrigen)->razon_social)
                ->addColumn('destino', fn ($r) => optional($r->clienteDestino)->razon_social)
                ->rawColumns(['action', 'estado'])
                ->editColumn('estado', function ($row) {
                    $color = match ($row->estado) {
                        'anulada' => 'danger',
                        'registrada' => 'success',
                        'rectificada' => 'warning text-dark',
                        'parcial' => 'info',
                        'devuelto' => 'secondary',
                        default => 'primary',
                    };

                    return '<span class="badge bg-'.$color.'">'.ucfirst($row->estado).'</span>';
                })
                ->make(true);
        }

        return view('prestamos.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    // ─── Métodos privados migrados a PrestamoService ──────────
    // private function isIncreaseStock() → PrestamoService::isIncreaseStock()

    /**
     * Crea un nuevo préstamo.
     *
     * Valida los datos del request y delega la creación
     * al servicio PrestamoService::createPrestamo().
     *
     * @param  Request  $request  Datos del formulario
     * @return JsonResponse Respuesta con el resultado de la operación
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->filled('fecha_prestamo')) {
            $request->merge([
                'fecha_prestamo' => str_replace('T', ' ', $request->fecha_prestamo).':00',
            ]);
        }

        $data = $this->validateData($request);

        if ($request->boolean('es_rectificacion')) {
            $data['rectificacion_motivo'] = $request->validate([
                'rectificacion_motivo' => 'nullable|string|max:500',
            ])['rectificacion_motivo'] ?? null;
        }

        try {
            if ($request->boolean('es_rectificacion') && $request->filled('prestamo_anulado_id')) {
                $result = $this->prestamoService->rectificarPrestamo(
                    (int) $request->prestamo_anulado_id,
                    $data,
                    $request->comprobante_tipo_codigo,
                    $request->serie,
                    (int) $request->correlativo
                );
            } else {
                // Si no es rectificación, forzamos estado registrada
                $result = $this->prestamoService->createPrestamo($data, 'registrada');
            }

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'prestamo_id' => $result['prestamo']->id,
                'correlativo' => $result['correlativo'],
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // Log para depuración: muestra el error SQL real
            \Log::error('Prestamo store QueryException: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos: '.$e->getMessage(),
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
            $registro = Prestamo::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'afectacion_tipo_codigo', 'codigo', 'nombre', 'costo_unitario', 'empaque', 'unidad_codigo');
                },
                'detalles.producto.afectacionTipo' => function ($query) {
                    $query->select('codigo', 'descripcion', 'porcentaje');
                },
                'detalles.producto.fracciones',
                'clienteOrigen' => function ($query) {
                    $query->select('id', 'razon_social');
                },
                'clienteDestino' => function ($query) {
                    $query->select('id', 'razon_social');
                },
            ])->findOrFail($id);

            // Calcular saldos pendientes
            $saldos = [];
            if (in_array($registro->movimiento_tipo, ['PA', 'PD'])) {
                $saldos = $this->prestamoService->obtenerSaldosPendientes($id);
            }

            // Convertir a array para agregar la propiedad dinámica
            $data = $registro->toArray();
            $data['saldos'] = $saldos;

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('PrestamoController show error: '.$e->getMessage());

            return response()->json(['error' => 'Registro no encontrado: '.$e->getMessage()], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prestamo $prestamo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Actualiza un préstamo existente.
     *
     * Valida los datos del request y delega la actualización
     * al servicio PrestamoService::updatePrestamo().
     *
     * @param  Request  $request  Datos del formulario
     * @param  int  $id  ID del préstamo a actualizar
     * @return JsonResponse Respuesta con el resultado de la operación
     */
    public function update(Request $request, $id): JsonResponse
    {
        if ($request->filled('fecha_prestamo')) {
            $request->merge([
                'fecha_prestamo' => str_replace('T', ' ', $request->fecha_prestamo).':00',
            ]);
        }

        $data = $this->validateData($request);

        try {
            $this->prestamoService->updatePrestamo($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado satisfactoriamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Elimina un préstamo existente.
     *
     * Delega la eliminación y reversión de stock
     * al servicio PrestamoService::deletePrestamo().
     *
     * @param  int  $id  ID del préstamo a eliminar
     * @return JsonResponse Respuesta con el resultado de la operación
     */
    // public function destroy($id): JsonResponse
    // {
    //     try {
    //         $this->prestamoService->deletePrestamo($id);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Prestamo eliminado satisfactoriamente.',
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al eliminar el registro: '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Anula un préstamo registrando movimientos de reversión
     * y recalculando el kardex en cascada.
     *
     * @param  int  $id  ID del préstamo a anular
     */
    public function anular(Request $request, $id): JsonResponse
    {
        $data = $request->validate(['motivo' => 'nullable|string|max:500']);

        try {
            $prestamo = $this->prestamoService->anularPrestamo((int) $id, $data['motivo'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Préstamo anulado correctamente. El kardex fue recalculado.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular: '.$e->getMessage(),
            ], 500);
        }
    }

    /* ============================================================
     * COMENTADO: Migrado a PrestamoService.
     *
     * La lógica de negocio (processPrestamoData, calculateDetail,
     * isIncreaseStock) fue movida a App\Services\PrestamoService.
     * Los métodos store(), update() y destroy() ahora delegan
     * al servicio inyectado.
     * ============================================================ */

    // private function processPrestamoData(array $data, bool $isNew = true)
    // {
    //     $comprobanteTipo = ComprobanteTipo::where('codigo', $data['comprobante_tipo_codigo'])->first();
    //     $productos = Producto::with('afectacionTipo','unidad')
    //         ->whereIn('id', collect($data['detalles'])->pluck('producto_id'))
    //         ->get()
    //         ->keyBy('id');
    //     $totales = ['total' => 0];
    //     $detallesCalculados = [];
    //     foreach ($data['detalles'] as $detalle) {
    //         $detallesCalculados[] = $this->calculateDetail($productos[$detalle['producto_id']], $detalle['cantidad'], $detalle['precio_unitario'], $totales);
    //     }
    //     $clienteId = $data['cliente_id'];
    //     [$clienteOrigenId, $clienteDestinoId] = match ($data['movimiento_tipo']) {
    //         'PA', 'DA' => [$this->empresa_cliente_id, $clienteId],
    //         'PD', 'DD' => [$clienteId, $this->empresa_cliente_id],
    //     };
    //     $prestamoData = [
    //         'movimiento_tipo'        => $data['movimiento_tipo'],
    //         'cliente_origen_id'      => $clienteOrigenId,
    //         'cliente_destino_id'     => $clienteDestinoId,
    //         'prestamo_referencia_id' => $data['prestamo_referencia_id'],
    //         'comprobante_tipo_codigo' => $data['comprobante_tipo_codigo'],
    //         'comprobante_tipo_nombre' => $comprobanteTipo->codigo ?? '',
    //         'serie' => $data['serie'],
    //         'correlativo' => $data['correlativo'],
    //         'fecha_prestamo' => $data['fecha_prestamo'] ?? now(),
    //         'total' => round($totales['total'],2)
    //     ];
    //     if ($isNew) {
    //         $prestamoData['estado'] = !is_null($data['prestamo_referencia_id'])
    //             ? 'DEVUELTO' : 'REGISTRADO';
    //         $prestamoData['user_id'] = auth()->id();
    //         $prestamoData['user_nombre'] = auth()->user()->name;
    //     }
    //     return ['prestamo' => $prestamoData, 'detalles' => $detallesCalculados];
    // }

    // private function calculateDetail($producto, $cantidad, $precio_unitario_input, array &$totales)
    // {
    //     $precio_unitario = $precio_unitario_input;
    //     $detalleTotal = $precio_unitario * $cantidad;
    //     $totales['total'] += $detalleTotal;
    //     return [
    //         'producto_id' => $producto->id,
    //         'producto_nombre'    => $producto->nombre,
    //         'unidad_codigo'      => $producto->unidad->codigo ?? '',
    //         'unidad_nombre'      => $producto->unidad->descripcion ?? '',
    //         'producto_empaque'   => $producto->empaque ?? 0,
    //         'cantidad' => $cantidad,
    //         'cantidad_kgm' => $cantidad * $producto->empaque,
    //         'valor_unitario' => round($precio_unitario,4),
    //         'total' => round($detalleTotal,2)
    //     ];
    // }

    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            // Cabecera de la venta
            'movimiento_tipo' => 'required|in:PA,PD,DD,DA',
            'cliente_id' => 'required|exists:clientes,id',
            'prestamo_referencia_id' => 'nullable|exists:prestamos,id',
            'comprobante_tipo_codigo' => 'required|in:SP,DP,IP,SD',
            'serie' => 'required|string|max:4',
            'correlativo' => 'required|integer|min:1',
            'moneda' => 'nullable|string|size:3',
            'fecha_prestamo' => 'required|date_format:Y-m-d H:i:s',
            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0.01',
            'detalles.*.unidad_codigo' => 'nullable|string|max:3',
            'detalles.*.empaque' => 'nullable|numeric|min:0',
        ]);
    }

    public function printTicket($id)
    {
        $prestamo = Prestamo::with([
            'detalles' => function ($query) {
                $query->select('id', 'prestamo_id', 'producto_nombre', 'cantidad', 'valor_unitario', 'total');
            },
            'clienteOrigen:id,razon_social,documento_numero,direccion',
            'clienteDestino:id,razon_social,documento_numero,direccion',
        ])->findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'Consorcios Villegas E.I.R.L.',
            'direccion' => 'Cal. Inca Roca Nro. 1210 - La Victoria - Chiclayo',
            'ruc' => '20538937321',
        ];

        $pdf = app(TicketPdfService::class)->render('prestamos.ticket', compact('prestamo', 'empresa'));

        return $pdf->stream("ticket_{$prestamo->id}.pdf");
    }

    public function view($id)
    {
        try {
            $prestamo = Prestamo::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'afectacion_tipo_codigo', 'codigo', 'nombre', 'costo_unitario', 'unidad_codigo');
                },
                'detalles.producto.afectacionTipo' => function ($query) {
                    $query->select('codigo', 'descripcion', 'porcentaje');
                },
                'clienteOrigen' => function ($query) {
                    $query->select('id', 'razon_social', 'direccion', 'documento_tipo_codigo', 'documento_numero');
                },
                'clienteDestino' => function ($query) {
                    $query->select('id', 'razon_social', 'direccion', 'documento_tipo_codigo', 'documento_numero');
                },
                'prestamoReferencia' => function ($query) {
                    $query->select('id', 'comprobante_tipo_codigo', 'serie', 'correlativo');
                },
            ])->findOrFail($id);

            // Devolver vista parcial
            // return $prestamo;
            $saldos = [];
            if (in_array($prestamo->movimiento_tipo, ['PA', 'PD'])) {
                $saldos = app(\App\Services\PrestamoService::class)->obtenerSaldosPendientes($id);
            }

            return view('prestamos.view', compact('prestamo', 'saldos'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

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
