<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\NumeroALetras;
use App\Models\Cliente;
use App\Models\ComprobanteSerie;
use App\Models\ComprobanteTipo;
use App\Models\Cotizacion;
use App\Models\PagoForma;
use App\Models\Producto;
use App\Services\TicketPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CotizacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:cotizaciones_list')->only(['index', 'view', 'printTicket']);
        $this->middleware('can:cotizaciones_create')->only(['store']);
        $this->middleware('can:cotizaciones_edit')->only(['show', 'update']);
        $this->middleware('can:cotizaciones_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Cotizacion::select([
                'id', 'user_nombre', 'cliente_nombre', 'comprobante_tipo_nombre',
                'pago_forma_nombre', 'serie', 'correlativo',
                DB::raw('DATE(fecha_cotizacion) as fecha_cotizacion'), 'moneda', 'op_gravada',
                'op_exonerada', 'op_inafecta', 'impuesto', 'total', 'estado', 'venta_id',
            ])->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('cotizaciones_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('cotizaciones_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->correlativo])->render();
                    }
                    $ticketButton = '<a href="'.route('cotizaciones.imprimir', $row->id).'" 
                        target="_blank" 
                        class="btn btn-sm btn-secondary" 
                        title="Ver Comprobante">
                        <i class="bi bi-printer"></i>
                     </a>';
                    $ver = '<button class="btn btn-sm btn-primary btn-view-cotizacion" data-id="'.$row->id.'" title="Ver Cotización">
                        <i class="bi bi-eye"></i>
                     </button>';
                    // Botón para generar venta desde cotización
                    $generarVentaButton = '';
                    if (is_null($row->venta_id)) {
                        // Si no tiene venta_id, mostrar botón para generar venta
                        $generarVentaButton = '<a href="'.route('ventas.index').'?cotizacion_id='.$row->id.'" 
                            class="btn btn-sm btn-success" 
                            title="Generar Venta">
                            <i class="bi bi-cart-plus"></i>
                         </a>';
                    } else {
                        // Si ya tiene venta_id, mostrar botón para ver la venta
                        $generarVentaButton = '<button 
                            class="btn btn-sm btn-info btn-view-venta" 
                            data-id="'.$row->venta_id.'" 
                            title="Ver Venta Generada">
                            <i class="bi bi-receipt"></i>
                         </button>';
                    }

                    // Combinar ambos botones en una cadena y devolverla
                    return '<div class="btn-group">'.$editButton.$deleteButton.$ver.$ticketButton.$generarVentaButton.'</div>';
                })
                // ->addColumn('usuario', fn($row) => optional($row->user)->name)
                ->rawColumns(['action', 'estado'])
                ->editColumn('estado', function ($row) {
                    return '<span class="badge bg-primary">'.$row->estado.'</span>';
                })
                ->editColumn('fecha_cotizacion', function ($row) {
                    return \Carbon\Carbon::parse($row->fecha_cotizacion)->format('Y-m-d');
                })
                ->make(true);
        }

        return view('cotizaciones.index');
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
        if ($request->filled('fecha_cotizacion')) {
            $request->merge([
                'fecha_cotizacion' => str_replace('T', ' ', $request->fecha_cotizacion).':00',
            ]);
        }

        $data = $this->validateData($request);

        DB::beginTransaction();
        try {

            // ✅ lock dentro de la transacción
            $serieConfig = ComprobanteSerie::where('comprobante_tipo_codigo', $data['comprobante_tipo_codigo'])
                ->where('serie', $data['serie'])
                ->lockForUpdate()
                ->firstOrFail();

            $correlativo = (int) $serieConfig->correlativo;

            // ✅ correlativo manda backend
            $data['correlativo'] = $correlativo;

            // ✅ en store NO existe $cotizacion
            $data['moneda'] = $data['moneda'] ?? 'PEN';

            $cliente = Cliente::find($data['cliente_id']);
            $data['cliente_nombre'] = $cliente->razon_social ?? '';

            $comprobanteTipo = ComprobanteTipo::where('codigo', $data['comprobante_tipo_codigo'])->first();
            $data['comprobante_tipo_nombre'] = $comprobanteTipo->codigo ?? '';

            $pagoForma = PagoForma::where('codigo', $data['pago_forma_codigo'])->first();
            $data['pago_forma_nombre'] = $pagoForma->descripcion ?? '';

            $cotizacionData = $this->processCotizacionData($data, true);

            $cotizacion = Cotizacion::create($cotizacionData['cotizacion']);
            $cotizacion->detalles()->createMany($cotizacionData['detalles']);

            // ✅ incrementar correlativo (todavía dentro de la misma transacción)
            $serieConfig->update([
                'correlativo' => $correlativo + 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'cotizacion_id' => $cotizacion->id,
                'correlativo' => $correlativo,
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'No se pudo reservar correlativo. Intente nuevamente.',
            ], 409);

        } catch (\Exception $e) {
            DB::rollBack();

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
            // $registro = Venta::with(['detalles.producto.afectacionTipo', 'cliente'])->findOrFail($id);
            $registro = Cotizacion::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'afectacion_tipo_codigo', 'codigo', 'nombre', 'costo_unitario', 'unidad_codigo', 'stock_almacen');
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
    public function edit(Cotizacion $cotizacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        if ($request->filled('fecha_cotizacion')) {
            $request->merge([
                'fecha_cotizacion' => str_replace('T', ' ', $request->fecha_cotizacion).':00',
            ]);
        }

        $data = $this->validateData($request);
        $data['moneda'] = $data['moneda'] ?? 'PEN';
        // Obtener cliente desde el modelo
        $cliente = Cliente::find($data['cliente_id']);
        $data['cliente_nombre'] = $cliente->razon_social ?? '';

        // Obtener comprobante tipo desde el modelo
        $comprobanteTipo = ComprobanteTipo::where('codigo', $data['comprobante_tipo_codigo'])->first();
        $data['comprobante_tipo_nombre'] = $comprobanteTipo->codigo ?? '';

        // Obtener forma de pago desde el modelo
        $pagoForma = PagoForma::where('codigo', $data['pago_forma_codigo'])->first();
        $data['pago_forma_nombre'] = $pagoForma->descripcion ?? '';

        DB::beginTransaction();
        try {
            $cotizacionData = $this->processCotizacionData($data, false);

            $cotizacion->update($cotizacionData['cotizacion']);
            $cotizacion->detalles()->delete();
            $cotizacion->detalles()->createMany($cotizacionData['detalles']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado satisfactoriamente',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $registro = Cotizacion::findOrFail($id);
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

    private function processCotizacionData(array $data, bool $isNew = true)
    {
        $productos = Producto::with('afectacionTipo', 'unidad')
            ->whereIn('id', collect($data['detalles'])->pluck('producto_id'))
            ->get()
            ->keyBy('id');

        $totales = [
            'salida_kg' => 0.00,
            'ingreso_kg' => 0.00,
            'ingreso_saco' => 0.00,
        ];

        $detallesCalculados = [];
        foreach ($data['detalles'] as $detalle) {
            $detallesCalculados[] = $this->calculateDetail($productos[$detalle['producto_id']], $detalle, $totales);
        }
        $totalItems = count($data['detalles']);

        $cotizacionData = [
            'cliente_id' => $data['cliente_id'],
            'cliente_nombre' => $data['cliente_nombre'] ?? '',
            'items' => $totalItems,
            'comprobante_tipo_codigo' => $data['comprobante_tipo_codigo'],
            'comprobante_tipo_nombre' => $data['comprobante_tipo_nombre'] ?? '',

            'serie' => $data['serie'],
            'correlativo' => $data['correlativo'],
            'fecha_cotizacion' => $data['fecha_cotizacion'] ?? now(),

            'pago_forma_codigo' => $data['pago_forma_codigo'],
            'pago_forma_nombre' => $data['pago_forma_nombre'] ?? '',

            'moneda' => $data['moneda'],
            // Los formularios llegan como texto aunque la validación `numeric` sea correcta.
            // Normalizamos antes de usar funciones aritméticas bajo strict_types.
            'op_gravada' => round((float) ($data['op_gravada'] ?? 0), 2),
            'op_exonerada' => round((float) ($data['op_exonerada'] ?? 0), 2),
            'op_inafecta' => round((float) ($data['op_inafecta'] ?? 0), 2),
            'impuesto' => round((float) ($data['impuesto'] ?? 0), 2),
            'total' => round((float) ($data['total'] ?? 0), 2),
        ];

        if ($isNew) {
            // $cotizacionData['fecha_venta'] = now();
            $cotizacionData['estado'] = 'registrado';
            $cotizacionData['user_id'] = auth()->id();
            $cotizacionData['user_nombre'] = auth()->user()->name;
        }

        return [
            'cotizacion' => $cotizacionData,
            'detalles' => $detallesCalculados,
        ];
    }

    private function calculateDetail($producto, $detalle, array &$totales)
    {
        $unidad_codigo = $detalle['unidad_codigo'];
        $precio_unitario = (float) $detalle['precio_unitario'];
        $cantidad = (float) $detalle['cantidad'];
        $porcentajeImpuesto = (float) (optional($producto->afectacionTipo)->porcentaje ?? 0);
        $valor_unitario = $porcentajeImpuesto > 0 ? $precio_unitario / (1 + $porcentajeImpuesto) : $precio_unitario;
        // Usar el total enviado desde la vista si está disponible
        $detalleTotal = isset($detalle['total']) ? (float) $detalle['total'] : round($precio_unitario * $cantidad, 2);
        $subtotal = $porcentajeImpuesto > 0 ? $detalleTotal / (1 + $porcentajeImpuesto) : $detalleTotal;
        $detalleImpuesto = $detalleTotal - $subtotal;
        $empaque = (float) $detalle['empaque'];

        $salidaKg = $cantidad * $empaque;

        $totales['salida_kg'] += $salidaKg;
        $totales['ingreso_kg'] = 0.00;
        $totales['ingreso_saco'] += 0.00;

        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'producto_empaque' => $empaque ?? 0,
            'unidad_codigo' => $unidad_codigo ?? '',
            'cantidad' => $cantidad,
            'precio_unitario' => round($precio_unitario, 4),
            'subtotal' => round($subtotal, 2),
            'porcentaje_impuesto' => $porcentajeImpuesto,
            'impuesto' => round($detalleImpuesto, 2),
            'total' => round($detalleTotal, 2),
        ];
    }

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
            'fecha_cotizacion' => 'required|date_format:Y-m-d H:i:s',

            // Totales enviados desde la vista
            'total' => 'nullable|numeric|min:0',
            'op_gravada' => 'nullable|numeric|min:0',
            'op_exonerada' => 'nullable|numeric|min:0',
            'op_inafecta' => 'nullable|numeric|min:0',
            'impuesto' => 'nullable|numeric|min:0',

            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.unidad_codigo' => 'required|exists:unidades,codigo',
            'detalles.*.empaque' => 'required|numeric',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.total' => 'nullable|numeric|min:0',
        ]);
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

    public function printTicket($id)
    {
        $cotizacion = Cotizacion::with(['cliente', 'detalles'])->findOrFail($id);

        $correlativoFormateado = str_pad((string) $cotizacion->correlativo, 8, '0', STR_PAD_LEFT);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3'."\n".'A espaldas de Ferretería Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $formatter = new NumeroALetras;
        $total_letras = $formatter->convertir($cotizacion->total);

        $pdf = app(TicketPdfService::class)->render('cotizaciones.ticket', compact('cotizacion', 'empresa', 'total_letras'));

        return $pdf->stream("COT - {$cotizacion->serie}-{$correlativoFormateado}.pdf");
    }

    public function view($id)
    {
        try {
            $cotizacion = Cotizacion::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'nombre', 'afectacion_tipo_codigo', 'codigo', 'costo_unitario');
                },
                'cliente' => function ($query) {
                    $query->select('id', 'razon_social', 'documento_numero');
                },
            ])->findOrFail($id);

            // Devolver vista parcial
            return view('cotizaciones.view', compact('cotizacion'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }
}
