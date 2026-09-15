import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { StatGrid, StatusBreakdown, TopList, POListWidget, POSiapDikirimWidget } from '@/Components/Widgets';
import { formatRupiah } from '@/lib/utils';
import { 
    Target, TrendingUp, Wallet, Package, Layers, 
    Clock, CheckCircle2, 
    BarChart3, LineChart, Table2, Filter, ExternalLink, ArrowUpRight
} from 'lucide-react';

export default function Owner({ stats }) {
    const { app } = usePage().props;
    const targetView = app?.target_view || 'pcs';

    // Current Month Summary Data
    const currentMonth = stats.current_month_summary || {
        bulan_num: new Date().getMonth() + 1,
        bulan: new Date().toLocaleDateString('id-ID', { month: 'long' }),
        total_po: 0,
        total_omset: 0,
        total_pcs: 0,
        total_paid: 0,
        total_unpaid: 0,
        lunas_po_count: 0,
        belum_lunas_po_count: 0,
        collection_rate: 0
    };

    // Jenis PO Breakdown Data (/laporan/jenis-po)
    const jenisPoData = stats.jenis_po_breakdown || { yearly: { total_po: 0, total_pcs: 0, total_omset: 0, items: [] }, current_month: { total_po: 0, total_pcs: 0, total_omset: 0, items: [] } };
    const [jenisPoPeriod, setJenisPoPeriod] = useState(
        jenisPoData.current_month?.total_po > 0 ? 'month' : 'yearly'
    );
    const activeJenisPo = jenisPoPeriod === 'month' ? (jenisPoData.current_month || { items: [] }) : (jenisPoData.yearly || { items: [] });

    // Multi-Brand Monthly Comparison Data (/laporan-comparison)
    const brandComparison = stats.brand_monthly_comparison?.data || {};
    const ownedBrands = stats.owned_brands ?? [];
    const allBrandIds = ownedBrands.map(b => b.id);
    const [selectedCompBrands, setSelectedCompBrands] = useState(allBrandIds);
    const [activeMetric, setActiveMetric] = useState('omset'); // 'paid', 'unpaid', 'omset', 'po', 'pcs'

    // Annual Trend Data
    const trendBulanan = stats.trend_bulanan ?? [];
    const trendMonths = trendBulanan.map((tb) => tb.bulan.substring(0, 3));
    const trendBulananPaid = trendBulanan.map((tb) => tb.total_paid ?? 0);
    const trendBulananUnpaid = trendBulanan.map((tb) => tb.total_unpaid ?? 0);
    const trendBulananOmset = trendBulanan.map((tb) => tb.total_omset ?? 0);
    const trendBulananPcs = trendBulanan.map((tb) => tb.total_pcs ?? 0);
    const trendBulananPo = trendBulanan.map((tb) => tb.total_po ?? 0);
    const trendBulananTargetRev = trendBulanan.map((tb) => tb.target_revenue ?? 0);
    const [trendChartMode, setTrendChartMode] = useState('financial'); // 'financial', 'volume', 'target'

    // Daily Trend & Progress
    const trend = stats.trend_harian ?? [];
    const trendDates = trend.map((t) => new Date(t.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const trendValues = trend.map((t) => t.count);
    const progressDist = stats.progress_distribution ?? [];

    // Marketing Analytics
    const produk = stats.produk_terpopuler ?? [];
    const kategori = stats.kategori_distribusi ?? [];
    const sumber = stats.sumber_distribusi ?? [];
    const kategoriPelanggan = stats.kategori_pelanggan_distribusi ?? [];
    const wilayah = stats.wilayah_top ?? [];
    const topPelanggan = stats.top_pelanggan ?? [];

    const currentBrandId = stats.current_brand_id ?? 'all';

    function changeBrand(v) {
        const params = { brand_id: v };
        router.get(route('dashboard'), params, { preserveScroll: true, preserveState: true });
    }

    function toggleCompBrand(brandId) {
        if (selectedCompBrands.includes(brandId)) {
            if (selectedCompBrands.length === 1) return; // minimal 1 brand
            setSelectedCompBrands(selectedCompBrands.filter(id => id !== brandId));
        } else {
            setSelectedCompBrands([...selectedCompBrands, brandId]);
        }
    }

    function selectAllCompBrands() {
        setSelectedCompBrands(allBrandIds);
    }

    // Comparison Chart series setup
    const monthsKeys = Array.from({ length: 12 }, (_, i) => i + 1);
    const monthsNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const monthsShort = monthsNames.map(m => m.substring(0, 3));

    const activeBrandEntries = Object.entries(brandComparison).filter(([id]) => 
        selectedCompBrands.length === 0 || selectedCompBrands.includes(id)
    );

    const compChartSeries = activeBrandEntries.map(([id, b]) => {
        const dataPoints = monthsKeys.map(k => {
            const m = b.months?.[k] || {};
            if (activeMetric === 'paid') return m.total_paid || 0;
            if (activeMetric === 'unpaid') return m.total_unpaid || 0;
            if (activeMetric === 'po') return m.total_po || 0;
            if (activeMetric === 'pcs') return m.total_pcs || 0;
            return m.total_omset || 0;
        });
        return {
            name: b.brand_name || 'Brand',
            data: dataPoints,
            color: b.warna || '#6366F1'
        };
    });

    const isCurrencyMetric = ['paid', 'unpaid', 'omset'].includes(activeMetric);

    const compChartOptions = {
        chart: { toolbar: { show: true }, zoom: { enabled: false } },
        xaxis: { categories: monthsShort },
        yaxis: {
            labels: {
                formatter: (val) => isCurrencyMetric ? ('Rp ' + (val >= 1000000 ? (val / 1000000).toFixed(1) + 'M' : val.toLocaleString('id-ID'))) : val
            }
        },
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0.05 } },
        tooltip: {
            y: {
                formatter: (val) => isCurrencyMetric ? formatRupiah(val) : `${val.toLocaleString('id-ID')} unit`
            }
        },
        dataLabels: { enabled: false }
    };

    return (
        <div className="space-y-7 pb-10">
            {/* Top Bar: Brand Filter & Quick Navigation */}
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-card p-4 rounded-2xl border shadow-sm">
                <div>
                    <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
                        <Filter className="h-4 w-4 text-indigo-600" /> Filter Data Dashboard
                    </h2>
                    <p className="text-xs text-muted-foreground">Pilih brand spesifik atau tinjau agregat performa seluruh brand milik Anda.</p>
                </div>
                <div className="flex flex-wrap items-center gap-2.5">
                    <Select onValueChange={changeBrand} value={currentBrandId}>
                        <SelectTrigger className="w-full sm:w-64 font-medium border-slate-300 shadow-sm bg-white">
                            <SelectValue placeholder="Semua Brand" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all" className="font-semibold text-indigo-700">Semua Brand (Aggregated)</SelectItem>
                            {ownedBrands.map((b) => (
                                <SelectItem key={b.id} value={b.id}>
                                    <span className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full" style={{ backgroundColor: b.warna_primary || '#6366F1' }} />
                                        {b.nama_brand}
                                    </span>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Link href={route('comparison.show')}>
                        <Button variant="outline" size="sm" className="border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100 font-semibold text-xs h-9">
                            <Layers className="h-3.5 w-3.5 mr-1.5 text-indigo-600" /> Multi-Brand Report
                        </Button>
                    </Link>
                    <Link href={route('reports.show', 'jenis-po')}>
                        <Button variant="outline" size="sm" className="border-slate-200 text-slate-700 hover:bg-slate-100 font-semibold text-xs h-9">
                            <BarChart3 className="h-3.5 w-3.5 mr-1.5 text-slate-600" /> Laporan Jenis PO
                        </Button>
                    </Link>
                </div>
            </div>

            {/* SECTION 1: PEROLEHAN BULAN BERJALAN (MONTHLY REALIZATION CARDS) */}
            <div className="space-y-3">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="p-1.5 rounded-lg bg-emerald-100 text-emerald-700">
                                <Wallet className="h-4 w-4" />
                            </div>
                            <h3 className="text-sm font-extrabold uppercase tracking-wider text-slate-800">
                                Perolehan Bulan Berjalan ({currentMonth.bulan} {new Date().getFullYear()})
                            </h3>
                            <Badge variant="outline" className="text-[11px] bg-emerald-50 text-emerald-700 border-emerald-300 font-bold">
                                Kolektibilitas: {currentMonth.collection_rate}%
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Realisasi uang yang sudah masuk (terbayar), piutang yang belum lunas, dan volume PO pada bulan ini.
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Card 1: Sudah Masuk (Terbayar) */}
                    <Card className="relative overflow-hidden border-l-4 border-l-emerald-500 bg-gradient-to-br from-emerald-500/10 via-white to-emerald-50/20 shadow-sm hover:shadow-md transition">
                        <CardHeader className="pb-1 pt-4 px-4 flex flex-row items-center justify-between space-y-0">
                            <span className="text-xs font-bold uppercase tracking-wider text-emerald-800">Sudah Masuk (Lunas)</span>
                            <div className="h-8 w-8 rounded-full bg-emerald-500/15 flex items-center justify-center text-emerald-600">
                                <CheckCircle2 className="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent className="px-4 pb-4 pt-1">
                            <div className="text-2xl font-black text-slate-900 font-mono tracking-tight">
                                {formatRupiah(currentMonth.total_paid)}
                            </div>
                            <div className="mt-2 flex items-center justify-between text-xs">
                                <span className="text-muted-foreground font-medium">
                                    <strong className="text-emerald-700">{currentMonth.lunas_po_count} PO</strong> telah terbayar
                                </span>
                                <Badge variant="outline" className="bg-emerald-100/70 border-emerald-300 text-emerald-800 font-bold text-[10px]">
                                    {currentMonth.collection_rate}% Terbayar
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Card 2: Belum Masuk (Belum Lunas / Piutang) */}
                    <Card className="relative overflow-hidden border-l-4 border-l-amber-500 bg-gradient-to-br from-amber-500/10 via-white to-amber-50/20 shadow-sm hover:shadow-md transition">
                        <CardHeader className="pb-1 pt-4 px-4 flex flex-row items-center justify-between space-y-0">
                            <span className="text-xs font-bold uppercase tracking-wider text-amber-800">Belum Masuk (Piutang)</span>
                            <div className="h-8 w-8 rounded-full bg-amber-500/15 flex items-center justify-center text-amber-600">
                                <Clock className="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent className="px-4 pb-4 pt-1">
                            <div className="text-2xl font-black text-slate-900 font-mono tracking-tight">
                                {formatRupiah(currentMonth.total_unpaid)}
                            </div>
                            <div className="mt-2 flex items-center justify-between text-xs">
                                <span className="text-muted-foreground font-medium">
                                    <strong className="text-amber-700">{currentMonth.belum_lunas_po_count} PO</strong> belum lunas
                                </span>
                                <Badge variant="outline" className="bg-amber-100/70 border-amber-300 text-amber-800 font-bold text-[10px]">
                                    Sisa Tagihan
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Card 3: Total Omset Bulan Ini */}
                    <Card className="relative overflow-hidden border-l-4 border-l-indigo-500 bg-gradient-to-br from-indigo-500/10 via-white to-indigo-50/20 shadow-sm hover:shadow-md transition">
                        <CardHeader className="pb-1 pt-4 px-4 flex flex-row items-center justify-between space-y-0">
                            <span className="text-xs font-bold uppercase tracking-wider text-indigo-800">Total Nilai PO (Omset)</span>
                            <div className="h-8 w-8 rounded-full bg-indigo-500/15 flex items-center justify-center text-indigo-600">
                                <TrendingUp className="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent className="px-4 pb-4 pt-1">
                            <div className="text-2xl font-black text-slate-900 font-mono tracking-tight">
                                {formatRupiah(currentMonth.total_omset)}
                            </div>
                            {/* Visual Progress Ratio Pelunasan */}
                            <div className="mt-2 space-y-1">
                                <div className="w-full bg-slate-200 rounded-full h-2 overflow-hidden flex shadow-inner">
                                    <div 
                                        className="bg-emerald-500 h-full transition-all duration-500" 
                                        style={{ width: `${Math.min(100, currentMonth.collection_rate)}%` }} 
                                        title={`Sudah Masuk: ${currentMonth.collection_rate}%`}
                                    />
                                    <div 
                                        className="bg-amber-500 h-full transition-all duration-500" 
                                        style={{ width: `${Math.max(0, 100 - currentMonth.collection_rate)}%` }} 
                                        title={`Belum Lunas: ${(100 - currentMonth.collection_rate).toFixed(1)}%`}
                                    />
                                </div>
                                <div className="flex justify-between text-[10px] text-muted-foreground font-semibold">
                                    <span className="text-emerald-700">Masuk: {currentMonth.collection_rate}%</span>
                                    <span className="text-amber-700">Belum: {(100 - currentMonth.collection_rate).toFixed(1)}%</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Card 4: Volume Order & PCS Bulan Ini */}
                    <Card className="relative overflow-hidden border-l-4 border-l-blue-500 bg-gradient-to-br from-blue-500/10 via-white to-blue-50/20 shadow-sm hover:shadow-md transition">
                        <CardHeader className="pb-1 pt-4 px-4 flex flex-row items-center justify-between space-y-0">
                            <span className="text-xs font-bold uppercase tracking-wider text-blue-800">Volume Produksi (PCS)</span>
                            <div className="h-8 w-8 rounded-full bg-blue-500/15 flex items-center justify-center text-blue-600">
                                <Package className="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent className="px-4 pb-4 pt-1">
                            <div className="text-2xl font-black text-slate-900 font-mono tracking-tight">
                                {currentMonth.total_pcs?.toLocaleString('id-ID') ?? 0} <span className="text-base font-semibold text-slate-500">Pcs</span>
                            </div>
                            <div className="mt-2 flex items-center justify-between text-xs">
                                <span className="text-muted-foreground font-medium">
                                    Total <strong className="text-blue-700">{currentMonth.total_po} lembar PO</strong>
                                </span>
                                <Badge variant="outline" className="bg-blue-100/70 border-blue-300 text-blue-800 font-bold text-[10px]">
                                    {currentMonth.total_po > 0 ? `${Math.round(currentMonth.total_pcs / currentMonth.total_po)} pcs/PO` : '0 pcs/PO'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* SECTION 2: RINCIAN SESUAI JENIS PO (/laporan/jenis-po) */}
            <Card className="shadow-sm border-slate-200">
                <CardHeader className="pb-3 border-b bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="p-1 rounded bg-violet-100 text-violet-700">
                                <Layers className="h-4 w-4" />
                            </div>
                            <CardTitle className="text-sm font-bold uppercase tracking-wider text-slate-800">
                                Rincian Sesuai Jenis PO
                            </CardTitle>
                        </div>
                        <CardDescription className="text-xs mt-0.5">
                            Klasifikasi jumlah PO, total PCS, dan nilai tagihan berdasarkan jenis PO (Normal, Special Order, Reseller, Repeat Order).
                        </CardDescription>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="flex bg-slate-200/80 p-0.5 rounded-lg border text-xs font-semibold">
                            <button
                                onClick={() => setJenisPoPeriod('month')}
                                className={`px-2.5 py-1 rounded-md transition ${jenisPoPeriod === 'month' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
                            >
                                Bulan Ini ({currentMonth.bulan})
                            </button>
                            <button
                                onClick={() => setJenisPoPeriod('yearly')}
                                className={`px-2.5 py-1 rounded-md transition ${jenisPoPeriod === 'yearly' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
                            >
                                Tahun {new Date().getFullYear()}
                            </button>
                        </div>
                        <Link href={route('reports.show', 'jenis-po')}>
                            <Button variant="ghost" size="sm" className="h-7 text-xs text-indigo-600 hover:text-indigo-800 font-semibold p-1">
                                Lihat Laporan <ArrowUpRight className="h-3.5 w-3.5 ml-0.5" />
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent className="pt-4 space-y-4">
                    {/* 4 Cards Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        {activeJenisPo.items?.map((item) => (
                            <div 
                                key={item.key} 
                                className="p-3.5 rounded-xl border bg-slate-50/40 hover:bg-slate-50 transition relative overflow-hidden"
                                style={{ borderLeftColor: item.color, borderLeftWidth: '4px' }}
                            >
                                <div className="flex items-center justify-between mb-1">
                                    <span className="text-xs font-bold text-slate-800">{item.label}</span>
                                    <Badge variant="outline" className="text-[10px] font-mono font-semibold" style={{ color: item.color, borderColor: item.color + '60' }}>
                                        {item.percentage_po}% PO
                                    </Badge>
                                </div>
                                <div className="space-y-1">
                                    <div className="flex items-baseline justify-between">
                                        <span className="text-lg font-black text-slate-900 font-mono">{item.total_po} <span className="text-xs font-normal text-slate-500">PO</span></span>
                                        <span className="text-sm font-bold text-slate-700 font-mono">{item.total_pcs.toLocaleString('id-ID')} <span className="text-xs font-normal text-slate-500">pcs</span></span>
                                    </div>
                                    <div className="text-xs font-semibold text-slate-600 font-mono">
                                        {formatRupiah(item.total_omset)}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Visual Volume Breakdown Bar */}
                    <div className="space-y-1.5 pt-1">
                        <div className="flex justify-between text-xs text-slate-600 font-medium">
                            <span>Distribusi Volume PO ({activeJenisPo.total_po} PO / {activeJenisPo.total_pcs?.toLocaleString('id-ID')} Pcs)</span>
                            <span className="font-semibold text-indigo-700">{formatRupiah(activeJenisPo.total_omset)}</span>
                        </div>
                        <div className="w-full bg-slate-100 rounded-full h-3 overflow-hidden flex shadow-inner border">
                            {activeJenisPo.items?.map((item) => {
                                const pct = item.percentage_po || 0;
                                if (pct <= 0) return null;
                                return (
                                    <div 
                                        key={item.key} 
                                        style={{ width: `${pct}%`, backgroundColor: item.color }}
                                        className="h-full transition-all duration-500"
                                        title={`${item.label}: ${item.total_po} PO (${pct}%)`}
                                    />
                                );
                            })}
                        </div>
                        <div className="flex flex-wrap gap-4 pt-1 text-[11px] text-muted-foreground font-medium">
                            {activeJenisPo.items?.map((item) => (
                                <span key={item.key} className="flex items-center gap-1.5">
                                    <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: item.color }} />
                                    {item.label}: <strong className="text-slate-800">{item.total_po} PO</strong> ({item.total_pcs?.toLocaleString('id-ID')} pcs)
                                </span>
                            ))}
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* SECTION 3: MODUL DINAMIS PERBANDINGAN BRAND (SEPERTI DI /laporan-comparison) */}
            <Card className="shadow-sm border-indigo-100">
                <CardHeader className="pb-3 border-b bg-slate-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="p-1 rounded bg-indigo-100 text-indigo-700">
                                <BarChart3 className="h-4 w-4" />
                            </div>
                            <CardTitle className="text-sm font-bold uppercase tracking-wider text-slate-800">
                                Perbandingan Dinamis Per-Brand (Januari — Desember)
                            </CardTitle>
                        </div>
                        <CardDescription className="text-xs mt-0.5">
                            Analisis dan komparasi interaktif performa seluruh brand milik Anda sepanjang tahun {new Date().getFullYear()}.
                        </CardDescription>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href={route('comparison.show')}>
                            <Button variant="outline" size="sm" className="h-8 text-xs border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100 font-semibold flex items-center gap-1">
                                <ExternalLink className="h-3.5 w-3.5" /> Buka Analisis Lengkap Multi-Tahun
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent className="pt-4 space-y-5">
                    {/* Brand Selector Chips */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b">
                        <div className="space-y-1.5">
                            <span className="text-xs font-bold text-slate-700 uppercase tracking-wide">Pilih Brand untuk Dibandingkan:</span>
                            <div className="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    onClick={selectAllCompBrands}
                                    className={`px-3 py-1.5 rounded-full text-xs font-bold transition border ${selectedCompBrands.length === allBrandIds.length ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'}`}
                                >
                                    Semua Brand ({ownedBrands.length})
                                </button>
                                {ownedBrands.map((b) => {
                                    const isSelected = selectedCompBrands.includes(b.id);
                                    return (
                                        <button
                                            key={b.id}
                                            type="button"
                                            onClick={() => toggleCompBrand(b.id)}
                                            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition border ${isSelected ? 'bg-indigo-50 text-indigo-900 border-indigo-400 ring-1 ring-indigo-300' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'}`}
                                        >
                                            <span className="h-2 w-2 rounded-full" style={{ backgroundColor: b.warna_primary || '#6366F1' }} />
                                            <span>{b.nama_brand}</span>
                                            <Badge variant="outline" className="text-[9px] px-1 py-0">{b.kode}</Badge>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Metric Focus Switcher */}
                        <div className="space-y-1.5">
                            <span className="text-xs font-bold text-slate-700 uppercase tracking-wide">Metrik Grafik:</span>
                            <div className="flex bg-slate-100 p-0.5 rounded-lg border text-xs font-semibold">
                                <button
                                    onClick={() => setActiveMetric('omset')}
                                    className={`px-2.5 py-1 rounded-md transition ${activeMetric === 'omset' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`}
                                >
                                    Total Omset
                                </button>
                                <button
                                    onClick={() => setActiveMetric('paid')}
                                    className={`px-2.5 py-1 rounded-md transition ${activeMetric === 'paid' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`}
                                >
                                    Sudah Masuk
                                </button>
                                <button
                                    onClick={() => setActiveMetric('unpaid')}
                                    className={`px-2.5 py-1 rounded-md transition ${activeMetric === 'unpaid' ? 'bg-white text-amber-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`}
                                >
                                    Belum Lunas
                                </button>
                                <button
                                    onClick={() => setActiveMetric('po')}
                                    className={`px-2.5 py-1 rounded-md transition ${activeMetric === 'po' ? 'bg-white text-blue-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`}
                                >
                                    Jumlah PO
                                </button>
                                <button
                                    onClick={() => setActiveMetric('pcs')}
                                    className={`px-2.5 py-1 rounded-md transition ${activeMetric === 'pcs' ? 'bg-white text-purple-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`}
                                >
                                    Total PCS
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Multi-Brand Dynamic Chart */}
                    <div className="bg-slate-50/50 p-3 rounded-xl border">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                <LineChart className="h-4 w-4 text-indigo-600" /> Visualisasi Grafik Komparasi ({activeMetric.toUpperCase()})
                            </span>
                        </div>
                        {compChartSeries.length === 0 ? (
                            <div className="py-12 text-center text-muted-foreground text-sm">Pilih minimal 1 brand untuk menampilkan grafik perbandingan.</div>
                        ) : (
                            <Chart
                                type="area"
                                height={280}
                                series={compChartSeries}
                                options={compChartOptions}
                            />
                        )}
                    </div>

                    {/* Comprehensive Monthly Comparison Table (Jan s.d. Des + Total Tahunan) */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <Table2 className="h-4 w-4 text-indigo-600" /> Tabel Rincian Komparasi Bulanan Per-Brand
                            </span>
                            <span className="text-[11px] text-muted-foreground">Mencakup PO, PCS, Uang Masuk, Piutang Belum Lunas, dan Total Omset</span>
                        </div>

                        <div className="overflow-x-auto rounded-xl border border-slate-200">
                            <table className="w-full text-xs text-left border-collapse min-w-[760px]">
                                <thead>
                                    <tr className="bg-slate-100 text-slate-700 font-bold border-b">
                                        <th className="px-3 py-2.5 border-r sticky left-0 bg-slate-100 z-10">Bulan</th>
                                        {activeBrandEntries.map(([id, b]) => (
                                            <th 
                                                key={id} 
                                                colSpan={5} 
                                                className="px-3 py-2 text-center border-r font-bold text-slate-900" 
                                                style={{ borderTop: `4px solid ${b.warna || '#6366F1'}` }}
                                            >
                                                <div className="flex items-center justify-center gap-1.5">
                                                    <span className="h-2 w-2 rounded-full" style={{ backgroundColor: b.warna || '#6366F1' }} />
                                                    {b.brand_name} ({b.kode})
                                                </div>
                                            </th>
                                        ))}
                                    </tr>
                                    <tr className="bg-slate-50 text-[10px] text-slate-500 font-semibold border-b">
                                        <th className="px-3 py-1.5 border-r sticky left-0 bg-slate-50 z-10"></th>
                                        {activeBrandEntries.map(([id]) => (
                                            <>
                                                <th key={`${id}-po`} className="px-2 py-1.5 text-right font-mono">PO</th>
                                                <th key={`${id}-pcs`} className="px-2 py-1.5 text-right font-mono">Pcs</th>
                                                <th key={`${id}-paid`} className="px-2 py-1.5 text-right font-mono text-emerald-700">Masuk (Rp)</th>
                                                <th key={`${id}-unpaid`} className="px-2 py-1.5 text-right font-mono text-amber-700">Belum (Rp)</th>
                                                <th key={`${id}-omset`} className="px-2 py-1.5 text-right font-mono border-r font-bold text-slate-800">Total Omset</th>
                                            </>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-slate-700">
                                    {monthsKeys.map((k) => (
                                        <tr key={k} className="hover:bg-slate-50/70 transition">
                                            <td className="px-3 py-2 font-medium border-r sticky left-0 bg-white z-10">{monthsNames[k - 1]}</td>
                                            {activeBrandEntries.map(([id, b]) => {
                                                const m = b.months?.[k] || { total_po: 0, total_pcs: 0, total_paid: 0, total_unpaid: 0, total_omset: 0 };
                                                return (
                                                    <>
                                                        <td key={`${id}-${k}-po`} className="px-2 py-2 text-right font-mono">{m.total_po}</td>
                                                        <td key={`${id}-${k}-pcs`} className="px-2 py-2 text-right font-mono">{m.total_pcs?.toLocaleString('id-ID')}</td>
                                                        <td key={`${id}-${k}-paid`} className="px-2 py-2 text-right font-mono text-emerald-700">{formatRupiah(m.total_paid)}</td>
                                                        <td key={`${id}-${k}-unpaid`} className="px-2 py-2 text-right font-mono text-amber-700">{formatRupiah(m.total_unpaid)}</td>
                                                        <td key={`${id}-${k}-omset`} className="px-2 py-2 text-right font-mono border-r font-semibold text-slate-900">{formatRupiah(m.total_omset)}</td>
                                                    </>
                                                );
                                            })}
                                        </tr>
                                    ))}

                                    {/* Total Tahunan Row */}
                                    <tr className="bg-slate-100/90 font-bold border-t-2 border-slate-300 text-slate-900">
                                        <td className="px-3 py-3 border-r sticky left-0 bg-slate-100 z-10 uppercase tracking-wider text-indigo-700 font-extrabold">TOTAL TAHUNAN</td>
                                        {activeBrandEntries.map(([id, b]) => {
                                            const t = b.totals || { total_po: 0, total_pcs: 0, total_paid: 0, total_unpaid: 0, total_omset: 0 };
                                            return (
                                                <>
                                                    <td key={`${id}-tot-po`} className="px-2 py-3 text-right font-mono text-blue-800">{t.total_po}</td>
                                                    <td key={`${id}-tot-pcs`} className="px-2 py-3 text-right font-mono text-purple-800">{t.total_pcs?.toLocaleString('id-ID')}</td>
                                                    <td key={`${id}-tot-paid`} className="px-2 py-3 text-right font-mono text-emerald-800">{formatRupiah(t.total_paid)}</td>
                                                    <td key={`${id}-tot-unpaid`} className="px-2 py-3 text-right font-mono text-amber-800">{formatRupiah(t.total_unpaid)}</td>
                                                    <td key={`${id}-tot-omset`} className="px-2 py-3 text-right font-mono border-r text-indigo-900 font-extrabold">{formatRupiah(t.total_omset)}</td>
                                                </>
                                            );
                                        })}
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* SECTION 4: GRAFIK TREN BULANAN & REALISASI KEUANGAN SEPANJANG TAHUN */}
            {trendBulanan.length > 0 && (
                <Card className="shadow-sm">
                    <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b bg-slate-50/50">
                        <div>
                            <CardTitle className="text-base font-bold text-slate-800 flex items-center gap-2">
                                <TrendingUp className="h-4 w-4 text-emerald-600" />
                                Tren Perolehan & Pertumbuhan Bulanan ({new Date().getFullYear()})
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Tinjau visualisasi uang masuk vs belum lunas, volume PO & PCS, serta perbandingan dengan target penjualan.
                            </CardDescription>
                        </div>
                        <div className="flex bg-slate-200/80 p-0.5 rounded-lg border text-xs font-semibold">
                            <button
                                onClick={() => setTrendChartMode('financial')}
                                className={`px-3 py-1 rounded-md transition ${trendChartMode === 'financial' ? 'bg-white text-emerald-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`}
                            >
                                Uang Masuk vs Belum Lunas
                            </button>
                            <button
                                onClick={() => setTrendChartMode('volume')}
                                className={`px-3 py-1 rounded-md transition ${trendChartMode === 'volume' ? 'bg-white text-blue-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`}
                            >
                                Volume (PO & PCS)
                            </button>
                            <button
                                onClick={() => setTrendChartMode('target')}
                                className={`px-3 py-1 rounded-md transition ${trendChartMode === 'target' ? 'bg-white text-indigo-700 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`}
                            >
                                Omset vs Target
                            </button>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-4">
                        {trendChartMode === 'financial' && (
                            <Chart
                                type="bar"
                                height={280}
                                series={[
                                    { name: 'Sudah Masuk (Rp)', data: trendBulananPaid, type: 'column' },
                                    { name: 'Belum Lunas (Rp)', data: trendBulananUnpaid, type: 'column' },
                                    { name: 'Total Omset (Rp)', data: trendBulananOmset, type: 'line' },
                                ]}
                                options={{
                                    chart: { stacked: true },
                                    xaxis: { categories: trendMonths },
                                    colors: ['#10B981', '#F59E0B', '#6366F1'],
                                    yaxis: [
                                        {
                                            title: { text: 'Nominal Tagihan (Rp)' },
                                            labels: { formatter: (v) => 'Rp ' + (v / 1000000).toFixed(1) + 'M' }
                                        }
                                    ],
                                    tooltip: {
                                        y: { formatter: (v) => formatRupiah(v) }
                                    },
                                    plotOptions: { bar: { columnWidth: '50%', borderRadius: 4 } }
                                }}
                            />
                        )}

                        {trendChartMode === 'volume' && (
                            <Chart
                                type="bar"
                                height={280}
                                series={[
                                    { name: 'Total PCS', data: trendBulananPcs, type: 'bar' },
                                    { name: 'Jumlah PO', data: trendBulananPo, type: 'line' },
                                ]}
                                options={{
                                    chart: { type: 'bar' },
                                    xaxis: { categories: trendMonths },
                                    colors: ['#3B82F6', '#EC4899'],
                                    yaxis: [
                                        { title: { text: 'Total Quantity (PCS)' }, labels: { formatter: (v) => v.toLocaleString('id-ID') + ' pcs' } },
                                        { opposite: true, title: { text: 'Jumlah PO' }, labels: { formatter: (v) => v + ' PO' } },
                                    ],
                                    plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } }
                                }}
                            />
                        )}

                        {trendChartMode === 'target' && (
                            <Chart
                                type="bar"
                                height={280}
                                series={[
                                    { name: 'Realisasi Omset (Rp)', data: trendBulananOmset, type: 'bar' },
                                    { name: 'Target Omset (Rp)', data: trendBulananTargetRev, type: 'line' },
                                ]}
                                options={{
                                    chart: { type: 'bar' },
                                    xaxis: { categories: trendMonths },
                                    colors: ['#6366F1', '#F59E0B'],
                                    yaxis: [
                                        { title: { text: 'Omset (Rp)' }, labels: { formatter: (v) => 'Rp ' + (v / 1000000).toFixed(1) + 'M' } },
                                    ],
                                    tooltip: { y: { formatter: (v) => formatRupiah(v) } },
                                    plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } }
                                }}
                            />
                        )}
                    </CardContent>
                </Card>
            )}

            {/* SECTION 5: TARGET PROGRESS & ALL-TIME ACCUMULATED KPI */}
            {stats.target_progress && (
                <div className="space-y-3">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <Target className="h-4 w-4 text-indigo-600" /> Target Bulanan ({stats.target_progress.month_name})
                            </h3>
                            <p className="text-xs text-muted-foreground">Monitor pencapaian target bulanan Anda.</p>
                        </div>
                        <Link href={route('brand-targets.index')}>
                            <Button variant="outline" size="sm" className="h-8 text-xs border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100 font-semibold">
                                <Target className="h-3.5 w-3.5 mr-1 text-indigo-600" /> Kelola Target Penjualan
                            </Button>
                        </Link>
                    </div>

                    <Card className="bg-gradient-to-br from-emerald-50/60 via-white to-indigo-50/30 border-l-4 border-emerald-500 shadow-sm">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <Target className="h-4 w-4 text-emerald-600" /> Target Qty (Pcs) Bulan Ini ({stats.target_progress.month_name})
                            </CardTitle>
                            <CardDescription className="text-xs">Realisasi quantity produk terjual dibandingkan target.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-baseline justify-between">
                                <span className="text-2xl font-black text-slate-800 font-mono">
                                    {stats.target_progress.actual_pcs.toLocaleString('id-ID')} Pcs
                                </span>
                                <span className="text-xs text-muted-foreground font-semibold">
                                    dari target <strong className="text-slate-700">{stats.target_progress.target_pcs.toLocaleString('id-ID')} Pcs</strong>
                                </span>
                            </div>
                            <div className="space-y-1">
                                <div className="flex justify-between text-xs font-bold text-emerald-700">
                                    <span>Pencapaian</span>
                                    <span>
                                        {stats.target_progress.target_pcs > 0 
                                            ? `${stats.target_progress.pcs_percentage}%` 
                                            : 'Belum ada target'}
                                    </span>
                                </div>
                                <div className="w-full bg-slate-200/80 rounded-full h-2.5 overflow-hidden shadow-inner">
                                    <div 
                                        className={`h-full rounded-full transition-all duration-500 ${stats.target_progress.pcs_percentage >= 100 ? 'bg-emerald-500' : 'bg-indigo-600'}`} 
                                        style={{ width: `${stats.target_progress.target_pcs > 0 ? Math.min(100, stats.target_progress.pcs_percentage) : 0}%` }} 
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            )}

            {/* All-Time Accumulated KPI Cards */}
            <div className="space-y-2">
                <span className="text-xs font-bold text-slate-700 uppercase tracking-wider">Statistik Akumulasi All-Time</span>
                <StatGrid cards={stats.cards ?? []} />
            </div>

            {/* SECTION 6: TREN 14 HARI & STATUS BREAKDOWN */}
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">Tren Order 14 Hari</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Chart
                            type="area" height={240}
                            series={[{ name: 'PO', data: trendValues }]}
                            options={{
                                xaxis: { categories: trendDates },
                                colors: ['#10B981'],
                                fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0 } },
                            }}
                        />
                    </CardContent>
                </Card>
                <StatusBreakdown items={stats.status_breakdown ?? []} />
            </div>

            {/* Tahapan Progress */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Tahapan Progress Produksi</CardTitle>
                    <CardDescription>Jumlah PO yang sedang dikerjakan di setiap tahapan progress.</CardDescription>
                </CardHeader>
                <CardContent>
                    {progressDist.length === 0 ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">Tidak ada tahapan aktif saat ini.</p>
                    ) : (
                        <Chart
                            type="bar"
                            height={260}
                            series={[{ name: 'PO dalam proses', data: progressDist.map((r) => r.count) }]}
                            options={{
                                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
                                xaxis: { categories: progressDist.map((r) => r.label), labels: { rotate: -20, style: { fontSize: '10px' } } },
                                colors: progressDist.map((r) => r.warna || '#F59E0B'),
                                legend: { show: false },
                            }}
                        />
                    )}
                </CardContent>
            </Card>

            {/* SECTION 7: MARKETING ANALYTICS */}
            <div className="rounded-xl border-2 border-dashed border-indigo-200 bg-indigo-50/30 p-1">
                <p className="px-4 pt-3 pb-1 text-xs font-black uppercase tracking-widest text-indigo-600">📊 Analisis Pasar & Marketing</p>

                {/* Produk Terpopuler */}
                {produk.length > 0 && (
                    <Card className="mb-4 mx-1">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Produk Terpopuler</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Chart
                                type="bar" height={Math.max(200, produk.length * 40)}
                                series={[{ name: 'Qty', data: produk.map((p) => p.total_qty) }]}
                                options={{
                                    chart: { type: 'bar' },
                                    plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                                    xaxis: { categories: produk.map((p) => p.nama) },
                                    colors: ['#3B82F6'],
                                    dataLabels: { enabled: true },
                                }}
                            />
                        </CardContent>
                    </Card>
                )}

                {/* Distribusi Charts — 3 kolom */}
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3 mx-1 mb-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-semibold">Kategori Pelanggan</CardTitle>
                            <CardDescription className="text-[10px]">Segmentasi pelanggan berdasarkan tipe</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {kategoriPelanggan.length === 0 ? (
                                <p className="text-center text-xs text-muted-foreground py-8">Belum ada data.</p>
                            ) : (
                                <Chart
                                    type="donut" height={200}
                                    series={kategoriPelanggan.map((kp) => kp.count)}
                                    options={{
                                        labels: kategoriPelanggan.map((kp) => kp.label),
                                        colors: ['#EC4899', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4', '#EF4444'],
                                        legend: { position: 'bottom', fontSize: '11px' },
                                        plotOptions: { pie: { donut: { size: '60%' } } },
                                    }}
                                />
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-semibold">Sumber Order</CardTitle>
                            <CardDescription className="text-[10px]">Dari mana order masuk</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {sumber.length === 0 ? (
                                <p className="text-center text-xs text-muted-foreground py-8">Belum ada data.</p>
                            ) : (
                                <Chart
                                    type="donut" height={200}
                                    series={sumber.map((s) => s.count)}
                                    options={{
                                        labels: sumber.map((s) => s.label),
                                        colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'],
                                        legend: { position: 'bottom', fontSize: '11px' },
                                        plotOptions: { pie: { donut: { size: '60%' } } },
                                    }}
                                />
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-semibold">Kategori Order</CardTitle>
                            <CardDescription className="text-[10px]">Distribusi per kategori produk</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {kategori.length === 0 ? (
                                <p className="text-center text-xs text-muted-foreground py-8">Belum ada data.</p>
                            ) : (
                                <Chart
                                    type="donut" height={200}
                                    series={kategori.map((k) => k.count)}
                                    options={{
                                        labels: kategori.map((k) => k.label),
                                        colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'],
                                        legend: { position: 'bottom', fontSize: '11px' },
                                        plotOptions: { pie: { donut: { size: '60%' } } },
                                    }}
                                />
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Top Pelanggan + Wilayah */}
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 mx-1 mb-3">
                    <TopList
                        title="Top Pelanggan"
                        items={topPelanggan}
                        valueKey="total_order"
                        valueLabel="PO"
                        currencyKey="total_value"
                    />
                    <TopList
                        title="Top Wilayah"
                        items={wilayah.map(w => ({ ...w, nama: w.nama }))}
                        valueKey="count"
                        valueLabel="order"
                    />
                </div>
            </div>

            {/* PO Siap Dikirim Section */}
            <div className="mb-4">
                <POSiapDikirimWidget items={stats.po_siap_dikirim ?? []} />
            </div>

            {/* PO Alerts */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <POListWidget title="⏰ Deadline Mendekat" items={stats.deadline_mendekat ?? []} columns={['no_po', 'pelanggan', 'meta']} />
                <POListWidget title="⚠️ PO Terlambat" items={stats.po_terlambat ?? []} columns={['no_po', 'pelanggan', 'meta']} />
            </div>
        </div>
    );
}
