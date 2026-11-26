<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La línea de venta guarda la presentación vendida (Unidad/Blíster/Caja). El factor para
 * convertir a unidades base del inventario sale de la presentación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_details', function (Blueprint $table) {
            $table->foreignId('id_product_presentation')->nullable()->after('id_product')
                ->constrained('product_presentations', 'id_product_presentation')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_product_presentation');
        });
    }
};
