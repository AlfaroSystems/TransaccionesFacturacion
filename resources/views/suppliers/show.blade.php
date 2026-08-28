@extends('layouts.app')
@section('title', 'Detalle de Proveedor')
@section('content')
<div class="animate-fade-in duration-300">
    {{-- Encabezado --}}
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">
                    {{ $supplier->name }}
                </h1>
                @if($supplier->is_active)
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-full border border-emerald-200">
                        Activo
                    </span>
                @else
                    <span class="px-3 py-1 bg-rose-100 text-rose-800 text-xs font-bold rounded-full border border-rose-200">
                        Inactivo
                    </span>
                @endif
            </div>
            <p class="text-slate-400 text-sm font-semibold mt-1">
                Ficha de detalle del proveedor y sus contactos comerciales asociados.
            </p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('suppliers.index') }}"
                class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold text-sm transition-all">
                ← Regresar
            </a>
            <button type="button"
                onclick="openModal('edit-supplier-modal-{{ $supplier->id_supplier }}')"
                class="px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md transition-all">
                Editar Proveedor
            </button>
        </div>
    </header>

    {{-- Datos Generales --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 mb-6">
        <h3 class="text-sm font-extrabold text-[#005e66] uppercase tracking-wider mb-5">
            Datos Generales
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Código</p>
                <p class="text-sm font-extrabold text-[#005e66] font-mono">{{ $supplier->code ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Nombre del Proveedor</p>
                <p class="text-sm font-semibold text-slate-700">{{ $supplier->name }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">País</p>
                <p class="text-sm font-semibold text-slate-700">{{ $supplier->country }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Correo Electrónico</p>
                <p class="text-sm font-semibold text-slate-700 font-mono">{{ $supplier->email }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Teléfono</p>
                <p class="text-sm font-semibold text-slate-700">{{ $supplier->phone ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Sitio Web</p>
                @if($supplier->website)
                    <a href="{{ $supplier->website }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-sm font-semibold text-[#005e66] hover:underline break-all">
                        {{ $supplier->website }}
                    </a>
                @else
                    <p class="text-sm font-semibold text-slate-700">-</p>
                @endif
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Estado</p>
                <p class="text-sm font-semibold text-slate-700">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</p>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Registrado el</p>
                <p class="text-sm font-semibold text-slate-700">
                    {{ $supplier->created_at?->format('d/m/Y h:i A') ?? '-' }}
                </p>
            </div>
        </div>

        @if($supplier->address)
            <div class="mt-6 pt-6 border-t border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Dirección</p>
                <p class="text-sm font-semibold text-slate-700 leading-relaxed">{{ $supplier->address }}</p>
            </div>
        @endif
    </div>

    {{-- Contactos Asociados --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-extrabold text-[#005e66] uppercase tracking-wider">
                Contactos Asociados
            </h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-cyan-100 text-cyan-800 text-xs font-bold">
                {{ $supplier->contacts->count() }} {{ Str::plural('contacto', $supplier->contacts->count()) }}
            </span>
        </div>

        @if($supplier->contacts->isEmpty())
            <p class="text-center text-slate-400 font-semibold py-10">
                Este proveedor no tiene contactos registrados.
            </p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($supplier->contacts as $contact)
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-[#005e66] text-white flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($contact->full_name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-700 text-sm">
                                    {{ $contact->full_name }}
                                </p>
                                @if(isset($contact->is_active))
                                    <span class="text-[10px] font-bold uppercase {{ $contact->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                        {{ $contact->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-slate-500">
                                <span class="font-bold text-slate-400 uppercase">Tel:</span>
                                {{ $contact->phone }}
                            </p>
                            <p class="text-xs text-slate-500">
                                <span class="font-bold text-slate-400 uppercase">Correo:</span>
                                {{ $contact->email ?? '-' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- MODAL EDICIÓN PROVEEDOR --}}
@can('suppliers.editar')
    @php
        $supplierId = $supplier->id_supplier;
    @endphp
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

    <script>
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

        document.addEventListener('DOMContentLoaded', function () {
            setupSupplierGeographicFilter('edit_supplier_{{ $supplierId }}_');
        });
    </script>
@endcan
@endsection