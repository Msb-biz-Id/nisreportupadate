import { Head, Link, usePage } from '@inertiajs/react';
import { ShieldCheck, ArrowRight, Layers, BarChart3, Users } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import usePublicSecurity from '@/hooks/usePublicSecurity';

export default function Welcome({ auth, canLogin }) {
    usePublicSecurity();
    const { app } = usePage().props;
    const appName = app?.name || 'ProTrack';
    return (
        <>
            <Head>
                <title>Selamat Datang</title>
                {app?.favicon_url && <link rel="icon" href={app.favicon_url} />}
            </Head>
            <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-rose-50/30">
                <header className="border-b bg-white/60 backdrop-blur">
                    <div className="container flex h-16 items-center justify-between">
                        <div className="flex items-center gap-2">
                            {app?.logo_url ? (
                                <img
                                    src={app.logo_url}
                                    alt={appName}
                                    className="h-9 w-9 rounded-lg object-contain bg-white p-1 border shadow-sm"
                                />
                            ) : (
                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                    <ShieldCheck className="h-5 w-5" />
                                </div>
                            )}
                            <div className="leading-tight">
                                <div className="text-sm font-semibold">{appName}</div>
                                <div className="text-[11px] text-muted-foreground">Multi-Brand Order Management</div>
                            </div>
                        </div>
                        {canLogin && (
                            <div className="flex items-center gap-2">
                                {auth?.user ? (
                                    <Button asChild>
                                        <Link href={route('dashboard')}>
                                            Dashboard <ArrowRight className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button asChild>
                                        <Link href={route('login')}>
                                            Masuk <ArrowRight className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                </header>

                <main className="container py-16 sm:py-24">
                    <div className="mx-auto max-w-3xl text-center">
                        <span className="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary">
                            Phase 1 — Foundation
                        </span>
                        <h1 className="mt-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                            Sistem Manajemen Order Apparel <br className="hidden sm:block" />
                            <span className="bg-gradient-to-r from-primary to-rose-700 bg-clip-text text-transparent">
                                multi-brand, terisolasi, modern.
                            </span>
                        </h1>
                        <p className="mx-auto mt-6 max-w-2xl text-base text-muted-foreground sm:text-lg">
                            {appName} mengelola order, produksi, keuangan, dan laporan untuk multiple brand
                            apparel/jersey dengan isolasi data, RBAC, dan tracking publik per PO.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                            <Button asChild size="lg">
                                <Link href={auth?.user ? route('dashboard') : route('login')}>
                                    {auth?.user ? 'Buka Dashboard' : 'Masuk ke Sistem'}
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline">
                                <a href="#fitur">Lihat Fitur</a>
                            </Button>
                        </div>
                    </div>

                    <div id="fitur" className="mt-20 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {[
                            {
                                icon: Layers,
                                title: 'Multi-Brand Isolation',
                                desc: 'Master data, order, dan laporan terisolasi per brand. Superadmin punya view global lintas brand.',
                            },
                            {
                                icon: Users,
                                title: '6 Role Terkontrol',
                                desc: 'Superadmin, Owner, Admin Brand, Reseller, Admin Produksi, Admin Keuangan. Permission per fitur.',
                            },
                            {
                                icon: BarChart3,
                                title: 'Dashboard & Laporan',
                                desc: 'Analisa master data: produk terlaris, pelanggan aktif, wilayah, promo, kategori, sumber order.',
                            },
                        ].map(({ icon: Icon, title, desc }) => (
                            <div key={title} className="rounded-2xl border bg-card p-6 shadow-sm">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <Icon className="h-5 w-5" />
                                </div>
                                <h3 className="mt-4 font-semibold">{title}</h3>
                                <p className="mt-1 text-sm text-muted-foreground">{desc}</p>
                            </div>
                        ))}
                    </div>
                </main>

                <footer className="border-t bg-white/60 py-6 text-center text-xs text-muted-foreground">
                    {appName} — versi pengembangan Phase 1
                </footer>
            </div>
        </>
    );
}
