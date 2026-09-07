<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderExpense extends Model
{
    protected $table = 'purchase_order_expenses';
    protected $primaryKey = 'id_purchase_order_expense';
    protected $fillable = [
        'id_purchase_order',
        'id_expense_type',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrder::class,
            'id_purchase_order',
            'id_purchase_order'
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