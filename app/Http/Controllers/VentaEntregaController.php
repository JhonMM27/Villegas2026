<?php

/**
 * Controlador de Entregas de Venta (Entregas Parciales).
 *
 * Maneja la entrada/salida HTTP del módulo de entregas de venta.
 * - Listado con DataTables (index)
 * - Consulta de registro para edición (show)
 * - CRUD delegado a VentaEntregaService (store, update, destroy)
 * - Consulta de ventas por entregar (ventasPorEntregar)
 * - Detalle de ventas por entregar (ventasPorEntregarDetalle)
 * - Generación de PDF para ticket (printTicket)
 * - Vista detallada parcial (view)
 */

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaEntrega;
use App\Services\VentaEntregaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class VentaEntregaController extends Controller
{
    /**
     * Constructor: inyecta el servicio y define los middleware de permisos.
     *
     * @param  VentaEntregaService  $ventaEntregaService  Servicio con la lógica de negocio
     */
    public function __construct(
        protected VentaEntregaService $ventaEntregaService
    ) {
        $this->middleware('can:venta_entregas_list')->only(['index', 'imprimir', 'view']);
        $this->middleware('can:venta_entregas_create')->only(['store']);
        $this->middleware('can:venta_entregas_edit')->only(['show', 'update']);
        $this->middleware('can:venta_entregas_delete')->only(['destroy']);
    }

    /**
     * Listado + DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('venta_entregas as ve')
                ->join('ventas as v', 've.venta_id', '=', 'v.id')
                ->join('clientes as c', 'v.cliente_id', '=', 'c.id')
                ->select([
                    've.id',
                    've.user_nombre',
                    've.fecha_entrega',
                    've.numero_recibo',
                    'v.comprobante_tipo_codigo',
                    'v.serie',
                    'v.correlativo',
                    'c.razon_social as cliente_nombre',
                ]);

            return DataTables::of($data)
                ->filterColumn('serie', function ($query, $keyword) {
                    $query->where('v.serie', 'like', "%{$keyword}%");
                })
                ->filterColumn('correlativo', function ($query, $keyword) {
                    $query->where('v.correlativo', 'like', "%{$keyword}%");
                })
                ->filterColumn('comprobante_tipo_codigo', function ($query, $keyword) {
                    $query->where('v.comprobante_tipo_codigo', 'like', "%{$keyword}%");
                })
                ->filterColumn('cliente_nombre', function ($query, $keyword) {
                    $query->where('c.razon_social', 'like', "%{$keyword}%");
                })
                ->filterColumn('numero_recibo', function ($query, $keyword) {
                    $query->where('ve.numero_recibo', 'like', "%{$keyword}%");
                })
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('venta_entregas_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('venta_entregas_delete')) {
                        $texto = trim(($row->cliente_nombre ?? '').' - '.($row->numero_recibo ?? ''));
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $texto])->render();
                    }
                    $ticketButton = '<a href="'.route('venta-entregas.imprimir', $row->id).'" 
                        target="_blank" 
                        class="btn btn-sm btn-secondary" 
                        title="Ver Comprobante">
                        <i class="bi bi-printer"></i>
                     </a>';
                    $ver = '<button class="btn btn-sm btn-primary btn-view-venta" data-id="'.$row->id.'" title="Ver Entrega">
                        <i class="bi bi-eye"></i>
                     </button>';

                    return '<div class="btn-group">'.$editButton.$deleteButton.$ver.$ticketButton.'</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('venta-entregas.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * Convierte fechas, valida datos y delega la creación al servicio.
     */
    public function store(Request $request)
    {
        if ($request->filled('fecha_entrega')) {
            $request->merge([
                'fecha_entrega' => str_replace('T', ' ', $request->fecha_entrega).':00',
            ]);
        }

        $data = $this->validateData($request);

        try {
            $entrega = $this->ventaEntregaService->createEntrega($data);

            return response()->json([
                'success' => true,
                'message' => 'Entrega registrada satisfactoriamente',
                'venta_entrega_id' => $entrega->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la entrega: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = VentaEntrega::with([
                'venta:id,cliente_id,cliente_nombre',
                'detalles:id,venta_entrega_id,venta_detalle_id,producto_nombre,producto_empaque,cantidad,producto_id',
                'detalles.ventaDetalle:id,cantidad,entregado,unidad_codigo,precio_unitario,saldo,producto_empaque',
            ])->findOrFail($id);

            // Aplanar cliente para el JS
            $registro->cliente_id = optional($registro->venta)->cliente_id;
            $registro->cliente_nombre = optional($registro->venta)->cliente_nombre;

            $registro->setRelation('detalles', $registro->detalles->map(function ($d) {
                $vd = $d->ventaDetalle;

                $vendido = (float) ($vd->cantidad ?? 0);
                $entregadoTotal = (float) ($vd->entregado ?? 0);
                $entregadoEsta = (float) ($d->cantidad ?? 0);

                $entregadoSinEsta = max(0, $entregadoTotal - $entregadoEsta);
                $pendiente = max(0, $vendido - $entregadoSinEsta);

                return [
                    'id' => $d->id,
                    'producto_id' => $d->producto_id,
                    'venta_detalle_id' => $d->venta_detalle_id,
                    'producto_nombre' => $d->producto_nombre,
                    'producto_empaque' => $d->producto_empaque,
                    'unidad_codigo' => $vd->unidad_codigo ?? null,
                    'precio_unitario' => $vd->precio_unitario ?? 0,

                    'vendido' => $vendido,
                    'entregado_total' => $entregadoTotal,
                    'entregado_esta' => $entregadoEsta,
                    'pendiente' => $pendiente,
                    'max_editable' => $pendiente,
                ];
            }));

            return response()->json($registro);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * Convierte fechas, valida datos y delega la actualización al servicio.
     */
    public function update(Request $request, $id)
    {
        if ($request->filled('fecha_entrega')) {
            $request->merge([
                'fecha_entrega' => str_replace('T', ' ', $request->fecha_entrega).':00',
            ]);
        }

        $data = $this->validateData($request, $id);

        try {
            $this->ventaEntregaService->updateEntrega($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Entrega actualizada satisfactoriamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la entrega: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * Delega la eliminación al servicio.
     */
    public function destroy($id)
    {
        try {
            $this->ventaEntregaService->deleteEntrega($id);

            return response()->json([
                'success' => true,
                'message' => 'Entrega eliminada correctamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la entrega: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valida los datos del request para una entrega.
     *
     * @param  Request  $request  Datos de la petición HTTP
     * @param  int|null  $id  ID de la entrega (para validación en edición)
     * @return array Datos validados
     */
    protected function validateData(Request $request, $id = null): array
    {
        $rules = [

            'venta_id' => 'required|exists:ventas,id',
            'fecha_entrega' => 'required|date',
            'comentario' => 'nullable|string|max:100',

            // detalles es opcional
            'detalles' => 'nullable|array',
            'detalles.*.venta_detalle_id' => 'nullable|exists:venta_detalles,id',
            'detalles.*.producto_id' => 'nullable|exists:productos,id',
            'detalles.*.producto_nombre' => 'nullable|string|max:50',
            'detalles.*.producto_empaque' => 'nullable|string|max:10',
            'detalles.*.cantidad' => 'nullable|numeric|min:0',
        ];

        $messages = [
            'detalles.array' => 'El formato de detalles es inválido.',
            'detalles.*.venta_detalle_id.exists' => 'Una de lis detalles a entregar no existe.',
        ];

        $validator = validator($request->all(), $rules, $messages);

        return $validator->validate();
    }

    /**
     * Consulta ventas con productos pendientes por entregar para un cliente.
     *
     * @param  Request  $request  Requiere 'cliente_id'
     * @return \Illuminate\Http\JsonResponse
     */
    public function ventasPorEntregar(Request $request)
    {
        $request->validate([
            'cliente_id' => ['required', 'integer'],
        ]);

        $ventas = Venta::query()
            ->select([
                'id',
                'user_nombre',
                'cliente_id',
                'cliente_nombre',
                'fecha_venta',
                'comprobante_tipo_codigo',
                'serie',
                'correlativo',
                'total',
                'estado',
            ])
            ->where('estado', '!=', 'Anulada')
            ->where('cliente_id', $request->cliente_id)
            ->whereHas('detalles', function ($q) {
                $q->whereColumn('entregado', '<', 'cantidad');
            })
            ->orderByDesc('fecha_venta')
            ->get();

        return response()->json($ventas);
    }

    /**
     * Consulta los detalles de una venta con productos pendientes por entregar.
     *
     * @param  int  $ventaId  ID de la venta
     * @return \Illuminate\Http\JsonResponse
     */
    public function ventasPorEntregarDetalle(Request $request, $ventaId)
    {
        $detalles = VentaDetalle::query()
            ->select([
                'id',
                'venta_id',
                'detalle',
                'producto_id',
                'producto_nombre',
                'producto_empaque',
                'precio_unitario',
                'unidad_codigo',
                'cantidad',
                'entregado',
                'saldo',
                'salida_kg',
            ])
            ->where('venta_id', $ventaId)
            ->whereColumn('entregado', '<', 'cantidad')
            ->orderBy('detalle')
            ->get()
            ->map(function ($d) {
                // pendiente por UI
                $cantidad = (float) $d->cantidad;
                $entregado = (float) $d->entregado;
                $d->pendiente = max(0, $cantidad - $entregado);

                return $d;
            });

        return response()->json($detalles);
    }

    /**
     * Genera y devuelve el ticket PDF de una entrega.
     *
     * @param  int  $id  ID de la entrega
     * @return \Illuminate\Http\Response PDF streamed
     */
    public function printTicket($id)
    {
        $entrega = VentaEntrega::with([
            'venta.cliente.documentoTipo',
            'detalles.ventaDetalle',
        ])->findOrFail($id);

        $venta = $entrega->venta;

        // Aplanar venta -> entrega (para que el blade use $entrega->serie, etc.)
        $entrega->comprobante_tipo_codigo = $venta->comprobante_tipo_codigo ?? null;
        $entrega->comprobante_tipo_nombre = $venta->comprobante_tipo_nombre ?? null;
        $entrega->serie = $venta->serie ?? null;
        $entrega->correlativo = $venta->correlativo ?? null;
        $entrega->fecha_venta = $venta->fecha_venta ?? null;

        $entrega->cliente_nombre = $venta->cliente_nombre ?? null;
        $entrega->cliente_documento = $venta->cliente_documento ?? null;
        $entrega->cliente_direccion = $venta->cliente_direccion ?? null;

        // Cliente para: $entrega->cliente->documentoTipo->descripcion
        $entrega->setRelation('cliente', $venta->cliente);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => "Carretera Pomalca KM 3\nA espaldas de Ferretería Herrera",
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $pdf = Pdf::loadView('venta-entregas.ticket', compact('entrega', 'empresa'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("ticket_entrega_{$entrega->id}.pdf");
    }

    /**
     * Devuelve la vista parcial con el detalle de una entrega.
     *
     * @param  int  $id  ID de la entrega
     * @return string|JsonResponse
     */
    public function view($id)
    {
        try {
            $entrega = VentaEntrega::with([
                'venta',
                'detalles',
            ])->findOrFail($id);

            return view('venta-entregas.view', compact('entrega'))->render();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Registro no encontrado',
            ], 404);
        }
    }

    /**
     * Anula una entrega parcial.
     *
     * @param  int  $id  ID de la entrega a anular
     * @return JsonResponse
     */
    public function anular($id)
    {
        try {
            $entrega = $this->ventaEntregaService->anularEntrega($id);

            return response()->json([
                'success' => true,
                'message' => 'Entrega anulada correctamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular la entrega: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rectifica una entrega previamente anulada.
     *
     * @param  int  $id  ID de la entrega anulada
     * @return JsonResponse
     */
    public function rectificar(Request $request, $id)
    {
        if ($request->filled('fecha_entrega')) {
            $request->merge([
                'fecha_entrega' => str_replace('T', ' ', $request->fecha_entrega).':00',
            ]);
        }

        $data = $this->validateData($request, $id);

        try {
            $entrega = $this->ventaEntregaService->rectificarEntrega($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Entrega rectificada correctamente',
                'venta_entrega_id' => $entrega->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rectificar la entrega: '.$e->getMessage(),
            ], 500);
        }
    }
}
