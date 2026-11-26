<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') - {{ config('app.name', 'Sistema de Transacciones y Facturación') }}</title>
    <!-- Google Fonts: Nunito -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Aplica el tema guardado antes de pintar, para evitar parpadeos --}}
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="min-h-screen md:h-screen md:overflow-hidden flex flex-col md:flex-row font-sans overflow-x-hidden bg-canvas text-body transition-colors duration-300"
      x-data="{
          collapsed: localStorage.getItem('sidebarCollapsed') === '1',
          mobileOpen: false,
          toggle() { this.collapsed = !this.collapsed; localStorage.setItem('sidebarCollapsed', this.collapsed ? '1' : '0'); }
      }">

    @php
        // Cada grupo del menú enlaza al primer módulo que el usuario puede ver
        $firstAllowedRoute = fn (array $modules) => collect($modules)
            ->first(fn ($route, $permission) => auth()->user()->can($permission));

        $isInventario = request()->routeIs('inventory.*') || request()->routeIs('inventory-transfers.*') || request()->routeIs('warehouses.*') || request()->routeIs('warehouse_categories.*') || request()->routeIs('locations.*') || request()->routeIs('vehicles.*');
        $inventarioRoute = $firstAllowedRoute([
            'inventory.ver' => 'inventory.index',
            'warehouses.ver' => 'warehouses.index',
            'warehouse_categories.ver' => 'warehouse_categories.index',
            'locations.ver' => 'locations.index',
            'vehicles.ver' => 'vehicles.index',
        ]);

        $isEmpresa = request()->routeIs('branches.*') || request()->routeIs('companies.*') || request()->routeIs('employees.*');
        $empresaRoute = $firstAllowedRoute([
            'companies.ver' => 'companies.index',
            'branches.ver' => 'branches.index',
            'employees.ver' => 'employees.index',
        ]);

        $isProductos = request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('units.*');
        $productosRoute = $firstAllowedRoute([
            'products.ver' => 'products.index',
            'categories.ver' => 'categories.index',
            'subcategories.ver' => 'subcategories.index',
            'units.ver' => 'units.index',
        ]);

        $isSupplier = request()->routeIs('suppliers.*');
        $isCustomer = request()->routeIs('customers.*');
        $isPrices = request()->routeIs('prices.*');
        $isCash = request()->routeIs('cash.*');
        $isSales = request()->routeIs('sales.*');

        $isCompras = request()->routeIs('purchase-requests.*') || request()->routeIs('purchase-quotation-requests.*') || request()->routeIs('purchase_orders.*') || request()->routeIs('expense-types.*') || request()->routeIs('purchases.*') || request()->routeIs('retaceos.*');
        $comprasRoute = $firstAllowedRoute([
            'purchase_requests.ver' => 'purchase-requests.index',
            'purchase_quotation_requests.ver' => 'purchase-quotation-requests.index',
            'purchase_orders.ver' => 'purchase_orders.index',
            'purchases.ver' => 'purchases.index',
            'retaceos.ver' => 'retaceos.index',
            'expense_types.ver' => 'expense-types.index',
        ]);

        $isAdministracion = request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('audit-logs.*');
        $administracionRoute = $firstAllowedRoute([
            'usuarios.ver' => 'users.index',
            'admin' => 'roles.index',
            'bitacora.ver' => 'audit-logs.index',
        ]);
    @endphp

    <!-- BARRA SUPERIOR MÓVIL -->
    <div class="md:hidden flex items-center justify-between px-4 h-14 bg-sidebar text-sidebar-fg shrink-0">
        <button type="button" @click="mobileOpen = true" class="p-2 -ml-2 rounded-lg hover:bg-white/10" aria-label="Abrir menú">
            <x-icon name="menu" class="w-6 h-6" />
        </button>
        <span class="font-extrabold text-sm tracking-tight truncate">{{ config('app.name', 'Transacciones') }}</span>
        <button type="button" onclick="toggleDarkMode()" class="p-2 -mr-2 rounded-lg hover:bg-white/10" aria-label="Cambiar tema">
            <x-icon name="moon" class="w-5 h-5 dark:hidden" />
            <x-icon name="sun" class="w-5 h-5 hidden dark:block text-amber-300" />
        </button>
    </div>

    <!-- BACKDROP DEL DRAWER (móvil) -->
    <div x-show="mobileOpen" x-cloak @click="mobileOpen = false" class="drawer-backdrop md:hidden"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    <!-- BARRA LATERAL -->
    <aside class="app-sidebar flex flex-col justify-between p-4 shrink-0 md:h-screen"
           :class="{ 'is-collapsed': collapsed, 'is-open': mobileOpen }">
        <div class="min-h-0 overflow-y-auto">
            <!-- Encabezado: logo + colapsar -->
            <div class="flex items-center justify-between mb-5 px-1">
                <span class="font-extrabold text-sm tracking-tight nav-label">{{ config('app.name', 'Transacciones') }}</span>
                <button type="button" @click="toggle()" class="hidden md:inline-flex p-1.5 rounded-lg hover:bg-white/10 text-sidebar-fg" aria-label="Colapsar menú">
                    <x-icon name="chevron-left" class="w-5 h-5" x-show="!collapsed" />
                    <x-icon name="chevron-right" class="w-5 h-5" x-show="collapsed" x-cloak />
                </button>
                <button type="button" @click="mobileOpen = false" class="md:hidden p-1.5 rounded-lg hover:bg-white/10 text-sidebar-fg" aria-label="Cerrar menú">
                    <x-icon name="x" class="w-5 h-5" />
                </button>
            </div>

            <!-- Usuario autenticado -->
            <div class="sidebar-userblock flex flex-col items-center text-center mb-6 border-b border-white/10 pb-5">
                <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center font-bold text-lg text-white mb-2 uppercase border border-white/20">
                    {{ auth()->check() ? substr(auth()->user()->username, 0, 2) : 'US' }}
                </div>
                <span class="text-sm font-bold text-white block truncate max-w-full">{{ auth()->check() ? auth()->user()->username : 'Usuario' }}</span>
                <span class="text-xs text-sidebar-fg/80 font-semibold mt-0.5 truncate max-w-full">{{ auth()->check() ? auth()->user()->email : '' }}</span>
            </div>

            <!-- Menú -->
            <nav class="space-y-1.5">
                <x-ui.nav-item :href="route('dashboard')" icon="dashboard" label="Dashboard" :active="request()->routeIs('dashboard')" />

                @if($inventarioRoute)
                    <x-ui.nav-item :href="route($inventarioRoute)" icon="inventory" label="Inventario" :active="$isInventario" />
                @endif
                @if($empresaRoute)
                    <x-ui.nav-item :href="route($empresaRoute)" icon="building" label="Empresa" :active="$isEmpresa" />
                @endif
                @if($productosRoute)
                    <x-ui.nav-item :href="route($productosRoute)" icon="tag" label="Productos" :active="$isProductos" />
                @endif
                @can('suppliers.ver')
                    <x-ui.nav-item :href="route('suppliers.index')" icon="truck" label="Proveedores" :active="$isSupplier" />
                @endcan
                @can('customers.ver')
                    <x-ui.nav-item :href="route('customers.index')" icon="user" label="Clientes" :active="$isCustomer" />
                @endcan
                @can('prices.ver')
                    <x-ui.nav-item :href="route('prices.index')" icon="currency" label="Precios" :active="$isPrices" />
                @endcan
                @can('cash.ver')
                    <x-ui.nav-item :href="route('cash.index')" icon="currency" label="Caja" :active="$isCash" />
                @endcan
                @can('sales.ver')
                    <x-ui.nav-item :href="route('sales.index')" icon="cart" label="Ventas" :active="$isSales" />
                @endcan
                @if($comprasRoute)
                    <x-ui.nav-item :href="route($comprasRoute)" icon="bag" label="Compras" :active="$isCompras" />
                @endif
                @if($administracionRoute)
                    <x-ui.nav-item :href="route($administracionRoute)" icon="cog" label="Administración" :active="$isAdministracion" />
                @endif
            </nav>
        </div>

        <!-- Pie del sidebar -->
        <div class="pt-3 space-y-2">
            <button type="button" onclick="toggleDarkMode()" class="sidebar-foot-btn flex items-center gap-2.5 w-full py-2.5 px-3 rounded-xl bg-white/10 border border-white/15 hover:bg-white/20 transition-all text-sm font-semibold text-white" title="Cambiar tema">
                <x-icon name="moon" class="w-4 h-4 dark:hidden text-amber-300 shrink-0" />
                <x-icon name="sun" class="w-4 h-4 hidden dark:block text-amber-300 shrink-0" />
                <span class="sidebar-foot-label"><span class="dark:hidden">Modo oscuro</span><span class="hidden dark:inline">Modo claro</span></span>
            </button>
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">@csrf</form>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="sidebar-foot-btn flex items-center gap-2.5 w-full py-2.5 px-3 rounded-xl border border-white/15 hover:bg-white/10 transition-all text-sm font-semibold text-sidebar-fg" title="Cerrar sesión">
                <x-icon name="logout" class="w-4 h-4 shrink-0" />
                <span class="sidebar-foot-label">Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="relative flex-1 p-5 md:p-8 overflow-y-auto bg-canvas text-body transition-colors duration-300">
        <!-- Submenús -->
        @if($isCompras)
            <x-ui.tab-nav>
                @can('purchase_requests.ver')<x-ui.tab-link :href="route('purchase-requests.index')" :active="request()->routeIs('purchase-requests.*')">Solicitudes de Compra</x-ui.tab-link>@endcan
                @can('purchase_quotation_requests.ver')<x-ui.tab-link :href="route('purchase-quotation-requests.index')" :active="request()->routeIs('purchase-quotation-requests.*')">Solicitudes de Cotización</x-ui.tab-link>@endcan
                @can('purchase_orders.ver')<x-ui.tab-link :href="route('purchase_orders.index')" :active="request()->routeIs('purchase_orders.*')">Órdenes de Compra</x-ui.tab-link>@endcan
                @can('purchases.ver')<x-ui.tab-link :href="route('purchases.index')" :active="request()->routeIs('purchases.*')">Facturas / Compras</x-ui.tab-link>@endcan
                @can('retaceos.ver')<x-ui.tab-link :href="route('retaceos.index')" :active="request()->routeIs('retaceos.*')">Retaceos</x-ui.tab-link>@endcan
                @can('expense_types.ver')<x-ui.tab-link :href="route('expense-types.index')" :active="request()->routeIs('expense-types.*')">Tipos de Gastos</x-ui.tab-link>@endcan
            </x-ui.tab-nav>
        @endif
        @if($isInventario)
            <x-ui.tab-nav>
                @can('inventory.ver')
                    <x-ui.tab-link :href="route('inventory.index')" :active="request()->routeIs('inventory.index')">Existencias</x-ui.tab-link>
                    <x-ui.tab-link :href="route('inventory.lots.index')" :active="request()->routeIs('inventory.lots.*', 'inventory.labels*')">Lotes</x-ui.tab-link>
                    <x-ui.tab-link :href="route('inventory.movements')" :active="request()->routeIs('inventory.movements')">Movimientos</x-ui.tab-link>
                    <x-ui.tab-link :href="route('inventory-transfers.index')" :active="request()->routeIs('inventory-transfers.*')">Traslados</x-ui.tab-link>
                @endcan
                @can('warehouses.ver')<x-ui.tab-link :href="route('warehouses.index')" :active="request()->routeIs('warehouses.*')">Bodegas</x-ui.tab-link>@endcan
                @can('warehouse_categories.ver')<x-ui.tab-link :href="route('warehouse_categories.index')" :active="request()->routeIs('warehouse_categories.*')">Categorías</x-ui.tab-link>@endcan
                @can('locations.ver')
                    <x-ui.tab-link :href="route('locations.index')" :active="request()->routeIs('locations.index', 'locations.create', 'locations.edit', 'locations.show')">Ubicaciones</x-ui.tab-link>
                    <x-ui.tab-link :href="route('locations.map')" :active="request()->routeIs('locations.map')">Mapa Visual</x-ui.tab-link>
                @endcan
                @can('vehicles.ver')<x-ui.tab-link :href="route('vehicles.index')" :active="request()->routeIs('vehicles.*')">Vehículos</x-ui.tab-link>@endcan
            </x-ui.tab-nav>
        @endif
        @if($isEmpresa)
            <x-ui.tab-nav>
                @can('companies.ver')<x-ui.tab-link :href="route('companies.index')" :active="request()->routeIs('companies.*')">Empresas</x-ui.tab-link>@endcan
                @can('branches.ver')<x-ui.tab-link :href="route('branches.index')" :active="request()->routeIs('branches.*')">Sucursales</x-ui.tab-link>@endcan
                @can('employees.ver')<x-ui.tab-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">Empleados</x-ui.tab-link>@endcan
            </x-ui.tab-nav>
        @endif
        @if($isProductos)
            <x-ui.tab-nav>
                @can('products.ver')<x-ui.tab-link :href="route('products.index')" :active="request()->routeIs('products.*')">Productos</x-ui.tab-link>@endcan
                @can('categories.ver')<x-ui.tab-link :href="route('categories.index')" :active="request()->routeIs('categories.*')">Categorías</x-ui.tab-link>@endcan
                @can('subcategories.ver')<x-ui.tab-link :href="route('subcategories.index')" :active="request()->routeIs('subcategories.*')">Subcategorías</x-ui.tab-link>@endcan
                @can('units.ver')<x-ui.tab-link :href="route('units.index')" :active="request()->routeIs('units.*')">Unidades de Medida</x-ui.tab-link>@endcan
            </x-ui.tab-nav>
        @endif
        @if($isAdministracion)
            <x-ui.tab-nav>
                @can('usuarios.ver')<x-ui.tab-link :href="route('users.index')" :active="request()->routeIs('users.*')">Usuarios</x-ui.tab-link>@endcan
                @can('admin')<x-ui.tab-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">Roles y Permisos</x-ui.tab-link>@endcan
                @can('bitacora.ver')<x-ui.tab-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')">Bitácora de Auditoría</x-ui.tab-link>@endcan
            </x-ui.tab-nav>
        @endif

        @isset($header)
            <header class="ui-surface mb-6 p-4">
                <div class="w-full flex items-center justify-between">{{ $header }}</div>
            </header>
        @endisset

        @yield('content')
        @if(isset($slot))
            {{ $slot }}
        @endif
    </main>

    <!-- MODAL DE ELIMINACIÓN GLOBAL -->
    <div id="global-delete-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-xs transition-all duration-200">
        <div class="ui-surface p-8 max-w-md w-full pop-shadow text-center relative mx-4 transform scale-95 transition-all duration-200" id="global-delete-card">
            <div class="w-14 h-14 rounded-full border-2 border-warning-line flex items-center justify-center mx-auto text-warning mb-5">
                <x-icon name="warning" class="w-7 h-7" />
            </div>
            <h3 class="text-lg font-bold text-body mb-2" id="global-delete-title">¿Inactivar Registro?</h3>
            <p class="text-muted text-sm mb-6" id="global-delete-description">Estás a punto de cambiar el estado de este registro.</p>
            <form id="global-delete-form" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="flex justify-center gap-3">
                    <button type="button" onclick="closeGlobalDeleteModal()" class="px-5 py-2.5 bg-sunken hover:brightness-95 text-body font-semibold rounded-xl text-sm transition-all">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 bg-danger hover:brightness-110 text-white font-semibold rounded-xl text-sm transition-all shadow-sm">Sí, inactivar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL VISOR DE IMÁGENES GLOBAL -->
    <div id="global-image-modal" class="hidden fixed inset-0 items-center justify-center bg-slate-950/85 backdrop-blur-md transition-all duration-200" style="z-index: 99999;" onclick="closeGlobalImageModal()">
        <div class="relative max-w-4xl max-h-[90vh] p-2 mx-4" onclick="event.stopPropagation()">
            <button type="button" onclick="closeGlobalImageModal()" class="absolute -top-3 -right-3 z-50 w-9 h-9 bg-slate-800 hover:bg-slate-700 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-2xl border-2 border-slate-600 transition-all cursor-pointer" title="Cerrar vista previa">✕</button>
            <div class="rounded-2xl overflow-hidden shadow-2xl bg-black border border-slate-800 flex items-center justify-center p-1">
                <img id="global-image-target" src="" class="max-w-full max-h-[80vh] object-contain block mx-auto rounded-xl" alt="Vista previa">
            </div>
            <p id="global-image-title" class="text-center text-slate-300 text-xs font-semibold mt-3 font-mono truncate"></p>
        </div>
    </div>

    <x-sileo-toast />
</body>
</html>
