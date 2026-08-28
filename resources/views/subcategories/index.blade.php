@extends('layouts.app')
@section('title', 'Gestión de Subcategorías')
@section('content')
<div class="animate-fade-in duration-300">
    <!-- Encabezado de Página -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Gestión de Subcategorías</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Administra las subcategorías vinculadas a las categorías principales de productos.</p>
        </div>

        @can('subcategories.crear')
        <button type="button" onclick="openModal('create-subcategory-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Nueva Subcategoría</span>
        </button>
        @endcan
    </header>

    <!-- Filtro por categoría -->
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 mb-6">
        <form method="GET" action="{{ route('subcategories.index') }}" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Filtrar por Categoría Padre</label>
                <select name="id_category" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                    <option value="">Todas las Categorías</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('id_category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold text-sm transition-all">Filtrar</button>
                <a href="{{ route('subcategories.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm transition-all">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Listado de Subcategorías -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
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
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200 {{ !$subCategory->is_active ? 'opacity-50 grayscale-[35%]' : '' }}">
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm font-bold text-slate-700">
                            #{{ $subCategory->id }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm font-bold text-[#005e66]">
                            {{ $subCategory->category?->name ?? 'Sin categoría' }}
                            @if($subCategory->category && !$subCategory->category->is_active)
                                <div class="text-xs text-amber-600 font-semibold mt-0.5">⚠️ Categoría inactiva</div>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm font-extrabold text-slate-800">
                            {{ $subCategory->name }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-500 max-w-xs truncate">
                            {{ $subCategory->description ?? 'Sin descripción' }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            @if($subCategory->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                    ● Activa
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                    ● Inactiva
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('subcategories.editar')
                                <button type="button" onclick="openModal('edit-subcategory-modal-{{ $subCategory->id }}')" class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 font-semibold text-xs transition-all" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                @endcan
                                
                                @can('subcategories.eliminar')
                                    @if($subCategory->is_active)
                                        <button type="button" onclick="confirmDelete('{{ route('subcategories.destroy', $subCategory) }}', 'Subcategoría {{ $subCategory->name }}')" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 font-semibold text-xs transition-all" title="Inactivar Subcategoría">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    @else
                                        <button type="button" onclick="confirmDelete('{{ route('subcategories.destroy', $subCategory) }}', 'Subcategoría {{ $subCategory->name }}')" class="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-semibold text-xs transition-all" title="Reactivar Subcategoría">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>>
                    </tr>

                    <!-- Modal de Edición de Subcategoría -->
                    <div id="edit-subcategory-modal-{{ $subCategory->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
                        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl mx-4 transform scale-95 transition-all">
                            <div class="flex items-center justify-between border-b pb-4 mb-4">
                                <h3 class="text-lg font-bold text-slate-800">Editar Subcategoría</h3>
                                <button onclick="closeModal('edit-subcategory-modal-{{ $subCategory->id }}')" class="text-slate-400 hover:text-slate-600">✕</button>
                            </div>
                            <form action="{{ route('subcategories.update', $subCategory) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Categoría Padre *</label>
                                    <select name="id_category" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('id_category', $subCategory->id_category) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre de la Subcategoría *</label>
                                    <input type="text" name="name" value="{{ old('name', $subCategory->name) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Descripción</label>
                                    <textarea name="description" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">{{ old('description', $subCategory->description) }}</textarea>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="is_active" id="sub_is_active_{{ $subCategory->id }}" value="1" {{ $subCategory->is_active ? 'checked' : '' }} class="rounded text-[#005e66]">
                                    <label for="sub_is_active_{{ $subCategory->id }}" class="text-sm font-semibold text-slate-700">Subcategoría Activa</label>
                                </div>
                                <div class="flex justify-end gap-3 pt-4 border-t">
                                    <button type="button" onclick="closeModal('edit-subcategory-modal-{{ $subCategory->id }}')" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#005e66] text-white font-bold text-sm">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">No se encontraron subcategorías registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- Modal de Creación de Subcategoría -->
<div id="create-subcategory-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl mx-4 transform scale-95 transition-all">
        <div class="flex items-center justify-between border-b pb-4 mb-4">
            <h3 class="text-lg font-bold text-slate-800">Registrar Nueva Subcategoría</h3>
            <button onclick="closeModal('create-subcategory-modal')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form action="{{ route('subcategories.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Categoría Padre *</label>
                <select name="id_category" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                    <option value="">Selecciona una categoría...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre de la Subcategoría *</label>
                <input type="text" name="name" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej: Jugos Naturales, Leche Entera, Detergentes">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Descripción</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Descripción opcional..."></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="create_sub_is_active" value="1" checked class="rounded text-[#005e66]">
                <label for="create_sub_is_active" class="text-sm font-semibold text-slate-700">Activa inmediatamente</label>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('create-subcategory-modal')" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#005e66] text-white font-bold text-sm">Crear Subcategoría</button>
            </div>
        </form>
    </div>
</div>
@endsection