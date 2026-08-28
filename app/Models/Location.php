<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    protected $fillable = [
        'warehouse_id',
        'code',
        'pasillo',
        'rack',
        'level',
        'position',
        'capacity',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    /**
     * Generar el código concatenando los datos (ej: B0D1 - PAS2 - EST3 - NIV1 - P0S4).
     */
    public static function generateCode($warehouse_id, $pasillo, $rack, $level, $position): string
    {
        $parts = [];
        
        if ($warehouse_id) {
            $parts[] = 'B0D' . $warehouse_id;
        }
        if ($pasillo !== null && $pasillo !== '') {
            $parts[] = 'PAS' . $pasillo;
        }
        if ($rack !== null && $rack !== '') {
            $parts[] = 'EST' . $rack;
        }
        if ($level !== null && $level !== '') {
            $parts[] = 'NIV' . $level;
        }
        if ($position !== null && $position !== '') {
            $parts[] = 'P0S' . $position;
        }

        return implode(' - ', $parts);
    }

    /**
     * Relación con el almacén (warehouse) al que pertenece la ubicación.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}