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
    
    
    <!-- Tailwind CSS (Vite o CDN local) -->
    @if(app()->environment('local'))
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: { sans: ['Nunito', 'sans-serif'] },
                        colors: {
                            navy: {
                                sidebar: '#005e66', // Deep dark teal
                                active: '#3cb0a4',  // Primary teal
                                800: '#00474f'      // Darkest teal
                            }
                        }
                    }
                }
            }
        </script>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            background-color: #f4f6f9;
            color: #1e293b;
        }
        .sidebar-shadow {
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
        }
        .card-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="min-h-screen md:h-screen md:overflow-hidden flex flex-col md:flex-row font-sans overflow-x-hidden">

    <!-- BARRA LATERAL -->
    <aside class="w-full md:w-64 bg-navy-sidebar flex flex-col justify-between p-5 min-h-[450px] md:h-screen md:min-h-0 flex-shrink-0 sidebar-shadow text-white">
        <div>
            <!-- Header con Logo y Usuario Autenticado -->
            <div class="flex flex-col items-center text-center mt-4 mb-8 border-b border-white/10 pb-6">
                <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center font-bold text-lg text-white mb-2 uppercase border border-white/20">
                    {{ auth()->check() ? substr(auth()->user()->name, 0, 2) : 'US' }}
                </div>
                <span class="text-sm font-bold text-white block">{{ auth()->check() ? auth()->user()->name : 'Usuario de Prueba' }}</span>
                <span class="text-xs text-slate-300 font-semibold mt-1">{{ auth()->check() ? auth()->user()->email : 'correo@ejemplo.com' }}</span>
            </div>
            <!-- Menú de Opciones -->
            <nav class="space-y-1.5 px-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('dashboard') ? 'bg-navy-active text-white font-bold' : 'text-slate-300 hover:bg-white/5 hover:text-white font-semibold' }} transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Dashboard</span>
                </a>


                <!-- Inventario -->
                @php
                    $isInventario = request()->routeIs('warehouses.*') || request()->routeIs('warehouse_categories.*') || request()->routeIs('locations.*');
                @endphp
                @canany(['warehouses.ver', 'warehouse_categories.ver', 'locations.ver'])
                <a href="{{ route('warehouses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isInventario ? 'bg-navy-active text-white font-bold' : 'text-slate-300 hover:bg-white/5 hover:text-white font-semibold' }} transition-all">
                    <svg class="w-5 h-5 {{ $isInventario ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Inventario</span>
                </a>
                @endcanany

                <!-- Empresa -->
                @php
                    $isEmpresa = request()->routeIs('branches.*') || request()->routeIs('empleados.*') || request()->routeIs('companies.*');
                @endphp
                @canany(['companies.ver', 'branches.ver', 'empleados.ver'])
                <a href="{{ route('companies.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isEmpresa ? 'bg-navy-active text-white font-bold' : 'text-slate-300 hover:bg-white/5 hover:text-white font-semibold' }} transition-all">
                    <svg class="w-5 h-5 {{ $isEmpresa ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>Empresa</span>
                </a>
                @endcanany

                <!-- Administración -->
                @php
                    $isAdministracion = request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('audit-logs.*');
                @endphp
                @canany(['usuarios.ver', 'roles.administrar', 'bitacora.ver'])
                <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isAdministracion ? 'bg-navy-active text-white font-bold' : 'text-slate-300 hover:bg-white/5 hover:text-white font-semibold' }} transition-all">
                    <svg class="w-5 h-5 {{ $isAdministracion ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Administración</span>
                </a>
                @endcanany
            </nav>
        </div>

        <!-- Botón Cerrar Sesión (Estilo borde blanco de la imagen) -->
        <div class="px-1 mb-4">
            <!-- Formulario de Logout Laravel -->
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">
                @csrf
            </form>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-white/40 hover:border-white hover:bg-white/5 transition-all text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        @php
            $isAdministracion = request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('audit-logs.*');
            $isInventario = request()->routeIs('warehouses.*') || request()->routeIs('warehouse_categories.*') || request()->routeIs('locations.*');
            $isEmpresa = request()->routeIs('branches.*') || request()->routeIs('empleados.*') || request()->routeIs('companies.*');
        @endphp

        @if($isEmpresa)
            <!-- Horizontal Submenu for Empresa -->
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <!-- Tab: Empresas -->
                @can('companies.ver')
                <a href="{{ route('companies.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('companies.*') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    <span>Empresas</span>
                </a>
                @endcan
                <!-- Tab: Sucursales -->
                @can('branches.ver')
                <a href="{{ route('branches.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('branches.*') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    <span>Sucursales</span>
                </a>
                @endcan
                <!-- Tab: Empleados -->
                @can('empleados.ver')
                <a href="{{ route('empleados.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('empleados.*') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-7 4h10" /></svg>
                    <span>Empleados</span>
                </a>
                @endcan
            </div>
        @endif

        @if($isAdministracion)
            <!-- Horizontal Submenu for Administración -->
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <!-- Tab: Usuarios -->
                @can('usuarios.ver')
                <a href="{{ route('users.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('users.*') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <span>Usuarios</span>
                </a>
                @endcan
                <!-- Tab: Roles y Permisos -->
                @can('roles.administrar')
                <a href="{{ route('roles.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('roles.*') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    <span>Roles y Permisos</span>
                </a>
                @endcan
                <!-- Tab: Bitácora -->
                @can('bitacora.ver')
                <a href="{{ route('audit-logs.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('audit-logs.*') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M12 9v6m-7 6h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <span>Bitácora</span>
                </a>
                @endcan
            </div>
        @endif

        @if($isInventario)
            <!-- Horizontal Submenu for Inventario -->
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <!-- Tab: Bodegas -->
                @can('warehouses.ver')
                <a href="{{ route('warehouses.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('warehouses.*') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7l9-4 9 4M4 10h16v8H4v-8z" /></svg>
                    <span>Bodegas</span>
                </a>
                @endcan
                <!-- Tab: Categorías -->
                @can('warehouse_categories.ver')
                <a href="{{ route('warehouse_categories.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('warehouse_categories.*') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    <span>Categorías</span>
                </a>
                @endcan
                <!-- Tab: Ubicaciones -->
                @can('locations.ver')
                <a href="{{ route('locations.index') }}" class="flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition-all {{ request()->routeIs('locations.index', 'locations.create', 'locations.edit', 'locations.show') ? 'bg-[#005e66] text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    <span>Ubicaciones</span>
                </a>
                @endcan
            </div>
        @endif

        @isset($header)
            <header class="bg-white shadow mb-6 rounded-xl p-4 border border-slate-100 card-shadow">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    {{ $header }}
                </div>
            </header>
        @endisset

        @yield('content')
        @if(isset($slot))
            {{ $slot }}
        @endif
    </main>

    <!-- MODAL DE ELIMINACIÓN GLOBAL -->
    <div id="global-delete-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl text-center relative mx-4 transform scale-95 transition-all duration-200" id="global-delete-card">
            <!-- Icono de Advertencia -->
            <div class="w-16 h-16 rounded-full border-2 border-orange-400 flex items-center justify-center mx-auto text-orange-400 mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            
            <!-- Título -->
            <h3 class="text-xl font-bold text-slate-800 mb-2" id="global-delete-title">¿Eliminar Registro?</h3>
            
            <!-- Descripción -->
            <p class="text-slate-500 text-sm mb-8" id="global-delete-description">Estás a punto de eliminar este registro de forma permanente. Esta acción no se puede deshacer.</p>
            
            <!-- Botones -->
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeGlobalDeleteModal()" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl text-sm transition-all">
                    Cancelar
                </button>
                <form id="global-delete-form" action="" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-6 py-2.5 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl text-sm transition-all shadow-md">
                        Sí, eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT DE MANEJO DE MODALES GLOBAL -->
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Si el modal contiene una tarjeta que queremos animar
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

        function confirmDelete(actionUrl, resourceName, descriptionText = null) {
            const modal = document.getElementById('global-delete-modal');
            const card = document.getElementById('global-delete-card');
            const form = document.getElementById('global-delete-form');
            const title = document.getElementById('global-delete-title');
            const desc = document.getElementById('global-delete-description');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            form.action = actionUrl;
            title.textContent = `¿Eliminar ${resourceName}?`;
            if (descriptionText) {
                desc.textContent = descriptionText;
                submitBtn.textContent = 'Sí, desactivar';
            } else {
                desc.textContent = `Estás a punto de eliminar el registro de '${resourceName}'. Esta acción no se puede deshacer.`;
                submitBtn.textContent = 'Sí, eliminar';
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
    </script>
</body>
</html>
