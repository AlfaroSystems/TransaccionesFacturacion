<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuotationExpense extends Model
{
    use HasFactory;

    protected $table = 'purchase_quotation_expenses';
    protected $primaryKey = 'id_purchase_quotation_expense';

    protected $fillable = [
        'id_purchase_quotation',
        'id_expense_type',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseQuotation::class,
            'id_purchase_quotation',
            'id_purchase_quotation'
        );
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(
            ExpenseType::class,
            'id_expense_type',
            'id_expense_type'
        );
    }
}
