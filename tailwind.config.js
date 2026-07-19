import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                ink: {
                    DEFAULT: '#12151a',
                    soft: '#1a1f27',
                    deep: '#0d1014',
                },
                paper: '#e8ebe6',
                muted: '#8b9388',
                accent: {
                    DEFAULT: '#2dd4bf',
                    dim: '#14b8a6',
                },
                line: 'rgba(232, 235, 230, 0.08)',
                warn: '#f59e0b',
                danger: '#f87171',
            },
            fontFamily: {
                sans: ['Syne', ...defaultTheme.fontFamily.sans],
                display: ['Syne', ...defaultTheme.fontFamily.sans],
                mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
        },
    },

    plugins: [forms],
};
