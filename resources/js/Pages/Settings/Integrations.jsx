import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Sparkles, MessageCircle, Send, Settings, CheckCircle2, AlertTriangle, FlaskConical, Bell, Volume2, ShieldAlert, CheckSquare, Clock, Calendar, Mail, Copy, Building2 } from 'lucide-react';
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
import { playNotificationSound } from '@/Services/audio';
import { cn } from '@/lib/utils';

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
        <Card className="shadow-md border-t-4 border-t-primary">
            <CardHeader className="flex flex-row items-start justify-between gap-2 border-b pb-4">
                <div>
                    <CardTitle className="flex items-center gap-2 text-base"><Sparkles className="h-4.5 w-4.5 text-primary" /> Gemini AI</CardTitle>
                    <CardDescription>API key untuk fitur AI Tools (load-balanced, multi-key).</CardDescription>
                </div>
                <StatusBadge ok={ai.is_configured} />
            </CardHeader>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label className="text-xs font-semibold text-gray-700">API Keys (comma-separated, satu atau banyak)</Label>
                        {ai.has_keys && (
                            <div className="mt-1.5 mb-2 flex flex-wrap gap-1.5">
                                {ai.gemini_api_keys_masked.map((k, i) => (
                                    <Badge key={i} variant="outline" className="font-mono text-[10px] bg-muted/40">{k}</Badge>
                                ))}
                            </div>
                        )}
                        <Textarea
                            value={data.gemini_api_keys}
                            onChange={(e) => setData('gemini_api_keys', e.target.value)}
                            rows={3}
                            placeholder={ai.has_keys ? "Isi untuk mengganti, kosongkan untuk mempertahankan yang ada" : "AIzaSy... (paste API key dari Google AI Studio)"}
                            className="font-mono text-xs mt-1.5"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">Dapatkan free API key di <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener noreferrer" className="underline font-medium text-primary hover:text-primary-hover">aistudio.google.com/apikey</a></p>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Model</Label>
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
                            <Label className="text-xs font-semibold text-gray-700">Temperature (0-2)</Label>
                            <Input type="number" step="0.1" min="0" max="2" value={data.temperature} onChange={(e) => setData('temperature', Number(e.target.value))} className="mt-1.5" />
                        </div>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Max Tokens</Label>
                            <Input type="number" min="128" max="8192" value={data.max_tokens} onChange={(e) => setData('max_tokens', Number(e.target.value))} className="mt-1.5" />
                        </div>
                    </div>
                    <div className="flex gap-2 pt-2 border-t">
                        <Button type="submit" disabled={processing} className="px-5">Simpan</Button>
                        <Button type="button" variant="outline" onClick={testConnection}>
                            <FlaskConical className="h-4 w-4 mr-1.5" /> Test Koneksi
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function WhatsAppSection({ wa }) {
    const { data, setData, put, processing } = useForm({
        api_url:           wa.api_url           || 'https://api.sidobe.com/wa/v1',
        api_key:           '',
        default_recipient: wa.default_recipient || '',
        sender_phone:      wa.sender_phone      || '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.whatsapp'), { preserveScroll: true });
    }

    function testConnection() {
        router.post(route('settings.integrasi.test.whatsapp'), { to: data.default_recipient }, { preserveScroll: true });
    }

    return (
        <Card className="shadow-md border-t-4 border-t-emerald-500">
            <CardHeader className="flex flex-row items-start justify-between gap-2 border-b pb-4">
                <div>
                    <CardTitle className="flex items-center gap-2 text-base"><MessageCircle className="h-4.5 w-4.5 text-emerald-600" /> WhatsApp Gateway (Sidobe)</CardTitle>
                    <CardDescription>
                        Integrasi Sidobe API v1 — notifikasi real-time, kirim invoice, & laporan otomatis.{' '}
                        <a href="https://docs.sidobe.com" target="_blank" rel="noopener noreferrer" className="text-emerald-600 hover:underline text-xs">Docs ↗</a>
                    </CardDescription>
                </div>
                <StatusBadge ok={wa.is_configured} />
            </CardHeader>
            <CardContent className="pt-6 space-y-6">
                <form onSubmit={submit} className="space-y-4">
                    {/* Auth */}
                    <div className="space-y-3">
                        <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Autentikasi Sidobe</p>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Base URL API</Label>
                            <Input value={data.api_url} onChange={(e) => setData('api_url', e.target.value)}
                                placeholder="https://api.sidobe.com/wa/v1" className="mt-1.5 font-mono text-xs" />
                            <p className="text-[10px] text-muted-foreground mt-1">Default: <code>https://api.sidobe.com/wa/v1</code></p>
                        </div>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                                Secret Key (X-Secret-Key)
                                {wa.has_key && <Badge variant="outline" className="font-mono text-[10px] bg-muted/40">{wa.api_key_masked}</Badge>}
                            </Label>
                            <Input type="password" value={data.api_key} onChange={(e) => setData('api_key', e.target.value)}
                                placeholder={wa.has_key ? "Isi untuk mengganti" : "Dari Sidobe Dashboard → Developer Tools → Credential"}
                                className="mt-1.5" />
                        </div>
                    </div>

                    {/* Nomor */}
                    <div className="space-y-3">
                        <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Nomor & Pengirim</p>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Sender Phone (Opsional)</Label>
                            <Input value={data.sender_phone} onChange={(e) => setData('sender_phone', e.target.value)}
                                placeholder="628123456789 (nomor WA yang terdaftar di Sidobe)" className="mt-1.5 font-mono text-xs" />
                            <p className="text-[10px] text-muted-foreground mt-1">Kosongkan untuk pakai device default Sidobe.</p>
                        </div>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Default Recipient (fallback)</Label>
                            <Input value={data.default_recipient} onChange={(e) => setData('default_recipient', e.target.value)}
                                placeholder="628123456789 atau Group ID" className="mt-1.5 font-mono text-xs" />
                        </div>
                    </div>

                    <div className="flex gap-2 pt-2 border-t">
                        <Button type="submit" disabled={processing} className="px-5">Simpan</Button>
                        <Button type="button" variant="outline" onClick={testConnection}>
                            <Send className="h-4 w-4 mr-1.5" /> Test Kirim
                        </Button>
                    </div>
                </form>

                {/* Webhook URL info */}
                <div className="rounded-lg bg-emerald-50 border border-emerald-200 p-3 space-y-2">
                    <p className="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                        <ShieldAlert className="h-3.5 w-3.5" /> Webhook URL — daftarkan di Sidobe Dashboard
                    </p>
                    <p className="text-[10px] text-emerald-700">
                        Sidobe Console → Developer Tools → Settings → Webhook WhatsApp → masukkan URL ini:
                    </p>
                    <div className="flex items-center gap-2">
                        <code className="flex-1 block rounded bg-emerald-100 border border-emerald-200 px-2 py-1.5 font-mono text-[11px] text-emerald-900 break-all">
                            {wa.webhook_url}
                        </code>
                        <Button type="button" size="xs" variant="outline" className="shrink-0"
                            onClick={() => navigator.clipboard.writeText(wa.webhook_url).then(() => alert('URL disalin!'))}>
                            Copy
                        </Button>
                    </div>
                    <p className="text-[10px] text-emerald-600">
                        Event yang ditangani: <code>SEND_MESSAGE_STATUS</code> — update status pengiriman invoice WA.
                    </p>
                </div>
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
        <Card className="shadow-md border-t-4 border-t-cyan-500">
            <CardHeader className="flex flex-row items-start justify-between gap-2 border-b pb-4">
                <div>
                    <CardTitle className="flex items-center gap-2 text-base"><Send className="h-4.5 w-4.5 text-cyan-600" /> Telegram Bot</CardTitle>
                    <CardDescription>Bot Telegram untuk notifikasi & laporan otomatis.</CardDescription>
                </div>
                <StatusBadge ok={tg.is_configured} />
            </CardHeader>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label className="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                            Bot Token
                            {tg.has_key && <Badge variant="outline" className="font-mono text-[10px] bg-muted/40">{tg.bot_token_masked}</Badge>}
                        </Label>
                        <Input type="password" value={data.bot_token} onChange={(e) => setData('bot_token', e.target.value)} placeholder={tg.has_key ? "Isi untuk mengganti" : "Dari @BotFather"} className="mt-1.5 font-mono text-xs" />
                        <p className="mt-1.5 text-xs text-muted-foreground">Buat bot baru lewat <code className="rounded bg-muted px-1">@BotFather</code> di Telegram, lalu salin token.</p>
                    </div>
                    <div>
                        <Label className="text-xs font-semibold text-gray-700">Default Chat ID</Label>
                        <Input value={data.default_chat_id} onChange={(e) => setData('default_chat_id', e.target.value)} placeholder="-1001234567890" className="mt-1.5 font-mono text-xs" />
                        <p className="mt-1.5 text-xs text-muted-foreground">Dapatkan chat ID lewat <code className="rounded bg-muted px-1">@userinfobot</code> atau <code className="rounded bg-muted px-1">@getidsbot</code>.</p>
                    </div>
                    <div className="flex gap-2 pt-2 border-t">
                        <Button type="submit" disabled={processing} className="px-5">Simpan</Button>
                        <Button type="button" variant="outline" onClick={testConnection}>
                            <Send className="h-4 w-4 mr-1.5" /> Test Kirim
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
        customer_import_enabled: !!sys.customer_import_enabled,
    });

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.system'), { preserveScroll: true });
    }

    return (
        <Card className="shadow-md border-t-4 border-t-violet-500">
            <CardHeader className="border-b pb-4">
                <CardTitle className="flex items-center gap-2 text-base"><Settings className="h-4.5 w-4.5 text-violet-600" /> Channel Default & Master Switches</CardTitle>
                <CardDescription>Pilih channel notifikasi default untuk laporan terjadwal.</CardDescription>
            </CardHeader>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label className="text-xs font-semibold text-gray-700">Channel Default Laporan</Label>
                        <Select value={data.notification_channel} onValueChange={(v) => setData('notification_channel', v)}>
                            <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="whatsapp">WhatsApp saja</SelectItem>
                                <SelectItem value="telegram">Telegram saja</SelectItem>
                                <SelectItem value="both">Keduanya (WhatsApp + Telegram)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex items-center justify-between rounded-lg border p-4 bg-muted/20">
                        <div>
                            <Label className="text-xs font-semibold text-gray-800">WhatsApp Master Switch</Label>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Aktifkan untuk mengizinkan notifikasi keluar via WhatsApp.</p>
                        </div>
                        <Switch checked={data.whatsapp_enabled} onCheckedChange={(v) => setData('whatsapp_enabled', v)} />
                    </div>
                    <div className="flex items-center justify-between rounded-lg border p-4 bg-muted/20">
                        <div>
                            <Label className="text-xs font-semibold text-gray-800">Telegram Master Switch</Label>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Aktifkan untuk mengizinkan notifikasi keluar via Telegram.</p>
                        </div>
                        <Switch checked={data.telegram_enabled} onCheckedChange={(v) => setData('telegram_enabled', v)} />
                    </div>
                    <div className="flex items-center justify-between rounded-lg border p-4 bg-muted/20">
                        <div>
                            <Label className="text-xs font-semibold text-gray-800">Master Data Customer Import (Web)</Label>
                            <p className="text-[11px] text-red-600 font-medium mt-0.5">⚠️ Aktifkan fitur impor CSV pelanggan secara massal via dashboard web (Hanya Superadmin).</p>
                        </div>
                        <Switch checked={data.customer_import_enabled} onCheckedChange={(v) => setData('customer_import_enabled', v)} />
                    </div>
                    <div className="pt-2 border-t">
                        <Button type="submit" disabled={processing} className="px-5">Simpan Konfigurasi</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function NotificationMatrixSection({ matrix, availableRoles }) {
    const { data, setData, put, processing } = useForm({
        matrix: { ...matrix }
    });

    const eventLabels = {
        order_published: {
            title: 'PO Baru Diterbitkan',
            desc: 'Dipicu ketika PO baru diterbitkan oleh Admin Brand.'
        },
        progress_updated: {
            title: 'Progress PO Diperbarui',
            desc: 'Dipicu ketika tahapan produksi PO diperbarui oleh Admin Produksi.'
        },
        rijek_reported: {
            title: 'Laporan Rijek Baru',
            desc: 'Dipicu ketika produk rijek dicatat pada tahapan produksi.'
        },
        refund_submitted: {
            title: 'Pengajuan Refund Dana',
            desc: 'Dipicu ketika Admin Brand mengajukan refund dana atas masalah PO.'
        },
        refund_processed: {
            title: 'Pengajuan Refund Diproses',
            desc: 'Dipicu ketika Keuangan menyetujui (publish) atau menolak refund.'
        }
    };

    const roleLabels = {
        superadmin: 'Superadmin',
        owner: 'Owner',
        admin_brand: 'Admin Brand',
        admin_reseller: 'Admin Reseller',
        admin_produksi: 'Produksi',
        admin_keuangan: 'Keuangan',
        supervisor: 'Supervisor'
    };

    function toggleChannel(eventKey, channelField, checked) {
        const updatedEvent = {
            ...data.matrix[eventKey],
            [channelField]: checked
        };
        setData('matrix', {
            ...data.matrix,
            [eventKey]: updatedEvent
        });
    }

    function toggleRole(eventKey, roleName, checked) {
        const currentRoles = data.matrix[eventKey]?.roles || [];
        let nextRoles;
        if (checked) {
            nextRoles = [...currentRoles, roleName];
        } else {
            nextRoles = currentRoles.filter(r => r !== roleName);
        }

        const updatedEvent = {
            ...data.matrix[eventKey],
            roles: nextRoles
        };
        setData('matrix', {
            ...data.matrix,
            [eventKey]: updatedEvent
        });
    }

    function setSound(eventKey, soundName) {
        const updatedEvent = {
            ...data.matrix[eventKey],
            sound: soundName
        };
        setData('matrix', {
            ...data.matrix,
            [eventKey]: updatedEvent
        });

        playNotificationSound(soundName);
    }

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.matrix'), { preserveScroll: true });
    }

    return (
        <Card className="shadow-md border-t-4 border-t-primary">
            <CardHeader className="flex flex-row items-center justify-between border-b pb-4">
                <div>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Bell className="h-4.5 w-4.5 text-primary" /> Matriks Notifikasi & Sound Dinamis
                    </CardTitle>
                    <CardDescription>
                        Kontrol granular rute notifikasi, target role penerima (RBAC), dan suara peringatan visual.
                    </CardDescription>
                </div>
                <Badge variant="outline" className="bg-primary/5 text-primary text-[11px] font-semibold border-primary/20">
                    Granular Multi-Channel Setup
                </Badge>
            </CardHeader>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-6">
                    <div className="overflow-x-auto rounded-lg border">
                        <table className="w-full text-left text-sm border-collapse min-w-[800px]">
                            <thead>
                                <tr className="border-b text-[10px] text-muted-foreground uppercase bg-muted/40 font-semibold">
                                    <th className="py-2 px-3.5 w-1/4">Aktivitas Sistem</th>
                                    <th className="py-2 px-3 w-1/4">Rute Channel</th>
                                    <th className="py-2 px-3 w-1/3">Target Penerima (Role RBAC)</th>
                                    <th className="py-2 px-3 w-1/6">Suara Peringatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {Object.keys(eventLabels).map((eventKey) => {
                                    const eventConfig = data.matrix[eventKey] || {
                                        in_app: true,
                                        whatsapp: false,
                                        telegram: false,
                                        os_desktop: true,
                                        roles: [],
                                        sound: 'bell-chime'
                                    };

                                    return (
                                        <tr key={eventKey} className="border-b hover:bg-muted/10 transition-colors">
                                            <td className="py-2 px-3.5 align-middle">
                                                <div className="font-bold text-gray-800 text-xs">{eventLabels[eventKey].title}</div>
                                                <div className="text-[10px] text-muted-foreground mt-0.5 max-w-[200px] leading-snug">
                                                    {eventLabels[eventKey].desc}
                                                </div>
                                            </td>
                                            <td className="py-2 px-3 align-middle">
                                                <div className="flex flex-col gap-1.5 justify-center">
                                                    <div className="flex items-center gap-4">
                                                        <label className="flex items-center gap-1.5 text-xs cursor-pointer select-none font-medium">
                                                            <input
                                                                type="checkbox"
                                                                checked={!!eventConfig.in_app}
                                                                onChange={(e) => toggleChannel(eventKey, 'in_app', e.target.checked)}
                                                                className="rounded border-gray-300 text-primary focus:ring-primary h-3.5 w-3.5"
                                                            />
                                                            <span>🔔 In-App</span>
                                                        </label>
                                                        <label className="flex items-center gap-1.5 text-xs cursor-pointer select-none font-medium">
                                                            <input
                                                                type="checkbox"
                                                                checked={!!eventConfig.whatsapp}
                                                                onChange={(e) => toggleChannel(eventKey, 'whatsapp', e.target.checked)}
                                                                className="rounded border-gray-300 text-primary focus:ring-primary h-3.5 w-3.5"
                                                            />
                                                            <span>💬 WA</span>
                                                        </label>
                                                    </div>
                                                    <div className="flex items-center gap-4">
                                                        <label className="flex items-center gap-1.5 text-xs cursor-pointer select-none font-medium">
                                                            <input
                                                                type="checkbox"
                                                                checked={!!eventConfig.telegram}
                                                                onChange={(e) => toggleChannel(eventKey, 'telegram', e.target.checked)}
                                                                className="rounded border-gray-300 text-primary focus:ring-primary h-3.5 w-3.5"
                                                            />
                                                            <span>✈️ Tele</span>
                                                        </label>
                                                        <label className="flex items-center gap-1.5 text-xs cursor-pointer select-none font-medium">
                                                            <input
                                                                type="checkbox"
                                                                checked={!!eventConfig.os_desktop}
                                                                onChange={(e) => toggleChannel(eventKey, 'os_desktop', e.target.checked)}
                                                                className="rounded border-gray-300 text-primary focus:ring-primary h-3.5 w-3.5"
                                                            />
                                                            <span>🖥️ OS</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="py-2 px-3 align-middle">
                                                <div className="grid grid-cols-3 gap-x-2 gap-y-1.5">
                                                    {availableRoles.map((role) => (
                                                        <label key={role} className="flex items-center gap-1.5 text-[11px] cursor-pointer select-none group">
                                                            <input
                                                                type="checkbox"
                                                                checked={eventConfig.roles?.includes(role) || false}
                                                                onChange={(e) => toggleRole(eventKey, role, e.target.checked)}
                                                                className="rounded border-gray-300 text-primary focus:ring-primary h-3 w-3 transition-transform group-hover:scale-105"
                                                            />
                                                            <span className={eventConfig.roles?.includes(role) ? "font-bold text-gray-800" : "text-muted-foreground"}>
                                                                {roleLabels[role] || role.split(/[_-]/).map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}
                                                            </span>
                                                        </label>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="py-2 px-3 align-middle">
                                                <div className="flex items-center gap-1">
                                                    <Select value={eventConfig.sound} onValueChange={(v) => setSound(eventKey, v)}>
                                                        <SelectTrigger className="h-7 text-[11px] py-0 px-2 min-w-[110px]">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="bell-chime">Pleasant Bell 🔔</SelectItem>
                                                            <SelectItem value="success-tada">Success Tada 🎉</SelectItem>
                                                            <SelectItem value="warning-alert">Sweep Alert ⚠️</SelectItem>
                                                            <SelectItem value="cash-register">Coins Register 🪙</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        className="h-7 w-7 hover:bg-violet-50 text-violet-600 border-violet-200"
                                                        onClick={() => playNotificationSound(eventConfig.sound)}
                                                        title="Play Sound Preview"
                                                    >
                                                        <Volume2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex justify-end pt-4 border-t">
                        <Button type="submit" disabled={processing} className="px-6 flex items-center gap-2">
                            <CheckSquare className="h-4 w-4" /> Simpan Matriks Notifikasi
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function ScheduledReportsSection({ reports }) {
    const { data, setData, put, processing } = useForm({
        enable_auto_report:    reports.enable_auto_report,
        daily_report_time:     reports.daily_report_time    || '08:00',
        weekly_report_day:     reports.weekly_report_day    || 'monday',
        monthly_report_date:   reports.monthly_report_date  || 1,
        report_types:          reports.report_types         || 'brand,produksi',
        superadmin_recipients: reports.superadmin_recipients || '',
        produksi_recipients:   reports.produksi_recipients  || '',
        brand_recipients:      reports.brand_recipients     || '',
        owner_recipients:      reports.owner_recipients     || '',
        keuangan_recipients:   reports.keuangan_recipients  || '',
    });

    const typeList = [
        { key: 'superadmin', label: 'Superadmin (semua brand)', icon: '🌐' },
        { key: 'brand',      label: 'Admin Brand (per brand)',  icon: '🏷️' },
        { key: 'produksi',   label: 'Admin Produksi',           icon: '🏭' },
        { key: 'owner',      label: 'Owner (per brand)',         icon: '👑' },
        { key: 'keuangan',   label: 'Admin Keuangan',            icon: '💰' },
    ];

    const activeTypes = data.report_types ? data.report_types.split(',').map(t => t.trim()).filter(Boolean) : [];

    function toggleType(key) {
        const current = new Set(activeTypes);
        current.has(key) ? current.delete(key) : current.add(key);
        setData('report_types', [...current].join(','));
    }

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.reports'), { preserveScroll: true });
    }

    const dayOptions = [
        { value: 'monday', label: 'Senin' }, { value: 'tuesday', label: 'Selasa' },
        { value: 'wednesday', label: 'Rabu' }, { value: 'thursday', label: 'Kamis' },
        { value: 'friday', label: 'Jumat' }, { value: 'saturday', label: 'Sabtu' },
        { value: 'sunday', label: 'Minggu' },
    ];

    return (
        <Card className="shadow-md border-t-4 border-t-indigo-500">
            <CardHeader className="border-b pb-4">
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-base"><Calendar className="h-4.5 w-4.5 text-indigo-600" /> Laporan Otomatis Terjadwal</CardTitle>
                        <CardDescription>Konfigurasi pengiriman laporan berkala per-role ke WhatsApp/Telegram (BRD 17.2.1 & 17.2.2).</CardDescription>
                    </div>
                    <Badge variant={data.enable_auto_report ? 'success' : 'secondary'}>
                        {data.enable_auto_report ? '● Aktif' : '○ Nonaktif'}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-6">

                    {/* Enable toggle */}
                    <div className="flex items-center justify-between rounded-lg border p-4 bg-muted/20">
                        <div>
                            <p className="font-semibold text-sm">Aktifkan Laporan Otomatis</p>
                            <p className="text-xs text-muted-foreground mt-0.5">Jika diaktifkan, laporan akan dikirim sesuai jadwal cron terdaftar.</p>
                        </div>
                        <Switch checked={data.enable_auto_report} onCheckedChange={(v) => setData('enable_auto_report', v)} />
                    </div>

                    {/* Jadwal */}
                    <div className="space-y-3">
                        <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Jadwal Pengiriman</p>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Waktu Harian</Label>
                                <Input type="time" value={data.daily_report_time} onChange={(e) => setData('daily_report_time', e.target.value)} className="h-9 text-xs" />
                                <p className="text-[10px] text-muted-foreground">Jam kirim laporan harian</p>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs">Hari Mingguan</Label>
                                <Select value={data.weekly_report_day} onValueChange={(v) => setData('weekly_report_day', v)}>
                                    <SelectTrigger className="h-9 text-xs"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {dayOptions.map(d => <SelectItem key={d.value} value={d.value}>{d.label}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs">Tanggal Bulanan</Label>
                                <Input type="number" min={1} max={28} value={data.monthly_report_date} onChange={(e) => setData('monthly_report_date', parseInt(e.target.value) || 1)} className="h-9 text-xs" />
                                <p className="text-[10px] text-muted-foreground">Tanggal 1–28 setiap bulan</p>
                            </div>
                        </div>
                    </div>

                    <Separator />

                    {/* Jenis laporan */}
                    <div className="space-y-3">
                        <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Jenis Laporan yang Dikirim</p>
                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {typeList.map(t => (
                                <button
                                    key={t.key}
                                    type="button"
                                    onClick={() => toggleType(t.key)}
                                    className={cn(
                                        "flex items-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition text-left",
                                        activeTypes.includes(t.key)
                                            ? "border-indigo-400 bg-indigo-50 text-indigo-800"
                                            : "border-slate-200 bg-white text-slate-500 hover:border-slate-300"
                                    )}
                                >
                                    <span className="text-base">{t.icon}</span>
                                    {t.label}
                                    {activeTypes.includes(t.key) && <CheckCircle2 className="ml-auto h-3.5 w-3.5 text-indigo-600" />}
                                </button>
                            ))}
                        </div>
                    </div>

                    <Separator />

                    {/* Recipients per role */}
                    <div className="space-y-3">
                        <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Penerima Laporan per Role (nomor WA, pisah koma)</p>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            {[
                                { key: 'superadmin_recipients', label: '🌐 Superadmin', placeholder: '6281234,6285678' },
                                { key: 'produksi_recipients',   label: '🏭 Admin Produksi', placeholder: '6281234,6285678' },
                                { key: 'brand_recipients',      label: '🏷️ Admin Brand',   placeholder: '6281234,6285678' },
                                { key: 'owner_recipients',      label: '👑 Owner',          placeholder: '6281234,6285678' },
                                { key: 'keuangan_recipients',   label: '💰 Admin Keuangan', placeholder: '6281234,6285678' },
                            ].map(f => (
                                <div key={f.key} className="space-y-1">
                                    <Label className="text-xs">{f.label}</Label>
                                    <Input
                                        value={data[f.key]}
                                        onChange={(e) => setData(f.key, e.target.value)}
                                        placeholder={f.placeholder}
                                        className="h-9 text-xs font-mono"
                                    />
                                </div>
                            ))}
                        </div>
                        <p className="text-[10px] text-muted-foreground">Kosongkan = pakai default WA/Telegram dari pengaturan di atas. Format: <code>628xxxxxxx</code> atau Group ID.</p>
                    </div>

                    <Separator />

                    {/* Cron info */}
                    <div className="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs leading-relaxed space-y-2">
                        <p className="font-bold text-blue-900 flex items-center gap-1"><Clock className="h-3.5 w-3.5" /> Panduan Cron Job Server</p>
                        <code className="block rounded bg-blue-100 border border-blue-200 px-2 py-1 font-mono text-[11px] text-blue-900">
                            {"* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1"}
                        </code>
                        <p className="text-blue-700">Atau jalankan manual:</p>
                        <div className="flex gap-2 flex-wrap">
                            {['harian', 'mingguan', 'bulanan'].map(p => (
                                <code key={p} className="rounded bg-blue-100 border border-blue-200 px-2 py-0.5 font-mono text-[11px] text-blue-900">
                                    php artisan reports:send {p}
                                </code>
                            ))}
                        </div>
                        <p className="text-blue-700">Paksa kirim (abaikan enable toggle): tambahkan <code className="bg-blue-100 px-1 rounded">--force</code></p>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing} className="px-6">
                            {processing ? 'Menyimpan…' : 'Simpan Pengaturan Laporan'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function SeoSection({ seo }) {
    const { data, setData, post, processing, errors } = useForm({
        site_name: seo.site_name || '',
        site_description: seo.site_description || '',
        logo: null,
        favicon: null,
        _method: 'PUT',
    });

    function submit(e) {
        e.preventDefault();
        post(route('settings.integrasi.seo'), {
            preserveScroll: true,
            onSuccess: () => {
                setData('logo', null);
                setData('favicon', null);
            }
        });
    }

    return (
        <Card className="shadow-md border-t-4 border-t-sky-500">
            <CardHeader className="border-b pb-4">
                <CardTitle className="flex items-center gap-2 text-base">
                    <Settings className="h-4.5 w-4.5 text-sky-600" /> SEO & Branding Aplikasi
                </CardTitle>
                <CardDescription>
                    Atur nama sistem, deskripsi untuk metadata, logo, dan favicon secara dinamis.
                </CardDescription>
            </CardHeader>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-5" encType="multipart/form-data">
                    <div>
                        <Label className="text-xs font-semibold text-gray-700">Nama Aplikasi / Judul Situs (SEO)</Label>
                        <Input 
                            value={data.site_name} 
                            onChange={(e) => setData('site_name', e.target.value)} 
                            placeholder="Circle Sportwear - Tracking PO" 
                            className="mt-1.5" 
                            required
                        />
                        {errors.site_name && <p className="text-red-500 text-xs mt-1">{errors.site_name}</p>}
                    </div>

                    <div>
                        <Label className="text-xs font-semibold text-gray-700">Deskripsi Situs (SEO & Meta)</Label>
                        <Textarea 
                            value={data.site_description} 
                            onChange={(e) => setData('site_description', e.target.value)} 
                            placeholder="Sistem tracking PO dan invoice secara aman dan privat." 
                            rows={3}
                            className="mt-1.5 text-xs" 
                        />
                        {errors.site_description && <p className="text-red-500 text-xs mt-1">{errors.site_description}</p>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                        <div className="border rounded-xl p-4 bg-muted/10">
                            <Label className="text-xs font-semibold text-gray-700 block mb-2">Logo Aplikasi (Sidebar/Invoice)</Label>
                            
                            {seo.logo_url && (
                                <div className="mb-3 p-3 bg-white rounded-lg border border-slate-100 flex items-center justify-center h-20">
                                    <img src={seo.logo_url} alt="Site Logo" className="max-h-full max-w-full object-contain" />
                                </div>
                            )}

                            <Input 
                                type="file" 
                                onChange={(e) => setData('logo', e.target.files[0])} 
                                accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                className="mt-1.5 text-xs" 
                            />
                            <p className="mt-1.5 text-[10px] text-muted-foreground">Mendukung PNG, JPG, WEBP, SVG. Max 2MB.</p>
                            {errors.logo && <p className="text-red-500 text-xs mt-1">{errors.logo}</p>}
                        </div>

                        <div className="border rounded-xl p-4 bg-muted/10">
                            <Label className="text-xs font-semibold text-gray-700 block mb-2">Favicon Aplikasi (Browser Tab)</Label>
                            
                            {seo.favicon_url && (
                                <div className="mb-3 p-3 bg-white rounded-lg border border-slate-100 flex items-center justify-center h-20">
                                    <img src={seo.favicon_url} alt="Favicon" className="h-10 w-10 object-contain" />
                                </div>
                            )}

                            <Input 
                                type="file" 
                                onChange={(e) => setData('favicon', e.target.files[0])} 
                                accept="image/x-icon,image/png,image/jpeg,image/svg+xml,image/webp"
                                className="mt-1.5 text-xs" 
                            />
                            <p className="mt-1.5 text-[10px] text-muted-foreground">Mendukung ICO, PNG, SVG. Max 1MB.</p>
                            {errors.favicon && <p className="text-red-500 text-xs mt-1">{errors.favicon}</p>}
                        </div>
                    </div>

                    <div className="pt-4 border-t flex justify-end">
                        <Button type="submit" disabled={processing} className="px-6 flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white">
                            <CheckCircle2 className="h-4 w-4" /> Simpan SEO & Branding
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function MailSection({ mail }) {
    const { data, setData, put, processing, errors } = useForm({
        mail_host: mail.mail_host || '',
        mail_port: mail.mail_port || '2525',
        mail_username: mail.mail_username || '',
        mail_password: '',
        mail_encryption: mail.mail_encryption || 'tls',
        mail_from_address: mail.mail_from_address || '',
        mail_from_name: mail.mail_from_name || '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.mail'), { preserveScroll: true });
    }

    return (
        <Card className="shadow-md border-t-4 border-t-amber-500">
            <CardHeader className="border-b pb-4">
                <CardTitle className="flex items-center gap-2 text-base">
                    <Mail className="h-4.5 w-4.5 text-amber-600" /> Konfigurasi SMTP Mail Server
                </CardTitle>
                <CardDescription>
                    Atur server surat keluar SMTP untuk pengiriman email lupa password, verifikasi akun, dan laporan otomatis.
                </CardDescription>
            </CardHeader>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="md:col-span-2">
                            <Label className="text-xs font-semibold text-gray-700">SMTP Host</Label>
                            <Input 
                                value={data.mail_host} 
                                onChange={(e) => setData('mail_host', e.target.value)} 
                                placeholder="smtp.mailtrap.io" 
                                className="mt-1.5 font-mono text-xs" 
                                required
                            />
                            {errors.mail_host && <p className="text-red-500 text-xs mt-1">{errors.mail_host}</p>}
                        </div>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">SMTP Port</Label>
                            <Input 
                                type="number" 
                                value={data.mail_port} 
                                onChange={(e) => setData('mail_port', e.target.value)} 
                                placeholder="2525" 
                                className="mt-1.5 font-mono text-xs" 
                                required
                            />
                            {errors.mail_port && <p className="text-red-500 text-xs mt-1">{errors.mail_port}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Username</Label>
                            <Input 
                                value={data.mail_username} 
                                onChange={(e) => setData('mail_username', e.target.value)} 
                                placeholder="Masukkan username SMTP" 
                                className="mt-1.5 font-mono text-xs" 
                            />
                            {errors.mail_username && <p className="text-red-500 text-xs mt-1">{errors.mail_username}</p>}
                        </div>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                                Password
                                {mail.has_password && <Badge variant="outline" className="font-mono text-[9px] bg-muted/40">{mail.mail_password_masked}</Badge>}
                            </Label>
                            <Input 
                                type="password" 
                                value={data.mail_password} 
                                onChange={(e) => setData('mail_password', e.target.value)} 
                                placeholder={mail.has_password ? "Isi untuk mengganti" : "Masukkan password SMTP"} 
                                className="mt-1.5 font-mono text-xs" 
                            />
                            {errors.mail_password && <p className="text-red-500 text-xs mt-1">{errors.mail_password}</p>}
                        </div>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Enkripsi</Label>
                            <Select value={data.mail_encryption} onValueChange={(v) => setData('mail_encryption', v)}>
                                <SelectTrigger className="mt-1.5 font-mono text-xs"><SelectValue /></SelectTrigger>
                                <SelectContent className="font-sans text-xs">
                                    <SelectItem value="tls">TLS</SelectItem>
                                    <SelectItem value="ssl">SSL</SelectItem>
                                    <SelectItem value="none">Tanpa Enkripsi (none)</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.mail_encryption && <p className="text-red-500 text-xs mt-1">{errors.mail_encryption}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Pengirim Email (From Address)</Label>
                            <Input 
                                type="email" 
                                value={data.mail_from_address} 
                                onChange={(e) => setData('mail_from_address', e.target.value)} 
                                placeholder="no-reply@circlesportwear.com" 
                                className="mt-1.5 text-xs" 
                                required
                            />
                            {errors.mail_from_address && <p className="text-red-500 text-xs mt-1">{errors.mail_from_address}</p>}
                        </div>
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Nama Pengirim (From Name)</Label>
                            <Input 
                                value={data.mail_from_name} 
                                onChange={(e) => setData('mail_from_name', e.target.value)} 
                                placeholder="Circle Sportwear" 
                                className="mt-1.5 text-xs" 
                                required
                            />
                            {errors.mail_from_name && <p className="text-red-500 text-xs mt-1">{errors.mail_from_name}</p>}
                        </div>
                    </div>

                    <div className="pt-4 border-t flex justify-end">
                        <Button type="submit" disabled={processing} className="px-6 flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white">
                            <CheckCircle2 className="h-4 w-4" /> Simpan Mail Server
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function ResellerBrandingSection({ reseller_branding }) {
    const { data, setData, post, processing, errors } = useForm({
        nama_brand: reseller_branding.nama_brand || '',
        tagline: reseller_branding.tagline || '',
        email: reseller_branding.email || '',
        no_hp: reseller_branding.no_hp || '',
        alamat: reseller_branding.alamat || '',
        instagram: reseller_branding.instagram || '',
        tiktok: reseller_branding.tiktok || '',
        facebook: reseller_branding.facebook || '',
        logo: null,
        _method: 'PUT',
    });

    function submit(e) {
        e.preventDefault();
        post(route('settings.integrasi.reseller-branding'), {
            preserveScroll: true,
            onSuccess: () => {
                setData('logo', null);
            }
        });
    }

    return (
        <Card className="shadow-md border-t-4 border-t-indigo-500">
            <CardHeader className="border-b pb-4">
                <CardTitle className="flex items-center gap-2 text-base">
                    <Building2 className="h-4.5 w-4.5 text-indigo-600" /> Branding Reseller Global
                </CardTitle>
                <CardDescription>
                    Atur nama utama, email, nomor HP, alamat, media sosial, dan logo KOP surat global untuk semua akun reseller.
                </CardDescription>
            </CardHeader>
            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-5" encType="multipart/form-data">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Nama Utama Brand Reseller</Label>
                            <Input 
                                value={data.nama_brand} 
                                onChange={(e) => setData('nama_brand', e.target.value)} 
                                placeholder="Circle Reseller" 
                                className="mt-1.5" 
                                required
                            />
                            {errors.nama_brand && <p className="text-red-500 text-xs mt-1">{errors.nama_brand}</p>}
                        </div>

                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Tagline / Deskripsi KOP</Label>
                            <Input 
                                value={data.tagline} 
                                onChange={(e) => setData('tagline', e.target.value)} 
                                placeholder="Reseller Official Hub" 
                                className="mt-1.5" 
                            />
                            {errors.tagline && <p className="text-red-500 text-xs mt-1">{errors.tagline}</p>}
                        </div>

                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Email KOP</Label>
                            <Input 
                                type="email"
                                value={data.email} 
                                onChange={(e) => setData('email', e.target.value)} 
                                placeholder="reseller@circlesportwear.com" 
                                className="mt-1.5" 
                            />
                            {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                        </div>

                        <div>
                            <Label className="text-xs font-semibold text-gray-700">No. HP / WhatsApp KOP</Label>
                            <Input 
                                value={data.no_hp} 
                                onChange={(e) => setData('no_hp', e.target.value)} 
                                placeholder="08123456789" 
                                className="mt-1.5" 
                            />
                            {errors.no_hp && <p className="text-red-500 text-xs mt-1">{errors.no_hp}</p>}
                        </div>

                        <div className="md:col-span-2">
                            <Label className="text-xs font-semibold text-gray-700">Alamat Lengkap KOP</Label>
                            <Textarea 
                                value={data.alamat} 
                                onChange={(e) => setData('alamat', e.target.value)} 
                                placeholder="Jl. Reseller Jaya No. 123, Bandung" 
                                rows={2}
                                className="mt-1.5 text-xs" 
                            />
                            {errors.alamat && <p className="text-red-500 text-xs mt-1">{errors.alamat}</p>}
                        </div>

                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Instagram</Label>
                            <Input 
                                value={data.instagram} 
                                onChange={(e) => setData('instagram', e.target.value)} 
                                placeholder="@circlereseller" 
                                className="mt-1.5" 
                            />
                            {errors.instagram && <p className="text-red-500 text-xs mt-1">{errors.instagram}</p>}
                        </div>

                        <div>
                            <Label className="text-xs font-semibold text-gray-700">TikTok</Label>
                            <Input 
                                value={data.tiktok} 
                                onChange={(e) => setData('tiktok', e.target.value)} 
                                placeholder="@circlereseller" 
                                className="mt-1.5" 
                            />
                            {errors.tiktok && <p className="text-red-500 text-xs mt-1">{errors.tiktok}</p>}
                        </div>

                        <div>
                            <Label className="text-xs font-semibold text-gray-700">Facebook</Label>
                            <Input 
                                value={data.facebook} 
                                onChange={(e) => setData('facebook', e.target.value)} 
                                placeholder="Circle Reseller" 
                                className="mt-1.5" 
                            />
                            {errors.facebook && <p className="text-red-500 text-xs mt-1">{errors.facebook}</p>}
                        </div>

                        <div className="border rounded-xl p-4 bg-muted/10 md:col-span-2">
                            <Label className="text-xs font-semibold text-gray-700 block mb-2">Logo KOP Reseller (Mempengaruhi PDF Kop Surat Reseller)</Label>
                            
                            {reseller_branding.logo_url && (
                                <div className="mb-3 p-3 bg-white rounded-lg border border-slate-100 flex items-center justify-center h-20">
                                    <img src={reseller_branding.logo_url} alt="Reseller Logo" className="max-h-full max-w-full object-contain" />
                                </div>
                            )}

                            <Input 
                                type="file" 
                                onChange={(e) => setData('logo', e.target.files[0])} 
                                accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                className="mt-1.5 text-xs" 
                            />
                            <p className="mt-1.5 text-[10px] text-muted-foreground">Mendukung PNG, JPG, WEBP, SVG. Max 2MB.</p>
                            {errors.logo && <p className="text-red-500 text-xs mt-1">{errors.logo}</p>}
                        </div>
                    </div>

                    <div className="pt-4 border-t flex justify-end">
                        <Button type="submit" disabled={processing} className="px-6 flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                            <CheckCircle2 className="h-4 w-4" /> Simpan Branding Reseller
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

export default function Integrations({ ai, whatsapp, telegram, system, notification_matrix, available_roles, seo, reseller_branding, mail, reports }) {
    const roles = available_roles || ['superadmin', 'owner', 'admin_brand', 'reseller', 'admin_produksi', 'admin_keuangan'];
    const [activeTab, setActiveTab] = useState('seo');

    const tabs = [
        { id: 'seo', name: 'SEO & Branding', icon: Settings, badge: 'App' },
        { id: 'reseller_branding', name: 'Branding Reseller', icon: Building2, badge: 'Reseller' },
        { id: 'mail', name: 'SMTP Mail Server', icon: Mail, badge: 'SMTP' },
        { id: 'whatsapp', name: 'WhatsApp Gateway', icon: MessageCircle, badge: wa => wa.is_configured ? 'ON' : 'Mock' },
        { id: 'telegram', name: 'Telegram Bot', icon: Send, badge: tg => tg.is_configured ? 'ON' : 'Mock' },
        { id: 'gemini', name: 'Gemini AI Hub', icon: Sparkles, badge: ai => ai.is_configured ? 'ON' : 'Mock' },
        { id: 'system', name: 'System & Reports', icon: Settings, badge: 'System' }
    ];

    return (
        <AppLayout title="Pengaturan Integrasi">
            <Head title="Pengaturan Integrasi" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Pengaturan Integrasi & Notifikasi</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        Kelola dan konfigurasi channel pengiriman WhatsApp, Telegram, AI Hub, dan sound dinamis untuk tiap alur kerja.
                    </p>
                </div>

                <div className="rounded-lg border bg-blue-50/50 p-4 text-sm flex gap-3 items-start border-blue-200">
                    <span className="text-lg">💡</span>
                    <div className="leading-relaxed">
                        <strong className="text-blue-900 block mb-0.5">Catatan Sandbox Mode:</strong>
                        Tanpa API key WhatsApp/Telegram yang terisi, sistem akan otomatis berjalan dalam <strong>Sandbox Mode</strong>. Semua log notifikasi tercatat di database in-app dan panel audio, tapi pengiriman external di-mock agar aman untuk testing lokal Anda.
                    </div>
                </div>

            {/* RESPONSIVE VERTICAL TAB LAYOUT */}
            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
                {/* Vertical Sidebar Tabs on the Left */}
                <div className="lg:col-span-1 space-y-1 bg-white p-2.5 rounded-xl border shadow-sm">
                    {tabs.map((t) => {
                        const Icon = t.icon;
                        const isActive = activeTab === t.id;
                        let badgeText = typeof t.badge === 'function'
                            ? t.badge(t.id === 'whatsapp' ? whatsapp : t.id === 'telegram' ? telegram : ai)
                            : t.badge;

                        return (
                            <button
                                key={t.id}
                                type="button"
                                onClick={() => setActiveTab(t.id)}
                                className={cn(
                                    "w-full flex items-center justify-between px-3.5 py-3 rounded-lg text-xs font-semibold tracking-wide transition-all duration-200 select-none text-left border-l-2",
                                    isActive
                                        ? "bg-slate-50 text-primary border-primary font-bold shadow-sm"
                                        : "text-gray-600 hover:text-gray-900 hover:bg-slate-50 border-transparent"
                                )}
                            >
                                <div className="flex items-center gap-2.5 min-w-0">
                                    <Icon className={cn("h-4 w-4 shrink-0", isActive ? "text-primary" : "text-gray-400")} />
                                    <span className="truncate">{t.name}</span>
                                </div>
                                <Badge
                                    className={cn(
                                        "ml-2 text-[9px] font-bold px-1.5 py-0.5 shrink-0",
                                        isActive ? "bg-primary/10 text-primary border-transparent" : "bg-slate-100 text-gray-500"
                                    )}
                                >
                                    {badgeText}
                                </Badge>
                            </button>
                        );
                    })}
                </div>

                {/* Tab Content Panes on the Right */}
                <div className="lg:col-span-3 transition-all duration-250 ease-out">
                    {activeTab === 'seo' && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-200">
                            <SeoSection seo={seo} />
                        </div>
                    )}

                    {activeTab === 'reseller_branding' && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-200">
                            <ResellerBrandingSection reseller_branding={reseller_branding} />
                        </div>
                    )}

                    {activeTab === 'mail' && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-200">
                            <MailSection mail={mail} />
                        </div>
                    )}

                    {activeTab === 'whatsapp' && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-200">
                            <WhatsAppSection wa={whatsapp} />
                        </div>
                    )}

                    {activeTab === 'telegram' && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-200">
                            <TelegramSection tg={telegram} />
                        </div>
                    )}

                    {activeTab === 'gemini' && (
                        <div className="animate-in fade-in slide-in-from-bottom-2 duration-200">
                            <AiSection ai={ai} />
                        </div>
                    )}

                    {activeTab === 'system' && (
                        <div className="grid grid-cols-1 gap-5 animate-in fade-in slide-in-from-bottom-2 duration-200">
                            <SystemSection sys={system} />
                            <ScheduledReportsSection reports={reports ?? {}} />
                        </div>
                    )}
                </div>
            </div>
            </div>
        </AppLayout>
    );
}
