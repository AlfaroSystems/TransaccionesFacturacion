<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PurchaseQuotation extends Model
{
    use HasFactory;

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
        $year = date('Y');
        $prefix = "COT-{$year}-";

        $lastQuotation = static::where('purchase_quotation_code', 'like', "{$prefix}%")
            ->orderByDesc('id_purchase_quotation')
            ->first();

        if ($lastQuotation) {
            $lastNum = (int) substr($lastQuotation->purchase_quotation_code, -4);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
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
        return $this->belongsTo(User::class, 'id_user', 'id');
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
