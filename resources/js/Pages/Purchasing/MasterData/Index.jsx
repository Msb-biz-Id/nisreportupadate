import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Plus,
    Pencil,
    Trash2,
    Search,
    SlidersHorizontal,
    Building2,
    Layers,
    CheckCircle2,
    XCircle,
    Package,
    ShieldAlert,
    ExternalLink,
    Tag,
    Clock,
    Scale,
    Droplets,
    Check,
    X,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Switch } from '@/Components/ui/switch';
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

const CATEGORY_TABS = [
    { key: 'item_category', label: 'Kategori Item Pembelian', icon: Layers, group: 'Pengadaan' },
    { key: 'unit', label: 'Satuan Barang', icon: Scale, group: 'Pengadaan' },
    { key: 'location', label: 'Lokasi Ruko / Gudang', icon: Building2, group: 'Operasional' },
    { key: 'vendor_type', label: 'Kategori Vendor', icon: Tag, group: 'Pengadaan' },
    { key: 'payment_type', label: 'Jenis Transaksi Pembayaran', icon: Clock, group: 'Finansial' },
    { key: 'purchase_status', label: 'Status Pembelian', icon: CheckCircle2, group: 'Operasional' },
    { key: 'asset_category', label: 'Kategori Aset (20 Kode)', icon: Package, group: 'Aset Tetap' },
    { key: 'asset_unit', label: 'Satuan Aset Tetap', icon: Scale, group: 'Aset Tetap' },
    { key: 'retirement_reason', label: 'Alasan Aset Retirement', icon: ShieldAlert, group: 'Aset Tetap' },
    { key: 'final_condition', label: 'Kondisi Akhir Aset', icon: Tag, group: 'Aset Tetap' },
    { key: 'disposal_method', label: 'Metode Pelepasan Aset', icon: Layers, group: 'Aset Tetap' },
    { key: 'ink_variant', label: 'Varian Tinta & Maintenance', icon: Droplets, group: 'Material Khusus' },
    { key: 'hris_organization', label: 'Struktur HRIS (Single Source)', icon: ExternalLink, group: 'Sinkronisasi HRIS' },
];

