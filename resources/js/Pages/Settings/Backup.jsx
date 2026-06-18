import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Download, Trash2, ShieldAlert, Archive, CheckCircle, Database, CloudLightning, Save, Check, Loader2, Key, Link as LinkIcon, RefreshCw, XCircle } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Switch } from '@/Components/ui/switch';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { toast } from 'sonner';

export default function Backup({ stats, gdrive }) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [runningBackup, setRunningBackup] = useState(false);
    const [disconnecting, setDisconnecting] = useState(false);

    // Form untuk Clean Up lokal
    const cleanupForm = useForm({
        confirm: false,
    });

    // Form untuk kredensial aplikasi Google OAuth
    const gdriveForm = useForm({
        is_enabled: gdrive.is_enabled,
        client_id: gdrive.client_id,
        client_secret: gdrive.has_secret ? '************' : '', // Tampilkan placeholder jika sudah tersimpan
        folder_id: gdrive.folder_id,
    });

    const handleLocalDownload = () => {
        toast.info('Arsip ZIP sedang dibuat di server, silakan tunggu...');
        window.location.href = route('settings.backup.download');
    };

    const handleCleanupSubmit = (e) => {
        e.preventDefault();
        if (!cleanupForm.data.confirm) {
            toast.error('Harap centang persetujuan konfirmasi pembersihan.');
            return;
        }

        cleanupForm.post(route('settings.backup.cleanup'), {
            preserveScroll: true,
            onSuccess: () => {
                setConfirmOpen(false);
                cleanupForm.setData('confirm', false);
                toast.success('Pembersihan aset lama berhasil diselesaikan.');
            },
            onError: () => {
                toast.error('Gagal melakukan pembersihan aset.');
            }
        });
    };

    const handleGDriveSave = (e) => {
        e.preventDefault();
        
        // Bersihkan data secret agar tidak menimpa dengan placeholder bintang-bintang
        const payload = { ...gdriveForm.data };
        if (payload.client_secret === '************') {
            delete payload.client_secret;
        }

        gdriveForm.transform(() => payload).post(route('settings.backup.settings'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Pengaturan Google Drive berhasil disimpan.');
            },
            onError: () => {
                toast.error('Gagal menyimpan pengaturan.');
            }
        });
    };

    const connectGoogle = () => {
        if (!gdrive.client_id) {
            toast.error('Masukkan Client ID Google OAuth terlebih dahulu dan klik Simpan.');
            return;
        }
        toast.info('Mengarahkan ke Google untuk memilih akun...');
        window.location.href = route('settings.backup.gdrive.redirect');
    };

    const disconnectGoogle = () => {
        if (!confirm('Putuskan hubungan akun Google Drive saat ini?')) return;
        setDisconnecting(true);

        const form = useForm({});
        form.post(route('settings.backup.gdrive.disconnect'), {
            preserveScroll: true,
            onSuccess: () => {
                setDisconnecting(false);
                toast.success('Akun Google Drive berhasil diputuskan.');
            },
            onError: () => {
                setDisconnecting(false);
                toast.error('Gagal memutuskan akun Google Drive.');
            }
        });
    };

    const triggerGDriveBackup = () => {
        setRunningBackup(true);
        toast.info('Memulai backup database & aset ke Google Drive...');

        const form = useForm({});
        form.post(route('settings.backup.run'), {
            preserveScroll: true,
            onSuccess: () => {
                setRunningBackup(false);
                toast.success('Proses backup otomatis ke Google Drive selesai dengan sukses.');
            },
            onError: () => {
                setRunningBackup(false);
                toast.error('Gagal melakukan backup ke Google Drive. Periksa log error.');
            }
        });
    };

    return (
        <AppLayout title="Backup & Arsip Aset">
            <Head title="Backup & Arsip Aset" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Backup & Pembersihan Aset</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        Kelola kapasitas ruang server dengan mengarsipkan foto pesanan (orders) ke Google Drive atau ZIP lokal, serta membersihkan berkas lama yang sudah tidak aktif.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Kolom 1 & 2: Local & GDrive Actions */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Section Google Drive Backup Action */}
                        <Card className="shadow-md border-t-4 border-t-emerald-500 bg-white">
                            <CardHeader className="pb-4">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <CloudLightning className="h-4.5 w-4.5 text-emerald-500" /> Backup Google Drive
                                        </CardTitle>
                                        <CardDescription className="mt-1">
                                            Kirim salinan database SQLite beserta arsip ZIP foto pesanan langsung ke Google Drive Cloud Anda.
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" className={gdrive.is_enabled && gdrive.is_connected ? "bg-emerald-50 text-emerald-700 border-emerald-200" : "bg-slate-50 text-slate-500 border-slate-200"}>
                                        {gdrive.is_enabled && gdrive.is_connected ? "Siap Digunakan" : "Belum Siap"}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* GDrive Connection Status Bar */}
                                {gdrive.is_connected ? (
                                    <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="h-9 w-9 rounded-full bg-emerald-500/10 flex items-center justify-center border border-emerald-200">
                                                <Check className="h-5 w-5 text-emerald-600" />
                                            </div>
                                            <div>
                                                <span className="text-[10px] text-emerald-600 font-black uppercase tracking-wider block">Akun Google Terhubung</span>
                                                <span className="text-xs font-bold text-slate-700">{gdrive.connected_email}</span>
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={disconnectGoogle}
                                            disabled={disconnecting}
                                            className="text-xs border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700 bg-white shadow-sm font-semibold h-8"
                                        >
                                            {disconnecting ? 'Memutus...' : 'Putuskan Akun'}
                                        </Button>
                                    </div>
                                ) : (
                                    <div className="bg-slate-50 border rounded-xl p-4 flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="h-9 w-9 rounded-full bg-slate-500/10 flex items-center justify-center border border-slate-200">
                                                <LinkIcon className="h-5 w-5 text-slate-500" />
                                            </div>
                                            <div>
                                                <span className="text-[10px] text-slate-500 font-black uppercase tracking-wider block">Status Koneksi</span>
                                                <span className="text-xs font-medium text-slate-600">Google Drive belum terhubung</span>
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            onClick={connectGoogle}
                                            disabled={!gdrive.client_id}
                                            className="text-xs bg-slate-800 hover:bg-slate-700 text-white font-bold h-8 flex items-center gap-1"
                                        >
                                            <LinkIcon className="h-3 w-3" /> Pilih Akun Google
                                        </Button>
                                    </div>
                                )}

                                <div className="bg-slate-50 border rounded-xl p-4 space-y-3">
                                    <div className="flex items-center gap-2.5 text-xs text-slate-600">
                                        <Database className="h-4 w-4 text-indigo-500" />
                                        <span><strong>Database Backup</strong>: Menghasilkan salinan <code className="bg-slate-200/60 px-1 py-0.2 rounded text-[11px] font-bold text-slate-700">database.sqlite</code> (~{stats.total_size_bytes > 0 ? '71 MB' : '0 MB'})</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-xs text-slate-600">
                                        <Archive className="h-4 w-4 text-amber-500" />
                                        <span><strong>Assets Backup</strong>: Mengompres folder <code className="bg-slate-200/60 px-1 py-0.2 rounded text-[11px] font-bold text-slate-700">public/orders/</code> ({stats.file_count} file) ke berkas ZIP</span>
                                    </div>
                                    <div className="h-px bg-slate-200 my-2" />
                                    <div className="text-[11px] text-slate-500 leading-normal">
                                        ⏱️ <strong>Jadwal Otomatis:</strong> Backup berjalan otomatis setiap hari pada pukul <strong>02:00 subuh</strong> melalui Laravel Scheduler.
                                    </div>
                                </div>

                                <div className="flex gap-3">
                                    <Button
                                        type="button"
                                        onClick={triggerGDriveBackup}
                                        disabled={!gdrive.is_enabled || !gdrive.is_connected || runningBackup}
                                        className="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold flex items-center justify-center gap-2 py-5"
                                    >
                                        {runningBackup ? (
                                            <><Loader2 className="h-4 w-4 animate-spin" /> Memproses Unggah GDrive...</>
                                        ) : (
                                            <><CloudLightning className="h-4 w-4" /> Jalankan Backup GDrive Sekarang</>
                                        )}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Card: Local ZIP Download */}
                            <Card className="shadow-md border-t-4 border-t-indigo-500 bg-white">
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-700">
                                        <Archive className="h-4 w-4 text-indigo-500" /> ZIP Download
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Download manual semua foto pesanan ke komputer lokal.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="bg-slate-50 border rounded-xl p-3 flex justify-between items-center text-xs">
                                        <div>
                                            <span className="text-[10px] text-slate-400 block font-semibold">Total Aset</span>
                                            <span className="font-bold text-slate-800 text-sm">{stats.total_size_human}</span>
                                        </div>
                                        <div className="text-right">
                                            <span className="text-[10px] text-slate-400 block font-semibold">Berkas</span>
                                            <span className="font-bold text-slate-800 text-sm">{stats.file_count} file</span>
                                        </div>
                                    </div>
                                    <Button
                                        onClick={handleLocalDownload}
                                        className="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center gap-1.5 text-xs py-4"
                                        disabled={stats.file_count === 0}
                                    >
                                        <Download className="h-3.5 w-3.5" /> Download Arsip ZIP
                                    </Button>
                                </CardContent>
                            </Card>

                            {/* Card: Clean Up */}
                            <Card className="shadow-md border-t-4 border-t-red-500 bg-white">
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-700">
                                        <Trash2 className="h-4 w-4 text-red-500" /> Bersihkan Storage
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Hapus file gambar pesanan lama yang sudah selesai (lebih dari 30 hari).
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="bg-red-50/50 border border-red-100 rounded-xl p-3 flex justify-between items-center text-xs">
                                        <div>
                                            <span className="text-[10px] text-red-500 block font-semibold">Bisa Dibersihkan</span>
                                            <span className="font-bold text-red-700 text-sm">{stats.cleanup_size_human}</span>
                                        </div>
                                        <div className="text-right">
                                            <span className="text-[10px] text-red-500 block font-semibold">Berkas PO Lama</span>
                                            <span className="font-bold text-red-700 text-sm">{stats.cleanup_file_count} file</span>
                                        </div>
                                    </div>
                                    <Button
                                        onClick={() => setConfirmOpen(true)}
                                        variant="destructive"
                                        className="w-full font-bold flex items-center justify-center gap-1.5 text-xs py-4"
                                        disabled={stats.cleanup_file_count === 0}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" /> Bersihkan Server
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    {/* Kolom 3: Google Drive Credentials Form */}
                    <div className="lg:col-span-1">
                        <Card className="shadow-md border-t-4 border-t-slate-800 bg-white h-full flex flex-col justify-between">
                            <div>
                                <CardHeader className="pb-4 border-b">
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Key className="h-4.5 w-4.5 text-slate-700" /> Kredensial App Google
                                    </CardTitle>
                                    <CardDescription>
                                        Konfigurasi Google OAuth Client ID & Secret untuk mengizinkan aplikasi melakukan otentikasi login akun Google secara langsung.
                                    </CardDescription>
                                </CardHeader>

                                <CardContent className="pt-6">
                                    <form onSubmit={handleGDriveSave} className="space-y-5">
                                        {/* Switch Enable GDrive */}
                                        <div className="flex items-center justify-between bg-slate-50 border rounded-lg p-3">
                                            <div className="space-y-0.5">
                                                <Label htmlFor="gdrive-status" className="text-xs font-black uppercase text-slate-700">Pencadangan Cloud</Label>
                                                <span className="text-[10px] text-slate-400 block font-medium">Aktifkan backup harian</span>
                                            </div>
                                            <Switch
                                                id="gdrive-status"
                                                checked={gdriveForm.data.is_enabled}
                                                onCheckedChange={(checked) => gdriveForm.setData('is_enabled', checked)}
                                            />
                                        </div>

                                        {/* Client ID */}
                                        <div className="space-y-1.5">
                                            <Label htmlFor="client_id" className="text-[10px] font-black uppercase text-slate-500 tracking-wider">OAuth Client ID</Label>
                                            <Input
                                                id="client_id"
                                                type="text"
                                                placeholder="987654321-abcde.apps.googleusercontent.com"
                                                value={gdriveForm.data.client_id}
                                                onChange={(e) => gdriveForm.setData('client_id', e.target.value)}
                                                className="text-xs focus:ring-1 focus:ring-slate-800"
                                            />
                                        </div>

                                        {/* Client Secret */}
                                        <div className="space-y-1.5">
                                            <Label htmlFor="client_secret" className="text-[10px] font-black uppercase text-slate-500 tracking-wider">OAuth Client Secret</Label>
                                            <Input
                                                id="client_secret"
                                                type="password"
                                                placeholder="GOCSPX-abcde12345..."
                                                value={gdriveForm.data.client_secret}
                                                onChange={(e) => gdriveForm.setData('client_secret', e.target.value)}
                                                className="text-xs focus:ring-1 focus:ring-slate-800"
                                            />
                                        </div>

                                        {/* Redirect URI (Read-only Info) */}
                                        <div className="space-y-1.5 bg-slate-50 p-2.5 border rounded-lg">
                                            <span className="text-[9px] font-black uppercase text-slate-500 tracking-wider block">Authorized Redirect URI</span>
                                            <span className="text-[10px] font-mono select-all text-slate-700 break-all block leading-tight">
                                                {route('settings.backup.gdrive.callback')}
                                            </span>
                                            <span className="text-[8px] text-slate-400 block leading-tight mt-1">
                                                *Salin alamat URI ini dan daftarkan ke Google Cloud Console pada OAuth 2.0 Client Credentials milik Anda.
                                            </span>
                                        </div>

                                        {/* Folder ID */}
                                        <div className="space-y-1.5">
                                            <Label htmlFor="folder_id" className="text-[10px] font-black uppercase text-slate-500 tracking-wider">Folder ID Google Drive</Label>
                                            <Input
                                                id="folder_id"
                                                type="text"
                                                placeholder="1AbcdEfGhIjKlMnOpQrStUvWxYz..."
                                                value={gdriveForm.data.folder_id}
                                                onChange={(e) => gdriveForm.setData('folder_id', e.target.value)}
                                                className="text-xs focus:ring-1 focus:ring-slate-800"
                                            />
                                            <span className="text-[9px] text-slate-400 block leading-tight">
                                                ID folder dari URL browser Google Drive tempat menyimpan berkas backup (misal: *drive.google.com/drive/folders/ID-FOLDER*).
                                            </span>
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={gdriveForm.processing}
                                            className="w-full bg-slate-800 hover:bg-slate-700 text-white font-bold flex items-center justify-center gap-1.5 py-4 text-xs mt-2"
                                        >
                                            <Save className="h-3.5 w-3.5" /> Simpan Konfigurasi
                                        </Button>
                                    </form>
                                </CardContent>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Modal Dialog Konfirmasi Pembersihan */}
            <Dialog open={confirmOpen} onOpenChange={(v) => { if (!v && !cleanupForm.processing) setConfirmOpen(false); }}>
                <DialogContent className="max-w-md p-6">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-600 font-black uppercase tracking-wide text-sm">
                            <ShieldAlert className="h-5 w-5" /> Konfirmasi Tindakan Berisiko
                        </DialogTitle>
                        <DialogDescription className="text-xs text-slate-500 leading-relaxed pt-2">
                            Anda akan menghapus secara permanen sebanyak <strong>{stats.cleanup_file_count} berkas foto</strong> dari server, membebaskan ruang sekitar <strong>{stats.cleanup_size_human}</strong>. Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCleanupSubmit} className="space-y-4 pt-3">
                        <div className="bg-slate-50 border rounded-lg p-3 text-xs text-slate-600 space-y-2 leading-relaxed">
                            <div className="flex gap-2 items-start font-semibold text-slate-800 mb-1">
                                <CheckCircle className="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" />
                                <span>Yang tetap aman (tidak dihapus):</span>
                            </div>
                            <ul className="list-disc list-inside pl-1 space-y-1">
                                <li>Data invoice, detail PO, nameset, dan nominal keuangan di database.</li>
                                <li>Foto dari pesanan aktif (berstatus draft, published, on_progress, dll).</li>
                                <li>Foto produk di Master Data dan logo brand utama.</li>
                            </ul>
                        </div>

                        <label className="flex items-start gap-2.5 cursor-pointer select-none bg-red-50/50 hover:bg-red-50 border border-red-100 p-3 rounded-lg text-xs leading-normal font-medium text-slate-800">
                            <input
                                type="checkbox"
                                checked={cleanupForm.data.confirm}
                                onChange={(e) => cleanupForm.setData('confirm', e.target.checked)}
                                className="rounded border-slate-300 text-red-600 focus:ring-red-500 h-4 w-4 shrink-0 mt-0.5"
                            />
                            <span>Saya mengonfirmasi telah mengunduh backup arsip ZIP/unggah ke Google Drive terlebih dahulu dan setuju menghapus file foto fisik ini secara permanen.</span>
                        </label>

                        <DialogFooter className="pt-2">
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                onClick={() => setConfirmOpen(false)}
                                disabled={cleanupForm.processing}
                            >
                                Batal
                            </Button>
                            <Button 
                                type="submit" 
                                variant="destructive" 
                                size="sm" 
                                disabled={cleanupForm.processing || !cleanupForm.data.confirm}
                                className="font-bold flex items-center gap-1.5"
                            >
                                {cleanupForm.processing ? 'Memproses...' : 'Ya, Bersihkan Permanen'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
