import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, AlertTriangle, Play, CheckCircle2, SkipForward, Plus } from 'lucide-react';
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

function RijekModal({ order, open, onOpenChange, progressOptions }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        progress_id: '', jumlah: 1, jenis: 'jahit', tingkat: 'ringan',
        kendala: '', penanganan: '', biaya_ganti: 0,
    });

    function submit(e) {
        e.preventDefault();
        post(route('produksi.rijek.store', order.id), {
            preserveScroll: true,
            onSuccess: () => { reset(); onOpenChange(false); },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Tambah Rijek</DialogTitle>
                        <DialogDescription>Catat barang rijek/cacat untuk PO ini.</DialogDescription>
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
                        <div>
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
                        <div>
                            <Label>Biaya Ganti</Label>
                            <Input type="number" min={0} value={data.biaya_ganti} onChange={(e) => setData('biaya_ganti', Number(e.target.value))} className="mt-1.5" />
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
                        <Button type="submit" disabled={processing}>Catat</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function ProgressPage({ order, can }) {
    const [updating, setUpdating] = useState(null);
    const [openRijek, setOpenRijek] = useState(false);

    const sortedDetails = (order.progress_details ?? []).slice().sort(
        (a, b) => (a.progress?.urutan ?? 0) - (b.progress?.urutan ?? 0)
    );

    const progressOptions = sortedDetails.map((d) => d.progress).filter(Boolean);

    return (
        <AppLayout title={`Progress ${order.no_po}`}>
            <Head title={`Progress ${order.no_po}`} />

            <div className="space-y-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('orders.show', order.id)}><ArrowLeft className="h-4 w-4" /> Preview PO</Link>
                    </Button>
                    {can?.addReject && (
                        <Button variant="outline" size="sm" onClick={() => setOpenRijek(true)}>
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
            <RijekModal order={order} open={openRijek} onOpenChange={setOpenRijek} progressOptions={progressOptions} />
        </AppLayout>
    );
}
