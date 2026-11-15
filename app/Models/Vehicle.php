<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vehículo de la empresa. Es un catálogo, como los proveedores: lo ven todas las sucursales.
 * Sus espacios de carga (vehicle_locations) son ubicaciones de inventario.
 */
class Vehicle extends Model
{
    const UPDATED_AT = null;

    protected $table = 'vehicles';

    protected $primaryKey = 'id_vehicle';

    protected $fillable = [
        'brand',
        'model',
        'year',
        'license_plate',
        'id_responsible_employee',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Placa, marca y modelo: "P123-456 · Isuzu NPR".
     */
    public function label(): string
    {
        return $this->license_plate.' · '.trim($this->brand.' '.$this->model);
    }

    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_responsible_employee', 'id_employee')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(VehicleLocation::class, 'id_vehicle', 'id_vehicle');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'id_vehicle', 'id_vehicle');
    }
}
