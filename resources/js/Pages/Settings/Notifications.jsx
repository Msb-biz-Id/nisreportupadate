import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Bell, Volume2, CheckSquare } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { MultiSelect } from '@/Components/ui/multi-select';
import { playNotificationSound } from '@/Services/audio';
import { cn } from '@/lib/utils';

export default function Notifications({ notification_matrix, available_roles, available_sounds }) {
    const { data, setData, put, processing } = useForm({
        matrix: { ...notification_matrix }
    });

    const eventLabels = {
        order_published: {
            title: 'PO Baru Diterbitkan 📦',
            desc: 'Dipicu ketika PO baru diterbitkan oleh Admin Brand.'
        },
        progress_updated: {
            title: 'Progress PO Diperbarui ⚙️',
            desc: 'Dipicu ketika tahapan produksi PO diperbarui oleh Admin Produksi.'
        },
        rijek_reported: {
            title: 'Laporan Rijek Baru ⚠️',
            desc: 'Dipicu ketika produk rijek dicatat pada tahapan produksi.'
        },
        refund_submitted: {
            title: 'Pengajuan Refund Dana 🪙',
            desc: 'Dipicu ketika Admin Brand mengajukan refund dana atas masalah PO.'
        },
        refund_processed: {
            title: 'Pengajuan Refund Diproses 💳',
            desc: 'Dipicu ketika Keuangan menyetujui (publish) atau menolak refund.'
        },
        payment_submitted: {
            title: 'Pembayaran PO Diajukan 📥',
            desc: 'Dipicu ketika pembayaran DP/pelunasan atau tanda jadi dikirim oleh reseller/staf.'
        },
        payment_verified: {
            title: 'Pembayaran PO Diverifikasi 💸',
            desc: 'Dipicu ketika pembayaran DP/pelunasan disetujui (diverifikasi) oleh Admin Keuangan.'
        }
    };

    const roleOptions = available_roles.map((r) => ({
        value: r,
        label: r.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
    }));

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

    function handleRolesChange(eventKey, selectedRoles) {
        const updatedEvent = {
            ...data.matrix[eventKey],
            roles: selectedRoles
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

        // Play the sound instantly for preview
        playNotificationSound(soundName);
    }

    function submit(e) {
        e.preventDefault();
        put(route('settings.integrasi.matrix'), { preserveScroll: true });
    }

    return (
        <AppLayout title="Matriks Notifikasi">
            <Head title="Matriks Notifikasi" />

            <div className="space-y-6 max-w-7xl mx-auto">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Pengaturan Matriks Notifikasi</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        Atur rute pengiriman notifikasi, target role penerima, dan suara peringatan dinamis untuk setiap aktivitas sistem.
                    </p>
                </div>

                <form onSubmit={submit}>
                    <Card className="shadow-md border-t-4 border-t-primary bg-white">
                        <CardHeader className="flex flex-row items-center justify-between border-b pb-4">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Bell className="h-5 w-5 text-primary" /> Matriks Notifikasi Sistem
                                </CardTitle>
                                <CardDescription>
                                    Tentukan channel rute dinamis (In-App, WhatsApp, Telegram, Desktop) dan target penerima notifikasi.
                                </CardDescription>
                            </div>
                            <Badge variant="outline" className="bg-primary/5 text-primary text-[11px] font-semibold border-primary/20">
                                Asynchronous Event Notification
                            </Badge>
                        </CardHeader>
                        <CardContent className="pt-6 px-0 sm:px-6">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm border-collapse min-w-[900px]">
                                    <thead>
                                        <tr className="border-b text-[10px] text-muted-foreground uppercase bg-muted/40 font-semibold">
                                            <th className="py-3 px-4 w-1/4">Aktivitas Sistem</th>
                                            <th className="py-3 px-4 w-1/4">Rute Channel</th>
                                            <th className="py-3 px-4 w-1/4">Target Penerima (Role RBAC)</th>
                                            <th className="py-3 px-4 w-1/4">Suara Peringatan</th>
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
                                                    <td className="py-4 px-4 align-top">
                                                        <div className="font-bold text-gray-800 text-xs">{eventLabels[eventKey].title}</div>
                                                        <div className="text-[10px] text-muted-foreground mt-1 max-w-[220px] leading-relaxed">
                                                            {eventLabels[eventKey].desc}
                                                        </div>
                                                    </td>
                                                    <td className="py-4 px-4 align-top">
                                                        <div className="flex flex-col gap-2">
                                                            <div className="flex items-center justify-between gap-4 p-1.5 rounded-md hover:bg-slate-50">
                                                                <span className="text-xs font-medium text-gray-600">🔔 In-App Bell</span>
                                                                <Switch 
                                                                    checked={!!eventConfig.in_app} 
                                                                    onCheckedChange={(checked) => toggleChannel(eventKey, 'in_app', checked)}
                                                                />
                                                            </div>
                                                            <div className="flex items-center justify-between gap-4 p-1.5 rounded-md hover:bg-slate-50">
                                                                <span className="text-xs font-medium text-gray-600">💬 WhatsApp</span>
                                                                <Switch 
                                                                    checked={!!eventConfig.whatsapp} 
                                                                    onCheckedChange={(checked) => toggleChannel(eventKey, 'whatsapp', checked)}
                                                                />
                                                            </div>
                                                            <div className="flex items-center justify-between gap-4 p-1.5 rounded-md hover:bg-slate-50">
                                                                <span className="text-xs font-medium text-gray-600">✈️ Telegram</span>
                                                                <Switch 
                                                                    checked={!!eventConfig.telegram} 
                                                                    onCheckedChange={(checked) => toggleChannel(eventKey, 'telegram', checked)}
                                                                />
                                                            </div>
                                                            <div className="flex items-center justify-between gap-4 p-1.5 rounded-md hover:bg-slate-50">
                                                                <span className="text-xs font-medium text-gray-600">💻 OS Desktop</span>
                                                                <Switch 
                                                                    checked={!!eventConfig.os_desktop} 
                                                                    onCheckedChange={(checked) => toggleChannel(eventKey, 'os_desktop', checked)}
                                                                />
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="py-4 px-4 align-top">
                                                        <MultiSelect
                                                            value={eventConfig.roles || []}
                                                            options={roleOptions}
                                                            onChange={(selected) => handleRolesChange(eventKey, selected)}
                                                            placeholder="Pilih target roles..."
                                                            className="w-full max-w-[250px]"
                                                        />
                                                    </td>
                                                    <td className="py-4 px-4 align-top">
                                                        <div className="flex items-center gap-2 max-w-[200px]">
                                                            <Select
                                                                value={eventConfig.sound || 'bell-chime'}
                                                                onValueChange={(val) => setSound(eventKey, val)}
                                                            >
                                                                <SelectTrigger className="w-full text-xs h-9">
                                                                    <SelectValue placeholder="Pilih Suara" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {available_sounds.map((sound) => (
                                                                        <SelectItem key={sound.value} value={sound.value} className="text-xs">
                                                                            {sound.label}
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                className="h-9 w-9 text-gray-500 hover:text-primary shrink-0"
                                                                onClick={() => playNotificationSound(eventConfig.sound || 'bell-chime')}
                                                            >
                                                                <Volume2 className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                            <div className="flex justify-end mt-6 pt-4 border-t px-4 pb-2">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="gap-2 font-semibold shadow-sm"
                                >
                                    <CheckSquare className="h-4 w-4" /> Simpan Matriks Notifikasi
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
