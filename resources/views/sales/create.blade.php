@extends('layouts.app')
@section('title', 'Nueva Venta')
@section('content')
<div class="animate-fade-in duration-300" x-data="pos()">
    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-primary dark:text-accent tracking-tight">Nueva Venta</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Venta de mostrador — se descuenta el inventario al finalizar (PEPS).</p>
        </div>
        <x-ui.button :href="route('sales.index')" variant="secondary" icon="arrow-left">Volver</x-ui.button>
    </header>

    @if ($errors->any())
        <div class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if($products->isEmpty())
        <div class="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 font-semibold">
            No hay productos con precio de venta asignado. Asigne precios en el módulo <a href="{{ route('prices.index') }}" class="underline">Precios</a> antes de vender.
        </div>
    @else
    <form action="{{ route('sales.store') }}" method="POST" @submit="prepare">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Columna izquierda: productos --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="ui-surface p-4">
                    <label class="ui-label">Buscar producto</label>
                    <div class="relative">
                        <input type="text" x-model="search" @focus="openList = true" @click.away="openList = false"
                            placeholder="Nombre, SKU o código interno..."
                            class="ui-input">
                        <div x-show="openList && filtered.length" class="absolute z-30 left-0 right-0 mt-1 bg-surface border border-line rounded-xl shadow-xl max-h-64 overflow-y-auto">
                            <template x-for="p in filtered" :key="p.id">
                                <button type="button" @click="addProduct(p)" class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-700"><span x-text="p.name"></span>
                                        <span class="text-xs text-slate-400" x-text="p.sku"></span></span>
                                    <span class="text-sm font-bold text-primary dark:text-accent" x-text="'$ ' + p.price.toFixed(2)"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="ui-surface overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider bg-slate-50">
                                <th class="py-2 px-4">Producto</th>
                                <th class="py-2 px-3 w-24 text-center">Cant.</th>
                                <th class="py-2 px-3 w-28 text-right">Precio</th>
                                <th class="py-2 px-3 w-28 text-right">Desc.</th>
                                <th class="py-2 px-3 w-28 text-right">Total</th>
                                <th class="py-2 px-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(line, i) in cart" :key="line.id">
                                <tr class="border-t border-line">
                                    <td class="py-2 px-4 font-semibold text-slate-700" x-text="line.name"></td>
                                    <td class="py-2 px-3 text-center">
                                        <input type="number" min="1" step="1" inputmode="numeric" x-model.number="line.qty"
                                            @input="line.qty = line.qty.toString().replace(/[^0-9]/g,'')"
                                            @blur="line.qty = Math.max(1, Math.floor(Number(line.qty) || 1))"
                                            class="w-20 px-2 py-1.5 rounded-lg border border-line bg-surface text-body text-sm text-center">
                                    </td>
                                    <td class="py-2 px-3 text-right text-slate-600" x-text="'$ ' + line.price.toFixed(2)"></td>
                                    <td class="py-2 px-3 text-right">
                                        <input type="number" min="0" step="0.01" x-model.number="line.discount"
                                            class="w-20 px-2 py-1.5 rounded-lg border border-line text-sm text-right">
                                    </td>
                                    <td class="py-2 px-3 text-right font-bold text-slate-800" x-text="'$ ' + lineSubtotal(line).toFixed(2)"></td>
                                    <td class="py-2 px-2 text-center">
                                        <button type="button" @click="cart.splice(i,1)" class="text-rose-500 hover:text-rose-700 font-bold">✕</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!cart.length">
                                <td colspan="6" class="py-8 text-center text-slate-400 font-semibold">Busque y agregue productos a la venta.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Columna derecha: datos y totales --}}
            <div class="space-y-4">
                <div class="ui-surface p-4 space-y-4">
                    @if($branches->count() > 1)
                        <div>
                            <label class="ui-label">Sucursal *</label>
                            <select name="id_branch" class="w-full px-4 py-2.5 rounded-xl border border-line text-sm">
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id_branch }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="id_branch" value="{{ $branches->first()->id_branch ?? '' }}">
                        <p class="text-xs font-semibold text-slate-500">Sucursal: <span class="font-bold text-slate-700">{{ $branches->first()->name ?? '—' }}</span></p>
                    @endif

                    <div>
                        <label class="ui-label">Cliente *</label>
                        <select name="id_customer" id="id_customer" required class="w-full px-4 py-2.5 rounded-xl border border-line text-sm">
                            <option value="">Seleccione cliente</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id_customer }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        @can('customers.crear')
                            <button type="button" onclick="openModal('create-customer-modal')" class="text-[11px] text-primary dark:text-accent font-semibold hover:underline">+ Registrar cliente</button>
                        @endcan
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="ui-label">Documento *</label>
                            <select name="document_type" class="w-full px-3 py-2.5 rounded-xl border border-line text-sm">
                                <option value="invoice">Factura</option>
                                <option value="ccf">Crédito Fiscal</option>
                            </select>
                        </div>
                        <div>
                            <label class="ui-label">Forma de pago *</label>
                            <select name="payment_method" class="w-full px-3 py-2.5 rounded-xl border border-line text-sm">
                                <option value="cash">Efectivo</option>
                                <option value="credit_card">Tarjeta</option>
                                <option value="check">Cheque</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="ui-label">IVA (%)</label>
                        <input type="number" name="tax_rate" x-model.number="taxRate" min="0" max="100" step="0.01"
                            class="w-full px-4 py-2.5 rounded-xl border border-line text-sm">
                    </div>
                </div>

                <div class="ui-surface p-4 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500 font-semibold">Subtotal</span><span class="font-bold" x-text="'$ ' + totals.subtotal.toFixed(2)"></span></div>
                    <div class="flex justify-between"><span class="text-slate-500 font-semibold">IVA</span><span class="font-bold" x-text="'$ ' + totals.tax.toFixed(2)"></span></div>
                    <div class="flex justify-between text-base pt-2 border-t"><span class="text-slate-700 font-extrabold">Total</span><span class="font-extrabold text-primary dark:text-accent" x-text="'$ ' + totals.total.toFixed(2)"></span></div>
                    <button type="submit" :disabled="!cart.length"
                        class="w-full mt-3 px-5 py-3 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 disabled:opacity-50 text-white font-bold text-sm transition-all">
                        Finalizar venta
                    </button>
                </div>
            </div>
        </div>

        {{-- Líneas enviadas al servidor --}}
        <template x-for="(line, i) in cart" :key="'h'+line.id">
            <div>
                <input type="hidden" :name="`lines[${i}][id_product]`" :value="line.id">
                <input type="hidden" :name="`lines[${i}][quantity]`" :value="line.qty">
                <input type="hidden" :name="`lines[${i}][discount]`" :value="line.discount || 0">
            </div>
        </template>
    </form>
    @endif
