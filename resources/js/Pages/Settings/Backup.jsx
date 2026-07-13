import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Download, Trash2, ShieldAlert, Archive, CheckCircle, Database, Cloud, Loader2, Server, HelpCircle, HardDrive, Check } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { toast } from 'sonner';

export default function Backup({ stats, r2 }) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [runningBackup, setRunningBackup] = useState(false);

    // Form untuk Clean Up lokal
    const cleanupForm = useForm({
        confirm: false,
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

    const triggerR2Backup = () => {
        setRunningBackup(true);
        toast.info('Memulai backup database & aset ke Cloudflare R2...');

        const form = useForm({});
        form.post(route('settings.backup.run'), {
            preserveScroll: true,
            onSuccess: () => {
                setRunningBackup(false);
                toast.success('Proses backup ke Cloudflare R2 selesai dengan sukses.');
            },
            onError: (err) => {
                setRunningBackup(false);
                const errMsg = err?.message || 'Periksa log error server untuk detail.';
                toast.error('Gagal melakukan backup ke Cloudflare R2. ' + errMsg);
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
                        Kelola kapasitas ruang server dengan mengarsipkan database dan foto pesanan (orders) ke Cloudflare R2 secara otomatis atau unduh ZIP lokal, serta bersihkan berkas lama yang sudah tidak aktif.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Kolom 1 & 2: Cloudflare R2 & Actions */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Section Cloudflare R2 Backup Action */}
                        <Card className="shadow-md border-t-4 border-t-sky-500 bg-white">
                            <CardHeader className="pb-4">
                                <div className="flex justify-between items-start">
                                    <div>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <Cloud className="h-4.5 w-4.5 text-sky-500" /> Backup Cloudflare R2
                                        </CardTitle>
                                        <CardDescription className="mt-1">
                                            Kirim salinan database SQLite beserta arsip ZIP foto pesanan langsung ke Cloudflare R2 Object Storage Anda.
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" className={r2.is_configured ? "bg-sky-50 text-sky-700 border-sky-200" : "bg-slate-50 text-slate-500 border-slate-200"}>
                                        {r2.is_configured ? "Terkonfigurasi" : "Belum Siap"}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {/* R2 Connection Status Bar */}
                                {r2.is_configured ? (
                                    <div className="bg-sky-50 border border-sky-100 rounded-xl p-4 space-y-3">
                                        <div className="flex items-center gap-3">
                                            <div className="h-9 w-9 rounded-full bg-sky-500/10 flex items-center justify-center border border-sky-200">
                                                <Check className="h-5 w-5 text-sky-600" />
                                            </div>
                                            <div>
                                                <span className="text-[10px] text-sky-600 font-black uppercase tracking-wider block">Integrasi R2 Aktif</span>
                                                <span className="text-xs font-bold text-slate-700">Bucket: {r2.bucket}</span>
                                            </div>
                                        </div>
                                        <div className="text-xs text-slate-500 font-mono break-all bg-white/60 p-2 rounded border border-sky-100/60">
                                            Endpoint: {r2.endpoint}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="bg-slate-50 border rounded-xl p-4 flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="h-9 w-9 rounded-full bg-slate-500/10 flex items-center justify-center border border-slate-200">
                                                <ShieldAlert className="h-5 w-5 text-slate-500" />
                                            </div>
                                            <div>
                                                <span className="text-[10px] text-slate-500 font-black uppercase tracking-wider block">Status Koneksi</span>
                                                <span className="text-xs font-medium text-slate-600">Cloudflare R2 belum dikonfigurasi di file .env</span>
                                            </div>
                                        </div>
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
                                    <div className="text-[11px] text-slate-500 leading-normal space-y-1">
                                        <div>⏱️ <strong>Jadwal Otomatis (Laravel Scheduler):</strong></div>
                                        <ul className="list-disc list-inside pl-2 space-y-0.5 text-slate-600">
                                            <li><strong>Harian:</strong> Setiap hari jam 02:00 subuh (Retensi 30 hari)</li>
                                            <li><strong>Bulanan:</strong> Setiap tanggal 1 jam 03:00 (Retensi 12 bulan)</li>
                                            <li><strong>Tahunan:</strong> Setiap tanggal 1 Januari jam 04:00 (Retensi 5 tahun)</li>
                                        </ul>
                                    </div>
                                </div>

                                <div className="flex gap-3">
                                    <Button
                                        type="button"
                                        onClick={r2.is_configured ? triggerR2Backup : undefined}
                                        disabled={!r2.is_configured || runningBackup}
                                        className="flex-1 bg-sky-600 hover:bg-sky-500 text-white font-bold flex items-center justify-center gap-2 py-5"
                                    >
                                        {runningBackup ? (
                                            <><Loader2 className="h-4 w-4 animate-spin" /> Memproses Unggah R2...</>
                                        ) : (
                                            <><Cloud className="h-4 w-4" /> Jalankan Backup R2 Sekarang</>
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

                    {/* Kolom 3: Setup Instructions */}
                    <div className="lg:col-span-1">
                        <Card className="shadow-md border-t-4 border-t-slate-800 bg-white h-full flex flex-col justify-between">
                            <div>
                                <CardHeader className="pb-4 border-b">
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Server className="h-4.5 w-4.5 text-slate-700" /> Panduan Cloudflare R2
                                    </CardTitle>
                                    <CardDescription>
                                        Integrasi Cloudflare R2 dikonfigurasi melalui file environment <code className="bg-slate-100 px-1 py-0.5 rounded text-[10px] font-mono font-bold">.env</code> server untuk alasan keamanan.
                                    </CardDescription>
                                </CardHeader>

                                <CardContent className="pt-6 space-y-4">
                                    <div className="text-xs text-slate-600 leading-relaxed space-y-3">
                                        <p>
                                            Tambahkan variabel berikut ke file <code className="bg-slate-100 px-1 py-0.5 rounded text-[10px] font-mono font-bold">.env</code> Anda:
                                        </p>
                                        <div className="bg-slate-900 text-slate-100 p-3 rounded-lg font-mono text-[10px] space-y-1 select-all">
                                            <div>R2_ACCESS_KEY_ID=xxx</div>
                                            <div>R2_SECRET_ACCESS_KEY=xxx</div>
                                            <div>R2_BUCKET=xxx</div>
                                            <div>R2_ENDPOINT=https://xxx.r2.cloudflarestorage.com</div>
                                            <div>R2_URL=https://xxx.pub.r2.dev</div>
                                        </div>
                                        <div className="flex gap-2 bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-lg text-[11px]">
                                            <HelpCircle className="h-4 w-4 shrink-0 mt-0.5" />
                                            <div>
                                                Pastikan Anda telah membuat API Token di Cloudflare dengan akses <strong>Object Read & Write</strong> untuk bucket R2 Anda.
                                            </div>
                                        </div>
                                    </div>
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
                            <ShieldAlert className="h-5 w-5" /> Konfirmasi Pembersihan
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
                            <span>Saya mengonfirmasi telah mengunduh backup arsip ZIP/unggah ke Cloudflare R2 terlebih dahulu dan setuju menghapus file foto fisik ini secara permanen.</span>
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
