<?php

namespace App\Http\Controllers;
use App\Http\Requests\StorePurchaseQuotationRequest;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\ExpenseType;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseQuotationDetail;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;

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

        return view('purchase_quotation_requests.index', compact('quotationRequests', 'metrics', 'search'));
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
     * Crea la solicitud de cotización con una o varias solicitudes de compra aprobadas,
     * que se cotizan completas.
     */
    public function store(StorePurchaseQuotationRequest $request)
    {
        Gate::authorize('purchase_quotation_requests.crear');

        $purchaseRequestIds = $request->validated()['purchase_requests'];

        $created = DB::transaction(function () use ($purchaseRequestIds) {
            // Se bloquean las solicitudes de compra para que dos usuarios no las coticen a la vez
            $purchaseRequests = PurchaseRequest::whereKey($purchaseRequestIds)
                ->lockForUpdate()
                ->with('details')
                ->get();

            $allApproved = $purchaseRequests->count() === count($purchaseRequestIds)
                && $purchaseRequests->every(fn ($pr) => $pr->status === PurchaseRequest::STATUS_APPROVED);

            if (! $allApproved) {
                return false;
            }

            PurchaseQuotationRequest::createFromPurchaseRequests($purchaseRequests);

            // Quedan en cotización: ya no pueden devolverse, rechazarse ni cotizarse otra vez
            $purchaseRequests->each->update([
                'status' => PurchaseRequest::STATUS_QUOTED,
            ]);

            return true;
        });

        if (! $created) {
            return redirect()
                ->route('purchase-quotation-requests.index')
                ->with('error', 'Alguna de las solicitudes de compra ya no está disponible para cotizar.');
        }

        return redirect()
            ->route('purchase-quotation-requests.index')
            ->with('success', 'Se generó exitosamente la solicitud de cotización.');
    }

    /**
     * Muestra el detalle completo de una solicitud de cotización.
     */
    public function show(int $id)
    {
        Gate::authorize('purchase_quotation_requests.ver');

        $quotationRequest = PurchaseQuotationRequest::with([
            'details.purchaseRequestDetail.product',
            'details.purchaseRequestDetail.unit',
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

        return view('purchase_quotation_requests.show', compact(
            'quotationRequest',
            'purchaseQuotationRequest',
            'lines',
            'suppliers',
            'expenseTypes',
            'supplierQuotations',
            'generatedOrders'
        ));
    }

    /**
     * Adjudica cada producto a la oferta de un proveedor: todo a uno solo, o unos
     * productos a uno y otros a otro. Cada línea de la solicitud queda apuntando a la
     * línea ganadora; las ofertas con algún producto ganado quedan aprobadas (de cada una
     * sale una orden de compra con lo adjudicado) y las demás, rechazadas.
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
                    'details.purchaseRequestDetail.product',
                    'details.purchaseRequestDetail.unit',
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

            // Cada producto debe adjudicarse a una línea de oferta del mismo producto y unidad
            $selected = [];
            foreach ($quotationRequest->quotationLines() as $line) {
                $offerDetail = $offerDetails->get((int) ($awards[$line->key] ?? 0));

                $matches = $offerDetail
                    && (int) $offerDetail->id_product === (int) $line->product?->id_product
                    && ($offerDetail->id_unit === null || (int) $offerDetail->id_unit === (int) $line->unit?->id_unit);

                if (! $matches) {
                    return 'Elija el proveedor que gana "' . ($line->product?->name ?? 'producto') . '".';
                }

                $selected[$line->key] = $offerDetail;
            }

            foreach ($quotationRequest->details as $detail) {
                $key = $detail->purchaseRequestDetail->id_product . '-' . $detail->purchaseRequestDetail->id_unit;
                $detail->update([
                    'id_purchase_quotation_detail' => $selected[$key]->id_purchase_quotation_detail,
                ]);
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

            $byQuotation = $quotationRequest->details
                ->filter(fn ($line) => $line->quotationDetail)
                ->groupBy(fn ($line) => $line->quotationDetail->id_purchase_quotation);

            foreach ($byQuotation as $lines) {
                $quotation = $lines->first()->quotationDetail->quotation;

                // Una orden por sucursal y bodega de destino (las de la solicitud de compra)
                $groups = $lines
                    ->groupBy(fn ($line) => $line->purchaseRequestDetail->purchaseRequest->id_branch
                        . '|' . $line->purchaseRequestDetail->purchaseRequest->id_warehouse)
                    ->values()
                    ->map(function ($groupLines) {
                        $products = $groupLines
                            ->groupBy('id_purchase_quotation_detail')
                            ->map(function ($sameLine) {
                                $offerLine = $sameLine->first()->quotationDetail;
                                $quantity = $sameLine->sum(fn ($line) => (float) $line->quantity);

                                return [
                                    'id_product' => $offerLine->id_product,
                                    'quantity'   => $quantity,
                                    'id_unit'    => $offerLine->id_unit ?? $sameLine->first()->purchaseRequestDetail->id_unit,
                                    'unit_price' => (float) $offerLine->unit_price,
                                    // Descuento de la oferta, en proporción a la cantidad de esta orden
                                    'discount'   => (float) $offerLine->quantity > 0
                                        ? round((float) $offerLine->discount * $quantity / (float) $offerLine->quantity, 2)
                                        : 0,
                                    'tax_rate'   => (float) $offerLine->tax_rate,
                                ];
                            })
                            ->values()
                            ->all();

                        return [
                            'products' => $products,
                            'subtotal' => collect($products)->sum(fn ($p) => $p['quantity'] * $p['unit_price'] - $p['discount']),
                            'requests' => $groupLines->map(fn ($line) => $line->purchaseRequestDetail->purchaseRequest)->unique('id_purchase_request')->values(),
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
                                'description'     => $expense->description,
                                'amount'          => $amount,
                            ];
                        }
                    }
                }

                foreach ($groups as $i => $group) {
                    $destination = $group['requests']->first();
                    $codes = $group['requests']->pluck('purchase_request_code')->join(', ');

                    $service->crear([
                        'id_supplier'           => $quotation->id_supplier,
                        'id_branch'             => $destination->id_branch,
                        'id_warehouse'          => $destination->id_warehouse,
                        'id_purchase_quotation' => $quotation->id_purchase_quotation,
                        'order_date'            => now(),
                        'expected_date'         => $quotation->delivery_days
                            ? now()->addDays((int) $quotation->delivery_days)
                            : ($group['requests']->pluck('required_date')->filter()->min() ?? now()->addDays(7)),
                        'currency'              => $quotation->currency ?: 'USD',
                        'payment_terms'         => $quotation->payment_terms ?: 'Según cotización',
                        'notes'                 => 'Generada de la solicitud de cotización #'
                            . str_pad($quotationRequest->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT)
                            . " para {$codes}.",
                        'products'              => $group['products'],
                        'expenses'              => $expensesByGroup[$i],
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
