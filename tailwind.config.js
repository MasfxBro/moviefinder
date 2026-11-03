import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            colors: {
                bg: 'var(--color-bg)',
                surface: 'var(--color-surface)',
                card: 'var(--color-card)',
                elevated: 'var(--color-elevated)',
                border: 'var(--color-border)',
                text: 'var(--color-text)',
                muted: 'var(--color-muted)',
                faint: 'var(--color-faint)',
                accent: 'var(--color-accent)',
                'accent-soft': 'var(--color-accent-soft)',
                pro: 'var(--color-pro)',
                success: 'var(--color-success)',
                danger: 'var(--color-danger)',
                warning: 'var(--color-warning)',
            },
            fontFamily: {
                sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', '"SF Pro Text"', '"Segoe UI"', 'Roboto', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                'input': '12px',
                'card': '16px',
                'modal': '24px',
            },
        },
    },

    plugins: [forms],
};
