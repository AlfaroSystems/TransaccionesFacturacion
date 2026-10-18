<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes de cotización en borrador y con productos propios:
 *
 * - purchase_quotation_requests.status: draft mientras se arman sus productos; sent al
 *   registrar la primera oferta. Las que ya tienen ofertas quedan enviadas.
 * - purchase_quotation_requests.id_branch / id_warehouse: destino de los productos que el
 *   departamento de compras cotiza sin solicitud de compra.
 * - Cada línea guarda su producto y unidad; la solicitud de compra pasa a ser opcional.
 * - Permisos para editar los productos y generar el PDF, para los roles que ya crean
 *   solicitudes de cotización.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        [
            'id_permission' => 'purchase_quotation_requests.editar',
            'name' => 'Editar Solicitudes de Cotización',
            'description' => 'Permite agregar, quitar y cambiar la cantidad de los productos de una solicitud en borrador.',
            'action' => 'edit',
        ],
        [
            'id_permission' => 'purchase_quotation_requests.pdf',
            'name' => 'Generar PDF de Solicitudes de Cotización',
            'description' => 'Permite generar el PDF que se envía a los proveedores para que coticen.',
            'action' => 'pdf',
        ],
    ];

    public function up(): void
    {
        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            $table->string('status', 20)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('id_branch')->nullable()->constrained('branches', 'id_branch')->restrictOnDelete();
            $table->foreignId('id_warehouse')->nullable()->constrained('warehouses', 'id_warehouse')->restrictOnDelete();
        });

        DB::table('purchase_quotation_requests')
            ->whereIn('id_purchase_quotation_request', DB::table('purchase_quotations')->select('id_purchase_quotation_request'))
            ->update(['status' => 'sent', 'sent_at' => DB::raw('created_at')]);

        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->foreignId('id_product')->nullable()->constrained('products', 'id_product')->restrictOnDelete();
            $table->foreignId('id_unit')->nullable()->constrained('units', 'id_unit')->restrictOnDelete();
        });

        DB::statement('
            UPDATE purchase_quotation_request_details d
               SET id_product = prd.id_product, id_unit = prd.id_unit
              FROM purchase_request_details prd
             WHERE prd.id_purchase_request_detail = d.id_purchase_request_detail
        ');

        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id_product')->nullable(false)->change();
            $table->unsignedBigInteger('id_unit')->nullable(false)->change();
            $table->unsignedBigInteger('id_purchase_request_detail')->nullable()->change();
        });

        $roles = DB::table('roles_permissions')->where('id_permission', 'purchase_quotation_requests.crear')->pluck('id_role');

        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->insertOrIgnore($permission + ['created_at' => now(), 'updated_at' => now()]);

            foreach ($roles as $role) {
                DB::table('roles_permissions')->insertOrIgnore(['id_role' => $role, 'id_permission' => $permission['id_permission']]);
            }
        }
    }

    /**
     * Las líneas sin solicitud de compra no tienen lugar en el esquema anterior y se eliminan.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('id_permission', array_column(self::PERMISSIONS, 'id_permission'))->delete();

        DB::table('purchase_quotation_request_details')->whereNull('id_purchase_request_detail')->delete();

        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id_purchase_request_detail')->nullable(false)->change();
            $table->dropForeign(['id_product']);
            $table->dropForeign(['id_unit']);
            $table->dropColumn(['id_product', 'id_unit']);
        });

        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            $table->dropForeign(['id_branch']);
            $table->dropForeign(['id_warehouse']);
            $table->dropColumn(['status', 'sent_at', 'id_branch', 'id_warehouse']);
        });
    }
};
