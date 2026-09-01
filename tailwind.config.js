const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#c20000',
                    hover: '#a30000',
                    light: '#fff5f5',
                },
                secondary: '#1a1a1a',
            },
            fontFamily: {
                sans: ['Noto Sans Arabic', 'Nunito', ...defaultTheme.fontFamily.sans],
                'sans-latin': ['Montserrat', ...defaultTheme.fontFamily.sans],
                'sans-arabic': ['Noto Sans Arabic', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
};
