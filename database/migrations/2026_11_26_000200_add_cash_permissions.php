<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos de Caja. Se otorgan a los roles que ya pueden registrar ventas.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        ['id_permission' => 'cash.ver', 'name' => 'Ver Caja', 'description' => 'Permite ver el turno de caja, su resumen y los egresos.', 'action' => 'index'],
        ['id_permission' => 'cash.abrir', 'name' => 'Abrir Caja', 'description' => 'Permite abrir un turno de caja con su monto de apertura.', 'action' => 'open'],
        ['id_permission' => 'cash.cerrar', 'name' => 'Cerrar Caja', 'description' => 'Permite cerrar el turno de caja y registrar el arqueo.', 'action' => 'close'],
        ['id_permission' => 'cash.egreso', 'name' => 'Registrar Egresos de Caja', 'description' => 'Permite registrar retiros o egresos de efectivo del turno.', 'action' => 'outflow'],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->insertOrIgnore($permission + ['created_at' => now(), 'updated_at' => now()]);
        }

        foreach (DB::table('roles_permissions')->where('id_permission', 'sales.crear')->pluck('id_role') as $role) {
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
