<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_quotation_request_id');
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('taxes', 12, 2)->default(0);
            $table->decimal('additional_expenses', 12, 2)->default(0);
            $table->text('conditions')->nullable();
            $table->decimal('total', 12, 2);
            $table->timestamps();

            $table->foreign('purchase_quotation_request_id')
                  ->references('id_purchase_quotation_request')
                  ->on('purchase_quotation_requests')
                  ->onDelete('cascade');

            $table->foreign('supplier_id')
                  ->references('id_supplier')
                  ->on('suppliers')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
    }
};