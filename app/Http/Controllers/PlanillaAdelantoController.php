<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlanillaAdelanto;
use App\Services\EmpleadoService;
use App\Services\PlanillaAdelantoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class PlanillaAdelantoController extends Controller
{
    public function __construct(
        protected PlanillaAdelantoService $adelantoService,
        protected EmpleadoService $empleadoService
    ) {
        $this->middleware('can:planilla_adelantos_list')->only(['index', 'edit', 'printTicket']);
        $this->middleware('can:planilla_adelantos_create')->only(['store']);
        $this->middleware('can:planilla_adelantos_edit')->only(['update']);
        $this->middleware('can:planilla_adelantos_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PlanillaAdelanto::with('empleado')
                ->whereHas('empleado', fn ($q) => $q->where('estado', 'activo'))
                ->select(['id', 'numero_interno', 'empleado_id', 'monto', 'fecha', 'observaciones']);

            $mes = $request->get('mes');
            if ($mes && preg_match('/^(\d{4})-(\d{2})$/', $mes, $m)) {
                $data->whereYear('fecha', (int) $m[1])
                    ->whereMonth('fecha', (int) $m[2]);
            }

            $data->orderByDesc('id');

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<button class="btn btn-sm btn-info me-1" onclick="adelantoManager.verDetalle('.$row->id.')">
                        <i class="bi bi-eye"></i>
                    </button>';

                    $buttons .= '<a href="'.route('planilla-adelantos.imprimir', $row->id).'" target="_blank" class="btn btn-sm btn-secondary me-1" title="Imprimir">
                        <i class="bi bi-printer"></i>
                    </a>';

                    if (auth()->user()->can('planilla_adelantos_edit')) {
                        $buttons .= '<button class="btn btn-sm btn-warning me-1" onclick="adelantoManager.showEditModal('.$row->id.')"><i class="bi bi-pencil"></i></button>';
                    }

                    if (auth()->user()->can('planilla_adelantos_delete')) {
                        $buttons .= '<button class="btn btn-sm btn-danger me-1" onclick="window.adelantoManager.confirmDelete('.$row->id.')">
                            <i class="bi bi-trash"></i>
                        </button>';
                    }

                    return '<div class="btn-group">'.$buttons.'</div>';
                })
                ->editColumn('empleado_id', fn ($row) => $row->empleado->nombre)
                ->editColumn('monto', fn ($row) => 'S/'.number_format((float) $row->monto, 2))
                ->editColumn('fecha', fn ($row) => $row->fecha->format('d/m/Y'))
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('planilla.adelantos.index');
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validateData($request);
            $this->adelantoService->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Adelanto registrado correctamente',
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
        $adelanto = $this->adelantoService->findById((int) $id);
        if (! $adelanto) {
            return response()->json(['success' => false, 'message' => 'Adelanto no encontrado'], 404);
        }

        return response()->json(['adelanto' => $adelanto]);
    }

    public function show($id)
    {
        $adelanto = $this->adelantoService->findById((int) $id);
        if (! $adelanto) {
            return response()->json(['success' => false, 'message' => 'Adelanto no encontrado'], 404);
        }

        return response()->json(['adelanto' => $adelanto]);
    }

    public function update(Request $request, $id)
    {
        $adelanto = $this->adelantoService->findById((int) $id);
        if (! $adelanto) {
            return response()->json(['success' => false, 'message' => 'Adelanto no encontrado'], 404);
        }

        try {
            $data = $this->validateData($request, $id);
            $this->adelantoService->update($adelanto, $data);

            return response()->json([
                'success' => true,
                'message' => 'Adelanto actualizado correctamente',
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
        $adelanto = $this->adelantoService->findById((int) $id);
        if (! $adelanto) {
            return response()->json(['success' => false, 'message' => 'Adelanto no encontrado'], 404);
        }

        $this->adelantoService->delete($adelanto);

        return response()->json([
            'success' => true,
            'message' => 'Adelanto eliminado correctamente',
        ]);
    }

    public function getDisponible(Request $request)
    {
        $empleadoId = $request->get('empleado_id');
        $disponible = $this->empleadoService->calcularDisponible((int) $empleadoId);

        return response()->json(['disponible' => $disponible]);
    }

    protected function validateData(Request $request, $id = null)
    {
        $uniqueRule = $id
            ? "unique:planilla_adelantos,numero_interno,{$id},id"
            : 'unique:planilla_adelantos,numero_interno';

        $validator = Validator::make($request->all(), [
            'empleado_id' => 'required|exists:empleados,id',
            'numero_interno' => "required|integer|{$uniqueRule}",
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string',
            'principal' => 'nullable|numeric|min:0',
            'deposito' => 'nullable|numeric|min:0',
            'consorcio' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::warning('Validacion fallo en PlanillaAdelanto', [
                'id' => $id,
                'errors' => $validator->errors()->toArray(),
                'data' => $request->only([
                    'empleado_id', 'numero_interno', 'monto', 'fecha',
                    'principal', 'deposito', 'consorcio',
                ]),
            ]);
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function printTicket($id)
    {
        $adelanto = PlanillaAdelanto::with('empleado')->findOrFail($id);

        $empresa = (object) [
            'razon_social' => 'CONSORCIOS VILLEGAS E.I.R.L.',
            'direccion' => 'Carretera Pomalca KM 3. Atras de Ferreteria Herrera',
            'ruc' => '20538937321',
            'celular' => '967984895 - 978431737 - 915177079',
        ];

        $pdf = Pdf::loadView('planilla.adelantos.ticket', compact('adelanto', 'empresa'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream("adelanto_{$adelanto->numero_interno}.pdf");
    }
}
