@extends('layouts.app')
@section('title', 'Vehículos')

@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-primary-soft text-on-primary-soft dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Inventario</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Vehículos</span>
            </div>
            <h1 class="text-3xl font-extrabold text-primary dark:text-accent dark:text-teal-400 tracking-tight mt-1">Vehículos</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Vehículos de la empresa y sus espacios de carga. Cada espacio pertenece a una bodega y es una ubicación de inventario.</p>
        </div>
        @can('vehicles.crear')
            <x-ui.button :href="route('vehicles.create')" icon="plus">Nuevo Vehículo</x-ui.button>
        @endcan
    </div>

    <!-- Filtros -->
    <div class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs p-4">
        <form method="GET" action="{{ route('vehicles.index') }}" class="flex flex-col md:flex-row gap-3 md:items-center">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por placa, marca o modelo" class="flex-1 px-3.5 py-2.5 rounded-xl border border-line dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm outline-hidden focus:ring-2 focus:ring-[#005e66]">
            <button type="submit" class="bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs">Buscar</button>
            @if($search)
                <a href="{{ route('vehicles.index') }}" class="text-center text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Listado -->
    <div class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-line dark:border-slate-800">
                    <tr>
                        <th class="py-4 px-6">Placa</th>
                        <th class="py-4 px-6">Vehículo</th>
                        <th class="py-4 px-6">Responsable</th>
                        <th class="py-4 px-6">Espacios de carga</th>
                        <th class="py-4 px-6">Estado</th>
                        <th class="py-4 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($vehicles as $vehicle)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors {{ $vehicle->is_active ? '' : 'opacity-60' }}">
                            <td class="py-4 px-6 font-mono font-bold text-primary dark:text-accent dark:text-teal-400">{{ $vehicle->license_plate }}</td>
                            <td class="py-4 px-6">
                                <span class="font-semibold text-slate-800 dark:text-white block">{{ trim($vehicle->brand.' '.$vehicle->model) }}</span>
                                <span class="text-xs text-slate-400">{{ $vehicle->year }}</span>
                            </td>
                            <td class="py-4 px-6 text-xs">{{ $vehicle->responsibleEmployee?->fullName() ?? 'Sin asignar' }}</td>
                            <td class="py-4 px-6 text-xs">
                                @forelse($vehicle->locations as $espacio)
                                    <span class="inline-block mr-1 mb-1 px-2 py-0.5 rounded-md font-mono {{ $espacio->is_active ? 'bg-slate-100 dark:bg-slate-800' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/60' }}" title="{{ $espacio->warehouseAllBranches?->name }}">{{ $espacio->code }}</span>
                                @empty
                                    <span class="text-slate-400">Sin espacios</span>
                                @endforelse
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-1 rounded-lg text-xs font-extrabold {{ $vehicle->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' }}">{{ $vehicle->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                @can('vehicles.editar')
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="text-xs font-bold text-primary dark:text-accent dark:text-teal-400 hover:underline">Editar</a>
                                @endcan
                                @can('vehicles.eliminar')
                                    <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" class="inline" onsubmit="return confirm('¿Eliminar el vehículo {{ addslashes($vehicle->license_plate) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-xs font-bold text-slate-400 hover:text-rose-600">Eliminar</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-slate-400 text-sm">
                                {{ $search ? 'No se encontraron vehículos con esa búsqueda.' : 'Aún no hay vehículos registrados.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vehicles->hasPages())
            <div class="px-6 py-4 border-t border-line dark:border-slate-800">{{ $vehicles->links() }}</div>
        @endif
    </div>
</div>
@endsection
