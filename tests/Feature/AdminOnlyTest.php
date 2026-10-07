<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

// =============================================================================
// Crear, editar y desactivar empresas y administrar roles: solo el admin. Ni
// siquiera un rol con los antiguos permisos asignables lo consigue.
// =============================================================================

function usuarioNoAdminDeEmpresa(Company $company, array $permisos): User
{
    $role = Role::create(['name' => 'no-admin-'.Str::lower(Str::random(8))]);

    foreach ($permisos as $permiso) {
        Permission::firstOrCreate(['id_permission' => $permiso], ['name' => $permiso]);

        if (! Gate::has($permiso)) {
            Gate::define($permiso, fn (User $user) => $user->hasPermission($permiso));
        }
    }

    $role->permissions()->sync($permisos);

    $branch = Branch::create(['id_company' => $company->id_company, 'name' => 'Sucursal '.Str::random(6)]);
    $user = User::factory()->create(['id_branch' => $branch->id_branch]);
    $user->roles()->attach($role->id_role, ['assigned_at' => now()]);

    return $user;
}

/** Los campos que manda el formulario de empresa (vacíos se convierten en null) */
function datosEmpresa(string $name): array
{
    return array_merge(array_fill_keys(['commercial_name', 'nit', 'nrc', 'commercial_line_1', 'commercial_line_2', 'commercial_line_3', 'addres', 'id_department', 'id_municipality', 'id_district', 'phone', 'email', 'web_site'], ''), ['name' => $name]);
}

function usuarioAdmin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    return $admin;
}

test('solo el admin crea, edita y desactiva empresas', function () {
    $company = Company::create(['name' => 'Empresa '.Str::random(6)]);
    $user = usuarioNoAdminDeEmpresa($company, ['companies.ver', 'companies.crear', 'companies.editar', 'companies.eliminar']);

    $this->actingAs($user);
    $this->get(route('companies.index'))->assertOk();
    $this->post(route('companies.store'), datosEmpresa('Nueva'))->assertForbidden();
    $this->get(route('companies.edit', $company))->assertForbidden();
    $this->put(route('companies.update', $company), datosEmpresa('Cambiada'))->assertForbidden();
    $this->delete(route('companies.destroy', $company))->assertForbidden();
    expect($company->fresh()->name)->not->toBe('Cambiada')
        ->and(Company::where('name', 'Nueva')->exists())->toBeFalse();

    $this->actingAs(usuarioAdmin());
    $this->post(route('companies.store'), datosEmpresa('Nueva'))->assertRedirect(route('companies.index'))->assertSessionHasNoErrors();
    $this->put(route('companies.update', $company), datosEmpresa('Cambiada'))->assertRedirect(route('companies.index'))->assertSessionHasNoErrors();
    expect($company->fresh()->name)->toBe('Cambiada')
        ->and(Company::where('name', 'Nueva')->exists())->toBeTrue();
});

test('solo el admin administra roles', function () {
    $company = Company::create(['name' => 'Empresa '.Str::random(6)]);
    $user = usuarioNoAdminDeEmpresa($company, ['roles.administrar']);
    $role = Role::create(['name' => 'rol-'.Str::lower(Str::random(6))]);

    $this->actingAs($user);
    $this->get(route('roles.index'))->assertForbidden();
    $this->get(route('roles.create'))->assertForbidden();
    $this->post(route('roles.store'), ['name' => 'Nuevo rol'])->assertForbidden();
    $this->get(route('roles.edit', $role))->assertForbidden();
    $this->delete(route('roles.destroy', $role))->assertForbidden();
    expect(Role::find($role->id_role))->not->toBeNull();

    $this->actingAs(usuarioAdmin());
    $this->get(route('roles.index'))->assertOk();
    // La edición es un modal del listado: edit redirige a él
    $this->get(route('roles.edit', $role))->assertRedirect(route('roles.index'));
});
