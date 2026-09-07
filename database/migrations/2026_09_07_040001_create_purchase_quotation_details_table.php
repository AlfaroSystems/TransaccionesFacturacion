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
        Schema::create('purchase_quotation_details', function (Blueprint $table) {
            $table->id('id_purchase_quotation_detail');

            $table->foreignId('id_purchase_quotation')
                ->constrained('purchase_quotations', 'id_purchase_quotation')
                ->cascadeOnDelete();

            $table->foreignId('id_product')
                ->constrained('products', 'id')
                ->restrictOnDelete();

            $table->decimal('quantity', 12, 4);

            $table->foreignId('id_unit')
                ->nullable()
                ->constrained('units', 'id')
                ->nullOnDelete();

            $table->decimal('unit_price', 14, 4)->default(0);
            $table->decimal('discount', 14, 4)->default(0);
            $table->decimal('subtotal', 14, 4)->default(0);

            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 14, 4)->default(0);
            $table->decimal('total', 14, 4)->default(0);

            $table->integer('delivery_days')->nullable();
            $table->decimal('available_quantity', 12, 4)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_quotation_details');
    }
};
