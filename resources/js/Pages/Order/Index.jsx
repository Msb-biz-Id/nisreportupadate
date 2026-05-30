import { Head, Link, router } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import { Plus, Pencil, Trash2, Search, Eye, Package, RotateCw, Copy, Check, X, Calendar } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_LABEL = {
    draft:            { label: 'Draft',           variant: 'outline',     color: '#94A3B8' },
    published:        { label: 'Baru Masuk',       variant: 'info',        color: '#3B82F6' },
    on_progress:      { label: 'On Progress',      variant: 'warning',     color: '#F59E0B' },
    selesai_produksi: { label: 'Selesai Produksi', variant: 'success',     color: '#22C55E' },
    siap_dikirim:     { label: 'Siap Dikirim',     variant: 'info',        color: '#06B6D4' },
    sudah_dikirim:    { label: 'Sudah Dikirim',    variant: 'secondary',   color: '#8B5CF6' },
    delay:            { label: 'Delay',            variant: 'destructive', color: '#EF4444' },
    hold:             { label: 'Hold',             variant: 'warning',     color: '#F97316' },
};

const NONE = '__none__';

export default function OrderIndex({ orders, filters, statuses, statusCounts, brands, can }) {
    const [search, setSearch]     = useState(filters?.q ?? '');
    const [status, setStatus]     = useState(filters?.status ?? 'all');
    const [brandId, setBrandId]   = useState(filters?.brand_id ?? '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo]     = useState(filters?.date_to ?? '');
    const [confirmDelete, setConfirmDelete] = useState(null);
    const [copied, setCopied]     = useState(false);
    const [showDatePanel, setShowDatePanel] = useState(!!(filters?.date_from || filters?.date_to));

    function applyFilters(overrides = {}) {
        router.get(route('orders.index'), {
            q:         overrides.hasOwnProperty('q') ? overrides.q : search,
            status:    (overrides.hasOwnProperty('status') ? overrides.status : status) === 'all' ? '' : (overrides.hasOwnProperty('status') ? overrides.status : status),
            brand_id:  (overrides.hasOwnProperty('brand_id') ? overrides.brand_id : brandId) === NONE ? '' : (overrides.hasOwnProperty('brand_id') ? overrides.brand_id : brandId),
            date_from: overrides.hasOwnProperty('date_from') ? overrides.date_from : dateFrom,
            date_to:   overrides.hasOwnProperty('date_to') ? overrides.date_to : dateTo,
        }, { preserveScroll: true, preserveState: true });
    }

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
                return { start: formatDateStr(firstDay), end: formatDateStr(today) };
            }
            default:
                return { start: '', end: '' };
        }
    };

    const getActivePreset = () => {
        if (!dateFrom && !dateTo) return 'all';

        const todayStr = new Date().toLocaleDateString('sv');
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toLocaleDateString('sv');

        if (dateFrom === todayStr && dateTo === todayStr) return 'today';
        if (dateFrom === yesterdayStr && dateTo === yesterdayStr) return 'yesterday';

        const last7 = new Date();
        last7.setDate(last7.getDate() - 6);
        if (dateFrom === last7.toLocaleDateString('sv') && dateTo === todayStr) return 'last_7';

        const last30 = new Date();
        last30.setDate(last30.getDate() - 29);
        if (dateFrom === last30.toLocaleDateString('sv') && dateTo === todayStr) return 'last_30';

        const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        if (dateFrom === firstDay.toLocaleDateString('sv') && dateTo === todayStr) return 'this_month';

        return 'custom';
    };

    const handlePresetClick = (preset) => {
        const range = calculateDateRange(preset);
        setDateFrom(range.start);
        setDateTo(range.end);
        applyFilters({ date_from: range.start, date_to: range.end });
    };

    const getDateFilterLabel = () => {
        if (!dateFrom && !dateTo) return "Filter Tanggal";
        const active = getActivePreset();
        if (active === 'today') return "Hari Ini";
        if (active === 'yesterday') return "Kemarin";
        if (active === 'last_7') return "7 Hari Terakhir";
        if (active === 'last_30') return "30 Hari Terakhir";
        if (active === 'this_month') return "Bulan Ini";
        
        return `${dateFrom ? formatDate(dateFrom) : ''} - ${dateTo ? formatDate(dateTo) : ''}`;
    };

    function resetFilters() {
        setSearch(''); setStatus('all'); setBrandId('');
        setDateFrom(''); setDateTo(''); setShowDatePanel(false);
        router.get(route('orders.index'), {}, { preserveScroll: true, preserveState: true });
    }

    function doDelete() {
        if (!confirmDelete) return;
        router.delete(route('orders.destroy', confirmDelete.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmDelete(null),
        });
    }

    const hasActiveFilter = search || (status && status !== 'all') || brandId || dateFrom || dateTo;
    const totalPo = Object.values(statusCounts ?? {}).reduce((s, v) => s + v, 0);
    const summaryItems = statuses.filter((s) => (statusCounts?.[s] ?? 0) > 0).map((s) => ({ key: s, ...STATUS_LABEL[s], count: statusCounts[s] }));

    function copyToClipboard() {
        const headers = ['No. PO', 'Nama PO', 'Brand', 'Pelanggan', 'Tgl Masuk', 'Deadline', 'Status', 'Total', 'Items'];
        const rows = orders.data.map((o) => [
            o.no_po,
            o.nama_po,
            o.brand?.nama_brand ?? '',
            o.pelanggan?.nama ?? '',
            formatDate(o.tanggal_masuk),
            formatDate(o.deadline_customer),
            STATUS_LABEL[o.status_po]?.label ?? o.status_po,
            o.total_tagihan ?? 0,
            o.items_count ?? 0,
        ]);

        const tsv = [headers, ...rows].map((r) => r.join('\t')).join('\n');
        navigator.clipboard.writeText(tsv).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    }

    return (
        <AppLayout title="Order Management">
            <Head title="Order" />

            <div className="space-y-4">
                {/* Summary Cards */}
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
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={copyToClipboard}
                                className={copied ? 'text-emerald-600 border-emerald-400' : ''}
                                title="Salin data halaman ini ke clipboard (untuk Excel)"
                            >
                                {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                                {copied ? 'Tersalin!' : 'Salin Data'}
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
                        {/* Filter Bar */}
                        <div className="mb-6 space-y-4 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                                {/* Search */}
                                <div className="relative">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        placeholder="Cari no PO, nama, pelanggan..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                        className="pl-9 bg-white"
                                    />
                                </div>

                                {/* Status Select */}
                                <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                    <SelectTrigger className="bg-white"><SelectValue placeholder="Status" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        {statuses.map((s) => (
                                            <SelectItem key={s} value={s}>{STATUS_LABEL[s]?.label ?? s}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                {/* Brand Filter */}
                                {can?.filter_by_brand && brands?.length > 0 ? (
                                    <Select value={brandId || NONE} onValueChange={(v) => { const val = v === NONE ? '' : v; setBrandId(val); applyFilters({ brand_id: val }); }}>
                                        <SelectTrigger className="bg-white"><SelectValue placeholder="Semua Brand" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>Semua Brand</SelectItem>
                                            {brands.map((b) => (
                                                <SelectItem key={b.id} value={b.id}>{b.nama_brand}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <div className="hidden lg:block"></div>
                                )}

                                {/* Collapsible Date Toggle */}
                                <Button 
                                    type="button" 
                                    variant={showDatePanel || dateFrom || dateTo ? "secondary" : "outline"} 
                                    onClick={() => setShowDatePanel(!showDatePanel)}
                                    className="bg-white hover:bg-slate-50 border flex justify-between items-center text-slate-700 font-medium w-full"
                                >
                                    <span className="flex items-center gap-2">
                                        <Calendar className="h-4 w-4 text-slate-500" />
                                        {getDateFilterLabel()}
                                    </span>
                                </Button>

                                {/* Action Buttons */}
                                <div className="flex gap-2">
                                    <Button className="flex-1" onClick={() => applyFilters()}>Filter</Button>
                                    <Button variant="outline" onClick={resetFilters}>Reset</Button>
                                </div>
                            </div>

                            {/* Collapsible Date Filter Panel */}
                            {showDatePanel && (
                                <div className="p-4 bg-white rounded-lg border border-slate-200/60 mt-2 space-y-4 animate-in fade-in slide-in-from-top-2 duration-200">
                                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div>
                                            <span className="text-xs font-semibold text-slate-500 block mb-2">Pilih Cepat Rentang Tanggal PO</span>
                                            <div className="flex flex-wrap gap-1.5">
                                                {[
                                                    { label: 'Hari Ini', preset: 'today' },
                                                    { label: 'Kemarin', preset: 'yesterday' },
                                                    { label: '7 Hari Terakhir', preset: 'last_7' },
                                                    { label: '30 Hari Terakhir', preset: 'last_30' },
                                                    { label: 'Bulan Ini', preset: 'this_month' },
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
                                        </div>
                                        
                                        <div className="border-t md:border-t-0 md:border-l border-dashed border-slate-200 pt-3 md:pt-0 md:pl-6">
                                            <span className="text-xs font-semibold text-slate-500 block mb-2">Pilih Tanggal Manual</span>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="date"
                                                    value={dateFrom}
                                                    onChange={(e) => { setDateFrom(e.target.value); applyFilters({ date_from: e.target.value }); }}
                                                    className="text-xs border rounded-md px-3 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                                />
                                                <span className="text-slate-400 text-xs">s/d</span>
                                                <input
                                                    type="date"
                                                    value={dateTo}
                                                    onChange={(e) => { setDateTo(e.target.value); applyFilters({ date_to: e.target.value }); }}
                                                    className="text-xs border rounded-md px-3 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Active Filters Display */}
                            <div className="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground pt-3 border-t border-slate-100">
                                {hasActiveFilter ? (
                                    <>
                                        <span className="font-semibold text-slate-500 mr-1">Filter Aktif:</span>
                                        {search && <Badge variant="secondary" className="px-2 py-0.5">{search}</Badge>}
                                        {brandId && (
                                            <Badge variant="secondary" className="px-2 py-0.5">
                                                {brands.find(b => String(b.id) === String(brandId))?.nama_brand ?? 'Brand'}
                                            </Badge>
                                        )}
                                        {status && status !== 'all' && (
                                            <Badge variant="secondary" className="px-2 py-0.5">
                                                {STATUS_LABEL[status]?.label ?? status.toUpperCase()}
                                            </Badge>
                                        )}
                                        {(dateFrom || dateTo) && <Badge variant="secondary" className="px-2 py-0.5">{getDateFilterLabel()}</Badge>}
                                    </>
                                ) : (
                                    <span className="text-slate-400">Menampilkan semua PO tanpa filter.</span>
                                )}
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. PO</TableHead>
                                        <TableHead>Nama PO</TableHead>
                                        {can?.filter_by_brand && <TableHead>Brand</TableHead>}
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead>Tgl Masuk</TableHead>
                                        <TableHead>Deadline</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-center">Items</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={can?.filter_by_brand ? 10 : 9} className="py-8 text-center text-sm text-muted-foreground">
                                                Belum ada PO yang cocok dengan filter.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {orders.data.map((o) => {
                                        const st = STATUS_LABEL[o.status_po] ?? { label: o.status_po, variant: 'outline' };
                                        return (
                                            <TableRow key={o.id}>
                                                <TableCell className="font-mono text-xs">{o.no_po}</TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{o.nama_po}</div>
                                                    {o.is_repeat_order && <Badge variant="outline" className="mt-1 text-[10px]"><RotateCw className="mr-1 h-3 w-3" />Repeat</Badge>}
                                                </TableCell>
                                                {can?.filter_by_brand && (
                                                    <TableCell className="text-xs text-muted-foreground">{o.brand?.kode ?? '-'}</TableCell>
                                                )}
                                                <TableCell>{o.pelanggan?.nama ?? '-'}</TableCell>
                                                <TableCell className="text-xs">{formatDate(o.tanggal_masuk)}</TableCell>
                                                <TableCell className="text-xs">{formatDate(o.deadline_customer)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs">{formatRupiah(o.total_tagihan)}</TableCell>
                                                <TableCell><Badge variant={st.variant}>{st.label}</Badge></TableCell>
                                                <TableCell className="text-center">
                                                    <Badge variant="outline">{o.items_count ?? 0}</Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button asChild size="icon" variant="ghost" title="Preview">
                                                            <Link href={route('orders.show', o.id)}>
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        {can?.update && o.status_po === 'draft' && (
                                                            <Button asChild size="icon" variant="ghost" title="Edit">
                                                                <Link href={route('orders.edit', o.id)}>
                                                                    <Pencil className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {can?.delete && o.status_po === 'draft' && (
                                                            <Button size="icon" variant="ghost" title="Hapus" className="text-destructive hover:text-destructive" onClick={() => setConfirmDelete(o)}>
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="mt-3 flex items-center justify-between text-xs text-muted-foreground">
                            <span>
                                {orders.total > 0
                                    ? `Menampilkan ${orders.from ?? 0}–${orders.to ?? 0} dari ${orders.total} PO`
                                    : '0 PO ditemukan'}
                                {hasActiveFilter && <span className="ml-1 text-amber-600">(difilter)</span>}
                            </span>
                            {orders.last_page > 1 && (
                                <div className="flex gap-1">
                                    {orders.links.map((link, i) => (
                                        <Button key={i} variant={link.active ? 'default' : 'outline'} size="sm" disabled={!link.url}
                                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                                            dangerouslySetInnerHTML={{ __html: link.label }} />
                                    ))}
                                </div>
                            )}
                        </div>
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
        </AppLayout>
    );
}
