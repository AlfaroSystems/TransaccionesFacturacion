<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Sistema de Transacciones y Facturación') }}</title>
    <!-- Google Fonts: Nunito -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: { sans: ['Nunito', 'sans-serif'] },
                        colors: {
                            navy: {
                                sidebar: '#005e66',
                                active: '#3cb0a4',
                                800: '#00474f'
                            }
                        }
                    }
                }
            }
        </script>
    @endif
    <!-- Aplicar tema antes de mostrar la página para evitar parpadeo -->
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
        }
        .sidebar-shadow {
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
        }
        .card-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }
        html.dark .show-on-dark { display: flex !important; }
        html.dark .hide-on-dark { display: none !important; }
        html:not(.dark) .show-on-dark { display: none !important; }
        html:not(.dark) .hide-on-dark { display: flex !important; }
        html.dark body,
        html.dark main {
            background-color: #0b1120 !important;
            color: #f1f5f9 !important;
        }
        html.dark .bg-white,
        html.dark [class*="bg-white"] {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
        }
        html.dark .bg-slate-50,
        html.dark .bg-slate-100,
        html.dark .bg-gray-50,
        html.dark .bg-gray-100 {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }
        html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]),
        html.dark select,
        html.dark textarea {
            background-color: #0f172a !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }
        html.dark input::placeholder,
        html.dark textarea::placeholder {
            color: #64748b !important;
        }
        html.dark h1,
        html.dark h2,
        html.dark h3,
        html.dark h4,
        html.dark .text-slate-950,
        html.dark .text-slate-900,
        html.dark .text-slate-800,
        html.dark .text-slate-700,
        html.dark .text-gray-900,
        html.dark .text-gray-800,
        html.dark .text-navy-800 {
            color: #f1f5f9 !important;
        }
        html.dark .text-slate-600,
        html.dark .text-slate-500,
        html.dark .text-slate-400 {
            color: #94a3b8 !important;
        }
        html.dark .border-slate-100,
        html.dark .border-slate-200,
        html.dark .border-gray-100 {
            border-color: #334155 !important;
        }
        html.dark .card-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.40) !important;
        }
        html.dark aside.bg-navy-sidebar {
            background-color: #0f172a !important;
            border-right: 1px solid #1e293b !important;
        }
    </style>
</head>
<body class="min-h-screen md:h-screen md:overflow-hidden flex flex-col md:flex-row font-sans overflow-x-hidden bg-slate-50 text-slate-800 dark:bg-slate-900 dark:text-slate-100 transition-colors duration-300">
    <!-- BARRA LATERAL -->
    <aside class="w-full md:w-64 bg-navy-sidebar flex flex-col justify-between p-5 min-h-[450px] md:h-screen md:min-h-0 flex-shrink-0 sidebar-shadow text-white transition-colors duration-300">
        <div>
            <!-- Header con Logo y Usuario Autenticado -->
            <div class="flex flex-col items-center text-center mt-4 mb-8 border-b border-white/10 dark:border-slate-800 pb-6">
                <div class="w-12 h-12 rounded-full bg-white/10 dark:bg-slate-800 flex items-center justify-center font-bold text-lg text-white mb-2 uppercase border border-white/20 dark:border-slate-700">
                    {{ auth()->check() ? substr(auth()->user()->name, 0, 2) : 'US' }}
                </div>
<<<<<<< HEAD
                <span class="text-sm font-bold text-white block">
                    {{ auth()->check() ? auth()->user()->name : 'Usuario de Prueba' }}
                </span>
                <span class="text-xs text-slate-300 dark:text-slate-400 font-semibold mt-1">
                    {{ auth()->check() ? auth()->user()->email : 'correo@ejemplo.com' }}
                </span>
=======
                <span class="text-sm font-bold text-white block">{{ auth()->check() ? auth()->user()->name : 'Usuario de Prueba' }}</span>
                <span class="text-xs text-slate-300 dark:text-slate-400 font-semibold mt-1">{{ auth()->check() ? auth()->user()->email : 'correo@ejemplo.com' }}</span>
