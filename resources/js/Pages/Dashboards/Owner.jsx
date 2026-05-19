import { router } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { StatGrid, StatusBreakdown } from '@/Components/Widgets';
import { formatRupiah } from '@/lib/utils';

export default function Owner({ stats }) {
    const trend = stats.trend_harian ?? [];
    const trendDates = trend.map((t) => new Date(t.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const trendValues = trend.map((t) => t.count);
    const brands = stats.brand_performance ?? [];
    const ownedBrands = stats.owned_brands ?? [];

    function changeBrand(v) {
        const params = v === 'all' ? {} : { brand_id: v };
        router.get(route('dashboard'), params, { preserveScroll: true, preserveState: true });
    }

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle className="text-base">Filter Tampilan</CardTitle>
                        <CardDescription>Pilih brand untuk drill-down, atau lihat agregat semua brand milik Anda.</CardDescription>
                    </div>
                    <Select onValueChange={changeBrand} defaultValue="all">
                        <SelectTrigger className="sm:w-64"><SelectValue placeholder="Semua Brand" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Brand (Aggregated)</SelectItem>
                            {ownedBrands.map((b) => (
                                <SelectItem key={b.id} value={b.id}>{b.nama_brand}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </CardHeader>
            </Card>

            <StatGrid cards={stats.cards ?? []} />

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">Tren Order 14 Hari</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Chart
                            type="area" height={280}
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

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Brand Comparison</CardTitle>
                    <CardDescription>Perbandingan performa antar brand yang Anda miliki.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="overflow-hidden rounded-lg border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-2 text-left">Brand</th>
                                    <th className="px-4 py-2 text-right">Total PO</th>
                                    <th className="px-4 py-2 text-right">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                {brands.map((b) => (
                                    <tr key={b.kode} className="border-t">
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
                                {brands.length === 0 && (
                                    <tr><td colSpan={3} className="px-4 py-6 text-center text-sm text-muted-foreground">Belum ada data PO.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
