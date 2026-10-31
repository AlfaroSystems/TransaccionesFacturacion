<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario (fase 2): traslados entre bodegas e impresión de etiquetas de lote.
 *
 * - inventory_transfers: Casa Matriz reparte a las sucursales lo que entró con las compras. El
 *   traslado va de una bodega origen a una destino; mientras viaja, la mercadería está en la
 *   ubicación del vehículo (id_vehicle_location). Se despacha y luego se recibe.
 * - inventory_transfer_details: cada lote trasladado, de qué ubicación sale y a cuál llega, con
 *   lo despachado, lo recibido y su costo.
 * - inventory_label_prints: cada impresión de etiquetas de un lote; una reimpresión lleva su motivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id('id_inventory_transfer');
            $table->string('transfer_code', 50)->unique();
            $table->foreignId('id_source_warehouse')->constrained('warehouses', 'id_warehouse')->restrictOnDelete();
            $table->foreignId('id_destination_warehouse')->constrained('warehouses', 'id_warehouse')->restrictOnDelete();
            $table->foreignId('id_vehicle_location')->nullable()->constrained('locations', 'id_location')->restrictOnDelete();
            $table->timestamp('transfer_date');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('status');
        });

        Schema::create('inventory_transfer_details', function (Blueprint $table) {
            $table->id('id_inventory_transfer_detail');
            $table->foreignId('id_inventory_transfer')->constrained('inventory_transfers', 'id_inventory_transfer')->cascadeOnDelete();
            $table->foreignId('id_inventory_lot')->constrained('inventory_lots', 'id_inventory_lot')->restrictOnDelete();
            $table->foreignId('id_product')->constrained('products', 'id_product')->restrictOnDelete();
            $table->foreignId('id_source_location')->nullable()->constrained('locations', 'id_location')->restrictOnDelete();
            $table->foreignId('id_destination_location')->nullable()->constrained('locations', 'id_location')->restrictOnDelete();
            $table->decimal('quantity_dispatched', 12, 4);
            $table->decimal('quantity_received', 12, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->text('notes')->nullable();

            $table->index('id_inventory_transfer');
            $table->index('id_inventory_lot');
        });

        Schema::create('inventory_label_prints', function (Blueprint $table) {
            $table->id('id_inventory_label_print');
            $table->foreignId('id_inventory_lot')->constrained('inventory_lots', 'id_inventory_lot')->restrictOnDelete();
            $table->unsignedInteger('quantity_printed');
            $table->timestamp('print_date')->useCurrent();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->string('reprint_reason', 255)->nullable();

            $table->index('id_inventory_lot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_label_prints');
        Schema::dropIfExists('inventory_transfer_details');
        Schema::dropIfExists('inventory_transfers');
    }
};
