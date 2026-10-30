<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Una compra tiene a lo sumo un retaceo activo (cualquier estado salvo cancelado). Lo revisa
 * RetaceoService::crear con la compra bloqueada; el índice lo garantiza también en la base,
 * para cualquier otro camino que inserte retaceos.
 *
 * Si ya hay compras con más de un retaceo activo, la migración se detiene y las lista: hay que
 * cancelar los sobrantes antes de volver a ejecutarla.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repetidas = DB::table('retaceos')
            ->where('status', '!=', 'cancelled')
            ->groupBy('id_purchase')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('id_purchase');

        if ($repetidas->isNotEmpty()) {
            throw new RuntimeException('Compras con más de un retaceo activo (id_purchase): '
                .$repetidas->implode(', ').'. Cancele los sobrantes y vuelva a ejecutar la migración.');
        }

        DB::statement("CREATE UNIQUE INDEX retaceos_id_purchase_active_unique ON retaceos (id_purchase) WHERE status <> 'cancelled'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS retaceos_id_purchase_active_unique');
    }
};
