import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    Layers, 
    Palette, 
    FileText, 
    Droplets, 
    Plus, 
    Search, 
    Filter, 
    AlertTriangle, 
    CheckCircle2, 
    ArrowDownLeft, 
    ArrowUpRight, 
    History, 
    Trash2, 
    Edit, 
    RefreshCw, 
    Calendar,
    SlidersHorizontal,
    PackageCheck,
    ChevronRight,
    Info
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { toast } from 'sonner';
import RecordTransactionModal from './Components/RecordTransactionModal';
import CreateStockModal from './Components/CreateStockModal';

const MATERIAL_CATEGORIES = [
    { 
        key: 'KAIN_PUTIH', 
        label: 'Kain Putih', 
        subtitle: 'Kontrol Stok & Batas Minimum',
        icon: Layers, 
        desc: 'Kontrol stok fisik harian & batas minimum reorder per roll/kg.',
        badge: 'Harian'
    },
    { 
        key: 'KAIN_WARNA', 
        label: 'Kain Warna', 
        subtitle: 'Log Pembelian & Biaya PO',
        icon: Palette, 
        desc: 'Pencatatan pembelian masuk proyek & riwayat distribusi langsung.',
        badge: 'Proyek'
    },
    { 
        key: 'KERTAS', 
        label: 'Kertas Printing', 
        subtitle: 'Media Sublime & Transfer',
        icon: FileText, 
        desc: 'Media cetak sublime roll & transfer paper gudang.',
        badge: 'Gudang'
    },
    { 
        key: 'TINTA', 
        label: 'Tinta Printing', 
        subtitle: '8 Varian Tinta & Cairan',
        icon: Droplets, 
        desc: '8 Varian warna tinta cetak & cairan cleaning maintenance.',
        badge: 'Produksi'
    },
];

