<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El departamento de compras aprueba la solicitud enviada (estado approved) antes de
 * generar la solicitud de cotización, en lugar de cotizarla directamente.
 *
 * Permiso purchase_requests.aprobar, para los roles que ya pueden devolver o rechazar.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'id_permission' => 'purchase_requests.aprobar',
            'name' => 'Aprobar Solicitudes de Compra',
            'description' => 'Permite aprobar solicitudes enviadas para generar su solicitud de cotización.',
            'action' => 'approve',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = DB::table('roles_permissions')->where('id_permission', 'purchase_requests.devolver')->pluck('id_role');

        foreach ($roles as $role) {
            DB::table('roles_permissions')->insertOrIgnore(['id_role' => $role, 'id_permission' => 'purchase_requests.aprobar']);
        }
    }

    public function down(): void
    {
        // Las aprobadas sin cotizar vuelven a quedar enviadas
        DB::table('purchase_requests')->where('status', 'approved')->update(['status' => 'sent']);
        DB::table('permissions')->where('id_permission', 'purchase_requests.aprobar')->delete();
    }
};
