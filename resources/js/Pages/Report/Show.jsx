import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import * as Icons from 'lucide-react';
import { Download, FileSpreadsheet, FileText, Filter, BarChart3 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import Chart from '@/Components/Chart';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_BADGE = {
    draft: 'outline', published: 'info', on_progress: 'warning',
    selesai_produksi: 'success', siap_dikirim: 'info', sudah_dikirim: 'secondary',
    delay: 'destructive', hold: 'warning',
    pending_review: 'warning', approved: 'info', rejected: 'destructive',
    ringan: 'outline', sedang: 'warning', berat: 'destructive',
};

function FormatCell({ value, format }) {
    if (value === null || value === undefined || value === '') {
        return <span className="text-muted-foreground">-</span>;
    }
    if (format === 'currency') return <span className="font-mono">{formatRupiah(value)}</span>;
    if (format === 'number') return <span className="font-mono">{Number(value).toLocaleString('id-ID')}</span>;
    if (format === 'date') return formatDate(value);
    if (format === 'status_badge' || format === 'badge') {
        return <Badge variant={STATUS_BADGE[value] ?? 'outline'}>{String(value).replace(/_/g, ' ')}</Badge>;
    }
    if (format === 'days_indicator') {
        const days = Number(value);
        if (days < 0) return <Badge variant="destructive">{Math.abs(days)} hari telat</Badge>;
        if (days <= 2) return <Badge variant="warning">H-{days}</Badge>;
        return <Badge variant="outline">H-{days}</Badge>;
    }
    return value;
}

function FilterBar({ config, filters, onApply, customerTypes = [], sumberOrders = [], brands = [], products = [] }) {
    const [local, setLocal] = useState(filters);

    function patch(k, v) { setLocal({ ...local, [k]: v }); }
    function apply() { onApply(local); }

    return (
        <Card>
            <CardContent className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                {config.filters?.includes('date_range') && (
                    <>
                        <div>
                            <Label className="text-xs">Dari Tanggal</Label>
                            <Input type="date" value={local.from || ''} onChange={(e) => patch('from', e.target.value)} className="mt-1 h-9" />
                        </div>
                        <div>
                            <Label className="text-xs">Sampai Tanggal</Label>
                            <Input type="date" value={local.to || ''} onChange={(e) => patch('to', e.target.value)} className="mt-1 h-9" />
                        </div>
                    </>
                )}
                {config.filters?.includes('status_po') && (
                    <div>
                        <Label className="text-xs">Status PO</Label>
                        <Select value={local.status || '__all__'} onValueChange={(v) => patch('status', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua</SelectItem>
                                {['draft', 'published', 'on_progress', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'delay', 'hold'].map((s) => (
                                    <SelectItem key={s} value={s}>{s}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('refund_status') && (
                    <div>
                        <Label className="text-xs">Status Refund</Label>
                        <Select value={local.refund_status || '__all__'} onValueChange={(v) => patch('refund_status', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua</SelectItem>
                                {['pending_review', 'approved', 'published', 'rejected'].map((s) => (
                                    <SelectItem key={s} value={s}>{s}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('level_wilayah') && (
                    <div>
                        <Label className="text-xs">Tingkat Wilayah</Label>
                        <Select value={local.level_wilayah || 'kabupaten'} onValueChange={(v) => patch('level_wilayah', v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Pilih Tingkat" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="provinsi">Provinsi</SelectItem>
                                <SelectItem value="kabupaten">Kabupaten / Kota</SelectItem>
                                <SelectItem value="kecamatan">Kecamatan</SelectItem>
                                <SelectItem value="desa">Desa / Kelurahan</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('threshold') && (
                    <div>
                        <Label className="text-xs">Threshold (hari)</Label>
                        <Input type="number" min={1} value={local.threshold || 7} onChange={(e) => patch('threshold', Number(e.target.value))} className="mt-1 h-9" />
                    </div>
                )}
                {config.filters?.includes('is_auto') && (
                    <div>
                        <Label className="text-xs">Sumber Data</Label>
                        <Select value={local.is_auto === '' || local.is_auto === undefined ? '__all__' : String(local.is_auto)} onValueChange={(v) => patch('is_auto', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua</SelectItem>
                                <SelectItem value="1">Otomatis</SelectItem>
                                <SelectItem value="0">Manual</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('customer_type') && (
                    <div>
                        <Label className="text-xs">Kategori Pelanggan</Label>
                        <Select value={local.customer_type_id || '__all__'} onValueChange={(v) => patch('customer_type_id', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Kategori</SelectItem>
                                {customerTypes.map((t) => (
                                    <SelectItem key={t.id} value={String(t.id)}>{t.nama}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('sumber_order') && (
                    <div>
                        <Label className="text-xs">Sumber Order</Label>
                        <Select value={local.sumber_order_id || '__all__'} onValueChange={(v) => patch('sumber_order_id', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Sumber</SelectItem>
                                {sumberOrders.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>{s.nama}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('brand') && brands.length > 0 && (
                    <div>
                        <Label className="text-xs">Brand</Label>
                        <Select value={local.brand_id || '__all__'} onValueChange={(v) => patch('brand_id', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua Brand" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Brand</SelectItem>
                                {brands.map((b) => (
                                    <SelectItem key={b.id} value={String(b.id)}>{b.nama_brand}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('product') && products.length > 0 && (
                    <div>
                        <Label className="text-xs">Produk</Label>
                        <Select value={local.product_id || '__all__'} onValueChange={(v) => patch('product_id', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua Produk" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Produk</SelectItem>
                                {products.map((p) => (
                                    <SelectItem key={p.id} value={String(p.id)}>{p.nama}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('region') && (
                    <div>
                        <Label className="text-xs">Pencarian Wilayah</Label>
                        <Input 
                            type="text" 
                            placeholder="Cari Kota/Provinsi..." 
                            value={local.region || ''} 
                            onChange={(e) => patch('region', e.target.value)} 
                            className="mt-1 h-9" 
                        />
                    </div>
                )}
                <div className="sm:col-span-2 lg:col-span-4 flex justify-end">
                    <Button size="sm" onClick={apply}><Filter className="h-4 w-4" /> Terapkan Filter</Button>
                </div>
            </CardContent>
        </Card>
    );
}

function SummaryCards({ items }) {
    if (!items?.length) return null;
    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {items.map((s, i) => (
                <Card key={i}>
                    <CardContent className="p-3">
                        <div className="text-xs uppercase tracking-wider text-muted-foreground">{s.label}</div>
                        <div className="mt-1 text-xl font-bold font-mono">
                            {s.format === 'currency' ? formatRupiah(s.value) : (typeof s.value === 'number' ? s.value.toLocaleString('id-ID') : s.value)}
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function ReportChart({ config, rows, heatmapSeries }) {
    if (!config.chart) return null;
    const { type, x, y, label, value, title } = config.chart;

    if (type === 'heatmap') {
        if (!heatmapSeries?.length) return null;
        return (
            <Card>
                <CardHeader><CardTitle className="text-base flex items-center gap-2"><BarChart3 className="h-4 w-4 text-primary" /> {title}</CardTitle></CardHeader>
                <CardContent>
                    <Chart type="heatmap" height={340} series={heatmapSeries} options={{
                        dataLabels: { enabled: false },
                        colors: ['#3B82F6'],
                        xaxis: { labels: { style: { fontSize: '10px' } } },
                        plotOptions: {
                            heatmap: {
                                shadeIntensity: 0.5,
                                colorScale: {
                                    ranges: [
                                        { from: 0, to: 0, color: '#F1F5F9', name: 'Tidak ada' },
                                        { from: 1, to: 3, color: '#BFDBFE', name: 'Rendah' },
                                        { from: 4, to: 7, color: '#60A5FA', name: 'Sedang' },
                                        { from: 8, to: 999, color: '#1D4ED8', name: 'Tinggi' },
                                    ],
                                },
                            },
                        },
                        tooltip: {
                            y: { formatter: (v) => `${v} order` },
                        },
                    }} />
                </CardContent>
            </Card>
        );
    }

    if (!rows.length) return null;
    const top = rows.slice(0, 12);

    if (type === 'donut') {
        const series = top.map((r) => Number(r[value]));
        const labels = top.map((r) => r[label]);
        return (
            <Card>
                <CardHeader><CardTitle className="text-base flex items-center gap-2"><BarChart3 className="h-4 w-4 text-primary" /> {title}</CardTitle></CardHeader>
                <CardContent>
                    <Chart type="donut" height={300} series={series} options={{
                        labels,
                        legend: { position: 'bottom' },
                        colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#EF4444', '#84CC16'],
                    }} />
                </CardContent>
            </Card>
        );
    }

    const series = [{ name: y, data: top.map((r) => Number(r[y])) }];
    const categories = top.map((r) => r[x]);

    return (
        <Card>
            <CardHeader><CardTitle className="text-base flex items-center gap-2"><BarChart3 className="h-4 w-4 text-primary" /> {title}</CardTitle></CardHeader>
            <CardContent>
                <Chart type={type === 'line' ? 'line' : 'bar'} height={Math.max(300, top.length * 28)} series={series} options={{
                    plotOptions: type === 'bar' ? { bar: { horizontal: true, borderRadius: 6, barHeight: '65%' } } : undefined,
                    xaxis: { categories },
                    colors: ['#3B82F6'],
                }} />
            </CardContent>
        </Card>
    );
}

export default function ReportShow({ config, filters, rows, summary, heatmapSeries, groups, allReports, customerTypes = [], sumberOrders = [], brands = [], products = [] }) {
    const Icon = Icons[config.icon] ?? Icons.BarChart3;

    function applyFilters(newFilters) {
        router.get(route('reports.show', config.slug), newFilters, { preserveScroll: true });
    }

    return (
        <AppLayout title={`Laporan ${config.label}`}>
            <Head title={`Laporan ${config.label}`} />

            <div className="space-y-5">
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <Icon className="h-5 w-5 text-primary" /> {config.label}
                            </div>
                            <CardDescription className="mt-1">{config.description}</CardDescription>
                        </div>
                        <div className="flex gap-2">
                            <Button asChild variant="outline" size="sm">
                                <a href={route('reports.export.excel', { ...filters, slug: config.slug })}>
                                    <FileSpreadsheet className="h-4 w-4" /> Excel
                                </a>
                            </Button>
                            <Button asChild size="sm">
                                <a href={route('reports.export.pdf', { ...filters, slug: config.slug })}>
                                    <FileText className="h-4 w-4" /> PDF
                                </a>
                            </Button>
                        </div>
                    </CardHeader>
                </Card>

                <FilterBar config={config} filters={filters} onApply={applyFilters} customerTypes={customerTypes} sumberOrders={sumberOrders} brands={brands} products={products} />

                <SummaryCards items={summary} />

                <ReportChart config={config} rows={rows} heatmapSeries={heatmapSeries} />

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Detail ({rows.length} baris)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        {config.columns.map((c) => (
                                            <TableHead key={c.key} className={['currency', 'number'].includes(c.format) ? 'text-right' : ''}>
                                                {c.label}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={config.columns.length} className="py-8 text-center text-sm text-muted-foreground">
                                                Tidak ada data untuk filter ini.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        rows.map((row, i) => (
                                            <TableRow key={i}>
                                                {config.columns.map((c) => (
                                                    <TableCell key={c.key} className={['currency', 'number'].includes(c.format) ? 'text-right' : ''}>
                                                        <FormatCell value={row[c.key]} format={c.format} />
                                                    </TableCell>
                                                ))}
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
