<?php
namespace App\Http\Controllers;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderExpense;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ExpenseType;
use App\Models\PurchaseQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class PurchaseOrderController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::with([
            'supplier',
            'branch',
            'warehouse',
            'user'
        ])
        ->orderByDesc('id_purchase_order')
        ->paginate(10);

        return view('purchase_orders.index', compact('orders'));
    }
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $expenseTypes = ExpenseType::orderBy('name')->get();
        $quotations = PurchaseQuotation::whereIn('status', [
            'approved',
            'aprobada'
        ])
        ->orderByDesc('id_purchase_quotation')
        ->get();
        return view('purchase_orders.create', compact(
            'suppliers',
            'branches',
            'warehouses',
            'products',
            'units',
            'expenseTypes',
            'quotations'
        ));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_supplier' => ['required', 'exists:suppliers,id'],
            'id_branch' => ['required', 'exists:branches,id'],
            'id_warehouse' => ['required', 'exists:warehouses,id'],
            'id_purchase_quotation' => [
                'nullable',
                'exists:purchase_quotations,id_purchase_quotation'
            ],
            'order_date' => ['required', 'date'],
            'expected_date' => ['required', 'date', 'after_or_equal:order_date'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_terms' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id_product' => [
                'required',
                'exists:products,id'
            ],
            'products.*.quantity' => [
                'required',
                'numeric',
                'min:0.0001'
            ],
            'products.*.id_unit' => [
                'required',
                'exists:units,id'
            ],
            'products.*.unit_price' => [
                'required',
                'numeric',
                'min:0'
            ],
             'products.*.discount' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'products.*.tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100'
            ],
            'products.*.notes' => [
                'nullable',
                'string'
            ],
            'expenses' => ['nullable', 'array'],
            'expenses.*.id_expense_type' => [
                'required',
                'exists:expense_types,id_expense_type'
            ],
            'expenses.*.description' => [
                'required',
                'string',
                'max:255'
            ],
            'expenses.*.amount' => [
                'required',
                'numeric',
                'min:0'
            ],
        ]);
        DB::transaction(function () use ($validated, $request) {

            $subtotal = 0;
            $discount = 0;
            $tax = 0;
            $additionalExpenses = 0;
            foreach ($validated['products'] as $product) {
                $lineSubtotal =
                    ((float) $product['quantity']) *
                    ((float) $product['unit_price']);
                $lineDiscount = (float) ($product['discount'] ?? 0);
                $base = max(0, $lineSubtotal - $lineDiscount);
                $taxRate = (float) ($product['tax_rate'] ?? 0);
                $taxAmount = $base * ($taxRate / 100);
                $lineTotal = $base + $taxAmount;
                $subtotal += $lineSubtotal;
                $discount += $lineDiscount;
                $tax += $taxAmount;
            }
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $expense) {
                    $additionalExpenses += (float) $expense['amount'];
                }
            }
            $total =
                $subtotal -
                $discount +
                $tax +
                $additionalExpenses;
            $order = PurchaseOrder::create([
                'id_supplier' => $validated['id_supplier'],
                'id_branch' => $validated['id_branch'],
                'id_warehouse' => $validated['id_warehouse'],
                'id_purchase_quotation' =>
                    $validated['id_purchase_quotation'] ?? null,
                'id_user' => Auth::id(),
                'order_date' => $validated['order_date'],
                'expected_date' => $validated['expected_date'],
                'currency' => strtoupper($validated['currency']),
                'payment_terms' => $validated['payment_terms'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'additional_expenses' => $additionalExpenses,
                'total' => $total,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);
            foreach ($validated['products'] as $product) {
                $lineSubtotal =
                    ((float) $product['quantity']) *
                    ((float) $product['unit_price']);
                $lineDiscount = (float) ($product['discount'] ?? 0);
                $base = max(0, $lineSubtotal - $lineDiscount);
                $taxRate = (float) ($product['tax_rate'] ?? 0);
                $taxAmount = $base * ($taxRate / 100);
                $lineTotal = $base + $taxAmount;
                PurchaseOrderDetail::create([
                    'id_purchase_order' => $order->id_purchase_order,
                    'id_product' => $product['id_product'],
                    'quantity' => $product['quantity'],
                    'id_unit' => $product['id_unit'],
                    'unit_price' => $product['unit_price'],
                    'discount' => $lineDiscount,
                    'subtotal' => $lineSubtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total' => $lineTotal,
                    'notes' => $product['notes'] ?? null,
                ]);
            }
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $expense) {
                    PurchaseOrderExpense::create([
                        'id_purchase_order' =>
                            $order->id_purchase_order,
                        'id_expense_type' =>
                            $expense['id_expense_type'],
                        'description' =>
                            $expense['description'],
                        'amount' =>
                            $expense['amount'],
                    ]);
                }
            }
        });
        return redirect()
            ->route('purchase_orders.index')
            ->with('success', 'Orden de compra creada correctamente.');
    }
    public function show(PurchaseOrder $purchase_order)
    {
        $purchase_order->load([
            'supplier',
            'branch',
            'warehouse',
            'user',
            'quotation',
            'details.product',
            'details.unit',
            'expenses.expenseType',
        ]);
        return view(
            'purchase_orders.show',
            compact('purchase_order')
        );
    }
    public function edit(PurchaseOrder $purchase_order)
    {
        if (!$purchase_order->isEditable()) {
            return redirect()
                ->route('purchase_orders.show', $purchase_order)
                ->with('error', 'La orden ya fue emitida y no puede editarse.');
        }
        $purchase_order->load([
            'details',
            'expenses'
        ]);
        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $expenseTypes = ExpenseType::orderBy('name')->get();
        $quotations = PurchaseQuotation::whereIn('status', [
            'approved',
            'aprobada'
        ])->get();
        return view('purchase_orders.create', compact(
            'purchase_order',
            'suppliers',
            'branches',
            'warehouses',
            'products',
            'units',
            'expenseTypes',
            'quotations'
        ));
    }
    public function update(
        Request $request,
        PurchaseOrder $purchase_order
    ) {
        if (!$purchase_order->isEditable()) {
            return back()->with(
                'error',
                'No se puede editar una orden que ya fue emitida.'
            );
        }
        $validated = $request->validate([
            'id_supplier' => ['required', 'exists:suppliers,id'],
            'id_branch' => ['required', 'exists:branches,id'],
            'id_warehouse' => ['required', 'exists:warehouses,id'],
            'id_purchase_quotation' => [
                'nullable',
                'exists:purchase_quotations,id_purchase_quotation'
            ],
            'order_date' => ['required', 'date'],
            'expected_date' => ['required', 'date', 'after_or_equal:order_date'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_terms' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $purchase_order->update($validated);

        return redirect()
            ->route('purchase_orders.show', $purchase_order)
            ->with('success', 'Orden actualizada correctamente.');
    }
    public function updateStatus(
        Request $request,
        PurchaseOrder $purchase_order
    ) {
        $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'issued',
                    'partial_received',
                    'completed',
                    'cancelled'
                ])
            ]
        ]);
        $newStatus = $request->status;
        if ($purchase_order->status === 'cancelled') {
            return back()->with(
                'error',
                'Una orden cancelada no puede cambiar de estado.'
            );
        }
        if (
            $purchase_order->status === 'issued' &&
            $newStatus === 'draft'
        ) {
            return back()->with(
                'error',
                'Una orden emitida no puede regresar a borrador.'
            );
        }
        $purchase_order->update([
            'status' => $newStatus
        ]);
        return back()->with(
            'success',
            'Estado de la orden actualizado correctamente.'
        );
    }
    public function destroy(PurchaseOrder $purchase_order)
    {
        if (!$purchase_order->isEditable()) {
            return back()->with(
                'error',
                'Solo se pueden eliminar órdenes en borrador.'
            );
        }
        $purchase_order->delete();

        return redirect()
            ->route('purchase_orders.index')
            ->with('success', 'Orden eliminada correctamente.');
    }
    public function generatePdf(PurchaseOrder $purchase_order)
    {
        $purchase_order->load([
            'supplier',
            'branch',
            'warehouse',
            'user',
            'details.product',
            'details.unit',
            'expenses.expenseType',
        ]);
        return view(
            'purchase_orders.pdf',
            compact('purchase_order')
        );
    }
}