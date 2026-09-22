import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    server: {
        cors: true,
        host: true,
        hmr: {
            host: 'localhost',
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    build: {
        emptyOutDir: true,
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                /**
                 * Chunk strategy:
                 *  - chunk-charts : Recharts + D3 helpers (only needed on dashboard/report pages)
                 *  - vendor       : all other node_modules (React, Radix, Lucide, Inertia, Axios, etc.)
                 *
                 * Note: finer splitting (per-framework, per-ui-lib) causes circular chunk warnings
                 * because React, Radix UI, and Lucide have cross-package internal imports.
                 * Per-page code splitting (the real perf win) is handled by { eager: false } in app.jsx.
                 */
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;

                    // Heavy visualisation libs — only needed on dashboard/report pages
                    if (
                        id.includes('recharts') ||
                        id.includes('/d3-') ||
                        id.includes('d3/') ||
                        id.includes('victory')
                    ) {
                        return 'chunk-charts';
                    }

                    // Everything else: stable vendor chunk (React, Radix, Lucide, Inertia…)
                    return 'vendor';
                },
            },
        },
    },
});
