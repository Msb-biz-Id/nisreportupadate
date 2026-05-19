import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Trophy, TrendingUp, Package, Users, AlertTriangle, RotateCcw, Filter } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import Chart from '@/Components/Chart';
import { formatRupiah } from '@/lib/utils';

function MetricRow({ icon: Icon, label, brands, valueKey, formatter, winnerKey }) {
    return (
        <div className="grid grid-cols-1 gap-2 rounded-lg border bg-card/60 p-3 sm:grid-cols-2 lg:grid-cols-4">
            <div className="flex items-center gap-2 font-medium">
                <Icon className="h-4 w-4 text-primary" />
                {label}
            </div>
            {brands.map((b) => {
                const value = b[valueKey];
                const isWinner = winnerKey && b[winnerKey];
                return (
                    <div key={b.brand_id} className={`flex items-center justify-between rounded px-2 py-1 ${isWinner ? 'bg-emerald-50 ring-1 ring-emerald-300' : 'bg-muted/30'}`}>
                        <div className="flex items-center gap-2">
                            <span className="h-3 w-3 rounded-full" style={{ background: b.warna || '#3B82F6' }} />
                            <span className="text-xs font-mono">{b.kode}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                            {isWinner && <Trophy className="h-3.5 w-3.5 text-amber-500" />}
                            <span className="font-mono text-sm font-semibold">
                                {formatter ? formatter(value) : (value ?? '—')}
                            </span>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

export default function ComparisonShow({ availableBrands, selectedBrandIds, filters, result }) {
    const [sel, setSel] = useState(selectedBrandIds || []);
    const [from, setFrom] = useState(filters?.from || '');
    const [to, setTo] = useState(filters?.to || '');

    function toggle(id) {
        setSel((cur) => cur.includes(id) ? cur.filter((x) => x !== id) : [...cur, id]);
    }

    function apply() {
        if (sel.length < 2) {
            alert('Pilih minimal 2 brand untuk comparison.');
            return;
        }
        router.get(route('comparison.show'), { brand_ids: sel, from, to }, { preserveScroll: true });
    }

    const brands = result?.brands ?? [];

    return (
        <AppLayout title="Comparison Report">
            <Head title="Comparison Report" />

            <div className="space-y-5">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Comparison Report</h1>
                    <p className="text-sm text-muted-foreground">
                        Bandingkan performa antar brand head-to-head pada periode yang sama.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base"><Filter className="h-4 w-4" /> Filter</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div>
                            <Label className="mb-1.5 block">Pilih Brand (min. 2)</Label>
                            <div className="flex flex-wrap gap-2">
                                {availableBrands.map((b) => {
                                    const active = sel.includes(b.id);
                                    return (
                                        <button
                                            key={b.id}
                                            type="button"
                                            onClick={() => toggle(b.id)}
                                            className={`flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm transition ${active ? 'border-primary bg-primary text-primary-foreground' : 'bg-background hover:bg-accent'}`}
                                        >
                                            <span className="h-2.5 w-2.5 rounded-full" style={{ background: b.warna_primary || '#3B82F6' }} />
                                            {b.nama_brand}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <Label>Dari Tanggal</Label>
                                <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="mt-1.5" />
                            </div>
                            <div>
                                <Label>Sampai Tanggal</Label>
                                <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="mt-1.5" />
                            </div>
                            <div className="flex items-end">
                                <Button onClick={apply} className="w-full"><Filter className="h-4 w-4" /> Terapkan</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {brands.length < 2 ? (
                    <Card>
                        <CardContent className="py-12 text-center text-muted-foreground">
                            Pilih minimal 2 brand untuk melihat comparison.
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Periode: {result.periode}</CardTitle>
                                <CardDescription>
                                    Membandingkan {brands.length} brand. <Trophy className="inline h-3.5 w-3.5 text-amber-500" /> = winner per metric.
                                </CardDescription>
                            </CardHeader>
                        </Card>

                        <div className={`grid grid-cols-1 gap-4 ${brands.length === 2 ? 'sm:grid-cols-2' : brands.length === 3 ? 'lg:grid-cols-3' : 'sm:grid-cols-2 lg:grid-cols-4'}`}>
                            {brands.map((b) => (
                                <Card key={b.brand_id} className="overflow-hidden">
                                    <div className="h-1.5" style={{ background: b.warna || '#3B82F6' }} />
                                    <CardHeader>
                                        <CardTitle className="text-base">{b.brand_name}</CardTitle>
                                        <Badge variant="outline" className="font-mono text-[10px]">{b.kode}</Badge>
                                    </CardHeader>
                                    <CardContent className="space-y-2 text-sm">
                                        <div className="flex justify-between border-b pb-1">
                                            <span className="text-muted-foreground">Revenue</span>
                                            <span className="font-mono font-semibold flex items-center gap-1">
                                                {b.is_winner_revenue && <Trophy className="h-3 w-3 text-amber-500" />}
                                                {formatRupiah(b.revenue)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-1">
                                            <span className="text-muted-foreground">Total PO</span>
                                            <span className="font-mono flex items-center gap-1">
                                                {b.is_winner_po && <Trophy className="h-3 w-3 text-amber-500" />}
                                                {b.po_count}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-1">
                                            <span className="text-muted-foreground">Avg PO Value</span>
                                            <span className="font-mono text-xs">{formatRupiah(b.avg_po_value)}</span>
                                        </div>
                                        <div className="flex justify-between border-b pb-1">
                                            <span className="text-muted-foreground">Pelanggan Unik</span>
                                            <span className="font-mono">{b.customer_count}</span>
                                        </div>
                                        <div className="flex justify-between border-b pb-1">
                                            <span className="text-muted-foreground">Total Qty Produksi</span>
                                            <span className="font-mono">{b.total_qty}</span>
                                        </div>
                                        <div className="flex justify-between border-b pb-1">
                                            <span className="text-muted-foreground">Rijek Rate</span>
                                            <span className={`font-mono flex items-center gap-1 ${b.rijek_rate > 5 ? 'text-destructive' : ''}`}>
                                                {b.is_winner_rijek && <Trophy className="h-3 w-3 text-amber-500" />}
                                                {b.rijek_rate !== null ? `${b.rijek_rate}%` : '—'}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-1">
                                            <span className="text-muted-foreground">Refund</span>
                                            <span className="font-mono text-xs">{formatRupiah(b.refund_amount)}</span>
                                        </div>
                                        <div className="pt-1">
                                            <div className="text-xs text-muted-foreground">Top Produk</div>
                                            <div className="text-sm font-medium">
                                                {b.top_product ? `${b.top_product.nama} (${b.top_product.qty}x)` : '—'}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Revenue Comparison</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Chart
                                        type="bar"
                                        height={Math.max(240, brands.length * 50)}
                                        series={[{ name: 'Revenue', data: brands.map((b) => Math.round(b.revenue)) }]}
                                        options={{
                                            plotOptions: { bar: { horizontal: true, borderRadius: 6, distributed: true, barHeight: '65%' } },
                                            xaxis: { categories: brands.map((b) => b.brand_name), labels: { formatter: (v) => formatRupiah(v) } },
                                            colors: brands.map((b) => b.warna || '#3B82F6'),
                                            legend: { show: false },
                                            tooltip: { y: { formatter: (v) => formatRupiah(v) } },
                                        }}
                                    />
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">PO Count Comparison</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Chart
                                        type="bar"
                                        height={Math.max(240, brands.length * 50)}
                                        series={[{ name: 'Total PO', data: brands.map((b) => b.po_count) }]}
                                        options={{
                                            plotOptions: { bar: { horizontal: true, borderRadius: 6, distributed: true, barHeight: '65%' } },
                                            xaxis: { categories: brands.map((b) => b.brand_name) },
                                            colors: brands.map((b) => b.warna || '#10B981'),
                                            legend: { show: false },
                                        }}
                                    />
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Ringkasan Gabungan</CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                <div className="rounded-lg bg-muted/40 p-3">
                                    <div className="text-xs uppercase tracking-wider text-muted-foreground">Total Revenue</div>
                                    <div className="font-mono text-lg font-bold">{formatRupiah(result.summary.total_revenue)}</div>
                                </div>
                                <div className="rounded-lg bg-muted/40 p-3">
                                    <div className="text-xs uppercase tracking-wider text-muted-foreground">Total PO</div>
                                    <div className="font-mono text-lg font-bold">{result.summary.total_po}</div>
                                </div>
                                <div className="rounded-lg bg-muted/40 p-3">
                                    <div className="text-xs uppercase tracking-wider text-muted-foreground">Total Pelanggan</div>
                                    <div className="font-mono text-lg font-bold">{result.summary.total_customers}</div>
                                </div>
                                <div className="rounded-lg bg-muted/40 p-3">
                                    <div className="text-xs uppercase tracking-wider text-muted-foreground">Avg Rijek Rate</div>
                                    <div className="font-mono text-lg font-bold">{result.summary.avg_rijek_rate}%</div>
                                </div>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
