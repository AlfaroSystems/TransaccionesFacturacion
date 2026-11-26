<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos del pago según la forma de pago (tarjeta, cheque): referencia o autorización, banco y
 * número de comprobante. Quedan nulos cuando el pago es en efectivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_reference', 100)->nullable()->after('payment_method');
            $table->string('payment_bank', 100)->nullable()->after('payment_reference');
            $table->string('payment_document_number', 100)->nullable()->after('payment_bank');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'payment_bank', 'payment_document_number']);
        });
    }
};
