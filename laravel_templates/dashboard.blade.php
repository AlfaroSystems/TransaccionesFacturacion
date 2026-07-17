@extends('layouts.app')

@section('title', 'Dashboard Principal')

@section('content')
<!-- Encabezado de Página -->
<header class="flex justify-between items-center mb-8">
    <div>
        <p class="text-blue-400 text-sm font-semibold tracking-wider uppercase">Fase 1: Administración</p>
        <h1 class="text-3xl md:text-4xl font-bold text-white tracking-tight mt-1">Panel de Control</h1>
    </div>
    <div class="flex items-center gap-3">
        <div class="text-right hidden sm:block">
            <p class="text-sm font-medium text-slate-300">{{ auth()->user()->name ?? 'Administrador Principal' }}</p>
            <span class="text-xs text-emerald-400 font-semibold bg-emerald-500/10 px-2 py-0.5 rounded-full">En Línea</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-md">
            {{ initials(auth()->user()->name ?? 'Admin') }}
        </div>
    </div>
</header>

<!-- Sección de Estadísticas Rápidas -->
<section class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <!-- Card 1: Usuarios -->
    <div class="glass-panel p-6 rounded-2xl relative overflow-hidden group hover:border-blue-500/30 transition-all duration-300">
        <div class="absolute -right-4 -bottom-4 text-blue-500/5 group-hover:scale-110 transition-transform duration-500">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </div>
        <p class="text-sm text-slate-400 font-medium">Usuarios Registrados</p>
        <h3 class="text-3xl font-bold mt-2 text-white">{{ \App\Models\User::count() ?? 0 }}</h3>
        <span class="text-xs text-blue-400 mt-2 inline-block font-medium">Control de acceso activo</span>
    </div>

    <!-- Card 2: Empleados -->
    <div class="glass-panel p-6 rounded-2xl relative overflow-hidden group hover:border-blue-500/30 transition-all duration-300">
        <div class="absolute -right-4 -bottom-4 text-purple-500/5 group-hover:scale-110 transition-transform duration-500">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        </div>
        <p class="text-sm text-slate-400 font-medium">Perfiles de Empleados</p>
        <h3 class="text-3xl font-bold mt-2 text-white">{{ \App\Models\Employee::count() ?? 0 }}</h3>
        <span class="text-xs text-purple-400 mt-2 inline-block font-medium">Fichas de Recursos Humanos</span>
    </div>

    <!-- Card 3: Roles -->
    <div class="glass-panel p-6 rounded-2xl relative overflow-hidden group hover:border-blue-500/30 transition-all duration-300">
        <div class="absolute -right-4 -bottom-4 text-emerald-500/5 group-hover:scale-110 transition-transform duration-500">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
        </div>
        <p class="text-sm text-slate-400 font-medium">Roles de Seguridad</p>
        <h3 class="text-3xl font-bold mt-2 text-white">{{ \Spatie\Permission\Models\Role::count() ?? 0 }}</h3>
        <span class="text-xs text-emerald-400 mt-2 inline-block font-medium">Permisos y accesos activos</span>
    </div>
</section>

<!-- Contenido Principal: Bitácora -->
<div class="glass-panel p-6 rounded-2xl">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-white">Actividad Reciente (Bitácora - RF-004)</h3>
        <span class="text-xs bg-slate-800 text-slate-400 px-3 py-1 rounded-full">Monitoreo en Vivo</span>
    </div>
    
    <div class="space-y-4">
        {{-- Aquí se iteraría sobre la bitácora con Laravel --}}
        {{-- 
        @forelse($activities as $activity)
            <div class="flex items-center justify-between p-3 rounded-lg bg-slate-800/40 border border-slate-700/30">
                <div class="flex items-center gap-3">
                    <span class="w-2.5 h-2.5 rounded-full {{ $activity->color }}"></span>
                    <p class="text-sm text-slate-200">{{ $activity->description }}</p>
                </div>
                <span class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500 text-center py-4">No hay actividad registrada en la bitácora.</p>
        @endforelse 
        --}}

        <!-- Marcadores de posición de ejemplo -->
        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-800/40 border border-slate-700/30">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <p class="text-sm text-slate-200">El usuario <span class="text-blue-400">admin@empresa.com</span> modificó el rol del empleado <span class="text-purple-400">Juan Pérez</span>.</p>
            </div>
            <span class="text-xs text-slate-500">Hace unos momentos</span>
        </div>
        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-800/40 border border-slate-700/30">
            <div class="flex items-center gap-3">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                <p class="text-sm text-slate-200">Se creó el nuevo perfil de empleado para <span class="text-purple-400">María Gómez</span>.</p>
            </div>
            <span class="text-xs text-slate-500">Hace 2 horas</span>
        </div>
    </div>
</div>
@endsection
