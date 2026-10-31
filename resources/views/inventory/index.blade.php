@extends('layouts.app')
@section('title', 'Existencias de Inventario')

@section('content')
@php
    $tabClass = fn (bool $active) => $active
        ? 'bg-customTeal-800 dark:bg-sky-600 text-white shadow-sm'
        : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4), '0'), '.');
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Inventario</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Existencias</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Existencias</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Lo que hay en bodega, por lote y ubicación, y el costo promedio de cada producto. Entra al confirmar la colocación de cada lote.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('inventory.index', array_filter(['search' => $search, 'id_warehouse' => $warehouseId])) }}" class="px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ $tabClass($vista === 'lotes') }}">Por lote y ubicación</a>
            <a href="{{ route('inventory.index', array_filter(['vista' => 'productos', 'search' => $search])) }}" class="px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ $tabClass($vista === 'productos') }}">Por producto</a>
        </div>
    </div>

    <!-- Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#005e66] dark:text-teal-300 flex items-center justify-center text-xl">📦</div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Productos con existencia</span>
                <span class="text-2xl font-extrabold text-slate-800 dark:text-white">{{ number_format($totales->productos ?? 0) }}</span>
            </div>
        </div>
        @if($vista === 'lotes')
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 flex items-center justify-center text-xl">🏷️</div>
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Lotes por ubicación</span>
                    <span class="text-2xl font-extrabold text-slate-800 dark:text-white">{{ number_format($totales->filas ?? 0) }}</span>
                </div>
            </div>
        @endif
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xl">💲</div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Valor del inventario</span>
                <span class="text-2xl font-extrabold text-slate-800 dark:text-white">${{ number_format((float) ($totales->valor ?? 0), 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-4">
        <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-col md:flex-row gap-3 md:items-center">
            @if($vista === 'productos')
                <input type="hidden" name="vista" value="productos">
            @endif
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ $vista === 'productos' ? 'Buscar producto o SKU...' : 'Buscar producto, SKU o lote...' }}" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm focus:ring-2 focus:ring-[#005e66] focus:border-transparent outline-hidden">
            </div>
            @if($vista === 'lotes')
                <select name="id_warehouse" class="md:w-64 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm">
                    <option value="">Todas las bodegas</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id_warehouse }}" @selected($warehouse->id_warehouse == $warehouseId)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            @endif
            <button type="submit" class="bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs">Filtrar</button>
            @if($search || $warehouseId)
                <a href="{{ route('inventory.index', $vista === 'productos' ? ['vista' => 'productos'] : []) }}" class="text-center text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Listado -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            @if($vista === 'productos')
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-4 px-6">Producto</th>
                            @if($showCompany)
                                <th class="py-4 px-6">Empresa</th>
                            @endif
                            <th class="py-4 px-6 text-right">Existencia</th>
                            <th class="py-4 px-6 text-right">Costo Promedio</th>
                            <th class="py-4 px-6 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($averages as $average)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-4 px-6">
                                    <span class="font-bold text-slate-800 dark:text-white block">{{ $average->product?->name ?? 'Producto' }}</span>
                                    <span class="text-xs font-mono text-slate-400">{{ $average->product?->sku }}</span>
                                </td>
                                @if($showCompany)
                                    <td class="py-4 px-6 text-xs">{{ $average->company?->name ?? 'N/A' }}</td>
                                @endif
                                <td class="py-4 px-6 text-right font-mono font-extrabold text-slate-800 dark:text-white">
                                    {{ $qty($average->quantity_on_hand) }}
                                    <span class="text-xs font-sans font-semibold text-slate-400">{{ $average->product?->purchaseUnit?->name }}</span>
                                </td>
                                <td class="py-4 px-6 text-right font-mono">${{ number_format((float) $average->average_unit_cost, 4) }}</td>
                                <td class="py-4 px-6 text-right font-mono font-bold text-[#005e66] dark:text-teal-400">${{ number_format((float) $average->inventory_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showCompany ? 5 : 4 }}" class="py-16 text-center text-slate-400 text-sm">
                                    {{ $search ? 'No se encontraron productos con existencia para la búsqueda.' : 'Aún no hay existencias: entran al confirmar la colocación de los lotes.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-4 px-6">Producto</th>
                            <th class="py-4 px-6">Lote</th>
                            <th class="py-4 px-6">Bodega / Ubicación</th>
                            <th class="py-4 px-6">Entrada</th>
                            <th class="py-4 px-6 text-right">Existencia</th>
                            <th class="py-4 px-6 text-right">Costo Unit.</th>
                            <th class="py-4 px-6 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($stock as $row)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-4 px-6">
                                    <span class="font-bold text-slate-800 dark:text-white block">{{ $row->product?->name ?? 'Producto' }}</span>
                                    <span class="text-xs font-mono text-slate-400">{{ $row->product?->sku }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2 py-0.5 rounded-md bg-teal-50 dark:bg-teal-950 text-[#005e66] dark:text-teal-300 text-xs font-mono font-extrabold border border-teal-200 dark:border-teal-800">{{ $row->lot?->lot_code }}</span>
                                    <a href="{{ route('inventory.lots.show', $row->id_inventory_lot) }}" class="block mt-1.5 text-[11px] font-bold text-slate-500 hover:text-[#005e66] dark:hover:text-teal-400">Ver lote →</a>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-semibold text-slate-700 dark:text-slate-200 block">{{ $row->warehouse?->name ?? 'N/A' }}</span>
                                    <span class="text-xs font-mono text-slate-400">{{ $row->location?->code ?? 'Sin ubicación' }}</span>
                                </td>
                                <td class="py-4 px-6 text-xs whitespace-nowrap">{{ $row->lot?->received_at?->format('d/m/Y') }}</td>
                                <td class="py-4 px-6 text-right font-mono font-extrabold text-slate-800 dark:text-white whitespace-nowrap">
                                    {{ $qty($row->stock) }}
                                    {{-- En la unidad en que se recibió el lote --}}
                                    <span class="text-xs font-sans font-semibold text-slate-400">{{ $row->lot?->purchaseDetail?->unit?->name }}</span>
                                </td>
                                <td class="py-4 px-6 text-right font-mono">${{ number_format((float) $row->lot?->unit_cost, 4) }}</td>
                                <td class="py-4 px-6 text-right font-mono font-bold text-[#005e66] dark:text-teal-400">${{ number_format((float) $row->stock * (float) $row->lot?->unit_cost, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center text-slate-400 text-sm">
                                    {{ ($search || $warehouseId) ? 'No se encontraron existencias para los filtros.' : 'Aún no hay existencias: entran al confirmar la colocación de los lotes.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @php $paginator = $vista === 'productos' ? $averages : $stock; @endphp
        @if($paginator->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $paginator->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
