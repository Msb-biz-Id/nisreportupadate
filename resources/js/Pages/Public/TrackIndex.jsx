import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Search, ShieldCheck, MapPin, Truck, Calendar, Clock, ArrowRight } from 'lucide-react';
import usePublicSecurity from '@/hooks/usePublicSecurity';

export default function TrackIndex() {
    usePublicSecurity();
    const { app } = usePage().props;
    const appName = app?.name || 'ProTrack';
    const [noPo, setNoPo] = useState('');
    const [error, setError] = useState('');

    const handleSearch = (e) => {
        e.preventDefault();
        const trimmed = noPo.trim();
        if (!trimmed) {
            setError('Silakan masukkan nomor PO Anda');
            return;
        }
        setError('');
        router.visit(route('track.show', { noPo: trimmed }));
    };

    return (
        <>
            <Head>
                <title>Lacak Progress Pesanan</title>
                {app?.favicon_url && <link rel="icon" href={app.favicon_url} />}
            </Head>
            <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-red-50/20 px-4 py-12 sm:py-20 flex flex-col justify-center items-center relative overflow-hidden font-sans">
                {/* Decorative background glows */}
                <div className="absolute top-1/4 left-1/4 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-red-100/30 rounded-full blur-3xl pointer-events-none" />
                <div className="absolute bottom-1/4 right-1/4 translate-x-1/2 translate-y-1/2 w-96 h-96 bg-slate-100/40 rounded-full blur-3xl pointer-events-none" />

                <div className="w-full max-w-xl z-10 space-y-6">
                    {/* Header: Pure White-labeled, Elegant & Clean */}
                    <div className="flex flex-col items-center text-center space-y-2">
                        {app?.logo_url ? (
                            <img
                                src={app.logo_url}
                                alt={appName}
                                className="h-11 w-11 rounded-2xl object-contain bg-white p-1.5 border border-slate-100 shadow-md"
                            />
                        ) : (
                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-red-600 text-white shadow-md shadow-red-600/10 border border-red-500/20">
                                <ShieldCheck className="h-5.5 w-5.5 stroke-[2]" />
                            </div>
                        )}
                        <div className="space-y-0.5">
                            <h1 className="text-xl sm:text-2xl font-black tracking-tight text-slate-800">
                                Lacak Progress Pesanan
                            </h1>
                            <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                Portal Pelacakan PO Mandiri
                            </p>
                        </div>
                    </div>

                    {/* Search Card */}
                    <div className="rounded-3xl border border-slate-100 bg-white p-5 sm:p-6 shadow-xl shadow-slate-100/80 space-y-5">
                        <div className="space-y-1.5">
                            <h2 className="text-sm font-bold text-slate-800">Masukkan Nomor PO Anda</h2>
                            <p className="text-xs text-slate-500 leading-relaxed">
                                Silakan masukkan nomor Purchase Order (PO) lengkap yang Anda terima untuk melihat detail perkembangan produksi secara real-time.
                            </p>
                        </div>

                        <form onSubmit={handleSearch} className="space-y-3">
                            {/* Premium Responsive Search & Button Layout */}
                            <div className="flex flex-col sm:flex-row gap-3">
                                {/* Search Input Container */}
                                <div className="h-12 relative flex items-center bg-slate-50/70 border border-slate-200 focus-within:border-red-500 focus-within:ring-4 focus-within:ring-red-500/10 rounded-2xl px-3 transition-all duration-200 flex-1">
                                    <Search className="h-4.5 w-4.5 text-slate-400 shrink-0" />
                                    <input
                                        type="text"
                                        placeholder="Contoh: PO-CRL-POPTMAJUBERS-001"
                                        value={noPo}
                                        onChange={(e) => setNoPo(e.target.value)}
                                        style={{ border: 'none', outline: 'none', boxShadow: 'none' }}
                                        className="flex-1 min-w-0 bg-transparent text-slate-800 placeholder-slate-400 text-sm font-mono tracking-wide focus:ring-0 focus:outline-none focus:border-none px-3 py-1"
                                    />
                                </div>
                                
                                {/* Track Button */}
                                <button
                                    type="submit"
                                    className="h-12 px-6 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold rounded-2xl shadow-md shadow-red-600/10 transition-all hover:shadow-red-600/20 hover:-translate-y-0.5 active:translate-y-0 duration-200 flex items-center justify-center gap-1.5 text-xs font-semibold whitespace-nowrap w-full sm:w-auto"
                                >
                                    Lacak PO
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </button>
                            </div>

                            {error && (
                                <p className="text-xs font-semibold text-red-500 flex items-center gap-1.5 pl-1 animate-pulse">
                                    <span className="w-1.5 h-1.5 rounded-full bg-red-500" />
                                    {error}
                                </p>
                            )}
                        </form>
                    </div>

                    {/* Features/Info Cards - Horizontal Row Style */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div className="rounded-2xl border border-slate-100 bg-white p-4 flex items-start gap-3 shadow-sm shadow-slate-100/50">
                            <div className="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100/30 shrink-0">
                                <Clock className="h-4 w-4" />
                            </div>
                            <div className="space-y-0.5 text-left">
                                <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Real-time</h3>
                                <p className="text-[10px] text-slate-400 leading-normal font-medium">
                                    Pantau langsung status pengerjaan pesanan.
                                </p>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-100 bg-white p-4 flex items-start gap-3 shadow-sm shadow-slate-100/50">
                            <div className="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-100/30 shrink-0">
                                <Calendar className="h-4 w-4" />
                            </div>
                            <div className="space-y-0.5 text-left">
                                <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Estimasi</h3>
                                <p className="text-[10px] text-slate-400 leading-normal font-medium">
                                    Informasi perkiraan tanggal selesai berkala.
                                </p>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-100 bg-white p-4 flex items-start gap-3 shadow-sm shadow-slate-100/50">
                            <div className="p-2 rounded-xl bg-violet-50 text-violet-600 border border-violet-100/30 shrink-0">
                                <Truck className="h-4 w-4" />
                            </div>
                            <div className="space-y-0.5 text-left">
                                <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Ekspedisi</h3>
                                <p className="text-[10px] text-slate-400 leading-normal font-medium">
                                    Lacak nomor resi setelah pesanan dikirim.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
