<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseQuotationRequest;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseQuotationRequestDetail;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseQuotationRequestController extends Controller
{
    /**
     * Muestra el listado de solicitudes de cotización a proveedores.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = PurchaseQuotationRequest::with([
            'purchaseRequest',
            'supplier',
            'details.purchaseRequestDetail.product',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('purchaseRequest', function ($sub) use ($search) {
                    $sub->where('purchase_request_code', 'ilike', "%{$search}%")
                        ->orWhere('justification', 'ilike', "%{$search}%");
                })->orWhereHas('supplier', function ($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $quotationRequests = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Métricas rápidas para tarjetas de resumen
        $metrics = [
            'total' => PurchaseQuotationRequest::count(),
            'pending' => PurchaseQuotationRequest::where('status', 'pending')->count(),
            'sent' => PurchaseQuotationRequest::where('status', 'sent')->count(),
            'quoted' => PurchaseQuotationRequest::where('status', 'quoted')->count(),
        ];

        return view('purchase_quotation_requests.index', compact('quotationRequests', 'metrics', 'search', 'status'));
    }

    /**
     * Muestra el formulario para convocar a proveedores.
     */
    public function create()
    {
        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get();

        $approvedRequests = PurchaseRequest::where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return view('purchase_quotation_requests.create', compact('suppliers', 'approvedRequests'));
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
     * Almacena las invitaciones de cotización formal para cada proveedor seleccionado.
     */
    public function store(StorePurchaseQuotationRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $purchaseRequestId = $validated['id_purchase_request'];
            $supplierIds = $validated['supplier_ids'];
            $items = $validated['items'];
            $notes = $validated['notes'] ?? null;

            foreach ($supplierIds as $supplierId) {
                // Crear la cabecera de la solicitud de cotización por proveedor
                $quotationRequest = PurchaseQuotationRequest::create([
                    'id_purchase_request' => $purchaseRequestId,
                    'id_supplier' => $supplierId,
                    'id_purchase_quotation' => null,
                    'status' => 'sent',
                    'notes' => $notes,
                ]);

                // Crear los detalles de ítems requeridos
                foreach ($items as $item) {
                    $quotationRequest->details()->create([
                        'id_purchase_request_detail' => $item['id_purchase_request_detail'],
                        'id_purchase_quotation_detail' => null,
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }
        });

        $supplierCount = count($validated['supplier_ids']);

        return redirect()
            ->route('purchase-quotation-requests.index')
            ->with('success', "Se generaron exitosamente {$supplierCount} invitaciones de cotización a proveedores.");
    }

    /**
     * Muestra el detalle completo de una solicitud de cotización formal.
     */
    public function show(int $id)
    {
        $quotationRequest = PurchaseQuotationRequest::with([
            'purchaseRequest.branch',
            'purchaseRequest.warehouse',
            'purchaseRequest.user',
            'supplier',
            'details.purchaseRequestDetail.product',
            'details.purchaseRequestDetail.unit',
        ])->findOrFail($id);

        return view('purchase_quotation_requests.show', compact('quotationRequest'));
    }
}
