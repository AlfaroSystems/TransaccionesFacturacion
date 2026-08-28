@extends('layouts.app')
@section('title', 'Catálogo de Productos')
@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado Principal -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg">Catálogo General</span>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500">Módulo de Productos</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] tracking-tight mt-1">
                Catálogo General de Productos
            </h1>
            <p class="text-slate-500 text-sm mt-0.5">
                Consulte, filtre y gestione el catálogo general, precios y niveles de existencias.
            </p>
        </div>
        @can('products.crear')
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button type="button" onclick="openModal('create-product-modal')" class="w-full md:w-auto bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold px-5 py-3 rounded-full shadow-lg transition-all flex items-center justify-center gap-2 text-sm transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span>Nuevo Producto</span>
            </button>
        </div>
        @endcan
    </div>

    <!-- Tarjetas de Métricas Rápidas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#005e66] flex items-center justify-center text-xl font-bold">
                💊
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Catálogo</span>
                <span class="text-xl font-extrabold text-slate-800">{{ $products->total() }}</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                ●
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Activos</span>
                <span class="text-xl font-extrabold text-slate-800">{{ $products->where('is_active', true)->count() }}</span>
            </div>
        </div>
    </div>

    <!-- Buscador y Filtros -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, SKU o código de barras..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
            </div>
            <div>
                <select name="id_category" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                    <option value="">Todas las Categorías</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('id_category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="w-full bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold py-2.5 px-4 rounded-xl text-sm transition-all">Filtrar</button>
                @if(request('search') || request('id_category'))
                    <a href="{{ route('products.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl text-sm transition-all">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Listado de Productos -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Producto / SKU</th>
                    <th class="py-3 px-6">Categoría</th>
                    <th class="py-3 px-6">Precio Compra</th>
                    <th class="py-3 px-6">Precio Venta</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
                    @forelse($products as $product)
                    <tr class="group hover:scale-[1.002] hover:shadow-md transition-all duration-200 {{ !$product->is_active ? 'opacity-50 grayscale-[35%]' : '' }}">
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100">
                            <div class="font-extrabold text-slate-800 text-sm">{{ $product->name }}</div>
                            <div class="text-xs text-slate-400 font-mono">SKU: {{ $product->sku }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600 font-bold">
                            {{ $product->category?->name ?? 'Sin Categoría' }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm font-semibold text-slate-600">
                            ${{ number_format($product->purchase_price, 2) }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm font-bold text-emerald-600">
                            ${{ number_format($product->sale_price, 2) }}
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            @if($product->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">● Activo</span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">● Inactivo</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('products.ver')
                                <a href="{{ route('products.show', $product) }}" class="p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 font-semibold text-xs transition-all" title="Ver Detalles">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                                @endcan

                                @can('products.editar')
                                <button type="button" onclick="openModal('edit-product-modal-{{ $product->id }}')" class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 font-semibold text-xs transition-all" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                @endcan

                                @can('products.eliminar')
                                    @if($product->is_active)
                                        <button type="button" onclick="confirmDelete('{{ route('products.destroy', $product) }}', 'Producto {{ $product->name }}')" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 font-semibold text-xs transition-all" title="Inactivar Producto">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    @else
                                        <button type="button" onclick="confirmDelete('{{ route('products.destroy', $product) }}', 'Producto {{ $product->name }}')" class="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-semibold text-xs transition-all" title="Reactivar Producto">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>                  </tr>

                    <!-- Modal de Edición de Producto -->
                    <div id="edit-product-modal-{{ $product->id }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
                        <div class="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
                            <div class="flex items-center justify-between border-b pb-4 mb-4">
                                <h3 class="text-lg font-bold text-slate-800">Editar Producto</h3>
                                <button onclick="closeModal('edit-product-modal-{{ $product->id }}')" class="text-slate-400 hover:text-slate-600">✕</button>
                            </div>
                            <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre del Producto *</label>
                                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">SKU *</label>
                                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Precio Compra *</label>
                                        <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Precio Venta *</label>
                                        <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 pt-4 border-t">
                                    <button type="button" onclick="closeModal('edit-product-modal-{{ $product->id }}')" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#005e66] text-white font-bold text-sm">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">No hay productos registrados en el catálogo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <!-- Paginación -->
    @if($products->hasPages())
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif
</div>

<!-- Modal de Creación de Producto -->
<div id="create-product-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b pb-4 mb-4">
            <h3 class="text-xl font-extrabold text-slate-800">Registrar Nuevo Producto</h3>
            <button onclick="closeModal('create-product-modal')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form action="{{ route('products.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre del Producto *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. Amoxicilina 500mg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">SKU *</label>
                    <input type="text" name="sku" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. AMX-500">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Categoría *</label>
                    <select name="id_category" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                        <option value="">Selecciona categoría...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Subcategoría</label>
                    <select name="id_sub_category" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                        <option value="">Selecciona subcategoría...</option>
                        @foreach($subCategories as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Precio Compra ($) *</label>
                    <input type="number" step="0.01" name="purchase_price" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Precio Venta ($) *</label>
                    <input type="number" step="0.01" name="sale_price" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="0.00">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="create_prod_active" value="1" checked class="rounded text-[#005e66]">
                <label for="create_prod_active" class="text-sm font-semibold text-slate-700">Producto Activo</label>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeModal('create-product-modal')" class="px-6 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] text-white font-bold text-sm">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

@if(request()->filled('edit'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('edit-product-modal-{{ request('edit') }}');
        });
    </script>
@endif
@endsection