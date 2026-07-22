import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { StatGrid, StatusBreakdown, POListWidget, POSiapDikirimWidget } from '@/Components/Widgets';
import { formatRupiah } from '@/lib/utils';
import { Target } from 'lucide-react';

export default function Superadmin({ stats }) {
    const { app } = usePage().props;
    const targetView = app?.target_view || 'pcs'; // 'both', 'revenue', 'pcs'
    const trend = stats.trend_harian ?? [];
    const trendDates = trend.map((t) => new Date(t.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const trendValues = trend.map((t) => t.count);

    const brands = stats.brand_performance ?? [];
    const progressDist = stats.progress_distribution ?? [];

    return (
        <div className="space-y-6">
            <StatGrid cards={stats.cards ?? []} />

            {/* Target Progress Section */}
            {stats.target_progress && (
                <div className="space-y-3">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <Target className="h-4 w-4 text-indigo-655" /> Target Global Bulanan ({stats.target_progress.month_name})
                            </h3>
                            <p className="text-xs text-muted-foreground">Monitor pencapaian target global bulan ini.</p>
                        </div>
                    </div>

                    <div>
                        <Card className="bg-gradient-to-br from-emerald-50/50 to-white border-l-4 border-emerald-500">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Target className="h-4 w-4 text-emerald-500" /> Target Qty Global (Pcs) Bulan Ini ({stats.target_progress.month_name})
                                </CardTitle>
                                <CardDescription>Realisasi qty produk seluruh brand dibandingkan target.</CardDescription>
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

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">Tren Order Global (14 hari)</CardTitle>
                        <CardDescription>Aggregat seluruh brand.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Chart
                            type="area"
                            height={280}
                            series={[{ name: 'PO', data: trendValues }]}
                            options={{
                                xaxis: { categories: trendDates },
                                colors: ['#6366F1'],
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
                                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                                xaxis: { categories: progressDist.map((r) => r.label), labels: { rotate: -20, style: { fontSize: '10px' } } },
                                colors: ['#F59E0B'],
                            }}
                        />
                    )}
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Brand Performance — Total PO</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {brands.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">Belum ada PO.</p>
                        ) : (
                            <Chart
                                type="bar"
                                height={Math.max(240, brands.length * 50)}
                                series={[{ name: 'PO', data: brands.map((b) => b.total) }]}
                                options={{
                                    plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '60%', distributed: true } },
                                    xaxis: { categories: brands.map((b) => b.brand) },
                                    colors: brands.map((b) => b.warna || '#3B82F6'),
                                    legend: { show: false },
                                }}
                            />
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Omset per Brand</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {brands.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">Belum ada data.</p>
                        ) : (
                            <Chart
                                type="donut"
                                height={300}
                                series={brands.map((b) => Math.round(b.revenue))}
                                options={{
                                    labels: brands.map((b) => b.brand),
                                    colors: brands.map((b) => b.warna || '#3B82F6'),
                                    legend: { position: 'bottom' },
                                    tooltip: { y: { formatter: (v) => formatRupiah(v) } },
                                }}
                            />
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Grafik Bulanan */}
            {stats.trend_bulanan && stats.trend_bulanan.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Pertumbuhan & Perbandingan Target Bulanan Global ({new Date().getFullYear()})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Chart
                            type="bar" height={260}
                            series={[
                                { name: 'Omset (Rp)', data: stats.trend_bulanan.map(tb => tb.total_omset), type: 'bar' },
                                { name: 'PCS', data: stats.trend_bulanan.map(tb => tb.total_pcs), type: 'line' },
                                { name: 'Target PCS', data: stats.trend_bulanan.map(tb => tb.target_pcs), type: 'line' },
                            ]}
                            options={{
                                chart: { type: 'bar' },
                                xaxis: { categories: stats.trend_bulanan.map(tb => tb.bulan.substring(0, 3)) },
                                colors: ['#6366F1', '#10B981', '#6EE7B7'],
                                yaxis: [
                                    { title: { text: 'Omset (Rp)' }, labels: { formatter: (v) => 'Rp ' + (v / 1000000).toFixed(1) + 'jt' } },
                                    { opposite: true, title: { text: 'PCS' } },
                                ],
                            }}
                        />
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Ranking Brand</CardTitle>
                    <CardDescription>Top brand berdasarkan omset.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="overflow-hidden rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-2 text-left">#</th>
                                    <th className="px-4 py-2 text-left">Brand</th>
                                    <th className="px-4 py-2 text-right">Total PO</th>
                                    <th className="px-4 py-2 text-right">Total Pcs</th>
                                    <th className="px-4 py-2 text-right">Omset</th>
                                </tr>
                            </thead>
                            <tbody>
                                {[...brands].sort((a, b) => b.revenue - a.revenue).map((b, i) => (
                                    <tr key={b.kode} className="border-t">
                                        <td className="px-4 py-2 font-mono text-xs">{i + 1}</td>
                                        <td className="px-4 py-2">
                                            <div className="flex items-center gap-2">
                                                <span className="h-3 w-3 rounded-full" style={{ background: b.warna }} />
                                                <span className="font-medium">{b.brand}</span>
                                                <Badge variant="outline" className="text-[10px]">{b.kode}</Badge>
                                            </div>
                                        </td>
                                        <td className="px-4 py-2 text-right font-mono">{b.total}</td>
                                        <td className="px-4 py-2 text-right font-mono">{b.total_pcs?.toLocaleString('id-ID') ?? 0}</td>
                                        <td className="px-4 py-2 text-right font-mono font-semibold">{formatRupiah(b.revenue)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {/* PO Siap Dikirim Section */}
            <div className="mb-4">
                <POSiapDikirimWidget items={stats.po_siap_dikirim ?? []} />
            </div>

            <POListWidget
                title="10 PO Terbaru (Semua Brand)"
                items={stats.po_terbaru ?? []}
                link={{ href: route('orders.index'), label: 'Semua PO' }}
            />
        </div>
    );
}
