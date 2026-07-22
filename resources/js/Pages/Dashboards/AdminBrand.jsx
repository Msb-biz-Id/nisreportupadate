import { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { StatGrid, StatusBreakdown, POListWidget, TopList, POSiapDikirimWidget, POTypeDistributionWidget } from '@/Components/Widgets';
import { formatDate, formatRupiah } from '@/lib/utils';
import { Target, Sparkles, RotateCcw, CheckCircle2, ArrowUpRight, Wallet, Landmark, RefreshCw, Clock, Coins, FileText, Filter } from 'lucide-react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';

export default function AdminBrand({ stats, filters }) {
    const { app } = usePage().props;
    const [metric, setMetric] = useState('omset');
    const targetView = app?.target_view || 'pcs'; // 'both', 'revenue', 'pcs'

    const [dateFilter, setDateFilter] = useState(filters?.date_filter || 'bulanan');
    const [fromDate, setFromDate] = useState(filters?.from || '');
    const [toDate, setToDate] = useState(filters?.to || '');

    function applyFilters(newFilter = dateFilter, fromVal = fromDate, toVal = toDate) {
        const params = {};
        const urlParams = new URLSearchParams(window.location.search);
        const brandId = urlParams.get('brand_id');
        if (brandId) {
            params.brand_id = brandId;
        }
        params.date_filter = newFilter;
        if (newFilter === 'custom') {
            params.from = fromVal;
            params.to = toVal;
        }
        router.get(route('dashboard'), params, { preserveScroll: true, preserveState: true });
    }
    
    const getBankColor = (bankName) => {
        const name = bankName ? bankName.toLowerCase() : '';
        if (name.includes('bca')) return 'bg-blue-600 text-white';
        if (name.includes('mandiri')) return 'bg-amber-500 text-slate-900 border-amber-600';
        if (name.includes('bri')) return 'bg-sky-700 text-white';
        if (name.includes('bni')) return 'bg-teal-600 text-white';
        if (name.includes('cash') || name.includes('tunai')) return 'bg-emerald-600 text-white';
        return 'bg-slate-600 text-white';
    };
    const trend = stats.trend_harian ?? [];
    const trendDates = trend.map((t) => new Date(t.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const trendValues = trend.map((t) => t.count);
    const progressDist = stats.progress_distribution ?? [];

    const produk = stats.produk_terpopuler ?? [];

    const kategori = stats.kategori_distribusi ?? [];
    const sumber = stats.sumber_distribusi ?? [];
    const kategoriPelanggan = stats.kategori_pelanggan_distribusi ?? [];

    const trendBulanan = stats.trend_bulanan ?? [];
    const trendBulananMonths = trendBulanan.map((tb) => tb.bulan.substring(0, 3));
    const trendBulananPO = trendBulanan.map((tb) => tb.total_po);
    const trendBulananOmset = trendBulanan.map((tb) => tb.total_omset);
    const trendBulananPcs = trendBulanan.map((tb) => tb.total_pcs);

    const getSeries = () => {
        if (metric === 'omset') {
            return [
                { name: 'Omset', data: trendBulananOmset, type: 'area' }
            ];
        }
        if (metric === 'pcs') {
            return [
                { name: 'Total Pcs', data: trendBulananPcs, type: 'area' },
                { name: 'Target Pcs', data: trendBulanan.map((tb) => tb.target_pcs), type: 'line' }
            ];
        }
        return [
            { name: 'Jumlah PO', data: trendBulananPO, type: 'area' }
        ];
    };

    const getColors = () => {
        if (metric === 'omset') return ['#8B5CF6'];
        if (metric === 'pcs') return ['#10B981', '#6EE7B7'];
        return ['#3B82F6'];
    };

    return (
        <div className="space-y-6">
            {/* Filter Date Range Section */}
            <Card className="shadow-sm border-l-4 border-l-indigo-500 bg-gradient-to-r from-slate-50 to-white">
                <CardContent className="p-4">
                    <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div className="flex flex-col sm:flex-row sm:items-center gap-4 flex-1">
                            <div className="space-y-1 min-w-[160px]">
                                <Label className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <Filter className="h-3.5 w-3.5 text-indigo-500" />
                                    Filter Waktu
                                </Label>
                                <Select value={dateFilter} onValueChange={(val) => {
                                    setDateFilter(val);
                                    if (val !== 'custom') {
                                        applyFilters(val);
                                    }
                                }}>
                                    <SelectTrigger className="h-9 bg-white border-slate-200 focus:ring-indigo-500">
                                        <SelectValue placeholder="Pilih filter" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="harian">Hari Ini</SelectItem>
                                        <SelectItem value="mingguan">Minggu Ini</SelectItem>
                                        <SelectItem value="bulanan">Bulan Ini</SelectItem>
                                        <SelectItem value="custom">Range Tanggal</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {dateFilter === 'custom' && (
                                <div className="flex flex-col sm:flex-row items-end gap-3 flex-1">
                                    <div className="space-y-1 w-full sm:w-auto flex-1">
                                        <Label className="text-xs font-bold text-slate-700">Dari Tanggal</Label>
                                        <Input
                                            type="date"
                                            value={fromDate}
                                            onChange={(e) => setFromDate(e.target.value)}
                                            className="h-9 bg-white border-slate-200 focus:ring-indigo-500 w-full"
                                        />
                                    </div>
                                    <div className="space-y-1 w-full sm:w-auto flex-1">
                                        <Label className="text-xs font-bold text-slate-700">Sampai Tanggal</Label>
                                        <Input
                                            type="date"
                                            value={toDate}
                                            onChange={(e) => setToDate(e.target.value)}
                                            className="h-9 bg-white border-slate-200 focus:ring-indigo-500 w-full"
                                        />
                                    </div>
                                    <Button
                                        onClick={() => applyFilters('custom', fromDate, toDate)}
                                        className="h-9 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 hover:shadow-md transition duration-200"
                                    >
                                        Terapkan
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <StatGrid cards={stats.cards ?? []} />

            {/* Target Progress Section */}
            {stats.target_progress && (
                <div className="space-y-3">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <Target className="h-4 w-4 text-indigo-650" /> Target Bulanan ({stats.target_progress.month_name})
                            </h3>
                            <p className="text-xs text-muted-foreground">Monitor pencapaian target bulanan Anda.</p>
                        </div>
                    </div>

                    <div>
                        <Card className="bg-gradient-to-br from-emerald-50/50 to-white border-l-4 border-emerald-500">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Target className="h-4 w-4 text-emerald-500" /> Target Qty (Pcs) Bulan Ini ({stats.target_progress.month_name})
                                </CardTitle>
                                <CardDescription>Realisasi quantity produk terjual dibandingkan target.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-baseline justify-between">
                                    <span className="text-2xl font-black text-slate-800 font-mono">
                                        {stats.target_progress.actual_pcs.toLocaleString('id-ID')} Pcs
                                    </span>
                                    <span className="text-xs text-muted-foreground font-semibold">
                                        dari target {stats.target_progress.target_pcs.toLocaleString('id-ID')} Pcs
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
                                    <div className="w-full bg-slate-100 rounded-full h-2 overflow-hidden shadow-inner">
                                        <div 
                                            className="bg-emerald-500 h-full rounded-full transition-all duration-500" 
                                            style={{ width: `${stats.target_progress.target_pcs > 0 ? Math.min(100, stats.target_progress.pcs_percentage) : 0}%` }} 
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            )}

            {/* PO Type Breakdown & Tahapan Progress Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <POTypeDistributionWidget data={stats.po_type_distribution} />

                {/* Tahapan Progress */}
                <Card className="transition hover:shadow-md">
                    <CardHeader>
                        <CardTitle className="text-base font-bold text-slate-800">Tahapan Progress Produksi</CardTitle>
                        <CardDescription className="text-xs">Jumlah PO yang sedang dikerjakan di setiap tahapan progress.</CardDescription>
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
                                    plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                                    xaxis: { categories: progressDist.map((r) => r.label), labels: { rotate: -20, style: { fontSize: '10px' } } },
                                    colors: ['#F59E0B'],
                                }}
                            />
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Ringkasan Keuangan & Sinkronisasi Mingguan */}
            <div className="space-y-4">
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <Coins className="h-5 w-5 text-indigo-600" />
                            Ringkasan Keuangan & Sinkronisasi Mingguan
                        </h2>
                        <p className="text-xs text-muted-foreground">
                            Pantau saldo kas/bank dan persiapkan setoran & sinkronisasi mingguan setiap hari Sabtu.
                        </p>
                    </div>
                </div>

                {/* Saldo Akun / Rekening Koran Grid */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {(stats.ringkasan_keuangan ?? []).map((account) => {
                        const isCash = account.bank.toUpperCase() === 'CASH';
                        return (
                            <Card key={account.id} className="relative overflow-hidden transition-all hover:shadow-md border-slate-200 bg-white">
                                <div className={`h-1.5 w-full ${isCash ? 'bg-emerald-500' : 'bg-indigo-600'}`} />
                                <CardHeader className="p-4 pb-2">
                                    <div className="flex items-center justify-between">
                                        <Badge className={`px-2 py-0.5 text-[9px] font-black tracking-wider uppercase font-mono border-0 text-white ${getBankColor(account.bank)}`}>
                                            {account.bank}
                                        </Badge>
                                        {isCash ? (
                                            <Wallet className="h-4 w-4 text-emerald-500" />
                                        ) : (
                                            <Landmark className="h-4 w-4 text-indigo-500" />
                                        )}
                                    </div>
                                    <CardTitle className="text-sm font-black mt-2 font-mono tracking-tight text-slate-800">
                                        {account.nomor_rekening}
                                    </CardTitle>
                                    <CardDescription className="text-[11px] truncate text-slate-500 font-semibold">
                                        A/N: {account.atas_nama}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-4 pt-2 space-y-2 border-t bg-slate-50/20">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="text-muted-foreground flex items-center gap-1">
                                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Terverifikasi
                                        </span>
                                        <span className="font-mono font-bold text-slate-800">{formatRupiah(account.total_verified)}</span>
                                    </div>
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="text-muted-foreground flex items-center gap-1">
                                            <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Menunggu Validasi
                                        </span>
                                        <span className="font-mono font-bold text-amber-600">{formatRupiah(account.total_pending)}</span>
                                    </div>
                                    <div className="pt-2 border-t flex items-center justify-between text-xs font-black">
                                        <span className="text-slate-700">Total Akumulasi</span>
                                        <span className="font-mono text-indigo-700 text-[13px]">{formatRupiah(account.total_all)}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Panduan Aksi & Setoran Hari Sabtu */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    {/* Setoran Uang Tunai */}
                    <Card className="border-l-4 border-l-emerald-500 bg-gradient-to-br from-emerald-50/10 to-white">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-bold flex items-center gap-2 text-slate-800">
                                <Wallet className="h-4 w-4 text-emerald-650" /> Setoran Uang Tunai (CASH)
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Akumulasi dana tunai terkumpul untuk disetor ke Admin Keuangan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="p-3 bg-emerald-50/40 border border-emerald-100/80 rounded-xl">
                                <div className="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Tunai Masuk Minggu Ini</div>
                                <div className="text-2xl font-black text-emerald-700 font-mono tracking-tight mt-1">
                                    {formatRupiah(
                                        (stats.ringkasan_keuangan ?? [])
                                            .filter(a => a.bank.toUpperCase() === 'CASH')
                                            .reduce((sum, a) => sum + a.week_total, 0)
                                    )}
                                </div>
                                <div className="flex justify-between items-center mt-2 pt-2 border-t border-emerald-100 text-[10px] text-emerald-800 font-semibold">
                                    <span>Terverifikasi: {formatRupiah(
                                        (stats.ringkasan_keuangan ?? [])
                                            .filter(a => a.bank.toUpperCase() === 'CASH')
                                            .reduce((sum, a) => sum + a.week_verified, 0)
                                    )}</span>
                                    <span>Menunggu: {formatRupiah(
                                        (stats.ringkasan_keuangan ?? [])
                                            .filter(a => a.bank.toUpperCase() === 'CASH')
                                            .reduce((sum, a) => sum + a.week_pending, 0)
                                    )}</span>
                                </div>
                            </div>

                            <div className="space-y-2 text-xs text-slate-600 font-medium">
                                <div className="flex gap-2">
                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">1</span>
                                    <span>Hitung fisik uang tunai di laci/kas fisik Anda.</span>
                                </div>
                                <div className="flex gap-2">
                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">2</span>
                                    <span>Setorkan fisik uang tunai ke Admin Keuangan setiap hari Sabtu.</span>
                                </div>
                                <div className="flex gap-2">
                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">3</span>
                                    <span>Minta Admin Keuangan memverifikasi pembayaran CASH yang belum diverifikasi.</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Sinkronisasi Rekening Bank */}
                    <Card className="border-l-4 border-l-indigo-600 bg-gradient-to-br from-indigo-50/10 to-white">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-bold flex items-center gap-2 text-slate-800">
                                <Landmark className="h-4 w-4 text-indigo-600" /> Sinkronisasi Rekening Bank
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Akumulasi dana masuk via transfer bank untuk pencocokan mutasi.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="p-3 bg-indigo-50/40 border border-indigo-100/80 rounded-xl">
                                <div className="text-[10px] text-indigo-800 font-bold uppercase tracking-wider">Transfer Masuk Minggu Ini</div>
                                <div className="text-2xl font-black text-indigo-750 font-mono tracking-tight mt-1">
                                    {formatRupiah(
                                        (stats.ringkasan_keuangan ?? [])
                                            .filter(a => a.bank.toUpperCase() !== 'CASH')
                                            .reduce((sum, a) => sum + a.week_total, 0)
                                    )}
                                </div>
                                <div className="flex justify-between items-center mt-2 pt-2 border-t border-indigo-100 text-[10px] text-indigo-800 font-semibold">
                                    <span>Terverifikasi: {formatRupiah(
                                        (stats.ringkasan_keuangan ?? [])
                                            .filter(a => a.bank.toUpperCase() !== 'CASH')
                                            .reduce((sum, a) => sum + a.week_verified, 0)
                                    )}</span>
                                    <span>Menunggu: {formatRupiah(
                                        (stats.ringkasan_keuangan ?? [])
                                            .filter(a => a.bank.toUpperCase() !== 'CASH')
                                            .reduce((sum, a) => sum + a.week_pending, 0)
                                    )}</span>
                                </div>
                            </div>

                            <div className="space-y-2 text-xs text-slate-600 font-medium">
                                <div className="flex gap-2">
                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-bold">1</span>
                                    <span>Cek mutasi m-banking per hari Sabtu secara berkala.</span>
                                </div>
                                <div className="flex gap-2">
                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-bold">2</span>
                                    <span>Cocokkan data rekening koran dengan pembayaran yang belum divalidasi di bawah.</span>
                                </div>
                                <div className="flex gap-2">
                                    <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-bold">3</span>
                                    <span>Koordinasikan ke Keuangan untuk validasi mutasi yang sesuai.</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Ringkasan Status Transaksi Minggu Ini */}
                    <Card className="border-l-4 border-l-slate-400 bg-slate-50/10">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-bold flex items-center gap-2 text-slate-800">
                                <RefreshCw className="h-4 w-4 text-slate-600" /> Status Sinkronisasi Pembayaran
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Status verifikasi pembayaran terdaftar dalam minggu ini.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {(() => {
                                const weeklyTotal = (stats.ringkasan_keuangan ?? []).reduce((sum, a) => sum + a.week_total, 0);
                                const weeklyVerified = (stats.ringkasan_keuangan ?? []).reduce((sum, a) => sum + a.week_verified, 0);
                                const weeklyPending = (stats.ringkasan_keuangan ?? []).reduce((sum, a) => sum + a.week_pending, 0);
                                const verifiedPct = weeklyTotal > 0 ? Math.round((weeklyVerified / weeklyTotal) * 100) : 0;
                                
                                return (
                                    <>
                                        <div className="space-y-1">
                                            <div className="flex justify-between text-xs font-bold text-slate-700">
                                                <span>Kemajuan Validasi Keuangan</span>
                                                <span>{verifiedPct}% Terverifikasi</span>
                                            </div>
                                            <div className="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden shadow-inner border border-slate-200">
                                                <div 
                                                    className="bg-indigo-600 h-full rounded-full transition-all duration-500" 
                                                    style={{ width: `${verifiedPct}%` }} 
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-2 pt-1">
                                            <div className="flex justify-between text-xs">
                                                <span className="text-slate-500 font-medium">Dana Terverifikasi</span>
                                                <span className="font-mono font-bold text-emerald-600">{formatRupiah(weeklyVerified)}</span>
                                            </div>
                                            <div className="flex justify-between text-xs">
                                                <span className="text-slate-500 font-medium">Dana Menunggu Verifikasi</span>
                                                <span className="font-mono font-bold text-amber-600">{formatRupiah(weeklyPending)}</span>
                                            </div>
                                            <div className="flex justify-between text-xs pt-1 border-t font-bold">
                                                <span className="text-slate-700">Total Pembayaran Masuk</span>
                                                <span className="font-mono text-slate-900">{formatRupiah(weeklyTotal)}</span>
                                            </div>
                                        </div>
                                    </>
                                );
                            })()}
                        </CardContent>
                    </Card>
                </div>

                {/* 10 Transaksi Pembayaran Terbaru */}
                <Card className="border-slate-200 shadow-sm overflow-hidden">
                    <CardHeader className="flex flex-row items-center justify-between pb-3 border-b bg-slate-50/50">
                        <div>
                            <CardTitle className="text-sm flex items-center gap-2 font-bold text-slate-900">
                                <FileText className="h-4 w-4 text-indigo-500" />
                                Mutasi Pembayaran Masuk Terbaru (10 Terakhir)
                            </CardTitle>
                            <CardDescription className="text-xs text-slate-500 font-medium">
                                Gunakan tabel ini untuk mencocokkan mutasi rekening koran atau tanda terima tunai.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        {(stats.recent_payments ?? []).length === 0 ? (
                            <div className="py-12 flex flex-col items-center justify-center text-muted-foreground bg-white">
                                <Clock className="h-8 w-8 text-slate-300 mb-2" />
                                <p className="text-sm">Tidak ada riwayat pembayaran masuk.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto bg-white">
                                <table className="w-full text-xs border-t">
                                    <thead className="bg-slate-50/80 text-[10px] uppercase tracking-wider text-slate-500 border-b font-bold">
                                        <tr>
                                            <th className="px-4 py-3 text-left">Tanggal</th>
                                            <th className="px-4 py-3 text-left">No. PO</th>
                                            <th className="px-4 py-3 text-left">Pelanggan</th>
                                            <th className="px-4 py-3 text-left">Akun Pembayaran</th>
                                            <th className="px-4 py-3 text-right">Jumlah</th>
                                            <th className="px-4 py-3 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(stats.recent_payments ?? []).map((p) => (
                                            <tr key={p.id} className="border-b last:border-0 hover:bg-slate-50/50 transition-colors">
                                                <td className="px-4 py-3 font-mono text-slate-500 font-medium">{formatDate(p.payment_date)}</td>
                                                <td className="px-4 py-3 font-mono font-bold text-slate-800">
                                                    <div className="flex flex-col">
                                                        <span>{p.no_po}</span>
                                                        <span className="text-[9px] text-muted-foreground font-medium truncate max-w-[120px]">{p.nama_po}</span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="font-semibold text-slate-800">{p.pelanggan}</div>
                                                    {p.notes && <div className="text-[10px] text-slate-400 italic max-w-[150px] truncate">Catatan: "{p.notes}"</div>}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-1.5">
                                                        <Badge className={`px-1.5 py-0 text-[9px] font-extrabold uppercase font-mono border-0 text-white ${getBankColor(p.bank)}`}>
                                                            {p.bank}
                                                        </Badge>
                                                        <span className="font-mono text-slate-500 font-medium">{p.nomor_rekening}</span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-right font-mono font-extrabold text-slate-900 text-[13px]">
                                                    {formatRupiah(p.amount)}
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    {p.verified ? (
                                                        <Badge className="bg-emerald-50 hover:bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[9px] inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full">
                                                            <span className="w-1 h-1 rounded-full bg-emerald-500"></span> Terverifikasi
                                                        </Badge>
                                                    ) : (
                                                        <Badge className="bg-amber-50 hover:bg-amber-50 text-amber-700 border border-amber-200 font-bold text-[9px] inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full">
                                                            <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Menunggu
                                                        </Badge>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">Tren Order 14 Hari Terakhir</CardTitle>
                        <CardDescription>Jumlah PO masuk per hari.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Chart
                            type="area"
                            height={280}
                            series={[{ name: 'PO', data: trendValues }]}
                            options={{
                                xaxis: { categories: trendDates, labels: { style: { fontSize: '11px' } } },
                                yaxis: { labels: { style: { fontSize: '11px' } } },
                                colors: ['#3B82F6'],
                                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0 } },
                            }}
                        />
                    </CardContent>
                </Card>

                <StatusBreakdown items={stats.status_breakdown ?? []} />
            </div>

            <Card>
                <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pb-2">
                    <div>
                        <CardTitle className="text-base">Grafik Pertumbuhan & Kinerja Bulanan ({new Date().getFullYear()})</CardTitle>
                        <CardDescription>Visualisasi jumlah PO, pcs diproduksi, dan nilai omset per bulan.</CardDescription>
                    </div>
                    <div className="flex bg-muted/80 p-0.5 rounded-lg border">
                        <button
                            onClick={() => setMetric('omset')}
                            className={`px-3 py-1.5 text-xs font-medium rounded-md transition ${metric === 'omset' ? 'bg-background shadow text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Omset
                        </button>
                        <button
                            onClick={() => setMetric('po')}
                            className={`px-3 py-1.5 text-xs font-medium rounded-md transition ${metric === 'po' ? 'bg-background shadow text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Jumlah PO
                        </button>
                        <button
                            onClick={() => setMetric('pcs')}
                            className={`px-3 py-1.5 text-xs font-medium rounded-md transition ${metric === 'pcs' ? 'bg-background shadow text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Total Pcs
                        </button>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    <Chart
                        type="line"
                        height={300}
                        series={getSeries()}
                        options={{
                            xaxis: { categories: trendBulananMonths },
                            yaxis: {
                                labels: {
                                    formatter: (v) => metric === 'omset' ? formatRupiah(v) : v
                                }
                            },
                            colors: getColors(),
                            stroke: { curve: 'smooth', width: metric === 'po' ? [3] : [3, 2] },
                            fill: { type: 'solid', opacity: metric === 'po' ? [0.15] : [0.15, 0.95] },
                            tooltip: {
                                y: {
                                    formatter: (v) => metric === 'omset' ? formatRupiah(v) : `${v} ${metric === 'po' ? 'PO' : 'pcs'}`
                                }
                            }
                        }}
                    />
                    
                    <div className="grid grid-cols-3 gap-3 pt-2 text-center border-t">
                        <div className="p-2">
                            <div className="text-[10px] sm:text-xs uppercase tracking-wider text-muted-foreground">Total Omset Setahun</div>
                            <div className="font-mono text-sm sm:text-lg font-bold text-violet-600">
                                {formatRupiah(trendBulananOmset.reduce((a, b) => a + b, 0))}
                            </div>
                        </div>
                        <div className="p-2">
                            <div className="text-[10px] sm:text-xs uppercase tracking-wider text-muted-foreground">Total PO Setahun</div>
                            <div className="font-mono text-sm sm:text-lg font-bold text-blue-600">
                                {trendBulananPO.reduce((a, b) => a + b, 0)} PO
                            </div>
                        </div>
                        <div className="p-2">
                            <div className="text-[10px] sm:text-xs uppercase tracking-wider text-muted-foreground">Total Pcs Setahun</div>
                            <div className="font-mono text-sm sm:text-lg font-bold text-emerald-600">
                                {trendBulananPcs.reduce((a, b) => a + b, 0)} pcs
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Produk Terpopuler</CardTitle>
                        <CardDescription>Top {produk.length} produk berdasarkan jumlah quantity.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {produk.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">Belum ada data.</p>
                        ) : (
                            <Chart
                                type="bar"
                                height={Math.max(280, produk.length * 32)}
                                series={[{ name: 'Qty', data: produk.map((p) => p.total_qty) }]}
                                options={{
                                    plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '70%' } },
                                    xaxis: { categories: produk.map((p) => p.nama) },
                                    colors: ['#10B981'],
                                }}
                            />
                        )}
                    </CardContent>
                </Card>

                <div className="space-y-4">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-semibold">Kategori Favorit</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col justify-center min-h-[220px]">
                                {kategori.length === 0 ? (
                                    <p className="text-center text-xs text-muted-foreground">Belum ada data.</p>
                                ) : (
                                    <Chart
                                        type="donut"
                                        height={200}
                                        series={kategori.map((k) => k.count)}
                                        options={{
                                            labels: kategori.map((k) => k.label),
                                            colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#EF4444'],
                                            legend: { show: false },
                                        }}
                                    />
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-semibold">Kategori Pelanggan</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col justify-center min-h-[220px]">
                                {kategoriPelanggan.length === 0 ? (
                                    <p className="text-center text-xs text-muted-foreground">Belum ada data.</p>
                                ) : (
                                    <Chart
                                        type="donut"
                                        height={200}
                                        series={kategoriPelanggan.map((kp) => kp.count)}
                                        options={{
                                            labels: kategoriPelanggan.map((kp) => kp.label),
                                            colors: ['#EC4899', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4', '#EF4444'],
                                            legend: { show: false },
                                        }}
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Sumber Order</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {sumber.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">Belum ada data.</p>
                            ) : (
                                <Chart
                                    type="bar"
                                    height={Math.max(200, sumber.length * 32)}
                                    series={[{ name: 'PO', data: sumber.map((s) => s.count) }]}
                                    options={{
                                        plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '70%' } },
                                        xaxis: { categories: sumber.map((s) => s.label) },
                                        colors: ['#8B5CF6'],
                                    }}
                                />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <TopList
                    title="Top 5 Pelanggan"
                    description="Berdasarkan total transaksi."
                    items={stats.top_pelanggan ?? []}
                    valueKey="total_order" valueLabel="order"
                    currencyKey="total_value"
                    link={{ href: route('master.pelanggan.index'), label: 'Lihat semua' }}
                />
                <TopList
                    title="Top Wilayah"
                    description="Distribusi pelanggan per kabupaten."
                    items={stats.wilayah_top ?? []}
                    valueKey="count" valueLabel="order"
                />
            </div>

            {/* PO Siap Dikirim Section */}
            <div className="mb-4">
                <POSiapDikirimWidget items={stats.po_siap_dikirim ?? []} />
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <POListWidget
                    title="Deadline Mendekat (≤7 hari)"
                    description="PO yang perlu perhatian segera."
                    items={stats.deadline_mendekat ?? []}
                    link={{ href: route('orders.index') + '?status=on_progress', label: 'Lihat semua' }}
                />
                <POListWidget
                    title="PO Terlambat"
                    description="Sudah melewati deadline customer."
                    items={stats.po_terlambat ?? []}
                    link={{ href: route('orders.index') + '?status=delay', label: 'Lihat semua' }}
                />
            </div>

            <POListWidget
                title="10 PO Terbaru"
                description="Order paling baru masuk."
                items={stats.po_terbaru ?? []}
                link={{ href: route('orders.index'), label: 'Semua PO' }}
            />

            {/* Tanda Jadi & Refund Pending Section */}
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Tanda Jadi Pending Validasi */}
                <Card className="border-slate-200 shadow-sm overflow-hidden">
                    <CardHeader className="flex flex-row items-center justify-between pb-3 border-b bg-slate-50/50">
                        <div>
                            <CardTitle className="text-base flex items-center gap-2 font-bold text-slate-900">
                                <Sparkles className="h-4 w-4 text-amber-500" />
                                Tanda Jadi Menunggu Validasi
                            </CardTitle>
                            <CardDescription className="text-xs text-slate-500">Pembayaran DP desain menunggu verifikasi keuangan.</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm" className="text-indigo-600 hover:text-indigo-700 font-semibold text-xs">
                            <Link href={route('invoices.list') + '?tab=tanda_jadi'}>
                                Lihat Semua <ArrowUpRight className="h-3.5 w-3.5 ml-1" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0">
                        {(stats.dp_pending_list ?? []).length === 0 ? (
                            <div className="py-12 flex flex-col items-center justify-center text-muted-foreground">
                                <CheckCircle2 className="h-8 w-8 text-slate-300 mb-2" />
                                <p className="text-sm">Tidak ada tanda jadi menunggu validasi.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm border-t">
                                    <thead className="bg-slate-50/80 text-[10px] uppercase tracking-wider text-slate-500 border-b">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-bold">No. Transaksi</th>
                                            <th className="px-4 py-3 text-left font-bold">Pelanggan</th>
                                            <th className="px-4 py-3 text-right font-bold">Nominal</th>
                                            <th className="px-4 py-3 text-left font-bold">Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(stats.dp_pending_list ?? []).map((dp) => (
                                            <tr key={dp.id} className="border-b last:border-0 hover:bg-slate-50/50 transition-colors">
                                                <td className="px-4 py-3 font-mono text-xs font-semibold text-slate-700">
                                                    <div className="flex flex-col">
                                                        <span>{dp.deposit_number}</span>
                                                        <span className="text-[9px] text-indigo-600 font-bold uppercase">{dp.brand?.kode}</span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="font-semibold text-xs text-slate-800">{dp.customer?.nama ?? dp.customer_name}</div>
                                                    <div className="text-[10px] text-slate-400 truncate max-w-[150px]">{dp.description}</div>
                                                </td>
                                                <td className="px-4 py-3 text-right font-mono text-xs font-bold text-indigo-600">{formatRupiah(dp.amount)}</td>
                                                <td className="px-4 py-3 text-xs text-slate-500 font-mono">{formatDate(dp.payment_date)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Refund Pending Review */}
                <Card className="border-slate-200 shadow-sm overflow-hidden">
                    <CardHeader className="flex flex-row items-center justify-between pb-3 border-b bg-slate-50/50">
                        <div>
                            <CardTitle className="text-base flex items-center gap-2 font-bold text-slate-900">
                                <RotateCcw className="h-4 w-4 text-rose-500" />
                                Refund Menunggu Review
                            </CardTitle>
                            <CardDescription className="text-xs text-slate-500">Pengajuan refund yang menunggu verifikasi.</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm" className="text-indigo-600 hover:text-indigo-700 font-semibold text-xs">
                            <Link href={route('refunds.index') + '?status=pending_review'}>
                                Lihat Semua <ArrowUpRight className="h-3.5 w-3.5 ml-1" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0">
                        {(stats.refund_pending_list ?? []).length === 0 ? (
                            <div className="py-12 flex flex-col items-center justify-center text-muted-foreground">
                                <CheckCircle2 className="h-8 w-8 text-slate-300 mb-2" />
                                <p className="text-sm">Tidak ada refund menunggu review.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm border-t">
                                    <thead className="bg-slate-50/80 text-[10px] uppercase tracking-wider text-slate-500 border-b">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-bold">No. Refund</th>
                                            <th className="px-4 py-3 text-left font-bold">No. PO</th>
                                            <th className="px-4 py-3 text-right font-bold">Nominal</th>
                                            <th className="px-4 py-3 text-left font-bold">Diajukan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(stats.refund_pending_list ?? []).map((r) => (
                                            <tr key={r.id} className="border-b last:border-0 hover:bg-slate-50/50 transition-colors">
                                                <td className="px-4 py-3 font-mono text-xs font-semibold text-slate-700">{r.refund_number}</td>
                                                <td className="px-4 py-3 font-mono text-xs font-bold text-slate-900">{r.order?.no_po}</td>
                                                <td className="px-4 py-3 text-right font-mono text-xs font-bold text-rose-600">{formatRupiah(r.nominal_refund)}</td>
                                                <td className="px-4 py-3 text-xs">
                                                    <div className="font-semibold text-slate-800 text-xs">{r.creator?.name}</div>
                                                    <div className="text-slate-400 text-[10px] font-mono">{formatDate(r.created_at)}</div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
