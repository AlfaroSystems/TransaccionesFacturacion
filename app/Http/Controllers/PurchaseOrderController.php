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
use App\Rules\Accessible;
use App\Rules\DiscountWithinLine;
use App\Services\PurchaseOrderService;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    private function formData(): array
    {
        // El departamento de compras emite órdenes para cualquier sucursal de su empresa:
        // cada orden va a la sucursal que pidió los productos
        $isPurchasingDepartment = BranchAccess::isPurchasingDepartment();

        $branches = ($isPurchasingDepartment
            ? Branch::queryAllBranches()->where('id_company', BranchAccess::companyId())
            : Branch::query())
            ->orderBy('name')
            ->get();

        $warehouses = ($isPurchasingDepartment ? Warehouse::queryAllBranches() : Warehouse::query())
            ->whereIn('id_branch', $branches->pluck('id_branch'))
            ->orderBy('name')
            ->get();

        return [
            'suppliers'    => Supplier::orderBy('name')->get(),
            'branches'     => $branches,
            'warehouses'   => $warehouses,
            'products'     => Product::orderBy('name')->get(),
            'units'        => Unit::orderBy('name')->get(),
            'expenseTypes' => ExpenseType::orderBy('name')->get(),
        ];
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    public function index()
    {
        Gate::authorize('purchase_orders.ver');

        $query = PurchaseOrder::with(['supplier', 'branch', 'warehouse', 'user']);

        $purchase_orders = $query
            ->orderByDesc('id_purchase_order')
            ->paginate(10);

        return view('purchase_orders.index', array_merge(
            $this->formData(),
            compact('purchase_orders')
        ));
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
            $this->formData(),
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

        if (DB::table('purchases')->where('id_purchase_order', $purchase_order->id_purchase_order)->exists()) {
            return back()->with('error', 'No se puede eliminar una orden que ya tiene compras registradas.');
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
            ? 'exists:products,id_product'
            : 'exists:products,id_product';

        $unitIdRule = $mode === 'store'
            ? 'exists:units,id_unit'
            : 'exists:units,id_unit';

        // El departamento de compras puede elegir cualquier sucursal de su empresa
        $anyCompanyBranch = BranchAccess::isPurchasingDepartment();

        return $request->validate([
            'id_supplier'                    => ['required', 'exists:supliers,id_supplier'],
            // Sucursal, bodega y cotización deben ser visibles para el usuario; la bodega, de la sucursal elegida
            'id_branch'                      => ['required', $anyCompanyBranch
                ? Rule::exists('branches', 'id_branch')->where('id_company', BranchAccess::companyId())
                : new Accessible(Branch::class)],
            'id_warehouse'                   => ['required', $anyCompanyBranch
                ? Rule::exists('warehouses', 'id_warehouse')->where('id_branch', $request->input('id_branch'))
                : new Accessible(Warehouse::class, constraint: fn ($q) => $q->where('id_branch', $request->input('id_branch')))],
            'id_purchase_quotation'          => ['nullable', new Accessible(PurchaseQuotation::class)],
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
            'products.*.discount'            => ['nullable', 'numeric', 'min:0', new DiscountWithinLine()],
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