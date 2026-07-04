import InputError from '@/Components/InputError';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Eye, EyeOff, Lock, Mail, LogIn, AlertTriangle, HelpCircle, Info, ChevronDown, ChevronUp } from 'lucide-react';
import { useState } from 'react';

export default function Login({ status, canResetPassword }) {
    const { app } = usePage().props;
    const appName = app?.name || 'ProTrack';
    const logoLetter = appName.charAt(0).toUpperCase();

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

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

                            {/* Submit */}
                            <button
                                type="submit"
                                disabled={processing}
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
                                        <div className="space-y-1.5">
                                            <div className="bg-white p-2 rounded-xl border border-slate-100 flex justify-between items-center shadow-sm">
                                                <div className="flex flex-col">
                                                    <span className="font-bold text-[10px] text-slate-800">Superadmin (Semua Brand)</span>
                                                    <span className="font-mono text-slate-500 text-[10px]">superadmin@nisreport.local</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setData(d => ({ ...d, email: 'superadmin@nisreport.local', password: 'password' }));
                                                        setShowHelp(false);
                                                    }}
                                                    className="text-[10px] bg-slate-900 hover:bg-black text-white px-2 py-1 rounded-md font-bold transition"
                                                >
                                                    Gunakan
                                                </button>
                                            </div>
                                            <div className="bg-white p-2 rounded-xl border border-slate-100 flex justify-between items-center shadow-sm">
                                                <div className="flex flex-col">
                                                    <span className="font-bold text-[10px] text-slate-800">Owner (Brand SHU + NIS)</span>
                                                    <span className="font-mono text-slate-500 text-[10px]">owner@nisreport.local</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setData(d => ({ ...d, email: 'owner@nisreport.local', password: 'password' }));
                                                        setShowHelp(false);
                                                    }}
                                                    className="text-[10px] bg-slate-900 hover:bg-black text-white px-2 py-1 rounded-md font-bold transition"
                                                >
                                                    Gunakan
                                                </button>
                                            </div>
                                            <div className="bg-white p-2 rounded-xl border border-slate-100 flex justify-between items-center shadow-sm">
                                                <div className="flex flex-col">
                                                    <span className="font-bold text-[10px] text-slate-800">Keuangan (Brand SHU + NIS)</span>
                                                    <span className="font-mono text-slate-500 text-[10px]">keuangan@nisreport.local</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setData(d => ({ ...d, email: 'keuangan@nisreport.local', password: 'password' }));
                                                        setShowHelp(false);
                                                    }}
                                                    className="text-[10px] bg-slate-900 hover:bg-black text-white px-2 py-1 rounded-md font-bold transition"
                                                >
                                                    Gunakan
                                                </button>
                                            </div>
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
