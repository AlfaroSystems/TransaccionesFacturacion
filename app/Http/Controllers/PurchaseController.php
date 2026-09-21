<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('purchases.ver');

        $query = Purchase::with(['purchaseOrder', 'supplier', 'branch', 'warehouse', 'user'])
            ->orderByDesc('id_purchase');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('purchase_code', 'ilike', "%{$search}%")
                  ->orWhere('supplier_invoice_number', 'ilike', "%{$search}%")
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'ilike', "%{$search}%"))
                  ->orWhereHas('purchaseOrder', fn ($oq) => $oq->where('purchase_order_code', 'ilike', "%{$search}%"));
            });
        }

        if ($request->filled('id_supplier')) {
            $query->where('id_supplier', $request->id_supplier);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        $purchases = $query->paginate(10)->withQueryString();

        // Métricas
        $totalCount     = Purchase::count();
        $draftCount     = Purchase::where('status', 'draft')->count();
        $completedCount = Purchase::whereIn('status', ['received', 'completed'])->count();
        $totalAmount    = Purchase::where('status', '!=', 'cancelled')->sum('total');

        $suppliers = Supplier::orderBy('name')->get();
        $branches   = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $orders     = PurchaseOrder::whereIn('status', ['issued', 'partial_received'])
            ->with(['supplier', 'branch', 'warehouse', 'details.product', 'details.unit'])
            ->orderByDesc('id_purchase_order')
            ->get();

        return view('purchases.index', compact(
            'purchases',
            'totalCount',
            'draftCount',
            'completedCount',
            'totalAmount',
            'suppliers',
            'branches',
            'warehouses',
            'orders'
        ));
    }

    public function create(Request $request)
    {
        Gate::authorize('purchases.crear');

        $orders = PurchaseOrder::whereIn('status', ['issued', 'partial_received'])
            ->with(['supplier', 'branch', 'warehouse', 'details.product', 'details.unit'])
            ->orderByDesc('id_purchase_order')
            ->get();

        $selectedOrderId = $request->query('id_purchase_order');
        $preselectedOrder = null;
        if ($selectedOrderId) {
            $preselectedOrder = PurchaseOrder::with(['supplier', 'branch', 'warehouse', 'details.product', 'details.unit'])
                ->find($selectedOrderId);
        }

        $suppliers  = Supplier::orderBy('name')->get();
        $branches   = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $products   = Product::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();

        return view('purchases.create', compact(
            'orders',
            'preselectedOrder',
            'suppliers',
            'branches',
            'warehouses',
            'products',
            'units'
        ));
    }

    public function getOrderData($id)
    {
        Gate::authorize('purchases.ver');

        $order = PurchaseOrder::with([
            'supplier',
            'branch',
            'warehouse',
            'details.product',
            'details.unit',
        ])->findOrFail($id);

        // Calcular cantidades previamente recibidas para cada detalle de la orden
        $orderDetailIds = $order->details->pluck('id_purchase_order_detail');
        $receivedSums = PurchaseDetail::whereHas('purchase', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->whereIn('id_purchase_order_detail', $orderDetailIds)
            ->groupBy('id_purchase_order_detail')
            ->selectRaw('id_purchase_order_detail, SUM(quantity_received) as sum_received')
            ->pluck('sum_received', 'id_purchase_order_detail');

        return response()->json([
            'id_purchase_order'   => $order->id_purchase_order,
            'purchase_order_code' => $order->purchase_order_code,
            'id_supplier'         => $order->id_supplier,
            'supplier_name'       => $order->supplier?->name,
            'id_branch'           => $order->id_branch,
            'branch_name'         => $order->branch?->name,
            'id_warehouse'        => $order->id_warehouse,
            'warehouse_name'      => $order->warehouse?->name,
            'currency'            => $order->currency,
            'details'             => $order->details->map(function ($d) use ($receivedSums) {
                $ordered = (float) $d->quantity;
                $alreadyReceived = (float) ($receivedSums[$d->id_purchase_order_detail] ?? 0);
                $pending = max(0, $ordered - $alreadyReceived);

                return [
                    'id_purchase_order_detail' => $d->id_purchase_order_detail,
                    'id_product'               => $d->id_product,
                    'product_name'             => $d->product?->name ?? 'Producto #'.$d->id_product,
                    'product_code'             => $d->product?->code ?? '',
                    'quantity_ordered'         => $ordered,
                    'already_received'         => $alreadyReceived,
                    'pending_quantity'         => $pending,
                    'quantity_received'        => $pending,
                    'id_unit'                  => $d->id_unit,
                    'unit_name'                => $d->unit?->name ?? 'Unidad',
                    'unit_price'               => (float) $d->unit_price,
                    'discount'                 => (float) $d->discount,
                    'tax_rate'                 => (float) $d->tax_rate,
                    'notes'                    => $d->notes ?? '',
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('purchases.crear');

        $validated = $request->validate([
            'id_purchase_order'          => ['required', 'exists:purchase_orders,id_purchase_order'],
            'id_supplier'                => ['required', 'exists:suppliers,id_supplier'],
            'id_branch'                  => ['nullable', 'exists:branches,id'],
            'id_warehouse'               => ['nullable', 'exists:warehouses,id'],
            'purchase_date'              => ['required', 'date'],
            'supplier_invoice_number'    => ['nullable', 'string', 'max:100'],
            'supplier_invoice_date'      => ['nullable', 'date'],
            'currency'                   => ['nullable', 'string', 'max:3'],
            'status'                     => ['required', Rule::in(['draft', 'received', 'completed'])],
            'notes'                      => ['nullable', 'string'],
            'details'                    => ['required', 'array', 'min:1'],
            'details.*.id_product'       => ['required', 'exists:products,id'],
            'details.*.quantity_ordered' => ['nullable', 'numeric', 'min:0'],
            'details.*.quantity_received'=> ['required', 'numeric', 'min:0.0001'],
            'details.*.unit_price'       => ['required', 'numeric', 'min:0'],
            'details.*.discount'         => ['nullable', 'numeric', 'min:0'],
            'details.*.tax_rate'         => ['nullable', 'numeric', 'min:0'],
            'details.*.id_unit'          => ['nullable', 'exists:units,id'],
            'details.*.id_purchase_order_detail' => ['nullable', 'exists:purchase_order_details,id_purchase_order_detail'],
            'details.*.notes'            => ['nullable', 'string'],
        ]);

        $purchase = $this->service->crear($validated);

        return redirect()
            ->route('purchases.show', $purchase->id_purchase)
            ->with('success', 'Factura de compra registrada exitosamente.');
    }

    public function show(Purchase $purchase)
    {
        Gate::authorize('purchases.ver');

        $purchase->load([
            'purchaseOrder',
            'supplier',
            'branch',
            'warehouse',
            'user',
            'details.product',
            'details.unit',
            'details.purchaseOrderDetail',
        ]);

        return view('purchases.show', compact('purchase'));
    }

    public function getEditData(Purchase $purchase)
    {
        Gate::authorize('purchases.editar');

        $purchase->load([
            'purchaseOrder',
            'supplier',
            'details.product',
            'details.unit',
        ]);

        return response()->json([
            'id_purchase'             => $purchase->id_purchase,
            'purchase_code'           => $purchase->purchase_code,
            'id_purchase_order'       => $purchase->id_purchase_order,
            'purchase_order_code'     => $purchase->purchaseOrder?->purchase_order_code,
            'id_supplier'             => $purchase->id_supplier,
            'id_branch'               => $purchase->id_branch,
            'id_warehouse'            => $purchase->id_warehouse,
            'purchase_date'           => $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d\TH:i') : '',
            'supplier_invoice_number' => $purchase->supplier_invoice_number ?? '',
            'supplier_invoice_date'   => $purchase->supplier_invoice_date ? $purchase->supplier_invoice_date->format('Y-m-d') : '',
            'currency'                => $purchase->currency,
            'status'                  => $purchase->status,
            'notes'                   => $purchase->notes ?? '',
            'details'                 => $purchase->details->map(fn ($d) => [
                'id_purchase_detail'       => $d->id_purchase_detail,
                'id_purchase_order_detail' => $d->id_purchase_order_detail,
                'id_product'               => $d->id_product,
                'product_name'             => $d->product?->name ?? 'Producto #'.$d->id_product,
                'product_code'             => $d->product?->code ?? '',
                'quantity_ordered'         => (float) $d->quantity_ordered,
                'quantity_received'        => (float) $d->quantity_received,
                'id_unit'                  => $d->id_unit,
                'unit_name'                => $d->unit?->name ?? 'Unidad',
                'unit_price'               => (float) $d->unit_price,
                'discount'                 => (float) $d->discount,
                'tax_rate'                 => (float) $d->tax_rate,
                'notes'                    => $d->notes ?? '',
            ]),
        ]);
    }

    public function edit(Purchase $purchase)
    {
        Gate::authorize('purchases.editar');

        if ($purchase->status !== 'draft') {
            return redirect()
                ->route('purchases.show', $purchase->id_purchase)
                ->with('error', 'Solo las compras en estado borrador pueden ser modificadas.');
        }

        $purchase->load(['details.product', 'details.unit', 'purchaseOrder']);

        $suppliers  = Supplier::orderBy('name')->get();
        $branches   = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $products   = Product::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();

        return view('purchases.create', compact(
            'purchase',
            'suppliers',
            'branches',
            'warehouses',
            'products',
            'units'
        ));
    }

    public function update(Request $request, Purchase $purchase)
    {
        Gate::authorize('purchases.editar');

        if ($purchase->status !== 'draft') {
            return redirect()
                ->route('purchases.show', $purchase->id_purchase)
                ->with('error', 'Solo las compras en estado borrador pueden ser modificadas.');
        }

        $validated = $request->validate([
            'id_supplier'                => ['required', 'exists:suppliers,id_supplier'],
            'id_branch'                  => ['nullable', 'exists:branches,id'],
            'id_warehouse'               => ['nullable', 'exists:warehouses,id'],
            'purchase_date'              => ['required', 'date'],
            'supplier_invoice_number'    => ['nullable', 'string', 'max:100'],
            'supplier_invoice_date'      => ['nullable', 'date'],
            'currency'                   => ['nullable', 'string', 'max:3'],
            'notes'                      => ['nullable', 'string'],
            'details'                    => ['required', 'array', 'min:1'],
            'details.*.id_product'       => ['required', 'exists:products,id'],
            'details.*.quantity_ordered' => ['nullable', 'numeric', 'min:0'],
            'details.*.quantity_received'=> ['required', 'numeric', 'min:0.0001'],
            'details.*.unit_price'       => ['required', 'numeric', 'min:0'],
            'details.*.discount'         => ['nullable', 'numeric', 'min:0'],
            'details.*.tax_rate'         => ['nullable', 'numeric', 'min:0'],
            'details.*.id_unit'          => ['nullable', 'exists:units,id'],
            'details.*.id_purchase_order_detail' => ['nullable', 'exists:purchase_order_details,id_purchase_order_detail'],
            'details.*.notes'            => ['nullable', 'string'],
        ]);

        $this->service->actualizar($purchase, $validated);

        return redirect()
            ->route('purchases.show', $purchase->id_purchase)
            ->with('success', 'Compra actualizada correctamente.');
    }

    public function updateStatus(Request $request, Purchase $purchase)
    {
        Gate::authorize('purchases.cambiar_estado');

        $request->validate([
            'status' => ['required', Rule::in(['draft', 'received', 'completed', 'cancelled'])],
        ]);

        try {
            $this->service->cambiarEstado($purchase, $request->status);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Estado de la compra actualizado correctamente.');
    }

    public function destroy(Purchase $purchase)
    {
        Gate::authorize('purchases.eliminar');

        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Solo se pueden eliminar compras en borrador.');
        }

        $purchase->delete();

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Compra eliminada correctamente.');
    }
}
