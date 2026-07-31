@extends('layouts.app')

@section('title', 'Gestión de Empresas')

@section('content')
<div class="animate-fade-in duration-300">
    <!-- Mensajes de Sesión -->
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

    <!-- Encabezado de Página -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Gestión de Empresas</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Registra y administra la información fiscal, giros comerciales, ubicaciones geográficas y logos de las empresas.</p>
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
                                <button type="button" onclick="openEditCompanyModal('{{ route('companies.update', $company) }}', {{ json_encode($company) }})" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl transition-all" title="Editar Empresa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                @endcan

                                <!-- Eliminar -->
                                @can('companies.eliminar')
                                <button type="button" onclick="confirmDelete('{{ route('companies.destroy', $company) }}', '{{ $company->name }}')" class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100/50 rounded-xl transition-all" title="Eliminar Empresa">
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
</div>

<!-- MODAL DE REGISTRO DE EMPRESA -->
<div id="create-company-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-3xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-company-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Registrar Nueva Empresa</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Ingresa todos los datos requeridos por el sistema fiscal y de catastro.</p>
            </div>
        </div>

        <form action="{{ route('companies.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">

            <!-- Fila 1: Datos Generales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Nombre Razón Social -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Razón Social <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('modal_type') === 'create' ? old('name') : '' }}" placeholder="Ej. Corporación ABC S.A. de C.V." class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    @error('name')
                        @if(old('modal_type') === 'create')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <!-- Nombre Comercial -->
                <div>
                    <label for="commercial_name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre Comercial</label>
                    <input type="text" name="commercial_name" id="commercial_name" value="{{ old('modal_type') === 'create' ? old('commercial_name') : '' }}" placeholder="Ej. Tiendas ABC" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 2: Documentos Fiscales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- NIT -->
                <div>
                    <label for="nit" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NIT</label>
                    <input type="text" name="nit" id="nit" value="{{ old('modal_type') === 'create' ? old('nit') : '' }}" placeholder="Ej. 0614-123456-101-9" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- NRC -->
                <div>
                    <label for="nrc" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NRC</label>
                    <input type="text" name="nrc" id="nrc" value="{{ old('modal_type') === 'create' ? old('nrc') : '' }}" placeholder="Ej. 123456-7" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 3: Giros Comerciales -->
            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Actividades o Giros Comerciales</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="commercial_line_1" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Principal</label>
                        <input type="text" name="commercial_line_1" id="commercial_line_1" value="{{ old('modal_type') === 'create' ? old('commercial_line_1') : '' }}" placeholder="Giro 1" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label for="commercial_line_2" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Secundario</label>
                        <input type="text" name="commercial_line_2" id="commercial_line_2" value="{{ old('modal_type') === 'create' ? old('commercial_line_2') : '' }}" placeholder="Giro 2" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label for="commercial_line_3" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Adicional</label>
                        <input type="text" name="commercial_line_3" id="commercial_line_3" value="{{ old('modal_type') === 'create' ? old('commercial_line_3') : '' }}" placeholder="Giro 3" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                </div>
            </div>

            <!-- Fila 4: Contacto y Sitio -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Teléfono -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Teléfono</label>
                    <input type="text" name="phone" id="phone" value="{{ old('modal_type') === 'create' ? old('phone') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- Correo -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo electrónico</label>
                    <input type="email" name="email" id="email" value="{{ old('modal_type') === 'create' ? old('email') : '' }}" placeholder="Ej. contacto@empresa.com" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- Sitio Web -->
                <div>
                    <label for="web_site" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sitio Web</label>
                    <input type="url" name="web_site" id="web_site" value="{{ old('modal_type') === 'create' ? old('web_site') : '' }}" placeholder="Ej. https://www.empresa.com" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 5: Ubicación Geográfica (El Salvador) -->
            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Ubicación Geográfica (El Salvador)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="create_department_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Departamento</label>
                        <select name="department_id" id="create_department_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione departamento</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="create_municipality_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Municipio</label>
                        <select name="municipality_id" id="create_municipality_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione municipio</option>
                            @foreach($municipalities as $muni)
                                <option value="{{ $muni->id }}" data-parent="{{ $muni->department_id }}" {{ old('municipality_id') == $muni->id ? 'selected' : '' }}>{{ $muni->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="create_district_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Distrito</label>
                        <select name="district_id" id="create_district_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione distrito</option>
                            @foreach($districts as $dist)
                                <option value="{{ $dist->id }}" data-parent="{{ $dist->municipality_id }}" {{ old('district_id') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Fila 6: Dirección Detallada y Logo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Dirección -->
                <div>
                    <label for="address" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Dirección de la Empresa</label>
                    <textarea name="address" id="address" rows="2" placeholder="Ej. Calle y Avenida, San Salvador" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold">{{ old('modal_type') === 'create' ? old('address') : '' }}</textarea>
                </div>

                <!-- Logo -->
                <div>
                    <label for="logo" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Logo Corporativo</label>
                    <input type="file" name="logo" id="logo" accept="image/*" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-500 font-semibold">
                    <p class="text-[10px] text-slate-400 mt-1">Formatos permitidos: JPEG, PNG, JPG, GIF. Máximo 2MB.</p>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('create-company-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Empresa
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE EMPRESA -->
<div id="edit-company-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-3xl w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-company-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Editar Empresa</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Modifica la información fiscal, giros, mapa de ubicaciones y logotipos corporativos.</p>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <!-- Fila 1: Datos Generales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit-name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Razón Social <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('name') : '' }}" placeholder="Ej. Corporación ABC S.A. de C.V." class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    @error('name')
                        @if(old('modal_type') === 'edit')
                            <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                        @endif
                    @enderror
                </div>

                <div>
                    <label for="edit-commercial_name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre Comercial</label>
                    <input type="text" name="commercial_name" id="edit-commercial_name" value="{{ old('modal_type') === 'edit' ? old('commercial_name') : '' }}" placeholder="Ej. Tiendas ABC" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 2: Documentos Fiscales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- NIT -->
                <div>
                    <label for="edit-nit" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NIT</label>
                    <input type="text" name="nit" id="edit-nit" value="{{ old('modal_type') === 'edit' ? old('nit') : '' }}" placeholder="Ej. 0614-123456-101-9" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- NRC -->
                <div>
                    <label for="edit-nrc" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">NRC</label>
                    <input type="text" name="nrc" id="edit-nrc" value="{{ old('modal_type') === 'edit' ? old('nrc') : '' }}" placeholder="Ej. 123456-7" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 3: Giros Comerciales -->
            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Actividades o Giros Comerciales</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="edit-commercial_line_1" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Principal</label>
                        <input type="text" name="commercial_line_1" id="edit-commercial_line_1" value="{{ old('modal_type') === 'edit' ? old('commercial_line_1') : '' }}" placeholder="Giro 1" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label for="edit-commercial_line_2" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Secundario</label>
                        <input type="text" name="commercial_line_2" id="edit-commercial_line_2" value="{{ old('modal_type') === 'edit' ? old('commercial_line_2') : '' }}" placeholder="Giro 2" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label for="edit-commercial_line_3" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Giro Adicional</label>
                        <input type="text" name="commercial_line_3" id="edit-commercial_line_3" value="{{ old('modal_type') === 'edit' ? old('commercial_line_3') : '' }}" placeholder="Giro 3" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none transition-all text-slate-700 font-semibold">
                    </div>
                </div>
            </div>

            <!-- Fila 4: Contacto y Sitio -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Teléfono -->
                <div>
                    <label for="edit-phone" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Teléfono</label>
                    <input type="text" name="phone" id="edit-phone" value="{{ old('modal_type') === 'edit' ? old('phone') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- Correo -->
                <div>
                    <label for="edit-email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo electrónico</label>
                    <input type="email" name="email" id="edit-email" value="{{ old('modal_type') === 'edit' ? old('email') : '' }}" placeholder="Ej. contacto@empresa.com" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>

                <!-- Sitio Web -->
                <div>
                    <label for="edit-web_site" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sitio Web</label>
                    <input type="url" name="web_site" id="edit-web_site" value="{{ old('modal_type') === 'edit' ? old('web_site') : '' }}" placeholder="Ej. https://www.empresa.com" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] focus:bg-white rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-all text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Fila 5: Ubicación Geográfica (El Salvador) -->
            <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">Ubicación Geográfica (El Salvador)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="edit_department_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Departamento</label>
                        <select name="department_id" id="edit_department_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione departamento</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit_municipality_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Municipio</label>
                        <select name="municipality_id" id="edit_municipality_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione municipio</option>
                            @foreach($municipalities as $muni)
                                <option value="{{ $muni->id }}" data-parent="{{ $muni->department_id }}">{{ $muni->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="edit_district_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Distrito</label>
                        <select name="district_id" id="edit_district_id" class="w-full bg-white border border-slate-200 focus:border-[#005e66] rounded-xl px-3 py-2 text-xs focus:outline-none text-slate-700 font-semibold">
                            <option value="">Seleccione distrito</option>
                            @foreach($districts as $dist)
                                <option value="{{ $dist->id }}" data-parent="{{ $dist->municipality_id }}">{{ $dist->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Fila 6: Dirección Detallada y Logo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Dirección -->
                <div>
                    <label for="edit-address" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Dirección de la Empresa</label>
                    <textarea name="address" id="edit-address" rows="2" placeholder="Ej. Calle y Avenida, San Salvador" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold">{{ old('modal_type') === 'edit' ? old('address') : '' }}</textarea>
                </div>

                <!-- Logo -->
                <div>
                    <label for="edit-logo" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Actualizar Logo</label>
                    <input type="file" name="logo" id="edit-logo" accept="image/*" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-500 font-semibold">
                    <p class="text-[10px] text-slate-400 mt-1">Dejar en blanco para conservar el actual. Máx 2MB.</p>
                </div>
            </div>

            <!-- Estado Activo -->
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="edit-is_active" value="1" class="rounded text-navy-sidebar focus:ring-[#005e66] border-slate-300 w-4 h-4 cursor-pointer">
                <label for="edit-is_active" class="text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer">Empresa Activa</label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-company-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Inicializar filtros geográficos dinámicos
    let createGeographicFilter, editGeographicFilter;

    function setupGeographicFilters(prefix) {
        const deptSelect = document.getElementById(`${prefix}department_id`);
        const muniSelect = document.getElementById(`${prefix}municipality_id`);
        const distSelect = document.getElementById(`${prefix}district_id`);

        if (!deptSelect || !muniSelect || !distSelect) return null;

        // Mantener copia de todos los options iniciales
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

    function openEditCompanyModal(actionUrl, company) {
        const modal = document.getElementById('edit-company-modal');
        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = company.id;
        document.getElementById('edit-name').value = company.name;
        document.getElementById('edit-commercial_name').value = company.commercial_name || '';
        document.getElementById('edit-nit').value = company.nit || '';
        document.getElementById('edit-nrc').value = company.nrc || '';
        document.getElementById('edit-commercial_line_1').value = company.commercial_line_1 || '';
        document.getElementById('edit-commercial_line_2').value = company.commercial_line_2 || '';
        document.getElementById('edit-commercial_line_3').value = company.commercial_line_3 || '';
        document.getElementById('edit-web_site').value = company.web_site || '';
        document.getElementById('edit-phone').value = company.phone || '';
        document.getElementById('edit-email').value = company.email || '';
        document.getElementById('edit-address').value = company.address || '';
        document.getElementById('edit-is_active').checked = company.is_active;

        // Cargar geografía
        if (editGeographicFilter) {
            editGeographicFilter.setValues(company.department_id, company.municipality_id, company.district_id);
        }

        openModal('edit-company-modal');
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Configurar filtros geográficos
        createGeographicFilter = setupGeographicFilters('create_');
        editGeographicFilter = setupGeographicFilters('edit_');

        // Si hay errores de validación de la sesión (vienen del redirect-back)
        @if($errors->any() && old('modal_type') === 'create')
            openModal('create-company-modal');
            if (createGeographicFilter) {
                createGeographicFilter.setValues(
                    "{{ old('department_id') }}", 
                    "{{ old('municipality_id') }}", 
                    "{{ old('district_id') }}"
                );
            }
        @elseif($errors->any() && old('modal_type') === 'edit')
            const editId = "{{ old('id') }}";
            if (editId) {
                const actionUrl = "{{ route('companies.update', ':id') }}".replace(':id', editId);
                const mockCompanyObj = {
                    id: editId,
                    name: "{{ old('name') }}",
                    commercial_name: "{{ old('commercial_name') }}",
                    nit: "{{ old('nit') }}",
                    nrc: "{{ old('nrc') }}",
                    commercial_line_1: "{{ old('commercial_line_1') }}",
                    commercial_line_2: "{{ old('commercial_line_2') }}",
                    commercial_line_3: "{{ old('commercial_line_3') }}",
                    web_site: "{{ old('web_site') }}",
                    phone: "{{ old('phone') }}",
                    email: "{{ old('email') }}",
                    address: "{{ old('address') }}",
                    department_id: "{{ old('department_id') }}",
                    municipality_id: "{{ old('municipality_id') }}",
                    district_id: "{{ old('district_id') }}",
                    is_active: {{ old('is_active') ? 'true' : 'false' }}
                };
                openEditCompanyModal(actionUrl, mockCompanyObj);
            }
        @endif
    });
</script>
@endsection