export default function PurchasingMaterialsIndex({ 
    active_tab = 'KAIN_PUTIH', 
    stocks = [], 
    usages = { data: [], links: [] }, 
    metrics = {}, 
    locations = [],
    departments = [],
    divisions = [],
    filters = {}
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    // State Modals
    const [isTransactionOpen, setIsTransactionOpen] = useState(false);
    const [isCreateStockOpen, setIsCreateStockOpen] = useState(false);
    const [editingStock, setEditingStock] = useState(null);

    const activeMeta = MATERIAL_CATEGORIES.find(c => c.key === active_tab) || MATERIAL_CATEGORIES[0];
    const ActiveIcon = activeMeta.icon;

    const handleSelectCategory = (catKey) => {
        router.get(route('purchasing.materials.index'), {
            tab: catKey,
            search: '',
            date_from: dateFrom,
            date_to: dateTo,
        }, { preserveState: false });
    };

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(route('purchasing.materials.index'), {
            tab: active_tab,
            search,
            date_from: dateFrom,
            date_to: dateTo,
        }, { preserveState: true, replace: true });
    };

    const handleResetFilter = () => {
        setSearch('');
        setDateFrom('');
        setDateTo('');
        router.get(route('purchasing.materials.index'), { tab: active_tab });
    };

    const handleDeleteTransaction = (usageId, matName) => {
        if (!confirm(`Batalkan catatan transaksi untuk "${matName}"? Saldo stok fisik akan dikembalikan secara otomatis.`)) {
            return;
        }

        router.delete(route('purchasing.materials.transactions.destroy', usageId), {
            onSuccess: () => toast.success('Catatan transaksi berhasil dibatalkan.'),
            onError: () => toast.error('Gagal membatalkan transaksi.')
        });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    return (
        <AppLayout title={`Inventaris Material - ${activeMeta.label}`}>
            <Head title={`Inventaris Material - ${activeMeta.label}`} />

            <div className="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                {/* Header Title & Top Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400">
                                <PackageCheck className="h-5 w-5" />
                            </span>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                                Inventaris Material Khusus
                            </h1>
                        </div>
                        <p className="text-sm text-slate-500 mt-1">
                            Pencatatan persediaan fisik, batas minimum reorder, dan distribusi material khusus produksi.
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={() => { setEditingStock(null); setIsCreateStockOpen(true); }}
                            className="text-xs font-semibold"
                        >
                            <Plus className="h-4 w-4 mr-1.5" />
                            Tambah Master Item
                        </Button>
                        <Button 
                            size="sm" 
                            onClick={() => setIsTransactionOpen(true)}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm"
                        >
                            <SlidersHorizontal className="h-4 w-4 mr-1.5" />
                            Catat Keluar / Masuk
                        </Button>
                    </div>
                </div>

                {/* Main Two-Column Layout (Internal Sidebar + Content) */}
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
                    {/* Internal Left Sidebar (Vertical Navigation) */}
                    <Card className="lg:col-span-1 shadow-sm border-slate-200 dark:border-slate-800 sticky top-20">
                        <CardHeader className="pb-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40">
                            <CardTitle className="text-xs font-semibold tracking-wider text-slate-500 uppercase">
                                Kategori Material Khusus
                            </CardTitle>
                            <CardDescription className="text-[11px]">
                                Pilih material untuk melihat kontrol stok & log harian.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-2 space-y-1">
                            {MATERIAL_CATEGORIES.map((cat) => {
                                const Icon = cat.icon;
                                const isActive = active_tab === cat.key;
                                return (
                                    <button
                                        key={cat.key}
                                        type="button"
                                        onClick={() => handleSelectCategory(cat.key)}
                                        className={`w-full flex items-center justify-between p-3 rounded-xl text-left transition-all group ${
                                            isActive
                                                ? 'bg-slate-900 text-white dark:bg-indigo-600 shadow-sm font-semibold'
                                                : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                        }`}
                                    >
                                        <div className="flex items-center gap-3 min-w-0 pr-2">
                                            <div className={`p-2 rounded-lg shrink-0 ${
                                                isActive 
                                                    ? 'bg-white/10 text-white' 
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 group-hover:text-indigo-600'
                                            }`}>
                                                <Icon className="h-4 w-4" />
                                            </div>
                                            <div className="min-w-0">
                                                <span className="text-xs font-bold block truncate">
                                                    {cat.label}
                                                </span>
                                                <span className={`text-[11px] block truncate ${
                                                    isActive ? 'text-slate-300' : 'text-slate-400'
                                                }`}>
                                                    {cat.subtitle}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="shrink-0 flex items-center gap-1.5">
                                            <Badge 
                                                variant="outline" 
                                                className={`text-[10px] px-1.5 py-0 border ${
                                                    isActive 
                                                        ? 'border-white/30 text-white bg-white/10' 
                                                        : 'border-slate-200 dark:border-slate-700 text-slate-500'
                                                }`}
                                            >
                                                {cat.badge}
                                            </Badge>
                                            <ChevronRight className={`h-3.5 w-3.5 ${isActive ? 'text-white' : 'text-slate-400 opacity-60'}`} />
                                        </div>
                                    </button>
                                );
                            })}
                        </CardContent>

                        {/* Petunjuk Operasional Gudang */}
                        <div className="p-3 m-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 space-y-1">
                            <div className="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                <Info className="w-3.5 h-3.5 text-blue-500" />
                                Informasi Reorder
                            </div>
                            <p>
                                Jika stok fisik $\le$ batas minimum, sistem otomatis memicu <strong>Red Alert</strong> di dashboard purchasing untuk pengadaan ulang.
                            </p>
                        </div>
                    </Card>

                    {/* Right Content Column (Active Material Workspace) */}
                    <div className="lg:col-span-3 space-y-6">
                        {/* Active Material Header Banner */}
                        <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <div className="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                                    <ActiveIcon className="w-6 h-6" />
                                </div>
                                <div>
                                    <h2 className="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                        {activeMeta.label}
                                        <Badge variant="secondary" className="text-xs">
                                            {activeMeta.subtitle}
                                        </Badge>
                                    </h2>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        {activeMeta.desc}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* KPI Cards Header */}
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div className="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Total Item Terdaftar</span>
                                <div className="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1">{metrics.total_items || 0}</div>
                                <div className="text-[11px] text-slate-500 mt-1">Master item aktif</div>
                            </div>

                            <div className={`rounded-2xl p-4 border shadow-xs ${
                                (metrics.critical_items || 0) > 0 
                                    ? 'bg-rose-50/70 border-rose-200 dark:bg-rose-950/30 dark:border-rose-900' 
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'
                            }`}>
                                <div className="flex items-center justify-between">
                                    <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Stok Kritis (Red Alert)</span>
                                    {(metrics.critical_items || 0) > 0 && <AlertTriangle className="h-4 w-4 text-rose-600 animate-pulse" />}
                                </div>
                                <div className={`text-2xl font-black mt-1 ${(metrics.critical_items || 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100'}`}>
                                    {metrics.critical_items || 0}
                                </div>
                                <div className="text-[11px] text-slate-500 mt-1">
                                    {(metrics.critical_items || 0) > 0 ? 'Di bawah batas minimum reorder' : 'Seluruh stok dalam level aman'}
                                </div>
                            </div>

                            <div className="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Inbound Bulan Ini</span>
                                <div className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                                    +{Number(metrics.total_inbound_month || 0).toLocaleString()}
                                </div>
                                <div className="text-[11px] text-slate-500 mt-1">Total kuantitas barang masuk</div>
                            </div>

                            {active_tab === 'KAIN_WARNA' ? (
                                <div className="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-indigo-200 dark:border-indigo-900/60 shadow-xs">
                                    <span className="text-[10px] font-bold text-indigo-500 uppercase tracking-widest block">Total Biaya Pembelian (PO)</span>
                                    <div className="text-xl font-black text-indigo-700 dark:text-indigo-300 mt-1">
                                        {formatRupiah(metrics.total_purchase_cost || 0)}
                                    </div>
                                    <div className="text-[11px] text-slate-500 mt-1">Inbound purchase log & total biaya</div>
                                </div>
                            ) : (
                                <div className="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
                                    <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Pemakaian Bulan Ini</span>
                                    <div className="text-2xl font-black text-slate-800 dark:text-slate-200 mt-1">
                                        -{Number(metrics.total_outbound_month || 0).toLocaleString()}
                                    </div>
                                    <div className="text-[11px] text-slate-500 mt-1">Total pemakaian produksi</div>
                                </div>
                            )}
                        </div>

                        {/* Status Saldo Stok Fisik (Cards Matrix) */}
                        <Card className="shadow-xs border-slate-200 dark:border-slate-800">
                            <CardHeader className="pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wide">
                                            Status Saldo Stok Fisik ({activeMeta.label})
                                        </CardTitle>
                                        <CardDescription className="text-xs">
                                            {active_tab === 'KAIN_WARNA' 
                                                ? 'Rekap kuantitas barang masuk dan saldo tersedia untuk proyek pengerjaan.'
                                                : 'Level sisa stok dibandingkan batas minimum pemesanan ulang (threshold).'
                                            }
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" className="text-xs font-semibold">
                                        {stocks.length} Varian Terdaftar
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="p-4 sm:p-5">
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                                    {stocks.map((item) => {
                                        const pct = item.minimum_threshold > 0 
                                            ? Math.min(100, Math.round((item.current_stock / item.minimum_threshold) * 100))
                                            : 100;
                                        return (
                                            <div 
                                                key={item.id} 
                                                className={`p-4 rounded-xl border transition-all ${
                                                    item.is_critical 
                                                        ? 'bg-rose-50/40 border-rose-300 dark:bg-rose-950/20 dark:border-rose-900 ring-1 ring-rose-300/50' 
                                                        : 'bg-slate-50/60 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800 hover:border-slate-300'
                                                }`}
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="min-w-0">
                                                        <span className="font-mono text-[10px] font-bold text-slate-400 block">{item.code}</span>
                                                        <h3 className="font-bold text-xs text-slate-800 dark:text-slate-200 truncate" title={item.name}>{item.name}</h3>
                                                    </div>
                                                    <button 
                                                        type="button" 
                                                        onClick={() => { setEditingStock(item); setIsCreateStockOpen(true); }}
                                                        className="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 p-1 rounded-md"
                                                        title="Ubah Batas Minimum"
                                                    >
                                                        <Edit className="h-3.5 w-3.5" />
                                                    </button>
                                                </div>

                                                {/* Indikator Stok */}
                                                <div className="mt-3 flex items-baseline justify-between">
                                                    <div className="flex items-baseline gap-1.5">
                                                        <span className={`text-xl font-black font-mono ${item.is_critical ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100'}`}>
                                                            {item.current_stock}
                                                        </span>
                                                        <span className="text-xs font-bold text-slate-500">{item.unit}</span>
                                                    </div>
                                                    <span className="text-[11px] text-slate-400">
                                                        Min: <strong className="text-slate-600 dark:text-slate-300">{item.minimum_threshold}</strong>
                                                    </span>
                                                </div>

                                                {/* Progress Bar Status */}
                                                <div className="mt-2 w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                                    <div 
                                                        className={`h-full transition-all duration-500 ${item.is_critical ? 'bg-rose-500' : 'bg-emerald-500'}`} 
                                                        style={{ width: `${pct}%` }}
                                                    />
                                                </div>

                                                <div className="mt-2 flex items-center justify-between text-[10px]">
                                                    {item.is_critical ? (
                                                        <span className="inline-flex items-center text-rose-600 dark:text-rose-400 font-bold gap-1">
                                                            <AlertTriangle className="h-3 w-3" /> Stok Kritis
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-bold gap-1">
                                                            <CheckCircle2 className="h-3 w-3" /> Level Aman
                                                        </span>
                                                    )}
                                                    <span className="text-slate-400">{item.usages_count} riwayat log</span>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Filter & Riwayat Log Transaksi Harian */}
                        <Card className="shadow-xs border-slate-200 dark:border-slate-800">
                            <CardHeader className="pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <CardTitle className="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wide flex items-center gap-2">
                                            <History className="h-4 w-4 text-slate-500" />
                                            Riwayat Log Transaksi Masuk & Keluar
                                        </CardTitle>
                                        <CardDescription className="text-xs">
                                            Catatan kronologis penerimaan barang dan pemakaian produksi.
                                        </CardDescription>
                                    </div>

                                    {/* Search & Date Filter Form */}
                                    <form onSubmit={handleFilter} className="flex flex-wrap items-center gap-2">
                                        <div className="relative">
                                            <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
                                            <Input 
                                                type="text" 
                                                placeholder="Cari SPK, nama, operator..." 
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                className="h-8 pl-8 text-xs w-44"
                                            />
                                        </div>
                                        <Input 
                                            type="date" 
                                            value={dateFrom} 
                                            onChange={(e) => setDateFrom(e.target.value)} 
                                            className="h-8 text-xs w-32" 
                                        />
                                        <span className="text-slate-400 text-xs">s/d</span>
                                        <Input 
                                            type="date" 
                                            value={dateTo} 
                                            onChange={(e) => setDateTo(e.target.value)} 
                                            className="h-8 text-xs w-32" 
                                        />
                                        <Button type="submit" size="sm" variant="secondary" className="h-8 text-xs font-bold">
                                            Filter
                                        </Button>
                                        {(search || dateFrom || dateTo) && (
                                            <Button type="button" size="sm" variant="ghost" onClick={handleResetFilter} className="h-8 text-xs text-slate-500">
                                                Reset
                                            </Button>
                                        )}
                                    </form>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader className="bg-slate-50 dark:bg-slate-900/60">
                                            <TableRow>
                                                <TableHead className="w-10 text-center font-bold text-xs uppercase">No</TableHead>
                                                <TableHead className="font-bold text-xs uppercase">Tanggal</TableHead>
                                                <TableHead className="font-bold text-xs uppercase">Jenis</TableHead>
                                                <TableHead className="font-bold text-xs uppercase">Nama Material</TableHead>
                                                <TableHead className="text-right font-bold text-xs uppercase">Kuantitas</TableHead>
                                                <TableHead className="text-right font-bold text-xs uppercase">Saldo Akhir</TableHead>
                                                <TableHead className="font-bold text-xs uppercase">Divisi / Lokasi</TableHead>
                                                <TableHead className="font-bold text-xs uppercase">Operator & SPK</TableHead>
                                                <TableHead className="font-bold text-xs uppercase">Catatan</TableHead>
                                                <TableHead className="w-10 text-center font-bold text-xs uppercase sticky right-0 z-20 bg-slate-50 dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800">Aksi</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {usages.data && usages.data.length > 0 ? (
                                                usages.data.map((log, idx) => (
                                                    <TableRow key={log.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/50">
                                                        <TableCell className="text-center font-mono text-xs text-slate-400">
                                                            {idx + 1}
                                                        </TableCell>
                                                        <TableCell className="font-mono text-xs text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                                            {log.usage_date ? new Date(log.usage_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}
                                                        </TableCell>
                                                        <TableCell>
                                                            {Number(log.quantity_in) > 0 ? (
                                                                <span className="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                                    <ArrowDownLeft className="h-3 w-3" /> MASUK
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex items-center gap-1 text-[10px] font-black px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                                                    <ArrowUpRight className="h-3 w-3" /> KELUAR
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            <span className="font-bold text-xs text-slate-900 dark:text-slate-100 block">{log.material_name}</span>
                                                            <span className="text-[10px] text-slate-400 font-mono">{log.stock?.code}</span>
                                                        </TableCell>
                                                        <TableCell className="text-right font-mono font-bold text-xs whitespace-nowrap">
                                                            {Number(log.quantity_in) > 0 ? (
                                                                <span className="text-emerald-600 dark:text-emerald-400">+{Number(log.quantity_in).toLocaleString()} {log.unit}</span>
                                                            ) : (
                                                                <span className="text-rose-600 dark:text-rose-400">-{Number(log.quantity_out).toLocaleString()} {log.unit}</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-right font-mono font-bold text-xs text-slate-800 dark:text-slate-200">
                                                            {Number(log.balance_stock).toLocaleString()} {log.unit}
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            <div className="font-semibold text-slate-700 dark:text-slate-300">{log.division || log.department || '-'}</div>
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            <div className="font-medium text-slate-800 dark:text-slate-200">{log.operator_name || '-'}</div>
                                                            {log.production_ref && (
                                                                <span className="inline-block font-mono text-[10px] text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50 dark:bg-indigo-950/60 px-1.5 py-0.2 rounded mt-0.5">
                                                                    {log.production_ref}
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-xs text-slate-500 max-w-[180px] truncate" title={log.notes}>
                                                            {log.notes || '—'}
                                                        </TableCell>
                                                        <TableCell className="text-center sticky right-0 z-10 bg-white/95 dark:bg-slate-900/95 border-l border-slate-200 dark:border-slate-800">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDeleteTransaction(log.id, log.material_name)}
                                                                className="text-slate-400 hover:text-rose-600 p-1 rounded transition"
                                                                title="Batalkan & Hapus Catatan"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </button>
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={10} className="text-center py-10 text-slate-400 text-xs">
                                                        Belum ada catatan transaksi material pada periode ini.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>

                                {/* Pagination */}
                                {usages.links && usages.links.length > 3 && (
                                    <div className="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 p-3">
                                        <span className="text-xs text-slate-400">
                                            Menampilkan {usages.from || 0} - {usages.to || 0} dari {usages.total || 0} catatan
                                        </span>
                                        <div className="flex items-center gap-1">
                                            {usages.links.map((link, i) => (
                                                <Link
                                                    key={i}
                                                    href={link.url || '#'}
                                                    preserveScroll
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                    className={`px-2.5 py-1 text-xs rounded-lg font-bold transition ${
                                                        link.active
                                                            ? 'bg-indigo-600 text-white'
                                                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                                                    } ${!link.url ? 'opacity-40 pointer-events-none' : ''}`}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Modal Tambah Master Stock */}
            <CreateStockModal
                isOpen={isCreateStockOpen}
                onClose={() => { setIsCreateStockOpen(false); setEditingStock(null); }}
                editingStock={editingStock}
                activeTab={active_tab}
            />

            {/* Modal Catat Transaksi In/Out */}
            <RecordTransactionModal
                isOpen={isTransactionOpen}
                onClose={() => setIsTransactionOpen(false)}
                stocks={stocks}
                activeTab={active_tab}
                locations={locations}
                departments={departments}
                divisions={divisions}
            />
        </AppLayout>
    );
}
