<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gasto aduanal o seguro de un retaceo, de un tipo de gasto. Se ve a través de su retaceo, que
 * es el que se filtra por sucursal.
 */
class RetaceoExpense extends Model
{
    protected $table = 'retaceo_expenses';

    protected $primaryKey = 'id_retaceo_expense';

    protected $fillable = [
        'id_retaceo',
        'id_expense_type',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function retaceo(): BelongsTo
    {
        return $this->belongsTo(Retaceo::class, 'id_retaceo', 'id_retaceo')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class, 'id_expense_type', 'id_expense_type');
    }
}
