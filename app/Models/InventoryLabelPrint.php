<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Impresión de etiquetas de un lote. La primera no lleva motivo; cada reimpresión, sí.
 */
class InventoryLabelPrint extends Model
{
    public $timestamps = false;

    protected $table = 'inventory_label_prints';

    protected $primaryKey = 'id_inventory_label_print';

    protected $fillable = [
        'id_inventory_lot',
        'quantity_printed',
        'print_date',
        'id_user',
        'reprint_reason',
    ];

    protected $casts = [
        'print_date' => 'datetime',
        'quantity_printed' => 'integer',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'id_inventory_lot', 'id_inventory_lot')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
