import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                sentinel: {
                    black: '#020809',
                    charcoal: '#0f172a',
                    steel: '#1f2937',
                    olive: '#4b5a31',
                    graphite: '#334155',
                    cyan: '#22d3ee',
                    green: '#7cfc00',
                    amber: '#f59e0b',
                    red: '#ef4444',
                    slate: '#64748b',
                },
            },
        },
    },

    plugins: [forms],
};
