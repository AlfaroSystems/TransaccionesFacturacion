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
        Schema::create('purchase_quotations', function (Blueprint $table) {
            $table->id('id_purchase_quotation');
            $table->uuid('uuid')->unique();
            $table->string('purchase_quotation_code', 50)->unique();

            $table->foreignId('id_purchase_quotation_request')
                ->nullable()
                ->constrained('purchase_quotation_requests', 'id_purchase_quotation_request')
                ->cascadeOnDelete();

            $table->foreignId('id_supplier')
                ->constrained('suppliers', 'id_supplier')
                ->cascadeOnDelete();

            $table->dateTime('quotation_date');
            $table->dateTime('valid_until')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('payment_terms')->nullable();
            $table->integer('delivery_days')->nullable();

            $table->decimal('subtotal', 14, 4)->default(0);
            $table->decimal('discount', 14, 4)->default(0);
            $table->decimal('tax', 14, 4)->default(0);
            $table->decimal('total', 14, 4)->default(0);

            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_quotations');
    }
};
