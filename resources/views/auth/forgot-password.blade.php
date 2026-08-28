<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Recuperar Contraseña</title>
    <!-- Google Fonts: Nunito -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <!-- Carga del CSS modular mediante Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Aplicar tema guardado previamente para evitar parpadeos -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="auth-body font-sans antialiased relative overflow-hidden">
    <!-- BOTÓN MODO OSCURO (Componente theme-toggle.css) -->
    <button
        type="button"
        onclick="toggleDarkMode()"
        class="theme-toggle-btn"
        title="Cambiar tema"
    >
        <!-- Luna (modo claro activo) -->
        <svg class="w-5 h-5 dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
        </svg>
        <!-- Sol (modo oscuro activo) -->
        <svg class="w-5 h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
    </button>

    <!-- Círculos decorativos del fondo principal -->
    <div class="absolute -top-32 -left-32 w-80 h-80 rounded-full bg-[#d2ebe6] dark:bg-slate-800/40 opacity-60 pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-[#d6eee9] dark:bg-slate-800/30 opacity-60 pointer-events-none"></div>
    <div class="absolute top-1/4 right-12 w-64 h-64 rounded-full bg-[#d9f1ed] dark:bg-slate-800/20 opacity-40 pointer-events-none"></div>

    <!-- TARJETA PRINCIPAL (Componente auth.css) -->
    <div class="auth-card relative overflow-hidden">

        <!-- LEFT SIDE: Form & Navbar -->
        <div class="w-full md:w-3/5 p-8 flex flex-col justify-between">
            <!-- Form Wrapper -->
            <div class="w-full max-w-sm mx-auto my-auto py-4">
                <!-- Tab Headers -->
                <div class="flex border-b border-gray-100 dark:border-slate-700 mb-8">
                    <button onclick="window.location.href='{{ route('login') }}'" class="flex-1 text-center pb-3 text-sm font-semibold text-gray-400 dark:text-slate-400 border-b-2 border-transparent hover:text-gray-600 dark:hover:text-slate-200 transition-all">
                        Iniciar sesión
                    </button>
                    <button class="flex-1 text-center pb-3 text-sm font-bold border-b-2 border-[#3cb0a4] text-[#2b7f76] dark:text-customTeal-400">
                        Recuperar contraseña
                    </button>
                </div>

                <!-- Info Message -->
                <div class="mb-6 text-xs text-gray-500 dark:text-slate-300 font-medium leading-relaxed bg-[#edf9f6]/70 dark:bg-slate-900/60 p-4 rounded-2xl border border-customTeal-100 dark:border-slate-700">
                    ¿Olvidaste tu contraseña? No te preocupes. Ingresa tu correo electrónico y te enviaremos un enlace para restablecerla.
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />
                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf
                    <!-- Email Address -->
                    <div>
                        <div class="relative flex items-center shadow-sm border border-gray-200 dark:border-slate-700 rounded-full bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-[#4ebbb0] transition-all">
                            <span class="absolute left-1 w-9 h-9 rounded-full bg-[#edf9f6] dark:bg-slate-800 flex items-center justify-center text-[#3cb0a4]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                            <input id="email" class="w-full pl-12 pr-4 py-3 rounded-full text-xs border-0 focus:outline-none placeholder-gray-400 dark:placeholder-slate-500 text-gray-700 dark:text-slate-100 font-medium bg-transparent" type="email" name="email" :value="old('email')" placeholder="Correo electrónico" required autofocus />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs ml-4" />
                    </div>

                    <!-- Action Button -->
                    <div>
                        <button type="submit" class="w-full bg-[#3cb0a4] hover:bg-[#349b90] text-white rounded-full py-3 text-xs font-bold transition-all shadow-lg active:scale-95 uppercase tracking-wider">
                            Enviar enlace de recuperación
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer terms -->
            <footer class="text-center md:text-left">
                <span class="text-[10px] text-gray-400 dark:text-slate-500 font-medium">&copy; 2026 {{ config('app.name', 'Laravel') }}. Todos los derechos reservados.</span>
            </footer>
        </div>
        
        <!-- RIGHT SIDE: Isometric Illustration (Componente auth.css) -->
        <div class="auth-illustration-side relative overflow-hidden select-none">
            <!-- Concentric design waves/borders -->
            <div class="absolute w-72 h-72 rounded-full border-2 border-white/20 dark:border-slate-700/30 pointer-events-none"></div>
            <div class="absolute w-96 h-96 rounded-full border-4 border-white/25 dark:border-slate-700/40 pointer-events-none"></div>
            <div class="absolute w-[450px] h-[450px] rounded-full border-8 border-white/30 dark:border-slate-700/50 pointer-events-none"></div>
            <!-- Illustration Asset -->
            <div class="relative z-10 w-full max-w-[280px] h-auto drop-shadow-[0_15px_30px_rgba(0,0,0,0.15)] hover:scale-105 transition-transform duration-500">
                <img src="{{ asset('images/login_illustration.png') }}" alt="Login Illustration" class="w-full h-auto object-contain opacity-90 dark:opacity-80" />
            </div>
        </div>
    </div>

    <!-- Toggle function for dark mode -->
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
    </script>
</body>
</html>