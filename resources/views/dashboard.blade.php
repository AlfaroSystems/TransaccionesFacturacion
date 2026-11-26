@extends('layouts.app')
@section('title', 'Panel de Control')

@section('content')
{{-- Chart.js para el gráfico de órdenes de compra; solo se carga con permiso sobre el módulo --}}
@can('purchase_orders.ver')
@vite('resources/js/dashboard.js')
@endcan

<div class="animate-fade-in">
    <x-ui.page-header title="Panel de Control" subtitle="Resumen general de la operación.">
        <x-slot:actions>
            <x-ui.button :href="route('dashboard')" icon="dashboard" variant="primary" size="sm">Vista General</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- TARJETAS DE MÉTRICAS -->
    @canany(['usuarios.ver', 'products.ver', 'admin'])
    <section class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        @can('usuarios.ver')
            <x-ui.metric-card icon="users" tone="info" label="Usuarios en Sistema" :value="$userCount"
                :href="route('users.index')" linkText="Gestionar" />
        @endcan
        @can('products.ver')
            <x-ui.metric-card icon="tag" tone="success" label="Productos Registrados" :value="$productCount"
                :href="route('products.index')" linkText="Ver productos" />
        @endcan
        @can('admin')
            <x-ui.metric-card icon="cog" tone="neutral" label="Roles Definidos" :value="$roleCount"
                :href="route('roles.index')" linkText="Configurar" />
        @endcan
    </section>
    @endcanany

    <!-- RENDIMIENTO Y ACCESOS RÁPIDOS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        @can('purchase_orders.ver')
        <div class="lg:col-span-2">
            <x-ui.card class="h-full flex flex-col justify-between">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-extrabold text-body">Rendimiento Mensual</h2>
                    <x-ui.badge tone="neutral">Compras / Tiempo</x-ui.badge>
                </div>
                <div class="relative w-full h-64 md:h-72">
                    <canvas id="performanceChart"></canvas>
                </div>
            </x-ui.card>
        </div>
        @endcan

        @canany(['purchase_orders.ver', 'usuarios.ver', 'categories.ver'])
        <div class="flex flex-col">
            <h2 class="text-lg font-extrabold text-body mb-4">Accesos Rápidos</h2>
            <div class="space-y-3">
                @can('purchase_orders.ver')
                    <a href="{{ route('purchase_orders.index') }}" class="ui-surface flex items-center justify-between p-4 hover:-translate-y-0.5 transition-all group">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-danger-soft text-danger flex items-center justify-center shrink-0"><x-icon name="cart" class="w-5 h-5" /></div>
                            <div>
                                <h4 class="text-sm font-extrabold text-body">Gestionar Compras</h4>
                                <p class="text-xs text-muted font-semibold mt-0.5">Ver transacciones recientes</p>
                            </div>
                        </div>
                        <x-icon name="chevron-right" class="w-5 h-5 text-subtle group-hover:translate-x-1 transition-transform" />
                    </a>
                @endcan
                @can('usuarios.ver')
                    <a href="{{ route('users.index') }}" class="ui-surface flex items-center justify-between p-4 hover:-translate-y-0.5 transition-all group">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-info-soft text-info flex items-center justify-center shrink-0"><x-icon name="check-circle" class="w-5 h-5" /></div>
                            <div>
                                <h4 class="text-sm font-extrabold text-body">Seguridad y Permisos</h4>
                                <p class="text-xs text-muted font-semibold mt-0.5">Configuración de acceso</p>
                            </div>
                        </div>
                        <x-icon name="chevron-right" class="w-5 h-5 text-subtle group-hover:translate-x-1 transition-transform" />
                    </a>
                @endcan
                @can('categories.ver')
                    <a href="{{ route('categories.index') }}" class="ui-surface flex items-center justify-between p-4 hover:-translate-y-0.5 transition-all group">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-success-soft text-success flex items-center justify-center shrink-0"><x-icon name="inventory" class="w-5 h-5" /></div>
                            <div>
                                <h4 class="text-sm font-extrabold text-body">Categorías</h4>
                                <p class="text-xs text-muted font-semibold mt-0.5">Gestión de familias de productos</p>
                            </div>
                        </div>
                        <x-icon name="chevron-right" class="w-5 h-5 text-subtle group-hover:translate-x-1 transition-transform" />
                    </a>
                @endcan
            </div>
        </div>
        @endcanany
    </div>
</div>

{{-- Script del gráfico: incluye datos de órdenes, así que solo se envía con permiso --}}
@can('purchase_orders.ver')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('performanceChart');
        if (!ctx) return;

        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';
        const textColor = isDark ? '#94a3b8' : '#64748b';
        const brand = isDark ? '#2dd4bf' : '#005e66';

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Transacciones',
                    data: @json($chartData),
                    borderColor: brand,
                    borderWidth: 3,
                    backgroundColor: isDark ? 'rgba(45, 212, 191, 0.06)' : 'rgba(0, 94, 102, 0.05)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: brand,
                    pointBorderColor: isDark ? '#0f172a' : '#ffffff',
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
