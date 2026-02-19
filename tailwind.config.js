import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        // Navbar colors por perfil
        'bg-slate-900',
        'bg-red-900',
        'bg-emerald-900',
        'bg-amber-900',
        'bg-[#D10000]',
        'bg-blue-600',
        'bg-emerald-600',
        'bg-violet-600',
        'bg-rose-600',
        'bg-[#9CA6BA]',
        'bg-[#5C73D1]',
        'text-white',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    red: {
                        DEFAULT: '#dc2626',
                    },
                    dark: {
                        DEFAULT: '#111111',
                    },
                    light: {
                        DEFAULT: '#fff1f2',
                    },
                },
            },
            animation: {
                blob: 'blob 7s infinite',
            },
            keyframes: {
                blob: {
                    '0%': {
                        transform: 'translate(0px, 0px) scale(1)',
                    },
                    '33%': {
                        transform: 'translate(30px, -50px) scale(1.1)',
                    },
                    '66%': {
                        transform: 'translate(-20px, 20px) scale(0.9)',
                    },
                    '100%': {
                        transform: 'translate(0px, 0px) scale(1)',
                    },
                },
            },
        },
    },

    plugins: [forms],
};
