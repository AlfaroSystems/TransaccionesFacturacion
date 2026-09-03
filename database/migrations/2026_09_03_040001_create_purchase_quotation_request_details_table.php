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
        Schema::create('purchase_quotation_request_details', function (Blueprint $table) {
            $table->id('id_purchase_quotation_request_detail');

            $table->foreignId('id_purchase_quotation_request')
                ->constrained('purchase_quotation_requests', 'id_purchase_quotation_request')
                ->cascadeOnDelete();

            $table->foreignId('id_purchase_request_detail')
                ->constrained('purchase_request_details', 'id_purchase_request_detail')
                ->restrictOnDelete();

            if (Schema::hasTable('purchase_quotation_details')) {
                $table->foreignId('id_purchase_quotation_detail')
                    ->nullable()
                    ->constrained('purchase_quotation_details', 'id_purchase_quotation_detail')
                    ->nullOnDelete();
            } else {
                $table->unsignedBigInteger('id_purchase_quotation_detail')->nullable();
            }

            $table->decimal('quantity', 12, 4);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_quotation_request_details');
    }
};
