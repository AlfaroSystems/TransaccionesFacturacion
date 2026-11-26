@extends('layouts.app')
@section('title', 'Gestión de Subcategorías')
@section('content')
<div class="animate-fade-in duration-300">
    <x-ui.page-header eyebrow="Productos" module="Subcategorías"
        title="Gestión de Subcategorías" subtitle="Administra las subcategorías vinculadas a las categorías principales de productos.">
        @can('subcategories.crear')
            <x-slot:actions>
                <x-ui.button type="button" icon="plus" onclick="openModal('create-subcategory-modal')">Nueva Subcategoría</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <!-- Filtro por categoría -->
    <div class="bg-surface rounded-2xl p-4 shadow-xs border border-line mb-6">
        <form method="GET" action="{{ route('subcategories.index') }}" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label class="ui-label">Filtrar por Categoría Padre</label>
                <select name="id_category" class="ui-input">
                    <option value="">Todas las Categorías</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id_category }}" {{ request('id_category') == $category->id_category ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm transition-all">Filtrar</button>
                <a href="{{ route('subcategories.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm transition-all">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Listado de Subcategorías -->
    <section class="overflow-x-auto">
        <table class="ui-table">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">ID</th>
                    <th class="py-3 px-6">Categoría Padre</th>
                    <th class="py-3 px-6">Subcategoría</th>
                    <th class="py-3 px-6">Descripción</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
                    @forelse($subCategories as $subCategory)
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200 {{ !$subCategory->is_active ? 'opacity-50 grayscale-35' : '' }}">
                        <td class="py-4 px-6 bg-surface rounded-l-2xl border-l border-y border-line text-sm font-bold text-slate-700">
                            #{{ $subCategory->id_sub_category }}
                        </td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm font-bold text-primary dark:text-accent">
                            {{ $subCategory->category?->name ?? 'Sin categoría' }}
                            @if($subCategory->category && !$subCategory->category->is_active)
                                <div class="text-xs text-amber-600 font-semibold mt-0.5">⚠️ Categoría inactiva</div>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm font-extrabold text-slate-800">
                            {{ $subCategory->name }}
                        </td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm text-slate-500 max-w-xs truncate">
                            {{ $subCategory->description ?? 'Sin descripción' }}
                        </td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-center">
                            @if($subCategory->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-success-soft text-success">
                                    ● Activa
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-danger-soft text-danger">
                                    ● Inactiva
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-surface rounded-r-2xl border-r border-y border-line text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('subcategories.editar')
                                <button type="button" onclick="openModal('edit-subcategory-modal-{{ $subCategory->id_sub_category }}')" class="p-2.5 rounded-xl bg-warning-soft text-warning hover:brightness-95 font-semibold text-xs transition-all flex items-center justify-center" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                @endcan
                                
                                @can('subcategories.eliminar')
                                    @if($subCategory->is_active)
                                        <button type="button" onclick="confirmDelete('{{ route('subcategories.destroy', $subCategory) }}', '{{ addslashes($subCategory->name) }}', false)" class="p-2.5 rounded-xl bg-danger-soft text-danger hover:brightness-95 font-semibold text-xs transition-all flex items-center justify-center" title="Inactivar Subcategoría">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    @else
                                        <button type="button" onclick="confirmDelete('{{ route('subcategories.destroy', $subCategory) }}', '{{ addslashes($subCategory->name) }}', true)" class="p-2.5 rounded-xl bg-success-soft text-success hover:brightness-95 font-semibold text-xs transition-all flex items-center justify-center" title="Reactivar Subcategoría">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">No se encontraron subcategorías registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- MODALES DE EDICIÓN DE SUBCATEGORÍA -->
@foreach($subCategories as $subCategory)
    <div id="edit-subcategory-modal-{{ $subCategory->id_sub_category }}" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 flex items-center justify-center">
        <div class="bg-surface rounded-3xl p-6 max-w-lg w-full shadow-2xl mx-4 transform scale-95 transition-all">
            <div class="flex items-center justify-between border-b pb-4 mb-4">
                <h3 class="text-lg font-bold text-slate-800">Editar Subcategoría</h3>
                <button type="button" onclick="closeModal('edit-subcategory-modal-{{ $subCategory->id_sub_category }}')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="{{ route('subcategories.update', $subCategory) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="ui-label">Categoría Padre *</label>
                    <select name="id_category" required class="ui-input">
                        @foreach($categories as $category)
                            <option value="{{ $category->id_category }}" {{ old('id_category', $subCategory->id_category) == $category->id_category ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ui-label">Nombre de la Subcategoría *</label>
                    <input type="text" name="name" value="{{ old('name', $subCategory->name) }}" required class="ui-input">
                </div>
                <div>
                    <label class="ui-label">Descripción</label>
                    <textarea name="description" rows="3" class="ui-input">{{ old('description', $subCategory->description) }}</textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="sub_is_active_{{ $subCategory->id_sub_category }}" value="1" {{ $subCategory->is_active ? 'checked' : '' }} class="rounded-sm text-primary dark:text-accent">
                    <label for="sub_is_active_{{ $subCategory->id_sub_category }}" class="text-sm font-semibold text-slate-700">Subcategoría Activa</label>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeModal('edit-subcategory-modal-{{ $subCategory->id_sub_category }}')" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-customTeal-800 text-white font-bold text-sm">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<!-- Modal de Creación de Subcategoría -->
<div id="create-subcategory-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-surface rounded-3xl p-6 max-w-lg w-full shadow-2xl mx-4 transform scale-95 transition-all">
        <div class="flex items-center justify-between border-b pb-4 mb-4">
            <h3 class="text-lg font-bold text-slate-800">Registrar Nueva Subcategoría</h3>
            <button onclick="closeModal('create-subcategory-modal')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form action="{{ route('subcategories.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="ui-label">Categoría Padre *</label>
                <select name="id_category" required class="ui-input">
                    <option value="">Selecciona una categoría...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id_category }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ui-label">Nombre de la Subcategoría *</label>
                <input type="text" name="name" required class="ui-input" placeholder="Ej: Jugos Naturales, Leche Entera, Detergentes">
            </div>
            <div>
                <label class="ui-label">Descripción</label>
                <textarea name="description" rows="3" class="ui-input" placeholder="Descripción opcional..."></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="create_sub_is_active" value="1" checked class="rounded-sm text-primary dark:text-accent">
                <label for="create_sub_is_active" class="text-sm font-semibold text-slate-700">Activa inmediatamente</label>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('create-subcategory-modal')" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-customTeal-800 text-white font-bold text-sm">Crear Subcategoría</button>
            </div>
        </form>
    </div>
</div>
@endsection