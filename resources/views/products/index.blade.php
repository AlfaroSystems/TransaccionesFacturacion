@extends('layouts.app')
@section('title', 'Catálogo de Productos')
@section('content')
<div class="w-full space-y-6 animate-fade-in">
    <x-ui.page-header
        eyebrow="Catálogo General"
        module="Módulo de Productos"
        title="Catálogo General de Productos"
        subtitle="Consulte, filtre y gestione el catálogo general, imágenes, especificaciones y niveles de existencias.">
        @can('products.crear')
            <x-slot:actions>
                <x-ui.button type="button" icon="plus" onclick="openModal('create-product-modal')">Nuevo Producto</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <!-- Métricas rápidas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-ui.metric-card icon="inventory" tone="primary" label="Total Catálogo" :value="$products->total()" />
        <x-ui.metric-card icon="check-circle" tone="success" label="Activos" :value="$products->where('is_active', true)->count()" />
    </div>

    <!-- Buscador y filtros -->
    <x-ui.filter-bar :action="route('products.index')" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <x-ui.input name="search" :value="request('search')" placeholder="Buscar por nombre, SKU, códigos de fábrica o barras..." />
        </div>
        <x-ui.select name="id_category">
            <option value="">Todas las Categorías</option>
            @foreach($categories as $category)
                <option value="{{ $category->id_category }}" {{ request('id_category') == $category->id_category ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </x-ui.select>
        <div class="flex gap-2">
            <x-ui.button type="submit" icon="filter" class="flex-1 !rounded-xl">Filtrar</x-ui.button>
            @if(request('search') || request('id_category'))
                <x-ui.button :href="route('products.index')" variant="secondary" class="!rounded-xl">Limpiar</x-ui.button>
            @endif
        </div>
    </x-ui.filter-bar>

    <!-- Listado de productos -->
    <x-ui.table>
        <x-slot:head>
            <x-ui.th align="center">Imagen</x-ui.th>
            <x-ui.th>Producto / Códigos</x-ui.th>
            <x-ui.th>Categoría / Subcat.</x-ui.th>
            <x-ui.th>Presentación &amp; Medidas</x-ui.th>
            <x-ui.th align="right">Último Costo</x-ui.th>
            <x-ui.th align="center">Estado</x-ui.th>
            <x-ui.th align="center">Acciones</x-ui.th>
        </x-slot:head>
                @forelse($products as $product)
                    <tr @class(['group', 'is-inactive' => !$product->is_active])>
                        <!-- Imagen de producto -->
                        <td class="text-center">
                            @if($product->images->count() > 0)
                                <div class="relative w-12 h-12 mx-auto rounded-xl overflow-hidden border border-line shadow-xs group-hover:scale-105 transition-all cursor-pointer" onclick="openGlobalImageModal('{{ asset('storage/' . $product->images->first()->path) }}', '{{ addslashes($product->name) }}')" title="Ampliar imagen de {{ addslashes($product->name) }}">
                                    <img src="{{ asset('storage/' . $product->images->first()->path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @if($product->images->count() > 1)
                                        <span class="absolute bottom-0 right-0 bg-slate-900/80 text-white text-[9px] font-extrabold px-1 rounded-tl">
                                            +{{ $product->images->count() - 1 }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <div class="w-12 h-12 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-lg font-bold border border-line">
                                    🖼️
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="font-extrabold text-body text-sm">{{ $product->name }}</div>
                            <div class="flex items-center gap-2 text-xs text-subtle font-mono mt-0.5 flex-wrap">
                                <span>SKU: {{ $product->sku }}</span>
                                @if($product->original_code)
                                    <span>• Orig: {{ $product->original_code }}</span>
                                @endif
                                @if($product->internal_code)
                                    <span>• Int: {{ $product->internal_code }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-sm">
                            <div class="font-bold text-body">{{ $product->category?->name ?? 'Sin Categoría' }}</div>
                            <div class="text-xs text-muted font-semibold">{{ $product->subCategory?->name ?? 'Sin Subcategoría' }}</div>
                        </td>
                        <td class="text-xs">
                            <div class="font-semibold text-body">{{ $product->presentation ?: 'N/A' }}</div>
                            <div class="text-muted">
                                {{ $product->size ? 'Talla/Tam: '.$product->size : '' }} 
                                {{ $product->dimensions ? '('.$product->dimensions.')' : '' }}
                            </div>
                        </td>
                        <td class="text-xs text-right whitespace-nowrap">
                            @if($product->last_cost !== null)
                                <div class="font-mono font-bold text-body">${{ number_format($product->last_cost, 4) }}</div>
                                <div class="text-muted">
                                    {{ $product->lastCostPurchaseDetail?->unit?->name ? 'por '.$product->lastCostPurchaseDetail->unit->name.' · ' : '' }}{{ $product->last_cost_date?->format('d/m/Y') }}
                                </div>
                            @else
                                <span class="text-subtle">Sin compras</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($product->is_active)
                                <x-ui.badge tone="success">Activo</x-ui.badge>
                            @else
                                <x-ui.badge tone="danger">Inactivo</x-ui.badge>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('products.ver')
                                    <x-ui.icon-button icon="eye" tone="info" :href="route('products.show', $product)" label="Ver detalles" />
                                @endcan
                                @can('products.editar')
                                    <x-ui.icon-button icon="pencil" tone="warning" label="Editar"
                                        onclick="openModal('edit-product-modal-{{ $product->id_product }}')" />
                                @endcan
                                @can('products.eliminar')
                                    @if($product->is_active)
                                        <x-ui.icon-button icon="x-circle" tone="danger" label="Inactivar producto"
                                            onclick="confirmDelete('{{ route('products.destroy', $product) }}', '{{ addslashes($product->name) }}', false)" />
                                    @else
                                        <x-ui.icon-button icon="check-circle" tone="success" label="Reactivar producto"
                                            onclick="confirmDelete('{{ route('products.destroy', $product) }}', '{{ addslashes($product->name) }}', true)" />
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-state :colspan="7" icon="inventory" title="Sin productos"
                        message="No hay productos registrados en el catálogo." />
                @endforelse
    </x-ui.table>

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
        <div id="edit-product-modal-{{ $product->id_product }}" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6 flex items-start sm:items-center justify-center">
            <div class="bg-surface rounded-3xl p-6 sm:p-8 max-w-4xl w-full pop-shadow mx-4 my-auto max-h-[85vh] overflow-y-auto border border-line relative transform scale-95 transition-all duration-200">
                
                <!-- Encabezado Sticky (Fijo al hacer scroll dentro del modal) -->
                <div class="sticky -top-6 -mx-6 -mt-6 sm:-top-8 sm:-mx-8 sm:-mt-8 p-6 bg-surface z-20 border-b border-line flex items-center justify-between mb-6 shadow-xs rounded-t-3xl">
                    <div>
                        <h3 class="text-xl font-extrabold text-body">Editar Producto: {{ $product->name }}</h3>
                        <p class="text-xs text-slate-400">Actualiza los datos del producto, precios e imágenes asociadas.</p>
                    </div>
                    <button type="button" onclick="closeModal('edit-product-modal-{{ $product->id_product }}')" class="p-2 rounded-xl text-muted hover:text-body hover:bg-sunken transition">✕</button>
                </div>

                <!-- Formulario Principal -->
                <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- 1. Información General -->
                    <div class="bg-sunken p-4 rounded-2xl border border-line space-y-3">
                        <h4 class="text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">1. Información General</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="ui-label">Nombre del Producto *</label>
                                <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="ui-input">
                            </div>
                            <div>
                                <label class="ui-label">SKU *</label>
                                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required class="ui-input">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="ui-label">Categoría *</label>
                                <select name="id_category" id="edit_id_category_{{ $product->id_product }}" required onchange="filterSubcategories(this, document.getElementById('edit_id_sub_category_{{ $product->id_product }}'))" class="ui-input">
                                    <option value="">Selecciona categoría...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id_category }}" {{ old('id_category', $product->id_category) == $cat->id_category ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="ui-label">Subcategoría</label>
                                <select name="id_sub_category" id="edit_id_sub_category_{{ $product->id_product }}" class="ui-input">
                                    <option value="">Selecciona subcategoría...</option>
                                    @foreach($subCategories as $sub)
                                        <option value="{{ $sub->id_sub_category }}" data-category="{{ $sub->id_category }}" {{ old('id_sub_category', $product->id_sub_category) == $sub->id_sub_category ? 'selected' : '' }}>{{ $sub->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Códigos de Identificación -->
                    <div class="bg-sunken p-4 rounded-2xl border border-line space-y-3">
                        <h4 class="text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">2. Códigos de Identificación</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="ui-label">Código Original / Fábrica</label>
                                <input type="text" name="original_code" value="{{ old('original_code', $product->original_code) }}" class="ui-input" placeholder="Ej. ORG-9843">
                            </div>
                            <div>
                                <label class="ui-label">Código Interno</label>
                                <input type="text" name="internal_code" value="{{ old('internal_code', $product->internal_code) }}" class="ui-input" placeholder="Ej. INT-0042">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Presentación & Medidas -->
                    <div class="bg-sunken p-4 rounded-2xl border border-line space-y-3">
                        <h4 class="text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">3. Presentación & Especificaciones Técnicas</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="ui-label">Presentación</label>
                                <input type="text" name="presentation" value="{{ old('presentation', $product->presentation) }}" class="ui-input" placeholder="Ej. Caja por 12 unidades">
                            </div>
                            <div>
                                <label class="ui-label">Talla / Tamaño</label>
                                <input type="text" name="size" value="{{ old('size', $product->size) }}" class="ui-input" placeholder="Ej. Grande / 500ml">
                            </div>
                            <div>
                                <label class="ui-label">Dimensiones</label>
                                <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" class="ui-input" placeholder="Ej. 10x5x2 cm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="ui-label">Unidad de Compra</label>
                                <select name="id_purchase_unit" class="ui-input">
                                    <option value="">Selecciona unidad...</option>
                                    @foreach($units as $u)
                                        <option value="{{ $u->id_unit }}" {{ old('id_purchase_unit', $product->id_purchase_unit) == $u->id_unit ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="ui-label">Unidad de Venta</label>
                                <select name="id_sale_unit" class="ui-input">
                                    <option value="">Selecciona unidad...</option>
                                    @foreach($units as $u)
                                        <option value="{{ $u->id_unit }}" {{ old('id_sale_unit', $product->id_sale_unit) == $u->id_unit ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Descripción -->
                    <div class="bg-sunken p-4 rounded-2xl border border-line space-y-2">
                        <label class="block text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">4. Descripción / Notas</label>
                        <textarea name="description" rows="2" class="ui-input">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <!-- 5. Imágenes del Producto -->
                    <div class="bg-sunken p-4 rounded-2xl border border-line space-y-3">
                        <h4 class="text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">5. Imágenes del Producto (Permite múltiples archivos)</h4>
                        
                        <!-- Imágenes Existentes -->
                        @if($product->images->count() > 0)
                            <div>
                                <span class="text-xs font-bold text-slate-500 block mb-2">Imágenes Actuales:</span>
                                <div class="flex flex-wrap gap-3">
                                    @foreach($product->images as $img)
                                        <div class="relative group/img rounded-xl overflow-hidden border border-line shadow-xs bg-surface cursor-pointer" style="width: 80px; height: 80px; min-width: 80px; min-height: 80px; max-width: 80px; max-height: 80px;" onclick="openGlobalImageModal('{{ asset('storage/' . $img->path) }}', 'Imagen de {{ addslashes($product->name) }}')" title="Haz clic para ampliar">
                                            <img src="{{ asset('storage/' . $img->path) }}" style="width: 100%; height: 100%; object-fit: cover;" class="hover:opacity-90 transition-opacity">
                                            <div class="absolute inset-0 bg-slate-900/60 flex items-center justify-center opacity-0 group-hover/img:opacity-100 transition-all">
                                                <button type="button" onclick="event.stopPropagation(); deleteProductImage('{{ route('product-images.destroy', $img->id_product_image) }}')" class="p-1.5 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition cursor-pointer" title="Eliminar imagen">
                                                    🗑️
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Subir nuevas imágenes (Selección Acumulativa) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Agregar más imágenes (se irán sumando):</label>
                            <input type="file" id="edit_product_images_input_{{ $product->id_product }}" name="images[]" multiple accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-customTeal-800 file:text-white hover:file:bg-customTeal-500 cursor-pointer">
                            <p class="text-[11px] text-slate-400 mt-1">Puedes seleccionar imágenes de una en una o varias a la vez. Se irán acumulando abajo.</p>

                            <!-- Vista Previa de Nuevas Imágenes por Subir -->
                            <div id="edit_images_preview_container_{{ $product->id_product }}" class="flex flex-wrap gap-3 pt-2"></div>
                        </div>
                    </div>

                    <!-- Checkbox Activo -->
                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_active" id="edit_prod_active_{{ $product->id_product }}" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="rounded-sm text-primary dark:text-accent">
                        <label for="edit_prod_active_{{ $product->id_product }}" class="text-sm font-semibold text-slate-700">Producto Activo para Operaciones</label>
                    </div>

                    <!-- Acciones del Formulario al final -->
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-line">
                        <button type="button" onclick="closeModal('edit-product-modal-{{ $product->id_product }}')" class="px-6 py-2.5 rounded-xl bg-surface border border-line text-slate-600 font-bold text-sm hover:bg-slate-100 transition">Cancelar</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-customTeal-800 text-white font-bold text-sm hover:bg-customTeal-500 transition-all shadow-md">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach

<!-- MODAL DE CREACIÓN DE PRODUCTO -->
<div id="create-product-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs p-4 sm:p-6 flex items-start sm:items-center justify-center">
    <div class="bg-surface rounded-3xl p-6 sm:p-8 max-w-4xl w-full pop-shadow mx-4 my-auto max-h-[85vh] overflow-y-auto border border-line relative transform scale-95 transition-all duration-200">
        
        <!-- Encabezado Sticky (Fijo al hacer scroll dentro del modal) -->
        <div class="sticky -top-6 -mx-6 -mt-6 sm:-top-8 sm:-mx-8 sm:-mt-8 p-6 bg-surface z-20 border-b border-line flex items-center justify-between mb-6 shadow-xs rounded-t-3xl">
            <div>
                <h3 class="text-xl font-extrabold text-body">Registrar Nuevo Producto</h3>
                <p class="text-xs text-slate-400">Complete la información requerida, especificaciones e imágenes del producto.</p>
            </div>
            <button type="button" onclick="closeModal('create-product-modal')" class="p-2 rounded-xl text-muted hover:text-body hover:bg-sunken transition">✕</button>
        </div>

        <!-- Formulario Completo -->
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- 1. Información General -->
            <div class="bg-sunken p-4 rounded-2xl border border-line space-y-3">
                <h4 class="text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">1. Información General</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="ui-label">Nombre del Producto *</label>
                        <input type="text" name="name" required class="ui-input" placeholder="Ej. Laptop Pro 15 pulgadas">
                    </div>
                    <div>
                        <label class="ui-label">SKU (Opcional - Se autogenera si está vacío)</label>
                        <input type="text" name="sku" class="ui-input" placeholder="Ej. PRD-2026-X9A3B">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="ui-label">Categoría *</label>
                        <select name="id_category" id="create_id_category" required onchange="filterSubcategories(this, document.getElementById('create_id_sub_category'))" class="ui-input">
                            <option value="">Selecciona categoría...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id_category }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ui-label">Subcategoría</label>
                        <select name="id_sub_category" id="create_id_sub_category" class="ui-input">
                            <option value="">Selecciona subcategoría...</option>
                            @foreach($subCategories as $sub)
                                <option value="{{ $sub->id_sub_category }}" data-category="{{ $sub->id_category }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. Códigos de Identificación -->
            <div class="bg-sunken p-4 rounded-2xl border border-line space-y-3">
                <h4 class="text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">2. Códigos de Identificación</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="ui-label">Código Original / Fábrica</label>
                        <input type="text" name="original_code" class="ui-input" placeholder="Ej. FAB-8834">
                    </div>
                    <div>
                        <label class="ui-label">Código Interno</label>
                        <input type="text" name="internal_code" class="ui-input" placeholder="Ej. INT-0021">
                    </div>
                </div>
            </div>

            <!-- 3. Presentación & Medidas -->
            <div class="bg-sunken p-4 rounded-2xl border border-line space-y-3">
                <h4 class="text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">3. Presentación & Especificaciones Técnicas</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="ui-label">Presentación</label>
                        <input type="text" name="presentation" class="ui-input" placeholder="Ej. Caja por 12 unidades">
                    </div>
                    <div>
                        <label class="ui-label">Talla / Tamaño</label>
                        <input type="text" name="size" class="ui-input" placeholder="Ej. Mediano / 250ml">
                    </div>
                    <div>
                        <label class="ui-label">Dimensiones</label>
                        <input type="text" name="dimensions" class="ui-input" placeholder="Ej. 12x4x2 cm">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="ui-label">Unidad de Compra</label>
                        <select name="id_purchase_unit" class="ui-input">
                            <option value="">Selecciona unidad...</option>
                            @foreach($units as $u)
                                <option value="{{ $u->id_unit }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ui-label">Unidad de Venta</label>
                        <select name="id_sale_unit" class="ui-input">
                            <option value="">Selecciona unidad...</option>
                            @foreach($units as $u)
                                <option value="{{ $u->id_unit }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- 4. Descripción -->
            <div class="bg-sunken p-4 rounded-2xl border border-line space-y-2">
                <label class="block text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">4. Descripción / Notas</label>
                <textarea name="description" rows="2" class="ui-input" placeholder="Indicaciones, componentes o detalles adicionales del producto..."></textarea>
            </div>

            <!-- 5. Galería de Imágenes (Selección Acumulativa) -->
            <div class="bg-sunken p-4 rounded-2xl border border-line space-y-3">
                <label class="block text-xs font-bold text-primary dark:text-accent uppercase tracking-wider">5. Imágenes del Producto (Selección Acumulativa)</label>
                <input type="file" id="create_product_images_input" name="images[]" multiple accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-customTeal-800 file:text-white hover:file:bg-customTeal-500 cursor-pointer">
                <p class="text-[11px] text-slate-400">Puedes seleccionar imágenes de una en una o varias a la vez. Se irán acumulando abajo en la vista previa.</p>

                <!-- Vista Previa de Imágenes Seleccionadas -->
                <div id="create_images_preview_container" class="flex flex-wrap gap-3 pt-2"></div>
            </div>

            <!-- Checkbox Activo -->
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" id="create_prod_active" value="1" checked class="rounded-sm text-primary dark:text-accent">
                <label for="create_prod_active" class="text-sm font-semibold text-slate-700">Producto Activo para Operaciones</label>
            </div>

            <!-- Acciones del Formulario al final -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-line">
                <button type="button" onclick="closeModal('create-product-modal')" class="px-6 py-2.5 rounded-xl bg-surface border border-line text-slate-600 font-bold text-sm hover:bg-slate-100 transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-customTeal-800 text-white font-bold text-sm hover:bg-customTeal-500 transition-all shadow-md">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<!-- SCRIPT DE FILTRADO DINÁMICO DE SUBCATEGORÍAS -->
<script>
    function deleteProductImage(actionUrl) {
        if (typeof confirmDelete === 'function') {
            confirmDelete(actionUrl, 'esta imagen', 'delete');
        } else {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);

            document.body.appendChild(form);
            form.submit();
        }
    }

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
                card.style.cssText = 'width: 112px; height: 112px; min-width: 112px; min-height: 112px; max-width: 112px; max-height: 112px; position: relative; overflow: hidden; border-radius: 1rem; border: 2px solid #e2e8f0; background-color: #f8fafc; flex-shrink: 0; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);';
                card.className = 'group/thumb hover:border-teal-500 transition-all cursor-pointer';

                reader.onload = (e) => {
                    const imgSrc = e.target.result;
                    const safeName = file.name.replace(/'/g, "\\'");
                    card.onclick = (evt) => {
                        evt.preventDefault();
                        evt.stopPropagation();
                        openGlobalImageModal(imgSrc, safeName);
                    };
                    card.title = 'Haz clic para ampliar ' + file.name;

                    card.innerHTML = `
                        <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: cover; display: block;" class="hover:scale-105 transition-all">
                        
                        <!-- Botón de eliminación siempre visible y cliqueable -->
                        <button type="button" 
                                onclick="event.preventDefault(); event.stopPropagation(); imageUploaders['${this.inputId}'].removeFile(${fileIndex})" 
                                class="absolute top-1 right-1 z-30 w-6 h-6 bg-rose-600 hover:bg-rose-700 active:scale-90 text-white rounded-full flex items-center justify-center font-black text-xs shadow-lg transition-all cursor-pointer" 
                                title="Eliminar esta imagen">
                            ✕
                        </button>

                        <!-- Nombre de la imagen -->
                        <div class="absolute inset-x-0 bottom-0 bg-slate-900/85 py-1 px-1 text-center pointer-events-none z-10">
                            <span class="text-white text-[9px] font-bold block truncate font-mono">${escapeHtml(file.name)}</span>
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