<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_quotation_expenses', function (Blueprint $table) {
            $table->id('id_purchase_quotation_expense');

            $table->foreignId('id_purchase_quotation')
                ->constrained('purchase_quotations', 'id_purchase_quotation')
                ->cascadeOnDelete();

            $table->foreignId('id_expense_type')
                ->constrained('expense_types', 'id_expense_type')
                ->restrictOnDelete();

            $table->string('description', 255)->nullable();
            $table->decimal('amount', 14, 4)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_quotation_expenses');
    }
};
