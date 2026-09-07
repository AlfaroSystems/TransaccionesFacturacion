<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierQuotation extends Model
{
    use HasFactory;

    protected $table = 'supplier_quotations'; // O el nombre real de tu tabla
    protected $primaryKey = 'id'; // O 'id_supplier_quotation'

    protected $fillable = [
        'purchase_quotation_request_id',
        'supplier_id',
        'unit_price',
        'taxes',
        'additional_expenses',
        'total',
        'conditions',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id'); // Ajusta las llaves foráneas si varían
    }

    public function quotationRequest()
    {
        return $this->belongsTo(PurchaseQuotationRequest::class, 'purchase_quotation_request_id', 'id');
    }
}