<?php

namespace App\Http\Controllers;

use App\Models\SunatCertificado;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SunatCertificadoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:sunat_list')->only(['index']);
        $this->middleware('can:sunat_create')->only(['store']);
        $this->middleware('can:sunat_edit')->only(['show', 'update']);
        $this->middleware('can:sunat_delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = SunatCertificado::select(['id', 'nombre', 'certificado_path', 'activo', 'expires_at']);

            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $editButton = '';
                    if (auth()->user()->can('sunat_edit')) {
                        $editButton = view('components.button-edit', ['id' => $row->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('sunat_delete')) {
                        $deleteButton = view('components.button-delete', ['id' => $row->id, 'texto' => $row->nombre])->render();
                    }

                    return '<div class="btn-group">'.$editButton.$deleteButton.'</div>';
                })
                ->rawColumns(['action', 'activo'])
                ->editColumn('activo', function ($row) {
                    return $row->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->make(true);
        }

        return view('sunat-certificados.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        SunatCertificado::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro creado satisfactoriamente',
        ]);
    }

    public function show($id)
    {
        try {
            $registro = SunatCertificado::where('id', $id)->firstOrFail();

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateData($request, $id);
        $registro = SunatCertificado::where('id', $id)->firstOrFail();
        $registro->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente',
        ]);
    }

    public function destroy($id)
    {
        try {
            $registro = SunatCertificado::findOrFail($id);
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

    protected function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'nombre' => 'required|string|max:255',
            'certificado_path' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'activo' => 'sometimes|boolean',
            'expires_at' => 'nullable|date',
        ]);
    }

    public function select(Request $request)
    {
        $items = SunatCertificado::select('id', 'nombre')->where('activo', true)->get();

        return response()->json($items);
    }
}
