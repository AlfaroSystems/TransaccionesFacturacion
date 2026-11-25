<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Líneas de la venta. El precio es el vigente del producto al momento de la venta (snapshot).
 * unit_cost y total_cost son el costo PEPS de la línea; se calculan al entregar (ver
 * sales_cost_details, que guarda de qué lotes salió).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_details', function (Blueprint $table) {
            $table->id('id_sale_detail');
            $table->foreignId('id_sale')->constrained('sales', 'id_sale')->cascadeOnDelete();
            $table->foreignId('id_product')->constrained('products', 'id_product')->restrictOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->foreignId('id_unit')->nullable()->constrained('units', 'id_unit')->nullOnDelete();
            $table->decimal('unit_price', 14, 4);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_rate', 6, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('total_cost', 14, 4)->default(0);
            $table->text('notes')->nullable();

            $table->index('id_sale');
            $table->index('id_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_details');
    }
};
