<?php

namespace App\Http\Controllers;
use App\Models\PurchaseQuotationRequest;
use App\Models\SupplierQuotation;
use App\Rules\Accessible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupplierQuotationController extends Controller
{
    public function store(Request $request)
    {
        Gate::authorize('purchase_quotations.crear');

        $request->validate([
            'purchase_quotation_request_id' => ['required', new Accessible(PurchaseQuotationRequest::class)],
            'supplier_id' => 'required|exists:suppliers,id_supplier',
            'unit_price' => 'required|numeric|min:0',
        'taxes' => 'nullable|numeric|min:0',
        'additional_expenses' => 'nullable|numeric|min:0',
        'conditions' => 'nullable|string',
    ]);

    // Cálculo automático del total si lo manejas en base de datos
    $unitPrice = $request->input('unit_price');
    $taxes = $request->input('taxes', 0);
    $additionalExpenses = $request->input('additional_expenses', 0);
    
    // O si compras más de 1 unidad, multiplicarías por la cantidad solicitada, 
    // pero asumiendo precio unitario + impuestos + gastos:
    $total = $unitPrice + $taxes + $additionalExpenses;

    SupplierQuotation::create([
        'purchase_quotation_request_id' => $request->purchase_quotation_request_id,
        'supplier_id' => $request->supplier_id,
        'unit_price' => $unitPrice,
        'taxes' => $taxes,
        'additional_expenses' => $additionalExpenses,
        'total' => $total,
        'conditions' => $request->conditions,
    ]);

    return redirect()->back()->with('success', '¡Oferta de proveedor registrada con éxito!');
}

public function destroy($id)
{
    Gate::authorize('purchase_quotations.eliminar');

    $quotation = SupplierQuotation::findOrFail($id);
    $quotation->delete();

    return redirect()->back()->with('success', 'Oferta eliminada correctamente.');
}
}