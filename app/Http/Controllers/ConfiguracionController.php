<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:configuraciones_list')->only(['index', 'show']);
        $this->middleware('can:configuraciones_edit')->only(['update']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Configuracion::select(['id', 'clave', 'valor', 'descripcion', 'updated_at']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $buttons = [];
                    if (auth()->user()->can('configuraciones_edit')) {
                        $buttons[] = '<button class="btn btn-sm btn-info btn-edit" data-id="'.$row->id.'" title="Editar"><i class="bi bi-pencil"></i></button>';
                    }

                    return '<div class="btn-group">'.implode('', $buttons).'</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('configuraciones.index');
    }

    public function show(int $id): JsonResponse
    {
        $registro = Configuracion::findOrFail($id);

        return response()->json($registro);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'valor' => 'required|string|max:255',
        ]);

        $registro = Configuracion::findOrFail($id);
        $registro->update(['valor' => $request->valor]);

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada correctamente',
        ]);
    }

    public function json(): JsonResponse
    {
        $configs = Configuracion::allAsArray();

        return response()->json($configs);
    }
}
