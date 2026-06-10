import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ShieldCheck, Plus, Pencil, Trash2, ShieldAlert, Check } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { roleLabel } from '@/lib/utils';

function RoleFormModal({ open, onOpenChange, role, groupedPermissions, onSuccess }) {
    const isEdit = !!role;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: role?.name ? role.name.replace(/[_-]/g, ' ').toUpperCase() : '',
        permissions: role?.permissions ?? [],
    });

    function togglePermission(perm) {
        if (data.permissions.includes(perm)) {
            setData('permissions', data.permissions.filter((p) => p !== perm));
        } else {
            setData('permissions', [...data.permissions, perm]);
        }
    }

    function toggleGroup(perms, checked) {
        if (checked) {
            // Add all permissions in group that are not already in list
            const newPerms = [...data.permissions];
            perms.forEach((p) => {
                if (!newPerms.includes(p)) newPerms.push(p);
            });
            setData('permissions', newPerms);
        } else {
            // Remove all permissions in group
            setData('permissions', data.permissions.filter((p) => !perms.includes(p)));
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
            put(route('roles.update', role.id), opts);
        } else {
            post(route('roles.store'), opts);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col p-6">
                <form onSubmit={submit} className="flex flex-col h-full overflow-hidden">
                    <DialogHeader>
                        <DialogTitle>{isEdit ? `Edit Role: ${roleLabel(role.name)}` : 'Tambah Role Baru'}</DialogTitle>
                        <DialogDescription>
                            Tentukan nama role dan pilih izin/akses fitur yang diberikan untuk role ini.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex-1 overflow-y-auto my-4 pr-2 space-y-5">
                        <div>
                            <Label htmlFor="name">Nama Role <span className="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="CONTOH: SUPERVISOR OUTLET"
                                className="mt-1.5 uppercase font-semibold tracking-wider"
                                disabled={isEdit && role.name === 'superadmin'}
                            />
                            {errors.name && <p className="mt-1 text-xs text-destructive">{errors.name}</p>}
                        </div>

                        <div className="space-y-4">
                            <Label className="text-base font-semibold">Izin & Hak Akses Fitur</Label>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {Object.entries(groupedPermissions).map(([category, perms]) => {
                                    const allChecked = perms.every((p) => data.permissions.includes(p));
                                    const someChecked = perms.some((p) => data.permissions.includes(p)) && !allChecked;

                                    return (
                                        <Card key={category} className="border border-muted/80 shadow-none bg-muted/10">
                                            <CardHeader className="p-3 bg-muted/40 border-b flex flex-row items-center justify-between space-y-0">
                                                <span className="font-semibold text-sm">{category}</span>
                                                <label className="flex items-center gap-2 text-xs font-medium cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={allChecked}
                                                        ref={(el) => {
                                                            if (el) el.indeterminate = someChecked;
                                                        }}
                                                        onChange={(e) => toggleGroup(perms, e.target.checked)}
                                                        className="h-3.5 w-3.5 rounded border-input text-primary focus:ring-ring"
                                                    />
                                                    Pilih Semua
                                                </label>
                                            </CardHeader>
                                            <CardContent className="p-3 grid grid-cols-1 gap-2">
                                                {perms.map((perm) => (
                                                    <label
                                                        key={perm}
                                                        className={`flex items-start gap-2.5 p-2 rounded-lg border text-xs cursor-pointer transition ${
                                                            data.permissions.includes(perm)
                                                                ? 'border-primary bg-accent/60'
                                                                : 'bg-background hover:bg-muted/30'
                                                        }`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={data.permissions.includes(perm)}
                                                            onChange={() => togglePermission(perm)}
                                                            className="h-4 w-4 mt-0.5 rounded border-input text-primary focus:ring-ring"
                                                        />
                                                        <div className="flex-1">
                                                            <div className="font-medium text-foreground">{perm.replace(/[_-]/g, ' ').toUpperCase()}</div>
                                                            <div className="text-muted-foreground mt-0.5 scale-95 origin-top-left">
                                                                Code: <code className="bg-muted px-1 py-0.5 rounded text-[10px]">{perm}</code>
                                                            </div>
                                                        </div>
                                                    </label>
                                                ))}
                                            </CardContent>
                                        </Card>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    <DialogFooter className="border-t pt-4">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>Batal</Button>
                        <Button type="submit" disabled={processing}>{isEdit ? 'Simpan Perubahan' : 'Buat Role'}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function RolesIndex({ roles, grouped_permissions }) {
    const [openForm, setOpenForm] = useState(false);
    const [editingRole, setEditingRole] = useState(null);
    const [confirmDelete, setConfirmDelete] = useState(null);

    function openCreate() {
        setEditingRole(null);
        setOpenForm(true);
    }

    function openEdit(role) {
        setEditingRole(role);
        setOpenForm(true);
    }

    function doDelete() {
        if (!confirmDelete) return;
        router.delete(route('roles.destroy', confirmDelete.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmDelete(null),
        });
    }

    return (
        <AppLayout title="Manajemen Role & Izin">
            <Head title="Role & Izin" />

            <div className="space-y-5">
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <ShieldCheck className="h-5 w-5 text-primary" /> Dynamic Role & Permission Matrix
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Kelola role pengguna sistem dan atur hak akses/izin secara dinamis dan real-time.
                            </p>
                        </div>
                        <Button onClick={openCreate} className="gap-1.5 shadow-sm">
                            <Plus className="h-4 w-4" /> Tambah Role Baru
                        </Button>
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                                        <TableHead className="w-1/4">Nama Role</TableHead>
                                        <TableHead className="w-1/6">Jumlah User</TableHead>
                                        <TableHead>Daftar Izin Aktif</TableHead>
                                        <TableHead className="text-right w-[120px]">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {roles.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={4} className="py-8 text-center text-sm text-muted-foreground">
                                                Belum ada role terdaftar.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {roles.map((role) => {
                                        const isSystemRole = ['superadmin', 'owner'].includes(role.name);
                                        return (
                                            <TableRow key={role.id} className="hover:bg-muted/10 transition">
                                                <TableCell>
                                                    <div className="font-semibold text-sm tracking-wide text-foreground">
                                                        {roleLabel(role.name)}
                                                    </div>
                                                    <div className="text-[11px] text-muted-foreground mt-0.5">
                                                        slug: <code className="bg-muted px-1 rounded">{role.name}</code>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary" className="px-2 py-0.5 font-medium">
                                                        {role.users_count} User
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap gap-1 max-w-2xl py-1">
                                                        {role.name === 'superadmin' ? (
                                                            <Badge variant="default" className="bg-emerald-600 hover:bg-emerald-700">
                                                                ALL PERMISSIONS (BYPASS)
                                                            </Badge>
                                                        ) : role.permissions.length === 0 ? (
                                                            <span className="text-xs text-muted-foreground italic">Tidak ada izin aktif</span>
                                                        ) : (
                                                            role.permissions.map((perm) => (
                                                                <Badge key={perm} variant="outline" className="text-[10px] py-0 bg-background">
                                                                    {perm}
                                                                </Badge>
                                                            ))
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button size="icon" variant="ghost" title="Edit Role & Izin" onClick={() => openEdit(role)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        {!isSystemRole && (
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                title={role.users_count > 0 ? "Role tidak dapat dihapus karena masih digunakan" : "Hapus Role"}
                                                                className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                                disabled={role.users_count > 0}
                                                                onClick={() => setConfirmDelete(role)}
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <RoleFormModal
                key={editingRole?.id ?? 'new'}
                open={openForm}
                onOpenChange={setOpenForm}
                role={editingRole}
                groupedPermissions={grouped_permissions}
                onSuccess={() => setOpenForm(false)}
            />

            <Dialog open={!!confirmDelete} onOpenChange={(v) => !v && setConfirmDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-destructive">
                            <ShieldAlert className="h-5 w-5" /> Hapus Role?
                        </DialogTitle>
                        <DialogDescription>
                            Role <span className="font-semibold text-foreground">{roleLabel(confirmDelete?.name)}</span> akan dihapus secara permanen dari sistem. Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmDelete(null)}>Batal</Button>
                        <Button variant="destructive" onClick={doDelete}>Hapus Permanen</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
