import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowUpRight, Landmark, Building2, ChevronDown, ChevronUp, SlidersHorizontal, Info, CheckCircle2, AlertCircle, FileText, FileCheck, TrendingUp, TrendingDown, RotateCcw } from 'lucide-react';
import { StatGrid } from '@/Components/Widgets';
import { formatDate, formatRupiah } from '@/lib/utils';

export default function Finance({ stats }) {
    const paymentStatus = stats.payment_status ?? [];
    const brands = stats.brands ?? [];
    const currentBrandId = stats.current_brand_id ?? 'all';
    const [expandedBankId, setExpandedBankId] = useState(null);

    function changeBrand(v) {
        const params = { brand_id: v };
        router.get(route('dashboard'), params, { preserveScroll: true, preserveState: true });
    }

    // Dynamic bank styling based on name
    const getBankColor = (bankName) => {
        const name = bankName.toLowerCase();
        if (name.includes('bca')) return 'bg-blue-600 text-white';
        if (name.includes('mandiri')) return 'bg-amber-500 text-slate-900 border-amber-600';
        if (name.includes('bri')) return 'bg-sky-700 text-white';
        if (name.includes('bni')) return 'bg-teal-600 text-white';
        if (name.includes('cash') || name.includes('tunai')) return 'bg-emerald-600 text-white';
        return 'bg-slate-600 text-white';
    };

    // Calculate aggregated brand reports totals
    const reports = stats.brand_financial_reports ?? [];
    const totalOmset = reports.reduce((sum, r) => sum + (r.total_revenue ?? 0), 0);
    const totalTerbayar = reports.reduce((sum, r) => sum + (r.total_payments ?? 0), 0);
    const totalSisa = reports.reduce((sum, r) => sum + (r.outstanding ?? 0), 0);

    return (
        <div className="space-y-6">
            {/* Brand Filter Control Bar */}
            <Card className="bg-gradient-to-r from-slate-50 to-indigo-50/20 border-slate-200/80 shadow-sm">
                <CardHeader className="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-indigo-500/10 text-indigo-600 rounded-lg">
                            <SlidersHorizontal className="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle className="text-base font-bold text-slate-800">Filter Analisis Keuangan</CardTitle>
                            <CardDescription className="text-xs text-slate-500">
                                Pilih brand/reseller untuk menyaring omset, tagihan outstanding, dan mutasi bank.
                            </CardDescription>
                        </div>
                    </div>
                    <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        {currentBrandId !== 'all' && (
                            <Badge variant="outline" className="self-start sm:self-auto border-indigo-200 bg-indigo-50/50 text-indigo-700 font-mono text-[10px] py-1 px-2.5">
                                Terfilter: {brands.find(b => b.id === currentBrandId)?.nama_brand ?? 'General'}
                            </Badge>
                        )}
                        <Select onValueChange={changeBrand} value={currentBrandId}>
                            <SelectTrigger className="w-full sm:w-64 bg-white border-slate-200 text-slate-700 font-medium hover:bg-slate-50/50 transition-colors">
                                <SelectValue placeholder="Semua Brand & Reseller" />
                            </SelectTrigger>
                            <SelectContent className="max-h-80">
                                <SelectItem value="all" className="font-semibold">Semua Brand & Reseller (Akumulasi)</SelectItem>
                                {brands.map((b) => (
                                    <SelectItem key={b.id} value={b.id} className="font-mono text-xs">
                                        {b.nama_brand} ({b.kode})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
            </Card>

            {/* Main Financial KPI Grid */}
            <StatGrid cards={stats.cards ?? []} />

            {/* Charts and Invoices Grid */}
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Status Pembayaran PO</CardTitle>
                        <CardDescription>Distribusi PO berdasarkan kondisi pembayaran.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {paymentStatus.length === 0 ? (
                            <div className="py-12 flex flex-col items-center justify-center text-muted-foreground">
                                <Info className="h-8 w-8 text-slate-300 mb-2" />
                                <p className="text-sm">Belum ada data untuk brand ini.</p>
                            </div>
                        ) : (
                            <Chart
                                type="donut"
                                height={280}
                                series={paymentStatus.map((p) => p.value)}
                                options={{
                                    labels: paymentStatus.map((p) => p.label),
                                    colors: ['#10B981', '#F59E0B', '#EF4444'],
                                    legend: { position: 'bottom' },
                                }}
                            />
                        )}
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between pb-3 border-b">
                        <div>
                            <CardTitle className="text-base flex items-center gap-2">
                                <FileText className="h-4 w-4 text-amber-500" />
                                Invoice Pending Validasi
                            </CardTitle>
                            <CardDescription>Invoice draft atau menunggu persetujuan.</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm" className="text-indigo-600">
                            <Link href={route('invoices.index') + '?status=draft'}>
                                Lihat Semua <ArrowUpRight className="h-4 w-4 ml-1" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="pt-4">
                        {(stats.invoice_pending_list ?? []).length === 0 ? (
                            <div className="py-12 flex flex-col items-center justify-center text-muted-foreground">
                                <FileCheck className="h-8 w-8 text-slate-300 mb-2" />
                                <p className="text-sm">Tidak ada invoice pending untuk brand ini.</p>
                            </div>
                        ) : (
                            <ul className="space-y-3">
                                {(stats.invoice_pending_list ?? []).map((iv) => (
                                    <li key={iv.id} className="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50/40 hover:bg-slate-50 transition-colors p-3">
                                        <div className="min-w-0 flex-1">
                                            <div className="font-mono text-[10px] text-slate-400 font-semibold uppercase">{iv.invoice_number}</div>
                                            <div className="truncate text-sm font-semibold text-slate-800">{iv.order?.no_po}</div>
                                            <div className="truncate text-xs text-slate-500">{iv.order?.pelanggan?.nama ?? '-'}</div>
                                        </div>
                                        <div className="text-right">
                                            <div className="font-mono text-sm font-bold text-slate-900">{formatRupiah(iv.total_tagihan)}</div>
                                            <Badge variant="warning" className="mt-1 text-[10px] font-semibold">{iv.status}</Badge>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Laporan Keuangan Per Brand / Reseller */}
            <Card className="border-slate-200 shadow-sm overflow-hidden">
                <CardHeader className="bg-slate-50/50 pb-4">
                    <CardTitle className="text-base flex items-center gap-2">
                        <Building2 className="h-5 w-5 text-indigo-500" />
                        Laporan Keuangan Per Brand & Reseller
                    </CardTitle>
                    <CardDescription>Ringkasan omset, pembayaran masuk, dan sisa tagihan per brand.</CardDescription>
                </CardHeader>
                <CardContent className="p-0">
                    {reports.length === 0 ? (
                        <div className="py-12 flex flex-col items-center justify-center text-muted-foreground border-t">
                            <Info className="h-8 w-8 text-slate-300 mb-2" />
                            <p className="text-sm">Belum ada data keuangan brand.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm border-t">
                                <thead className="bg-slate-50/80 text-xs uppercase tracking-wider text-slate-500 border-b">
                                    <tr>
                                        <th className="px-5 py-3.5 text-left font-bold">Brand / Reseller</th>
                                        <th className="px-5 py-3.5 text-right font-bold">Total Omset PO</th>
                                        <th className="px-5 py-3.5 text-right font-bold">Total Terbayar</th>
                                        <th className="px-5 py-3.5 text-right font-bold">Sisa Tagihan</th>
                                        <th className="px-5 py-3.5 text-left font-bold">Metode / Jenis Kas Masuk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {reports.map((br) => (
                                        <tr key={br.id} className="border-b last:border-0 hover:bg-slate-50/60 transition-colors">
                                            <td className="px-5 py-4">
                                                <div className="flex items-center gap-2.5">
                                                    <span className="w-3 h-3 rounded-full shadow-sm" style={{ backgroundColor: br.warna || '#4F46E5' }}></span>
                                                    <div>
                                                        <span className="font-bold text-slate-800">{br.nama_brand}</span>
                                                        <span className="ml-2 px-1.5 py-0.5 rounded text-[10px] bg-slate-100 text-slate-600 font-bold font-mono uppercase border">{br.kode}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-5 py-4 text-right font-mono font-bold text-slate-900">{formatRupiah(br.total_revenue)}</td>
                                            <td className="px-5 py-4 text-right font-mono font-bold text-emerald-600">{formatRupiah(br.total_payments)}</td>
                                            <td className="px-5 py-4 text-right font-mono font-bold text-rose-600">{formatRupiah(br.outstanding)}</td>
                                            <td className="px-5 py-4">
                                                <div className="flex flex-wrap gap-1.5">
                                                    {(br.payment_type_breakdown ?? []).map((pb, idx) => (
                                                        <Badge key={idx} variant="secondary" className="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-700 border font-semibold">
                                                            {pb.nama}: {formatRupiah(pb.total)}
                                                        </Badge>
                                                    ))}
                                                    {(br.payment_type_breakdown ?? []).length === 0 && (
                                                        <span className="text-xs text-slate-400 font-medium">Belum ada dana masuk</span>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                {reports.length > 1 && (
                                    <tfoot className="bg-slate-100/80 border-t-2 border-slate-200">
                                        <tr className="font-bold text-slate-800">
                                            <td className="px-5 py-4 text-left font-black tracking-wide">TOTAL AGREGAT</td>
                                            <td className="px-5 py-4 text-right font-mono font-black text-slate-950">{formatRupiah(totalOmset)}</td>
                                            <td className="px-5 py-4 text-right font-mono font-black text-emerald-700">{formatRupiah(totalTerbayar)}</td>
                                            <td className="px-5 py-4 text-right font-mono font-black text-rose-700">{formatRupiah(totalSisa)}</td>
                                            <td className="px-5 py-4">
                                                <Badge className="bg-indigo-600 text-white font-semibold">Akumulasi Aktif</Badge>
                                            </td>
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Rekonsiliasi Rekening Koran (Bank Accounts Summary) */}
            <Card className="border-slate-200 shadow-sm">
                <CardHeader className="border-b pb-4">
                    <CardTitle className="text-base flex items-center gap-2">
                        <Landmark className="h-5 w-5 text-indigo-500" />
                        Pencocokan Rekening Koran & Arus Kas Bank
                    </CardTitle>
                    <CardDescription>
                        Daftar rekening bank aktif per brand/reseller untuk mencocokkan mutasi rekening secara rinci.
                    </CardDescription>
                </CardHeader>
                <CardContent className="pt-6">
                    {(stats.bank_accounts_summary ?? []).length === 0 ? (
                        <div className="py-12 flex flex-col items-center justify-center text-muted-foreground">
                            <Info className="h-8 w-8 text-slate-300 mb-2" />
                            <p className="text-sm">Tidak ada rekening bank aktif untuk brand ini.</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                            {(stats.bank_accounts_summary ?? []).map((bank) => {
                                const isExpanded = expandedBankId === bank.id;
                                return (
                                    <div key={bank.id} className="border border-slate-200/80 rounded-2xl bg-card hover:shadow-md transition-all overflow-hidden flex flex-col">
                                        {/* Header Rekening */}
                                        <div className="p-4 bg-slate-50/70 border-b flex items-start justify-between gap-3 flex-1">
                                            <div className="space-y-1.5">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <span className="text-[10px] px-2 py-0.5 rounded-full font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 font-mono">
                                                        {bank.brand_kode}
                                                    </span>
                                                    <span className="text-[11px] font-medium text-slate-400">{bank.brand_name}</span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Badge className={`px-2 py-0.5 text-xs font-black rounded font-mono ${getBankColor(bank.bank)}`}>
                                                        {bank.bank.toUpperCase()}
                                                    </Badge>
                                                    <h4 className="font-bold text-slate-800 text-sm tracking-tight">{bank.nomor_rekening}</h4>
                                                </div>
                                                <p className="text-xs text-slate-500">Atas Nama: <span className="font-bold text-slate-700">{bank.atas_nama}</span></p>
                                            </div>
                                            <div className="text-right space-y-0.5">
                                                <div className="text-[10px] text-slate-400 uppercase font-black tracking-wider">Total Dana Masuk</div>
                                                <div className="text-base font-black text-indigo-600 font-mono tracking-tight">{formatRupiah(bank.total_received)}</div>
                                            </div>
                                        </div>

                                        {/* Action Button to Expand */}
                                        <div className="px-4 py-2.5 border-b bg-white flex justify-between items-center">
                                            <span className="text-xs text-slate-500 font-semibold flex items-center gap-1.5">
                                                <span className="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                                Mutasi & Riwayat Transaksi
                                            </span>
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                onClick={() => setExpandedBankId(isExpanded ? null : bank.id)}
                                                className="h-8 text-xs flex items-center gap-1.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50/50 rounded-lg px-2.5"
                                            >
                                                {isExpanded ? (
                                                    <>Sembunyikan <ChevronUp className="h-4 w-4" /></>
                                                ) : (
                                                    <>Tampilkan Detail ({bank.recent_transactions?.length ?? 0}) <ChevronDown className="h-4 w-4" /></>
                                                )}
                                            </Button>
                                        </div>

                                        {/* Collapsible Mutasi Rekening */}
                                        {isExpanded && (
                                            <div className="bg-slate-50/30 max-h-[350px] overflow-y-auto divide-y divide-slate-100">
                                                {(bank.recent_transactions ?? []).length === 0 ? (
                                                    <div className="p-8 text-center text-xs text-slate-400 italic">
                                                        Belum ada mutasi terdaftar di rekening ini.
                                                    </div>
                                                ) : (
                                                    <div className="divide-y text-xs">
                                                        {bank.recent_transactions.map((tx) => (
                                                            <div key={tx.id} className="p-3.5 hover:bg-slate-50/80 transition-colors flex justify-between items-start gap-4">
                                                                <div className="space-y-1 min-w-0">
                                                                    <div className="flex items-center gap-2 flex-wrap">
                                                                        <span className="font-mono font-bold text-slate-700 bg-slate-100 border px-1.5 py-0.5 rounded text-[10px]">{tx.no_po}</span>
                                                                        <span className="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 font-semibold text-indigo-600 border border-indigo-100">
                                                                            {tx.tipe}
                                                                        </span>
                                                                        {tx.verified ? (
                                                                            <span className="text-[9px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-100 flex items-center gap-0.5">
                                                                                <span className="w-1 h-1 rounded-full bg-emerald-500"></span> Verified
                                                                            </span>
                                                                        ) : (
                                                                            <span className="text-[9px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-bold border border-amber-100 flex items-center gap-0.5">
                                                                                <span className="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                    <p className="truncate text-slate-600 font-semibold">{tx.pelanggan}</p>
                                                                    {tx.notes && <p className="text-[10px] text-slate-400 italic bg-white border border-slate-100 rounded px-1.5 py-0.5 inline-block">Notes: "{tx.notes}"</p>}
                                                                </div>
                                                                <div className="text-right flex-shrink-0 space-y-0.5">
                                                                    <div className="font-mono font-extrabold text-slate-900 text-sm">{formatRupiah(tx.amount)}</div>
                                                                    <div className="text-[10px] text-slate-400 font-bold font-mono">{formatDate(tx.payment_date)}</div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Refund Pending Review */}
            <Card className="border-slate-200 shadow-sm overflow-hidden">
                <CardHeader className="flex flex-row items-center justify-between pb-3 border-b bg-slate-50/50">
                    <div>
                        <CardTitle className="text-base flex items-center gap-2">
                            <RotateCcw className="h-4 w-4 text-rose-500" />
                            Refund Pending Review
                        </CardTitle>
                        <CardDescription>Pengajuan refund yang menunggu verifikasi.</CardDescription>
                    </div>
                    <Button asChild variant="ghost" size="sm" className="text-indigo-600">
                        <Link href={route('refunds.index') + '?status=pending_review'}>
                            Lihat Semua <ArrowUpRight className="h-4 w-4 ml-1" />
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
                                <thead className="bg-slate-50/80 text-xs uppercase tracking-wider text-slate-500 border-b">
                                    <tr>
                                        <th className="px-5 py-3 text-left font-bold">No. Refund</th>
                                        <th className="px-5 py-3 text-left font-bold">No. PO</th>
                                        <th className="px-5 py-3 text-left font-bold">Jenis</th>
                                        <th className="px-5 py-3 text-right font-bold">Nominal</th>
                                        <th className="px-5 py-3 text-left font-bold">Diajukan Oleh / Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(stats.refund_pending_list ?? []).map((r) => (
                                        <tr key={r.id} className="border-b last:border-0 hover:bg-slate-50/50 transition-colors">
                                            <td className="px-5 py-3 font-mono text-xs font-semibold text-slate-700">{r.refund_number}</td>
                                            <td className="px-5 py-3 font-mono text-xs font-bold text-slate-900">{r.order?.no_po}</td>
                                            <td className="px-5 py-3">
                                                <Badge variant="outline" className="text-[10px] font-bold border-rose-200 bg-rose-50 text-rose-700 uppercase">
                                                    {r.jenis_masalah?.replace(/_/g, ' ')}
                                                </Badge>
                                            </td>
                                            <td className="px-5 py-3 text-right font-mono font-bold text-rose-600">{formatRupiah(r.nominal_refund)}</td>
                                            <td className="px-5 py-3 text-xs">
                                                <div className="font-semibold text-slate-800">{r.creator?.name}</div>
                                                <div className="text-slate-400 font-mono">{formatDate(r.created_at)}</div>
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
    );
}
