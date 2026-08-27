import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Download, Trash2, ShieldAlert, Archive, CheckCircle, HardDrive } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { toast } from 'sonner';

export default function Backup({ stats }) {
    const [confirmOpen, setConfirmOpen] = useState(false);

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

    return (
        <AppLayout title="Backup & Arsip Aset">
            <Head title="Backup & Arsip Aset" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Backup & Pembersihan Aset</h1>
                    <p className="text-sm text-muted-foreground mt-1">
                        Kelola kapasitas ruang server dengan mengunduh salinan ZIP arsip foto pesanan (orders), serta bersihkan berkas gambar lama yang sudah selesai.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Card: Local ZIP Download */}
                    <Card className="shadow-md border-t-4 border-t-indigo-500 bg-white">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-slate-700">
                                <Archive className="h-4 w-4 text-indigo-500" /> ZIP Download
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Download manual semua foto pesanan ke komputer lokal dalam format arsip .zip.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="bg-slate-50 border rounded-xl p-3 flex justify-between items-center text-xs">
                                <div>
                                    <span className="text-[10px] text-slate-400 block font-semibold">Total Aset Gambar</span>
                                    <span className="font-bold text-slate-800 text-sm">{stats.total_size_human}</span>
                                </div>
                                <div className="text-right">
                                    <span className="text-[10px] text-slate-400 block font-semibold">Jumlah Berkas</span>
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
                                Hapus file gambar pesanan lama yang sudah selesai / terkirim (lebih dari 30 hari).
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="bg-red-50/50 border border-red-100 rounded-xl p-3 flex justify-between items-center text-xs">
                                <div>
                                    <span className="text-[10px] text-red-500 block font-semibold">Bisa Dibersihkan</span>
                                    <span className="font-bold text-red-700 text-sm">{stats.cleanup_size_human}</span>
                                </div>
                                <div className="text-right">
                                    <span className="text-[10px] text-red-500 block font-semibold">Berkas PO Selesai</span>
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
                            <span>Saya mengonfirmasi setuju menghapus file foto fisik pesanan lama ini secara permanen.</span>
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
