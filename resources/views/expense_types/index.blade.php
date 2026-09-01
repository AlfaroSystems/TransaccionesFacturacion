@extends('layouts.app')

@section('title', 'Tipos de Gastos')

@section('content')

<div class="animate-fade-in duration-300">

    {{-- ENCABEZADO --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">
                Tipos de Gastos
            </h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Gestiona el catálogo de tipos de gastos.
            </p>
        </div>

        @can('expense_types.crear')
            <button
                type="button"
                onclick="openExpenseTypeModal('create')"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white text-sm font-bold shadow-sm transition-all"
            >
                <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>

                Nuevo tipo de gasto
            </button>
        @endcan

    </div>


    {{-- MENSAJE DE ÉXITO --}}
    @if(session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif


    {{-- ERRORES --}}
    @if($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">

            <p class="font-bold mb-1">
                Se encontraron errores:
            </p>

            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>
    @endif


    {{-- TABLA --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 card-shadow overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700">

                    <tr>

                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            ID
                        </th>

                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Nombre
                        </th>

                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Descripción
                        </th>

                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Estado
                        </th>

                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 text-right">
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">

                    @forelse($expenseTypes as $expenseType)

                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">

                            {{-- ID --}}
                            <td class="px-6 py-4 text-sm font-semibold text-slate-600 dark:text-slate-300">
                                {{ $expenseType->id_expense_type }}
                            </td>


                            {{-- NOMBRE --}}
                            <td class="px-6 py-4">

                                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    {{ $expenseType->name }}
                                </span>

                            </td>


                            {{-- DESCRIPCIÓN --}}
                            <td class="px-6 py-4">

                                <span class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ $expenseType->description ?: 'Sin descripción' }}
                                </span>

                            </td>


                            {{-- ESTADO --}}
                            <td class="px-6 py-4">

                                @if($expenseType->is_active)

                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                        Activo
                                    </span>

                                @else

                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                        Inactivo
                                    </span>

                                @endif

                            </td>


                            {{-- ACCIONES --}}
                            <td class="px-6 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    {{-- EDITAR --}}
                                    @can('expense_types.editar')

                                        <button
                                            type="button"
                                            class="edit-expense-type-btn px-3 py-2 rounded-lg bg-sky-50 hover:bg-sky-100 text-sky-700 dark:bg-sky-900/20 dark:hover:bg-sky-900/40 dark:text-sky-300 text-xs font-bold transition-all"
                                            data-id="{{ $expenseType->id_expense_type }}"
                                            data-name="{{ $expenseType->name }}"
                                            data-description="{{ $expenseType->description ?? '' }}"
                                            data-active="{{ $expenseType->is_active ? '1' : '0' }}"
                                        >
                                            Editar
                                        </button>

                                    @endcan


                                    {{-- INACTIVAR / REACTIVAR --}}
                                    @can('expense_types.eliminar')

                                        @if($expenseType->is_active)

                                            <button
                                                type="button"
                                                class="deactivate-expense-type-btn px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 dark:text-rose-300 text-xs font-bold transition-all"
                                                data-id="{{ $expenseType->id_expense_type }}"
                                                data-name="{{ $expenseType->name }}"
                                            >
                                                Inactivar
                                            </button>

                                        @else

                                            <button
                                                type="button"
                                                class="reactivate-expense-type-btn px-3 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 dark:text-emerald-300 text-xs font-bold transition-all"
                                                data-id="{{ $expenseType->id_expense_type }}"
                                                data-name="{{ $expenseType->name }}"
                                            >
                                                Reactivar
                                            </button>

                                        @endif

                                    @endcan

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5" class="px-6 py-12 text-center">

                                <div class="flex flex-col items-center justify-center">

                                    <svg
                                        class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 13h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V20a2 2 0 01-2 2z"
                                        />
                                    </svg>

                                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400">
                                        No hay tipos de gastos registrados.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

{{-- ========================================================= --}}
{{-- MODAL CREAR / EDITAR --}}
{{-- ========================================================= --}}

<div
    id="expense-type-form-modal"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm p-4"
>

    <div
        id="expense-type-form-card"
        class="w-full max-w-lg bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl transform scale-95 transition-all duration-200"
    >

        {{-- CABECERA --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200 dark:border-slate-700">

            <div>

                <h2
                    id="expense-type-form-title"
                    class="text-lg font-extrabold text-slate-800 dark:text-slate-100"
                >
                    Nuevo tipo de gasto
                </h2>

                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Completa los campos solicitados.
                </p>

            </div>


            <button
                type="button"
                onclick="closeExpenseTypeFormModal()"
                class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors"
            >

                <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>

            </button>

        </div>

        {{-- FORMULARIO --}}
        <form
            id="expense-type-form"
            method="POST"
            action="{{ route('expense-types.store') }}"
        >

            @csrf

            <input
                type="hidden"
                id="expense-type-method"
                name="_method"
                value="POST"
            >


            <div class="p-6 space-y-5">

                {{-- NOMBRE --}}
                <div>

                    <label
                        for="expense-type-name"
                        class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2"
                    >
                        Nombre
                    </label>

                    <input
                        type="text"
                        id="expense-type-name"
                        name="name"
                        required
                        maxlength="150"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 px-4 py-3 text-sm focus:ring-2 focus:ring-[#005e66] focus:border-[#005e66] outline-none"
                        placeholder="Ej. Gastos de envío"
                    >

                </div>

                {{-- DESCRIPCIÓN --}}
                <div>

                    <label
                        for="expense-type-description"
                        class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2"
                    >
                        Descripción
                    </label>

                    <textarea
                        id="expense-type-description"
                        name="description"
                        rows="4"
                        maxlength="1000"
                        required
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 px-4 py-3 text-sm focus:ring-2 focus:ring-[#005e66] focus:border-[#005e66] outline-none resize-none"
                        placeholder="Describe el tipo de gasto..."
                    ></textarea>

                </div>


                {{-- ESTADO --}}
                <div>

                    <label class="flex items-center gap-3 cursor-pointer">

                        <input
                            type="hidden"
                            name="is_active"
                            value="0"
                        >

                        <input
                            type="checkbox"
                            id="expense-type-active"
                            name="is_active"
                            value="1"
                            checked
                            class="w-4 h-4 rounded border-slate-300 text-[#005e66] focus:ring-[#005e66]"
                        >

                        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">
                            Tipo de gasto activo
                        </span>

                    </label>

                </div>

            </div>

            {{-- BOTONES --}}
            <div class="flex justify-end gap-3 px-6 py-5 border-t border-slate-200 dark:border-slate-700">

                <button
                    type="button"
                    onclick="closeExpenseTypeFormModal()"
                    class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-bold transition-all"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    id="expense-type-submit-btn"
                    class="px-5 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white text-sm font-bold transition-all"
                >
                    Guardar
                </button>

            </div>

        </form>

    </div>

</div>

{{-- ========================================================= --}}
{{-- MODAL INACTIVAR / REACTIVAR --}}
{{-- ========================================================= --}}

<div
    id="expense-type-status-modal"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm p-4"
>

    <div
        id="expense-type-status-card"
        class="w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl transform scale-95 transition-all duration-200"
    >

        <div class="p-6 text-center">

            {{-- ICONO --}}
            <div
                id="expense-type-status-icon"
                class="w-14 h-14 rounded-full border-2 border-amber-400 flex items-center justify-center mx-auto text-amber-500 mb-5"
            >

                <svg
                    class="w-7 h-7"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>

            </div>

            <h2
                id="expense-type-status-title"
                class="text-lg font-extrabold text-slate-800 dark:text-slate-100 mb-2"
            >
                ¿Inactivar tipo de gasto?
            </h2>

            <p
                id="expense-type-status-description"
                class="text-sm text-slate-500 dark:text-slate-400 mb-6"
            >
                El tipo de gasto pasará a estar inactivo.
            </p>


            {{-- FORMULARIO DE CAMBIO DE ESTADO --}}
            <form
                id="expense-type-status-form"
                method="POST"
            >

                @csrf
                @method('DELETE')

                <div class="flex justify-center gap-3">

                    <button
                        type="button"
                        onclick="closeExpenseTypeStatusModal()"
                        class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-bold transition-all"
                    >
                        Cancelar
                    </button>


                    <button
                        type="submit"
                        id="expense-type-status-submit"
                        class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold transition-all"
                    >
                        Sí, inactivar
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    // =========================================
    // EDITAR
    // =========================================

    document.querySelectorAll('.edit-expense-type-btn').forEach(function (button) {

        button.addEventListener('click', function () {

            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description || '';
            const active = this.dataset.active === '1';

            document.getElementById('expense-type-form-title').textContent =
                'Editar tipo de gasto';

            document.getElementById('expense-type-submit-btn').textContent =
                'Actualizar';

            document.getElementById('expense-type-name').value =
                name;

            document.getElementById('expense-type-description').value =
                description;

            document.getElementById('expense-type-active').checked =
                active;

            document.getElementById('expense-type-form').action =
                '/expense-types/' + id;

            document.getElementById('expense-type-method').value =
                'PUT';

            openExpenseTypeFormModal();

        });

    });

    // =========================================
    // INACTIVAR
    // =========================================

    document.querySelectorAll('.deactivate-expense-type-btn').forEach(function (button) {

        button.addEventListener('click', function () {

            openExpenseTypeStatusModal(
                this.dataset.id,
                this.dataset.name,
                false
            );

        });

    });

    // =========================================
    // REACTIVAR
    // =========================================

    document.querySelectorAll('.reactivate-expense-type-btn').forEach(function (button) {

        button.addEventListener('click', function () {

            openExpenseTypeStatusModal(
                this.dataset.id,
                this.dataset.name,
                true
            );

        });

    });

});

// =========================================
// ABRIR MODAL CREAR
// =========================================

function openExpenseTypeModal(type) {

    if (type === 'create') {

        document.getElementById('expense-type-form-title').textContent =
            'Nuevo tipo de gasto';

        document.getElementById('expense-type-submit-btn').textContent =
            'Guardar';

        document.getElementById('expense-type-form').action =
            "{{ route('expense-types.store') }}";

        document.getElementById('expense-type-method').value =
            'POST';

        document.getElementById('expense-type-name').value =
            '';

        document.getElementById('expense-type-description').value =
            '';

        document.getElementById('expense-type-active').checked =
            true;

        openExpenseTypeFormModal();
    }

}

// =========================================
// ABRIR MODAL FORMULARIO
// =========================================

function openExpenseTypeFormModal() {

    const modal =
        document.getElementById('expense-type-form-modal');

    const card =
        document.getElementById('expense-type-form-card');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(function () {

        card.classList.remove('scale-95');
        card.classList.add('scale-100');

    }, 10);

}

// =========================================
// CERRAR MODAL FORMULARIO
// =========================================

function closeExpenseTypeFormModal() {

    const modal =
        document.getElementById('expense-type-form-modal');

    const card =
        document.getElementById('expense-type-form-card');

    card.classList.remove('scale-100');
    card.classList.add('scale-95');

    setTimeout(function () {

        modal.classList.add('hidden');
        modal.classList.remove('flex');

    }, 150);

}

// =========================================
// ABRIR MODAL ESTADO
// =========================================

function openExpenseTypeStatusModal(id, name, reactivate) {

    const modal =
        document.getElementById('expense-type-status-modal');

    const card =
        document.getElementById('expense-type-status-card');

    const title =
        document.getElementById('expense-type-status-title');

    const description =
        document.getElementById('expense-type-status-description');

    const submit =
        document.getElementById('expense-type-status-submit');

    const icon =
        document.getElementById('expense-type-status-icon');

    const form =
        document.getElementById('expense-type-status-form');


    // La petición siempre va al método destroy()
    form.action =
        '/expense-types/' + id;


    if (reactivate) {

        // =========================================
        // REACTIVAR
        // =========================================

        title.textContent =
            '¿Reactivar tipo de gasto?';

        description.textContent =
            "El tipo de gasto '" + name + "' volverá a estar activo.";

        submit.textContent =
            'Sí, reactivar';

        submit.className =
            'px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold transition-all';

        icon.className =
            'w-14 h-14 rounded-full border-2 border-emerald-400 flex items-center justify-center mx-auto text-emerald-500 mb-5';

        icon.innerHTML = `
            <svg
                class="w-7 h-7"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
        `;

    } else {

        // =========================================
        // INACTIVAR
        // =========================================

        title.textContent =
            '¿Inactivar tipo de gasto?';

        description.textContent =
            "El tipo de gasto '" + name + "' pasará a estar inactivo.";

        submit.textContent =
            'Sí, inactivar';

        submit.className =
            'px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold transition-all';

        icon.className =
            'w-14 h-14 rounded-full border-2 border-amber-400 flex items-center justify-center mx-auto text-amber-500 mb-5';

        icon.innerHTML = `
            <svg
                class="w-7 h-7"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
            </svg>
        `;

    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(function () {

        card.classList.remove('scale-95');
        card.classList.add('scale-100');

    }, 10);

}

// =========================================
// CERRAR MODAL ESTADO
// =========================================

function closeExpenseTypeStatusModal() {

    const modal =
        document.getElementById('expense-type-status-modal');

    const card =
        document.getElementById('expense-type-status-card');

    card.classList.remove('scale-100');
    card.classList.add('scale-95');

    setTimeout(function () {

        modal.classList.add('hidden');
        modal.classList.remove('flex');

    }, 150);

}

</script>

@endsection