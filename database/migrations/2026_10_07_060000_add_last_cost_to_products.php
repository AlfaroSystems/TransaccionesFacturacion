<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Último costo de los productos (ver App\Services\ProductCostService).
 *
 * Sin inventario no hay existencias para un costo promedio: el producto guarda el costo de
 * su compra más reciente, y cada línea de compra, el costo con que entró.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('unit_cost', 14, 4)->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('last_cost', 14, 4)->nullable();
            $table->timestamp('last_cost_date')->nullable();
            // Línea de compra de donde salió el costo: da la unidad, la compra y la fecha
            $table->foreignId('id_last_cost_purchase_detail')->nullable()
                ->constrained('purchase_details', 'id_purchase_detail')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_last_cost_purchase_detail');
            $table->dropColumn(['last_cost', 'last_cost_date']);
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
