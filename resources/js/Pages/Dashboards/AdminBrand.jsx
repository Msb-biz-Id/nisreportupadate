import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { StatGrid, StatusBreakdown, POListWidget, TopList } from '@/Components/Widgets';

export default function AdminBrand({ stats }) {
    const trend = stats.trend_harian ?? [];
    const trendDates = trend.map((t) => new Date(t.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const trendValues = trend.map((t) => t.count);

    const produk = stats.produk_terpopuler ?? [];

    const kategori = stats.kategori_distribusi ?? [];
    const sumber = stats.sumber_distribusi ?? [];

    return (
        <div className="space-y-6">
            <StatGrid cards={stats.cards ?? []} />

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
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Kategori Favorit</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {kategori.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">Belum ada data.</p>
                            ) : (
                                <Chart
                                    type="donut"
                                    height={240}
                                    series={kategori.map((k) => k.count)}
                                    options={{
                                        labels: kategori.map((k) => k.label),
                                        colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#EF4444'],
                                        legend: { position: 'bottom' },
                                    }}
                                />
                            )}
                        </CardContent>
                    </Card>

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
        </div>
    );
}
