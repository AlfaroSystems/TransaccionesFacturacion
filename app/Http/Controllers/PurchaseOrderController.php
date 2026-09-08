<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ExpenseType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseQuotation;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service) {}

    // =========================================================================
    // Recursos de formulario reutilizables
    // =========================================================================

    /**
     * Datos de catálogo necesarios para el formulario de creación / edición.
     */
    private function formData(?int $excludeQuotationExceptId = null): array
    {
        $usedQuotationIds = PurchaseOrder::whereNotNull('id_purchase_quotation')
            ->when($excludeQuotationExceptId, fn ($q) => $q->where('id_purchase_quotation', '!=', $excludeQuotationExceptId))
            ->pluck('id_purchase_quotation');

        return [
            'suppliers'    => Supplier::orderBy('name')->get(),
            'branches'     => Branch::orderBy('name')->get(),
            'warehouses'   => Warehouse::orderBy('name')->get(),
            'products'     => Product::orderBy('name')->get(),
            'units'        => Unit::orderBy('name')->get(),
            'expenseTypes' => ExpenseType::orderBy('name')->get(),
            'quotations'   => PurchaseQuotation::whereIn('status', ['approved', 'aprobada'])
                ->whereNotIn('id_purchase_quotation', $usedQuotationIds)
                ->with(['supplier', 'details.product', 'details.unit', 'expenses.expenseType'])
                ->orderByDesc('id_purchase_quotation')
                ->get(),
        ];
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    public function index()
    {
        Gate::authorize('purchase_orders.ver');

        $purchase_orders = PurchaseOrder::with(['supplier', 'branch', 'warehouse', 'user'])
            ->orderByDesc('id_purchase_order')
            ->paginate(10);

        $usedQuotationIds = PurchaseOrder::whereNotNull('id_purchase_quotation')->pluck('id_purchase_quotation');

        $purchase_quotations = PurchaseQuotation::whereIn('status', ['approved', 'aprobada'])
            ->whereNotIn('id_purchase_quotation', $usedQuotationIds)
            ->with(['supplier', 'details.product', 'details.unit', 'expenses.expenseType'])
            ->orderByDesc('id_purchase_quotation')
            ->get();

        return view('purchase_orders.index', array_merge(
            $this->formData(),
            compact('purchase_orders', 'purchase_quotations')
        ));
    }

    /**
     * Endpoint AJAX para obtener los datos completados de una cotización aprobada.
     */
    public function getQuotationData($id)
    {
        Gate::authorize('purchase_orders.ver');

        $quotation = PurchaseQuotation::with([
            'supplier',
            'quotationRequest.purchaseRequest',
            'details.product',
            'details.unit',
            'expenses.expenseType',
        ])->findOrFail($id);

        $originRequest = $quotation->quotationRequest?->purchaseRequest;

        $expectedDate = match (true) {
            !empty($quotation->delivery_days)       => now()->addDays((int) $quotation->delivery_days)->format('Y-m-d\TH:i'),
            !empty($originRequest?->required_date)  => Carbon::parse($originRequest->required_date)->format('Y-m-d\TH:i'),
            default                                 => now()->addDays(7)->format('Y-m-d\TH:i'),
        };

        return response()->json([
            'id_purchase_quotation' => $quotation->id_purchase_quotation,
            'id_supplier'           => $quotation->id_supplier,
            'id_branch'             => $originRequest?->id_branch,
            'id_warehouse'          => $originRequest?->id_warehouse,
            'expected_date'         => $expectedDate,
            'currency'              => $quotation->currency,
            'payment_terms'         => $quotation->payment_terms,
            'delivery_days'         => $quotation->delivery_days,
            'notes'                 => $quotation->notes,
            'details'               => $quotation->details->map(fn ($d) => [
                'id_product' => $d->id_product,
                'quantity'   => (float) $d->quantity,
                'id_unit'    => $d->id_unit,
                'unit_price' => (float) $d->unit_price,
                'discount'   => (float) $d->discount,
                'tax_rate'   => (float) $d->tax_rate,
                'total'      => (float) $d->total,
            ]),
            'expenses'              => $quotation->expenses->map(fn ($e) => [
                'id_expense_type' => $e->id_expense_type,
                'description'     => $e->description,
                'amount'          => (float) $e->amount,
            ]),
        ]);
    }

    public function create()
    {
        Gate::authorize('purchase_orders.crear');

        return view('purchase_orders.create', $this->formData());
    }

    public function store(Request $request)
    {
        Gate::authorize('purchase_orders.crear');

        // Compatibilidad con formularios que envían 'details' en vez de 'products'
        if (!$request->has('products') && $request->has('details')) {
            $request->merge(['products' => $request->input('details')]);
        }

        $validated = $this->validateOrderRequest($request, mode: 'store');

        $this->service->crear($validated);

        return redirect()
            ->route('purchase_orders.index')
            ->with('success', 'Orden de compra creada correctamente.');
    }

    public function show(PurchaseOrder $purchase_order)
    {
        Gate::authorize('purchase_orders.ver');

        $purchase_order->load([
            'supplier', 'branch', 'warehouse', 'user', 'quotation',
            'details.product', 'details.unit', 'expenses.expenseType',
        ]);

        return view('purchase_orders.show', compact('purchase_order'));
    }

    public function edit(PurchaseOrder $purchase_order)
    {
        Gate::authorize('purchase_orders.editar');

        if (!$purchase_order->isEditable()) {
            return redirect()
                ->route('purchase_orders.show', $purchase_order)
                ->with('error', 'La orden ya fue emitida y no puede editarse.');
        }

        $purchase_order->load(['details', 'expenses']);

        return view('purchase_orders.create', array_merge(
            $this->formData(excludeQuotationExceptId: $purchase_order->id_purchase_quotation),
            compact('purchase_order')
        ));
    }

    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        Gate::authorize('purchase_orders.editar');

        if (!$purchase_order->isEditable()) {
            return back()->with('error', 'No se puede editar una orden que ya fue emitida.');
        }

        $validated = $this->validateOrderRequest($request, mode: 'update');

        $this->service->actualizar($purchase_order, $validated);

        return redirect()
            ->route('purchase_orders.show', $purchase_order)
            ->with('success', 'Orden de compra actualizada correctamente.');
    }

    public function updateStatus(Request $request, PurchaseOrder $purchase_order)
    {
        Gate::authorize('purchase_orders.aprobar');

        $request->validate([
            'status' => ['required', Rule::in(['draft', 'issued', 'partial_received', 'completed', 'cancelled'])],
        ]);

        try {
            $this->service->cambiarEstado($purchase_order, $request->status);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Estado de la orden actualizado correctamente.');
    }

    public function destroy(PurchaseOrder $purchase_order)
    {
        Gate::authorize('purchase_orders.eliminar');

        if (!$purchase_order->isEditable()) {
            return back()->with('error', 'Solo se pueden eliminar órdenes en borrador.');
        }

        $purchase_order->delete();

        return redirect()
            ->route('purchase_orders.index')
            ->with('success', 'Orden eliminada correctamente.');
    }

    public function generatePdf(PurchaseOrder $purchase_order)
    {
        Gate::authorize('purchase_orders.pdf');

        $purchase_order->load([
            'supplier', 'branch', 'warehouse', 'user',
            'details.product', 'details.unit', 'expenses.expenseType',
        ]);

        return view('purchase_orders.pdf', compact('purchase_order'));
    }

    // =========================================================================
    // Validación centralizada (store / update comparten mismas reglas base)
    // =========================================================================

    private function validateOrderRequest(Request $request, string $mode): array
    {
        $productIdRule = $mode === 'store'
            ? 'exists:products,id'
            : 'exists:products,id';

        $unitIdRule = $mode === 'store'
            ? 'exists:units,id'
            : 'exists:units,id';

        return $request->validate([
            'id_supplier'                    => ['required', 'exists:suppliers,id_supplier'],
            'id_branch'                      => ['required', 'exists:branches,id'],
            'id_warehouse'                   => ['required', 'exists:warehouses,id'],
            'id_purchase_quotation'          => ['nullable', 'exists:purchase_quotations,id_purchase_quotation'],
            'order_date'                     => ['required', 'date'],
            'expected_date'                  => ['required', 'date', 'after_or_equal:order_date'],
            'currency'                       => ['required', 'string', 'max:3'],
            'payment_terms'                  => ['required', 'string', 'max:255'],
            'notes'                          => ['nullable', 'string'],
            'products'                       => ['required', 'array', 'min:1'],
            'products.*.id_product'          => ['required', $productIdRule],
            'products.*.quantity'            => ['required', 'numeric', 'min:0.0001'],
            'products.*.id_unit'             => ['required', $unitIdRule],
            'products.*.unit_price'          => ['required', 'numeric', 'min:0'],
            'products.*.discount'            => ['nullable', 'numeric', 'min:0'],
            'products.*.tax_rate'            => ['nullable', 'numeric', 'min:0', 'max:100'],
            'products.*.notes'               => ['nullable', 'string'],
            'expenses'                       => ['nullable', 'array'],
            'expenses.*.id_expense_type'     => ['required_with:expenses', 'exists:expense_types,id_expense_type'],
            'expenses.*.description'         => ['nullable', 'string', 'max:255'],
            'expenses.*.amount'              => ['required_with:expenses', 'numeric', 'min:0'],
        ], [
            'required'       => 'El campo :attribute es obligatorio.',
            'min'            => 'El campo :attribute no cumple el valor mínimo requerido.',
            'exists'         => 'El valor seleccionado para :attribute no es válido.',
            'after_or_equal' => 'La :attribute debe ser igual o posterior a la Fecha de Orden.',
        ], [
            'id_supplier'                => 'Proveedor',
            'id_branch'                  => 'Sucursal',
            'id_warehouse'               => 'Bodega',
            'order_date'                 => 'Fecha de Orden',
            'expected_date'              => 'Fecha Esperada',
            'currency'                   => 'Moneda',
            'payment_terms'              => 'Condiciones de Pago',
            'products'                   => 'Productos',
            'products.*.id_product'      => 'Producto',
            'products.*.quantity'        => 'Cantidad de Producto',
            'products.*.id_unit'         => 'Unidad de Medida',
            'products.*.unit_price'      => 'Precio Unitario',
            'expenses.*.id_expense_type' => 'Tipo de Gasto',
            'expenses.*.amount'          => 'Monto del Gasto',
        ]);
    }
}