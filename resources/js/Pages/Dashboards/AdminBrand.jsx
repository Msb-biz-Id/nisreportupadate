import { useState } from 'react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { StatGrid, StatusBreakdown, POListWidget, TopList } from '@/Components/Widgets';
import { formatRupiah } from '@/lib/utils';

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
                        type="area"
                        height={300}
                        series={[{
                            name: metric === 'omset' ? 'Omset' : metric === 'po' ? 'Jumlah PO' : 'Total Pcs',
                            data: metric === 'omset' ? trendBulananOmset : metric === 'po' ? trendBulananPO : trendBulananPcs
                        }]}
                        options={{
                            xaxis: { categories: trendBulananMonths },
                            yaxis: {
                                labels: {
                                    formatter: (v) => metric === 'omset' ? formatRupiah(v) : v
                                }
                            },
                            colors: [metric === 'omset' ? '#8B5CF6' : metric === 'po' ? '#3B82F6' : '#10B981'],
                            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0 } },
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
        </div>
    );
}
