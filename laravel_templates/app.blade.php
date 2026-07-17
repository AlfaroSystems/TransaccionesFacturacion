<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Sistema de Transacciones y Facturación</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Mediante CDN para desarrollo o asset compilado con Vite) -->
    @if(app()->environment('local'))
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: { sans: ['Outfit', 'sans-serif'] },
                        colors: { brand: { dark: '#0b0f19', card: '#1e293b' } }
                    }
                }
            }
        </script>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            background-color: #0b0f19;
            color: #f1f5f9;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glass-sidebar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row font-sans overflow-x-hidden">

    <!-- CONTENIDO PRINCIPAL (LADO IZQUIERDO) -->
    <main class="flex-1 p-6 md:p-10 order-2 md:order-1 overflow-y-auto">
        @yield('content')
    </main>

    <!-- MENÚ / BARRA LATERAL DERECHA -->
    <aside class="w-full md:w-80 glass-sidebar flex flex-col order-1 md:order-2 p-6 justify-between min-h-[300px] md:min-h-screen">
        <div>
            <!-- Branding / Logo -->
            <div class="flex items-center gap-3 mb-10 pb-6 border-b border-slate-800">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white leading-tight">Antigravity Fact.</h2>
                    <span class="text-xs text-slate-400">Laravel v11</span>
                </div>
            </div>

            <!-- Menú del Lado Derecho -->
            <nav class="space-y-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4 px-3">Módulos</p>
                
                <!-- Dashboard (Activo) -->
                <a href="{{ route('dashboard') }}" class="flex items-center justify-between px-4 py-3 rounded-xl {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white font-medium shadow-md shadow-blue-500/10' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }} transition-all">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"/></svg>
                        <span>Dashboard</span>
                    </div>
                </a>

                <!-- Usuarios (RF-001) -->
                <a href="#" class="flex items-center justify-between px-4 py-3 rounded-xl text-slate-300 hover:bg-slate-800/60 hover:text-white transition-all group">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>Usuarios</span>
                    </div>
                    <span class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded-full group-hover:bg-blue-500/10 group-hover:text-blue-400 transition-colors">RF-001</span>
                </a>

                <!-- Empleados (RF-002) -->
                <a href="#" class="flex items-center justify-between px-4 py-3 rounded-xl text-slate-300 hover:bg-slate-800/60 hover:text-white transition-all group">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-7 4h10"/></svg>
                        <span>Empleados</span>
                    </div>
                    <span class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded-full group-hover:bg-blue-500/10 group-hover:text-blue-400 transition-colors">RF-002</span>
                </a>

                <!-- Roles y Permisos (RF-003) -->
                <a href="#" class="flex items-center justify-between px-4 py-3 rounded-xl text-slate-300 hover:bg-slate-800/60 hover:text-white transition-all group">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Roles y Permisos</span>
                    </div>
                    <span class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded-full group-hover:bg-blue-500/10 group-hover:text-blue-400 transition-colors">RF-003</span>
                </a>
            </nav>
        </div>

        <!-- Footer / Usuario Conectado -->
        <div class="pt-6 border-t border-slate-800 text-xs text-slate-500 flex flex-col gap-1">
            <div class="flex justify-between">
                <span>Usuario:</span>
                <span class="text-slate-300 font-mono">{{ auth()->user()->email ?? 'admin@empresa.com' }}</span>
            </div>
            <div class="flex justify-between">
                <span>Estado:</span>
                <span class="text-emerald-400">En línea</span>
            </div>
        </div>
    </aside>

</body>
</html>
