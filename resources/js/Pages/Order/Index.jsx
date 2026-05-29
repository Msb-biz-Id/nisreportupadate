import { Head, Link, router } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import { Plus, Pencil, Trash2, Search, Eye, Package, RotateCw, Copy, Check, X } from 'lucide-react';
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

    function applyFilters(overrides = {}) {
        router.get(route('orders.index'), {
            q:         overrides.q         ?? search,
            status:    (overrides.status   ?? status) === 'all' ? '' : (overrides.status ?? status),
            brand_id:  (overrides.brand_id ?? brandId) === NONE ? '' : (overrides.brand_id ?? brandId),
            date_from: overrides.date_from ?? dateFrom,
            date_to:   overrides.date_to   ?? dateTo,
        }, { preserveScroll: true, preserveState: true });
    }

    function resetFilters() {
        setSearch(''); setStatus('all'); setBrandId('');
        setDateFrom(''); setDateTo('');
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
                        <div className="mb-4 space-y-2">
                            <div className="flex flex-col gap-2 sm:flex-row">
                                <div className="relative flex-1">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        placeholder="Cari no PO, nama PO, atau pelanggan..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                        className="pl-9"
                                    />
                                </div>
                                <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                    <SelectTrigger className="sm:w-48"><SelectValue placeholder="Status" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        {statuses.map((s) => (
                                            <SelectItem key={s} value={s}>{STATUS_LABEL[s]?.label ?? s}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {can?.filter_by_brand && brands?.length > 0 && (
                                    <Select value={brandId || NONE} onValueChange={(v) => { const val = v === NONE ? '' : v; setBrandId(val); applyFilters({ brand_id: val }); }}>
                                        <SelectTrigger className="sm:w-44"><SelectValue placeholder="Semua Brand" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>Semua Brand</SelectItem>
                                            {brands.map((b) => (
                                                <SelectItem key={b.id} value={b.id}>{b.nama_brand}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            </div>

                            {/* Range Tanggal */}
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <span className="text-xs font-medium text-muted-foreground whitespace-nowrap">Tgl Masuk:</span>
                                <Input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                    className="h-8 text-xs sm:w-40"
                                />
                                <span className="text-xs text-muted-foreground">s/d</span>
                                <Input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                    className="h-8 text-xs sm:w-40"
                                />
                                <Button size="sm" onClick={() => applyFilters()}>Terapkan</Button>
                                {hasActiveFilter && (
                                    <Button size="sm" variant="ghost" onClick={resetFilters} className="text-muted-foreground">
                                        <X className="h-3.5 w-3.5 mr-1" /> Reset
                                    </Button>
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
