@extends('layouts.app')
@section('title', 'Gestión de Categorías')
@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado de Página -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 dark:text-slate-100 tracking-tight transition-colors duration-300">Gestión de Categorías de Productos</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-semibold mt-1">Administra las categorías principales para la clasificación de productos en el inventario.</p>
        </div>

        @can('categories.crear')
        <button type="button" onclick="openModal('create-category-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Crear Nueva Categoría</span>
        </button>
        @endcan
    </header>

    <!-- Barra de Búsqueda -->
    <section class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 card-shadow mb-8 transition-colors duration-300">
        <form method="GET" action="{{ route('categories.index') }}" class="w-full flex flex-col sm:flex-row gap-4 items-center">
            <div class="flex-1 w-full">
                <label for="search-categories" class="block text-xs font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider mb-2">Buscar Categoría</label>
                <div class="relative">
                    <input type="text" name="search" id="search-categories" value="{{ request('search') }}" placeholder="Buscar por nombre o descripción..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 pl-10 text-sm focus:outline-none focus:border-[#005e66] dark:focus:border-sky-500 focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold">
                    <div class="absolute left-3.5 top-3.5 text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
            </div>
            <div class="flex items-end gap-2 self-end sm:self-auto pt-6">
                <button type="submit" class="px-5 py-2.5 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-xl font-bold text-sm shadow-sm transition-all">
                    Filtrar
                </button>
                @if(request('search'))
                    <a href="{{ route('categories.index') }}" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 rounded-xl font-bold text-sm transition-all">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </section>

    <!-- Listado de Categorías -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                    <th class="py-3 px-6 text-left w-32 pl-10">ID</th>
                    <th class="py-3 px-6">Nombre de Categoría</th>
                    <th class="py-3 px-6">Descripción</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="categories-table-body">
                @forelse($categories as $category)
                    <tr class="table-row-item group hover:scale-[1.005] hover:shadow-md transition-all duration-200 {{ !$category->is_active ? 'opacity-50 grayscale-[35%]' : '' }}">
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 rounded-l-2xl border-l border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-400 dark:text-slate-400 font-bold transition-colors duration-300">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/40 flex items-center justify-center text-[#005e66] dark:text-teal-400 font-bold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2zM9 16h6M9 12h6" />
                                    </svg>
                                </div>
                                <span>#{{ $category->id }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm font-bold text-slate-900 dark:text-slate-100 search-name transition-colors duration-300">
                            {{ $category->name }}
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-slate-600 dark:text-slate-300 search-description transition-colors duration-300 max-w-xs truncate">
                            {{ $category->description ?? 'Sin descripción' }}
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 border-y border-slate-100 dark:border-slate-700/80 text-sm text-center transition-colors duration-300">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $category->is_active ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $category->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                                {{ $category->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="py-4 px-6 bg-white dark:bg-slate-800 rounded-r-2xl border-r border-y border-slate-100 dark:border-slate-700/80 text-center transition-colors duration-300">
                            <div class="flex items-center justify-center gap-2">
                                <!-- Editar -->
                                @can('categories.editar')
                                <button type="button" onclick="openEditCategoryModal('{{ route('categories.update', $category->id) }}', {{ json_encode($category) }})" class="p-2 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-100/50 dark:border-blue-800/60 rounded-xl transition-all" title="Editar Categoría">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                @endcan

                                <!-- Eliminar / Inactivar / Reactivar -->
                                @can('categories.eliminar')
                                    @if($category->is_active)
                                        <button type="button" onclick="confirmDelete('{{ route('categories.destroy', $category->id) }}', '{{ addslashes($category->name) }}', false)" class="p-2 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-100/50 dark:border-rose-800/60 rounded-xl transition-all" title="Inactivar Categoría">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @else
                                        <button type="button" onclick="confirmDelete('{{ route('categories.destroy', $category->id) }}', '{{ addslashes($category->name) }}', true)" class="p-2 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-100/50 dark:border-emerald-800/60 rounded-xl transition-all" title="Reactivar Categoría">
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
                        <td colspan="5" class="py-12 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 text-center text-slate-400 dark:text-slate-500 font-semibold shadow-sm transition-colors duration-300">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2zM9 16h6M9 12h6" />
                                </svg>
                                <span>No se encontraron categorías de productos registradas.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- MODAL DE REGISTRO DE CATEGORÍA -->
