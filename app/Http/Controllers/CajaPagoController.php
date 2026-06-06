<?php

namespace App\Http\Controllers;

use App\Models\CajaPago;
use App\Models\ComprobanteTipo;
use App\Models\Gasto;
use App\Models\PagoForma;
use App\Models\Producto;
use App\Models\Proveedor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class CajaPagoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:caja_pagos_list')->only(['index', 'view', 'printTicket']);
        $this->middleware('can:caja_pagos_create')->only(['store']);
        $this->middleware('can:caja_pagos_edit')->only(['show', 'update']);
        $this->middleware('can:caja_pagos_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $recibosPlanilla = Gasto::whereNotNull('planilla_mes')
                ->whereNotNull('planilla_anio')
                ->pluck('numero_recibo');

            $data = CajaPago::select([
                'id', 'user_id', 'user_nombre', 'fecha_caja', 'numero_interno', 'numero_recibo', 'entrada', 'salida',
                'persona_tipo', 'persona_id', 'persona_nombre', 'comprobante_tipo_codigo', 'comprobante_tipo_nombre',
                'situacion', 'cobranza_tipo_id', 'cobranza_tipo_nombre', 'comentario',
            ])->whereNotIn('numero_recibo', $recibosPlanilla)->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    /*
                    if(auth()->user()->can('compras_edit')){
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if(auth()->user()->can('compras_delete')){
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->correlativo])->render();
                    }
                    $ticketButton= '<a href="' . route('compras.imprimir', $row->id) . '"
                        target="_blank"
                        class="btn btn-sm btn-secondary"
                        title="Ver Comprobante">
                        <i class="bi bi-printer"></i>
                     </a>';
                     */
                    $ver = '<button class="btn btn-sm btn-info btn-view-caja" data-id="'.$row->id.'" title="Ver Detalle">
                        <i class="bi bi-eye"></i>
                     </button>';

                    // Combinar ambos botones en una cadena y devolverla
                    return '<div class="btn-group">'.$ver.'</div>';
                })
                ->editColumn('fecha_caja', function ($row) {
                    return \Carbon\Carbon::parse($row->fecha_caja)
                        ->timezone('America/Lima')
                        ->format('d/m/Y H:i');
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('caja-pagos.index');
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
        // Convertir fechas antes de validar
        if ($request->filled('fecha_compra')) {
            $request->merge([
                'fecha_compra' => str_replace('T', ' ', $request->fecha_compra).':00',
            ]);
        }

        if ($request->filled('fecha_vencimiento')) {
            $request->merge([
                'fecha_vencimiento' => str_replace('T', ' ', $request->fecha_vencimiento).':00',
            ]);
        }
        $data = $this->validateData($request);
        $data['moneda'] = $data['moneda'] ?? $compra->moneda ?? 'PEN';
        // Obtener proveedor desde el modelo
        $proveedor = Proveedor::find($data['proveedor_id']);
        $data['proveedor_nombre'] = $proveedor->razon_social ?? '';
        $data['proveedor_documento'] = $proveedor->documento_numero ?? '';

        // Obtener comprobante tipo desde el modelo
        $comprobanteTipo = ComprobanteTipo::where('codigo', $data['comprobante_tipo_codigo'])->first();
        $data['comprobante_tipo_nombre'] = $comprobanteTipo->codigo ?? '';

        // Obtener forma de pago desde el modelo
        $pagoForma = PagoForma::where('codigo', $data['pago_forma_codigo'])->first();
        $data['pago_forma_nombre'] = $pagoForma->descripcion ?? '';

        DB::beginTransaction();
        try {
            $compraData = $this->processCompraData($data, true);

            $compra = Compra::create($compraData['compra']);
            $compra->detalles()->createMany($compraData['detalles']);
            Producto::updateStock(true, $compraData['detalles']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro creado satisfactoriamente',
                'compra_id' => $compra->id,
            ]);
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
            $registro = Compra::with([
                'detalles.producto' => function ($query) {
                    $query->select('id', 'afectacion_tipo_codigo', 'codigo', 'nombre', 'costo_unitario', 'unidad_codigo');
                },
                'detalles.producto.afectacionTipo' => function ($query) {
                    $query->select('codigo', 'descripcion', 'porcentaje');
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $compra = Compra::findOrFail($id);
        if ($request->filled('fecha_compra')) {
            $request->merge([
                'fecha_compra' => str_replace('T', ' ', $request->fecha_compra).':00',
            ]);
        }

        if ($request->filled('fecha_vencimiento')) {
            $request->merge([
                'fecha_vencimiento' => str_replace('T', ' ', $request->fecha_vencimiento).':00',
            ]);
        }
        $data = $this->validateData($request);
        $data['moneda'] = $data['moneda'] ?? $compra->moneda ?? 'PEN';
        // Obtener proveedor desde el modelo
        $proveedor = Proveedor::find($data['proveedor_id']);
        $data['proveedor_nombre'] = $proveedor->razon_social ?? '';
        $data['proveedor_documento'] = $proveedor->documento_numero ?? '';

        // Obtener comprobante tipo desde el modelo
        $comprobanteTipo = ComprobanteTipo::where('codigo', $data['comprobante_tipo_codigo'])->first();
        $data['comprobante_tipo_nombre'] = $comprobanteTipo->codigo ?? '';

        // Obtener forma de pago desde el modelo
        $pagoForma = PagoForma::where('codigo', $data['pago_forma_codigo'])->first();
        $data['pago_forma_nombre'] = $pagoForma->descripcion ?? '';

        DB::beginTransaction();
        try {
            $compraData = $this->processCompraData($data, false);

            $compra->update($compraData['compra']);
            Producto::updateStock(false, $compra->detalles->toArray());
            $compra->detalles()->delete();
            $compra->detalles()->createMany($compraData['detalles']);
            Producto::updateStock(true, $compraData['detalles']);

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
            $registro = Compra::findOrFail($id);
            Producto::updateStock(false, $registro->detalles->toArray());
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

    private function processCompraData(array $data, bool $isNew = true)
    {
        $productos = Producto::with('afectacionTipo', 'unidad')
            ->whereIn('id', collect($data['detalles'])->pluck('producto_id'))
            ->get()
            ->keyBy('id');

        $totales = [
            'op_gravada' => 0,
            'op_exonerada' => 0,
            'op_inafecta' => 0,
            'impuesto' => 0,
            'total' => 0,
        ];

        $detallesCalculados = [];
        foreach ($data['detalles'] as $detalle) {
            $detallesCalculados[] = $this->calculateDetail($productos[$detalle['producto_id']], $detalle['cantidad'], $detalle['precio_unitario'], $detalle['precio_unitario_servicio'], $totales);
        }

        $compraData = [
            'proveedor_id' => $data['proveedor_id'],
            'proveedor_nombre' => $data['proveedor_nombre'] ?? '',
            'proveedor_documento' => $data['proveedor_documento'] ?? '',

            'comprobante_tipo_codigo' => $data['comprobante_tipo_codigo'],
            'comprobante_tipo_nombre' => $data['comprobante_tipo_nombre'] ?? '',

            'serie' => $data['serie'],
            'correlativo' => $data['correlativo'],

            'pago_forma_codigo' => $data['pago_forma_codigo'],
            'pago_forma_nombre' => $data['pago_forma_nombre'] ?? '',

            'moneda' => $data['moneda'],
            'op_gravada' => round($totales['op_gravada'], 2),
            'op_exonerada' => round($totales['op_exonerada'], 2),
            'op_inafecta' => round($totales['op_inafecta'], 2),
            'impuesto' => round($totales['impuesto'], 2),
            'total' => round($totales['total'], 2),
            'fecha_compra' => $data['fecha_compra'] ?? now(),
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
        ];

        if ($isNew) {
            // $compraData['fecha_compra'] = now();
            $compraData['estado'] = 'registrado';
            $compraData['user_id'] = auth()->id();
            $compraData['user_nombre'] = auth()->user()->name;
        }

        return [
            'compra' => $compraData,
            'detalles' => $detallesCalculados,
        ];
    }

    private function calculateDetail($producto, $cantidad, $precio_unitario_input, $precio_servicio_input, array &$totales)
    {
        $precio_unitario = $precio_unitario_input;
        $precio_unitario_servicio = $precio_servicio_input;
        $porcentajeImpuesto = optional($producto->afectacionTipo)->porcentaje ?? 0;
        $costo_unitario = $porcentajeImpuesto > 0 ? $precio_unitario / (1 + $porcentajeImpuesto) : $precio_unitario;
        $subtotal = $costo_unitario * $cantidad;
        $detalleImpuesto = ($precio_unitario - $costo_unitario) * $cantidad;
        $detalleTotal = $precio_unitario * $cantidad;

        // Acumular totales
        switch ($producto->afectacion_tipo_codigo) {
            case '10': $totales['op_gravada'] += $subtotal;
                break;
            case '20': $totales['op_exonerada'] += $subtotal;
                break;
            case '30': $totales['op_inafecta'] += $subtotal;
                break;
        }
        $totales['impuesto'] += $detalleImpuesto;
        $totales['total'] += $detalleTotal;

        return [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,                 // <--- corregido
            'unidad_codigo' => $producto->unidad->codigo ?? '',
            'unidad_nombre' => $producto->unidad->descripcion ?? '',   // <--- relación unidad
            'producto_empaque' => $producto->empaque ?? 0,
            'cantidad' => $cantidad,
            'cantidad_kgm' => $cantidad * $producto->empaque,
            'costo_unitario' => round($precio_unitario, 2),
            'costo_unitario_servicio' => round($precio_unitario_servicio, 2),
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
            'proveedor_id' => 'required|exists:proveedores,id',
            'comprobante_tipo_codigo' => 'required|exists:comprobante_tipos,codigo',
            'serie' => 'required|string|max:4',
            'correlativo' => 'required|integer|min:1',
            'pago_forma_codigo' => 'required|exists:pago_formas,codigo',
            'moneda' => 'nullable|string|size:3',
            'fecha_compra' => 'required|date_format:Y-m-d H:i:s',
            'fecha_vencimiento' => 'nullable|date_format:Y-m-d H:i:s|after_or_equal:fecha_compra',

            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario_servicio' => 'nullable|numeric|min:0.01',
        ]);
    }

    public function printTicket($id)
    {
        $compra = Compra::with(['proveedor'])->findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'Consorcios Villegas E.I.R.L.',
            'direccion' => 'Cal. Inca Roca Nro. 1210 - La Victoria - Chiclayo',
            'ruc' => '20538937321',
        ];

        $pdf = Pdf::loadView('compras.ticket', compact('compra', 'empresa'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("ticket_{$compra->id}.pdf");
    }

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
}
