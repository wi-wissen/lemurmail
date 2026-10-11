import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                paper: '#F5F7F6',
                surface: '#FFFFFF',
                ink: { DEFAULT: '#16211D', soft: '#4F5F59' },
                line: { DEFAULT: '#D5DCD9', strong: '#7C8B85' },
                accent: { DEFAULT: '#0B7A5E', hover: '#095F49', soft: '#E3F1EC' },
                warn: { DEFAULT: '#B45309', soft: '#FBF1E3', ink: '#7A3A05' },
                danger: { DEFAULT: '#B42318', soft: '#FBEAEA', ink: '#7A1D14' },
                beamer: { bg: '#0E1814', row: '#16251F', text: '#EAF3EF', muted: '#9DB5AB', accent: '#5FD3B0', warn: '#F2B36B' },
            },
            fontFamily: {
                sans: ['Atkinson Hyperlegible', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono Variable', ...defaultTheme.fontFamily.mono],
            },
        },
    },

    plugins: [forms],
};
