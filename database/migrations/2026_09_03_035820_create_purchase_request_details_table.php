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
        Schema::create('purchase_request_details', function (Blueprint $table) {
            $table->id('id_purchase_request_detail');
            $table->foreignId('id_purchase_request')
                ->constrained('purchase_requests', 'id_purchase_request')
                ->cascadeOnDelete();
            $table->foreignId('id_product')
                ->constrained('products', 'id')
                ->restrictOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->foreignId('id_unit')
                ->constrained('units', 'id')
                ->restrictOnDelete();
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_details');
    }
};