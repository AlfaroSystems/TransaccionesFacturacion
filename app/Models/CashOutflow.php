<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Egreso (retiro) de efectivo de un turno de caja.
 */
class CashOutflow extends Model
{
    const UPDATED_AT = null;

    protected $table = 'cash_outflows';

    protected $primaryKey = 'id_cash_outflow';

    protected $fillable = [
        'id_cash_shift',
        'amount',
        'concept',
        'id_user',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'id_cash_shift', 'id_cash_shift');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
