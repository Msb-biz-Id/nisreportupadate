import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Package, User, MapPin, CalendarClock, Pencil, Send, RotateCw, Trash2, Lock, Unlock,
    CreditCard, ListChecks, AlertTriangle, Receipt, FileText, ExternalLink, CheckCircle2, XCircle,
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

function AddPaymentDialog({ order, open, onOpenChange, banks, jenis_pembayarans = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        master_jenis_pembayaran_id: '',
        amount: 0,
        payment_date: new Date().toISOString().split('T')[0],
        bank_id: '',
        notes: '',
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
                            <Select value={data.master_jenis_pembayaran_id} onValueChange={(v) => setData('master_jenis_pembayaran_id', v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {jenis_pembayarans.map((jp) => (
                                        <SelectItem key={jp.id} value={jp.id}>{jp.nama}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.master_jenis_pembayaran_id && <p className="text-xs text-destructive">{errors.master_jenis_pembayaran_id}</p>}
                        </div>
                        <div>
                            <Label>Nominal</Label>
                            <Input type="number" min={0} value={data.amount === 0 ? '' : data.amount} onChange={(e) => setData('amount', e.target.value === '' ? 0 : Number(e.target.value))} className="mt-1.5" />
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

function TimelineForm({ order, onDone }) {
    const { data, setData, patch, processing } = useForm({
        start_production_date: order.start_production_date?.slice(0, 10) ?? '',
        end_production_date:   order.end_production_date?.slice(0, 10) ?? '',
    });

    function submit(e) {
        e.preventDefault();
        patch(route('orders.timeline.update', order.id), { onSuccess: onDone });
    }

    return (
        <form onSubmit={submit} className="space-y-2 pt-2 border-t border-slate-100">
            <div className="flex justify-between items-center gap-2">
                <span className="text-muted-foreground text-sm">Mulai Produksi</span>
                <Input type="date" value={data.start_production_date} onChange={(e) => setData('start_production_date', e.target.value)} className="h-7 text-xs w-36" />
            </div>
            <div className="flex justify-between items-center gap-2">
                <span className="text-muted-foreground text-sm">Selesai Produksi</span>
                <Input type="date" value={data.end_production_date} onChange={(e) => setData('end_production_date', e.target.value)} className="h-7 text-xs w-36" />
            </div>
            <div className="flex gap-2 justify-end pt-1">
                <Button type="button" size="sm" variant="ghost" onClick={onDone}>Batal</Button>
                <Button type="submit" size="sm" disabled={processing}>Simpan</Button>
            </div>
        </form>
    );
}

export default function OrderPreview({ order, can, dp_info = null, printings = [], banks = [], jenis_pembayarans = [] }) {
    const [openUnlock, setOpenUnlock] = useState(false);
    const [openPayment, setOpenPayment] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [editTimeline, setEditTimeline] = useState(false);

    const st = STATUS_LABEL[order.status_po] ?? { label: order.status_po, variant: 'outline' };
    const pendingPayments = (order.payments ?? []).filter(p => !p.verified_at);
    const isDeduction = (p) => {
        if (p.master_jenis_pembayaran) return p.master_jenis_pembayaran.tipe_keuangan === 'pengeluaran';
        return ['cashback', 'return'].includes(p.payment_type);
    };
    const pendingPaid = Math.max(0, pendingPayments.reduce((s, p) => s + (isDeduction(p) ? -Number(p.amount) : Number(p.amount)), 0));

    const invoice = order.invoices?.[0];

    // Use server-computed values (dp_info) to stay in sync with backend publish logic.
    // Falls back to client calculation if dp_info is absent (e.g., older cached page).
    const totalTagihan    = dp_info ? Number(dp_info.total_tagihan)    : Number(order.total_tagihan || 0);
    const totalPaid       = dp_info ? Number(dp_info.total_paid)       : Math.max(0, (order.payments ?? []).filter(p => p.verified_at).reduce((s, p) => s + (isDeduction(p) ? -Number(p.amount) : Number(p.amount)), 0));
    const minDpPercentage = dp_info ? Number(dp_info.min_dp_percentage) : (order.brand?.min_dp_percentage != null ? Number(order.brand.min_dp_percentage) : 0.50);
    const minDp           = dp_info ? Number(dp_info.min_dp)           : totalTagihan * minDpPercentage;
    const isDpSufficient  = dp_info ? Boolean(dp_info.is_sufficient)   : (totalPaid >= minDp || order.is_dp_bypassed);
    const sisaTagihan     = Math.max(0, totalTagihan - totalPaid);

    function bypassDp() {
        if (!confirm(order.is_dp_bypassed ? 'Nonaktifkan bypass DP?' : 'Bypass minimal DP untuk PO ini?')) return;
        router.post(route('orders.bypass-dp', order.id), {}, { preserveScroll: true });
    }

    function publish() {
        if (!isDpSufficient) {
            alert(
                `PO tidak bisa diterbitkan.\n\n` +
                `Pembayaran terverifikasi: ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(totalPaid)}\n` +
                `Minimal DP ${(minDpPercentage * 100).toFixed(0)}%: ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(minDp)}\n\n` +
                `Pastikan Admin Keuangan telah memvalidasi pembayaran DP.`
            );
            return;
        }
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
                                    <Button
                                        onClick={publish}
                                        size="sm"
                                        className={!isDpSufficient ? "opacity-60 cursor-not-allowed bg-slate-400 hover:bg-slate-400 text-white" : ""}
                                        title={!isDpSufficient ? `Pembayaran DP belum mencapai minimal ${(minDpPercentage * 100).toFixed(0)}%` : "Terbitkan PO ke Produksi"}
                                    >
                                        <Send className="h-4 w-4" /> Terbitkan
                                    </Button>
                                )}
                                {can?.bypass_dp && order.status_po === 'draft' && (!isDpSufficient || order.is_dp_bypassed) && (
                                    <Button onClick={bypassDp} variant={order.is_dp_bypassed ? "destructive" : "secondary"} size="sm">
                                        {order.is_dp_bypassed ? 'Batalkan Bypass DP' : 'Bypass DP'}
                                    </Button>
                                )}
                                {can?.repeat && (
                                    <Button onClick={repeatOrder} variant="outline" size="sm"><RotateCw className="h-4 w-4" /> Repeat Order</Button>
                                )}
                                <Button asChild variant="outline" size="sm">
                                    <a href={route('orders.spk.pdf', order.id)} target="_blank" rel="noopener noreferrer">
                                        <FileText className="h-4 w-4" /> SPK PDF
                                    </a>
                                </Button>
                                {order.invoices?.length > 0 && (
                                    <>
                                        <Button asChild variant="outline" size="sm">
                                            <a href={route('invoice.public', order.invoices[0].invoice_number)} target="_blank" rel="noopener noreferrer">
                                                <Receipt className="h-4 w-4" /> Invoice
                                            </a>
                                        </Button>
                                        <Button asChild size="sm">
                                            <a href={route('invoice.public.pdf', order.invoices[0].invoice_number)} target="_blank" rel="noopener noreferrer">
                                                <FileText className="h-4 w-4" /> Cetak Invoice
                                            </a>
                                        </Button>
                                    </>
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

                {order.status_po === 'draft' && !isDpSufficient && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 flex items-start gap-3 text-amber-800 shadow-sm">
                        <AlertTriangle className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                        <div className="space-y-1">
                            <h4 className="text-sm font-bold">Menunggu DP Minimal {(minDpPercentage * 100).toFixed(0)}% 💸</h4>
                            <p className="text-xs text-amber-700 leading-relaxed font-medium">
                                PO belum bisa diterbitkan ke produksi. Pembayaran terverifikasi saat ini{' '}
                                <strong>{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(totalPaid)}</strong>
                                {' '}belum mencapai minimal {(minDpPercentage * 100).toFixed(0)}% DP
                                {' '}(<strong>{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(minDp)}</strong>).
                                {pendingPaid > 0 && (
                                    <span className="block mt-1">Terdapat{' '}
                                        <strong>{new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(pendingPaid)}</strong>
                                        {' '}pembayaran menunggu validasi oleh Admin Keuangan.
                                    </span>
                                )}
                            </p>
                        </div>
                    </div>
                )}

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
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="flex items-center gap-2 text-base"><CalendarClock className="h-4 w-4 text-primary" /> Timeline</CardTitle>
                            {can?.edit_timeline && !editTimeline && (
                                <Button size="icon" variant="ghost" className="h-7 w-7" onClick={() => setEditTimeline(true)} title="Edit Timeline Produksi">
                                    <Pencil className="h-3.5 w-3.5" />
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="space-y-1.5 text-sm">
                            <div className="flex justify-between"><span className="text-muted-foreground">Tanggal Masuk</span><span className="font-medium">{formatDate(order.tanggal_masuk)}</span></div>
                            <div className="flex justify-between"><span className="text-muted-foreground">Deadline Customer</span><span className="font-medium">{formatDate(order.deadline_customer)}</span></div>
                            {editTimeline ? (
                                <TimelineForm order={order} onDone={() => setEditTimeline(false)} />
                            ) : (
                                <>
                                    <div className="flex justify-between"><span className="text-muted-foreground">Mulai Produksi</span><span className="font-medium">{formatDate(order.start_production_date) || '-'}</span></div>
                                    <div className="flex justify-between"><span className="text-muted-foreground">Selesai Produksi</span><span className="font-medium">{formatDate(order.end_production_date) || '-'}</span></div>
                                </>
                            )}
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
                            <div className="flex justify-between"><span className="text-muted-foreground">Total Tagihan</span><span className="font-mono font-semibold">{formatRupiah(totalTagihan)}</span></div>
                            <div className="flex justify-between"><span className="text-muted-foreground">Sudah Diverifikasi</span><span className="font-mono text-emerald-600">{formatRupiah(totalPaid)}</span></div>
                            {pendingPaid > 0 && (
                                <div className="flex justify-between"><span className="text-muted-foreground">Menunggu Validasi</span><span className="font-mono text-amber-600">{formatRupiah(pendingPaid)}</span></div>
                            )}
                            <div className="flex justify-between"><span className="text-muted-foreground">Sisa Tagihan</span><span className="font-mono font-bold text-destructive">{formatRupiah(sisaTagihan)}</span></div>

                            {/* DP Status for draft PO */}
                            {order.status_po === 'draft' && (
                                <div className={`flex items-center justify-between rounded-lg border px-3 py-2 text-xs ${
                                    order.is_dp_bypassed
                                        ? 'border-blue-200 bg-blue-50/60 text-blue-700'
                                        : isDpSufficient
                                            ? 'border-emerald-200 bg-emerald-50/60 text-emerald-700'
                                            : 'border-amber-200 bg-amber-50/60 text-amber-700'
                                }`}>
                                    <div className="flex items-center gap-1.5">
                                        {order.is_dp_bypassed
                                            ? <CheckCircle2 className="h-3.5 w-3.5 text-blue-500" />
                                            : isDpSufficient
                                                ? <CheckCircle2 className="h-3.5 w-3.5 text-emerald-500" />
                                                : <AlertTriangle className="h-3.5 w-3.5 text-amber-500" />
                                        }
                                        <span className="font-semibold">
                                            {order.is_dp_bypassed
                                                ? `Bypass DP Aktif — diizinkan oleh Keuangan`
                                                : isDpSufficient
                                                    ? `DP ${(minDpPercentage * 100).toFixed(0)}%: Terpenuhi ✓`
                                                    : `DP ${(minDpPercentage * 100).toFixed(0)}%: Belum Terpenuhi`
                                            }
                                        </span>
                                    </div>
                                    <span className="font-mono font-bold">
                                        {totalTagihan > 0 ? ((totalPaid / totalTagihan) * 100).toFixed(0) : 0}%
                                    </span>
                                </div>
                            )}

                            <Separator className="my-2" />

                            {/* Status Lunas */}
                            <div className="flex items-center justify-between rounded-lg border px-3 py-2.5">
                                <div className="flex items-center gap-2">
                                     {(order.is_lunas || order.is_special_order)
                                         ? <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                         : <XCircle className="h-4 w-4 text-rose-400" />
                                     }
                                     <span className="text-sm font-semibold">
                                         {(order.is_lunas || order.is_special_order) ? 'Lunas' : 'Belum Lunas'}
                                     </span>
                                     {order.is_lunas && order.lunas_at && (
                                         <span className="text-[10px] text-muted-foreground">{formatDate(order.lunas_at)}</span>
                                     )}
                                 </div>
                                 {can?.mark_lunas && !order.is_special_order && (
                                     <Button
                                         size="xs"
                                         variant={order.is_lunas ? 'outline' : 'default'}
                                         className={order.is_lunas ? 'text-xs h-7' : 'text-xs h-7 bg-emerald-600 hover:bg-emerald-700'}
                                         onClick={() => router.post(route('orders.mark-lunas', order.id), {}, { preserveScroll: true })}
                                     >
                                         {order.is_lunas ? 'Batalkan' : 'Tandai Lunas'}
                                     </Button>
                                 )}
                             </div>

                            {/* Payment History */}
                            {(order.payments ?? []).length > 0 && (
                                <div className="space-y-3 pt-1">
                                    <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Riwayat Pembayaran</span>
                                    {(order.payments ?? []).map((p) => (
                                        <div key={p.id} className={`flex flex-col gap-2 rounded-xl border p-3 text-xs ${p.verified_at ? 'bg-slate-50/70 border-slate-200' : 'bg-amber-50/50 border-amber-200'}`}>
                                            <div className="flex items-center justify-between">
                                                <div className="space-y-0.5">
                                                    <div className="font-bold text-slate-800 text-xs">
                                                        {p.master_jenis_pembayaran?.nama ?? (p.payment_type ? p.payment_type.toUpperCase() : '-')} — {formatDate(p.payment_date)}
                                                    </div>
                                                    {p.notes && <div className="text-slate-500 font-medium text-[11px]">Memo: "{p.notes}"</div>}
                                                    {p.bank && <div className="text-slate-400 text-[10px] font-mono">{p.bank.bank} · {p.bank.nomor_rekening}</div>}
                                                </div>
                                                <div className="text-right space-y-1">
                                                    <div className="font-mono font-bold text-slate-900">{formatRupiah(p.amount)}</div>
                                                    <div className="flex items-center gap-1 justify-end">
                                                        <Badge variant={p.verified_at ? 'success' : 'warning'} className="text-[9px] px-1.5 py-0 font-bold">
                                                            {p.verified_at ? '✓ VERIFIED' : '⏳ PENDING'}
                                                        </Badge>
                                                        {can?.delete_payment && (
                                                            <button
                                                                onClick={() => {
                                                                    const msg = p.verified_at
                                                                        ? `Hapus pembayaran ${p.master_jenis_pembayaran?.nama ?? p.payment_type} Rp ${Number(p.amount).toLocaleString('id-ID')}?\n\nPembayaran ini sudah diverifikasi. Catatan keuangan (pemasukan/pengeluaran) terkait juga akan dihapus.`
                                                                        : `Hapus pembayaran ${p.master_jenis_pembayaran?.nama ?? p.payment_type} Rp ${Number(p.amount).toLocaleString('id-ID')}?`;
                                                                    if (confirm(msg)) {
                                                                        router.delete(route('invoices.payments.destroy', p.id), { preserveScroll: true });
                                                                    }
                                                                }}
                                                                className="ml-1 p-0.5 rounded text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors"
                                                                title="Hapus record pembayaran ini"
                                                            >
                                                                <XCircle className="h-3.5 w-3.5" />
                                                            </button>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            {p.verified_at && (
                                                <div className="mt-1.5 rounded-lg border border-slate-100 bg-white p-2.5 space-y-2 shadow-sm">
                                                    <div className="flex items-center justify-between text-[10px] font-bold text-slate-700">
                                                        <span className="flex items-center gap-1 text-slate-600">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                            Diverifikasi oleh:
                                                        </span>
                                                        <span className="text-slate-900 font-bold bg-slate-100 px-1.5 py-0.5 rounded text-[9px]">{p.verifier?.name ?? 'Finance Admin'}</span>
                                                    </div>
                                                    
                                                    {p.verification_checks && (
                                                        <div className="grid grid-cols-3 gap-1 pt-1 border-t border-slate-50">
                                                            <div className="flex items-center gap-1 text-[9px] font-semibold">
                                                                <span className={p.verification_checks.bank_mutasi ? "text-emerald-600" : "text-slate-400"}>
                                                                    {p.verification_checks.bank_mutasi ? '✓ Mutasi Koran' : '✗ Mutasi Koran'}
                                                                </span>
                                                            </div>
                                                            <div className="flex items-center gap-1 text-[9px] font-semibold">
                                                                <span className={p.verification_checks.nominal_cocok ? "text-emerald-600" : "text-slate-400"}>
                                                                    {p.verification_checks.nominal_cocok ? '✓ Nominal Cocok' : '✗ Nominal Cocok'}
                                                                </span>
                                                            </div>
                                                            <div className="flex items-center gap-1 text-[9px] font-semibold">
                                                                <span className={p.verification_checks.bukti_valid ? "text-emerald-600" : "text-slate-400"}>
                                                                    {p.verification_checks.bukti_valid ? '✓ Bukti Valid' : '✗ Bukti Valid'}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    )}
                                                    
                                                    {p.verification_notes ? (
                                                        <div className="text-[10px] text-slate-600 bg-slate-50 p-1.5 rounded-md border border-slate-100 font-medium italic">
                                                            "{p.verification_notes}"
                                                        </div>
                                                    ) : (
                                                        <div className="text-[10px] text-slate-400 italic">
                                                            Tidak ada catatan verifikasi.
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}

                            {can?.add_payment && (
                                <Button size="sm" variant="outline" className="w-full mt-2" onClick={() => setOpenPayment(true)}>
                                    <CreditCard className="h-4 w-4" /> Tambah Pembayaran
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Detail PO */}
                {(() => {
                    const fields = [
                        order.jenisOrder?.nama && { label: 'Jenis Order', value: order.jenisOrder.nama },
                        order.sumberOrder?.nama && { label: 'Sumber Order', value: order.sumberOrder.nama },
                        order.paketOrder?.nama && {
                            label: 'Paket Order',
                            value: order.paketOrder.nama,
                            warna: order.paketOrder.warna,
                        },
                        printings.length > 0 && { label: 'Jenis Printing', value: printings.map(p => p.nama).join(', ') },
                        order.iklan?.nama && { label: 'Promo', value: order.iklan.nama + (order.iklan.platform ? ` (${order.iklan.platform})` : '') },
                        (order.nama_ekspedisi || order.no_resi) && { label: 'Ekspedisi', value: [order.nama_ekspedisi, order.no_resi].filter(Boolean).join(' · ') },
                        order.catatan && { label: 'Catatan PO', value: order.catatan, full: true },
                    ].filter(Boolean);

                    if (fields.length === 0) return null;

                    return (
                        <Card>
                            <CardContent className="pt-4">
                                <div className="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-3 lg:grid-cols-4">
                                    {fields.map((f) => (
                                        <div key={f.label} className={f.full ? 'col-span-2 sm:col-span-3 lg:col-span-4' : ''}>
                                            <span className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{f.label}</span>
                                            {f.warna ? (
                                                <p className="mt-0.5 flex items-center gap-1.5 text-sm font-medium">
                                                    <span className="h-2.5 w-2.5 rounded-full shrink-0" style={{ background: f.warna }} />
                                                    {f.value}
                                                </p>
                                            ) : (
                                                <p className="mt-0.5 text-sm font-medium">{f.value}</p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    );
                })()}

                {/* Items */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base"><Package className="h-4 w-4 text-primary" /> Item Produk ({order.items?.length ?? 0})</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {(order.items ?? []).map((item, idx) => {
                            const SETELAN_LABEL = { stell: 'Stell', non_stell: 'Non-Stell', atasan_saja: 'Atasan Saja', bawahan_saja: 'Bawahan Saja' };
                            // Bahan Atasan: dari bahan_kain_ids (multi) atau bahan_kain (single)
                            const bahanAtasanStr = item.bahan_kain_ids?.length
                                ? (item.bahan_kains_names ?? item.bahan_kain?.nama ?? '')
                                : item.bahan_kain?.nama ?? '';
                            // Bahan Bawahan: dari bahan_kain_bawahan_ids atau bahan_kain_bawahan
                            const bahanBawahanStr = item.bahan_kain_bawahan_ids?.length
                                ? (item.bahan_kain_bawahan_names ?? item.bahan_kain_bawahan?.nama ?? '')
                                : item.bahan_kain_bawahan?.nama ?? '';
                            // Logo: dari logo_names (server-resolved) atau logo_ids atau single logo
                            const logoStr = item.logo_names?.length
                                ? item.logo_names.join(', ')
                                : item.logo?.nama ?? '';

                            const specFields = [
                                item.jenis_setelan && { label: 'Setelan', value: SETELAN_LABEL[item.jenis_setelan] ?? item.jenis_setelan },
                                item.pola && { label: 'Pola', value: item.pola === 'perempuan' ? 'Perempuan' : 'Standart' },
                                bahanAtasanStr && { label: 'Bahan Atasan', value: bahanAtasanStr },
                                bahanBawahanStr && { label: 'Bahan Bawahan', value: bahanBawahanStr },
                                item.warna && { label: 'Warna', value: item.warna },
                                item.jml_atasan && { label: 'Jml Atasan', value: item.jml_atasan },
                                item.jml_bawahan && { label: 'Jml Bawahan', value: item.jml_bawahan },
                                logoStr && { label: 'Logo', value: logoStr },
                                item.jenis_rib && { label: 'Jenis RIB', value: item.jenis_rib },
                                item.tutup_kerah && { label: 'Tutup Kerah', value: item.tutup_kerah },
                                item.list_kerah && { label: 'List Kerah', value: item.list_kerah },
                                item.list_lengan && { label: 'List Lengan', value: item.list_lengan },
                                item.list_samping_celana && { label: 'List Samping Celana', value: item.list_samping_celana },
                                item.list_bawah_celana && { label: 'List Bawah Celana', value: item.list_bawah_celana },
                            ].filter(Boolean);
                            const jahitanFields = [
                                item.pola_jahitan?.nama && {
                                    label: 'Pola Jahitan',
                                    value: item.pola_jahitan.nama
                                },
                                // Jahitan List Lengan: dari relasi pola_jahitan_lengan (UUID) atau string lama
                                (item.pola_jahitan_lengan?.nama || item.jahitan_list_lengan) && {
                                    label: 'Jahitan List Lengan',
                                    value: item.pola_jahitan_lengan?.nama ?? item.jahitan_list_lengan
                                },
                            ].filter(Boolean);
                            const hasImages = item.gambar_desain || item.gambar_kerah || item.gambar_ket_tambahan
                                           || item.ket_atasan || item.ket_bawahan || item.jenis_kerah;

                            return (
                                <div key={item.id} className="rounded-lg border p-3 space-y-3">
                                    {/* Header item */}
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <div className="font-medium">{idx + 1}. {item.nama_produk} {item.varian_label && <Badge variant="outline" className="ml-1">{item.varian_label}</Badge>}</div>
                                            <div className="text-xs text-muted-foreground">Qty: <span className="font-mono font-semibold">{item.quantity}</span> × {formatRupiah(item.harga_satuan)}</div>
                                        </div>
                                        <div className="text-right font-mono font-semibold">{formatRupiah(item.subtotal)}</div>
                                    </div>

                                    {/* Spesifikasi */}
                                    {specFields.length > 0 && (
                                        <div className="grid grid-cols-2 gap-x-4 gap-y-1 rounded-md bg-slate-50 p-2.5 text-xs sm:grid-cols-3 lg:grid-cols-4">
                                            {specFields.map((f) => (
                                                <div key={f.label}>
                                                    <span className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{f.label}</span>
                                                    <p className="font-medium text-slate-800">{f.value}</p>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {/* Jahitan & Resleting */}
                                    {(jahitanFields.length > 0 || item.resleting?.nama) && (
                                        <div className="grid grid-cols-2 gap-x-4 gap-y-1 rounded-md border border-slate-100 p-2.5 text-xs sm:grid-cols-3">
                                            {jahitanFields.map((f) => (
                                                <div key={f.label}>
                                                    <span className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{f.label}</span>
                                                    <p className="font-medium text-slate-800">{f.value}</p>
                                                </div>
                                            ))}
                                            {item.resleting?.nama && (
                                                <div>
                                                    <span className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Resleting</span>
                                                    <p className="font-medium text-slate-800">{item.resleting.nama}</p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Referensi */}
                                    {hasImages && (
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            {(item.gambar_desain || item.ket_atasan || item.ket_bawahan) && (
                                                <div className="space-y-1.5">
                                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Referensi Desain</p>
                                                    {item.gambar_desain && <img src={`/storage/${item.gambar_desain}`} alt="Desain" className="max-h-36 rounded border object-contain" />}
                                                    {item.ket_atasan && <p className="text-xs"><span className="font-semibold">Atasan:</span> {item.ket_atasan}</p>}
                                                    {item.ket_bawahan && <p className="text-xs"><span className="font-semibold">Bawahan:</span> {item.ket_bawahan}</p>}
                                                </div>
                                            )}
                                            {(item.gambar_kerah || item.jenis_kerah) && (
                                                <div className="space-y-1.5">
                                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Referensi Kerah</p>
                                                    {item.gambar_kerah && <img src={`/storage/${item.gambar_kerah}`} alt="Kerah" className="max-h-36 rounded border object-contain" />}
                                                    {item.jenis_kerah && <p className="text-xs"><span className="font-semibold">Jenis Kerah:</span> {item.jenis_kerah}</p>}
                                                </div>
                                            )}
                                            {item.gambar_ket_tambahan && (
                                                <div className="space-y-1.5">
                                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Keterangan Tambahan</p>
                                                    <img src={`/storage/${item.gambar_ket_tambahan}`} alt="Ket Tambahan" className="max-h-36 rounded border object-contain" />
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Nameset */}
                                    {item.namesets?.length > 0 && (
                                        <details className="mt-1">
                                            <summary className="cursor-pointer text-xs font-medium text-muted-foreground">Nameset ({item.namesets.length})</summary>
                                            <div className="mt-2 overflow-x-auto">
                                                <table className="w-full border-collapse text-xs">
                                                    <thead>
                                                        <tr className="bg-slate-100 text-slate-600 uppercase tracking-wide">
                                                            <th className="px-2 py-1.5 border font-semibold w-8 text-center">No</th>
                                                            <th className="px-2 py-1.5 border font-semibold">Nama Punggung</th>
                                                            <th className="px-2 py-1.5 border font-semibold text-center">No. Punggung</th>
                                                            <th className="px-2 py-1.5 border font-semibold">Nama Dada</th>
                                                            <th className="px-2 py-1.5 border font-semibold text-center">No. Dada</th>
                                                            <th className="px-2 py-1.5 border font-semibold">Nama Lengan</th>
                                                            <th className="px-2 py-1.5 border font-semibold text-center">No. Lengan</th>
                                                            <th className="px-2 py-1.5 border font-semibold text-center">No. Punggung 2</th>
                                                            <th className="px-2 py-1.5 border font-semibold text-center">Size</th>
                                                            <th className="px-2 py-1.5 border font-semibold">Keterangan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {item.namesets.map((ns, ni) => (
                                                            <tr key={ns.id} className="border-b hover:bg-slate-50">
                                                                <td className="px-2 py-1 border text-center font-bold text-slate-500">{ni + 1}</td>
                                                                <td className="px-2 py-1 border font-medium uppercase">{ns.nama_punggung || '—'}</td>
                                                                <td className="px-2 py-1 border text-center font-mono">{ns.nomor_punggung || '—'}</td>
                                                                <td className="px-2 py-1 border font-medium uppercase">{ns.nama_dada || '—'}</td>
                                                                <td className="px-2 py-1 border text-center font-mono">{ns.nomor_dada || '—'}</td>
                                                                <td className="px-2 py-1 border font-medium uppercase">{ns.nama_lengan || '—'}</td>
                                                                <td className="px-2 py-1 border text-center font-mono">{ns.nomor_lengan || '—'}</td>
                                                                <td className="px-2 py-1 border text-center font-mono">{ns.nomor_punggung_2 || '—'}</td>
                                                                <td className="px-2 py-1 border text-center">{ns.size ? `${ns.size.kategori_size}-${ns.size.ukuran}` : (ns.size_label || '—')}</td>
                                                                <td className="px-2 py-1 border text-muted-foreground">{ns.keterangan || '—'}</td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    )}
                                </div>
                            );
                        })}
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

                {/* Rijek & Refund Detailing */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <AlertTriangle className="h-4 w-4 text-amber-500" />
                            Detailing & Total Laporan Rijek & Refund
                        </CardTitle>
                        <CardDescription>
                            Ringkasan cacat produksi (rijek) dan pengembalian dana (refund) untuk PO ini.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {/* Summary Grid */}
                        <div className="grid grid-cols-2 gap-4 rounded-lg bg-muted/40 p-4">
                            <div className="text-center sm:text-left">
                                <div className="text-xs text-muted-foreground">Total Item Rijek</div>
                                <div className="mt-1 text-2xl font-bold text-amber-600 font-mono">
                                    {(order.rijeks ?? []).reduce((sum, r) => sum + Number(r.jumlah), 0)} pcs
                                </div>
                                <div className="text-[10px] text-muted-foreground mt-0.5">
                                    Dari {(order.rijeks ?? []).length} insiden produksi
                                </div>
                            </div>
                            <div className="text-center sm:text-left border-l pl-4">
                                <div className="text-xs text-muted-foreground">Total Refund Dana</div>
                                <div className="mt-1 text-2xl font-bold text-emerald-600 font-mono">
                                    {formatRupiah((order.refunds ?? []).reduce((sum, r) => sum + Number(r.nominal_refund), 0))}
                                </div>
                                <div className="text-[10px] text-muted-foreground mt-0.5">
                                    Dari {(order.refunds ?? []).length} pengajuan refund
                                </div>
                            </div>
                        </div>

                        {/* Rijek Detail Table */}
                        <div>
                            <h4 className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Rincian Rijek Produksi ({(order.rijeks ?? []).length})
                            </h4>
                            {order.rijeks?.length > 0 ? (
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="bg-muted/50 border-b text-muted-foreground">
                                                <th className="p-2">Tahapan</th>
                                                <th className="p-2">Jenis / Tingkat</th>
                                                <th className="p-2 text-right">Jumlah</th>
                                                <th className="p-2">Kendala</th>
                                                <th className="p-2">Penanganan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {order.rijeks.map((r) => (
                                                <tr key={r.id} className="border-b last:border-0 hover:bg-muted/30">
                                                    <td className="p-2 font-medium">{r.progress?.nama_progress ?? '—'}</td>
                                                    <td className="p-2">
                                                        <span className="capitalize">{r.jenis}</span>
                                                        <Badge variant={r.tingkat === 'berat' ? 'destructive' : r.tingkat === 'sedang' ? 'warning' : 'outline'} className="ml-1 text-[9px] py-0 px-1 font-semibold uppercase">
                                                            {r.tingkat}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-2 text-right font-mono font-semibold text-amber-600">{r.jumlah} pcs</td>
                                                    <td className="p-2 text-muted-foreground max-w-[150px] truncate" title={r.kendala}>{r.kendala}</td>
                                                    <td className="p-2 text-muted-foreground max-w-[150px] truncate" title={r.penanganan}>{r.penanganan || '—'}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-xs text-muted-foreground italic pl-1">Belum ada rijek tercatat pada PO ini.</p>
                            )}
                        </div>

                        {/* Refund Detail Table */}
                        <div>
                            <h4 className="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                Rincian Refund Dana ({(order.refunds ?? []).length})
                            </h4>
                            {order.refunds?.length > 0 ? (
                                <div className="overflow-x-auto rounded-md border">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="bg-muted/50 border-b text-muted-foreground">
                                                <th className="p-2">No Refund</th>
                                                <th className="p-2">Jenis Masalah</th>
                                                <th className="p-2">Alasan</th>
                                                <th className="p-2 text-right">Qty Item</th>
                                                <th className="p-2 text-right">Nominal</th>
                                                <th className="p-2">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {order.refunds.map((r) => (
                                                <tr key={r.id} className="border-b last:border-0 hover:bg-muted/30">
                                                    <td className="p-2 font-mono font-medium">{r.refund_number}</td>
                                                    <td className="p-2 capitalize">{r.jenis_masalah?.replace('_', ' ') || '—'}</td>
                                                    <td className="p-2 text-muted-foreground max-w-[150px] truncate" title={r.alasan}>{r.alasan}</td>
                                                    <td className="p-2 text-right font-mono">{r.jumlah_item} pcs</td>
                                                    <td className="p-2 text-right font-mono font-semibold text-emerald-600">{formatRupiah(r.nominal_refund)}</td>
                                                    <td className="p-2">
                                                        <Badge variant={r.status === 'published' ? 'success' : r.status === 'rejected' ? 'destructive' : 'warning'} className="text-[9px] py-0 px-1 font-semibold uppercase">
                                                            {r.status}
                                                        </Badge>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-xs text-muted-foreground italic pl-1">Belum ada pengajuan refund pada PO ini.</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

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
            <AddPaymentDialog order={order} open={openPayment} onOpenChange={setOpenPayment} banks={banks} jenis_pembayarans={jenis_pembayarans} />

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
