@extends('layouts.app')
@section('title', 'Asignación de Precios')
@section('content')
<div class="animate-fade-in duration-300">
    {{-- ENCABEZADO --}}
    <x-ui.page-header eyebrow="Precios" title="Asignación de Precios"
        subtitle="Precio de venta vigente de cada producto y su historial de cambios." />

    {{-- BUSCADOR --}}
    <div class="ui-surface p-4 mb-6">
        <form method="GET" action="{{ route('prices.index') }}" class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Buscar por nombre, SKU o código interno..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-line text-sm focus:border-accent">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold rounded-xl text-sm transition-all">Buscar</button>
                @if(request('search'))
                    <a href="{{ route('prices.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl text-sm transition-all">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    {{-- LISTADO --}}
    <section class="overflow-x-auto">
        <table class="ui-table">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Producto</th>
                    <th class="py-3 px-6">Categoría</th>
                    <th class="py-3 px-6 text-right">Precio vigente</th>
                    <th class="py-3 px-6">Desde</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php $current = $product->currentPrice; @endphp
                    <tr class="group hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-surface rounded-l-2xl border-l border-y border-line text-sm">
                            <span class="font-bold text-primary dark:text-accent text-xs">{{ $product->sku }}</span>
                            <span class="font-extrabold text-slate-800 block">{{ $product->name }}</span>
                        </td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm text-slate-600 font-semibold">{{ $product->category->name ?? '-' }}</td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm text-right">
                            @if($current)
                                <span class="font-extrabold text-slate-800">$ {{ number_format($current->price, 2) }}</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-warning-soft text-warning border border-amber-200 uppercase tracking-wider">Sin precio</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-surface border-y border-line text-sm text-slate-500">
                            {{ $current ? $current->valid_from->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="py-4 px-6 bg-surface rounded-r-2xl border-r border-y border-line text-center">
                            <div class="flex items-center justify-center gap-2">
                                @can('prices.cambiar')
                                    <button type="button" onclick="openModal('change-price-modal-{{ $product->id_product }}')"
                                        class="px-3 py-2 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-xs transition-all">
                                        {{ $current ? 'Cambiar precio' : 'Asignar precio' }}
                                    </button>
                                @endcan
                                @can('prices.historial')
                                    <button type="button" onclick="openModal('history-price-modal-{{ $product->id_product }}')"
                                        class="p-2.5 rounded-xl bg-info-soft text-info hover:brightness-95 font-semibold text-xs transition-all flex items-center justify-center" title="Historial">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-slate-400 font-semibold">No se encontraron productos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($products->hasPages())
        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</div>

{{-- MODALES --}}
@foreach($products as $product)
    @php $current = $product->currentPrice; @endphp

    {{-- Cambiar / Asignar precio --}}
    @can('prices.cambiar')
        <div id="change-price-modal-{{ $product->id_product }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-surface rounded-3xl p-6 max-w-md w-full shadow-2xl mx-4">
                <div class="flex items-center justify-between border-b pb-4 mb-5">
                    <h3 class="text-lg font-extrabold text-slate-800">{{ $current ? 'Cambiar precio' : 'Asignar precio' }}</h3>
                    <button type="button" onclick="closeModal('change-price-modal-{{ $product->id_product }}')" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
                </div>
                <p class="text-sm font-semibold text-slate-600 mb-4">{{ $product->name }}
                    @if($current)<span class="block text-xs text-slate-400 mt-0.5">Precio vigente: $ {{ number_format($current->price, 2) }}</span>@endif
                </p>
                <form action="{{ route('prices.change', $product) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="ui-label">Nuevo precio *</label>
                        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $current->price ?? '') }}" required
                            class="ui-input">
                    </div>
                    <div>
                        <label class="ui-label">Motivo / Observación</label>
                        <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}"
                            class="ui-input">
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" onclick="closeModal('change-price-modal-{{ $product->id_product }}')" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- Historial --}}
    @can('prices.historial')
        <div id="history-price-modal-{{ $product->id_product }}" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-surface rounded-3xl p-6 max-w-2xl w-full shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-4 mb-5">
                    <h3 class="text-lg font-extrabold text-slate-800">Historial de precios — {{ $product->name }}</h3>
                    <button type="button" onclick="closeModal('history-price-modal-{{ $product->id_product }}')" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
                </div>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b">
                            <th class="py-2 pr-4 text-right">Precio</th>
                            <th class="py-2 px-4">Desde</th>
                            <th class="py-2 px-4">Hasta</th>
                            <th class="py-2 px-4">Usuario</th>
                            <th class="py-2 pl-4">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->prices as $price)
                            <tr class="border-b border-slate-50">
                                <td class="py-2 pr-4 text-right font-bold text-slate-800">$ {{ number_format($price->price, 2) }}</td>
                                <td class="py-2 px-4 text-slate-600">{{ $price->valid_from->format('d/m/Y H:i') }}</td>
                                <td class="py-2 px-4">
                                    @if($price->valid_until)
                                        <span class="text-slate-500">{{ $price->valid_until->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-success-soft text-success border border-emerald-200 uppercase">Vigente</span>
                                    @endif
                                </td>
                                <td class="py-2 px-4 text-slate-600">{{ $price->user->name ?? '-' }}</td>
                                <td class="py-2 pl-4 text-slate-500">{{ $price->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-400 font-semibold">Este producto todavía no tiene precios registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endcan
@endforeach
@endsection
