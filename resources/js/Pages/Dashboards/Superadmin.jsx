import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { StatGrid, StatusBreakdown, POListWidget } from '@/Components/Widgets';
import { formatRupiah } from '@/lib/utils';

export default function Superadmin({ stats }) {
    const trend = stats.trend_harian ?? [];
    const trendDates = trend.map((t) => new Date(t.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const trendValues = trend.map((t) => t.count);

    const brands = stats.brand_performance ?? [];

    return (
        <div className="space-y-6">
            <StatGrid cards={stats.cards ?? []} />

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
                        <CardTitle className="text-base">Revenue per Brand</CardTitle>
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

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Ranking Brand</CardTitle>
                    <CardDescription>Top brand berdasarkan revenue.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="overflow-hidden rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-2 text-left">#</th>
                                    <th className="px-4 py-2 text-left">Brand</th>
                                    <th className="px-4 py-2 text-right">Total PO</th>
                                    <th className="px-4 py-2 text-right">Revenue</th>
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
                                        <td className="px-4 py-2 text-right font-mono font-semibold">{formatRupiah(b.revenue)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <POListWidget
                title="10 PO Terbaru (Semua Brand)"
                items={stats.po_terbaru ?? []}
                link={{ href: route('orders.index'), label: 'Semua PO' }}
            />
        </div>
    );
}
