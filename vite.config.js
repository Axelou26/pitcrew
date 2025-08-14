import { defineConfig } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';
import path from 'path';

export default defineConfig({
    plugins: [
        symfonyPlugin(),
    ],
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        manifest: true,
        rollupOptions: {
            input: {
                app: './assets/app.js',
                main: './assets/main.js'
            },
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]'
            }
        }
    },
    optimizeDeps: {
        include: ['bootstrap']
    },
    server: {
        port: 5173,
        host: '0.0.0.0',
        strictPort: true,
        hmr: {
            host: 'localhost'
        }
    },
    resolve: {
        alias: {
            '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
            '@': path.resolve(__dirname, './assets')
        }
    },
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                includePaths: ['node_modules']
            }
        }
    }
}); 