<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas (prerrequisito): factor de conversión entre la unidad de inventario (compra) y la
 * unidad de venta de cada producto. El inventario se lleva en la unidad de compra; al vender,
 * la cantidad de venta se convierte a unidad base: cantidad_base = cantidad_venta / factor.
 *
 * Ejemplo: se compra por caja de 12 y se vende por unidad -> factor = 12. Si compra y venta
 * usan la misma unidad, el factor es 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('base_to_sale_factor', 12, 4)->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('base_to_sale_factor');
        });
    }
};
