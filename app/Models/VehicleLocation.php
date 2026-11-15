<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Espacio de carga de un vehículo, asignado a una bodega. Como las ubicaciones de bodega, cada
 * uno tiene su ubicación de inventario, que se crea con él.
 */
class VehicleLocation extends Model
{
    use BelongsToBranch;

    // Los de las bodegas de la sucursal del usuario, o de toda su empresa para el departamento
    // de compras (que es el que despacha los traslados)
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $branches = BranchAccess::isPurchasingDepartment() ? BranchAccess::companyBranchIds() : [$branchId];

        $query->whereIn(
            $this->qualifyColumn('id_warehouse'),
            Warehouse::queryAllBranches()->whereIn('id_branch', $branches)->select('id_warehouse')
        );
    }

    const UPDATED_AT = null;

    protected $table = 'vehicle_locations';

    protected $primaryKey = 'id_vehicle_location';

    protected $fillable = [
        'id_vehicle',
        'id_warehouse',
        'code',
        'capacity',
        'notes',
        'is_active',
    ];

    protected $attributes = [
        'capacity' => 0,
        'is_active' => true,
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(fn (VehicleLocation $location) => InventoryLocation::create([
            'id_vehicle_location' => $location->id_vehicle_location,
            'location_type' => InventoryLocation::TYPE_VEHICLE,
            'is_active' => $location->is_active,
        ]));

        static::updated(function (VehicleLocation $location) {
            if ($location->wasChanged('is_active')) {
                InventoryLocation::where('id_vehicle_location', $location->id_vehicle_location)
                    ->get()
                    ->each->update(['is_active' => $location->is_active]);
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'id_vehicle', 'id_vehicle');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse', 'id_warehouse');
    }

    public function warehouseAllBranches(): BelongsTo
    {
        return $this->warehouse()->withoutGlobalScope(BranchScope::class);
    }

    public function inventoryLocation(): HasOne
    {
        return $this->hasOne(InventoryLocation::class, 'id_vehicle_location', 'id_vehicle_location');
    }
}
