<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\CuadreStockService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CuadreStockController extends Controller
{
    public function __construct(
        protected CuadreStockService $cuadreService
    ) {
        $this->middleware('can:cuadre_stock_list')->only(['index']);
        $this->middleware('can:cuadre_stock_create')->only(['store', 'preview']);
        $this->middleware('can:cuadre_stock_view')->only(['show']);
        $this->middleware('can:cuadre_stock_delete')->only(['destroy']);
        $this->middleware('can:cuadre_stock_edit')->only(['rectificar']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->cuadreService->getAll();

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = '';

                    $buttons .= '<button class="btn btn-sm btn-secondary me-1" onclick="cuadreStockManager.verDetalle('.$row->id.')">
                        <i class="bi bi-eye"></i>
                    </button>';

                    if ($row->estado === 'anulado' && $row->rectificacion_count < 3 && auth()->user()->can('cuadre_stock_edit')) {
                        $buttons .= '<button class="btn btn-sm btn-warning me-1" onclick="cuadreStockManager.showRectifyModal('.$row->id.')">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>';
                    }

                    if ($row->estado === 'completado') {
                        $buttons .= '<button class="btn btn-sm btn-danger me-1" onclick="cuadreStockManager.confirmarAnular('.$row->id.')">
                            <i class="bi bi-x-circle"></i>
                        </button>';
                    }

                    return '<div class="btn-group">'.$buttons.'</div>';
                })
                ->editColumn('fecha', fn ($row) => \Carbon\Carbon::parse($row->fecha)->format('d/m/Y'))
                ->editColumn('estado', fn ($row) => $row->estado === 'completado'
                    ? '<span class="badge bg-success">Completado</span>'
                    : '<span class="badge bg-secondary">Anulado</span>')
                ->editColumn('user_id', fn ($row) => $row->user->name ?? 'N/A')
                ->editColumn('detalles_count', fn ($row) => $row->detalles->count())
                ->rawColumns(['action', 'estado'])
                ->make(true);
        }

        $productos = Producto::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo', 'stock_almacen', 'costo_unitario', 'empaque', 'unidad_codigo']);

        return view('cuadre_stock.index', compact('productos'));
    }

    public function preview(Request $request)
    {
        try {
            $detalles = $request->input('detalles', []);

            $preview = $this->cuadreService->generarPreview($detalles);

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'fecha' => 'required|date',
                'notas' => 'nullable|string',
                'detalles' => 'required|array|min:1',
                'detalles.*.producto_id' => 'required|exists:productos,id',
                'detalles.*.stock_fisico' => 'required|numeric|min:0',
            ]);

            $cuadre = $this->cuadreService->crearCuadreStock($data);

            return response()->json([
                'success' => true,
                'message' => 'Cuadre de stock registrado correctamente',
                'cuadre_id' => $cuadre->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show($id)
    {
        $cuadre = $this->cuadreService->findById((int) $id);

        if (! $cuadre) {
            return response()->json(['success' => false, 'message' => 'Cuadre no encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'cuadre' => $cuadre,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $data = $request->validate(['motivo' => 'nullable|string|max:500']);

        try {
            $cuadre = $this->cuadreService->anularCuadreStock((int) $id, $data['motivo'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Cuadre anulado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function rectificar(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'detalles' => 'required|array|min:1',
                'detalles.*.producto_id' => 'required|exists:productos,id',
                'detalles.*.stock_fisico' => 'required|numeric|min:0',
                'rectificacion_motivo' => 'nullable|string|max:500',
            ]);

            $cuadre = $this->cuadreService->rectificarCuadreStock((int) $id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Cuadre rectificado correctamente',
                'cuadre_id' => $cuadre->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
