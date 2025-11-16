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
                // CagSU Campus Colors
                'cagsu': {
                    'yellow': '#FFD700',      // Primary Gold
                    'orange': '#FF8C00',      // Secondary Dark Orange  
                    'maroon': '#800000',      // Accent Maroon
                    'gold': '#FFD700',        // Alias for yellow
                    'blue': '#1D4ED8',        // CagSU Blue
                },
                // Additional government system colors
                'gov': {
                    'primary': '#FFD700',     // CagSU Yellow
                    'secondary': '#FF8C00',   // CagSU Orange
                    'accent': '#800000',      // CagSU Maroon
                    'light': '#F5F5F5',      // Light gray backgrounds
                    'dark': '#2D3748',       // Dark text
                }
            },
        },
    },

    plugins: [forms],
};
