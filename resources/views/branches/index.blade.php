@extends('layouts.app')
@section('title', 'Gestión de Sucursales')
@section('content')
<div class="animate-fade-in duration-300">

    <!-- Encabezado de Página -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 dark:text-slate-100 tracking-tight transition-colors duration-300">Gestión de Sucursales</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold mt-1">Administra y registra las sucursales, sucursales físicas y asignación de empresas.</p>
        </div>
        @can('branches.crear')
        <button type="button" onclick="openModal('create-branch-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Registrar Nueva Sucursal</span>
        </button>
        @endcan
    </header>

    <!-- Listado de Sucursales -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Empresa</th>
                    <th class="py-3 px-6">Nombre de Sucursal</th>
                    <th class="py-3 px-6">Ubicación / Geografía</th>
                    <th class="py-3 px-6">Teléfono / Email</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 rounded-l-2xl border-l border-y border-slate-100 dark:border-slate-700/80 text-sm font-semibold text-slate-700 dark:text-slate-300 transition-colors duration-300">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/60">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span class="font-bold text-slate-900 dark:text-slate-100">{{ $branch->company->name ?? 'Sin empresa' }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm font-bold text-slate-950 dark:text-slate-100 transition-colors duration-300">
                            {{ $branch->name }}
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300">
                            <span class="text-xs block text-slate-500 dark:text-slate-400 font-medium truncate max-w-[200px]" title="{{ $branch->address }}">{{ $branch->address }}</span>
                            @if($branch->department || $branch->municipality || $branch->district)
                                <span class="text-[10px] bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-300 font-bold px-2 py-0.5 rounded mt-1 inline-block">
                                    {{ $branch->department?->name }} / {{ $branch->municipality?->name }} / {{ $branch->district?->name }}
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300">
                            <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $branch->phone ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-400 mt-0.5">{{ $branch->email ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-center transition-colors duration-300">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $branch->is_active ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/60' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-100 dark:border-rose-800/60' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $branch->is_active ? 'bg-emerald-500 dark:bg-emerald-400' : 'bg-rose-500 dark:bg-rose-400' }}"></span>
                                {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 rounded-r-2xl border-r border-y border-slate-100 dark:border-slate-700/80 text-center transition-colors duration-300">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Editar -->
                                @can('branches.editar')
                                <button type="button" onclick="openEditBranchModal('{{ route('branches.update', $branch) }}', {{ json_encode($branch) }})" class="p-2 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-100/50 dark:border-blue-800/60 rounded-xl transition-all" title="Editar Sucursal">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                @endcan
                                <!-- Eliminar -->
                                @can('branches.eliminar')
                                <button type="button" onclick="confirmDelete('{{ route('branches.destroy', $branch) }}', '{{ $branch->name }}')" class="p-2 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-100/50 dark:border-rose-800/60 rounded-xl transition-all" title="Eliminar Sucursal">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 text-center text-slate-400 dark:text-slate-500 font-semibold shadow-sm transition-colors duration-300">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>No se encontraron sucursales registradas.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- MODAL DE REGISTRO DE SUCURSAL -->
<div id="create-branch-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/75 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-8 max-w-3xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-branch-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 dark:bg-sky-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">Registrar Nueva Sucursal</h2>
                <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Ingresa los datos para habilitar una nueva sucursal física en el sistema.</p>
            </div>
        </div>

        <form action="{{ route('branches.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">
            <!-- Fila 1: Empresa y Nombre -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="company_id" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Empresa <span class="text-rose-500">*</span></label>
                    <select name="company_id" id="company_id" class="w-full bg-slate-50 dark:bg-slate-900 border @error('company_id') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 font-semibold" required>
                        <option value="">Seleccione una empresa</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ (old('modal_type') === 'create' && old('company_id') == $company->id) ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Nombre de la Sucursal <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('modal_type') === 'create' ? old('name') : '' }}" placeholder="Ej. Sucursal Central, San Salvador" class="w-full bg-slate-50 dark:bg-slate-900 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                    @error('name')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <!-- Fila 2: Teléfono y Correo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Teléfono</label>
                    <input type="text" name="phone" id="phone" value="{{ old('modal_type') === 'create' ? old('phone') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 dark:bg-slate-900 border @error('phone') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold">
                    @error('phone')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Correo electrónico</label>
                    <input type="email" name="email" id="email" value="{{ old('modal_type') === 'create' ? old('email') : '' }}" placeholder="Ej. sucursal@empresa.com" class="w-full bg-slate-50 dark:bg-slate-900 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold">
                    @error('email')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <!-- Fila 3: Ubicación Geográfica (El Salvador) -->
            <div class="p-4 bg-slate-50/50 dark:bg-slate-900/60 rounded-2xl border border-slate-100 dark:border-slate-700 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] dark:text-teal-400 uppercase tracking-wider">Ubicación Geográfica (El Salvador)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="create_department_id" class="block text-[10px] font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-1.5">Departamento</label>
                        <select name="department_id" id="create_department_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 dark:text-slate-100 font-semibold">
                            <option value="">Seleccione departamento</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="create_municipality_id" class="block text-[10px] font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-1.5">Municipio</label>
                        <select name="municipality_id" id="create_municipality_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 dark:text-slate-100 font-semibold">
                            <option value="">Seleccione municipio</option>
                            @foreach($municipalities as $muni)
                                <option value="{{ $muni->id }}" data-parent="{{ $muni->department_id }}" {{ old('municipality_id') == $muni->id ? 'selected' : '' }}>{{ $muni->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="create_district_id" class="block text-[10px] font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-1.5">Distrito</label>
                        <select name="district_id" id="create_district_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 dark:text-slate-100 font-semibold">
                            <option value="">Seleccione distrito</option>
                            @foreach($districts as $dist)
                                <option value="{{ $dist->id }}" data-parent="{{ $dist->municipality_id }}" {{ old('district_id') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Fila 4: Dirección -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="address" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Dirección Detallada <span class="text-rose-500">*</span></label>
                    <textarea name="address" id="address" rows="2" placeholder="Ej. Alameda Manuel Enrique Araujo, San Salvador" class="w-full bg-slate-50 dark:bg-slate-900 border @error('address') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>{{ old('modal_type') === 'create' ? old('address') : '' }}</textarea>
                    @error('address')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeModal('create-branch-modal')" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Sucursal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE SUCURSAL -->
<div id="edit-branch-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/75 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-8 max-w-3xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-branch-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] dark:bg-sky-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">Editar Sucursal</h2>
                <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Modifica los detalles fiscales, notas e información geográfica de la sucursal.</p>
            </div>
        </div>

        <form action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <!-- Fila 1: Empresa y Nombre -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit-company_id" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Empresa <span class="text-rose-500">*</span></label>
                    <select name="company_id" id="edit-company_id" class="w-full bg-slate-50 dark:bg-slate-900 border @error('company_id') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 font-semibold" required>
                        <option value="">Seleccione una empresa</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <label for="edit-name" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Nombre de la Sucursal <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('name') : '' }}" placeholder="Ej. Sucursal Central, San Salvador" class="w-full bg-slate-50 dark:bg-slate-900 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>
                    @error('name')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <!-- Fila 2: Teléfono y Correo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit-phone" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Teléfono</label>
                    <input type="text" name="phone" id="edit-phone" value="{{ old('modal_type') === 'edit' ? old('phone') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 dark:bg-slate-900 border @error('phone') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold">
                    @error('phone')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <label for="edit-email" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Correo electrónico</label>
                    <input type="email" name="email" id="edit-email" value="{{ old('modal_type') === 'edit' ? old('email') : '' }}" placeholder="Ej. sucursal@empresa.com" class="w-full bg-slate-50 dark:bg-slate-900 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold">
                    @error('email')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <!-- Fila 3: Ubicación Geográfica (El Salvador) -->
            <div class="p-4 bg-slate-50/50 dark:bg-slate-900/60 rounded-2xl border border-slate-100 dark:border-slate-700 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] dark:text-teal-400 uppercase tracking-wider">Ubicación Geográfica (El Salvador)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="edit_department_id" class="block text-[10px] font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-1.5">Departamento</label>
                        <select name="department_id" id="edit_department_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 dark:text-slate-100 font-semibold">
                            <option value="">Seleccione departamento</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit_municipality_id" class="block text-[10px] font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-1.5">Municipio</label>
                        <select name="municipality_id" id="edit_municipality_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 dark:text-slate-100 font-semibold">
                            <option value="">Seleccione municipio</option>
                            @foreach($municipalities as $muni)
                                <option value="{{ $muni->id }}" data-parent="{{ $muni->department_id }}">{{ $muni->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit_district_id" class="block text-[10px] font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-1.5">Distrito</label>
                        <select name="district_id" id="edit_district_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 dark:text-slate-100 font-semibold">
                            <option value="">Seleccione distrito</option>
                            @foreach($districts as $dist)
                                <option value="{{ $dist->id }}" data-parent="{{ $dist->municipality_id }}">{{ $dist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Fila 4: Dirección -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit-address" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Dirección Detallada <span class="text-rose-500">*</span></label>
                    <textarea name="address" id="edit-address" rows="2" placeholder="Ej. Alameda Manuel Enrique Araujo, San Salvador" class="w-full bg-slate-50 dark:bg-slate-900 border @error('address') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required>{{ old('modal_type') === 'edit' ? old('address') : '' }}</textarea>
                    @error('address')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-3">Estado de la Sucursal</label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="edit-is_active" value="1" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-slate-200 dark:bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    <span class="text-sm font-semibold text-slate-600 dark:text-slate-300" id="edit-is_active_label">Sucursal Activa</span>
                </label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeModal('edit-branch-modal')" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let createGeographicFilter, editGeographicFilter;
    function setupGeographicFilters(prefix) {
        const deptSelect = document.getElementById(`${prefix}department_id`);
        const muniSelect = document.getElementById(`${prefix}municipality_id`);
        const distSelect = document.getElementById(`${prefix}district_id`);

        if (!deptSelect || !muniSelect || !distSelect) return null;

        const allMunis = Array.from(muniSelect.options).filter(opt => opt.value !== "");
        const allDists = Array.from(distSelect.options).filter(opt => opt.value !== "");

        function filterMunicipalities() {
            const deptId = deptSelect.value;
            muniSelect.innerHTML = '<option value="">Seleccione municipio</option>';
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            const filteredMunis = allMunis.filter(opt => opt.getAttribute('data-parent') === deptId);
            filteredMunis.forEach(opt => muniSelect.appendChild(opt.cloneNode(true)));
        }

        function filterDistricts() {
            const muniId = muniSelect.value;
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            const filteredDists = allDists.filter(opt => opt.getAttribute('data-parent') === muniId);
            filteredDists.forEach(opt => distSelect.appendChild(opt.cloneNode(true)));
        }

        deptSelect.addEventListener('change', filterMunicipalities);
        muniSelect.addEventListener('change', filterDistricts);

        return {
            setValues: (deptId, muniId, distId) => {
                deptSelect.value = deptId || "";
                filterMunicipalities();
                muniSelect.value = muniId || "";
                filterDistricts();
                distSelect.value = distId || "";
            }
        };
    }

    function openEditBranchModal(actionUrl, branch) {
        const modal = document.getElementById('edit-branch-modal');
        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = branch.id;
        document.getElementById('edit-company_id').value = branch.company_id;
        document.getElementById('edit-name').value = branch.name;
        document.getElementById('edit-address').value = branch.address;
        document.getElementById('edit-phone').value = branch.phone || '';
        document.getElementById('edit-email').value = branch.email || '';
        
        const isActiveChk = document.getElementById('edit-is_active');
        isActiveChk.checked = branch.is_active == 1;
        
        const label = document.getElementById('edit-is_active_label');
        label.textContent = branch.is_active == 1 ? 'Sucursal Activa' : 'Sucursal Inactiva';

        if (editGeographicFilter) {
            editGeographicFilter.setValues(branch.department_id, branch.municipality_id, branch.district_id);
        }
        openModal('edit-branch-modal');
    }

    document.addEventListener('DOMContentLoaded', () => {
        createGeographicFilter = setupGeographicFilters('create_');
        editGeographicFilter = setupGeographicFilters('edit_');
        const isActiveChk = document.getElementById('edit-is_active');
        const label = document.getElementById('edit-is_active_label');
        if (isActiveChk && label) {
            isActiveChk.addEventListener('change', () => {
                label.textContent = isActiveChk.checked ? 'Sucursal Activa' : 'Sucursal Inactiva';
            });
        }
    });
</script>

@if($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            @if(old('modal_type') === 'edit')
                const editRoute = "{{ route('branches.update', old('id', 0)) }}";
                const oldBranch = {
                    id: "{{ old('id') }}",
                    company_id: "{{ old('company_id') }}",
                    name: "{{ old('name') }}",
                    address: "{{ old('address') }}",
                    department_id: "{{ old('department_id') }}",
                    municipality_id: "{{ old('municipality_id') }}",
                    district_id: "{{ old('district_id') }}",
                    phone: "{{ old('phone') }}",
                    email: "{{ old('email') }}",
                    is_active: "{{ old('is_active', '0') }}"
                };
                openEditBranchModal(editRoute, oldBranch);
            @else
                openModal('create-branch-modal');
                if (createGeographicFilter) {
                    createGeographicFilter.setValues(
                        "{{ old('department_id') }}", 
                        "{{ old('municipality_id') }}", 
                        "{{ old('district_id') }}"
                    );
                }
            @endif
        });
    </script>
@endif
@endsection