import { Head, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Mail, Loader2 } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { toast } from 'sonner';

export default function OtpChallenge() {
    const { status } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({ code: '' });
    const [resending, setResending] = useState(false);
    const [countdown, setCountdown] = useState(0);

    // Show toast when OTP is resent
    useEffect(() => {
        if (status === 'otp-resent') {
            toast.success('Kode OTP baru telah dikirim ke email Anda.');
        }
    }, [status]);

    // Handle resend countdown
    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [countdown]);

    function submit(e) {
        e.preventDefault();
        post(route('otp.challenge.store'));
    }

    function resendOtp(e) {
        e.preventDefault();
        if (countdown > 0 || resending) return;

        setResending(true);
        router.post(route('otp.challenge.resend'), {}, {
            onFinish: () => {
                setResending(false);
                setCountdown(60); // 60 seconds cooldown
            }
        });
    }

    return (
        <GuestLayout>
            <Head title="Verifikasi OTP Email" />

            <div className="mb-6 flex flex-col items-center gap-2 text-center">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/10">
                    <Mail className="h-6 w-6 text-blue-500" />
                </div>
                <h2 className="text-lg font-semibold text-slate-900">Verifikasi OTP Email</h2>
                <p className="text-sm text-slate-500">
                    Masukkan 6 digit kode keamanan yang kami kirimkan ke alamat email Anda.
                </p>
            </div>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="code" value="Kode Keamanan" className="text-slate-700" />
                    <TextInput
                        id="code"
                        type="text"
                        inputMode="numeric"
                        pattern="[0-9]{6}"
                        maxLength={6}
                        className="mt-2 block w-full tracking-[0.75em] text-center text-2xl font-bold font-mono text-slate-900 border-slate-200 focus:border-blue-500 focus:ring-blue-500"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                        autoFocus
                        autoComplete="one-time-code"
                        placeholder="000000"
                    />
                    <InputError message={errors.code} className="mt-2" />
                </div>

                <div className="mt-6">
                    <PrimaryButton className="w-full justify-center py-2.5" disabled={processing || data.code.length !== 6}>
                        {processing ? 'Memverifikasi...' : 'Verifikasi'}
                    </PrimaryButton>
                </div>

                <div className="mt-6 text-center text-sm">
                    <span className="text-slate-500">Tidak menerima kode? </span>
                    {countdown > 0 ? (
                        <span className="text-slate-600 font-semibold">Kirim ulang dalam {countdown}s</span>
                    ) : (
                        <button
                            type="button"
                            onClick={resendOtp}
                            disabled={resending}
                            className="text-blue-600 hover:text-blue-700 hover:underline font-semibold focus:outline-none inline-flex items-center gap-1.5"
                        >
                            {resending && <Loader2 className="h-3.5 w-3.5 animate-spin" />}
                            Kirim Ulang
                        </button>
                    )}
                </div>

                <p className="mt-6 text-center text-xs">
                    <a href={route('login')} className="text-slate-500 hover:text-slate-700 underline hover:no-underline">
                        Kembali ke login
                    </a>
                </p>
            </form>
        </GuestLayout>
    );
}
