<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';
    protected $primaryKey = 'id_purchase_order';
    protected $fillable = [
        'uuid',
        'purchase_order_code',
        'id_supplier',
        'id_branch',
        'id_warehouse',
        'id_purchase_quotation',
        'id_user',
        'order_date',
        'expected_date',
        'currency',
        'payment_terms',
        'subtotal',
        'discount',
        'tax',
        'additional_expenses',
        'total',
        'status',
        'notes',
    ];
    protected $casts = [
        'order_date' => 'datetime',
        'expected_date' => 'datetime',
        'subtotal' => 'decimal:4',
        'discount' => 'decimal:4',
        'tax' => 'decimal:4',
        'additional_expenses' => 'decimal:4',
        'total' => 'decimal:4',
    ];
    protected static function booted(): void
    {
        static::creating(function ($order) {
            if (!$order->uuid) {
                $order->uuid = (string) Str::uuid();
            }
            if (!$order->purchase_order_code) {
                $year = now()->year;
                $last = self::whereYear('created_at', $year)
                    ->orderByDesc('id_purchase_order')
                    ->first();
                $number = $last
                    ? ((int) substr($last->purchase_order_code, -4)) + 1
                    : 1;
                $order->purchase_order_code =
                    'OC-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'id_branch');
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseQuotation::class,
            'id_purchase_quotation',
            'id_purchase_quotation'
        );
    }
    public function details(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderDetail::class,
            'id_purchase_order',
            'id_purchase_order'
        );
    }
    public function expenses(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderExpense::class,
            'id_purchase_order',
            'id_purchase_order'
        );
    }
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }
}