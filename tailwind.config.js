/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./assets/**/*.js",
        "./templates/**/*.html.twig",
    ],
    theme: {
        theme: {
            fontFamily: {
                sans: ['Roboto', 'sans-serif'],
            },
        },
        extend: {},
    },
    plugins: [
        require('daisyui')
    ]
}
