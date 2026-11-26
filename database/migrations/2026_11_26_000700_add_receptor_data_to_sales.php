<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos fiscales del receptor en la venta (copia/snapshot del cliente al momento de la venta).
 * Los exige el Comprobante de Crédito Fiscal (CCF): razón social, documento, NRC, giro y
 * dirección. Se conservan en la venta para que no cambien si luego se edita el cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('receptor_business_name')->nullable()->after('id_customer');
            $table->string('receptor_document_type', 10)->nullable()->after('receptor_business_name');
            $table->string('receptor_document_number', 50)->nullable()->after('receptor_document_type');
            $table->string('receptor_nrc', 50)->nullable()->after('receptor_document_number');
            $table->string('receptor_line')->nullable()->after('receptor_nrc');
            $table->string('receptor_address')->nullable()->after('receptor_line');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'receptor_business_name', 'receptor_document_type', 'receptor_document_number',
                'receptor_nrc', 'receptor_line', 'receptor_address',
            ]);
        });
    }
};
