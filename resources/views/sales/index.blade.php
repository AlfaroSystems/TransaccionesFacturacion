@extends('layouts.app')
@section('title', 'Ventas')
@section('content')
<div class="animate-fade-in">
    <x-ui.page-header title="Ventas" subtitle="Ventas registradas y entregadas.">
        <x-slot:actions>
            @can('sales.rentabilidad')
                <x-ui.button :href="route('sales.report')" variant="secondary" icon="currency">Rentabilidad</x-ui.button>
            @endcan
            @can('sales.crear')
                <x-ui.button :href="route('sales.create')" icon="plus">Nueva Venta</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.filter-bar :action="route('sales.index')" class="flex flex-col sm:flex-row gap-3 mb-6">
        <x-ui.input name="search" :value="request('search')" placeholder="Buscar por código, documento o cliente..." class="flex-1" />
        <x-ui.button type="submit" icon="search" class="!rounded-xl">Buscar</x-ui.button>
        @if(request('search'))
            <x-ui.button :href="route('sales.index')" variant="secondary" class="!rounded-xl">Limpiar</x-ui.button>
        @endif
    </x-ui.filter-bar>

    <x-ui.table>
        <x-slot:head>
            <x-ui.th>Código / Fecha</x-ui.th>
            <x-ui.th>Cliente</x-ui.th>
            <x-ui.th>Documento</x-ui.th>
            <x-ui.th>Pago</x-ui.th>
            <x-ui.th align="right">Total</x-ui.th>
            <x-ui.th align="center">Estado</x-ui.th>
            <x-ui.th align="center">Acciones</x-ui.th>
        </x-slot:head>
        @forelse($sales as $sale)
            <tr class="group">
                <td>
                    <span class="font-bold text-primary dark:text-accent text-xs">{{ $sale->sale_code }}</span>
                    <span class="block text-muted text-xs">{{ $sale->sale_date?->format('d/m/Y H:i') }}</span>
                </td>
                <td class="text-sm font-semibold text-body">{{ $sale->customer->name ?? '-' }}</td>
                <td class="text-sm text-muted">{{ $sale->documentLabel() }}</td>
                <td class="text-sm text-muted">{{ $sale->paymentLabel() }}</td>
                <td class="text-sm text-right font-extrabold text-body">$ {{ number_format($sale->total, 2) }}</td>
                <td class="text-center">
                    @php
                        $tone = match($sale->status) {
                            'delivered' => 'success',
                            'cancelled' => 'danger',
                            default => 'neutral',
                        };
                    @endphp
                    <x-ui.badge :tone="$tone">{{ $sale->statusLabel() }}</x-ui.badge>
                </td>
                <td class="text-center">
                    <x-ui.icon-button icon="eye" tone="info" :href="route('sales.show', $sale)" label="Ver venta" />
                </td>
            </tr>
        @empty
            <x-ui.empty-state :colspan="7" icon="cart" title="Sin ventas" message="No hay ventas registradas." />
        @endforelse
    </x-ui.table>

    @if($sales->hasPages())<div class="mt-6">{{ $sales->links() }}</div>@endif
</div>
@endsection
