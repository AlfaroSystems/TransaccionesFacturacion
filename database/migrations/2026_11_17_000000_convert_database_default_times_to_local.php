<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las columnas que llena PostgreSQL (CURRENT_TIMESTAMP, NOW()) quedaban en UTC, la zona de la
 * base, mientras Laravel guarda en la de la aplicación (El Salvador): la bitácora mostraba las
 * 6:20 PM como las 00:20 del día siguiente. Desde ahora la conexión usa la zona de la aplicación
 * (config/database.php); aquí se pasa a hora local lo que ya se guardó en UTC.
 *
 * La aplicación usa la hora de El Salvador desde el 08/09/2026 a las 06:12 UTC: lo anterior se
 * guardó en UTC en todas las tablas y se deja como está, para no descuadrarlo con sus registros.
 */
return new class extends Migration
{
    private const DESDE = '2026-09-08 06:12:05';

    private const ZONA = 'America/El_Salvador';

    public function up(): void
    {
        $aLocal = fn (string $columna) => "({$columna} AT TIME ZONE 'UTC') AT TIME ZONE '".self::ZONA."'";

        DB::update('UPDATE logs SET created_at = '.$aLocal('created_at').' WHERE created_at >= ?', [self::DESDE]);
        DB::update('UPDATE users_roles SET assigned_at = '.$aLocal('assigned_at').' WHERE assigned_at >= ?', [self::DESDE]);
        // La tabla es del 11/11/2026: todo lo llenó NOW() en UTC
        DB::update('UPDATE inventory_location_details SET updated_at = '.$aLocal('updated_at').' WHERE updated_at IS NOT NULL');
    }

    public function down(): void
    {
        // Sin vuelta atrás: la hora local es la correcta y no se distingue qué filas se convirtieron
    }
};
