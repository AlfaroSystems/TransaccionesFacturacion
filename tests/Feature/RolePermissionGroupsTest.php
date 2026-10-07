<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionGroups;
use Database\Seeders\RoleAndPermissionSeeder;

test('todos los permisos del seeder aparecen en algún grupo de la pantalla de roles', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $sinGrupo = PermissionGroups::ungrouped(Permission::all())->pluck('id_permission');

    expect($sinGrupo->all())->toBe([]);
});

test('la pantalla de roles permite asignar los permisos del módulo de compras', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    $response = $this->actingAs($admin)->get(route('roles.index'));

    $response->assertOk()
        ->assertSee('Compras: Solicitudes de Compra')
        ->assertSee('Compras: Retaceos');

    // Cada permiso de compras tiene su casilla en los modales de crear y editar
    $compras = Permission::where('id_permission', '~', '^(purchase|retaceos|expense_types)')->pluck('id_permission');
    expect($compras)->not->toBeEmpty();

    foreach ($compras as $permiso) {
        $id = str_replace('.', '-', $permiso);
        $response->assertSee('id="create-permission-'.$id.'"', false)
            ->assertSee('id="edit-permission-'.$id.'"', false);
    }
});
