<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseQuotationRequest;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseQuotationRequestDetail;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseQuotationRequestController extends Controller
{
    /**
     * Muestra el listado de solicitudes de cotización.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = PurchaseQuotationRequest::with([
            'purchaseRequest',
        ]);

        if ($search) {
            $query->whereHas('purchaseRequest', function ($sub) use ($search) {
                $sub->where('purchase_request_code', 'like', "%{$search}%")
                    ->orWhere('justification', 'like', "%{$search}%");
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
     * Muestra el formulario para crear una solicitud de cotización.
     */
    public function create()
    {
        $approvedRequests = PurchaseRequest::where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return view('purchase_quotation_requests.create', compact('approvedRequests'));
    }

    /**
     * Endpoint AJAX: Devuelve las Solicitudes de Compra en estado approved.
     */
    public function getApprovedPurchaseRequests(): JsonResponse
    {
        $approvedRequests = PurchaseRequest::where('status', 'approved')
            ->select([
                'id_purchase_request',
                'purchase_request_code',
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
        $details = PurchaseRequestDetail::where('id_purchase_request', $id)
            ->with([
                'product:id,name,sku,barcode',
                'unit:id,name,abbreviation',
            ])
            ->get();

        return response()->json($details);
    }

    /**
     * Almacena la solicitud de cotización y sus detalles.
     */
    public function store(StorePurchaseQuotationRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $purchaseRequestId = $validated['id_purchase_request'];
            $items = $validated['items'];

            $quotationRequest = PurchaseQuotationRequest::create([
                'id_purchase_request' => $purchaseRequestId,
                'id_purchase_quotation' => null,
            ]);

            foreach ($items as $item) {
                PurchaseQuotationRequestDetail::create([
                    'id_purchase_request_detail' => $item['id_purchase_request_detail'],
                    'id_purchase_quotation_detail' => null,
                    'quantity' => $item['quantity'],
                ]);
            }
        });

        return redirect()
            ->route('purchase-quotation-requests.index')
            ->with('success', 'Se generó exitosamente la solicitud de cotización.');
    }

    /**
     * Muestra el detalle completo de una solicitud de cotización.
     */
    public function show(int $id)
    {
        $quotationRequest = PurchaseQuotationRequest::with([
            'purchaseRequest.branch',
            'purchaseRequest.warehouse',
            'purchaseRequest.user',
            'purchaseRequest.details.product',
            'purchaseRequest.details.unit',
        ])->findOrFail($id);

        $detailIds = $quotationRequest->purchaseRequest->details->pluck('id_purchase_request_detail');

        $details = PurchaseQuotationRequestDetail::whereIn('id_purchase_request_detail', $detailIds)
            ->with(['purchaseRequestDetail.product', 'purchaseRequestDetail.unit'])
            ->get();

        return view('purchase_quotation_requests.show', compact('quotationRequest', 'details'));
    }
}
