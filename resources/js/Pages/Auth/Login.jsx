import InputError from '@/Components/InputError';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Eye, EyeOff, Lock, Mail, LogIn, AlertTriangle, HelpCircle, Info, ChevronDown, ChevronUp, ShieldCheck } from 'lucide-react';
import { useState, useEffect, useRef, useCallback } from 'react';
import { createTimeline, animate } from 'animejs';

export default function Login({ status, canResetPassword, turnstile }) {
    const { app } = usePage().props;
    const appName = app?.name || 'ProTrack';
    const logoLetter = appName.charAt(0).toUpperCase();
    const turnstileEnabled = turnstile?.enabled && turnstile?.site_key;
    const turnstileRef = useRef(null);

    const [showSplash, setShowSplash] = useState(true);

    useEffect(() => {
        if (!showSplash) return;

        const timeline = createTimeline({
            onComplete: () => {
                animate('#splash-container', {
                    opacity: 0,
                    scale: 1.15,
                    duration: 600,
                    ease: 'inOutQuad',
                    onComplete: () => {
                        setShowSplash(false);
                    }
                });
            }
        });

        // 1. Decorative background entry
        timeline.add('#splash-bg-decor', {
            opacity: [0, 0.15],
            duration: 800,
            ease: 'outQuad'
        });

        // 2. Splash ring entry and rotate
        timeline.add('#splash-ring', {
            rotate: '1.25turn',
            scale: [0.3, 1],
            opacity: [0, 1],
            duration: 1000,
            ease: 'outBack'
        }, '-=600');

        // 3. Logo scaling, rotating and fade-in
        timeline.add('#splash-logo', {
            scale: [0, 1],
            rotate: '360deg',
            opacity: [0, 1],
            duration: 800,
            ease: 'outElastic(1, .6)'
        }, '-=800');

        // 4. App Name slide up and fade-in
        timeline.add('#splash-title', {
            translateY: [25, 0],
            opacity: [0, 1],
            duration: 600,
            ease: 'outQuad'
        }, '-=400');

        // 5. Subtitle letters spacing & fade-in
        timeline.add('#splash-subtitle', {
            letterSpacing: ['0.4em', '0.15em'],
            opacity: [0, 1],
            duration: 800,
            ease: 'outQuad'
        }, '-=400');

        // 6. Pause at full display
        timeline.add('#splash-logo', {
            scale: 1.05,
            duration: 600,
            ease: 'inOutQuad'
        });
    }, []);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
        cf_turnstile_response: '',
    });

    // Load Turnstile script once when widget is enabled
    useEffect(() => {
        if (!turnstileEnabled) return;
        if (document.getElementById('cf-turnstile-script')) return;
        const script = document.createElement('script');
        script.id = 'cf-turnstile-script';
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }, [turnstileEnabled]);

    const [showPassword, setShowPassword] = useState(false);
    const [isShaking, setIsShaking] = useState(false);
    const [showHelp, setShowHelp] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
            onError: () => {
                setIsShaking(true);
                setTimeout(() => setIsShaking(false), 600);
            }
        });
    };

    return (
        <div className="flex min-h-screen">
            <Head title="Masuk Ke Sistem" />

            {showSplash && (
                <div id="splash-container" className="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950 text-white select-none overflow-hidden">
                    <div className="relative flex flex-col items-center">
                        <div id="splash-ring" className="absolute w-28 h-28 rounded-full border-2 border-primary/30 border-t-primary" />
                        <div id="splash-logo" className="w-16 h-16 rounded-2xl bg-white flex items-center justify-center font-black text-3xl shadow-2xl relative z-10 overflow-hidden">
                            {app?.logo_url ? (
                                <img src={app.logo_url} alt={appName} className="w-12 h-12 object-contain bg-white p-1" />
                            ) : (
                                <span className="text-black">{logoLetter}</span>
                            )}
                        </div>
                        <div className="mt-6 text-center">
                            <h1 id="splash-title" className="text-3xl font-black tracking-[0.2em] text-white opacity-0 uppercase">
                                {appName}
                            </h1>
                            <p id="splash-subtitle" className="text-[10px] text-white/50 tracking-widest mt-2 uppercase opacity-0">
                                Sistem Manajemen Order
                            </p>
                        </div>
                    </div>
                    <div id="splash-bg-decor" className="absolute inset-0 opacity-10 pointer-events-none">
                        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,var(--tw-gradient-stops))] from-primary/40 via-transparent to-transparent" />
                    </div>
                </div>
            )}

            <style>{`
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-6px); }
                    20%, 40%, 60%, 80% { transform: translateX(6px); }
                }
                .animate-shake {
                    animation: shake 0.5s ease-in-out;
                }
            `}</style>

            {/* Left panel */}
            <div
                className="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 text-white relative overflow-hidden bg-black"
            >
                {/* Logo */}
                <div className="relative z-10">
                    <div className="inline-flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 border border-white/20">
                        {app?.logo_url ? (
                            <img
                                src={app.logo_url}
                                alt={appName}
                                className="w-10 h-10 rounded-xl object-contain bg-white p-1"
                            />
                        ) : (
                            <div className="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg bg-primary">
                                {logoLetter}
                            </div>
                        )}
                        <span className="font-semibold tracking-widest text-sm uppercase">{appName}</span>
                    </div>
                </div>

                {/* Hero text */}
                <div className="relative z-10 space-y-6">
                    <p className="text-white/50 text-sm tracking-[0.3em] uppercase">
                        Manajemen Order Multi-Brand
                    </p>
                    <h1 className="text-5xl font-black leading-tight tracking-tight">
                        KELOLA ORDER<br />
                        APPAREL CUSTOM<br />
                        <span style={{ color: 'hsl(var(--primary))' }}>LEBIH MUDAH.</span>
                    </h1>
                    <p className="text-white/60 text-sm max-w-xs leading-relaxed">
                        Sistem manajemen order kaos, jersey, dan apparel custom untuk bisnis multi-brand Indonesia.
                    </p>
                </div>

                {/* Footer */}
                <div className="relative z-10 flex items-center justify-between text-white/40 text-xs tracking-widest uppercase">
                    <span>Solusi Apparel Industri</span>
                    <span>Ver. 1.0.0</span>
                </div>
            </div>

            {/* Right panel */}
            <div className="flex-1 flex items-center justify-center bg-gray-50 p-8">
                <div className="w-full max-w-md">
                    {/* Mobile logo */}
                    <div className="lg:hidden mb-8 flex justify-center">
                        <div className="inline-flex items-center gap-3">
                            {app?.logo_url ? (
                                <img
                                    src={app.logo_url}
                                    alt={appName}
                                    className="w-10 h-10 rounded-xl object-contain bg-white p-1 border shadow-sm"
                                />
                            ) : (
                                <div className="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg text-white bg-primary">
                                    {logoLetter}
                                </div>
                            )}
                            <span className="font-bold text-gray-800">{appName}</span>
                        </div>
                    </div>

                    <div className={`bg-white rounded-3xl shadow-xl p-8 border border-gray-100 transition-all duration-300 ${isShaking ? 'animate-shake' : ''}`}>
                        <div className="mb-8">
                            <h2 className="text-2xl font-bold text-gray-900">
                                Selamat Datang 👋
                            </h2>
                            <p className="text-gray-500 text-sm mt-1">
                                Masukkan kredensial Anda untuk melanjutkan
                            </p>
                        </div>

                        {status && (
                            <div className="mb-6 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                                {status}
                            </div>
                        )}

                        {/* Interactive Error Summary Alert */}
                        {Object.keys(errors).length > 0 && (
                            <div className="mb-6 rounded-2xl bg-rose-50 border border-rose-100 p-4 text-sm text-rose-800 flex items-start gap-3 shadow-sm animate-in fade-in slide-in-from-top-4 duration-300">
                                <AlertTriangle className="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
                                <div className="space-y-1 flex-1">
                                    <div className="font-bold text-rose-900 text-xs">Gagal Masuk Ke Sistem</div>
                                    <ul className="list-disc list-inside space-y-0.5 text-[11px] text-rose-755 leading-normal">
                                        {Object.entries(errors).map(([key, msg]) => (
                                            <li key={key}>{msg}</li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        )}

                        <form onSubmit={submit} className="space-y-5">
                            {/* Email */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                    Alamat Email
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <Mail className="h-4 w-4 text-gray-400" />
                                    </div>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        autoComplete="username"
                                        autoFocus
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder={`contoh@${appName.toLowerCase().replace(/\s+/g, '')}.local`}
                                        className="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary focus:bg-white transition"
                                    />
                                </div>
                                <InputError message={errors.email} className="mt-1.5 text-xs font-semibold" />
                            </div>

                            {/* Password */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                    Kata Sandi
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <Lock className="h-4 w-4 text-gray-400" />
                                    </div>
                                    <input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        name="password"
                                        value={data.password}
                                        autoComplete="current-password"
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="Masukkan kata sandi Anda"
                                        className="w-full pl-10 pr-11 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary focus:bg-white transition"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600"
                                    >
                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                                <InputError message={errors.password} className="mt-1.5 text-xs font-semibold" />
                            </div>

                            {/* Cloudflare Turnstile Widget */}
                            {turnstileEnabled && (
                                <div className="space-y-1.5">
                                    <div className="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                        <ShieldCheck className="h-3.5 w-3.5 text-orange-500" />
                                        Verifikasi Keamanan
                                    </div>
                                    <div
                                        ref={turnstileRef}
                                        className="cf-turnstile"
                                        data-sitekey={turnstile.site_key}
                                        data-callback={(token) => setData('cf_turnstile_response', token)}
                                        data-error-callback={() => setData('cf_turnstile_response', '')}
                                        data-theme="light"
                                        data-size="normal"
                                    />
                                    {errors.cf_turnstile_response && (
                                        <p className="text-xs text-red-600 font-semibold">{errors.cf_turnstile_response}</p>
                                    )}
                                </div>
                            )}

                            {/* Submit */}
                            <button
                                type="submit"
                                disabled={processing || (turnstileEnabled && !data.cf_turnstile_response)}
                                className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-white font-semibold text-sm transition disabled:opacity-60 disabled:cursor-not-allowed shadow-md hover:-translate-y-0.5 active:translate-y-0 duration-150 bg-black hover:bg-neutral-900"
                            >
                                <LogIn className="h-4 w-4" />
                                {processing ? 'Memproses...' : 'Masuk'}
                            </button>

                            {/* Bottom Help & Forgot Password Actions */}
                            <div className="pt-2 flex flex-col items-center gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowHelp(!showHelp)}
                                    className="text-xs font-bold tracking-wide text-slate-500 hover:text-slate-800 transition flex items-center gap-1"
                                >
                                    Bantuan Masuk & Demo Akun {showHelp ? <ChevronUp className="h-3.5 w-3.5" /> : <ChevronDown className="h-3.5 w-3.5" />}
                                </button>
                                
                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-xs font-semibold tracking-widest uppercase text-primary hover:opacity-85 transition"
                                    >
                                        Kesulitan masuk? Lupa kata sandi
                                    </Link>
                                )}
                            </div>

                            {/* Demo Credentials Drawer Panel */}
                            {showHelp && (
                                <div className="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-[11px] text-slate-600 space-y-2.5 animate-in fade-in slide-in-from-top-2 duration-200">
                                    <div className="font-bold text-slate-850 flex items-center gap-1.5">
                                        <Info className="h-4 w-4 text-primary" />
                                        Panduan Masuk & Reset Password
                                    </div>
                                    <p className="leading-relaxed">
                                        Jika Anda lupa kata sandi Anda, silakan hubungi <strong>Administrator IT / Owner</strong> untuk mengatur ulang kata sandi Anda melalui Menu Pengaturan User.
                                    </p>
                                    
                                    <div className="pt-2 border-t border-slate-200 space-y-2">
                                        <span className="font-bold text-slate-700 block">Kredensial Akun Default (Dev/Seeded):</span>
                                        <div className="max-h-60 overflow-y-auto space-y-1.5 pr-1">
                                            {[
                                                { role: 'Superadmin', email: 'itidwarehouse@gmail.com' },
                                                { role: 'Owner', email: 'owner@nisreport.local' },
                                                { role: 'Keuangan', email: 'keuangan.nisgroup@gmail.com' },
                                                { role: 'Finance', email: 'finance.nisgroup@gmail.com' },
                                                { role: 'PIC Produksi', email: 'produksi.nisgroup@gmail.com' },
                                                { role: 'Admin Produksi', email: 'adminproduksi.nisgroup@gmail.com' },
                                                { role: 'Supervisor', email: 'supervisor.nisgroup@gmail.com' },
                                                { role: 'Admin Brand ALG', email: 'allegiant.id@gmail.com' },
                                                { role: 'Admin Brand CRL', email: 'circlesportwear@gmail.com' },
                                                { role: 'Admin Brand DRV', email: 'sportweardrive@gmail.com' },
                                                { role: 'Admin Reseller IDW', email: 'indonesiasportwarehouse@gmail.com' }
                                            ].map((account) => (
                                                <div key={account.email} className="bg-white p-2 rounded-xl border border-slate-100 flex justify-between items-center shadow-sm">
                                                    <div className="flex flex-col min-w-0">
                                                        <span className="font-bold text-[10px] text-slate-800">{account.role}</span>
                                                        <span className="font-mono text-slate-500 text-[10px] truncate max-w-[200px]">{account.email}</span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setData(d => ({ ...d, email: account.email, password: 'password' }));
                                                            setShowHelp(false);
                                                        }}
                                                        className="text-[10px] bg-slate-900 hover:bg-black text-white px-2 py-1 rounded-md font-bold transition shrink-0 ml-2"
                                                    >
                                                        Gunakan
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </form>
                    </div>

                    <p className="text-center text-xs text-gray-400 mt-6">
                        © {new Date().getFullYear()} — {appName}
                    </p>
                </div>
            </div>
        </div>
    );
}
