<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Correlativos de documentos (REQ-2026-0001, OC-2026-0001, ...).
 *
 * El último número de cada prefijo vive en document_sequences y se incrementa en una sola
 * sentencia, así que dos documentos creados a la vez nunca reciben el mismo código. Dentro
 * de una transacción el contador queda bloqueado hasta confirmarla; si se revierte, el
 * número se vuelve a usar. Los números de documentos eliminados no se reutilizan.
 */
final class DocumentSequence
{
    /**
     * Siguiente código del prefijo, p. ej. "OC-2026-" → "OC-2026-0007".
     *
     * La primera vez que se usa un prefijo, el contador arranca desde el código más alto que
     * ya exista en $table.$column.
     */
    public static function next(string $prefix, string $table, string $column, int $digits = 4): string
    {
        $row = DB::selectOne(
            'UPDATE document_sequences SET last_number = last_number + 1 WHERE prefix = ? RETURNING last_number',
            [$prefix]
        );

        // Si dos procesos inicializan el mismo prefijo a la vez, el segundo cae en ON CONFLICT
        // y toma el número siguiente
        $row ??= DB::selectOne(
            "INSERT INTO document_sequences (prefix, last_number)
             SELECT ?, COALESCE(MAX(CAST(SUBSTRING({$column} FROM CAST(? AS TEXT)) AS BIGINT)), 0) + 1
             FROM {$table}
             WHERE {$column} LIKE ?
             ON CONFLICT (prefix) DO UPDATE SET last_number = document_sequences.last_number + 1
             RETURNING last_number",
            [$prefix, '^' . preg_quote($prefix) . '([0-9]+)$', $prefix . '%']
        );

        return $prefix . str_pad((string) $row->last_number, $digits, '0', STR_PAD_LEFT);
    }
}
