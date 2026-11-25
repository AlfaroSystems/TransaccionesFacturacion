<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes del módulo de Ventas. Catálogo general (como suppliers): no se borra, se desactiva.
 *
 * - customer_type: final (consumidor final) o taxpayer (contribuyente). Define el documento.
 * - document_type / document_number: DUI o NIT del cliente.
 * - nrc: solo para contribuyentes.
 * - email: necesario para enviarle el comprobante por correo.
 * - Ubicación geográfica de El Salvador (departments, municipalities, districts), opcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id('id_customer');
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('customer_type', 20)->default('final');
            $table->string('document_type', 10)->nullable();
            $table->string('document_number', 50)->nullable();
            $table->string('nrc', 50)->nullable();
            $table->string('commercial_line')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('id_department')->nullable()->constrained('departments', 'id_department')->nullOnDelete();
            $table->foreignId('id_municipality')->nullable()->constrained('municipalities', 'id_municipality')->nullOnDelete();
            $table->foreignId('id_district')->nullable()->constrained('districts', 'id_district')->nullOnDelete();
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
