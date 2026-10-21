import { Head, router, useForm } from '@inertiajs/react';
import { useState, useEffect, useMemo } from 'react';
import { Plus, Pencil, Trash2, Search, Users as UsersIcon, KeyRound } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
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
        telegram_chat_id: user?.telegram_chat_id ?? '',
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
                            <Label htmlFor="phone">No. HP (WhatsApp)</Label>
                            <Input id="phone" value={data.phone ?? ''} onChange={(e) => setData('phone', e.target.value)} className="mt-1.5" placeholder="628xxxxxxxx" />
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
            page: overrides.page ?? undefined,
            per_page: overrides.per_page ?? (users?.per_page ?? 15),
        }, { preserveScroll: true, preserveState: true });
    }

    const userColumns = useMemo(() => [
        {
            id: 'user',
            accessorFn: (u) => `${u.name || ''} ${u.email || ''}`,
            meta: { title: 'User' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="User" />,
            cell: ({ row }) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground">
                        {initials(row.original.name)}
                    </div>
                    <div>
                        <div className="font-medium leading-tight">{row.original.name}</div>
                        <div className="text-xs text-muted-foreground">{row.original.email}</div>
                    </div>
                </div>
            ),
        },
        {
            id: 'roles',
            accessorFn: (u) => (u.roles ?? []).map((r) => roleLabel(r.name)).join(', '),
            meta: { title: 'Role' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Role" />,
            cell: ({ row }) => (
                <div className="flex flex-wrap gap-1">
                    {row.original.roles?.map((r) => (
                        <Badge key={r.id} variant={r.name === 'superadmin' ? 'default' : 'secondary'}>
                            {roleLabel(r.name)}
                        </Badge>
                    ))}
                </div>
            ),
        },
        {
            id: 'brands',
            accessorFn: (u) => (u.brands ?? []).map((b) => b.kode).join(', '),
            meta: { title: 'Brand' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Brand" />,
            cell: ({ row }) => (
                <div className="flex flex-wrap gap-1">
                    {row.original.brands?.map((b) => (
                        <Badge key={b.id} variant="outline" className="text-xs">{b.kode}</Badge>
                    ))}
                </div>
            ),
        },
        {
            id: 'last_login_at',
            accessorFn: (u) => u.last_login_at || '',
            meta: { title: 'Login Terakhir', cellClassName: 'text-xs text-muted-foreground' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Login Terakhir" />,
            cell: ({ row }) => (row.original.last_login_at ? formatDateTime(row.original.last_login_at) : 'Belum pernah'),
        },
        {
            id: 'is_active',
            accessorFn: (u) => (u.is_active ? 1 : 0),
            meta: { title: 'Status' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
            cell: ({ row }) => <Badge variant={row.original.is_active ? 'success' : 'secondary'}>{row.original.is_active ? 'Aktif' : 'Non-Aktif'}</Badge>,
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Aksi</div>,
            cell: ({ row }) => (
                <div className="flex justify-end gap-1">
                    {can?.update && (
                        <Button size="icon" variant="ghost" title="Edit" onClick={() => openEdit(row.original)}>
                            <Pencil className="h-4 w-4" />
                        </Button>
                    )}
                    {can?.delete && (
                        <Button size="icon" variant="ghost" title="Hapus" className="text-destructive hover:text-destructive" onClick={() => setConfirmDelete(row.original)}>
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    )}
                </div>
            ),
        },
    ], [can?.update, can?.delete]);

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
                    <CardContent className="p-0">
                        <DataTable
                            columns={userColumns}
                            data={users.data || []}
                            serverPagination={users}
                            searchValue={search}
                            onServerSearchChange={(val) => { setSearch(val); applyFilters({ q: val, page: 1 }); }}
                            onServerPageChange={(page) => applyFilters({ page })}
                            onServerPerPageChange={(per_page) => applyFilters({ per_page, page: 1 })}
                            searchPlaceholder="Cari nama atau email..."
                            exportFileName={`Users_${new Date().toISOString().slice(0, 10)}`}
                            emptyMessage="Tidak ada user yang cocok."
                            filterSlot={
                                <div className="flex flex-wrap items-center gap-2">
                                    <Select value={roleFilter} onValueChange={(v) => { setRoleFilter(v); applyFilters({ role: v, page: 1 }); }}>
                                        <SelectTrigger className="h-8 w-44 text-xs bg-background"><SelectValue placeholder="Role" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Role</SelectItem>
                                            {rolesAvailable.map((r) => (
                                                <SelectItem key={r} value={r}>{roleLabel(r)}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); applyFilters({ status: v, page: 1 }); }}>
                                        <SelectTrigger className="h-8 w-40 text-xs bg-background"><SelectValue placeholder="Status" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Status</SelectItem>
                                            <SelectItem value="active">Aktif</SelectItem>
                                            <SelectItem value="inactive">Non-Aktif</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            }
                        />
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
