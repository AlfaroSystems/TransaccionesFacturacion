<?php

namespace App\Http\Controllers;

use App\Models\PurchaseQuotation;
use App\Models\PurchaseQuotationDetail;
use App\Models\PurchaseQuotationExpense;
use App\Models\PurchaseQuotationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseQuotationController extends Controller
{
    /**
     * Almacena una nueva cotización/oferta de proveedor.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_purchase_quotation_request' => 'required|exists:purchase_quotation_requests,id_purchase_quotation_request',
            'id_supplier'                   => 'required|exists:suppliers,id_supplier',
            'quotation_date'                => 'required|date',
            'valid_until'                   => 'nullable|date',
            'currency'                      => 'nullable|string|max:3',
            'payment_terms'                 => 'nullable|string|max:255',
            'delivery_days'                 => 'nullable|integer|min:0',
            'notes'                         => 'nullable|string',

            // Detalle de Ítems
            'items'                         => 'required|array|min:1',
            'items.*.id_product'            => 'required|exists:products,id',
            'items.*.quantity'             => 'required|numeric|min:0.0001',
            'items.*.id_unit'               => 'nullable|exists:units,id',
            'items.*.unit_price'            => 'required|numeric|min:0',
            'items.*.discount'              => 'nullable|numeric|min:0',
            'items.*.tax_rate'              => 'nullable|numeric|min:0',
            'items.*.delivery_days'          => 'nullable|integer|min:0',
            'items.*.available_quantity'    => 'nullable|numeric|min:0',
            'items.*.notes'                 => 'nullable|string',

            // Gastos Adicionales
            'expenses'                      => 'nullable|array',
            'expenses.*.id_expense_type'    => 'required_with:expenses|exists:expense_types,id_expense_type',
            'expenses.*.description'        => 'nullable|string|max:255',
            'expenses.*.amount'             => 'required_with:expenses|numeric|min:0',
        ]);

        $quotationRequest = PurchaseQuotationRequest::findOrFail($validated['id_purchase_quotation_request']);
        if ($quotationRequest->id_purchase_quotation) {
            return redirect()
                ->back()
                ->with('error', 'No se pueden registrar nuevas ofertas porque esta solicitud de cotización ya tiene una oferta aceptada.');
        }

        DB::transaction(function () use ($validated, $request) {
            $headerSubtotal = 0;
            $headerDiscount = 0;
            $headerTax = 0;
            $headerExpensesTotal = 0;

            // 1. Calcular subtotales, descuentos e impuestos de los productos
            $preparedDetails = [];
            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $discount = (float) ($item['discount'] ?? 0);
                $taxRate = (float) ($item['tax_rate'] ?? 0);

                $subtotal = $qty * $price;
                $baseForTax = max(0, $subtotal - $discount);
                $taxAmount = $baseForTax * ($taxRate / 100.0);
                $itemTotal = $baseForTax + $taxAmount;

                $headerSubtotal += $subtotal;
                $headerDiscount += $discount;
                $headerTax += $taxAmount;

                $preparedDetails[] = [
                    'id_product'         => $item['id_product'],
                    'quantity'           => $qty,
                    'id_unit'            => $item['id_unit'] ?? null,
                    'unit_price'         => $price,
                    'discount'           => $discount,
                    'subtotal'           => $subtotal,
                    'tax_rate'           => $taxRate,
                    'tax_amount'         => $taxAmount,
                    'total'              => $itemTotal,
                    'delivery_days'      => isset($item['delivery_days']) && $item['delivery_days'] !== '' ? (int) $item['delivery_days'] : null,
                    'available_quantity' => isset($item['available_quantity']) && $item['available_quantity'] !== '' ? (float) $item['available_quantity'] : $qty,
                    'notes'              => $item['notes'] ?? null,
                ];
            }

            // 2. Calcular gastos adicionales
            $preparedExpenses = [];
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $exp) {
                    $amount = (float) ($exp['amount'] ?? 0);
                    if ($amount <= 0) continue;

                    $headerExpensesTotal += $amount;
                    $preparedExpenses[] = [
                        'id_expense_type' => $exp['id_expense_type'],
                        'description'     => $exp['description'] ?? null,
                        'amount'          => $amount,
                    ];
                }
            }

            $grandTotal = ($headerSubtotal - $headerDiscount) + $headerTax + $headerExpensesTotal;

            // 3. Crear Encabezado de Cotización
            $quotation = PurchaseQuotation::create([
                'id_purchase_quotation_request' => $validated['id_purchase_quotation_request'],
                'id_supplier'                   => $validated['id_supplier'],
                'quotation_date'                => $validated['quotation_date'],
                'valid_until'                   => $validated['valid_until'] ?? null,
                'currency'                      => $validated['currency'] ?? 'USD',
                'payment_terms'                 => $validated['payment_terms'] ?? null,
                'delivery_days'                 => isset($validated['delivery_days']) && $validated['delivery_days'] !== '' ? (int) $validated['delivery_days'] : null,
                'subtotal'                      => $headerSubtotal,
                'discount'                      => $headerDiscount,
                'tax'                           => $headerTax,
                'total'                         => $grandTotal,
                'status'                        => 'submitted',
                'notes'                         => $validated['notes'] ?? null,
                'id_user'                       => auth()->id(),
            ]);

            // 4. Guardar Detalles de Ítems
            foreach ($preparedDetails as &$detail) {
                $detail['id_purchase_quotation'] = $quotation->id_purchase_quotation;
                PurchaseQuotationDetail::create($detail);
            }

            // 5. Guardar Gastos Adicionales
            foreach ($preparedExpenses as &$expense) {
                $expense['id_purchase_quotation'] = $quotation->id_purchase_quotation;
                PurchaseQuotationExpense::create($expense);
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Oferta de proveedor registrada exitosamente.');
    }

    /**
     * Elimina una oferta de proveedor.
     */
    public function destroy(int $id)
    {
        $quotation = PurchaseQuotation::findOrFail($id);
        $quotation->delete();

        return redirect()
            ->back()
            ->with('success', 'Oferta de proveedor eliminada correctamente.');
    }
}
