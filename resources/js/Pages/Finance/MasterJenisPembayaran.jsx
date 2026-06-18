import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { Search, Plus, Edit, Trash2 } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';

export default function MasterJenisPembayaran({ items, filters }) {
    const [search, setSearch] = useState(filters.q || '');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingId, setEditingId] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        nama: '',
        tipe_keuangan: 'pemasukan',
        efek_tagihan: 'netral',
        deskripsi: '',
        is_active: true,
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('master-pembayaran.index'), { q: search }, { preserveState: true });
    };

    const openCreateDialog = () => {
        reset();
        clearErrors();
        setEditingId(null);
        setDialogOpen(true);
    };

    const openEditDialog = (item) => {
        clearErrors();
        setEditingId(item.id);
        setData({
            nama: item.nama,
            tipe_keuangan: item.tipe_keuangan,
            efek_tagihan: item.efek_tagihan,
            deskripsi: item.deskripsi || '',
            is_active: item.is_active,
        });
        setDialogOpen(true);
    };

    const handleDelete = (item) => {
        if (confirm(`Hapus master pembayaran ${item.nama}?`)) {
            router.delete(route('master-pembayaran.destroy', item.id));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (editingId) {
            put(route('master-pembayaran.update', editingId), {
                onSuccess: () => setDialogOpen(false),
            });
        } else {
            post(route('master-pembayaran.store'), {
                onSuccess: () => setDialogOpen(false),
            });
        }
    };

    return (
        <AppLayout title="Master Data Pembayaran">
            <Head title="Master Data Pembayaran" />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-4">
                <h1 className="text-2xl font-bold tracking-tight text-slate-900">Master Data Pembayaran</h1>
                <Button onClick={openCreateDialog}>
                    <Plus className="mr-2 h-4 w-4" /> Tambah Pembayaran
                </Button>
            </div>

            <Card>
                <CardHeader className="p-4 sm:p-6 pb-0">
                    <div className="flex flex-col sm:flex-row justify-between gap-4">
                        <CardTitle className="text-lg">Daftar Jenis Pembayaran</CardTitle>
                        <form onSubmit={handleSearch} className="flex gap-2 relative max-w-sm w-full">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input 
                                placeholder="Cari nama..." 
                                className="pl-9" 
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                            />
                            <Button type="submit" variant="secondary">Cari</Button>
                        </form>
                    </div>
                </CardHeader>
                <CardContent className="p-0 mt-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-6 w-[200px]">Nama Pembayaran</TableHead>
                                <TableHead className="min-w-[250px]">Deskripsi / Penjelasan</TableHead>
                                <TableHead>Tipe Keuangan</TableHead>
                                <TableHead>Efek Tagihan PO</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right pr-6">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">Tidak ada data pembayaran.</TableCell>
                                </TableRow>
                            ) : items.data.map(item => (
                                <TableRow key={item.id}>
                                    <TableCell className="pl-6 font-medium">{item.nama}</TableCell>
                                    <TableCell className="text-xs text-slate-500 max-w-xs truncate" title={item.deskripsi}>{item.deskripsi || '—'}</TableCell>
                                    <TableCell>
                                        <Badge variant={item.tipe_keuangan === 'pemasukan' ? 'success' : 'destructive'}>
                                            {item.tipe_keuangan.toUpperCase()}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={item.efek_tagihan === 'netral' ? 'outline' : (item.efek_tagihan === 'penambahan' ? 'success' : 'destructive')}>
                                            {item.efek_tagihan.toUpperCase()}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {item.is_active ? (
                                            <span className="text-emerald-600 font-semibold text-xs">Aktif</span>
                                        ) : (
                                            <span className="text-slate-400 font-semibold text-xs">Nonaktif</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right pr-6 space-x-2">
                                        <Button variant="ghost" size="icon" onClick={() => openEditDialog(item)}>
                                            <Edit className="h-4 w-4 text-blue-600" />
                                        </Button>
                                        <Button variant="ghost" size="icon" onClick={() => handleDelete(item)}>
                                            <Trash2 className="h-4 w-4 text-red-600" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>{editingId ? 'Edit' : 'Tambah'} Jenis Pembayaran</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div>
                                <Label>Nama Pembayaran</Label>
                                <Input value={data.nama} onChange={e => setData('nama', e.target.value)} placeholder="Contoh: Ongkir, Cashback, DP" className="mt-1" />
                                {errors.nama && <p className="text-xs text-destructive mt-1">{errors.nama}</p>}
                            </div>
                            <div>
                                <Label>Tipe Keuangan (Buku Kas)</Label>
                                <Select value={data.tipe_keuangan} onValueChange={v => setData('tipe_keuangan', v)}>
                                    <SelectTrigger className="mt-1"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pemasukan">Pemasukan (Dana Masuk)</SelectItem>
                                        <SelectItem value="pengeluaran">Pengeluaran (Dana Keluar)</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.tipe_keuangan && <p className="text-xs text-destructive mt-1">{errors.tipe_keuangan}</p>}
                            </div>
                            <div>
                                <Label>Efek Tagihan PO</Label>
                                <Select value={data.efek_tagihan} onValueChange={v => setData('efek_tagihan', v)}>
                                    <SelectTrigger className="mt-1"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="penambahan">Menambah Harga Tagihan (Contoh: Ongkir)</SelectItem>
                                        <SelectItem value="pengurangan">Mengurangi Harga Tagihan (Contoh: Return / Diskon)</SelectItem>
                                        <SelectItem value="netral">Netral / Hanya Membayar (Contoh: DP / Pelunasan)</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.efek_tagihan && <p className="text-xs text-destructive mt-1">{errors.efek_tagihan}</p>}
                            </div>
                            <div>
                                                                <Label>Deskripsi / Penjelasan</Label>
                                                                <Textarea value={data.deskripsi} onChange={e => setData('deskripsi', e.target.value)} placeholder="Contoh: Pembayaran uang muka untuk memulai proses pengerjaan order." className="mt-1 text-xs" rows={2} />
                                                                {errors.deskripsi && <p className="text-xs text-destructive mt-1">{errors.deskripsi}</p>}
                                                            </div>
                                                            <div className="flex items-center space-x-2 pt-2">
                                <Switch checked={data.is_active} onCheckedChange={c => setData('is_active', c)} />
                                <Label className="font-normal cursor-pointer text-sm">Aktif digunakan</Label>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" onClick={() => setDialogOpen(false)}>Batal</Button>
                            <Button type="submit" disabled={processing}>Simpan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
