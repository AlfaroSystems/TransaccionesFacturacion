<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaza cada venta con el turno de caja en que se registró (para el arqueo y el corte de caja).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('id_cash_shift')->nullable()->after('id_branch')
                ->constrained('cash_shifts', 'id_cash_shift')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_cash_shift');
        });
    }
};
