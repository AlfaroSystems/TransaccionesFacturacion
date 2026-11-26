<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Services\CashRegisterService;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CashController extends Controller
{
    public function __construct(private readonly CashRegisterService $service) {}

    public function index()
    {
        Gate::authorize('cash.ver');

        $branchId = BranchAccess::branchId();
        $open = $branchId ? CashShift::openForBranch($branchId) : null;
        $open?->load(['user', 'outflows.user']);

        $history = CashShift::with(['user', 'closedBy'])
            ->where('status', CashShift::STATUS_CLOSED)
            ->orderByDesc('id_cash_shift')
            ->paginate(10);

        return view('cash.index', [
            'open' => $open,
            'history' => $history,
            'sinSucursal' => $branchId === null && ! BranchAccess::isUnrestricted(),
        ]);
    }

    public function open(Request $request)
    {
        Gate::authorize('cash.abrir');

        $validated = $request->validate([
            'opening_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $branchId = BranchAccess::branchId();

        if (! $branchId) {
            return back()->withErrors(['opening_amount' => 'Tu usuario no tiene una sucursal asignada para abrir caja.']);
        }

        $this->service->abrir($branchId, (float) $validated['opening_amount']);

        return redirect()->route('cash.index')->with('success', 'Caja abierta.');
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
