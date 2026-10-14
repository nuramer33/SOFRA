import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // app.js = dashboard & auth; menu.js = public menu; landing.js = marketing site.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/menu.js', 'resources/js/landing.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
