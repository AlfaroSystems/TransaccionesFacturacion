<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Tailwind CDN for advanced color palette configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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
</head>
<body class="font-sans antialiased bg-[#e1f2ef] min-h-screen relative overflow-hidden flex items-center justify-center">

    <!-- Decorative background waves/circles -->
    <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-[#d2ebe6] opacity-60"></div>
    <div class="absolute -bottom-45 -right-20 w-[500px] h-[500px] rounded-full bg-[#d6eee9] opacity-60"></div>
    <div class="absolute top-[20%] right-[10%] w-72 h-72 rounded-full bg-[#d9f1ed] opacity-40"></div>

    <div class="w-full max-w-5xl bg-white rounded-[32px] shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[580px] relative z-10 m-4 border border-teal-50">
        
        <!-- LEFT SIDE: Form & Navbar -->
        <div class="w-full md:w-3/5 p-8 flex flex-col justify-between">
            


            <!-- Form Wrapper -->
            <div class="w-full max-w-sm mx-auto my-auto py-4">
                
                <!-- Tab Headers -->
                <div class="flex border-b border-gray-100 mb-8">
                    <button class="flex-1 text-center pb-3 text-sm font-bold border-b-2 border-customTeal-500 text-customTeal-700">
                        Iniciar sesión
                    </button>
                    <button onclick="window.location.href='{{ route('register') }}'" class="flex-1 text-center pb-3 text-sm font-semibold text-gray-400 border-b-2 border-transparent hover:text-gray-600 transition-all">
                        Registrarse
                    </button>
                </div>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <div class="relative flex items-center shadow-[0_4px_15px_rgba(0,0,0,0.02)] border border-gray-100 rounded-full bg-white focus-within:ring-2 focus-within:ring-customTeal-400 transition-all">
                            <span class="absolute left-1 w-9 h-9 rounded-full bg-customTeal-50 flex items-center justify-center text-customTeal-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                            <input id="email" class="w-full pl-12 pr-4 py-3 rounded-full text-xs border-0 focus:outline-none placeholder-gray-400 text-gray-700 font-medium" 
                                   type="email" name="email" :value="old('email')" placeholder="Correo electrónico" required autofocus autocomplete="username" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs ml-4" />
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="relative flex items-center shadow-[0_4px_15px_rgba(0,0,0,0.02)] border border-gray-100 rounded-full bg-white focus-within:ring-2 focus-within:ring-customTeal-400 transition-all">
                            <span class="absolute left-1 w-9 h-9 rounded-full bg-customTeal-50 flex items-center justify-center text-customTeal-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                            <input id="password" class="w-full pl-12 pr-4 py-3 rounded-full text-xs border-0 focus:outline-none placeholder-gray-400 text-gray-700 font-medium"
                                   type="password" name="password" placeholder="Contraseña" required autocomplete="current-password" />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs ml-4" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center ml-4">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-customTeal-500 shadow-sm focus:ring-customTeal-400" name="remember">
                        <label for="remember_me" class="ms-2 text-xs text-gray-500 font-medium cursor-pointer">Recordarme</label>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-between pt-2">
                        @if (Route::has('password.request'))
                            <a class="text-xs text-customTeal-700 hover:text-customTeal-500 hover:underline font-bold transition-all" href="{{ route('password.request') }}">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif

                        <button type="submit" class="bg-customTeal-500 hover:bg-customTeal-600 text-white rounded-full px-9 py-2.5 text-sm font-bold transition-all shadow-lg shadow-customTeal-500/20 active:scale-95">
                            Iniciar sesión
                        </button>
                    </div>
                </form>

            </div>

            <!-- Footer terms -->
            <footer class="text-center md:text-left">
                <span class="text-[10px] text-gray-400 font-medium">&copy; 2026 {{ config('app.name', 'Laravel') }}. Todos los derechos reservados.</span>
            </footer>

        </div>
        
        <!-- RIGHT SIDE: Isometric Illustration -->
        <div class="w-full md:w-2/5 bg-[#89d5cc] relative flex items-center justify-center p-8 overflow-hidden select-none">
            
            <!-- Concentric design waves/borders -->
            <div class="absolute w-[200%] h-[200%] border-[2px] border-white/10 rounded-full top-[-50%] right-[-50%] pointer-events-none"></div>
            <div class="absolute w-[150%] h-[150%] border-[4px] border-white/15 rounded-full top-[-25%] right-[-25%] pointer-events-none"></div>
            <div class="absolute w-[100%] h-[100%] border-[6px] border-white/20 rounded-full top-[0%] right-[0%] pointer-events-none"></div>
            <div class="absolute w-[60%] h-[60%] border-[8px] border-white/25 rounded-full top-[20%] right-[20%] pointer-events-none"></div>

            <!-- Illustration Asset -->
            <div class="relative z-10 w-full max-w-[280px] h-auto drop-shadow-[0_15px_30px_rgba(0,0,0,0.15)] hover:scale-105 transition-transform duration-500">
                <img src="{{ asset('images/login_illustration.png') }}" alt="Login Illustration" class="w-full h-auto object-contain" />
            </div>

        </div>

    </div>

</body>
</html>
