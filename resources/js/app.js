
import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/themes/airbnb.css';

window.Alpine = Alpine;
Alpine.start();

// Calendario de los campos de fecha (initFlatpickr en el layout); como módulo se ejecuta
// antes de DOMContentLoaded, que es cuando el layout lo inicializa
flatpickr.l10ns.es = Spanish;
window.flatpickr = flatpickr;
