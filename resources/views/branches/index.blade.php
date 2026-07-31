@extends('layouts.app')

@section('title', 'Gestión de Sucursales')

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

    <!-- Encabezado de Página -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Gestión de Sucursales</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Administra los locales físicos y los puntos de operación de tu empresa.</p>
        </div>

        <button type="button" onclick="openModal('create-branch-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Crear Nueva Sucursal</span>
        </button>
    </header>

    <!-- Listado de Sucursales -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Empresa</th>
                    <th class="py-3 px-6">Nombre</th>
                    <th class="py-3 px-6">Dirección</th>
                    <th class="py-3 px-6">Teléfono / Email</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    <tr class="group hover:scale-[1.005] hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm font-semibold text-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span>{{ $branch->company->name ?? 'Sin empresa' }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm font-bold text-slate-900">
                            {{ $branch->name }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-500 max-w-xs truncate">
                            {{ $branch->address }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600">
                            <div class="font-semibold">{{ $branch->phone ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $branch->email ?? 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $branch->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $branch->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Editar -->
                                <button type="button" onclick="openEditBranchModal('{{ route('branches.update', $branch) }}', {{ json_encode($branch) }})" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl transition-all" title="Editar Sucursal">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <!-- Eliminar -->
                                <button type="button" onclick="confirmDelete('{{ route('branches.destroy', $branch) }}', '{{ $branch->name }}')" class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100/50 rounded-xl transition-all" title="Eliminar Sucursal">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 bg-white rounded-2xl border border-slate-100 text-center text-slate-400 font-semibold shadow-sm">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
<div id="create-branch-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-branch-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Registrar Nueva Sucursal</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Ingresa los datos para habilitar una nueva sucursal en el sistema.</p>
            </div>
        </div>

        <form action="{{ route('branches.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">

            <!-- Empresa -->
            <div>
                <label for="company_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Empresa</label>
                <select name="company_id" id="company_id" class="w-full bg-slate-50 border @error('company_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione una empresa</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (old('modal_type') === 'create' && old('company_id') == $company->id) ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
                @error('company_id')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Nombre -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre de la Sucursal</label>
                <input type="text" name="name" id="name" value="{{ old('modal_type') === 'create' ? old('name') : '' }}" placeholder="Ej. Sucursal Central, San Salvador" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                @error('name')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Dirección -->
            <div>
                <label for="address" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Dirección</label>
                <input type="text" name="address" id="address" value="{{ old('modal_type') === 'create' ? old('address') : '' }}" placeholder="Ej. Alameda Manuel Enrique Araujo, San Salvador" class="w-full bg-slate-50 border @error('address') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                @error('address')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Teléfono -->
            <div>
                <label for="phone" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Teléfono</label>
                <input type="text" name="phone" id="phone" value="{{ old('modal_type') === 'create' ? old('phone') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 border @error('phone') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                @error('phone')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Correo -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo electrónico</label>
                <input type="email" name="email" id="email" value="{{ old('modal_type') === 'create' ? old('email') : '' }}" placeholder="Ej. contacto@empresa.com" class="w-full bg-slate-50 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                @error('email')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('create-branch-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Sucursal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE SUCURSAL -->
<div id="edit-branch-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-branch-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Editar Sucursal</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Modifica los detalles de la sucursal seleccionada.</p>
            </div>
        </div>

        <form action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <!-- Empresa -->
            <div>
                <label for="edit-company_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Empresa</label>
                <select name="company_id" id="edit-company_id" class="w-full bg-slate-50 border @error('company_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione una empresa</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (old('modal_type') === 'edit' && old('company_id') == $company->id) ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
                @error('company_id')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Nombre -->
            <div>
                <label for="edit-name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre de la Sucursal</label>
                <input type="text" name="name" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('name') : '' }}" placeholder="Ej. Sucursal Central, San Salvador" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                @error('name')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Dirección -->
            <div>
                <label for="edit-address" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Dirección</label>
                <input type="text" name="address" id="edit-address" value="{{ old('modal_type') === 'edit' ? old('address') : '' }}" placeholder="Ej. Alameda Manuel Enrique Araujo, San Salvador" class="w-full bg-slate-50 border @error('address') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                @error('address')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Teléfono -->
            <div>
                <label for="edit-phone" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Teléfono</label>
                <input type="text" name="phone" id="edit-phone" value="{{ old('modal_type') === 'edit' ? old('phone') : '' }}" placeholder="Ej. 2222-2222" class="w-full bg-slate-50 border @error('phone') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                @error('phone')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Correo -->
            <div>
                <label for="edit-email" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Correo electrónico</label>
                <input type="email" name="email" id="edit-email" value="{{ old('modal_type') === 'edit' ? old('email') : '' }}" placeholder="Ej. contacto@empresa.com" class="w-full bg-slate-50 border @error('email') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold">
                @error('email')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Estado de la Sucursal</label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="edit-is_active" value="1" class="sr-only peer" {{ old('modal_type') === 'edit' ? (old('is_active') ? 'checked' : '') : '' }}>
                    <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    <span class="text-sm font-semibold text-slate-600" id="edit-is_active_label">Sucursal Activa</span>
                </label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-branch-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
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
        
        openModal('edit-branch-modal');
    }

    document.addEventListener('DOMContentLoaded', () => {
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
                    phone: "{{ old('phone') }}",
                    email: "{{ old('email') }}",
                    is_active: "{{ old('is_active', '0') }}"
                };
                openEditBranchModal(editRoute, oldBranch);
            @else
                openModal('create-branch-modal');
            @endif
        });
    </script>
@endif

@endsection