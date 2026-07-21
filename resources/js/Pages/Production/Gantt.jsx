import { Head, Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { AlertTriangle, Calendar, LayoutGrid } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import Chart from '@/Components/Chart';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const STATUS_ORDER = ['published', 'on_progress', 'delay', 'hold', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim'];

function DaysRemaining({ days }) {
    if (days === null) return null;
    if (days < 0) return <span className="text-xs font-semibold text-red-600">Terlambat {Math.abs(days)} hari</span>;
    if (days === 0) return <span className="text-xs font-semibold text-orange-500">Hari ini</span>;
    if (days <= 2) return <span className="text-xs font-semibold text-yellow-600">{days} hari lagi</span>;
    return <span className="text-xs text-muted-foreground">{days} hari lagi</span>;
}

export default function Gantt({ items, statusColors, statusLabels }) {
    const [filterStatus, setFilterStatus] = useState('all');

    const filtered = useMemo(() => {
        if (filterStatus === 'all') return items;
        return items.filter((i) => i.status_po === filterStatus);
    }, [items, filterStatus]);

    // Kelompokkan per status untuk legend ringkasan
    const summary = useMemo(() => {
        const counts = {};
        for (const item of items) {
            counts[item.status_po] = (counts[item.status_po] ?? 0) + 1;
        }
        return counts;
    }, [items]);

    // ApexCharts rangeBar series: 1 series per status
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
            chart: { type: 'rangeBar', toolbar: { show: false } },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '60%',
                    rangeBarGroupRows: false,
                },
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    datetimeUTC: false,
                    format: 'dd MMM',
                    style: { fontSize: '11px' },
                },
            },
            yaxis: {
                labels: {
                    style: { fontSize: '11px' },
                    maxWidth: 200,
                },
            },
            tooltip: {
                custom: ({ seriesIndex, dataPointIndex, w }) => {
                    const d = w.config.series[seriesIndex].data[dataPointIndex];
                    const item = d.item;
                    const start = new Date(d.y[0]).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    const end = new Date(d.y[1]).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    const days = item.days_remaining;
                    const daysText = days === null ? '-' : days < 0 ? `<span style="color:#EF4444">Terlambat ${Math.abs(days)} hari</span>` : `${days} hari lagi`;
                    return `<div style="padding:8px 12px;font-size:12px;line-height:1.8">
                        <strong>${item.no_po}</strong><br/>
                        ${item.nama_po ?? ''}<br/>
                        Pelanggan: ${item.pelanggan ?? '-'}<br/>
                        Mulai: ${start}<br/>
                        Deadline: ${(item.end_production_date || item.deadline_customer) ? new Date(item.end_production_date || item.deadline_customer).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}<br/>
                        Sisa: ${daysText}
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
                style: { fontSize: '10px', colors: ['#fff'] },
            },
            grid: { borderColor: 'rgba(148,163,184,0.15)', strokeDashArray: 4 },
        };

        return { series, chartOptions };
    }, [filtered, statusColors, statusLabels]);

    const activeStatuses = STATUS_ORDER.filter((s) => summary[s]);
    const chartHeight = Math.max(300, filtered.length * 36 + 80);

    return (
        <AppLayout>
            <Head title="Gantt Chart Produksi" />
            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Gantt Chart Produksi</h1>
                        <p className="text-sm text-muted-foreground">{items.length} order aktif</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Select value={filterStatus} onValueChange={setFilterStatus}>
                            <SelectTrigger className="w-44 h-8 text-sm">
                                <SelectValue placeholder="Filter status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                {activeStatuses.map((s) => (
                                    <SelectItem key={s} value={s}>
                                        {statusLabels[s]} ({summary[s]})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('produksi.kanban')}>
                                <LayoutGrid className="h-4 w-4 mr-1" />
                                Kanban
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Summary badges */}
                <div className="flex flex-wrap gap-2">
                    {activeStatuses.map((s) => (
                        <button
                            key={s}
                            onClick={() => setFilterStatus(filterStatus === s ? 'all' : s)}
                            className="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium text-white transition-opacity hover:opacity-80"
                            style={{ backgroundColor: statusColors[s], opacity: filterStatus !== 'all' && filterStatus !== s ? 0.4 : 1 }}
                        >
                            {statusLabels[s]}
                            <span className="rounded-full bg-white/30 px-1.5">{summary[s]}</span>
                        </button>
                    ))}
                </div>

                {/* Chart */}
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium flex items-center gap-2">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            Timeline Order
                            <span className="text-xs font-normal text-muted-foreground ml-1">
                                Bar dimulai dari tanggal mulai produksi (atau tanggal masuk) → deadline
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {filtered.length === 0 ? (
                            <div className="flex h-48 items-center justify-center text-sm text-muted-foreground">
                                Tidak ada order untuk filter ini.
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

                {/* Tabel ringkas */}
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium">Daftar Order</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-xs text-muted-foreground">
                                        <th className="px-4 py-2 text-left font-medium">No. PO</th>
                                        <th className="px-4 py-2 text-left font-medium">Nama PO</th>
                                        <th className="px-4 py-2 text-left font-medium">Pelanggan</th>
                                        <th className="px-4 py-2 text-left font-medium">Status</th>
                                        <th className="px-4 py-2 text-left font-medium">Mulai</th>
                                        <th className="px-4 py-2 text-left font-medium">Deadline</th>
                                        <th className="px-4 py-2 text-left font-medium">Sisa</th>
                                        <th className="px-4 py-2 text-left font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filtered.map((item) => {
                                        const overdue = item.days_remaining !== null && item.days_remaining < 0;
                                        return (
                                            <tr key={item.id} className={`border-b last:border-0 hover:bg-muted/40 transition-colors ${overdue ? 'bg-red-50/50' : ''}`}>
                                                <td className="px-4 py-2 font-mono text-xs">{item.no_po}</td>
                                                <td className="px-4 py-2">{item.nama_po ?? '-'}</td>
                                                <td className="px-4 py-2 text-muted-foreground">{item.pelanggan ?? '-'}</td>
                                                <td className="px-4 py-2">
                                                    <span
                                                        className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                                                        style={{ backgroundColor: item.color }}
                                                    >
                                                        {item.status_label}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-2 text-xs text-muted-foreground">
                                                    {item.start ? new Date(item.start).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) : '-'}
                                                </td>
                                                <td className="px-4 py-2 text-xs">
                                                    {(item.end_production_date || item.deadline_customer)
                                                        ? new Date(item.end_production_date || item.deadline_customer).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                                                        : '-'}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {overdue && <AlertTriangle className="h-3 w-3 inline text-red-500 mr-1" />}
                                                    <DaysRemaining days={item.days_remaining} />
                                                </td>
                                                <td className="px-4 py-2">
                                                    <Link
                                                        href={item.detail_url}
                                                        className="text-xs text-primary hover:underline"
                                                    >
                                                        Detail
                                                    </Link>
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
