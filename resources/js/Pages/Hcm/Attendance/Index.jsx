import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo, useEffect } from 'react';
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
    Layers,
    Save,
    Sparkles,
    ShieldAlert,
    ExternalLink,
    X,
    Check,
    AlertTriangle,
    Eye,
    TrendingUp,
    FileText,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

export default function AttendanceIndex({
    activeTab: initialTab = 'matrix',
    selectedMonth: propMonth,
    selectedYear: propYear,
    selectedDate: propDate,
    daysList = [],
    matrixEmployees = [],
    dossierEmployees = [],
    allMonthsInYear = [],
    dailyMatrixData = [],
    departments = [],
    positions = [],
    metrics = {},
    filters = {},
}) {
    // State Tab Navigasi ('matrix', 'dossier', 'daily')
    const [currentTab, setCurrentTab] = useState(filters.tab || initialTab || 'matrix');

    // State Filter Matriks Bulanan
    const [currentMonth, setCurrentMonth] = useState(filters.month || propMonth || new Date().toISOString().substring(0, 7));
    const [currentYear, setCurrentYear] = useState(filters.year || propYear || new Date().getFullYear());
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || 'all');
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    // State Entri Harian
    const [dailyDate, setDailyDate] = useState(filters.date || propDate || new Date().toISOString().split('T')[0]);
    const [dailyRows, setDailyRows] = useState(dailyMatrixData);
    const [isDirty, setIsDirty] = useState(false);
    const [isSavingDaily, setIsSavingDaily] = useState(false);

    // Modals
    const [isBulkShiftModalOpen, setIsBulkShiftModalOpen] = useState(false);
    const [isSetAllModalOpen, setIsSetAllModalOpen] = useState(false);
    const [cellDetailModal, setCellDetailModal] = useState({
        isOpen: false,
        employee: null,
        dayInfo: null,
        record: null,
    });

    // Form Quick Edit Single Cell
    const quickEditForm = useForm({
        employee_id: '',
        date: '',
        attendance_category: 'Hadir',
        clock_in: '08:00',
        clock_out: '17:00',
        notes: '',
    });

    // Form Bulk Shift
    const bulkShiftForm = useForm({
        date: dailyDate,
        department: 'all',
        position: 'all',
        attendance_category: 'Hadir',
        clock_in: '08:00',
        clock_out: '17:00',
        notes: '',
        override_existing: false,
    });

    // Sinkronisasi data rows entri harian
    useEffect(() => {
        setDailyRows(dailyMatrixData);
        setIsDirty(false);
    }, [dailyMatrixData]);

    // Navigasi Bulan (Sebelumnya / Berikutnya)
    const handleMonthChange = (offset) => {
        const [yearStr, monthStr] = currentMonth.split('-');
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
        setCurrentMonth(newMonth);
        setCurrentYear(year);
        applyFilters(currentTab, newMonth, year, dailyDate, selectedDepartment, searchTerm);
    };

    // Terapkan Filter
    const applyFilters = (tab = currentTab, m = currentMonth, y = currentYear, dt = dailyDate, dept = selectedDepartment, s = searchTerm) => {
        router.get(
            route('hcm.attendance.index'),
            {
                tab,
                month: m,
                year: y,
                date: dt,
                department: dept,
                search: s,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Label Bulan Bahasa Indonesia
    const formatMonthIndo = (mStr) => {
        if (!mStr) return '';
        const [year, m] = mStr.split('-');
        const monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
        return `${monthNames[parseInt(m, 10) - 1]} ${year}`;
    };

    const departmentOptions = [
        { value: 'all', label: 'Semua Divisi' },
        ...(departments || []).map((d) => ({ value: d, label: d })),
    ];

    // Helper Badge Matriks Bulanan
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
        let badgeColor = 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';

        if (cat === 'Hadir') {
            badgeChar = 'H';
            badgeColor = 'bg-emerald-500 text-white font-bold shadow-xs';
        } else if (cat === 'Terlambat') {
            badgeChar = 'T';
            badgeColor = 'bg-amber-500 text-white font-bold shadow-xs';
        } else if (cat === 'Izin' || cat === 'Dinas Luar') {
            badgeChar = 'I';
            badgeColor = 'bg-sky-500 text-white font-semibold shadow-xs';
        } else if (cat === 'Sakit') {
            badgeChar = 'S';
            badgeColor = 'bg-purple-500 text-white font-semibold shadow-xs';
        } else if (cat && cat.includes('Cuti')) {
            badgeChar = 'C';
            badgeColor = 'bg-teal-500 text-white font-semibold shadow-xs';
        } else if (cat === 'Alpha/Mangkir') {
            badgeChar = 'A';
            badgeColor = 'bg-rose-500 text-white font-bold shadow-xs';
        }

        return (
            <span
                className={`inline-flex items-center justify-center w-5 h-5 rounded text-[10px] cursor-pointer transition-transform hover:scale-115 ${badgeColor}`}
                title={`${record.category}${record.clock_in ? ` (${record.clock_in} - ${record.clock_out || '?'})` : ''}${record.notes ? `\nCatatan: ${record.notes}` : ''}`}
            >
                {badgeChar}
            </span>
        );
    };

    // Buka Modal Detail Cell
    const handleCellClick = (emp, dayInfo, record) => {
        setCellDetailModal({
            isOpen: true,
            employee: emp,
            dayInfo,
            record,
        });

        quickEditForm.setData({
            employee_id: emp.employee_id,
            date: dayInfo.date,
            attendance_category: record?.category || 'Hadir',
            clock_in: record?.clock_in || '08:00',
            clock_out: record?.clock_out || '17:00',
            notes: record?.notes || '',
        });
    };

    const handleQuickEditSubmit = (e) => {
        e.preventDefault();
        quickEditForm.post(route('hcm.attendance.single-update'), {
            preserveScroll: true,
            onSuccess: () => {
                setCellDetailModal((prev) => ({ ...prev, isOpen: false }));
            },
        });
    };

    // Handler Entri Harian
    const handleDailyRowChange = (index, field, value) => {
        const updated = [...dailyRows];
        updated[index] = { ...updated[index], [field]: value };
        setDailyRows(updated);
        setIsDirty(true);
    };

    const handleSaveDailyBatch = () => {
        setIsSavingDaily(true);
        router.post(
            route('hcm.attendance.batch-store'),
            {
                date: dailyDate,
                items: dailyRows.map((r) => ({
                    employee_id: r.employee_id,
                    position: r.position,
                    attendance_category: r.attendance_category,
                    clock_in: r.clock_in,
                    clock_out: r.clock_out,
                    notes: r.notes,
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsDirty(false);
                    setIsSavingDaily(false);
                },
                onError: () => setIsSavingDaily(false),
            }
        );
    };

    const handleSetAllPresentSubmit = () => {
        router.post(
            route('hcm.attendance.set-all-present'),
            {
                date: dailyDate,
                department: selectedDepartment,
            },
            {
                preserveScroll: true,
                onSuccess: () => setIsSetAllModalOpen(false),
            }
        );
    };

    const handleBulkShiftSubmit = (e) => {
        e.preventDefault();
        bulkShiftForm.setData('date', dailyDate);
        bulkShiftForm.post(route('hcm.attendance.set-bulk'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsBulkShiftModalOpen(false);
                bulkShiftForm.reset();
            },
        });
    };

    // Filtered data matriks bulanan
    const filteredMatrixEmployees = useMemo(() => {
        return (matrixEmployees || []).filter((emp) => {
            const matchDept = selectedDepartment === 'all' || emp.department === selectedDepartment;
            const matchSearch =
                !searchTerm ||
                emp.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                emp.employee_code?.toLowerCase().includes(searchTerm.toLowerCase());
            return matchDept && matchSearch;
        });
    }, [matrixEmployees, selectedDepartment, searchTerm]);

    // Filtered data dossier multi-bulan
    const filteredDossierEmployees = useMemo(() => {
        return (dossierEmployees || []).filter((emp) => {
            const matchDept = selectedDepartment === 'all' || emp.department === selectedDepartment;
            const matchSearch =
                !searchTerm ||
                emp.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                emp.employee_code?.toLowerCase().includes(searchTerm.toLowerCase());
            return matchDept && matchSearch;
        });
    }, [dossierEmployees, selectedDepartment, searchTerm]);

    return (
        <AppLayout>
            <Head title="Presensi & Absensi Karyawan - HCM System" />

            <div className="space-y-6">
                {/* Header Utama & Navigasi Tab */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl md:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                            <div className="p-2 rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
                                <CalendarDays className="h-6 w-6" />
                            </div>
                            Presensi & Absensi Karyawan
                        </h1>
                        <p className="text-xs md:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                            Matriks kalender bulanan 1–31 hari, rekapitulasi multi-bulan sinkron Profil Dossier, dan pencatatan harian massal.
                        </p>
                    </div>

                    {/* Tab Navigation Switcher */}
                    <div className="flex items-center gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700/60 self-start md:self-auto shadow-xs">
                        <button
                            type="button"
                            onClick={() => {
                                setCurrentTab('matrix');
                                applyFilters('matrix');
                            }}
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                currentTab === 'matrix'
                                    ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                            }`}
                        >
                            <CalendarDays className="h-3.5 w-3.5" />
                            Matriks Bulanan (1–31)
                        </button>

                        <button
                            type="button"
                            onClick={() => {
                                setCurrentTab('dossier');
                                applyFilters('dossier');
                            }}
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                currentTab === 'dossier'
                                    ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                            }`}
                        >
                            <Layers className="h-3.5 w-3.5" />
                            Rekap Multi-Bulan (Dossier)
                        </button>

                        <button
                            type="button"
                            onClick={() => {
                                setCurrentTab('daily');
                                applyFilters('daily');
                            }}
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                currentTab === 'daily'
                                    ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                            }`}
                        >
                            <Clock className="h-3.5 w-3.5" />
                            Entri Harian & Shift
                        </button>
                    </div>
                </div>

                {/* Kartu Metrik KPI Ringkas */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <Card className="border-zinc-200 dark:border-zinc-800 shadow-xs bg-white dark:bg-zinc-900">
                        <CardContent className="p-3.5">
                            <span className="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider block">Tenaga Kerja</span>
                            <div className="flex items-baseline justify-between mt-1">
                                <span className="text-xl font-bold text-zinc-900 dark:text-zinc-100">{metrics.total_active ?? 0}</span>
                                <Users className="h-4 w-4 text-zinc-400" />
                            </div>
                            <span className="text-[10px] text-zinc-400 block mt-0.5">Karyawan Aktif</span>
                        </CardContent>
                    </Card>

                    <Card className="border-emerald-200 dark:border-emerald-900/40 shadow-xs bg-emerald-50/30 dark:bg-emerald-950/10">
                        <CardContent className="p-3.5">
                            <span className="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider block">Hadir Tepat</span>
                            <div className="flex items-baseline justify-between mt-1">
                                <span className="text-xl font-bold text-emerald-700 dark:text-emerald-300">
                                    {currentTab === 'daily' ? metrics.daily?.hadir ?? 0 : metrics.monthly?.total_hadir ?? 0}
                                </span>
                                <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                            </div>
                            <span className="text-[10px] text-emerald-600/80 block mt-0.5">
                                {currentTab === 'daily' ? 'Hari Ini' : `Bulan ${formatMonthIndo(currentMonth)}`}
                            </span>
                        </CardContent>
                    </Card>

                    <Card className="border-amber-200 dark:border-amber-900/40 shadow-xs bg-amber-50/30 dark:bg-amber-950/10">
                        <CardContent className="p-3.5">
                            <span className="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider block">Terlambat</span>
                            <div className="flex items-baseline justify-between mt-1">
                                <span className="text-xl font-bold text-amber-700 dark:text-amber-300">
                                    {currentTab === 'daily' ? metrics.daily?.terlambat ?? 0 : metrics.monthly?.total_terlambat ?? 0}
                                </span>
                                <Clock className="h-4 w-4 text-amber-500" />
                            </div>
                            <span className="text-[10px] text-amber-600/80 block mt-0.5">Potong Uang Makan</span>
                        </CardContent>
                    </Card>

                    <Card className="border-sky-200 dark:border-sky-900/40 shadow-xs bg-sky-50/30 dark:bg-sky-950/10">
                        <CardContent className="p-3.5">
                            <span className="text-[11px] font-semibold text-sky-700 dark:text-sky-400 uppercase tracking-wider block">Izin / Sakit / Cuti</span>
                            <div className="flex items-baseline justify-between mt-1">
                                <span className="text-xl font-bold text-sky-700 dark:text-sky-300">
                                    {currentTab === 'daily' ? metrics.daily?.cuti_izin_sakit ?? 0 : metrics.monthly?.total_izin_sakit_cuti ?? 0}
                                </span>
                                <AlertCircle className="h-4 w-4 text-sky-500" />
                            </div>
                            <span className="text-[10px] text-sky-600/80 block mt-0.5">Tervalidasi Resmi</span>
                        </CardContent>
                    </Card>

                    <Card className="border-rose-200 dark:border-rose-900/40 shadow-xs bg-rose-50/30 dark:bg-rose-950/10">
                        <CardContent className="p-3.5">
                            <span className="text-[11px] font-semibold text-rose-700 dark:text-rose-400 uppercase tracking-wider block">Alpha / Mangkir</span>
                            <div className="flex items-baseline justify-between mt-1">
                                <span className="text-xl font-bold text-rose-700 dark:text-rose-300">
                                    {currentTab === 'daily' ? metrics.daily?.alpha ?? 0 : metrics.monthly?.total_alpha ?? 0}
                                </span>
                                <ShieldAlert className="h-4 w-4 text-rose-500" />
                            </div>
                            <span className="text-[10px] text-rose-600/80 block mt-0.5">Tanpa Keterangan</span>
                        </CardContent>
                    </Card>

                    <Card className="border-indigo-200 dark:border-indigo-900/40 shadow-xs bg-indigo-50/30 dark:bg-indigo-950/10">
                        <CardContent className="p-3.5">
                            <span className="text-[11px] font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider block">Hari Kerja Efektif</span>
                            <div className="flex items-baseline justify-between mt-1">
                                <span className="text-xl font-bold text-indigo-700 dark:text-indigo-300">
                                    {metrics.monthly?.effective_work_days ?? 22} Hari
                                </span>
                                <TrendingUp className="h-4 w-4 text-indigo-500" />
                            </div>
                            <span className="text-[10px] text-indigo-600/80 block mt-0.5">Senin – Sabtu</span>
                        </CardContent>
                    </Card>
                </div>

                {/* ========================================================================= */}
                {/* TAB 1: MATRIKS KALENDER BULANAN (1 - 31 HARI)                              */}
                {/* ========================================================================= */}
                {currentTab === 'matrix' && (
                    <div className="space-y-4">
                        {/* Toolbar Filter & Kontrol Navigasi Bulan */}
                        <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 p-3 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xs">
                            <div className="flex flex-wrap items-center gap-2">
                                <div className="flex items-center rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/80 p-0.5">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleMonthChange(-1)}
                                        className="h-7 w-7 p-0 text-zinc-600 hover:text-zinc-900 dark:text-zinc-300"
                                        title="Bulan Sebelumnya"
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                    </Button>

                                    <div className="px-2.5 py-0.5 text-xs font-bold text-zinc-800 dark:text-zinc-200 min-w-[120px] text-center">
                                        {formatMonthIndo(currentMonth)}
                                    </div>

                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleMonthChange(1)}
                                        className="h-7 w-7 p-0 text-zinc-600 hover:text-zinc-900 dark:text-zinc-300"
                                        title="Bulan Berikutnya"
                                    >
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>

                                <Input
                                    type="month"
                                    value={currentMonth}
                                    onChange={(e) => {
                                        if (e.target.value) {
                                            setCurrentMonth(e.target.value);
                                            applyFilters('matrix', e.target.value);
                                        }
                                    }}
                                    className="h-8 w-36 text-xs"
                                />

                                <div className="w-48">
                                    <SearchableSelect
                                        value={selectedDepartment}
                                        onValueChange={(val) => {
                                            setSelectedDepartment(val);
                                            applyFilters('matrix', currentMonth, currentYear, dailyDate, val, searchTerm);
                                        }}
                                        options={departmentOptions}
                                        placeholder="Filter Divisi..."
                                        clearable={false}
                                        className="h-8 text-xs"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="relative flex-1 lg:w-56">
                                    <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-zinc-400" />
                                    <Input
                                        placeholder="Cari karyawan..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                applyFilters('matrix', currentMonth, currentYear, dailyDate, selectedDepartment, searchTerm);
                                            }
                                        }}
                                        className="h-8 pl-8 text-xs"
                                    />
                                </div>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => window.print()}
                                    className="h-8 text-xs gap-1.5 border-zinc-300 dark:border-zinc-700"
                                >
                                    <Printer className="h-3.5 w-3.5" />
                                    <span className="hidden sm:inline">Cetak Rekap</span>
                                </Button>

                                <Button
                                    size="sm"
                                    onClick={() => setIsBulkShiftModalOpen(true)}
                                    className="h-8 text-xs gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"
                                >
                                    <Sparkles className="h-3.5 w-3.5" />
                                    Set Massal Shift
                                </Button>
                            </div>
                        </div>

                        {/* Matriks Kalender Bulanan 1 - 31 Hari */}
                        <Card className="border-zinc-200 dark:border-zinc-800 shadow-xs overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr className="bg-zinc-50 dark:bg-zinc-800/80 border-b border-zinc-200 dark:border-zinc-700">
                                            <th className="sticky left-0 z-20 bg-zinc-50 dark:bg-zinc-800 p-2.5 font-bold text-zinc-700 dark:text-zinc-300 w-48 min-w-[190px] shadow-r">
                                                Nama Tenaga Kerja
                                            </th>
                                            <th className="p-2.5 font-bold text-zinc-700 dark:text-zinc-300 w-28 min-w-[110px] hidden md:table-cell">
                                                Divisi & Jabatan
                                            </th>

                                            {/* Header Hari 1 s.d. 31 */}
                                            {daysList.map((d) => (
                                                <th
                                                    key={d.day}
                                                    className={`p-1 text-center font-bold min-w-[28px] max-w-[32px] border-l border-zinc-200/60 dark:border-zinc-700/60 ${
                                                        d.is_weekend
                                                            ? 'bg-rose-50/70 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400'
                                                            : 'text-zinc-600 dark:text-zinc-400'
                                                    }`}
                                                >
                                                    <span className="block text-[10px]">{d.day}</span>
                                                    <span className="block text-[8px] font-normal uppercase opacity-75">{d.day_name}</span>
                                                </th>
                                            ))}

                                            {/* Rekapitulasi Baris */}
                                            <th className="p-1.5 text-center font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20 min-w-[32px]" title="Hadir Tepat">H</th>
                                            <th className="p-1.5 text-center font-bold text-amber-700 dark:text-amber-400 bg-amber-50/40 dark:bg-amber-950/20 min-w-[32px]" title="Terlambat">T</th>
                                            <th className="p-1.5 text-center font-bold text-sky-700 dark:text-sky-400 bg-sky-50/40 dark:bg-sky-950/20 min-w-[32px]" title="Izin / Dinas">I</th>
                                            <th className="p-1.5 text-center font-bold text-purple-700 dark:text-purple-400 bg-purple-50/40 dark:bg-purple-950/20 min-w-[32px]" title="Sakit">S</th>
                                            <th className="p-1.5 text-center font-bold text-teal-700 dark:text-teal-400 bg-teal-50/40 dark:bg-teal-950/20 min-w-[32px]" title="Cuti">C</th>
                                            <th className="p-1.5 text-center font-bold text-rose-700 dark:text-rose-400 bg-rose-50/40 dark:bg-rose-950/20 min-w-[32px]" title="Alpha">A</th>
                                            <th className="p-2 text-center font-bold text-indigo-700 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/30 min-w-[55px]">Kehadiran</th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-zinc-200/80 dark:divide-zinc-800">
                                        {filteredMatrixEmployees.length === 0 ? (
                                            <tr>
                                                <td colSpan={daysList.length + 9} className="p-8 text-center text-zinc-400">
                                                    Tidak ada data presensi karyawan untuk filter ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            filteredMatrixEmployees.map((emp) => (
                                                <tr
                                                    key={emp.employee_id}
                                                    className="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 transition-colors"
                                                >
                                                    {/* Nama Karyawan (Sticky) */}
                                                    <td className="sticky left-0 z-10 bg-white dark:bg-zinc-900 p-2 font-medium text-zinc-900 dark:text-zinc-100 shadow-r">
                                                        <div className="flex flex-col">
                                                            <span className="font-semibold text-zinc-900 dark:text-zinc-100 truncate max-w-[170px]" title={emp.name}>
                                                                {emp.name}
                                                            </span>
                                                            <span className="text-[10px] text-zinc-400 font-mono">
                                                                {emp.employee_code}
                                                            </span>
                                                        </div>
                                                    </td>

                                                    {/* Divisi & Posisi */}
                                                    <td className="p-2 hidden md:table-cell text-zinc-500">
                                                        <div className="flex flex-col">
                                                            <span className="text-[11px] font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-[110px]">
                                                                {emp.department || '-'}
                                                            </span>
                                                            <span className="text-[10px] text-zinc-400 truncate max-w-[110px]">
                                                                {emp.position || '-'}
                                                            </span>
                                                        </div>
                                                    </td>

                                                    {/* Kolom Tanggal 1 s.d. 31 */}
                                                    {daysList.map((d) => {
                                                        const record = emp.days?.[d.day];
                                                        return (
                                                            <td
                                                                key={d.day}
                                                                onClick={() => handleCellClick(emp, d, record)}
                                                                className={`p-1 text-center border-l border-zinc-200/60 dark:border-zinc-800/60 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors ${
                                                                    d.is_weekend ? 'bg-rose-50/20 dark:bg-rose-950/10' : ''
                                                                }`}
                                                            >
                                                                {getCategoryBadge(record, d.is_weekend)}
                                                            </td>
                                                        );
                                                    })}

                                                    {/* Summary Counts */}
                                                    <td className="p-1 text-center font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50/20 dark:bg-emerald-950/10 font-mono">
                                                        {emp.summary?.hadir ?? 0}
                                                    </td>
                                                    <td className="p-1 text-center font-bold text-amber-600 dark:text-amber-400 bg-amber-50/20 dark:bg-amber-950/10 font-mono">
                                                        {emp.summary?.terlambat ?? 0}
                                                    </td>
                                                    <td className="p-1 text-center font-semibold text-sky-600 dark:text-sky-400 bg-sky-50/20 dark:bg-sky-950/10 font-mono">
                                                        {emp.summary?.izin ?? 0}
                                                    </td>
                                                    <td className="p-1 text-center font-semibold text-purple-600 dark:text-purple-400 bg-purple-50/20 dark:bg-purple-950/10 font-mono">
                                                        {emp.summary?.sakit ?? 0}
                                                    </td>
                                                    <td className="p-1 text-center font-semibold text-teal-600 dark:text-teal-400 bg-teal-50/20 dark:bg-teal-950/10 font-mono">
                                                        {emp.summary?.cuti ?? 0}
                                                    </td>
                                                    <td className="p-1 text-center font-bold text-rose-600 dark:text-rose-400 bg-rose-50/20 dark:bg-rose-950/10 font-mono">
                                                        {emp.summary?.alpha ?? 0}
                                                    </td>
                                                    <td className="p-2 text-center font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/20 font-mono text-[11px]">
                                                        {emp.summary?.rate ?? 0}%
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Legenda Indikator */}
                            <div className="p-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-800 flex flex-wrap items-center justify-between gap-3 text-[11px] text-zinc-500">
                                <div className="flex flex-wrap items-center gap-3">
                                    <span className="font-semibold text-zinc-700 dark:text-zinc-300">Legenda:</span>
                                    <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded bg-emerald-500 text-white font-bold inline-flex items-center justify-center text-[9px]">H</span> Hadir Tepat</span>
                                    <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded bg-amber-500 text-white font-bold inline-flex items-center justify-center text-[9px]">T</span> Terlambat</span>
                                    <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded bg-sky-500 text-white font-semibold inline-flex items-center justify-center text-[9px]">I</span> Izin / Dinas</span>
                                    <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded bg-purple-500 text-white font-semibold inline-flex items-center justify-center text-[9px]">S</span> Sakit</span>
                                    <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded bg-teal-500 text-white font-semibold inline-flex items-center justify-center text-[9px]">C</span> Cuti Tahunan</span>
                                    <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded bg-rose-500 text-white font-bold inline-flex items-center justify-center text-[9px]">A</span> Alpha</span>
                                    <span className="flex items-center gap-1.5"><span className="text-zinc-400 font-mono">•</span> Akhir Pekan</span>
                                </div>
                                <div className="text-[11px] text-zinc-400">
                                    * Klik sel tanggal untuk melihat detail log atau mengubah presensi dengan cepat.
                                </div>
                            </div>
                        </Card>
                    </div>
                )}

                {/* ========================================================================= */}
                {/* TAB 2: REKAP MULTI-BULAN & DOSSIER TRACKER (SINKRON BUKU DOSSIER)          */}
                {/* ========================================================================= */}
                {currentTab === 'dossier' && (
                    <div className="space-y-4">
                        {/* Toolbar Rekap Multi-Bulan */}
                        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xs">
                            <div className="flex items-center gap-2">
                                <Label className="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Tahun Kalender:</Label>
                                <Input
                                    type="number"
                                    min="2020"
                                    max="2035"
                                    value={currentYear}
                                    onChange={(e) => {
                                        setCurrentYear(parseInt(e.target.value, 10));
                                        applyFilters('dossier', currentMonth, parseInt(e.target.value, 10), dailyDate, selectedDepartment, searchTerm);
                                    }}
                                    className="h-8 w-24 text-xs font-bold"
                                />

                                <div className="w-48 ml-2">
                                    <SearchableSelect
                                        value={selectedDepartment}
                                        onValueChange={(val) => {
                                            setSelectedDepartment(val);
                                            applyFilters('dossier', currentMonth, currentYear, dailyDate, val, searchTerm);
                                        }}
                                        options={departmentOptions}
                                        placeholder="Filter Divisi..."
                                        clearable={false}
                                        className="h-8 text-xs"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="relative flex-1 sm:w-56">
                                    <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-zinc-400" />
                                    <Input
                                        placeholder="Cari nama karyawan..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                applyFilters('dossier', currentMonth, currentYear, dailyDate, selectedDepartment, searchTerm);
                                            }
                                        }}
                                        className="h-8 pl-8 text-xs"
                                    />
                                </div>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => window.print()}
                                    className="h-8 text-xs gap-1.5"
                                >
                                    <Printer className="h-3.5 w-3.5" />
                                    Cetak Dossier Rekap
                                </Button>
                            </div>
                        </div>

                        {/* List Profil Dossier Multi-Bulan Tiap Karyawan */}
                        <div className="space-y-4">
                            {filteredDossierEmployees.length === 0 ? (
                                <Card className="p-8 text-center text-zinc-400">
                                    Tidak ada data rekap presensi untuk tahun {currentYear}.
                                </Card>
                            ) : (
                                filteredDossierEmployees.map((emp) => (
                                    <Card key={emp.employee_id} className="border-zinc-200 dark:border-zinc-800 shadow-xs overflow-hidden">
                                        {/* Header Karyawan */}
                                        <div className="p-3.5 bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div className="flex items-center gap-3">
                                                <div className="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-xs border border-indigo-200 dark:border-indigo-800">
                                                    {emp.name.substring(0, 2).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-bold text-zinc-900 dark:text-zinc-100 text-sm">
                                                            {emp.name}
                                                        </span>
                                                        <Badge variant="outline" className="text-[10px] font-mono">
                                                            {emp.employee_code}
                                                        </Badge>
                                                    </div>
                                                    <span className="text-xs text-zinc-500">
                                                        {emp.department} &bull; {emp.position}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2 text-xs">
                                                <span className="text-zinc-500">Total Kehadiran {currentYear}:</span>
                                                <span className="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                                    {emp.annual_totals?.hadir + emp.annual_totals?.terlambat} Hari
                                                </span>
                                                <span className="text-zinc-300 dark:text-zinc-700">|</span>
                                                <Link
                                                    href={route('hcm.pdf.employee-dossier', emp.employee_id)}
                                                    target="_blank"
                                                    className="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                                                >
                                                    <FileText className="h-3 w-3" />
                                                    Download Dossier PDF
                                                </Link>
                                            </div>
                                        </div>

                                        {/* Tabel Rekap Multi-Bulan Sinkron Format Dossier */}
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left text-xs border-collapse">
                                                <thead>
                                                    <tr className="bg-zinc-100/60 dark:bg-zinc-800/40 text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800 text-[11px] uppercase tracking-wider">
                                                        <th className="p-2.5 font-bold">Periode Bulan</th>
                                                        <th className="p-2.5 font-bold text-center text-emerald-700 dark:text-emerald-400">Hadir</th>
                                                        <th className="p-2.5 font-bold text-center text-amber-700 dark:text-amber-400">Terlambat</th>
                                                        <th className="p-2.5 font-bold text-center text-sky-700 dark:text-sky-400">Izin / Dinas</th>
                                                        <th className="p-2.5 font-bold text-center text-purple-700 dark:text-purple-400">Sakit</th>
                                                        <th className="p-2.5 font-bold text-center text-teal-700 dark:text-teal-400">Cuti</th>
                                                        <th className="p-2.5 font-bold text-center text-rose-700 dark:text-rose-400">Alpha</th>
                                                        <th className="p-2.5 font-bold text-center">Total Catatan</th>
                                                        <th className="p-2.5 font-bold text-center text-indigo-700 dark:text-indigo-400">% Kehadiran</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800">
                                                    {allMonthsInYear.map((mInfo) => {
                                                        const monthData = emp.months?.[mInfo.year_month];
                                                        const isRowCurrent = mInfo.is_current;
                                                        return (
                                                            <tr
                                                                key={mInfo.year_month}
                                                                className={`hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors ${
                                                                    isRowCurrent ? 'bg-indigo-50/20 dark:bg-indigo-950/10 font-medium' : ''
                                                                }`}
                                                            >
                                                                <td className="p-2.5">
                                                                    <div className="flex items-center gap-2">
                                                                        <span className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                                            {mInfo.full_label}
                                                                        </span>
                                                                        {isRowCurrent && (
                                                                            <Badge variant="outline" className="text-[9px] py-0 px-1 border-indigo-400 text-indigo-600">
                                                                                Bulan Ini
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                </td>
                                                                <td className="p-2 text-center font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                                                    {monthData?.hadir ?? 0}
                                                                </td>
                                                                <td className={`p-2 text-center font-bold font-mono ${monthData?.terlambat > 2 ? 'text-rose-600' : 'text-amber-600'}`}>
                                                                    {monthData?.terlambat ?? 0}
                                                                </td>
                                                                <td className="p-2 text-center text-sky-600 font-mono">
                                                                    {monthData?.izin ?? 0}
                                                                </td>
                                                                <td className="p-2 text-center text-purple-600 font-mono">
                                                                    {monthData?.sakit ?? 0}
                                                                </td>
                                                                <td className="p-2 text-center text-teal-600 font-mono">
                                                                    {monthData?.cuti ?? 0}
                                                                </td>
                                                                <td className={`p-2 text-center font-bold font-mono ${monthData?.alpha > 0 ? 'text-rose-600' : 'text-zinc-400'}`}>
                                                                    {monthData?.alpha ?? 0}
                                                                </td>
                                                                <td className="p-2 text-center font-bold text-zinc-700 dark:text-zinc-300 font-mono">
                                                                    {monthData?.total ?? 0}
                                                                </td>
                                                                <td className="p-2 text-center font-bold text-indigo-600 dark:text-indigo-400 font-mono">
                                                                    {monthData?.rate ?? 0}%
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                                <tfoot>
                                                    <tr className="bg-zinc-100/80 dark:bg-zinc-800/80 font-bold border-t border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                                                        <td className="p-2.5">Total Akumulasi {currentYear}</td>
                                                        <td className="p-2 text-center text-emerald-600 font-mono">{emp.annual_totals?.hadir}</td>
                                                        <td className="p-2 text-center text-amber-600 font-mono">{emp.annual_totals?.terlambat}</td>
                                                        <td className="p-2 text-center text-sky-600 font-mono">{emp.annual_totals?.izin}</td>
                                                        <td className="p-2 text-center text-purple-600 font-mono">{emp.annual_totals?.sakit}</td>
                                                        <td className="p-2 text-center text-teal-600 font-mono">{emp.annual_totals?.cuti}</td>
                                                        <td className="p-2 text-center text-rose-600 font-mono">{emp.annual_totals?.alpha}</td>
                                                        <td className="p-2 text-center font-mono">{emp.annual_totals?.total}</td>
                                                        <td className="p-2 text-center text-indigo-600 font-mono">
                                                            {emp.annual_totals?.total > 0
                                                                ? Math.round(((emp.annual_totals?.hadir + emp.annual_totals?.terlambat) / Math.max(1, allMonthsInYear.reduce((acc, m) => acc + m.work_days, 0))) * 100)
                                                                : 0}%
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </Card>
                                ))
                            )}
                        </div>
                    </div>
                )}

                {/* ========================================================================= */}
                {/* TAB 3: ENTRI PRESENSI HARIAN & SHIFT MASSAL                                */}
                {/* ========================================================================= */}
                {currentTab === 'daily' && (
                    <div className="space-y-4">
                        {/* Toolbar Harian */}
                        <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 p-3 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xs">
                            <div className="flex flex-wrap items-center gap-2">
                                <Label className="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Tanggal:</Label>
                                <Input
                                    type="date"
                                    value={dailyDate}
                                    onChange={(e) => {
                                        setDailyDate(e.target.value);
                                        applyFilters('daily', currentMonth, currentYear, e.target.value, selectedDepartment, searchTerm);
                                    }}
                                    className="h-8 w-38 text-xs font-semibold"
                                />

                                <div className="w-48 ml-2">
                                    <SearchableSelect
                                        value={selectedDepartment}
                                        onValueChange={(val) => {
                                            setSelectedDepartment(val);
                                            applyFilters('daily', currentMonth, currentYear, dailyDate, val, searchTerm);
                                        }}
                                        options={departmentOptions}
                                        placeholder="Pilih Divisi..."
                                        clearable={false}
                                        className="h-8 text-xs"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setIsSetAllModalOpen(true)}
                                    className="h-8 text-xs gap-1.5 border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400"
                                >
                                    <Check className="h-3.5 w-3.5" />
                                    1-Klik Semua Hadir
                                </Button>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setIsBulkShiftModalOpen(true)}
                                    className="h-8 text-xs gap-1.5 border-indigo-300 text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-400"
                                >
                                    <Sparkles className="h-3.5 w-3.5" />
                                    Set Shift / Divisi
                                </Button>

                                <Button
                                    size="sm"
                                    disabled={!isDirty || isSavingDaily}
                                    onClick={handleSaveDailyBatch}
                                    className="h-8 text-xs gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"
                                >
                                    <Save className="h-3.5 w-3.5" />
                                    {isSavingDaily ? 'Menyimpan...' : 'Simpan Presensi Harian'}
                                </Button>
                            </div>
                        </div>

                        {/* Tabel Input Harian */}
                        <Card className="border-zinc-200 dark:border-zinc-800 shadow-xs overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr className="bg-zinc-50 dark:bg-zinc-800/80 border-b border-zinc-200 dark:border-zinc-700 text-[11px] font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                            <th className="p-3 w-52">Karyawan</th>
                                            <th className="p-3 w-36">Divisi & Posisi</th>
                                            <th className="p-3 w-40">Status Presensi</th>
                                            <th className="p-3 w-28">Jam Masuk</th>
                                            <th className="p-3 w-28">Jam Keluar</th>
                                            <th className="p-3">Catatan / Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800">
                                        {dailyRows.length === 0 ? (
                                            <tr>
                                                <td colSpan={6} className="p-8 text-center text-zinc-400">
                                                    Tidak ada data karyawan aktif untuk tanggal ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            dailyRows.map((row, idx) => (
                                                <tr key={row.employee_id} className="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30">
                                                    <td className="p-2.5">
                                                        <div className="font-semibold text-zinc-900 dark:text-zinc-100">{row.name}</div>
                                                        <div className="text-[10px] text-zinc-400 font-mono">{row.employee_code}</div>
                                                    </td>
                                                    <td className="p-2.5 text-zinc-600 dark:text-zinc-400">
                                                        <div>{row.department}</div>
                                                        <div className="text-[10px] text-zinc-400">{row.position}</div>
                                                    </td>
                                                    <td className="p-2">
                                                        <select
                                                            value={row.attendance_category || 'Hadir'}
                                                            onChange={(e) => handleDailyRowChange(idx, 'attendance_category', e.target.value)}
                                                            className="w-full h-8 text-xs rounded-md border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 focus:ring-indigo-500 focus:border-indigo-500"
                                                        >
                                                            <option value="Hadir">Hadir Tepat</option>
                                                            <option value="Terlambat">Terlambat</option>
                                                            <option value="Izin">Izin</option>
                                                            <option value="Sakit">Sakit</option>
                                                            <option value="Cuti">Cuti</option>
                                                            <option value="Dinas Luar">Dinas Luar</option>
                                                            <option value="Alpha/Mangkir">Alpha / Mangkir</option>
                                                        </select>
                                                    </td>
                                                    <td className="p-2">
                                                        <Input
                                                            type="time"
                                                            value={row.clock_in || ''}
                                                            onChange={(e) => handleDailyRowChange(idx, 'clock_in', e.target.value)}
                                                            className="h-8 text-xs font-mono"
                                                        />
                                                    </td>
                                                    <td className="p-2">
                                                        <Input
                                                            type="time"
                                                            value={row.clock_out || ''}
                                                            onChange={(e) => handleDailyRowChange(idx, 'clock_out', e.target.value)}
                                                            className="h-8 text-xs font-mono"
                                                        />
                                                    </td>
                                                    <td className="p-2">
                                                        <Input
                                                            value={row.notes || ''}
                                                            onChange={(e) => handleDailyRowChange(idx, 'notes', e.target.value)}
                                                            placeholder="Keterangan..."
                                                            className="h-8 text-xs"
                                                        />
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    </div>
                )}
            </div>

            {/* ========================================================================= */}
            {/* MODAL 1: DETAIL LOG & EDIT CEPAT TANGGAL TERPILIH                          */}
            {/* ========================================================================= */}
            <Dialog open={cellDetailModal.isOpen} onOpenChange={(open) => setCellDetailModal((prev) => ({ ...prev, isOpen: open }))}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <Clock className="h-4 w-4 text-indigo-600" />
                            Detail & Edit Log Presensi
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Karyawan: <strong>{cellDetailModal.employee?.name}</strong> &bull; Tanggal: <strong>{cellDetailModal.dayInfo?.date}</strong>
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleQuickEditSubmit} className="space-y-3 pt-2">
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Status Kehadiran *</Label>
                            <select
                                value={quickEditForm.data.attendance_category}
                                onChange={(e) => quickEditForm.setData('attendance_category', e.target.value)}
                                className="w-full h-8 text-xs rounded-md border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900"
                            >
                                <option value="Hadir">Hadir Tepat</option>
                                <option value="Terlambat">Terlambat</option>
                                <option value="Izin">Izin</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Dinas Luar">Dinas Luar</option>
                                <option value="Alpha/Mangkir">Alpha / Mangkir</option>
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Jam Masuk (Clock In)</Label>
                                <Input
                                    type="time"
                                    value={quickEditForm.data.clock_in}
                                    onChange={(e) => quickEditForm.setData('clock_in', e.target.value)}
                                    className="h-8 text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Jam Keluar (Clock Out)</Label>
                                <Input
                                    type="time"
                                    value={quickEditForm.data.clock_out}
                                    onChange={(e) => quickEditForm.setData('clock_out', e.target.value)}
                                    className="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Catatan / Keterangan</Label>
                            <Textarea
                                rows={2}
                                value={quickEditForm.data.notes}
                                onChange={(e) => quickEditForm.setData('notes', e.target.value)}
                                placeholder="Alasan terlambat, keperluan izin, dll..."
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setCellDetailModal((prev) => ({ ...prev, isOpen: false }))}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={quickEditForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                {quickEditForm.processing ? 'Menyimpan...' : 'Perbarui Presensi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ========================================================================= */}
            {/* MODAL 2: SET MASSAL SHIFT / DIVISI                                        */}
            {/* ========================================================================= */}
            <Dialog open={isBulkShiftModalOpen} onOpenChange={setIsBulkShiftModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <Sparkles className="h-4 w-4 text-indigo-600" />
                            Set Presensi Massal Per Divisi / Shift
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Atur kehadiran massal untuk divisi atau posisi tertentu pada tanggal terpilih ({dailyDate}).
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleBulkShiftSubmit} className="space-y-3 pt-2">
                        {/* Preset Waktu Cepat */}
                        <div className="space-y-1.5 p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                            <Label className="text-[11px] font-semibold text-zinc-500 uppercase">Preset Jam Kerja Cepat:</Label>
                            <div className="flex flex-wrap gap-1.5 mt-1">
                                <Button
                                    type="button"
                                    size="xs"
                                    variant="outline"
                                    onClick={() => {
                                        bulkShiftForm.setData({
                                            ...bulkShiftForm.data,
                                            clock_in: '07:00',
                                            clock_out: '15:00',
                                            notes: 'Shift Pagi (07:00 - 15:00)',
                                        });
                                    }}
                                    className="text-[11px] h-6 px-2"
                                >
                                    Pagi (07:00 - 15:00)
                                </Button>
                                <Button
                                    type="button"
                                    size="xs"
                                    variant="outline"
                                    onClick={() => {
                                        bulkShiftForm.setData({
                                            ...bulkShiftForm.data,
                                            clock_in: '08:00',
                                            clock_out: '17:00',
                                            notes: 'Shift Normal (08:00 - 17:00)',
                                        });
                                    }}
                                    className="text-[11px] h-6 px-2"
                                >
                                    Normal (08:00 - 17:00)
                                </Button>
                                <Button
                                    type="button"
                                    size="xs"
                                    variant="outline"
                                    onClick={() => {
                                        bulkShiftForm.setData({
                                            ...bulkShiftForm.data,
                                            clock_in: '15:00',
                                            clock_out: '23:00',
                                            notes: 'Shift Siang (15:00 - 23:00)',
                                        });
                                    }}
                                    className="text-[11px] h-6 px-2"
                                >
                                    Siang (15:00 - 23:00)
                                </Button>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Divisi Target</Label>
                                <SearchableSelect
                                    value={bulkShiftForm.data.department}
                                    onValueChange={(val) => bulkShiftForm.setData('department', val)}
                                    options={departmentOptions}
                                    placeholder="Semua Divisi"
                                    clearable={false}
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Kategori</Label>
                                <select
                                    value={bulkShiftForm.data.attendance_category}
                                    onChange={(e) => bulkShiftForm.setData('attendance_category', e.target.value)}
                                    className="w-full h-8 text-xs rounded-md border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900"
                                >
                                    <option value="Hadir">Hadir</option>
                                    <option value="Terlambat">Terlambat</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Dinas Luar">Dinas Luar</option>
                                    <option value="Alpha/Mangkir">Alpha</option>
                                </select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Jam Masuk</Label>
                                <Input
                                    type="time"
                                    value={bulkShiftForm.data.clock_in}
                                    onChange={(e) => bulkShiftForm.setData('clock_in', e.target.value)}
                                    className="h-8 text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Jam Keluar</Label>
                                <Input
                                    type="time"
                                    value={bulkShiftForm.data.clock_out}
                                    onChange={(e) => bulkShiftForm.setData('clock_out', e.target.value)}
                                    className="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Catatan Presensi (Opsional)</Label>
                            <Input
                                value={bulkShiftForm.data.notes}
                                onChange={(e) => bulkShiftForm.setData('notes', e.target.value)}
                                placeholder="Keterangan jadwal kerja..."
                                className="h-8 text-xs"
                            />
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="override_check"
                                checked={bulkShiftForm.data.override_existing}
                                onChange={(e) => bulkShiftForm.setData('override_existing', e.target.checked)}
                                className="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <Label htmlFor="override_check" className="text-xs text-zinc-600 dark:text-zinc-400 cursor-pointer">
                                Timpa presensi yang sudah terisi sebelumnya
                            </Label>
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setIsBulkShiftModalOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={bulkShiftForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                {bulkShiftForm.processing ? 'Menerapkan...' : 'Terapkan Shift'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ========================================================================= */}
            {/* MODAL 3: KONFIRMASI 1-KLIK SEMUA HADIR                                    */}
            {/* ========================================================================= */}
            <Dialog open={isSetAllModalOpen} onOpenChange={setIsSetAllModalOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2 text-emerald-600">
                            <Check className="h-4 w-4" />
                            Set Seluruhnya Hadir?
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Sistem akan menandai seluruh karyawan yang belum diabsen pada tanggal <strong>{dailyDate}</strong> dengan status <strong>Hadir (08:00 - 17:00)</strong>. Karyawan yang sedang cuti atau izin resmi tidak akan tertimpa.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="gap-2 pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setIsSetAllModalOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            onClick={handleSetAllPresentSubmit}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white"
                        >
                            Ya, Set Semua Hadir
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
