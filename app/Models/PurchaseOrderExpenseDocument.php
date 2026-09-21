<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderExpenseDocument extends Model
{
    protected $table = 'purchase_order_expense_documents';
    protected $primaryKey = 'id_purchase_order_expense_document';

    protected $fillable = [
        'id_purchase_order_expense',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function purchaseOrderExpense(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrderExpense::class,
            'id_purchase_order_expense',
            'id_purchase_order_expense'
        );
    }
}
