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

class Retaceo extends Model
{
    use HasFactory, BelongsToBranch;

    // Pertenece a la sucursal de su compra
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereHas('purchase');
    }

    public const STATUS_LABELS = [
        'draft'      => 'Borrador',
        'calculated' => 'Liquidado',
        'applied'    => 'Aplicado',
        'cancelled'  => 'Cancelado',
    ];

    protected $table = 'retaceos';
    protected $primaryKey = 'id_retaceo';

    protected $fillable = [
        'uuid',
        'retaceo_code',
        'id_supplier',
        'id_purchase',
        'retaceo_date',
        'origin_country',
        'import_invoice_number',
        'import_invoice_date',
        'import_policy_number',
        'import_policy_date',
        'total_fob',
        'total_freight',
        'total_expenses',
        'total_dai',
        'total_cost',
        'status',
        'notes',
        'id_user',
    ];

    protected $casts = [
        'retaceo_date'        => 'datetime',
        'import_invoice_date' => 'date',
        'import_policy_date'  => 'date',
        'total_fob'           => 'decimal:4',
        'total_freight'       => 'decimal:4',
        'total_expenses'      => 'decimal:4',
        'total_dai'           => 'decimal:4',
        'total_cost'          => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function ($retaceo) {
            if (!$retaceo->uuid) {
                $retaceo->uuid = (string) Str::uuid();
            }
            if (!$retaceo->retaceo_code) {
                // El correlativo es global: lo comparten los retaceos de todas las sucursales
                $retaceo->retaceo_code = DocumentSequence::next('RET-' . now()->year . '-', 'retaceos', 'retaceo_code');
            }
        });
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(
            Purchase::class,
            'id_purchase',
            'id_purchase'
        );
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class,
            'id_supplier',
            'id_supplier'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_user'
        );
    }

    public function details(): HasMany
    {
        return $this->hasMany(
            RetaceoDetail::class,
            'id_retaceo',
            'id_retaceo'
        );
    }
}
