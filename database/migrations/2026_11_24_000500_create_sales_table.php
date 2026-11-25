<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas: la venta de productos desde una sucursal. Al entregar se descuenta el inventario de
 * esa sucursal con PEPS (lote más antiguo primero) y se registra la salida en el Kardex.
 *
 * - document_type: invoice (Factura de consumidor final) o ccf (Comprobante de Crédito Fiscal).
 * - payment_method: cash (efectivo), credit_card (tarjeta) o check (cheque).
 * - total_cost: costo de venta (COGS) por PEPS; se calcula al entregar. Dato interno.
 * - status: draft, confirmed, delivered (genera la salida), cancelled.
 * - pdf_path / emailed_at: comprobante generado y enviado al cliente (fase posterior).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id('id_sale');
            $table->uuid('uuid');
            $table->string('sale_code', 30)->unique();
            $table->foreignId('id_branch')->constrained('branches', 'id_branch')->restrictOnDelete();
            $table->foreignId('id_customer')->constrained('customers', 'id_customer')->restrictOnDelete();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->timestamp('sale_date');
            $table->string('document_type', 20)->default('invoice');
            $table->string('fiscal_document_number', 50)->nullable();
            $table->string('payment_method', 20);
            $table->string('currency', 10)->default('USD');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 4)->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('delivered_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['id_branch', 'status']);
            $table->index('id_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
