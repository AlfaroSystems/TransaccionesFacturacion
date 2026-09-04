<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuotationRequest extends Model
{
    use HasFactory;

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
}
