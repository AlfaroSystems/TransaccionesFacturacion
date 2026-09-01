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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // UUID único autogenerado y SKU controlado único
            $table->uuid('uuid')->unique();
            $table->string('sku', 50)->unique();
            $table->string('original_code', 100)->nullable();
            $table->string('internal_code', 100)->nullable();
            $table->string('name', 200);
            $table->string('size', 100)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->string('presentation', 100)->nullable();
            $table->text('description')->nullable();
            
            // Claves foráneas solicitadas
            $table->foreignId('id_category')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('id_sub_category')->nullable()->constrained('sub_categories')->nullOnDelete();
            $table->foreignId('purchase_unit')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('sale_unit')->nullable()->constrained('units')->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
