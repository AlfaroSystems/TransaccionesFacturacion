<?php
namespace App\Http\Controllers;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Solicitudes de compra: cada sucursal crea las suyas y las envía al departamento de
 * compras (la sucursal marcada como tal, p. ej. Casa Matriz), que las aprueba, las
 * devuelve a la sucursal o las rechaza; de las aprobadas se genera la solicitud de
 * cotización. Una vez enviada, nadie puede editarla ni eliminarla (ver PurchaseRequest).
 */
class PurchaseRequestController extends Controller
{
    /**
     * Lista de solicitudes y carga los datos necesarios
     * para los modales de creación y edición.
     */
    public function index(Request $request)
    {
        Gate::authorize('purchase_requests.ver');

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
                    'ilike',
                    "%{$search}%"
                )
                ->orWhere(
                    'justification',
                    'ilike',
                    "%{$search}%"
                );
            });
        }

        $purchaseRequests = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Conteo por estado de todas las solicitudes visibles (no solo de la página actual)
        $statusCounts = PurchaseRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Sucursales y bodegas para los formularios. El departamento de compras puede
        // crear solicitudes para cualquier sucursal de su empresa.
        $isPurchasingDepartment = BranchAccess::isPurchasingDepartment();

        $branchQuery = $isPurchasingDepartment
            ? Branch::queryAllBranches()->where('id_company', BranchAccess::companyId())
            : Branch::query();

        $branches = $branchQuery->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = ($isPurchasingDepartment ? Warehouse::queryAllBranches() : Warehouse::query())
            ->whereIn('id_branch', $branches->pluck('id_branch'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Sucursal propia: las solicitudes para otra sucursal se envían al crearlas
        $ownBranchId = BranchAccess::isUnrestricted() ? null : BranchAccess::branchId();

        return view('purchase_requests.index', compact(
            'purchaseRequests',
            'statusCounts',
            'branches',
            'warehouses',
            'products',
            'units',
            'ownBranchId'
        ));
    }

    /**
     * Redirige al listado y abre el modal de creación.
     */
    public function create()
    {
        Gate::authorize('purchase_requests.crear');

        return redirect()
            ->route('purchase-requests.index')
            ->with('open_create_modal', true);
    }

    /**
     * Guarda una solicitud con sus detalles, como borrador o ya enviada a compras.
     */
    public function store(StorePurchaseRequest $request)
    {
        Gate::authorize('purchase_requests.crear');

        $validated = $request->validated();

        // Una solicitud para otra sucursal (la crea el departamento de compras) se envía
        // directamente: como borrador solo la vería y editaría esa sucursal
        $send = $request->boolean('send') || $this->isForAnotherBranch($validated['id_branch']);

        if ($send) {
            Gate::authorize('purchase_requests.enviar');
        }

        DB::transaction(function () use ($validated, $send) {

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

                'status' => $send
                    ? PurchaseRequest::STATUS_SENT
                    : PurchaseRequest::STATUS_DRAFT,

                'sent_at' => $send ? now() : null,

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
                $send
                    ? 'Solicitud de compra creada y enviada al departamento de compras.'
                    : 'Solicitud de compra guardada como borrador.'
            );
    }

    /**
     * Redirige al listado para mostrar el detalle en modal.
     */
    public function show(PurchaseRequest $purchaseRequest)
    {
        Gate::authorize('purchase_requests.ver');

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
        Gate::authorize('purchase_requests.editar');

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.index')
                ->with(
                    'error',
                    'Solo se pueden editar solicitudes en borrador o devueltas.'
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
        Gate::authorize('purchase_requests.editar');

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.index')
                ->with(
                    'error',
                    'Solo se pueden modificar solicitudes en borrador o devueltas.'
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
            // para registrar nuevamente el maestro-detalle
            // (uno por uno, para que cada borrado quede en la bitácora).
            $purchaseRequest->details()->get()->each->delete();

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
        Gate::authorize('purchase_requests.eliminar');

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.index')
                ->with(
                    'error',
                    'Solo se pueden eliminar solicitudes en borrador o devueltas.'
                );
        }

        if (DB::table('purchase_quotation_requests')->where('id_purchase_request', $purchaseRequest->id_purchase_request)->exists()) {
            return redirect()
                ->route('purchase-requests.index')
                ->with(
                    'error',
                    'No se puede eliminar una solicitud que ya tiene solicitudes de cotización.'
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
     * La sucursal envía la solicitud al departamento de compras.
     */
    public function send(PurchaseRequest $purchaseRequest)
    {
        Gate::authorize('purchase_requests.enviar');

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.index')
                ->with('error', 'Esta solicitud ya fue enviada.');
        }

        $purchaseRequest->update([
            'status' => PurchaseRequest::STATUS_SENT,
            'sent_at' => now(),
            'status_reason' => null,
        ]);

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Solicitud enviada al departamento de compras.');
    }

    /**
     * Compras aprueba la solicitud enviada: queda lista para generar su solicitud de
     * cotización (y ya no puede devolverse ni rechazarse).
     */
    public function approve(PurchaseRequest $purchaseRequest)
    {
        Gate::authorize('purchase_requests.aprobar');

        if ($purchaseRequest->status !== PurchaseRequest::STATUS_SENT) {
            return redirect()
                ->route('purchase-requests.index')
                ->with('error', 'Solo se pueden aprobar solicitudes enviadas.');
        }

        $purchaseRequest->update([
            'status' => PurchaseRequest::STATUS_APPROVED,
            'status_reason' => null,
        ]);

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Solicitud aprobada; ya puede generar su solicitud de cotización.');
    }

    /**
     * Compras devuelve la solicitud a la sucursal para que la corrija y la reenvíe.
     */
    public function returnToBranch(Request $request, PurchaseRequest $purchaseRequest)
    {
        return $this->review(
            $request,
            $purchaseRequest,
            PurchaseRequest::STATUS_RETURNED,
            'Solicitud devuelta a la sucursal.'
        );
    }

    /**
     * Compras rechaza la solicitud de forma definitiva.
     */
    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        return $this->review(
            $request,
            $purchaseRequest,
            PurchaseRequest::STATUS_REJECTED,
            'Solicitud rechazada.'
        );
    }

    /**
     * Devuelve o rechaza una solicitud enviada; el motivo es obligatorio.
     */
    private function review(
        Request $request,
        PurchaseRequest $purchaseRequest,
        string $status,
        string $message
    ) {
        Gate::authorize('purchase_requests.devolver');

        if ($purchaseRequest->status !== PurchaseRequest::STATUS_SENT) {
            return redirect()
                ->route('purchase-requests.index')
                ->with('error', 'Solo se pueden devolver o rechazar solicitudes enviadas.');
        }

        $reason = trim((string) $request->input('reason'));

        if ($reason === '' || mb_strlen($reason) > 1000) {
            return redirect()
                ->route('purchase-requests.index', ['show' => $purchaseRequest->id_purchase_request])
                ->with('error', 'Debe indicar el motivo (máximo 1000 caracteres).');
        }

        $purchaseRequest->update([
            'status' => $status,
            'status_reason' => $reason,
        ]);

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', $message);
    }

    /**
     * La solicitud es para una sucursal distinta de la del usuario.
     */
    private function isForAnotherBranch(int|string $branchId): bool
    {
        return ! BranchAccess::isUnrestricted()
            && (int) $branchId !== BranchAccess::branchId();
    }

    /**
     * Genera código correlativo:
     * REQ-2026-0001
     */
    private function generatePurchaseRequestCode(): string
    {
        $year = now()->format('Y');

        $prefix = "REQ-{$year}-";

        // El correlativo es global: debe considerar las solicitudes de todas las sucursales
        $lastRequest = PurchaseRequest::queryAllBranches()->where(
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
