<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('usuarios.ver');

        $query = User::with(['roles', 'branch']);

        // Búsqueda por nombre o email
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->input('role'));
            });
        }

        // Filtro por sucursal
        if ($request->filled('id_branch')) {
            $query->where('id_branch', $request->input('id_branch'));
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Paginación y mantenimiento de los parámetros de búsqueda/filtro
        $users = $query->latest()->paginate(10)->withQueryString();
        $roles = Role::all();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('usuarios.crear');

        $roles = Role::all();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.create', compact('roles', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('usuarios.crear');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'id_branch' => ['nullable', 'exists:branches,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'id_branch' => $validated['id_branch'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        $user->roles()->sync($validated['roles']);

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        Gate::authorize('usuarios.editar');

        $roles = Role::all();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('users.edit', compact('user', 'roles', 'branches', 'userRoles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('usuarios.editar');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'id_branch' => ['nullable', 'exists:branches,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'id_branch' => $validated['id_branch'] ?? null,
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);
        $user->roles()->sync($validated['roles']);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('usuarios.eliminar');

        // Evitar que el usuario se elimine a sí mismo si está autenticado
        if (auth()->check() && auth()->id() === $user->id) {
            return redirect()->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}