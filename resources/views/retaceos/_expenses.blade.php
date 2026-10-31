{{--
    Otros gastos aduanales / seguro del retaceo: una línea por gasto, con su tipo (de la tabla de
    tipos de gasto), descripción y monto. Su suma va al campo $totalId, que lee el prorrateo de
    la página; el servidor la vuelve a calcular con las líneas (ver RetaceoService).

    Variables: $expenseTypes (tipos activos), $totalId y $lineas (con las que abre el formulario).
    Desde la página: retaceoGastos.reemplazar(lineas) y retaceoGastos.sugerir(lineas).
--}}
<div>
    <div class="flex items-center justify-between mb-2">
        <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Otros Gastos Aduanales / Seguro</span>
        <button type="button" onclick="retaceoGastos.agregar()" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline">+ Agregar Gasto</button>
    </div>
    <div id="retaceoExpenseRows" class="space-y-2"></div>
    <div class="flex items-center justify-end gap-3 mt-2">
        <label for="{{ $totalId }}" class="text-xs font-bold text-slate-500 uppercase">Total otros gastos ($)</label>
        <input type="number" id="{{ $totalId }}" value="0.00" readonly tabindex="-1" title="Suma de las líneas de gasto" class="w-40 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 text-sm text-right font-bold font-mono text-slate-800 dark:text-slate-100 cursor-not-allowed">
    </div>
</div>

<template id="retaceoExpenseTypeOptions">
    <option value="">-- Tipo de Gasto --</option>
    @foreach($expenseTypes as $expenseType)
        <option value="{{ $expenseType->id_expense_type }}">{{ $expenseType->name }}</option>
    @endforeach
</template>

<script>
    const retaceoGastos = (() => {
        const campoClass = 'w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-hidden';
        const filas = () => document.getElementById('retaceoExpenseRows');
        let indice = 0;

        function escapar(texto) {
            const span = document.createElement('span');
            span.textContent = texto ?? '';
            return span.innerHTML;
        }

        // La suma va al total que lee el prorrateo, que escucha su evento input
        function sumar() {
            let total = 0;
            filas().querySelectorAll('.retaceo-expense-amount').forEach(input => { total += parseFloat(input.value) || 0; });
            const campo = document.getElementById(@json($totalId));
            campo.value = total.toFixed(2);
            campo.dispatchEvent(new Event('input'));
        }

        function agregar(gasto = {}, sugerido = false) {
            const i = indice++;
            const fila = document.createElement('div');
            fila.className = 'retaceo-expense-row flex items-center gap-2 border border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-slate-50 dark:bg-slate-900';
            if (sugerido) fila.dataset.sugerido = '1';
            fila.innerHTML = `
                <div class="basis-[35%] min-w-0">
                    <select name="expenses[${i}][id_expense_type]" required class="retaceo-expense-type ${campoClass} font-medium">
                        ${document.getElementById('retaceoExpenseTypeOptions').innerHTML}
                    </select>
                </div>
                <div class="basis-[40%] min-w-0">
                    <input type="text" name="expenses[${i}][description]" value="${escapar(gasto.description)}" maxlength="255" placeholder="Descripción del gasto (ej. seguro)" class="retaceo-expense-desc ${campoClass} font-medium">
                </div>
                <div class="basis-[20%] min-w-0">
                    <input type="number" step="0.0001" min="0" name="expenses[${i}][amount]" value="${gasto.amount ?? ''}" required placeholder="Monto $" class="retaceo-expense-amount ${campoClass} font-bold text-right font-mono">
                </div>
                <button type="button" class="retaceo-expense-remove shrink-0 text-rose-500 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors" title="Eliminar gasto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            `;
            fila.querySelector('select').value = gasto.id_expense_type ?? '';
            filas().appendChild(fila);
            sumar();
        }

        function reemplazar(lineas, sugerido = false) {
            filas().innerHTML = '';
            (lineas || []).forEach(gasto => agregar(gasto, sugerido));
            sumar();
        }

        // Los gastos de la orden reemplazan solo lo que el usuario no ha tocado
        function sugerir(lineas) {
            const actuales = [...filas().querySelectorAll('.retaceo-expense-row')];
            if (actuales.every(fila => fila.dataset.sugerido === '1')) {
                reemplazar(lineas, true);
            }
        }

        document.addEventListener('input', e => {
            const fila = e.target.closest?.('.retaceo-expense-row');
            if (!fila) return;
            delete fila.dataset.sugerido;
            // Escrita a mano: ya no la cambia el tipo
            if (e.target.classList.contains('retaceo-expense-desc')) delete e.target.dataset.auto;
            if (e.target.classList.contains('retaceo-expense-amount')) sumar();
        });

        // Al elegir el tipo, su nombre como descripción si la línea no tiene una o si la anterior
        // también la puso el tipo (así no queda la de otro tipo)
        document.addEventListener('change', e => {
            if (!e.target.classList?.contains('retaceo-expense-type')) return;
            const fila = e.target.closest('.retaceo-expense-row');
            delete fila.dataset.sugerido;
            const descripcion = fila.querySelector('.retaceo-expense-desc');
            const opcion = e.target.selectedOptions[0];
            if (descripcion.value.trim() && descripcion.dataset.auto !== '1') return;
            descripcion.value = opcion?.value ? opcion.text.trim() : '';
            descripcion.dataset.auto = '1';
        });

        document.addEventListener('click', e => {
            const boton = e.target.closest?.('.retaceo-expense-remove');
            if (!boton) return;
            boton.closest('.retaceo-expense-row').remove();
            sumar();
        });

        document.addEventListener('DOMContentLoaded', () => reemplazar(@json($lineas)));

        return { agregar, reemplazar, sugerir };
    })();
</script>
