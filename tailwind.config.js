import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
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
                    sidebar: '#005e66', // Deep dark teal
                    active: '#3cb0a4',  // Primary teal
                    800: '#00474f'      // Darkest teal
                }
            },
        },
    },

    plugins: [forms],
};
