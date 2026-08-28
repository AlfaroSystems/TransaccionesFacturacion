@extends('layouts.app')
@section('title', 'Gestión de Proveedores')
@section('content')
<div class="animate-fade-in duration-300">
    {{-- ENCABEZADO --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">
                Gestión de Proveedores
            </h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">
                Administra los proveedores registrados y sus contactos comerciales asociados.
            </p>
        </div>

        @can('suppliers.crear')
            <button
                type="button"
                onclick="openModal('create-supplier-modal')"
                class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Nuevo Proveedor</span>
            </button>
        @endcan
    </header>

    {{-- BUSCADOR --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('suppliers.index') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Buscar por código, nombre, país o correo..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold rounded-xl text-sm transition-all">
                    Buscar
                </button>
                @if(request('search'))
                    <a href="{{ route('suppliers.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl text-sm transition-all">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- LISTADO --}}
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Código / Proveedor</th>
                    <th class="py-3 px-6">País</th>
                    <th class="py-3 px-6">Correo Electrónico</th>
                    <th class="py-3 px-6">Teléfono</th>
                    <th class="py-3 px-6 text-center">Contactos</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200 {{ !$supplier->is_active ? 'opacity-50 grayscale-[35%]' : '' }}">
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm">
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="font-bold text-[#005e66] text-xs">{{ $supplier->code }}</span>
                                @if(!$supplier->is_active)
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-400 border border-rose-200 dark:border-rose-800 uppercase tracking-wider">Inactivo</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 uppercase tracking-wider">Activo</span>
                                @endif
                            </div>
                            <span class="font-extrabold text-slate-800 block">{{ $supplier->name }}</span>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600 font-semibold">
                            {{ $supplier->country }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600 font-mono">
                            {{ $supplier->email }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600">
                            {{ $supplier->phone ?? '-' }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-teal-50 text-[#005e66] border border-teal-100 text-xs font-bold">
                                {{ $supplier->contacts->count() }} {{ $supplier->contacts->count() === 1 ? 'contacto' : 'contactos' }}
                            </span>
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('suppliers.ver')
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 font-semibold text-xs transition-all" title="Ver Detalles">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                @endcan
                                @can('suppliers.editar')
                                    <button
                                        type="button"
                                        onclick="openModal('edit-supplier-modal-{{ $supplier->id_supplier }}')"
                                        class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 font-semibold text-xs transition-all"
                                        title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                @endcan
                                @can('suppliers.eliminar')
                                    @if($supplier->is_active)
                                        <button
                                            type="button"
                                            class="delete-supplier-btn p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 font-semibold text-xs transition-all"
                                            title="Inactivar Proveedor"
                                            data-url="{{ route('suppliers.destroy', $supplier) }}"
                                            data-name="{{ $supplier->name }}"
                                            data-active="1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="delete-supplier-btn p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-semibold text-xs transition-all"
                                            title="Reactivar Proveedor"
                                            data-url="{{ route('suppliers.destroy', $supplier) }}"
                                            data-name="{{ $supplier->name }}"
                                            data-active="0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">
                            No se encontraron proveedores registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{-- PAGINACIÓN --}}
    @if($suppliers->hasPages())
        <div class="mt-6">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>

{{-- MODALES DE EDICIÓN DE PROVEEDOR --}}
@foreach($suppliers as $supplier)
    @php
        $supplierId = $supplier->id_supplier;
    @endphp
    @can('suppliers.editar')
        <div id="edit-supplier-modal-{{ $supplierId }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-6 max-w-4xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-4 mb-6">
                    <div>
                        <h3 class="text-xl font-extrabold text-slate-800">
                            Editar Proveedor
                        </h3>
                        <p class="text-xs font-semibold text-slate-400 mt-1">
                            Modifica los datos del proveedor y sus contactos asociados.
                        </p>
                    </div>
                    <button type="button" onclick="closeModal('edit-supplier-modal-{{ $supplierId }}')" class="text-slate-400 hover:text-slate-600 text-xl">
                        ✕
                    </button>
                </div>
                <form action="{{ route('suppliers.update', $supplier) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4 relative z-30">
                        <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">
                            Datos Generales
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                    Código *
                                </label>
                                <input
                                    type="text"
                                    name="code"
                                    value="{{ old('code', $supplier->code) }}"
                                    required
                                    maxlength="20"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                    Nombre del Proveedor *
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $supplier->name) }}"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                    Correo Electrónico *
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $supplier->email) }}"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                    Teléfono
                                </label>
                                <input
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone', $supplier->phone) }}"
                                    maxlength="20"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                    Sitio Web
                                </label>
                                <input
                                    type="url"
                                    name="website"
                                    value="{{ old('website', $supplier->website) }}"
                                    maxlength="255"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 relative z-50">
                            <div>
                                @php
                                    $dbCountries = \App\Models\Country::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
                                    $countriesList = !empty($dbCountries) ? $dbCountries : config('countries', []);
                                @endphp
                                <div x-data="{
                                    open: false,
                                    search: '',
                                    selected: '{{ old('country', $supplier->country) }}',
                                    countries: {{ json_encode($countriesList) }},
                                    get filteredCountries() {
                                        if (!this.search.trim()) return this.countries;
                                        return this.countries.filter(c => c.toLowerCase().includes(this.search.toLowerCase().trim()));
                                    },
                                    selectCountry(c) {
                                        this.selected = c;
                                        this.open = false;
                                        this.search = '';
                                        $nextTick(() => {
                                            const input = $refs.hiddenInput;
                                            if (input) {
                                                input.dispatchEvent(new Event('change', { bubbles: true }));
                                            }
                                        });
                                    }
                                }" class="relative z-50">
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                        País *
                                    </label>

                                    <input type="hidden" 
                                        name="country" 
                                        id="edit_supplier_country_{{ $supplierId }}" 
                                        x-ref="hiddenInput" 
                                        :value="selected" 
                                        onchange="toggleSupplierGeographic(this, 'edit_supplier_geo_section_{{ $supplierId }}')">

                                    <button type="button" 
                                            @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                            @click.away="open = false"
                                            class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-semibold text-slate-700 dark:text-slate-100 focus:outline-none focus:border-[#005e66] transition-all">
                                        <span x-text="selected || 'Seleccionar país'" :class="!selected ? 'text-slate-400 dark:text-slate-500' : ''"></span>
                                        <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <div x-show="open" 
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute z-[100] left-0 right-0 top-full mt-1.5 w-full rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden"
                                        style="display: none; max-height: 230px;">

                                        <div class="p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/60">
                                            <input type="text" 
                                                    x-ref="searchInput"
                                                    x-model="search" 
                                                    placeholder="Buscar país..." 
                                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs focus:outline-none focus:border-[#005e66] bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-100 placeholder-slate-400">
                                        </div>

                                        <div style="max-height: 180px; overflow-y: auto;" class="p-2 pb-4 space-y-1 custom-scrollbar">
                                            <template x-for="c in filteredCountries" :key="c">
                                                <button type="button" 
                                                        @click="selectCountry(c)"
                                                        style="padding-left: 1.25rem; padding-right: 1rem; padding-top: 0.6rem; padding-bottom: 0.6rem;"
                                                        class="w-full text-left px-5 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center justify-between"
                                                        :class="selected === c ? 'bg-[#005e66]/15 text-[#005e66] dark:bg-teal-500/20 dark:text-teal-300 font-bold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'">
                                                    <span x-text="c" class="truncate pr-2"></span>
                                                    <svg x-show="selected === c" class="w-4 h-4 text-[#005e66] dark:text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </template>
                                            <div x-show="filteredCountries.length === 0" class="px-4 py-3 text-xs text-slate-400 dark:text-slate-500 text-center font-medium">
                                                No se encontraron países
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                    Estado
                                </label>
                                <select name="is_active" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    <option value="1" {{ old('is_active', $supplier->is_active) ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ !old('is_active', $supplier->is_active) ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        {{-- Ubicación Geográfica (El Salvador) --}}
                        @php
                            $editSelectedCountry = old('country', $supplier->country);
                            $editShowGeo = trim($editSelectedCountry) === 'El Salvador';
                        @endphp
                        <div id="edit_supplier_geo_section_{{ $supplierId }}" class="{{ $editShowGeo ? '' : 'hidden' }} p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                            <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Ubicación Geográfica (El Salvador)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label for="edit_supplier_{{ $supplierId }}_department_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Departamento</label>
                                    <select name="department_id" id="edit_supplier_{{ $supplierId }}_department_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                                        <option value="">Seleccione departamento</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" {{ old('department_id', $supplier->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_supplier_{{ $supplierId }}_municipality_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Municipio</label>
                                    <select name="municipality_id" id="edit_supplier_{{ $supplierId }}_municipality_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                                        <option value="">Seleccione municipio</option>
                                        @foreach($municipalities as $muni)
                                            <option value="{{ $muni->id }}" data-parent="{{ $muni->department_id }}" {{ old('municipality_id', $supplier->municipality_id) == $muni->id ? 'selected' : '' }}>{{ $muni->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_supplier_{{ $supplierId }}_district_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Distrito</label>
                                    <select name="district_id" id="edit_supplier_{{ $supplierId }}_district_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                                        <option value="">Seleccione distrito</option>
                                        @foreach($districts as $dist)
                                            <option value="{{ $dist->id }}" data-parent="{{ $dist->municipality_id }}" {{ old('district_id', $supplier->district_id) == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                Dirección
                            </label>
                            <textarea name="address" rows="2" maxlength="255" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">{{ old('address', $supplier->address) }}</textarea>
                        </div>
                    </div>

                    {{-- CONTACTOS --}}
                    <div class="space-y-4 pt-5 border-t relative z-10">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">
                                    Contactos del Proveedor
                                </h4>
                                <p class="text-xs text-slate-400 mt-1">
                                    Puedes modificar o agregar contactos asociados a este proveedor.
                                </p>
                            </div>
                            <button
                                type="button"
                                onclick="addEditContactRow({{ $supplierId }})"
                                class="px-3 py-1.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-lg text-xs font-bold transition-all whitespace-nowrap">
                                + Agregar Contacto
                            </button>
                        </div>

                        <div
                            id="edit-contacts-container-{{ $supplierId }}"
                            data-next-index="{{ $supplier->contacts->count() }}"
                            class="space-y-3">
                            @forelse($supplier->contacts as $index => $contact)
                                <div class="edit-contact-row grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <input type="hidden" name="contacts[{{ $index }}][id_contact]" value="{{ $contact->id_contact }}">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                            Nombre completo *
                                        </label>
                                        <input
                                            type="text"
                                            name="contacts[{{ $index }}][full_name]"
                                            value="{{ old('contacts.' . $index . '.full_name', $contact->full_name) }}"
                                            required
                                            placeholder="Nombre completo"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                            Teléfono *
                                        </label>
                                        <input
                                            type="text"
                                            name="contacts[{{ $index }}][phone]"
                                            value="{{ old('contacts.' . $index . '.phone', $contact->phone) }}"
                                            required
                                            placeholder="Teléfono"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                            Correo
                                        </label>
                                        <input
                                            type="email"
                                            name="contacts[{{ $index }}][email]"
                                            value="{{ old('contacts.' . $index . '.email', $contact->email) }}"
                                            placeholder="Correo electrónico"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                    <div class="flex items-end">
                                        <button
                                            type="button"
                                            onclick="removeEditContactRow(this)"
                                            class="w-full px-3 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg text-xs font-bold transition-all">
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="edit-contact-empty p-4 rounded-xl bg-amber-50 border border-amber-100 text-amber-700 text-xs font-semibold">
                                    Este proveedor no tiene contactos registrados. Agrega al menos un contacto.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-5 border-t">
                        <button type="button" onclick="closeModal('edit-supplier-modal-{{ $supplierId }}')" class="px-6 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold text-sm">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach

{{-- MODAL CREAR PROVEEDOR --}}
<div id="create-supplier-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 max-w-4xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b pb-4 mb-6">
            <div>
                <h3 class="text-xl font-extrabold text-slate-800">
                    Registrar Nuevo Proveedor
                </h3>
                <p class="text-xs font-semibold text-slate-400 mt-1">
                    Completa todos los datos de la empresa y sus contactos comerciales.
                </p>
            </div>
            <button type="button" onclick="closeModal('create-supplier-modal')" class="text-slate-400 hover:text-slate-600 text-xl">
                ✕
            </button>
        </div>

        <form action="{{ route('suppliers.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="space-y-4 relative z-30">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">
                    Datos Generales
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                            Código *
                        </label>
                        <input
                            type="text"
                            name="code"
                            value="{{ old('code') }}"
                            required
                            maxlength="20"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"
                            placeholder="Ej. PROV001">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                            Nombre del Proveedor *
                        </label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"
                            placeholder="Ej. Distribuidora Andina S.A.">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                            Correo Electrónico *
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"
                            placeholder="contacto@proveedor.com">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                            Teléfono
                        </label>
                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            maxlength="20"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"
                            placeholder="Ej. 2222-2222">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                            Sitio Web
                        </label>
                        <input
                            type="url"
                            name="website"
                            value="{{ old('website') }}"
                            maxlength="255"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"
                            placeholder="https://proveedor.com">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 relative z-50">
                    <div>
                        @php
                            $dbCountries = \App\Models\Country::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
                            $countriesList = !empty($dbCountries) ? $dbCountries : config('countries', []);
                        @endphp
                        <div x-data="{
                            open: false,
                            search: '',
                            selected: '{{ old('country') }}',
                            countries: {{ json_encode($countriesList) }},
                            get filteredCountries() {
                                if (!this.search.trim()) return this.countries;
                                return this.countries.filter(c => c.toLowerCase().includes(this.search.toLowerCase().trim()));
                            },
                            selectCountry(c) {
                                this.selected = c;
                                this.open = false;
                                this.search = '';
                                $nextTick(() => {
                                    const input = $refs.hiddenInput;
                                    if (input) {
                                        input.dispatchEvent(new Event('change', { bubbles: true }));
                                    }
                                });
                            }
                        }" class="relative z-50">
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                                País *
                            </label>

                            <input type="hidden" 
                                    name="country" 
                                    id="create_supplier_country" 
                                    x-ref="hiddenInput" 
                                    :value="selected" 
                                    onchange="toggleSupplierGeographic(this, 'create_supplier_geo_section')">

                            <button type="button" 
                                    @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                    @click.away="open = false"
                                    class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-semibold text-slate-700 dark:text-slate-100 focus:outline-none focus:border-[#005e66] transition-all">
                                <span x-text="selected || 'Seleccionar país'" :class="!selected ? 'text-slate-400 dark:text-slate-500' : ''"></span>
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open" 
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-[100] left-0 right-0 top-full mt-1.5 w-full rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden"
                                style="display: none; max-height: 230px;">

                                <div class="p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/60">
                                    <input type="text" 
                                        x-ref="searchInput"
                                        x-model="search" 
                                        placeholder="Buscar país..." 
                                        class="w-full px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs focus:outline-none focus:border-[#005e66] bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-100 placeholder-slate-400">
                                </div>

                                <div style="max-height: 180px; overflow-y: auto;" class="p-2 pb-4 space-y-1 custom-scrollbar">
                                    <template x-for="c in filteredCountries" :key="c">
                                        <button type="button" 
                                                @click="selectCountry(c)"
                                                style="padding-left: 1.25rem; padding-right: 1rem; padding-top: 0.6rem; padding-bottom: 0.6rem;"
                                                class="w-full text-left px-5 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center justify-between"
                                                :class="selected === c ? 'bg-[#005e66]/15 text-[#005e66] dark:bg-teal-500/20 dark:text-teal-300 font-bold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'">
                                            <span x-text="c" class="truncate pr-2"></span>
                                            <svg x-show="selected === c" class="w-4 h-4 text-[#005e66] dark:text-teal-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </template>
                                    <div x-show="filteredCountries.length === 0" class="px-4 py-3 text-xs text-slate-400 dark:text-slate-500 text-center font-medium">
                                        No se encontraron países
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                            Estado
                        </label>
                        <select name="is_active" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                {{-- Ubicación Geográfica (El Salvador) --}}
                <div id="create_supplier_geo_section" class="{{ old('country') === 'El Salvador' ? '' : 'hidden' }} p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                    <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Ubicación Geográfica (El Salvador)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label for="create_supplier_department_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Departamento</label>
                            <select name="department_id" id="create_supplier_department_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                                <option value="">Seleccione departamento</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="create_supplier_municipality_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Municipio</label>
                            <select name="municipality_id" id="create_supplier_municipality_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                                <option value="">Seleccione municipio</option>
                                @foreach($municipalities as $muni)
                                    <option value="{{ $muni->id }}" data-parent="{{ $muni->department_id }}" {{ old('municipality_id') == $muni->id ? 'selected' : '' }}>{{ $muni->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="create_supplier_district_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Distrito</label>
                            <select name="district_id" id="create_supplier_district_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                                <option value="">Seleccione distrito</option>
                                @foreach($districts as $dist)
                                    <option value="{{ $dist->id }}" data-parent="{{ $dist->municipality_id }}" {{ old('district_id') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">
                        Dirección
                    </label>
                    <textarea name="address" rows="2" maxlength="255" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Dirección del proveedor">{{ old('address') }}</textarea>
                </div>
            </div>

            {{-- CONTACTOS INICIALES --}}
            <div class="space-y-4 pt-4 border-t relative z-10">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">
                        Contactos del Proveedor
                    </h4>
                    <button
                        type="button"
                        onclick="addModalContactRow()"
                        class="px-3 py-1.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-lg text-xs font-bold transition-all">
                        + Agregar Contacto
                    </button>
                </div>
                <div id="modal-contacts-container" class="space-y-3">
                    <div class="create-contact-row grid grid-cols-1 md:grid-cols-3 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 relative group">
                        <div>
                            <input
                                type="text"
                                name="contacts[0][full_name]"
                                required
                                placeholder="Nombre completo *"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
                        </div>
                        <div>
                            <input
                                type="text"
                                name="contacts[0][phone]"
                                required
                                placeholder="Teléfono *"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
                        </div>
                        <div>
                            <input
                                type="email"
                                name="contacts[0][email]"
                                placeholder="Correo electrónico"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('create-supplier-modal')" class="px-6 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold text-sm">
                    Guardar Proveedor
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JAVASCRIPT --}}
<script>
    let modalContactIndex = 1;
    function addModalContactRow() {
        const container = document.getElementById('modal-contacts-container');
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'create-contact-row grid grid-cols-1 md:grid-cols-3 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 relative group';
        row.innerHTML = `
            <div>
                <input type="text" name="contacts[${modalContactIndex}][full_name]" required placeholder="Nombre completo *" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
            </div>
            <div>
                <input type="text" name="contacts[${modalContactIndex}][phone]" required placeholder="Teléfono *" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
            </div>
            <div class="flex gap-2">
                <input type="email" name="contacts[${modalContactIndex}][email]" placeholder="Correo electrónico" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:border-[#005e66]">
                <button type="button" onclick="this.closest('.create-contact-row').remove()" class="text-rose-500 hover:text-rose-700 px-2 font-bold text-sm">✕</button>
            </div>
        `;
        container.appendChild(row);
        modalContactIndex++;
    }

    function addEditContactRow(supplierId) {
        const container = document.getElementById('edit-contacts-container-' + supplierId);
        if (!container) return;

        let nextIndex = parseInt(container.dataset.nextIndex || '0', 10);
        const row = document.createElement('div');
        row.className = 'edit-contact-row grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100';

        row.innerHTML = `
            <input type="hidden" name="contacts[${nextIndex}][id_contact]" value="">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nombre completo *</label>
                <input type="text" name="contacts[${nextIndex}][full_name]" required placeholder="Nombre completo" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#005e66]">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Teléfono *</label>
                <input type="text" name="contacts[${nextIndex}][phone]" required placeholder="Teléfono" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#005e66]">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Correo</label>
                <input type="email" name="contacts[${nextIndex}][email]" placeholder="Correo electrónico" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#005e66]">
            </div>
            <div class="flex items-end">
                <button type="button" onclick="removeEditContactRow(this)" class="w-full px-3 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg text-xs font-bold transition-all">Eliminar</button>
            </div>
        `;

        const emptyMessage = container.querySelector('.edit-contact-empty');
        if (emptyMessage) emptyMessage.remove();

        container.appendChild(row);
        container.dataset.nextIndex = nextIndex + 1;
    }

    function removeEditContactRow(button) {
        const row = button.closest('.edit-contact-row');
        if (!row) return;
        const container = row.parentElement;
        const rows = container.querySelectorAll('.edit-contact-row');
        if (rows.length <= 1) {
            alert('El proveedor debe tener al menos un contacto.');
            return;
        }
        row.remove();
    }

    function toggleSupplierGeographic(selectEl, targetId) {
        const geoSection = document.getElementById(targetId);
        if (!geoSection) return;
        const val = (selectEl.value || '').trim();
        if (val === 'El Salvador') {
            geoSection.classList.remove('hidden');
        } else {
            geoSection.classList.add('hidden');
            const selects = geoSection.querySelectorAll('select');
            selects.forEach(s => {
                s.value = '';
                s.dispatchEvent(new Event('change'));
            });
        }
    }

    function setupSupplierGeographicFilter(prefix) {
        const deptSelect = document.getElementById(`${prefix}department_id`);
        const muniSelect = document.getElementById(`${prefix}municipality_id`);
        const distSelect = document.getElementById(`${prefix}district_id`);

        if (!deptSelect || !muniSelect || !distSelect) return;

        const allMunis = Array.from(muniSelect.options).filter(opt => opt.value !== "");
        const allDists = Array.from(distSelect.options).filter(opt => opt.value !== "");

        function filterMunicipalities() {
            const deptId = deptSelect.value;
            const currentMuniVal = muniSelect.value;
            muniSelect.innerHTML = '<option value="">Seleccione municipio</option>';
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            const filteredMunis = allMunis.filter(opt => opt.getAttribute('data-parent') === deptId);
            filteredMunis.forEach(opt => muniSelect.appendChild(opt.cloneNode(true)));
            if (filteredMunis.some(opt => opt.value === currentMuniVal)) {
                muniSelect.value = currentMuniVal;
                filterDistricts();
            }
        }

        function filterDistricts() {
            const muniId = muniSelect.value;
            const currentDistVal = distSelect.value;
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            const filteredDists = allDists.filter(opt => opt.getAttribute('data-parent') === muniId);
            filteredDists.forEach(opt => distSelect.appendChild(opt.cloneNode(true)));
            if (filteredDists.some(opt => opt.value === currentDistVal)) {
                distSelect.value = currentDistVal;
            }
        }

        deptSelect.addEventListener('change', filterMunicipalities);
        muniSelect.addEventListener('change', filterDistricts);

        if (deptSelect.value) filterMunicipalities();
        if (muniSelect.value) filterDistricts();
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupSupplierGeographicFilter('create_supplier_');

        document.querySelectorAll('[id^="edit_supplier_geo_section_"]').forEach(section => {
            const supplierId = section.id.replace('edit_supplier_geo_section_', '');
            setupSupplierGeographicFilter(`edit_supplier_${supplierId}_`);
        });

        const deleteButtons = document.querySelectorAll('.delete-supplier-btn');
        deleteButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const url = button.dataset.url;
                const name = button.dataset.name;
                const isActive = button.dataset.active === '1';
                const actionText = isActive ? 'inactivar' : 'reactivar';

                if (typeof confirmDelete === 'function') {
                    confirmDelete(url, `Proveedor ${name}`, `Se va a ${actionText} este proveedor.`);
                } else {
                    if (confirm(`¿Está seguro de ${actionText} el proveedor "${name}"?`)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        const method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'DELETE';
                        form.appendChild(csrf);
                        form.appendChild(method);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            });
        });
    });
</script>
@endsection