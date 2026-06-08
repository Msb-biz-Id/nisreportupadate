import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Pencil, Trash2, Power, Search, Building2 } from 'lucide-react';
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
import { formatDate } from '@/lib/utils';

function BrandForm({ open, onOpenChange, brand, onSuccess, isAdminReseller = false }) {
    const isEdit = !!brand;
    const { data, setData, post, put, processing, errors, reset } = useForm({
        nama_brand: brand?.nama_brand ?? '',
        kode: brand?.kode ?? '',
        tagline: brand?.tagline ?? '',
        deskripsi: brand?.deskripsi ?? '',
        logo: null,
        email: brand?.email ?? '',
        no_hp: brand?.no_hp ?? '',
        alamat: brand?.alamat ?? '',
        instagram: brand?.instagram ?? '',
        facebook: brand?.facebook ?? '',
        tiktok: brand?.tiktok ?? '',
        whatsapp: brand?.whatsapp ?? '',
        website: brand?.website ?? '',
        warna_primary: brand?.warna_primary ?? '#3B82F6',
        timezone: brand?.timezone ?? 'Asia/Jakarta',
        currency: brand?.currency ?? 'IDR',
        is_active: brand?.is_active ?? true,
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
            router.post(route('brands.update', brand.id), {
                ...data,
                _method: 'PUT'
            }, opts);
        } else {
            post(route('brands.store'), opts);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>
                            {isEdit
                                ? (isAdminReseller ? 'Edit Reseller' : 'Edit Brand')
                                : (isAdminReseller ? '+ Tambah Reseller Baru' : 'Tambah Brand Baru')
                            }
                        </DialogTitle>
                        <DialogDescription>
                            {isAdminReseller
                                ? 'Reseller baru akan ditambahkan ke daftar dan bisa langsung dikelola. Setiap reseller memiliki order, invoice, dan laporan terpisah.'
                                : 'Brand bersifat terisolasi — semua master data, order, dan laporan dipisahkan per brand.'
                            }
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Label htmlFor="nama_brand">Nama Brand <span className="text-destructive">*</span></Label>
                            <Input
                                id="nama_brand"
                                value={data.nama_brand}
                                onChange={(e) => setData('nama_brand', e.target.value)}
                                placeholder="Contoh: Shubuh Apparel"
                                className="mt-1.5"
                            />
                            {errors.nama_brand && <p className="mt-1 text-xs text-destructive">{errors.nama_brand}</p>}
                        </div>

                        <div>
                            <Label htmlFor="kode">Kode <span className="text-destructive">*</span></Label>
                            <Input
                                id="kode"
                                value={data.kode}
                                onChange={(e) => setData('kode', e.target.value.toUpperCase())}
                                placeholder="SHU"
                                maxLength={20}
                                className="mt-1.5"
                            />
                            {errors.kode && <p className="mt-1 text-xs text-destructive">{errors.kode}</p>}
                        </div>

                        <div>
                            <Label htmlFor="warna_primary">Warna Utama</Label>
                            <div className="mt-1.5 flex items-center gap-2">
                                <Input
                                    id="warna_primary"
                                    type="color"
                                    value={data.warna_primary}
                                    onChange={(e) => setData('warna_primary', e.target.value)}
                                    className="h-9 w-14 cursor-pointer p-1"
                                />
                                <Input
                                    value={data.warna_primary}
                                    onChange={(e) => setData('warna_primary', e.target.value)}
                                    placeholder="#3B82F6"
                                />
                            </div>
                        </div>

                        {/* Logo Upload Field */}
                        <div className="sm:col-span-2 space-y-2">
                            <Label>Logo Brand</Label>
                            <div className="flex items-center gap-4 p-3 rounded-lg border bg-slate-50/50">
                                {brand?.logo && !data.logo && (
                                    <div className="relative h-16 w-16 overflow-hidden rounded-lg border bg-white flex items-center justify-center p-1 shadow-sm">
                                        <img 
                                            src={`/storage/${brand.logo}`} 
                                            alt="Current Logo" 
                                            className="h-full w-full object-contain"
                                        />
                                    </div>
                                )}
                                {data.logo && (
                                    <div className="relative h-16 w-16 overflow-hidden rounded-lg border bg-white flex items-center justify-center p-1 shadow-sm">
                                        <img 
                                            src={URL.createObjectURL(data.logo)} 
                                            alt="New Logo Preview" 
                                            className="h-full w-full object-contain"
                                        />
                                    </div>
                                )}
                                {!brand?.logo && !data.logo && (
                                    <div className="h-16 w-16 rounded-lg border-2 border-dashed border-slate-200 bg-white flex items-center justify-center text-slate-400">
                                        <Building2 className="h-6 w-6" />
                                    </div>
                                )}
                                <div className="flex-1 space-y-1">
                                    <input 
                                        type="file" 
                                        id="logo-upload"
                                        accept="image/*"
                                        onChange={(e) => setData('logo', e.target.files[0])}
                                        className="hidden" 
                                    />
                                    <Label 
                                        htmlFor="logo-upload"
                                        className="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                    >
                                        Pilih Gambar Logo
                                    </Label>
                                    <p className="text-[10px] text-muted-foreground">Format PNG, JPG, JPEG. Maksimal 2MB.</p>
                                </div>
                            </div>
                            {errors.logo && <p className="text-xs text-destructive">{errors.logo}</p>}
                        </div>

                        <div className="sm:col-span-2">
                            <Label htmlFor="tagline">Tagline</Label>
                            <Input
                                id="tagline"
                                value={data.tagline}
                                onChange={(e) => setData('tagline', e.target.value)}
                                placeholder="Custom Jersey Bermutu Tinggi"
                                className="mt-1.5"
                            />
                        </div>

                        <div className="sm:col-span-2">
                            <Label htmlFor="deskripsi">Deskripsi</Label>
                            <Textarea
                                id="deskripsi"
                                value={data.deskripsi}
                                onChange={(e) => setData('deskripsi', e.target.value)}
                                rows={3}
                                className="mt-1.5"
                            />
                        </div>

                        <div>
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="mt-1.5"
                            />
                            {errors.email && <p className="mt-1 text-xs text-destructive">{errors.email}</p>}
                        </div>

                        <div>
                            <Label htmlFor="no_hp">No. HP</Label>
                            <Input
                                id="no_hp"
                                value={data.no_hp}
                                onChange={(e) => setData('no_hp', e.target.value)}
                                className="mt-1.5"
                            />
                        </div>

                        <div className="sm:col-span-2">
                            <Label htmlFor="alamat">Alamat</Label>
                            <Textarea
                                id="alamat"
                                value={data.alamat}
                                onChange={(e) => setData('alamat', e.target.value)}
                                rows={2}
                                className="mt-1.5"
                            />
                        </div>

                        <div className="sm:col-span-2">
                            <Label className="text-xs font-bold text-slate-500 uppercase tracking-wider">Media Sosial</Label>
                            <div className="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <Label htmlFor="instagram" className="text-xs">Instagram</Label>
                                    <Input id="instagram" value={data.instagram} onChange={(e) => setData('instagram', e.target.value)} placeholder="@namaakun" className="mt-1" />
                                </div>
                                <div>
                                    <Label htmlFor="tiktok" className="text-xs">TikTok</Label>
                                    <Input id="tiktok" value={data.tiktok} onChange={(e) => setData('tiktok', e.target.value)} placeholder="@namaakun" className="mt-1" />
                                </div>
                                <div>
                                    <Label htmlFor="facebook" className="text-xs">Facebook</Label>
                                    <Input id="facebook" value={data.facebook} onChange={(e) => setData('facebook', e.target.value)} placeholder="Nama Page" className="mt-1" />
                                </div>
                                <div>
                                    <Label htmlFor="whatsapp" className="text-xs">WhatsApp</Label>
                                    <Input id="whatsapp" value={data.whatsapp} onChange={(e) => setData('whatsapp', e.target.value)} placeholder="628xxxxxxxxxx" className="mt-1" />
                                </div>
                                <div className="sm:col-span-2">
                                    <Label htmlFor="website" className="text-xs">Website</Label>
                                    <Input id="website" value={data.website} onChange={(e) => setData('website', e.target.value)} placeholder="https://..." className="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="timezone">Timezone</Label>
                            <Input
                                id="timezone"
                                value={data.timezone}
                                onChange={(e) => setData('timezone', e.target.value)}
                                className="mt-1.5"
                            />
                        </div>

                        <div>
                            <Label htmlFor="currency">Mata Uang</Label>
                            <Input
                                id="currency"
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value)}
                                className="mt-1.5"
                            />
                        </div>

                        <div className="sm:col-span-2">
                            <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                                <div>
                                    <Label htmlFor="is_active" className="text-sm">Status Aktif</Label>
                                    <p className="text-xs text-muted-foreground">Brand non-aktif tidak bisa dipakai untuk transaksi baru.</p>
                                </div>
                                <Switch
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(v) => setData('is_active', v)}
                                />
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {isEdit ? 'Simpan Perubahan' : 'Buat Brand'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function BrandIndex({ brands, filters, can, is_admin_reseller = false, accessible_brand_ids = [] }) {
    const [openForm, setOpenForm] = useState(false);
    const [editing, setEditing] = useState(null);
    const [confirmDelete, setConfirmDelete] = useState(null);
    const [search, setSearch] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');

    function applyFilters(overrides = {}) {
        router.get(route('brands.index'), {
            q: overrides.q ?? search,
            status: (overrides.status ?? status) === 'all' ? '' : (overrides.status ?? status),
        }, { preserveScroll: true, preserveState: true });
    }

    function openCreate() {
        setEditing(null);
        setOpenForm(true);
    }

    function openEdit(brand) {
        setEditing(brand);
        setOpenForm(true);
    }

    function toggleActive(brand) {
        router.post(route('brands.toggle', brand.id), {}, { preserveScroll: true });
    }

    function doDelete() {
        if (!confirmDelete) return;
        router.delete(route('brands.destroy', confirmDelete.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmDelete(null),
        });
    }

    return (
        <AppLayout title="Manajemen Brand">
            <Head title="Brand" />

            <div className="space-y-5">
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <Building2 className="h-5 w-5 text-primary" /> Daftar Brand
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {is_admin_reseller
                                    ? 'Kelola reseller yang beroperasi di bawah manajemen Anda.'
                                    : 'Kelola brand yang beroperasi di sistem.'
                                }
                            </p>
                        </div>
                        {can?.create && (
                            <Button onClick={openCreate}>
                                <Plus className="h-4 w-4" />
                                {is_admin_reseller ? 'Tambah Reseller' : 'Tambah Brand'}
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama atau kode..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                <SelectTrigger className="sm:w-44">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
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
                                        <TableHead className="w-12">Brand</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Tagline</TableHead>
                                        <TableHead className="text-center">User</TableHead>
                                        <TableHead>Dibuat</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {brands.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={7} className="py-10 text-center">
                                                {is_admin_reseller ? (
                                                    <div className="space-y-2">
                                                        <p className="text-sm font-semibold text-slate-600">Belum ada Branch Reseller</p>
                                                        <p className="text-xs text-muted-foreground">Klik <strong>+ Tambah Reseller</strong> untuk mendaftarkan reseller baru yang akan Anda kelola.</p>
                                                    </div>
                                                ) : (
                                                    <p className="text-sm text-muted-foreground">Belum ada brand yang cocok dengan filter.</p>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {brands.data.map((brand) => {
                                        // admin_reseller: bisa kelola penuh brand yang mereka punya akses
                                        // brand lain yang muncul = view only (bisa tambah akses nanti)
                                        const isManaged = !is_admin_reseller || accessible_brand_ids.includes(brand.id);
                                        return (
                                        <TableRow key={brand.id} className={!isManaged ? 'opacity-70' : ''}>
                                            <TableCell>
                                                {brand.logo ? (
                                                    <div className="h-9 w-9 overflow-hidden rounded-lg border bg-white flex items-center justify-center p-1 shadow-sm">
                                                        <img
                                                            src={`/storage/${brand.logo}`}
                                                            alt={brand.nama_brand}
                                                            className="h-full w-full object-contain"
                                                        />
                                                    </div>
                                                ) : (
                                                    <span
                                                        className="flex h-9 w-9 items-center justify-center rounded-lg text-xs font-bold text-white shadow-sm"
                                                        style={{ background: brand.warna_primary || '#3B82F6' }}
                                                    >
                                                        {brand.kode}
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-medium">{brand.nama_brand}</div>
                                                {brand.email && <div className="text-xs text-muted-foreground">{brand.email}</div>}
                                                {is_admin_reseller && !isManaged && (
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (confirm(`Ambil alih pengelolaan "${brand.nama_brand}"? Brand akan ditambahkan ke daftar kelola Anda.`)) {
                                                                router.post(route('brands.take-ownership', brand.id), {}, { preserveScroll: true });
                                                            }
                                                        }}
                                                        className="mt-0.5 text-[10px] font-bold text-amber-600 hover:text-amber-800 hover:underline transition-colors"
                                                    >
                                                        ⚡ Belum dikelola — Klik untuk kelola
                                                    </button>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">{brand.tagline ?? '-'}</TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant="outline">{brand.users_count}</Badge>
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">{formatDate(brand.created_at)}</TableCell>
                                            <TableCell>
                                                <Badge variant={brand.is_active ? 'success' : 'secondary'}>
                                                    {brand.is_active ? 'Aktif' : 'Non-Aktif'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    {can?.update && isManaged && (
                                                        <>
                                                            <Button size="icon" variant="ghost" title={brand.is_active ? 'Nonaktifkan' : 'Aktifkan'} onClick={() => toggleActive(brand)}>
                                                                <Power className="h-4 w-4" />
                                                            </Button>
                                                            <Button size="icon" variant="ghost" title="Edit" onClick={() => openEdit(brand)}>
                                                                <Pencil className="h-4 w-4" />
                                                            </Button>
                                                        </>
                                                    )}
                                                    {can?.delete && isManaged && (
                                                        <Button size="icon" variant="ghost" title="Hapus" className="text-destructive hover:text-destructive" onClick={() => setConfirmDelete(brand)}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    );})}
                                </TableBody>
                            </Table>
                        </div>

                        {brands.last_page > 1 && (
                            <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span className="text-muted-foreground">
                                    Menampilkan {brands.from ?? 0}–{brands.to ?? 0} dari {brands.total} data
                                </span>
                                <div className="flex gap-1">
                                    {brands.links.map((link, i) => (
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

            <BrandForm
                                key={editing?.id ?? 'create'}
                                open={openForm}
                                onOpenChange={setOpenForm}
                                brand={editing}
                                onSuccess={() => setOpenForm(false)}
                                isAdminReseller={is_admin_reseller}
                            />

            <Dialog open={!!confirmDelete} onOpenChange={(v) => !v && setConfirmDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Brand?</DialogTitle>
                        <DialogDescription>
                            Brand <span className="font-semibold">{confirmDelete?.nama_brand}</span> akan dihapus.
                            Brand yang masih memiliki user terhubung tidak bisa dihapus.
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
