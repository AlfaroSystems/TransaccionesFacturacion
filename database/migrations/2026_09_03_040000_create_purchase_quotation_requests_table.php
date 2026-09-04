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
        Schema::create('purchase_quotation_requests', function (Blueprint $table) {
            $table->id('id_purchase_quotation_request');

            $table->foreignId('id_purchase_request')
                ->constrained('purchase_requests', 'id_purchase_request')
                ->cascadeOnDelete();

            $table->foreignId('id_supplier')
                ->nullable()
                ->constrained('suppliers', 'id_supplier')
                ->nullOnDelete();

            if (Schema::hasTable('purchase_quotations')) {
                $table->foreignId('id_purchase_quotation')
                    ->nullable()
                    ->constrained('purchase_quotations', 'id_purchase_quotation')
                    ->nullOnDelete();
            } else {
                $table->unsignedBigInteger('id_purchase_quotation')->nullable();
            }

            $table->string('status')->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_quotation_requests');
    }
};
