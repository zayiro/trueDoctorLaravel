import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/nearby-doctors.js', 'resources/js/nearby-landing.js'],
            refresh: true,
        }),
    ],
});
