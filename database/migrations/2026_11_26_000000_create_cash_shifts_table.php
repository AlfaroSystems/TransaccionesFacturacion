<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turnos de caja (arqueo). El POS exige un turno abierto en la sucursal para poder vender.
 *
 * - Se abre con un monto inicial (apertura). Cada venta entregada suma al turno su total y el
 *   total por forma de pago. Los egresos (retiros) restan del efectivo esperado.
 * - Al cerrar se registra lo contado (arqueo) y se calcula la diferencia contra lo esperado:
 *   efectivo esperado = apertura + ventas en efectivo - egresos.
 * - Un solo turno abierto por sucursal a la vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id('id_cash_shift');
            $table->string('shift_code', 30)->unique();
            $table->foreignId('id_branch')->constrained('branches', 'id_branch')->restrictOnDelete();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_amount', 14, 2)->default(0);
            $table->decimal('total_sales', 14, 2)->default(0);
            $table->decimal('total_cash', 14, 2)->default(0);
            $table->decimal('total_card', 14, 2)->default(0);
            $table->decimal('total_check', 14, 2)->default(0);
            $table->decimal('total_outflows', 14, 2)->default(0);
            $table->decimal('counted_amount', 14, 2)->nullable();
            $table->decimal('difference', 14, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('id_closed_by')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['id_branch', 'status']);
        });

        // Un solo turno abierto por sucursal
        DB::statement("CREATE UNIQUE INDEX cash_shifts_one_open_per_branch ON cash_shifts (id_branch) WHERE status = 'open'");

        Schema::create('cash_outflows', function (Blueprint $table) {
            $table->id('id_cash_outflow');
            $table->foreignId('id_cash_shift')->constrained('cash_shifts', 'id_cash_shift')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('concept', 200);
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_cash_shift');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_outflows');
        Schema::dropIfExists('cash_shifts');
    }
};
