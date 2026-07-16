<?php

/**
 * Controlador de Ventas Provisionales (Pagos Anticipados).
 *
 * Maneja la entrada/salida HTTP del módulo de ventas provisionales.
 * - Listado con DataTables (index)
 * - Consulta de registro para edición (show)
 * - CRUD delegado a VentaProvisionalService (store, update, destroy)
 * - Búsqueda para select2 (buscar)
 * - Consulta de ventas con saldo (ventasConSaldo)
 * - Generación de PDF para ticket (printTicket)
 * - Vista detallada parcial (view)
 */

namespace App\Http\Controllers;

use App\Helpers\NumeroALetras;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\VentaProvisional;
use App\Services\VentaProvisionalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class VentaProvisionalController extends Controller
{
    /**
     * Constructor: inyecta el servicio y define los middleware de permisos.
     *
     * @param  VentaProvisionalService  $ventaProvisionalService  Servicio con la lógica de negocio
     */
    public function __construct(
        protected VentaProvisionalService $ventaProvisionalService
    ) {
        $this->middleware('can:venta_provisionales_list')->only(['index', 'imprimir', 'view']);
        $this->middleware('can:venta_provisionales_create')->only(['store']);
        $this->middleware('can:venta_provisionales_edit')->only(['show', 'update']);
        $this->middleware('can:venta_provisionales_delete')->only(['destroy']);
    }

    /**
     * Listado + DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $tipo = $request->get('tipo', 'T');

            $data = DB::table('venta_provisionales as v')
                ->selectRaw("
                v.id,
                v.user_nombre,
                v.fecha_provisional,
                v.numero_recibo,
                v.numero_interno,
                v.cliente_nombre,
                v.monto,
                v.libre,
                v.tipo,
                v.importe_p,
                v.importe_d,
                v.importe_c,
                (
                    SELECT GROUP_CONCAT(
                        DISTINCT CONCAT(
                            COALESCE(d.comprobante_tipo_codigo,''), ' ', COALESCE(d.serie,''), '-', COALESCE(d.correlativo,0)
                        )
                        ORDER BY d.serie, d.correlativo
                        SEPARATOR ' | '
                    )
                    FROM venta_provisional_detalles d
                    WHERE d.venta_provisional_id = v.id
                ) as documentos
            ");
            if ($tipo === 'PL') {
                $data->where('v.libre', '>', 0);
            }

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('venta_provisionales_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('venta_provisionales_delete')) {
                        $texto = trim(($row->cliente_nombre ?? '').' - '.($row->numero_recibo ?? ''));
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $texto])->render();
                    }
                    $ticketButton = '<a href="'.route('venta-provisionales.imprimir', $row->id).'" 
                        target="_blank" 
                        class="btn btn-sm btn-secondary" 
                        title="Ver Comprobante">
                        <i class="bi bi-printer"></i>
                     </a>';
                    $ver = '<button class="btn btn-sm btn-primary btn-view-venta" data-id="'.$row->id.'" title="Ver Provisional">
                        <i class="bi bi-eye"></i>
                     </button>';

                    return '<div class="btn-group">'.$editButton.$deleteButton.$ver.$ticketButton.'</div>';
                })
                ->editColumn('monto', function ($row) {
                    return number_format((float) $row->monto, 2, '.', '');
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('venta-provisionales.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * Convierte fechas, valida datos y delega la creación al servicio.
     */
    public function store(Request $request)
    {
        if ($request->filled('fecha_provisional')) {
            $request->merge([
                'fecha_provisional' => str_replace('T', ' ', $request->fecha_provisional).':00',
            ]);
        }
        $data = $this->validateData($request);

        try {
            $provisional = $this->ventaProvisionalService->createProvisional($data);

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'venta_provisional_id' => $provisional->id,
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
            $registro = VentaProvisional::query()
                ->with([
                    'detalles.venta' => function ($q) {
                        $q->select(
                            'id',
                            'fecha_venta',
                            'comprobante_tipo_codigo',
                            'serie',
                            'correlativo',
                            'total',
                            'acuenta',
                            'abonos',
                            'saldo'
                        );
                    },
                ])
                ->findOrFail($id);

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
        if ($request->filled('fecha_provisional')) {
            $request->merge([
                'fecha_provisional' => str_replace('T', ' ', $request->fecha_provisional).':00',
            ]);
        }

        $data = $this->validateData($request);

        try {
            $this->ventaProvisionalService->updateProvisional($id, $data);

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
     *
     * Delega la eliminación al servicio.
     */
    public function destroy($id)
    {
        try {
            $this->ventaProvisionalService->deleteProvisional($id);

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el registro: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valida los datos del request para un pago provisional.
     *
     * @param  Request  $request  Datos de la petición HTTP
     * @param  int|null  $id  ID del provisional (para validación en edición)
     * @return array Datos validados
     */
    protected function validateData(Request $request, $id = null): array
    {
        $rules = [
            'numero_interno' => 'nullable|string|max:10',
            'fecha_provisional' => 'required|date',

            'cliente_id' => 'required|exists:clientes,id',
            'cliente_nombre' => 'required|string|max:100',

            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
            'total_cobranza' => 'nullable|numeric|min:0.01',

            // ventas es opcional: si es adelanto puede no venir o venir vacío
            'ventas' => 'nullable|array',
            'ventas.*.venta_id' => 'nullable|exists:ventas,id',
            'ventas.*.comprobante_tipo_codigo' => 'nullable|string|max:10',
            'ventas.*.serie' => 'nullable|string|max:10',
            'ventas.*.correlativo' => 'nullable|string|max:20',
            'ventas.*.monto' => 'nullable|numeric|min:0',
        ];

        $messages = [
            'ventas.array' => 'El formato de ventas es inválido.',
            'ventas.*.venta_id.exists' => 'Una de las ventas seleccionadas no existe.',
        ];

        $validator = validator($request->all(), $rules, $messages);

        return $validator->validate();
    }

    /**
     * Buscar (para select2/autocomplete)
     */
    public function buscar(Request $request)
    {
        $q = $request->input('q');

        return VentaProvisional::query()
            ->where('id', $q)
            ->orWhere('numero_recibo', 'like', "%{$q}%")
            ->orWhere('numero_interno', 'like', "%{$q}%")
            ->orWhere('cliente_nombre', 'like', "%{$q}%")
            ->select('id', 'numero_recibo', 'numero_interno', 'cliente_nombre', 'fecha_provisional', 'monto')
            ->orderByDesc('fecha_provisional')
            ->limit(10)
            ->get();
    }

    /**
     * Consulta ventas con saldo pendiente para un cliente.
     *
     * @param  Request  $request  Requiere 'cliente_id'
     * @return \Illuminate\Http\JsonResponse
     */
    public function ventasConSaldo(Request $request)
    {
        $ventas = Venta::where('cliente_id', $request->cliente_id)
            ->where('estado', '!=', 'anulada')
            ->where('saldo', '>', 0)
            ->get();

        return response()->json($ventas);
    }

    /**
     * Genera y devuelve el ticket PDF de un pago provisional.
     *
     * @param  int  $id  ID del provisional
     * @return \Illuminate\Http\Response PDF streamed
     */
    public function printTicket($id)
    {
        $provisional = VentaProvisional::with(['cliente'])->findOrFail($id);

        // ────────────────────────────────────────────────────────────
        // BLOQUE: Cálculo del total de deuda del cliente
        // ────────────────────────────────────────────────────────────
        // ¿Qué hace?: Suma los saldos pendientes de todas las ventas
        //              activas del cliente para mostrar el total
        //              adeudado en el ticket del provisional.
        //
        // ¿Por qué filtrar estado != 'anulada'?:  Una venta anulada
        //   ya no representa deuda real (la transacción fue cancelada).
        //   Sin este filtro, ventas con saldo residual huérfano
        //   (generado por bugs en la anulación) inflan la deuda
        //   mostrada al cliente, generando discrepancias con el
        //   reporte de cuenta corriente.
        //
        // Consistencia: Este criterio coincide con
        //   CuentaCorrienteClienteController::creditosCobrarClienteTodos
        //   y con CompraProvisionalController::printTicket.
        // ────────────────────────────────────────────────────────────
        $totalDeuda = Venta::query()
            ->where('cliente_id', $provisional->cliente_id)
            ->where('estado', '!=', 'anulada')
            ->where('saldo', '>', 0)
            ->sum('saldo');

        // Pago realizado (monto del provisional)
        $pagoRealizado = (float) $provisional->monto;

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3'."\n".'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $formatter = new NumeroALetras;
        $total_letras = $formatter->convertir($provisional->monto);

        $pdf = Pdf::loadView('venta-provisionales.ticket', compact('provisional', 'totalDeuda', 'empresa', 'total_letras'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("ticket_{$provisional->id}.pdf");
    }

    /**
     * Devuelve la vista parcial con el detalle de un pago provisional.
     *
     * @param  int  $id  ID del provisional
     */
    public function view($id)
    {
        try {
            $provisional = VentaProvisional::findOrFail($id);

            // Devolver vista parcial
            return view('venta-provisionales.view', compact('provisional'))->render();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }
}
