import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { 
    Target, 
    AlertCircle, 
    Copy, 
    Save, 
    Check, 
    Calendar, 
    Table as TableIcon, 
    LayoutGrid, 
    TrendingUp, 
    Filter,
    FileSpreadsheet
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Badge } from '@/Components/ui/badge';

const MONTH_NAMES = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const MONTH_SHORT_NAMES = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
];

export default function TargetIndex({ brands = [], year = new Date().getFullYear(), month = 0, availableYears, targets = {}, actuals = {} }) {
    const [selectedBrand, setSelectedBrand] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [copiedToast, setCopiedToast] = useState(false);
    const [activeView, setActiveView] = useState('all'); // 'all', 'cards', 'matrix'
    const [matrixMode, setMatrixMode] = useState('both'); // 'both', 'actual', 'target'

    // Dynamic Year List fallback
    const currentYear = new Date().getFullYear();
    const yearsList = availableYears && availableYears.length > 0 
        ? availableYears 
        : Array.from({ length: 5 }, (_, i) => currentYear - 2 + i);

    const activeMonth = parseInt(month) || 0;

    const { data, setData, post, processing, reset } = useForm({
        year: year,
        targets: []
    });

    function handleYearChange(newYear) {
        router.get(route('brand-targets.index'), { year: newYear, month: activeMonth }, { preserveScroll: true });
    }

    function handleMonthChange(newMonth) {
        router.get(route('brand-targets.index'), { year: year, month: newMonth }, { preserveScroll: true });
    }

    function openTargetModal(brand) {
        setSelectedBrand(brand);
        
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
            [field]: value === '' ? 0 : parseInt(value)
        };
        setData('targets', updated);
    }

    // Copy January target_pcs to all subsequent months
    function copyJanuaryToAll() {
        if (data.targets.length === 0) return;
        const janTarget = data.targets[0];
        const updated = data.targets.map(t => ({
            ...t,
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

    // Calculations for Brand Cards (Filter-Aware)
    function getBrandSummary(brandId, filterMonth = 0) {
        const brandTargets = targets[brandId] ?? [];
        const brandActuals = actuals[brandId] ?? [];

        if (filterMonth > 0) {
            // Specific Month calculation
            const monthTarget = brandTargets.find(t => t.month === filterMonth);
            const monthActual = brandActuals.find(a => a.month === filterMonth);

            const targetPcs = monthTarget ? parseInt(monthTarget.target_pcs) : 0;
            const actualPcs = monthActual ? parseInt(monthActual.pcs) : 0;

            return {
                targetPcs,
                actualPcs,
                pcsPercent: targetPcs > 0 ? Math.round((actualPcs / targetPcs) * 100) : 0,
                isSingleMonth: true
            };
        } else {
            // Full Year calculation
            const totalTargetPcs = brandTargets.reduce((sum, t) => sum + parseInt(t.target_pcs), 0);
            const totalActualPcs = brandActuals.reduce((sum, a) => sum + parseInt(a.pcs), 0);

            return {
                targetPcs: totalTargetPcs,
                actualPcs: totalActualPcs,
                pcsPercent: totalTargetPcs > 0 ? Math.round((totalActualPcs / totalTargetPcs) * 100) : 0,
                isSingleMonth: false
            };
        }
    }

    // Function to Copy Matrix Report to Clipboard as Excel/TSV text
    function copyReportToClipboard() {
        const monthHeader = activeMonth > 0 ? MONTH_NAMES[activeMonth - 1] : 'Semua Bulan (1-12)';
        let text = `LAPORAN TARGET PENJUALAN BRAND - TAHUN ${year} (${monthHeader})\n\n`;
        
        // Header
        text += `No\tNama Brand\tKode\t` + MONTH_SHORT_NAMES.join('\t') + `\tTotal Target\tTotal Realisasi\t% Tercapai\n`;

        // Body
        brands.forEach((brand, idx) => {
            const brandTargets = targets[brand.id] ?? [];
            const brandActuals = actuals[brand.id] ?? [];
            const summary = getBrandSummary(brand.id, 0);

            let row = `${idx + 1}\t${brand.nama_brand}\t${brand.kode}\t`;
            
            const monthCells = Array.from({ length: 12 }, (_, i) => {
                const m = i + 1;
                const targetObj = brandTargets.find(t => t.month === m);
                const actualObj = brandActuals.find(a => a.month === m);
                const tPcs = targetObj ? parseInt(targetObj.target_pcs) : 0;
                const aPcs = actualObj ? parseInt(actualObj.pcs) : 0;
                return `${aPcs}/${tPcs}`;
            });

            row += monthCells.join('\t') + `\t${summary.targetPcs}\t${summary.actualPcs}\t${summary.pcsPercent}%\n`;
            text += row;
        });

        navigator.clipboard.writeText(text).then(() => {
            setCopiedToast(true);
            setTimeout(() => setCopiedToast(false), 3000);
        });
    }

    return (
        <AppLayout title="Target Penjualan Brand">
            <Head title="Target Penjualan Brand" />

            <div className="space-y-6">
                {/* Notification Banner when Copied */}
                {copiedToast && (
                    <div className="fixed bottom-5 right-5 z-50 flex items-center gap-2 bg-emerald-700 text-white px-4 py-3 rounded-lg shadow-xl animate-in fade-in slide-in-from-bottom-5">
                        <Check className="h-5 w-5 text-emerald-200" />
                        <span className="text-sm font-medium">Laporan berhasil disalin ke clipboard! Siap ditempel di Excel atau WhatsApp.</span>
                    </div>
                )}

                {/* Filter & Action Header Card */}
                <Card className="border-slate-200 shadow-sm">
                    <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between pb-4">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-bold text-slate-800">
                                <Target className="h-6 w-6 text-indigo-600" /> 
                                <span>Target Penjualan Brand</span>
                                {activeMonth > 0 && (
                                    <Badge variant="secondary" className="bg-indigo-50 text-indigo-700 border-indigo-200 ml-2 font-medium">
                                        Bulan: {MONTH_NAMES[activeMonth - 1]} {year}
                                    </Badge>
                                )}
                            </div>
                            <p className="text-sm text-slate-500 mt-0.5">
                                Tetapkan dan pantau pencapaian target penjualan (Pcs) bulanan dan tahunan seluruh brand secara langsung.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            {/* Month Filter */}
                            <div className="flex items-center gap-2 bg-slate-50 p-1.5 rounded-lg border border-slate-200">
                                <Filter className="h-4 w-4 text-slate-400 ml-1" />
                                <Label htmlFor="month-select" className="text-xs font-semibold text-slate-600">Bulan:</Label>
                                <Select value={activeMonth.toString()} onValueChange={(v) => handleMonthChange(parseInt(v))}>
                                    <SelectTrigger id="month-select" className="w-36 h-8 text-xs bg-white border-slate-300">
                                        <SelectValue placeholder="Pilih Bulan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="0" className="font-semibold text-indigo-600">Semua Bulan (Tahunan)</SelectItem>
                                        {MONTH_NAMES.map((name, i) => (
                                            <SelectItem key={i + 1} value={(i + 1).toString()}>
                                                {i + 1}. {name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Dynamic Year Filter */}
                            <div className="flex items-center gap-2 bg-slate-50 p-1.5 rounded-lg border border-slate-200">
                                <Calendar className="h-4 w-4 text-slate-400 ml-1" />
                                <Label htmlFor="year-select" className="text-xs font-semibold text-slate-600">Tahun:</Label>
                                <Select value={year.toString()} onValueChange={(v) => handleYearChange(parseInt(v))}>
                                    <SelectTrigger id="year-select" className="w-28 h-8 text-xs bg-white border-slate-300">
                                        <SelectValue placeholder="Pilih Tahun" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {yearsList.map(y => (
                                            <SelectItem key={y} value={y.toString()}>{y}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Salin Laporan Button */}
                            <Button 
                                onClick={copyReportToClipboard} 
                                variant="outline" 
                                size="sm"
                                className="h-11 border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100/70 font-semibold"
                            >
                                <Copy className="h-4 w-4 mr-1.5 text-indigo-600" />
                                Salin Laporan
                            </Button>
                        </div>
                    </CardHeader>
                </Card>

                {/* View Controls & Filter Status */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-100/80 p-2 rounded-xl border border-slate-200">
                    <div className="flex items-center gap-1">
                        <Button 
                            variant={activeView === 'all' ? 'default' : 'ghost'} 
                            size="sm" 
                            onClick={() => setActiveView('all')}
                            className={activeView === 'all' ? 'bg-indigo-600 hover:bg-indigo-700 text-white h-8 text-xs' : 'h-8 text-xs text-slate-600'}
                        >
                            <LayoutGrid className="h-3.5 w-3.5 mr-1.5" /> Tampilan Lengkap (Kartu & Matriks)
                        </Button>
                        <Button 
                            variant={activeView === 'cards' ? 'default' : 'ghost'} 
                            size="sm" 
                            onClick={() => setActiveView('cards')}
                            className={activeView === 'cards' ? 'bg-indigo-600 hover:bg-indigo-700 text-white h-8 text-xs' : 'h-8 text-xs text-slate-600'}
                        >
                            <LayoutGrid className="h-3.5 w-3.5 mr-1.5" /> Kartu Brand Only
                        </Button>
                        <Button 
                            variant={activeView === 'matrix' ? 'default' : 'ghost'} 
                            size="sm" 
                            onClick={() => setActiveView('matrix')}
                            className={activeView === 'matrix' ? 'bg-indigo-600 hover:bg-indigo-700 text-white h-8 text-xs' : 'h-8 text-xs text-slate-600'}
                        >
                            <TableIcon className="h-3.5 w-3.5 mr-1.5" /> Tabel Laporan Bulanan
                        </Button>
                    </div>

                    <div className="text-xs text-slate-500 font-medium px-2">
                        {activeMonth > 0 ? (
                            <span>Filter Aktif: <strong className="text-indigo-700">{MONTH_NAMES[activeMonth - 1]} {year}</strong></span>
                        ) : (
                            <span>Filter Aktif: <strong className="text-slate-700">Tahunan {year} (Semua Bulan)</strong></span>
                        )}
                    </div>
                </div>

                {/* SECTION 1: BRAND CARDS (Responsive Grid) */}
                {(activeView === 'all' || activeView === 'cards') && (
                    <div>
                        <div className="flex items-center justify-between mb-3">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                <TrendingUp className="h-4 w-4 text-indigo-600" />
                                Ringkasan Brand {activeMonth > 0 ? `(Bulan ${MONTH_NAMES[activeMonth - 1]} ${year})` : `(Tahun ${year})`}
                            </h3>
                        </div>

                        <div className="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                            {brands.map(brand => {
                                const summary = getBrandSummary(brand.id, activeMonth);
                                return (
                                    <Card key={brand.id} className="relative overflow-hidden border-t-4 shadow-sm hover:shadow-md transition-shadow" style={{ borderTopColor: brand.warna_primary || '#6366F1' }}>
                                        <CardHeader className="pb-3">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <span className="h-3.5 w-3.5 rounded-full" style={{ background: brand.warna_primary || '#6366F1' }} />
                                                    <CardTitle className="text-base font-bold text-slate-800">{brand.nama_brand}</CardTitle>
                                                </div>
                                                <span className="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-600 font-medium">{brand.kode}</span>
                                            </div>
                                            <CardDescription className="text-xs">
                                                {activeMonth > 0 
                                                    ? `Target vs Realisasi Bulan ${MONTH_NAMES[activeMonth - 1]} ${year}`
                                                    : `Target vs Realisasi Tahun ${year} (Total 12 Bulan)`
                                                }
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            {/* Qty / Pcs Metric */}
                                            <div className="space-y-1.5 bg-slate-50/70 p-3 rounded-lg border border-slate-100">
                                                <div className="flex justify-between text-xs font-semibold">
                                                    <span className="text-slate-500">Volume Qty (Pcs)</span>
                                                    <span className={summary.targetPcs > 0 ? summary.pcsPercent >= 100 ? "text-emerald-600 font-bold" : "text-indigo-600 font-bold" : "text-slate-400"}>
                                                        {summary.targetPcs > 0 ? `${summary.pcsPercent}% Tercapai` : 'Belum ada target'}
                                                    </span>
                                                </div>
                                                <div className="flex items-baseline justify-between">
                                                    <span className="text-lg font-extrabold text-slate-800">{summary.actualPcs.toLocaleString('id-ID')} Pcs</span>
                                                    <span className="text-xs text-slate-500">dari target <strong className="text-slate-700">{summary.targetPcs.toLocaleString('id-ID')} Pcs</strong></span>
                                                </div>
                                                <div className="w-full bg-slate-200 rounded-full h-2 overflow-hidden mt-1">
                                                    <div 
                                                        className={`h-2 rounded-full transition-all duration-500 ${summary.pcsPercent >= 100 ? 'bg-emerald-500' : 'bg-indigo-600'}`} 
                                                        style={{ width: `${summary.targetPcs > 0 ? Math.min(100, summary.pcsPercent) : 0}%` }} 
                                                    />
                                                </div>
                                            </div>

                                            <Button 
                                                onClick={() => openTargetModal(brand)} 
                                                className="w-full h-9 text-xs font-semibold" 
                                                variant="outline"
                                            >
                                                <Target className="h-3.5 w-3.5 mr-2 text-indigo-600" /> Atur Target Bulanan
                                            </Button>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* SECTION 2: DIRECT MONTHLY MATRIX REPORT TABLE */}
                {(activeView === 'all' || activeView === 'matrix') && (
                    <Card className="border-slate-200 shadow-sm overflow-hidden">
                        <CardHeader className="bg-slate-50/70 border-b pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <CardTitle className="text-base font-bold text-slate-800 flex items-center gap-2">
                                    <FileSpreadsheet className="h-5 w-5 text-indigo-600" />
                                    Laporan Target & Realisasi Bulanan (Tahun {year})
                                </CardTitle>
                                <CardDescription className="text-xs mt-0.5">
                                    Tabel rincian realisasi (Actual) dan target (Target) seluruh brand dari bulan Januari hingga Desember.
                                </CardDescription>
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="flex items-center gap-1 bg-white border border-slate-200 rounded-md p-1">
                                    <Button 
                                        variant={matrixMode === 'both' ? 'secondary' : 'ghost'} 
                                        size="sm"
                                        onClick={() => setMatrixMode('both')}
                                        className="h-6 text-[11px] px-2 font-medium"
                                    >
                                        Realisasi & Target
                                    </Button>
                                    <Button 
                                        variant={matrixMode === 'actual' ? 'secondary' : 'ghost'} 
                                        size="sm"
                                        onClick={() => setMatrixMode('actual')}
                                        className="h-6 text-[11px] px-2 font-medium"
                                    >
                                        Realisasi (Actual)
                                    </Button>
                                    <Button 
                                        variant={matrixMode === 'target' ? 'secondary' : 'ghost'} 
                                        size="sm"
                                        onClick={() => setMatrixMode('target')}
                                        className="h-6 text-[11px] px-2 font-medium"
                                    >
                                        Target Qty
                                    </Button>
                                </div>

                                <Button 
                                    onClick={copyReportToClipboard}
                                    variant="outline"
                                    size="sm"
                                    className="h-7 text-xs border-indigo-200 text-indigo-700 bg-white hover:bg-indigo-50"
                                >
                                    <Copy className="h-3.5 w-3.5 mr-1" /> Salin Tabel
                                </Button>
                            </div>
                        </CardHeader>

                        <div className="overflow-x-auto">
                            <table className="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr className="bg-slate-100/80 text-slate-600 font-bold uppercase border-b border-slate-200 text-[11px]">
                                        <th className="py-3 px-3 w-10 text-center border-r border-slate-200">#</th>
                                        <th className="py-3 px-4 min-w-[180px] border-r border-slate-200">Nama Brand</th>
                                        {MONTH_SHORT_NAMES.map((mName, idx) => {
                                            const mNum = idx + 1;
                                            const isSelected = activeMonth === mNum;
                                            return (
                                                <th 
                                                    key={mName} 
                                                    className={`py-3 px-2 text-center border-r border-slate-200 min-w-[70px] ${isSelected ? 'bg-indigo-100 text-indigo-900 font-extrabold' : ''}`}
                                                >
                                                    {mName}
                                                </th>
                                            );
                                        })}
                                        <th className="py-3 px-3 text-right bg-slate-100 border-r border-slate-200 min-w-[90px]">Total Target</th>
                                        <th className="py-3 px-3 text-right bg-slate-100 border-r border-slate-200 min-w-[90px]">Total Actual</th>
                                        <th className="py-3 px-3 text-center bg-indigo-50 text-indigo-900 min-w-[80px]">% Capaian</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 bg-white">
                                    {brands.map((brand, bIdx) => {
                                        const brandTargets = targets[brand.id] ?? [];
                                        const brandActuals = actuals[brand.id] ?? [];
                                        const summary = getBrandSummary(brand.id, 0);

                                        return (
                                            <tr key={brand.id} className="hover:bg-slate-50/80 transition-colors">
                                                <td className="py-2.5 px-3 text-center font-mono text-slate-400 border-r border-slate-200">{bIdx + 1}</td>
                                                <td className="py-2.5 px-4 font-bold text-slate-800 border-r border-slate-200">
                                                    <div className="flex items-center gap-2">
                                                        <span className="h-2.5 w-2.5 rounded-full flex-shrink-0" style={{ background: brand.warna_primary || '#6366F1' }} />
                                                        <span>{brand.nama_brand}</span>
                                                        <span className="text-[10px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded ml-auto">{brand.kode}</span>
                                                    </div>
                                                </td>

                                                {/* 12 Months cells */}
                                                {MONTH_SHORT_NAMES.map((_, idx) => {
                                                    const mNum = idx + 1;
                                                    const isSelected = activeMonth === mNum;
                                                    const targetObj = brandTargets.find(t => t.month === mNum);
                                                    const actualObj = brandActuals.find(a => a.month === mNum);
                                                    const tPcs = targetObj ? parseInt(targetObj.target_pcs) : 0;
                                                    const aPcs = actualObj ? parseInt(actualObj.pcs) : 0;
                                                    const pct = tPcs > 0 ? Math.round((aPcs / tPcs) * 100) : 0;

                                                    return (
                                                        <td 
                                                            key={mNum} 
                                                            className={`py-2 px-2 text-center border-r border-slate-200 font-mono ${isSelected ? 'bg-indigo-50/70 border-x-2 border-indigo-300' : ''}`}
                                                        >
                                                            {matrixMode === 'both' && (
                                                                <div className="flex flex-col items-center">
                                                                    <span className="font-extrabold text-slate-800 text-[11px]">{aPcs.toLocaleString('id-ID')}</span>
                                                                    <span className="text-[10px] text-slate-400">/ {tPcs.toLocaleString('id-ID')}</span>
                                                                    {tPcs > 0 && (
                                                                        <span className={`text-[9px] font-sans font-semibold mt-0.5 ${pct >= 100 ? 'text-emerald-600' : 'text-indigo-600'}`}>
                                                                            {pct}%
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            )}
                                                            {matrixMode === 'actual' && (
                                                                <span className="font-bold text-slate-800">{aPcs.toLocaleString('id-ID')}</span>
                                                            )}
                                                            {matrixMode === 'target' && (
                                                                <span className="font-medium text-slate-600">{tPcs.toLocaleString('id-ID')}</span>
                                                            )}
                                                        </td>
                                                    );
                                                })}

                                                {/* Total Columns */}
                                                <td className="py-2.5 px-3 text-right font-mono font-semibold text-slate-600 bg-slate-50/50 border-r border-slate-200">
                                                    {summary.targetPcs.toLocaleString('id-ID')} Pcs
                                                </td>
                                                <td className="py-2.5 px-3 text-right font-mono font-extrabold text-slate-800 bg-slate-50/50 border-r border-slate-200">
                                                    {summary.actualPcs.toLocaleString('id-ID')} Pcs
                                                </td>
                                                <td className="py-2.5 px-3 text-center bg-indigo-50/50 font-bold">
                                                    <Badge 
                                                        variant="secondary" 
                                                        className={summary.pcsPercent >= 100 ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : summary.targetPcs > 0 ? 'bg-indigo-100 text-indigo-800 border-indigo-200' : 'bg-slate-100 text-slate-500'}
                                                    >
                                                        {summary.targetPcs > 0 ? `${summary.pcsPercent}%` : '-'}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}
            </div>

            {/* Set Monthly Target Modal */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="max-w-xl max-h-[90vh] flex flex-col p-0 border-slate-200">
                    <form onSubmit={submit} className="flex flex-col h-full overflow-hidden">
                        <DialogHeader className="p-6 pb-4 border-b bg-slate-50/50">
                            <DialogTitle className="flex items-center gap-2 text-slate-800">
                                <Target className="h-5 w-5 text-indigo-600" />
                                Atur Target Bulanan: {selectedBrand?.nama_brand} ({year})
                            </DialogTitle>
                            <DialogDescription className="text-xs text-slate-500">
                                Masukkan target jumlah quantity (Pcs) untuk setiap bulan di tahun {year}.
                            </DialogDescription>
                        </DialogHeader>

                        {/* Modal Body with scrollable table */}
                        <div className="flex-1 overflow-y-auto p-6 space-y-4">
                            <div className="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-900">
                                <div className="flex items-center gap-2">
                                    <AlertCircle className="h-4 w-4 text-amber-600 flex-shrink-0" />
                                    <span>Salin cepat nilai target bulan Januari ke bulan-bulan berikutnya.</span>
                                </div>
                                <Button 
                                    type="button" 
                                    size="sm" 
                                    variant="outline" 
                                    onClick={copyJanuaryToAll}
                                    className="h-7 border-amber-300 text-amber-900 bg-amber-100/50 hover:bg-amber-100 font-semibold"
                                >
                                    <Copy className="h-3 w-3 mr-1" /> Salin Jan ke Semua
                                </Button>
                            </div>

                            <div className="border border-slate-200 rounded-lg overflow-hidden">
                                <table className="w-full text-xs">
                                    <thead className="bg-slate-100 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                                        <tr>
                                            <th className="px-4 py-2.5 text-left w-1/3">Bulan</th>
                                            <th className="px-4 py-2.5 text-left w-1/3">Target Qty (Pcs)</th>
                                            <th className="px-4 py-2.5 text-right w-1/3 bg-slate-50">Realisasi (Actual)</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-200 bg-white">
                                        {data.targets.map((t, idx) => {
                                            const monthActual = (actuals[selectedBrand?.id] ?? []).find(a => a.month === t.month);
                                            const actualPcs = monthActual ? parseInt(monthActual.pcs) : 0;

                                            return (
                                                <tr key={t.month} className="hover:bg-slate-50/50">
                                                    <td className="px-4 py-2.5 font-semibold text-slate-700">
                                                        {MONTH_NAMES[t.month - 1]}
                                                    </td>
                                                    <td className="px-4 py-2.5">
                                                        <div className="relative">
                                                            <Input
                                                                type="number"
                                                                value={t.target_pcs || ''}
                                                                onChange={(e) => updateTargetField(idx, 'target_pcs', e.target.value)}
                                                                className="pr-8 h-8 text-xs font-mono font-semibold"
                                                                placeholder="0"
                                                            />
                                                            <span className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 font-semibold">Pcs</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-2.5 text-right bg-slate-50/40 font-mono text-slate-600 font-bold">
                                                        {actualPcs.toLocaleString('id-ID')} Pcs
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <DialogFooter className="p-4 border-t bg-slate-50/70">
                            <Button type="button" variant="outline" size="sm" onClick={() => setIsModalOpen(false)} disabled={processing}>
                                Batal
                            </Button>
                            <Button type="submit" size="sm" disabled={processing} className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                                <Save className="h-3.5 w-3.5 mr-1.5" /> Simpan Target
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
