@extends('layouts.app')
@section('title', 'Panel de Control')

@section('content')
{{-- Chart.js para el gráfico de órdenes de compra; solo se carga con permiso sobre el módulo --}}
@can('purchase_orders.ver')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endcan

<!-- Encabezado de Página -->
<header class="mb-6">
    <h1 class="text-2xl md:text-3xl font-extrabold text-navy-800 dark:text-slate-100 tracking-tight transition-colors duration-300">
        Panel de Control
    </h1>
    
    <!-- Botón Vista General -->
    <button class="flex items-center gap-2 mt-4 px-4 py-2 bg-navy-sidebar dark:bg-sky-600 text-white rounded-full font-bold text-sm hover:bg-navy-active dark:hover:bg-sky-500 shadow-md transition-all">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
        </svg>
        <span>Vista General</span>
    </button>
</header>

<!-- SECCIÓN 1: TARJETAS SUPERIORES (3 COLUMNAS) — cada tarjeta solo con permiso sobre su módulo -->
@canany(['usuarios.ver', 'products.ver', 'admin'])
<section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    @can('usuarios.ver')
    <!-- Card 1: Usuarios en Sistema -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/80 card-shadow hover:scale-[1.01] transition-all duration-300">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 dark:text-blue-400 mb-4">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.07-.47.1-1 .1-1.5 0-.85-.1-1.72-.28-2.54C14.07 12.35 15.42 12 17 12c1.66 0 3.32.74 3.73 2.23C20.9 14.88 21 15.42 21 16c0 .58-.1 1.12-.27 1.63a.5.5 0 01-.46.37H12.93zM10.47 18a.5.5 0 01-.47-.37C9.9 17.12 9.9 16.58 9.9 16c0-1.63-.44-3.12-1.18-4.23C10.02 11.26 11.42 11 13 11c1.58 0 2.98.26 3.98.77C17.72 12.88 18.16 14.37 18.16 16c0 .58-.04 1.12-.1 1.63a.5.5 0 01-.46.37H10.47zM3.63 18a.5.5 0 01-.46-.37C3.1 17.12 3 16.58 3 16c0-1.66 1.66-2.23 3.73-2.23 2.07 0 3.73.57 3.73 2.23 0 .58-.1 1.12-.27 1.63a.5.5 0 01-.46.37H3.63z" />
            </svg>
        </div>
        <h3 class="text-4xl font-extrabold text-navy-800 dark:text-slate-100 leading-none">{{ $userCount }}</h3>
        <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-2">Usuarios en Sistema</p>
        <div class="border-t border-slate-100 dark:border-slate-700/80 mt-5 pt-3">
            <a href="{{ route('users.index') }}" class="text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-bold flex items-center gap-1">
                <span>Gestionar</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
    </div>
    @endcan

    @can('products.ver')
    <!-- Card 2: Productos Registrados -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/80 card-shadow hover:scale-[1.01] transition-all duration-300">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-500 dark:text-emerald-400 mb-4">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 2h10v7H5V5zm4 9a1 1 0 11-2 0 1 1 0 012 0zm3 1a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
        </div>
        <h3 class="text-4xl font-extrabold text-navy-800 dark:text-slate-100 leading-none">{{ $productCount }}</h3>
        <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-2">Productos Registrados</p>
        <div class="border-t border-slate-100 dark:border-slate-700/80 mt-5 pt-3">
            <a href="{{ route('products.index') }}" class="text-emerald-500 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 text-sm font-bold flex items-center gap-1">
                <span>Ver Productos</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
    </div>
    @endcan

    @can('admin')
    <!-- Card 3: Roles Definidos -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/80 card-shadow hover:scale-[1.01] transition-all duration-300">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center text-slate-500 dark:text-slate-300 mb-4">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2V6a2 2 0 012-2zm2 4a1 1 0 000 2h8a1 1 0 100-2H6zm0 4a1 1 0 100 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
            </svg>
        </div>
        <h3 class="text-4xl font-extrabold text-navy-800 dark:text-slate-100 leading-none">{{ $roleCount }}</h3>
        <p class="text-slate-400 dark:text-slate-400 text-sm font-semibold mt-2">Roles Definidos</p>
        <div class="border-t border-slate-100 dark:border-slate-700/80 mt-5 pt-3">
            <a href="{{ route('roles.index') }}" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-sm font-bold flex items-center gap-1">
                <span>Configurar</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
    </div>
    @endcan
