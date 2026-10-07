<?php

namespace App\Models;
use App\Models\Concerns\BelongsToBranch;
use App\Support\DocumentSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PurchaseQuotation extends Model
{
    use HasFactory, BelongsToBranch;

    // Pertenece a la sucursal de su solicitud de cotización
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereHas('quotationRequest');
    }

    protected $table = 'purchase_quotations';
    protected $primaryKey = 'id_purchase_quotation';

    protected $fillable = [
        'uuid',
        'purchase_quotation_code',
        'id_purchase_quotation_request',
        'id_supplier',
        'quotation_date',
        'valid_until',
        'currency',
        'payment_terms',
        'delivery_days',
        'subtotal',
        'discount',
        'tax',
        'total',
        'status',
        'notes',
        'id_user',
    ];

    protected $casts = [
        'quotation_date' => 'datetime',
        'valid_until' => 'datetime',
        'subtotal' => 'decimal:4',
        'discount' => 'decimal:4',
        'tax' => 'decimal:4',
        'total' => 'decimal:4',
        'delivery_days' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseQuotation $quotation) {
            if (empty($quotation->uuid)) {
                $quotation->uuid = (string) Str::uuid();
            }

            if (empty($quotation->purchase_quotation_code)) {
                $quotation->purchase_quotation_code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        // El correlativo es global: lo comparten las cotizaciones de todas las sucursales
        return DocumentSequence::next('COT-' . now()->year . '-', 'purchase_quotations', 'purchase_quotation_code');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function quotationRequest(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseQuotationRequest::class,
            'id_purchase_quotation_request',
            'id_purchase_quotation_request'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function details(): HasMany
    {
        return $this->hasMany(
            PurchaseQuotationDetail::class,
            'id_purchase_quotation',
            'id_purchase_quotation'
        );
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(
            PurchaseQuotationExpense::class,
            'id_purchase_quotation',
            'id_purchase_quotation'
        );
    }
}
