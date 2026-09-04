<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuotationRequestDetail extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $table = 'purchase_quotation_request_details';
    protected $primaryKey = 'id_purchase_quotation_request_detail';

    protected $fillable = [
        'id_purchase_quotation_detail',
        'id_purchase_request_detail',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    /**
     * Detalle de la solicitud de compra asociada.
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
