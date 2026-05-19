import { Head, router, useForm } from '@inertiajs/react';
import { Sparkles, MessageCircle, Send, Settings, CheckCircle2, AlertTriangle, FlaskConical } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Separator } from '@/Components/ui/separator';

function StatusBadge({ ok }) {
    return ok
        ? <Badge variant="success"><CheckCircle2 className="mr-1 h-3 w-3" /> Terkonfigurasi</Badge>
        : <Badge variant="warning"><AlertTriangle className="mr-1 h-3 w-3" /> Mock Mode</Badge>;
}

function AiSection({ ai }) {
    const { data, setData, put, processing } = useForm({
        gemini_api_keys: '',
        model: ai.model,
        temperature: ai.temperature,
        max_tokens: ai.max_tokens,
    });

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.ai'), { preserveScroll: true });
    }

    function testConnection() {
        router.post(route('settings.integrasi.test.ai'), {}, { preserveScroll: true });
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-2">
                <div>
                    <CardTitle className="flex items-center gap-2 text-base"><Sparkles className="h-4 w-4 text-primary" /> Gemini AI</CardTitle>
                    <CardDescription>API key untuk fitur AI Tools (load-balanced, multi-key).</CardDescription>
                </div>
                <StatusBadge ok={ai.is_configured} />
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-3">
                    <div>
                        <Label>API Keys (comma-separated, satu atau banyak)</Label>
                        {ai.has_keys && (
                            <div className="mb-2 flex flex-wrap gap-1.5">
                                {ai.gemini_api_keys_masked.map((k, i) => (
                                    <Badge key={i} variant="outline" className="font-mono text-[10px]">{k}</Badge>
                                ))}
                            </div>
                        )}
                        <Textarea
                            value={data.gemini_api_keys}
                            onChange={(e) => setData('gemini_api_keys', e.target.value)}
                            rows={3}
                            placeholder={ai.has_keys ? "Isi untuk mengganti, kosongkan untuk mempertahankan yang ada" : "AIzaSy... (paste API key dari Google AI Studio)"}
                            className="font-mono text-xs"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">Dapatkan free API key di <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener noreferrer" className="underline">aistudio.google.com/apikey</a></p>
                    </div>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <Label>Model</Label>
                            <Select value={data.model} onValueChange={(v) => setData('model', v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="gemini-1.5-flash">Gemini 1.5 Flash (cepat)</SelectItem>
                                    <SelectItem value="gemini-1.5-pro">Gemini 1.5 Pro (presisi)</SelectItem>
                                    <SelectItem value="gemini-2.0-flash-exp">Gemini 2.0 Flash (experimental)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Temperature (0-2)</Label>
                            <Input type="number" step="0.1" min="0" max="2" value={data.temperature} onChange={(e) => setData('temperature', Number(e.target.value))} className="mt-1.5" />
                        </div>
                        <div>
                            <Label>Max Tokens</Label>
                            <Input type="number" min="128" max="8192" value={data.max_tokens} onChange={(e) => setData('max_tokens', Number(e.target.value))} className="mt-1.5" />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Simpan</Button>
                        <Button type="button" variant="outline" onClick={testConnection}>
                            <FlaskConical className="h-4 w-4" /> Test Koneksi
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function WhatsAppSection({ wa }) {
    const { data, setData, put, processing } = useForm({
        api_url: wa.api_url || '',
        api_key: '',
        default_recipient: wa.default_recipient || '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.whatsapp'), { preserveScroll: true });
    }

    function testConnection() {
        router.post(route('settings.integrasi.test.whatsapp'), { to: data.default_recipient }, { preserveScroll: true });
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-2">
                <div>
                    <CardTitle className="flex items-center gap-2 text-base"><MessageCircle className="h-4 w-4 text-emerald-600" /> WhatsApp (Sidobe)</CardTitle>
                    <CardDescription>Gateway WhatsApp untuk notifikasi & laporan otomatis.</CardDescription>
                </div>
                <StatusBadge ok={wa.is_configured} />
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-3">
                    <div>
                        <Label>API URL</Label>
                        <Input value={data.api_url} onChange={(e) => setData('api_url', e.target.value)} placeholder="https://api.sidobe.com/v1" className="mt-1.5" />
                    </div>
                    <div>
                        <Label>API Key {wa.has_key && <Badge variant="outline" className="ml-1 font-mono text-[10px]">{wa.api_key_masked}</Badge>}</Label>
                        <Input type="password" value={data.api_key} onChange={(e) => setData('api_key', e.target.value)} placeholder={wa.has_key ? "Isi untuk mengganti" : "API key"} className="mt-1.5" />
                    </div>
                    <div>
                        <Label>Default Recipient (HP/Group)</Label>
                        <Input value={data.default_recipient} onChange={(e) => setData('default_recipient', e.target.value)} placeholder="6281234567890" className="mt-1.5" />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Simpan</Button>
                        <Button type="button" variant="outline" onClick={testConnection}>
                            <Send className="h-4 w-4" /> Test Kirim
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function TelegramSection({ tg }) {
    const { data, setData, put, processing } = useForm({
        bot_token: '',
        default_chat_id: tg.default_chat_id || '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.telegram'), { preserveScroll: true });
    }

    function testConnection() {
        router.post(route('settings.integrasi.test.telegram'), { chat_id: data.default_chat_id }, { preserveScroll: true });
    }

    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-2">
                <div>
                    <CardTitle className="flex items-center gap-2 text-base"><Send className="h-4 w-4 text-cyan-600" /> Telegram Bot</CardTitle>
                    <CardDescription>Bot Telegram untuk notifikasi & laporan otomatis.</CardDescription>
                </div>
                <StatusBadge ok={tg.is_configured} />
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-3">
                    <div>
                        <Label>Bot Token {tg.has_key && <Badge variant="outline" className="ml-1 font-mono text-[10px]">{tg.bot_token_masked}</Badge>}</Label>
                        <Input type="password" value={data.bot_token} onChange={(e) => setData('bot_token', e.target.value)} placeholder={tg.has_key ? "Isi untuk mengganti" : "Dari @BotFather"} className="mt-1.5 font-mono text-xs" />
                        <p className="mt-1 text-xs text-muted-foreground">Buat bot baru lewat <code className="rounded bg-muted px-1">@BotFather</code> di Telegram, copy token.</p>
                    </div>
                    <div>
                        <Label>Default Chat ID</Label>
                        <Input value={data.default_chat_id} onChange={(e) => setData('default_chat_id', e.target.value)} placeholder="-1001234567890" className="mt-1.5 font-mono text-xs" />
                        <p className="mt-1 text-xs text-muted-foreground">Dapatkan chat ID lewat <code className="rounded bg-muted px-1">@userinfobot</code> atau <code className="rounded bg-muted px-1">@getidsbot</code>.</p>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Simpan</Button>
                        <Button type="button" variant="outline" onClick={testConnection}>
                            <Send className="h-4 w-4" /> Test Kirim
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function SystemSection({ sys }) {
    const { data, setData, put, processing } = useForm({
        notification_channel: sys.notification_channel,
        whatsapp_enabled: !!sys.whatsapp_enabled,
        telegram_enabled: !!sys.telegram_enabled,
    });

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.system'), { preserveScroll: true });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base"><Settings className="h-4 w-4 text-violet-600" /> Pengaturan Channel</CardTitle>
                <CardDescription>Pilih channel notifikasi untuk laporan otomatis.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-3">
                    <div>
                        <Label>Channel Default</Label>
                        <Select value={data.notification_channel} onValueChange={(v) => setData('notification_channel', v)}>
                            <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="whatsapp">WhatsApp saja</SelectItem>
                                <SelectItem value="telegram">Telegram saja</SelectItem>
                                <SelectItem value="both">Keduanya (WhatsApp + Telegram)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex items-center justify-between rounded-lg border p-3">
                        <div>
                            <Label>WhatsApp Enabled</Label>
                            <p className="text-xs text-muted-foreground">Master switch untuk channel WhatsApp.</p>
                        </div>
                        <Switch checked={data.whatsapp_enabled} onCheckedChange={(v) => setData('whatsapp_enabled', v)} />
                    </div>
                    <div className="flex items-center justify-between rounded-lg border p-3">
                        <div>
                            <Label>Telegram Enabled</Label>
                            <p className="text-xs text-muted-foreground">Master switch untuk channel Telegram.</p>
                        </div>
                        <Switch checked={data.telegram_enabled} onCheckedChange={(v) => setData('telegram_enabled', v)} />
                    </div>
                    <Button type="submit" disabled={processing}>Simpan</Button>
                </form>
            </CardContent>
        </Card>
    );
}

export default function Integrations({ ai, whatsapp, telegram, system }) {
    return (
        <AppLayout title="Pengaturan Integrasi">
            <Head title="Pengaturan Integrasi" />

            <div className="space-y-5">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Pengaturan Integrasi</h1>
                    <p className="text-sm text-muted-foreground">
                        Konfigurasi AI (Gemini), WhatsApp (Sidobe), Telegram Bot, dan channel notifikasi default.
                    </p>
                </div>

                <div className="rounded-lg border bg-blue-50 p-4 text-sm">
                    <strong className="text-blue-900">💡 Catatan:</strong> Tanpa API key terisi, fitur AI/notifikasi akan jalan dalam <strong>mock mode</strong> — UI tetap berfungsi tapi tidak ada panggilan API sungguhan. Aman untuk demo dan testing.
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <AiSection ai={ai} />
                    <SystemSection sys={system} />
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <WhatsAppSection wa={whatsapp} />
                    <TelegramSection tg={telegram} />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Scheduled Reports</CardTitle>
                        <CardDescription>Laporan otomatis dikirim via channel terkonfigurasi.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div className="flex justify-between rounded border p-2">
                            <span>📊 Laporan Harian</span>
                            <Badge variant="outline">Setiap hari 08:00 WIB</Badge>
                        </div>
                        <div className="flex justify-between rounded border p-2">
                            <span>📊 Laporan Mingguan</span>
                            <Badge variant="outline">Setiap Senin 08:00 WIB</Badge>
                        </div>
                        <div className="flex justify-between rounded border p-2">
                            <span>📊 Laporan Bulanan</span>
                            <Badge variant="outline">Tanggal 1 setiap bulan 08:00 WIB</Badge>
                        </div>
                        <Separator className="my-3" />
                        <p className="text-xs text-muted-foreground">
                            Pastikan cron Laravel berjalan: <code className="rounded bg-muted px-1">* * * * * php /path/to/project/artisan schedule:run</code>. Jalankan manual via <code className="rounded bg-muted px-1">php artisan reports:send harian</code>.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