<div id="create-category-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/75 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Botón cerrar -->
        <button type="button" onclick="closeModal('create-category-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] dark:bg-sky-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">Registrar Nueva Categoría</h2>
                <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Crea una clasificación principal para los productos.</p>
            </div>
        </div>

        <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">

            <!-- Nombre -->
            <div>
                <label for="create-name" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Nombre de la Categoría *</label>
                <input type="text" name="name" id="create-name" value="{{ old('modal_type') === 'create' ? old('name') : '' }}" placeholder="Ej. Farmacia, Bebidas, Lácteos..." class="w-full bg-slate-50 dark:bg-slate-900 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required max="100">
                @error('name')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Descripción -->
            <div>
                <label for="create-description" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Descripción</label>
                <textarea name="description" id="create-description" rows="3" placeholder="Descripción opcional de la categoría..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] dark:focus:border-sky-500 focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold">{{ old('modal_type') === 'create' ? old('description') : '' }}</textarea>
            </div>

            <!-- Estado -->
            <div class="flex items-center gap-3 pt-2">
                <input type="checkbox" name="is_active" id="create-is_active" value="1" checked class="w-4 h-4 rounded border-slate-300 text-[#005e66] focus:ring-[#005e66]">
                <label for="create-is_active" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Activa inmediatamente</label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeModal('create-category-modal')" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] dark:hover:bg-sky-500 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Categoría
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE CATEGORÍA -->
<div id="edit-category-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/75 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Botón cerrar -->
        <button type="button" onclick="closeModal('edit-category-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] dark:bg-sky-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">Editar Categoría</h2>
                <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Modifica la información de la categoría seleccionada.</p>
            </div>
        </div>

        <form action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <!-- Nombre -->
            <div>
                <label for="edit-name" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Nombre de la Categoría *</label>
                <input type="text" name="name" id="edit-name" value="{{ old('modal_type') === 'edit' ? old('name') : '' }}" placeholder="Ej. Farmacia, Bebidas, Lácteos..." class="w-full bg-slate-50 dark:bg-slate-900 border @error('name') border-rose-300 focus:border-rose-500 @else border-slate-200 dark:border-slate-700 focus:border-[#005e66] dark:focus:border-sky-500 @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold" required max="100">
                @error('name')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 dark:text-rose-400 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Descripción -->
            <div>
                <label for="edit-description" class="block text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider mb-2">Descripción</label>
                <textarea name="description" id="edit-description" rows="3" placeholder="Descripción opcional..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 font-semibold">{{ old('modal_type') === 'edit' ? old('description') : '' }}</textarea>
            </div>

            <!-- Estado -->
            <div class="flex items-center gap-3 pt-2">
                <input type="checkbox" name="is_active" id="edit-is_active" value="1" class="w-4 h-4 rounded border-slate-300 text-[#005e66] focus:ring-[#005e66]">
                <label for="edit-is_active" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Categoría Activa</label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="closeModal('edit-category-modal')" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-200 rounded-full font-bold text-sm transition-all text-center">
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
    function openEditCategoryModal(actionUrl, category) {
        const modal = document.getElementById('edit-category-modal');
        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = category.id;
        document.getElementById('edit-name').value = category.name;
        document.getElementById('edit-description').value = category.description || '';
        document.getElementById('edit-is_active').checked = !!category.is_active;
        openModal('edit-category-modal');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-categories');
        const tableRows = document.querySelectorAll('.table-row-item');

        if (searchInput && tableRows.length > 0) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                tableRows.forEach(row => {
                    const name = row.querySelector('.search-name') ? row.querySelector('.search-name').textContent.toLowerCase() : '';
                    const desc = row.querySelector('.search-description') ? row.querySelector('.search-description').textContent.toLowerCase() : '';
                    if (name.includes(query) || desc.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

@if($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            @if(old('modal_type') === 'edit')
                const editRoute = "{{ route('categories.update', old('id', 0)) }}";
                const oldCategory = {
                    id: "{{ old('id') }}",
                    name: "{{ old('name') }}",
                    description: "{{ old('description') }}",
                    is_active: {{ old('is_active') ? 'true' : 'false' }}
                };
                openEditCategoryModal(editRoute, oldCategory);
            @else
                openModal('create-category-modal');
            @endif
        });
    </script>
@endif
@endsection