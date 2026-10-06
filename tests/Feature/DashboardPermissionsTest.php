<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

function usuarioConPermisosDeDashboard(array $permisos): User
{
    $role = Role::create(['name' => 'dashboard-'.Str::lower(Str::random(8))]);

    foreach ($permisos as $permiso) {
        Permission::firstOrCreate(['id_permission' => $permiso], ['name' => $permiso]);

        if (! Gate::has($permiso)) {
            Gate::define($permiso, fn (User $user) => $user->hasPermission($permiso));
        }
    }

    $role->permissions()->sync($permisos);

    $company = Company::create(['name' => 'Empresa '.Str::random(6)]);
    $branch = Branch::create(['id_company' => $company->id_company, 'name' => 'Sucursal '.Str::random(6)]);

    $user = User::factory()->create(['id_branch' => $branch->id_branch]);
    $user->roles()->attach($role->id_role, ['assigned_at' => now()]);

    return $user->load('roles.permissions');
}

test('el dashboard solo muestra tarjetas, gráfico y accesos rápidos de los módulos permitidos', function () {
    $user = usuarioConPermisosDeDashboard(['products.ver', 'categories.ver']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    // Los datos de órdenes del gráfico no se calculan ni se envían en el código de la página
    expect($response->viewData('chartData'))->toBe([]);

    $response
        ->assertOk()
        ->assertDontSee('new Chart(', false)
        ->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false)
        ->assertSee('Productos Registrados')
        ->assertSee('Categorías')
        ->assertDontSee('Usuarios en Sistema')
        ->assertDontSee('Roles Definidos')
        ->assertDontSee('Rendimiento Mensual')
        ->assertDontSee('performanceChart')
        ->assertDontSee('Gestionar Compras')
        ->assertDontSee('Seguridad y Permisos');
});

test('sin permisos sobre ningún módulo el dashboard no muestra tarjetas ni accesos rápidos', function () {
    $user = usuarioConPermisosDeDashboard([]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Productos Registrados')
        ->assertDontSee('Usuarios en Sistema')
        ->assertDontSee('Roles Definidos')
        ->assertDontSee('Rendimiento Mensual')
        ->assertDontSee('Accesos Rápidos');
});

test('cada grupo del menú lleva al primer módulo que el usuario puede ver', function () {
    // Sin permiso de bodegas ni de usuarios: Inventario y Administración llevan a lo permitido
    $user = usuarioConPermisosDeDashboard(['locations.ver', 'bitacora.ver']);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('locations.index').'"', false)
        ->assertSee('href="'.route('audit-logs.index').'"', false)
        ->assertDontSee('href="'.route('warehouses.index').'"', false)
        ->assertDontSee('href="'.route('users.index').'"', false)
        ->assertDontSee('href="'.route('purchase-requests.index').'"', false);

    $this->get(route('locations.index'))->assertOk();
    $this->get(route('audit-logs.index'))->assertOk();
});

test('el administrador ve todo el dashboard', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Usuarios en Sistema')
        ->assertSee('Productos Registrados')
        ->assertSee('Roles Definidos')
        ->assertSee('Rendimiento Mensual')
        ->assertSee('new Chart(', false)
        ->assertSee('Gestionar Compras')
        ->assertSee('Seguridad y Permisos')
        ->assertSee('Categorías');
});
