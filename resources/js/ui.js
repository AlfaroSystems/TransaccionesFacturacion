/*
 * UI del sistema: tema, modales, confirmaciones y calendarios.
 * Antes vivía como <script> inline en layouts/app.blade.php. Se expone en
 * window porque muchas vistas lo invocan por onclick="openModal(...)" etc.
 */

// Escapa texto antes de insertarlo con innerHTML en las plantillas JS
window.escapeHtml = function (value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

window.toggleDarkMode = function () {
    const html = document.documentElement;
    if (html.classList.contains('dark')) {
        html.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        html.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
};

// -------------------------------- Modales --------------------------------
window.openModal = function (id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (window.initFlatpickr) window.initFlatpickr(modal);
    const card = modal.querySelector('.transform');
    if (card) {
        setTimeout(() => {
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }, 10);
    }
};

window.closeModal = function (id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    const card = modal.querySelector('.transform');
    if (card) {
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 150);
    } else {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
};

// Visor de imágenes global
window.openGlobalImageModal = function (imageSrc, titleText = '') {
    const modal = document.getElementById('global-image-modal');
    const targetImg = document.getElementById('global-image-target');
    const titleEl = document.getElementById('global-image-title');
    if (!modal || !targetImg) return;
    targetImg.src = imageSrc;
    if (titleEl) titleEl.textContent = titleText || '';
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
};
window.closeGlobalImageModal = function () {
    const modal = document.getElementById('global-image-modal');
    if (!modal) return;
    modal.style.display = 'none';
    modal.classList.add('hidden');
};

// Confirmación global de inactivar / reactivar / eliminar
window.confirmDelete = function (actionUrl, resourceName, isReactivate = false, customDescription = null) {
    const modal = document.getElementById('global-delete-modal');
    const card = document.getElementById('global-delete-card');
    const form = document.getElementById('global-delete-form');
    const title = document.getElementById('global-delete-title');
    const desc = document.getElementById('global-delete-description');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    const iconContainer = card ? card.querySelector('.w-14.h-14') : null;
    if (!form || !modal) return;
    form.action = actionUrl;

    const dangerBtn = 'px-5 py-2.5 bg-danger text-white font-semibold rounded-xl text-sm transition-all shadow-sm hover:brightness-110';
    const successBtn = 'px-5 py-2.5 bg-success text-white font-semibold rounded-xl text-sm transition-all shadow-sm hover:brightness-110';

    if (isReactivate === 'delete') {
        title.textContent = `¿Eliminar ${resourceName}?`;
        desc.textContent = customDescription || `Estás a punto de eliminar '${resourceName}'. Esta acción no se puede deshacer.`;
        if (submitBtn) { submitBtn.textContent = 'Sí, eliminar'; submitBtn.className = dangerBtn; }
        if (iconContainer) {
            iconContainer.className = 'w-14 h-14 rounded-full border-2 border-danger-line flex items-center justify-center mx-auto text-danger mb-5';
            iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>';
        }
    } else if (isReactivate === true) {
        title.textContent = `¿Reactivar ${resourceName}?`;
        desc.textContent = customDescription || `El estado del registro '${resourceName}' pasará a estar activo nuevamente.`;
        if (submitBtn) { submitBtn.textContent = 'Sí, reactivar'; submitBtn.className = successBtn; }
        if (iconContainer) {
            iconContainer.className = 'w-14 h-14 rounded-full border-2 border-success-line flex items-center justify-center mx-auto text-success mb-5';
            iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
        }
    } else {
        title.textContent = `¿Inactivar ${resourceName}?`;
        desc.textContent = customDescription || `El estado del registro '${resourceName}' pasará a estar inactivo.`;
        if (submitBtn) { submitBtn.textContent = 'Sí, inactivar'; submitBtn.className = dangerBtn; }
        if (iconContainer) {
            iconContainer.className = 'w-14 h-14 rounded-full border-2 border-warning-line flex items-center justify-center mx-auto text-warning mb-5';
            iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
        }
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        if (card) { card.classList.remove('scale-95'); card.classList.add('scale-100'); }
    }, 10);
};
window.closeGlobalDeleteModal = function () { window.closeModal('global-delete-modal'); };

// ------------------------------- Flatpickr -------------------------------
window.initFlatpickr = function (container = document) {
    if (typeof flatpickr === 'undefined') return;
    container.querySelectorAll('input[type="datetime-local"], .flatpickr-datetime').forEach(function (el) {
        if (el._flatpickr) return;
        flatpickr(el, {
            locale: 'es', enableTime: true, dateFormat: 'Y-m-d H:i',
            altInput: true, altFormat: 'd/m/Y h:i K', allowInput: true,
            minDate: el.dataset.minDate || null,
        });
    });
    container.querySelectorAll('input[type="date"], .flatpickr-date').forEach(function (el) {
        if (el._flatpickr) return;
        flatpickr(el, {
            locale: 'es', enableTime: false, dateFormat: 'Y-m-d',
            altInput: true, altFormat: 'd/m/Y', allowInput: true,
            minDate: el.dataset.minDate || null,
        });
    });
};

document.addEventListener('DOMContentLoaded', function () {
    window.initFlatpickr();
});

// -------------------- Mensajes de validación en español -----------------
document.addEventListener('invalid', (function () {
    return function (e) {
        if (e.target && e.target.setCustomValidity) {
            e.target.setCustomValidity('');
            if (!e.target.validity.valid) {
                if (e.target.validity.valueMissing) {
                    e.target.setCustomValidity('Por favor complete este campo.');
                } else if (e.target.validity.typeMismatch && e.target.type === 'email') {
                    e.target.setCustomValidity('Por favor ingrese un correo electrónico válido.');
                } else if (e.target.validity.typeMismatch && e.target.type === 'url') {
                    e.target.setCustomValidity('Por favor ingrese una URL válida.');
                }
            }
        }
    };
})(), true);
document.addEventListener('input', function (e) {
    if (e.target && e.target.setCustomValidity) {
        e.target.setCustomValidity('');
    }
});
