<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El precio de venta pasa a ser por presentación. Se agrega id_product_presentation a
 * product_prices, se reasignan los precios existentes a la presentación por defecto del
 * producto, y el índice de "un solo precio vigente" pasa a ser por presentación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->foreignId('id_product_presentation')->nullable()->after('id_product')
                ->constrained('product_presentations', 'id_product_presentation')->cascadeOnDelete();
        });

        // Los precios existentes eran por producto: van a su presentación por defecto ("Unidad")
        DB::statement("UPDATE product_prices pp
            SET id_product_presentation = ppr.id_product_presentation
            FROM product_presentations ppr
            WHERE ppr.id_product = pp.id_product AND ppr.name = 'Unidad'");

        // Un solo precio vigente por presentación (antes era por producto)
        DB::statement('DROP INDEX IF EXISTS product_prices_one_current');
        DB::statement('CREATE UNIQUE INDEX product_prices_one_current ON product_prices (id_product_presentation) WHERE valid_until IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS product_prices_one_current');
        DB::statement('CREATE UNIQUE INDEX product_prices_one_current ON product_prices (id_product) WHERE valid_until IS NULL');

        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_product_presentation');
        });
    }
};
