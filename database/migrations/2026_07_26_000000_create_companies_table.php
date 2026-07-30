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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('commercial_name')->nullable();
            $table->string('nit')->nullable();
            $table->string('nrc')->nullable();
            
            // Giros comerciales
            $table->string('commercial_line_1')->nullable();
            $table->string('commercial_line_2')->nullable();
            $table->string('commercial_line_3')->nullable();
            
            $table->string('address')->nullable();
            
            // Relaciones geográficas (El Salvador)
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('web_site')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
