<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Rules\Accessible;
use App\Rules\DiscountWithinLine;
use App\Services\PurchaseService;
use App\Support\BranchAccess;
use App\Support\Moneda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    /** Mensajes de las reglas de líneas (solo productos de la orden, una vez cada uno) */
    private const MENSAJES_LINEAS = [
        'details.*.id_purchase_order_detail.required' => 'Solo se pueden recibir productos de la orden de compra.',
        'details.*.id_purchase_order_detail.exists' => 'Solo se pueden recibir productos de la orden de compra.',
        'details.*.id_purchase_order_detail.distinct' => 'Un producto de la orden aparece en más de una línea.',
    ];

    public function __construct(private readonly PurchaseService $service) {}

    /** Mensajes de la bodega destino: la mercadería entra a Casa Matriz */
    private const MENSAJES_BODEGA = [
        'id_warehouse.required' => 'Elija la bodega de Casa Matriz donde entra la mercadería.',
        'id_warehouse.in' => 'La mercadería entra a una bodega de Casa Matriz (el departamento de compras), que luego la reparte a las sucursales.',
    ];

    /**
     * Bodegas activas del departamento de compras (Casa Matriz). Sin el filtro por sucursal:
     * quien registra la compra puede no ser de Casa Matriz, como el administrador.
     */
    private function bodegasDeCompras(): Builder
    {
        return Warehouse::queryAllBranches()
            ->where('is_active', true)
            ->whereIn('id_branch', Branch::queryAllBranches()->where('is_purchasing_department', true)->select('id_branch'));
    }

    /**
     * Bodegas donde puede entrar la mercadería de una orden: las de Casa Matriz de la empresa de
     * la orden. La orden va a la sucursal que pidió los productos, pero lo comprado llega a
     * Casa Matriz, que luego lo reparte a las sucursales.
     *
     * @return Collection<int, int> id_warehouse
     */
    private function bodegasDeCasaMatriz(?int $orderBranchId): Collection
    {
        $companyId = Branch::queryAllBranches()->whereKey($orderBranchId)->value('id_company');

        return $this->bodegasDeCompras()
            ->whereIn('id_branch', Branch::queryAllBranches()->where('id_company', $companyId)->select('id_branch'))
            ->pluck('id_warehouse');
    }

    /**
     * Sin bodega elegida, la compra entra a la preferida (la que ya tenía) si es de Casa Matriz,
     * o a la de Casa Matriz si tiene una sola.
     */
    private function elegirBodegaPorDefecto(Request $request, Collection $bodegas, ?int $preferida): void
    {
        if ($request->filled('id_warehouse')) {
            return;
        }

        $bodega = $bodegas->contains($preferida) ? $preferida : ($bodegas->count() === 1 ? $bodegas->first() : null);

        if ($bodega) {
            $request->merge(['id_warehouse' => $bodega]);
        }
    }

    /**
     * Bodegas del selector de los formularios: las de Casa Matriz que el usuario puede usar.
     */
    private function bodegasDelFormulario(): Collection
    {
        return $this->bodegasDeCompras()
            ->when(! BranchAccess::isUnrestricted(), fn ($q) => $q->whereIn('id_branch', BranchAccess::companyBranchIds()))
            ->orderBy('name')
            ->get();
    }

    /**
     * El producto de cada línea debe ser el de su línea de orden.
     */
    private function productoDeSuLinea(Request $request): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($request) {
            $index = explode('.', $attribute)[1] ?? null;
            $orderLineId = $request->input("details.{$index}.id_purchase_order_detail");
            $orderLineProduct = $orderLineId ? PurchaseOrderDetail::whereKey($orderLineId)->value('id_product') : null;

            if ($orderLineProduct !== null && (int) $orderLineProduct !== (int) $value) {
                $fail('El producto no corresponde a su línea de la orden de compra.');
            }
        };
    }

    public function index(Request $request)
    {
        Gate::authorize('purchases.ver');

        // Purchase, PurchaseOrder, Branch y Warehouse se filtran por la sucursal del usuario (BranchScope)
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
            $query->where('id_supplier', $request->integer('id_supplier'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($desde = $this->fechaDelFiltro($request, 'date_from')) {
            $query->whereDate('purchase_date', '>=', $desde);
        }

        if ($hasta = $this->fechaDelFiltro($request, 'date_to')) {
            $query->whereDate('purchase_date', '<=', $hasta);
        }

        $purchases = $query->paginate(10)->withQueryString();

        // Métricas
        $totalCount = Purchase::count();
        $draftCount = Purchase::where('status', 'draft')->count();
        $completedCount = Purchase::whereIn('status', ['received', 'completed'])->count();
        $totalAmount = Purchase::where('status', '!=', 'cancelled')->sum('total');

        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $warehouses = $this->bodegasDelFormulario();
        // Solo para el selector: el detalle de la orden elegida lo trae getOrderData
        $orders = PurchaseOrder::whereIn('status', ['issued', 'partial_received'])
            ->with('supplier')
            ->orderByDesc('id_purchase_order')
            ->get();

        return view('purchases.index', [
            ...compact(
                'purchases',
                'totalCount',
                'draftCount',
                'completedCount',
                'totalAmount',
                'suppliers',
                'branches',
                'warehouses',
                'orders'
            ),
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('purchases.crear');

        // Solo para el selector (la vista preselecciona ?id_purchase_order): el detalle de la
        // orden elegida lo trae getOrderData
        $orders = PurchaseOrder::whereIn('status', ['issued', 'partial_received'])
            ->with('supplier')
            ->orderByDesc('id_purchase_order')
            ->get();

        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $warehouses = $this->bodegasDelFormulario();
        $products = Product::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('purchases.create', [
            ...compact(
                'orders',
                'suppliers',
                'branches',
                'warehouses',
                'products',
                'units'
            ),
        ]);
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

        // Cantidad ya recibida de cada línea en compras confirmadas (el borrador no cuenta)
        $receivedSums = $this->service->recibidoPorLinea($order->details->pluck('id_purchase_order_detail'));

        return response()->json([
            'id_purchase_order' => $order->id_purchase_order,
            'purchase_order_code' => $order->purchase_order_code,
            'id_supplier' => $order->id_supplier,
            'supplier_name' => $order->supplier?->name,
            'id_branch' => $order->id_branch,
            'branch_name' => $order->branch?->name,
            'id_warehouse' => $order->id_warehouse,
            'warehouse_name' => $order->warehouse?->name,
            'currency' => $order->currency,
            // Solo las líneas a las que aún les falta recibir algo
            'details' => $order->details->filter(
                fn ($d) => (float) $d->quantity - (float) ($receivedSums[$d->id_purchase_order_detail] ?? 0) > 0
            )->values()->map(function ($d) use ($receivedSums) {
                $ordered = (float) $d->quantity;
                $alreadyReceived = (float) ($receivedSums[$d->id_purchase_order_detail] ?? 0);
                $pending = max(0, $ordered - $alreadyReceived);

                return [
                    'id_purchase_order_detail' => $d->id_purchase_order_detail,
                    'id_product' => $d->id_product,
                    'product_name' => $d->product?->name ?? 'Producto #'.$d->id_product,
                    'product_code' => $d->product?->code ?? '',
                    'quantity_ordered' => $ordered,
                    'already_received' => $alreadyReceived,
                    'pending_quantity' => $pending,
                    'quantity_received' => $pending,
                    'id_unit' => $d->id_unit,
                    'unit_name' => $d->unit?->name ?? 'Unidad',
                    'unit_price' => (float) $d->unit_price,
                    'discount' => (float) $d->discount,
                    'tax_rate' => (float) $d->tax_rate,
                    'notes' => $d->notes ?? '',
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('purchases.crear');

        // La compra hereda la sucursal de su orden (la orden debe ser visible para el usuario), y
        // su mercadería entra a una bodega de Casa Matriz de esa empresa
        $order = PurchaseOrder::whereKey($request->integer('id_purchase_order'))->first();
        $bodegas = $this->bodegasDeCasaMatriz($order?->id_branch);
        $this->elegirBodegaPorDefecto($request, $bodegas, $order?->id_warehouse);

        $validated = $request->validate([
            'id_purchase_order' => ['required', new Accessible(PurchaseOrder::class)],
            'id_supplier' => ['required', 'exists:supliers,id_supplier'],
            'id_warehouse' => ['required', Rule::in($bodegas->all())],
            'purchase_date' => ['required', 'date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_date' => ['nullable', 'date'],
            'currency' => Moneda::regla(),
            'status' => ['required', Rule::in(['draft', 'received', 'completed'])],
            'notes' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.id_product' => ['required', 'exists:products,id_product', $this->productoDeSuLinea($request)],
            'details.*.quantity_ordered' => ['nullable', 'numeric', 'min:0'],
            'details.*.quantity_received' => ['required', 'numeric', 'min:0.0001'],
            'details.*.unit_price' => ['required', 'numeric', 'min:0'],
            'details.*.discount' => ['nullable', 'numeric', 'min:0', new DiscountWithinLine('quantity_received')],
            'details.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.id_unit' => ['nullable', 'exists:units,id_unit'],
            'details.*.id_purchase_order_detail' => ['required', 'integer', 'distinct', Rule::exists('purchase_order_details', 'id_purchase_order_detail')->where('id_purchase_order', $request->integer('id_purchase_order'))],
            'details.*.notes' => ['nullable', 'string'],
        ], [...self::MENSAJES_LINEAS, ...self::MENSAJES_BODEGA, 'currency.in' => Moneda::MENSAJE]);

        try {
            $purchase = $this->service->crear($validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

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
            'details.inventoryLots',
            'retaceos',
        ]);

        // Toda compra lleva retaceo: al aplicarlo se crean sus lotes, que luego se ubican en bodega
        if (in_array($purchase->status, Purchase::RECEIVED_STATUSES, true) && ! $purchase->retaceos->contains('status', 'applied')) {
            session()->now('info', 'La mercadería todavía no está en inventario: sus lotes se crean al aplicar el retaceo de esta compra y entran al ubicarlos en bodega.');
        }

        return view('purchases.show', compact('purchase'));
    }

    public function generatePdf(Purchase $purchase)
    {
        Gate::authorize('purchases.ver');

        $purchase->load([
            'purchaseOrder',
            'supplier',
            'branch.company',
            'warehouse',
            'user',
            'details.product',
            'details.unit',
            'details.purchaseOrderDetail',
        ]);

        $company = $purchase->branch?->company ?? Company::where('is_active', true)->first();

        return view('purchases.pdf', compact('purchase', 'company'));
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
            'id_purchase' => $purchase->id_purchase,
            'purchase_code' => $purchase->purchase_code,
            'id_purchase_order' => $purchase->id_purchase_order,
            'purchase_order_code' => $purchase->purchaseOrder?->purchase_order_code,
            'id_supplier' => $purchase->id_supplier,
            'id_branch' => $purchase->id_branch,
            'id_warehouse' => $purchase->id_warehouse,
            'purchase_date' => $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d\TH:i') : '',
            'supplier_invoice_number' => $purchase->supplier_invoice_number ?? '',
            'supplier_invoice_date' => $purchase->supplier_invoice_date ? $purchase->supplier_invoice_date->format('Y-m-d') : '',
            'currency' => $purchase->currency,
            'status' => $purchase->status,
            'notes' => $purchase->notes ?? '',
            'details' => $purchase->details->map(fn ($d) => [
                'id_purchase_detail' => $d->id_purchase_detail,
                'id_purchase_order_detail' => $d->id_purchase_order_detail,
                'id_product' => $d->id_product,
                'product_name' => $d->product?->name ?? 'Producto #'.$d->id_product,
                'product_code' => $d->product?->code ?? '',
                'quantity_ordered' => (float) $d->quantity_ordered,
                'quantity_received' => (float) $d->quantity_received,
                'id_unit' => $d->id_unit,
                'unit_name' => $d->unit?->name ?? 'Unidad',
                'unit_price' => (float) $d->unit_price,
                'discount' => (float) $d->discount,
                'tax_rate' => (float) $d->tax_rate,
                'notes' => $d->notes ?? '',
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

        $suppliers = Supplier::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $warehouses = $this->bodegasDelFormulario();
        $products = Product::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('purchases.create', [
            ...compact(
                'purchase',
                'suppliers',
                'branches',
                'warehouses',
                'products',
                'units'
            ),
        ]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        Gate::authorize('purchases.editar');

        if ($purchase->status !== 'draft') {
            return redirect()
                ->route('purchases.show', $purchase->id_purchase)
                ->with('error', 'Solo las compras en estado borrador pueden ser modificadas.');
        }

        // La sucursal y la orden de la compra no cambian: las líneas deben ser de esa orden y la
        // bodega, de Casa Matriz de esa empresa
        $bodegas = $this->bodegasDeCasaMatriz($purchase->id_branch);
        $this->elegirBodegaPorDefecto($request, $bodegas, $purchase->id_warehouse);

        $validated = $request->validate([
            'id_supplier' => ['required', 'exists:supliers,id_supplier'],
            'id_warehouse' => ['required', Rule::in($bodegas->all())],
            'purchase_date' => ['required', 'date'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'supplier_invoice_date' => ['nullable', 'date'],
            'currency' => Moneda::regla(),
            'notes' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.id_product' => ['required', 'exists:products,id_product', $this->productoDeSuLinea($request)],
            'details.*.quantity_ordered' => ['nullable', 'numeric', 'min:0'],
            'details.*.quantity_received' => ['required', 'numeric', 'min:0.0001'],
            'details.*.unit_price' => ['required', 'numeric', 'min:0'],
            'details.*.discount' => ['nullable', 'numeric', 'min:0', new DiscountWithinLine('quantity_received')],
            'details.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'details.*.id_unit' => ['nullable', 'exists:units,id_unit'],
            'details.*.id_purchase_order_detail' => ['required', 'integer', 'distinct', Rule::exists('purchase_order_details', 'id_purchase_order_detail')->where('id_purchase_order', $purchase->id_purchase_order)],
            'details.*.notes' => ['nullable', 'string'],
        ], [...self::MENSAJES_LINEAS, ...self::MENSAJES_BODEGA, 'currency.in' => Moneda::MENSAJE]);

        try {
            $this->service->actualizar($purchase, $validated);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('purchases.show', $purchase->id_purchase)
                ->with('error', $e->getMessage());
        }

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

        try {
            $this->service->eliminar($purchase);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Compra eliminada correctamente.');
    }
}
