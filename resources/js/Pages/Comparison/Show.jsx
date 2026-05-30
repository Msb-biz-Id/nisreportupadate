import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Trophy, TrendingUp, Package, Users, Filter, Calendar, BarChart3, LineChart, Table2, Layers } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import Chart from '@/Components/Chart';
import { formatRupiah } from '@/lib/utils';

export default function ComparisonShow({
    availableBrands,
    selectedBrandIds,
    selectedYears,
    singleBrandId,
    singleYear,
    mode,
    result
}) {
    // Mode State
    const [currentMode, setCurrentMode] = useState(mode || 'brands');
    
    // Filters States
    const [selBrands, setSelBrands] = useState(selectedBrandIds || []);
    const [selYears, setSelYears] = useState(selectedYears || [new Date().getFullYear(), new Date().getFullYear() - 1]);
    const [selSingleBrand, setSelSingleBrand] = useState(singleBrandId || (availableBrands[0]?.id ?? ''));
    const [selSingleYear, setSelSingleYear] = useState(singleYear || new Date().getFullYear());
    
    // Metric state for chart & highlight ('omset', 'po', 'pcs')
    const [activeMetric, setActiveMetric] = useState('omset');

    const monthsKeys = Array.from({ length: 12 }, (_, i) => i + 1);
    const monthsNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    function toggleBrand(id) {
        setSelBrands((cur) => cur.includes(id) ? cur.filter((x) => x !== id) : [...cur, id]);
    }

    function toggleYear(year) {
        setSelYears((cur) => cur.includes(year) ? cur.filter((x) => x !== year) : [...cur, year]);
    }

    function handleModeChange(newMode) {
        setCurrentMode(newMode);
        // Clean default arrays if needed
    }

    function apply() {
        const params = { mode: currentMode };
        
        if (currentMode === 'brands') {
            if (selBrands.length === 0) {
                alert('Pilih minimal 1 brand untuk dianalisis.');
                return;
            }
            params.brand_ids = selBrands;
            params.year = selSingleYear;
        } else {
            if (selYears.length === 0) {
                alert('Pilih minimal 1 tahun untuk perbandingan.');
                return;
            }
            params.brand_id = selSingleBrand;
            params.years = selYears;
        }
        
        router.get(route('comparison.show'), params, { preserveScroll: true });
    }

    // Chart Series and Options Setup
    let chartSeries = [];
    let chartCategories = monthsNames.map(m => m.substring(0, 3));
    let colorPalette = ['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EC4899', '#06B6D4'];

    if (currentMode === 'brands') {
        const brandData = result?.data ?? {};
        chartSeries = Object.entries(brandData).map(([id, b]) => {
            const dataPoints = monthsKeys.map(k => {
                const monthInfo = b.months[k] ?? {};
                return activeMetric === 'omset' ? monthInfo.total_omset ?? 0 :
                       activeMetric === 'po' ? monthInfo.total_po ?? 0 :
                       monthInfo.total_pcs ?? 0;
            });
            return {
                name: b.brand_name,
                data: dataPoints,
                color: b.warna || '#3B82F6'
            };
        });
    } else {
        const yearData = result?.data ?? {};
        chartSeries = Object.entries(yearData).map(([year, info], idx) => {
            const dataPoints = monthsKeys.map(k => {
                const monthInfo = info.months[k] ?? {};
                return activeMetric === 'omset' ? monthInfo.total_omset ?? 0 :
                       activeMetric === 'po' ? monthInfo.total_po ?? 0 :
                       monthInfo.total_pcs ?? 0;
            });
            return {
                name: `Tahun ${year}`,
                data: dataPoints,
                color: colorPalette[idx % colorPalette.length]
            };
        });
    }

    const chartOptions = {
        chart: {
            toolbar: { show: true },
            zoom: { enabled: false }
        },
        xaxis: { categories: chartCategories },
        yaxis: {
            labels: {
                formatter: (val) => activeMetric === 'omset' ? formatRupiah(val) : val
            }
        },
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0 } },
        tooltip: {
            y: {
                formatter: (val) => activeMetric === 'omset' ? formatRupiah(val) : `${val} unit`
            }
        }
    };

    return (
        <AppLayout title="Pertumbuhan & Perbandingan Kinerja">
            <Head title="Analisis Pertumbuhan & Kinerja" />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-violet-600 to-indigo-600 bg-clip-text text-transparent">
                            Analisis Pertumbuhan & Perbandingan Multi-Tahun
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Lacak grafik pertumbuhan bulanan, perbandingan lintas tahun, dan bandingkan antar brand secara dinamis.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline" size="sm" className="border-indigo-200 hover:border-indigo-300 text-indigo-700 bg-indigo-50/50 flex items-center gap-1.5 font-semibold">
                            <a href={route('comparison.export.excel', {
                                mode: currentMode,
                                brand_ids: selBrands,
                                brand_id: selSingleBrand,
                                year: selSingleYear,
                                years: selYears
                            })}>
                                <svg className="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Excel
                            </a>
                        </Button>
                        <Button asChild variant="outline" size="sm" className="border-indigo-200 hover:border-indigo-300 text-indigo-700 bg-indigo-50/50 flex items-center gap-1.5 font-semibold">
                            <a href={route('comparison.export.pdf', {
                                mode: currentMode,
                                brand_ids: selBrands,
                                brand_id: selSingleBrand,
                                year: selSingleYear,
                                years: selYears
                            })}>
                                <svg className="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                PDF
                            </a>
                        </Button>
                    </div>
                </div>

                {/* Mode Selector Tab Container */}
                <div className="flex bg-muted/70 p-1.5 rounded-xl border max-w-lg">
                    <button
                        onClick={() => handleModeChange('brands')}
                        className={`flex-1 flex items-center justify-center gap-2 py-2 text-sm font-medium rounded-lg transition ${currentMode === 'brands' ? 'bg-background shadow text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground'}`}
                    >
                        <Layers className="h-4 w-4" /> Perbandingan Lintas Brand
                    </button>
                    <button
                        onClick={() => handleModeChange('years')}
                        className={`flex-1 flex items-center justify-center gap-2 py-2 text-sm font-medium rounded-lg transition ${currentMode === 'years' ? 'bg-background shadow text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground'}`}
                    >
                        <TrendingUp className="h-4 w-4" /> Perbandingan Multi-Tahun
                    </button>
                </div>

                {/* Interactive Dynamic Filters Panel */}
                <Card className="border-indigo-100 shadow-sm">
                    <CardHeader className="pb-3 border-b bg-slate-50/50">
                        <CardTitle className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <Filter className="h-4 w-4 text-violet-500" /> Filter Parameter Analisis
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4 space-y-4">
                        {currentMode === 'brands' ? (
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                <div className="lg:col-span-2 space-y-1.5">
                                    <Label className="text-xs text-muted-foreground font-semibold">Pilih Brand untuk Dibandingkan</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {availableBrands.map((b) => {
                                            const active = selBrands.includes(b.id);
                                            return (
                                                <button
                                                    key={b.id}
                                                    type="button"
                                                    onClick={() => toggleBrand(b.id)}
                                                    className={`flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold shadow-sm transition ${active ? 'border-violet-600 bg-violet-50 text-violet-700 ring-2 ring-violet-200' : 'bg-background hover:bg-slate-50 text-slate-600'}`}
                                                >
                                                    <span className="h-2 w-2 rounded-full" style={{ background: b.warna_primary || '#3B82F6' }} />
                                                    {b.nama_brand}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs text-muted-foreground font-semibold">Tahun Analisis</Label>
                                    <select
                                        value={selSingleYear}
                                        onChange={(e) => setSelSingleYear(Number(e.target.value))}
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    >
                                        {[2026, 2025, 2024, 2023].map((y) => (
                                            <option key={y} value={y}>Tahun {y}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs text-muted-foreground font-semibold">Pilih Brand</Label>
                                    <select
                                        value={selSingleBrand}
                                        onChange={(e) => setSelSingleBrand(Number(e.target.value))}
                                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    >
                                        {availableBrands.map((b) => (
                                            <option key={b.id} value={b.id}>{b.nama_brand} ({b.kode})</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="lg:col-span-2 space-y-1.5">
                                    <Label className="text-xs text-muted-foreground font-semibold font-semibold">Tahun untuk Dibandingkan</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {[2026, 2025, 2024, 2023].map((y) => {
                                            const active = selYears.includes(y);
                                            return (
                                                <button
                                                    key={y}
                                                    type="button"
                                                    onClick={() => toggleYear(y)}
                                                    className={`flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold shadow-sm transition ${active ? 'border-violet-600 bg-violet-50 text-violet-700 ring-2 ring-violet-200' : 'bg-background hover:bg-slate-50 text-slate-600'}`}
                                                >
                                                    <Calendar className="h-3 w-3 text-violet-500" />
                                                    Tahun {y}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="flex justify-end pt-2 border-t">
                            <Button onClick={apply} className="bg-indigo-600 hover:bg-indigo-700 flex items-center gap-2">
                                <Filter className="h-4 w-4" /> Proses Analisis
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Metric Selection Switcher */}
                <div className="flex justify-between items-center bg-card p-3 rounded-lg border shadow-sm">
                    <span className="text-xs font-bold text-muted-foreground uppercase tracking-wide">Fokus Grafik Metric:</span>
                    <div className="flex bg-muted/80 p-0.5 rounded-lg border">
                        <button
                            onClick={() => setActiveMetric('omset')}
                            className={`px-3 py-1 text-xs font-semibold rounded-md transition ${activeMetric === 'omset' ? 'bg-background shadow text-violet-700 font-bold' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Omset (Rupiah)
                        </button>
                        <button
                            onClick={() => setActiveMetric('po')}
                            className={`px-3 py-1 text-xs font-semibold rounded-md transition ${activeMetric === 'po' ? 'bg-background shadow text-blue-700 font-bold' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Jumlah PO
                        </button>
                        <button
                            onClick={() => setActiveMetric('pcs')}
                            className={`px-3 py-1 text-xs font-semibold rounded-md transition ${activeMetric === 'pcs' ? 'bg-background shadow text-emerald-700 font-bold' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            Total Pcs
                        </button>
                    </div>
                </div>

                {/* Main Interactive Chart */}
                <Card className="shadow-sm border-indigo-100">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-semibold text-slate-800 flex items-center gap-2">
                            <LineChart className="h-4 w-4 text-indigo-500" /> Visualisasi Grafik Kinerja
                        </CardTitle>
                        <CardDescription>
                            Grafik perbandingan {activeMetric === 'omset' ? 'omset' : activeMetric === 'po' ? 'jumlah po' : 'total pcs'} per bulan.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {chartSeries.length === 0 ? (
                            <div className="py-12 text-center text-muted-foreground">Belum ada data untuk ditampilkan. Silakan pilih parameter di atas.</div>
                        ) : (
                            <Chart
                                type="area"
                                height={350}
                                series={chartSeries}
                                options={chartOptions}
                            />
                        )}
                    </CardContent>
                </Card>

                {/* Comprehensive Tabular Data Numbers per Month to Full-Year Totals */}
                <Card className="shadow-sm border-indigo-100">
                    <CardHeader className="border-b bg-slate-50/50 pb-3 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-base font-semibold text-slate-800 flex items-center gap-2">
                                <Table2 className="h-4 w-4 text-violet-500" /> Data Angka Pertumbuhan & Kinerja Detil
                            </CardTitle>
                            <CardDescription>
                                Jumlah PO, total quantity (pcs), dan nilai pendapatan per bulan hingga total tahun.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0 overflow-x-auto">
                        <table className="w-full text-sm text-left border-collapse min-w-[700px]">
                            <thead className="text-xs uppercase bg-slate-50 text-slate-600 font-semibold border-b">
                                <tr>
                                    <th className="px-4 py-3 font-semibold border-r">Bulan</th>
                                    {currentMode === 'brands' ? (
                                        Object.entries(result?.data ?? {}).map(([id, b]) => (
                                            <th key={id} colSpan={3} className="px-4 py-3 text-center border-r font-bold" style={{ borderTop: `4px solid ${b.warna || '#3B82F6'}` }}>
                                                {b.brand_name} ({b.kode})
                                            </th>
                                        ))
                                    ) : (
                                        selectedYears.map((year) => (
                                            <th key={year} colSpan={3} className="px-4 py-3 text-center border-r font-bold border-t-4 border-indigo-500">
                                                Tahun {year}
                                            </th>
                                        ))
                                    )}
                                </tr>
                                <tr className="border-b bg-slate-100/50 text-[10px] text-slate-500">
                                    <th className="px-4 py-2 border-r"></th>
                                    {currentMode === 'brands' ? (
                                        Object.keys(result?.data ?? {}).map((id) => (
                                            <>
                                                <th key={`${id}-po`} className="px-2 py-2 text-right">PO</th>
                                                <th key={`${id}-pcs`} className="px-2 py-2 text-right">Pcs</th>
                                                <th key={`${id}-omset`} className="px-2 py-2 text-right border-r">Omset</th>
                                            </>
                                        ))
                                    ) : (
                                        selectedYears.map((year) => (
                                            <>
                                                <th key={`${year}-po`} className="px-2 py-2 text-right">PO</th>
                                                <th key={`${year}-pcs`} className="px-2 py-2 text-right">Pcs</th>
                                                <th key={`${year}-omset`} className="px-2 py-2 text-right border-r">Omset</th>
                                            </>
                                        ))
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y text-slate-700">
                                {monthsKeys.map((k) => (
                                    <tr key={k} className="hover:bg-slate-50/50 transition">
                                        <td className="px-4 py-3 font-medium border-r">{monthsNames[k-1]}</td>
                                        {currentMode === 'brands' ? (
                                            Object.entries(result?.data ?? {}).map(([id, b]) => {
                                                const m = b.months[k] ?? { total_po: 0, total_pcs: 0, total_omset: 0 };
                                                return (
                                                    <>
                                                        <td className="px-2 py-3 text-right font-mono">{m.total_po}</td>
                                                        <td className="px-2 py-3 text-right font-mono">{m.total_pcs}</td>
                                                        <td className="px-2 py-3 text-right font-mono border-r font-medium text-slate-900">{formatRupiah(m.total_omset)}</td>
                                                    </>
                                                );
                                            })
                                        ) : (
                                            selectedYears.map((year) => {
                                                const m = result?.data[year]?.months[k] ?? { total_po: 0, total_pcs: 0, total_omset: 0 };
                                                return (
                                                    <>
                                                        <td className="px-2 py-3 text-right font-mono">{m.total_po}</td>
                                                        <td className="px-2 py-3 text-right font-mono">{m.total_pcs}</td>
                                                        <td className="px-2 py-3 text-right font-mono border-r font-medium text-slate-900">{formatRupiah(m.total_omset)}</td>
                                                    </>
                                                );
                                            })
                                        )}
                                    </tr>
                                ))}
                                
                                {/* Bottom Total Row */}
                                <tr className="bg-slate-100/80 font-bold border-t-2 text-slate-900 border-slate-300">
                                    <td className="px-4 py-3.5 border-r font-extrabold text-indigo-700 uppercase tracking-wide">TOTAL TAHUNAN</td>
                                    {currentMode === 'brands' ? (
                                        Object.entries(result?.data ?? {}).map(([id, b]) => (
                                            <>
                                                <td className="px-2 py-3.5 text-right font-mono text-blue-700">{b.totals.total_po}</td>
                                                <td className="px-2 py-3.5 text-right font-mono text-emerald-700">{b.totals.total_pcs}</td>
                                                <td className="px-2 py-3.5 text-right font-mono border-r text-violet-700">{formatRupiah(b.totals.total_omset)}</td>
                                            </>
                                        ))
                                    ) : (
                                        selectedYears.map((year) => {
                                            const totals = result?.data[year]?.totals ?? { total_po: 0, total_pcs: 0, total_omset: 0 };
                                            return (
                                                <>
                                                    <td className="px-2 py-3.5 text-right font-mono text-blue-700">{totals.total_po}</td>
                                                    <td className="px-2 py-3.5 text-right font-mono text-emerald-700">{totals.total_pcs}</td>
                                                    <td className="px-2 py-3.5 text-right font-mono border-r text-violet-700">{formatRupiah(totals.total_omset)}</td>
                                                </>
                                            );
                                        })
                                    )}
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
