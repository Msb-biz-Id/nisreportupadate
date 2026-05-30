import { Head, Link, useForm, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { ArrowLeft, AlertTriangle, Play, CheckCircle2, SkipForward, Plus, Edit, Trash2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatDateTime } from '@/lib/utils';

const STATUS_VARIANT = {
    pending: 'outline',
    on_progress: 'warning',
    selesai: 'success',
    skipped: 'secondary',
};

function UpdateModal({ order, detail, open, onOpenChange }) {
    const isSending = detail?.progress?.nama_progress?.toUpperCase() === 'SENDING';

    const { data, setData, put, processing, errors, reset } = useForm({
        status: detail?.status ?? 'pending',
        catatan: detail?.catatan ?? '',
        kendala: detail?.kendala ?? '',
        skipped_reason: detail?.skipped_reason ?? '',
        nama_ekspedisi: order?.nama_ekspedisi ?? '',
        no_resi: order?.no_resi ?? '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('produksi.progress.update', { order: order.id, detail: detail.id }), {
            preserveScroll: true,
            onSuccess: () => { reset(); onOpenChange(false); },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Update {detail?.progress?.nama_progress}</DialogTitle>
                        <DialogDescription>
                            Pilih status baru untuk tahapan ini. Catatan wajib diisi untuk transisi status.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-3 py-4">
                        <div>
                            <Label>Status</Label>
                            <Select value={data.status} onValueChange={(v) => setData('status', v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="on_progress">On Progress</SelectItem>
                                    <SelectItem value="selesai">Selesai</SelectItem>
                                    <SelectItem value="skipped">Skipped</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Catatan {data.status !== 'pending' && <span className="text-destructive">*</span>}</Label>
                            <Textarea value={data.catatan} onChange={(e) => setData('catatan', e.target.value)} rows={3} className="mt-1.5" placeholder="Apa yang dikerjakan / hasil pengerjaan" />
                            {errors.catatan && <p className="mt-1 text-xs text-destructive">{errors.catatan}</p>}
                        </div>
                        <div>
                            <Label>Kendala (opsional)</Label>
                            <Textarea value={data.kendala} onChange={(e) => setData('kendala', e.target.value)} rows={2} className="mt-1.5" placeholder="Hambatan/masalah jika ada" />
                        </div>
                        {data.status === 'skipped' && (
                            <div>
                                <Label>Alasan Skip <span className="text-destructive">*</span></Label>
                                <Textarea value={data.skipped_reason} onChange={(e) => setData('skipped_reason', e.target.value)} rows={2} className="mt-1.5" />
                                {errors.skipped_reason && <p className="mt-1 text-xs text-destructive">{errors.skipped_reason}</p>}
                            </div>
                        )}
                        {isSending && data.status === 'selesai' && (
                            <div className="rounded-lg border border-violet-200 bg-violet-50 p-3 space-y-3">
                                <p className="text-xs font-black text-violet-700 uppercase tracking-wide">Data Pengiriman</p>
                                <div>
                                    <Label>Nama Ekspedisi <span className="text-destructive">*</span></Label>
                                    <Input value={data.nama_ekspedisi} onChange={(e) => setData('nama_ekspedisi', e.target.value)} placeholder="JNE / J&T / SiCepat / dll" className="mt-1.5" />
                                    {errors.nama_ekspedisi && <p className="mt-1 text-xs text-destructive">{errors.nama_ekspedisi}</p>}
                                </div>
                                <div>
                                    <Label>Nomor Resi</Label>
                                    <Input value={data.no_resi} onChange={(e) => setData('no_resi', e.target.value)} placeholder="Nomor resi pengiriman" className="mt-1.5 font-mono" />
                                </div>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                        <Button type="submit" disabled={processing}>Update</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function RijekModal({ order, open, onOpenChange, progressOptions, rijek = null }) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        progress_id: '', jumlah: 1, jenis: 'jahit', tingkat: 'ringan',
        kendala: '', penanganan: '',
    });

    useEffect(() => {
        if (open) {
            if (rijek) {
                setData({
                    progress_id: rijek.progress_id || '',
                    jumlah: rijek.jumlah || 1,
                    jenis: rijek.jenis || 'jahit',
                    tingkat: rijek.tingkat || 'ringan',
                    kendala: rijek.kendala || '',
                    penanganan: rijek.penanganan || '',
                });
            } else {
                setData({
                    progress_id: '',
                    jumlah: 1,
                    jenis: 'jahit',
                    tingkat: 'ringan',
                    kendala: '',
                    penanganan: '',
                });
            }
        }
    }, [open, rijek]);

    function submit(e) {
        e.preventDefault();
        if (rijek) {
            put(route('produksi.rijek.update', { order: order.id, rijek: rijek.id }), {
                preserveScroll: true,
                onSuccess: () => { onOpenChange(false); },
            });
        } else {
            post(route('produksi.rijek.store', order.id), {
                preserveScroll: true,
                onSuccess: () => { reset(); onOpenChange(false); },
            });
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{rijek ? 'Edit Rijek' : 'Tambah Rijek'}</DialogTitle>
                        <DialogDescription>
                            {rijek ? 'Ubah data barang rijek/cacat.' : 'Catat barang rijek/cacat untuk PO ini.'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid grid-cols-1 gap-3 py-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Label>Tahapan</Label>
                            <Select value={data.progress_id || '__none__'} onValueChange={(v) => setData('progress_id', v === '__none__' ? '' : v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__none__">— Tidak terkait tahap —</SelectItem>
                                    {progressOptions.map((p) => (<SelectItem key={p.id} value={p.id}>{p.nama_progress}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Jumlah</Label>
                            <Input type="number" min={1} value={data.jumlah} onChange={(e) => setData('jumlah', Number(e.target.value))} className="mt-1.5" />
                        </div>
                        <div>
                            <Label>Jenis</Label>
                            <Select value={data.jenis} onValueChange={(v) => setData('jenis', v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="sablon">Sablon</SelectItem>
                                    <SelectItem value="printing">Printing</SelectItem>
                                    <SelectItem value="jahit">Jahit</SelectItem>
                                    <SelectItem value="ukuran">Ukuran</SelectItem>
                                    <SelectItem value="lain">Lain</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="sm:col-span-2">
                            <Label>Tingkat</Label>
                            <Select value={data.tingkat} onValueChange={(v) => setData('tingkat', v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ringan">Ringan</SelectItem>
                                    <SelectItem value="sedang">Sedang</SelectItem>
                                    <SelectItem value="berat">Berat</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="sm:col-span-2">
                            <Label>Kendala <span className="text-destructive">*</span></Label>
                            <Textarea value={data.kendala} onChange={(e) => setData('kendala', e.target.value)} rows={2} className="mt-1.5" />
                            {errors.kendala && <p className="mt-1 text-xs text-destructive">{errors.kendala}</p>}
                        </div>
                        <div className="sm:col-span-2">
                            <Label>Penanganan</Label>
                            <Textarea value={data.penanganan} onChange={(e) => setData('penanganan', e.target.value)} rows={2} className="mt-1.5" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                        <Button type="submit" disabled={processing}>{rijek ? 'Simpan Perubahan' : 'Catat Rijek'}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function ProgressPage({ order, can }) {
    const [updating, setUpdating] = useState(null);
    const [openRijek, setOpenRijek] = useState(false);
    const [editingRijek, setEditingRijek] = useState(null);

    const sortedDetails = (order.progress_details ?? []).slice().sort(
        (a, b) => (a.progress?.urutan ?? 0) - (b.progress?.urutan ?? 0)
    );

    const progressOptions = sortedDetails.map((d) => d.progress).filter(Boolean);
    const isSent = order.status_po === 'sudah_dikirim';

    return (
        <AppLayout title={`Progress ${order.no_po}`}>
            <Head title={`Progress ${order.no_po}`} />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('orders.show', order.id)}><ArrowLeft className="h-4 w-4" /> Preview PO</Link>
                    </Button>
                    {can?.addReject && !isSent && (
                        <Button variant="outline" size="sm" onClick={() => { setEditingRijek(null); setOpenRijek(true); }}>
                            <AlertTriangle className="h-4 w-4" /> Catat Rijek
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Update Progress Per-Tahapan</CardTitle>
                        <CardDescription>
                            <span className="font-mono">{order.no_po}</span> — {order.nama_po}.
                            PO ter-lock otomatis saat tahap pertama jadi <em>On Progress</em>.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {sortedDetails.map((d) => {
                            const variant = STATUS_VARIANT[d.status] ?? 'outline';
                            return (
                                <div key={d.id} className="flex flex-wrap items-center gap-3 rounded-lg border p-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white" style={{ background: d.progress?.warna || '#3B82F6' }}>
                                        {d.progress?.urutan}
                                    </div>
                                    <div className="flex-1">
                                        <div className="font-medium">{d.progress?.nama_progress}</div>
                                        {d.catatan && <p className="text-xs text-muted-foreground">{d.catatan}</p>}
                                        {d.kendala && <p className="text-xs text-destructive">⚠ {d.kendala}</p>}
                                        {d.completed_at && <p className="text-[10px] text-muted-foreground">Selesai {formatDateTime(d.completed_at)}</p>}
                                    </div>
                                    <Badge variant={variant}>{d.status}</Badge>
                                    {d.has_reject && <Badge variant="destructive"><AlertTriangle className="mr-1 h-3 w-3" />Reject</Badge>}
                                    {can?.update && (
                                        <Button size="sm" variant="outline" onClick={() => setUpdating(d)}>Update</Button>
                                    )}
                                </div>
                            );
                        })}
                        {sortedDetails.length === 0 && (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                Belum ada progress. PO mungkin masih draft — terbitkan dulu agar progress di-init.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <div>
                            <CardTitle>Daftar Rijek PO</CardTitle>
                            <CardDescription>Cacat produksi yang tercatat pada PO ini.</CardDescription>
                        </div>
                        {can?.addReject && !isSent && (
                            <Button size="sm" onClick={() => { setEditingRijek(null); setOpenRijek(true); }}>
                                <Plus className="mr-1 h-4 w-4" /> Catat Rijek
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b text-muted-foreground">
                                        <th className="py-2">Tahapan</th>
                                        <th className="py-2">Jenis / Tingkat</th>
                                        <th className="py-2 text-right">Jumlah</th>
                                        <th className="py-2">Kendala</th>
                                        <th className="py-2">Penanganan</th>
                                        <th className="py-2">Oleh</th>
                                        {can?.addReject && !isSent && <th className="py-2 text-right">Aksi</th>}
                                    </tr>
                                </thead>
                                <tbody>
                                    {(order.rijeks ?? []).map((r) => (
                                        <tr key={r.id} className="border-b hover:bg-muted/50">
                                            <td className="py-2.5 font-medium">{r.progress?.nama_progress ?? '—'}</td>
                                            <td className="py-2.5">
                                                <div className="flex gap-1.5 items-center">
                                                    <span className="capitalize">{r.jenis}</span>
                                                    <Badge variant={
                                                        r.tingkat === 'berat' ? 'destructive' :
                                                        r.tingkat === 'sedang' ? 'warning' : 'outline'
                                                    } className="text-[10px] py-0 px-1.5 uppercase font-semibold">
                                                        {r.tingkat}
                                                    </Badge>
                                                </div>
                                            </td>
                                            <td className="py-2.5 text-right font-mono font-bold text-amber-600">{r.jumlah} pcs</td>
                                            <td className="py-2.5 text-muted-foreground max-w-xs truncate" title={r.kendala}>{r.kendala}</td>
                                            <td className="py-2.5 text-muted-foreground max-w-xs truncate" title={r.penanganan}>{r.penanganan || '—'}</td>
                                            <td className="py-2.5 text-xs text-muted-foreground">
                                                <div>{r.creator?.name || 'Sistem'}</div>
                                                {r.created_at && <div className="text-[10px] opacity-75">{formatDateTime(r.created_at)}</div>}
                                            </td>
                                            {can?.addReject && !isSent && (
                                                <td className="py-2.5 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button size="icon" variant="ghost" className="h-8 w-8 text-blue-600 hover:text-blue-700" onClick={() => {
                                                            setEditingRijek(r);
                                                            setOpenRijek(true);
                                                        }}>
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                        <Button size="icon" variant="ghost" className="h-8 w-8 text-destructive hover:text-destructive/90" onClick={() => {
                                                            if (confirm('Apakah Anda yakin ingin menghapus data rijek ini?')) {
                                                                router.delete(route('produksi.rijek.destroy', { order: order.id, rijek: r.id }), {
                                                                    preserveScroll: true
                                                                });
                                                            }
                                                        }}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                    {(order.rijeks ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={can?.addReject && !isSent ? 7 : 6} className="py-8 text-center text-muted-foreground text-sm">
                                                Tidak ada rijek tercatat.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {updating && (
                <UpdateModal
                    key={updating.id}
                    order={order}
                    detail={updating}
                    open={!!updating}
                    onOpenChange={(v) => !v && setUpdating(null)}
                />
            )}
            <RijekModal
                order={order}
                open={openRijek}
                onOpenChange={(v) => {
                    setOpenRijek(v);
                    if (!v) setEditingRijek(null);
                }}
                progressOptions={progressOptions}
                rijek={editingRijek}
            />
        </AppLayout>
    );
}
