import { Head, Link, useForm } from '@inertiajs/react';
import { Mail, ArrowLeft, KeyRound } from 'lucide-react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Lupa Password" />

            <div className="space-y-6">
                {/* Intro Icon & Title */}
                <div className="text-center space-y-2">
                    <div className="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-primary/20 shadow-sm">
                        <KeyRound className="h-6 w-6 stroke-[1.8]" />
                    </div>
                    <div className="space-y-1">
                        <h2 className="text-xl font-extrabold text-slate-800">
                            Lupa Password?
                        </h2>
                        <p className="text-xs text-slate-400 font-medium max-w-[280px] mx-auto leading-normal">
                            Masukkan email terdaftar Anda untuk menerima tautan pemulihan kata sandi.
                        </p>
                    </div>
                </div>

                {status && (
                    <div className="rounded-2xl bg-emerald-50 border border-emerald-100 p-4 text-xs font-semibold text-emerald-800 leading-relaxed shadow-sm">
                        <span className="flex items-center gap-1.5 mb-0.5">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                            Email Terkirim
                        </span>
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-5">
                    {/* Email Input */}
                    <div className="space-y-1.5">
                        <label htmlFor="email" className="block text-xs font-bold text-slate-700">
                            Email Terdaftar
                        </label>
                        <div className="relative">
                            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <Mail className="h-4 w-4 text-slate-400" />
                            </div>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                autoComplete="username"
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="nama@email.com"
                                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary focus:bg-white transition duration-200"
                                required
                            />
                        </div>
                        <InputError message={errors.email} className="mt-1" />
                    </div>

                    {/* Action Button */}
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-2xl text-white font-bold text-sm bg-black hover:bg-neutral-900 shadow-lg shadow-black/10 hover:shadow-black/20 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed transition duration-200"
                    >
                        {processing ? 'Mengirim...' : 'Kirim Link Reset Password'}
                    </button>

                    {/* Back to Login */}
                    <div className="pt-2 border-t border-slate-100 flex justify-center">
                        <Link
                            href={route('login')}
                            className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-primary hover:opacity-80 transition duration-150"
                        >
                            <ArrowLeft className="h-3.5 w-3.5" />
                            Kembali ke Halaman Masuk
                        </Link>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
