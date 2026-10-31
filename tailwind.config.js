import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Support/**/*.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
                outfit: ['Outfit', 'sans-serif'],
            },
            colors: {
                brand: {
                    25: '#f2f7ff',
                    50: '#ecf3ff',
                    100: '#dde9ff',
                    200: '#c2d6ff',
                    300: '#9cb9ff',
                    400: '#7592ff',
                    500: '#465fff',
                    600: '#3641f5',
                    700: '#2a31d8',
                    800: '#252dae',
                    900: '#262e89',
                    950: '#161950',
                },
                error: {
                    50: '#fef3f2',
                    100: '#fee4e2',
                    500: '#f04438',
                    600: '#d92d20',
                },
                success: {
                    50: '#ecfdf3',
                    100: '#dcfae6',
                    500: '#12b76a',
                    600: '#039855',
                },
            },
            boxShadow: {
                'theme-xs': '0px 1px 2px 0px rgba(16, 24, 40, 0.05)',
                'theme-sm': '0px 1px 3px 0px rgba(16, 24, 40, 0.10), 0px 1px 2px 0px rgba(16, 24, 40, 0.06)',
            },
        },
    },

    plugins: [forms],
};
