import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    BarChart3,
    TrendingUp,
    FileSpreadsheet,
    FileText,
    Calendar,
    Filter,
    RotateCcw,
    Building2,
    Store,
    Users,
    Wallet,
    CreditCard,
    AlertCircle,
    ArrowUpRight,
    Briefcase,
} from 'lucide-react';

export default function ReportsIndex({
    summary = {},
    dept_breakdown = [],
    div_breakdown = [],
    loc_breakdown = [],
    top_vendors = [],
    recent_orders = [],
    locations = [],
    departments = [],
    divisions = [],
    filters = {},
}) {
    const [period, setPeriod] = useState(filters?.period || 'this_month');
    const [dateFrom, setDateFrom] = useState(filters?.date_from || '');
    const [dateTo, setDateTo] = useState(filters?.date_to || '');
    const [department, setDepartment] = useState(filters?.department || '');
    const [division, setDivision] = useState(filters?.division || '');
    const [locationId, setLocationId] = useState(filters?.location_id ? String(filters.location_id) : '');
    const [orderType, setOrderType] = useState(filters?.order_type || 'ALL');
    const [activeTab, setActiveTab] = useState('dept');

    const handleFilter = () => {
        router.get(
            route('purchasing.reports.index'),
            {
                period,
                date_from: dateFrom,
                date_to: dateTo,
                department: department || undefined,
                division: division || undefined,
                location_id: locationId || undefined,
                order_type: orderType !== 'ALL' ? orderType : undefined,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleReset = () => {
        setPeriod('this_month');
        setDateFrom('');
        setDateTo('');
        setDepartment('');
        setDivision('');
        setLocationId('');
        setOrderType('ALL');
        router.get(route('purchasing.reports.index'), {}, { preserveState: false });
    };

    const handleExportExcel = () => {
        const params = new URLSearchParams({
            period,
            date_from: dateFrom,
            date_to: dateTo,
            department,
            division,
            location_id: locationId,
            order_type: orderType,
        });
        window.open(`${route('purchasing.reports.export.excel')}?${params.toString()}`, '_blank');
    };

    const handleExportPdf = () => {
        const params = new URLSearchParams({
            period,
            date_from: dateFrom,
            date_to: dateTo,
            department,
            division,
            location_id: locationId,
            order_type: orderType,
        });
        window.open(`${route('purchasing.reports.export.pdf')}?${params.toString()}`, '_blank');
    };

    const formatRupiah = (num) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(num || 0);
    };

    return (
        <AppLayout>
            <Head title="Laporan & Rekapitulasi Pengadaan" />

            <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
                {/* Header Title & Export Actions */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                                <BarChart3 className="w-6 h-6" />
                            </span>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                                Laporan & Analitik Pengadaan
                            </h1>
                        </div>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Rekapitulasi anggaran pengeluaran Opex & Capex, alokasi per divisi, lokasi ruko, dan vendor
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <Button
                            onClick={handleExportExcel}
                            variant="outline"
                            className="gap-2 border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-emerald-950"
                        >
                            <FileSpreadsheet className="w-4 h-4 text-emerald-600" />
                            Ekspor Excel (.xlsx)
                        </Button>
                        <Button
                            onClick={handleExportPdf}
                            className="gap-2 bg-rose-600 hover:bg-rose-700 text-white shadow-sm"
                        >
                            <FileText className="w-4 h-4" />
                            Cetak / Ekspor PDF
                        </Button>
                    </div>
                </div>

                {/* Filter Control Card */}
                <Card className="border border-slate-200 dark:border-slate-800 shadow-sm bg-slate-50/50 dark:bg-slate-900/50">
                    <CardHeader className="pb-3 pt-4 px-5">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-slate-200">
                                <Filter className="w-4 h-4 text-emerald-600" />
                                Filter Rekapitulasi Data
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={handleReset}
                                className="h-8 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"
                            >
                                <RotateCcw className="w-3.5 h-3.5 mr-1" />
                                Reset Filter
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="px-5 pb-5 pt-0">
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                            <div>
                                <label className="text-xs font-medium text-slate-600 dark:text-slate-400 block mb-1.5">
                                    Periode Waktu
                                </label>
                                <select
                                    value={period}
                                    onChange={(e) => setPeriod(e.target.value)}
                                    className="w-full h-9 text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-emerald-500"
                                >
                                    <option value="this_month">Bulan Ini</option>
                                    <option value="last_month">Bulan Lalu</option>
                                    <option value="this_year">Tahun Ini</option>
                                    <option value="custom">Kustom Tanggal</option>
                                </select>
                            </div>

                            <div>
                                <label className="text-xs font-medium text-slate-600 dark:text-slate-400 block mb-1.5">
                                    Dari Tanggal
                                </label>
                                <Input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => {
                                        setDateFrom(e.target.value);
                                        setPeriod('custom');
                                    }}
                                    className="h-9 text-xs bg-white dark:bg-slate-950"
                                />
                            </div>

                            <div>
                                <label className="text-xs font-medium text-slate-600 dark:text-slate-400 block mb-1.5">
                                    Sampai Tanggal
                                </label>
                                <Input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => {
                                        setDateTo(e.target.value);
                                        setPeriod('custom');
                                    }}
                                    className="h-9 text-xs bg-white dark:bg-slate-950"
                                />
                            </div>

                            <div>
                                <label className="text-xs font-medium text-slate-600 dark:text-slate-400 block mb-1.5">
                                    Departemen
                                </label>
                                <select
                                    value={department}
                                    onChange={(e) => setDepartment(e.target.value)}
                                    className="w-full h-9 text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-emerald-500"
                                >
                                    <option value="">Semua Departemen</option>
                                    {departments && departments.map((d, idx) => {
                                        const name = typeof d === 'object' ? d.name : d;
                                        const code = typeof d === 'object' ? ` (${d.code})` : '';
                                        return (
                                            <option key={idx} value={name}>
                                                {name}{code}
                                            </option>
                                        );
                                    })}
                                </select>
                            </div>

                            <div>
                                <label className="text-xs font-medium text-slate-600 dark:text-slate-400 block mb-1.5">
                                    Lokasi Ruko
                                </label>
                                <select
                                    value={locationId}
                                    onChange={(e) => setLocationId(e.target.value)}
                                    className="w-full h-9 text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 focus:ring-emerald-500"
                                >
                                    <option value="">Semua Lokasi</option>
                                    {locations && locations.map((loc) => (
                                        <option key={loc.id} value={String(loc.id)}>
                                            {loc.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex items-end gap-2">
                                <Button
                                    onClick={handleFilter}
                                    className="w-full h-9 text-xs bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5"
                                >
                                    <Filter className="w-3.5 h-3.5" />
                                    Terapkan
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* 4 Executive KPI Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl -mr-6 -mt-6"></div>
                        <CardHeader className="pb-2 pt-4 px-5">
                            <div className="flex items-center justify-between">
                                <CardDescription className="text-xs font-medium text-slate-500">
                                    Total Belanja (All In)
                                </CardDescription>
                                <span className="p-1.5 rounded-md bg-emerald-50 dark:bg-emerald-950 text-emerald-600">
                                    <Wallet className="w-4 h-4" />
                                </span>
                            </div>
                            <CardTitle className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                                {formatRupiah(summary.total_spending)}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-5 pb-4 pt-0">
                            <p className="text-xs text-slate-500 flex items-center gap-1">
                                <span className="font-semibold text-slate-700 dark:text-slate-300">
                                    {summary.total_orders}
                                </span>{' '}
                                total transaksi PO
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl -mr-6 -mt-6"></div>
                        <CardHeader className="pb-2 pt-4 px-5">
                            <div className="flex items-center justify-between">
                                <CardDescription className="text-xs font-medium text-slate-500">
                                    Biaya Operasional
                                </CardDescription>
                                <span className="p-1.5 rounded-md bg-blue-50 dark:bg-blue-950 text-blue-600">
                                    <TrendingUp className="w-4 h-4" />
                                </span>
                            </div>
                            <CardTitle className="text-2xl font-bold tracking-tight text-blue-600 dark:text-blue-400">
                                {formatRupiah(summary.total_opex)}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-5 pb-4 pt-0">
                            <p className="text-xs text-slate-500">
                                {summary.total_spending > 0
                                    ? Math.round((summary.total_opex / summary.total_spending) * 100)
                                    : 0}
                                % dari total anggaran
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl -mr-6 -mt-6"></div>
                        <CardHeader className="pb-2 pt-4 px-5">
                            <div className="flex items-center justify-between">
                                <CardDescription className="text-xs font-medium text-slate-500">
                                    Pengadaan Aset Tetap
                                </CardDescription>
                                <span className="p-1.5 rounded-md bg-purple-50 dark:bg-purple-950 text-purple-600">
                                    <Briefcase className="w-4 h-4" />
                                </span>
                            </div>
                            <CardTitle className="text-2xl font-bold tracking-tight text-purple-600 dark:text-purple-400">
                                {formatRupiah(summary.total_capex)}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-5 pb-4 pt-0">
                            <p className="text-xs text-slate-500">
                                {summary.total_spending > 0
                                    ? Math.round((summary.total_capex / summary.total_spending) * 100)
                                    : 0}
                                % belanja inventaris modal
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl -mr-6 -mt-6"></div>
                        <CardHeader className="pb-2 pt-4 px-5">
                            <div className="flex items-center justify-between">
                                <CardDescription className="text-xs font-medium text-slate-500">
                                    Tagihan Belum Lunas
                                </CardDescription>
                                <span className="p-1.5 rounded-md bg-amber-50 dark:bg-amber-950 text-amber-600">
                                    <CreditCard className="w-4 h-4" />
                                </span>
                            </div>
                            <CardTitle className="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                                {formatRupiah(summary.total_unpaid)}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-5 pb-4 pt-0">
                            <p className="text-xs text-slate-500">
                                Terbayar:{' '}
                                <span className="text-emerald-600 font-medium">
                                    {formatRupiah(summary.total_paid)}
                                </span>
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Tab Navigation for Breakdowns */}
                <div className="flex border-b border-slate-200 dark:border-slate-800 gap-2">
                    <button
                        onClick={() => setActiveTab('dept')}
                        className={`pb-3 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
                            activeTab === 'dept'
                                ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <Building2 className="w-4 h-4" />
                        Per Departemen
                    </button>
                    <button
                        onClick={() => setActiveTab('div')}
                        className={`pb-3 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
                            activeTab === 'div'
                                ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <Users className="w-4 h-4" />
                        Per Divisi
                    </button>
                    <button
                        onClick={() => setActiveTab('loc')}
                        className={`pb-3 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
                            activeTab === 'loc'
                                ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <Store className="w-4 h-4" />
                        Per Lokasi Ruko
                    </button>
                    <button
                        onClick={() => setActiveTab('vendor')}
                        className={`pb-3 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
                            activeTab === 'vendor'
                                ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <Briefcase className="w-4 h-4" />
                        Top 8 Vendor
                    </button>
                    <button
                        onClick={() => setActiveTab('recent')}
                        className={`pb-3 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
                            activeTab === 'recent'
                                ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <Calendar className="w-4 h-4" />
                        Daftar Transaksi Terbaru
                    </button>
                </div>

                {/* Tab 1: Departemen */}
                {activeTab === 'dept' && (
                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm">
                        <CardHeader className="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <CardTitle className="text-base font-semibold">
                                Rekapitulasi Pengeluaran per Departemen
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Perbandingan belanja Opex vs Capex serta porsi persentase terhadap total pengeluaran
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-slate-900/60 text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">
                                        <tr>
                                            <th className="py-3 px-6">Departemen</th>
                                            <th className="py-3 px-4 text-right">Belanja Operasional</th>
                                            <th className="py-3 px-4 text-right">Pengadaan Aset</th>
                                            <th className="py-3 px-4 text-right">Total Belanja</th>
                                            <th className="py-3 px-4 text-center">Jumlah PO</th>
                                            <th className="py-3 px-6 text-right">Porsi (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {dept_breakdown.length === 0 ? (
                                            <tr>
                                                <td colSpan="6" className="py-8 text-center text-slate-400">
                                                    Tidak ada data transaksi pengadaan pada filter periode ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            dept_breakdown.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/40">
                                                    <td className="py-3.5 px-6 font-medium text-slate-800 dark:text-slate-200">
                                                        {row.department}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-right text-slate-600 dark:text-slate-300 font-mono text-xs">
                                                        {formatRupiah(row.total_opex)}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-right text-purple-600 dark:text-purple-400 font-mono text-xs">
                                                        {formatRupiah(row.total_capex)}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-right font-bold text-slate-900 dark:text-slate-100 font-mono text-xs">
                                                        {formatRupiah(row.total_spending)}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-center">
                                                        <Badge variant="outline" className="text-xs font-semibold">
                                                            {row.order_count} PO
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3.5 px-6 text-right">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <div className="w-16 bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                                                <div
                                                                    className="bg-emerald-500 h-full rounded-full"
                                                                    style={{ width: `${Math.min(100, row.percentage)}%` }}
                                                                ></div>
                                                            </div>
                                                            <span className="text-xs font-semibold text-slate-700 dark:text-slate-300 min-w-8">
                                                                {row.percentage}%
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Tab 2: Divisi */}
                {activeTab === 'div' && (
                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm">
                        <CardHeader className="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <CardTitle className="text-base font-semibold">
                                Rekapitulasi Pengeluaran per Divisi
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Distribusi biaya berdasarkan unit divisi kerja pemohon
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-slate-900/60 text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">
                                        <tr>
                                            <th className="py-3 px-6">Divisi</th>
                                            <th className="py-3 px-6">Departemen Terkait</th>
                                            <th className="py-3 px-4 text-center">Jumlah Transaksi</th>
                                            <th className="py-3 px-6 text-right">Total Akumulasi Biaya</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {div_breakdown.length === 0 ? (
                                            <tr>
                                                <td colSpan="4" className="py-8 text-center text-slate-400">
                                                    Tidak ada data divisi pada periode ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            div_breakdown.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/40">
                                                    <td className="py-3.5 px-6 font-medium text-slate-800 dark:text-slate-200">
                                                        {row.division}
                                                    </td>
                                                    <td className="py-3.5 px-6 text-slate-500">
                                                        {row.department}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-center">
                                                        <Badge variant="outline">{row.order_count} PO</Badge>
                                                    </td>
                                                    <td className="py-3.5 px-6 text-right font-bold text-slate-900 dark:text-slate-100 font-mono text-xs">
                                                        {formatRupiah(row.total_spending)}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Tab 3: Lokasi Ruko */}
                {activeTab === 'loc' && (
                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm">
                        <CardHeader className="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <CardTitle className="text-base font-semibold">
                                Rekapitulasi Pengeluaran per Lokasi Ruko
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Akumulasi belanja operasional dan fisik berdasarkan ruko cabang NIS
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-slate-900/60 text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">
                                        <tr>
                                            <th className="py-3 px-6">Lokasi Ruko</th>
                                            <th className="py-3 px-4 text-center">Jumlah Transaksi</th>
                                            <th className="py-3 px-6 text-right">Total Akumulasi Belanja</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {loc_breakdown.length === 0 ? (
                                            <tr>
                                                <td colSpan="3" className="py-8 text-center text-slate-400">
                                                    Tidak ada data lokasi pada periode ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            loc_breakdown.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/40">
                                                    <td className="py-3.5 px-6 font-medium text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                                        <Store className="w-4 h-4 text-slate-400" />
                                                        {row.location_name}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-center">
                                                        <Badge variant="outline">{row.order_count} PO</Badge>
                                                    </td>
                                                    <td className="py-3.5 px-6 text-right font-bold text-slate-900 dark:text-slate-100 font-mono text-xs">
                                                        {formatRupiah(row.total_spending)}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Tab 4: Top 8 Vendor */}
                {activeTab === 'vendor' && (
                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm">
                        <CardHeader className="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <CardTitle className="text-base font-semibold">
                                8 Suplier / Vendor dengan Transaksi Terbesar
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Mitra pengadaan utama berdasarkan akumulasi nilai transaksi
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-slate-900/60 text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">
                                        <tr>
                                            <th className="py-3 px-4 text-center w-12">#</th>
                                            <th className="py-3 px-6">Nama Vendor / Toko</th>
                                            <th className="py-3 px-4 text-center">Total PO</th>
                                            <th className="py-3 px-6 text-right">Total Transaksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {top_vendors.length === 0 ? (
                                            <tr>
                                                <td colSpan="4" className="py-8 text-center text-slate-400">
                                                    Tidak ada data vendor pada periode ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            top_vendors.map((row, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/40">
                                                    <td className="py-3.5 px-4 text-center font-bold text-slate-400">
                                                        {idx + 1}
                                                    </td>
                                                    <td className="py-3.5 px-6 font-medium text-slate-800 dark:text-slate-200">
                                                        {row.vendor_name}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-center">
                                                        <Badge variant="outline">{row.order_count} PO</Badge>
                                                    </td>
                                                    <td className="py-3.5 px-6 text-right font-bold text-slate-900 dark:text-slate-100 font-mono text-xs">
                                                        {formatRupiah(row.total_spending)}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Tab 5: Riwayat Pengadaan Terbaru */}
                {activeTab === 'recent' && (
                    <Card className="border border-slate-200 dark:border-slate-800 shadow-sm">
                        <CardHeader className="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                            <CardTitle className="text-base font-semibold">
                                Daftar Transaksi Pengadaan Terbaru
                            </CardTitle>
                            <CardDescription className="text-xs">
                                20 transaksi terbaru yang tercakup dalam filter saat ini
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-slate-50 dark:bg-slate-900/60 text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">
                                        <tr>
                                            <th className="py-3 px-6">Nomor PO</th>
                                            <th className="py-3 px-4">Tanggal</th>
                                            <th className="py-3 px-4">Tipe</th>
                                            <th className="py-3 px-4">Departemen / Pemohon</th>
                                            <th className="py-3 px-4">Vendor</th>
                                            <th className="py-3 px-6 text-right">Grand Total</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {recent_orders.length === 0 ? (
                                            <tr>
                                                <td colSpan="6" className="py-8 text-center text-slate-400">
                                                    Tidak ada data transaksi.
                                                </td>
                                            </tr>
                                        ) : (
                                            recent_orders.map((o) => (
                                                <tr key={o.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-900/40">
                                                    <td className="py-3.5 px-6 font-mono font-semibold text-xs text-slate-800 dark:text-slate-200">
                                                        {o.po_number}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-xs text-slate-500">
                                                        {o.transaction_date}
                                                    </td>
                                                    <td className="py-3.5 px-4">
                                                        <Badge
                                                            variant="outline"
                                                            className={`text-[10px] font-bold ${
                                                                o.order_type === 'CAPEX'
                                                                    ? 'text-purple-600 border-purple-200 bg-purple-50 dark:bg-purple-950/40'
                                                                    : 'text-blue-600 border-blue-200 bg-blue-50 dark:bg-blue-950/40'
                                                            }`}
                                                        >
                                                            {o.order_type}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3.5 px-4 text-xs">
                                                        <span className="font-medium text-slate-700 dark:text-slate-300">
                                                            {o.department}
                                                        </span>
                                                        <span className="text-slate-400 block text-[11px]">
                                                            {o.requester_name || '-'}
                                                        </span>
                                                    </td>
                                                    <td className="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400">
                                                        {o.vendor_name}
                                                    </td>
                                                    <td className="py-3.5 px-6 text-right font-mono font-bold text-xs text-slate-900 dark:text-slate-100">
                                                        {formatRupiah(o.grand_total)}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
