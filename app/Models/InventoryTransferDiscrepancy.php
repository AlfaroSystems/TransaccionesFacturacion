<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Diferencia de una línea de traslado al recibirlo: faltante (no llegó), daño (llegó dañado y se
 * da de baja) o sobrante (llegó de más y entra a la bodega destino). Se registra al recibir y se
 * aplica al inventario al regularizar (regularized_at). Se ve a través de su traslado.
 */
class InventoryTransferDiscrepancy extends Model
{
    public const TYPE_SHORTAGE = 'shortage';

    public const TYPE_DAMAGE = 'damage';

    public const TYPE_SURPLUS = 'surplus';

    public const TYPE_LABELS = [
        self::TYPE_SHORTAGE => 'Faltante',
        self::TYPE_DAMAGE => 'Daño',
        self::TYPE_SURPLUS => 'Sobrante',
    ];

    const UPDATED_AT = null;

    protected $table = 'inventory_transfer_discrepancies';

    protected $primaryKey = 'id_inventory_transfer_discrepancy';

    protected $fillable = [
        'id_inventory_transfer_detail',
        'discrepancy_type',
        'quantity',
        'id_destination_inventory_location',
        'notes',
        'id_user',
        'regularized_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'regularized_at' => 'datetime',
    ];

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->discrepancy_type] ?? $this->discrepancy_type;
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(InventoryTransferDetail::class, 'id_inventory_transfer_detail', 'id_inventory_transfer_detail');
    }

    /**
     * Ubicación de bodega donde entra un sobrante.
     */
    public function destinationLocation(): HasOneThrough
    {
        return $this->hasOneThrough(Location::class, InventoryLocation::class, 'id_inventory_location', 'id_location', 'id_destination_inventory_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
