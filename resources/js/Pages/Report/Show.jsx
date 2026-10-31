import { Head, Link, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import * as Icons from 'lucide-react';
import { FileSpreadsheet, FileText, Filter, BarChart3 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableFooter } from '@/Components/ui/table';
import Chart from '@/Components/Chart';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/utils';
import { MultiSelect } from '@/Components/ui/multi-select';
import DateRangeFilter from '@/Components/DateRangeFilter';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';

const STATUS_BADGE = {
    draft: 'outline', published: 'info', on_progress: 'warning',
    selesai_produksi: 'success', siap_dikirim: 'info', sudah_dikirim: 'secondary',
    delay: 'destructive', hold: 'warning',
    pending_review: 'warning', approved: 'info', rejected: 'destructive',
    ringan: 'outline', sedang: 'warning', berat: 'destructive',
    Safe: 'success', Warning: 'warning', 'High Risk': 'destructive',
    verified: 'success', pending: 'warning',
};

const STATUS_LABELS = {
    draft: 'Draft',
    validated: 'Validasi',
    published: 'Baru Masuk',
    on_progress: 'Sedang Produksi',
    selesai_produksi: 'Selesai Produksi',
    siap_dikirim: 'Siap Dikirim',
    sudah_dikirim: 'Sudah Dikirim',
    delay: 'Tertunda (Delay)',
    hold: 'Ditahan (Hold)',
    cancel: 'Dibatalkan',
    paid: 'Lunas',
    overdue: 'Jatuh Tempo',
    sent: 'Dikirim',
};

function FormatCell({ value, format }) {
    if (value === null || value === undefined || value === '') {
        return <span className="text-muted-foreground">-</span>;
    }
    if (format === 'currency') return <span className="font-mono">{formatRupiah(value)}</span>;
    if (format === 'number') return <span className="font-mono">{Number(value).toLocaleString('id-ID')}</span>;
    if (format === 'date') return formatDate(value);
    if (format === 'datetime') return formatDateTime(value);
    if (typeof value === 'string' && value.startsWith('Telat')) {
        return <Badge variant="destructive">{value}</Badge>;
    }
    if (typeof value === 'string' && value === 'Tepat Waktu') {
        return <Badge variant="success">✅ Tepat Waktu</Badge>;
    }
    if (format === 'status_badge' || format === 'badge') {
        const displayValue = STATUS_LABELS[value] ?? String(value).replace(/_/g, ' ');
        return <Badge variant={STATUS_BADGE[value] ?? 'outline'}>{displayValue}</Badge>;
    }
    if (format === 'churn_status') {
        const labels = {
            'Safe': 'Aman',
            'Warning': 'Waspada',
            'High Risk': 'Risiko Tinggi'
        };
        const displayValue = labels[value] ?? value;
        return <Badge variant={STATUS_BADGE[value] ?? 'outline'}>{displayValue}</Badge>;
    }
    if (format === 'days_indicator') {
        const days = Number(value);
        if (days < 0) return <Badge variant="destructive">{Math.abs(days)} hari telat</Badge>;
        if (days <= 2) return <Badge variant="warning">H-{days}</Badge>;
        return <Badge variant="outline">H-{days}</Badge>;
    }
    if (format === 'whatsapp_action') {
        const { nama, nomor_hp, type, recency, aoi, order_tahun_lalu, tanggal_order_lalu } = value || {};
        const phone = String(nomor_hp || '').replace(/[^0-9]/g, '');
        const formattedPhone = phone.startsWith('0') ? '62' + phone.slice(1) : phone;
        
        let messageText = '';
        if (type === 'seasonal') {
            messageText = `Halo Kak ${nama}, semoga sehat selalu! Kami melihat tahun lalu sekitar tanggal ${formatDate(tanggal_order_lalu)} Kakak pernah memesan dengan No PO ${order_tahun_lalu}. Karena sudah memasuki siklus tahunan project tersebut, apakah tahun ini ada rencana pembuatan jersey atau jaket baru yang bisa kami bantu kembali? 😊`;
        } else {
            const recencyDays = Math.round(Number(recency || 0));
            const aoiDays = Math.round(Number(aoi || 0));
            messageText = `Halo Kak ${nama}, semoga sehat selalu! Kami dari brand pilihan Kakak melihat Kak sudah ${recencyDays} hari tidak melakukan pemesanan. Biasanya Kakak memesan setiap ${aoiDays} hari sekali. Apakah saat ini ada kebutuhan jersey atau project jaket baru yang bisa kami bantu? Dapatkan penawaran promo menarik khusus repeat order hari ini! 😊`;
        }
        
        const message = encodeURIComponent(messageText);
        const url = `https://wa.me/${formattedPhone}?text=${message}`;
        return (
            <Button asChild size="sm" variant="outline" className="h-7 px-2 text-xs border-green-500 hover:bg-green-50 hover:text-green-600">
                <a href={url} target="_blank" rel="noopener noreferrer" className="flex items-center gap-1">
                    <Icons.MessageSquare className="h-3 w-3 text-green-500" /> Hubungi WA
                </a>
            </Button>
        );
    }
    return value;
}

function FilterBar({ config, filters, onApply, customerTypes = [], sumberOrders = [], brands = [], products = [], bankAccounts = [] }) {
    const [local, setLocal] = useState(filters);
    const [loading, setLoading] = useState(false);

    function patch(k, v) { setLocal({ ...local, [k]: v }); }
    function apply() { 
        setLoading(true);
        onApply(local); 
    }

    const handleBrandChange = (v) => {
        const nextBrandId = v === '__all__' ? '' : v;
        const validBankIds = (local.bank_ids || []).filter((id) => {
            const acc = bankAccounts.find(b => String(b.id) === String(id));
            return !nextBrandId || (acc && String(acc.brand_id) === String(nextBrandId));
        });
        setLocal({
            ...local,
            brand_id: nextBrandId,
            bank_ids: validBankIds
        });
    };

    const filteredBankAccounts = bankAccounts.filter((b) => {
        if (!local.brand_id || local.brand_id === '__all__') return true;
        return String(b.brand_id) === String(local.brand_id);
    });

    return (
        <Card>
            <CardContent className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                {config.filters?.includes('date_range') && (
                    <div className="sm:col-span-2">
                        <DateRangeFilter
                            from={local.from || ''}
                            to={local.to || ''}
                            onChange={(from, to) => setLocal({ ...local, from, to })}
                        />
                    </div>
                )}
                {config.filters?.includes('date_basis') && (
                    <div>
                        <Label className="text-xs">Basis Tanggal</Label>
                        <Select value={local.date_basis || 'packing_date'} onValueChange={(v) => patch('date_basis', v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Pilih Basis Tanggal" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="packing_date">📦 Tgl Selesai Packing (Output Produksi)</SelectItem>
                                <SelectItem value="tanggal_masuk">📅 Tgl Masuk PO (Siklus Order)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('jenis_po') && (
                    <div>
                        <Label className="text-xs">Jenis PO</Label>
                        <Select value={local.jenis_po || '__all__'} onValueChange={(v) => patch('jenis_po', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua Jenis PO" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Jenis PO</SelectItem>
                                <SelectItem value="normal">PO Normal</SelectItem>
                                <SelectItem value="special_order">PO Support</SelectItem>
                                <SelectItem value="reseller_price">PO Harga Reseller</SelectItem>
                                <SelectItem value="repeat_order">PO Repeat Order</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                )}
                {config.filters?.includes('lateness_status') && (
                    <div>
                        <Label className="text-xs">Status Keterlambatan</Label>
                        <Select value={local.lateness_status || '__all__'} onValueChange={(v) => patch('lateness_status', v === '__all__' ? '' : v)}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Semua Status" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">Semua Status</SelectItem>
                                <SelectItem value="terlambat">⚠️ Terlambat Saja</SelectItem>
                                <SelectItem value="tepat_waktu">✅ Tepat Waktu / Dalam Deadline</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
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
                        <Label className="text-xs">Batas Waktu (Deadline)</Label>
                        <Select value={String(local.threshold || 7)} onValueChange={(v) => patch('threshold', Number(v))}>
                            <SelectTrigger className="mt-1 h-9"><SelectValue placeholder="Pilih Batas Waktu" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="3">3 Hari Ke Depan</SelectItem>
                                <SelectItem value="7">7 Hari Ke Depan (1 Minggu)</SelectItem>
                                <SelectItem value="14">14 Hari Ke Depan (2 Minggu)</SelectItem>
                                <SelectItem value="30">30 Hari Ke Depan (1 Bulan)</SelectItem>
                                <SelectItem value="60">60 Hari Ke Depan (2 Bulan)</SelectItem>
                                <SelectItem value="90">90 Hari Ke Depan (3 Bulan)</SelectItem>
                            </SelectContent>
                        </Select>
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
                        <Select value={local.brand_id || '__all__'} onValueChange={handleBrandChange}>
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
                {config.filters?.includes('bank_accounts') && (
                    <div className="sm:col-span-2">
                        <Label className="text-xs">Rekening Bank</Label>
                        <MultiSelect
                            value={local.bank_ids || []}
                            onChange={(val) => patch('bank_ids', val)}
                            options={filteredBankAccounts.map((b) => ({
                                value: String(b.id),
                                label: b.label || `${b.bank} — ${b.nomor_rekening} (${b.atas_nama})`
                            }))}
                            placeholder="Semua Rekening Bank"
                            className="mt-1"
                        />
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
                    <Button size="sm" onClick={apply} disabled={loading}>
                        {loading ? <Icons.Loader2 className="h-4 w-4 animate-spin" /> : <Filter className="h-4 w-4" />} Terapkan Filter
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function SummaryCards({ items, onCardClick, currentLateness }) {
    if (!items?.length) return null;
    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {items.map((s, i) => {
                const isLateCard = s.label?.toLowerCase().includes('telat');
                const isTotalCard = s.label === 'Total PO';
                const isClickable = isLateCard || isTotalCard;

                const isSelected = (isLateCard && currentLateness === 'terlambat') || (isTotalCard && (!currentLateness || currentLateness === 'all'));

                return (
                    <Card 
                        key={i}
                        onClick={() => {
                            if (isLateCard) onCardClick?.('terlambat');
                            else if (isTotalCard) onCardClick?.('all');
                        }}
                        className={`transition-all ${isClickable ? 'cursor-pointer hover:border-primary hover:shadow-md' : ''} ${isSelected ? 'ring-2 ring-primary border-primary bg-primary/5' : ''}`}
                    >
                        <CardContent className="p-3">
                            <div className="flex items-center justify-between">
                                <div className="text-xs uppercase tracking-wider text-muted-foreground">{s.label}</div>
                                {isLateCard && <span className="text-[10px] bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300 font-semibold px-1.5 py-0.5 rounded">Filter</span>}
                            </div>
                            <div className="mt-1 text-xl font-bold font-mono">
                                {s.format === 'currency' ? formatRupiah(s.value) : (typeof s.value === 'number' ? s.value.toLocaleString('id-ID') : s.value)}
                            </div>
                            {isClickable && (
                                <p className="text-[10px] text-muted-foreground mt-1 font-medium">
                                    👉 Klik untuk filter
                                </p>
                            )}
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}

function ReportChart({ config, rows, heatmapSeries, packingChartData }) {
    if (!config.chart) return null;
    const { type, x, y, label, value, title } = config.chart;

    // Grafik Khusus Kinerja Produksi: Output Harian Packing (Wave / Area Chart)
    if (config.slug === 'kinerja-produksi' && packingChartData?.series?.length) {
        return (
            <Card className="border-slate-200 dark:border-slate-800 shadow-sm">
                <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-2">
                    <div>
                        <CardTitle className="text-base flex items-center gap-2 font-semibold">
                            <BarChart3 className="h-4 w-4 text-cyan-600 dark:text-cyan-400" />
                            {title || 'Grafik Output Harian Selesai Packing (PCS & PO)'}
                        </CardTitle>
                        <CardDescription>
                            Fluktuasi harian penyelesaian PO dan kuantitas PCS oleh divisi produksi hingga tahap packing.
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap items-center gap-2 text-xs mt-2 sm:mt-0 font-medium">
                        <span className="bg-cyan-50 text-cyan-800 dark:bg-cyan-950/60 dark:text-cyan-300 px-2.5 py-1 rounded-full border border-cyan-200 dark:border-cyan-800 font-mono font-semibold">
                            Total: {packingChartData.total_pcs?.toLocaleString('id-ID')} pcs ({packingChartData.total_po} PO)
                        </span>
                        <span className="bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 px-2.5 py-1 rounded-full border border-emerald-200 dark:border-emerald-800 font-mono font-semibold">
                            {packingChartData.total_po_tepat_waktu ?? 0} Tepat Waktu
                        </span>
                        <span className="bg-red-50 text-red-800 dark:bg-red-950/60 dark:text-red-300 px-2.5 py-1 rounded-full border border-red-200 dark:border-red-800 font-mono font-semibold">
                            {packingChartData.total_po_telat ?? 0} Telat
                        </span>
                        <span className="bg-blue-50 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 px-2.5 py-1 rounded-full border border-blue-200 dark:border-blue-800 font-mono font-semibold">
                            Rata²: {packingChartData.avg_pcs_day?.toLocaleString('id-ID')} pcs/hari
                        </span>
                    </div>
                </CardHeader>
                <CardContent>
                    <Chart
                        type="area"
                        height={320}
                        series={packingChartData.series}
                        options={{
                            stroke: { curve: 'smooth', width: [3, 2, 2.5] },
                            colors: ['#06B6D4', '#10B981', '#EF4444'],
                            fill: {
                                type: ['gradient', 'solid', 'solid'],
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.45,
                                    opacityTo: 0.05,
                                    stops: [0, 95, 100]
                                }
                            },
                            xaxis: {
                                categories: packingChartData.categories,
                                labels: { style: { fontSize: '11px' } }
                            },
                            yaxis: [
                                {
                                    title: { text: 'Total PCS Dipacking', style: { fontSize: '11px', fontWeight: 600 } },
                                    labels: { formatter: (v) => Math.round(v).toLocaleString('id-ID') }
                                },
                                {
                                    opposite: true,
                                    title: { text: 'Jumlah PO', style: { fontSize: '11px', fontWeight: 600 } },
                                    labels: { formatter: (v) => Math.round(v) }
                                },
                                {
                                    opposite: true,
                                    show: false,
                                }
                            ],
                            legend: { position: 'top', horizontalAlign: 'right' },
                            tooltip: {
                                shared: true,
                                intersect: false,
                                y: {
                                    formatter: (v, { seriesIndex }) => {
                                        if (seriesIndex === 0) return `${v?.toLocaleString('id-ID') ?? 0} pcs`;
                                        if (seriesIndex === 1) return `${v ?? 0} PO (Tepat Waktu)`;
                                        return `${v ?? 0} PO (Telat)`;
                                    }
                                }
                            }
                        }}
                    />
                </CardContent>
            </Card>
        );
    }

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

const NUMERIC_FORMATS = ['currency', 'number'];

function SummaryTableCard({ summaryTable }) {
    if (!summaryTable || !summaryTable.rows?.length) return null;

    return (
        <Card className="border-slate-200 dark:border-slate-800 shadow-sm">
            <CardHeader className="py-3 px-4 border-b bg-slate-50/70 dark:bg-slate-900/60">
                <CardTitle className="text-sm font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wide">
                    {summaryTable.title || 'Ringkasan Data'}
                </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-100/60 dark:bg-slate-800/60">
                                {summaryTable.columns.map((c) => (
                                    <TableHead 
                                        key={c.key} 
                                        className={`font-bold text-xs uppercase tracking-wider text-slate-600 dark:text-slate-300 ${NUMERIC_FORMATS.includes(c.format) ? 'text-right' : ''}`}
                                    >
                                        {c.label}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {summaryTable.rows.map((row, i) => (
                                <TableRow key={i} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    {summaryTable.columns.map((c) => (
                                        <TableCell 
                                            key={c.key} 
                                            className={`text-sm py-2.5 ${NUMERIC_FORMATS.includes(c.format) ? 'text-right font-mono' : 'font-semibold text-slate-700 dark:text-slate-200'}`}
                                        >
                                            <FormatCell value={row[c.key]} format={c.format} />
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))}
                        </TableBody>
                        {summaryTable.total && (
                            <TableFooter className="bg-slate-100 dark:bg-slate-900 font-bold border-t-2 border-slate-300 dark:border-slate-700">
                                <TableRow>
                                    {summaryTable.columns.map((c) => (
                                        <TableCell 
                                            key={c.key} 
                                            className={`py-3 text-slate-900 dark:text-white font-extrabold ${NUMERIC_FORMATS.includes(c.format) ? 'text-right font-mono' : ''}`}
                                        >
                                            <FormatCell value={summaryTable.total[c.key]} format={c.format} />
                                        </TableCell>
                                    ))}
                                </TableRow>
                            </TableFooter>
                        )}
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}

export default function ReportShow({ config, filters, rows, summary, summaryTable = null, brandSummaryTable = null, heatmapSeries, packingChartData = null, groups, allReports, customerTypes = [], sumberOrders = [], brands = [], products = [], bankAccounts = [] }) {
    const Icon = Icons[config.icon] ?? Icons.BarChart3;

    function applyFilters(newFilters) {
        router.get(route('reports.show', config.slug), newFilters, { preserveScroll: true });
    }

    const groupHeaderLabel = config.slug === 'monitoring-deadline' ? 'Deadline Produksi' : 'Deadline';

    const detailColumns = useMemo(() => config.columns.map((c, idx) => {
        const isNumeric = NUMERIC_FORMATS.includes(c.format);
        return {
            id: c.key,
            accessorFn: (row) => (row.is_group_header || row.is_group_total ? '' : row[c.key]),
            meta: { title: c.label, cellClassName: isNumeric ? 'text-right' : '' },
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={c.label} className={isNumeric ? 'justify-end' : ''} />
            ),
            cell: ({ row }) => {
                const r = row.original;
                if (r.is_group_header) {
                    if (idx !== 0) return null;
                    return (
                        <span className="font-bold text-blue-700 dark:text-blue-300">
                            {groupHeaderLabel}: {formatDate(r.deadline_produksi || r.deadline)}
                        </span>
                    );
                }
                if (r.is_group_total) {
                    if (c.key === 'pelanggan') return <span className="font-extrabold text-slate-700 dark:text-slate-200">TOTAL PCS</span>;
                    if (c.key === 'pcs') return <FormatCell value={r[c.key]} format={c.format} />;
                    return null;
                }
                return <FormatCell value={r[c.key]} format={c.format} />;
            },
        };
    }), [config.columns, groupHeaderLabel]);

    const dataRows = useMemo(() => rows.filter((r) => !r.is_group_header && !r.is_group_total), [rows]);

    const detailRowClassName = (row) => {
        if (row.is_group_header) {
            return '!bg-blue-50 dark:!bg-blue-950/40 hover:!bg-blue-50 dark:hover:!bg-blue-950/40';
        }
        if (row.is_group_total) {
            return '!bg-slate-100 dark:!bg-slate-800 font-bold hover:!bg-slate-100 dark:hover:!bg-slate-800';
        }
        return '';
    };

    const KEYS_TO_SUM = ['pcs', 'total_qty', 'total_order', 'total_tagihan', 'total_value', 'jumlah', 'nominal', 'nominal_refund', 'amount', 'total_pcs'];

    const detailFooter = dataRows.length > 0 ? (
        <TableRow className="bg-slate-100 dark:bg-slate-900 font-bold">
            {config.columns.map((c, idx) => {
                if (idx === 0) {
                    return (
                        <TableCell key={c.key} className="font-extrabold text-slate-800 dark:text-slate-100 py-3">
                            TOTAL KESELURUHAN ({dataRows.length} Data)
                        </TableCell>
                    );
                }
                if (KEYS_TO_SUM.includes(c.key)) {
                    const totalSum = dataRows.reduce((acc, r) => acc + (Number(r[c.key]) || 0), 0);
                    return (
                        <TableCell key={c.key} className="text-right font-extrabold text-slate-800 dark:text-slate-100 py-3">
                            <FormatCell value={totalSum} format={c.format} />
                        </TableCell>
                    );
                }
                return <TableCell key={c.key} className="py-3" />;
            })}
        </TableRow>
    ) : null;

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

                <FilterBar config={config} filters={filters} onApply={applyFilters} customerTypes={customerTypes} sumberOrders={sumberOrders} brands={brands} products={products} bankAccounts={bankAccounts} />

                <SummaryCards 
                    items={summary} 
                    currentLateness={filters?.lateness_status}
                    onCardClick={(status) => applyFilters({ ...filters, lateness_status: status === 'all' ? '' : status })} 
                />

                <ReportChart config={config} rows={rows} heatmapSeries={heatmapSeries} packingChartData={packingChartData} />

                <SummaryTableCard summaryTable={summaryTable} />

                <SummaryTableCard summaryTable={brandSummaryTable} />

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Detail ({dataRows.length} baris)</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <DataTable
                            columns={detailColumns}
                            data={rows}
                            searchPlaceholder="Cari data laporan..."
                            exportFileName={`laporan-${config.slug}`}
                            enableExportExcel={config.slug !== 'jenis-po'}
                            emptyMessage="Tidak ada data untuk filter ini."
                            rowClassName={detailRowClassName}
                            footerSlot={detailFooter}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
