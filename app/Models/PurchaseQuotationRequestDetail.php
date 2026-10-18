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
        'id_purchase_quotation_request',
        'id_purchase_quotation_detail',
        'id_purchase_request_detail',
        'id_product',
        'id_unit',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    /**
     * Clave de la línea que se cotiza: las líneas del mismo producto y unidad se cotizan juntas.
     */
    public function lineKey(): string
    {
        return $this->id_product.'-'.$this->id_unit;
    }

    /**
     * Producto que se cotiza.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    /**
     * Unidad en que se cotiza.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'id_unit', 'id_unit');
    }

    /**
     * Solicitud de cotización a la que pertenece la línea.
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
     * Línea de la oferta de proveedor adjudicada (null mientras no se adjudica).
     */
    public function quotationDetail(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseQuotationDetail::class,
            'id_purchase_quotation_detail',
            'id_purchase_quotation_detail'
        );
    }

    /**
     * Detalle de la solicitud de compra asociada (null en los productos que el
     * departamento de compras agrega sin solicitud de compra).
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
