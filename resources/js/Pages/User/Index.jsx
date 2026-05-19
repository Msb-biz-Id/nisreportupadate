import { Head, router, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Plus, Pencil, Trash2, Search, Users as UsersIcon, KeyRound } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatDateTime, roleLabel, initials } from '@/lib/utils';

function BrandCheckbox({ brand, checked, isDefault, onToggle, onSetDefault, disabled }) {
    return (
        <div className={`flex items-center gap-3 rounded-lg border p-2.5 transition ${checked ? 'border-primary bg-accent' : 'bg-muted/20'}`}>
            <input
                type="checkbox"
                checked={checked}
                disabled={disabled}
                onChange={() => onToggle(brand.id)}
                className="h-4 w-4 rounded border-input text-primary focus:ring-ring"
            />
            <div className="flex-1">
                <div className="text-sm font-medium leading-tight">{brand.nama_brand}</div>
                <div className="text-xs text-muted-foreground">{brand.kode}</div>
            </div>
            {checked && (
                <label className="flex items-center gap-1.5 text-xs">
                    <input
                        type="radio"
                        name="default_brand"
                        checked={isDefault}
                        onChange={() => onSetDefault(brand.id)}
                        className="h-3.5 w-3.5 text-primary"
                    />
                    Default
                </label>
            )}
        </div>
    );
}

