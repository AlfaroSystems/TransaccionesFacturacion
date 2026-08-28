import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                customTeal: {
                    50: '#edf9f6',
                    100: '#d4eedc',
                    400: '#4ebbb0',
                    500: '#3cb0a4',
                    600: '#349b90',
                    700: '#2b7f76',
                    800: '#005e66',
                },
                navy: {
                    sidebar: '#005e66',
                    active: '#3cb0a4',
                    800: '#00474f',
                },
                darkBg: {
                    base: '#0b1120',      // Fondo general profundo (Slate 950/900 suave)
                    sidebar: '#0f172a',   // Sidebar oscuro (Slate 900)
                    card: '#1e293b',      // Tarjetas, tablas y modales (Slate 800)
                    input: '#0f172a',     // Fondo interno de inputs/selects (Slate 900)
                    border: '#334155',    // Bordes y líneas divisorias (Slate 700)
                    hover: '#334155',     // Efectos hover
                }
            },
        },
    },
    plugins: [forms],
};