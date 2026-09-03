<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PurchaseRequest extends Model
{
    use HasFactory;
    protected $table = 'purchase_requests';
    protected $primaryKey = 'id_purchase_request';
    protected $fillable = [
        'uuid',
        'purchase_request_code',
        'id_branch',
        'id_warehouse',
        'id_user',
        'request_date',
        'required_date',
        'justification',
        'status',
        'notes',
    ];
    protected $casts = [
        'request_date' => 'datetime',
        'required_date' => 'datetime',
    ];
    /**
     * Usuario que creó la solicitud.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    /**
     * Sucursal de la solicitud.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'id_branch');
    }
    /**
     * Bodega de la solicitud.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }
    /**
     * Productos/detalles de la solicitud.
     */
    public function details(): HasMany
    {
        return $this->hasMany(
            PurchaseRequestDetail::class,
            'id_purchase_request',
            'id_purchase_request'
        );
    }

    /**
     * Solicitudes de cotización vinculadas a esta solicitud de compra.
     */
    public function quotationRequests(): HasMany
    {
        return $this->hasMany(
            PurchaseQuotationRequest::class,
            'id_purchase_request',
            'id_purchase_request'
        );
    }
}