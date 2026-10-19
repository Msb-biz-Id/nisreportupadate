import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { 
    ArrowDownLeft, 
    ArrowUpRight, 
    AlertCircle, 
    Sparkles, 
    Search, 
    CheckCircle2, 
    Layers, 
    FileText, 
    Building2,
    PackageCheck,
    RotateCcw
} from 'lucide-react';
import { toast } from 'sonner';

const CATEGORY_LABELS = {
    KAIN_PUTIH: 'Kain Putih',
    KAIN_WARNA: 'Kain Warna',
    KERTAS: 'Kertas Printing',
    TINTA: 'Tinta Printing',
};

export default function RecordTransactionModal({ 
    isOpen, 
    onClose, 
    stocks = [], 
    allStocks = [],
    purchaseOrders = [],
    activeTab = 'KAIN_PUTIH',
    locations = [],
    departments = [],
    divisions = [],
    onOpenBulk,
    presetStock = null,
    presetType = null,
}) {
    const [selectedPOId, setSelectedPOId] = useState('');
    const [selectedPO, setSelectedPO] = useState(null);
    const [useAllStocks, setUseAllStocks] = useState(false);

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

    const activeStockList = useAllStocks || stocks.length === 0 ? (allStocks.length > 0 ? allStocks : stocks) : stocks;

    // Inisialisasi default stock atau preset
    useEffect(() => {
        if (isOpen) {
            if (presetStock) {
                setData(prev => ({
                    ...prev,
                    material_stock_id: String(presetStock.id),
                    transaction_type: presetType || prev.transaction_type,
                }));
            } else if (stocks.length > 0 && !data.material_stock_id) {
                setData('material_stock_id', String(stocks[0].id));
            }
        } else {
            setSelectedPOId('');
            setSelectedPO(null);
        }
    }, [isOpen, stocks, presetStock, presetType]);

    const selectedStock = (allStocks.length > 0 ? allStocks : stocks).find(
        s => String(s.id) === String(data.material_stock_id)
    );

    // Auto tarik data dari Pengajuan PO
    const handleSelectPO = (poId) => {
        setSelectedPOId(poId);
        if (!poId) {
            setSelectedPO(null);
            return;
        }

        const po = purchaseOrders.find(p => String(p.id) === String(poId));
        if (!po) return;

        setSelectedPO(po);

        // Cari kecocokan material stock
        let matchedStockId = data.material_stock_id;
        const availableStocks = allStocks.length > 0 ? allStocks : stocks;
        const poItemLower = (po.item_name || '').toLowerCase();

        const matched = availableStocks.find(s => {
            const stockNameLower = s.name.toLowerCase();
            return poItemLower.includes(stockNameLower) || stockNameLower.includes(poItemLower) ||
                (stockNameLower.includes('milano') && poItemLower.includes('milano')) ||
                (stockNameLower.includes('serena') && poItemLower.includes('serena')) ||
                (stockNameLower.includes('dryfit') && poItemLower.includes('dryfit')) ||
                (stockNameLower.includes('sublime') && poItemLower.includes('sublime')) ||
                (stockNameLower.includes('cyan') && poItemLower.includes('cyan')) ||
                (stockNameLower.includes('magenta') && poItemLower.includes('magenta')) ||
                (stockNameLower.includes('yellow') && poItemLower.includes('yellow')) ||
                (stockNameLower.includes('black') && poItemLower.includes('black')) ||
                (stockNameLower.includes('abu') && poItemLower.includes('abu'));
        });

        if (matched) {
            matchedStockId = String(matched.id);
        }

        setData(prev => ({
            ...prev,
            transaction_type: 'IN', // PO pasti transaksi masuk / inbound
            production_ref: po.po_number,
            quantity: String(po.quantity || ''),
            location_id: po.location_id ? String(po.location_id) : (locations[0] ? (typeof locations[0] === 'object' ? String(locations[0].id) : String(locations[0])) : ''),
            department: po.department || prev.department,
            division: po.division || prev.division,
            notes: `Penerimaan barang dari ${po.vendor_name} (PO: ${po.po_number})`,
            material_stock_id: matchedStockId,
        }));

        toast.success(`Data berhasil ditarik dari ${po.po_number}: ${po.item_name} (${po.quantity} ${po.unit})`);
    };

    const handleResetPO = () => {
        setSelectedPOId('');
        setSelectedPO(null);
        setData(prev => ({
            ...prev,
            production_ref: '',
            quantity: '',
            notes: '',
        }));
    };

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
                setSelectedPOId('');
                setSelectedPO(null);
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
            <DialogContent className="max-w-xl max-h-[92vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl p-0 shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                {/* Header */}
                <DialogHeader className="p-5 pb-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60">
                    <div className="flex items-start justify-between gap-3">
                        <DialogTitle className="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2.5">
                            <div className={`p-2 rounded-xl shadow-2xs ${
                                data.transaction_type === 'IN' 
                                    ? 'bg-emerald-600 text-white' 
                                    : 'bg-rose-600 text-white'
                            }`}>
                                {data.transaction_type === 'IN' ? <ArrowDownLeft className="h-5 w-5" /> : <ArrowUpRight className="h-5 w-5" />}
                            </div>
                            <div>
                                <span>Pencatatan Keluar / Masuk Material</span>
                                <p className="text-xs font-normal text-slate-500 mt-0.5">
                                    Catat arus keluar (pemakaian SPK) atau barang masuk (inbound pengadaan).
                                </p>
                            </div>
                        </DialogTitle>
                    </div>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="flex-1 flex flex-col overflow-hidden">
                    <div className="flex-1 overflow-y-auto p-5 space-y-4">
                        {/* 1. Mode Transaksi Switcher */}
                        <div>
                            <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                Jenis Transaksi
                            </Label>
                            <div className="grid grid-cols-2 gap-2 mt-1.5">
                                <button
                                    type="button"
                                    onClick={() => setData('transaction_type', 'OUT')}
                                    className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-bold transition ${
                                        data.transaction_type === 'OUT' 
                                            ? 'bg-rose-50 border-rose-500 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 shadow-2xs' 
                                            : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100'
                                    }`}
                                >
                                    <ArrowUpRight className="h-4 w-4" />
                                    Pemakaian / Distribusi (Keluar)
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setData('transaction_type', 'IN')}
                                    className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-bold transition ${
                                        data.transaction_type === 'IN' 
                                            ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 shadow-2xs' 
                                            : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100'
                                    }`}
                                >
                                    <ArrowDownLeft className="h-4 w-4" />
                                    Pembelian / Inbound (Masuk)
                                </button>
                            </div>
                        </div>

                        {/* Banner shortcut ke Bulk Record */}
                        {onOpenBulk && (
                            <div className="p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/60 flex items-center justify-between gap-3 text-xs">
                                <div className="flex items-center gap-2 text-indigo-900 dark:text-indigo-200">
                                    <Layers className="w-4 h-4 text-indigo-600 shrink-0" />
                                    <span>
                                        {data.transaction_type === 'IN' 
                                            ? 'Menerima banyak barang sekaligus dari PO/Supplier?' 
                                            : 'Mengeluarkan banyak material sekaligus untuk 1 SPK?'}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => { onClose(); onOpenBulk(data.transaction_type); }}
                                    className="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shrink-0 text-[11px] shadow-2xs"
                                >
                                    Buka Mode Massal (Bulk {data.transaction_type === 'IN' ? 'Masuk' : 'Keluar'})
                                </button>
                            </div>
                        )}

                        {/* 2. FITUR UTAMA: Tarik Data Otomatis dari Pengajuan PO (Khusus Mode Masuk / Inbound) */}
                        {data.transaction_type === 'IN' && (
                            <div className="p-3.5 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase flex items-center gap-1.5">
                                        <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                                        Tarik Data Otomatis dari Pengajuan PO
                                    </Label>
                                    {selectedPO && (
                                        <button
                                            type="button"
                                            onClick={handleResetPO}
                                            className="text-[11px] text-slate-500 hover:text-slate-700 flex items-center gap-1"
                                        >
                                            <RotateCcw className="w-3 h-3" /> Reset Pilihan PO
                                        </button>
                                    )}
                                </div>

                                <Select value={selectedPOId} onValueChange={handleSelectPO}>
                                    <SelectTrigger className="h-8 text-xs bg-white dark:bg-slate-800 border-emerald-300 dark:border-emerald-800">
                                        <SelectValue placeholder="— Pilih Nomor PO / Nama Pengajuan untuk Tarik Data —" />
                                    </SelectTrigger>
                                    <SelectContent className="max-h-60">
                                        {purchaseOrders.length === 0 ? (
                                            <SelectItem value="none" disabled>Tidak ada pengajuan PO aktif</SelectItem>
                                        ) : (
                                            purchaseOrders.map((po) => (
                                                <SelectItem key={po.id} value={String(po.id)}>
                                                    [{po.po_number}] {po.item_name} ({po.quantity} {po.unit}) — {po.vendor_name}
                                                </SelectItem>
                                            ))
                                        )}
                                    </SelectContent>
                                </Select>

                                {selectedPO && (
                                    <div className="p-2.5 rounded-lg bg-emerald-100/70 dark:bg-emerald-900/40 text-[11px] text-emerald-900 dark:text-emerald-200 space-y-1">
                                        <div className="flex items-center gap-1 font-bold">
                                            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                                            Berhasil menarik data PO {selectedPO.po_number}
                                        </div>
                                        <p className="text-slate-600 dark:text-slate-300">
                                            Barang: <strong>{selectedPO.item_name}</strong> • Kuantitas: <strong>{selectedPO.quantity} {selectedPO.unit}</strong> • Suplier: <strong>{selectedPO.vendor_name}</strong>
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* 3. Pilih Material Stock */}
                        <div>
                            <div className="flex items-center justify-between">
                                <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Pilih Material
                                </Label>
                                {allStocks.length > stocks.length && (
                                    <button
                                        type="button"
                                        onClick={() => setUseAllStocks(!useAllStocks)}
                                        className="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                                    >
                                        {useAllStocks ? 'Batasi Tab Ini' : 'Tampilkan Semua Kelompok'}
                                    </button>
                                )}
                            </div>

                            <Select 
                                value={String(data.material_stock_id)} 
                                onValueChange={(val) => setData('material_stock_id', val)}
                            >
                                <SelectTrigger className="mt-1 h-9 text-xs">
                                    <SelectValue placeholder="— Pilih Item Material —" />
                                </SelectTrigger>
                                <SelectContent className="max-h-60">
                                    {activeStockList.map((s) => (
                                        <SelectItem key={s.id} value={String(s.id)}>
                                            [{CATEGORY_LABELS[s.material_type] || s.material_type}] {s.name} ({s.code}) — Sisa: {s.current_stock} {s.unit}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {selectedStock && (
                                <div className="flex items-center justify-between text-[11px] mt-1.5 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800">
                                    <span>Satuan: <strong className="text-slate-800 dark:text-slate-200">{selectedStock.unit}</strong></span>
                                    <span>Batas Min: <strong className="text-slate-800 dark:text-slate-200">{selectedStock.minimum_threshold} {selectedStock.unit}</strong></span>
                                    <span>Stok Fisik: <strong className={selectedStock.is_critical ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold'}>{selectedStock.current_stock} {selectedStock.unit}</strong></span>
                                </div>
                            )}
                        </div>

                        {/* 4. Kuantitas & Tanggal Transaksi */}
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Kuantitas {selectedStock ? `(${selectedStock.unit})` : ''} *
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
                                <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Tanggal Transaksi *
                                </Label>
                                <Input
                                    type="date"
                                    value={data.usage_date}
                                    onChange={(e) => setData('usage_date', e.target.value)}
                                    className="mt-1 h-9 text-xs"
                                    required
                                />
                            </div>
                        </div>

                        {/* 5. Lokasi & Referensi SPK / PO */}
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Lokasi Ruko / Gudang
                                </Label>
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
                                <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    No. SPK / No. PO / Referensi
                                </Label>
                                <Input
                                    type="text"
                                    value={data.production_ref}
                                    onChange={(e) => setData('production_ref', e.target.value)}
                                    placeholder="Contoh: SPK-2026-081"
                                    className="mt-1 h-9 text-xs font-semibold"
                                />
                            </div>
                        </div>

                        {/* 6. Divisi & Operator */}
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Divisi Pemohon / Pemakai
                                </Label>
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
                                <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                    Operator / Penerima
                                </Label>
                                <Input
                                    type="text"
                                    value={data.operator_name}
                                    onChange={(e) => setData('operator_name', e.target.value)}
                                    placeholder="Nama staf / operator"
                                    className="mt-1 h-9 text-xs"
                                />
                            </div>
                        </div>

                        {/* 7. Catatan / Keterangan */}
                        <div>
                            <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase">
                                Keterangan / Catatan
                            </Label>
                            <Input
                                type="text"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder="Alasan pemakaian, kondisi kain, atau nama suplier pengirim..."
                                className="mt-1 h-9 text-xs"
                            />
                        </div>
                    </div>

                    {/* Footer */}
                    <DialogFooter className="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" size="sm" onClick={onClose} disabled={processing} className="text-xs">
                            Batal
                        </Button>
                        <Button 
                            type="submit" 
                            size="sm" 
                            disabled={processing}
                            className={`font-bold text-xs ${
                                data.transaction_type === 'IN' 
                                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white' 
                                    : 'bg-rose-600 hover:bg-rose-700 text-white'
                            } shadow-2xs`}
                        >
                            {processing ? 'Menyimpan...' : 'Simpan Transaksi'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
