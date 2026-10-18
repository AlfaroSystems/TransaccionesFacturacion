{{--
    Productos que el departamento de compras cotiza sin solicitud de compra, y la sucursal y
    bodega que los reciben. Se incluye una sola vez por página.

    Variables: $products, $units, $branches, $warehouses, $ownRows (líneas actuales con
    id_product, id_unit y quantity), $destinationBranch, $destinationWarehouse.
--}}
@php
    // Catálogo para el buscador de productos
    $productCatalog = $products->map(fn ($product) => [
        'id' => $product->id_product,
        'name' => $product->name,
        'sku' => $product->sku,
        'unit' => $product->purchase_unit,
    ])->values();
@endphp
<div class="space-y-2">
    <div class="flex items-center justify-between gap-3">
        <label class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            Productos sin Solicitud de Compra
        </label>
        <button type="button" onclick="addOwnProductRow(null, true)" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline">
            + Agregar Producto
        </button>
    </div>
    <p class="text-xs text-slate-400">Productos que compras necesita cotizar aunque ninguna sucursal los haya pedido.</p>

    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[480px]">
            <thead class="bg-slate-50 dark:bg-slate-900/80 font-extrabold text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                <tr>
                    <th class="py-2.5 px-3">Producto</th>
                    <th class="py-2.5 px-3 w-28">Cantidad</th>
                    <th class="py-2.5 px-3 w-32">Unidad</th>
                    <th class="py-2.5 px-3 w-12"></th>
                </tr>
            </thead>
            <tbody id="own-products-tbody" class="divide-y divide-slate-100 dark:divide-slate-700"></tbody>
        </table>
        <p id="own-products-empty" class="p-4 text-center text-xs text-slate-400 {{ count($ownRows) ? 'hidden' : '' }}">Sin productos agregados.</p>
    </div>

    <div id="own-products-destination" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 {{ count($ownRows) ? '' : 'hidden' }}">
        <div class="space-y-1">
            <label class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sucursal Destino <span class="text-rose-500">*</span></label>
            <select name="id_branch" id="own-destination-branch" onchange="filterOwnWarehouses()" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white">
                <option value="">Seleccione sucursal...</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id_branch }}" @selected($branch->id_branch == $destinationBranch)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bodega Destino <span class="text-rose-500">*</span></label>
            <select name="id_warehouse" id="own-destination-warehouse" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white">
                <option value="">Seleccione bodega...</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id_warehouse }}" data-branch="{{ $warehouse->id_branch }}" @selected($warehouse->id_warehouse == $destinationWarehouse)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <p class="sm:col-span-2 text-xs text-slate-400">Reciben estos productos: sus órdenes de compra se generan para esta sucursal y bodega.</p>
    </div>
</div>

