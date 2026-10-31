<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\RefreshesWithLock;
use App\Support\DocumentSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Retaceo extends Model
{
    use BelongsToBranch, HasFactory, RefreshesWithLock;

    // Pertenece a la sucursal de su compra
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereHas('purchase');
    }

    public const STATUS_LABELS = [
        'draft' => 'Borrador',
        'calculated' => 'Liquidado',
        'applied' => 'Aplicado',
        'cancelled' => 'Cancelado',
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
        'retaceo_date' => 'datetime',
        'import_invoice_date' => 'date',
        'import_policy_date' => 'date',
        'total_fob' => 'decimal:4',
        'total_freight' => 'decimal:4',
        'total_expenses' => 'decimal:4',
        'total_dai' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function ($retaceo) {
            if (! $retaceo->uuid) {
                $retaceo->uuid = (string) Str::uuid();
            }
            if (! $retaceo->retaceo_code) {
                // El correlativo es global: lo comparten los retaceos de todas las sucursales
                $retaceo->retaceo_code = DocumentSequence::next('RET-'.now()->year.'-', 'retaceos', 'retaceo_code');
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

    /**
     * Otros gastos aduanales / seguro, uno por tipo de gasto; total_expenses es su suma.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(RetaceoExpense::class, 'id_retaceo', 'id_retaceo');
    }

    /**
     * Las líneas de gasto para el formulario. Un retaceo de antes de las líneas solo tiene el
     * total: se ofrece como una línea sin tipo, que hay que elegir al guardarlo.
     *
     * @return array<int, array{id_expense_type: ?int, description: ?string, amount: float}>
     */
    public function lineasDeGastos(): array
    {
        $lineas = $this->expenses()->orderBy('id_retaceo_expense')->get()
            ->map(fn (RetaceoExpense $g) => [
                'id_expense_type' => $g->id_expense_type,
                'description' => $g->description,
                'amount' => (float) $g->amount,
            ])
            ->all();

        if (! $lineas && (float) $this->total_expenses > 0) {
            $lineas[] = ['id_expense_type' => null, 'description' => 'Otros gastos', 'amount' => (float) $this->total_expenses];
        }

        return $lineas;
    }
}
