import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Calendar, Clock, AlertCircle, Sparkles } from 'lucide-react';
import { toast } from 'sonner';
import { formatDate } from '@/lib/utils';

export default function SetTimelineDialog({ open, onOpenChange, order, onSuccess }) {
    const { data, setData, patch, processing, errors } = useForm({
        start_production_date: '',
        end_production_date: '',
    });

    useEffect(() => {
        if (order && open) {
            const todayStr = new Date().toISOString().slice(0, 10);
            setData({
                start_production_date: order.start_production_date?.slice(0, 10) || order.tanggal_masuk?.slice(0, 10) || todayStr,
                end_production_date: order.end_production_date?.slice(0, 10) || '',
            });
        }
    }, [order, open]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!order?.id) return;

        if (!data.end_production_date) {
            toast.error('Harap isi tanggal deadline / selesai produksi.');
            return;
        }

        // Tutup modal langsung agar UI responsif seketika
        onOpenChange(false);
        const loadingToast = toast.loading(`Menyimpan deadline produksi PO ${order.no_po}...`);

        patch(route('orders.timeline.update', order.id), {
            preserveScroll: true,
            only: ['columns', 'items', 'order', 'orders', 'flash'],
            onSuccess: () => {
                toast.dismiss(loadingToast);
                toast.success(`Deadline produksi PO ${order.no_po} berhasil disimpan! PO berpindah ke tahap Review Produksi.`);
                if (onSuccess) onSuccess();
            },
            onError: (errs) => {
                toast.dismiss(loadingToast);
                toast.error(errs.end_production_date || errs.start_production_date || 'Gagal menyimpan deadline produksi.');
                onOpenChange(true); // Buka kembali jika ada error validasi
            },
        });
    };

    if (!order) return null;

    const custDeadline = order.deadline_customer || order.deadlineCustomer;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-lg font-bold text-slate-900">
                        <Calendar className="h-5 w-5 text-indigo-600" />
                        Set Deadline Produksi
                    </DialogTitle>
                    <DialogDescription className="text-xs text-slate-500">
                        Tentukan jadwal produksi untuk PO <span className="font-mono font-bold text-slate-800">{order.no_po}</span> ({order.nama_po}).
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 py-2">
                    {/* Customer Deadline Alert */}
                    <div className="rounded-lg bg-blue-50/70 border border-blue-200/80 p-3 text-xs flex items-start gap-2.5">
                        <AlertCircle className="h-4 w-4 text-blue-600 shrink-0 mt-0.5" />
                        <div className="space-y-0.5">
                            <span className="font-semibold text-blue-900 block">Batas Waktu Pengiriman Customer:</span>
                            <p className="text-blue-800">
                                {custDeadline ? (
                                    <span className="font-bold underline decoration-blue-400">
                                        {formatDate(custDeadline)}
                                    </span>
                                ) : (
                                    <span className="italic text-slate-500">Belum ditentukan customer</span>
                                )}
                            </p>
                            <p className="text-[11px] text-blue-600/90 pt-0.5">
                                Pastikan target selesai produksi selesai sebelum atau sesuai deadline customer di atas.
                            </p>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="start_production_date" className="text-xs font-semibold text-slate-700">
                                Mulai Produksi
                            </Label>
                            <Input
                                id="start_production_date"
                                type="date"
                                value={data.start_production_date}
                                onChange={(e) => setData('start_production_date', e.target.value)}
                                className="h-9 text-xs"
                            />
                            {errors.start_production_date && (
                                <p className="text-[11px] text-red-500">{errors.start_production_date}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="end_production_date" className="text-xs font-bold text-slate-900 flex items-center gap-1">
                                    <Clock className="h-3.5 w-3.5 text-indigo-600" />
                                    Deadline Produksi <span className="text-red-500">*</span>
                                </Label>
                                {custDeadline && (
                                    <button
                                        type="button"
                                        onClick={() => setData('end_production_date', custDeadline.slice(0, 10))}
                                        className="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium underline"
                                    >
                                        Samakan dgn Customer
                                    </button>
                                )}
                            </div>
                            <Input
                                id="end_production_date"
                                type="date"
                                required
                                value={data.end_production_date}
                                onChange={(e) => setData('end_production_date', e.target.value)}
                                className="h-9 text-xs font-semibold border-indigo-300 focus-visible:ring-indigo-500 bg-indigo-50/20"
                            />
                            {errors.end_production_date && (
                                <p className="text-[11px] text-red-500">{errors.end_production_date}</p>
                            )}
                            <p className="text-[10px] text-slate-500">
                                Setelah disimpan, status PO otomatis berpindah ke tahap <strong>Review Produksi</strong>.
                            </p>
                        </div>
                    </div>

                    <DialogFooter className="gap-2 pt-2 border-t">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                            className="text-xs"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            disabled={processing}
                            className="gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold"
                        >
                            <Sparkles className="h-3.5 w-3.5" />
                            {processing ? 'Menyimpan...' : 'Simpan & Review Produksi'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
