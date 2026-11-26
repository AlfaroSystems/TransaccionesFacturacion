@extends('layouts.app')
@section('title', 'Asignación de Precios')
@section('content')
<div class="animate-fade-in duration-300">
    <x-ui.page-header eyebrow="Precios" title="Asignación de Precios"
        subtitle="Presentaciones de venta de cada producto (Unidad, Blíster, Caja...), su precio vigente e historial." />

    {{-- BUSCADOR --}}
    <div class="ui-surface p-4 mb-6">
        <form method="GET" action="{{ route('prices.index') }}" class="flex flex-col sm:flex-row gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, SKU o código interno..."
                class="flex-1 px-4 py-2.5 rounded-xl border border-line text-sm focus:border-accent">
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold rounded-xl text-sm">Buscar</button>
                @if(request('search'))
                    <a href="{{ route('prices.index') }}" class="px-5 py-2.5 bg-sunken hover:brightness-95 text-body font-bold rounded-xl text-sm">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    {{-- PRODUCTOS Y PRESENTACIONES --}}
    <div class="space-y-4">
        @forelse($products as $product)
            <div class="ui-surface p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <span class="font-extrabold text-body">{{ $product->name }}</span>
                        <span class="text-xs text-muted">· {{ $product->sku }} · {{ $product->category->name ?? 'Sin categoría' }}</span>
                    </div>
                    @can('prices.cambiar')
                        <button type="button" onclick="openModal('add-pres-{{ $product->id_product }}')" class="px-3 py-1.5 rounded-lg bg-customTeal-800 hover:bg-customTeal-500 text-white text-xs font-bold">+ Presentación</button>
                    @endcan
                </div>

                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-line">
                            <th class="py-2">Presentación</th>
                            <th class="py-2 text-center">Factor (u. base)</th>
                            <th class="py-2 text-right">Precio vigente</th>
                            <th class="py-2">Desde</th>
                            <th class="py-2 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->presentations as $pres)
                            @php $current = $pres->currentPrice; @endphp
                            <tr class="border-b border-line/60 {{ $pres->is_active ? '' : 'opacity-50' }}">
                                <td class="py-2 font-semibold text-body">{{ $pres->name }}</td>
                                <td class="py-2 text-center text-muted">{{ rtrim(rtrim(number_format($pres->factor, 4), '0'), '.') }}</td>
                                <td class="py-2 text-right">
                                    @if($current)<span class="font-extrabold text-body">$ {{ number_format($current->price, 2) }}</span>
                                    @else<span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-amber-100 text-amber-700 border border-amber-200 uppercase">Sin precio</span>@endif
                                </td>
                                <td class="py-2 text-muted text-xs">{{ $current ? $current->valid_from->format('d/m/Y H:i') : '-' }}</td>
                                <td class="py-2 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @can('prices.cambiar')
                                            <button type="button" onclick="openModal('change-pres-{{ $pres->id_product_presentation }}')" class="px-3 py-1.5 rounded-lg bg-customTeal-800 hover:bg-customTeal-500 text-white text-xs font-bold">{{ $current ? 'Cambiar' : 'Asignar' }} precio</button>
                                        @endcan
                                        @can('prices.historial')
                                            <button type="button" onclick="openModal('hist-pres-{{ $pres->id_product_presentation }}')" class="px-2.5 py-1.5 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 text-xs font-bold" title="Historial">Historial</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-muted">Sin presentaciones.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MODAL: agregar presentación --}}
            @can('prices.cambiar')
                <div id="add-pres-{{ $product->id_product }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
                    <div class="bg-surface rounded-3xl p-6 max-w-sm w-full pop-shadow mx-4 border border-line">
                        <div class="flex items-center justify-between border-b border-line pb-4 mb-5"><h3 class="text-lg font-extrabold text-body">Nueva presentación</h3><button type="button" onclick="closeModal('add-pres-{{ $product->id_product }}')" class="text-muted text-xl">✕</button></div>
                        <p class="text-sm text-muted mb-4">{{ $product->name }}</p>
                        <form action="{{ route('prices.presentations.store', $product) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="ui-label">Nombre *</label>
                                <input type="text" name="name" maxlength="100" required placeholder="Ej. Caja, Blíster, Unidad" class="w-full px-4 py-2.5 rounded-xl border border-line text-sm">
                            </div>
                            <div>
                                <label class="ui-label">Factor (unidades base por presentación) *</label>
                                <input type="number" step="0.0001" min="0.0001" name="factor" value="1" required class="w-full px-4 py-2.5 rounded-xl border border-line text-sm">
                            </div>
                            <div class="flex justify-end gap-3 pt-3 border-t border-line">
                                <button type="button" onclick="closeModal('add-pres-{{ $product->id_product }}')" class="px-5 py-2.5 rounded-xl bg-sunken text-body font-bold text-sm">Cancelar</button>
                                <button type="submit" class="px-5 py-2.5 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm">Agregar</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            {{-- MODALES por presentación --}}
            @foreach($product->presentations as $pres)
                @php $current = $pres->currentPrice; @endphp
                @can('prices.cambiar')
                    <div id="change-pres-{{ $pres->id_product_presentation }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
                        <div class="bg-surface rounded-3xl p-6 max-w-sm w-full pop-shadow mx-4 border border-line">
                            <div class="flex items-center justify-between border-b border-line pb-4 mb-5"><h3 class="text-lg font-extrabold text-body">{{ $current ? 'Cambiar' : 'Asignar' }} precio</h3><button type="button" onclick="closeModal('change-pres-{{ $pres->id_product_presentation }}')" class="text-muted text-xl">✕</button></div>
                            <p class="text-sm text-muted mb-4">{{ $product->name }} — <strong>{{ $pres->name }}</strong>@if($current)<span class="block text-xs">Vigente: $ {{ number_format($current->price, 2) }}</span>@endif</p>
                            <form action="{{ route('prices.change', $pres) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="ui-label">Nuevo precio *</label>
                                    <input type="number" step="0.01" min="0" name="price" value="{{ $current->price ?? '' }}" required class="w-full px-4 py-2.5 rounded-xl border border-line text-sm">
                                </div>
                                <div>
                                    <label class="ui-label">Motivo / Observación</label>
                                    <input type="text" name="notes" maxlength="255" class="w-full px-4 py-2.5 rounded-xl border border-line text-sm">
                                </div>
                                <div class="flex justify-end gap-3 pt-3 border-t border-line">
                                    <button type="button" onclick="closeModal('change-pres-{{ $pres->id_product_presentation }}')" class="px-5 py-2.5 rounded-xl bg-sunken text-body font-bold text-sm">Cancelar</button>
                                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm">Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan
                @can('prices.historial')
                    <div id="hist-pres-{{ $pres->id_product_presentation }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
                        <div class="bg-surface rounded-3xl p-6 max-w-xl w-full pop-shadow mx-4 max-h-[90vh] overflow-y-auto border border-line">
                            <div class="flex items-center justify-between border-b border-line pb-4 mb-5"><h3 class="text-lg font-extrabold text-body">Historial — {{ $product->name }} ({{ $pres->name }})</h3><button type="button" onclick="closeModal('hist-pres-{{ $pres->id_product_presentation }}')" class="text-muted text-xl">✕</button></div>
                            <table class="w-full text-left text-sm">
                                <thead><tr class="text-[10px] font-extrabold text-slate-400 uppercase border-b border-line"><th class="py-2 text-right">Precio</th><th class="py-2 px-3">Desde</th><th class="py-2 px-3">Hasta</th><th class="py-2 px-3">Usuario</th><th class="py-2">Motivo</th></tr></thead>
                                <tbody>
                                    @forelse($pres->prices as $price)
                                        <tr class="border-b border-line/60">
                                            <td class="py-2 text-right font-bold text-body">$ {{ number_format($price->price, 2) }}</td>
                                            <td class="py-2 px-3 text-muted">{{ $price->valid_from->format('d/m/Y H:i') }}</td>
                                            <td class="py-2 px-3">@if($price->valid_until)<span class="text-muted">{{ $price->valid_until->format('d/m/Y H:i') }}</span>@else<span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-emerald-100 text-emerald-700 uppercase">Vigente</span>@endif</td>
                                            <td class="py-2 px-3 text-muted">{{ $price->user->name ?? '-' }}</td>
                                            <td class="py-2 text-muted">{{ $price->notes ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="py-4 text-center text-muted">Sin precios registrados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endcan
            @endforeach
        @empty
            <div class="ui-surface p-8 text-center text-muted font-semibold">No se encontraron productos.</div>
        @endforelse
    </div>

    @if($products->hasPages())<div class="mt-6">{{ $products->links() }}</div>@endif
</div>
@endsection
