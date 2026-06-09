import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Target, Calendar, Sparkles, AlertCircle, Copy, Save, Landmark, Layers } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatRupiah } from '@/lib/utils';

const MONTH_NAMES = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export default function TargetIndex({ brands, year, targets, actuals }) {
    const [selectedBrand, setSelectedBrand] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    
    // Generate year range for selector
    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 5 }, (_, i) => currentYear - 2 + i);

    const { data, setData, post, processing, reset } = useForm({
        year: year,
        targets: []
    });

    function changeYear(newYear) {
        router.get(route('brand-targets.index'), { year: newYear }, { preserveScroll: true });
    }

    function openTargetModal(brand) {
        setSelectedBrand(brand);
        
        // Populate modal data with existing targets or defaults
        const brandTargets = targets[brand.id] ?? [];
        const initialTargets = Array.from({ length: 12 }, (_, i) => {
            const m = i + 1;
            const existing = brandTargets.find(t => t.month === m);
            return {
                brand_id: brand.id,
                month: m,
                target_revenue: existing ? parseFloat(existing.target_revenue) : 0,
                target_pcs: existing ? parseInt(existing.target_pcs) : 0
            };
        });

        setData({
            year: year,
            targets: initialTargets
        });
        setIsModalOpen(true);
    }

    function updateTargetField(index, field, value) {
        const updated = [...data.targets];
        updated[index] = {
            ...updated[index],
            [field]: value === '' ? 0 : (field === 'target_revenue' ? parseFloat(value) : parseInt(value))
        };
        setData('targets', updated);
    }

    function copyJanuaryToAll() {
        if (data.targets.length === 0) return;
        const janTarget = data.targets[0];
        const updated = data.targets.map(t => ({
            ...t,
            target_revenue: janTarget.target_revenue,
            target_pcs: janTarget.target_pcs
        }));
        setData('targets', updated);
    }

    function submit(e) {
        e.preventDefault();
        post(route('brand-targets.store'), {
            onSuccess: () => {
                setIsModalOpen(false);
                reset();
            }
        });
    }

    // Calculations for Brand Card Overviews
    function getBrandSummary(brandId) {
        const brandTargets = targets[brandId] ?? [];
        const brandActuals = actuals[brandId] ?? [];

        const totalTargetRevenue = brandTargets.reduce((sum, t) => sum + parseFloat(t.target_revenue), 0);
        const totalTargetPcs = brandTargets.reduce((sum, t) => sum + parseInt(t.target_pcs), 0);
        
        const totalActualRevenue = brandActuals.reduce((sum, a) => sum + parseFloat(a.revenue), 0);
        const totalActualPcs = brandActuals.reduce((sum, a) => sum + parseInt(a.pcs), 0);

        return {
            targetRevenue: totalTargetRevenue,
            targetPcs: totalTargetPcs,
            actualRevenue: totalActualRevenue,
            actualPcs: totalActualPcs,
            revPercent: totalTargetRevenue > 0 ? Math.round((totalActualRevenue / totalTargetRevenue) * 100) : 0,
            pcsPercent: totalTargetPcs > 0 ? Math.round((totalActualPcs / totalTargetPcs) * 100) : 0
        };
    }

    return (
        <AppLayout title="Target Penjualan Brand">
            <Head title="Target Penjualan" />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pb-4">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <Target className="h-5 w-5 text-indigo-600" /> Target Penjualan Bulanan
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Tetapkan target omset penjualan dan target jumlah pcs bulanan untuk masing-masing brand.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Label htmlFor="year-select" className="text-sm font-medium">Tahun:</Label>
                            <Select value={year.toString()} onValueChange={(v) => changeYear(parseInt(v))}>
                                <SelectTrigger id="year-select" className="w-32">
                                    <SelectValue placeholder="Pilih Tahun" />
                                </SelectTrigger>
                                <SelectContent>
                                    {years.map(y => (
                                        <SelectItem key={y} value={y.toString()}>{y}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>
                </Card>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {brands.map(brand => {
                        const summary = getBrandSummary(brand.id);
                        return (
                            <Card key={brand.id} className="relative overflow-hidden border-t-4" style={{ borderTopColor: brand.warna_primary || '#6366F1' }}>
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <span className="h-3 w-3 rounded-full" style={{ background: brand.warna_primary }} />
                                            <CardTitle className="text-base font-bold">{brand.nama_brand}</CardTitle>
                                        </div>
                                        <span className="text-xs font-mono bg-muted px-2 py-0.5 rounded text-muted-foreground">{brand.kode}</span>
                                    </div>
                                    <CardDescription>Target vs Realisasi Tahun {year}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Omset / Revenue Metric */}
                                    <div className="space-y-1">
                                        <div className="flex justify-between text-xs font-semibold">
                                            <span className="text-muted-foreground">Omset Penjualan</span>
                                            <span className={summary.targetRevenue > 0 ? "text-indigo-600" : "text-muted-foreground"}>
                                                {summary.targetRevenue > 0 ? `${summary.revPercent}% Tercapai` : 'Belum ada target'}
                                            </span>
                                        </div>
                                        <div className="flex items-baseline justify-between">
                                            <span className="text-sm font-bold text-slate-800">{formatRupiah(summary.actualRevenue)}</span>
                                            <span className="text-xs text-muted-foreground">dari target {formatRupiah(summary.targetRevenue)}</span>
                                        </div>
                                        <div className="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                className="bg-indigo-600 h-1.5 rounded-full transition-all duration-500" 
                                                style={{ width: `${summary.targetRevenue > 0 ? Math.min(100, summary.revPercent) : 0}%` }} 
                                            />
                                        </div>
                                    </div>

                                    {/* Qty / Pcs Metric */}
                                    <div className="space-y-1">
                                        <div className="flex justify-between text-xs font-semibold">
                                            <span className="text-muted-foreground">Volume Qty (Pcs)</span>
                                            <span className={summary.targetPcs > 0 ? "text-emerald-600" : "text-muted-foreground"}>
                                                {summary.targetPcs > 0 ? `${summary.pcsPercent}% Tercapai` : 'Belum ada target'}
                                            </span>
                                        </div>
                                        <div className="flex items-baseline justify-between">
                                            <span className="text-sm font-bold text-slate-800">{summary.actualPcs.toLocaleString('id-ID')} Pcs</span>
                                            <span className="text-xs text-muted-foreground">dari target {summary.targetPcs.toLocaleString('id-ID')} Pcs</span>
                                        </div>
                                        <div className="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                className="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" 
                                                style={{ width: `${summary.targetPcs > 0 ? Math.min(100, summary.pcsPercent) : 0}%` }} 
                                            />
                                        </div>
                                    </div>

                                    <Button 
                                        onClick={() => openTargetModal(brand)} 
                                        className="w-full mt-2" 
                                        variant="outline"
                                    >
                                        <Target className="h-4 w-4 mr-2" /> Atur Target Bulanan
                                    </Button>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>

            {/* Set Monthly Target Modal */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col p-0">
                    <form onSubmit={submit} className="flex flex-col h-full">
                        <DialogHeader className="p-6 pb-2 border-b">
                            <DialogTitle className="flex items-center gap-2">
                                <Target className="h-5 w-5 text-indigo-600" />
                                Atur Target Bulanan: {selectedBrand?.nama_brand} ({year})
                            </DialogTitle>
                            <DialogDescription>
                                Masukkan target omset penjualan dan quantity pcs untuk setiap bulan di tahun {year}.
                            </DialogDescription>
                        </DialogHeader>

                        {/* Modal Body with scrollable table */}
                        <div className="flex-1 overflow-y-auto p-6 space-y-4">
                            <div className="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800">
                                <div className="flex items-center gap-2">
                                    <AlertCircle className="h-4 w-4 flex-shrink-0" />
                                    <span>Gunakan tombol salin di samping untuk menyamakan target Januari ke bulan-bulan berikutnya dengan cepat.</span>
                                </div>
                                <Button 
                                    type="button" 
                                    size="sm" 
                                    variant="outline" 
                                    onClick={copyJanuaryToAll}
                                    className="h-7 border-amber-300 text-amber-900 bg-amber-100/50 hover:bg-amber-100"
                                >
                                    <Copy className="h-3 w-3 mr-1" /> Salin Target Jan
                                </Button>
                            </div>

                            <div className="border rounded-lg overflow-hidden">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b">
                                        <tr>
                                            <th className="px-4 py-3 text-left w-1/4">Bulan</th>
                                            <th className="px-4 py-3 text-left w-1/4">Target Omset (Rp)</th>
                                            <th className="px-4 py-3 text-left w-1/4">Target Qty (Pcs)</th>
                                            <th className="px-4 py-3 text-right w-1/4 bg-slate-50/50">Realisasi (Actual)</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y bg-white">
                                        {data.targets.map((t, idx) => {
                                            const monthActual = (actuals[selectedBrand?.id] ?? []).find(a => a.month === t.month);
                                            const actualRevenue = monthActual ? parseFloat(monthActual.revenue) : 0;
                                            const actualPcs = monthActual ? parseInt(monthActual.pcs) : 0;

                                            return (
                                                <tr key={t.month} className="hover:bg-slate-50/50">
                                                    <td className="px-4 py-3 font-medium text-slate-700">
                                                        {MONTH_NAMES[t.month - 1]}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="relative">
                                                            <span className="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted-foreground font-semibold">Rp</span>
                                                            <Input
                                                                type="number"
                                                                value={t.target_revenue || ''}
                                                                onChange={(e) => updateTargetField(idx, 'target_revenue', e.target.value)}
                                                                className="pl-8 h-9 text-xs"
                                                                placeholder="0"
                                                            />
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="relative">
                                                            <Input
                                                                type="number"
                                                                value={t.target_pcs || ''}
                                                                onChange={(e) => updateTargetField(idx, 'target_pcs', e.target.value)}
                                                                className="pr-8 h-9 text-xs"
                                                                placeholder="0"
                                                            />
                                                            <span className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground font-semibold">Pcs</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right bg-slate-50/20 font-mono text-xs text-slate-500">
                                                        <div className="font-semibold text-slate-700">{formatRupiah(actualRevenue)}</div>
                                                        <div className="text-[10px]">{actualPcs.toLocaleString('id-ID')} Pcs</div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <DialogFooter className="p-6 border-t bg-slate-50/50">
                            <Button type="button" variant="outline" onClick={() => setIsModalOpen(false)} disabled={processing}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700">
                                <Save className="h-4 w-4 mr-2" /> Simpan Target
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
