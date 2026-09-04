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
        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_quotation_requests', 'id_supplier')) {
                $table->dropForeign(['id_supplier']);
                $table->dropColumn('id_supplier');
            }
            if (Schema::hasColumn('purchase_quotation_requests', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('purchase_quotation_requests', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('purchase_quotation_requests', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });

        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_quotation_request_details', 'id_purchase_quotation_request')) {
                $table->dropForeign(['id_purchase_quotation_request']);
                $table->dropColumn('id_purchase_quotation_request');
            }
            if (Schema::hasColumn('purchase_quotation_request_details', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('purchase_quotation_request_details', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            $table->foreignId('id_supplier')->nullable()->constrained('suppliers', 'id_supplier')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->foreignId('id_purchase_quotation_request')->nullable()->constrained('purchase_quotation_requests', 'id_purchase_quotation_request')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
};
