import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import * as Icons from 'lucide-react';
import { Plus, Pencil, Trash2, Search } from 'lucide-react';
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
import ImageUploader from '@/Components/ImageUploader';
import { formatRupiah } from '@/lib/utils';

function getIcon(name) {
    const Icon = Icons[name] ?? Icons.Boxes;
    return Icon;
}

function defaultsFromConfig(fields) {
    const obj = {};
    for (const f of fields) {
        if (f.type === 'switch') obj[f.name] = f.default ?? false;
        else if (f.type === 'number') obj[f.name] = f.default ?? 0;
        else obj[f.name] = f.default ?? '';
    }
    return obj;
}

function MasterFormDialog({ open, onOpenChange, config, record, onSuccess }) {
    const isEdit = !!record;
    const initial = useMemo(() => {
        if (!record) return defaultsFromConfig(config.fields);
        const obj = {};
        for (const f of config.fields) {
            const v = record[f.name];
            obj[f.name] = v === null || v === undefined ? defaultsFromConfig([f])[f.name] : v;
        }
        return obj;
    }, [record, config.fields]);

    const { data, setData, post, put, processing, errors, reset } = useForm(initial);

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
            put(route('master.update', { slug: config.slug, id: record.id }), opts);
        } else {
            post(route('master.store', config.slug), opts);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-xl">
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>{isEdit ? `Edit ${config.label}` : `Tambah ${config.label}`}</DialogTitle>
                        <DialogDescription>
                            {config.scope === 'global' && 'Data global — berlaku untuk semua brand.'}
                            {config.scope === 'brand' && 'Data terisolasi per brand aktif.'}
                            {config.scope === 'brand_nullable' && 'Data per brand aktif (global jika brand_id null).'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2">
                        {config.fields.map((field) => (
                            <FieldRenderer
                                key={field.name}
                                field={field}
                                value={data[field.name]}
                                onChange={(v) => setData(field.name, v)}
                                error={errors[field.name]}
                            />
                        ))}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={processing}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {isEdit ? 'Simpan Perubahan' : 'Buat'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function FieldRenderer({ field, value, onChange, error }) {
    const wide = ['textarea'].includes(field.type) || field.full_width;
    const wrapperCls = wide ? 'sm:col-span-2' : '';

    if (field.type === 'switch') {
        return (
            <div className={wrapperCls}>
                <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                    <div>
                        <Label htmlFor={field.name} className="text-sm">{field.label}</Label>
                    </div>
                    <Switch id={field.name} checked={!!value} onCheckedChange={onChange} />
                </div>
                {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
            </div>
        );
    }

    if (field.type === 'select') {
        return (
            <div className={wrapperCls}>
                <Label htmlFor={field.name}>
                    {field.label} {field.required && <span className="text-destructive">*</span>}
                </Label>
                <Select value={String(value ?? '')} onValueChange={onChange}>
                    <SelectTrigger id={field.name} className="mt-1.5"><SelectValue placeholder="Pilih" /></SelectTrigger>
                    <SelectContent>
                        {field.options?.map((opt) => (
                            <SelectItem key={opt.value} value={String(opt.value)}>{opt.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
            </div>
        );
    }

    if (field.type === 'textarea') {
        return (
            <div className={wrapperCls}>
                <Label htmlFor={field.name}>
                    {field.label} {field.required && <span className="text-destructive">*</span>}
                </Label>
                <Textarea
                    id={field.name}
                    rows={3}
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={field.placeholder}
                    className="mt-1.5"
                />
                {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
            </div>
        );
    }

    if (field.type === 'color') {
        return (
            <div className={wrapperCls}>
                <Label htmlFor={field.name}>{field.label}</Label>
                <div className="mt-1.5 flex items-center gap-2">
                    <Input
                        id={field.name}
                        type="color"
                        value={value || '#3B82F6'}
                        onChange={(e) => onChange(e.target.value)}
                        className="h-9 w-14 cursor-pointer p-1"
                    />
                    <Input
                        value={value || ''}
                        onChange={(e) => onChange(e.target.value)}
                        placeholder="#3B82F6"
                    />
                </div>
                {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
            </div>
        );
    }

    if (field.type === 'image') {
        return (
            <div className={wrapperCls}>
                <Label>{field.label}</Label>
                <div className="mt-1.5">
                    <ImageUploader
                        value={value || null}
                        onChange={onChange}
                        purpose={field.purpose || 'products'}
                        aspect={field.aspect || 1}
                    />
                </div>
                {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
            </div>
        );
    }

    return (
        <div className={wrapperCls}>
            <Label htmlFor={field.name}>
                {field.label} {field.required && <span className="text-destructive">*</span>}
            </Label>
            <Input
                id={field.name}
                type={field.type === 'number' ? 'number' : 'text'}
                step={field.step}
                value={value ?? ''}
                onChange={(e) => onChange(field.type === 'number' ? (e.target.value === '' ? '' : Number(e.target.value)) : e.target.value)}
                placeholder={field.placeholder}
                maxLength={field.max}
                className="mt-1.5"
            />
            {error && <p className="mt-1 text-xs text-destructive">{error}</p>}
        </div>
    );
}

function renderCell(col, row) {
    const value = row[col.key];

    if (col.type === 'badge_active') {
        return <Badge variant={value ? 'success' : 'secondary'}>{value ? 'Aktif' : 'Non-Aktif'}</Badge>;
    }
    if (col.type === 'badge') {
        return value ? <Badge variant="outline">{value}</Badge> : <span className="text-muted-foreground">-</span>;
    }
    if (col.type === 'badge_bool') {
        return <Badge variant={value ? 'info' : 'outline'}>{value ? col.true_label : col.false_label}</Badge>;
    }
    if (col.type === 'color_swatch') {
        return (
            <div className="flex items-center gap-2">
                <span className="h-4 w-4 rounded-full border" style={{ background: value || '#3B82F6' }} />
                <span className="font-mono text-xs text-muted-foreground">{value}</span>
            </div>
        );
    }
    if (col.type === 'currency') {
        return <span className="font-mono">{formatRupiah(value)}</span>;
    }
    if (col.type === 'image') {
        return value
            ? <img src={`/storage/${value}`} alt="" className="h-10 w-10 rounded border object-cover" />
            : <div className="flex h-10 w-10 items-center justify-center rounded border bg-muted text-[10px] text-muted-foreground">—</div>;
    }
    if (value === null || value === undefined || value === '') return <span className="text-muted-foreground">-</span>;
    return value;
}

export default function MasterIndex({ config, items, filters, can }) {
    const [openForm, setOpenForm] = useState(false);
    const [editing, setEditing] = useState(null);
    const [confirmDelete, setConfirmDelete] = useState(null);
    const [search, setSearch] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');
    const Icon = getIcon(config.icon);

    function applyFilters(overrides = {}) {
        router.get(route('master.index', config.slug), {
            q: overrides.q ?? search,
            status: (overrides.status ?? status) === 'all' ? '' : (overrides.status ?? status),
        }, { preserveScroll: true, preserveState: true });
    }

    function openCreate() {
        setEditing(null);
        setOpenForm(true);
    }

    function openEdit(row) {
        setEditing(row);
        setOpenForm(true);
    }

    function doDelete() {
        if (!confirmDelete) return;
        router.delete(route('master.destroy', { slug: config.slug, id: confirmDelete.id }), {
            preserveScroll: true,
            onSuccess: () => setConfirmDelete(null),
        });
    }

    return (
        <AppLayout title={`Master ${config.label}`}>
            <Head title={`Master ${config.label}`} />

            <div className="space-y-5">
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <Icon className="h-5 w-5 text-primary" /> Master {config.label}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                {config.scope === 'global' && 'Data global — berlaku di semua brand.'}
                                {config.scope === 'brand' && 'Data terisolasi sesuai brand aktif.'}
                                {config.scope === 'brand_nullable' && 'Data per brand aktif + master global reseller.'}
                            </p>
                        </div>
                        {can?.manage && (
                            <Button onClick={openCreate}>
                                <Plus className="h-4 w-4" /> Tambah
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent className="pt-0">
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari..."
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
                                        {config.list_columns.map((col) => (
                                            <TableHead key={col.key} className={col.class}>{col.label}</TableHead>
                                        ))}
                                        {can?.manage && <TableHead className="w-32 text-right">Aksi</TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={config.list_columns.length + 1} className="py-8 text-center text-sm text-muted-foreground">
                                                Belum ada data {config.label.toLowerCase()}.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {items.data.map((row) => (
                                        <TableRow key={row.id}>
                                            {config.list_columns.map((col) => (
                                                <TableCell key={col.key} className={col.class}>
                                                    {renderCell(col, row)}
                                                </TableCell>
                                            ))}
                                            {can?.manage && (
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button size="icon" variant="ghost" onClick={() => openEdit(row)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button size="icon" variant="ghost" className="text-destructive hover:text-destructive" onClick={() => setConfirmDelete(row)}>
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
                <MasterFormDialog
                    key={editing?.id ?? 'new'}
                    open={openForm}
                    onOpenChange={setOpenForm}
                    config={config}
                    record={editing}
                    onSuccess={() => setOpenForm(false)}
                />
            )}

            <Dialog open={!!confirmDelete} onOpenChange={(v) => !v && setConfirmDelete(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus data?</DialogTitle>
                        <DialogDescription>
                            Data ini akan dihapus dari master {config.label.toLowerCase()}. Action ini soft delete (bisa di-restore admin).
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
