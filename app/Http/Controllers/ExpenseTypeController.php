<?php

namespace App\Http\Controllers;
use App\Http\Requests\StoreExpenseTypeRequest;
use App\Http\Requests\UpdateExpenseTypeRequest;
use App\Models\ExpenseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExpenseTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('expense_types.ver');

        $expenseTypes = ExpenseType::orderBy('id_expense_type')->get();

        return view('expense_types.index', compact('expenseTypes'));
    }

    public function store(StoreExpenseTypeRequest $request): RedirectResponse
    {
        Gate::authorize('expense_types.crear');

        ExpenseType::create($request->validated());

        return redirect()
            ->route('expense-types.index')
            ->with('success', 'Tipo de gasto creado correctamente.');
    }

    public function edit(ExpenseType $expenseType): View
    {
        Gate::authorize('expense_types.editar');

        return view('expense_types.edit', compact('expenseType'));
    }

    public function update(
        UpdateExpenseTypeRequest $request,
        ExpenseType $expenseType
    ): RedirectResponse {
        Gate::authorize('expense_types.editar');

        $expenseType->update($request->validated());

        return redirect()
            ->route('expense-types.index')
            ->with('success', 'Tipo de gasto actualizado correctamente.');
    }

    public function destroy(ExpenseType $expenseType): RedirectResponse
    {
        Gate::authorize('expense_types.eliminar');

        $expenseType->update([
            'is_active' => !$expenseType->is_active,
        ]);

        $message = $expenseType->is_active
            ? 'Tipo de gasto reactivado correctamente.'
            : 'Tipo de gasto inactivado correctamente.';

        return redirect()
            ->route('expense-types.index')
            ->with('success', $message);
    }
}