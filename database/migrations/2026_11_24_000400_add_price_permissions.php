<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos de Asignación de Precios. Se otorgan a los roles que ya gestionan productos.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        ['id_permission' => 'prices.ver', 'name' => 'Ver Precios de Venta', 'description' => 'Permite consultar el precio de venta vigente de los productos.', 'action' => 'index'],
        ['id_permission' => 'prices.cambiar', 'name' => 'Asignar/Cambiar Precios', 'description' => 'Permite asignar y cambiar el precio de venta de un producto (cierra el vigente y abre uno nuevo).', 'action' => 'update'],
        ['id_permission' => 'prices.historial', 'name' => 'Ver Historial de Precios', 'description' => 'Permite consultar el historial de precios de un producto con sus fechas.', 'action' => 'history'],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->insertOrIgnore($permission + ['created_at' => now(), 'updated_at' => now()]);
        }

        foreach (DB::table('roles_permissions')->where('id_permission', 'products.ver')->pluck('id_role') as $role) {
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
