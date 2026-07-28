import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { toast } from 'sonner';

// Prevent WAF/firewall blocks from displaying as full-screen modal overlays, avoiding data loss
router.on('invalid', (event) => {
    event.preventDefault();
    console.error('Inertia invalid response intercepted:', event.detail.response);
    toast.error('Keamanan server (WAF) mendeteksi aktivitas tidak biasa atau koneksi terputus. Silakan sesuaikan data atau coba Simpan kembali.');
});

const appName = import.meta.env.VITE_APP_NAME || 'ProTrack';

createInertiaApp({
    title: (title) => {
        const dynamicAppName = typeof document !== 'undefined' ? document.querySelector('meta[name="app-name"]')?.getAttribute('content') : null;
        return `${title} - ${dynamicAppName || appName}`;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