>>>>>>> origin/feature/solicitudes-compra
            </div>
            <!-- Menú de Opciones -->
            <nav class="space-y-1.5 px-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('dashboard') ? 'bg-navy-active text-white font-bold' : 'text-slate-200 dark:text-slate-400 hover:bg-white/10 dark:hover:bg-slate-800/60 hover:text-white font-semibold' }} transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <!-- Inventario -->
                @php
                    $isInventario = request()->routeIs('warehouses.*') || request()->routeIs('warehouse_categories.*') || request()->routeIs('locations.*');
                @endphp
                @canany(['warehouses.ver', 'warehouse_categories.ver', 'locations.ver'])
                    <a href="{{ route('warehouses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isInventario ? 'bg-navy-active text-white font-bold' : 'text-slate-200 dark:text-slate-400 hover:bg-white/10 dark:hover:bg-slate-800/60 hover:text-white font-semibold' }} transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Inventario</span>
                    </a>
                @endcanany
                <!-- Empresa -->
                @php
                    $isEmpresa = request()->routeIs('branches.*') || request()->routeIs('empleados.*') || request()->routeIs('companies.*');
                @endphp
                @canany(['companies.ver', 'branches.ver', 'empleados.ver'])
                    <a href="{{ route('companies.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isEmpresa ? 'bg-navy-active text-white font-bold' : 'text-slate-200 dark:text-slate-400 hover:bg-white/10 dark:hover:bg-slate-800/60 hover:text-white font-semibold' }} transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>Empresa</span>
                    </a>
                @endcanany
                <!-- Productos -->
                @php
                    $isProductos = request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('units.*');
                @endphp
                @canany(['products.ver', 'categories.ver', 'subcategories.ver', 'units.ver'])
                    <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isProductos ? 'bg-navy-active text-white font-bold' : 'text-slate-200 dark:text-slate-400 hover:bg-white/10 dark:hover:bg-slate-800/60 hover:text-white font-semibold' }} transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Productos</span>
                    </a>
                @endcanany
                <!-- Proveedores -->
                @php
                    $isSupplier = request()->routeIs('suppliers.*');
                @endphp
                @can('suppliers.ver')
                    <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isSupplier ? 'bg-navy-active text-white font-bold' : 'text-slate-200 dark:text-slate-400 hover:bg-white/10 dark:hover:bg-slate-800/60 hover:text-white font-semibold' }} transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Proveedores</span>
                    </a>
                @endcan
                {{-- Tipos de Gastos --}}
                @php
                    $isExpenseTypes = request()->routeIs('expense-types.*');
                @endphp
                <a href="{{ route('expense-types.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isExpenseTypes ? 'bg-navy-active text-white font-bold' : 'text-slate-200 dark:text-slate-400 hover:bg-white/10 dark:hover:bg-slate-800/60 hover:text-white font-semibold' }} transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2 M9 5a3 3 0 006 0 M9 5h6 M9 12h6 M9 16h4" />
                    </svg>
                    <span>Tipos de Gastos</span>
                </a>

                <!-- Solicitudes de Compra -->
                <a href="{{ route('purchase-requests.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('purchase-requests.*') ? 'bg-navy-active text-white font-bold' : 'text-slate-200 dark:text-slate-400 hover:bg-white/10 dark:hover:bg-slate-800/60 hover:text-white font-semibold' }} transition-all">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Solicitudes de Compra</span>
                </a>
                <!-- Administración -->
                @php
                    $isAdministracion = request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('audit-logs.*');
                @endphp
                @canany(['usuarios.ver', 'roles.administrar', 'bitacora.ver'])
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isAdministracion ? 'bg-navy-active text-white font-bold' : 'text-slate-200 dark:text-slate-400 hover:bg-white/10 dark:hover:bg-slate-800/60 hover:text-white font-semibold' }} transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c-.94 1.543.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c.94-1.543-.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Administración</span>
                    </a>
                @endcanany
            </nav>
        </div>
        <!-- Pie del Sidebar -->
        <div class="px-1 mb-4 space-y-2">
            <button type="button" onclick="toggleDarkMode()" class="flex items-center justify-center gap-2.5 w-full py-2.5 px-4 rounded-xl bg-white/10 dark:bg-slate-800/80 border border-white/20 dark:border-slate-700/80 hover:bg-white/20 dark:hover:bg-slate-700/80 transition-all text-sm font-semibold text-white shadow-sm" title="Cambiar tema">
                <div class="hide-on-dark items-center justify-center gap-2 text-white">
                    <svg class="w-4 h-4 text-amber-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                    <span class="text-white text-sm font-semibold">Modo Oscuro</span>
                </div>
                <div class="show-on-dark items-center justify-center gap-2 text-white">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="text-white text-sm font-semibold">Modo Claro</span>
                </div>
            </button>
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">
                @csrf
            </form>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-white/20 dark:border-slate-700 hover:border-white/50 hover:bg-white/5 dark:hover:bg-slate-800 transition-all text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>
    <!-- CONTENIDO PRINCIPAL -->
    <main class="relative flex-1 p-6 md:p-8 overflow-y-auto bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 transition-colors duration-300">
        @php
            $isAdministracion = request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('audit-logs.*');
            $isInventario = request()->routeIs('warehouses.*') || request()->routeIs('warehouse_categories.*') || request()->routeIs('locations.*');
            $isEmpresa = request()->routeIs('branches.*') || request()->routeIs('empleados.*') || request()->routeIs('companies.*');
            $isProductos = request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('units.*');
        @endphp
        <!-- Submenús -->
        @if($isInventario)
            <div class="mb-6 flex flex-wrap items-center gap-3">
                @can('warehouses.ver')
                    <a href="{{ route('warehouses.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('warehouses.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Bodegas</span></a>
                @endcan
                @can('warehouse_categories.ver')
                    <a href="{{ route('warehouse_categories.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('warehouse_categories.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Categorías</span></a>
                @endcan
                @can('locations.ver')
                    <a href="{{ route('locations.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('locations.index', 'locations.create', 'locations.edit', 'locations.show') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Ubicaciones</span></a>
                    <a href="{{ route('locations.map') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('locations.map') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Mapa Visual</span></a>
                @endcan
            </div>
        @endif
        @if($isEmpresa)
            <div class="mb-6 flex flex-wrap items-center gap-3">
                @can('companies.ver')
                    <a href="{{ route('companies.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('companies.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Empresas</span></a>
                @endcan
                @can('branches.ver')
                    <a href="{{ route('branches.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('branches.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Sucursales</span></a>
                @endcan
                @can('empleados.ver')
                    <a href="{{ route('empleados.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('empleados.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Empleados</span></a>
                @endcan
            </div>
        @endif
        @if($isProductos)
            <div class="mb-6 flex flex-wrap items-center gap-3">
                @can('products.ver')
                    <a href="{{ route('products.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('products.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Productos</span></a>
                @endcan
                @can('categories.ver')
                    <a href="{{ route('categories.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('categories.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Categorías</span></a>
                @endcan
                @can('subcategories.ver')
                    <a href="{{ route('subcategories.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('subcategories.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Subcategorías</span></a>
                @endcan
                @can('units.ver')
                    <a href="{{ route('units.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('units.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Unidades de Medida</span></a>
                @endcan
            </div>
        @endif
        @if($isAdministracion)
            <div class="mb-6 flex flex-wrap items-center gap-3">
                @can('usuarios.ver')
                    <a href="{{ route('users.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('users.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Usuarios</span></a>
                @endcan
                @can('roles.administrar')
                    <a href="{{ route('roles.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('roles.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Roles y Permisos</span></a>
                @endcan
                @can('bitacora.ver')
                    <a href="{{ route('audit-logs.index') }}" class="flex items-center gap-2 px-5 py-2 rounded-full text-xs font-bold transition-all {{ request()->routeIs('audit-logs.*') ? 'bg-[#005e66] dark:bg-sky-600 text-white shadow' : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' }}"><span>Bitácora de Auditoría</span></a>
                @endcan
            </div>
        @endif
        @isset($header)
            <header class="bg-white dark:bg-slate-800/80 shadow mb-6 rounded-xl p-4 border border-slate-200 dark:border-slate-700/80 card-shadow transition-colors duration-300">
                <div class="max-w-7xl mx-auto flex items-center justify-between">{{ $header }}</div>
            </header>
        @endisset
        @yield('content')
        @if(isset($slot))
            {{ $slot }}
        @endif
    </main>
    <!-- MODAL DE ELIMINACIÓN GLOBAL -->
    <div id="global-delete-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm transition-all duration-200">
