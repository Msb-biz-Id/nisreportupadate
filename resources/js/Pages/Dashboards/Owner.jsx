import { router } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Badge } from '@/Components/ui/badge';
import { StatGrid, StatusBreakdown, TopList, POListWidget } from '@/Components/Widgets';
import { formatRupiah } from '@/lib/utils';

export default function Owner({ stats }) {
    const trend       = stats.trend_harian ?? [];
    const trendDates  = trend.map((t) => new Date(t.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }));
    const trendValues = trend.map((t) => t.count);

    const brands        = stats.brand_performance ?? [];
    const ownedBrands   = stats.owned_brands ?? [];

    // Marketing analytics
    const produk             = stats.produk_terpopuler ?? [];
    const kategori           = stats.kategori_distribusi ?? [];
    const sumber             = stats.sumber_distribusi ?? [];
    const kategoriPelanggan  = stats.kategori_pelanggan_distribusi ?? [];
    const wilayah            = stats.wilayah_top ?? [];
    const topPelanggan       = stats.top_pelanggan ?? [];

    // Trend bulanan
    const trendBulanan       = stats.trend_bulanan ?? [];
    const trendMonths        = trendBulanan.map((tb) => tb.bulan.substring(0, 3));
    const trendBulananOmset  = trendBulanan.map((tb) => tb.total_omset);
    const trendBulananPcs    = trendBulanan.map((tb) => tb.total_pcs);

    function changeBrand(v) {
        const params = v === 'all' ? {} : { brand_id: v };
        router.get(route('dashboard'), params, { preserveScroll: true, preserveState: true });
    }

    return (
        <div className="space-y-6">
            {/* Filter */}
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

            {/* KPI Cards */}
            <StatGrid cards={stats.cards ?? []} />

            {/* Tren + Status */}
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

            {/* Grafik Bulanan */}
            {trendBulanan.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Pertumbuhan Bulanan ({new Date().getFullYear()})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Chart
                            type="bar" height={260}
                            series={[
                                { name: 'Omset (Rp)', data: trendBulananOmset, type: 'bar' },
                                { name: 'PCS', data: trendBulananPcs, type: 'line' },
                            ]}
                            options={{
                                chart: { type: 'bar' },
                                xaxis: { categories: trendMonths },
                                colors: ['#3B82F6', '#F59E0B'],
                                yaxis: [
                                    { title: { text: 'Omset (Rp)' }, labels: { formatter: (v) => 'Rp ' + (v / 1000000).toFixed(1) + 'jt' } },
                                    { opposite: true, title: { text: 'PCS' } },
                                ],
                            }}
                        />
                    </CardContent>
                </Card>
            )}

            {/* Brand Comparison */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Brand Comparison</CardTitle>
                    <CardDescription>Perbandingan performa antar brand.</CardDescription>
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

            {/* ===== MARKETING ANALYTICS ===== */}
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

            {/* PO Alerts */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <POListWidget title="⏰ Deadline Mendekat" items={stats.deadline_mendekat ?? []} columns={['no_po', 'pelanggan', 'meta']} />
                <POListWidget title="⚠️ PO Terlambat" items={stats.po_terlambat ?? []} columns={['no_po', 'pelanggan', 'meta']} />
            </div>
        </div>
    );
}