</section>
@endcanany

<!-- SECCIÓN 2: RENDIMIENTO MENSUAL Y ACCESOS RÁPIDOS -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @can('purchase_orders.ver')
    <!-- COLUMNA IZQUIERDA (2/3): GRÁFICO DE RENDIMIENTO MENSUAL (órdenes de compra) -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/80 card-shadow h-full flex flex-col justify-between">
            <!-- Encabezado de la Tarjeta del Gráfico -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-extrabold text-navy-800 dark:text-slate-100">Rendimiento Mensual</h2>
                <span class="px-3 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-bold bg-slate-50 dark:bg-slate-900/50">
                    Compras / Tiempo
                </span>
            </div>

            <!-- Contenedor del Gráfico -->
            <div class="relative w-full h-64 md:h-72">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>
    @endcan

    @canany(['purchase_orders.ver', 'usuarios.ver', 'categories.ver'])
    <!-- COLUMNA DERECHA (1/3): ACCESOS RÁPIDOS (FICHAS HORIZONTALES) — solo los módulos permitidos -->
    <div class="flex flex-col justify-start">
        <h2 class="text-xl font-extrabold text-navy-800 dark:text-slate-100 mb-4">Accesos Rápidos</h2>

        <div class="space-y-4">
            @can('purchase_orders.ver')
            <!-- Tarjeta 1: Gestionar Compras / Ventas (Rosado / Rojo Suave) -->
            <a href="{{ route('purchase_orders.index') }}" class="flex items-center justify-between p-4 bg-rose-50/70 dark:bg-rose-950/20 hover:bg-rose-100/70 dark:hover:bg-rose-950/40 border border-rose-100/80 dark:border-rose-900/30 rounded-2xl transition-all group">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-500 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                            Gestionar Compras
                        </h4>
                        <p class="text-xs text-slate-400 dark:text-slate-400 font-semibold mt-0.5">
                            Ver transacciones recientes
                        </p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-slate-400 dark:text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            @endcan

            @can('usuarios.ver')
            <!-- Tarjeta 2: Seguridad y Permisos (Azul Suave) -->
            <a href="{{ route('users.index') }}" class="flex items-center justify-between p-4 bg-sky-50/70 dark:bg-sky-950/20 hover:bg-sky-100/70 dark:hover:bg-sky-950/40 border border-sky-100/80 dark:border-sky-900/30 rounded-2xl transition-all group">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-sky-100 dark:bg-sky-900/50 text-sky-500 dark:text-sky-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                            Seguridad y Permisos
                        </h4>
                        <p class="text-xs text-slate-400 dark:text-slate-400 font-semibold mt-0.5">
                            Configuración de acceso
                        </p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-slate-400 dark:text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            @endcan

            @can('categories.ver')
            <!-- Tarjeta 3: Categorías (Verde Suave) -->
            <a href="{{ route('categories.index') }}" class="flex items-center justify-between p-4 bg-emerald-50/70 dark:bg-emerald-950/20 hover:bg-emerald-100/70 dark:hover:bg-emerald-950/40 border border-emerald-100/80 dark:border-emerald-900/30 rounded-2xl transition-all group">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-500 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800 dark:text-slate-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                            Categorías
                        </h4>
                        <p class="text-xs text-slate-400 dark:text-slate-400 font-semibold mt-0.5">
                            Gestión de familias de productos
                        </p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-slate-400 dark:text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            @endcan
        </div>
    </div>
    @endcanany
</div>

{{-- Script del gráfico: incluye los datos de órdenes, así que solo se envía con permiso --}}
@can('purchase_orders.ver')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('performanceChart');
        if (!ctx) return;

        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [{
                    label: 'Transacciones',
                    data: {!! json_encode($chartData) !!},
                    borderColor: '#1e3a8a', // Azul marino idéntico al diseño de referencia
                    borderWidth: 3,
                    backgroundColor: 'rgba(30, 58, 138, 0.04)',
                    fill: true,
                    tension: 0.35, // Curva suave idéntica
                    pointBackgroundColor: '#1e3a8a',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 10,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { family: 'Nunito', size: 11, weight: '600' } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        // Conteos de órdenes: solo enteros, con escala automática
                        suggestedMax: 5,
                        ticks: { color: textColor, font: { family: 'Nunito', size: 11, weight: '600' }, precision: 0 }
                    }
                }
            }
        });
    });
</script>
@endcan
@endsection