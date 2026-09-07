@extends('layouts.app')
@section('title', 'Detalle de Solicitud de Cotización')

@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado con Botón Regresar y Botón Registrar Oferta -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchase-quotation-requests.index') }}" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline flex items-center gap-1">
                    ← Volver al listado
                </a>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">
                Solicitud #{{ str_pad($purchaseQuotationRequest->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT) }}
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Gestión de cotización y ofertas recibidas de proveedores.</p>
        </div>
        <div>
            <button type="button" onclick="openProviderOfferModal()" class="bg-[#005e66] hover:bg-[#00474f] text-white font-bold px-5 py-2.5 rounded-xl shadow-lg transition-all flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Registrar Oferta de Proveedor</span>
            </button>
        </div>
    </div>

    <!-- Información General -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Código de Solicitud de Compra</span>
            <span class="text-lg font-extrabold text-slate-800 dark:text-white mt-1 block">
                {{ $purchaseQuotationRequest->purchaseRequest->purchase_request_code ?? 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Fecha de Creación</span>
            <span class="text-lg font-medium text-slate-700 dark:text-slate-300 mt-1 block">
                {{ $purchaseQuotationRequest->created_at->format('d/m/Y h:i A') }}
            </span>
        </div>
        <div class="md:col-span-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Justificación</span>
            <p class="text-slate-600 dark:text-slate-300 text-sm mt-1">
                {{ $purchaseQuotationRequest->purchaseRequest->justification ?? 'Sin justificación registrada.' }}
            </p>
        </div>
    </div>

    <!-- Tabla de Ítems Solicitados -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden p-6">
        <h3 class="text-base font-extrabold text-slate-800 dark:text-white mb-4">Productos Solicitados</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800 text-xs uppercase font-extrabold text-slate-400 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Producto</th>
                        <th class="py-3 px-4">Unidad</th>
                        <th class="py-3 px-4 text-center">Cantidad a Cotizar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($purchaseQuotationRequest->details ?? [] as $detail)
                        <tr>
                            <td class="py-3 px-4 font-bold text-slate-800 dark:text-white">
                                {{ $detail->product->name ?? 'Producto no disponible' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-xs font-semibold">
                                    {{ $detail->unit->name ?? 'Unidad' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold">
                                {{ $detail->quantity }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-400 text-xs">
                                No hay ítems registrados para esta solicitud.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL PARA REGISTRAR OFERTA DE PROVEEDOR --}}
<div id="provider-offer-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm p-4">
    <div id="provider-offer-card" class="w-full max-w-3xl bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl transform scale-95 transition-all duration-200 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700 shrink-0">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800 dark:text-slate-100">Registrar Oferta de Proveedor</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ingrese precios unitarios, impuestos, condiciones y gastos adicionales.</p>
            </div>
            <button type="button" onclick="closeProviderOfferModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('supplier-quotations.store') }}" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <input type="hidden" name="id_purchase_quotation_request" value="{{ $purchaseQuotationRequest->id_purchase_quotation_request }}">

            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Proveedor <span class="text-rose-500">*</span></label>
                        <input type="text" name="provider_name" required placeholder="Nombre o Razón Social del Proveedor" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium focus:ring-2 focus:ring-[#005e66] outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Condiciones de Pago</label>
                        <input type="text" name="payment_terms" placeholder="Ej. Crédito a 30 días, Contado..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium focus:ring-2 focus:ring-[#005e66] outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Impuesto (%)</label>
                        <input type="number" step="0.01" name="tax_percentage" value="13" placeholder="Ej. 13 o 15" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium focus:ring-2 focus:ring-[#005e66] outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Gastos Adicionales / Envío</label>
                        <input type="number" step="0.01" name="additional_costs" value="0.00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium focus:ring-2 focus:ring-[#005e66] outline-none">
                    </div>
                </div>

                <div class="space-y-2 pt-2">
                    <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Precios Unitarios por Producto</label>
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 dark:bg-slate-900/80 font-extrabold text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                                <tr>
                                    <th class="py-2.5 px-3">Producto</th>
                                    <th class="py-2.5 px-3 text-center">Cant. Solicitada</th>
                                    <th class="py-2.5 px-3 text-center w-40">Precio Unitario ($)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($purchaseQuotationRequest->details ?? [] as $index => $detail)
                                    <tr>
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">
                                            {{ $detail->product->name ?? 'Producto' }}
                                            <input type="hidden" name="items[{{ $index }}][id_purchase_request_detail]" value="{{ $detail->id_purchase_request_detail }}">
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-mono font-bold">
                                            {{ $detail->quantity }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" step="0.0001" min="0" name="items[{{ $index }}][unit_price]" required placeholder="0.00" class="w-32 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-bold text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-none">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                <button type="button" onclick="closeProviderOfferModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white text-xs font-bold transition-all shadow-sm">
                    Guardar Oferta
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openProviderOfferModal() {
    const modal = document.getElementById('provider-offer-modal');
    const card = document.getElementById('provider-offer-card');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }, 10);
}

function closeProviderOfferModal() {
    const modal = document.getElementById('provider-offer-modal');
    const card = document.getElementById('provider-offer-card');
    card.classList.remove('scale-100');
    card.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 150);
}
</script>
@endsection