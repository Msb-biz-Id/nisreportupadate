import { Head, Link, useForm } from '@inertiajs/react';
import { MailCheck, LogOut } from 'lucide-react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verifikasi Email" />

            <div className="space-y-6">
                {/* Intro Icon & Title */}
                <div className="text-center space-y-2">
                    <div className="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm">
                        <MailCheck className="h-6 w-6 stroke-[1.8]" />
                    </div>
                    <div className="space-y-1">
                        <h2 className="text-xl font-extrabold text-slate-800">
                            Verifikasi Email Anda
                        </h2>
                        <p className="text-xs text-slate-400 font-medium leading-relaxed">
                            Terima kasih telah mendaftar! Sebelum memulai, silakan verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan ke email Anda. Jika Anda tidak menerima email tersebut, kami dengan senang hati akan mengirimkan yang baru.
                        </p>
                    </div>
                </div>

                {status === 'verification-link-sent' && (
                    <div className="rounded-2xl bg-emerald-50 border border-emerald-100 p-4 text-xs font-semibold text-emerald-800 leading-relaxed shadow-sm">
                        <span className="flex items-center gap-1.5 mb-0.5">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                            Link Baru Dikirim
                        </span>
                        Link verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.
                    </div>
                )}

                <form onSubmit={submit} className="space-y-5">
                    {/* Action Buttons */}
                    <div className="flex flex-col gap-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-2xl text-white font-bold text-sm bg-black hover:bg-neutral-900 shadow-lg shadow-black/10 hover:shadow-black/20 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed transition duration-200"
                        >
                            {processing ? 'Mengirim Ulang...' : 'Kirim Ulang Email Verifikasi'}
                        </button>

                        <div className="flex justify-center">
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-800 transition duration-150"
                            >
                                <LogOut className="h-3.5 w-3.5" />
                                Keluar Aplikasi
                            </Link>
                        </div>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
