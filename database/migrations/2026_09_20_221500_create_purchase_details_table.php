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
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id('id_purchase_detail');

            $table->foreignId('id_purchase')
                ->constrained('purchases', 'id_purchase')
                ->cascadeOnDelete();

            $table->foreignId('id_purchase_order_detail')
                ->nullable()
                ->constrained('purchase_order_details', 'id_purchase_order_detail')
                ->nullOnDelete();

            $table->foreignId('id_product')
                ->constrained('products', 'id')
                ->restrictOnDelete();

            $table->decimal('quantity_ordered', 12, 4)->default(0);
            $table->decimal('quantity_received', 12, 4);

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

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_details');
    }
};
