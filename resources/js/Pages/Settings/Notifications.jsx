import { Head, useForm } from '@inertiajs/react';
import { Bell, Volume2, CheckSquare } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { playNotificationSound } from '@/Services/audio';
import { cn } from '@/lib/utils';

function NotificationMatrixSection({ matrix, availableRoles, availableSounds }) {
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
        reseller: 'Reseller',
        admin_produksi: 'Produksi',
        admin_keuangan: 'Keuangan'
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
        <Card className="shadow-md border-t-4 border-t-primary bg-white">
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
                                                            <span>In-App</span>
                                                        </label>
                                                        <label className="flex items-center gap-1.5 text-xs cursor-pointer select-none font-medium">
                                                            <input
                                                                type="checkbox"
                                                                checked={!!eventConfig.whatsapp}
                                                                onChange={(e) => toggleChannel(eventKey, 'whatsapp', e.target.checked)}
                                                                className="rounded border-gray-300 text-primary focus:ring-primary h-3.5 w-3.5"
                                                            />
                                                            <span>WA</span>
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
                                                            <span>Tele</span>
                                                        </label>
                                                        <label className="flex items-center gap-1.5 text-xs cursor-pointer select-none font-medium">
                                                            <input
                                                                type="checkbox"
                                                                checked={!!eventConfig.os_desktop}
                                                                onChange={(e) => toggleChannel(eventKey, 'os_desktop', e.target.checked)}
                                                                className="rounded border-gray-300 text-primary focus:ring-primary h-3.5 w-3.5"
                                                            />
                                                            <span>OS</span>
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
                                                                {roleLabels[role] || role}
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
                                                            {availableSounds?.map((sound) => (
                                                                 <SelectItem key={sound.value} value={sound.value}>
                                                                     {sound.label}
                                                                 </SelectItem>
                                                             ))}
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

export default function Notifications({ notification_matrix, available_roles, available_sounds }) {
    const roles = available_roles || ['superadmin', 'owner', 'admin_brand', 'reseller', 'admin_produksi', 'admin_keuangan'];

    return (
        <AppLayout title="Matriks Notifikasi">
            <Head title="Matriks Notifikasi" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Pengaturan Matriks Notifikasi</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        Kelola dan konfigurasi target role penerima (RBAC), channel pengiriman, dan sound dinamis untuk tiap alur kerja sistem.
                    </p>
                </div>

                <div className="rounded-lg border bg-blue-50/50 p-4 text-sm flex gap-3 items-start border-blue-200">
                    <span className="text-lg">💡</span>
                    <div className="leading-relaxed">
                        <strong className="text-blue-900 block mb-0.5">Catatan Penting:</strong>
                        Pengaturan di bawah ini berlaku untuk notifikasi internal (In-App & OS Desktop) serta notifikasi eksternal (WhatsApp & Telegram). Pastikan master switches untuk channel yang dipilih telah diaktifkan di menu integrasi.
                    </div>
                </div>

                <div className="animate-in fade-in slide-in-from-bottom-2 duration-200">
                    <NotificationMatrixSection matrix={notification_matrix} availableRoles={roles} availableSounds={available_sounds} />
                </div>
            </div>
        </AppLayout>
    );
}
