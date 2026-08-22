<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Recuperar Contraseña</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Tailwind CDN for advanced color palette configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        customTeal: {
                            50: '#edf9f6',
                            100: '#d4eedc',
                            400: '#4ebbb0',
                            500: '#3cb0a4',
                            600: '#349b90',
                            700: '#2b7f76',
                            800: '#005e66',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Aplicar tema guardado previamente -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="font-sans antialiased bg-[#e1f2ef] dark:bg-[#0b1120] text-gray-800 dark:text-slate-100 min-h-screen relative overflow-hidden flex items-center justify-center transition-colors duration-300">

    <!-- BOTÓN MODO OSCURO -->
    <button
        type="button"
        onclick="toggleDarkMode()"
        class="fixed top-5 right-6 z-50 w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-md flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all"
        title="Cambiar tema">
        <!-- Luna (modo claro activo) -->
        <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
        </svg>
        <!-- Sol (modo oscuro activo) -->
        <svg class="w-5 h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
    </button>

    <!-- Decorative background waves/circles -->
    <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-[#d2ebe6] dark:bg-slate-800/40 opacity-60 pointer-events-none"></div>
    <div class="absolute -bottom-45 -right-20 w-[500px] h-[500px] rounded-full bg-[#d6eee9] dark:bg-slate-800/30 opacity-60 pointer-events-none"></div>
    <div class="absolute top-[20%] right-[10%] w-72 h-72 rounded-full bg-[#d9f1ed] dark:bg-slate-800/20 opacity-40 pointer-events-none"></div>

    <div class="w-full max-w-5xl bg-white dark:bg-slate-800/90 dark:backdrop-blur-md rounded-[32px] shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[580px] relative z-10 m-4 border border-teal-50 dark:border-slate-700 transition-colors duration-300">
        
        <!-- LEFT SIDE: Form & Navbar -->
        <div class="w-full md:w-3/5 p-8 flex flex-col justify-between">

            <!-- Form Wrapper -->
            <div class="w-full max-w-sm mx-auto my-auto py-4">
                
                <!-- Tab Headers -->
                <div class="flex border-b border-gray-100 dark:border-slate-700 mb-8">
                    <button onclick="window.location.href='{{ route('login') }}'" class="flex-1 text-center pb-3 text-sm font-semibold text-gray-400 dark:text-slate-400 border-b-2 border-transparent hover:text-gray-600 dark:hover:text-slate-200 transition-all">
                        Iniciar sesión
                    </button>
                    <button class="flex-1 text-center pb-3 text-sm font-bold border-b-2 border-customTeal-500 text-customTeal-700 dark:text-customTeal-400">
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
                        <div class="relative flex items-center shadow-[0_4px_15px_rgba(0,0,0,0.02)] border border-gray-100 dark:border-slate-700 rounded-full bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-customTeal-400 transition-all">
                            <span class="absolute left-1 w-9 h-9 rounded-full bg-customTeal-50 dark:bg-slate-800 flex items-center justify-center text-customTeal-500 dark:text-customTeal-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                            <input id="email" class="w-full pl-12 pr-4 py-3 rounded-full text-xs border-0 focus:outline-none placeholder-gray-400 dark:placeholder-slate-500 text-gray-700 dark:text-slate-100 font-medium bg-transparent" 
                                   type="email" name="email" :value="old('email')" placeholder="Correo electrónico" required autofocus />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs ml-4" />
                    </div>

                    <!-- Action Button -->
                    <div>
                        <button type="submit" class="w-full bg-customTeal-500 hover:bg-customTeal-600 text-white rounded-full py-3 text-xs font-bold transition-all shadow-lg shadow-customTeal-500/20 active:scale-95 uppercase tracking-wider">
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
        
        <!-- RIGHT SIDE: Isometric Illustration -->
        <div class="w-full md:w-2/5 bg-[#89d5cc] dark:bg-slate-900/80 relative flex items-center justify-center p-8 overflow-hidden select-none transition-colors duration-300">
            
            <!-- Concentric design waves/borders -->
            <div class="absolute w-[200%] h-[200%] border-[2px] border-white/10 dark:border-slate-700/30 rounded-full top-[-50%] right-[-50%] pointer-events-none"></div>
            <div class="absolute w-[150%] h-[150%] border-[4px] border-white/15 dark:border-slate-700/40 rounded-full top-[-25%] right-[-25%] pointer-events-none"></div>
            <div class="absolute w-[100%] h-[100%] border-[6px] border-white/20 dark:border-slate-700/50 rounded-full top-[0%] right-[0%] pointer-events-none"></div>
            <div class="absolute w-[60%] h-[60%] border-[8px] border-white/25 dark:border-slate-700/60 rounded-full top-[20%] right-[20%] pointer-events-none"></div>

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
