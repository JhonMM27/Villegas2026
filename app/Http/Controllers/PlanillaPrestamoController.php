<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlanillaPrestamo;
use App\Services\PlanillaPrestamoService;
use App\Services\TicketPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class PlanillaPrestamoController extends Controller
{
    public function __construct(
        protected PlanillaPrestamoService $prestamoService
    ) {
        $this->middleware('can:planilla_prestamos_list')->only(['index', 'view', 'edit', 'printTicket']);
        $this->middleware('can:planilla_prestamos_create')->only(['store']);
        $this->middleware('can:planilla_prestamos_edit')->only(['update']);
        $this->middleware('can:planilla_prestamos_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');
            $data = $this->prestamoService->getAll($fechaInicio, $fechaFin);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<button class="btn btn-sm btn-info me-1" data-id="'.$row->id.'" onclick="window.prestamoManager.verDetalle('.$row->id.')">
                        <i class="bi bi-eye"></i>
                    </button>';

                    $buttons .= '<a href="'.route('planilla-prestamos.imprimir', $row->id).'" target="_blank" class="btn btn-sm btn-secondary me-1" title="Imprimir">
                        <i class="bi bi-printer"></i>
                    </a>';

                    if (auth()->user()->can('planilla_prestamos_edit')) {
                        $buttons .= '<button class="btn btn-sm btn-warning me-1" data-id="'.$row->id.'" onclick="window.prestamoManager.showEditModal('.$row->id.')">
                            <i class="bi bi-pencil"></i>
                        </button>';
                    }

                    if (auth()->user()->can('planilla_prestamos_delete')) {
                        $buttons .= '<button class="btn btn-sm btn-danger me-1" data-id="'.$row->id.'" onclick="window.prestamoManager.confirmDelete('.$row->id.')">
                            <i class="bi bi-trash"></i>
                        </button>';
                    }

                    // if ($row->estado === 'activo') {
                    //     $buttons .= '<button class="btn btn-sm btn-success me-1" data-id="'.$row->id.'" onclick="window.prestamoManager.mostrarPago('.$row->id.')">
                    //         <i class="bi bi-cash"></i>
                    //     </button>';
                    // }

                    return '<div class="btn-group">'.$buttons.'</div>';
                })
                ->editColumn('empleado_id', fn ($row) => $row->empleado->nombre)
                ->editColumn('monto_original', fn ($row) => 'S/'.number_format((float) $row->monto_original, 2))
                ->editColumn('saldo_pendiente', fn ($row) => 'S/'.number_format((float) $row->saldo_pendiente, 2))
                ->editColumn('fecha_prestamo', fn ($row) => $row->fecha_prestamo->format('d/m/Y'))
                ->editColumn('estado', fn ($row) => match ($row->estado) {
                    'activo' => '<span class="badge bg-success">Activo</span>',
                    'pagado' => '<span class="badge bg-primary">Pagado</span>',
                    'anulado' => '<span class="badge bg-secondary">Anulado</span>',
                })
                ->rawColumns(['action', 'estado'])
                ->make(true);
        }

        return view('planilla.prestamos.index');
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validateData($request);
            $prestamo = $this->prestamoService->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Préstamo registrado correctamente',
                'prestamo_id' => $prestamo->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function edit($id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        return response()->json(['prestamo' => $prestamo]);
    }

    public function show($id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        return response()->json(['prestamo' => $prestamo]);
    }

    public function getMontos($id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        $prestamo->refresh();

        return response()->json([
            'monto_original' => (float) $prestamo->monto_original,
            'total_pagado' => (float) $prestamo->total_pagado,
            'saldo_pendiente' => (float) $prestamo->saldo_pendiente,
            'estado' => $prestamo->estado,
        ]);
    }

    public function update(Request $request, $id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        try {
            $data = $this->validateData($request, $id);
            $this->prestamoService->update($prestamo, $data);

            return response()->json([
                'success' => true,
                'message' => 'Préstamo actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        $this->prestamoService->delete($prestamo);

        return response()->json([
            'success' => true,
            'message' => 'Préstamo eliminado correctamente',
        ]);
    }

    public function view($id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['error' => 'Préstamo no encontrado'], 404);
        }

        return view('planilla.prestamos.view', compact('prestamo'));
    }

    public function registrarPago(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'numero_interno' => 'required|integer|min:1',
                'monto_pagado' => 'required|numeric|min:0.01',
                'fecha_pago' => 'required|date',
                'observaciones' => 'nullable|string',
                'importe_p' => 'nullable|numeric|min:0',
                'importe_d' => 'nullable|numeric|min:0',
                'importe_c' => 'nullable|numeric|min:0',
            ]);

            $pago = $this->prestamoService->registrarPago((int) $id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'pago_id' => $pago->id,
                'numero_interno' => $pago->numero_interno,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function pagosIndex($prestamoId)
    {
        $prestamo = $this->prestamoService->findById((int) $prestamoId);
        if (! $prestamo) {
            abort(404, 'Préstamo no encontrado');
        }

        return view('planilla.prestamos.pagos_index', compact('prestamo'));
    }

    public function showPago($id)
    {
        $pago = $this->prestamoService->findPagoById((int) $id);
        if (! $pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        return response()->json(['pago' => $pago]);
    }

    public function editPago($id)
    {
        $pago = $this->prestamoService->findPagoById((int) $id);
        if (! $pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        return response()->json(['pago' => $pago]);
    }

    public function updatePago(Request $request, $id)
    {
        $pago = $this->prestamoService->findPagoById((int) $id);
        if (! $pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        try {
            $data = $request->validate([
                'numero_interno' => 'required|integer|min:1',
                'monto_pagado' => 'required|numeric|min:0.01',
                'fecha_pago' => 'required|date',
                'observaciones' => 'nullable|string',
                'importe_p' => 'nullable|numeric|min:0',
                'importe_d' => 'nullable|numeric|min:0',
                'importe_c' => 'nullable|numeric|min:0',
            ]);

            $this->prestamoService->updatePago($pago, $data);

            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroyPago($id)
    {
        $pago = $this->prestamoService->findPagoById((int) $id);
        if (! $pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        try {
            $this->prestamoService->deletePago($pago);

            return response()->json([
                'success' => true,
                'message' => 'Pago eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function printPagoTicket($id)
    {
        $pago = $this->prestamoService->findPagoById((int) $id);
        if (! $pago) {
            abort(404, 'Pago no encontrado');
        }

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3. Atras de Ferreteria Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $pdf = app(TicketPdfService::class)->render('planilla.prestamos.pago_ticket', compact('pago', 'empresa'));

        return $pdf->stream("pago_prestamo_{$pago->numero_interno}.pdf");
    }

    public function pagosData(Request $request, $prestamoId)
    {
        if ($request->ajax()) {
            $pagos = $this->prestamoService->getPagos((int) $prestamoId);

            $data = $pagos->map(function ($row) {
                $buttons = '';

                $buttons .= '<button class="btn btn-sm btn-info me-1" onclick="window.pagoManager.verPago('.$row->id.')">
                    <i class="bi bi-eye"></i>
                </button>';

                $buttons .= '<a href="'.route('planilla-prestamos.pagos-imprimir', $row->id).'" target="_blank" class="btn btn-sm btn-secondary me-1">
                    <i class="bi bi-printer"></i>
                </a>';

                if (auth()->user()->can('planilla_prestamos_edit')) {
                    $buttons .= '<button class="btn btn-sm btn-warning me-1" onclick="window.pagoManager.mostrarEditarPago('.$row->id.')">
                        <i class="bi bi-pencil"></i>
                    </button>';
                }

                if (auth()->user()->can('planilla_prestamos_delete')) {
                    $buttons .= '<button class="btn btn-sm btn-danger me-1" onclick="window.pagoManager.confirmDelete('.$row->id.')">
                        <i class="bi bi-trash"></i>
                    </button>';
                }

                return [
                    'action' => '<div class="btn-group">'.$buttons.'</div>',
                    'numero_interno' => str_pad((string) $row->numero_interno, 6, '0', STR_PAD_LEFT),
                    'fecha_pago' => $row->fecha_pago->format('d/m/Y'),
                    'monto_pagado' => 'S/'.number_format((float) $row->monto_pagado, 2),
                    'observaciones' => $row->observaciones ?? '-',
                ];
            });

            return response()->json([
                'data' => $data,
            ]);
        }

        return abort(403);
    }

    public function getCuotas($id)
    {
        $prestamo = $this->prestamoService->findById((int) $id);
        if (! $prestamo) {
            return response()->json(['error' => 'Préstamo no encontrado'], 404);
        }

        return response()->json($prestamo->pagos);
    }

    protected function validateData(Request $request, $id = null)
    {
        $uniqueRule = $id
            ? "unique:planilla_prestamos,numero_interno,{$id},id"
            : 'unique:planilla_prestamos,numero_interno';

        $rules = [
            'empleado_id' => 'required|exists:empleados,id',
            'numero_interno' => "required|integer|{$uniqueRule}",
            'monto_original' => 'required|numeric|min:0.01',
            'fecha_prestamo' => 'required|date',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:activo,pagado,anulado',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            Log::warning('Validacion fallo en PlanillaPrestamo', [
                'id' => $id,
                'errors' => $validator->errors()->toArray(),
                'data' => $request->only([
                    'empleado_id', 'numero_interno', 'monto_original', 'fecha_prestamo', 'estado',
                    'principal', 'deposito', 'consorcio',
                ]),
            ]);
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function printTicket($id)
    {
        $prestamo = PlanillaPrestamo::with(['empleado', 'pagos'])->findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3. Atras de Ferreteria Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $pdf = app(TicketPdfService::class)->render('planilla.prestamos.ticket', compact('prestamo', 'empresa'));

        return $pdf->stream("prestamo_{$prestamo->numero_interno}.pdf");
    }
}
