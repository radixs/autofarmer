import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: 'resources/js/tests/setup.js',
        css: true,
        pool: 'forks',
        poolOptions: {
            forks: {
                singleFork: true,
            },
        },
    },
});
