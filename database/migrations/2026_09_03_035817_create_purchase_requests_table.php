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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id('id_purchase_request');
            $table->uuid('uuid')->unique();
            $table->string('purchase_request_code')->unique();
            $table->foreignId('id_branch')
                ->constrained('branches', 'id')
                ->restrictOnDelete();
            $table->foreignId('id_warehouse')
                ->constrained('warehouses', 'id')
                ->restrictOnDelete();
            $table->foreignId('id_user')
                ->constrained('users', 'id')
                ->restrictOnDelete();
            $table->dateTime('request_date');
            $table->dateTime('required_date');
            $table->text('justification');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};