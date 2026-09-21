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
        Schema::create('retaceos', function (Blueprint $table) {
            $table->id('id_retaceo');
            $table->uuid('uuid')->unique();
            $table->string('retaceo_code', 50)->unique();

            $table->foreignId('id_supplier')
                ->constrained('suppliers', 'id_supplier')
                ->cascadeOnDelete();

            $table->foreignId('id_purchase')
                ->constrained('purchases', 'id_purchase')
                ->cascadeOnDelete();

            $table->dateTime('retaceo_date');
            $table->string('origin_country', 100)->nullable();
            $table->string('import_invoice_number', 100)->nullable();
            $table->date('import_invoice_date')->nullable();
            $table->string('import_policy_number', 100)->nullable();
            $table->date('import_policy_date')->nullable();

            $table->decimal('total_fob', 14, 4)->default(0);
            $table->decimal('total_freight', 14, 4)->default(0);
            $table->decimal('total_expenses', 14, 4)->default(0);
            $table->decimal('total_dai', 14, 4)->default(0);
            $table->decimal('total_cost', 14, 4)->default(0);

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
        Schema::dropIfExists('retaceos');
    }
};
