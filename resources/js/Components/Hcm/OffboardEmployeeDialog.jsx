import { useForm } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { SearchableSelect } from '@/Components/ui/searchable-select';

const toOptions = (arr) => (arr || []).map((v) => (typeof v === 'string' ? { value: v, label: v } : v));

/**
 * Dialog "Proses Karyawan Keluar" (Modul 12).
 * Menangkap data offboarding sekaligus menonaktifkan karyawan dalam satu langkah.
 */
export default function OffboardEmployeeDialog({ isOpen, onClose, employee, dropdowns = {} }) {
    const form = useForm({
        position: employee?.position || '',
        exit_date: new Date().toISOString().slice(0, 10),
        exit_reason: '',
        notice_compliance: '',
        rights_status: '',
        asset_clearance: '',
        clearance_status: 'Pending',
        offboarding_notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('hcm.employees.offboard', employee.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-xl">
                <DialogHeader>
                    <DialogTitle className="text-base flex items-center gap-2">
                        <LogOut className="h-4 w-4 text-rose-600" /> Proses Karyawan Keluar
                    </DialogTitle>
                    <DialogDescription className="text-xs">
                        Catat data offboarding <strong>{employee?.name}</strong>. Karyawan akan berstatus non-aktif setelah disimpan.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4 pt-2">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Posisi Terakhir</Label>
                            <Input
                                value={form.data.position}
                                onChange={(e) => form.setData('position', e.target.value)}
                                placeholder="e.g. Operator Jahit"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Tanggal Keluar *</Label>
                            <Input
                                type="date"
                                required
                                value={form.data.exit_date}
                                onChange={(e) => form.setData('exit_date', e.target.value)}
                            />
                            {form.errors.exit_date && <p className="text-[11px] text-rose-500">{form.errors.exit_date}</p>}
                        </div>
                        <div className="space-y-1 sm:col-span-2">
                            <Label className="text-xs font-medium">Alasan Keluar *</Label>
                            <Input
                                required
                                value={form.data.exit_reason}
                                onChange={(e) => form.setData('exit_reason', e.target.value)}
                                placeholder="e.g. Habis kontrak, Menikah, Mendapat pekerjaan baru"
                            />
                            {form.errors.exit_reason && <p className="text-[11px] text-rose-500">{form.errors.exit_reason}</p>}
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Kepatuhan Notice Period</Label>
                            <SearchableSelect
                                value={form.data.notice_compliance}
                                onValueChange={(val) => form.setData('notice_compliance', val)}
                                options={toOptions(dropdowns.notice_compliance)}
                                placeholder="Pilih Notice Period..."
                                className="text-xs"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Hak Sisa Karyawan</Label>
                            <SearchableSelect
                                value={form.data.rights_status}
                                onValueChange={(val) => form.setData('rights_status', val)}
                                options={toOptions(dropdowns.rights_status)}
                                placeholder="Pilih Status Hak..."
                                className="text-xs"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Pengembalian Aset &amp; Paklaring</Label>
                            <SearchableSelect
                                value={form.data.asset_clearance}
                                onValueChange={(val) => form.setData('asset_clearance', val)}
                                options={toOptions(dropdowns.asset_clearance)}
                                placeholder="Pilih Status Aset..."
                                className="text-xs"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Status Clearance Sheet</Label>
                            <SearchableSelect
                                value={form.data.clearance_status}
                                onValueChange={(val) => form.setData('clearance_status', val)}
                                options={toOptions(dropdowns.clearance_status?.length ? dropdowns.clearance_status : ['Pending', 'Selesai (Clear)'])}
                                placeholder="Pilih Status Clearance..."
                                className="text-xs"
                            />
                        </div>
                    </div>

                    <div className="space-y-1">
                        <Label className="text-xs font-medium">Catatan / Keterangan</Label>
                        <Textarea
                            rows={2}
                            value={form.data.offboarding_notes}
                            onChange={(e) => form.setData('offboarding_notes', e.target.value)}
                            placeholder="e.g. Paklaring sudah diserahkan"
                        />
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" size="sm" onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" size="sm" disabled={form.processing} className="bg-rose-600 hover:bg-rose-700 text-white">
                            Simpan &amp; Nonaktifkan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
