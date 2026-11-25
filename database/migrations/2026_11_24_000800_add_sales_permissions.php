<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos del módulo de Ventas. Se otorgan a los roles que ya gestionan clientes.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        ['id_permission' => 'sales.ver', 'name' => 'Ver Ventas', 'description' => 'Permite consultar el listado y detalle de ventas.', 'action' => 'index'],
        ['id_permission' => 'sales.crear', 'name' => 'Registrar Ventas', 'description' => 'Permite registrar ventas y entregarlas, descontando el inventario con PEPS.', 'action' => 'create'],
        ['id_permission' => 'sales.anular', 'name' => 'Anular Ventas', 'description' => 'Permite anular ventas.', 'action' => 'cancel'],
        ['id_permission' => 'sales.rentabilidad', 'name' => 'Ver Rentabilidad de Ventas', 'description' => 'Permite ver el costo de venta (PEPS) y el margen de las ventas.', 'action' => 'profit'],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->insertOrIgnore($permission + ['created_at' => now(), 'updated_at' => now()]);
        }

        foreach (DB::table('roles_permissions')->where('id_permission', 'customers.ver')->pluck('id_role') as $role) {
            foreach (self::PERMISSIONS as $permission) {
                DB::table('roles_permissions')->insertOrIgnore(['id_role' => $role, 'id_permission' => $permission['id_permission']]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('id_permission', array_column(self::PERMISSIONS, 'id_permission'))->delete();
    }
};
