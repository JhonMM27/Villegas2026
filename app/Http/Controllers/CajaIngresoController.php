<?php

namespace App\Http\Controllers;

use App\Models\CajaIngreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class CajaIngresoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:caja_ingresos_list')->only(['index', 'datatable']);
        $this->middleware('can:caja_ingresos_create')->only(['store']);
        $this->middleware('can:caja_ingresos_edit')->only(['update']);
        $this->middleware('can:caja_ingresos_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = CajaIngreso::query()
                ->select([
                    'id',
                    'fecha',
                    'caja_destino',
                    'monto',
                    'user_id',
                    'user_nombre',
                    'comentario',
                    'created_at',
                    'updated_at',
                ]);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $edit = '';
                    $delete = '';

                    if (auth()->user()->can('caja_ingresos_edit')) {
                        $edit = '<button class="btn btn-sm btn-warning me-1" onclick="window.cajaIngresoManager.showEditModal('.$row->id.')">
                            <i class="bi bi-pencil"></i>
                        </button>';
                    }

                    if (auth()->user()->can('caja_ingresos_delete')) {
                        $delete = '<button class="btn btn-sm btn-danger" onclick="window.cajaIngresoManager.confirmDelete('.$row->id.')">
                            <i class="bi bi-trash"></i>
                        </button>';
                    }

                    return '<div class="btn-group">'.$edit.$delete.'</div>';
                })
                ->editColumn('fecha', fn ($row) => $row->fecha ? \Carbon\Carbon::parse($row->fecha)->format('d/m/Y') : '—')
                ->editColumn('caja_destino', function ($row) {
                    $opts = CajaIngreso::cajaDestinoOptions();
                    $texto = $opts[$row->caja_destino] ?? $row->caja_destino;
                    $color = match ($row->caja_destino) {
                        'P' => 'primary',
                        'D' => 'info',
                        'C' => 'warning',
                        default => 'secondary',
                    };

                    return '<span class="badge bg-'.$color.'">'.$row->caja_destino.' · '.$texto.'</span>';
                })
                ->editColumn('monto', fn ($row) => '<span class="text-end d-block fw-bold">S/ '.number_format((float) $row->monto, 2).'</span>')
                ->editColumn('user_nombre', fn ($row) => $row->user_nombre ?? '—')
                ->editColumn('comentario', fn ($row) => $row->comentario ? '<span title="'.e($row->comentario).'">'.(strlen($row->comentario) > 60 ? substr($row->comentario, 0, 60).'…' : $row->comentario).'</span>' : '<span class="text-muted">—</span>')
                ->rawColumns(['action', 'caja_destino', 'monto', 'comentario'])
                ->make(true);
        }

        return view('caja-ingresos.index');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->validateData($request);
            $data['user_id'] = auth()->id();
            $data['user_nombre'] = auth()->user()->name;

            $ingreso = CajaIngreso::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Ingreso a caja registrado correctamente',
                'ingreso' => $ingreso,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creando ingreso caja', ['msg' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el ingreso: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): JsonResponse
    {
        try {
            $ingreso = CajaIngreso::findOrFail($id);

            return response()->json([
                'success' => true,
                'ingreso' => $ingreso,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado: '.$e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $ingreso = CajaIngreso::findOrFail($id);

        try {
            $data = $this->validateData($request, $id);
            $ingreso->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Ingreso a caja actualizado correctamente',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error actualizando ingreso caja', ['id' => $id, 'msg' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el ingreso: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $ingreso = CajaIngreso::findOrFail($id);

        try {
            $ingreso->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ingreso a caja eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando ingreso caja', ['id' => $id, 'msg' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el ingreso: '.$e->getMessage(),
            ], 500);
        }
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $validator = Validator::make($request->all(), [
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0.01',
            'caja_destino' => 'required|in:P,D,C',
            'comentario' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            Log::warning('Validacion fallo en CajaIngreso', [
                'id' => $id,
                'errors' => $validator->errors()->toArray(),
            ]);
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
