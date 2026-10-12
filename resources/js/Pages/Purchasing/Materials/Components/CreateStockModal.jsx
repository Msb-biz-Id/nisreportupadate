import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Layers, Palette, FileText, Droplets } from 'lucide-react';
import { toast } from 'sonner';

export default function CreateStockModal({ 
    isOpen, 
    onClose, 
    activeTab = 'KAIN_PUTIH',
    editingStock = null
}) {
    const isEdit = !!editingStock;

    const { data, setData, post, put, processing, reset, errors } = useForm({
        material_type: activeTab,
        name: '',
        unit: 'Roll',
        minimum_threshold: '5',
        initial_stock: '0',
        code: '',
    });

    useEffect(() => {
        if (editingStock) {
            setData({
                material_type: editingStock.material_type,
                name: editingStock.name,
                unit: editingStock.unit,
                minimum_threshold: String(editingStock.minimum_threshold),
                initial_stock: String(editingStock.current_stock),
                code: editingStock.code,
            });
        } else {
            const defaultUnits = {
                KAIN_PUTIH: 'Roll',
                KAIN_WARNA: 'Kg',
                KERTAS: 'Roll',
                TINTA: 'Botol',
            };
            setData({
                material_type: activeTab,
                name: '',
                unit: defaultUnits[activeTab] || 'Roll',
                minimum_threshold: '5',
                initial_stock: '0',
                code: '',
            });
        }
    }, [isOpen, editingStock, activeTab]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('purchasing.materials.stocks.update', editingStock.id), {
                onSuccess: () => {
                    toast.success('Master material berhasil diperbarui.');
                    reset();
                    onClose();
                },
                onError: (errs) => {
                    const msg = Object.values(errs)[0] || 'Gagal memperbarui data.';
                    toast.error(msg);
                }
            });
        } else {
            post(route('purchasing.materials.stocks.store'), {
                onSuccess: () => {
                    toast.success('Master material baru berhasil ditambahkan.');
                    reset();
                    onClose();
                },
                onError: (errs) => {
                    const msg = Object.values(errs)[0] || 'Gagal menambahkan material.';
                    toast.error(msg);
                }
            });
        }
    };

    const tabLabels = {
        KAIN_PUTIH: 'Kain Putih',
        KAIN_WARNA: 'Kain Warna',
        KERTAS: 'Kertas Printing',
        TINTA: 'Tinta Printing',
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-md bg-white rounded-2xl p-6 shadow-2xl">
                <DialogHeader className="border-b border-slate-100 pb-3">
                    <DialogTitle className="text-base font-black text-slate-800 flex items-center gap-2">
                        {isEdit ? 'Ubah Informasi Material' : `Tambah Master Item (${tabLabels[activeTab] || activeTab})`}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4 pt-2">
                    {/* Nama Material */}
                    <div>
                        <Label className="text-xs font-bold text-slate-700 uppercase">Nama Item Material</Label>
                        <Input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={activeTab === 'TINTA' ? 'Contoh: Tinta Sublime Cyan (C)' : 'Contoh: Milano Putih Roll'}
                            className="mt-1 h-9 text-xs"
                            required
                        />
                    </div>

                    {/* Kode Barang (Opsional) */}
                    {!isEdit && (
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">Kode Item (Opsional / Otomatis)</Label>
                            <Input
                                type="text"
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value)}
                                placeholder="Kosongkan untuk auto-generate (MAT-...)"
                                className="mt-1 h-9 text-xs font-mono"
                            />
                        </div>
                    )}

                    {/* Satuan & Batas Minimal */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">Satuan</Label>
                            <Select 
                                value={data.unit} 
                                onValueChange={(val) => setData('unit', val)}
                            >
                                <SelectTrigger className="mt-1 h-9 text-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Roll">Roll</SelectItem>
                                    <SelectItem value="Kg">Kg (Kilogram)</SelectItem>
                                    <SelectItem value="Botol">Botol</SelectItem>
                                    <SelectItem value="Meter">Meter</SelectItem>
                                    <SelectItem value="Pcs">Pcs</SelectItem>
                                    <SelectItem value="Box">Box / Dus</SelectItem>
                                    <SelectItem value="Pack">Pack / Pak</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">Batas Minimal (Threshold)</Label>
                            <Input
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.minimum_threshold}
                                onChange={(e) => setData('minimum_threshold', e.target.value)}
                                className="mt-1 h-9 text-xs font-mono font-bold"
                                required
                            />
                        </div>
                    </div>

                    {/* Saldo Awal (Hanya saat create) */}
                    {!isEdit && (
                        <div>
                            <Label className="text-xs font-bold text-slate-700 uppercase">Saldo Stok Awal Fisik</Label>
                            <Input
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.initial_stock}
                                onChange={(e) => setData('initial_stock', e.target.value)}
                                placeholder="0"
                                className="mt-1 h-9 text-xs font-mono font-bold text-emerald-600"
                            />
                            <p className="text-[10px] text-slate-400 mt-1">Saldo stok awal fisik saat barang pertama kali didaftarkan.</p>
                        </div>
                    )}

                    <DialogFooter className="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" size="sm" onClick={onClose} disabled={processing}>
                            Batal
                        </Button>
                        <Button 
                            type="submit" 
                            size="sm" 
                            disabled={processing}
                            className="bg-slate-900 hover:bg-slate-800 text-white font-bold"
                        >
                            {processing ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Material')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
