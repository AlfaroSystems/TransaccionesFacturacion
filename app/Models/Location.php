<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Location extends Model
{
    use BelongsToBranch;

    // Ubicaciones de las bodegas visibles (la bodega aplica su propio filtro)
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereHas('warehouse');
    }

    protected $primaryKey = 'id_location';

    protected $fillable = [
        'id_warehouse',
        'code',
        'aisle',
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
     * Cada ubicación de bodega es una ubicación de inventario: se crea con ella y se activa o
     * desactiva con ella.
     */
    protected static function booted(): void
    {
        static::created(fn (Location $location) => InventoryLocation::create([
            'id_location' => $location->id_location,
            'location_type' => InventoryLocation::TYPE_LOCATION,
            'is_active' => $location->is_active ?? true,
        ]));

        static::updated(function (Location $location) {
            if ($location->wasChanged('is_active')) {
                InventoryLocation::where('id_location', $location->id_location)
                    ->get()
                    ->each->update(['is_active' => $location->is_active]);
            }
        });
    }

    /**
     * Generar el código concatenando los datos (ej: B0D1 - PAS2 - EST3 - NIV1 - P0S4).
     */
    public static function generateCode($id_warehouse, $aisle, $rack, $level, $position): string
    {
        $parts = [];

        if ($id_warehouse) {
            $parts[] = 'B0D'.$id_warehouse;
        }
        if ($aisle !== null && $aisle !== '') {
            $parts[] = 'PAS'.$aisle;
        }
        if ($rack !== null && $rack !== '') {
            $parts[] = 'EST'.$rack;
        }
        if ($level !== null && $level !== '') {
            $parts[] = 'NIV'.$level;
        }
        if ($position !== null && $position !== '') {
            $parts[] = 'P0S'.$position;
        }

        return implode(' - ', $parts);
    }

    /**
     * Relación con el almacén (warehouse) al que pertenece la ubicación.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }

    /**
     * La bodega sin el filtro por sucursal: el departamento de compras ve existencias de las
     * bodegas de toda su empresa.
     */
    public function warehouseAllBranches(): BelongsTo
    {
        return $this->warehouse()->withoutGlobalScope(BranchScope::class);
    }

    public function inventoryLocation(): HasOne
    {
        return $this->hasOne(InventoryLocation::class, 'id_location', 'id_location');
    }
}
