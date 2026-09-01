<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones de la tabla de imágenes de productos.
     */
    public function up(): void
    {
        Schema::create('products_images', function (Blueprint $table) {
            $table->id('id_product_image');
            $table->uuid('uuid')->unique();
            $table->foreignId('id_product')->constrained('products')->onDelete('cascade');
            $table->string('path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_images');
    }
};
