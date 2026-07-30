<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear tabla de sucursales.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            
            // Relación con la empresa
            $table->foreignId('company_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Datos de la sucursal
            $table->string('name');
            $table->string('address')->nullable();
            
            // Relaciones geográficas (El Salvador)
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();

            // Estado de la sucursal
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Eliminar tabla de sucursales.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
