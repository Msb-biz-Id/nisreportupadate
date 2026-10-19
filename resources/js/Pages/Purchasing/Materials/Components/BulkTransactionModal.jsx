import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { 
    Layers, 
    Plus, 
    Trash2, 
    AlertTriangle, 
    ArrowUpRight, 
    ArrowDownLeft, 
    PackageCheck,
    Palette,
    FileText,
    Droplets,
    Sparkles,
    FileDown,
    Building2,
    Calendar,
    UserCheck,
    Info
} from 'lucide-react';
import { toast } from 'sonner';

const CATEGORY_META = {
    KAIN_PUTIH: { label: 'Kain Putih', icon: Layers, color: 'text-blue-600 bg-blue-50 border-blue-200' },
    KAIN_WARNA: { label: 'Kain Warna', icon: Palette, color: 'text-purple-600 bg-purple-50 border-purple-200' },
    KERTAS: { label: 'Kertas Printing', icon: FileText, color: 'text-amber-600 bg-amber-50 border-amber-200' },
    TINTA: { label: 'Tinta Printing', icon: Droplets, color: 'text-rose-600 bg-rose-50 border-rose-200' },
};

export default function BulkTransactionModal({
    isOpen,
    onClose,
    allStocks = [],
    purchaseOrders = [],
    initialType = 'OUT',
    locations = [],
    departments = [],
    divisions = []
}) {
    const [selectedCategoryFilter, setSelectedCategoryFilter] = useState('ALL');
    const [itemSearchQuery, setItemSearchQuery] = useState('');
    const [selectedPOId, setSelectedPOId] = useState('');

    const { data, setData, post, processing, reset, errors } = useForm({
        transaction_type: initialType,
        usage_date: new Date().toISOString().slice(0, 10),
        location_id: locations.length > 0 ? (typeof locations[0] === 'object' ? String(locations[0].id) : String(locations[0])) : '',
        department: 'Produksi',
        division: 'Produksi',
        operator_name: '',
        production_ref: '',
        general_notes: '',
        items: [
            { material_stock_id: '', quantity: '', notes: '' }
        ]
    });

    // Sinkronisasi tipe saat modal dibuka
    useEffect(() => {
        if (isOpen) {
            setData(prev => ({
                ...prev,
                transaction_type: initialType || 'OUT',
                items: prev.items.length > 0 ? prev.items : [{ material_stock_id: allStocks[0] ? String(allStocks[0].id) : '', quantity: '', notes: '' }]
            }));
            setSelectedPOId('');
        }
    }, [isOpen, initialType]);

    // Smart matcher nama item ke master material stock
    const matchItemToStock = (itemName) => {
        if (!itemName || allStocks.length === 0) return allStocks[0] ? String(allStocks[0].id) : '';
        const norm = itemName.toLowerCase();
        
        // Exact / substring match
        const direct = allStocks.find(s => {
            const sName = s.name.toLowerCase();
            const sCode = s.code.toLowerCase();
            return norm.includes(sName) || sName.includes(norm) || (sCode && norm.includes(sCode));
        });
        if (direct) return String(direct.id);

        // Keyword rules
        if (norm.includes('milano')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('milano'));
            if (m) return String(m.id);
        }
        if (norm.includes('serena')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('serena'));
            if (m) return String(m.id);
        }
        if (norm.includes('dryfit') || norm.includes('bintik') || norm.includes('titik')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('dryfit') || s.name.toLowerCase().includes('bintik'));
            if (m) return String(m.id);
        }
        if (norm.includes('tc') || norm.includes('kain putih tc')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('tc'));
            if (m) return String(m.id);
        }
        if (norm.includes('sublime') || norm.includes('kertas')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('sublime') || s.material_type === 'KERTAS');
            if (m) return String(m.id);
        }
        if (norm.includes('cyan')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('cyan'));
            if (m) return String(m.id);
        }
        if (norm.includes('magenta')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('magenta'));
            if (m) return String(m.id);
        }
        if (norm.includes('yellow')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('yellow'));
            if (m) return String(m.id);
        }
        if (norm.includes('black') || norm.includes('hitam')) {
            const m = allStocks.find(s => s.name.toLowerCase().includes('black'));
            if (m) return String(m.id);
        }

        return allStocks[0] ? String(allStocks[0].id) : '';
    };

    // Auto-pull seluruh item dari Pengajuan PO
    const handleSelectPO = (poId) => {
        setSelectedPOId(poId);
        const po = purchaseOrders.find(p => String(p.id) === String(poId));
        if (!po) return;

        const sourceItems = (po.items && po.items.length > 0) 
            ? po.items 
            : [{ item_name: po.item_name, quantity: po.quantity, unit: po.unit }];

        const newItems = sourceItems.map(it => ({
            material_stock_id: matchItemToStock(it.item_name),
            quantity: it.quantity > 0 ? String(it.quantity) : '1',
            notes: `PO #${po.po_number}: ${it.item_name}`
        }));

        setData(prev => ({
            ...prev,
            production_ref: `PO: ${po.po_number}`,
            location_id: po.location_id ? String(po.location_id) : prev.location_id,
            department: po.department || prev.department,
            division: po.division || prev.division,
            general_notes: `Penerimaan dari PO #${po.po_number} (${po.vendor_name})`,
            items: newItems
        }));

        toast.success(`Berhasil menarik ${newItems.length} item dari Pengajuan PO ${po.po_number}!`);
    };

    const handleAddItemRow = (presetStockId = '') => {
        setData('items', [
            ...data.items,
            { material_stock_id: presetStockId || (allStocks[0] ? String(allStocks[0].id) : ''), quantity: '', notes: '' }
        ]);
    };

    const handleRemoveItemRow = (index) => {
        if (data.items.length <= 1) {
            toast.error('Minimal harus ada 1 material dalam transaksi massal.');
            return;
        }
        const updated = [...data.items];
        updated.splice(index, 1);
        setData('items', updated);
    };

    const handleItemChange = (index, field, value) => {
        const updated = [...data.items];
        updated[index] = { ...updated[index], [field]: value };
        setData('items', updated);
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!data.items || data.items.length === 0) {
            toast.error('Tambahkan minimal 1 item material.');
            return;
        }

        for (let i = 0; i < data.items.length; i++) {
            const item = data.items[i];
            if (!item.material_stock_id) {
                toast.error(`Baris #${i + 1}: Silakan pilih material.`);
                return;
            }
            if (!item.quantity || Number(item.quantity) <= 0) {
                toast.error(`Baris #${i + 1}: Kuantitas harus lebih dari 0.`);
                return;
            }
        }

        post(route('purchasing.materials.transactions.bulkStore'), {
            onSuccess: () => {
                toast.success(`Pencatatan massal material (${data.transaction_type === 'IN' ? 'Barang Masuk' : 'Pemakaian Keluar'}) berhasil disimpan!`);
                reset();
                onClose();
            },
            onError: (errs) => {
                const msg = Object.values(errs)[0] || 'Periksa kembali kelengkapan form input.';
                toast.error(msg);
            }
        });
    };

    const filteredCatalogStocks = allStocks.filter(s => {
        const matchCat = selectedCategoryFilter === 'ALL' || s.material_type === selectedCategoryFilter;
        const matchSearch = !itemSearchQuery || 
            s.name.toLowerCase().includes(itemSearchQuery.toLowerCase()) || 
            s.code.toLowerCase().includes(itemSearchQuery.toLowerCase());
        return matchCat && matchSearch;
    });

    const getStockById = (id) => allStocks.find(s => String(s.id) === String(id));
    const isInbound = data.transaction_type === 'IN';

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-4xl max-h-[92vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl p-0 shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                {/* Header */}
                <DialogHeader className="p-5 pb-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <DialogTitle className="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2.5">
                            <div className={`p-2 rounded-xl text-white shadow-xs ${isInbound ? 'bg-emerald-600' : 'bg-rose-600'}`}>
                                {isInbound ? <ArrowDownLeft className="h-5 w-5" /> : <PackageCheck className="h-5 w-5" />}
                            </div>
                            <div>
                                <span>
                                    {isInbound 
                                        ? 'Pencatatan Barang Masuk Massal (Bulk Inbound)' 
                                        : 'Pencatatan Pemakaian Massal (Bulk Keluar SPK)'}
                                </span>
                                <p className="text-xs font-normal text-slate-500 mt-0.5">
                                    {isInbound 
                                        ? 'Catat penerimaan beberapa material sekaligus atau tarik otomatis semua item dari Pengajuan PO tanpa berpindah tab.' 
                                        : 'Catat pengeluaran beberapa material produksi sekaligus ke operator tanpa harus berpindah-pindah tab kategori.'}
                                </p>
                            </div>
                        </DialogTitle>

                        {/* Switcher Mode IN / OUT */}
                        <div className="flex items-center gap-1 bg-slate-200/70 dark:bg-slate-800 p-1 rounded-xl text-xs font-bold">
                            <button
                                type="button"
                                onClick={() => setData('transaction_type', 'IN')}
                                className={`px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all ${
                                    isInbound
                                        ? 'bg-emerald-600 text-white shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'
                                }`}
                            >
                                <ArrowDownLeft className="w-3.5 h-3.5" />
                                Penerimaan (Masuk Massal)
                            </button>
                            <button
                                type="button"
                                onClick={() => setData('transaction_type', 'OUT')}
                                className={`px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all ${
                                    !isInbound
                                        ? 'bg-rose-600 text-white shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'
                                }`}
                            >
                                <ArrowUpRight className="w-3.5 h-3.5" />
                                Pemakaian (Keluar Massal)
                            </button>
                        </div>
                    </div>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="flex-1 flex flex-col overflow-hidden">
                    <div className="flex-1 overflow-y-auto p-5 space-y-5">
                        
                        {/* Auto-Tarik dari Pengajuan PO khusus mode MASUK / INBOUND */}
                        {isInbound && purchaseOrders && purchaseOrders.length > 0 && (
                            <div className="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/50 space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                        <FileDown className="w-4 h-4 text-emerald-600" />
                                        Tarik Otomatis dari Pengajuan PO (Inbound Masuk)
                                    </Label>
                                    <span className="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">
                                        Otomatis mengisi referensi, kuantitas & mencocokkan stok material
                                    </span>
                                </div>
                                <Select
                                    value={selectedPOId}
                                    onValueChange={handleSelectPO}
                                >
                                    <SelectTrigger className="h-8.5 bg-white dark:bg-slate-900 text-xs font-medium border-emerald-200 dark:border-emerald-800">
                                        <SelectValue placeholder="— Pilih No. PO untuk Tarik Seluruh Item Masuk —" />
                                    </SelectTrigger>
                                    <SelectContent className="max-h-64">
                                        {purchaseOrders.map((po) => (
                                            <SelectItem key={po.id} value={String(po.id)}>
                                                <div className="flex items-center justify-between gap-3 text-xs">
                                                    <span className="font-mono font-bold text-emerald-700 dark:text-emerald-400">[{po.po_number}]</span>
                                                    <span className="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[220px]">
                                                        {po.item_name} {po.items && po.items.length > 1 ? `(+${po.items.length - 1} item lain)` : ''}
                                                    </span>
                                                    <span className="text-slate-400 font-mono text-[11px]">({po.quantity} {po.unit})</span>
                                                    <span className="text-slate-500 font-medium text-[11px] truncate max-w-[120px]">• {po.vendor_name}</span>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {/* 1. Header Informasi Referensi & Pemakai/Penerima */}
                        <div className="p-4 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-3">
                            <div className="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                <Sparkles className="w-3.5 h-3.5 text-indigo-500" />
                                {isInbound ? 'Informasi Surat Jalan / PO Masuk' : 'Informasi Surat Tugas / SPK Produksi'}
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <Label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                        {isInbound ? 'No. PO / Surat Jalan Masuk *' : 'No. SPK / No. Order Keluar *'}
                                    </Label>
                                    <Input
                                        type="text"
                                        value={data.production_ref}
                                        onChange={(e) => setData('production_ref', e.target.value)}
                                        placeholder={isInbound ? 'Contoh: PO-2026-081 atau SJ-991' : 'Contoh: SPK-2026-081'}
                                        className="mt-1 h-8 text-xs font-semibold"
                                        required
                                    />
                                </div>
                                <div>
                                    <Label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                        Tanggal Transaksi *
                                    </Label>
                                    <Input
                                        type="date"
                                        value={data.usage_date}
                                        onChange={(e) => setData('usage_date', e.target.value)}
                                        className="mt-1 h-8 text-xs font-medium"
                                        required
                                    />
                                </div>
                                <div>
                                    <Label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                        Lokasi Gudang / Ruko
                                    </Label>
                                    <Select
                                        value={String(data.location_id)}
                                        onValueChange={(val) => setData('location_id', val)}
                                    >
                                        <SelectTrigger className="mt-1 h-8 text-xs">
                                            <SelectValue placeholder="Pilih Lokasi" />
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
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                                <div>
                                    <Label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                        {isInbound ? 'Divisi Penerima' : 'Divisi Pemohon'}
                                    </Label>
                                    <Select
                                        value={data.division}
                                        onValueChange={(val) => setData('division', val)}
                                    >
                                        <SelectTrigger className="mt-1 h-8 text-xs">
                                            <SelectValue placeholder="Pilih Divisi" />
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
                                    <Label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                        {isInbound ? 'Operator / Petugas Penerima' : 'Operator / Pemegang SPK'}
                                    </Label>
                                    <Input
                                        type="text"
                                        value={data.operator_name}
                                        onChange={(e) => setData('operator_name', e.target.value)}
                                        placeholder={isInbound ? 'Nama staf gudang penerima' : 'Nama staf pemotong / penjahit'}
                                        className="mt-1 h-8 text-xs"
                                    />
                                </div>
                                <div>
                                    <Label className="text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                        Catatan Umum Transaksi
                                    </Label>
                                    <Input
                                        type="text"
                                        value={data.general_notes}
                                        onChange={(e) => setData('general_notes', e.target.value)}
                                        placeholder={isInbound ? 'Kondisi barang / nama suplier...' : 'Nama proyek / baju jersey...'}
                                        className="mt-1 h-8 text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* 2. Daftar Material Multi-Row */}
                        <div className="space-y-2">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <Label className="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <Layers className="w-3.5 h-3.5 text-indigo-600" />
                                    {isInbound 
                                        ? `Daftar Material yang Diterima Masuk (${data.items.length} Item)` 
                                        : `Daftar Material yang Dikeluarkan (${data.items.length} Item)`}
                                </Label>

                                {/* Filter Kelompok untuk mempermudah pemilihan */}
                                <div className="flex flex-wrap items-center gap-1">
                                    <button
                                        type="button"
                                        onClick={() => setSelectedCategoryFilter('ALL')}
                                        className={`px-2 py-1 rounded-md text-[11px] font-semibold transition ${
                                            selectedCategoryFilter === 'ALL'
                                                ? 'bg-slate-900 text-white dark:bg-indigo-600'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'
                                        }`}
                                    >
                                        Semua Kelompok
                                    </button>
                                    {Object.entries(CATEGORY_META).map(([key, meta]) => (
                                        <button
                                            key={key}
                                            type="button"
                                            onClick={() => setSelectedCategoryFilter(key)}
                                            className={`px-2 py-1 rounded-md text-[11px] font-semibold transition flex items-center gap-1 ${
                                                selectedCategoryFilter === key
                                                    ? 'bg-slate-900 text-white dark:bg-indigo-600'
                                                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'
                                            }`}
                                        >
                                            {meta.label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Multi-Item Table Rows */}
                            <div className="space-y-2.5">
                                {data.items.map((item, idx) => {
                                    const stock = getStockById(item.material_stock_id);
                                    const isExceeding = stock && !isInbound && Number(item.quantity || 0) > stock.current_stock;
                                    const meta = stock ? CATEGORY_META[stock.material_type] : null;

                                    return (
                                        <div
                                            key={idx}
                                            className={`p-3.5 rounded-xl border transition-all ${
                                                isExceeding
                                                    ? 'bg-rose-50/50 border-rose-300 dark:bg-rose-950/20 dark:border-rose-900'
                                                    : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-800 shadow-2xs'
                                            }`}
                                        >
                                            <div className="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                                {/* Nomor urut & Kelompok */}
                                                <div className="sm:col-span-5 space-y-1">
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-[10px] font-bold text-slate-400 uppercase">
                                                            Item #{idx + 1} {meta && <span className={`ml-1 px-1.5 py-0.2 rounded text-[9px] ${meta.color}`}>{meta.label}</span>}
                                                        </span>
                                                        {stock && (
                                                            <span className="text-[10px] font-mono text-slate-500">
                                                                {isInbound ? (
                                                                    <>Stok Fisik: <strong>{stock.current_stock} {stock.unit}</strong></>
                                                                ) : (
                                                                    <>Sisa: <strong className={stock.is_critical ? 'text-rose-600' : 'text-emerald-600'}>{stock.current_stock} {stock.unit}</strong></>
                                                                )}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <Select
                                                        value={String(item.material_stock_id)}
                                                        onValueChange={(val) => handleItemChange(idx, 'material_stock_id', val)}
                                                    >
                                                        <SelectTrigger className="h-8 text-xs font-semibold">
                                                            <SelectValue placeholder="— Pilih Material —" />
                                                        </SelectTrigger>
                                                        <SelectContent className="max-h-60">
                                                            {allStocks.map((s) => (
                                                                <SelectItem key={s.id} value={String(s.id)}>
                                                                    [{CATEGORY_META[s.material_type]?.label.slice(0, 4) || s.material_type}] {s.name} ({s.code}) — {s.current_stock} {s.unit}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>

                                                {/* Kuantitas Input */}
                                                <div className="sm:col-span-3 space-y-1">
                                                    <Label className="text-[10px] font-bold text-slate-500 uppercase block">
                                                        {isInbound ? 'Qty Masuk' : 'Qty Keluar'} {stock ? `(${stock.unit})` : ''} *
                                                    </Label>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        value={item.quantity}
                                                        onChange={(e) => handleItemChange(idx, 'quantity', e.target.value)}
                                                        placeholder="0.00"
                                                        className={`h-8 text-xs font-mono font-bold ${
                                                            isExceeding ? 'border-rose-500 text-rose-600 focus:ring-rose-500' : ''
                                                        }`}
                                                        required
                                                    />
                                                </div>

                                                {/* Catatan Item */}
                                                <div className="sm:col-span-3 space-y-1">
                                                    <Label className="text-[10px] font-bold text-slate-500 uppercase block">
                                                        Keterangan Item
                                                    </Label>
                                                    <Input
                                                        type="text"
                                                        value={item.notes}
                                                        onChange={(e) => handleItemChange(idx, 'notes', e.target.value)}
                                                        placeholder={isInbound ? 'Nama kain / roll ke-...' : 'Pola potong / kebutuhan...'}
                                                        className="h-8 text-xs"
                                                    />
                                                </div>

                                                {/* Action Hapus Row */}
                                                <div className="sm:col-span-1 flex items-end justify-center pt-3 sm:pt-0">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleRemoveItemRow(idx)}
                                                        className="h-8 w-8 p-0 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg"
                                                        title="Hapus Baris"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </div>

                                            {/* Preview Saldo Baru */}
                                            {stock && Number(item.quantity) > 0 && (
                                                <div className="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px]">
                                                    <span className="text-slate-500">
                                                        {isInbound ? (
                                                            <span className="text-emerald-600 font-semibold flex items-center gap-1">
                                                                <ArrowDownLeft className="w-3 h-3" />
                                                                Saldo akhir bertambah menjadi: {(Number(stock.current_stock) + Number(item.quantity)).toLocaleString()} {stock.unit}
                                                            </span>
                                                        ) : (
                                                            <span className={isExceeding ? 'text-rose-600 font-semibold' : 'text-slate-600'}>
                                                                Sisa stok akhir: {Math.max(0, Number(stock.current_stock) - Number(item.quantity)).toLocaleString()} {stock.unit}
                                                            </span>
                                                        )}
                                                    </span>
                                                    {isExceeding && (
                                                        <span className="text-rose-600 font-bold flex items-center gap-1">
                                                            <AlertTriangle className="w-3 h-3" /> Melebihi saldo fisik!
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Tombol Tambah Baris */}
                            <div className="pt-1 flex items-center justify-between">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleAddItemRow()}
                                    className="text-xs font-semibold text-indigo-600 border-indigo-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                                >
                                    <Plus className="w-3.5 h-3.5 mr-1" />
                                    Tambah Baris Material
                                </Button>

                                <span className="text-xs text-slate-400 font-medium">
                                    Total <strong>{data.items.length}</strong> material dalam batch ini
                                </span>
                            </div>
                        </div>

                        {/* Quick Add Presets: Klik Cepat untuk menambahkan material */}
                        <div className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-2">
                            <div className="flex items-center justify-between text-xs font-semibold text-slate-500">
                                <span className="flex items-center gap-1">
                                    <Sparkles className="w-3.5 h-3.5 text-amber-500" />
                                    Katalog Cepat (Klik untuk langsung menambahkan ke tabel input)
                                </span>
                                <span className="text-[11px] text-slate-400">{filteredCatalogStocks.length} item</span>
                            </div>
                            <div className="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto pr-1">
                                {filteredCatalogStocks.slice(0, 16).map((stock) => (
                                    <button
                                        key={stock.id}
                                        type="button"
                                        onClick={() => handleAddItemRow(String(stock.id))}
                                        className="text-[11px] px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-slate-700 dark:text-slate-300 flex items-center gap-1.5 transition shadow-2xs hover:bg-indigo-50/50"
                                    >
                                        <Plus className="w-3 h-3 text-indigo-500" />
                                        <span className="font-medium truncate max-w-[150px]">{stock.name}</span>
                                        <span className="text-[10px] text-slate-400 font-mono">({stock.current_stock} {stock.unit})</span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <DialogFooter className="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 flex items-center justify-between gap-3">
                        <div className="text-xs text-slate-500">
                            Mode: <strong className={isInbound ? 'text-emerald-600' : 'text-rose-600'}>
                                {isInbound ? 'Penerimaan Masuk (Inbound)' : 'Pemakaian Keluar (Outbound)'}
                            </strong> • Ref: <strong>{data.production_ref || '-'}</strong>
                        </div>

                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={onClose}
                                disabled={processing}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                                className={`text-xs font-bold ${
                                    isInbound
                                        ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                        : 'bg-rose-600 hover:bg-rose-700 text-white'
                                } shadow-sm`}
                            >
                                {processing 
                                    ? 'Menyimpan...' 
                                    : isInbound
                                        ? `Simpan Semua ${data.items.length} Barang Masuk (Bulk)`
                                        : `Simpan Semua ${data.items.length} Pengeluaran (Bulk)`}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
