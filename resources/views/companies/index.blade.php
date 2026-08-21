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
    <!-- Mensajes de SesiÃ³n -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm animate-bounce-subtle">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-semibold text-sm">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove();" class="text-emerald-500 hover:text-emerald-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm animate-bounce-subtle">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-semibold text-sm">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove();" class="text-rose-500 hover:text-rose-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    <!-- Encabezado de pagina -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Gestión de Empresas</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Registra y administra la información fiscal, giros comerciales, ubicaciones geograficas y logos de las empresas.</p>
        </div>

        @can('companies.crear')
        <button type="button" onclick="openModal('create-company-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
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
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Buscar por empresa, NIT, NRC, teléfono o correo..." class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl pl-11 pr-4 py-3 text-sm focus:outline-none transition-all text-slate-700 font-semibold shadow-sm">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm transition-all">Buscar</button>
            @if(request()->filled('search'))
                <a href="{{ route('companies.index') }}" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all">Limpiar</a>
            @endif
        </div>
    </form>

    <!-- Listado de Empresas -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
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
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm font-semibold text-slate-700">
                            <div class="flex items-center gap-3">
                                @if($company->logo)
                                    <div class="w-10 h-10 rounded-xl overflow-hidden border border-slate-100 flex items-center justify-center bg-slate-50">
                                        <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <span class="font-bold text-slate-950 block">{{ $company->name }}</span>
                                    <span class="text-xs text-slate-400">{{ $company->commercial_name ?? 'Sin nombre comercial' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600">
                            <div><span class="font-bold text-[10px] text-slate-400">NIT:</span> {{ $company->nit ?? 'N/A' }}</div>
                            <div class="text-xs mt-0.5"><span class="font-bold text-[10px] text-slate-400">NRC:</span> {{ $company->nrc ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600">
                            <span class="text-xs block text-slate-500 font-medium truncate max-w-[200px]" title="{{ $company->address }}">{{ $company->address ?? 'N/A' }}</span>
                            @if($company->department || $company->municipality || $company->district)
                                <span class="text-[10px] bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded mt-1 inline-block">
                                    {{ $company->department?->name }} / {{ $company->municipality?->name }} / {{ $company->district?->name }}
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-xs text-slate-500 max-w-xs">
                            @if($company->commercial_line_1)
                                <div class="truncate" title="{{ $company->commercial_line_1 }}"><span class="font-bold text-slate-400">1:</span> {{ $company->commercial_line_1 }}</div>
                            @endif
                            @if($company->commercial_line_2)
                                <div class="truncate mt-0.5" title="{{ $company->commercial_line_2 }}"><span class="font-bold text-slate-400">2:</span> {{ $company->commercial_line_2 }}</div>
                            @endif
                            @if($company->commercial_line_3)
                                <div class="truncate mt-0.5" title="{{ $company->commercial_line_3 }}"><span class="font-bold text-slate-400">3:</span> {{ $company->commercial_line_3 }}</div>
                            @endif
                            @if(!$company->commercial_line_1 && !$company->commercial_line_2 && !$company->commercial_line_3)
                                <span class="text-slate-400 italic">No registrados</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600">
                            <div class="font-semibold">{{ $company->phone ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $company->email ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $company->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $company->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $company->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
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
                                    class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl transition-all"
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
                                    class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100/50 rounded-xl transition-all"
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
                        <td colspan="7" class="py-12 bg-white rounded-2xl border border-slate-100 text-center text-slate-400 font-semibold shadow-sm">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
