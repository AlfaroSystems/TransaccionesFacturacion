@extends('layouts.app')

@section('title', 'Panel de Control')

@section('content')
<!-- Encabezado de Página -->
<header class="mb-6">
    <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 tracking-tight">Panel de Control</h1>
    
    <!-- Botón Vista General (Estilo cápsula azul de la imagen) -->
    <button class="flex items-center gap-2 mt-4 px-4 py-2 bg-navy-sidebar text-white rounded-full font-bold text-sm hover:bg-navy-active shadow-md transition-all">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
        </svg>
        <span>Vista General</span>
    </button>
</header>

<!-- Sección de Tarjetas (Fichas 3 columnas de la imagen) -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Card 1: Usuarios -->
    <div class="bg-white p-6 rounded-2xl border border-slate-100 card-shadow hover:scale-[1.01] transition-transform">
        <!-- Icono azul -->
        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 mb-4">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.07-.47.1-1 .1-1.5 0-.85-.1-1.72-.28-2.54C14.07 12.35 15.42 12 17 12c1.66 0 3.32.74 3.73 2.23C20.9 14.88 21 15.42 21 16c0 .58-.1 1.12-.27 1.63a.5.5 0 01-.46.37H12.93zM10.47 18a.5.5 0 01-.47-.37C9.9 17.12 9.9 16.58 9.9 16c0-1.63-.44-3.12-1.18-4.23C10.02 11.26 11.42 11 13 11c1.58 0 2.98.26 3.98.77C17.72 12.88 18.16 14.37 18.16 16c0 .58-.04 1.12-.1 1.63a.5.5 0 01-.46.37H10.47zM3.63 18a.5.5 0 01-.46-.37C3.1 17.12 3 16.58 3 16c0-1.66 1.66-2.23 3.73-2.23 2.07 0 3.73.57 3.73 2.23 0 .58-.1 1.12-.27 1.63a.5.5 0 01-.46.37H3.63z" />
            </svg>
        </div>
        <h3 class="text-4xl font-extrabold text-navy-800 leading-none">{{ \App\Models\User::count() ?? 0 }}</h3>
        <p class="text-slate-400 text-sm font-semibold mt-1">Usuarios en Sistema</p>
        <div class="border-t border-slate-100 mt-5 pt-3">
            <a href="{{ route('users.index') }}" class="text-blue-500 hover:text-blue-700 text-sm font-bold flex items-center gap-1">
                <span>Gestionar</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
    </div>

    <!-- Card 2: Alertas de Vencimiento -->
    <div class="bg-white p-6 rounded-2xl border border-slate-100 card-shadow hover:scale-[1.01] transition-transform">
        <!-- Icono verde -->
        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500 mb-4">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 2h10v7H5V5zm4 9a1 1 0 11-2 0 1 1 0 012 0zm3 1a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
        </div>
        <!-- Simulación de alertas para propósitos de demostración -->
        <h3 class="text-4xl font-extrabold text-navy-800 leading-none">6</h3>
        <p class="text-slate-400 text-sm font-semibold mt-1">Alertas de Vencimiento</p>
        <div class="border-t border-slate-100 mt-5 pt-3">
            <a href="#" class="text-emerald-500 hover:text-emerald-700 text-sm font-bold flex items-center gap-1">
                <span>Ver Inventario</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
    </div>

    <!-- Card 3: Roles Definidos -->
    <div class="bg-white p-6 rounded-2xl border border-slate-100 card-shadow hover:scale-[1.01] transition-transform">
        <!-- Icono gris -->
        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 mb-4">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2V6a2 2 0 012-2zm2 4a1 1 0 000 2h8a1 1 0 100-2H6zm0 4a1 1 0 100 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
            </svg>
        </div>
        <h3 class="text-4xl font-extrabold text-navy-800 leading-none">1</h3>
        <p class="text-slate-400 text-sm font-semibold mt-1">Roles Definidos</p>
        <div class="border-t border-slate-100 mt-5 pt-3">
            <a href="#" class="text-slate-500 hover:text-slate-700 text-sm font-bold flex items-center gap-1">
                <span>Configurar</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
    </div>
</section>

<!-- Fila de Rendimiento Mensual (Ancho Completo) -->
<section class="w-full">
    <!-- Gráfico: Rendimiento Mensual -->
    <div class="bg-white p-6 rounded-2xl border border-slate-100 card-shadow flex flex-col justify-between">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-extrabold text-navy-800">Rendimiento Mensual</h2>
            <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg font-bold">Ventas / Tiempo</span>
        </div>
        
        <!-- Área de Gráfico SVG Dinámico -->
        <div class="w-full h-56 relative flex items-end">
            <!-- Eje Y Lineas de Guía -->
            <div class="absolute inset-x-0 top-0 border-b border-slate-100 h-0"></div>
            <div class="absolute inset-x-0 top-1/4 border-b border-slate-100 h-0"></div>
            <div class="absolute inset-x-0 top-2/4 border-b border-slate-100 h-0"></div>
            <div class="absolute inset-x-0 top-3/4 border-b border-slate-100 h-0"></div>
            
            <!-- SVG Curva de Gráfico -->
            <svg class="w-full h-full absolute" viewBox="0 0 600 200" preserveAspectRatio="none">
                <!-- Sombra del área del gráfico -->
                <path d="M 0,90 Q 150,100 300,140 T 600,195 L 600,200 L 0,200 Z" fill="url(#chart-gradient)" opacity="0.15" />
                <!-- Línea del gráfico -->
                <path d="M 0,90 Q 150,100 300,140 T 600,195" fill="none" stroke="#183162" stroke-width="4" stroke-linecap="round" />
                
                <!-- Puntos de Datos (Círculos) -->
                <circle cx="20" cy="91" r="5" fill="#183162" />
                <circle cx="300" cy="140" r="5" fill="#183162" />
                <circle cx="580" cy="193" r="5" fill="#183162" />
                
                <defs>
                    <linearGradient id="chart-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#183162" />
                        <stop offset="100%" stop-color="#ffffff" />
                    </linearGradient>
                </defs>
            </svg>

            <!-- Valores a la Izquierda -->
            <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-xs text-slate-400 font-bold">
                <span>250</span>
                <span>200</span>
                <span>150</span>
                <span>100</span>
            </div>
        </div>
    </div>
</section>
@endsection
