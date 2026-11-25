<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Desglose PEPS del costo de cada línea de venta: de qué lotes y ubicaciones salió la mercadería
 * y a qué costo. Una línea puede consumir varios lotes (varias filas), cada una con su
 * movimiento de Kardex. Da la trazabilidad del costo: desde la venta se llega a la compra/retaceo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_cost_details', function (Blueprint $table) {
            $table->id('id_sales_cost_detail');
            $table->foreignId('id_sale_detail')->constrained('sales_details', 'id_sale_detail')->cascadeOnDelete();
            $table->foreignId('id_inventory_lot')->constrained('inventory_lots', 'id_inventory_lot')->restrictOnDelete();
            $table->foreignId('id_inventory_location')->constrained('inventory_locations', 'id_inventory_location')->restrictOnDelete();
            $table->foreignId('id_inventory_movement')->nullable()->unique()->constrained('inventory_movements', 'id_inventory_movement')->nullOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 14, 4);
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_sale_detail');
            $table->index('id_inventory_lot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_cost_details');
    }
};
