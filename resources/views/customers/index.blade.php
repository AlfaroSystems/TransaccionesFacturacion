@extends('layouts.app')
@section('title', 'Gestión de Clientes')
@section('content')
<div class="animate-fade-in duration-300">
    {{-- ENCABEZADO --}}
    <x-ui.page-header eyebrow="Clientes" title="Gestión de Clientes"
        subtitle="Administra los clientes para el proceso de ventas.">
        <x-slot:actions>
            <x-ui.button :href="route('customers.cartera')" variant="secondary">Cartera</x-ui.button>
            @can('customers.crear')
                <x-ui.button type="button" icon="plus" onclick="openModal('create-customer-modal')">Nuevo Cliente</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- BUSCADOR --}}
    <div class="ui-surface p-4 mb-6">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Buscar por código, nombre, documento, NRC o correo..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-line text-sm focus:border-accent">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold rounded-xl text-sm transition-all">Buscar</button>
                @if(request('search'))
                    <a href="{{ route('customers.index') }}" class="px-5 py-2.5 bg-sunken hover:brightness-95 text-body font-bold rounded-xl text-sm transition-all">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    {{-- LISTADO --}}
    <section class="overflow-x-auto">
        <table class="ui-table">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Código / Cliente</th>
                    <th class="py-3 px-6">Tipo</th>
                    <th class="py-3 px-6">Documento</th>
                    <th class="py-3 px-6">Correo</th>
                    <th class="py-3 px-6">Teléfono</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200 {{ !$customer->is_active ? 'opacity-50 grayscale-35' : '' }}">
                        <td class="py-4 px-6 bg-surface rounded-l-2xl border-l border-y border-line text-sm">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="font-bold text-primary dark:text-accent text-xs">{{ $customer->code }}</span>
                                @if(!$customer->is_active)
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-danger-soft text-danger border border-rose-200 uppercase tracking-wider">Inactivo</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-success-soft text-success border border-emerald-200 uppercase tracking-wider">Activo</span>
                                @endif
                            </div>
                            <span class="font-extrabold text-slate-800 block">{{ $customer->name }}</span>
                        </td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm text-slate-600 font-semibold">{{ $customer->typeLabel() }}</td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm text-slate-600">
                            @if($customer->document_number)
                                <span class="font-mono">{{ strtoupper($customer->document_type ?? '') }} {{ $customer->document_number }}</span>
                                @if($customer->nrc)<span class="block text-[11px] text-slate-400">NRC {{ $customer->nrc }}</span>@endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm text-slate-600 font-mono">{{ $customer->email ?? '-' }}</td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm text-slate-600">{{ $customer->phone ?? '-' }}</td>
                        <td class="py-4 px-6 bg-surface rounded-r-2xl border-r border-y border-line text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('customers.editar')
                                    <button type="button" onclick="openModal('edit-customer-modal-{{ $customer->id_customer }}')"
                                        class="p-2.5 rounded-xl bg-warning-soft text-warning hover:brightness-95 font-semibold text-xs transition-all flex items-center justify-center" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                @endcan
                                @can('customers.eliminar')
                                    <button type="button"
                                        class="delete-customer-btn p-2.5 rounded-xl {{ $customer->is_active ? 'bg-danger-soft text-danger hover:brightness-95' : 'bg-success-soft text-success hover:brightness-95' }} font-semibold text-xs transition-all flex items-center justify-center"
                                        title="{{ $customer->is_active ? 'Desactivar' : 'Reactivar' }}"
                                        data-url="{{ route('customers.destroy', $customer) }}"
                                        data-name="{{ $customer->name }}"
                                        data-active="{{ $customer->is_active ? '1' : '0' }}">
                                        @if($customer->is_active)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @endif
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-slate-400 font-semibold">No se encontraron clientes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($customers->hasPages())
        <div class="mt-6">{{ $customers->links() }}</div>
    @endif
</div>

