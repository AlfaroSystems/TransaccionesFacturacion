<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos de empleados y vehículos (ver RoleAndPermissionSeeder). Se asignan a los roles que
 * ya hacen esa parte del trabajo con las bodegas: ver, crear, editar o eliminar bodegas.
 */
return new class extends Migration
{
    private const PERMISOS = [
        'employees.ver' => ['Ver Empleados', 'Permite ver el listado de empleados de las sucursales.', 'index', 'warehouses.ver'],
        'employees.crear' => ['Crear Empleados', 'Permite registrar empleados (motoristas, responsables de vehículos).', 'create', 'warehouses.crear'],
        'employees.editar' => ['Editar Empleados', 'Permite modificar y desactivar empleados.', 'edit', 'warehouses.editar'],
        'employees.eliminar' => ['Eliminar Empleados', 'Permite eliminar empleados sin vehículos ni traslados.', 'destroy', 'warehouses.eliminar'],
        'vehicles.ver' => ['Ver Vehículos', 'Permite ver el listado de vehículos y sus espacios de carga.', 'index', 'warehouses.ver'],
        'vehicles.crear' => ['Crear Vehículos', 'Permite registrar vehículos con sus espacios de carga.', 'create', 'warehouses.crear'],
        'vehicles.editar' => ['Editar Vehículos', 'Permite modificar y desactivar vehículos y sus espacios de carga.', 'edit', 'warehouses.editar'],
        'vehicles.eliminar' => ['Eliminar Vehículos', 'Permite eliminar vehículos sin traslados ni existencias.', 'destroy', 'warehouses.eliminar'],
    ];

    public function up(): void
    {
        foreach (self::PERMISOS as $id => [$name, $description, $action, $segun]) {
            DB::table('permissions')->insertOrIgnore([
                'id_permission' => $id,
                'name' => $name,
                'description' => $description,
                'action' => $action,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (DB::table('roles_permissions')->where('id_permission', $segun)->pluck('id_role') as $role) {
                DB::table('roles_permissions')->insertOrIgnore(['id_role' => $role, 'id_permission' => $id]);
            }
        }
    }

    public function down(): void
    {
        DB::table('roles_permissions')->whereIn('id_permission', array_keys(self::PERMISOS))->delete();
        DB::table('permissions')->whereIn('id_permission', array_keys(self::PERMISOS))->delete();
    }
};
