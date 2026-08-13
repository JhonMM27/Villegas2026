<?php

/**
 * Controlador de Compras Provisionales (Pagos Anticipados a Proveedores).
 *
 * Maneja la entrada/salida HTTP del módulo de compras provisionales.
 * - Listado con DataTables (index)
 * - Consulta de registro para edición (show)
 * - CRUD delegado a CompraProvisionalService (store, update, destroy)
 * - Búsqueda para select2 (buscar)
 * - Consulta de compras con saldo (comprasConSaldo)
 * - Generación de PDF para ticket (printTicket)
 * - Vista detallada parcial (view)
 */

namespace App\Http\Controllers;

use App\Helpers\NumeroALetras;
use App\Models\Compra;
use App\Models\CompraProvisional;
use App\Models\Proveedor;
use App\Services\CompraProvisionalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CompraProvisionalController extends Controller
{
    /**
     * Constructor: inyecta el servicio y define los middleware de permisos.
     *
     * @param  CompraProvisionalService  $compraProvisionalService  Servicio con la lógica de negocio
     */
    public function __construct(
        protected CompraProvisionalService $compraProvisionalService
    ) {
        $this->middleware('can:compra_provisionales_list')->only(['index', 'imprimir']);
        $this->middleware('can:compra_provisionales_create')->only(['store']);
        $this->middleware('can:compra_provisionales_edit')->only(['show', 'update']);
        $this->middleware('can:compra_provisionales_delete')->only(['destroy']);
    }

    /**
     * Listado + DataTables
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $tipo = $request->get('tipo', 'T');

            $data = DB::table('compra_provisionales as v')
                ->selectRaw("
                v.id,
                v.user_nombre,
                v.fecha_provisional,
                v.numero_recibo,
                v.numero_interno,
                v.proveedor_nombre,
                v.monto,
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
                    FROM compra_provisional_detalles d
                    WHERE d.compra_provisional_id = v.id
                ) as documentos
            ");
            if ($tipo === 'PL') {
                $data->where('v.libre', '>', 0);
            }

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('compra_provisionales_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('compra_provisionales_delete')) {
                        $texto = trim(($row->proveedor_nombre ?? '').' - '.($row->numero_recibo ?? ''));
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $texto])->render();
                    }
                    $ticketButton = '<a href="'.route('compra-provisionales.imprimir', $row->id).'" 
                        target="_blank" 
                        class="btn btn-sm btn-secondary" 
                        title="Ver Comprobante">
                        <i class="bi bi-printer"></i>
                     </a>';
                    $ver = '<button class="btn btn-sm btn-primary btn-view-compra" data-id="'.$row->id.'" title="Ver Provisional">
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

        return view('compra-provisionales.index');
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
            $provisional = $this->compraProvisionalService->createProvisional($data);

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'compra_provisional_id' => $provisional->id,
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
            $registro = CompraProvisional::query()
                ->with([
                    'detalles.compra' => function ($q) {
                        $q->select(
                            'id',
                            'fecha_compra',
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
            $this->compraProvisionalService->updateProvisional($id, $data);

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
            $this->compraProvisionalService->deleteProvisional($id);

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
     * Valida los datos del request para un pago provisional de compra.
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

            'proveedor_id' => 'required|exists:proveedores,id',
            'proveedor_nombre' => 'required|string|max:100',

            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
            'total_cobranza' => 'nullable|numeric|min:0.01',

            // compras es opcional: si es adelanto puede no venir o venir vacío
            'compras' => 'nullable|array',
            'compras.*.compra_id' => 'nullable|exists:compras,id',
            'compras.*.comprobante_tipo_codigo' => 'nullable|string|max:10',
            'compras.*.serie' => 'nullable|string|max:10',
            'compras.*.correlativo' => 'nullable|string|max:20',
            'compras.*.monto' => 'nullable|numeric|min:0',
        ];

        $messages = [
            'compras.array' => 'El formato de compras es inválido.',
            'compras.*.compra_id.exists' => 'Una de las compras seleccionadas no existe.',
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

        return CompraProvisional::query()
            ->where('id', $q)
            ->orWhere('numero_recibo', 'like', "%{$q}%")
            ->orWhere('numero_interno', 'like', "%{$q}%")
            ->orWhere('proveedor_nombre', 'like', "%{$q}%")
            ->select('id', 'numero_recibo', 'numero_interno', 'proveedor_nombre', 'fecha_provisional', 'monto')
            ->orderByDesc('fecha_provisional')
            ->limit(10)
            ->get();
    }

    /**
     * Consulta compras con saldo pendiente para un proveedor, soportando provisional_id opcional.
     *
     * @param  Request  $request  Requiere 'proveedor_id', 'provisional_id' (opcional)
     * @return \Illuminate\Http\JsonResponse
     */
    public function comprasConSaldo(Request $request)
    {
        $proveedorId = $request->input('proveedor_id');
        $provisionalId = $request->input('provisional_id');

        if (! $proveedorId) {
            return response()->json([]);
        }

        $compras = Compra::where('proveedor_id', $proveedorId)
            ->where('estado', '!=', 'anulada')
            ->get();

        $aplicacionesPrevias = [];
        if ($provisionalId) {
            $detalles = DB::table('compra_provisional_detalles')
                ->where('compra_provisional_id', $provisionalId)
                ->whereNotNull('compra_id')
                ->get();
            foreach ($detalles as $d) {
                $aplicacionesPrevias[(int) $d->compra_id] = (float) $d->monto;
            }
        }

        $resultado = [];
        foreach ($compras as $c) {
            $montoPrevio = $aplicacionesPrevias[$c->id] ?? 0.0;
            $saldoDisponible = round((float) $c->saldo + $montoPrevio, 2);

            if ($saldoDisponible > 0) {
                $cArray = $c->toArray();
                $cArray['saldo_disponible'] = $saldoDisponible;
                $cArray['monto_aplicado_provisional'] = $montoPrevio;
                $resultado[] = $cArray;
            }
        }

        return response()->json($resultado);
    }

    /**
     * Genera y devuelve el ticket PDF de un pago provisional de compra.
     *
     * @param  int  $id  ID del provisional
     * @return \Illuminate\Http\Response PDF streamed
     */
    public function printTicket($id)
    {
        $provisional = CompraProvisional::with(['proveedor.documentoTipo'])->findOrFail($id);

        // ────────────────────────────────────────────────────────────
        // BLOQUE: Cálculo del total de deuda del proveedor
        // ────────────────────────────────────────────────────────────
        // ¿Qué hace?: Suma los saldos pendientes de todas las compras
        //              activas del proveedor para mostrar el total
        //              adeudado en el ticket del provisional.
        //
        // ¿Por qué filtrar estado != 'anulada'?:  Una compra anulada
        //   ya no representa deuda real (la transacción fue cancelada).
        //   Sin este filtro, compras con saldo residual huérfano
        //   (generado por bugs en la anulación) inflan la deuda
        //   mostrada al proveedor.
        //
        // Consistencia: Este criterio coincide con
        //   CuentaCorrienteClienteController y con
        //   VentaProvisionalController::printTicket.
        // ────────────────────────────────────────────────────────────
        $totalDeuda = Compra::query()
            ->where('proveedor_id', $provisional->proveedor_id)
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

        $pdf = Pdf::loadView('compra-provisionales.ticket', compact('provisional', 'totalDeuda', 'empresa', 'total_letras'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("ticket_{$provisional->id}.pdf");
    }

    /**
     * Devuelve la vista parcial con el detalle de un pago provisional de compra.
     *
     * @param  int  $id  ID del provisional
     * @return \Illuminate\Contracts\View\View|JsonResponse
     */
    public function view($id)
    {
        try {
            $provisional = CompraProvisional::findOrFail($id);

            // Devolver vista parcial
            return view('compra-provisionales.view', compact('provisional'))->render();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }
}
