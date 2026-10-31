<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Otros gastos aduanales / seguro del retaceo, uno por línea con su tipo de gasto (como los
 * gastos de la orden). retaceos.total_expenses es su suma, la que se prorratea.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retaceo_expenses', function (Blueprint $table) {
            $table->id('id_retaceo_expense');
            $table->foreignId('id_retaceo')->constrained('retaceos', 'id_retaceo')->cascadeOnDelete();
            $table->foreignId('id_expense_type')->constrained('expense_types', 'id_expense_type')->restrictOnDelete();
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 14, 4)->default(0);
            $table->timestamps();

            $table->index('id_retaceo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retaceo_expenses');
    }
};
