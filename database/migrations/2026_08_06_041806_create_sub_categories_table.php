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
        Schema::create('sub_categories', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('id_category');

            $table->string('name');

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreign('id_category')
                  ->references('id_category')
                  ->on('categories')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_categories');
    }
};