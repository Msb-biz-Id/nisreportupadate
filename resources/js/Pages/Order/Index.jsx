import { Head, Link, router } from '@inertiajs/react';
import { useState, useCallback, useRef, useEffect, useMemo } from 'react';
import { Plus, Pencil, Trash2, Eye, Package, RotateCw, X, Calendar, Download, CheckCircle2, Archive, Clock } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_LABEL = {
    draft:            { label: 'Draft',                    variant: 'outline',     color: '#94A3B8' },
    baru_masuk:       { label: 'Baru Masuk',                variant: 'info',        color: '#3B82F6' },
    review:           { label: 'Review Produksi',          variant: 'secondary',   color: '#8B5CF6' },
    published:        { label: 'Baru Masuk',                variant: 'info',        color: '#3B82F6' },
    on_progress:      { label: 'Sedang Produksi',          variant: 'warning',     color: '#F59E0B' },
    selesai_produksi: { label: 'Selesai Produksi',         variant: 'success',     color: '#22C55E' },
    siap_dikirim:     { label: 'Siap Dikirim',             variant: 'info',        color: '#06B6D4' },
    sudah_dikirim:    { label: 'Sudah Dikirim',            variant: 'secondary',   color: '#8B5CF6' },
    delay:            { label: 'Delay',                    variant: 'destructive', color: '#EF4444' },
    hold:             { label: 'Hold',                     variant: 'warning',     color: '#F97316' },
    selesai:          { label: 'Selesai',                  variant: 'success',     color: '#10B981' },
};

function getOrderStatus(o) {
    if (o.status_po === 'published') {
        return o.end_production_date ? STATUS_LABEL.review : STATUS_LABEL.baru_masuk;
    }
    return STATUS_LABEL[o.status_po] ?? { label: o.status_po, variant: 'outline' };
}

const NONE = '__none__';

