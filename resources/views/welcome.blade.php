<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col transition-colors duration-300">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 rounded-lg text-sm leading-normal transition-all"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 text-slate-700 dark:text-slate-300 border border-transparent hover:border-slate-300 dark:hover:border-slate-700 rounded-lg text-sm leading-normal transition-all"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 rounded-lg text-sm leading-normal transition-all"
                            >
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xl overflow-hidden transition-colors duration-300">
                <div class="text-sm leading-relaxed flex-1 p-6 pb-12 lg:p-16 text-slate-700 dark:text-slate-300">
                    <h1 class="mb-2 font-bold text-2xl text-slate-900 dark:text-slate-100">Let's get started</h1>
                    <p class="mb-6 text-slate-500 dark:text-slate-400">Laravel has an incredibly rich ecosystem. We suggest starting with the following.</p>
                    
                    <ul class="flex flex-col gap-4 mb-8">
                        <li class="flex items-center gap-3">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 text-xs font-bold">1</span>
                            <span>
                                Read the
                                <a href="https://laravel.com/docs" target="_blank" class="font-semibold underline underline-offset-4 text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 ml-1">Documentation</a>
                            </span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 text-xs font-bold">2</span>
                            <span>
                                Watch video tutorials at
                                <a href="https://laracasts.com" target="_blank" class="font-semibold underline underline-offset-4 text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 ml-1">Laracasts</a>
                            </span>
                        </li>
                    </ul>

                    <div>
                        <a href="https://cloud.laravel.com" target="_blank" class="inline-block px-6 py-2.5 bg-slate-900 dark:bg-sky-600 hover:bg-slate-800 dark:hover:bg-sky-500 text-white font-bold rounded-xl text-sm transition-all shadow-md">
                            Deploy now
                        </a>
                    </div>
                </div>

                <div class="bg-slate-100 dark:bg-slate-900/50 flex items-center justify-center p-8 lg:w-[380px] shrink-0 border-b lg:border-b-0 lg:border-l border-slate-200 dark:border-slate-700">
                    <svg class="w-32 h-32 text-teal-600 dark:text-teal-400" viewBox="0 0 50 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M49.626 11.564a1.999 1.999 0 0 0-.678-.89L25.948.517a2 2 0 0 0-2.096 0L.852 10.674a2 2 0 0 0-.678.89 2.03 2.03 0 0 0-.174.82v28.232c0 .285.06.566.174.82.14.31.37.57.678.89l23 10.157a1.996 1.996 0 0 0 2.096 0l23-10.157c.307-.32.537-.58.678-.89.115-.254.174-.535.174-.82V12.384a2.03 2.03 0 0 0-.174-.82ZM24.9 4.316l19.5 8.614-7.447 3.29-19.5-8.614 7.447-3.29ZM4 14.567l19 8.393v23.724L4 38.291V14.567Zm22 32.117V22.96l7-3.093v7.417a1 1 0 1 0 2 0v-8.3l9-3.977v23.724l-18 7.953Z" fill="currentColor"/>
                    </svg>
                </div>
            </main>
        </div>

        @if (Route::has('login'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>
</html>