<?php

namespace App\Models;

use App\Support\DocumentSequence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cliente del módulo de Ventas. Catálogo general: no se elimina físicamente, se desactiva.
 * El código (CLI-000001) se genera solo al crear si no se indica uno.
 */
class Customer extends Model
{
    use HasFactory;

    public const TYPE_FINAL = 'final';

    public const TYPE_TAXPAYER = 'taxpayer';

    public const TYPE_LABELS = [
        self::TYPE_FINAL => 'Consumidor final',
        self::TYPE_TAXPAYER => 'Contribuyente',
    ];

    protected $table = 'customers';

    protected $primaryKey = 'id_customer';

    protected $fillable = [
        'code',
        'name',
        'customer_type',
        'document_type',
        'document_number',
        'nrc',
        'commercial_line',
        'phone',
        'email',
        'address',
        'id_department',
        'id_municipality',
        'id_district',
        'credit_limit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->code)) {
                $customer->code = DocumentSequence::next('CLI-', 'customers', 'code', 6);
            }
        });
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->customer_type] ?? $this->customer_type;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'id_department');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class, 'id_municipality');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'id_district');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
