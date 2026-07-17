<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Sistema de Transacciones y Facturación</title>
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
                                sidebar: '#183162',
                                active: '#27437c',
                                800: '#1b2a45'
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
<body class="min-h-screen flex flex-col md:flex-row font-sans overflow-x-hidden">

    <!-- BARRA LATERAL -->
    <aside class="w-full md:w-64 bg-navy-sidebar flex flex-col justify-between p-5 min-h-[450px] md:min-h-screen sidebar-shadow text-white">
        <div>
            <!-- Header con Logo -->
            <div class="flex flex-col items-center text-center mt-4 mb-8">
                <span class="text-xs text-slate-300 font-semibold mt-1">Panel de Control</span>
            </div>

            <!-- Menú de Opciones -->
            <nav class="space-y-1.5 px-1">
                <!-- Dashboard (Activo) -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('dashboard') ? 'bg-navy-active text-white font-bold' : 'text-slate-300 hover:bg-white/5 hover:text-white font-semibold' }} transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                <!-- Usuarios -->
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all font-semibold">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Usuarios</span>
                </a>

                <!-- Empleados -->
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all font-semibold">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-7 4h10" />
                    </svg>
                    <span>Empleados</span>
                </a>

                <!-- Roles y Permisos -->
                <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all font-semibold">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Roles y Permisos</span>
                </a>
            </nav>
        </div>

        <!-- Botón Cerrar Sesión (Estilo borde blanco de la imagen) -->
        <div class="px-1 mb-4">
            <!-- Formulario de Logout Laravel -->
            <form method="POST" action="/logout" id="logout-form" class="hidden">
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

    <!-- CONTENIDO PRINCIPAL (LADO DERECHO - Según la imagen de referencia) -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        @yield('content')
    </main>

</body>
</html>
