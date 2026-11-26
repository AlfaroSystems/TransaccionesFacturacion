<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Presentaciones de venta de un producto (Unidad, Blíster, Caja...). Cada una con su factor
 * (cuántas unidades base/de inventario contiene) y su propio precio (ver product_prices).
 *
 * Al vender una presentación se descuentan cantidad × factor unidades base del inventario.
 * A cada producto existente se le crea la presentación por defecto "Unidad" con factor 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_presentations', function (Blueprint $table) {
            $table->id('id_product_presentation');
            $table->foreignId('id_product')->constrained('products', 'id_product')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('factor', 12, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['id_product', 'name']);
            $table->index('id_product');
        });

        // Presentación por defecto para cada producto existente
        DB::statement("INSERT INTO product_presentations (id_product, name, factor, is_active, created_at)
            SELECT id_product, 'Unidad', 1, true, NOW() FROM products");
    }

    public function down(): void
    {
        Schema::dropIfExists('product_presentations');
    }
};
