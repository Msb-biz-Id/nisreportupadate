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
    const jsonMessage = (typeof response?.data === 'object' && response?.data !== null) ? (response.data.message || response.data.error) : null;

    if (status === 419 || status === 401) {
        toast.error('Sesi Anda telah berakhir. Silakan muat ulang halaman (F5) dan login kembali.');
    } else if (jsonMessage) {
        toast.error(jsonMessage);
    } else if (status === 406 || bodyText.includes('Imunify360') || bodyText.includes('ModSecurity') || bodyText.includes('cf-turnstile') || bodyText.includes('cf-browser-verification')) {
        toast.error('Keamanan server (WAF/ModSecurity/Imunify360) di cPanel memblokir data.');
    } else if (status === 403) {
        toast.error('Akses ditolak (Error 403) atau Anda tidak memiliki izin.');
    } else if (status === 404) {
        toast.error('Halaman atau data tidak ditemukan (Error 404).');
    } else if (status === 500) {
        toast.error('Terjadi kesalahan internal pada server (Error 500). Silakan periksa log server.');
    } else {
        toast.error(`Respon server tidak valid (${status || 'Koneksi terputus'}). Silakan muat ulang halaman (F5).`);
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
