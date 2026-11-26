<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Support\BranchAccess;
use App\Support\DocumentSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Venta desde una sucursal. Al entregar (status delivered) se descuenta el inventario de la
 * sucursal con PEPS y se registra la salida en el Kardex (ver SaleService).
 */
class Sale extends Model
{
    use BelongsToBranch;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_CONFIRMED => 'Confirmada',
        self::STATUS_DELIVERED => 'Entregada',
        self::STATUS_CANCELLED => 'Anulada',
    ];

    public const DOCUMENT_LABELS = [
        'invoice' => 'Factura de consumidor final',
        'ccf' => 'Comprobante de Crédito Fiscal',
    ];

    public const PAYMENT_LABELS = [
        'cash' => 'Efectivo',
        'credit_card' => 'Tarjeta de crédito',
        'check' => 'Cheque',
    ];

    // Las ventas de las sucursales visibles: la del usuario, o las de toda su empresa para
    // el departamento de compras / casa matriz
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $branches = BranchAccess::isPurchasingDepartment() ? BranchAccess::companyBranchIds() : [$branchId];

        $query->whereIn($this->qualifyColumn('id_branch'), $branches);
    }

    protected $table = 'sales';

    protected $primaryKey = 'id_sale';

    protected $fillable = [
        'uuid',
        'sale_code',
        'id_branch',
        'id_cash_shift',
        'id_customer',
        'receptor_business_name',
        'receptor_document_type',
        'receptor_document_number',
        'receptor_nrc',
        'receptor_line',
        'receptor_address',
        'id_user',
        'sale_date',
        'document_type',
        'fiscal_document_number',
        'payment_method',
        'payment_reference',
        'payment_bank',
        'payment_document_number',
        'currency',
        'subtotal',
        'discount',
        'tax',
        'total',
        'total_cost',
        'status',
        'delivered_at',
        'pdf_path',
        'emailed_at',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'delivered_at' => 'datetime',
        'emailed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'total_cost' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            if (empty($sale->uuid)) {
                $sale->uuid = (string) Str::uuid();
            }
            if (empty($sale->sale_code)) {
                $sale->sale_code = DocumentSequence::next('VEN-'.now()->year.'-', 'sales', 'sale_code', 6);
            }
        });
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function documentLabel(): string
    {
        return self::DOCUMENT_LABELS[$this->document_type] ?? $this->document_type;
    }

    public function paymentLabel(): string
    {
        return self::PAYMENT_LABELS[$this->payment_method] ?? $this->payment_method;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'id_branch', 'id_branch');
    }

    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'id_cash_shift', 'id_cash_shift');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class, 'id_sale', 'id_sale');
    }
}