<<<<<<< HEAD
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 max-w-md w-full shadow-2xl text-center relative mx-4 transform scale-95 transition-all duration-200"id="global-delete-card">
            <div class="w-14 h-14 rounded-full border-2 border-amber-400 flex items-center justify-center mx-auto text-amber-400 mb-5">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2" id="global-delete-title">
                ¿Eliminar Registro?
            </h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm mb-6" id="global-delete-description">
                Estás a punto de eliminar este registro de forma permanente. Esta acción no se puede deshacer.
            </p>
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeGlobalDeleteModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-sm transition-all">
                    Cancelar
                </button>
                <form id="global-delete-form" action="" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">
                        Sí, eliminar
                    </button>
=======
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 max-w-md w-full shadow-2xl text-center relative mx-4 transform scale-95 transition-all duration-200" id="global-delete-card">
            <div class="w-14 h-14 rounded-full border-2 border-amber-400 flex items-center justify-center mx-auto text-amber-400 mb-5">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2" id="global-delete-title">¿Eliminar Registro?</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm mb-6" id="global-delete-description">Estás a punto de eliminar este registro de forma permanente. Esta acción no se puede deshacer.</p>
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeGlobalDeleteModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-sm transition-all">Cancelar</button>
                <form id="global-delete-form" action="" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">Sí, eliminar</button>
