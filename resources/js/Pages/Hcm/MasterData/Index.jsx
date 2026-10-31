import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import * as LucideIcons from 'lucide-react';
import {
    Plus,
    Pencil,
    Trash2,
    Search,
    FolderTree,
    CheckCircle2,
    XCircle,
    Layers,
    ArrowUpDown,
    SlidersHorizontal,
    Sparkles,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';

function resolveIcon(iconName) {
    if (!iconName) return LucideIcons.Folder;
    const Icon = LucideIcons[iconName] ?? LucideIcons.Folder;
    return Icon;
}

export default function HcmMasterDataIndex({
    categories = [],
    selectedCategory,
    options = [],
    departmentOptions = [],
    filters = {},
}) {
    // State lokal untuk filter
    const [categorySearch, setCategorySearch] = useState('');
    const [optionSearch, setOptionSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');

    // Dialog state
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingOption, setEditingOption] = useState(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [optionToDelete, setOptionToDelete] = useState(null);

    // Form Inertia untuk Tambah / Edit Opsi
    const form = useForm({
        name: '',
        code: '',
        parent_id: '',
        order_index: 0,
        description: '',
        is_active: true,
    });

    // Kelompokkan kategori berdasarkan grup
    const groupedCategories = useMemo(() => {
        const filtered = categories.filter((cat) =>
            cat.name.toLowerCase().includes(categorySearch.toLowerCase()) ||
            cat.group.toLowerCase().includes(categorySearch.toLowerCase())
        );

        const groups = {};
        for (const cat of filtered) {
            const grp = cat.group || 'Umum';
            if (!groups[grp]) groups[grp] = [];
            groups[grp].push(cat);
        }
        return groups;
    }, [categories, categorySearch]);

    // Handle klik kategori vertikal
    function handleSelectCategory(catCode) {
        router.get(
            route('hcm.master-data.index'),
            {
                category: catCode,
                status: statusFilter !== 'all' ? statusFilter : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }

    // Handle filter pencarian opsi
    function handleOptionSearchSubmit(e) {
        e.preventDefault();
        router.get(
            route('hcm.master-data.index'),
            {
                category: selectedCategory?.code,
                search: optionSearch || undefined,
                status: statusFilter !== 'all' ? statusFilter : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }

    // Handle filter status
    function handleStatusFilterChange(newStatus) {
        setStatusFilter(newStatus);
        router.get(
            route('hcm.master-data.index'),
            {
                category: selectedCategory?.code,
                search: optionSearch || undefined,
                status: newStatus !== 'all' ? newStatus : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    }

    // Buka dialog tambah opsi
    function openCreateDialog() {
        setEditingOption(null);
        const nextOrder = options.length > 0 ? Math.max(...options.map((o) => o.order_index || 0)) + 1 : 1;
        form.setData({
            name: '',
            code: '',
            parent_id: '',
            order_index: nextOrder,
            description: '',
            is_active: true,
        });
        form.clearErrors();
        setDialogOpen(true);
    }

    // Buka dialog edit opsi
    function openEditDialog(option) {
        setEditingOption(option);
        form.setData({
            name: option.name,
            code: option.code || '',
            parent_id: option.parent_id ? String(option.parent_id) : '',
            order_index: option.order_index || 0,
            description: option.description || '',
            is_active: Boolean(option.is_active),
        });
        form.clearErrors();
        setDialogOpen(true);
    }

    // Submit form Tambah / Edit
    function handleSubmit(e) {
        e.preventDefault();
        if (editingOption) {
            form.put(route('hcm.master-data.options.update', editingOption.uuid || editingOption.id), {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    form.reset();
                },
            });
        } else if (selectedCategory) {
            form.post(route('hcm.master-data.options.store', selectedCategory.code || selectedCategory.id), {
                preserveScroll: true,
                onSuccess: () => {
                    setDialogOpen(false);
                    form.reset();
                },
            });
        }
    }

    // Toggle status switch
    function handleToggleActive(option) {
        router.post(
            route('hcm.master-data.options.toggle', option.uuid || option.id),
            {},
            {
                preserveScroll: true,
            }
        );
    }

    // Buka dialog hapus
    function confirmDelete(option) {
        setOptionToDelete(option);
        setDeleteDialogOpen(true);
    }

    // Eksekusi hapus
    function handleDelete() {
        if (!optionToDelete) return;
        router.delete(route('hcm.master-data.options.destroy', optionToDelete.uuid || optionToDelete.id), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteDialogOpen(false);
                setOptionToDelete(null);
            },
        });
    }

    const SelectedIcon = resolveIcon(selectedCategory?.icon);

    return (
        <AppLayout title="Master Data Kepegawaian">
            <Head title="Master Data Kepegawaian" />

            <div className="space-y-6">
                {/* Header Utama */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <FolderTree className="h-7 w-7 text-primary" />
                            Master Data Kepegawaian
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola seluruh 22 kategori referensi dan opsi dropdown dinamis kepegawaian sesuai Blueprint resmi.
                        </p>
                    </div>

                    {selectedCategory && (
                        <Button
                            onClick={openCreateDialog}
                            className="inline-flex items-center gap-2 shadow-sm"
                        >
                            <Plus className="h-4 w-4" />
                            Tambah Data pada {selectedCategory.name}
                        </Button>
                    )}
                </div>

                {/* Split-Pane Layout: Sisi Kiri (Tab Vertikal) vs Sisi Kanan (Panel Data) */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    {/* Sisi Kiri: Menu Tab Vertikal Kategori */}
                    <div className="lg:col-span-4 xl:col-span-3 space-y-4">
                        <Card className="border shadow-sm">
                            <CardHeader className="p-4 pb-3">
                                <CardTitle className="text-sm font-semibold flex items-center justify-between">
                                    <span>Kategori Master</span>
                                    <Badge variant="secondary" className="text-xs font-mono">
                                        {categories.length} Kategori
                                    </Badge>
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Pilih kategori untuk melihat & mengedit datanya.
                                </CardDescription>
                                <div className="relative mt-2">
                                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        type="text"
                                        placeholder="Cari kategori..."
                                        value={categorySearch}
                                        onChange={(e) => setCategorySearch(e.target.value)}
                                        className="pl-8 text-xs h-9 bg-muted/30"
                                    />
                                </div>
                            </CardHeader>

                            <CardContent className="p-2 pt-0 max-h-[calc(100vh-280px)] overflow-y-auto space-y-4">
                                {Object.keys(groupedCategories).length === 0 ? (
                                    <div className="p-4 text-center text-xs text-muted-foreground">
                                        Tidak ada kategori yang sesuai pencarian.
                                    </div>
                                ) : (
                                    Object.entries(groupedCategories).map(([groupName, groupCats]) => (
                                        <div key={groupName} className="space-y-1">
                                            <div className="px-2 py-1 text-[11px] font-bold uppercase tracking-wider text-muted-foreground/80 flex items-center gap-1.5">
                                                <Layers className="h-3 w-3" />
                                                {groupName}
                                            </div>

                                            <div className="space-y-0.5">
                                                {groupCats.map((cat) => {
                                                    const CatIcon = resolveIcon(cat.icon);
                                                    const isSelected = selectedCategory?.id === cat.id;

                                                    return (
                                                        <button
                                                            key={cat.id}
                                                            type="button"
                                                            onClick={() => handleSelectCategory(cat.code)}
                                                            className={`w-full flex items-center justify-between px-3 py-2 text-xs rounded-md transition-all text-left ${
                                                                isSelected
                                                                    ? 'bg-primary text-primary-foreground font-semibold shadow-sm'
                                                                    : 'hover:bg-muted text-foreground/80 hover:text-foreground'
                                                            }`}
                                                        >
                                                            <div className="flex items-center gap-2 truncate">
                                                                <CatIcon className={`h-4 w-4 flex-shrink-0 ${isSelected ? 'text-primary-foreground' : 'text-primary'}`} />
                                                                <span className="truncate">{cat.name}</span>
                                                            </div>

                                                            <Badge
                                                                variant={isSelected ? 'outline' : 'secondary'}
                                                                className={`text-[10px] px-1.5 py-0 h-5 font-mono ml-2 flex-shrink-0 ${
                                                                    isSelected ? 'border-primary-foreground/40 text-primary-foreground' : ''
                                                                }`}
                                                            >
                                                                {cat.active_options_count ?? 0}
                                                            </Badge>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sisi Kanan: Panel Data CRUD Kategori Terpilih */}
                    <div className="lg:col-span-8 xl:col-span-9 space-y-4">
                        {selectedCategory ? (
                            <Card className="border shadow-sm">
                                {/* Header Kategori Terpilih */}
                                <CardHeader className="p-5 border-b bg-muted/10">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex items-start gap-3">
                                            <div className="p-2.5 rounded-lg bg-primary/10 text-primary mt-0.5">
                                                <SelectedIcon className="h-6 w-6" />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <CardTitle className="text-lg font-bold">
                                                        {selectedCategory.name}
                                                    </CardTitle>
                                                    <Badge variant="outline" className="text-xs">
                                                        {selectedCategory.group}
                                                    </Badge>
                                                </div>
                                                <CardDescription className="text-xs mt-1">
                                                    {selectedCategory.description || 'Pilihan data referensi untuk modul kepegawaian.'}
                                                </CardDescription>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 self-start sm:self-auto">
                                            <Badge variant="secondary" className="font-mono text-xs px-2.5 py-1">
                                                Total: {options.length} Opsi
                                            </Badge>
                                        </div>
                                    </div>

                                    {/* Action & Filter Bar */}
                                    <div className="mt-4 pt-3 border-t flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                                        <form onSubmit={handleOptionSearchSubmit} className="relative flex-1">
                                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                type="text"
                                                placeholder={`Cari data dalam ${selectedCategory.name}...`}
                                                value={optionSearch}
                                                onChange={(e) => setOptionSearch(e.target.value)}
                                                className="pl-8 text-xs h-9"
                                            />
                                        </form>

                                        <div className="flex items-center gap-2">
                                            <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                <SlidersHorizontal className="h-3.5 w-3.5" />
                                                <span>Status:</span>
                                            </div>
                                            <Select value={statusFilter} onValueChange={handleStatusFilterChange}>
                                                <SelectTrigger className="w-[140px] h-9 text-xs">
                                                    <SelectValue placeholder="Status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all" className="text-xs">Semua Data</SelectItem>
                                                    <SelectItem value="active" className="text-xs">Hanya Aktif</SelectItem>
                                                    <SelectItem value="inactive" className="text-xs">Non-Aktif</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </CardHeader>

                                {/* Tabel Opsi Data */}
                                <CardContent className="p-0">
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow className="bg-muted/40 text-xs">
                                                    <TableHead className="w-12 text-center font-bold">No</TableHead>
                                                    <TableHead className="w-20 font-bold">
                                                        <div className="flex items-center gap-1">
                                                            <ArrowUpDown className="h-3 w-3" />
                                                            <span>Urutan</span>
                                                        </div>
                                                    </TableHead>
                                                    <TableHead className="font-bold">Nama / Label Data</TableHead>
                                                    {['divisi', 'division'].includes(selectedCategory?.code) && (
                                                        <TableHead className="font-bold">Departemen Induk</TableHead>
                                                    )}
                                                    <TableHead className="font-bold">Kode Identifier</TableHead>
                                                    <TableHead className="font-bold">Keterangan</TableHead>
                                                    <TableHead className="w-28 text-center font-bold">Status Aktif</TableHead>
                                                    <TableHead className="w-24 text-right font-bold pr-4">Aksi</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {options.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell colSpan={['divisi', 'division'].includes(selectedCategory?.code) ? 8 : 7} className="h-36 text-center">
                                                            <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                                                <SelectedIcon className="h-8 w-8 text-muted-foreground/40" />
                                                                <p className="text-sm font-medium">Belum ada data opsi yang ditemukan.</p>
                                                                <p className="text-xs">Klik tombol tambah data untuk memasukkan opsi baru ke kategori ini.</p>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={openCreateDialog}
                                                                    className="mt-1"
                                                                >
                                                                    <Plus className="h-3.5 w-3.5 mr-1" />
                                                                    Tambah Opsi Sekarang
                                                                </Button>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    options.map((opt, idx) => (
                                                        <TableRow
                                                            key={opt.id}
                                                            className={`text-xs hover:bg-muted/30 transition-colors ${
                                                                !opt.is_active ? 'opacity-60 bg-muted/10' : ''
                                                            }`}
                                                        >
                                                            <TableCell className="text-center font-mono text-muted-foreground">
                                                                {idx + 1}
                                                            </TableCell>
                                                            <TableCell className="font-mono text-muted-foreground">
                                                                <Badge variant="outline" className="px-1.5 py-0 text-[11px]">
                                                                    {opt.order_index}
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell className="font-medium text-foreground">
                                                                <div className="flex items-center gap-1.5">
                                                                    <span>{opt.name}</span>
                                                                    {opt.is_active ? (
                                                                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-500" />
                                                                    ) : (
                                                                        <XCircle className="h-3.5 w-3.5 text-muted-foreground" />
                                                                    )}
                                                                </div>
                                                            </TableCell>
                                                            {['divisi', 'division'].includes(selectedCategory?.code) && (
                                                                <TableCell>
                                                                    <Badge variant="outline" className="text-[11px] font-normal text-indigo-700 bg-indigo-50/50 dark:text-indigo-300 dark:bg-indigo-950/30">
                                                                        {opt.parent?.name || 'Belum Terhubung'}
                                                                    </Badge>
                                                                </TableCell>
                                                            )}
                                                            <TableCell className="font-mono text-[11px] text-muted-foreground">
                                                                {opt.code || '-'}
                                                            </TableCell>
                                                            <TableCell className="text-muted-foreground max-w-xs truncate">
                                                                {opt.description || '-'}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                <div className="flex items-center justify-center">
                                                                    <Switch
                                                                        checked={Boolean(opt.is_active)}
                                                                        onCheckedChange={() => handleToggleActive(opt)}
                                                                        title={opt.is_active ? 'Klik untuk non-aktifkan' : 'Klik untuk aktifkan'}
                                                                    />
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="text-right pr-4">
                                                                <div className="flex items-center justify-end gap-1">
                                                                    <Button
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        className="h-7 w-7 text-muted-foreground hover:text-foreground"
                                                                        onClick={() => openEditDialog(opt)}
                                                                        title="Edit Data Opsi"
                                                                    >
                                                                        <Pencil className="h-3.5 w-3.5" />
                                                                    </Button>
                                                                    <Button
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        className="h-7 w-7 text-muted-foreground hover:text-destructive"
                                                                        onClick={() => confirmDelete(opt)}
                                                                        title="Hapus Data Opsi"
                                                                    >
                                                                        <Trash2 className="h-3.5 w-3.5" />
                                                                    </Button>
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card className="border p-8 text-center text-muted-foreground">
                                Pilih kategori master data pada panel sebelah kiri.
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            {/* Modal Dialog Form: Tambah / Edit Opsi */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-w-md">
                    <form onSubmit={handleSubmit}>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <Sparkles className="h-5 w-5 text-primary" />
                                {editingOption ? `Edit Opsi ${selectedCategory?.name}` : `Tambah Opsi Baru ke ${selectedCategory?.name}`}
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Masukkan nama opsi yang akan tampil pada pilihan dropdown di seluruh sistem.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="name" className="text-xs font-semibold">
                                    Nama / Label Opsi <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    placeholder="Contoh: Jahit, Kontrak, dll."
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    className="text-xs"
                                    autoFocus
                                />
                                {form.errors.name && (
                                    <p className="text-[11px] text-destructive">{form.errors.name}</p>
                                )}
                            </div>

                            {['divisi', 'division'].includes(selectedCategory?.code) && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="parent_id" className="text-xs font-semibold">
                                        Departemen Induk <span className="text-destructive">*</span>
                                    </Label>
                                    <Select
                                        value={form.data.parent_id ? String(form.data.parent_id) : 'none'}
                                        onValueChange={(val) => form.setData('parent_id', val === 'none' ? '' : val)}
                                    >
                                        <SelectTrigger className="text-xs h-9">
                                            <SelectValue placeholder="Pilih Departemen Induk..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none" className="text-xs text-muted-foreground">
                                                -- Belum Ada Departemen --
                                            </SelectItem>
                                            {departmentOptions.map((dept) => (
                                                <SelectItem key={dept.id} value={String(dept.id)} className="text-xs">
                                                    {dept.name} ({dept.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {form.errors.parent_id && (
                                        <p className="text-[11px] text-destructive">{form.errors.parent_id}</p>
                                    )}
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="code" className="text-xs font-semibold">
                                        Kode Slug (Opsional)
                                    </Label>
                                    <Input
                                        id="code"
                                        type="text"
                                        placeholder="Auto-generate"
                                        value={form.data.code}
                                        onChange={(e) => form.setData('code', e.target.value)}
                                        className="text-xs font-mono"
                                    />
                                    {form.errors.code && (
                                        <p className="text-[11px] text-destructive">{form.errors.code}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="order_index" className="text-xs font-semibold">
                                        Urutan Tampil
                                    </Label>
                                    <Input
                                        id="order_index"
                                        type="number"
                                        value={form.data.order_index}
                                        onChange={(e) => form.setData('order_index', parseInt(e.target.value) || 0)}
                                        className="text-xs font-mono"
                                    />
                                    {form.errors.order_index && (
                                        <p className="text-[11px] text-destructive">{form.errors.order_index}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="description" className="text-xs font-semibold">
                                    Keterangan Tambahan (Opsional)
                                </Label>
                                <Textarea
                                    id="description"
                                    rows={2}
                                    placeholder="Catatan penggunaan opsi ini..."
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    className="text-xs resize-none"
                                />
                                {form.errors.description && (
                                    <p className="text-[11px] text-destructive">{form.errors.description}</p>
                                )}
                            </div>

                            <div className="flex items-center justify-between p-3 rounded-lg border bg-muted/20">
                                <div>
                                    <p className="text-xs font-semibold text-foreground">Status Aktif</p>
                                    <p className="text-[11px] text-muted-foreground">
                                        Opsi aktif dapat langsung dipilih di formulir operasional.
                                    </p>
                                </div>
                                <Switch
                                    checked={form.data.is_active}
                                    onCheckedChange={(checked) => form.setData('is_active', checked)}
                                />
                            </div>
                        </div>

                        <DialogFooter className="gap-2 sm:gap-0">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDialogOpen(false)}
                                disabled={form.processing}
                                size="sm"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                size="sm"
                            >
                                {form.processing ? 'Menyimpan...' : (editingOption ? 'Simpan Perubahan' : 'Tambahkan Opsi')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Dialog Konfirmasi Hapus */}
            <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="text-destructive flex items-center gap-2">
                            <Trash2 className="h-5 w-5" />
                            Konfirmasi Hapus Data
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Apakah Anda yakin ingin menghapus opsi{' '}
                            <strong className="text-foreground font-semibold">"{optionToDelete?.name}"</strong> dari kategori {selectedCategory?.name}?
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-2 text-xs text-muted-foreground bg-destructive/10 p-3 rounded-md border border-destructive/20">
                        Peringatan: Opsi yang dihapus tidak akan dapat dipilih kembali. Jika opsi ini sudah dipakai pada data karyawan lama, disarankan untuk menonaktifkannya saja via tombol Switch.
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0 mt-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeleteDialogOpen(false)}
                            size="sm"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={handleDelete}
                            size="sm"
                        >
                            Ya, Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
