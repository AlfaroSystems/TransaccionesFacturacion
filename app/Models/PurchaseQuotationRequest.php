<?php

namespace App\Models;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuotationRequest extends Model
{
    use HasFactory, BelongsToBranch;

    // Pertenece a la sucursal de su solicitud de compra
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereHas('purchaseRequest');
    }

    const UPDATED_AT = null;

    protected $table = 'purchase_quotation_requests';
    protected $primaryKey = 'id_purchase_quotation_request';

    protected $fillable = [
        'id_purchase_quotation',
        'id_purchase_request',
    ];

    /**
     * Solicitud de compra base.
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
     * Cotizaciones recibidas de proveedores para esta solicitud.
     */
    public function supplierQuotations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            PurchaseQuotation::class,
            'id_purchase_quotation_request',
            'id_purchase_quotation_request'
        );
    }
}
