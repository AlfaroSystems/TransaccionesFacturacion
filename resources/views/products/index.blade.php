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
                Consulte, filtre y gestione el catálogo general, imágenes, especificaciones y niveles de existencias.
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
                📦
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
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, SKU, códigos de fábrica o barras..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
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
                    <th class="py-3 px-4 text-center">Imagen</th>
                    <th class="py-3 px-6">Producto / Códigos</th>
                    <th class="py-3 px-6">Categoría / Subcat.</th>
                    <th class="py-3 px-6">Presentación & Medidas</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr class="group hover:scale-[1.001] hover:shadow-md transition-all duration-200 {{ !$product->is_active ? 'opacity-50 grayscale-[35%]' : '' }}">
                        <!-- Imagen de producto -->
                        <td class="py-4 px-4 bg-white rounded-l-2xl border-l border-y border-slate-100 text-center">
                            @if($product->images->count() > 0)
                                <div class="relative w-12 h-12 mx-auto rounded-xl overflow-hidden border border-slate-200 shadow-sm group-hover:scale-105 transition-all">
                                    <img src="{{ asset('storage/' . $product->images->first()->path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @if($product->images->count() > 1)
                                        <span class="absolute bottom-0 right-0 bg-slate-900/80 text-white text-[9px] font-extrabold px-1 rounded-tl">
                                            +{{ $product->images->count() - 1 }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <div class="w-12 h-12 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-lg font-bold border border-slate-200">
                                    🖼️
                                </div>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100">
                            <div class="font-extrabold text-slate-800 text-sm">{{ $product->name }}</div>
                            <div class="flex items-center gap-2 text-xs text-slate-400 font-mono mt-0.5 flex-wrap">
                                <span>SKU: {{ $product->sku }}</span>
                                @if($product->original_code)
                                    <span>• Orig: {{ $product->original_code }}</span>
                                @endif
                                @if($product->internal_code)
                                    <span>• Int: {{ $product->internal_code }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm">
                            <div class="font-bold text-slate-700">{{ $product->category?->name ?? 'Sin Categoría' }}</div>
                            <div class="text-xs text-slate-400 font-semibold">{{ $product->subCategory?->name ?? 'Sin Subcategoría' }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-xs">
                            <div class="font-semibold text-slate-700">{{ $product->presentation ?: 'N/A' }}</div>
                            <div class="text-slate-400">
                                {{ $product->size ? 'Talla/Tam: '.$product->size : '' }} 
                                {{ $product->dimensions ? '('.$product->dimensions.')' : '' }}
                            </div>
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
                                        <button type="button" onclick="confirmDelete('{{ route('products.destroy', $product) }}', '{{ addslashes($product->name) }}', false)" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 font-semibold text-xs transition-all" title="Inactivar Producto">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </button>
                                    @else
                                        <button type="button" onclick="confirmDelete('{{ route('products.destroy', $product) }}', '{{ addslashes($product->name) }}', true)" class="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-semibold text-xs transition-all" title="Reactivar Producto">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400 font-semibold">No hay productos registrados en el catálogo.</td>
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

<!-- MODALES DE EDICIÓN DE PRODUCTO -->
@foreach($products as $product)
    @can('products.editar')
        <div id="edit-product-modal-{{ $product->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6 flex items-start sm:items-center justify-center">
            <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-4xl w-full shadow-2xl mx-4 my-auto max-h-[85vh] overflow-y-auto border border-slate-100 relative transform scale-95 transition-all duration-200">
                
                <!-- Encabezado Sticky (Fijo al hacer scroll dentro del modal) -->
                <div class="sticky -top-6 -mx-6 -mt-6 sm:-top-8 sm:-mx-8 sm:-mt-8 p-6 bg-white z-20 border-b border-slate-100 flex items-center justify-between mb-6 shadow-sm rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-extrabold text-slate-800">Editar Producto: {{ $product->name }}</h3>
                        <p class="text-xs text-slate-400">Actualiza los datos del producto, precios e imágenes asociadas.</p>
                    </div>
                    <button type="button" onclick="closeModal('edit-product-modal-{{ $product->id }}')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">✕</button>
                </div>

                <!-- Formulario Principal -->
                <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- 1. Información General -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-3">
                        <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">1. Información General</h4>
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
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Categoría *</label>
                                <select name="id_category" id="edit_id_category_{{ $product->id }}" required onchange="filterSubcategories(this, document.getElementById('edit_id_sub_category_{{ $product->id }}'))" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    <option value="">Selecciona categoría...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('id_category', $product->id_category) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Subcategoría</label>
                                <select name="id_sub_category" id="edit_id_sub_category_{{ $product->id }}" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    <option value="">Selecciona subcategoría...</option>
                                    @foreach($subCategories as $sub)
                                        <option value="{{ $sub->id }}" data-category="{{ $sub->id_category }}" {{ old('id_sub_category', $product->id_sub_category) == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Códigos de Identificación -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-3">
                        <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">2. Códigos de Identificación</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Código Original / Fábrica</label>
                                <input type="text" name="original_code" value="{{ old('original_code', $product->original_code) }}" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. ORG-9843">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Código Interno</label>
                                <input type="text" name="internal_code" value="{{ old('internal_code', $product->internal_code) }}" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. INT-0042">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Presentación & Medidas -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-3">
                        <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">3. Presentación & Especificaciones Técnicas</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Presentación</label>
                                <input type="text" name="presentation" value="{{ old('presentation', $product->presentation) }}" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. Caja por 12 unidades">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Talla / Tamaño</label>
                                <input type="text" name="size" value="{{ old('size', $product->size) }}" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. Grande / 500ml">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Dimensiones</label>
                                <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. 10x5x2 cm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Unidad de Compra</label>
                                <select name="purchase_unit" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    <option value="">Selecciona unidad...</option>
                                    @foreach($units as $u)
                                        <option value="{{ $u->id }}" {{ old('purchase_unit', $product->purchase_unit) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Unidad de Venta</label>
                                <select name="sale_unit" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                                    <option value="">Selecciona unidad...</option>
                                    @foreach($units as $u)
                                        <option value="{{ $u->id }}" {{ old('sale_unit', $product->sale_unit) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Descripción -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-2">
                        <label class="block text-xs font-bold text-[#005e66] uppercase tracking-wider">4. Descripción / Notas</label>
                        <textarea name="description" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <!-- 5. Imágenes del Producto -->
                    <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-3">
                        <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">5. Imágenes del Producto (Permite múltiples archivos)</h4>
                        
                        <!-- Imágenes Existentes -->
                        @if($product->images->count() > 0)
                            <div>
                                <span class="text-xs font-bold text-slate-500 block mb-2">Imágenes Actuales:</span>
                                <div class="flex flex-wrap gap-3">
                                    @foreach($product->images as $img)
                                        <div class="relative group/img w-20 h-20 rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                                            <img src="{{ asset('storage/' . $img->path) }}" class="w-full h-full object-cover">
                                            <form action="{{ route('product-images.destroy', $img->id_product_image) }}" method="POST" class="absolute inset-0 bg-slate-900/60 flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-all">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('¿Deseas eliminar esta imagen?')" class="p-1.5 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition" title="Eliminar imagen">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Subir nuevas imágenes (Selección Acumulativa) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Agregar más imágenes (se irán sumando):</label>
                            <input type="file" id="edit_product_images_input_{{ $product->id }}" name="images[]" multiple accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#005e66] file:text-white hover:file:bg-[#3cb0a4] cursor-pointer">
                            <p class="text-[11px] text-slate-400 mt-1">Puedes seleccionar imágenes de una en una o varias a la vez. Se irán acumulando abajo.</p>

                            <!-- Vista Previa de Nuevas Imágenes por Subir -->
                            <div id="edit_images_preview_container_{{ $product->id }}" class="flex flex-wrap gap-3 pt-2"></div>
                        </div>
                    </div>

                    <!-- Checkbox Activo -->
                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_active" id="edit_prod_active_{{ $product->id }}" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="rounded text-[#005e66]">
                        <label for="edit_prod_active_{{ $product->id }}" class="text-sm font-semibold text-slate-700">Producto Activo para Operaciones</label>
                    </div>

                    <!-- Acciones del Formulario al final -->
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                        <button type="button" onclick="closeModal('edit-product-modal-{{ $product->id }}')" class="px-6 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-100 transition">Cancelar</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] text-white font-bold text-sm hover:bg-[#3cb0a4] transition-all shadow-md">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach

<!-- MODAL DE CREACIÓN DE PRODUCTO -->
<div id="create-product-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6 flex items-start sm:items-center justify-center">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-4xl w-full shadow-2xl mx-4 my-auto max-h-[85vh] overflow-y-auto border border-slate-100 relative transform scale-95 transition-all duration-200">
        
        <!-- Encabezado Sticky (Fijo al hacer scroll dentro del modal) -->
        <div class="sticky -top-6 -mx-6 -mt-6 sm:-top-8 sm:-mx-8 sm:-mt-8 p-6 bg-white z-20 border-b border-slate-100 flex items-center justify-between mb-6 shadow-sm rounded-t-3xl">
            <div>
                <h3 class="text-xl font-extrabold text-slate-800">Registrar Nuevo Producto</h3>
                <p class="text-xs text-slate-400">Complete la información requerida, especificaciones e imágenes del producto.</p>
            </div>
            <button type="button" onclick="closeModal('create-product-modal')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">✕</button>
        </div>

        <!-- Formulario Completo -->
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- 1. Información General -->
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">1. Información General</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nombre del Producto *</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. Laptop Pro 15 pulgadas">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">SKU (Opcional - Se autogenera si está vacío)</label>
                        <input type="text" name="sku" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. PRD-2026-X9A3B">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Categoría *</label>
                        <select name="id_category" id="create_id_category" required onchange="filterSubcategories(this, document.getElementById('create_id_sub_category'))" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            <option value="">Selecciona categoría...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Subcategoría</label>
                        <select name="id_sub_category" id="create_id_sub_category" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            <option value="">Selecciona subcategoría...</option>
                            @foreach($subCategories as $sub)
                                <option value="{{ $sub->id }}" data-category="{{ $sub->id_category }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Códigos de Identificación -->
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">2. Códigos de Identificación</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Código Original / Fábrica</label>
                        <input type="text" name="original_code" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. FAB-8834">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Código Interno</label>
                        <input type="text" name="internal_code" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. INT-0021">
                    </div>
                </div>
            </div>

            <!-- 3. Presentación & Medidas -->
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-3">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">3. Presentación & Especificaciones Técnicas</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Presentación</label>
                        <input type="text" name="presentation" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. Caja por 12 unidades">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Talla / Tamaño</label>
                        <input type="text" name="size" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. Mediano / 250ml">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Dimensiones</label>
                        <input type="text" name="dimensions" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Ej. 12x4x2 cm">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Unidad de Compra</label>
                        <select name="purchase_unit" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            <option value="">Selecciona unidad...</option>
                            @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Unidad de Venta</label>
                        <select name="sale_unit" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            <option value="">Selecciona unidad...</option>
                            @foreach($units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- 4. Descripción -->
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-2">
                <label class="block text-xs font-bold text-[#005e66] uppercase tracking-wider">4. Descripción / Notas</label>
                <textarea name="description" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]" placeholder="Indicaciones, componentes o detalles adicionales del producto..."></textarea>
            </div>

            <!-- 5. Galería de Imágenes (Selección Acumulativa) -->
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-3">
                <label class="block text-xs font-bold text-[#005e66] uppercase tracking-wider">5. Imágenes del Producto (Selección Acumulativa)</label>
                <input type="file" id="create_product_images_input" name="images[]" multiple accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#005e66] file:text-white hover:file:bg-[#3cb0a4] cursor-pointer">
                <p class="text-[11px] text-slate-400">Puedes seleccionar imágenes de una en una o varias a la vez. Se irán acumulando abajo en la vista previa.</p>

                <!-- Vista Previa de Imágenes Seleccionadas -->
                <div id="create_images_preview_container" class="flex flex-wrap gap-3 pt-2"></div>
            </div>

            <!-- Checkbox Activo -->
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="create_prod_active" value="1" checked class="rounded text-[#005e66]">
                <label for="create_prod_active" class="text-sm font-semibold text-slate-700">Producto Activo para Operaciones</label>
            </div>

            <!-- Acciones del Formulario al final -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                <button type="button" onclick="closeModal('create-product-modal')" class="px-6 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-100 transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] text-white font-bold text-sm hover:bg-[#3cb0a4] transition-all shadow-md">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPT DE FILTRADO DINÁMICO DE SUBCATEGORÍAS -->
<script>
    function filterSubcategories(categorySelect, subcategorySelect) {
        if (!categorySelect || !subcategorySelect) return;

        const selectedCategoryId = categorySelect.value;
        const options = subcategorySelect.querySelectorAll('option[data-category]');

        options.forEach(option => {
            const optionCatId = option.getAttribute('data-category');
            if (!selectedCategoryId || optionCatId === selectedCategoryId) {
                option.style.display = '';
                option.disabled = false;
            } else {
                option.style.display = 'none';
                option.disabled = true;
                if (option.selected) {
                    option.selected = false;
                }
            }
        });

        // Resetea la subcategoría si la opción seleccionada ya no pertenece a la categoría elegida
        if (subcategorySelect.selectedOptions.length > 0 && subcategorySelect.selectedOptions[0].disabled) {
            subcategorySelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Filtrado dinámico al cargar la página en Modal de Creación
        const createCat = document.getElementById('create_id_category');
        const createSub = document.getElementById('create_id_sub_category');
        if (createCat && createSub) {
            filterSubcategories(createCat, createSub);
        }

        // Filtrado dinámico al cargar la página en Modales de Edición
        document.querySelectorAll('[id^="edit_id_category_"]').forEach(catSelect => {
            const id = catSelect.id.replace('edit_id_category_', '');
            const subSelect = document.getElementById('edit_id_sub_category_' + id);
            if (subSelect) {
                filterSubcategories(catSelect, subSelect);
            }
        });
    });

    // GESTOR DE SUBIDA DE IMÁGENES ACUMULATIVA
    const imageUploaders = {};

    class AccumulativeImageUploader {
        constructor(inputId, previewContainerId) {
            this.inputId = inputId;
            this.previewContainerId = previewContainerId;
            this.dt = new DataTransfer();
            this.init();
        }

        init() {
            const input = document.getElementById(this.inputId);
            if (!input) return;

            input.addEventListener('change', (e) => {
                const files = e.target.files;
                if (!files || files.length === 0) return;

                for (let i = 0; i < files.length; i++) {
                    this.dt.items.add(files[i]);
                }
                input.files = this.dt.files;
                this.render();
            });
        }

        removeFile(index) {
            const input = document.getElementById(this.inputId);
            if (!input) return;

            const newDt = new DataTransfer();
            const files = this.dt.files;

            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    newDt.items.add(files[i]);
                }
            }

            this.dt = newDt;
            input.files = this.dt.files;
            this.render();
        }

        render() {
            const container = document.getElementById(this.previewContainerId);
            if (!container) return;

            container.innerHTML = '';
            const files = this.dt.files;

            if (files.length === 0) return;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                const fileIndex = i;

                const card = document.createElement('div');
                card.className = 'relative group/thumb w-24 h-24 rounded-2xl overflow-hidden border-2 border-slate-200 shadow-sm bg-slate-100 flex-shrink-0 transition-all hover:border-teal-400 hover:shadow-md';

                reader.onload = (e) => {
                    card.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover">
                        
                        <!-- Botón de eliminación siempre visible y cliqueable -->
                        <button type="button" 
                                onclick="event.preventDefault(); event.stopPropagation(); imageUploaders['${this.inputId}'].removeFile(${fileIndex})" 
                                class="absolute top-1 right-1 z-30 w-6 h-6 bg-rose-600 hover:bg-rose-700 active:scale-90 text-white rounded-full flex items-center justify-center font-black text-xs shadow-lg transition-all cursor-pointer" 
                                title="Eliminar esta imagen">
                            ✕
                        </button>

                        <!-- Nombre de la imagen -->
                        <div class="absolute inset-x-0 bottom-0 bg-slate-900/85 py-1 px-1 text-center pointer-events-none z-10">
                            <span class="text-white text-[9px] font-bold block truncate font-mono">${file.name}</span>
                        </div>
                    `;
                };

                reader.readAsDataURL(file);
                container.appendChild(card);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar selector de imágenes en Modal de Creación
        if (document.getElementById('create_product_images_input')) {
            imageUploaders['create_product_images_input'] = new AccumulativeImageUploader('create_product_images_input', 'create_images_preview_container');
        }

        // Inicializar selector de imágenes en Modales de Edición
        document.querySelectorAll('[id^="edit_product_images_input_"]').forEach(input => {
            const id = input.id;
            const productId = id.replace('edit_product_images_input_', '');
            imageUploaders[id] = new AccumulativeImageUploader(id, 'edit_images_preview_container_' + productId);
        });
    });
</script>

@if(request()->filled('edit'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('edit-product-modal-{{ request('edit') }}');
        });
    </script>
@endif
@endsection