<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuotationRequestDetail extends Model
{
    use HasFactory;

    protected $table = 'purchase_quotation_request_details';
    protected $primaryKey = 'id_purchase_quotation_request_detail';

    protected $fillable = [
        'id_purchase_quotation_request',
        'id_purchase_request_detail',
        'id_purchase_quotation_detail',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    /**
     * Invitación a cotizar a la que pertenece este detalle.
     */
    public function quotationRequest(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseQuotationRequest::class,
            'id_purchase_quotation_request',
            'id_purchase_quotation_request'
        );
    }

    /**
     * Detalle original de la solicitud de compra.
     */
    public function purchaseRequestDetail(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseRequestDetail::class,
            'id_purchase_request_detail',
            'id_purchase_request_detail'
        );
    }
}
