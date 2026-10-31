<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos de traslados y etiquetas (ver RoleAndPermissionSeeder). Se asignan a los roles que
 * ya hacen esa parte del trabajo:
 * - Crear y despachar traslados, e imprimir etiquetas: los que registran compras (Casa Matriz).
 * - Recibir traslados: los que ven el inventario (las sucursales).
 */
return new class extends Migration
{
    private const PERMISOS = [
        'inventory.trasladar' => ['Crear y Despachar Traslados', 'Permite preparar traslados de lotes entre bodegas, despacharlos y cancelar o eliminar los borradores.', 'create', 'purchases.crear'],
        'inventory.recibir_traslados' => ['Recibir Traslados', 'Permite confirmar lo que llegó de un traslado despachado a una bodega de la sucursal.', 'update', 'inventory.ver'],
        'inventory.etiquetas' => ['Imprimir Etiquetas de Lote', 'Permite imprimir y reimprimir (con motivo) las etiquetas de los lotes.', 'print', 'purchases.crear'],
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