>>>>>>> origin/feature/solicitudes-compra
                </form>
            </div>
        </div>
    </div>
    <!-- SCRIPT DE MANEJO DE MODALES Y MODO OSCURO -->
    <script>
        function toggleDarkMode() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const card = modal.querySelector('.transform');
            if (card) {
                setTimeout(() => {
                    card.classList.remove('scale-95');
                    card.classList.add('scale-100');
                }, 10);
            }
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            const card = modal.querySelector('.transform');
            if (card) {
                card.classList.remove('scale-100');
                card.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }, 150);
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
        function confirmDelete(actionUrl, resourceName, isReactivate = false, customDescription = null) {
            const modal = document.getElementById('global-delete-modal');
            const card = document.getElementById('global-delete-card');
            const form = document.getElementById('global-delete-form');
            const title = document.getElementById('global-delete-title');
            const desc = document.getElementById('global-delete-description');
            const submitBtn = form.querySelector('button[type="submit"]');
            const iconContainer = card ? card.querySelector('.w-14.h-14') : null;
            form.action = actionUrl;
            if (isReactivate) {
                title.textContent = `¿Reactivar ${resourceName}?`;
                desc.textContent = customDescription || `El estado del registro '${resourceName}' pasará a estar activo nuevamente.`;
                submitBtn.textContent = 'Sí, reactivar';
                submitBtn.className = 'px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm';
                if (iconContainer) {
                    iconContainer.className = 'w-14 h-14 rounded-full border-2 border-emerald-400 flex items-center justify-center mx-auto text-emerald-500 dark:text-emerald-400 mb-5';
                    iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                }
            } else {
                title.textContent = `¿Inactivar ${resourceName}?`;
                desc.textContent = customDescription || `El estado del registro '${resourceName}' pasará a estar inactivo.`;
                submitBtn.textContent = 'Sí, inactivar';
                submitBtn.className = 'px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-xl text-sm transition-all shadow-sm';
                if (iconContainer) {
                    iconContainer.className = 'w-14 h-14 rounded-full border-2 border-amber-400 flex items-center justify-center mx-auto text-amber-500 dark:text-amber-400 mb-5';
                    iconContainer.innerHTML = '<svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
                }
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }, 10);
        }
        function closeGlobalDeleteModal() {
            closeModal('global-delete-modal');
        }
        document.addEventListener('invalid', (function () {
            return function (e) {
                if (e.target && e.target.setCustomValidity) {
                    e.target.setCustomValidity("");
                    if (!e.target.validity.valid) {
                        if (e.target.validity.valueMissing) {
                            e.target.setCustomValidity("Por favor complete este campo.");
                        } else if (e.target.validity.typeMismatch && e.target.type === 'email') {
                            e.target.setCustomValidity("Por favor ingrese un correo electrónico válido.");
                        } else if (e.target.validity.typeMismatch && e.target.type === 'url') {
                            e.target.setCustomValidity("Por favor ingrese una URL válida.");
                        }
                    }
                }
            };
        })(), true);
        document.addEventListener('input', function (e) {
            if (e.target && e.target.setCustomValidity) {
                e.target.setCustomValidity("");
            }
        });
    </script>
    <x-sileo-toast />
</body>
</html>