<template id="own-product-row-template">
    <tr class="own-product-row">
        <td class="p-2">
            <input type="hidden" data-field="id_product" class="own-product-value">
            <div class="relative">
                <svg class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" required autocomplete="off" placeholder="Buscar por nombre o SKU..."
                    onfocus="openOwnProductPicker(this)" oninput="searchOwnProductPicker(this)" onkeydown="navigateOwnProductPicker(event, this)" onblur="leaveOwnProductPicker(this)"
                    class="own-product-search w-full pl-8 pr-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-medium text-slate-800 dark:text-white placeholder:text-slate-400">
            </div>
        </td>
        <td class="p-2">
            <input type="number" data-field="quantity" step="0.0001" min="0.0001" required placeholder="0" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center text-xs font-bold text-slate-800 dark:text-white">
        </td>
        <td class="p-2">
            <select data-field="id_unit" required class="own-unit-select w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-medium text-slate-800 dark:text-white">
                @foreach($units as $unit)
                    <option value="{{ $unit->id_unit }}">{{ $unit->abbreviation ?: $unit->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="p-2 text-center">
            <button type="button" onclick="removeOwnProductRow(this)" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors" title="Quitar producto">✕</button>
        </td>
    </tr>
</template>

<script>
const ownProductCatalog = @json($productCatalog);
const ownProductInitialRows = @json(array_values($ownRows));
// Lista desplegable del buscador: una sola, en el body, para que el modal no la recorte
const ownProductPicker = { element: null, input: null, items: [], active: -1 };
const OWN_PRODUCT_PICKER_LIMIT = 100;
let ownProductRowIndex = 0;

function ownProductRows() {
    return document.querySelectorAll('#own-products-tbody .own-product-row');
}

function addOwnProductRow(row = null, focus = false) {
    const clone = document.getElementById('own-product-row-template').content.cloneNode(true);
    const tr = clone.querySelector('tr');
    const index = ownProductRowIndex++;
    tr.querySelectorAll('[data-field]').forEach(element => {
        element.name = `products[${index}][${element.dataset.field}]`;
    });

    if (row) {
        const product = ownProductCatalog.find(p => String(p.id) === String(row.id_product));
        if (product) {
            tr.querySelector('.own-product-value').value = product.id;
            tr.querySelector('.own-product-search').value = product.name;
        }
        tr.querySelector('[data-field="quantity"]').value = row.quantity ?? '';
        if (row.id_unit) tr.querySelector('.own-unit-select').value = row.id_unit;
    }

    document.getElementById('own-products-tbody').appendChild(tr);
    refreshOwnProducts();
    if (focus) tr.querySelector('.own-product-search').focus();
    return tr;
}

function removeOwnProductRow(button) {
    button.closest('.own-product-row').remove();
    refreshOwnProducts();
}

// ---- Buscador de productos ----

function normalizeOwnProductText(text) {
    return (text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

function ownProductPickerElement() {
    if (!ownProductPicker.element) {
        const element = document.createElement('div');
        element.className = 'hidden fixed z-[70] max-h-64 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-2xl py-1 text-xs';
        document.body.appendChild(element);
        ownProductPicker.element = element;
    }
    return ownProductPicker.element;
}

function selectedOwnProduct(input) {
    const value = input.closest('.own-product-row').querySelector('.own-product-value').value;
    return ownProductCatalog.find(p => String(p.id) === value) || null;
}

function openOwnProductPicker(input) {
    ownProductPicker.input = input;
    input.select();
    renderOwnProductPicker();
}

// Al escribir se pierde el producto elegido hasta que se elija otro de la lista
function searchOwnProductPicker(input) {
    input.closest('.own-product-row').querySelector('.own-product-value').value = '';
    input.setCustomValidity(input.value.trim() ? 'Elija un producto de la lista.' : '');
    ownProductPicker.input = input;
    renderOwnProductPicker();
}

function renderOwnProductPicker() {
    const input = ownProductPicker.input;
    const element = ownProductPickerElement();
    const selected = selectedOwnProduct(input);

    // Con el producto elegido en el campo se muestran todos; si no, los que coinciden con
    // todas las palabras escritas (nombre o SKU). Cada producto se elige una sola vez.
    const words = selected && input.value === selected.name ? [] : normalizeOwnProductText(input.value).split(/\s+/).filter(Boolean);
    const taken = Array.from(ownProductRows())
        .filter(row => row.querySelector('.own-product-search') !== input)
        .map(row => row.querySelector('.own-product-value').value)
        .filter(Boolean);
    const matches = ownProductCatalog
        .filter(p => !taken.includes(String(p.id)))
        .filter(p => words.every(word => normalizeOwnProductText(p.name + ' ' + p.sku).includes(word)));

    ownProductPicker.items = matches.slice(0, OWN_PRODUCT_PICKER_LIMIT);
    ownProductPicker.active = Math.max(0, ownProductPicker.items.findIndex(p => selected && p.id === selected.id));
    if (!ownProductPicker.items.length) ownProductPicker.active = -1;

    element.innerHTML = '';
    if (!ownProductPicker.items.length) {
        const empty = document.createElement('p');
        empty.className = 'px-3 py-2.5 text-slate-400';
        empty.textContent = 'Sin resultados';
        element.appendChild(empty);
    }
    ownProductPicker.items.forEach((product, i) => {
        const option = document.createElement('div');
        option.className = 'own-product-option px-3 py-2 cursor-pointer text-slate-700 dark:text-slate-200';
        const name = document.createElement('span');
        name.className = 'block font-bold';
        name.textContent = product.name;
        const sku = document.createElement('span');
        sku.className = 'block font-mono text-[10px] text-slate-400';
        sku.textContent = product.sku;
        option.append(name, sku);
        // mousedown: elige antes de que el campo pierda el foco
        option.addEventListener('mousedown', event => { event.preventDefault(); chooseOwnProduct(i); });
        option.addEventListener('mousemove', () => { if (ownProductPicker.active !== i) highlightOwnProduct(i); });
        element.appendChild(option);
    });
    if (matches.length > OWN_PRODUCT_PICKER_LIMIT) {
        const more = document.createElement('p');
        more.className = 'px-3 py-2 text-slate-400 border-t border-slate-100 dark:border-slate-700';
        more.textContent = 'Escriba más para ver otros productos...';
        element.appendChild(more);
    }

    // Debajo del campo, o encima si no cabe
    const rect = input.getBoundingClientRect();
    element.style.left = rect.left + 'px';
    element.style.width = Math.max(rect.width, 280) + 'px';
    const spaceBelow = window.innerHeight - rect.bottom;
    if (spaceBelow < 220 && rect.top > spaceBelow) {
        element.style.top = '';
        element.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
    } else {
        element.style.bottom = '';
        element.style.top = (rect.bottom + 4) + 'px';
    }
    element.classList.remove('hidden');
    highlightOwnProduct(ownProductPicker.active);
}

function highlightOwnProduct(index) {
    ownProductPicker.active = index;
    const list = ownProductPickerElement();
    list.querySelectorAll('.own-product-option').forEach((option, i) => {
        option.classList.toggle('bg-teal-50', i === index);
        option.classList.toggle('dark:bg-slate-700', i === index);
        // Se desplaza solo la lista (no la página) para mostrar el resaltado
        if (i === index) {
            if (option.offsetTop < list.scrollTop) {
                list.scrollTop = option.offsetTop;
            } else if (option.offsetTop + option.offsetHeight > list.scrollTop + list.clientHeight) {
                list.scrollTop = option.offsetTop + option.offsetHeight - list.clientHeight;
            }
        }
    });
}

function chooseOwnProduct(index) {
    const product = ownProductPicker.items[index];
    const input = ownProductPicker.input;
    if (!product || !input) return;
    const row = input.closest('.own-product-row');
    row.querySelector('.own-product-value').value = product.id;
    input.value = product.name;
    input.setCustomValidity('');
    if (product.unit) row.querySelector('.own-unit-select').value = product.unit;
    hideOwnProductPicker();
}

function hideOwnProductPicker() {
    ownProductPickerElement().classList.add('hidden');
}

function navigateOwnProductPicker(event, input) {
    const open = !ownProductPickerElement().classList.contains('hidden') && ownProductPicker.input === input;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (!open) return openOwnProductPicker(input);
        const last = ownProductPicker.items.length - 1;
        if (last < 0) return;
        const next = ownProductPicker.active + (event.key === 'ArrowDown' ? 1 : -1);
        highlightOwnProduct(Math.min(last, Math.max(0, next)));
    } else if (event.key === 'Enter') {
        // Enter elige el producto resaltado, no envía el formulario
        event.preventDefault();
        if (open && ownProductPicker.active >= 0) chooseOwnProduct(ownProductPicker.active);
    } else if (event.key === 'Escape' && open) {
        event.preventDefault();
        event.stopPropagation();
        hideOwnProductPicker();
    }
}

// Al salir del campo sin elegir: si lo escrito es exactamente el nombre de un producto, se elige
function leaveOwnProductPicker(input) {
    hideOwnProductPicker();
    if (selectedOwnProduct(input) || !input.value.trim()) return;
    const typed = normalizeOwnProductText(input.value.trim());
    const index = ownProductPicker.items.findIndex(p => normalizeOwnProductText(p.name) === typed);
    if (index >= 0) chooseOwnProduct(index);
}

// La lista sigue al campo: al desplazar el modal o la página se cierra
window.addEventListener('scroll', event => {
    if (event.target !== ownProductPicker.element) hideOwnProductPicker();
}, true);
window.addEventListener('resize', hideOwnProductPicker);

// ---- Filas y destino ----

// Sin productos propios no hay destino que elegir (ni se envía)
function refreshOwnProducts() {
    const hasRows = ownProductRows().length > 0;
    document.getElementById('own-products-empty').classList.toggle('hidden', hasRows);
    document.getElementById('own-products-destination').classList.toggle('hidden', !hasRows);
    ['own-destination-branch', 'own-destination-warehouse'].forEach(id => {
        const select = document.getElementById(id);
        select.required = hasRows;
        select.disabled = !hasRows;
    });
}

function filterOwnWarehouses() {
    const branchId = document.getElementById('own-destination-branch').value;
    const warehouseSelect = document.getElementById('own-destination-warehouse');
    const options = warehouseSelect.querySelectorAll('option[data-branch]');
    let selectedStillValid = false;
    options.forEach(option => {
        const belongs = branchId && option.dataset.branch === branchId;
        option.hidden = !belongs;
        option.disabled = !belongs;
        if (option.selected && belongs) selectedStillValid = true;
    });
    if (!selectedStillValid) {
        // Si la sucursal tiene una sola bodega, se elige sola
        const available = Array.from(options).filter(option => !option.disabled);
        warehouseSelect.value = available.length === 1 ? available[0].value : '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    ownProductInitialRows.forEach(row => addOwnProductRow(row));
    filterOwnWarehouses();
    refreshOwnProducts();
});
</script>
