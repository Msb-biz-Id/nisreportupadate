import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
import { ShieldCheck, ShieldOff, QrCode, Loader2 } from 'lucide-react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function TwoFactorForm({ twoFactorEnabled, status, className }) {
    const [setupState, setSetupState] = useState(null); // null | {secret, qr_svg}
    const [loading, setLoading] = useState(false);

    const enableForm = useForm({ code: '' });
    const disableForm = useForm({ code: '' });

    async function startSetup() {
        setLoading(true);
        try {
            const { data } = await axios.get(route('two-factor.setup'));
            setSetupState(data);
        } finally {
            setLoading(false);
        }
    }

    function submitEnable(e) {
        e.preventDefault();
        enableForm.post(route('two-factor.enable'), {
            onSuccess: () => { setSetupState(null); enableForm.reset(); },
        });
    }

    function submitDisable(e) {
        e.preventDefault();
        disableForm.post(route('two-factor.disable'), {
            onSuccess: () => disableForm.reset(),
        });
    }

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">Autentikasi Dua Langkah (2FA)</h2>
                <p className="mt-1 text-sm text-gray-600">
                    Tambahkan lapisan keamanan ekstra dengan Google Authenticator.
                </p>
            </header>

            {status === '2fa-enabled' && (
                <div className="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
                    2FA berhasil diaktifkan.
                </div>
            )}
            {status === '2fa-disabled' && (
                <div className="mt-4 rounded-md bg-yellow-50 p-3 text-sm text-yellow-700">
                    2FA berhasil dinonaktifkan.
                </div>
            )}

            {twoFactorEnabled ? (
                /* === 2FA AKTIF: form disable === */
                <div className="mt-6 space-y-4">
                    <div className="flex items-center gap-2 text-green-700">
                        <ShieldCheck className="h-5 w-5" />
                        <span className="text-sm font-medium">2FA aktif di akun ini.</span>
                    </div>
                    <form onSubmit={submitDisable} className="space-y-3">
                        <div>
                            <InputLabel htmlFor="disable_code" value="Masukkan kode OTP untuk menonaktifkan" />
                            <TextInput
                                id="disable_code"
                                type="text"
                                inputMode="numeric"
                                maxLength={6}
                                className="mt-1 block w-48 tracking-widest"
                                value={disableForm.data.code}
                                onChange={(e) => disableForm.setData('code', e.target.value.replace(/\D/g, ''))}
                                placeholder="000000"
                            />
                            <InputError message={disableForm.errors.code} className="mt-1" />
                        </div>
                        <button
                            type="submit"
                            disabled={disableForm.processing || disableForm.data.code.length !== 6}
                            className="inline-flex items-center gap-2 rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:opacity-50"
                        >
                            <ShieldOff className="h-4 w-4" />
                            Nonaktifkan 2FA
                        </button>
                    </form>
                </div>
            ) : setupState ? (
                /* === SETUP: scan QR + verify === */
                <div className="mt-6 space-y-5">
                    <p className="text-sm text-gray-600">
                        Scan QR code ini dengan <strong>Google Authenticator</strong> atau aplikasi TOTP lainnya.
                    </p>
                    <div className="inline-block rounded-lg border p-3 bg-white">
                        <img
                            src={`data:image/svg+xml;base64,${setupState.qr_svg}`}
                            alt="QR Code 2FA"
                            width={200}
                            height={200}
                        />
                    </div>
                    <div>
                        <p className="text-xs text-gray-500 mb-1">Atau masukkan secret key manual:</p>
                        <code className="rounded bg-gray-100 px-2 py-1 text-sm font-mono tracking-widest">
                            {setupState.secret}
                        </code>
                    </div>
                    <form onSubmit={submitEnable} className="space-y-3">
                        <div>
                            <InputLabel htmlFor="enable_code" value="Konfirmasi dengan kode OTP" />
                            <TextInput
                                id="enable_code"
                                type="text"
                                inputMode="numeric"
                                maxLength={6}
                                className="mt-1 block w-48 tracking-widest"
                                value={enableForm.data.code}
                                onChange={(e) => enableForm.setData('code', e.target.value.replace(/\D/g, ''))}
                                placeholder="000000"
                                autoFocus
                            />
                            <InputError message={enableForm.errors.code} className="mt-1" />
                        </div>
                        <div className="flex gap-3">
                            <PrimaryButton disabled={enableForm.processing || enableForm.data.code.length !== 6}>
                                Aktifkan 2FA
                            </PrimaryButton>
                            <button
                                type="button"
                                onClick={() => setSetupState(null)}
                                className="text-sm text-gray-500 hover:text-gray-700 underline"
                            >
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            ) : (
                /* === BELUM SETUP === */
                <div className="mt-6">
                    <p className="text-sm text-gray-600 mb-4">2FA belum diaktifkan di akun ini.</p>
                    <button
                        type="button"
                        onClick={startSetup}
                        disabled={loading}
                        className="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-60"
                    >
                        {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <QrCode className="h-4 w-4" />}
                        Setup 2FA
                    </button>
                </div>
            )}
        </section>
    );
}
