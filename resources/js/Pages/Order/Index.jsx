import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Pencil, Trash2, Search, Eye, Package, RotateCw, Send, Lock, Unlock } from 'lucide-react';
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
    draft: { label: 'Draft', variant: 'outline' },
    published: { label: 'Published', variant: 'info' },
    on_progress: { label: 'On Progress', variant: 'warning' },
    selesai_produksi: { label: 'Selesai Produksi', variant: 'success' },
    siap_dikirim: { label: 'Siap Dikirim', variant: 'info' },
    sudah_dikirim: { label: 'Sudah Dikirim', variant: 'secondary' },
    delay: { label: 'Delay', variant: 'destructive' },
    hold: { label: 'Hold', variant: 'warning' },
};

export default function OrderIndex({ orders, filters, statuses, can }) {
    const [search, setSearch] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');
    const [confirmDelete, setConfirmDelete] = useState(null);

    function applyFilters(overrides = {}) {
        router.get(route('orders.index'), {
            q: overrides.q ?? search,
            status: (overrides.status ?? status) === 'all' ? '' : (overrides.status ?? status),
        }, { preserveScroll: true, preserveState: true });
    }

    function doDelete() {
        if (!confirmDelete) return;
        router.delete(route('orders.destroy', confirmDelete.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmDelete(null),
        });
    }

    return (
        <AppLayout title="Order Management">
            <Head title="Order" />

            <div className="space-y-5">
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
                        {can?.create && (
                            <Button asChild>
                                <Link href={route('orders.create')}>
                                    <Plus className="h-4 w-4" /> Buat PO
                                </Link>
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
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
                                <SelectTrigger className="sm:w-52"><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    {statuses.map((s) => (
                                        <SelectItem key={s} value={s}>{STATUS_LABEL[s]?.label ?? s}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button variant="outline" onClick={() => applyFilters()}>Terapkan</Button>
                        </div>

                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. PO</TableHead>
                                        <TableHead>Nama PO</TableHead>
                                        <TableHead>Pelanggan</TableHead>
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
                                            <TableCell colSpan={8} className="py-8 text-center text-sm text-muted-foreground">
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
                                                <TableCell>{o.pelanggan?.nama ?? '-'}</TableCell>
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

                        {orders.last_page > 1 && (
                            <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span className="text-muted-foreground">
                                    Menampilkan {orders.from ?? 0}–{orders.to ?? 0} dari {orders.total} data
                                </span>
                                <div className="flex gap-1">
                                    {orders.links.map((link, i) => (
                                        <Button key={i} variant={link.active ? 'default' : 'outline'} size="sm" disabled={!link.url}
                                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                                            dangerouslySetInnerHTML={{ __html: link.label }} />
                                    ))}
                                </div>
                            </div>
                        )}
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