export default function PurchasingMasterDataIndex({
    options = { data: [] },
    activeCategory = 'item_category',
    categoryCounts = {},
    categoryLabels = {},
    hrisReferences = {},
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingOption, setEditingOption] = useState(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [optionToDelete, setOptionToDelete] = useState(null);

    const form = useForm({
        name: '',
        code: '',
        category: activeCategory,
        order_index: 0,
        is_active: true,
    });

    const activeTabMeta = useMemo(() => {
        return CATEGORY_TABS.find((t) => t.key === activeCategory) || CATEGORY_TABS[0];
    }, [activeCategory]);

    function handleSelectTab(key) {
        setSearch('');
        router.get(
            route('purchasing.master-data.index'),
            { category: key },
            { preserveState: true, replace: true }
        );
    }

    function handleSearchSubmit(e) {
        e.preventDefault();
        router.get(
            route('purchasing.master-data.index'),
            { category: activeCategory, search },
            { preserveState: true, replace: true }
        );
    }

    function openCreateDialog() {
        setEditingOption(null);
        form.reset();
        form.setData({
            name: '',
            code: '',
            category: activeCategory,
            order_index: (options.data?.length || 0) + 1,
            is_active: true,
        });
        form.clearErrors();
        setDialogOpen(true);
    }

    function openEditDialog(item) {
        setEditingOption(item);
        form.setData({
            name: item.name,
            code: item.code,
            category: item.category,
            order_index: item.order_index,
            is_active: Boolean(item.is_active),
        });
        form.clearErrors();
        setDialogOpen(true);
    }

    function handleSaveOption(e) {
        e.preventDefault();
        if (editingOption) {
            form.put(route('purchasing.master-data.update', editingOption.uuid), {
                onSuccess: () => {
                    setDialogOpen(false);
                    form.reset();
                },
            });
        } else {
            form.post(route('purchasing.master-data.store'), {
                onSuccess: () => {
                    setDialogOpen(false);
                    form.reset();
                },
            });
        }
    }

    function handleToggleActive(item) {
        router.post(
            route('purchasing.master-data.toggle', item.uuid),
            {},
            { preserveScroll: true }
        );
    }

    function confirmDelete(item) {
        setOptionToDelete(item);
        setDeleteDialogOpen(true);
    }

    function executeDelete() {
        if (!optionToDelete) return;
        router.delete(route('purchasing.master-data.destroy', optionToDelete.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteDialogOpen(false);
                setOptionToDelete(null);
            },
        });
    }

    return (
        <AppLayout>
            <Head title={`Master Data & Kode - ${activeTabMeta.label}`} />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                <SlidersHorizontal className="h-5 w-5" />
                            </span>
                            <h1 className="text-2xl font-bold tracking-tight text-foreground">
                                Master Data & Standar Kode Purchasing
                            </h1>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola parameter dinamis, standar kode aset, dan sinkronisasi struktur organisasi dari modul HRIS.
                        </p>
                    </div>

                    {activeCategory !== 'hris_organization' && (
                        <Button
                            onClick={openCreateDialog}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                        >
                            <Plus className="mr-1.5 h-4 w-4" />
                            Tambah Opsi
                        </Button>
                    )}
                </div>

                {/* Main Content: Vertical Tabs & Options Table */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-4">
                    {/* Vertical Sidebar Tabs */}
                    <Card className="lg:col-span-1 shadow-sm border-border/80">
                        <CardHeader className="pb-3 border-b border-border/50 bg-muted/20">
                            <CardTitle className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                Kategori Master Data
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-2 space-y-1">
                            {CATEGORY_TABS.map((tab) => {
                                const IconComponent = tab.icon;
                                const isActive = activeCategory === tab.key;
                                const count = categoryCounts[tab.key] ?? 0;

                                return (
                                    <button
                                        key={tab.key}
                                        onClick={() => handleSelectTab(tab.key)}
                                        className={`flex w-full items-center justify-between rounded-md px-3 py-2.5 text-xs font-medium transition-colors text-left ${
                                            isActive
                                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-semibold border-l-4 border-emerald-600 dark:border-emerald-500 pl-2'
                                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2.5 min-w-0">
                                            <IconComponent className={`h-4 w-4 shrink-0 ${isActive ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'}`} />
                                            <span className="truncate">{tab.label}</span>
                                        </div>
                                        {tab.key !== 'hris_organization' && (
                                            <Badge
                                                variant={isActive ? 'default' : 'secondary'}
                                                className={`text-[10px] px-1.5 py-0 shrink-0 ${
                                                    isActive ? 'bg-emerald-600 text-white dark:bg-emerald-700' : ''
                                                }`}
                                            >
                                                {count}
                                            </Badge>
                                        )}
                                    </button>
                                );
                            })}
                        </CardContent>
                    </Card>

                    {/* Table / Content Area */}
                    <div className="lg:col-span-3 space-y-4">
                        {activeCategory === 'hris_organization' ? (
                            /* HRIS Organization Tab (Read-Only Bridge) */
                            <div className="space-y-4">
                                <Card className="shadow-sm border-border/80 bg-muted/20">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Badge variant="outline" className="border-emerald-500 text-emerald-700 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/20">
                                                    Single Source of Truth (HRIS)
                                                </Badge>
                                                <CardTitle className="text-base font-semibold">
                                                    Struktur Organisasi & Standar Kode Departemen
                                                </CardTitle>
                                            </div>
                                        </div>
                                        <CardDescription>
                                            Modul Purchasing membaca data struktur resmi di bawah secara langsung dari Modul HRIS (<code className="text-xs bg-muted px-1 py-0.5 rounded">hcm_master_options</code>). Kode 3 digit departemen dipakai sebagai segmen ke-2 kodifikasi aset tetap.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {/* Departemen & Kode Singkatan */}
                                        <div>
                                            <h3 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">
                                                7 Departemen Resmi & Kode Segmen Aset
                                            </h3>
                                            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                                {hrisReferences.departments_with_codes?.map((dept, idx) => (
                                                    <div
                                                        key={idx}
                                                        className="flex items-center justify-between p-3 rounded-lg border border-border bg-card shadow-xs"
                                                    >
                                                        <div>
                                                            <div className="font-medium text-sm text-foreground">
                                                                {dept.name}
                                                            </div>
                                                            <div className="text-[11px] text-muted-foreground">
                                                                Contoh Aset: <code className="text-emerald-700 dark:text-emerald-400 font-mono">IT.{dept.code}.026.001</code>
                                                            </div>
                                                        </div>
                                                        <Badge className="font-mono text-xs bg-emerald-600 hover:bg-emerald-600 text-white">
                                                            {dept.code}
                                                        </Badge>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Struktur Fungsi Kerja (Hierarki Posisi Fungsional) */}
                                        <div>
                                            <h3 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">
                                                Pemetaan Posisi Fungsional per Divisi (Cascading Form Matrix)
                                            </h3>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                {Object.entries(hrisReferences.division_position_map || {}).map(([divName, positions]) => (
                                                    <Card key={divName} className="border-border/60">
                                                        <CardHeader className="p-3 pb-2 bg-muted/30">
                                                            <CardTitle className="text-xs font-semibold flex items-center justify-between">
                                                                <span>Divisi {divName}</span>
                                                                <Badge variant="outline" className="text-[10px]">
                                                                    {positions.length} Posisi
                                                                </Badge>
                                                            </CardTitle>
                                                        </CardHeader>
                                                        <CardContent className="p-3 pt-2">
                                                            <div className="flex flex-wrap gap-1.5">
                                                                {positions.map((pos, pIdx) => (
                                                                    <span
                                                                        key={pIdx}
                                                                        className="inline-flex items-center rounded-md bg-secondary px-2 py-0.5 text-xs text-secondary-foreground"
                                                                    >
                                                                        {pos}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        </CardContent>
                                                    </Card>
                                                ))}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        ) : (
                            /* Standard Purchasing Options Table */
                            <Card className="shadow-sm border-border/80">
                                <CardHeader className="p-4 border-b border-border/50">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <CardTitle className="text-base font-semibold">
                                                {activeTabMeta.label}
                                            </CardTitle>
                                            <CardDescription className="text-xs">
                                                Daftar opsi aktif yang dapat dipilih oleh staf pada formulir pembelian dan aset.
                                            </CardDescription>
                                        </div>

                                        <form onSubmit={handleSearchSubmit} className="flex items-center gap-2 w-full sm:w-auto">
                                            <div className="relative w-full sm:w-64">
                                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                                <Input
                                                    type="search"
                                                    placeholder="Cari opsi atau kode..."
                                                    value={search}
                                                    onChange={(e) => setSearch(e.target.value)}
                                                    className="pl-8 text-xs h-9"
                                                />
                                            </div>
                                            <Button type="submit" variant="secondary" size="sm" className="h-9">
                                                Cari
                                            </Button>
                                        </form>
                                    </div>
                                </CardHeader>

                                <CardContent className="p-0">
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow className="bg-muted/30">
                                                    <TableHead className="w-12 text-center text-xs">No</TableHead>
                                                    <TableHead className="text-xs">Nama Opsi</TableHead>
                                                    <TableHead className="text-xs font-mono">Kode Sistem</TableHead>
                                                    <TableHead className="w-24 text-center text-xs">Urutan</TableHead>
                                                    <TableHead className="w-28 text-center text-xs">Status</TableHead>
                                                    <TableHead className="w-24 text-right text-xs pr-4">Aksi</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {options.data?.length > 0 ? (
                                                    options.data.map((item, idx) => (
                                                        <TableRow key={item.uuid} className="hover:bg-muted/20">
                                                            <TableCell className="text-center text-xs text-muted-foreground">
                                                                {((options.current_page - 1) * options.per_page) + idx + 1}
                                                            </TableCell>
                                                            <TableCell className="font-medium text-xs text-foreground">
                                                                {item.name}
                                                            </TableCell>
                                                            <TableCell className="font-mono text-xs text-muted-foreground">
                                                                <span className="rounded bg-muted px-1.5 py-0.5">
                                                                    {item.code || '-'}
                                                                </span>
                                                            </TableCell>
                                                            <TableCell className="text-center text-xs text-muted-foreground">
                                                                {item.order_index}
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                <button
                                                                    onClick={() => handleToggleActive(item)}
                                                                    className="inline-flex items-center gap-1.5 focus:outline-none"
                                                                >
                                                                    {item.is_active ? (
                                                                        <Badge className="bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-500/30 text-[11px] gap-1 py-0.5">
                                                                            <Check className="h-3 w-3" />
                                                                            Aktif
                                                                        </Badge>
                                                                    ) : (
                                                                        <Badge variant="outline" className="text-muted-foreground gap-1 text-[11px] py-0.5">
                                                                            <X className="h-3 w-3" />
                                                                            Non-aktif
                                                                        </Badge>
                                                                    )}
                                                                </button>
                                                            </TableCell>
                                                            <TableCell className="text-right pr-4">
                                                                <div className="flex items-center justify-end gap-1">
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="h-7 w-7 text-muted-foreground hover:text-foreground"
                                                                        onClick={() => openEditDialog(item)}
                                                                    >
                                                                        <Pencil className="h-3.5 w-3.5" />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="h-7 w-7 text-muted-foreground hover:text-destructive"
                                                                        onClick={() => confirmDelete(item)}
                                                                    >
                                                                        <Trash2 className="h-3.5 w-3.5" />
                                                                    </Button>
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))
                                                ) : (
                                                    <TableRow>
                                                        <TableCell colSpan={6} className="h-32 text-center text-muted-foreground">
                                                            <div className="flex flex-col items-center justify-center gap-1.5">
                                                                <Package className="h-6 w-6 text-muted-foreground/60" />
                                                                <span className="text-xs">Belum ada data opsi untuk kategori ini.</span>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            {/* Modal Tambah / Edit Opsi */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-[440px]">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold">
                            {editingOption ? 'Edit Opsi Master' : 'Tambah Opsi Master Baru'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Kategori: <strong className="text-foreground">{activeTabMeta.label}</strong>
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveOption} className="space-y-4 py-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="name" className="text-xs font-medium">
                                Nama Opsi <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Contoh: ATK, Roll, Laras Liris..."
                                className="text-xs h-9"
                                autoFocus
                            />
                            {form.errors.name && (
                                <p className="text-[11px] text-destructive">{form.errors.name}</p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="code" className="text-xs font-medium">
                                Kode Sistem (Opsional)
                            </Label>
                            <Input
                                id="code"
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value)}
                                placeholder="Dikosongkan untuk generate otomatis"
                                className="text-xs font-mono h-9"
                            />
                            {form.errors.code && (
                                <p className="text-[11px] text-destructive">{form.errors.code}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="order_index" className="text-xs font-medium">
                                    Urutan Tampil
                                </Label>
                                <Input
                                    id="order_index"
                                    type="number"
                                    min="0"
                                    value={form.data.order_index}
                                    onChange={(e) => form.setData('order_index', parseInt(e.target.value) || 0)}
                                    className="text-xs h-9"
                                />
                            </div>

                            <div className="flex flex-col justify-end pb-1 space-y-1.5">
                                <Label htmlFor="is_active" className="text-xs font-medium">
                                    Status Aktif
                                </Label>
                                <div className="flex items-center gap-2 pt-1">
                                    <Switch
                                        id="is_active"
                                        checked={form.data.is_active}
                                        onCheckedChange={(checked) => form.setData('is_active', checked)}
                                    />
                                    <span className="text-xs text-muted-foreground">
                                        {form.data.is_active ? 'Aktif' : 'Non-aktif'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setDialogOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={form.processing}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white"
                            >
                                {form.processing ? 'Menyimpan...' : 'Simpan Opsi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Konfirmasi Hapus */}
            <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <DialogContent className="sm:max-w-[400px]">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold text-destructive flex items-center gap-2">
                            <ShieldAlert className="h-5 w-5" />
                            Konfirmasi Hapus Opsi
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Apakah Anda yakin ingin menghapus opsi{' '}
                            <strong className="text-foreground">"{optionToDelete?.name}"</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setDeleteDialogOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            onClick={executeDelete}
                        >
                            Hapus Permanen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
