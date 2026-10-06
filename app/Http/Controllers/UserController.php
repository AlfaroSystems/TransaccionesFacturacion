<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Rules\Accessible;
use App\Support\BranchAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('usuarios.ver');

        $query = User::accessible()->with(['roles', 'branch']);

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
        $assignableRoles = $this->assignableRoles();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'assignableRoles', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('usuarios.crear');

        $roles = $this->assignableRoles();
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
            'id_branch' => $this->branchRules(),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $this->ensureCanAssignRoles($validated['roles']);

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
        $this->ensureAccessible($user);

        if ($denied = $this->denyIfProtected($user)) {
            return $denied;
        }

        $roles = $this->assignableRoles();
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
        $this->ensureAccessible($user);

        if ($denied = $this->denyIfProtected($user)) {
            return $denied;
        }

        // Al editar su propia cuenta, el formulario no envía roles ni estado
        $isSelf = $request->user()->is($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'id_branch' => $this->branchRules(),
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => [$isSelf ? 'sometimes' : 'required', 'string', Rule::in(['active', 'inactive'])],
            'roles' => [$isSelf ? 'sometimes' : 'required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        if ($isSelf) {
            $this->ensureSelfAccessUnchanged($user, $validated);
        } else {
            $this->ensureCanAssignRoles($validated['roles']);
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'id_branch' => $validated['id_branch'] ?? null,
        ];

        if (! $isSelf) {
            $data['status'] = $validated['status'];
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        if (! $isSelf) {
            $user->roles()->sync($validated['roles']);
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Activa o desactiva el usuario. No se elimina físicamente para conservar la
     * bitácora de auditoría y los documentos que registró.
     */
    public function destroy(Request $request, User $user)
    {
        Gate::authorize('usuarios.eliminar');
        $this->ensureAccessible($user);

        if ($request->user()->is($user)) {
            return redirect()->route('users.index')
                ->with('error', 'No puedes desactivar tu propio usuario.');
        }

        if ($denied = $this->denyIfProtected($user)) {
            return $denied;
        }

        $newStatus = $user->isActive() ? 'inactive' : 'active';

        $user->update(['status' => $newStatus]);

        $message = $newStatus === 'active'
            ? 'Usuario reactivado exitosamente.'
            : 'Usuario desactivado exitosamente.';

        return redirect()->route('users.index')
            ->with('success', $message);
    }

    /**
     * Un usuario fuera de la sucursal del usuario actual se trata como inexistente.
     */
    private function ensureAccessible(User $user): void
    {
        abort_unless(User::accessible()->whereKey($user->getKey())->exists(), 404);
    }

    /**
     * La sucursal asignada debe ser visible para el usuario actual. Quien no es
     * administrador solo puede asignar su propia sucursal y no puede dejarla vacía.
     */
    private function branchRules(): array
    {
        return [
            BranchAccess::isUnrestricted() ? 'nullable' : 'required',
            new Accessible(Branch::class),
        ];
    }

    /**
     * Roles que el usuario autenticado puede asignar: solo un administrador
     * puede asignar el rol "admin".
     */
    private function assignableRoles(): Collection
    {
        return Role::query()
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->where('name', '!=', 'admin'))
            ->get();
    }

    /**
     * Impide que un usuario que no es administrador asigne el rol "admin".
     *
     * @throws ValidationException
     */
    private function ensureCanAssignRoles(array $roleIds): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $assignsAdmin = Role::whereIn('id', $roleIds)->where('name', 'admin')->exists();

        if ($assignsAdmin) {
            throw ValidationException::withMessages([
                'roles' => 'Solo un administrador puede asignar el rol Administrador.',
            ]);
        }
    }

    /**
     * Impide que un usuario cambie sus propios roles o desactive su propia cuenta.
     *
     * @throws ValidationException
     */
    private function ensureSelfAccessUnchanged(User $user, array $validated): void
    {
        if (array_key_exists('roles', $validated)) {
            $submitted = collect($validated['roles'])->map(fn ($id) => (int) $id)->sort()->values();
            $current = $user->roles->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

            if ($submitted->all() !== $current->all()) {
                throw ValidationException::withMessages([
                    'roles' => 'No puedes modificar tus propios roles.',
                ]);
            }
        }

        if (($validated['status'] ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'status' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }
    }

    /**
     * Un usuario que no es administrador no puede modificar a un administrador
     * (por ejemplo, cambiarle la contraseña para tomar su cuenta).
     */
    private function denyIfProtected(User $user): ?RedirectResponse
    {
        if ($user->isAdmin() && ! auth()->user()->isAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'Solo un administrador puede modificar a otro administrador.');
        }

        return null;
    }
}
