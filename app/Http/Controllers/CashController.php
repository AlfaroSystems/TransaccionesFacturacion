<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashShift;
use App\Services\CashRegisterService;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CashController extends Controller
{
    public function __construct(private readonly CashRegisterService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('cash.ver');

        // Un usuario con sucursal maneja la suya; un admin (sin restricción) maneja la de la
        // sucursal elegida (?sucursal=), igual que el POS, con selector para cambiarla.
        $sucursales = BranchAccess::isUnrestricted()
            ? Branch::queryAllBranches()->where('is_active', true)->orderBy('name')->get()
            : collect();
        $branchId = BranchAccess::branchId() ?? $this->sucursalElegida($request, $sucursales);

        $open = $branchId ? CashShift::openForBranch($branchId) : null;
        $open?->load(['user', 'outflows.user']);

        $history = CashShift::with(['user', 'closedBy'])
            ->where('status', CashShift::STATUS_CLOSED)
            ->when($branchId, fn ($q) => $q->where('id_branch', $branchId))
            ->orderByDesc('id_cash_shift')
            ->paginate(10);

        return view('cash.index', [
            'open' => $open,
            'history' => $history,
            'sucursales' => $sucursales,
            'sucursalActual' => $branchId,
            'sinSucursal' => $branchId === null && ! BranchAccess::isUnrestricted(),
        ]);
    }

    public function open(Request $request)
    {
        Gate::authorize('cash.abrir');

        $validated = $request->validate([
            'opening_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $branchId = BranchAccess::branchId() ?? $this->sucursalDelAdmin($request);

        if (! $branchId) {
            return back()->withErrors(['opening_amount' => 'Tu usuario no tiene una sucursal asignada para abrir caja.']);
        }

        $this->service->abrir($branchId, (float) $validated['opening_amount']);

        return redirect()->route('cash.index', BranchAccess::branchId() ? [] : ['sucursal' => $branchId])
            ->with('success', 'Caja abierta.');
    }

    /**
     * Sucursal que verá el admin en la pantalla de Caja: la de ?sucursal= (si es válida), o la
     * primera activa. Devuelve null solo si no hay sucursales.
     */
    private function sucursalElegida(Request $request, $sucursales): ?int
    {
        if (! BranchAccess::isUnrestricted()) {
            return null;
        }

        $elegida = (int) $request->integer('sucursal');

        return $sucursales->firstWhere('id_branch', $elegida)?->id_branch
            ?? $sucursales->first()?->id_branch;
    }

    /**
     * Sucursal con la que un admin abre caja: la enviada en el formulario, validada a una
     * sucursal activa. Para un usuario con sucursal propia no aplica (usa la suya).
     */
    private function sucursalDelAdmin(Request $request): ?int
    {
        if (! BranchAccess::isUnrestricted()) {
            return null;
        }

        $request->validate([
            'id_branch' => ['required', 'integer', 'exists:branches,id_branch'],
        ]);

        return (int) $request->integer('id_branch');
    }

    public function outflow(Request $request, CashShift $shift)
    {
        Gate::authorize('cash.egreso');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'concept' => ['required', 'string', 'max:200'],
        ]);

        $this->service->egreso($shift, (float) $validated['amount'], $validated['concept']);

        return redirect()->route('cash.index')->with('success', 'Egreso registrado.');
    }

    public function close(Request $request, CashShift $shift)
    {
        Gate::authorize('cash.cerrar');

        $validated = $request->validate([
            'counted_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->cerrar($shift, (float) $validated['counted_amount'], $validated['notes'] ?? null);

        return redirect()->route('cash.index')->with('success', 'Caja cerrada.');
    }
}
