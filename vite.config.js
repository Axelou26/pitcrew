import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';
import path from 'path';

export default defineConfig({
    plugins: [
        symfonyPlugin(),
    ],
    build: {
        rollupOptions: {
            input: {
                app: './assets/app.js'
            },
        }
    },
    resolve: {
        alias: {
            '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
            '@': path.resolve(__dirname, './assets')
        }
    },
    css: {
        preprocessorOptions: {
            scss: {
                additionalData: `@import "bootstrap/scss/bootstrap";`
            }
        }
    }
}); 