<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos del flujo de traslados (ver RoleAndPermissionSeeder). Los tienen los roles que ya
 * aprueban órdenes de compra.
 */
return new class extends Migration
{
    private const PERMISOS = [
        'inventory.autorizar_traslados' => ['Autorizar Traslados', 'Permite autorizar o rechazar los traslados enviados a autorización.', 'authorize', 'purchase_orders.aprobar'],
        'inventory.regularizar_traslados' => ['Regularizar Discrepancias de Traslados', 'Permite regularizar los faltantes, sobrantes y daños de un traslado y cerrarlo.', 'regularize', 'purchase_orders.aprobar'],
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
