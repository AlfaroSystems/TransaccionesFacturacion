<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseQuotationRequest extends Model
{
    use HasFactory;

    protected $table = 'purchase_quotation_requests';
    protected $primaryKey = 'id_purchase_quotation_request';

    protected $fillable = [
        'id_purchase_request',
        'id_supplier',
        'id_purchase_quotation',
        'status',
        'notes',
    ];

    /**
     * Solicitud de compra base en estado approved.
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseRequest::class,
            'id_purchase_request',
            'id_purchase_request'
        );
    }

    /**
     * Proveedor convocado a cotizar.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class,
            'id_supplier',
            'id_supplier'
        );
    }

    /**
     * Ítems y cantidades requeridas a cotizar.
     */
    public function details(): HasMany
    {
        return $this->hasMany(
            PurchaseQuotationRequestDetail::class,
            'id_purchase_quotation_request',
            'id_purchase_quotation_request'
        );
    }

    /**
     * Estado legible con color y etiqueta para la UI.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending' => [
                'label' => 'Pendiente',
                'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-300 dark:border-amber-700',
            ],
            'sent' => [
                'label' => 'Enviada',
                'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-300 dark:border-blue-700',
            ],
            'quoted' => [
                'label' => 'Cotizada',
                'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
            ],
            'cancelled' => [
                'label' => 'Cancelada',
                'class' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border-rose-300 dark:border-rose-700',
            ],
            default => [
                'label' => ucfirst($this->status),
                'class' => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
            ],
        };
    }
}
