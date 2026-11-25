<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de precios de venta por producto (módulo de Asignación de Precios).
 *
 * Los precios varían en el tiempo. Cada cambio cierra el precio vigente (valid_until = fecha del
 * cambio) y abre uno nuevo (valid_from = fecha del cambio, valid_until = NULL = vigente). No se
 * borra ni se modifica un precio anterior: así se sabe qué precio tenía el producto en cada fecha.
 *
 * La venta toma el precio vigente y lo copia como snapshot en la línea.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id('id_product_price');
            $table->foreignId('id_product')->constrained('products', 'id_product')->restrictOnDelete();
            $table->decimal('price', 14, 4);
            $table->string('currency', 10)->default('USD');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_product', 'valid_from']);
        });

        // Un solo precio vigente por producto (el que no tiene fecha de cambio)
        DB::statement('CREATE UNIQUE INDEX product_prices_one_current ON product_prices (id_product) WHERE valid_until IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
