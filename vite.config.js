import { defineConfig } from 'vite';
import path from 'path';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import Components from 'unplugin-vue-components/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        Components({
            resolvers: [PrimeVueResolver()]
        })
    ],
    resolve: {
        alias: [
            {
                find: 'primevue/button/style',
                replacement: path.resolve(__dirname, 'node_modules/primevue/button/style'),
            },
            {
                find: 'primevue/button-original',
                replacement: path.resolve(__dirname, 'node_modules/primevue/button'),
            },
            {
                find: 'primevue/button',
                replacement: path.resolve(__dirname, 'resources/js/Components/PrimeButtonSingleClickWrapper.js'),
            },
        ]
    },
});
