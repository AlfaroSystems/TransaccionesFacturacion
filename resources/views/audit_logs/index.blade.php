@extends('layouts.app')

@section('title', 'Bitácora de Auditoría')

@section('content')
<div class="animate-fade-in duration-300">
    <!-- Encabezado de Página -->
    <header class="mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 dark:text-slate-100 tracking-tight">Bitácora de Auditoría</h1>
            <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-1">Revisa el historial detallado de cambios y actividades del sistema.</p>
        </div>
    </header>

    <!-- Barra de Búsqueda y Filtros -->
    <section class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 card-shadow mb-8">
        <form action="{{ route('audit-logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-4 items-end">
            <!-- Filtro por Usuario -->
            <div>
                <label for="user_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Usuario</label>
                <select name="user_id" id="user_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-200 font-semibold">
                    <option value="">Todos los Usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Fecha Desde -->
            <div>
                <label for="date_from" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Desde</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-200 font-semibold">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label for="date_to" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Hasta</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#005e66] focus:bg-white dark:focus:bg-slate-900 transition-all text-slate-700 dark:text-slate-200 font-semibold">
            </div>

            <!-- Botones -->
            <div class="flex gap-2 w-full">
                <button type="submit" class="flex-1 py-2.5 bg-[#005e66] dark:bg-sky-600 hover:bg-[#3cb0a4] text-white rounded-xl text-sm font-bold transition-all shadow-sm">
                    Filtrar
                </button>
                @if(request()->anyFilled(['user_id', 'date_from', 'date_to']))
                    <a href="{{ route('audit-logs.index') }}" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 hover:bg-slate-200 rounded-xl text-sm font-bold transition-all text-center">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </section>

    <!-- Listado de Logs -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="px-6 py-3 pl-10">Fecha y Hora</th>
                    <th class="px-6 py-3">Usuario</th>
                    <th class="px-6 py-3">Controlador</th>
                    <th class="px-6 py-3">Acción</th>
                    <th class="px-6 py-3">ID Reg.</th>
                    <th class="px-6 py-3 text-right">Detalle</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr class="group hover:scale-[1.005] hover:shadow-md transition-all duration-200">
                        <!-- Fecha y Hora -->
                        <td class="px-6 py-4 bg-white rounded-l-2xl border-l border-y border-slate-100 text-slate-500 font-semibold">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span>{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : 'N/D' }}</span>
                            </div>
                        </td>

                        <!-- Usuario -->
                        <td class="px-6 py-4 bg-white border-y border-slate-100">
                            @if($log->user)
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-slate-100 text-navy-sidebar border border-slate-200 flex items-center justify-center font-bold text-xs uppercase">
                                        {{ substr($log->user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-700 block">{{ $log->user->name }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold">{{ $log->user->email }}</span>
                                    </div>
                                </div>
                            @else
                                <span class="text-slate-400 font-semibold italic">Sistema / Consola</span>
                            @endif
                        </td>

                        <!-- Controlador -->
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-slate-600 font-bold">
                            {{ $log->controller }}
                        </td>

                        <!-- Acción -->
                        <td class="px-6 py-4 bg-white border-y border-slate-100">
                            @if(in_array($log->action, ['created', 'store', 'assign_role']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
                                    {{ $log->action }}
                                </span>
                            @elseif(in_array($log->action, ['updated', 'update', 'sync_role']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/10">
                                    {{ $log->action }}
                                </span>
                            @elseif(in_array($log->action, ['deleted', 'destroy']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                    {{ $log->action }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-50 text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                    {{ $log->action }}
                                </span>
                            @endif
                        </td>

                        <!-- Registro ID -->
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-slate-500 font-bold">
                            #{{ $log->id_record ?? 'N/D' }}
                        </td>

                        <!-- Botón Ver Detalles -->
                        <td class="px-6 py-4 bg-white rounded-r-2xl border-r border-y border-slate-100 text-right">
                            <button type="button" onclick="toggleDetails({{ $log->id }})" class="px-3 py-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 ml-auto">
                                <span>Inspeccionar</span>
                                <svg id="icon-{{ $log->id }}" class="w-3.5 h-3.5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </td>
                    </tr>

                    <!-- Fila de Detalles Oculta / Expandible -->
                    <tr id="details-{{ $log->id }}" class="hidden">
                        <td colspan="6" class="px-6 py-5 bg-white border border-slate-100 rounded-2xl shadow-sm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in duration-200">
                                <!-- Datos Originales -->
                                <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-150">
                                    <h4 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                        Estado Original (Antes)
                                    </h4>
                                    @if($log->original_data && count($log->original_data) > 0)
                                        <pre class="text-xs text-slate-700 bg-white p-3 rounded-lg font-mono overflow-x-auto max-h-56 border border-slate-100 leading-relaxed select-all">@json($log->original_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                    @else
                                        <div class="text-xs text-slate-400 font-semibold italic bg-white p-3 rounded-lg border border-dashed border-slate-200 text-center">
                                            Sin datos anteriores o registro nuevo.
                                        </div>
                                    @endif
                                </div>

                                <!-- Datos Modificados -->
                                <div class="bg-slate-50/50 p-4 rounded-xl border border-slate-150">
                                    <h4 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-[#005e66] animate-pulse"></span>
                                        Nuevos Datos Guardados (Después)
                                    </h4>
                                    @if($log->modified_data && count($log->modified_data) > 0)
                                        <pre class="text-xs text-slate-700 bg-white p-3 rounded-lg font-mono overflow-x-auto max-h-56 border border-slate-100 leading-relaxed select-all">@json($log->modified_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                    @else
                                        <div class="text-xs text-slate-400 font-semibold italic bg-white p-3 rounded-lg border border-dashed border-slate-200 text-center">
                                            Sin datos nuevos (por ejemplo, eliminación).
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 bg-white rounded-2xl border border-slate-100 shadow-sm">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6M12 9v6m-7 6h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-slate-600 font-bold">No se encontraron logs de auditoría</h3>
                                <p class="text-slate-400 text-xs mt-1">Intente ajustar los filtros de búsqueda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

        <!-- Enlaces de Paginación -->
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </section>
</div>

<script>
    function toggleDetails(id) {
        const detailsRow = document.getElementById(`details-${id}`);
        const icon = document.getElementById(`icon-${id}`);
        
        if (detailsRow.classList.contains('hidden')) {
            detailsRow.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            detailsRow.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }
</script>
@endsection
