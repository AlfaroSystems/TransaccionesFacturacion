@extends('layouts.app')
@section('title', 'Bodegas')
@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">

    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-4xl font-bold text-slate-800">
                Bodegas
            </h1>
            <p class="text-gray-500 mt-2">
                Administración de bodegas registradas.
            </p>
        </div>
        @can('warehouses.crear')
        <button type="button" onclick="openModal('create-warehouse-modal')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl shadow-lg transition">
            + Nueva Bodega
        </button>
        @endcan
    </div>

    <!-- Tabla -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="px-6 py-3 pl-10">ID</th>
                    <th class="px-6 py-3">Sucursal</th>
                    <th class="px-6 py-3">Categoría</th>
                    <th class="px-6 py-3">Nombre</th>
                    <th class="px-6 py-3">Descripción</th>
                    <th class="px-6 py-3 text-center">Estado</th>
                    <th class="px-6 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $warehouse)
                    <tr class="group hover:scale-[1.005] hover:shadow-md transition-all duration-200 {{ !$warehouse->is_active ? 'opacity-50 grayscale-[35%]' : '' }}">
                        <td class="px-6 py-4 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm text-slate-400 font-bold">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center text-orange-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <span>#{{ $warehouse->id }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-sm text-slate-600 font-semibold">
                            {{ $warehouse->branch->name ?? 'Sin sucursal' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-sm text-slate-600">
                            {{ $warehouse->warehouseCategory->name ?? 'Sin categoría' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-sm font-bold text-slate-900">
                            {{ $warehouse->name }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-sm text-slate-500 max-w-xs truncate">
                            {{ $warehouse->description ?? 'Sin descripción' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $warehouse->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $warehouse->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $warehouse->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex justify-center gap-2">
                                <!-- Editar -->
                                @can('warehouses.editar')
                                <button type="button" onclick="openEditWarehouseModal('{{ route('warehouses.update', $warehouse->id) }}', {{ json_encode($warehouse) }})" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl transition-all" title="Editar Bodega">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                @endcan

                                <!-- Eliminar / Inactivar -->
                                @can('warehouses.eliminar')
                                    @if($warehouse->is_active)
                                        <button type="button" onclick="confirmDelete('{{ route('warehouses.destroy', $warehouse->id) }}', '{{ $warehouse->name }}')" class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100/50 rounded-xl transition-all" title="Inactivar Bodega">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @else
                                        <button type="button" onclick="confirmDelete('{{ route('warehouses.destroy', $warehouse->id) }}', '{{ $warehouse->name }}')" class="p-2 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100/50 rounded-xl transition-all" title="Reactivar Bodega">
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
                        <td colspan="7" class="py-12 bg-white rounded-2xl border border-slate-100 text-center text-slate-400 font-semibold shadow-sm">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h11a2 2 0 002-2V7M3 7l9-4 9 4M4 10h16v8H4v-8z" />
                                </svg>
                                <span>No hay bodegas registradas.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- MODAL DE REGISTRO DE BODEGA -->
<div id="create-warehouse-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-warehouse-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Registrar Nueva Bodega</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Ingresa los datos para habilitar el espacio en inventario.</p>
            </div>
        </div>
        <form action="{{ route('warehouses.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">

            <!-- Sucursal -->
            <div>
                <label for="branch_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sucursal</label>
                <select name="branch_id" id="branch_id" class="w-full bg-slate-50 border @error('branch_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione una sucursal</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (old('modal_type') === 'create' && old('branch_id') == $branch->id) ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Categoría -->
            <div>
                <label for="warehouse_category_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Categoría</label>
                <select name="warehouse_category_id" id="warehouse_category_id" class="w-full bg-slate-50 border @error('warehouse_category_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione una categoría</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (old('modal_type') === 'create' && old('warehouse_category_id') == $category->id) ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_category_id')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Nombre -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre de la Bodega</label>
                <input type="text" name="name" id="name" value="{{ old('modal_type') === 'create' ? old('name') : '' }}" placeholder="Ej. Bodega General A" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                @error('name')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Descripción -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Descripción</label>
                <textarea name="description" id="description" rows="2" placeholder="Detalles de la bodega..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold">{{ old('modal_type') === 'create' ? old('description') : '' }}</textarea>
                @error('description')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('create-warehouse-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Bodega
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE BODEGA -->
<div id="edit-warehouse-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-warehouse-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Editar Bodega</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Modifica los detalles de la bodega seleccionada.</p>
            </div>
        </div>
        <form action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <!-- Sucursal -->
            <div>
                <label for="edit-branch_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Sucursal</label>
                <select name="branch_id" id="edit-branch_id" class="w-full bg-slate-50 border @error('branch_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione una sucursal</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (old('modal_type') === 'edit' && old('branch_id') == $branch->id) ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Categoría -->
            <div>
                <label for="edit-warehouse_category_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Categoría</label>
                <select name="warehouse_category_id" id="edit-warehouse_category_id" class="w-full bg-slate-50 border @error('warehouse_category_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione una categoría</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (old('modal_type') === 'edit' && old('warehouse_category_id') == $category->id) ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_category_id')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Nombre -->
            <div>
                <label for="edit-name" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nombre de la Bodega</label>
                <input type="text" name="name" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('name') : '' }}" placeholder="Ej. Bodega General A" class="w-full bg-slate-50 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                @error('name')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Descripción -->
            <div>
                <label for="edit-description" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Descripción</label>
                <textarea name="description" id="edit-description" rows="2" placeholder="Detalles de la bodega..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white transition-all text-slate-700 font-semibold">{{ old('modal_type') === 'edit' ? old('description') : '' }}</textarea>
                @error('description')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Estado de la Bodega</label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="edit-is_active" value="1" class="sr-only peer" {{ old('modal_type') === 'edit' ? (old('is_active') ? 'checked' : '') : '' }}>
                    <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    <span class="text-sm font-semibold text-slate-600" id="edit-is_active_label">Bodega Activa</span>
                </label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-warehouse-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
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
    function openEditWarehouseModal(actionUrl, warehouse) {
        const modal = document.getElementById('edit-warehouse-modal');
        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = warehouse.id;
        document.getElementById('edit-branch_id').value = warehouse.branch_id;
        document.getElementById('edit-warehouse_category_id').value = warehouse.warehouse_category_id;
        document.getElementById('edit-name').value = warehouse.name;
        document.getElementById('edit-description').value = warehouse.description || '';
        
        const isActiveChk = document.getElementById('edit-is_active');
        isActiveChk.checked = warehouse.is_active == 1;
        
        const label = document.getElementById('edit-is_active_label');
        label.textContent = warehouse.is_active == 1 ? 'Bodega Activa' : 'Bodega Inactiva';
        
        openModal('edit-warehouse-modal');
    }

    // Toggle label text on change
    document.addEventListener('DOMContentLoaded', () => {
        const isActiveChk = document.getElementById('edit-is_active');
        const label = document.getElementById('edit-is_active_label');
        if (isActiveChk && label) {
            isActiveChk.addEventListener('change', () => {
                label.textContent = isActiveChk.checked ? 'Bodega Activa' : 'Bodega Inactiva';
            });
        }
    });
</script>

@if($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            @if(old('modal_type') === 'edit')
                const editRoute = "{{ route('warehouses.update', old('id', 0)) }}";
                const oldWarehouse = {
                    id: "{{ old('id') }}",
                    branch_id: "{{ old('branch_id') }}",
                    warehouse_category_id: "{{ old('warehouse_category_id') }}",
                    name: "{{ old('name') }}",
                    description: "{{ old('description') }}",
                    is_active: "{{ old('is_active', '0') }}"
                };
                openEditWarehouseModal(editRoute, oldWarehouse);
            @else
                openModal('create-warehouse-modal');
            @endif
        });
    </script>
@endif
@endsection