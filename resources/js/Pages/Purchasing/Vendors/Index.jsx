import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Building2,
    Plus,
    Search,
    Pencil,
    Trash2,
    Users,
    Phone,
    Mail,
    Star,
    MessageCircle,
    Check,
    X,
    CreditCard,
    ShieldAlert,
    ExternalLink,
    Briefcase,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import VendorContactDrawer from './Components/VendorContactDrawer';

export default function PurchasingVendorsIndex({
    vendors = { data: [] },
    vendorCategories = [],
    itemCategories = [],
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [categoryFilter, setCategoryFilter] = useState(filters.category || 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');

    // Dialog state
    const [vendorModalOpen, setVendorModalOpen] = useState(false);
    const [editingVendor, setEditingVendor] = useState(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [vendorToDelete, setVendorToDelete] = useState(null);

    // Drawer state
    const [selectedVendorForContacts, setSelectedVendorForContacts] = useState(null);
    const [contactsDrawerOpen, setContactsDrawerOpen] = useState(false);

    // Form Inertia untuk Create / Update Vendor
    const form = useForm({
        name: '',
        category: 'Pembelian Langsung / Offline',
        item_category: '',
        item_name: '',
        specification: '',
        address: '',
        bank_name: '',
        bank_account_no: '',
        bank_account_holder: '',
        default_top_days: 0,
        is_active: true,
        notes: '',
        contacts: [
            {
                pic_name: '',
                role_title: 'Sales Utama',
                phone: '',
                email: '',
                is_primary: true,
                notes: '',
            },
        ],
    });

    function handleFilterSubmit(e) {
        e.preventDefault();
        router.get(
            route('purchasing.vendors.index'),
            {
                search,
                category: categoryFilter === 'all' ? '' : categoryFilter,
                status: statusFilter === 'all' ? '' : statusFilter,
            },
            { preserveState: true, replace: true }
        );
    }

    function openCreateModal() {
        setEditingVendor(null);
        form.reset();
        form.setData({
            name: '',
            category: vendorCategories[0] || 'Pembelian Langsung / Offline',
            item_category: itemCategories[0] || '',
            item_name: '',
            specification: '',
            address: '',
            bank_name: '',
            bank_account_no: '',
            bank_account_holder: '',
            default_top_days: 0,
            is_active: true,
            notes: '',
            contacts: [
                {
                    pic_name: '',
                    role_title: 'Sales Utama',
                    phone: '',
                    email: '',
                    is_primary: true,
                    notes: '',
                },
            ],
        });
        form.clearErrors();
        setVendorModalOpen(true);
    }

    function openEditModal(vendor) {
        setEditingVendor(vendor);
        form.setData({
            name: vendor.name,
            category: vendor.category,
            item_category: vendor.item_category || '',
            item_name: vendor.item_name || '',
            specification: vendor.specification || '',
            address: vendor.address || '',
            bank_name: vendor.bank_name || '',
            bank_account_no: vendor.bank_account_no || '',
            bank_account_holder: vendor.bank_account_holder || '',
            default_top_days: vendor.default_top_days || 0,
            is_active: Boolean(vendor.is_active),
            notes: vendor.notes || '',
            contacts: (vendor.contacts && vendor.contacts.length > 0)
                ? vendor.contacts.map((c) => ({
                    id: c.id,
                    uuid: c.uuid,
                    pic_name: c.pic_name,
                    role_title: c.role_title || '',
                    phone: c.phone,
                    email: c.email || '',
                    is_primary: Boolean(c.is_primary),
                    notes: c.notes || '',
                }))
                : [
                    {
                        pic_name: '',
                        role_title: 'Sales Utama',
                        phone: '',
                        email: '',
                        is_primary: true,
                        notes: '',
                    },
                ],
        });
        form.clearErrors();
        setVendorModalOpen(true);
    }

    function handleSaveVendor(e) {
        e.preventDefault();
        if (editingVendor) {
            form.put(route('purchasing.vendors.update', editingVendor.uuid), {
                onSuccess: () => {
                    setVendorModalOpen(false);
                    form.reset();
                },
            });
        } else {
            form.post(route('purchasing.vendors.store'), {
                onSuccess: () => {
                    setVendorModalOpen(false);
                    form.reset();
                },
            });
        }
    }

    function addContactRow() {
        form.setData('contacts', [
            ...form.data.contacts,
            {
                pic_name: '',
                role_title: '',
                phone: '',
                email: '',
                is_primary: form.data.contacts.length === 0,
                notes: '',
            },
        ]);
    }

    function removeContactRow(idx) {
        const next = [...form.data.contacts];
        next.splice(idx, 1);
        if (next.length > 0 && !next.some((c) => c.is_primary)) {
            next[0].is_primary = true;
        }
        form.setData('contacts', next);
    }

    function updateContactField(idx, field, val) {
        const next = [...form.data.contacts];
        next[idx][field] = val;
        if (field === 'is_primary' && val === true) {
            next.forEach((c, i) => {
                if (i !== idx) c.is_primary = false;
            });
        }
        form.setData('contacts', next);
    }

    function confirmDelete(vendor) {
        setVendorToDelete(vendor);
        setDeleteDialogOpen(true);
    }

    function executeDelete() {
        if (!vendorToDelete) return;
        router.delete(route('purchasing.vendors.destroy', vendorToDelete.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteDialogOpen(false);
                setVendorToDelete(null);
            },
        });
    }

    function openContactsDrawer(vendor) {
        setSelectedVendorForContacts(vendor);
        setContactsDrawerOpen(true);
    }

    function formatWhatsAppUrl(phone) {
        const cleaned = (phone || '').replace(/[^0-9]/g, '');
        if (cleaned.startsWith('0')) {
            return `https://wa.me/62${cleaned.slice(1)}`;
        }
        return `https://wa.me/${cleaned}`;
    }

    return (
        <AppLayout>
            <Head title="Direktori Supplier & Multi-PIC" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                <Building2 className="h-5 w-5" />
                            </span>
                            <h1 className="text-2xl font-bold tracking-tight text-foreground">
                                Direktori Supplier & Multi-PIC
                            </h1>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola pangkalan data rekanan suplier, informasi rekening, dan kontak PIC sales/finance terverifikasi.
                        </p>
                    </div>

                    <Button
                        onClick={openCreateModal}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                    >
                        <Plus className="mr-1.5 h-4 w-4" />
                        Tambah Supplier
                    </Button>
                </div>

                {/* Filters & Search */}
                <Card className="shadow-sm border-border/80">
                    <CardContent className="p-4">
                        <form onSubmit={handleFilterSubmit} className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                            <div className="relative sm:col-span-2">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    type="search"
                                    placeholder="Cari kode vendor, nama perusahaan, item, atau nama PIC..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-8 text-xs h-9"
                                />
                            </div>

                            <div>
                                <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                                    <SelectTrigger className="h-9 text-xs">
                                        <SelectValue placeholder="Semua Kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Kategori</SelectItem>
                                        {vendorCategories.map((cat, idx) => (
                                            <SelectItem key={idx} value={cat}>
                                                {cat}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex gap-2">
                                <Select value={statusFilter} onValueChange={setStatusFilter}>
                                    <SelectTrigger className="h-9 text-xs">
                                        <SelectValue placeholder="Semua Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        <SelectItem value="active">Aktif Saja</SelectItem>
                                        <SelectItem value="inactive">Non-aktif Saja</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Button type="submit" variant="secondary" size="sm" className="h-9 px-4 shrink-0">
                                    Filter
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Vendor Directory Table */}
                <Card className="shadow-sm border-border/80">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/30">
                                        <TableHead className="w-24 text-xs font-mono">Kode</TableHead>
                                        <TableHead className="text-xs">Nama Perusahaan / Toko</TableHead>
                                        <TableHead className="text-xs">Kategori & Pasokan</TableHead>
                                        <TableHead className="text-xs">Kontak Utama (PIC)</TableHead>
                                        <TableHead className="w-24 text-center text-xs">Termin (TOP)</TableHead>
                                        <TableHead className="w-24 text-center text-xs">Status</TableHead>
                                        <TableHead className="w-32 text-right text-xs pr-4">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {vendors.data?.length > 0 ? (
                                        vendors.data.map((item) => {
                                            const primary = item.primary_contact || (item.contacts && item.contacts[0]);

                                            return (
                                                <TableRow key={item.uuid} className="hover:bg-muted/20">
                                                    <TableCell className="font-mono text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                                        {item.vendor_code}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="font-medium text-xs text-foreground">
                                                            {item.name}
                                                        </div>
                                                        {item.bank_name && (
                                                            <div className="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5">
                                                                <CreditCard className="h-3 w-3" />
                                                                {item.bank_name}: {item.bank_account_no} (a.n {item.bank_account_holder})
                                                            </div>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="outline" className="text-[10px] font-normal mb-0.5">
                                                            {item.category}
                                                        </Badge>
                                                        {(item.item_name || item.item_category) && (
                                                            <div className="text-[11px] text-muted-foreground line-clamp-1">
                                                                {item.item_name || item.item_category}
                                                            </div>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {primary ? (
                                                            <div>
                                                                <div className="flex items-center gap-1 text-xs font-medium text-foreground">
                                                                    <Star className="h-3 w-3 fill-emerald-600 text-emerald-600" />
                                                                    <span>{primary.pic_name}</span>
                                                                    {primary.role_title && (
                                                                        <span className="text-[11px] text-muted-foreground">({primary.role_title})</span>
                                                                    )}
                                                                </div>
                                                                <a
                                                                    href={formatWhatsAppUrl(primary.phone)}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className="inline-flex items-center gap-1 text-[11px] text-emerald-700 dark:text-emerald-400 hover:underline font-mono mt-0.5"
                                                                >
                                                                    <MessageCircle className="h-3 w-3" />
                                                                    {primary.phone}
                                                                </a>
                                                            </div>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground italic">Belum ada PIC</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center text-xs">
                                                        {item.default_top_days > 0 ? (
                                                            <Badge variant="secondary" className="text-[10px] font-mono">
                                                                {item.default_top_days} Hari
                                                            </Badge>
                                                        ) : (
                                                            <span className="text-muted-foreground text-[11px]">Tunai</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {item.is_active ? (
                                                            <Badge className="bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:text-emerald-400 border-emerald-500/30 text-[10px] py-0 px-1.5">
                                                                Aktif
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="text-muted-foreground text-[10px] py-0 px-1.5">
                                                                Non-aktif
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right pr-4">
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Button
                                                                variant="outline"
                                                                size="xs"
                                                                className="h-7 text-[11px] gap-1 px-2 border-emerald-500/30 hover:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400"
                                                                onClick={() => openContactsDrawer(item)}
                                                                title="Kelola Banyak PIC"
                                                            >
                                                                <Users className="h-3.5 w-3.5" />
                                                                <span>{item.contacts?.length || 0} PIC</span>
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-7 w-7 text-muted-foreground hover:text-foreground"
                                                                onClick={() => openEditModal(item)}
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
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-32 text-center text-muted-foreground">
                                                <div className="flex flex-col items-center justify-center gap-1.5">
                                                    <Building2 className="h-6 w-6 text-muted-foreground/60" />
                                                    <span className="text-xs">Belum ada data supplier yang terdaftar.</span>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Modal Create / Edit Vendor */}
            <Dialog open={vendorModalOpen} onOpenChange={setVendorModalOpen}>
                <DialogContent className="sm:max-w-[700px] max-h-[92vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold">
                            {editingVendor ? 'Edit Data Supplier' : 'Tambah Supplier Baru (Multi-PIC)'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Isi profil suplier dan daftarkan kontak penanggung jawab (PIC) terkait.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveVendor} className="space-y-4 py-2">
                        {/* Section 1: Profil Suplier */}
                        <div className="space-y-3">
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground border-b pb-1">
                                1. Profil & Legalitas Toko/Perusahaan
                            </h3>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs">Nama Perusahaan / Toko *</Label>
                                    <Input
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="Contoh: CV Sumber Makmur Textile"
                                        className="h-8 text-xs"
                                        autoFocus
                                    />
                                    {form.errors.name && (
                                        <p className="text-[10px] text-destructive">{form.errors.name}</p>
                                    )}
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Kategori Supplier *</Label>
                                    <Select
                                        value={form.data.category}
                                        onValueChange={(val) => form.setData('category', val)}
                                    >
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {vendorCategories.map((c, i) => (
                                                <SelectItem key={i} value={c}>
                                                    {c}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs">Kategori Item yang Dipasok</Label>
                                    <Select
                                        value={form.data.item_category}
                                        onValueChange={(val) => form.setData('item_category', val)}
                                    >
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Pilih Kategori Item" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {itemCategories.map((c, i) => (
                                                <SelectItem key={i} value={c}>
                                                    {c}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Nama Item / Produk Utama</Label>
                                    <Input
                                        value={form.data.item_name}
                                        onChange={(e) => form.setData('item_name', e.target.value)}
                                        placeholder="Contoh: Kain Milano, Benang Jahit"
                                        className="h-8 text-xs"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Alamat Toko / Gudang</Label>
                                <Textarea
                                    rows={2}
                                    value={form.data.address}
                                    onChange={(e) => form.setData('address', e.target.value)}
                                    placeholder="Alamat lengkap, ruko, atau link Google Maps..."
                                    className="text-xs resize-none"
                                />
                            </div>
                        </div>

                        {/* Section 2: Finansial & Rekening Bank */}
                        <div className="space-y-3">
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground border-b pb-1">
                                2. Rekening Pembayaran & Syarat Termin
                            </h3>
                            <div className="grid grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs">Nama Bank</Label>
                                    <Input
                                        value={form.data.bank_name}
                                        onChange={(e) => form.setData('bank_name', e.target.value)}
                                        placeholder="BCA, Mandiri, BRI"
                                        className="h-8 text-xs"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Nomor Rekening</Label>
                                    <Input
                                        value={form.data.bank_account_no}
                                        onChange={(e) => form.setData('bank_account_no', e.target.value)}
                                        placeholder="1234567890"
                                        className="h-8 text-xs font-mono"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Atas Nama Rekening</Label>
                                    <Input
                                        value={form.data.bank_account_holder}
                                        onChange={(e) => form.setData('bank_account_holder', e.target.value)}
                                        placeholder="PT Sumber Makmur"
                                        className="h-8 text-xs"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3 items-center">
                                <div className="space-y-1">
                                    <Label className="text-xs">Default Jatuh Tempo (Hari TOP)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={form.data.default_top_days}
                                        onChange={(e) => form.setData('default_top_days', parseInt(e.target.value) || 0)}
                                        placeholder="0 = Tunai"
                                        className="h-8 text-xs"
                                    />
                                </div>
                                <div className="flex items-center gap-2 pt-5">
                                    <Switch
                                        checked={form.data.is_active}
                                        onCheckedChange={(checked) => form.setData('is_active', checked)}
                                    />
                                    <Label className="text-xs">Suplier Aktif</Label>
                                </div>
                            </div>
                        </div>

                        {/* Section 3: Kontak PIC (Multi-PIC Repeater) */}
                        <div className="space-y-3">
                            <div className="flex items-center justify-between border-b pb-1">
                                <h3 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                    3. Kontak PIC (Multi-PIC Support)
                                </h3>
                                <Button
                                    type="button"
                                    size="xs"
                                    variant="outline"
                                    onClick={addContactRow}
                                    className="h-7 text-xs"
                                >
                                    <Plus className="mr-1 h-3 w-3" />
                                    Tambah Kontak PIC
                                </Button>
                            </div>

                            <div className="space-y-2.5">
                                {form.data.contacts.map((contact, idx) => (
                                    <div
                                        key={idx}
                                        className="p-3 rounded-lg border border-border/80 bg-muted/20 space-y-2 relative"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="text-[11px] font-semibold text-muted-foreground">
                                                Kontak PIC #{idx + 1}
                                            </span>
                                            <div className="flex items-center gap-3">
                                                <label className="flex items-center gap-1.5 text-xs cursor-pointer">
                                                    <input
                                                        type="radio"
                                                        name="primary_contact_radio"
                                                        checked={contact.is_primary}
                                                        onChange={() => updateContactField(idx, 'is_primary', true)}
                                                        className="text-emerald-600 focus:ring-emerald-500"
                                                    />
                                                    <span className="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">
                                                        Kontak Utama
                                                    </span>
                                                </label>
                                                {form.data.contacts.length > 1 && (
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() => removeContactRow(idx)}
                                                        className="h-6 w-6 text-muted-foreground hover:text-destructive"
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <Input
                                                    placeholder="Nama Lengkap PIC *"
                                                    value={contact.pic_name}
                                                    onChange={(e) => updateContactField(idx, 'pic_name', e.target.value)}
                                                    className="h-7 text-xs"
                                                />
                                            </div>
                                            <div>
                                                <Input
                                                    placeholder="Jabatan (Sales, Finance, dll)"
                                                    value={contact.role_title}
                                                    onChange={(e) => updateContactField(idx, 'role_title', e.target.value)}
                                                    className="h-7 text-xs"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <Input
                                                    placeholder="No Telepon / WhatsApp *"
                                                    value={contact.phone}
                                                    onChange={(e) => updateContactField(idx, 'phone', e.target.value)}
                                                    className="h-7 text-xs font-mono"
                                                />
                                            </div>
                                            <div>
                                                <Input
                                                    placeholder="Email (Opsional)"
                                                    type="email"
                                                    value={contact.email}
                                                    onChange={(e) => updateContactField(idx, 'email', e.target.value)}
                                                    className="h-7 text-xs"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setVendorModalOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={form.processing}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white"
                            >
                                {form.processing ? 'Menyimpan...' : 'Simpan Data Supplier'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Konfirmasi Hapus Vendor */}
            <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <DialogContent className="sm:max-w-[400px]">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold text-destructive flex items-center gap-2">
                            <ShieldAlert className="h-5 w-5" />
                            Konfirmasi Hapus Supplier
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Apakah Anda yakin ingin menghapus suplier{' '}
                            <strong className="text-foreground">"{vendorToDelete?.name}"</strong>?
                            Riwayat transaksi masa lalu tetap dipertahankan.
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
                            Hapus Supplier
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Drawer Kelola Multi-PIC Vendor Terpilih */}
            <VendorContactDrawer
                vendor={selectedVendorForContacts}
                open={contactsDrawerOpen}
                onOpenChange={setContactsDrawerOpen}
            />
        </AppLayout>
    );
}
