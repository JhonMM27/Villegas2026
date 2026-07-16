<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:users_list')->only(['index']);
        $this->middleware('can:users_create')->only(['store']);
        $this->middleware('can:users_edit')->only(['show', 'update']);
        $this->middleware('can:users_delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::with('roles')->select('id', 'name', 'email', 'activo');

            return DataTables::of($data)
                ->addColumn('roles', function ($user) {
                    return $user->roles->pluck('name')->map(function ($role) {
                        return '<span class="badge bg-primary">'.$role.'</span>';
                    })->implode(' ');
                })
                ->addColumn('action', function ($user) {
                    $editButton = '';
                    if (auth()->user()->can('users_edit')) {
                        $editButton = view('components.button-edit', ['id' => $user->id])->render();
                    }
                    $deleteButton = '';
                    if (auth()->user()->can('users_delete')) {
                        // Use $user (closure param) instead of undefined $row
                        $deleteButton = view('components.button-delete', ['id' => $user->id, 'texto' => $user->name])->render();
                    }

                    return '<div class="btn-group">'.$editButton.$deleteButton.'</div>';
                })
                ->rawColumns(['roles', 'action', 'activo'])
                ->editColumn('activo', function ($row) {
                    return $row->activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>';
                })
                ->make(true);
        }

        return view('users.index');
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

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->input('roles'));
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
            $registro = User::with('roles')->findOrFail($id);

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
        $user = User::findOrFail($id);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->input('roles'));
        }

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
            $user = User::findOrFail($id);
            $user->delete();

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
            'name' => 'required|string|max:10',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')->ignore($id),
            ],
            'password' => $id ? 'nullable|min:6' : 'required|min:6',
            'activo' => 'required|boolean',
        ]);
    }
}
