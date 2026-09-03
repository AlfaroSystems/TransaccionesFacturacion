<?php
namespace App\Http\Controllers;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseRequestController extends Controller
{
    /**
     * Lista de solicitudes y carga los datos necesarios
     * para los modales de creación y edición.
     */
    public function index(Request $request)
    {
        $query = PurchaseRequest::with([
            'branch',
            'warehouse',
            'user',
            'details.product',
            'details.unit',
        ]);

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Búsqueda por código o justificación
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'purchase_request_code',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'justification',
                    'like',
                    "%{$search}%"
                );
            });
        }

        $purchaseRequests = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Catálogos necesarios para los formularios
        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('purchase_requests.index', compact(
            'purchaseRequests',
            'branches',
            'warehouses',
            'products',
            'units'
        ));
    }

    /**
     * Redirige al listado y abre el modal de creación.
     */
    public function create()
    {
        return redirect()
            ->route('purchase-requests.index')
            ->with('open_create_modal', true);
    }

    /**
     * Guarda una solicitud con sus detalles.
     */
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {

            $purchaseRequest = PurchaseRequest::create([
                'uuid' => (string) Str::uuid(),

                'purchase_request_code' =>
                    $this->generatePurchaseRequestCode(),

                'id_branch' => $validated['id_branch'],
                'id_warehouse' => $validated['id_warehouse'],

                'id_user' => auth()->id(),

                'request_date' => $validated['request_date'],
                'required_date' => $validated['required_date'],

                'justification' => $validated['justification'],

                'status' => 'draft',

                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['details'] as $detail) {
                $purchaseRequest->details()->create([
                    'id_product' => $detail['id_product'],
                    'quantity' => $detail['quantity'],
                    'id_unit' => $detail['id_unit'],

                    'description' =>
                        $detail['description'] ?? null,

                    'notes' =>
                        $detail['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('purchase-requests.index')
            ->with(
                'success',
                'Solicitud de compra creada correctamente.'
            );
    }

    /**
     * Redirige al listado para mostrar el detalle en modal.
     */
    public function show(PurchaseRequest $purchaseRequest)
    {
        return redirect()
            ->route('purchase-requests.index', [
                'show' =>
                    $purchaseRequest->id_purchase_request,
            ]);
    }

    /**
     * Redirige al listado para editar mediante modal.
     */
    public function edit(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'draft') {
            return redirect()
                ->route('purchase-requests.index')
                ->with(
                    'error',
                    'Solo se pueden editar solicitudes en estado borrador.'
                );
        }

        return redirect()
            ->route('purchase-requests.index', [
                'edit' =>
                    $purchaseRequest->id_purchase_request,
            ]);
    }

    /**
     * Actualiza una solicitud.
     */
    public function update(
        StorePurchaseRequest $request,
        PurchaseRequest $purchaseRequest
    ) {
        if ($purchaseRequest->status !== 'draft') {
            return redirect()
                ->route('purchase-requests.index')
                ->with(
                    'error',
                    'Solo se pueden modificar solicitudes en estado borrador.'
                );
        }

        $validated = $request->validated();

        DB::transaction(function () use (
            $validated,
            $purchaseRequest
        ) {
            $purchaseRequest->update([
                'id_branch' => $validated['id_branch'],
                'id_warehouse' => $validated['id_warehouse'],

                'request_date' =>
                    $validated['request_date'],

                'required_date' =>
                    $validated['required_date'],

                'justification' =>
                    $validated['justification'],

                'notes' =>
                    $validated['notes'] ?? null,
            ]);

            // Eliminamos los detalles anteriores
            // para registrar nuevamente el maestro-detalle.
            $purchaseRequest->details()->delete();

            foreach ($validated['details'] as $detail) {
                $purchaseRequest->details()->create([
                    'id_product' =>
                        $detail['id_product'],

                    'quantity' =>
                        $detail['quantity'],

                    'id_unit' =>
                        $detail['id_unit'],

                    'description' =>
                        $detail['description'] ?? null,

                    'notes' =>
                        $detail['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('purchase-requests.index')
            ->with(
                'success',
                'Solicitud actualizada correctamente.'
            );
    }

    /**
     * Elimina una solicitud.
     */
    public function destroy(
        PurchaseRequest $purchaseRequest
    ) {
        if ($purchaseRequest->status !== 'draft') {
            return redirect()
                ->route('purchase-requests.index')
                ->with(
                    'error',
                    'Solo se pueden eliminar solicitudes en estado borrador.'
                );
        }

        $purchaseRequest->delete();

        return redirect()
            ->route('purchase-requests.index')
            ->with(
                'success',
                'Solicitud eliminada correctamente.'
            );
    }

    /**
     * Actualiza el estado de una solicitud.
     */
    public function updateStatus(
        Request $request,
        PurchaseRequest $purchaseRequest
    ) {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:draft,pending,approved,rejected',
            ],
        ]);

        $purchaseRequest->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('purchase-requests.index', [
                'show' =>
                    $purchaseRequest->id_purchase_request,
            ])
            ->with(
                'success',
                'Estado de la solicitud actualizado correctamente.'
            );
    }

    /**
     * Genera código correlativo:
     * REQ-2026-0001
     */
    private function generatePurchaseRequestCode(): string
    {
        $year = now()->format('Y');

        $prefix = "REQ-{$year}-";

        $lastRequest = PurchaseRequest::where(
            'purchase_request_code',
            'like',
            "{$prefix}%"
        )
            ->orderByDesc('purchase_request_code')
            ->first();

        if (!$lastRequest) {
            $nextNumber = 1;
        } else {
            $lastNumber = (int) substr(
                $lastRequest->purchase_request_code,
                -4
            );

            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad(
            $nextNumber,
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}