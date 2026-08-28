<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        <style>
            html.dark body {
                background-color: #0b1120 !important;
                color: #f1f5f9 !important;
            }

            html.dark .bg-white,
            html.dark [class*="bg-white"] {
                background-color: #1e293b !important;
                border-color: #334155 !important;
                color: #f1f5f9 !important;
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

            html.dark label,
            html.dark .text-gray-900,
            html.dark .text-gray-800,
            html.dark .text-gray-700 {
                color: #f1f5f9 !important;
            }

            html.dark .text-gray-600,
            html.dark .text-gray-500 {
                color: #94a3b8 !important;
            }

            html.dark a {
                color: #38bdf8 !important;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100 dark:bg-slate-900 transition-colors duration-300">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-slate-900 px-4">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500 dark:text-gray-400" />
                </a>
            </div>
            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-md overflow-hidden sm:rounded-2xl transition-colors duration-300">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>