<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes de inventario por conteo físico: lo contado de un lote en una ubicación de bodega. La
 * diferencia con lo que dice el sistema entra o sale al costo del lote (movimiento del kardex) y
 * mueve el costo promedio de la empresa. Los aplican quienes aprueban órdenes de compra.
 */
return new class extends Migration
{
    private const PERMISO = 'inventory.ajustar';

    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id('id_inventory_adjustment');
            $table->string('adjustment_code', 30)->unique();
            $table->foreignId('id_inventory_lot')->constrained('inventory_lots', 'id_inventory_lot')->restrictOnDelete();
            $table->foreignId('id_product')->constrained('products', 'id_product')->restrictOnDelete();
            $table->foreignId('id_warehouse')->constrained('warehouses', 'id_warehouse')->restrictOnDelete();
            $table->foreignId('id_inventory_location')->constrained('inventory_locations', 'id_inventory_location')->restrictOnDelete();
            $table->decimal('quantity_before', 12, 4);
            $table->decimal('quantity_counted', 12, 4);
            $table->decimal('difference', 12, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->string('reason', 20);
            $table->text('notes')->nullable();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_inventory_lot', 'id_inventory_location']);
        });

        DB::statement("ALTER TABLE inventory_adjustments ADD CONSTRAINT inventory_adjustments_reason_check CHECK (reason IN ('count', 'damage', 'expired', 'loss', 'other'))");
        DB::statement('ALTER TABLE inventory_adjustments ADD CONSTRAINT inventory_adjustments_quantities_check CHECK (quantity_before >= 0 AND quantity_counted >= 0 AND difference <> 0)');

        DB::table('permissions')->insertOrIgnore([
            'id_permission' => self::PERMISO,
            'name' => 'Ajustar Inventario',
            'description' => 'Permite registrar ajustes por conteo físico: lo contado de un lote en una ubicación; la diferencia entra o sale al costo del lote.',
            'action' => 'adjust',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('roles_permissions')->where('id_permission', 'purchase_orders.aprobar')->pluck('id_role') as $role) {
            DB::table('roles_permissions')->insertOrIgnore(['id_role' => $role, 'id_permission' => self::PERMISO]);
        }
    }

    public function down(): void
    {
        DB::table('roles_permissions')->where('id_permission', self::PERMISO)->delete();
        DB::table('permissions')->where('id_permission', self::PERMISO)->delete();
        Schema::dropIfExists('inventory_adjustments');
    }
};
