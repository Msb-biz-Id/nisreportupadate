import { Head, Link, useForm } from '@inertiajs/react';
import { Mail, Lock, User, ArrowRight, UserPlus } from 'lucide-react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Daftar Akun" />

            <div className="space-y-6">
                {/* Intro Icon & Title */}
                <div className="text-center space-y-2">
                    <div className="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-primary/20 shadow-sm">
                        <UserPlus className="h-6 w-6 stroke-[1.8]" />
                    </div>
                    <div className="space-y-1">
                        <h2 className="text-xl font-extrabold text-slate-800">
                            Daftar Akun Baru
                        </h2>
                        <p className="text-xs text-slate-400 font-medium">
                            Lengkapi formulir di bawah ini untuk membuat akun Anda.
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    {/* Name Input */}
                    <div className="space-y-1.5">
                        <label htmlFor="name" className="block text-xs font-bold text-slate-700">
                            Nama Lengkap
                        </label>
                        <div className="relative">
                            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <User className="h-4 w-4 text-slate-400" />
                            </div>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                value={data.name}
                                autoComplete="name"
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Nama Lengkap Anda"
                                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary focus:bg-white transition duration-200"
                                required
                                autoFocus
                            />
                        </div>
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    {/* Email Input */}
                    <div className="space-y-1.5">
                        <label htmlFor="email" className="block text-xs font-bold text-slate-700">
                            Alamat Email
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

                    {/* Password Input */}
                    <div className="space-y-1.5">
                        <label htmlFor="password" className="block text-xs font-bold text-slate-700">
                            Kata Sandi
                        </label>
                        <div className="relative">
                            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <Lock className="h-4 w-4 text-slate-400" />
                            </div>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                autoComplete="new-password"
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Buat kata sandi minimal 8 karakter"
                                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary focus:bg-white transition duration-200"
                                required
                            />
                        </div>
                        <InputError message={errors.password} className="mt-1" />
                    </div>

                    {/* Confirm Password Input */}
                    <div className="space-y-1.5">
                        <label htmlFor="password_confirmation" className="block text-xs font-bold text-slate-700">
                            Konfirmasi Kata Sandi
                        </label>
                        <div className="relative">
                            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <Lock className="h-4 w-4 text-slate-400" />
                            </div>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                value={data.password_confirmation}
                                autoComplete="new-password"
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                placeholder="Ulangi kata sandi Anda"
                                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary focus:bg-white transition duration-200"
                                required
                            />
                        </div>
                        <InputError message={errors.password_confirmation} className="mt-1" />
                    </div>

                    {/* Register Button */}
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-2xl text-white font-bold text-sm bg-black hover:bg-neutral-900 shadow-lg shadow-black/10 hover:shadow-black/20 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed transition duration-200"
                    >
                        {processing ? 'Mendaftarkan...' : 'Daftar Sekarang'}
                    </button>

                    {/* Login Link */}
                    <div className="pt-4 border-t border-slate-100 flex justify-center text-xs">
                        <span className="text-slate-400 font-medium mr-1">Sudah punya akun?</span>
                        <Link
                            href={route('login')}
                            className="font-bold text-primary hover:underline transition duration-150 flex items-center gap-0.5"
                        >
                            Masuk Di Sini <ArrowRight className="h-3 w-3" />
                        </Link>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
