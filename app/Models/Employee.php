<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Empleado de una sucursal: motorista de los traslados o responsable de un vehículo. No es un
 * usuario del sistema.
 */
class Employee extends Model
{
    use BelongsToBranch;

    // Los de la sucursal del usuario, o los de toda su empresa para el departamento de compras
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $branches = BranchAccess::isPurchasingDepartment() ? BranchAccess::companyBranchIds() : [$branchId];

        $query->whereIn($this->qualifyColumn('id_branch'), $branches);
    }

    const UPDATED_AT = null;

    protected $table = 'employees';

    protected $primaryKey = 'id_employee';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'dui',
        'position',
        'id_branch',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'id_branch', 'id_branch')
            ->withoutGlobalScope(BranchScope::class);
    }

    /**
     * Vehículos de los que es responsable.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'id_responsible_employee', 'id_employee');
    }

    /**
     * Traslados que manejó.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'id_driver_employee', 'id_employee');
    }
}
