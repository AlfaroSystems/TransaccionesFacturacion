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

class Purchase extends Model
{
    use HasFactory, BelongsToBranch;

    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->where($this->qualifyColumn('id_branch'), $branchId);
    }

    /**
     * Estados en los que la mercadería ya se recibió (y se puede calcular su retaceo).
     */
    public const RECEIVED_STATUSES = ['received', 'completed'];

    public const STATUS_LABELS = [
        'draft'     => 'Borrador',
        'received'  => 'Recibida',
        'completed' => 'Completada',
        'cancelled' => 'Anulada',
    ];

    /**
     * Compras recibidas sin un retaceo activo (no cancelado): las que admiten un retaceo nuevo.
     */
    public function scopeAvailableForRetaceo(Builder $query): void
    {
        $query->whereIn($this->qualifyColumn('status'), self::RECEIVED_STATUSES)
            ->whereDoesntHave('retaceos', fn ($q) => $q->where('status', '!=', 'cancelled'));
    }

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
                // El correlativo es global: lo comparten las compras de todas las sucursales
                $purchase->purchase_code = DocumentSequence::next('CMP-' . now()->year . '-', 'purchases', 'purchase_code');
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