</div>

{{-- MODAL: alta rápida de cliente sin salir de la venta --}}
@can('customers.crear')
<div id="create-customer-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/70 backdrop-blur-xs p-4">
    <div class="bg-surface rounded-3xl p-6 max-w-3xl w-full pop-shadow mx-4 max-h-[90vh] overflow-y-auto border border-line">
        <div class="flex items-center justify-between border-b border-line pb-4 mb-6">
            <h3 class="text-xl font-extrabold text-body">Registrar nuevo cliente</h3>
            <button type="button" onclick="closeModal('create-customer-modal')" class="text-muted hover:text-body text-xl">✕</button>
        </div>
        <form id="sale-customer-form" action="{{ route('customers.store') }}" method="POST" class="space-y-5">
            @csrf
            @include('customers._form', ['customer' => null, 'prefix' => 'sale_cust_'])
            <div class="flex justify-end gap-3 pt-4 border-t border-line">
                <button type="button" onclick="closeModal('create-customer-modal')" class="px-6 py-2.5 rounded-xl bg-sunken text-body font-bold text-sm hover:brightness-95">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-on-primary font-bold text-sm">Guardar cliente</button>
            </div>
        </form>
    </div>
</div>
@endcan

<script>
    // --- Alta rápida de cliente desde el modal de la venta ---
    function setupCustomerGeo(prefix) {
        const deptSelect = document.getElementById(prefix + 'id_department');
        const muniSelect = document.getElementById(prefix + 'id_municipality');
        const distSelect = document.getElementById(prefix + 'id_district');
        if (!deptSelect || !muniSelect || !distSelect) return;
        const allMunis = Array.from(muniSelect.options).filter(o => o.value !== '');
        const allDists = Array.from(distSelect.options).filter(o => o.value !== '');
        function filterMunis() {
            const deptId = deptSelect.value;
            muniSelect.innerHTML = '<option value="">Seleccione municipio</option>';
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            allMunis.filter(o => o.getAttribute('data-parent') === deptId).forEach(o => muniSelect.appendChild(o.cloneNode(true)));
        }
        function filterDists() {
            const muniId = muniSelect.value;
            distSelect.innerHTML = '<option value="">Seleccione distrito</option>';
            allDists.filter(o => o.getAttribute('data-parent') === muniId).forEach(o => distSelect.appendChild(o.cloneNode(true)));
        }
        deptSelect.addEventListener('change', filterMunis);
        muniSelect.addEventListener('change', filterDists);
    }
    function toggleCustomerNrc(prefix) {
        const typeSelect = document.getElementById(prefix + 'customer_type');
        const nrcWrap = document.getElementById(prefix + 'nrc_wrap');
        if (!typeSelect || !nrcWrap) return;
        const apply = () => nrcWrap.classList.toggle('hidden', typeSelect.value !== 'taxpayer');
        typeSelect.addEventListener('change', apply);
        apply();
    }
    document.addEventListener('DOMContentLoaded', function () {
        setupCustomerGeo('sale_cust_');
        toggleCustomerNrc('sale_cust_');

        const form = document.getElementById('sale-customer-form');
        if (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: new FormData(form),
                    });
                    if (res.status === 201) {
                        const data = await res.json();
                        const select = document.getElementById('id_customer');
                        const opt = document.createElement('option');
                        opt.value = data.id_customer;
                        opt.textContent = data.name;
                        select.appendChild(opt);
                        select.value = data.id_customer;
                        closeModal('create-customer-modal');
                        form.reset();
                        toggleCustomerNrc('sale_cust_');
                        if (window.sileo) window.sileo.success('Cliente registrado y seleccionado.', '¡Listo!');
                    } else if (res.status === 422) {
                        const { errors } = await res.json();
                        const first = Object.values(errors)[0][0];
                        if (window.sileo) window.sileo.error(first, 'Revise los datos'); else alert(first);
                    } else {
                        throw new Error('HTTP ' + res.status);
                    }
                } catch (err) {
                    if (window.sileo) window.sileo.error('No se pudo registrar el cliente.', 'Error'); else alert('No se pudo registrar el cliente.');
                } finally {
                    btn.disabled = false;
                }
            });
        }
    });

    function pos() {
        return {
            products: @json($products),
            cart: [],
            search: '',
            openList: false,
            taxRate: 13,
            get filtered() {
                const s = this.search.trim().toLowerCase();
                if (!s) return this.products.slice(0, 20);
                return this.products.filter(p =>
                    (p.name || '').toLowerCase().includes(s) ||
                    (p.sku || '').toLowerCase().includes(s) ||
                    (p.code || '').toLowerCase().includes(s)
                ).slice(0, 20);
            },
            addProduct(p) {
                const existing = this.cart.find(l => l.id === p.id);
                if (existing) { existing.qty = Number(existing.qty) + 1; }
                else { this.cart.push({ id: p.id, name: p.name, sku: p.sku, price: p.price, qty: 1, discount: 0 }); }
                this.search = '';
                this.openList = false;
            },
            lineSubtotal(line) {
                const sub = (Number(line.qty) || 0) * line.price - (Number(line.discount) || 0);
                return sub > 0 ? sub : 0;
            },
            get totals() {
                let subtotal = 0;
                this.cart.forEach(l => { subtotal += this.lineSubtotal(l); });
                const tax = subtotal * (Number(this.taxRate) || 0) / 100;
                return { subtotal, tax, total: subtotal + tax };
            },
            prepare(e) {
                if (!this.cart.length) { e.preventDefault(); }
            },
        };
    }
</script>
@endsection
