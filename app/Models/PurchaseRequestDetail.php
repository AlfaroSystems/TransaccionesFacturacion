<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PurchaseRequestDetail extends Model
{
    use HasFactory;
    protected $table = 'purchase_request_details';
    protected $primaryKey = 'id_purchase_request_detail';
    protected $fillable = [
        'id_purchase_request',
        'id_product',
        'quantity',
        'id_unit',
        'description',
        'notes',
    ];
    protected $casts = [
        'quantity' => 'decimal:4',
    ];
    /**
     * Solicitud de compra a la que pertenece.
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
     * Producto solicitado.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'id_product'
        );
    }
    /**
     * Unidad de medida.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(
            Unit::class,
            'id_unit'
        );
    }

    /**
     * Detalles de solicitudes de cotización vinculados a este ítem.
     */
    public function quotationRequestDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            PurchaseQuotationRequestDetail::class,
            'id_purchase_request_detail',
            'id_purchase_request_detail'
        );
    }
}