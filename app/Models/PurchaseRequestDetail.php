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
}