function UserForm({ open, onOpenChange, user, roles, brands, onSuccess }) {
    const isEdit = !!user;

    const initialBrandIds = user?.brands?.map((b) => b.id) ?? [];
    const initialDefault = user?.brands?.find((b) => b.pivot?.is_default)?.id ?? initialBrandIds[0] ?? '';

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        password: '',
        password_confirmation: '',
        is_active: user?.is_active ?? true,
        role: user?.roles?.[0]?.name ?? '',
        brand_ids: initialBrandIds,
        default_brand_id: initialDefault,
    });

    // Sync default ke first brand_id kalau current default ter-uncheck
    useEffect(() => {
        if (data.brand_ids.length > 0 && !data.brand_ids.includes(data.default_brand_id)) {
            setData('default_brand_id', data.brand_ids[0]);
        }
        if (data.brand_ids.length === 0 && data.default_brand_id) {
            setData('default_brand_id', '');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.brand_ids.join(',')]);

    function toggleBrand(id) {
        if (data.brand_ids.includes(id)) {
            setData('brand_ids', data.brand_ids.filter((x) => x !== id));
        } else {
            setData('brand_ids', [...data.brand_ids, id]);
        }
    }

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
            put(route('users.update', user.id), opts);
        } else {
            post(route('users.store'), opts);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{isEdit ? 'Edit User' : 'Tambah User Baru'}</DialogTitle>
                        <DialogDescription>
                            Setiap user memiliki satu role dan akses ke satu atau lebih brand. Brand default akan dipilih saat login.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="name">Nama <span className="text-destructive">*</span></Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1.5" />
                            {errors.name && <p className="mt-1 text-xs text-destructive">{errors.name}</p>}
                        </div>
                        <div>
                            <Label htmlFor="email">Email <span className="text-destructive">*</span></Label>
                            <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1.5" />
                            {errors.email && <p className="mt-1 text-xs text-destructive">{errors.email}</p>}
                        </div>
                        <div>
                            <Label htmlFor="phone">No. HP</Label>
                            <Input id="phone" value={data.phone ?? ''} onChange={(e) => setData('phone', e.target.value)} className="mt-1.5" />
                        </div>
                        <div>
                            <Label htmlFor="role">Role <span className="text-destructive">*</span></Label>
                            <Select value={data.role} onValueChange={(v) => setData('role', v)}>
                                <SelectTrigger id="role" className="mt-1.5">
                                    <SelectValue placeholder="Pilih role" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((r) => (
                                        <SelectItem key={r} value={r}>{roleLabel(r)}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.role && <p className="mt-1 text-xs text-destructive">{errors.role}</p>}
                        </div>

                        <div>
                            <Label htmlFor="password">
                                Password {isEdit ? <span className="text-xs text-muted-foreground">(kosongkan jika tidak diubah)</span> : <span className="text-destructive">*</span>}
                            </Label>
                            <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className="mt-1.5" />
                            {errors.password && <p className="mt-1 text-xs text-destructive">{errors.password}</p>}
                        </div>
                        <div>
                            <Label htmlFor="password_confirmation">Konfirmasi Password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="mt-1.5"
                            />
                        </div>

                        <div className="sm:col-span-2">
                            <Label>Akses Brand <span className="text-destructive">*</span></Label>
                            <p className="mb-2 text-xs text-muted-foreground">Pilih satu atau lebih brand yang dapat diakses. Tandai 1 sebagai default.</p>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                {brands.map((b) => (
                                    <BrandCheckbox
                                        key={b.id}
                                        brand={b}
                                        checked={data.brand_ids.includes(b.id)}
                                        isDefault={data.default_brand_id === b.id}
                                        onToggle={toggleBrand}
                                        onSetDefault={(id) => setData('default_brand_id', id)}
                                    />
                                ))}
                            </div>
                            {errors.brand_ids && <p className="mt-1 text-xs text-destructive">{errors.brand_ids}</p>}
                            {errors.default_brand_id && <p className="mt-1 text-xs text-destructive">{errors.default_brand_id}</p>}
                        </div>

                        <div className="sm:col-span-2">
                            <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                                <div>
                                    <Label htmlFor="is_active" className="text-sm">Akun Aktif</Label>
                                    <p className="text-xs text-muted-foreground">User non-aktif tidak bisa login.</p>
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
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>Batal</Button>
                        <Button type="submit" disabled={processing}>{isEdit ? 'Simpan Perubahan' : 'Buat User'}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function UserIndex({ users, filters, rolesAvailable, brandsAvailable, can }) {
    const [openForm, setOpenForm] = useState(false);
    const [editing, setEditing] = useState(null);
    const [confirmDelete, setConfirmDelete] = useState(null);
    const [search, setSearch] = useState(filters?.q ?? '');
    const [roleFilter, setRoleFilter] = useState(filters?.role ?? 'all');
    const [statusFilter, setStatusFilter] = useState(filters?.status ?? 'all');

    function applyFilters(overrides = {}) {
        router.get(route('users.index'), {
            q: overrides.q ?? search,
            role: (overrides.role ?? roleFilter) === 'all' ? '' : (overrides.role ?? roleFilter),
            status: (overrides.status ?? statusFilter) === 'all' ? '' : (overrides.status ?? statusFilter),
        }, { preserveScroll: true, preserveState: true });
    }

    function openCreate() {
        setEditing(null);
        setOpenForm(true);
    }

    function openEdit(u) {
        setEditing(u);
        setOpenForm(true);
    }

    function doDelete() {
        if (!confirmDelete) return;
        router.delete(route('users.destroy', confirmDelete.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmDelete(null),
        });
    }

    return (
        <AppLayout title="Manajemen User">
            <Head title="User" />

            <div className="space-y-5">
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <UsersIcon className="h-5 w-5 text-primary" /> Daftar User
                            </div>
                            <p className="text-sm text-muted-foreground">Kelola pengguna sistem dan akses brand mereka.</p>
                        </div>
                        {can?.create && (
                            <Button onClick={openCreate}>
                                <Plus className="h-4 w-4" /> Tambah User
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama atau email..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={roleFilter} onValueChange={(v) => { setRoleFilter(v); applyFilters({ role: v }); }}>
                                <SelectTrigger className="sm:w-44"><SelectValue placeholder="Role" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Role</SelectItem>
                                    {rolesAvailable.map((r) => (
                                        <SelectItem key={r} value={r}>{roleLabel(r)}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); applyFilters({ status: v }); }}>
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
                                        <TableHead>User</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Brand</TableHead>
                                        <TableHead>Login Terakhir</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-8 text-center text-sm text-muted-foreground">
                                                Tidak ada user yang cocok.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {users.data.map((u) => (
                                        <TableRow key={u.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground">
                                                        {initials(u.name)}
                                                    </div>
                                                    <div>
                                                        <div className="font-medium leading-tight">{u.name}</div>
                                                        <div className="text-xs text-muted-foreground">{u.email}</div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {u.roles?.map((r) => (
                                                        <Badge key={r.id} variant={r.name === 'superadmin' ? 'default' : 'secondary'}>
                                                            {roleLabel(r.name)}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {u.brands?.map((b) => (
                                                        <Badge key={b.id} variant="outline" className="text-xs">{b.kode}</Badge>
                                                    ))}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">
                                                {u.last_login_at ? formatDateTime(u.last_login_at) : 'Belum pernah'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={u.is_active ? 'success' : 'secondary'}>{u.is_active ? 'Aktif' : 'Non-Aktif'}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    {can?.update && (
                                                        <Button size="icon" variant="ghost" title="Edit" onClick={() => openEdit(u)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                    {can?.delete && (
                                                        <Button size="icon" variant="ghost" title="Hapus" className="text-destructive hover:text-destructive" onClick={() => setConfirmDelete(u)}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {users.last_page > 1 && (
                            <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span className="text-muted-foreground">
                                    Menampilkan {users.from ?? 0}–{users.to ?? 0} dari {users.total} data
                                </span>
                                <div className="flex gap-1">
                                    {users.links.map((link, i) => (
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

            <UserForm
                key={editing?.id ?? 'new'}
                open={openForm}
                onOpenChange={setOpenForm}
                user={editing}
                roles={rolesAvailable}
                brands={brandsAvailable}
                onSuccess={() => setOpenForm(false)}
            />

            <Dialog open={!!confirmDelete} onOpenChange={(v) => !v && setConfirmDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus User?</DialogTitle>
                        <DialogDescription>
                            User <span className="font-semibold">{confirmDelete?.name}</span> akan dihapus dari sistem.
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
