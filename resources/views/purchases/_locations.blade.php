{{--
    Ubicación de cada línea de la compra: una de la bodega destino, prellenada con la última
    donde entró el producto en esa bodega. Ahí entra al inventario lo recibido.

    Variables: $purchaseLocations (ubicaciones por bodega) y $purchaseLastLocations (última
    ubicación de cada producto por bodega).
--}}
<script>
const purchaseLocations = @json($purchaseLocations);
const purchaseLastLocations = @json($purchaseLastLocations);

function escapePurchaseHtml(text) {
    const span = document.createElement('span');
    span.textContent = text ?? '';
    return span.innerHTML;
}

// Opciones de la bodega; sin una elegida, la última donde entró el producto
function purchaseLocationOptions(warehouseId, productId, selectedId) {
    const locations = purchaseLocations[warehouseId] || [];
    const selected = selectedId || (purchaseLastLocations[warehouseId] || {})[productId] || '';
    let html = `<option value="">${locations.length ? 'Sin ubicación' : (warehouseId ? 'Bodega sin ubicaciones' : 'Elija la bodega')}</option>`;
    locations.forEach(location => {
        html += `<option value="${location.id}" ${String(location.id) === String(selected) ? 'selected' : ''}>${escapePurchaseHtml(location.code)}</option>`;
    });
    return html;
}

function purchaseLocationSelect(idx, warehouseId, productId, selectedId) {
    return `<select name="details[${idx}][id_location]" data-product="${productId}" class="location-select w-full min-w-[120px] px-2 py-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200">`
        + purchaseLocationOptions(warehouseId, productId, selectedId)
        + '</select>';
}

// Al cambiar la bodega, cada línea muestra las ubicaciones de la nueva
function refreshPurchaseLocations(container, warehouseId) {
    container.querySelectorAll('.location-select').forEach(select => {
        const current = select.value;
        const stillValid = (purchaseLocations[warehouseId] || []).some(location => String(location.id) === current);
        select.innerHTML = purchaseLocationOptions(warehouseId, select.dataset.product, stillValid ? current : '');
    });
}
</script>
