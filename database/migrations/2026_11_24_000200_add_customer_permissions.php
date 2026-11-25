<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos del módulo de Clientes. Se otorgan a los roles que ya gestionan proveedores
 * (roles comerciales) y, por el sync del seeder, al administrador.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        ['id_permission' => 'customers.ver', 'name' => 'Ver Clientes', 'description' => 'Permite ver el listado y detalle de los clientes.', 'action' => 'index'],
        ['id_permission' => 'customers.crear', 'name' => 'Crear Clientes', 'description' => 'Permite registrar nuevos clientes.', 'action' => 'create'],
        ['id_permission' => 'customers.editar', 'name' => 'Editar Clientes', 'description' => 'Permite modificar la información de los clientes.', 'action' => 'edit'],
        ['id_permission' => 'customers.eliminar', 'name' => 'Activar/Desactivar Clientes', 'description' => 'Permite activar o desactivar clientes.', 'action' => 'destroy'],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->insertOrIgnore($permission + ['created_at' => now(), 'updated_at' => now()]);
        }

        foreach (DB::table('roles_permissions')->where('id_permission', 'suppliers.ver')->pluck('id_role') as $role) {
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
