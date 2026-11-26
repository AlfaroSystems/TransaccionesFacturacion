@extends('layouts.app')
@section('title', 'Empleados')

@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-primary-soft text-on-primary-soft dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Empresa</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Empleados</span>
            </div>
            <h1 class="text-3xl font-extrabold text-primary dark:text-accent dark:text-teal-400 tracking-tight mt-1">Empleados</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Personal de las sucursales: motoristas de los traslados y responsables de los vehículos.</p>
        </div>
        @can('employees.crear')
            <x-ui.button :href="route('employees.create')" icon="plus">Nuevo Empleado</x-ui.button>
        @endcan
    </div>

    <!-- Filtros -->
    <div class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs p-4">
        <form method="GET" action="{{ route('employees.index') }}" class="flex flex-col md:flex-row gap-3 md:items-center">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre, DUI o cargo" class="flex-1 px-3.5 py-2.5 rounded-xl border border-line dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm outline-hidden focus:ring-2 focus:ring-[#005e66]">
            <select name="estado" class="md:w-48 px-3 py-2.5 rounded-xl border border-line dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm">
                <option value="">Todos</option>
                <option value="activos" @selected($estado === 'activos')>Activos</option>
                <option value="inactivos" @selected($estado === 'inactivos')>Inactivos</option>
            </select>
            <button type="submit" class="bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs">Filtrar</button>
            @if($search || $estado)
                <a href="{{ route('employees.index') }}" class="text-center text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Listado -->
    <div class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-line dark:border-slate-800">
                    <tr>
                        <th class="py-4 px-6">Nombre</th>
                        <th class="py-4 px-6">DUI</th>
                        <th class="py-4 px-6">Cargo</th>
                        <th class="py-4 px-6">Sucursal</th>
                        <th class="py-4 px-6">Contacto</th>
                        <th class="py-4 px-6 text-center">Vehículos</th>
                        <th class="py-4 px-6">Estado</th>
                        <th class="py-4 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors {{ $employee->is_active ? '' : 'opacity-60' }}">
                            <td class="py-4 px-6 font-bold text-slate-800 dark:text-white">{{ $employee->fullName() }}</td>
                            <td class="py-4 px-6 font-mono text-xs">{{ $employee->dui ?? '—' }}</td>
                            <td class="py-4 px-6 text-xs">{{ $employee->position ?? '—' }}</td>
                            <td class="py-4 px-6 text-xs">{{ $employee->branch?->name }}</td>
                            <td class="py-4 px-6 text-xs">
                                <span class="block">{{ $employee->phone ?? '' }}</span>
                                <span class="block text-slate-400">{{ $employee->email ?? '' }}</span>
                            </td>
                            <td class="py-4 px-6 text-center font-mono">{{ $employee->vehicles_count }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-1 rounded-lg text-xs font-extrabold {{ $employee->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' }}">{{ $employee->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                @can('employees.editar')
                                    <a href="{{ route('employees.edit', $employee) }}" class="text-xs font-bold text-primary dark:text-accent dark:text-teal-400 hover:underline">Editar</a>
                                @endcan
                                @can('employees.eliminar')
                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('¿Eliminar a {{ addslashes($employee->fullName()) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-xs font-bold text-slate-400 hover:text-rose-600">Eliminar</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-slate-400 text-sm">
                                {{ $search || $estado ? 'No se encontraron empleados con esos filtros.' : 'Aún no hay empleados registrados.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="px-6 py-4 border-t border-line dark:border-slate-800">{{ $employees->links() }}</div>
        @endif
    </div>
</div>
@endsection