{{-- MODAL CREAR --}}
@can('customers.crear')
    <div id="create-customer-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-surface rounded-3xl p-6 max-w-3xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b pb-4 mb-6">
                <h3 class="text-xl font-extrabold text-slate-800">Registrar Nuevo Cliente</h3>
                <button type="button" onclick="closeModal('create-customer-modal')" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
            </div>
            <form action="{{ route('customers.store') }}" method="POST" class="space-y-5">
                @csrf
                @include('customers._form', ['customer' => null, 'prefix' => 'create_customer_'])
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeModal('create-customer-modal')" class="px-6 py-2.5 rounded-xl bg-sunken text-muted font-bold text-sm">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
@endcan

{{-- MODALES EDITAR --}}
@can('customers.editar')
    @foreach($customers as $customer)
        <div id="edit-customer-modal-{{ $customer->id_customer }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-surface rounded-3xl p-6 max-w-3xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-4 mb-6">
                    <h3 class="text-xl font-extrabold text-slate-800">Editar Cliente — {{ $customer->code }}</h3>
                    <button type="button" onclick="closeModal('edit-customer-modal-{{ $customer->id_customer }}')" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
                </div>
                <form action="{{ route('customers.update', $customer) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')
                    @include('customers._form', ['customer' => $customer, 'prefix' => 'edit_customer_'.$customer->id_customer.'_'])
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" onclick="closeModal('edit-customer-modal-{{ $customer->id_customer }}')" class="px-6 py-2.5 rounded-xl bg-sunken text-muted font-bold text-sm">Cancelar</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endcan

<script>
    function setupCustomerGeo(prefix) {
        const deptSelect = document.getElementById(prefix + 'id_department');
        const muniSelect = document.getElementById(prefix + 'id_municipality');
        const distSelect = document.getElementById(prefix + 'id_district');
        if (!deptSelect || !muniSelect || !distSelect) return;

        const allMunis = Array.from(muniSelect.options).filter(o => o.value !== '');
        const allDists = Array.from(distSelect.options).filter(o => o.value !== '');

        function filterMunis() {
            const deptId = deptSelect.value;
            const current = muniSelect.value;
            muniSelect.innerHTML = '<option value="">Seleccione municipio</option>';
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            allMunis.filter(o => o.getAttribute('data-parent') === deptId).forEach(o => muniSelect.appendChild(o.cloneNode(true)));
            if (Array.from(muniSelect.options).some(o => o.value === current)) { muniSelect.value = current; filterDists(); }
        }
        function filterDists() {
            const muniId = muniSelect.value;
            const current = distSelect.value;
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            allDists.filter(o => o.getAttribute('data-parent') === muniId).forEach(o => distSelect.appendChild(o.cloneNode(true)));
            if (Array.from(distSelect.options).some(o => o.value === current)) distSelect.value = current;
        }
        deptSelect.addEventListener('change', filterMunis);
        muniSelect.addEventListener('change', filterDists);
        if (deptSelect.value) filterMunis();
        if (muniSelect.value) filterDists();
    }

    function toggleCustomerNrc(prefix) {
        const typeSelect = document.getElementById(prefix + 'customer_type');
        const nrcWrap = document.getElementById(prefix + 'nrc_wrap');
        if (!typeSelect || !nrcWrap) return;
        const apply = () => nrcWrap.classList.toggle('hidden', typeSelect.value !== 'taxpayer');
        typeSelect.addEventListener('change', apply);
        apply();
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupCustomerGeo('create_customer_');
        toggleCustomerNrc('create_customer_');
        document.querySelectorAll('[id^="edit-customer-modal-"]').forEach(modal => {
            const id = modal.id.replace('edit-customer-modal-', '');
            setupCustomerGeo('edit_customer_' + id + '_');
            toggleCustomerNrc('edit_customer_' + id + '_');
        });

        document.querySelectorAll('.delete-customer-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const url = button.dataset.url;
                const name = button.dataset.name;
                const isActive = button.dataset.active === '1';
                if (typeof confirmDelete === 'function') {
                    confirmDelete(url, `Cliente ${name}`, !isActive);
                } else if (confirm(`¿Seguro que desea ${isActive ? 'desactivar' : 'reactivar'} el cliente "${name}"?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''}"><input type="hidden" name="_method" value="DELETE">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
