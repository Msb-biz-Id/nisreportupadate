import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
    Users,
    Phone,
    Mail,
    Star,
    Plus,
    Trash2,
    ShieldCheck,
    MessageCircle,
    User,
    Building2,
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent } from '@/Components/ui/card';

export default function VendorContactDrawer({
    vendor,
    open,
    onOpenChange,
}) {
    const [showAddForm, setShowAddForm] = useState(false);

    const form = useForm({
        pic_name: '',
        role_title: '',
        phone: '',
        email: '',
        is_primary: false,
        notes: '',
    });

    if (!vendor) return null;

    function handleAddContact(e) {
        e.preventDefault();
        form.post(route('purchasing.vendors.contacts.store', vendor.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setShowAddForm(false);
            },
        });
    }

    function handleDeleteContact(contact) {
        if (!confirm(`Hapus kontak PIC "${contact.pic_name}"?`)) return;
        router.delete(
            route('purchasing.vendors.contacts.destroy', [vendor.uuid, contact.uuid]),
            { preserveScroll: true }
        );
    }

    function handleSetPrimary(contact) {
        router.post(
            route('purchasing.vendors.contacts.set-primary', [vendor.uuid, contact.uuid]),
            {},
            { preserveScroll: true }
        );
    }

    function formatWhatsAppUrl(phone) {
        const cleaned = (phone || '').replace(/[^0-9]/g, '');
        if (cleaned.startsWith('0')) {
            return `https://wa.me/62${cleaned.slice(1)}`;
        }
        return `https://wa.me/${cleaned}`;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[550px] max-h-[90vh] overflow-y-auto">
                <DialogHeader className="pb-2 border-b border-border/50">
                    <div className="flex items-center gap-2">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <Building2 className="h-4 w-4" />
                        </span>
                        <div>
                            <DialogTitle className="text-base font-semibold">
                                Direktori PIC & Kontak Suplier
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                <span className="font-semibold text-foreground">{vendor.name}</span> ({vendor.vendor_code})
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div className="space-y-4 py-2">
                    {/* Header Action: Tambah Kontak */}
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            Daftar Kontak ({vendor.contacts?.length || 0})
                        </span>
                        {!showAddForm && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setShowAddForm(true)}
                                className="h-8 text-xs"
                            >
                                <Plus className="mr-1 h-3.5 w-3.5" />
                                Tambah PIC
                            </Button>
                        )}
                    </div>

                    {/* Inline Form Tambah Kontak Baru */}
                    {showAddForm && (
                        <Card className="border-emerald-500/40 bg-emerald-500/5">
                            <CardContent className="p-3.5 space-y-3">
                                <div className="text-xs font-semibold text-foreground flex items-center justify-between">
                                    <span>Formulir PIC Baru</span>
                                    <Button
                                        size="xs"
                                        variant="ghost"
                                        onClick={() => setShowAddForm(false)}
                                    >
                                        Tutup
                                    </Button>
                                </div>

                                <form onSubmit={handleAddContact} className="space-y-2.5">
                                    <div className="grid grid-cols-2 gap-2">
                                        <div className="space-y-1">
                                            <Label className="text-[11px]">Nama PIC *</Label>
                                            <Input
                                                size="sm"
                                                value={form.data.pic_name}
                                                onChange={(e) => form.setData('pic_name', e.target.value)}
                                                placeholder="Contoh: Budi Santoso"
                                                className="h-8 text-xs"
                                                autoFocus
                                            />
                                            {form.errors.pic_name && (
                                                <p className="text-[10px] text-destructive">{form.errors.pic_name}</p>
                                            )}
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-[11px]">Jabatan / Peran</Label>
                                            <Input
                                                size="sm"
                                                value={form.data.role_title}
                                                onChange={(e) => form.setData('role_title', e.target.value)}
                                                placeholder="Contoh: Sales Utama, Finance"
                                                className="h-8 text-xs"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-2">
                                        <div className="space-y-1">
                                            <Label className="text-[11px]">Nomor Telepon / WA *</Label>
                                            <Input
                                                size="sm"
                                                value={form.data.phone}
                                                onChange={(e) => form.setData('phone', e.target.value)}
                                                placeholder="08123456789"
                                                className="h-8 text-xs"
                                            />
                                            {form.errors.phone && (
                                                <p className="text-[10px] text-destructive">{form.errors.phone}</p>
                                            )}
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-[11px]">Alamat Email</Label>
                                            <Input
                                                size="sm"
                                                type="email"
                                                value={form.data.email}
                                                onChange={(e) => form.setData('email', e.target.value)}
                                                placeholder="budi@vendor.com"
                                                className="h-8 text-xs"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between pt-1">
                                        <label className="flex items-center gap-1.5 text-xs cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={form.data.is_primary}
                                                onChange={(e) => form.setData('is_primary', e.target.checked)}
                                                className="rounded border-border text-emerald-600 focus:ring-emerald-500"
                                            />
                                            <span>Jadikan Kontak Utama</span>
                                        </label>

                                        <Button
                                            type="submit"
                                            size="sm"
                                            disabled={form.processing}
                                            className="h-8 text-xs bg-emerald-600 hover:bg-emerald-700 text-white"
                                        >
                                            {form.processing ? 'Menyimpan...' : 'Simpan PIC'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {/* List of Contacts */}
                    <div className="space-y-2">
                        {vendor.contacts && vendor.contacts.length > 0 ? (
                            vendor.contacts.map((contact) => (
                                <div
                                    key={contact.uuid}
                                    className={`p-3 rounded-lg border transition-all ${
                                        contact.is_primary
                                            ? 'border-emerald-500/50 bg-emerald-500/5 dark:bg-emerald-950/20 shadow-xs'
                                            : 'border-border bg-card hover:bg-muted/30'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="space-y-1 min-w-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span className="font-semibold text-sm text-foreground">
                                                    {contact.pic_name}
                                                </span>
                                                {contact.role_title && (
                                                    <span className="text-xs text-muted-foreground">
                                                        ({contact.role_title})
                                                    </span>
                                                )}
                                                {contact.is_primary && (
                                                    <Badge className="bg-emerald-600 text-white text-[10px] gap-1 py-0 px-1.5">
                                                        <Star className="h-2.5 w-2.5 fill-current" />
                                                        Kontak Utama
                                                    </Badge>
                                                )}
                                            </div>

                                            <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground pt-0.5">
                                                <a
                                                    href={formatWhatsAppUrl(contact.phone)}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1 text-emerald-700 dark:text-emerald-400 hover:underline font-mono"
                                                >
                                                    <MessageCircle className="h-3.5 w-3.5" />
                                                    {contact.phone}
                                                </a>
                                                {contact.email && (
                                                    <a
                                                        href={`mailto:${contact.email}`}
                                                        className="inline-flex items-center gap-1 hover:underline"
                                                    >
                                                        <Mail className="h-3.5 w-3.5" />
                                                        {contact.email}
                                                    </a>
                                                )}
                                            </div>

                                            {contact.notes && (
                                                <p className="text-[11px] text-muted-foreground/80 italic pt-0.5">
                                                    Catatan: {contact.notes}
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-1 shrink-0">
                                            {!contact.is_primary && (
                                                <Button
                                                    size="xs"
                                                    variant="ghost"
                                                    onClick={() => handleSetPrimary(contact)}
                                                    title="Jadikan Kontak Utama"
                                                    className="h-7 text-xs text-muted-foreground hover:text-emerald-600"
                                                >
                                                    <Star className="h-3.5 w-3.5 mr-1" />
                                                    Set Utama
                                                </Button>
                                            )}
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={() => handleDeleteContact(contact)}
                                                className="h-7 w-7 text-muted-foreground hover:text-destructive"
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="text-center py-6 border border-dashed rounded-lg text-muted-foreground text-xs">
                                <Users className="h-6 w-6 mx-auto mb-1 text-muted-foreground/60" />
                                Belum ada kontak PIC yang didaftarkan untuk suplier ini.
                            </div>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
