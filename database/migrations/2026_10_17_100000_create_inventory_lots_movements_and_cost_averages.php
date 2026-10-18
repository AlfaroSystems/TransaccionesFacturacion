<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario (fase 1): lotes, movimientos y costo promedio.
 *
 * - inventory_lots: cada línea de compra recibida entra como un lote, con su cantidad y costo.
 * - inventory_movements: entradas y salidas de cada lote por bodega y ubicación; la existencia
 *   es la suma de sus movimientos. Los ajustes de costo son movimientos sin cantidad.
 * - inventory_cost_averages: existencia, valor y costo promedio ponderado de cada producto
 *   por empresa.
 * - purchase_details.id_location: ubicación de la bodega donde se guarda lo recibido.
 * - Permiso inventory.ver, para los roles que ya ven las bodegas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id('id_inventory_lot');
            $table->string('lot_code', 50)->unique();
            $table->foreignId('id_product')->constrained('products', 'id_product')->restrictOnDelete();
            $table->foreignId('id_purchase_detail')->nullable()->constrained('purchase_details', 'id_purchase_detail')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->decimal('quantity_received', 12, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 14, 4);
            $table->string('status', 20)->default('active');
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_product');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id('id_inventory_movement');
            $table->foreignId('id_product')->constrained('products', 'id_product')->restrictOnDelete();
            $table->foreignId('id_inventory_lot')->nullable()->constrained('inventory_lots', 'id_inventory_lot')->restrictOnDelete();
            $table->foreignId('id_warehouse')->constrained('warehouses', 'id_warehouse')->restrictOnDelete();
            $table->foreignId('id_location')->nullable()->constrained('locations', 'id_location')->restrictOnDelete();
            $table->string('movement_type', 30);
            $table->timestamp('movement_date');
            $table->decimal('quantity_in', 12, 4)->default(0);
            $table->decimal('quantity_out', 12, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('total_cost', 14, 4)->default(0);
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_inventory_lot');
            $table->index(['id_product', 'id_warehouse']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('inventory_cost_averages', function (Blueprint $table) {
            $table->id('id_inventory_cost_average');
            $table->foreignId('id_company')->constrained('companies', 'id_company')->restrictOnDelete();
            $table->foreignId('id_product')->constrained('products', 'id_product')->restrictOnDelete();
            $table->decimal('quantity_on_hand', 14, 4)->default(0);
            $table->decimal('inventory_value', 16, 4)->default(0);
            $table->decimal('average_unit_cost', 14, 4)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['id_company', 'id_product']);
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->foreignId('id_location')->nullable()->constrained('locations', 'id_location')->nullOnDelete();
        });

        DB::table('permissions')->insertOrIgnore([
            'id_permission' => 'inventory.ver',
            'name' => 'Ver Existencias de Inventario',
            'description' => 'Permite consultar las existencias por lote, bodega y ubicación, sus movimientos y el costo promedio.',
            'action' => 'index',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('roles_permissions')->where('id_permission', 'warehouses.ver')->pluck('id_role') as $role) {
            DB::table('roles_permissions')->insertOrIgnore(['id_role' => $role, 'id_permission' => 'inventory.ver']);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('id_permission', 'inventory.ver')->delete();

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropForeign(['id_location']);
            $table->dropColumn('id_location');
        });

        Schema::dropIfExists('inventory_cost_averages');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_lots');
    }
};
