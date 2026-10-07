<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flujo de solicitudes de compra con departamento de compras:
 *
 * - branches.is_purchasing_department: la sucursal del departamento de compras (Casa
 *   Matriz) ve las solicitudes que le envían las demás sucursales de su empresa.
 * - purchase_requests: estados draft (borrador), sent (enviada a compras), returned
 *   (devuelta a la sucursal), rejected (rechazada) y quoted (en cotización); el motivo
 *   de la devolución o el rechazo y la fecha de envío.
 * - Permisos: purchase_requests.aprobar se reemplaza por purchase_requests.enviar
 *   (sucursales) y purchase_requests.devolver (compras: devolver o rechazar).
 */
return new class extends Migration
{
    private array $newPermissions = [
        ['purchase_requests.enviar', 'Enviar Solicitudes de Compra', 'Permite enviar solicitudes de compra al departamento de compras.', 'send'],
        ['purchase_requests.devolver', 'Devolver/Rechazar Solicitudes de Compra', 'Permite devolver a la sucursal o rechazar solicitudes enviadas, indicando el motivo.', 'review'],
    ];

    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('is_purchasing_department')->default(false)->after('description');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('sent_at')->nullable()->after('status_reason');
        });

        // Estados anteriores: pending (en aprobación) y approved (lista para cotizar)
        // pasan a sent; las que ya tienen solicitud de cotización, a quoted.
        DB::table('purchase_requests')->where('status', 'approved')
            ->whereIn('id_purchase_request', DB::table('purchase_quotation_requests')->select('id_purchase_request'))
            ->update(['status' => 'quoted']);
        DB::table('purchase_requests')->whereIn('status', ['pending', 'approved'])
            ->update(['status' => 'sent', 'sent_at' => DB::raw('updated_at')]);

        foreach ($this->newPermissions as [$id, $name, $description, $action]) {
            DB::table('permissions')->insertOrIgnore([
                'id_permission' => $id, 'name' => $name, 'description' => $description,
                'action' => $action, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Quien podía crear solicitudes ahora puede enviarlas; quien las aprobaba, devolverlas
        $this->grantTo('purchase_requests.crear', 'purchase_requests.enviar');
        $this->grantTo('purchase_requests.aprobar', 'purchase_requests.devolver');
        DB::table('permissions')->where('id_permission', 'purchase_requests.aprobar')->delete();
    }

    public function down(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'id_permission' => 'purchase_requests.aprobar',
            'name' => 'Aprobar/Rechazar Solicitudes de Compra',
            'description' => 'Permite cambiar el estado de las solicitudes de compra.',
            'action' => 'updateStatus',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->grantTo('purchase_requests.devolver', 'purchase_requests.aprobar');
        DB::table('permissions')->whereIn('id_permission', array_column($this->newPermissions, 0))->delete();

        DB::table('purchase_requests')->where('status', 'sent')->update(['status' => 'pending']);
        DB::table('purchase_requests')->where('status', 'quoted')->update(['status' => 'approved']);
        DB::table('purchase_requests')->where('status', 'returned')->update(['status' => 'draft']);

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['status_reason', 'sent_at']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('is_purchasing_department');
        });
    }

    /**
     * Asigna $permission a los roles que tienen $existing.
     */
    private function grantTo(string $existing, string $permission): void
    {
        $roles = DB::table('roles_permissions')->where('id_permission', $existing)->pluck('id_role');

        foreach ($roles as $role) {
            DB::table('roles_permissions')->insertOrIgnore(['id_role' => $role, 'id_permission' => $permission]);
        }
    }
};
