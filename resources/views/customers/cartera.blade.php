@extends('layouts.app')
@section('title', 'Cartera de Clientes')
@section('content')
<div class="animate-fade-in duration-300">
    <x-ui.page-header eyebrow="Clientes" title="Cartera de Clientes"
        subtitle="Clientes con compras: número de compras, última compra y total acumulado.">
        <x-slot:actions>
            <x-ui.button :href="route('customers.index')" variant="secondary">Volver</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="ui-surface p-4 mb-6">
        <form method="GET" action="{{ route('customers.cartera') }}" class="flex flex-col sm:flex-row gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, documento, teléfono o correo..."
                class="flex-1 px-4 py-2.5 rounded-xl border border-line text-sm focus:border-accent">
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold rounded-xl text-sm">Buscar</button>
                @if(request('search'))<a href="{{ route('customers.cartera') }}" class="px-5 py-2.5 bg-sunken hover:brightness-95 text-body font-bold rounded-xl text-sm">Limpiar</a>@endif
            </div>
        </form>
    </div>

    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-y-2">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-2 px-5 pl-6">Cliente</th>
                    <th class="py-2 px-5">Contacto</th>
                    <th class="py-2 px-5 text-center">Compras</th>
                    <th class="py-2 px-5">Última compra</th>
                    <th class="py-2 px-5 text-right">Total acumulado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td class="py-3 px-5 pl-6 bg-white rounded-l-xl border-l border-y border-slate-100 text-sm">
                            <span class="font-bold text-slate-700">{{ $customer->name }}</span>
                            <span class="block text-xs text-slate-400">{{ $customer->code }} · {{ $customer->typeLabel() }}</span>
                        </td>
                        <td class="py-3 px-5 bg-white border-y border-slate-100 text-sm text-slate-600">
                            {{ $customer->phone ?: '—' }}
                            @if($customer->email)<span class="block text-xs text-slate-400">{{ $customer->email }}</span>@endif
                        </td>
                        <td class="py-3 px-5 bg-white border-y border-slate-100 text-sm text-center font-bold text-slate-700">{{ $customer->compras }}</td>
                        <td class="py-3 px-5 bg-white border-y border-slate-100 text-sm text-slate-500">
                            {{ $customer->ultima_compra ? \Illuminate\Support\Carbon::parse($customer->ultima_compra)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="py-3 px-5 bg-white rounded-r-xl border-r border-y border-slate-100 text-sm text-right font-extrabold text-slate-800">$ {{ number_format($customer->total_acumulado ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-slate-400 font-semibold">No hay clientes con compras.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($customers->hasPages())<div class="mt-6">{{ $customers->links() }}</div>@endif
</div>
@endsection
