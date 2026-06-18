import { useState } from 'react';
import { Link } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { StatGrid, StatusBreakdown, POListWidget, TopList } from '@/Components/Widgets';
import { formatDate, formatRupiah } from '@/lib/utils';
import { Target, Sparkles, RotateCcw, CheckCircle2, ArrowUpRight } from 'lucide-react';

export default function AdminBrand({ stats }) {
    const [metric, setMetric] = useState('omset');
    const trend = stats.trend_harian ?? [];
    const trendDates = trend.map((t) => new Date(t.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const trendValues = trend.map((t) => t.count);

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
                { name: 'Omset', data: trendBulananOmset, type: 'area' },
                { name: 'Target Omset', data: trendBulanan.map((tb) => tb.target_revenue), type: 'line' }
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
        if (metric === 'omset') return ['#8B5CF6', '#C4B5FD'];
        if (metric === 'pcs') return ['#10B981', '#6EE7B7'];
        return ['#3B82F6'];
    };

    return (
        <div className="space-y-6">
            <StatGrid cards={stats.cards ?? []} />

            {/* Target Progress Cards */}
            {stats.target_progress && (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Card className="bg-gradient-to-br from-indigo-50/50 to-white border-l-4 border-indigo-600">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Target className="h-4 w-4 text-indigo-600" /> Target Omset Bulan Ini ({stats.target_progress.month_name})
                            </CardTitle>
                            <CardDescription>Realisasi omset penjualan dibandingkan target.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-baseline justify-between">
                                <span className="text-2xl font-black text-slate-800 font-mono">
                                    {formatRupiah(stats.target_progress.actual_revenue)}
                                </span>
                                <span className="text-xs text-muted-foreground font-semibold">
                                    dari target {formatRupiah(stats.target_progress.target_revenue)}
                                </span>
                            </div>
                            <div className="space-y-1">
                                <div className="flex justify-between text-xs font-bold text-indigo-700">
                                    <span>Pencapaian</span>
                                    <span>
                                        {stats.target_progress.target_revenue > 0 
                                            ? `${stats.target_progress.revenue_percentage}%` 
                                            : 'Belum ada target'}
                                    </span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2 overflow-hidden shadow-inner">
                                    <div 
                                        className="bg-indigo-600 h-full rounded-full transition-all duration-500" 
                                        style={{ width: `${stats.target_progress.target_revenue > 0 ? Math.min(100, stats.target_progress.revenue_percentage) : 0}%` }} 
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

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
            )}

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
                                Tanda Jadi Pending Validasi
                            </CardTitle>
                            <CardDescription className="text-xs text-slate-500">Pembayaran DP desain menunggu verifikasi keuangan.</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm" className="text-indigo-600 hover:text-indigo-700 font-semibold text-xs">
                            <Link href={route('design-deposits.index') + '?status=pending'}>
                                Lihat Semua <ArrowUpRight className="h-3.5 w-3.5 ml-1" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="p-0">
                        {(stats.dp_pending_list ?? []).length === 0 ? (
                            <div className="py-12 flex flex-col items-center justify-center text-muted-foreground">
                                <CheckCircle2 className="h-8 w-8 text-slate-300 mb-2" />
                                <p className="text-sm">Tidak ada tanda jadi pending.</p>
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
                                Refund Pending Review
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
