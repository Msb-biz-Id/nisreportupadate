import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { toast } from 'sonner';

// Prevent WAF/firewall blocks or server errors from displaying as full-screen modal overlays, avoiding data loss
router.on('invalid', (event) => {
    event.preventDefault();
    const response = event.detail.response;
    console.error('Inertia invalid response intercepted:', response);

    const status = response?.status;
    const bodyText = (typeof response?.data === 'string') ? response.data : '';

    if (status === 500) {
        toast.error('Terjadi kesalahan internal pada server (Error 500). Silakan hubungi Developer.');
    } else if (status === 403 || status === 406 || bodyText.includes('verifikasi') || bodyText.includes('Imunify') || bodyText.includes('shield') || bodyText.includes('Turnstile')) {
        toast.error('Keamanan server (WAF/Imunify360) mendeteksi payload tidak biasa. Data Anda aman di layar, silakan coba klik Simpan kembali.');
    } else {
        toast.error('Koneksi terputus atau respon server tidak valid. Silakan coba kembali.');
    }
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
