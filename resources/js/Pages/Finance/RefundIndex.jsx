import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { Search, Plus, CheckCircle2, XCircle, Receipt } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_VARIANT = {
    draft: 'outline', pending_review: 'warning', approved: 'info',
    published: 'success', rejected: 'destructive',
};

function CreateRefundDialog({ open, onOpenChange, jenisOptions }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        order_id: '', alasan: '', jenis_masalah: 'produk_cacat',
        jumlah_item: 1, nominal_refund: 0, catatan: '',
    });
    const [orders, setOrders] = useState([]);
    const [searchPO, setSearchPO] = useState('');

    useEffect(() => {
        if (!open) return;
        axios.get(route('orders.index'), { params: { q: searchPO } })
            .catch(() => {});
    }, [open, searchPO]);

    function submit(e) {
        e.preventDefault();
        post(route('refunds.store'), {
            preserveScroll: true,
            onSuccess: () => { reset(); onOpenChange(false); },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Ajukan Refund</DialogTitle>
                        <DialogDescription>
                            Refund untuk PO yang sudah diterbitkan. Akan masuk ke review Admin Keuangan.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-3 py-4">
                        <div>
                            <Label>ID PO (UUID) <span className="text-destructive">*</span></Label>
                            <Input value={data.order_id} onChange={(e) => setData('order_id', e.target.value)} className="mt-1.5 font-mono text-xs" placeholder="Tempel UUID PO" />
                            <p className="mt-1 text-xs text-muted-foreground">Dari halaman preview PO, copy UUID dari URL.</p>
                            {errors.order_id && <p className="text-xs text-destructive">{errors.order_id}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Jenis Masalah</Label>
                                <Select value={data.jenis_masalah} onValueChange={(v) => setData('jenis_masalah', v)}>
                                    <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {jenisOptions.map((j) => (<SelectItem key={j} value={j}>{j.replace(/_/g, ' ')}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Jumlah Item</Label>
                                <Input type="number" min={1} value={data.jumlah_item} onChange={(e) => setData('jumlah_item', Number(e.target.value))} className="mt-1.5" />
                            </div>
                        </div>
                        <div>
                            <Label>Nominal Refund <span className="text-destructive">*</span></Label>
                            <Input type="number" min={0} value={data.nominal_refund} onChange={(e) => setData('nominal_refund', Number(e.target.value))} className="mt-1.5" />
                            {errors.nominal_refund && <p className="text-xs text-destructive">{errors.nominal_refund}</p>}
                        </div>
                        <div>
                            <Label>Alasan <span className="text-destructive">*</span></Label>
                            <Textarea value={data.alasan} onChange={(e) => setData('alasan', e.target.value)} rows={2} className="mt-1.5" />
                            {errors.alasan && <p className="text-xs text-destructive">{errors.alasan}</p>}
                        </div>
                        <div>
                            <Label>Catatan</Label>
                            <Textarea value={data.catatan} onChange={(e) => setData('catatan', e.target.value)} rows={2} className="mt-1.5" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                        <Button type="submit" disabled={processing}>Ajukan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function RejectDialog({ refund, open, onOpenChange }) {
    const { data, setData, post, processing, errors, reset } = useForm({ rejected_reason: '' });
    function submit(e) {
        e.preventDefault();
        post(route('refunds.reject', refund.id), {
            preserveScroll: true,
            onSuccess: () => { reset(); onOpenChange(false); },
        });
    }
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Tolak Refund {refund?.refund_number}</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <Label>Alasan Penolakan <span className="text-destructive">*</span></Label>
                        <Textarea value={data.rejected_reason} onChange={(e) => setData('rejected_reason', e.target.value)} rows={3} className="mt-1.5" />
                        {errors.rejected_reason && <p className="mt-1 text-xs text-destructive">{errors.rejected_reason}</p>}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                        <Button type="submit" variant="destructive" disabled={processing}>Tolak</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function RefundIndex({ refunds, filters, statuses, jenis_options: jenisOptions, can }) {
    const [search, setSearch] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');
    const [createOpen, setCreateOpen] = useState(false);
    const [rejecting, setRejecting] = useState(null);

    function applyFilters(overrides = {}) {
        router.get(route('refunds.index'), {
            q: overrides.q ?? search,
            status: (overrides.status ?? status) === 'all' ? '' : (overrides.status ?? status),
        }, { preserveScroll: true, preserveState: true });
    }

    function publish(refund) {
        if (!confirm(`Terbitkan refund ${refund.refund_number}? Nominal akan jadi pengurangan pemasukan.`)) return;
        router.post(route('refunds.publish', refund.id), {}, { preserveScroll: true });
    }

    return (
        <AppLayout title="Refund Management">
            <Head title="Refund" />

            <div className="space-y-5">
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <Receipt className="h-5 w-5 text-primary" /> Daftar Refund
                            </div>
                            <p className="text-sm text-muted-foreground">Pengajuan refund untuk PO bermasalah. Diterbitkan oleh Admin Keuangan.</p>
                        </div>
                        {can?.create && (
                            <Button onClick={() => setCreateOpen(true)}><Plus className="h-4 w-4" /> Ajukan Refund</Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input placeholder="Cari no refund / no PO..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} className="pl-9" />
                            </div>
                            <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                <SelectTrigger className="sm:w-48"><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    {statuses.map((s) => (<SelectItem key={s} value={s}>{s}</SelectItem>))}
                                </SelectContent>
                            </Select>
                            <Button variant="outline" onClick={() => applyFilters()}>Terapkan</Button>
                        </div>

                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. Refund</TableHead>
                                        <TableHead>No. PO</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead className="text-right">Nominal</TableHead>
                                        <TableHead>Diajukan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {refunds.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={7} className="py-8 text-center text-sm text-muted-foreground">
                                                Belum ada refund.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {refunds.data.map((r) => (
                                        <TableRow key={r.id}>
                                            <TableCell className="font-mono text-xs">{r.refund_number}</TableCell>
                                            <TableCell className="font-mono text-xs">{r.order?.no_po}</TableCell>
                                            <TableCell><Badge variant="outline">{r.jenis_masalah?.replace(/_/g, ' ')}</Badge></TableCell>
                                            <TableCell className="text-right font-mono text-xs">{formatRupiah(r.nominal_refund)}</TableCell>
                                            <TableCell className="text-xs">
                                                {r.creator?.name}<br />
                                                <span className="text-muted-foreground">{formatDate(r.created_at)}</span>
                                            </TableCell>
                                            <TableCell><Badge variant={STATUS_VARIANT[r.status] ?? 'outline'}>{r.status}</Badge></TableCell>
                                            <TableCell className="text-right">
                                                {can?.review && r.status === 'pending_review' && (
                                                    <div className="flex justify-end gap-1">
                                                        <Button size="sm" variant="outline" className="text-emerald-600" onClick={() => publish(r)}>
                                                            <CheckCircle2 className="h-3.5 w-3.5" /> Terbitkan
                                                        </Button>
                                                        <Button size="sm" variant="outline" className="text-destructive" onClick={() => setRejecting(r)}>
                                                            <XCircle className="h-3.5 w-3.5" /> Tolak
                                                        </Button>
                                                    </div>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <CreateRefundDialog open={createOpen} onOpenChange={setCreateOpen} jenisOptions={jenisOptions} />
            {rejecting && (
                <RejectDialog key={rejecting.id} refund={rejecting} open={!!rejecting} onOpenChange={(v) => !v && setRejecting(null)} />
            )}
        </AppLayout>
    );
}
