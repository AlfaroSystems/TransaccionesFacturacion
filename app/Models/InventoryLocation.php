<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lugar donde puede estar un lote: una ubicación de bodega o el espacio de carga de un
 * vehículo. Se crea y se activa o desactiva con su ubicación (ver Location y VehicleLocation).
 * Los movimientos, la existencia por ubicación y los traslados apuntan aquí.
 */
class InventoryLocation extends Model
{
    public const TYPE_LOCATION = 'location';

    public const TYPE_VEHICLE = 'vehicle';

    public const TYPE_LABELS = [
        self::TYPE_LOCATION => 'Ubicación de bodega',
        self::TYPE_VEHICLE => 'Vehículo',
    ];

    public $timestamps = false;

    protected $table = 'inventory_locations';

    protected $primaryKey = 'id_inventory_location';

    protected $fillable = [
        'id_location',
        'id_vehicle_location',
        'location_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Ubicaciones de inventario de las bodegas indicadas (un id o una subconsulta de ids).
     */
    public function scopeInWarehouses(Builder $query, int|array|Builder $warehouses): void
    {
        $warehouses = is_int($warehouses) ? [$warehouses] : $warehouses;

        $query->where(fn (Builder $q) => $q
            ->whereIn('id_location', Location::queryAllBranches()->whereIn('id_warehouse', $warehouses)->select('id_location'))
            ->orWhereIn('id_vehicle_location', VehicleLocation::queryAllBranches()->whereIn('id_warehouse', $warehouses)->select('id_vehicle_location')));
    }

    /**
     * Ubicación de inventario de cada ubicación de bodega, por id de la ubicación.
     *
     * @param  array<int, int|string>  $locationIds
     * @return array<int, int>
     */
    public static function idsForLocations(array $locationIds): array
    {
        return static::whereIn('id_location', $locationIds)
            ->pluck('id_inventory_location', 'id_location')
            ->all();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'id_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function vehicleLocation(): BelongsTo
    {
        return $this->belongsTo(VehicleLocation::class, 'id_vehicle_location', 'id_vehicle_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(InventoryLocationDetail::class, 'id_inventory_location', 'id_inventory_location');
    }
}
