<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'purchases';
    protected $primaryKey = 'id_purchase';

    protected $fillable = [
        'uuid',
        'purchase_code',
        'id_purchase_order',
        'id_supplier',
        'id_branch',
        'id_warehouse',
        'purchase_date',
        'supplier_invoice_number',
        'supplier_invoice_date',
        'currency',
        'subtotal',
        'discount',
        'tax',
        'total',
        'status',
        'notes',
        'id_user',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'supplier_invoice_date' => 'date',
        'subtotal' => 'decimal:4',
        'discount' => 'decimal:4',
        'tax' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function ($purchase) {
            if (!$purchase->uuid) {
                $purchase->uuid = (string) Str::uuid();
            }
            if (!$purchase->purchase_code) {
                $year = now()->year;
                $last = self::whereYear('created_at', $year)
                    ->orderByDesc('id_purchase')
                    ->first();
                $number = $last
                    ? ((int) substr($last->purchase_code, -4)) + 1
                    : 1;
                $purchase->purchase_code =
                    'CMP-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrder::class,
            'id_purchase_order',
            'id_purchase_order'
        );
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'id_branch', 'id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(
            PurchaseDetail::class,
            'id_purchase',
            'id_purchase'
        );
    }

    public function retaceos(): HasMany
    {
        return $this->hasMany(
            Retaceo::class,
            'id_purchase',
            'id_purchase'
        );
    }
}
