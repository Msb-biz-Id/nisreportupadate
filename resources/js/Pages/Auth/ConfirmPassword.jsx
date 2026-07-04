import { Head, useForm } from '@inertiajs/react';
import { Lock, ShieldAlert } from 'lucide-react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Konfirmasi Kata Sandi" />

            <div className="space-y-6">
                {/* Intro Icon & Title */}
                <div className="text-center space-y-2">
                    <div className="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 shadow-sm">
                        <ShieldAlert className="h-6 w-6 stroke-[1.8]" />
                    </div>
                    <div className="space-y-1">
                        <h2 className="text-xl font-extrabold text-slate-800">
                            Konfirmasi Kata Sandi
                        </h2>
                        <p className="text-xs text-slate-400 font-medium max-w-[285px] mx-auto leading-normal">
                            Ini adalah area aplikasi yang aman. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    {/* Password Input */}
                    <div className="space-y-1.5">
                        <label htmlFor="password" className="block text-xs font-bold text-slate-700">
                            Kata Sandi Anda
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
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Masukkan kata sandi Anda"
                                className="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary focus:bg-white transition duration-200"
                                required
                                autoFocus
                            />
                        </div>
                        <InputError message={errors.password} className="mt-1" />
                    </div>

                    {/* Action Button */}
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-2xl text-white font-bold text-sm bg-black hover:bg-neutral-900 shadow-lg shadow-black/10 hover:shadow-black/20 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed transition duration-200"
                    >
                        {processing ? 'Memproses...' : 'Konfirmasi Kata Sandi'}
                    </button>
                </form>
            </div>
        </GuestLayout>
    );
}
