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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Truck, Loader2, PackageCheck } from 'lucide-react';
import { toast } from 'sonner';

export default function ShippingDialog({ open, onOpenChange, order, ekspedisiList = [], onSuccess }) {
    const { data, setData, patch, processing, errors } = useForm({
        ekspedisi_id: '',
        no_resi: '',
    });

    useEffect(() => {
        if (order) {
            setData({
                ekspedisi_id: order.ekspedisi_id || '',
                no_resi: order.no_resi || '',
            });
        }
    }, [order, open]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!order?.id) return;

        patch(route('orders.shipping.update', order.id), {
            preserveScroll: true,
            only: ['items', 'order', 'orders', 'flash'],
            onSuccess: () => {
                toast.success(`Data pengiriman PO ${order.no_po} berhasil disimpan!`);
                onOpenChange(false);
                if (onSuccess) onSuccess();
            },
            onError: () => {
                toast.error('Gagal memperbarui data pengiriman. Silakan periksa kembali.');
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-lg font-bold text-slate-900">
                        <Truck className="h-5 w-5 text-indigo-600" />
                        Input Ekspedisi & No. Resi
                    </DialogTitle>
                    <DialogDescription className="text-xs text-slate-500">
                        Perbarui kurir ekspedisi dan nomor resi untuk pesanan ini:
                    </DialogDescription>
                    <div className="mt-2 rounded-lg bg-slate-50 p-2.5 border border-slate-200 text-left">
                        <div className="font-mono font-bold text-slate-800 text-sm">
                            {order?.no_po}
                        </div>
                        {order?.nama_po && (
                            <div className="text-xs font-medium text-slate-600 truncate mt-0.5">
                                {order.nama_po}
                            </div>
                        )}
                        {order?.pelanggan && (
                            <div className="text-[11px] text-muted-foreground mt-1">
                                Pelanggan: <span className="font-semibold text-slate-700">{order.pelanggan}</span>
                            </div>
                        )}
                    </div>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 py-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="ekspedisi_id" className="text-xs font-semibold text-slate-700">
                            Pilih Ekspedisi / Kurir
                        </Label>
                        <Select
                            value={data.ekspedisi_id || ''}
                            onValueChange={(val) => setData('ekspedisi_id', val)}
                        >
                            <SelectTrigger id="ekspedisi_id" className="w-full bg-white">
                                <SelectValue placeholder="-- Pilih Ekspedisi --" />
                            </SelectTrigger>
                            <SelectContent className="max-h-60">
                                {ekspedisiList.map((e) => (
                                    <SelectItem key={e.id} value={e.id}>
                                        {e.nama}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.ekspedisi_id && (
                            <p className="text-xs text-red-500 font-medium">{errors.ekspedisi_id}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="no_resi" className="text-xs font-semibold text-slate-700">
                            Nomor Resi Pengiriman
                        </Label>
                        <Input
                            id="no_resi"
                            type="text"
                            placeholder="Contoh: JNE0192837192"
                            value={data.no_resi}
                            onChange={(e) => setData('no_resi', e.target.value)}
                            className="font-mono text-sm uppercase bg-white"
                        />
                        {errors.no_resi && (
                            <p className="text-xs text-red-500 font-medium">{errors.no_resi}</p>
                        )}
                    </div>

                    <DialogFooter className="mt-6 flex flex-row justify-end gap-2 pt-2 border-t">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center gap-1.5"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <PackageCheck className="h-4 w-4" />
                                    Simpan Data
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
