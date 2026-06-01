import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { 
    Search, Receipt, CheckCircle2, ExternalLink, Calendar, 
    ShieldCheck, Clock, Banknote, AlertTriangle, ArrowRight,
    Sparkles, Layers, RefreshCw, FileText, BarChart3, TrendingUp, Info
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_VARIANT = {
    draft: 'outline', 
    validated: 'info', 
    published: 'success',
    sent: 'info', 
    paid: 'success', 
    overdue: 'destructive', 
    cancel: 'secondary',
};

export default function InvoiceIndex({ 
    brands = [], 
    total_lunas = 0, 
    total_belum_lunas = 0, 
    total_tanda_jadi = 0, 
    total_pending = 0, 
    brand_breakdown = [], 
    unpaid_invoices = [], 
    paid_invoices = [], 
    filters = {},
    can = {}
}) {
    const [brandId, setBrandId] = useState(filters?.brand_id ?? 'all');
    const [activeTab, setActiveTab] = useState('unpaid');

    function applyBrandFilter(v) {
        setBrandId(v);
        router.get(route('invoices.index'), {
            brand_id: v === 'all' ? '' : v,
        }, { preserveScroll: true, preserveState: true });
    }

    const totalPortfolio = Number(total_lunas) + Number(total_belum_lunas) + Number(total_tanda_jadi);
    const recoveryRate = totalPortfolio > 0 ? (Number(total_lunas) / totalPortfolio) * 100 : 0;

    return (
        <AppLayout title="Dashboard Keuangan">
            <Head title="Ringkasan Keuangan & Invoice" />

            <div className="space-y-6">
                {/* Unified Premium Financial Notification Banner */}
                <div className={`flex flex-col sm:flex-row items-center justify-between gap-4 p-5 rounded-2xl border shadow-sm transition-all duration-300 ${
                    total_pending > 0 
                        ? 'bg-gradient-to-r from-amber-50 via-amber-100/35 to-amber-50 border-amber-200 text-amber-950 shadow-amber-100/10' 
                        : 'bg-gradient-to-r from-indigo-50/40 via-indigo-50/70 to-indigo-50/40 border-indigo-100 text-indigo-950'
                }`}>
                    <div className="flex items-start gap-3 w-full">
                        <div className={`h-10 w-10 rounded-xl flex items-center justify-center border shadow-xs shrink-0 mt-0.5 ${
                            total_pending > 0 
                                ? 'bg-amber-100 border-amber-200 text-amber-600' 
                                : 'bg-indigo-100 border-indigo-150 text-indigo-600'
                        }`}>
                            {total_pending > 0 ? (
                                <AlertTriangle className="h-5 w-5 animate-pulse text-amber-600" />
                            ) : (
                                <CheckCircle2 className="h-5 w-5 text-indigo-600" />
                            )}
                        </div>
                        <div className="space-y-1">
                            <strong className={`block font-extrabold text-sm ${
                                total_pending > 0 ? 'text-amber-900' : 'text-indigo-900'
                            }`}>
                                {total_pending > 0 
                                    ? 'Pemberitahuan Dashboard Finansial: Butuh Validasi Pembayaran' 
                                    : 'Pemberitahuan Dashboard Finansial'}
                            </strong>
                            <p className="text-xs leading-relaxed opacity-95 font-semibold">
                                {total_pending > 0 ? (
                                    <>
                                        Terdapat <span className="font-extrabold text-amber-900">{formatRupiah(total_pending)}</span> pembayaran pending (mutasi rekening koran) yang memerlukan verifikasi Admin Keuangan.
                                    </>
                                ) : (
                                    <>
                                        Seluruh kalkulasi finansial di atas terhubung langsung dengan core PO Ledger dan Tanda Jadi Desain. Jika Anda melakukan validasi pembayaran baru di submenu <strong className="text-indigo-900">Validasi Pembayaran</strong> atau memproses Tanda Jadi di submenu <strong className="text-indigo-900">List Invoice</strong>, angka likuiditas dan Recovery Rate di atas akan terupdate secara real-time.
                                    </>
                                )}
                            </p>
                        </div>
                    </div>
                    {total_pending > 0 && (
                        <Button asChild className="bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-md shrink-0 self-end sm:self-center">
                            <Link href={route('invoices.payments.pending')}>
                                <ShieldCheck className="h-4 w-4 mr-1.5" />
                                Validasi Sekarang
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Stunning Hero Header */}
                <div className="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-8 rounded-2xl shadow-xl text-white border border-indigo-900/50">
                    <div className="absolute top-0 right-0 p-4 opacity-10">
                        <Sparkles className="h-48 w-48 text-indigo-400" />
                    </div>
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-2">
                            <span className="bg-indigo-500/20 text-indigo-300 text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-indigo-500/30 backdrop-blur-sm">
                                Executive Financial Center
                            </span>
                            <h1 className="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-indigo-200 bg-clip-text text-transparent">
                                Ringkasan Keuangan & Likuiditas
                            </h1>
                            <p className="text-slate-300 max-w-2xl text-sm leading-relaxed">
                                Pantau kesehatan keuangan, realisasi tagihan lunas, outstanding piutang PO aktif, tanda jadi desain, dan rekonsiliasi mutasi rekening secara terpusat.
                            </p>
                        </div>
                        <div className="flex flex-col sm:flex-row gap-3">
                            <Button asChild className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-md rounded-xl transition-all hover:scale-102">
                                <Link href={route('invoices.list')}>
                                    <Receipt className="h-4 w-4 mr-2" />
                                    Kelola Invoice & TJ
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="bg-slate-800/50 text-white border-slate-700 hover:bg-slate-800/80 hover:text-white rounded-xl">
                                <Link href={route('invoices.payments.pending')}>
                                    <ShieldCheck className="h-4 w-4 mr-2 text-emerald-400" />
                                    Rekonsiliasi Mutasi
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Filter and Overview Controls */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-xl border shadow-sm">
                    <div className="flex items-center gap-2">
                        <TrendingUp className="h-5 w-5 text-indigo-600" />
                        <span className="text-sm font-bold text-slate-800">Filter Analisis Brand</span>
                    </div>
                    <div className="w-full sm:w-64">
                        <Select value={brandId} onValueChange={applyBrandFilter}>
                            <SelectTrigger className="bg-slate-50 border-slate-200 rounded-lg">
                                <SelectValue placeholder="Pilih Brand" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Brand</SelectItem>
                                {brands.map((b) => (
                                    <SelectItem key={b.id} value={b.id}>
                                        {b.nama_brand} ({b.kode})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Dashboard KPI Grid */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {/* Revenue Realized (Lunas) */}
                    <Card className="border-l-4 border-l-emerald-500 bg-white shadow-sm hover:shadow-md transition-shadow">
                        <CardContent className="pt-6">
                            <div className="flex justify-between items-start">
                                <div className="space-y-1">
                                    <span className="text-[10px] text-muted-foreground font-bold tracking-wider uppercase block">Total Pemasukan (Lunas)</span>
                                    <h3 className="text-2xl font-black text-slate-800 font-mono">
                                        {formatRupiah(total_lunas)}
                                    </h3>
                                    <span className="text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                                        Realisasi Keuangan Masuk
                                    </span>
                                </div>
                                <div className="h-10 w-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 border border-emerald-100 shadow-sm">
                                    <CheckCircle2 className="h-5 w-5" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Receivables Outstanding (Belum Lunas) */}
                    <Card className="border-l-4 border-l-orange-500 bg-white shadow-sm hover:shadow-md transition-shadow">
                        <CardContent className="pt-6">
                            <div className="flex justify-between items-start">
                                <div className="space-y-1">
                                    <span className="text-[10px] text-muted-foreground font-bold tracking-wider uppercase block">Piutang Dagang (Belum Lunas)</span>
                                    <h3 className="text-2xl font-black text-slate-800 font-mono">
                                        {formatRupiah(total_belum_lunas)}
                                    </h3>
                                    <span className="text-[11px] font-semibold text-orange-600">
                                        Outstanding Invoice Aktif
                                    </span>
                                </div>
                                <div className="h-10 w-10 bg-orange-50 rounded-xl flex items-center justify-center text-orange-600 border border-orange-100 shadow-sm">
                                    <Clock className="h-5 w-5" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Design Deposit (Tanda Jadi) */}
                    <Card className="border-l-4 border-l-indigo-500 bg-white shadow-sm hover:shadow-md transition-shadow">
                        <CardContent className="pt-6">
                            <div className="flex justify-between items-start">
                                <div className="space-y-1">
                                    <span className="text-[10px] text-muted-foreground font-bold tracking-wider uppercase block">Tanda Jadi Desain (TJ)</span>
                                    <h3 className="text-2xl font-black text-slate-800 font-mono">
                                        {formatRupiah(total_tanda_jadi)}
                                    </h3>
                                    <span className="text-[11px] font-semibold text-indigo-600">
                                        Dana Booking Desain
                                    </span>
                                </div>
                                <div className="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 border border-indigo-100 shadow-sm">
                                    <Banknote className="h-5 w-5" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Pending Mutasi */}
                    <Card className="border-l-4 border-l-amber-500 bg-white shadow-sm hover:shadow-md transition-shadow">
                        <CardContent className="pt-6">
                            <div className="flex justify-between items-start">
                                <div className="space-y-1">
                                    <span className="text-[10px] text-muted-foreground font-bold tracking-wider uppercase block">Pending Validasi Rekening</span>
                                    <h3 className="text-2xl font-black text-slate-800 font-mono">
                                        {formatRupiah(total_pending)}
                                    </h3>
                                    {total_pending > 0 ? (
                                        <Link href={route('invoices.payments.pending')} className="text-[11px] font-bold text-amber-600 hover:underline flex items-center gap-0.5">
                                            Butuh Verifikasi Segera <ArrowRight className="h-3 w-3" />
                                        </Link>
                                    ) : (
                                        <span className="text-[11px] font-medium text-slate-500">
                                            Seluruh Mutasi Bersih
                                        </span>
                                    )}
                                </div>
                                <div className="h-10 w-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600 border border-amber-100 shadow-sm">
                                    <AlertTriangle className={`h-5 w-5 ${total_pending > 0 ? 'animate-bounce' : ''}`} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Analytical Visual Breakdown */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Liquidity Breakdown by Brand */}
                    <Card className="lg:col-span-1 shadow-sm border rounded-2xl overflow-hidden bg-white">
                        <CardHeader className="bg-slate-50/50 border-b pb-4">
                            <CardTitle className="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                <BarChart3 className="h-4 w-4 text-indigo-600" />
                                Kontribusi Brand
                            </CardTitle>
                            <CardDescription className="text-xs">Proporsi dan realisasi likuiditas per Brand.</CardDescription>
                        </CardHeader>
                        <CardContent className="pt-4 space-y-5">
                            {brand_breakdown.map((item) => {
                                const brandTotal = item.lunas + item.belum_lunas + item.tanda_jadi;
                                const pctLunas = brandTotal > 0 ? (item.lunas / brandTotal) * 100 : 0;
                                return (
                                    <div key={item.id} className="space-y-2">
                                        <div className="flex justify-between items-center text-xs">
                                            <span className="font-bold text-slate-700">{item.nama} <span className="text-indigo-600 text-[10px] ml-1 bg-indigo-50 px-1.5 py-0.5 rounded font-mono">{item.kode}</span></span>
                                            <span className="font-mono text-slate-500 font-semibold">{formatRupiah(brandTotal)}</span>
                                        </div>
                                        <div className="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden flex">
                                            <div className="bg-emerald-500 h-full transition-all duration-500" style={{ width: `${pctLunas}%` }} title={`Lunas: ${pctLunas.toFixed(1)}%`} />
                                            <div className="bg-orange-400 h-full transition-all duration-500" style={{ width: `${100 - pctLunas}%` }} title={`Outstanding`} />
                                        </div>
                                        <div className="flex justify-between text-[10px] text-slate-500 font-semibold">
                                            <span className="text-emerald-600">Lunas: {formatRupiah(item.lunas)}</span>
                                            <span className="text-orange-600">Piutang: {formatRupiah(item.belum_lunas)}</span>
                                        </div>
                                    </div>
                                );
                            })}
                            
                            <div className="border-t pt-4 bg-slate-50/50 -mx-6 -mb-6 p-4 text-xs font-semibold text-slate-600 space-y-2">
                                <div className="flex justify-between">
                                    <span>Recovery Rate Finansial:</span>
                                    <span className="text-emerald-600 font-black">{recoveryRate.toFixed(1)}%</span>
                                </div>
                                <div className="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div className="bg-emerald-600 h-full transition-all duration-500" style={{ width: `${recoveryRate}%` }} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actionable PO & Invoices List */}
                    <Card className="lg:col-span-2 shadow-sm border rounded-2xl overflow-hidden bg-white">
                        <CardHeader className="bg-slate-50/50 border-b pb-4 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                    <Receipt className="h-4 w-4 text-indigo-600" />
                                    Aktivitas Invoice Terakhir
                                </CardTitle>
                                <CardDescription className="text-xs">Daftar invoice berdasarkan klasifikasi status pembayaran.</CardDescription>
                            </div>
                            <div className="flex space-x-1 p-0.5 bg-slate-100 rounded-lg">
                                <button
                                    onClick={() => setActiveTab('unpaid')}
                                    className={`px-3 py-1.5 text-xs font-bold rounded-md transition-all ${
                                        activeTab === 'unpaid' 
                                            ? 'bg-white text-indigo-700 shadow-sm' 
                                            : 'text-slate-600 hover:text-slate-800'
                                    }`}
                                >
                                    Belum Lunas ({unpaid_invoices.length})
                                </button>
                                <button
                                    onClick={() => setActiveTab('paid')}
                                    className={`px-3 py-1.5 text-xs font-bold rounded-md transition-all ${
                                        activeTab === 'paid' 
                                            ? 'bg-white text-indigo-700 shadow-sm' 
                                            : 'text-slate-600 hover:text-slate-800'
                                    }`}
                                >
                                    Lunas ({paid_invoices.length})
                                </button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            {activeTab === 'unpaid' ? (
                                <Table>
                                    <TableHeader className="bg-slate-50/50">
                                        <TableRow>
                                            <TableHead className="font-semibold text-xs text-slate-600">Invoice / PO</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600">Pelanggan</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600">Brand</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600 text-right">Total Tagihan</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600 text-right text-orange-600">Sisa</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600">Status</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600 text-right">Detail</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {unpaid_invoices.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={7} className="py-12 text-center text-sm text-muted-foreground italic">
                                                    Tidak ada invoice outstanding belum lunas.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            unpaid_invoices.map((iv) => (
                                                <TableRow key={iv.id} className="hover:bg-slate-50/30 text-xs">
                                                    <TableCell className="font-medium">
                                                        <div className="font-mono font-bold text-slate-800">{iv.invoice_number}</div>
                                                        <div className="font-mono text-[10px] text-slate-400">PO: {iv.order?.no_po ?? '—'}</div>
                                                    </TableCell>
                                                    <TableCell className="font-semibold text-slate-700">{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                                    <TableCell><Badge variant="outline" className="font-bold text-indigo-700">{iv.brand?.kode ?? '-'}</Badge></TableCell>
                                                    <TableCell className="text-right font-mono font-semibold">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                    <TableCell className="text-right font-mono font-bold text-orange-600">{formatRupiah(iv.sisa_pembayaran)}</TableCell>
                                                    <TableCell><Badge variant={STATUS_VARIANT[iv.status] ?? 'outline'}>{iv.status}</Badge></TableCell>
                                                    <TableCell className="text-right">
                                                        <Button asChild size="xs" variant="outline" className="text-[11px] py-0.5 h-7">
                                                            <a href={route('invoice.public', iv.invoice_number)} target="_blank" rel="noopener noreferrer">
                                                                <ExternalLink className="h-3 w-3 mr-1" /> View
                                                            </a>
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            ) : (
                                <Table>
                                    <TableHeader className="bg-slate-50/50">
                                        <TableRow>
                                            <TableHead className="font-semibold text-xs text-slate-600">Invoice / PO</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600">Pelanggan</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600">Brand</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600 text-right">Total Tagihan</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600 text-right text-emerald-600">Realisasi</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600">Status</TableHead>
                                            <TableHead className="font-semibold text-xs text-slate-600 text-right">Detail</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {paid_invoices.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={7} className="py-12 text-center text-sm text-muted-foreground italic">
                                                    Belum ada invoice berstatus lunas.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            paid_invoices.map((iv) => (
                                                <TableRow key={iv.id} className="hover:bg-slate-50/30 text-xs">
                                                    <TableCell className="font-medium">
                                                        <div className="font-mono font-bold text-slate-800">{iv.invoice_number}</div>
                                                        <div className="font-mono text-[10px] text-slate-400">PO: {iv.order?.no_po ?? '—'}</div>
                                                    </TableCell>
                                                    <TableCell className="font-semibold text-slate-700">{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                                    <TableCell><Badge variant="outline" className="font-bold text-indigo-700">{iv.brand?.kode ?? '-'}</Badge></TableCell>
                                                    <TableCell className="text-right font-mono font-semibold">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                    <TableCell className="text-right font-mono font-bold text-emerald-600">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                    <TableCell><Badge variant="success">LUNAS</Badge></TableCell>
                                                    <TableCell className="text-right">
                                                        <Button asChild size="xs" variant="outline" className="text-[11px] py-0.5 h-7">
                                                            <a href={route('invoice.public', iv.invoice_number)} target="_blank" rel="noopener noreferrer">
                                                                <ExternalLink className="h-3 w-3 mr-1" /> View
                                                            </a>
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>

            </div>
        </AppLayout>
    );
}
