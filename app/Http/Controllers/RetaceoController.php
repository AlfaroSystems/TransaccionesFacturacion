<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ExpenseType;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Retaceo;
use App\Models\Supplier;
use App\Rules\Accessible;
use App\Services\ProductCostService;
use App\Services\RetaceoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RetaceoController extends Controller
{
    /** Mensajes de las reglas de líneas (todas las de la compra, una vez cada una) */
    private const MENSAJES_LINEAS = [
        'details.*.id_purchase_detail.required' => 'Cada línea del retaceo debe ser una línea de la compra.',
        'details.*.id_purchase_detail.in' => 'Cada línea del retaceo debe ser una línea de la compra.',
        'details.*.id_purchase_detail.distinct' => 'Una línea de la compra aparece más de una vez en el retaceo.',
    ];

    /** Otros gastos aduanales / seguro: una línea por gasto, de un tipo de gasto activo */
    private const REGLAS_GASTOS = [
        'expenses' => ['nullable', 'array'],
        'expenses.*.id_expense_type' => ['required', 'integer', 'exists:expense_types,id_expense_type,is_active,1'],
        'expenses.*.description' => ['nullable', 'string', 'max:255'],
        'expenses.*.amount' => ['required', 'numeric', 'min:0'],
    ];

    private const MENSAJES_GASTOS = [
        'expenses.*.id_expense_type.required' => 'Elija el tipo de cada gasto.',
        'expenses.*.id_expense_type.exists' => 'El tipo de gasto no existe o está inactivo.',
        'expenses.*.amount.required' => 'Indique el monto de cada gasto.',
    ];

    public function __construct(private readonly RetaceoService $service) {}

    /**
     * Reglas de las líneas: cada una es una línea distinta de la compra, con su producto y la
     * cantidad recibida (la que entró al inventario y cuyo costo fija el retaceo), y están todas,
     * porque el flete, los gastos y el DAI de la póliza se reparten entre toda la mercadería.
     */
    private function reglasDeLineas(Request $request, int $purchaseId): array
    {
        $lineas = PurchaseDetail::with('product')->where('id_purchase', $purchaseId)->get()->keyBy('id_purchase_detail');

        // Línea de la compra a la que apunta la línea N del retaceo (details.N.campo)
        $lineaDe = fn (string $attribute) => $lineas->get(
            (int) $request->input('details.'.explode('.', $attribute)[1].'.id_purchase_detail')
        );

        return [
            'details' => ['required', 'array', 'min:1', function (string $attribute, mixed $value, \Closure $fail) use ($lineas) {
                $enviadas = collect($value)->pluck('id_purchase_detail')->map(fn ($id) => (int) $id);

                if ($lineas->keys()->diff($enviadas)->isNotEmpty()) {
                    $fail('El retaceo debe incluir todas las líneas de la compra.');
                }
            }],
            'details.*.id_purchase_detail' => ['required', 'integer', 'distinct', Rule::in($lineas->keys()->all())],
            'details.*.id_product' => ['required', function (string $attribute, mixed $value, \Closure $fail) use ($lineaDe) {
                $linea = $lineaDe($attribute);

                if ($linea && (int) $value !== (int) $linea->id_product) {
                    $fail('El producto no corresponde a su línea de la compra.');
                }
            }],
            'details.*.quantity' => ['required', 'numeric', 'min:0.0001', function (string $attribute, mixed $value, \Closure $fail) use ($lineaDe) {
                $linea = $lineaDe($attribute);

                if ($linea && is_numeric($value) && abs((float) $value - (float) $linea->quantity_received) > 0.00005) {
                    $fail(sprintf(
                        'La cantidad de %s debe ser la recibida en la compra: %s.',
                        $linea->product?->name ?? 'el producto',
                        rtrim(rtrim(number_format((float) $linea->quantity_received, 4, '.', ''), '0'), '.')
                    ));
                }
            }],
            'details.*.cost_fob' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Regla de la fecha de liquidación: no antes del día en que se recibió la compra.
     */
    private function fechaDesdeLaRecepcion(?Purchase $purchase): array
    {
        $recepcion = $purchase?->purchase_date;

        return ['bail', 'required', 'date', function (string $attribute, mixed $value, \Closure $fail) use ($recepcion) {
            if ($recepcion && Carbon::parse($value)->startOfDay()->lt($recepcion->copy()->startOfDay())) {
                $fail('La fecha de liquidación no puede ser anterior a la recepción de la compra ('.$recepcion->format('d/m/Y').').');
            }
        }];
    }

    /** Tipos de gasto activos, para las líneas de otros gastos */
    private function tiposDeGasto()
    {
        return ExpenseType::active()->orderBy('name')->get();
    }

    public function index(Request $request)
    {
        Gate::authorize('retaceos.ver');

        // Retaceo y Purchase se filtran por la sucursal del usuario (BranchScope)
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
            $query->where('id_supplier', $request->integer('id_supplier'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($desde = $this->fechaDelFiltro($request, 'date_from')) {
            $query->whereDate('retaceo_date', '>=', $desde);
        }

        if ($hasta = $this->fechaDelFiltro($request, 'date_to')) {
            $query->whereDate('retaceo_date', '<=', $hasta);
        }

        $retaceos = $query->paginate(10)->withQueryString();

        // Métricas
        $totalCount = Retaceo::count();
        $draftCount = Retaceo::where('status', 'draft')->count();
        $calculatedCount = Retaceo::whereIn('status', ['calculated', 'applied'])->count();
        $totalCostAmount = Retaceo::where('status', '!=', 'cancelled')->sum('total_cost');

        $suppliers = Supplier::orderBy('name')->get();
        // Solo para el selector del modal: las líneas las trae getPurchaseData
        $purchasesForModal = Purchase::availableForRetaceo()
            ->with('supplier')
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
        ) + ['expenseTypes' => $this->tiposDeGasto()]);
    }

    public function create(Request $request)
    {
        Gate::authorize('retaceos.crear');

        // Solo para el selector (la vista preselecciona ?id_purchase): las líneas de la compra
        // elegida las trae getPurchaseData
        $purchases = Purchase::availableForRetaceo()
            ->with('supplier')
            ->orderByDesc('id_purchase')
            ->get();

        $suppliers = Supplier::orderBy('name')->get();

        return view('retaceos.create', compact(
            'purchases',
            'suppliers'
        ) + ['expenseTypes' => $this->tiposDeGasto()]);
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
            'id_purchase' => $purchase->id_purchase,
            'purchase_code' => $purchase->purchase_code,
            'id_supplier' => $purchase->id_supplier,
            'supplier_name' => $purchase->supplier?->name,
            'origin_country' => $purchase->supplier?->country ?? 'El Salvador',
            'supplier_invoice_number' => $purchase->supplier_invoice_number,
            'supplier_invoice_date' => $purchase->supplier_invoice_date ? $purchase->supplier_invoice_date->format('Y-m-d') : null,
            'currency' => $purchase->currency,
            // La liquidación no puede ser anterior a la recepción (ver fechaDesdeLaRecepcion)
            'purchase_date' => $purchase->purchase_date?->format('Y-m-d\T00:00'),
            // Gastos de la orden marcados como costo que le tocan a esta compra, como sugerencia
            'suggested_expenses' => app(ProductCostService::class)->gastosDeLaCompra($purchase),
            // Y uno por uno, para las líneas de gasto del retaceo
            'suggested_expense_lines' => app(ProductCostService::class)->gastosSugeridos($purchase),
            'details' => $purchase->details->map(function ($d) {
                $qty = (float) $d->quantity_received;
                if ($qty <= 0) {
                    $qty = (float) $d->quantity_ordered;
                }
                // El valor de la línea en la factura: lo recibido menos su descuento, sin IVA (el
                // mismo neto que usa ProductCostService para el costo de la compra)
                $fob = $d->subtotal > 0
                    ? max(0, (float) $d->subtotal - (float) $d->discount)
                    : max(0, $qty * (float) $d->unit_price - (float) $d->discount);

                return [
                    'id_purchase_detail' => $d->id_purchase_detail,
                    'id_product' => $d->id_product,
                    'product_name' => $d->product?->name ?? 'Producto #'.$d->id_product,
                    'product_code' => $d->product?->code ?? '',
                    'quantity' => $qty,
                    'unit_name' => $d->unit?->name ?? 'Unidad',
                    'unit_price' => (float) $d->unit_price,
                    'cost_fob' => round($fob, 4),
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('retaceos.crear');

        $validated = $request->validate([
            // Que esté recibida y sin otro retaceo activo lo revisa RetaceoService::crear con la
            // compra bloqueada: aquí dos retaceos creados a la vez pasarían ambos
            'id_purchase' => ['required', new Accessible(Purchase::class)],
            'id_supplier' => ['required', 'exists:supliers,id_supplier'],
            'retaceo_date' => $this->fechaDesdeLaRecepcion(Purchase::find($request->integer('id_purchase'))),
            'origin_country' => ['nullable', 'string', 'max:100'],
            'import_invoice_number' => ['nullable', 'string', 'max:100'],
            'import_invoice_date' => ['nullable', 'date'],
            'import_policy_number' => ['nullable', 'string', 'max:100'],
            'import_policy_date' => ['nullable', 'date'],
            'total_freight' => ['nullable', 'numeric', 'min:0'],
            ...self::REGLAS_GASTOS,
            'total_dai' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'calculated', 'applied'])],
            'notes' => ['nullable', 'string'],
            ...$this->reglasDeLineas($request, $request->integer('id_purchase')),
        ], [...self::MENSAJES_LINEAS, ...self::MENSAJES_GASTOS]);

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
            'details.purchaseDetail.inventoryLots',
            'expenses.expenseType',
        ]);

        return view('retaceos.show', compact('retaceo'));
    }

    public function generatePdf(Retaceo $retaceo)
    {
        Gate::authorize('retaceos.ver');

        $retaceo->load([
            'supplier',
            'user',
            'purchase.branch.company',
            'purchase.warehouse',
            'purchase.supplier',
            'purchase.purchaseOrder',
            'details.product',
            'details.purchaseDetail.unit',
        ]);

        $company = $retaceo->purchase?->branch?->company ?? Company::where('is_active', true)->first();

        return view('retaceos.pdf', compact('retaceo', 'company'));
    }

    public function getEditData(Retaceo $retaceo)
    {
        Gate::authorize('retaceos.editar');

        $retaceo->load([
            'purchase',
            'supplier',
            'details.product',
            'details.purchaseDetail',
        ]);

        return response()->json([
            'id_retaceo' => $retaceo->id_retaceo,
            'retaceo_code' => $retaceo->retaceo_code,
            'id_purchase' => $retaceo->id_purchase,
            'purchase_code' => $retaceo->purchase?->purchase_code,
            'id_supplier' => $retaceo->id_supplier,
            'supplier_name' => $retaceo->supplier?->name,
            'origin_country' => $retaceo->origin_country ?? '',
            'retaceo_date' => $retaceo->retaceo_date ? $retaceo->retaceo_date->format('Y-m-d\TH:i') : '',
            'purchase_date' => $retaceo->purchase?->purchase_date?->format('Y-m-d\T00:00'),
            'import_invoice_number' => $retaceo->import_invoice_number ?? '',
            'import_invoice_date' => $retaceo->import_invoice_date ? $retaceo->import_invoice_date->format('Y-m-d') : '',
            'import_policy_number' => $retaceo->import_policy_number ?? '',
            'import_policy_date' => $retaceo->import_policy_date ? $retaceo->import_policy_date->format('Y-m-d') : '',
            'total_freight' => (float) $retaceo->total_freight,
            'total_expenses' => (float) $retaceo->total_expenses,
            'expenses' => $retaceo->lineasDeGastos(),
            'total_dai' => (float) $retaceo->total_dai,
            'status' => $retaceo->status,
            'notes' => $retaceo->notes ?? '',
            'details' => $retaceo->details->map(fn ($d) => [
                'id_retaceo_detail' => $d->id_retaceo_detail,
                'id_purchase_detail' => $d->id_purchase_detail,
                'id_product' => $d->id_product,
                'product_name' => $d->product?->name ?? 'Producto #'.$d->id_product,
                'product_code' => $d->product?->code ?? '',
                // La recibida en la compra, que es la que el retaceo debe llevar (ver reglasDeLineas)
                'quantity' => (float) ($d->purchaseDetail?->quantity_received ?? $d->quantity),
                'cost_fob' => (float) $d->cost_fob,
                'freight_amount' => (float) $d->freight_amount,
                'expense_amount' => (float) $d->expense_amount,
                'dai_amount' => (float) $d->dai_amount,
                'unit_cost' => (float) $d->unit_cost,
                'total_cost' => (float) $d->total_cost,
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

        // La cantidad de cada línea es la recibida en la compra (ver reglasDeLineas)
        $retaceo->load(['details.product', 'details.purchaseDetail', 'purchase']);
        $suppliers = Supplier::orderBy('name')->get();

        return view('retaceos.create', compact('retaceo', 'suppliers') + ['expenseTypes' => $this->tiposDeGasto()]);
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
            'id_supplier' => ['required', 'exists:supliers,id_supplier'],
            'retaceo_date' => $this->fechaDesdeLaRecepcion(Purchase::queryAllBranches()->find($retaceo->id_purchase)),
            'origin_country' => ['nullable', 'string', 'max:100'],
            'import_invoice_number' => ['nullable', 'string', 'max:100'],
            'import_invoice_date' => ['nullable', 'date'],
            'import_policy_number' => ['nullable', 'string', 'max:100'],
            'import_policy_date' => ['nullable', 'date'],
            'total_freight' => ['nullable', 'numeric', 'min:0'],
            ...self::REGLAS_GASTOS,
            'total_dai' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            ...$this->reglasDeLineas($request, $retaceo->id_purchase),
        ], [...self::MENSAJES_LINEAS, ...self::MENSAJES_GASTOS]);

        try {
            $this->service->actualizar($retaceo, $validated);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('retaceos.show', $retaceo->id_retaceo)
                ->with('error', $e->getMessage());
        }

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

        $revierte = $retaceo->status === 'applied';

        try {
            $this->service->cambiarEstado($retaceo, $request->status);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $mensaje = match (true) {
            $request->status === 'applied' => 'Retaceo aplicado: los lotes de la compra quedaron por ubicar en bodega.',
            $revierte => 'Retaceo revertido a borrador: sus lotes se anularon y se pueden corregir sus costos.',
            default => 'Estado del retaceo actualizado correctamente.',
        };

        return back()->with('success', $mensaje);
    }

    public function destroy(Retaceo $retaceo)
    {
        Gate::authorize('retaceos.eliminar');

        try {
            $this->service->eliminar($retaceo);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('retaceos.index')
            ->with('success', 'Retaceo eliminado correctamente.');
    }
}
