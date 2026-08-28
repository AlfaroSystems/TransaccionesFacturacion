@extends('layouts.app')

@section('title', 'Gestión de Empresas')

@section('content')
<div
    id="companies-page"
    class="animate-fade-in duration-300"
    data-update-url-template="{{ route('companies.update', ':id') }}"
    data-modal-state="{{ json_encode([
        'hasErrors' => $errors->any(),
        'modalType' => old('modal_type'),
        'id' => old('id'),
        'name' => old('name'),
        'commercial_name' => old('commercial_name'),
        'nit' => old('nit'),
        'nrc' => old('nrc'),
        'commercial_line_1' => old('commercial_line_1'),
        'commercial_line_2' => old('commercial_line_2'),
        'commercial_line_3' => old('commercial_line_3'),
        'web_site' => old('web_site'),
        'phone' => old('phone'),
        'email' => old('email'),
        'address' => old('address'),
        'department_id' => old('department_id'),
        'municipality_id' => old('municipality_id'),
        'district_id' => old('district_id'),
        'is_active' => (bool) old('is_active'),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
>

    <!-- Encabezado de pagina -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 dark:text-slate-100 tracking-tight transition-colors duration-300">Gestión de Empresas</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold mt-1">Registra y administra la información fiscal, giros comerciales, ubicaciones geográficas y logos de las empresas.</p>
        </div>

        @can('companies.crear')
        <button type="button" onclick="openModal('create-company-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Crear Nueva Empresa</span>
        </button>
        @endcan
    </header>

    <form method="GET" action="{{ route('companies.index') }}" class="mb-6 flex flex-col sm:flex-row gap-3">
        <label for="search" class="sr-only">Buscar empresas</label>
        <div class="relative flex-1">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Buscar por empresa, NIT, NRC, teléfono o correo..." class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 rounded-xl pl-11 pr-4 py-3 text-sm focus:outline-none transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold shadow-sm">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-5 py-3 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm transition-all">Buscar</button>
            @if(request()->filled('search'))
                <a href="{{ route('companies.index') }}" class="px-5 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-transparent dark:border-slate-700 rounded-full font-bold text-sm transition-all">Limpiar</a>
            @endif
        </div>
    </form>

    <!-- Listado de Empresas -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Empresa</th>
                    <th class="py-3 px-6">NIT / NRC</th>
                    <th class="py-3 px-6">Ubicación / Geografía</th>
                    <th class="py-3 px-6">Giros Registrados</th>
                    <th class="py-3 px-6">Teléfono / Email</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $company)
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 rounded-l-2xl border-l border-y border-slate-100 dark:border-slate-700/80 text-sm font-semibold text-slate-700 dark:text-slate-300 transition-colors duration-300">
                            <div class="flex items-center gap-3">
                                @if($company->logo)
                                    <div class="w-10 h-10 rounded-xl overflow-hidden border border-slate-100 dark:border-slate-700 flex items-center justify-center bg-slate-50 dark:bg-slate-900">
                                        <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/60">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <span class="font-bold text-slate-950 dark:text-slate-100 block">{{ $company->name }}</span>
                                    <span class="text-xs text-slate-400 dark:text-slate-400">{{ $company->commercial_name ?? 'Sin nombre comercial' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300">
                            <div><span class="font-bold text-[10px] text-slate-400 dark:text-slate-500">NIT:</span> {{ $company->nit ?? 'N/A' }}</div>
                            <div class="text-xs mt-0.5"><span class="font-bold text-[10px] text-slate-400 dark:text-slate-500">NRC:</span> {{ $company->nrc ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300">
                            <span class="text-xs block text-slate-500 dark:text-slate-400 font-medium truncate max-w-[200px]" title="{{ $company->address }}">{{ $company->address ?? 'N/A' }}</span>
                            @if($company->department || $company->municipality || $company->district)
                                <span class="text-[10px] bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-300 font-bold px-2 py-0.5 rounded mt-1 inline-block">
                                    {{ $company->department?->name }} / {{ $company->municipality?->name }} / {{ $company->district?->name }}
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-xs text-slate-500 dark:text-slate-400 max-w-xs transition-colors duration-300">
                            @if($company->commercial_line_1)
                                <div class="truncate" title="{{ $company->commercial_line_1 }}"><span class="font-bold text-slate-400 dark:text-slate-500">1:</span> {{ $company->commercial_line_1 }}</div>
                            @endif
                            @if($company->commercial_line_2)
                                <div class="truncate mt-0.5" title="{{ $company->commercial_line_2 }}"><span class="font-bold text-slate-400 dark:text-slate-500">2:</span> {{ $company->commercial_line_2 }}</div>
                            @endif
                            @if($company->commercial_line_3)
                                <div class="truncate mt-0.5" title="{{ $company->commercial_line_3 }}"><span class="font-bold text-slate-400 dark:text-slate-500">3:</span> {{ $company->commercial_line_3 }}</div>
                            @endif
                            @if(!$company->commercial_line_1 && !$company->commercial_line_2 && !$company->commercial_line_3)
                                <span class="text-slate-400 dark:text-slate-500 italic">No registrados</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 transition-colors duration-300">
                            <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $company->phone ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-400 dark:text-slate-400 mt-0.5">{{ $company->email ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-center transition-colors duration-300">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $company->is_active ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/60' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-100 dark:border-rose-800/60' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $company->is_active ? 'bg-emerald-500 dark:bg-emerald-400' : 'bg-rose-500 dark:bg-rose-400' }}"></span>
                                {{ $company->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 rounded-r-2xl border-r border-y border-slate-100 dark:border-slate-700/80 text-center transition-colors duration-300">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Editar -->
                                @can('companies.editar')
                                <button
                                    type="button"
                                    data-action="{{ route('companies.update', $company) }}"
                                    data-company="{{ json_encode($company->only([
                                        'id', 'name', 'commercial_name', 'nit', 'nrc',
                                        'commercial_line_1', 'commercial_line_2', 'commercial_line_3',
                                        'web_site', 'phone', 'email', 'address',
                                        'department_id', 'municipality_id', 'district_id', 'is_active',
                                    ]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                    onclick="openEditCompanyModal(this)"
                                    class="p-2 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-100/50 dark:border-blue-800/60 rounded-xl transition-all"
                                    title="Editar Empresa"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                @endcan

                                <!-- Eliminar -->
                                @can('companies.eliminar')
                                <button
                                    type="button"
                                    data-action="{{ route('companies.destroy', $company) }}"
                                    data-company-name="{{ $company->name }}"
                                    onclick="confirmDelete(this.dataset.action, this.dataset.companyName)"
                                    class="p-2 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-100/50 dark:border-rose-800/60 rounded-xl transition-all"
                                    title="Eliminar Empresa"
                                >
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
                                <span>No se encontraron empresas registradas.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($companies->hasPages())
        <div class="mt-6">
            {{ $companies->onEachSide(1)->links() }}
        </div>
    @endif
</div>

    @include('companies._create_modal')
    @include('companies._edit_modal')
    @include('companies._scripts')
@endsection