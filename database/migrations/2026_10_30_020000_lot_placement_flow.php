<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario (fase 3): los lotes nacen del retaceo aplicado y se ubican antes de entrar.
 *
 * - inventory_lots.id_warehouse: bodega a la que llegó el lote. Un lote por ubicar todavía no
 *   tiene movimientos, así que su bodega no puede salir de ellos.
 * - inventory_lots.placed_at: cuándo se confirmó su colocación (y entró al inventario).
 * - inventory_lot_placements: reparto del lote en las ubicaciones de su bodega. Se edita
 *   mientras el lote está por ubicar; al confirmarlo, cada fila es una entrada al inventario.
 * - purchase_details.id_location se elimina: la ubicación ya no se elige al recibir la compra.
 * - Permiso inventory.ubicar, para los roles que registran compras (los que imprimen etiquetas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_lots', function (Blueprint $table) {
            $table->foreignId('id_warehouse')->nullable()->constrained('warehouses', 'id_warehouse')->restrictOnDelete();
            $table->timestamp('placed_at')->nullable();

            $table->index('status');
        });

        // Los lotes que ya entraron: su bodega y fecha son las de su entrada por compra
        DB::statement("
            UPDATE inventory_lots l
            SET id_warehouse = m.id_warehouse, placed_at = m.movement_date
            FROM (
                SELECT DISTINCT ON (id_inventory_lot) id_inventory_lot, id_warehouse, movement_date
                FROM inventory_movements
                WHERE movement_type = 'purchase_receipt'
                ORDER BY id_inventory_lot, id_inventory_movement
            ) m
            WHERE m.id_inventory_lot = l.id_inventory_lot
        ");

        DB::statement('ALTER TABLE inventory_lots ALTER COLUMN id_warehouse SET NOT NULL');

        Schema::create('inventory_lot_placements', function (Blueprint $table) {
            $table->id('id_inventory_lot_placement');
            $table->foreignId('id_inventory_lot')->constrained('inventory_lots', 'id_inventory_lot')->cascadeOnDelete();
            $table->foreignId('id_location')->constrained('locations', 'id_location')->restrictOnDelete();
            $table->decimal('quantity', 12, 4);

            $table->unique(['id_inventory_lot', 'id_location']);
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropForeign(['id_location']);
            $table->dropColumn('id_location');
        });

        DB::table('permissions')->insertOrIgnore([
            'id_permission' => 'inventory.ubicar',
            'name' => 'Ubicar Lotes',
            'description' => 'Permite repartir los lotes por ubicar en las ubicaciones de su bodega, confirmar su colocación y cambiar de ubicación lo que ya está en inventario.',
            'action' => 'update',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('roles_permissions')->where('id_permission', 'purchases.crear')->pluck('id_role') as $role) {
            DB::table('roles_permissions')->insertOrIgnore(['id_role' => $role, 'id_permission' => 'inventory.ubicar']);
        }
    }

    public function down(): void
    {
        DB::table('roles_permissions')->where('id_permission', 'inventory.ubicar')->delete();
        DB::table('permissions')->where('id_permission', 'inventory.ubicar')->delete();

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->foreignId('id_location')->nullable()->constrained('locations', 'id_location')->nullOnDelete();
        });

        Schema::dropIfExists('inventory_lot_placements');

        Schema::table('inventory_lots', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropForeign(['id_warehouse']);
            $table->dropColumn(['id_warehouse', 'placed_at']);
        });
    }
};
