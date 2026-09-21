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
        Schema::create('retaceo_details', function (Blueprint $table) {
            $table->id('id_retaceo_detail');

            $table->foreignId('id_retaceo')
                ->constrained('retaceos', 'id_retaceo')
                ->cascadeOnDelete();

            $table->foreignId('id_purchase_detail')
                ->nullable()
                ->constrained('purchase_details', 'id_purchase_detail')
                ->nullOnDelete();

            $table->foreignId('id_product')
                ->constrained('products', 'id')
                ->restrictOnDelete();

            $table->decimal('quantity', 12, 4);
            $table->decimal('cost_fob', 14, 4)->default(0);
            $table->decimal('freight_amount', 14, 4)->default(0);
            $table->decimal('expense_amount', 14, 4)->default(0);
            $table->decimal('dai_amount', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('total_cost', 14, 4)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retaceo_details');
    }
};
