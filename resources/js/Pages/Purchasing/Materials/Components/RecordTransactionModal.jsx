import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowDownLeft, ArrowUpRight, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';

export default function RecordTransactionModal({ 
    isOpen, 
    onClose, 
    stocks = [], 
    activeTab = 'KAIN_PUTIH',
    locations = [],
    departments = [],
    divisions = []
}) {
    const { data, setData, post, processing, reset, errors } = useForm({
        material_stock_id: '',
        transaction_type: 'OUT',
        quantity: '',
        usage_date: new Date().toISOString().slice(0, 10),
        location_id: '',
        department: 'Produksi',
        division: 'Produksi',
        operator_name: '',
        production_ref: '',
        notes: '',
    });

    useEffect(() => {
        if (isOpen && stocks.length > 0 && !data.material_stock_id) {
            setData('material_stock_id', String(stocks[0].id));
        }
    }, [isOpen, stocks]);

    const selectedStock = stocks.find(s => String(s.id) === String(data.material_stock_id));

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!data.material_stock_id) {
            toast.error('Pilih material terlebih dahulu.');
            return;
        }
        if (!data.quantity || Number(data.quantity) <= 0) {
            toast.error('Jumlah kuantitas harus lebih dari 0.');
            return;
        }

        post(route('purchasing.materials.transactions.store'), {
            onSuccess: () => {
                toast.success('Transaksi material berhasil dicatat dan saldo stok diperbarui.');
                reset();
                onClose();
            },
            onError: (errs) => {
                const msg = Object.values(errs)[0] || 'Periksa kembali kelengkapan form input.';
                toast.error(msg);
            }
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-lg bg-white rounded-2xl p-6 shadow-2xl">
                <DialogHeader className="border-b border-slate-100 pb-3">
                    <DialogTitle className="text-base font-black text-slate-800 flex items-center gap-2">
                        <div className={`p-2 rounded-xl ${data.transaction_type === 'IN' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'}`}>
                            {data.transaction_type === 'IN' ? <ArrowDownLeft className="h-5 w-5" /> : <ArrowUpRight className="h-5 w-5" />}
                        </div>
                        Pencatatan Keluar / Masuk Material
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 pt-2">
                    {/* Mode Transaksi: IN vs OUT */}
                    <div>
                        <Label className="text-xs font-bold text-slate-700 uppercase">Jenis Transaksi</Label>
                        <div className="grid grid-cols-2 gap-2 mt-1.5">
                            <button
                                type="button"
                                onClick={() => setData('transaction_type', 'OUT')}
                                className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-black transition ${
                                    data.transaction_type === 'OUT' 
                                        ? 'bg-rose-50 border-rose-500 text-rose-700 shadow-sm' 
                                        : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                                }`}
                            >
                                <ArrowUpRight className="h-4 w-4" />
                                Pemakaian / Distribusi (Keluar)
                            </button>
                            <button
                                type="button"
                                onClick={() => setData('transaction_type', 'IN')}
                                className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-black transition ${
                                    data.transaction_type === 'IN' 
                                        ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-sm' 
                                        : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                                }`}
                            >
                                <ArrowDownLeft className="h-4 w-4" />
                                Pembelian / Inbound (Masuk)
                            </button>
                        </div>
                    </div>

                    {/* Pilih Material */}
                    <div>
                        <Label className="text-xs font-bold text-slate-700 uppercase">Pilih Material</Label>
                        <Select 
                            value={String(data.material_stock_id)} 
                            onValueChange={(val) => setData('material_stock_id', val)}
                        >
                            <SelectTrigger className="mt-1 h-9 text-xs">
                                <SelectValue placeholder="— Pilih Item Material —" />
                            </SelectTrigger>
                            <SelectContent>
                                {stocks.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>
                                        {s.name} ({s.code}) — Sisa: {s.current_stock} {s.unit}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {selectedStock && (
                            <div className="flex items-center justify-between text-[11px] mt-1 text-slate-500">
                                <span>Satuan: <strong className="text-slate-700">{selectedStock.unit}</strong></span>
                                <span>Batas Min: <strong className="text-slate-700">{selectedStock.minimum_threshold} {selectedStock.unit}</strong></span>
                                <span>Stok Fisik: <strong className={selectedStock.is_critical ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold'}>{selectedStock.current_stock} {selectedStock.unit}</strong></span>
                            </div>
                        )}
                    </div>

                    {/* Qty & Tanggal */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">
                                Kuantitas {selectedStock ? `(${selectedStock.unit})` : ''}
                            </Label>
                            <Input
                                type="number"
                                step="0.01"
                                min="0.01"
                                value={data.quantity}
                                onChange={(e) => setData('quantity', e.target.value)}
                                placeholder="0.00"
                                className="mt-1 h-9 text-xs font-mono font-bold"
                                required
                            />
                        </div>
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">Tanggal Transaksi</Label>
                            <Input
                                type="date"
                                value={data.usage_date}
                                onChange={(e) => setData('usage_date', e.target.value)}
                                className="mt-1 h-9 text-xs"
                                required
                            />
                        </div>
                    </div>

                    {/* Lokasi & Referensi Produksi */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">Lokasi Ruko / Gudang</Label>
                            <Select 
                                value={String(data.location_id)} 
                                onValueChange={(val) => setData('location_id', val)}
                            >
                                <SelectTrigger className="mt-1 h-9 text-xs">
                                    <SelectValue placeholder="— Pilih Lokasi —" />
                                </SelectTrigger>
                                <SelectContent>
                                    {locations.map((loc, idx) => {
                                        const id = typeof loc === 'object' ? String(loc.id) : String(loc);
                                        const name = typeof loc === 'object' ? loc.name : loc;
                                        return (
                                            <SelectItem key={id || idx} value={id}>
                                                {name}
                                            </SelectItem>
                                        );
                                    })}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">No. SPK / No. PO / Referensi</Label>
                            <Input
                                type="text"
                                value={data.production_ref}
                                onChange={(e) => setData('production_ref', e.target.value)}
                                placeholder="Contoh: SPK-2026-081"
                                className="mt-1 h-9 text-xs"
                            />
                        </div>
                    </div>

                    {/* Divisi & Operator */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">Divisi Pemohon / Pemakai</Label>
                            <Select 
                                value={data.division} 
                                onValueChange={(val) => setData('division', val)}
                            >
                                <SelectTrigger className="mt-1 h-9 text-xs">
                                    <SelectValue placeholder="— Pilih Divisi —" />
                                </SelectTrigger>
                                <SelectContent>
                                    {divisions.map((div) => (
                                        <SelectItem key={div} value={div}>
                                            {div}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">Operator / Penerima</Label>
                            <Input
                                type="text"
                                value={data.operator_name}
                                onChange={(e) => setData('operator_name', e.target.value)}
                                placeholder="Nama staf / operator"
                                className="mt-1 h-9 text-xs"
                            />
                        </div>
                    </div>

                    {/* Catatan */}
                    <div>
                        <Label className="text-xs font-bold text-slate-700 uppercase">Keterangan / Catatan</Label>
                        <Input
                            type="text"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Alasan pemakaian, kondisi kain, atau nama suplier pengirim..."
                            className="mt-1 h-9 text-xs"
                        />
                    </div>

                    <DialogFooter className="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" size="sm" onClick={onClose} disabled={processing}>
                            Batal
                        </Button>
                        <Button 
                            type="submit" 
                            size="sm" 
                            disabled={processing}
                            className={`font-bold ${data.transaction_type === 'IN' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-900 hover:bg-slate-800'} text-white`}
                        >
                            {processing ? 'Menyimpan...' : 'Simpan Transaksi'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