export default function OrderIndex({ orders, filters, statuses, statusCounts, archiveCounts, brands, can }) {
    const [search, setSearch]     = useState(filters?.q ?? '');
    const [status, setStatus]     = useState(filters?.status ?? 'all');
    const [brandId, setBrandId]   = useState(filters?.brand_id ?? '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo]     = useState(filters?.date_to ?? '');
    const [confirmDelete, setConfirmDelete] = useState(null);
    const [confirmComplete, setConfirmComplete] = useState(null);
    const [showDatePanel, setShowDatePanel] = useState(!!(filters?.date_from || filters?.date_to));
    const isArchiveTab = (filters?.tab ?? 'active') === 'archive';

    function applyFilters(overrides = {}) {
        const activeTab = overrides.hasOwnProperty('tab') ? overrides.tab : (filters?.tab ?? 'active');
        router.get(route('orders.index'), {
            q:         overrides.hasOwnProperty('q') ? overrides.q : search,
            status:    (overrides.hasOwnProperty('status') ? overrides.status : status) === 'all' ? '' : (overrides.hasOwnProperty('status') ? overrides.status : status),
            brand_id:  (overrides.hasOwnProperty('brand_id') ? overrides.brand_id : brandId) === NONE ? '' : (overrides.hasOwnProperty('brand_id') ? overrides.brand_id : brandId),
            date_from: overrides.hasOwnProperty('date_from') ? overrides.date_from : dateFrom,
            date_to:   overrides.hasOwnProperty('date_to') ? overrides.date_to : dateTo,
            tab:       activeTab,
            per_page:  overrides.hasOwnProperty('per_page') ? overrides.per_page : (filters?.per_page ?? 25),
            page:      overrides.hasOwnProperty('page') ? overrides.page : undefined,
        }, { preserveScroll: true, preserveState: true });
    }

    const orderColumns = useMemo(() => {
        const cols = [
            {
                id: 'no_po',
                accessorFn: (o) => o.no_po || '',
                meta: { title: 'No. PO' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="No. PO" />,
                cell: ({ row }) => {
                    const o = row.original;
                    return <div className="font-mono text-xs font-bold text-slate-800 truncate max-w-[155px]" title={o.no_po}>{o.no_po}</div>;
                },
            },
            {
                id: 'nama_po',
                accessorFn: (o) => o.nama_po || '',
                meta: { title: 'Nama PO' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Nama PO" />,
                cell: ({ row }) => {
                    const o = row.original;
                    return (
                        <>
                            <div className="font-semibold text-slate-800 truncate max-w-[150px]" title={o.nama_po}>{o.nama_po}</div>
                            {o.is_repeat_order && (
                                <Badge variant="outline" className="mt-0.5 text-[9px] px-1 py-0">
                                    <RotateCw className="mr-0.5 h-2.5 w-2.5" />Repeat
                                </Badge>
                            )}
                        </>
                    );
                },
            },
        ];

        if (can?.filter_by_brand) {
            cols.push({
                id: 'brand',
                accessorFn: (o) => o.brand?.kode || '',
                meta: { title: 'Brand', cellClassName: 'text-xs text-muted-foreground font-medium' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Brand" />,
                cell: ({ row }) => <span className="text-xs text-muted-foreground font-medium">{row.original.brand?.kode ?? '-'}</span>,
            });
        }

        cols.push(
            {
                id: 'pelanggan',
                accessorFn: (o) => o.pelanggan?.nama || '',
                meta: { title: 'Pelanggan' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Pelanggan" />,
                cell: ({ row }) => (
                    <div className="text-slate-700 truncate max-w-[140px]" title={row.original.pelanggan?.nama ?? '-'}>
                        {row.original.pelanggan?.nama ?? '-'}
                    </div>
                ),
            },
            {
                id: 'tanggal_masuk',
                accessorFn: (o) => o.tanggal_masuk || '',
                meta: { title: 'Tgl Masuk', cellClassName: 'text-xs whitespace-nowrap text-slate-600' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Tgl Masuk" />,
                cell: ({ row }) => <span className="text-xs whitespace-nowrap text-slate-600">{formatDate(row.original.tanggal_masuk)}</span>,
            },
            {
                id: 'deadline_customer',
                accessorFn: (o) => o.deadline_customer || '',
                meta: { title: 'Deadline', cellClassName: 'text-xs whitespace-nowrap text-slate-600' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Deadline" />,
                cell: ({ row }) => <span className="text-xs whitespace-nowrap text-slate-600">{formatDate(row.original.deadline_customer)}</span>,
            },
            {
                id: 'total_tagihan',
                accessorFn: (o) => Number(o.total_tagihan) || 0,
                meta: { title: 'Total', cellClassName: 'text-right font-mono text-xs font-bold text-slate-800' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Total" className="justify-end" />,
                cell: ({ row }) => <span className="font-mono text-xs font-bold text-slate-800">{formatRupiah(row.original.total_tagihan)}</span>,
            },
            {
                id: 'sisa_tagihan',
                accessorFn: (o) => (o.is_special_order || o.is_lunas ? 0 : (o.sisa_tagihan ?? 0)),
                meta: { title: 'Sisa/Tunggakan', cellClassName: 'text-right font-mono text-xs' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Sisa/Tunggakan" className="justify-end" />,
                cell: ({ row }) => {
                    const o = row.original;
                    if (o.is_special_order) {
                        return <span className="text-[10px] font-semibold text-purple-600 block text-right">Support (Rp 0)</span>;
                    }
                    if (o.is_lunas || (o.sisa_tagihan ?? 0) <= 0) {
                        return (
                            <span className="text-[10px] font-semibold text-emerald-600 flex items-center justify-end gap-0.5">
                                <CheckCircle2 className="h-3 w-3 inline text-emerald-500" /> Lunas
                            </span>
                        );
                    }
                    return (
                        <span className="text-[10px] font-bold text-rose-600 block text-right whitespace-nowrap" title={`Terbayar: ${formatRupiah(o.total_paid ?? 0)}`}>
                            Sisa: {formatRupiah(o.sisa_tagihan)}
                        </span>
                    );
                },
            },
            {
                id: 'paket_order',
                accessorFn: (o) => o.paket_order?.nama || '',
                meta: { title: 'Paket' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Paket" />,
                cell: ({ row }) => {
                    const p = row.original.paket_order;
                    if (!p) return <span className="text-xs text-muted-foreground">-</span>;
                    return (
                        <span
                            className="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold text-white whitespace-nowrap"
                            style={{ background: p.warna || '#6B7280' }}
                        >
                            {p.prioritas >= 2 ? '🚨' : p.prioritas >= 1 ? '⚡' : ''}
                            {p.nama}
                        </span>
                    );
                },
            },
            {
                id: 'status_po',
                accessorFn: (o) => getOrderStatus(o)?.label ?? o.status_po,
                meta: { title: 'Status' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
                cell: ({ row }) => {
                    const st = getOrderStatus(row.original);
                    return <Badge variant={st.variant} className="text-[10px] px-1.5 py-0.5">{st.label}</Badge>;
                },
            },
            {
                id: 'pcs',
                accessorFn: (o) => o.core_items_sum_quantity ?? 0,
                meta: { title: 'Pcs', cellClassName: 'text-center' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Pcs" className="justify-center" />,
                cell: ({ row }) => (
                    <Badge variant="outline" className="text-xs font-semibold">{row.original.core_items_sum_quantity ?? 0}</Badge>
                ),
            },
        );

        cols.push({
            id: 'actions',
            header: () => <div className="text-right">Aksi</div>,
            cell: ({ row }) => {
                const o = row.original;
                return (
                    <div className="flex justify-end gap-1">
                        {isArchiveTab && o.status_po === 'sudah_dikirim' && can?.update && (
                            <Button
                                size="sm"
                                variant="ghost"
                                title="Selesaikan Pesanan"
                                className="h-7 px-2 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 font-semibold text-[10px] flex items-center gap-1"
                                onClick={() => setConfirmComplete(o)}
                            >
                                <CheckCircle2 className="h-3 w-3" />
                                Selesai
                            </Button>
                        )}
                        <Button asChild size="icon" variant="ghost" className="h-7 w-7" title="Preview">
                            <Link href={route('orders.show', o.id)}>
                                <Eye className="h-3.5 w-3.5" />
                            </Link>
                        </Button>
                        {can?.update && o.status_po === 'draft' && (
                            <Button asChild size="icon" variant="ghost" className="h-7 w-7" title="Edit">
                                <Link href={route('orders.edit', o.id)}>
                                    <Pencil className="h-3.5 w-3.5" />
                                </Link>
                            </Button>
                        )}
                        {can?.delete && o.status_po === 'draft' && (
                            <Button size="icon" variant="ghost" title="Hapus" className="h-7 w-7 text-destructive hover:text-destructive" onClick={() => setConfirmDelete(o)}>
                                <Trash2 className="h-3.5 w-3.5" />
                            </Button>
                        )}
                    </div>
                );
            },
        });

        return cols;
    }, [can?.filter_by_brand, can?.update, can?.delete, isArchiveTab]);

    const calculateDateRange = (preset) => {
        const today = new Date();
        const formatDateStr = (date) => {
            const offset = date.getTimezoneOffset();
            const localDate = new Date(date.getTime() - (offset * 60 * 1000));
            return localDate.toISOString().split('T')[0];
        };

        switch (preset) {
            case 'today':
                return { start: formatDateStr(today), end: formatDateStr(today) };
            case 'yesterday': {
                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                return { start: formatDateStr(yesterday), end: formatDateStr(yesterday) };
            }
            case 'last_7': {
                const last7 = new Date(today);
                last7.setDate(today.getDate() - 6);
                return { start: formatDateStr(last7), end: formatDateStr(today) };
            }
            case 'last_30': {
                const last30 = new Date(today);
                last30.setDate(today.getDate() - 29);
                return { start: formatDateStr(last30), end: formatDateStr(today) };
            }
            case 'this_month': {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                return { start: formatDateStr(firstDay), end: formatDateStr(lastDay) };
            }
            case 'last_month': {
                const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
                return { start: formatDateStr(firstDay), end: formatDateStr(lastDay) };
            }
            case 'this_year': {
                const firstDay = new Date(today.getFullYear(), 0, 1);
                const lastDay = new Date(today.getFullYear(), 11, 31);
                return { start: formatDateStr(firstDay), end: formatDateStr(lastDay) };
            }
            default:
                return { start: '', end: '' };
        }
    };

    const getActivePreset = () => {
        if (!dateFrom && !dateTo) return 'all';

        const today = new Date();
        const formatDateStr = (d) => {
            const pad = (n) => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        };

        const todayStr = formatDateStr(today);
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        const yesterdayStr = formatDateStr(yesterday);

        if (dateFrom === todayStr && dateTo === todayStr) return 'today';
        if (dateFrom === yesterdayStr && dateTo === yesterdayStr) return 'yesterday';

        const last7 = new Date(today);
        last7.setDate(today.getDate() - 6);
        if (dateFrom === formatDateStr(last7) && dateTo === todayStr) return 'last_7';

        const last30 = new Date(today);
        last30.setDate(today.getDate() - 29);
        if (dateFrom === formatDateStr(last30) && dateTo === todayStr) return 'last_30';

        const thisMonthStart = formatDateStr(new Date(today.getFullYear(), today.getMonth(), 1));
        const thisMonthEnd = formatDateStr(new Date(today.getFullYear(), today.getMonth() + 1, 0));
        if (dateFrom === thisMonthStart && (dateTo === thisMonthEnd || dateTo === todayStr)) return 'this_month';

        const lastMonthStart = formatDateStr(new Date(today.getFullYear(), today.getMonth() - 1, 1));
        const lastMonthEnd = formatDateStr(new Date(today.getFullYear(), today.getMonth(), 0));
        if (dateFrom === lastMonthStart && dateTo === lastMonthEnd) return 'last_month';

        const thisYearStart = formatDateStr(new Date(today.getFullYear(), 0, 1));
        const thisYearEnd = formatDateStr(new Date(today.getFullYear(), 11, 31));
        if (dateFrom === thisYearStart && (dateTo === thisYearEnd || dateTo === todayStr)) return 'this_year';

        // Cek apakah persis mencakup 1 bulan
        if (dateFrom && dateTo && dateFrom.length === 10 && dateTo.length === 10) {
            const [y1, m1, d1] = dateFrom.split('-').map(Number);
            const [y2, m2, d2] = dateTo.split('-').map(Number);
            if (y1 === y2 && m1 === m2 && d1 === 1) {
                const endOfMonth = new Date(y1, m1, 0).getDate();
                if (d2 === endOfMonth) return 'specific_month';
            }
        }

        return 'custom';
    };

    const handlePresetClick = (preset) => {
        const range = calculateDateRange(preset);
        setDateFrom(range.start);
        setDateTo(range.end);
        applyFilters({ date_from: range.start, date_to: range.end });
    };

    const handleSpecificMonthChange = (monthVal) => {
        if (!monthVal) return;
        const [year, month] = monthVal.split('-').map(Number);
        const pad = (n) => String(n).padStart(2, '0');
        const start = `${year}-${pad(month)}-01`;
        const lastDay = new Date(year, month, 0).getDate();
        const end = `${year}-${pad(month)}-${pad(lastDay)}`;
        setDateFrom(start);
        setDateTo(end);
        applyFilters({ date_from: start, date_to: end });
    };

    const getDateFilterLabel = () => {
        if (!dateFrom && !dateTo) return "Filter Tanggal";
        const active = getActivePreset();
        if (active === 'today') return "Hari Ini";
        if (active === 'yesterday') return "Kemarin";
        if (active === 'last_7') return "7 Hari Terakhir";
        if (active === 'last_30') return "30 Hari Terakhir";
        if (active === 'this_month') return "Bulan Ini";
        if (active === 'last_month') return "Bulan Lalu";
        if (active === 'this_year') return "Tahun Ini";
        if (active === 'specific_month') {
            const [y, m] = dateFrom.split('-').map(Number);
            const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return `${monthNames[m]} ${y}`;
        }
        
        return `${dateFrom ? formatDate(dateFrom) : ''} - ${dateTo ? formatDate(dateTo) : ''}`;
    };

    function resetFilters() {
        setSearch(''); setStatus('all'); setBrandId('');
        setDateFrom(''); setDateTo(''); setShowDatePanel(false);
        router.get(route('orders.index'), {
            tab: filters?.tab ?? 'active',
            per_page: filters?.per_page ?? 25
        }, { preserveScroll: true, preserveState: true });
    }

    function doDelete() {
        if (!confirmDelete) return;
        router.delete(route('orders.destroy', confirmDelete.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmDelete(null),
        });
    }

    function doComplete() {
        if (!confirmComplete) return;
        router.post(route('orders.complete', confirmComplete.id), {}, {
            preserveScroll: true,
            onSuccess: () => setConfirmComplete(null),
        });
    }

    const hasActiveFilter = search || (status && status !== 'all') || brandId || dateFrom || dateTo;
    const totalPo = Object.values(statusCounts ?? {}).reduce((s, v) => Number(s) + Number(v), 0);
    const summaryItems = statuses.filter((s) => Number(statusCounts?.[s] ?? 0) > 0).map((s) => ({ key: s, ...STATUS_LABEL[s], count: Number(statusCounts[s]) }));

    return (
        <AppLayout title="Order Management">
            <Head title="Order" />

            <div className="space-y-4">
                {/* Summary Cards — Active Tab Only */}
                {!isArchiveTab && (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                        <button
                            onClick={() => { setStatus('all'); applyFilters({ status: 'all' }); }}
                            className={`rounded-xl border p-3 text-left transition hover:shadow-md ${status === 'all' ? 'border-slate-800 bg-slate-800 text-white' : 'bg-white hover:border-slate-400'}`}
                        >
                            <p className={`text-[10px] font-bold uppercase tracking-wider ${status === 'all' ? 'text-slate-300' : 'text-slate-400'}`}>Total PO</p>
                            <p className="mt-1 text-2xl font-black tabular-nums">{totalPo}</p>
                        </button>
                        {summaryItems.map((item) => (
                            <button
                                key={item.key}
                                onClick={() => { setStatus(item.key); applyFilters({ status: item.key }); }}
                                className="rounded-xl border p-3 text-left transition hover:shadow-md"
                                style={status === item.key
                                    ? { backgroundColor: item.color, borderColor: item.color, color: '#fff' }
                                    : { borderColor: item.color + '50', backgroundColor: item.color + '10' }
                                }
                            >
                                <p className="text-[10px] font-bold uppercase tracking-wider" style={{ color: status === item.key ? '#fff' : item.color }}>
                                    {item.label}
                                </p>
                                <p className="mt-1 text-2xl font-black tabular-nums" style={{ color: status === item.key ? '#fff' : item.color }}>
                                    {item.count}
                                </p>
                            </button>
                        ))}
                    </div>
                )}

                {/* Archive Summary Cards */}
                {isArchiveTab && archiveCounts && (
                    <div className="grid grid-cols-3 gap-3">
                        {/* Total Arsip */}
                        <button
                            onClick={() => { setStatus('all'); applyFilters({ status: 'all' }); }}
                            className={`rounded-xl border p-4 text-left transition hover:shadow-md ${
                                !status || status === 'all'
                                    ? 'border-slate-700 bg-slate-800 text-white'
                                    : 'bg-white border-slate-200 hover:border-slate-400'
                            }`}
                        >
                            <div className={`flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider mb-1 ${
                                !status || status === 'all' ? 'text-slate-300' : 'text-slate-500'
                            }`}>
                                <Archive className="h-3.5 w-3.5" />
                                Total Arsip
                            </div>
                            <p className="text-2xl font-black tabular-nums">{archiveCounts.total}</p>
                        </button>

                        {/* Menunggu Diselesaikan Admin Brand */}
                        <button
                            onClick={() => { setStatus('sudah_dikirim'); applyFilters({ status: 'sudah_dikirim' }); }}
                            className={`rounded-xl border p-4 text-left transition hover:shadow-md relative overflow-hidden ${
                                status === 'sudah_dikirim'
                                    ? 'border-violet-600 bg-violet-600 text-white shadow-lg shadow-violet-200'
                                    : 'bg-violet-50 border-violet-200 hover:border-violet-400 hover:shadow-violet-100'
                            }`}
                        >
                            {archiveCounts.sudah_dikirim > 0 && status !== 'sudah_dikirim' && (
                                <span className="absolute top-2 right-2 flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-[9px] font-black text-white animate-pulse">
                                    !
                                </span>
                            )}
                            <div className={`flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider mb-1 ${
                                status === 'sudah_dikirim' ? 'text-violet-100' : 'text-violet-500'
                            }`}>
                                <Clock className="h-3.5 w-3.5" />
                                Menunggu Diselesaikan
                            </div>
                            <p className={`text-2xl font-black tabular-nums ${
                                status === 'sudah_dikirim' ? 'text-white' : 'text-violet-700'
                            }`}>{archiveCounts.sudah_dikirim}</p>
                            {status !== 'sudah_dikirim' && archiveCounts.sudah_dikirim > 0 && (
                                <p className="text-[10px] text-violet-500 mt-0.5 font-medium">Perlu tindakan Admin Brand</p>
                            )}
                        </button>

                        {/* Sudah Selesai */}
                        <button
                            onClick={() => { setStatus('selesai'); applyFilters({ status: 'selesai' }); }}
                            className={`rounded-xl border p-4 text-left transition hover:shadow-md ${
                                status === 'selesai'
                                    ? 'border-emerald-600 bg-emerald-600 text-white shadow-lg shadow-emerald-200'
                                    : 'bg-emerald-50 border-emerald-200 hover:border-emerald-400'
                            }`}
                        >
                            <div className={`flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider mb-1 ${
                                status === 'selesai' ? 'text-emerald-100' : 'text-emerald-600'
                            }`}>
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                Sudah Selesai
                            </div>
                            <p className={`text-2xl font-black tabular-nums ${
                                status === 'selesai' ? 'text-white' : 'text-emerald-700'
                            }`}>{archiveCounts.selesai}</p>
                        </button>
                    </div>
                )}

                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <Package className="h-5 w-5 text-primary" /> Daftar Purchase Order
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Kelola PO: draft → terbitkan → produksi → kirim.
                            </p>
                        </div>
                        <div className="flex gap-2 flex-wrap">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    const params = new URLSearchParams({
                                        q: search,
                                        status: status === 'all' ? '' : status,
                                        brand_id: brandId,
                                        date_from: dateFrom,
                                        date_to: dateTo,
                                        tab: filters?.tab ?? 'active'
                                    }).toString();
                                    window.open(route('orders.export-comprehensive') + '?' + params, '_blank');
                                }}
                                className="text-indigo-600 border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-300 flex items-center gap-1.5"
                                title="Download Master Export (Semua Record: Progress, Rijek, Pembayaran)"
                            >
                                <Download className="h-4 w-4" />
                                Master Export
                            </Button>
                            {can?.create && (
                                <Button asChild>
                                    <Link href={route('orders.create')}>
                                        <Plus className="h-4 w-4" /> Buat PO
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="pt-0">
                        {/* Tab Switcher */}
                        <div className="flex border-b border-slate-200 mb-6">
                            <button
                                onClick={() => { setStatus('all'); applyFilters({ tab: 'active', status: 'all' }); }}
                                className={`py-3 px-6 font-semibold text-sm border-b-2 transition-all ${
                                    !isArchiveTab
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                }`}
                            >
                                PO Aktif
                            </button>
                            <button
                                onClick={() => { setStatus('all'); applyFilters({ tab: 'archive', status: 'all' }); }}
                                className={`py-3 px-6 font-semibold text-sm border-b-2 transition-all relative ${
                                    isArchiveTab
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                }`}
                            >
                                Arsip PO
                                {archiveCounts?.sudah_dikirim > 0 && (
                                    <span className="ml-2 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-orange-500 px-1.5 text-[10px] font-bold text-white">
                                        {archiveCounts.sudah_dikirim}
                                    </span>
                                )}
                            </button>
                        </div>

                        {/* Panel Filter Tanggal (collapsible) */}
                        {showDatePanel && (
                            <div className="mb-4 p-4 bg-slate-50/50 rounded-xl border border-slate-100 animate-in fade-in slide-in-from-top-2 duration-200">
                                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div>
                                        <span className="text-xs font-semibold text-slate-500 block mb-2">Pilih Cepat Rentang Tanggal PO</span>
                                        <div className="flex flex-wrap gap-1.5">
                                            {[
                                                { label: 'Bulan Ini', preset: 'this_month' },
                                                { label: 'Bulan Lalu', preset: 'last_month' },
                                                { label: '7 Hari Terakhir', preset: 'last_7' },
                                                { label: '30 Hari Terakhir', preset: 'last_30' },
                                                { label: 'Tahun Ini', preset: 'this_year' },
                                                { label: 'Hari Ini', preset: 'today' },
                                                { label: 'Kemarin', preset: 'yesterday' },
                                                { label: 'Semua', preset: 'all' },
                                            ].map((opt) => (
                                                <button
                                                    key={opt.preset}
                                                    type="button"
                                                    onClick={() => handlePresetClick(opt.preset)}
                                                    className={`text-xs px-3 py-1.5 rounded-full border transition font-medium ${
                                                        getActivePreset() === opt.preset
                                                            ? 'bg-primary text-white border-primary shadow-sm'
                                                            : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200'
                                                    }`}
                                                >
                                                    {opt.label}
                                                </button>
                                            ))}
                                        </div>

                                        <div className="mt-3 flex items-center gap-2">
                                            <span className="text-xs text-slate-500 font-medium">Atau Pilih Bulan Tertentu:</span>
                                            <input
                                                type="month"
                                                onChange={(e) => handleSpecificMonthChange(e.target.value)}
                                                className="text-xs border rounded-md px-2.5 py-1 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm font-medium"
                                            />
                                        </div>
                                    </div>

                                    <div className="border-t md:border-t-0 md:border-l border-dashed border-slate-200 pt-3 md:pt-0 md:pl-6">
                                        <span className="text-xs font-semibold text-slate-500 block mb-2">Pilih Tanggal Manual</span>
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="date"
                                                value={dateFrom}
                                                onChange={(e) => { setDateFrom(e.target.value); applyFilters({ date_from: e.target.value, page: 1 }); }}
                                                className="text-xs border rounded-md px-3 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                            />
                                            <span className="text-slate-400 text-xs">s/d</span>
                                            <input
                                                type="date"
                                                value={dateTo}
                                                onChange={(e) => { setDateTo(e.target.value); applyFilters({ date_to: e.target.value, page: 1 }); }}
                                                className="text-xs border rounded-md px-3 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        <DataTable
                            columns={orderColumns}
                            data={orders.data || []}
                            serverPagination={orders}
                            searchValue={search}
                            onServerSearchChange={(val) => {
                                setSearch(val);
                                applyFilters({ q: val, page: 1 });
                            }}
                            onServerPageChange={(page) => applyFilters({ page })}
                            onServerPerPageChange={(per_page) => applyFilters({ per_page, page: 1 })}
                            searchPlaceholder="Cari no PO, nama, pelanggan..."
                            exportFileName={`Order_PO_${new Date().toISOString().slice(0, 10)}`}
                            emptyMessage={isArchiveTab && status === 'sudah_dikirim'
                                ? 'Semua PO sudah diselesaikan! Tidak ada yang menunggu.'
                                : 'Belum ada PO yang cocok dengan filter.'}
                            filterSlot={
                                <div className="flex flex-wrap items-center gap-2">
                                    {can?.filter_by_brand && brands?.length > 0 && (
                                        <Select value={brandId || NONE} onValueChange={(v) => { const val = v === NONE ? '' : v; setBrandId(val); applyFilters({ brand_id: val, page: 1 }); }}>
                                            <SelectTrigger className="h-8 w-44 text-xs bg-background"><SelectValue placeholder="📦 Semua Brand Saya" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={NONE}>📦 Semua Brand Saya</SelectItem>
                                                {brands.map((b) => (
                                                    <SelectItem key={b.id} value={b.id}>{b.nama_brand}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}

                                    <Button
                                        type="button"
                                        variant={showDatePanel || dateFrom || dateTo ? 'secondary' : 'outline'}
                                        size="sm"
                                        onClick={() => setShowDatePanel(!showDatePanel)}
                                        className="h-8 text-xs gap-1.5"
                                    >
                                        <Calendar className="h-3.5 w-3.5 text-slate-500" />
                                        {getDateFilterLabel()}
                                    </Button>

                                    {hasActiveFilter && (
                                        <Button type="button" variant="ghost" size="sm" onClick={resetFilters} className="h-8 text-xs text-slate-500 gap-1">
                                            <X className="h-3.5 w-3.5" />
                                            Reset
                                        </Button>
                                    )}
                                </div>
                            }
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog open={!!confirmDelete} onOpenChange={(v) => !v && setConfirmDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus PO Draft?</DialogTitle>
                        <DialogDescription>
                            PO <span className="font-mono font-semibold">{confirmDelete?.no_po}</span> akan dihapus.
                            Hanya PO draft yang bisa dihapus.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmDelete(null)}>Batal</Button>
                        <Button variant="destructive" onClick={doDelete}>Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog Konfirmasi Selesaikan PO */}
            <Dialog open={!!confirmComplete} onOpenChange={(v) => !v && setConfirmComplete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <CheckCircle2 className="h-5 w-5 text-emerald-500" />
                            Selesaikan Pesanan?
                        </DialogTitle>
                        <DialogDescription>
                            PO <span className="font-mono font-semibold">{confirmComplete?.no_po}</span> —{' '}
                            <span className="font-medium">{confirmComplete?.nama_po}</span> akan ditandai{' '}
                            <span className="font-bold text-emerald-700">Selesai</span>.
                            <br />
                            <span className="text-xs text-slate-500 mt-1 block">
                                Status ini bersifat final dan tidak dapat diubah kembali. Pastikan pesanan sudah diterima pelanggan dan pembayaran lunas.
                            </span>
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmComplete(null)}>Batal</Button>
                        <Button
                            className="bg-emerald-600 hover:bg-emerald-700 text-white"
                            onClick={doComplete}
                        >
                            <CheckCircle2 className="h-4 w-4 mr-1" /> Ya, Selesaikan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
