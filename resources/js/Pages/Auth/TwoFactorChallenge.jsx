import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { ShieldCheck } from 'lucide-react';

export default function TwoFactorChallenge() {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    function submit(e) {
        e.preventDefault();
        post(route('two-factor.challenge.store'));
    }

    return (
        <GuestLayout>
            <Head title="Verifikasi 2FA" />

            <div className="mb-6 flex flex-col items-center gap-2 text-center">
                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                    <ShieldCheck className="h-6 w-6 text-primary" />
                </div>
                <h2 className="text-lg font-semibold">Verifikasi Dua Langkah</h2>
                <p className="text-sm text-muted-foreground">
                    Masukkan kode 6 digit dari aplikasi Google Authenticator.
                </p>
            </div>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="code" value="Kode OTP" />
                    <TextInput
                        id="code"
                        type="text"
                        inputMode="numeric"
                        pattern="[0-9]{6}"
                        maxLength={6}
                        className="mt-1 block w-full tracking-widest text-center text-xl"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                        autoFocus
                        autoComplete="one-time-code"
                        placeholder="000000"
                    />
                    <InputError message={errors.code} className="mt-2" />
                </div>

                <div className="mt-6">
                    <PrimaryButton className="w-full justify-center" disabled={processing || data.code.length !== 6}>
                        Verifikasi
                    </PrimaryButton>
                </div>

                <p className="mt-4 text-center text-xs text-muted-foreground">
                    Tidak bisa mengakses aplikasi authenticator?{' '}
                    <a href={route('login')} className="text-primary underline hover:no-underline">
                        Kembali ke login
                    </a>
                </p>
            </form>
        </GuestLayout>
    );
}
