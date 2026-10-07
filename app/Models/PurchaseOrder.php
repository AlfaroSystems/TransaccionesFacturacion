<?php

namespace App\Models;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use BelongsToBranch;

    /**
     * Recibida parcial y Completada se calculan según lo recibido; Cerrada es una orden
     * que se dio por terminada a mano porque el proveedor no entregará el resto.
     */
    public const STATUS_LABELS = [
        'draft'            => 'Borrador',
        'issued'           => 'Emitida',
        'partial_received' => 'Recibida parcial',
        'completed'        => 'Completada',
        'closed'           => 'Cerrada',
        'cancelled'        => 'Cancelada',
    ];

    public function restrictToBranch(Builder $query, int $branchId): void
    {
        // El departamento de compras emite y gestiona las órdenes de todas las sucursales
        // de su empresa (cada orden va a la sucursal que pidió los productos)
        if (BranchAccess::isPurchasingDepartment()) {
            $query->whereIn($this->qualifyColumn('id_branch'), BranchAccess::companyBranchIds());

            return;
        }

        $query->where($this->qualifyColumn('id_branch'), $branchId);
    }

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
                // El correlativo es global: debe considerar las órdenes de todas las sucursales
                $last = self::queryAllBranches()
                    ->whereYear('created_at', $year)
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
        // Sin el filtro por sucursal: quien ve la orden (p. ej. compras) ve su destino
        return $this->belongsTo(Branch::class, 'id_branch')->withoutGlobalScope(BranchScope::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse')->withoutGlobalScope(BranchScope::class);
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
    public function purchases(): HasMany
    {
        return $this->hasMany(
            Purchase::class,
            'id_purchase_order',
            'id_purchase_order'
        );
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }
}