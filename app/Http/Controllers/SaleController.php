<?php

namespace App\Http\Controllers;

use App\Mail\SaleReceiptMail;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\BranchAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('sales.ver');

        $search = $request->input('search');

        $sales = Sale::with(['customer', 'branch', 'user'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sale_code', 'ilike', '%'.$search.'%')
                        ->orWhere('fiscal_document_number', 'ilike', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', '%'.$search.'%'));
                });
            })
            ->orderByDesc('id_sale')
            ->paginate(12)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        Gate::authorize('sales.crear');

        $branches = $this->branchesForUser();
        $customers = Customer::active()->orderBy('name')->get(['id_customer', 'name', 'customer_type', 'email']);

        // Productos vendibles: activos y con precio vigente
        $products = Product::active()
            ->with('currentPrice')
            ->get(['id_product', 'sku', 'internal_code', 'name'])
            ->filter(fn (Product $p) => $p->currentPrice)
            ->map(fn (Product $p) => [
                'id' => $p->id_product,
                'sku' => $p->sku,
                'code' => $p->internal_code,
                'name' => $p->name,
                'price' => (float) $p->currentPrice->price,
            ])
            ->values();

        // Catálogos para el alta rápida de cliente desde el modal de la venta
        $departments = \App\Models\Department::orderBy('name')->get();
        $municipalities = \App\Models\Municipality::orderBy('name')->get();
        $districts = \App\Models\District::orderBy('name')->get();

        return view('sales.create', compact('branches', 'customers', 'products', 'departments', 'municipalities', 'districts'));
    }

    public function store(Request $request)
    {
        Gate::authorize('sales.crear');

        $validated = $request->validate([
            'id_branch' => ['required', 'integer', 'exists:branches,id_branch'],
            'id_customer' => ['required', 'integer', Rule::exists('customers', 'id_customer')->where('is_active', true)],
            'document_type' => ['required', Rule::in(array_keys(Sale::DOCUMENT_LABELS))],
            'payment_method' => ['required', Rule::in(array_keys(Sale::PAYMENT_LABELS))],
            'fiscal_document_number' => ['nullable', 'string', 'max:50'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id_product' => ['required', 'integer', 'exists:products,id_product'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'lines.required' => 'Agregue al menos un producto a la venta.',
            'id_customer.exists' => 'Seleccione un cliente activo.',
        ]);

        if (! BranchAccess::ownsBranch($validated['id_branch'])) {
            return back()->withInput()->withErrors(['id_branch' => 'No puede vender desde esa sucursal.']);
        }

        $sale = $this->service->vender($validated);

        $redirect = redirect()
            ->route('sales.show', $sale)
            ->with('success', "Venta {$sale->sale_code} registrada y entregada.");

        $enviado = $this->enviarComprobante($sale);

        if ($enviado === false) {
            $redirect->with('info', 'La venta se registró, pero el cliente no tiene correo para enviarle el comprobante.');
        }

        return $redirect;
    }

    public function generatePdf(Sale $sale)
    {
        Gate::authorize('sales.ver');

        $sale->load(['customer', 'branch.company', 'user', 'details.product', 'details.unit']);
        $company = $sale->branch?->company ?? Company::where('is_active', true)->first();

        return Pdf::loadView('sales.pdf', compact('sale', 'company'))
            ->stream('Comprobante-'.$sale->sale_code.'.pdf');
    }

    public function sendReceipt(Sale $sale)
    {
        Gate::authorize('sales.crear');

        $enviado = $this->enviarComprobante($sale);

        if ($enviado === false) {
            return back()->withErrors(['email' => 'El cliente no tiene un correo registrado.']);
        }

        if ($enviado === null) {
            return back()->withErrors(['email' => 'No se pudo enviar el comprobante. Revise la configuración de correo.']);
        }

        return back()->with('success', 'Comprobante enviado al correo del cliente.');
    }

    /**
     * Encola el comprobante al correo del cliente. Devuelve false si no tiene correo, null si el
     * envío falló, true si se encoló. No interrumpe la venta si el correo falla.
     */
    private function enviarComprobante(Sale $sale): ?bool
    {
        $email = $sale->customer?->email;

        if (! $email) {
            return false;
        }

        try {
            Mail::to($email)->queue(new SaleReceiptMail($sale));
            $sale->forceFill(['emailed_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el comprobante de la venta '.$sale->sale_code.': '.$e->getMessage());

            return null;
        }
    }

    public function report(Request $request)
    {
        Gate::authorize('sales.rentabilidad');

        $from = $request->filled('from') ? \Illuminate\Support\Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? \Illuminate\Support\Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        $sales = Sale::with(['customer', 'branch'])
            ->where('status', Sale::STATUS_DELIVERED)
            ->whereBetween('sale_date', [$from, $to])
            ->orderByDesc('sale_date')
            ->get();

        $ingresos = round((float) $sales->sum('subtotal'), 2);
        $costo = round((float) $sales->sum('total_cost'), 2);
        $utilidad = round($ingresos - $costo, 2);

        return view('sales.report', compact('sales', 'from', 'to', 'ingresos', 'costo', 'utilidad'));
    }

    public function show(Sale $sale)
    {
        Gate::authorize('sales.ver');

        $sale->load([
            'customer', 'branch.company', 'user',
            'details.product', 'details.unit',
            'details.costDetails.lot',
        ]);

        return view('sales.show', compact('sale'));
    }

    public function cancel(Sale $sale)
    {
        Gate::authorize('sales.anular');

        if ($sale->status === Sale::STATUS_DELIVERED) {
            return back()->withErrors(['status' => 'La venta ya fue entregada: use una devolución para revertir el inventario.']);
        }

        if ($sale->status === Sale::STATUS_CANCELLED) {
            return back()->withErrors(['status' => 'La venta ya está anulada.']);
        }

        $sale->update(['status' => Sale::STATUS_CANCELLED]);

        return redirect()->route('sales.show', $sale)->with('success', 'Venta anulada.');
    }

    /**
     * Sucursales desde las que el usuario puede vender: todas (admin) o solo la suya.
     *
     * @return \Illuminate\Support\Collection<int, Branch>
     */
    private function branchesForUser()
    {
        if (BranchAccess::isUnrestricted()) {
            return Branch::queryAllBranches()->where('is_active', true)->orderBy('name')->get();
        }

        return Branch::where('is_active', true)->orderBy('name')->get();
    }
}
