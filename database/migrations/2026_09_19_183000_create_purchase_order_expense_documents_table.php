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
        Schema::create('purchase_order_expense_documents', function (Blueprint $table) {
            $table->id('id_purchase_order_expense_document');

            $table->foreignId('id_purchase_order_expense')
                ->constrained('purchase_order_expenses', 'id_purchase_order_expense')
                ->cascadeOnDelete();

            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 100)->nullable();
            $table->timestamp('uploaded_at')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_expense_documents');
    }
};
