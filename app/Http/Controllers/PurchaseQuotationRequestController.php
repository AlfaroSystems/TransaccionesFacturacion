<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseQuotationRequest;
use App\Http\Requests\UpdatePurchaseQuotationRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\ExpenseType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseQuotationDetail;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use App\Support\BranchAccess;
use App\Support\Moneda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PurchaseQuotationRequestController extends Controller
{
    /**
     * Muestra el listado de solicitudes de cotización.
     */
    public function index(Request $request)
    {
        Gate::authorize('purchase_quotation_requests.ver');

        $search = $request->input('search');

        $query = PurchaseQuotationRequest::with([
            'branch',
            'details.purchaseRequestDetail.purchaseRequest.branch',
            'details.quotationDetail.quotation.supplier',
        ]);

        if ($search) {
            $query->whereHas('details.purchaseRequestDetail.purchaseRequest', function ($sub) use ($search) {
                $sub->where('purchase_request_code', 'ilike', "%{$search}%")
                    ->orWhere('justification', 'ilike', "%{$search}%");
            });
        }

        $quotationRequests = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $metrics = [
            'total' => PurchaseQuotationRequest::count(),
        ];

        return view('purchase_quotation_requests.index', [
            ...compact('quotationRequests', 'metrics', 'search'),
            ...$this->productFormData(),
        ]);
    }

    /**
     * Productos, unidades y destinos para los formularios de productos sin solicitud de
     * compra. El departamento de compras puede elegir cualquier sucursal de su empresa.
     */
    private function productFormData(): array
    {
        $isPurchasingDepartment = BranchAccess::isPurchasingDepartment();

        $branches = ($isPurchasingDepartment
                ? Branch::queryAllBranches()->where('id_company', BranchAccess::companyId())
                : Branch::query())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = ($isPurchasingDepartment ? Warehouse::queryAllBranches() : Warehouse::query())
            ->whereIn('id_branch', $branches->pluck('id_branch'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'branches' => $branches,
            'warehouses' => $warehouses,
            // Destino por defecto: la sucursal del usuario (Casa Matriz para compras)
            'ownBranchId' => BranchAccess::isUnrestricted() ? null : BranchAccess::branchId(),
        ];
    }

    /**
     * Endpoint AJAX: Devuelve las Solicitudes de Compra aprobadas por el departamento de
     * compras que aún no tienen solicitud de cotización.
     */
    public function getApprovedPurchaseRequests(): JsonResponse
    {
        Gate::authorize('purchase_quotation_requests.ver');

        $approvedRequests = PurchaseRequest::where('status', PurchaseRequest::STATUS_APPROVED)
            ->with('branch:id_branch,name')
            ->select([
                'id_purchase_request',
                'purchase_request_code',
                'id_branch',
                'justification',
                'request_date',
                'required_date',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($approvedRequests);
    }

    /**
     * Endpoint AJAX: Devuelve los ítems y cantidades de una solicitud de compra específica.
     */
    public function getPurchaseRequestDetails(int $id): JsonResponse
    {
        Gate::authorize('purchase_quotation_requests.ver');

        // La solicitud debe ser visible para el usuario (de su sucursal)
        $purchaseRequest = PurchaseRequest::findOrFail($id);

        $details = PurchaseRequestDetail::where('id_purchase_request', $purchaseRequest->id_purchase_request)
            ->with([
                'product:id_product,name,sku',
                'unit:id_unit,name,abbreviation',
            ])
            ->get();

        return response()->json($details);
    }

    /**
     * Crea la solicitud de cotización en borrador con solicitudes de compra aprobadas (se
     * cotizan completas) y/o productos sin solicitud de compra, que van al destino elegido.
     */
    public function store(StorePurchaseQuotationRequest $request)
    {
        Gate::authorize('purchase_quotation_requests.crear');

        $validated = $request->validated();
        $purchaseRequestIds = $validated['purchase_requests'] ?? [];
        $products = array_values($validated['products'] ?? []);

        $quotationRequest = DB::transaction(function () use ($validated, $purchaseRequestIds, $products) {
            // Se bloquean las solicitudes de compra para que dos usuarios no las coticen a la vez
            $purchaseRequests = PurchaseRequest::whereKey($purchaseRequestIds)
                ->lockForUpdate()
                ->with('details')
                ->get();

            $allApproved = $purchaseRequests->count() === count($purchaseRequestIds)
                && $purchaseRequests->every(fn ($pr) => $pr->status === PurchaseRequest::STATUS_APPROVED);

            if (! $allApproved) {
                return null;
            }

            $quotationRequest = PurchaseQuotationRequest::createFromPurchaseRequests($purchaseRequests, $products, $products ? [
                'id_branch' => $validated['id_branch'],
                'id_warehouse' => $validated['id_warehouse'],
            ] : []);

            // Quedan en cotización: ya no pueden devolverse, rechazarse ni cotizarse otra vez
            $purchaseRequests->each->update([
                'status' => PurchaseRequest::STATUS_QUOTED,
            ]);

            return $quotationRequest;
        });

        if (! $quotationRequest) {
            return redirect()
                ->route('purchase-quotation-requests.index')
                ->with('error', 'Alguna de las solicitudes de compra ya no está disponible para cotizar.');
        }

        return redirect()
            ->route('purchase-quotation-requests.show', $quotationRequest->id_purchase_quotation_request)
            ->with('success', 'Solicitud de cotización creada en borrador. Revise sus productos y genere el PDF para enviarlo a los proveedores.');
    }

    /**
     * Cambia los productos de una solicitud en borrador: quita o cambia la cantidad total
     * de los productos que vienen de solicitudes de compra, y reemplaza los productos sin
     * solicitud de compra (con su destino) por los del formulario.
     */
    public function update(UpdatePurchaseQuotationRequest $request, PurchaseQuotationRequest $purchaseQuotationRequest)
    {
        Gate::authorize('purchase_quotation_requests.editar');

        $validated = $request->validated();
        $quantities = $validated['lines'] ?? [];
        $products = array_values($validated['products'] ?? []);

        $result = DB::transaction(function () use ($purchaseQuotationRequest, $validated, $quantities, $products) {
            // Se bloquea la solicitud para que no reciba ofertas mientras cambian sus productos
            $quotationRequest = PurchaseQuotationRequest::whereKey($purchaseQuotationRequest->getKey())
                ->lockForUpdate()
                ->with('details.purchaseRequestDetail')
                ->firstOrFail();

            if (! $quotationRequest->isDraft()) {
                return 'Solo se pueden cambiar los productos de una solicitud en borrador; esta ya tiene ofertas de proveedores.';
            }

            [$fromRequests, $ownProducts] = $quotationRequest->details
                ->sortBy('id_purchase_quotation_request_detail')
                ->partition(fn ($detail) => $detail->id_purchase_request_detail !== null);

            // Las líneas de solicitudes de compra se editan por producto y unidad, con su total
            $byProduct = $fromRequests->groupBy(fn ($detail) => $detail->lineKey());

            if ($byProduct->keys()->every(fn ($key) => ! isset($quantities[$key])) && $products === []) {
                return 'La solicitud de cotización debe tener al menos un producto.';
            }

            foreach ($byProduct as $key => $lines) {
                if (isset($quantities[$key])) {
                    $this->distributeQuantity($lines, (float) $quantities[$key]['quantity']);
                } else {
                    $lines->each->delete();
                }
            }

            $ownProducts->each->delete();
            $quotationRequest->addProducts($products);
            $quotationRequest->update([
                'id_branch' => $products ? $validated['id_branch'] : null,
                'id_warehouse' => $products ? $validated['id_warehouse'] : null,
            ]);

            // Las solicitudes de compra que se quitaron por completo vuelven a quedar
            // aprobadas, para cotizarlas en otra solicitud
            [$kept, $removed] = $fromRequests->partition(fn ($detail) => $detail->exists);
            $removedRequests = $removed
                ->pluck('purchaseRequestDetail.id_purchase_request')
                ->unique()
                ->diff($kept->pluck('purchaseRequestDetail.id_purchase_request'));

            PurchaseRequest::queryAllBranches()
                ->whereKey($removedRequests->values()->all())
                ->where('status', PurchaseRequest::STATUS_QUOTED)
                ->get()
                ->each->update(['status' => PurchaseRequest::STATUS_APPROVED]);

            return true;
        });

        if ($result !== true) {
            return redirect()
                ->route('purchase-quotation-requests.show', $purchaseQuotationRequest->id_purchase_quotation_request)
                ->with('error', $result);
        }

        return redirect()
            ->route('purchase-quotation-requests.show', $purchaseQuotationRequest->id_purchase_quotation_request)
            ->with('success', 'Productos de la solicitud de cotización actualizados.');
    }

    /**
     * Reparte la nueva cantidad total de un producto entre sus líneas de solicitudes de
     * compra, en proporción a lo que tenía cada una (las órdenes se generan por sucursal y
     * bodega de cada solicitud). La última recibe el resto del redondeo; una línea que queda
     * en cero se quita.
     */
    private function distributeQuantity(Collection $lines, float $total): void
    {
        $current = $lines->sum(fn ($line) => (float) $line->quantity);

        if (abs($total - $current) < 0.00005) {
            return;
        }

        $remaining = $total;

        foreach ($lines->values() as $i => $line) {
            $quantity = $i === $lines->count() - 1
                ? round($remaining, 4)
                : round($total * (float) $line->quantity / $current, 4);
            $remaining -= $quantity;

            if ($quantity > 0) {
                $line->update(['quantity' => $quantity]);
            } else {
                $line->delete();
            }
        }
    }

    /**
     * Vista para imprimir o guardar como PDF que se envía a los proveedores para que coticen
     * los productos elegidos, sin precios.
     */
    public function generatePdf(Request $request, PurchaseQuotationRequest $purchaseQuotationRequest)
    {
        Gate::authorize('purchase_quotation_requests.pdf');

        $keys = array_map('strval', (array) $request->input('lines', []));

        $quotationRequest = $purchaseQuotationRequest->load([
            'details.product',
            'details.unit',
            'details.purchaseRequestDetail.purchaseRequest.branch',
            'branch',
        ]);

        $lines = $quotationRequest->quotationLines()->whereIn('key', $keys)->values();

        if ($lines->isEmpty()) {
            return redirect()
                ->route('purchase-quotation-requests.show', $purchaseQuotationRequest->id_purchase_quotation_request)
                ->with('error', 'Elija al menos un producto para el PDF.');
        }

        // Empresa que solicita la cotización: la del destino o la de sus solicitudes de compra
        $branch = $quotationRequest->branch ?? $quotationRequest->purchaseRequests->first()?->branch;
        $company = $branch ? Company::queryAllBranches()->find($branch->id_company) : null;

        return view('purchase_quotation_requests.pdf', compact('quotationRequest', 'lines', 'company'));
    }

    /**
     * Muestra el detalle completo de una solicitud de cotización.
     */
    public function show(int $id)
    {
        Gate::authorize('purchase_quotation_requests.ver');

        $quotationRequest = PurchaseQuotationRequest::with([
            'branch',
            'warehouse',
            'details.product',
            'details.unit',
            'details.purchaseRequestDetail.purchaseRequest.branch',
            'details.purchaseRequestDetail.purchaseRequest.warehouse',
            'details.purchaseRequestDetail.purchaseRequest.user',
        ])->findOrFail($id);

        // Una línea por producto y unidad, con el total de todas las solicitudes de compra
        $lines = $quotationRequest->quotationLines();

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get();

        $expenseTypes = ExpenseType::where('is_active', true)
            ->orderBy('name')
            ->get();

        $supplierQuotations = PurchaseQuotation::where('id_purchase_quotation_request', $id)
            ->with(['supplier', 'details.product', 'details.unit', 'expenses.expenseType'])
            ->orderByDesc('created_at')
            ->get();

        // Órdenes de compra que ya salieron de esta adjudicación
        $generatedOrders = PurchaseOrder::whereIn('id_purchase_quotation', $supplierQuotations->pluck('id_purchase_quotation'))
            ->with(['supplier', 'branch', 'warehouse'])
            ->orderBy('id_purchase_order')
            ->get();

        $purchaseQuotationRequest = $quotationRequest;

        // En borrador se pueden cambiar sus productos
        $editable = $quotationRequest->isDraft() && Gate::allows('purchase_quotation_requests.editar');

        return view('purchase_quotation_requests.show', [
            ...compact(
                'quotationRequest',
                'purchaseQuotationRequest',
                'lines',
                'suppliers',
                'expenseTypes',
                'supplierQuotations',
                'generatedOrders',
                'editable'
            ),
            ...($editable ? $this->productFormData() : []),
        ]);
    }

    /**
     * Adjudica cada producto a la oferta de un proveedor: todo a uno solo, o unos
     * productos a uno y otros a otro. Cada línea de la solicitud queda apuntando a la
     * línea ganadora; las ofertas con algún producto ganado quedan aprobadas (de cada una
     * sale una orden de compra con lo adjudicado) y las demás, rechazadas. Los productos
     * que ningún proveedor cotizó quedan sin adjudicar y no pasan a orden de compra.
     */
    public function award(Request $request, PurchaseQuotationRequest $purchaseQuotationRequest)
    {
        Gate::authorize('purchase_quotation_requests.seleccionar_cotizacion');

        $awards = (array) $request->input('awards', []);

        $result = DB::transaction(function () use ($purchaseQuotationRequest, $awards) {
            // Se bloquea la solicitud para que no se adjudique dos veces a la vez
            $quotationRequest = PurchaseQuotationRequest::whereKey($purchaseQuotationRequest->getKey())
                ->lockForUpdate()
                ->with([
                    'details.product',
                    'details.unit',
                    'details.purchaseRequestDetail.purchaseRequest',
                ])
                ->firstOrFail();

            if ($quotationRequest->isAwarded()) {
                return 'Esta solicitud de cotización ya fue adjudicada.';
            }

            // Líneas de las ofertas de esta solicitud (no de otra)
            $offerDetails = PurchaseQuotationDetail::whereHas('quotation', fn ($q) => $q
                ->where('id_purchase_quotation_request', $quotationRequest->id_purchase_quotation_request))
                ->get()
                ->keyBy('id_purchase_quotation_detail');

            // Cada producto cotizado debe adjudicarse a una línea de oferta del mismo producto y unidad
            $selected = [];
            foreach ($quotationRequest->quotationLines() as $line) {
                $matchesLine = fn ($offerDetail) => (int) $offerDetail->id_product === (int) $line->product?->id_product
                    && ($offerDetail->id_unit === null || (int) $offerDetail->id_unit === (int) $line->unit?->id_unit);

                if (! $offerDetails->contains($matchesLine)) {
                    continue;
                }

                $offerDetail = $offerDetails->get((int) ($awards[$line->key] ?? 0));

                if (! $offerDetail || ! $matchesLine($offerDetail)) {
                    return 'Elija el proveedor que gana "'.($line->product?->name ?? 'producto').'".';
                }

                $selected[$line->key] = $offerDetail;
            }

            if ($selected === []) {
                return 'Ningún proveedor ha cotizado los productos de esta solicitud.';
            }

            foreach ($quotationRequest->details as $detail) {
                if (isset($selected[$detail->lineKey()])) {
                    $detail->update([
                        'id_purchase_quotation_detail' => $selected[$detail->lineKey()]->id_purchase_quotation_detail,
                    ]);
                }
            }

            // Ofertas con algún producto ganado: aprobadas; las demás, rechazadas (una por una,
            // para que cada cambio quede en la bitácora)
            $winners = collect($selected)->pluck('id_purchase_quotation')->unique();

            PurchaseQuotation::where('id_purchase_quotation_request', $quotationRequest->id_purchase_quotation_request)
                ->get()
                ->each(fn ($quotation) => $quotation->update([
                    'status' => $winners->contains($quotation->id_purchase_quotation) ? 'approved' : 'rejected',
                ]));

            // La cotización asociada solo existe cuando un único proveedor gana todo
            $quotationRequest->update([
                'id_purchase_quotation' => $winners->count() === 1 ? $winners->first() : null,
            ]);

            return true;
        });

        if ($result !== true) {
            return redirect()
                ->route('purchase-quotation-requests.show', $purchaseQuotationRequest->id_purchase_quotation_request)
                ->with('error', $result);
        }

        return redirect()
            ->route('purchase-quotation-requests.show', $purchaseQuotationRequest->id_purchase_quotation_request)
            ->with('success', 'Adjudicación guardada. Cada proveedor ganador ya puede pasar a orden de compra con sus productos.');
    }

    /**
     * Genera en borrador las órdenes de compra de la adjudicación: una por cada proveedor
     * ganador y cada sucursal/bodega que pidió sus productos, con las cantidades de cada
     * solicitud. Los gastos adicionales de cada oferta se reparten entre sus órdenes según
     * el subtotal de cada una.
     */
    public function generateOrders(PurchaseQuotationRequest $purchaseQuotationRequest, PurchaseOrderService $service)
    {
        Gate::authorize('purchase_orders.crear');

        $result = DB::transaction(function () use ($purchaseQuotationRequest, $service) {
            // Se bloquea la solicitud para que las órdenes no se generen dos veces a la vez
            $quotationRequest = PurchaseQuotationRequest::whereKey($purchaseQuotationRequest->getKey())
                ->lockForUpdate()
                ->with([
                    'details.purchaseRequestDetail.purchaseRequest',
                    'details.quotationDetail.quotation.expenses',
                ])
                ->firstOrFail();

            if (! $quotationRequest->isAwarded()) {
                return 'Primero adjudique cada producto a un proveedor.';
            }

            if ($quotationRequest->generatedOrders()->exists()) {
                return 'Las órdenes de compra de esta solicitud ya se generaron.';
            }

            $orders = 0;

            // Destino de cada línea: el de su solicitud de compra o, si no tiene, el que se
            // eligió en la solicitud de cotización
            $destination = fn ($line) => $line->purchaseRequestDetail
                ? [$line->purchaseRequestDetail->purchaseRequest->id_branch, $line->purchaseRequestDetail->purchaseRequest->id_warehouse]
                : [$quotationRequest->id_branch, $quotationRequest->id_warehouse];

            $byQuotation = $quotationRequest->details
                ->filter(fn ($line) => $line->quotationDetail)
                ->groupBy(fn ($line) => $line->quotationDetail->id_purchase_quotation);

            foreach ($byQuotation as $lines) {
                $quotation = $lines->first()->quotationDetail->quotation;

                // Una orden por sucursal y bodega de destino
                $groups = $lines
                    ->groupBy(fn ($line) => implode('|', $destination($line)))
                    ->values()
                    ->map(function ($groupLines) use ($destination) {
                        $products = $groupLines
                            ->groupBy('id_purchase_quotation_detail')
                            ->map(function ($sameLine) {
                                $offerLine = $sameLine->first()->quotationDetail;
                                $quantity = $sameLine->sum(fn ($line) => (float) $line->quantity);

                                return [
                                    'id_product' => $offerLine->id_product,
                                    'quantity' => $quantity,
                                    'id_unit' => $offerLine->id_unit ?? $sameLine->first()->id_unit,
                                    'unit_price' => (float) $offerLine->unit_price,
                                    // Descuento de la oferta, en proporción a la cantidad de esta orden
                                    'discount' => (float) $offerLine->quantity > 0
                                        ? round((float) $offerLine->discount * $quantity / (float) $offerLine->quantity, 2)
                                        : 0,
                                    'tax_rate' => (float) $offerLine->tax_rate,
                                ];
                            })
                            ->values()
                            ->all();

                        [$branchId, $warehouseId] = $destination($groupLines->first());

                        return [
                            'products' => $products,
                            'subtotal' => collect($products)->sum(fn ($p) => $p['quantity'] * $p['unit_price'] - $p['discount']),
                            'id_branch' => $branchId,
                            'id_warehouse' => $warehouseId,
                            'requests' => $groupLines
                                ->map(fn ($line) => $line->purchaseRequestDetail?->purchaseRequest)
                                ->filter()
                                ->unique('id_purchase_request')
                                ->values(),
                        ];
                    });

                // Gastos de la oferta repartidos según el subtotal; la última orden recibe el
                // resto para no perder centavos al redondear
                $totalSubtotal = $groups->sum('subtotal');
                $expensesByGroup = $groups->map(fn () => [])->all();
                foreach ($quotation->expenses as $expense) {
                    $remaining = (float) $expense->amount;
                    foreach ($groups as $i => $group) {
                        $share = $totalSubtotal > 0 ? $group['subtotal'] / $totalSubtotal : 1 / $groups->count();
                        $amount = $i === $groups->count() - 1 ? round($remaining, 2) : round((float) $expense->amount * $share, 2);
                        $remaining -= $amount;

                        if ($amount > 0) {
                            $expensesByGroup[$i][] = [
                                'id_expense_type' => $expense->id_expense_type,
                                'description' => $expense->description,
                                'amount' => $amount,
                            ];
                        }
                    }
                }

                foreach ($groups as $i => $group) {
                    $codes = $group['requests']->pluck('purchase_request_code')->join(', ');

                    $service->crear([
                        'id_supplier' => $quotation->id_supplier,
                        'id_branch' => $group['id_branch'],
                        'id_warehouse' => $group['id_warehouse'],
                        'id_purchase_quotation' => $quotation->id_purchase_quotation,
                        'order_date' => now(),
                        'expected_date' => $quotation->delivery_days
                            ? now()->addDays((int) $quotation->delivery_days)
                            : ($group['requests']->pluck('required_date')->filter()->min() ?? now()->addDays(7)),
                        'currency' => $quotation->currency ?: Moneda::PREDETERMINADA,
                        'payment_terms' => $quotation->payment_terms ?: 'Según cotización',
                        'notes' => 'Generada de la solicitud de cotización #'
                            .str_pad($quotationRequest->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT)
                            .($codes !== '' ? " para {$codes}" : '').'.',
                        'products' => $group['products'],
                        'expenses' => $expensesByGroup[$i],
                    ]);
                    $orders++;
                }
            }

            return $orders;
        });

        if (is_string($result)) {
            return redirect()
                ->route('purchase-quotation-requests.show', $purchaseQuotationRequest->id_purchase_quotation_request)
                ->with('error', $result);
        }

        return redirect()
            ->route('purchase-quotation-requests.show', $purchaseQuotationRequest->id_purchase_quotation_request)
            ->with('success', "Se generaron {$result} órdenes de compra en borrador. Revíselas y emítalas en Órdenes de Compra.");
    }
}
