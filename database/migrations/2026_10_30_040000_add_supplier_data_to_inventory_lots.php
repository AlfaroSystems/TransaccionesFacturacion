<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos del fabricante o proveedor en cada lote: su número de lote y la fecha de vencimiento.
 * Se anotan, o se reparten al dividir el lote, mientras está por ubicar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_lots', function (Blueprint $table) {
            $table->string('supplier_lot_number', 100)->nullable();
            $table->date('expiration_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_lots', function (Blueprint $table) {
            $table->dropColumn(['supplier_lot_number', 'expiration_date']);
        });
    }
};
