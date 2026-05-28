import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Package, User, MapPin, CalendarClock, Pencil, Send, RotateCw, Trash2, Lock, Unlock,
    CreditCard, ListChecks, AlertTriangle, Receipt, FileText, ExternalLink,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Separator } from '@/Components/ui/separator';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/utils';

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

const PROGRESS_STATUS = {
    pending: { label: 'Pending', variant: 'outline' },
    on_progress: { label: 'On Progress', variant: 'warning' },
    selesai: { label: 'Selesai', variant: 'success' },
    skipped: { label: 'Skipped', variant: 'secondary' },
};

function UnlockDialog({ order, open, onOpenChange }) {
    const { data, setData, post, processing, errors } = useForm({ reason: '' });
    function submit(e) {
        e.preventDefault();
        post(route('orders.unlock', order.id), { onSuccess: () => onOpenChange(false), preserveScroll: true });
    }
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Unlock PO</DialogTitle>
                        <DialogDescription>
                            Setelah unlock, PO bisa diubah. Setiap perubahan akan tercatat di Change Log. Re-lock setelah selesai edit.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="py-4">
                        <Label>Alasan Unlock <span className="text-destructive">*</span></Label>
                        <Textarea value={data.reason} onChange={(e) => setData('reason', e.target.value)} rows={3} className="mt-1.5" placeholder="Contoh: Customer minta tambah ukuran, deadline diundur, dll" />
                        {errors.reason && <p className="mt-1 text-xs text-destructive">{errors.reason}</p>}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                        <Button type="submit" disabled={processing}>Unlock</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AddPaymentDialog({ order, open, onOpenChange, banks }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        payment_type: 'dp', amount: 0, payment_date: new Date().toISOString().slice(0, 10),
        bank_id: '', notes: '',
    });
    function submit(e) {
        e.preventDefault();
        post(route('orders.payments.store', order.id), {
            preserveScroll: true,
            onSuccess: () => { reset(); onOpenChange(false); },
        });
    }
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Tambah Pembayaran</DialogTitle>
                    </DialogHeader>
                    <div className="grid grid-cols-1 gap-3 py-4 sm:grid-cols-2">
                        <div>
                            <Label>Tipe</Label>
                            <Select value={data.payment_type} onValueChange={(v) => setData('payment_type', v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="dp">DP</SelectItem>
                                    <SelectItem value="pelunasan">Pelunasan</SelectItem>
                                    <SelectItem value="lainnya">Lainnya</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Nominal</Label>
                            <Input type="number" min={0} value={data.amount} onChange={(e) => setData('amount', Number(e.target.value))} className="mt-1.5" />
                            {errors.amount && <p className="text-xs text-destructive">{errors.amount}</p>}
                        </div>
                        <div>
                            <Label>Tanggal</Label>
                            <Input type="date" value={data.payment_date} onChange={(e) => setData('payment_date', e.target.value)} className="mt-1.5" />
                        </div>
                        <div>
                            <Label>Bank (opsional)</Label>
                            <Select value={data.bank_id || '__none__'} onValueChange={(v) => setData('bank_id', v === '__none__' ? '' : v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">— —</SelectItem>
                                    {banks.map((b) => (<SelectItem key={b.id} value={b.id}>{b.bank} {b.nomor_rekening}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="sm:col-span-2">
                            <Label>Catatan</Label>
                            <Textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} rows={2} className="mt-1.5" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                        <Button type="submit" disabled={processing}>Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function OrderPreview({ order, can }) {
    const [openUnlock, setOpenUnlock] = useState(false);
    const [openPayment, setOpenPayment] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);

    const banks = []; // banks fetched separately in real impl; here we accept what backend provides via payments

    const st = STATUS_LABEL[order.status_po] ?? { label: order.status_po, variant: 'outline' };
    const totalPaid = (order.payments ?? []).reduce((s, p) => s + Number(p.amount), 0);
    const sisaTagihan = Math.max(0, Number(order.total_tagihan) - totalPaid);

    function publish() {
        if (!confirm('Terbitkan PO sekarang? Setelah dipublish, PO masuk dashboard produksi dan tidak bisa dihapus.')) return;
        router.post(route('orders.publish', order.id), {}, { preserveScroll: true });
    }
    function repeatOrder() {
        if (!confirm('Buat PO baru dari template PO ini?')) return;
        router.post(route('orders.repeat', order.id));
    }
    function doDelete() {
        router.delete(route('orders.destroy', order.id));
    }
    function relock() {
        router.post(route('orders.relock', order.id), {}, { preserveScroll: true });
    }

    return (
        <AppLayout title={`Preview ${order.no_po}`}>
            <Head title={`PO ${order.no_po}`} />

            <div className="space-y-5">
                {/* Header */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-2xl font-bold font-mono">{order.no_po}</h2>
                                    <Badge variant={st.variant}>{st.label}</Badge>
                                    {order.is_special_order && <Badge variant="warning">Special Order</Badge>}
                                    {order.is_repeat_order && <Badge variant="outline"><RotateCw className="mr-1 h-3 w-3" />Repeat</Badge>}
                                    {order.lock_status?.is_locked && <Badge variant="secondary"><Lock className="mr-1 h-3 w-3" />Locked</Badge>}
                                </div>
                                <h3 className="mt-1 text-lg font-medium">{order.nama_po}</h3>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {can?.edit && (
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={route('orders.edit', order.id)}><Pencil className="h-4 w-4" /> Edit</Link>
                                    </Button>
                                )}
                                {can?.publish && (
                                    <Button onClick={publish} size="sm"><Send className="h-4 w-4" /> Terbitkan</Button>
                                )}
                                {can?.repeat && (
                                    <Button onClick={repeatOrder} variant="outline" size="sm"><RotateCw className="h-4 w-4" /> Repeat Order</Button>
                                )}
                                <Button asChild variant="outline" size="sm">
                                    <a href={route('orders.spk.pdf', order.id)} target="_blank" rel="noopener noreferrer">
                                        <FileText className="h-4 w-4" /> SPK PDF
                                    </a>
                                </Button>
                                {can?.manage_invoice && order.invoices?.length > 0 && (
                                    <Button asChild variant="outline" size="sm">
                                        <a href={route('invoice.public', order.invoices[0].invoice_number)} target="_blank" rel="noopener noreferrer">
                                            <Receipt className="h-4 w-4" /> Invoice
                                        </a>
                                    </Button>
                                )}
                                {order.lock_status?.is_locked && can?.unlock && (
                                    <Button variant="outline" size="sm" onClick={() => setOpenUnlock(true)}><Unlock className="h-4 w-4" /> Unlock</Button>
                                )}
                                {order.lock_status && ! order.lock_status?.is_locked && (
                                    <Button variant="outline" size="sm" onClick={relock}><Lock className="h-4 w-4" /> Re-lock</Button>
                                )}
                                {can?.delete && (
                                    <Button variant="outline" size="sm" className="text-destructive hover:text-destructive" onClick={() => setConfirmDelete(true)}>
                                        <Trash2 className="h-4 w-4" /> Hapus
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {/* Info Grid */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><User className="h-4 w-4 text-primary" /> Pelanggan</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <div className="font-semibold">{order.pelanggan?.nama ?? '-'}</div>
                            <div className="text-muted-foreground">{order.pelanggan?.kode}</div>
                            <div className="text-muted-foreground">{order.pelanggan?.nomor_hp}</div>
                            {order.pelanggan?.email && <div className="text-muted-foreground">{order.pelanggan.email}</div>}
                            {order.pelanggan?.kabupaten_nama && (
                                <div className="mt-2 flex items-start gap-1.5 text-xs text-muted-foreground">
                                    <MapPin className="mt-0.5 h-3 w-3" />
                                    <span>{order.pelanggan.kabupaten_nama}, {order.pelanggan.provinsi_nama}</span>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><CalendarClock className="h-4 w-4 text-primary" /> Timeline</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1.5 text-sm">
                            <div className="flex justify-between"><span className="text-muted-foreground">Tanggal Masuk</span><span className="font-medium">{formatDate(order.tanggal_masuk)}</span></div>
                            <div className="flex justify-between"><span className="text-muted-foreground">Deadline Customer</span><span className="font-medium">{formatDate(order.deadline_customer)}</span></div>
                            <div className="flex justify-between"><span className="text-muted-foreground">Mulai Produksi</span><span className="font-medium">{formatDate(order.start_production_date) || '-'}</span></div>
                            <div className="flex justify-between"><span className="text-muted-foreground">Selesai Produksi</span><span className="font-medium">{formatDate(order.end_production_date) || '-'}</span></div>
                            {order.published_at && (
                                <>
                                    <Separator className="my-2" />
                                    <div className="flex justify-between text-xs"><span className="text-muted-foreground">Diterbitkan</span><span>{formatDateTime(order.published_at)}</span></div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><CreditCard className="h-4 w-4 text-primary" /> Pembayaran</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1.5 text-sm">
                            <div className="flex justify-between"><span className="text-muted-foreground">Total Tagihan</span><span className="font-mono font-semibold">{formatRupiah(order.total_tagihan)}</span></div>
                            <div className="flex justify-between"><span className="text-muted-foreground">Sudah Dibayar</span><span className="font-mono text-emerald-600">{formatRupiah(totalPaid)}</span></div>
                            <div className="flex justify-between"><span className="text-muted-foreground">Sisa Tagihan</span><span className="font-mono font-bold text-destructive">{formatRupiah(sisaTagihan)}</span></div>
                            <Separator className="my-2" />
                            <Button size="sm" variant="outline" className="w-full" onClick={() => setOpenPayment(true)}>
                                <CreditCard className="h-4 w-4" /> Tambah Pembayaran
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                {/* Items */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base"><Package className="h-4 w-4 text-primary" /> Item Produk ({order.items?.length ?? 0})</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {(order.items ?? []).map((item, idx) => (
                            <div key={item.id} className="rounded-lg border p-3">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <div className="font-medium">{idx + 1}. {item.nama_produk} {item.varian_label && <Badge variant="outline" className="ml-1">{item.varian_label}</Badge>}</div>
                                        <div className="text-xs text-muted-foreground">Qty: <span className="font-mono font-semibold">{item.quantity}</span> × {formatRupiah(item.harga_satuan)}</div>
                                    </div>
                                    <div className="text-right font-mono font-semibold">{formatRupiah(item.subtotal)}</div>
                                </div>
                                <div className="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-muted-foreground sm:grid-cols-4">
                                    {item.bahan_kain && <div><span className="font-medium">Bahan:</span> {item.bahan_kain.nama}</div>}
                                    {item.jenis_setelan && <div><span className="font-medium">Setelan:</span> {item.jenis_setelan}</div>}
                                    {item.logo && <div><span className="font-medium">Logo:</span> {item.logo.nama}</div>}
                                    {item.printing && <div><span className="font-medium">Printing:</span> {item.printing.nama}</div>}
                                    {item.warna && <div><span className="font-medium">Warna:</span> {item.warna}</div>}
                                </div>
                                {item.namesets?.length > 0 && (
                                    <details className="mt-2">
                                        <summary className="cursor-pointer text-xs font-medium text-muted-foreground">Nameset ({item.namesets.length})</summary>
                                        <div className="mt-2 grid grid-cols-1 gap-1 text-xs sm:grid-cols-2 lg:grid-cols-3">
                                            {item.namesets.map((ns) => (
                                                <div key={ns.id} className="rounded border px-2 py-1">
                                                    <span className="font-medium">{ns.nama_punggung || '-'}</span> #{ns.nomor_punggung || '-'} · {ns.size?.kategori_size}-{ns.size?.ukuran || ns.size_label || '-'}
                                                </div>
                                            ))}
                                        </div>
                                    </details>
                                )}
                            </div>
                        ))}
                        {order.items?.length === 0 && <p className="text-center text-sm text-muted-foreground">Belum ada item.</p>}
                    </CardContent>
                </Card>

                {/* Progress Timeline */}
                {order.progress_details?.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><ListChecks className="h-4 w-4 text-primary" /> Progress Produksi</CardTitle>
                            <CardDescription>Klik <Link className="font-medium text-primary underline" href={route('produksi.progress', order.id)}>halaman progress</Link> untuk update status per tahapan.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ol className="relative space-y-3 border-l-2 border-border pl-5">
                                {order.progress_details
                                    .slice()
                                    .sort((a, b) => (a.progress?.urutan ?? 0) - (b.progress?.urutan ?? 0))
                                    .map((d) => {
                                        const ps = PROGRESS_STATUS[d.status] ?? { label: d.status, variant: 'outline' };
                                        return (
                                            <li key={d.id} className="relative">
                                                <span className="absolute -left-[27px] flex h-4 w-4 items-center justify-center rounded-full ring-2 ring-background" style={{ background: d.progress?.warna || '#3B82F6' }} />
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-medium">{d.progress?.urutan}. {d.progress?.nama_progress}</span>
                                                    <Badge variant={ps.variant}>{ps.label}</Badge>
                                                    {d.has_reject && <Badge variant="destructive"><AlertTriangle className="mr-1 h-3 w-3" />Ada Rijek</Badge>}
                                                </div>
                                                {d.catatan && <p className="text-xs text-muted-foreground">{d.catatan}</p>}
                                                {d.kendala && <p className="text-xs text-destructive">⚠ {d.kendala}</p>}
                                                {d.completed_at && <p className="text-xs text-muted-foreground">Selesai: {formatDateTime(d.completed_at)}</p>}
                                            </li>
                                        );
                                    })}
                            </ol>
                        </CardContent>
                    </Card>
                )}

                {/* Refunds */}
                {order.refunds?.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><Receipt className="h-4 w-4 text-primary" /> Riwayat Refund ({order.refunds.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {order.refunds.map((r) => (
                                <div key={r.id} className="flex items-center justify-between rounded border p-2 text-sm">
                                    <div>
                                        <div className="font-mono text-xs">{r.refund_number}</div>
                                        <div className="text-xs text-muted-foreground">{r.alasan}</div>
                                    </div>
                                    <div className="text-right">
                                        <div className="font-mono font-semibold">{formatRupiah(r.nominal_refund)}</div>
                                        <Badge variant={r.status === 'published' ? 'success' : r.status === 'rejected' ? 'destructive' : 'warning'}>{r.status}</Badge>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {/* Change Log */}
                {order.change_logs?.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><FileText className="h-4 w-4 text-primary" /> Change Log</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {order.change_logs.map((cl) => (
                                <div key={cl.id} className="rounded border p-2">
                                    <div className="flex justify-between text-xs">
                                        <span className="font-medium">{cl.field_changed}</span>
                                        <span className="text-muted-foreground">{formatDateTime(cl.created_at)} · {cl.changer?.name}</span>
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        <span className="line-through">{cl.old_value || '—'}</span> → <span className="font-semibold">{cl.new_value || '—'}</span>
                                    </div>
                                    {cl.change_reason && <div className="text-xs italic">"{cl.change_reason}"</div>}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {/* Public tracking link */}
                <Card>
                    <CardContent className="flex flex-col items-center justify-between gap-2 p-4 sm:flex-row">
                        <div>
                            <div className="text-sm font-medium">Link Tracking Publik</div>
                            <div className="font-mono text-xs text-muted-foreground">{window.location.origin}/track/{order.no_po}</div>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <a href={`/track/${order.no_po}`} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="h-4 w-4" /> Buka Tracking
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <UnlockDialog order={order} open={openUnlock} onOpenChange={setOpenUnlock} />
            <AddPaymentDialog order={order} open={openPayment} onOpenChange={setOpenPayment} banks={banks} />

            <Dialog open={confirmDelete} onOpenChange={setConfirmDelete}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus PO Draft?</DialogTitle>
                        <DialogDescription>PO ini akan dihapus permanen.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmDelete(false)}>Batal</Button>
                        <Button variant="destructive" onClick={doDelete}>Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
