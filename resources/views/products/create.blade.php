@extends('layouts.app')

@section('title', 'Nuevo Producto')

@section('content')

<div class="max-w-4xl mx-auto">

    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">
                Nuevo Producto
            </h1>
            <p class="text-gray-500 mt-1">
                Complete la información para registrar un nuevo artículo en el inventario.
            </p>
        </div>

        <a href="{{ route('products.index') }}"
           class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2.5 rounded-xl transition text-sm flex items-center gap-2">
            ← Volver al Listado
        </a>
    </div>

    <!-- Errores de validación -->
    @if($errors->any())
        <div class="mb-6 bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-xl shadow-sm">
            <p class="font-bold mb-1">Por favor corrige los siguientes errores:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario Avanzado -->
    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 md:p-8">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Nombre -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-bold text-slate-700 mb-2">Nombre del Producto <span class="text-rose-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="Ej. Paracetamol 500mg - Caja x 20 tabletas">
                </div>

                <!-- SKU -->
                <div>
                    <label for="sku" class="block text-sm font-bold text-slate-700 mb-2">
                        Código SKU <span class="text-xs text-slate-400 font-normal">(Opcional, se autogenera si se deja vacío)</span>
                    </label>
                    <input type="text" id="sku" name="sku" value="{{ old('sku') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-mono uppercase"
                           placeholder="Ej. MED-PAR-500">
                </div>

                <!-- Código de Barras -->
                <div>
                    <label for="barcode" class="block text-sm font-bold text-slate-700 mb-2">Código de Barras</label>
                    <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-mono"
                           placeholder="Ej. 7501234567890">
                </div>

                <!-- Select de Categoría -->
                <div>
                    <label for="id_category" class="block text-sm font-bold text-slate-700 mb-2">Categoría</label>
                    <select id="id_category" name="id_category"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm transition">
                        <option value="">Seleccione una categoría...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('id_category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Select de Subcategoría (Cargado dinámicamente vía AJAX) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="id_sub_category" class="block text-sm font-bold text-slate-700">Subcategoría</label>
                        <span id="sub-category-loading" class="text-xs text-blue-600 font-semibold hidden flex items-center gap-1">
                            <svg class="animate-spin h-3.5 w-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Cargando...
                        </span>
                    </div>
                    <select id="id_sub_category" name="id_sub_category" disabled
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed transition">
                        <option value="">Seleccione una categoría primero...</option>
                    </select>
                </div>

                <!-- Selector para Unidad de Compra (datos provistos por Dev 1) -->
                <div>
                    <label for="purchase_unit" class="block text-sm font-bold text-slate-700 mb-2">Unidad de Compra</label>
                    <select id="purchase_unit" name="purchase_unit"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Seleccione unidad de compra...</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('purchase_unit') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }} {{ $unit->abbreviation ? '(' . $unit->abbreviation . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Selector para Unidad de Venta (datos provistos por Dev 1) -->
                <div>
                    <label for="sale_unit" class="block text-sm font-bold text-slate-700 mb-2">Unidad de Venta</label>
                    <select id="sale_unit" name="sale_unit"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Seleccione unidad de venta...</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('sale_unit') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }} {{ $unit->abbreviation ? '(' . $unit->abbreviation . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Precio de Compra -->
                <div>
                    <label for="purchase_price" class="block text-sm font-bold text-slate-700 mb-2">Precio de Compra / Costo ($)</label>
                    <input type="number" step="0.01" min="0" id="purchase_price" name="purchase_price" value="{{ old('purchase_price', '0.00') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <!-- Precio de Venta -->
                <div>
                    <label for="sale_price" class="block text-sm font-bold text-slate-700 mb-2">Precio de Venta ($) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" id="sale_price" name="sale_price" value="{{ old('sale_price', '0.00') }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-semibold">
                </div>

                <!-- Stock Inicial -->
                <div>
                    <label for="stock" class="block text-sm font-bold text-slate-700 mb-2">Stock Inicial</label>
                    <input type="number" min="0" id="stock" name="stock" value="{{ old('stock', '0') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <!-- Stock Mínimo -->
                <div>
                    <label for="min_stock" class="block text-sm font-bold text-slate-700 mb-2">Stock Mínimo (Alerta)</label>
                    <input type="number" min="0" id="min_stock" name="min_stock" value="{{ old('min_stock', '5') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>

                <!-- Descripción -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-bold text-slate-700 mb-2">Descripción o Indicaciones</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                              placeholder="Detalles sobre el uso, lote, laboratorio o especificaciones técnicas...">{{ old('description') }}</textarea>
                </div>

                <!-- Estado Activo -->
                <div class="md:col-span-2 flex items-center gap-3 pt-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-5 h-5 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                    <label for="is_active" class="text-sm font-bold text-slate-700 cursor-pointer">
                        Producto activo y disponible para ventas / inventario
                    </label>
                </div>

            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100">
                <a href="{{ route('products.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-sm transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl shadow-lg transition text-sm">
                    Guardar Producto
                </button>
            </div>

        </form>
    </div>

</div>

<!-- SCRIPT AJAX: Carga dinámica de Subcategorías según la Categoría seleccionada -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('id_category');
    const subCategorySelect = document.getElementById('id_sub_category');
    const loadingIndicator = document.getElementById('sub-category-loading');
    const selectedSubCategory = "{{ old('id_sub_category', '') }}";

    function loadSubCategories(categoryId, preselectedValue = '') {
        if (!categoryId) {
            subCategorySelect.innerHTML = '<option value="">Seleccione una categoría primero...</option>';
            subCategorySelect.disabled = true;
            return;
        }

        // Mostrar indicador de carga y deshabilitar temporalmente
        if (loadingIndicator) loadingIndicator.classList.remove('hidden');
        subCategorySelect.disabled = true;
        subCategorySelect.innerHTML = '<option value="">Cargando subcategorías...</option>';

        // Endpoint desarrollado por Dev 3: /api/categories/{id}/sub-categories
        fetch(`/api/categories/${categoryId}/sub-categories`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(subCategories => {
            subCategorySelect.innerHTML = '<option value="">Seleccione una subcategoría...</option>';

            if (subCategories.length === 0) {
                subCategorySelect.innerHTML = '<option value="">No hay subcategorías disponibles</option>';
            } else {
                subCategories.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.name;
                    if (preselectedValue && String(sub.id) === String(preselectedValue)) {
                        option.selected = true;
                    }
                    subCategorySelect.appendChild(option);
                });
            }

            subCategorySelect.disabled = false;
        })
        .catch(error => {
            console.error('Error al cargar subcategorías:', error);
            subCategorySelect.innerHTML = '<option value="">Error al cargar subcategorías</option>';
        })
        .finally(() => {
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
        });
    }

    // Escuchar el evento change del select de Categoría
    categorySelect.addEventListener('change', function () {
        loadSubCategories(this.value);
    });

    // Si ya hay una categoría seleccionada al cargar (ej. después de un error de validación)
    if (categorySelect.value) {
        loadSubCategories(categorySelect.value, selectedSubCategory);
    }
});
</script>

@endsection
