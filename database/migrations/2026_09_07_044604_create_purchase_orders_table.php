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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id('id_purchase_order');
            $table->uuid('uuid')->unique();
            $table->string('purchase_order_code', 50)->unique();

            $table->foreignId('id_supplier')
                ->constrained('suppliers', 'id_supplier')
                ->cascadeOnDelete();

            $table->foreignId('id_branch')
                ->nullable()
                ->constrained('branches', 'id')
                ->nullOnDelete();

            $table->foreignId('id_warehouse')
                ->nullable()
                ->constrained('warehouses', 'id')
                ->nullOnDelete();

            $table->foreignId('id_purchase_quotation')
                ->nullable()
                ->constrained('purchase_quotations', 'id_purchase_quotation')
                ->nullOnDelete();

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->dateTime('order_date');
            $table->dateTime('expected_date')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('payment_terms')->nullable();

            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('discount', 14, 4)->default(0);
            $table->decimal('tax', 14, 4)->default(0);
            $table->decimal('additional_expenses', 14, 4)->default(0);
            $table->decimal('total', 14, 4)->default(0);

            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};

