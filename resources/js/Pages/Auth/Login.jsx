import InputError from '@/Components/InputError';
import { Head, Link, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Lock, Mail, LogIn } from 'lucide-react';
import { useState } from 'react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <div className="flex min-h-screen">
            <Head title="Masuk" />

            {/* Left panel */}
            <div
                className="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 text-white relative overflow-hidden bg-black"
            >
                {/* Logo */}
                <div className="relative z-10">
                    <div className="inline-flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 border border-white/20">
                        <div className="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg bg-primary">
                            N
                        </div>
                        <span className="font-semibold tracking-widest text-sm uppercase">NIS Report</span>
                    </div>
                </div>

                {/* Hero text */}
                <div className="relative z-10 space-y-6">
                    <p className="text-white/50 text-sm tracking-[0.3em] uppercase">
                        Multi-Brand Order Management
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
                    <span>Industrial Apparel Solution</span>
                    <span>Ver. 1.0.0</span>
                </div>
            </div>

            {/* Right panel */}
            <div className="flex-1 flex items-center justify-center bg-gray-50 p-8">
                <div className="w-full max-w-md">
                    {/* Mobile logo */}
                    <div className="lg:hidden mb-8 flex justify-center">
                        <div className="inline-flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg text-white bg-primary">
                                N
                            </div>
                            <span className="font-bold text-gray-800">NIS Report</span>
                        </div>
                    </div>

                    <div className="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
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

                        <form onSubmit={submit} className="space-y-5">
                            {/* Email */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                    Email
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
                                        placeholder="admin@nisreport.local"
                                        className="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary focus:bg-white transition"
                                    />
                                </div>
                                <InputError message={errors.email} className="mt-1.5" />
                            </div>

                            {/* Password */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                                    Password
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
                                        placeholder="••••••••••"
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
                                <InputError message={errors.password} className="mt-1.5" />
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

                            {/* Forgot password */}
                             {canResetPassword && (
                                  <div className="text-center">
                                      <Link
                                          href={route('password.request')}
                                          className="text-xs font-semibold tracking-widest uppercase text-primary hover:opacity-85 transition"
                                      >
                                          Kesulitan masuk? Lupa password
                                      </Link>
                                  </div>
                             )}
                        </form>
                    </div>

                    <p className="text-center text-xs text-gray-400 mt-6">
                        © {new Date().getFullYear()} — NIS Report
                    </p>
                </div>
            </div>
        </div>
    );
}
