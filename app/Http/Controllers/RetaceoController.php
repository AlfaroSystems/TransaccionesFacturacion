<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Purchase;
use App\Models\Retaceo;
use App\Models\Supplier;
use App\Services\RetaceoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RetaceoController extends Controller
{
    public function __construct(private readonly RetaceoService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('retaceos.ver');

        $query = Retaceo::with(['purchase', 'supplier', 'user'])
            ->orderByDesc('id_retaceo');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('retaceo_code', 'ilike', "%{$search}%")
                  ->orWhere('import_policy_number', 'ilike', "%{$search}%")
                  ->orWhere('import_invoice_number', 'ilike', "%{$search}%")
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'ilike', "%{$search}%"))
                  ->orWhereHas('purchase', fn ($pq) => $pq->where('purchase_code', 'ilike', "%{$search}%"));
            });
        }

        if ($request->filled('id_supplier')) {
            $query->where('id_supplier', $request->id_supplier);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('retaceo_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('retaceo_date', '<=', $request->date_to);
        }

        $retaceos = $query->paginate(10)->withQueryString();

        // Métricas
        $totalCount      = Retaceo::count();
        $draftCount      = Retaceo::where('status', 'draft')->count();
        $calculatedCount = Retaceo::whereIn('status', ['calculated', 'applied'])->count();
        $totalCostAmount = Retaceo::where('status', '!=', 'cancelled')->sum('total_cost');

        $suppliers = Supplier::orderBy('name')->get();
        $purchasesForModal = Purchase::where('status', '!=', 'cancelled')
            ->with(['supplier', 'details.product', 'details.unit'])
            ->orderByDesc('id_purchase')
            ->get();

        return view('retaceos.index', compact(
            'retaceos',
            'totalCount',
            'draftCount',
            'calculatedCount',
            'totalCostAmount',
            'suppliers',
            'purchasesForModal'
        ));
    }

    public function create(Request $request)
    {
        Gate::authorize('retaceos.crear');

        $purchases = Purchase::where('status', '!=', 'cancelled')
            ->with(['supplier', 'details.product', 'details.unit'])
            ->orderByDesc('id_purchase')
            ->get();

        $selectedPurchaseId = $request->query('id_purchase');
        $preselectedPurchase = null;
        if ($selectedPurchaseId) {
            $preselectedPurchase = Purchase::with(['supplier', 'details.product', 'details.unit'])->find($selectedPurchaseId);
        }

        $suppliers = Supplier::orderBy('name')->get();

        return view('retaceos.create', compact(
            'purchases',
            'preselectedPurchase',
            'suppliers'
        ));
    }

    public function getPurchaseData($id)
    {
        Gate::authorize('retaceos.ver');

        $purchase = Purchase::with([
            'supplier',
            'details.product',
            'details.unit',
        ])->findOrFail($id);

        return response()->json([
            'id_purchase'             => $purchase->id_purchase,
            'purchase_code'           => $purchase->purchase_code,
            'id_supplier'             => $purchase->id_supplier,
            'supplier_name'           => $purchase->supplier?->name,
            'origin_country'          => $purchase->supplier?->country ?? 'El Salvador',
            'supplier_invoice_number' => $purchase->supplier_invoice_number,
            'supplier_invoice_date'   => $purchase->supplier_invoice_date ? $purchase->supplier_invoice_date->format('Y-m-d') : null,
            'currency'                => $purchase->currency,
            'details'                 => $purchase->details->map(function ($d) {
                $qty = (float) $d->quantity_received;
                if ($qty <= 0) {
                    $qty = (float) $d->quantity_ordered;
                }
                $fob = $qty * (float) $d->unit_price;

                return [
                    'id_purchase_detail' => $d->id_purchase_detail,
                    'id_product'         => $d->id_product,
                    'product_name'       => $d->product?->name ?? 'Producto #'.$d->id_product,
                    'product_code'       => $d->product?->code ?? '',
                    'quantity'           => $qty,
                    'unit_name'          => $d->unit?->name ?? 'Unidad',
                    'unit_price'         => (float) $d->unit_price,
                    'cost_fob'           => round($fob, 4),
                    'dai_amount'         => 0,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('retaceos.crear');

        $validated = $request->validate([
            'id_purchase'                 => ['required', 'exists:purchases,id_purchase'],
            'id_supplier'                 => ['required', 'exists:suppliers,id_supplier'],
            'retaceo_date'                => ['required', 'date'],
            'origin_country'              => ['nullable', 'string', 'max:100'],
            'import_invoice_number'       => ['nullable', 'string', 'max:100'],
            'import_invoice_date'         => ['nullable', 'date'],
            'import_policy_number'        => ['nullable', 'string', 'max:100'],
            'import_policy_date'          => ['nullable', 'date'],
            'total_freight'               => ['nullable', 'numeric', 'min:0'],
            'total_expenses'              => ['nullable', 'numeric', 'min:0'],
            'status'                      => ['required', Rule::in(['draft', 'calculated', 'applied'])],
            'notes'                       => ['nullable', 'string'],
            'details'                     => ['required', 'array', 'min:1'],
            'details.*.id_product'        => ['required', 'exists:products,id'],
            'details.*.id_purchase_detail'=> ['nullable', 'exists:purchase_details,id_purchase_detail'],
            'details.*.quantity'          => ['required', 'numeric', 'min:0.0001'],
            'details.*.cost_fob'          => ['required', 'numeric', 'min:0'],
            'details.*.dai_amount'        => ['nullable', 'numeric', 'min:0'],
        ]);

        $retaceo = $this->service->crear($validated);

        return redirect()
            ->route('retaceos.show', $retaceo->id_retaceo)
            ->with('success', 'Retaceo de importación calculado y registrado exitosamente.');
    }

    public function show(Retaceo $retaceo)
    {
        Gate::authorize('retaceos.ver');

        $retaceo->load([
            'purchase',
            'supplier',
            'user',
            'details.product',
            'details.purchaseDetail',
        ]);

        return view('retaceos.show', compact('retaceo'));
    }

    public function getEditData(Retaceo $retaceo)
    {
        Gate::authorize('retaceos.editar');

        $retaceo->load([
            'purchase',
            'supplier',
            'details.product',
        ]);

        return response()->json([
            'id_retaceo'            => $retaceo->id_retaceo,
            'retaceo_code'          => $retaceo->retaceo_code,
            'id_purchase'           => $retaceo->id_purchase,
            'purchase_code'         => $retaceo->purchase?->purchase_code,
            'id_supplier'           => $retaceo->id_supplier,
            'supplier_name'         => $retaceo->supplier?->name,
            'origin_country'        => $retaceo->origin_country ?? '',
            'retaceo_date'          => $retaceo->retaceo_date ? $retaceo->retaceo_date->format('Y-m-d\TH:i') : '',
            'import_invoice_number' => $retaceo->import_invoice_number ?? '',
            'import_invoice_date'   => $retaceo->import_invoice_date ? $retaceo->import_invoice_date->format('Y-m-d') : '',
            'import_policy_number'  => $retaceo->import_policy_number ?? '',
            'import_policy_date'    => $retaceo->import_policy_date ? $retaceo->import_policy_date->format('Y-m-d') : '',
            'total_freight'         => (float) $retaceo->total_freight,
            'total_expenses'        => (float) $retaceo->total_expenses,
            'status'                => $retaceo->status,
            'notes'                 => $retaceo->notes ?? '',
            'details'               => $retaceo->details->map(fn ($d) => [
                'id_retaceo_detail'  => $d->id_retaceo_detail,
                'id_purchase_detail' => $d->id_purchase_detail,
                'id_product'         => $d->id_product,
                'product_name'       => $d->product?->name ?? 'Producto #'.$d->id_product,
                'product_code'       => $d->product?->code ?? '',
                'quantity'           => (float) $d->quantity,
                'cost_fob'           => (float) $d->cost_fob,
                'freight_amount'     => (float) $d->freight_amount,
                'expense_amount'     => (float) $d->expense_amount,
                'dai_amount'         => (float) $d->dai_amount,
                'unit_cost'          => (float) $d->unit_cost,
                'total_cost'         => (float) $d->total_cost,
            ]),
        ]);
    }

    public function edit(Retaceo $retaceo)
    {
        Gate::authorize('retaceos.editar');

        if ($retaceo->status !== 'draft') {
            return redirect()
                ->route('retaceos.show', $retaceo->id_retaceo)
                ->with('error', 'Solo los retaceos en estado borrador pueden ser modificados.');
        }

        $retaceo->load(['details.product', 'purchase']);
        $suppliers = Supplier::orderBy('name')->get();

        return view('retaceos.create', compact('retaceo', 'suppliers'));
    }

    public function update(Request $request, Retaceo $retaceo)
    {
        Gate::authorize('retaceos.editar');

        if ($retaceo->status !== 'draft') {
            return redirect()
                ->route('retaceos.show', $retaceo->id_retaceo)
                ->with('error', 'Solo los retaceos en estado borrador pueden ser modificados.');
        }

        $validated = $request->validate([
            'id_supplier'                 => ['required', 'exists:suppliers,id_supplier'],
            'retaceo_date'                => ['required', 'date'],
            'origin_country'              => ['nullable', 'string', 'max:100'],
            'import_invoice_number'       => ['nullable', 'string', 'max:100'],
            'import_invoice_date'         => ['nullable', 'date'],
            'import_policy_number'        => ['nullable', 'string', 'max:100'],
            'import_policy_date'          => ['nullable', 'date'],
            'total_freight'               => ['nullable', 'numeric', 'min:0'],
            'total_expenses'              => ['nullable', 'numeric', 'min:0'],
            'notes'                       => ['nullable', 'string'],
            'details'                     => ['required', 'array', 'min:1'],
            'details.*.id_product'        => ['required', 'exists:products,id'],
            'details.*.id_purchase_detail'=> ['nullable', 'exists:purchase_details,id_purchase_detail'],
            'details.*.quantity'          => ['required', 'numeric', 'min:0.0001'],
            'details.*.cost_fob'          => ['required', 'numeric', 'min:0'],
            'details.*.dai_amount'        => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->service->actualizar($retaceo, $validated);

        return redirect()
            ->route('retaceos.show', $retaceo->id_retaceo)
            ->with('success', 'Retaceo actualizado correctamente.');
    }

    public function updateStatus(Request $request, Retaceo $retaceo)
    {
        Gate::authorize('retaceos.cambiar_estado');

        $request->validate([
            'status' => ['required', Rule::in(['draft', 'calculated', 'applied', 'cancelled'])],
        ]);

        try {
            $this->service->cambiarEstado($retaceo, $request->status);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Estado del retaceo actualizado correctamente.');
    }

    public function destroy(Retaceo $retaceo)
    {
        Gate::authorize('retaceos.eliminar');

        if ($retaceo->status !== 'draft') {
            return back()->with('error', 'Solo se pueden eliminar retaceos en borrador.');
        }

        $retaceo->delete();

        return redirect()
            ->route('retaceos.index')
            ->with('success', 'Retaceo eliminado correctamente.');
    }
}
