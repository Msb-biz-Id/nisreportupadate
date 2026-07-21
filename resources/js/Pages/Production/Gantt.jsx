import { Head, Link, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { 
    AlertTriangle, 
    Calendar, 
    LayoutGrid, 
    Copy, 
    Check, 
    Download, 
    Search, 
    Package, 
    Clock, 
    CheckCircle2, 
    FileSpreadsheet 
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import Chart from '@/Components/Chart';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const STATUS_ORDER = ['published', 'on_progress', 'delay', 'hold', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim'];

function DaysRemaining({ days }) {
    if (days === null) return null;
    if (days < 0) return <span className="text-xs font-semibold text-red-600 dark:text-red-400">Terlambat {Math.abs(days)} hari</span>;
    if (days === 0) return <span className="text-xs font-semibold text-amber-600 dark:text-amber-400">Hari ini</span>;
    if (days <= 2) return <span className="text-xs font-semibold text-yellow-600 dark:text-yellow-400">{days} hari lagi</span>;
    return <span className="text-xs text-muted-foreground">{days} hari lagi</span>;
}

export default function Gantt({ items = [], statusColors = {}, statusLabels = {} }) {
    const [filterStatus, setFilterStatus] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [copied, setCopied] = useState(false);

    // Filter berdasarkan status & pencarian query
    const filtered = useMemo(() => {
        return items.filter((item) => {
            const matchesStatus = filterStatus === 'all' || item.status_po === filterStatus;
            const query = searchQuery.toLowerCase().trim();
            const matchesSearch = !query || 
                (item.no_po && item.no_po.toLowerCase().includes(query)) ||
                (item.nama_po && item.nama_po.toLowerCase().includes(query)) ||
                (item.pelanggan && item.pelanggan.toLowerCase().includes(query)) ||
                (item.brand_kode && item.brand_kode.toLowerCase().includes(query));
            
            return matchesStatus && matchesSearch;
        });
    }, [items, filterStatus, searchQuery]);

    // Ringkasan KPI & status
    const summary = useMemo(() => {
        const counts = {};
        let totalPcs = 0;
        let overdueCount = 0;
        let finishedCount = 0;

        for (const item of items) {
            counts[item.status_po] = (counts[item.status_po] ?? 0) + 1;
            totalPcs += Number(item.total_pcs || 0);
            if (item.days_remaining !== null && item.days_remaining < 0) {
                overdueCount++;
            }
            if (['selesai_produksi', 'siap_dikirim', 'sudah_dikirim'].includes(item.status_po)) {
                finishedCount++;
            }
        }

        return { counts, totalPcs, overdueCount, finishedCount };
    }, [items]);

    // Fungsi Salin ke Clipboard untuk Excel (TSV Tab-Separated)
    const handleCopyExcel = () => {
        if (filtered.length === 0) return;

        const headers = ['No. PO', 'Nama PO', 'Pelanggan', 'Brand', 'Status', 'Tgl Mulai', 'Deadline', 'Sisa Hari', 'Jumlah (Pcs)'];
        const rows = filtered.map((item) => {
            const daysText = item.days_remaining === null 
                ? '-' 
                : item.days_remaining < 0 
                ? `Terlambat ${Math.abs(item.days_remaining)} hr` 
                : `${item.days_remaining} hr lagi`;

            return [
                item.no_po || '',
                item.nama_po || '-',
                item.pelanggan || '-',
                item.brand_kode || '-',
                item.status_label || item.status_po,
                item.start ? new Date(item.start).toLocaleDateString('id-ID') : '-',
                (item.end_production_date || item.deadline_customer)
                    ? new Date(item.end_production_date || item.deadline_customer).toLocaleDateString('id-ID')
                    : '-',
                daysText,
                item.total_pcs || 0
            ];
        });

        const tsvContent = [headers.join('\t'), ...rows.map(r => r.join('\t'))].join('\n');
        navigator.clipboard.writeText(tsvContent);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
    };

    // Fungsi Export CSV File untuk Excel
    const handleExportCSV = () => {
        if (filtered.length === 0) return;

        const headers = ['No PO', 'Nama PO', 'Pelanggan', 'Brand', 'Status', 'Tanggal Mulai', 'Deadline', 'Sisa Hari', 'Total Pcs'];
        const rows = filtered.map((item) => [
            `"${item.no_po || ''}"`,
            `"${(item.nama_po || '').replace(/"/g, '""')}"`,
            `"${(item.pelanggan || '').replace(/"/g, '""')}"`,
            `"${item.brand_kode || ''}"`,
            `"${item.status_label || item.status_po}"`,
            `"${item.start || ''}"`,
            `"${item.end_production_date || item.deadline_customer || ''}"`,
            `"${item.days_remaining ?? ''}"`,
            `"${item.total_pcs || 0}"`
        ]);

        const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `Gantt_Produksi_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // Configuration ApexCharts
    const { series, chartOptions } = useMemo(() => {
        const grouped = {};
        for (const item of filtered) {
            if (!grouped[item.status_po]) grouped[item.status_po] = [];
            grouped[item.status_po].push(item);
        }

        const series = STATUS_ORDER.filter((s) => grouped[s]).map((status) => ({
            name: statusLabels[status] ?? status,
            color: statusColors[status],
            data: grouped[status].map((item) => ({
                x: `${item.no_po} — ${item.nama_po ?? ''}`.trim(),
                y: [
                    new Date(item.start).getTime(),
                    new Date(item.end + 'T23:59:59').getTime(),
                ],
                item,
            })),
        }));

        const chartOptions = {
            chart: { 
                type: 'rangeBar', 
                toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
                events: {
                    dataPointSelection: (event, chartContext, config) => {
                        const sIdx = config.seriesIndex;
                        const dIdx = config.dataPointIndex;
                        const dataObj = config.w.config.series[sIdx]?.data[dIdx];
                        if (dataObj?.item?.detail_url) {
                            router.visit(dataObj.item.detail_url);
                        }
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '65%',
                    rangeBarGroupRows: false,
                    borderRadius: 4,
                },
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    datetimeUTC: false,
                    format: 'dd MMM',
                    style: { fontSize: '11px', fontWeight: 500 },
                },
            },
            yaxis: {
                labels: {
                    style: { fontSize: '11px' },
                    maxWidth: 220,
                },
            },
            tooltip: {
                custom: ({ seriesIndex, dataPointIndex, w }) => {
                    const d = w.config.series[seriesIndex].data[dataPointIndex];
                    const item = d.item;
                    const start = new Date(d.y[0]).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    const end = new Date(d.y[1]).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    const days = item.days_remaining;
                    const daysText = days === null ? '-' : days < 0 ? `<span style="color:#EF4444;font-weight:bold;">⚠️ Terlambat ${Math.abs(days)} hari</span>` : `${days} hari lagi`;
                    
                    return `<div style="padding:10px 14px;font-size:12px;line-height:1.7;background:#0F172A;color:#fff;border-radius:8px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.3)">
                        <div style="font-weight:700;font-size:13px;color:#38BDF8;margin-bottom:4px;">${item.no_po}</div>
                        <div style="color:#E2E8F0;font-weight:600;">${item.nama_po ?? '-'}</div>
                        <div style="color:#94A3B8;">Pelanggan: <strong style="color:#F8FAFC">${item.pelanggan ?? '-'}</strong> | ${item.total_pcs || 0} Pcs</div>
                        <div style="margin-top:6px;padding-top:6px;border-top:1px solid rgba(255,255,255,0.1);display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                            <div>Mulai: <strong>${start}</strong></div>
                            <div>Deadline: <strong>${(item.end_production_date || item.deadline_customer) ? new Date(item.end_production_date || item.deadline_customer).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) : '-'}</strong></div>
                        </div>
                        <div style="margin-top:4px;">Sisa Waktu: <strong>${daysText}</strong></div>
                        <div style="margin-top:6px;font-size:11px;color:#38BDF8;text-align:right;">👉 Klik bar untuk buka detail</div>
                    </div>`;
                },
            },
            legend: { position: 'top', fontSize: '12px' },
            dataLabels: {
                enabled: true,
                formatter: (val, { seriesIndex, dataPointIndex, w }) => {
                    const d = w.config.series[seriesIndex].data[dataPointIndex];
                    return d.item.no_po;
                },
                style: { fontSize: '10px', colors: ['#fff'], fontWeight: 600 },
            },
            grid: { borderColor: 'rgba(148,163,184,0.15)', strokeDashArray: 4 },
        };

        return { series, chartOptions };
    }, [filtered, statusColors, statusLabels]);

    const activeStatuses = STATUS_ORDER.filter((s) => summary.counts[s]);
    const chartHeight = Math.max(340, filtered.length * 38 + 90);

    return (
        <AppLayout>
            <Head title="Gantt Chart Produksi" />
            <div className="space-y-5">
                {/* Header Utama & Tombol Aksi */}
                <div className="flex flex-wrap items-center justify-between gap-3 bg-card p-4 rounded-xl border shadow-sm">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight flex items-center gap-2">
                            <Calendar className="h-5 w-5 text-primary" />
                            Gantt Chart Produksi
                        </h1>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Visualisasi timeline & deadline order aktif real-time
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* Tombol Salin ke Excel (Clipboard TSV) */}
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={handleCopyExcel}
                            className={`h-9 gap-1.5 transition-colors ${copied ? 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300' : ''}`}
                            title="Salin tabel ke clipboard agar bisa langsung di-paste ke Excel (Ctrl + V)"
                        >
                            {copied ? <Check className="h-4 w-4 text-emerald-600" /> : <Copy className="h-4 w-4 text-emerald-600" />}
                            <span className="font-medium text-xs">{copied ? 'Berhasil Disalin!' : 'Salin ke Excel'}</span>
                        </Button>

                        {/* Tombol Export CSV File */}
                        <Button 
                            variant="outline" 
                            size="sm" 
                            onClick={handleExportCSV}
                            className="h-9 gap-1.5"
                            title="Unduh file spreadsheet .CSV"
                        >
                            <FileSpreadsheet className="h-4 w-4 text-emerald-600" />
                            <span className="font-medium text-xs">Export CSV</span>
                        </Button>

                        {/* Swtich ke Kanban */}
                        <Button variant="default" size="sm" asChild className="h-9 gap-1.5">
                            <Link href={route('produksi.kanban')}>
                                <LayoutGrid className="h-4 w-4" />
                                <span className="font-medium text-xs">Board Kanban</span>
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Ringkasan KPI Stats Cards */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div className="bg-card p-3.5 rounded-xl border shadow-sm flex items-center gap-3">
                        <div className="p-2.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                            <Calendar className="h-5 w-5" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Order Aktif</p>
                            <p className="text-lg font-bold">{items.length} <span className="text-xs font-normal text-muted-foreground">PO</span></p>
                        </div>
                    </div>

                    <div className="bg-card p-3.5 rounded-xl border shadow-sm flex items-center gap-3">
                        <div className="p-2.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                            <Package className="h-5 w-5" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Total Pcs</p>
                            <p className="text-lg font-bold">{summary.totalPcs.toLocaleString('id-ID')} <span className="text-xs font-normal text-muted-foreground">Pcs</span></p>
                        </div>
                    </div>

                    <div className="bg-card p-3.5 rounded-xl border shadow-sm flex items-center gap-3">
                        <div className="p-2.5 rounded-lg bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">
                            <AlertTriangle className="h-5 w-5" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Terlambat</p>
                            <p className="text-lg font-bold text-red-600 dark:text-red-400">{summary.overdueCount} <span className="text-xs font-normal text-muted-foreground">PO</span></p>
                        </div>
                    </div>

                    <div className="bg-card p-3.5 rounded-xl border shadow-sm flex items-center gap-3">
                        <div className="p-2.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                            <CheckCircle2 className="h-5 w-5" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">Selesai / Siap</p>
                            <p className="text-lg font-bold text-emerald-600 dark:text-emerald-400">{summary.finishedCount} <span className="text-xs font-normal text-muted-foreground">PO</span></p>
                        </div>
                    </div>
                </div>

                {/* Filter Status & Pencarian */}
                <div className="flex flex-wrap items-center justify-between gap-3 bg-card p-3 rounded-xl border shadow-sm">
                    {/* Filter Pills Badges */}
                    <div className="flex flex-wrap items-center gap-1.5">
                        <button
                            onClick={() => setFilterStatus('all')}
                            className={`rounded-full px-3 py-1 text-xs font-semibold transition-all ${
                                filterStatus === 'all' 
                                    ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 shadow-sm' 
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'
                            }`}
                        >
                            Semua ({items.length})
                        </button>
                        {activeStatuses.map((s) => (
                            <button
                                key={s}
                                onClick={() => setFilterStatus(filterStatus === s ? 'all' : s)}
                                className="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium text-white transition-opacity hover:opacity-90 shadow-sm"
                                style={{ 
                                    backgroundColor: statusColors[s], 
                                    opacity: filterStatus !== 'all' && filterStatus !== s ? 0.35 : 1 
                                }}
                            >
                                {statusLabels[s]}
                                <span className="rounded-full bg-white/30 px-1.5 py-0.2 text-[10px] font-bold">{summary.counts[s]}</span>
                            </button>
                        ))}
                    </div>

                    {/* Search Input Filter */}
                    <div className="relative w-full sm:w-64">
                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="text"
                            placeholder="Cari PO, Pelanggan..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="pl-9 h-9 text-xs"
                        />
                    </div>
                </div>

                {/* Visual Gantt Chart */}
                <Card className="shadow-sm">
                    <CardHeader className="pb-2 flex flex-row items-center justify-between">
                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                            <Calendar className="h-4 w-4 text-primary" />
                            Timeline Visual Order
                            <span className="text-xs font-normal text-muted-foreground ml-1 hidden sm:inline">
                                (Klik bar order untuk membuka detail progress)
                            </span>
                        </CardTitle>
                        <span className="text-xs font-medium text-muted-foreground">
                            Menampilkan {filtered.length} dari {items.length} Order
                        </span>
                    </CardHeader>
                    <CardContent className="pt-2">
                        {filtered.length === 0 ? (
                            <div className="flex h-48 flex-col items-center justify-center text-sm text-muted-foreground gap-2">
                                <Search className="h-8 w-8 opacity-40" />
                                Tidak ada order yang sesuai dengan filter atau kata kunci pencarian.
                            </div>
                        ) : (
                            <Chart
                                type="rangeBar"
                                series={series}
                                options={chartOptions}
                                height={chartHeight}
                            />
                        )}
                    </CardContent>
                </Card>

                {/* Tabel Data Order Interaktif */}
                <Card className="shadow-sm">
                    <CardHeader className="pb-3 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-sm font-bold">Daftar Order Produksi</CardTitle>
                            <p className="text-xs text-muted-foreground mt-0.5">Tabel ini dapat langsung disalin ke Excel dengan klik tombol &quot;Salin ke Excel&quot;</p>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm border-collapse">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-xs text-muted-foreground">
                                        <th className="px-4 py-2.5 text-left font-semibold">No. PO</th>
                                        <th className="px-4 py-2.5 text-left font-semibold">Nama PO</th>
                                        <th className="px-4 py-2.5 text-left font-semibold">Pelanggan</th>
                                        <th className="px-4 py-2.5 text-center font-semibold">Qty Pcs</th>
                                        <th className="px-4 py-2.5 text-left font-semibold">Status</th>
                                        <th className="px-4 py-2.5 text-left font-semibold">Mulai</th>
                                        <th className="px-4 py-2.5 text-left font-semibold">Deadline</th>
                                        <th className="px-4 py-2.5 text-left font-semibold">Sisa Waktu</th>
                                        <th className="px-4 py-2.5 text-right font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filtered.map((item) => {
                                        const overdue = item.days_remaining !== null && item.days_remaining < 0;
                                        return (
                                            <tr 
                                                key={item.id} 
                                                className={`border-b last:border-0 hover:bg-muted/40 transition-colors ${overdue ? 'bg-red-50/50 dark:bg-red-950/20' : ''}`}
                                            >
                                                <td className="px-4 py-2.5 font-mono text-xs font-semibold text-primary">
                                                    {item.no_po}
                                                </td>
                                                <td className="px-4 py-2.5 font-medium">{item.nama_po ?? '-'}</td>
                                                <td className="px-4 py-2.5 text-muted-foreground">{item.pelanggan ?? '-'}</td>
                                                <td className="px-4 py-2.5 text-center font-semibold text-xs">
                                                    {item.total_pcs ? `${item.total_pcs} Pcs` : '-'}
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <span
                                                        className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold text-white shadow-xs"
                                                        style={{ backgroundColor: item.color }}
                                                    >
                                                        {item.status_label}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-2.5 text-xs text-muted-foreground">
                                                    {item.start ? new Date(item.start).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) : '-'}
                                                </td>
                                                <td className="px-4 py-2.5 text-xs font-medium">
                                                    {(item.end_production_date || item.deadline_customer)
                                                        ? new Date(item.end_production_date || item.deadline_customer).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                                                        : '-'}
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    {overdue && <AlertTriangle className="h-3.5 w-3.5 inline text-red-500 mr-1 animate-pulse" />}
                                                    <DaysRemaining days={item.days_remaining} />
                                                </td>
                                                <td className="px-4 py-2.5 text-right">
                                                    <Button variant="ghost" size="sm" asChild className="h-7 text-xs text-primary hover:text-primary">
                                                        <Link href={item.detail_url}>
                                                            Detail →
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
