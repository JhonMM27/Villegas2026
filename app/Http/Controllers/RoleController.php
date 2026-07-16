<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:roles_list')->only(['index']);
        $this->middleware('can:roles_create')->only(['store']);
        $this->middleware('can:roles_edit')->only(['show', 'update']);
        $this->middleware('can:roles_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $roles = Role::with('permissions')->select('id', 'name');

            return DataTables::of($roles)
                ->addColumn('permissions', function ($role) {
                    return $role->permissions->map(function ($perm) {
                        return '<span class="badge bg-info me-1">'.$perm->name.'</span>';
                    })->implode(' ');
                })
                ->addColumn('action', function ($role) {
                    $editButton = '';
                    if (auth()->user()->can('roles_edit')) {
                        $editButton = view('components.button-edit', ['id' => $role->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('roles_delete')) {
                        // Use $role (closure param) instead of undefined $row
                        $deleteButton = view('components.button-delete', ['id' => $role->id, 'texto' => $role->name])->render();
                    }

                    return '<div class="btn-group">'.$editButton.$deleteButton.'</div>';
                })
                ->rawColumns(['permissions', 'action'])
                ->make(true);
        }

        return view('roles.index');
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
        $data = $this->validateData($request);
        $role = Role::create(['name' => $data['name']]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registro creado satisfactoriamente',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $registro = Role::with('permissions')->findOrFail($id);

            return response()->json($registro);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $this->validateData($request, $id);

        $role = Role::findOrFail($id);
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

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
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('roles', 'name')->ignore($id),
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
    }

    public function roles()
    {
        $roles = Role::select('id', 'name')->get();

        return response()->json($roles);
    }

    public function permisos()
    {
        $query = Permission::select('id', 'name')->orderBy('name');

        // Si el usuario NO tiene super_admin, no se lo mostramos
        if (! auth()->user()->can('super_admin')) {
            $query->where('name', '!=', 'super_admin');
        }

        $permissions = $query->get();

        return response()->json($permissions);
    }
}
