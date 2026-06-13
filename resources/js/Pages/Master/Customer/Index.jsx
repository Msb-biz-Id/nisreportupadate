import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Pencil, Trash2, Search, Users, Phone, Mail, MapPin, Upload, Download } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Separator } from '@/Components/ui/separator';
import RegionPicker from '@/Components/RegionPicker';
import { formatRupiah } from '@/lib/utils';

const NONE = '__none__';

function CustomerForm({ open, onOpenChange, customer, customerTypes, onSuccess }) {
    const isEdit = !!customer;
    const { data, setData, post, put, processing, errors, reset } = useForm({
        nama: customer?.nama ?? '',
        kode: customer?.kode ?? '',
        nomor_hp: customer?.nomor_hp ?? '',
        email: customer?.email ?? '',
        type_pelanggan_id: customer?.type_pelanggan_id ?? '',
        provinsi_code: customer?.provinsi_code ?? '',
        provinsi_nama: customer?.provinsi_nama ?? '',
        kabupaten_code: customer?.kabupaten_code ?? '',
        kabupaten_nama: customer?.kabupaten_nama ?? '',
        kecamatan_code: customer?.kecamatan_code ?? '',
        kecamatan_nama: customer?.kecamatan_nama ?? '',
        desa_code: customer?.desa_code ?? '',
        desa_nama: customer?.desa_nama ?? '',
        detail_alamat: customer?.detail_alamat ?? '',
        kodepos: customer?.kodepos ?? '',
        notes: customer?.notes ?? '',
        is_active: customer?.is_active ?? true,
    });

    function submit(e) {
        e.preventDefault();
        const opts = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onSuccess?.();
            },
        };
        if (isEdit) {
            put(route('master.pelanggan.update', customer.id), opts);
        } else {
            post(route('master.pelanggan.store'), opts);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{isEdit ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru'}</DialogTitle>
                        <DialogDescription>
                            Data pelanggan terisolasi per brand aktif. Kode otomatis di-generate jika kosong.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div>
                            <h4 className="mb-2 text-sm font-semibold text-muted-foreground">Identitas</h4>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <Label htmlFor="nama">Nama Pelanggan <span className="text-destructive">*</span></Label>
                                    <Input id="nama" value={data.nama} onChange={(e) => setData('nama', e.target.value)} className="mt-1.5" />
                                    {errors.nama && <p className="mt-1 text-xs text-destructive">{errors.nama}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="kode">Kode <span className="text-xs text-muted-foreground">(auto jika kosong)</span></Label>
                                    <Input id="kode" value={data.kode} onChange={(e) => setData('kode', e.target.value)} className="mt-1.5" placeholder="CUST-00001" />
                                    {errors.kode && <p className="mt-1 text-xs text-destructive">{errors.kode}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="nomor_hp">No. HP <span className="text-destructive">*</span></Label>
                                    <Input id="nomor_hp" value={data.nomor_hp} onChange={(e) => setData('nomor_hp', e.target.value)} className="mt-1.5" />
                                    {errors.nomor_hp && <p className="mt-1 text-xs text-destructive">{errors.nomor_hp}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="email">Email</Label>
                                    <Input id="email" type="email" value={data.email ?? ''} onChange={(e) => setData('email', e.target.value)} className="mt-1.5" />
                                </div>
                                <div>
                                    <Label htmlFor="type_pelanggan_id">Kategori Pelanggan</Label>
                                    <Select
                                        value={data.type_pelanggan_id || NONE}
                                        onValueChange={(v) => setData('type_pelanggan_id', v === NONE ? '' : v)}
                                    >
                                        <SelectTrigger id="type_pelanggan_id" className="mt-1.5"><SelectValue placeholder="Pilih Kategori" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak ada —</SelectItem>
                                            {customerTypes.map((t) => (
                                                <SelectItem key={t.id} value={t.id}>{t.nama}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <div>
                            <h4 className="mb-2 text-sm font-semibold text-muted-foreground">Alamat</h4>
                            <RegionPicker value={data} onChange={(patch) => Object.keys(patch).forEach((k) => setData(k, patch[k]))} />
                            <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <Label htmlFor="detail_alamat">Detail Alamat (RT/RW, Nama Jalan, dll)</Label>
                                    <Textarea id="detail_alamat" value={data.detail_alamat ?? ''} onChange={(e) => setData('detail_alamat', e.target.value)} rows={2} className="mt-1.5" />
                                </div>
                                <div>
                                    <Label htmlFor="kodepos">Kode Pos</Label>
                                    <Input id="kodepos" value={data.kodepos ?? ''} onChange={(e) => setData('kodepos', e.target.value)} className="mt-1.5" />
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <div>
                            <Label htmlFor="notes">Catatan Internal</Label>
                            <Textarea id="notes" value={data.notes ?? ''} onChange={(e) => setData('notes', e.target.value)} rows={2} className="mt-1.5" />
                        </div>

                        <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                            <div>
                                <Label htmlFor="is_active" className="text-sm">Pelanggan Aktif</Label>
                                <p className="text-xs text-muted-foreground">Pelanggan non-aktif tidak muncul di pencarian saat input PO.</p>
                            </div>
                            <Switch id="is_active" checked={data.is_active} onCheckedChange={(v) => setData('is_active', v)} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>Batal</Button>
                        <Button type="submit" disabled={processing}>{isEdit ? 'Simpan Perubahan' : 'Tambah Pelanggan'}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function ImportModal({ open, onOpenChange }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null,
    });

    function submit(e) {
        e.preventDefault();
        if (!data.file) return;
        post(route('master.pelanggan.import'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Import Pelanggan</DialogTitle>
                        <DialogDescription>
                            Unggah file CSV dengan format baku untuk mengimpor data pelanggan secara massal. Brand/reseller harus sudah terdaftar di sistem.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="rounded-lg border bg-muted/30 p-4">
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <div className="text-sm font-medium">Format Baku (Template)</div>
                                    <div className="text-xs text-muted-foreground">Unduh format kolom yang diperlukan.</div>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => window.open(route('master.pelanggan.import-template'), '_blank')}
                                >
                                    <Download className="mr-2 h-4 w-4" /> Unduh CSV
                                </Button>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="file">File CSV</Label>
                            <Input
                                id="file"
                                type="file"
                                accept=".csv"
                                onChange={(e) => setData('file', e.target.files[0])}
                                className="mt-1"
                            />
                            {errors.file && <p className="text-xs text-destructive">{errors.file}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>Batal</Button>
                        <Button type="submit" disabled={processing || !data.file}>
                            {processing ? 'Mengimpor...' : 'Mulai Import'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function CustomerIndex({ items, filters, customerTypes, can }) {
    const [openForm, setOpenForm] = useState(false);
    const [openImport, setOpenImport] = useState(false);
    const [editing, setEditing] = useState(null);
    const [confirmDelete, setConfirmDelete] = useState(null);
    const [search, setSearch] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');

    function applyFilters(overrides = {}) {
        router.get(route('master.pelanggan.index'), {
            q: overrides.q ?? search,
            status: (overrides.status ?? status) === 'all' ? '' : (overrides.status ?? status),
        }, { preserveScroll: true, preserveState: true });
    }

    function doDelete() {
        if (!confirmDelete) return;
        router.delete(route('master.pelanggan.destroy', confirmDelete.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmDelete(null),
        });
    }

    return (
        <AppLayout title="Master Pelanggan">
            <Head title="Master Pelanggan" />

            <div className="space-y-5">
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <Users className="h-5 w-5 text-primary" /> Master Pelanggan
                            </div>
                            <p className="text-sm text-muted-foreground">Pelanggan terisolasi per brand aktif. Reseller menggunakan database global.</p>
                        </div>
                        {can?.manage && (
                            <div className="flex flex-wrap items-center gap-2">
                                {can?.import && (
                                    <Button variant="outline" onClick={() => setOpenImport(true)}>
                                        <Upload className="h-4 w-4 mr-1" /> Import CSV
                                    </Button>
                                )}
                                <Button onClick={() => { setEditing(null); setOpenForm(true); }}>
                                    <Plus className="h-4 w-4 mr-1" /> Tambah Pelanggan
                                </Button>
                            </div>
                        )}
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama, kode, atau no. HP..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                <SelectTrigger className="sm:w-44"><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="active">Aktif</SelectItem>
                                    <SelectItem value="inactive">Non-Aktif</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button variant="outline" onClick={() => applyFilters()}>Terapkan</Button>
                        </div>

                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead>Kontak</TableHead>
                                        <TableHead>Wilayah</TableHead>
                                        <TableHead className="text-right">Total Order</TableHead>
                                        <TableHead className="text-right">Total Transaksi</TableHead>
                                        <TableHead>Status</TableHead>
                                        {can?.manage && <TableHead className="w-24 text-right">Aksi</TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={can?.manage ? 7 : 6} className="py-8 text-center text-sm text-muted-foreground">
                                                Belum ada pelanggan.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {items.data.map((c) => (
                                        <TableRow key={c.id}>
                                            <TableCell>
                                                <div className="font-medium">{c.nama}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {c.kode} {c.customer_type ? `· ${c.customer_type.nama}` : ''}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                <div className="flex items-center gap-1.5"><Phone className="h-3 w-3 text-muted-foreground" /> {c.nomor_hp}</div>
                                                {c.email && <div className="flex items-center gap-1.5 text-xs text-muted-foreground"><Mail className="h-3 w-3" /> {c.email}</div>}
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {c.kabupaten_nama ? (
                                                    <div className="flex items-start gap-1">
                                                        <MapPin className="mt-0.5 h-3 w-3 text-muted-foreground" />
                                                        <span>{c.kabupaten_nama}<br /><span className="text-muted-foreground">{c.provinsi_nama}</span></span>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right font-mono">{c.total_order ?? 0}</TableCell>
                                            <TableCell className="text-right font-mono text-xs">{formatRupiah(c.total_transaksi)}</TableCell>
                                            <TableCell>
                                                <Badge variant={c.is_active ? 'success' : 'secondary'}>{c.is_active ? 'Aktif' : 'Non-Aktif'}</Badge>
                                            </TableCell>
                                            {can?.manage && (
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button size="icon" variant="ghost" onClick={() => { setEditing(c); setOpenForm(true); }}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button size="icon" variant="ghost" className="text-destructive hover:text-destructive" onClick={() => setConfirmDelete(c)}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {items.last_page > 1 && (
                            <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span className="text-muted-foreground">
                                    Menampilkan {items.from ?? 0}–{items.to ?? 0} dari {items.total} data
                                </span>
                                <div className="flex gap-1">
                                    {items.links.map((link, i) => (
                                        <Button
                                            key={i}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {can?.manage && (
                <>
                    <CustomerForm
                        key={editing?.id ?? 'new'}
                        open={openForm}
                        onOpenChange={setOpenForm}
                        customer={editing}
                        customerTypes={customerTypes}
                        onSuccess={() => setOpenForm(false)}
                    />
                    <ImportModal
                        open={openImport}
                        onOpenChange={setOpenImport}
                    />
                </>
            )}

            <Dialog open={!!confirmDelete} onOpenChange={(v) => !v && setConfirmDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Pelanggan?</DialogTitle>
                        <DialogDescription>
                            Pelanggan <span className="font-semibold">{confirmDelete?.nama}</span> akan dihapus (soft delete).
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
