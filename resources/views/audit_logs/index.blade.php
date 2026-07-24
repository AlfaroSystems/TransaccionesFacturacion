@extends('layouts.app')

@section('title', 'Bitácora de Auditoría')

@section('content')
<div class="animate-fade-in duration-300">
    <!-- Encabezado de Página -->
    <header class="mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 tracking-tight">Bitácora de Auditoría</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Revisa el historial detallado de cambios y actividades del sistema.</p>
        </div>
    </header>

    <!-- Barra de Búsqueda y Filtros -->
    <section class="bg-white p-6 rounded-2xl border border-slate-100 card-shadow mb-8">
        <form action="{{ route('audit-logs.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
            <!-- Filtro por Usuario -->
            <div>
                <label for="user_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Usuario</label>
                <select name="user_id" id="user_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700 font-semibold">
                    <option value="">Todos los Usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro por Controlador -->
            <div>
                <label for="controller" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Controlador</label>
                <input type="text" name="controller" id="controller" value="{{ request('controller') }}" placeholder="Ej. UserController" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700 font-semibold">
            </div>

            <!-- Filtro por Acción -->
            <div>
                <label for="action" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Acción</label>
                <input type="text" name="action" id="action" value="{{ request('action') }}" placeholder="Ej. store" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700 font-semibold">
            </div>

            <!-- Fecha Desde -->
            <div>
                <label for="date_from" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Desde</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700 font-semibold">
            </div>

            <!-- Fecha Hasta & Botones -->
            <div class="flex gap-2 w-full">
                <div class="flex-1">
                    <label for="date_to" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Hasta</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-navy-sidebar focus:bg-white transition-all text-slate-700 font-semibold">
                </div>
                <div class="flex flex-col justify-end">
                    <div class="flex gap-2">
                        <button type="submit" class="px-5 py-2.5 bg-navy-sidebar text-white rounded-xl text-sm font-bold hover:bg-navy-active transition-all shadow-sm">
                            Filtrar
                        </button>
                        @if(request()->anyFilled(['user_id', 'controller', 'action', 'date_from', 'date_to']))
                            <a href="{{ route('audit-logs.index') }}" class="px-5 py-2.5 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-sm font-bold transition-all text-center">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </section>

    <!-- Listado de Logs -->
    <section class="bg-white rounded-2xl border border-slate-100 card-shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Fecha y Hora</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Controlador</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">Acción</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider">ID Reg.</th>
                        <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-wider text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/30 transition-colors group">
                            <!-- Fecha y Hora -->
                            <td class="px-6 py-4 text-slate-500 font-semibold">
                                {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : 'N/D' }}
                            </td>

                            <!-- Usuario -->
                            <td class="px-6 py-4">
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
                            <td class="px-6 py-4 text-slate-600 font-bold">
                                {{ $log->controller }}
                            </td>

                            <!-- Acción -->
                            <td class="px-6 py-4">
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
                            <td class="px-6 py-4 text-slate-500 font-bold">
                                #{{ $log->id_record ?? 'N/D' }}
                            </td>

                            <!-- Botón Ver Detalles -->
                            <td class="px-6 py-4 text-right">
                                <button type="button" onclick="toggleDetails({{ $log->id }})" class="px-3.5 py-1.5 bg-slate-50 text-slate-600 border border-slate-200 rounded-xl text-xs font-bold hover:bg-navy-sidebar hover:text-white hover:border-navy-sidebar transition-all flex items-center gap-1.5 ml-auto">
                                    <span>Inspeccionar</span>
                                    <svg id="icon-{{ $log->id }}" class="w-3 h-3 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <!-- Fila de Detalles Oculta / Expandible -->
                        <tr id="details-{{ $log->id }}" class="hidden bg-slate-50/30">
                            <td colspan="6" class="px-6 py-5 border-t border-b border-slate-100">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in duration-200">
                                    <!-- Datos Originales -->
                                    <div class="bg-white p-4 rounded-xl border border-slate-150 shadow-sm">
                                        <h4 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                            Estado Original (Antes)
                                        </h4>
                                        @if($log->original_data && count($log->original_data) > 0)
                                            <pre class="text-xs text-slate-700 bg-slate-50/70 p-3 rounded-lg font-mono overflow-x-auto max-h-56 leading-relaxed select-all">@json($log->original_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                        @else
                                            <div class="text-xs text-slate-400 font-semibold italic bg-slate-50 p-3 rounded-lg border border-dashed border-slate-200 text-center">
                                                Sin datos anteriores o registro nuevo.
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Datos Modificados -->
                                    <div class="bg-white p-4 rounded-xl border border-slate-150 shadow-sm">
                                        <h4 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-navy-sidebar animate-pulse"></span>
                                            Nuevos Datos Guardados (Después)
                                        </h4>
                                        @if($log->modified_data && count($log->modified_data) > 0)
                                            <pre class="text-xs text-slate-700 bg-slate-50/70 p-3 rounded-lg font-mono overflow-x-auto max-h-56 leading-relaxed select-all">@json($log->modified_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                        @else
                                            <div class="text-xs text-slate-400 font-semibold italic bg-slate-50 p-3 rounded-lg border border-dashed border-slate-200 text-center">
                                                Sin datos nuevos (por ejemplo, eliminación).
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
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
        </div>

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
