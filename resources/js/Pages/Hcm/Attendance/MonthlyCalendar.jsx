import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    CalendarDays,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Users,
    CheckCircle2,
    Clock,
    AlertCircle,
    FileSpreadsheet,
    Printer,
    Search,
    Filter,
    ArrowLeft,
    Check,
    X,
    Percent,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { SearchableSelect } from '@/Components/ui/searchable-select';

export default function MonthlyCalendar({
    month,
    daysList,
    employees,
    departments,
    positions,
    filters,
    metrics,
}) {
    const [selectedMonth, setSelectedMonth] = useState(month || new Date().toISOString().substring(0, 7));
    const [department, setDepartment] = useState(filters.department || 'all');
    const [search, setSearch] = useState(filters.search || '');

    // Navigasi Bulan (Sebelumnya / Berikutnya)
    const handleMonthChange = (offset) => {
        const [yearStr, monthStr] = selectedMonth.split('-');
        let year = parseInt(yearStr, 10);
        let m = parseInt(monthStr, 10) + offset;
        if (m < 1) {
            m = 12;
            year -= 1;
        } else if (m > 12) {
            m = 1;
            year += 1;
        }
        const newMonth = `${year}-${String(m).padStart(2, '0')}`;
        setSelectedMonth(newMonth);
        applyFilter(newMonth, department, search);
    };

    const applyFilter = (m = selectedMonth, d = department, s = search) => {
        router.get(
            route('hcm.attendance.monthly'),
            { month: m, department: d, search: s },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Label Bulan Bahasa Indonesia
    const formatMonthIndo = (mStr) => {
        if (!mStr) return '';
        const [year, m] = mStr.split('-');
        const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        return `${monthNames[parseInt(m, 10) - 1]} ${year}`;
    };

    const departmentOptions = [
        { value: 'all', label: 'Semua Divisi' },
        ...(departments || []).map((d) => ({ value: d, label: d })),
    ];

    // Helper badge category code & styling
    const getCategoryBadge = (record, isWeekend) => {
        if (!record) {
            return (
                <span className={`inline-block text-[11px] font-mono select-none ${isWeekend ? 'text-zinc-300 dark:text-zinc-700' : 'text-zinc-300 dark:text-zinc-700'}`}>
                    {isWeekend ? '•' : '-'}
                </span>
            );
        }

        const cat = record.category;
        let badgeChar = '-';
        let badgeColor = 'bg-zinc-100 text-zinc-500';

        if (cat === 'Hadir') {
            badgeChar = 'H';
            badgeColor = 'bg-emerald-500 text-white font-bold';
        } else if (cat === 'Terlambat') {
            badgeChar = 'T';
            badgeColor = 'bg-amber-500 text-white font-bold';
        } else if (cat === 'Izin' || cat === 'Dinas Luar') {
            badgeChar = 'I';
            badgeColor = 'bg-sky-500 text-white font-bold';
        } else if (cat === 'Sakit') {
            badgeChar = 'S';
            badgeColor = 'bg-purple-600 text-white font-bold';
        } else if (cat.includes('Cuti')) {
            badgeChar = 'C';
            badgeColor = 'bg-indigo-500 text-white font-bold';
        } else if (cat === 'Alpha/Mangkir') {
            badgeChar = 'A';
            badgeColor = 'bg-rose-600 text-white font-bold';
        }

        const tooltip = `${cat}${record.clock_in ? ` (${record.clock_in} - ${record.clock_out || '?'})` : ''}${record.notes ? ` : ${record.notes}` : ''}`;

        return (
            <span
                title={tooltip}
                className={`inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] shadow-xs cursor-default ${badgeColor}`}
            >
                {badgeChar}
            </span>
        );
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <CalendarDays className="h-5 w-5 text-indigo-600" />
                            Matriks Kalender Presensi Bulanan
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Visualisasi absensi 1-31 hari per karyawan untuk periode {formatMonthIndo(selectedMonth)}.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* Tombol Kembali ke Mode Harian */}
                        <Link href={route('hcm.attendance.index')}>
                            <Button
                                variant="outline"
                                size="sm"
                                className="text-xs gap-1.5 border-zinc-300 dark:border-zinc-700"
                            >
                                <ArrowLeft className="h-3.5 w-3.5" />
                                Mode Harian Massal
                            </Button>
                        </Link>

                        {/* Cetak Matriks */}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.print()}
                            className="text-xs gap-1.5"
                        >
                            <Printer className="h-3.5 w-3.5" />
                            Cetak Rekap
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`Presensi Bulanan ${formatMonthIndo(selectedMonth)} - HCM`} />

            <div className="space-y-4">
                {/* 1. KARTU METRIK RINGKASAN BULANAN */}
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs p-3">
                        <span className="text-[11px] text-zinc-500 font-medium">Total Karyawan</span>
                        <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-0.5">
                            {metrics.total_karyawan || 0}
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Total Hadir Tepat</span>
                        <div className="text-xl font-bold text-emerald-700 dark:text-emerald-300 mt-0.5">
                            {metrics.total_hadir || 0}
                        </div>
                    </Card>

                    <Card className="border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-amber-600 dark:text-amber-400 font-medium">Total Terlambat</span>
                        <div className="text-xl font-bold text-amber-700 dark:text-amber-300 mt-0.5">
                            {metrics.total_terlambat || 0}
                        </div>
                    </Card>

                    <Card className="border border-sky-200/80 dark:border-sky-900/40 bg-sky-50/30 dark:bg-sky-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-sky-600 dark:text-sky-400 font-medium">Cuti, Izin & Sakit</span>
                        <div className="text-xl font-bold text-sky-700 dark:text-sky-300 mt-0.5">
                            {metrics.total_izin_sakit_cuti || 0}
                        </div>
                    </Card>

                    <Card className="border border-rose-200/80 dark:border-rose-900/40 bg-rose-50/30 dark:bg-rose-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-rose-600 dark:text-rose-400 font-medium">Alpha / Mangkir</span>
                        <div className="text-xl font-bold text-rose-700 dark:text-rose-300 mt-0.5">
                            {metrics.total_alpha || 0}
                        </div>
                    </Card>

                    <Card className="border border-indigo-200/80 dark:border-indigo-900/40 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium">Hari Kerja Efektif</span>
                        <div className="text-xl font-bold text-indigo-700 dark:text-indigo-300 mt-0.5">
                            {metrics.effective_work_days || 0} Hari
                        </div>
                    </Card>
                </div>

                {/* 2. FILTER & NAVIGASI BULAN */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <CardContent className="p-3.5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        {/* Pengatur Periode Bulan */}
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleMonthChange(-1)}
                                className="h-8 w-8 p-0"
                                title="Bulan Sebelumnya"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>

                            <div className="flex items-center gap-1.5">
                                <Input
                                    type="month"
                                    value={selectedMonth}
                                    onChange={(e) => {
                                        setSelectedMonth(e.target.value);
                                        applyFilter(e.target.value, department, search);
                                    }}
                                    className="h-8 text-xs font-semibold w-40"
                                />
                                <span className="text-xs font-semibold text-zinc-700 dark:text-zinc-300 hidden sm:inline">
                                    {formatMonthIndo(selectedMonth)}
                                </span>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => handleMonthChange(1)}
                                className="h-8 w-8 p-0"
                                title="Bulan Berikutnya"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>

                        {/* Filter Divisi & Pencarian Karyawan */}
                        <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <div className="w-full sm:w-56">
                                <SearchableSelect
                                    value={department}
                                    onChange={(val) => {
                                        setDepartment(val);
                                        applyFilter(selectedMonth, val, search);
                                    }}
                                    options={departmentOptions}
                                    placeholder="Filter Divisi"
                                    className="w-full text-xs"
                                />
                            </div>

                            <div className="relative w-full sm:w-60">
                                <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-zinc-400" />
                                <Input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            applyFilter(selectedMonth, department, search);
                                        }
                                    }}
                                    placeholder="Cari nama / NIK..."
                                    className="pl-8 text-xs h-8"
                                />
                            </div>

                            <Button
                                size="sm"
                                onClick={() => applyFilter(selectedMonth, department, search)}
                                className="h-8 text-xs bg-zinc-800 hover:bg-zinc-900 text-white"
                            >
                                Terapkan
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* 3. LEGENDA KODE PRESENSI */}
                <div className="flex flex-wrap items-center gap-3 text-xs bg-zinc-50 dark:bg-zinc-900/60 p-2.5 rounded-lg border border-zinc-200/80 dark:border-zinc-800">
                    <span className="font-semibold text-zinc-700 dark:text-zinc-300">Legenda:</span>
                    <span className="flex items-center gap-1.5">
                        <span className="w-4 h-4 rounded-full bg-emerald-500 text-white text-[9px] font-bold flex items-center justify-center">H</span>
                        <span className="text-zinc-600 dark:text-zinc-400 text-[11px]">Hadir</span>
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="w-4 h-4 rounded-full bg-amber-500 text-white text-[9px] font-bold flex items-center justify-center">T</span>
                        <span className="text-zinc-600 dark:text-zinc-400 text-[11px]">Terlambat</span>
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="w-4 h-4 rounded-full bg-sky-500 text-white text-[9px] font-bold flex items-center justify-center">I</span>
                        <span className="text-zinc-600 dark:text-zinc-400 text-[11px]">Izin / Dinas</span>
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="w-4 h-4 rounded-full bg-purple-600 text-white text-[9px] font-bold flex items-center justify-center">S</span>
                        <span className="text-zinc-600 dark:text-zinc-400 text-[11px]">Sakit</span>
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="w-4 h-4 rounded-full bg-indigo-500 text-white text-[9px] font-bold flex items-center justify-center">C</span>
                        <span className="text-zinc-600 dark:text-zinc-400 text-[11px]">Cuti</span>
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="w-4 h-4 rounded-full bg-rose-600 text-white text-[9px] font-bold flex items-center justify-center">A</span>
                        <span className="text-zinc-600 dark:text-zinc-400 text-[11px]">Alpha</span>
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="text-zinc-400 text-xs font-mono">•</span>
                        <span className="text-zinc-500 dark:text-zinc-400 text-[11px]">Akhir Pekan / Belum Tercatat</span>
                    </span>
                </div>

                {/* 4. TABEL MATRIKS BULANAN (SPREADSHEET GRID) */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto max-h-[70vh]">
                        <table className="w-full border-collapse text-left text-xs">
                            <thead className="sticky top-0 z-20 bg-zinc-100 dark:bg-zinc-800 shadow-xs border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    {/* Kolom Sticky Kiri: Identitas */}
                                    <th className="sticky left-0 z-30 bg-zinc-100 dark:bg-zinc-800 p-2.5 text-zinc-700 dark:text-zinc-300 font-semibold w-10 text-center border-r border-zinc-200 dark:border-zinc-700">
                                        No
                                    </th>
                                    <th className="sticky left-10 z-30 bg-zinc-100 dark:bg-zinc-800 p-2.5 text-zinc-700 dark:text-zinc-300 font-semibold min-w-[160px] border-r border-zinc-200 dark:border-zinc-700">
                                        Karyawan & Divisi
                                    </th>

                                    {/* Kolom Hari 1 s.d. 31 */}
                                    {daysList.map((d) => (
                                        <th
                                            key={d.day}
                                            className={`p-1.5 text-center min-w-[28px] max-w-[32px] border-r border-zinc-200 dark:border-zinc-700 ${
                                                d.is_weekend
                                                    ? 'bg-rose-50/60 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 font-bold'
                                                    : 'text-zinc-700 dark:text-zinc-300'
                                            }`}
                                        >
                                            <div className="text-[11px] leading-tight font-bold">{d.day}</div>
                                            <div className="text-[9px] uppercase tracking-tighter opacity-70 leading-none">
                                                {d.day_name.substring(0, 2)}
                                            </div>
                                        </th>
                                    ))}

                                    {/* Kolom Sticky Rekap Kanan */}
                                    <th className="p-2 text-center text-emerald-700 dark:text-emerald-400 font-bold min-w-[34px] border-l border-zinc-200 dark:border-zinc-700">
                                        H
                                    </th>
                                    <th className="p-2 text-center text-amber-700 dark:text-amber-400 font-bold min-w-[34px]">
                                        T
                                    </th>
                                    <th className="p-2 text-center text-sky-700 dark:text-sky-400 font-bold min-w-[34px]">
                                        I
                                    </th>
                                    <th className="p-2 text-center text-purple-700 dark:text-purple-400 font-bold min-w-[34px]">
                                        S
                                    </th>
                                    <th className="p-2 text-center text-indigo-700 dark:text-indigo-400 font-bold min-w-[34px]">
                                        C
                                    </th>
                                    <th className="p-2 text-center text-rose-700 dark:text-rose-400 font-bold min-w-[34px]">
                                        A
                                    </th>
                                    <th className="p-2 text-center text-zinc-900 dark:text-zinc-100 font-bold min-w-[55px] border-l border-zinc-200 dark:border-zinc-700">
                                        %
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-200/60 dark:divide-zinc-800">
                                {employees.length > 0 ? (
                                    employees.map((emp, idx) => {
                                        const rate = emp.summary.rate;
                                        const rateBadgeColor =
                                            rate >= 90
                                                ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200'
                                                : rate >= 75
                                                ? 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 border-amber-200'
                                                : 'text-rose-600 bg-rose-50 dark:bg-rose-950/40 border-rose-200';

                                        return (
                                            <tr
                                                key={emp.employee_id}
                                                className="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition-colors"
                                            >
                                                {/* No Urut Sticky */}
                                                <td className="sticky left-0 z-10 bg-white dark:bg-zinc-900 p-2 text-center font-mono text-[11px] text-zinc-500 border-r border-zinc-200 dark:border-zinc-800">
                                                    {idx + 1}
                                                </td>

                                                {/* Identitas Karyawan Sticky */}
                                                <td className="sticky left-10 z-10 bg-white dark:bg-zinc-900 p-2 border-r border-zinc-200 dark:border-zinc-800">
                                                    <div className="font-semibold text-zinc-900 dark:text-zinc-100 text-xs truncate max-w-[150px]">
                                                        {emp.name}
                                                    </div>
                                                    <div className="text-[10px] text-zinc-400 truncate max-w-[150px]">
                                                        {emp.employee_code} • {emp.department}
                                                    </div>
                                                </td>

                                                {/* Nilai Presensi Hari 1 s.d. 31 */}
                                                {daysList.map((d) => (
                                                    <td
                                                        key={d.day}
                                                        className={`p-1 text-center border-r border-zinc-100 dark:border-zinc-800/80 ${
                                                            d.is_weekend
                                                                ? 'bg-zinc-50/40 dark:bg-zinc-800/20'
                                                                : ''
                                                        }`}
                                                    >
                                                        {getCategoryBadge(emp.days[d.day], d.is_weekend)}
                                                    </td>
                                                ))}

                                                {/* Rekap Ringkasan Kanan */}
                                                <td className="p-2 text-center font-semibold text-emerald-600 border-l border-zinc-200 dark:border-zinc-800">
                                                    {emp.summary.hadir || 0}
                                                </td>
                                                <td className="p-2 text-center font-semibold text-amber-600">
                                                    {emp.summary.terlambat || 0}
                                                </td>
                                                <td className="p-2 text-center font-medium text-sky-600">
                                                    {emp.summary.izin || 0}
                                                </td>
                                                <td className="p-2 text-center font-medium text-purple-600">
                                                    {emp.summary.sakit || 0}
                                                </td>
                                                <td className="p-2 text-center font-medium text-indigo-600">
                                                    {emp.summary.cuti || 0}
                                                </td>
                                                <td className="p-2 text-center font-bold text-rose-600">
                                                    {emp.summary.alpha || 0}
                                                </td>
                                                <td className="p-2 text-center border-l border-zinc-200 dark:border-zinc-800">
                                                    <span className={`inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border ${rateBadgeColor}`}>
                                                        {rate}%
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={daysList.length + 9}
                                            className="h-32 text-center text-xs text-zinc-400"
                                        >
                                            Tidak ada data karyawan ditemukan pada periode ini.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
