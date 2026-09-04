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

            if (Schema::hasTable('purchase_quotation_details')) {
                $table->foreignId('id_purchase_quotation_detail')
                    ->nullable()
                    ->constrained('purchase_quotation_details', 'id_purchase_quotation_detail')
                    ->nullOnDelete();
            } else {
                $table->unsignedBigInteger('id_purchase_quotation_detail')->nullable();
            }

            $table->foreignId('id_purchase_request_detail')
                ->constrained('purchase_request_details', 'id_purchase_request_detail')
                ->restrictOnDelete();

            $table->decimal('quantity', 12, 4);

            $table->timestamp('created_at')->useCurrent();
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
