@extends('layouts.app')

@section('title', 'Gestión de Categorías')

@section('content')
<div class="animate-fade-in duration-300">
    <!-- Mensajes de Sesión -->
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
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
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Categorías de Productos</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Administra las categorías generales de productos del catálogo.</p>
        </div>

        @can('categories.crear')
        <button type="button" onclick="openModal('create-category-modal')" class="flex items-center justify-center gap-2 px-5 py-3 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Nueva Categoría</span>
        </button>
        @endcan
    </header>

    <!-- Buscador -->
    <form method="GET" action="{{ route('categories.index') }}" class="mb-6">
        <div class="relative max-w-md">
            <input 
                type="text" 
                name="search" 
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#005e66] focus:ring-1 focus:ring-[#005e66] text-sm"
                placeholder="Buscar categoría por nombre..."
                value="{{ request('search') }}"
            >
            <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </form>

    <!-- Listado de Categorías -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">ID / Código</th>
                    <th class="py-3 px-6">Nombre de Categoría</th>
                    <th class="py-3 px-6">Descripción</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm font-bold text-slate-700">
                            #{{ $category->id }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm font-extrabold text-slate-800">
                            {{ $category->name }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-500 max-w-xs truncate">
                            {{ $category->description ?? 'Sin descripción' }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            @if($category->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                    ● Activa
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">
                                    ○ Inactiva
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('categories.editar')
                                <button type="button" onclick="openModal('edit-category-modal-{{ $category->id }}')" class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 font-semibold text-xs transition-all" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                
                                <form action="{{ route('categories.toggle', $category) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 font-semibold text-xs transition-all" title="Cambiar Estado">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                    </button>
                                </form>
                                @endcan

                                @can('categories.eliminar')
                                <button type="button" onclick="confirmDelete('{{ route('categories.destroy', $category) }}', 'Categoría {{ $category->name }}')" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 font-semibold text-xs transition-all" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>

                    <!-- Modal de Edición de Categoría -->
                    <div id="edit-category-modal-{{ $category->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
                        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl mx-4 transform scale-95 transition-all">
                            <div class="flex items-center justify-between border-b pb-4 mb-4">
                                <h3 class="text-lg font-bold text-slate-800">Editar Categoría</h3>
                                <button onclick="closeModal('edit-category-modal-{{ $category->id }}')" class="text-slate-400 hover:text-slate-600">✕</button>
                            </div>
                            <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre</label>
                                    <input type="text" name="name" value="{{ old('name', $category->name) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Descripción</label>
                                    <textarea name="description" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">{{ old('description', $category->description) }}</textarea>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="is_active" id="is_active_{{ $category->id }}" value="1" {{ $category->is_active ? 'checked' : '' }} class="rounded text-[#005e66]">
                                    <label for="is_active_{{ $category->id }}" class="text-sm font-semibold text-slate-700">Categoría Activa</label>
                                </div>
                                <div class="flex justify-end gap-3 pt-4 border-t">
                                    <button type="button" onclick="closeModal('edit-category-modal-{{ $category->id }}')" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#005e66] text-white font-bold text-sm">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400 font-semibold">No se encontraron categorías registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- Modal de Creación de Categoría -->
<div id="create-category-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl mx-4 transform scale-95 transition-all">
        <div class="flex items-center justify-between border-b pb-4 mb-4">
            <h3 class="text-lg font-bold text-slate-800">Registrar Nueva Categoría</h3>
            <button onclick="closeModal('create-category-modal')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre de la Categoría *</label>
                <input type="text" name="name" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej: Bebidas, Lácteos, Limpieza">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Descripción</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Breve descripción opcional..."></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="create_is_active" value="1" checked class="rounded text-[#005e66]">
                <label for="create_is_active" class="text-sm font-semibold text-slate-700">Activa inmediatamente</label>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('create-category-modal')" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-[#005e66] text-white font-bold text-sm">Crear Categoría</button>
            </div>
        </form>
    </div>
</div>
@endsection