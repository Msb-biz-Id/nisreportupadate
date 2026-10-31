import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import {
    Clock,
    Calendar,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Users,
    Plus,
    Filter,
    Search,
    Edit3,
    Trash2,
    CheckCircle2,
    XCircle,
    Layers,
    Sparkles,
    AlertCircle,
    Check,
    X,
    RotateCcw,
    SlidersHorizontal,
    ArrowRight,
    Info,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

const PRESET_COLORS = [
    { label: 'Biru (Office/Reguler)', value: '#2563eb' },
    { label: 'Emerald (Shift 1 Pagi)', value: '#10b981' },
    { label: 'Amber (Shift 2 Siang)', value: '#f59e0b' },
    { label: 'Ungu (Shift 3 Malam)', value: '#8b5cf6' },
    { label: 'Rose / Merah (Khusus)', value: '#f43f5e' },
    { label: 'Slate (Libur/Off)', value: '#64748b' },
    { label: 'Teal (Produksi)', value: '#0d9488' },
    { label: 'Indigo (Staf)', value: '#4f46e5' },
];

const DAYS_OF_WEEK = [
    { id: 1, label: 'Sen' },
    { id: 2, label: 'Sel' },
    { id: 3, label: 'Rab' },
    { id: 4, label: 'Kam' },
    { id: 5, label: 'Jum' },
    { id: 6, label: 'Sab' },
    { id: 0, label: 'Min' },
];

export default function ShiftsIndex({
    activeTab: initialTab = 'roster',
    selectedMonth: propMonth,
    shifts = [],
    rosterMatrix = [],
    daysList = [],
    departments = [],
    divisions = [],
    employees = [],
    metrics = {},
    filters = {},
}) {
    const [currentTab, setCurrentTab] = useState(filters.tab || initialTab || 'roster');
    const [currentMonth, setCurrentMonth] = useState(filters.month || propMonth || new Date().toISOString().substring(0, 7));
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || 'all');
    const [selectedDivision, setSelectedDivision] = useState(filters.division || 'all');
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    // Modals
    const [isBulkRosterOpen, setIsBulkRosterOpen] = useState(false);
    const [isSingleCellOpen, setIsSingleCellOpen] = useState(false);
    const [isShiftModalOpen, setIsShiftModalOpen] = useState(false);
    const [isDefaultShiftOpen, setIsDefaultShiftOpen] = useState(false);
    const [isClearRosterOpen, setIsClearRosterOpen] = useState(false);
    const [editingShift, setEditingShift] = useState(null);
    const [selectedCell, setSelectedCell] = useState(null); // { employee, day, dayInfo }

    // Forms
    const bulkRosterForm = useForm({
        start_date: `${currentMonth}-01`,
        end_date: `${currentMonth}-${String(daysList.length || 31).padStart(2, '0')}`,
        department: selectedDepartment,
        division: selectedDivision,
        employee_ids: [],
        shift_id: shifts[0]?.id || '',
        days_of_week: [1, 2, 3, 4, 5, 6], // default Sen-Sab
        override_existing: true,
        notes: '',
    });

    const singleCellForm = useForm({
        employee_id: '',
        roster_date: '',
        shift_id: '',
        notes: '',
    });

    const shiftForm = useForm({
        name: '',
        shift_code: '',
        start_time: '08:00',
        end_time: '17:00',
        late_tolerance_minutes: 15,
        color_code: '#2563eb',
        is_overnight: false,
        is_off_day: false,
        description: '',
        is_active: true,
    });

    const defaultShiftForm = useForm({
        department: 'all',
        employee_ids: [],
        shift_id: shifts[0]?.id || '',
    });

    const clearRosterForm = useForm({
        start_date: `${currentMonth}-01`,
        end_date: `${currentMonth}-${String(daysList.length || 31).padStart(2, '0')}`,
        department: 'all',
        employee_ids: [],
    });

    // Handle Month Navigation
    const handleMonthChange = (direction) => {
        const [year, month] = currentMonth.split('-').map(Number);
        let nextDate = new Date(year, month - 1 + direction, 1);
        const nextMonthStr = `${nextDate.getFullYear()}-${String(nextDate.getMonth() + 1).padStart(2, '0')}`;
        setCurrentMonth(nextMonthStr);
        applyFilters(currentTab, nextMonthStr);
    };

    const applyFilters = (tab = currentTab, month = currentMonth) => {
        router.get(
            route('hcm.shifts.index'),
            {
                tab,
                month,
                department: selectedDepartment,
                division: selectedDivision,
                search: searchTerm,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Open Edit Shift Modal
    const handleOpenEditShift = (shift) => {
        setEditingShift(shift);
        shiftForm.setData({
            name: shift.name,
            shift_code: shift.shift_code,
            start_time: shift.start_time ? shift.start_time.substring(0, 5) : '',
            end_time: shift.end_time ? shift.end_time.substring(0, 5) : '',
            late_tolerance_minutes: shift.late_tolerance_minutes ?? 15,
            color_code: shift.color_code || '#2563eb',
            is_overnight: !!shift.is_overnight,
            is_off_day: !!shift.is_off_day,
            description: shift.description || '',
            is_active: !!shift.is_active,
        });
        setIsShiftModalOpen(true);
    };

    const handleOpenCreateShift = () => {
        setEditingShift(null);
        shiftForm.reset();
        shiftForm.setData({
            name: '',
            shift_code: '',
            start_time: '08:00',
            end_time: '17:00',
            late_tolerance_minutes: 15,
            color_code: '#2563eb',
            is_overnight: false,
            is_off_day: false,
            description: '',
            is_active: true,
        });
        setIsShiftModalOpen(true);
    };

    const handleSaveShift = (e) => {
        e.preventDefault();
        if (editingShift) {
            shiftForm.put(route('hcm.shifts.update', editingShift.uuid || editingShift.id), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsShiftModalOpen(false);
                    setEditingShift(null);
                },
            });
        } else {
            shiftForm.post(route('hcm.shifts.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsShiftModalOpen(false);
                },
            });
        }
    };

    const handleDeleteShift = (shift) => {
        if (confirm(`Yakin ingin menghapus shift '${shift.name}' (${shift.shift_code})?`)) {
            router.delete(route('hcm.shifts.destroy', shift.uuid || shift.id), {
                preserveScroll: true,
            });
        }
    };

    const handleToggleActive = (shift) => {
        router.post(route('hcm.shifts.toggle-active', shift.uuid || shift.id), {}, {
            preserveScroll: true,
        });
    };

    // Open Cell Quick Edit
    const handleCellClick = (employee, dayNum, dayInfo) => {
        const cell = employee.days[dayNum];
        setSelectedCell({ employee, dayNum, dayInfo, cell });
        singleCellForm.setData({
            employee_id: employee.employee_id,
            roster_date: dayInfo.date,
            shift_id: cell.shift_id || '',
            notes: cell.notes || '',
        });
        setIsSingleCellOpen(true);
    };

    const handleSaveSingleCell = (e) => {
        e.preventDefault();
        singleCellForm.post(route('hcm.shifts.assign-single'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsSingleCellOpen(false);
                setSelectedCell(null);
            },
        });
    };

    const handleSaveBulkRoster = (e) => {
        e.preventDefault();
        bulkRosterForm.post(route('hcm.shifts.assign-bulk'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsBulkRosterOpen(false);
            },
        });
    };

    const handleSaveDefaultShift = (e) => {
        e.preventDefault();
        defaultShiftForm.post(route('hcm.shifts.set-default'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsDefaultShiftOpen(false);
            },
        });
    };

    const handleSaveClearRoster = (e) => {
        e.preventDefault();
        if (confirm('Jadwal roster terpilih akan dihapus dan dikembalikan ke shift default karyawan. Lanjutkan?')) {
            clearRosterForm.post(route('hcm.shifts.clear-roster'), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsClearRosterOpen(false);
                },
            });
        }
    };

    const formatMonthIndo = (ym) => {
        if (!ym) return '';
        const [year, month] = ym.split('-');
        const names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return `${names[parseInt(month, 10)]} ${year}`;
    };

    const shiftColumns = useMemo(() => [
        {
            id: 'shift_code',
            accessorFn: (row) => row.shift_code || '',
            meta: { title: 'Kode & Warna' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Kode & Warna" />,
            size: 130,
            minSize: 110,
            cell: ({ row }) => {
                const shift = row.original;
                return (
                    <div className="inline-flex items-center gap-2">
                        <span
                            className="px-2.5 py-1 rounded-md text-xs font-bold border"
                            style={{
                                backgroundColor: `${shift.color_code}15`,
                                borderColor: `${shift.color_code}40`,
                                color: shift.color_code,
                            }}
                        >
                            {shift.shift_code}
                        </span>
                        <span
                            className="w-3 h-3 rounded-full border border-black/10 shrink-0"
                            style={{ backgroundColor: shift.color_code }}
                        />
                    </div>
                );
            },
        },
        {
            accessorKey: 'name',
            meta: { title: 'Nama Shift' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nama Shift" />,
            size: 200,
            minSize: 160,
            cell: ({ row }) => (
                <span className="font-bold text-zinc-900 dark:text-zinc-100">{row.original.name}</span>
            ),
        },
        {
            id: 'work_hours',
            accessorFn: (row) => (row.is_off_day ? '' : `${row.start_time || ''}-${row.end_time || ''}`),
            meta: { title: 'Jam Kerja' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Jam Kerja" />,
            size: 180,
            minSize: 150,
            cell: ({ row }) => {
                const shift = row.original;
                if (shift.is_off_day) {
                    return <span className="text-zinc-400 italic">Libur / Tidak Bekerja</span>;
                }
                return (
                    <div className="flex items-center gap-1.5 font-mono font-medium text-zinc-700 dark:text-zinc-300">
                        <span>
                            {shift.start_time?.substring(0, 5)} - {shift.end_time?.substring(0, 5)}
                        </span>
                        {shift.is_overnight && (
                            <Badge variant="outline" className="text-[10px] bg-purple-50 text-purple-700 border-purple-300">
                                +1 Hari (Overnight)
                            </Badge>
                        )}
                    </div>
                );
            },
        },
        {
            id: 'late_tolerance',
            accessorFn: (row) => (row.is_off_day ? '' : row.late_tolerance_minutes),
            meta: { title: 'Toleransi Telat' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Toleransi Telat" />,
            size: 130,
            minSize: 110,
            cell: ({ row }) => (
                <span className="text-zinc-600 dark:text-zinc-300">
                    {row.original.is_off_day ? '-' : `${row.original.late_tolerance_minutes} Menit`}
                </span>
            ),
        },
        {
            id: 'shift_type',
            accessorFn: (row) => (row.is_off_day ? 'Off' : 'Aktif'),
            meta: { title: 'Tipe Shift' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Tipe Shift" />,
            size: 140,
            minSize: 120,
            cell: ({ row }) =>
                row.original.is_off_day ? (
                    <Badge variant="outline" className="bg-zinc-100 text-zinc-600 border-zinc-300">
                        Hari Libur / Off
                    </Badge>
                ) : (
                    <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-300">
                        Shift Kerja Aktif
                    </Badge>
                ),
        },
        {
            accessorKey: 'description',
            meta: { title: 'Deskripsi' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Deskripsi" />,
            size: 220,
            minSize: 180,
            cell: ({ row }) => (
                <span className="text-zinc-500 max-w-[240px] truncate inline-block align-middle">
                    {row.original.description || '-'}
                </span>
            ),
        },
        {
            id: 'is_active',
            accessorFn: (row) => (row.is_active ? 1 : 0),
            meta: { title: 'Status', cellClassName: 'text-center' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status" className="justify-center" />,
            size: 110,
            minSize: 95,
            cell: ({ row }) => {
                const shift = row.original;
                return (
                    <button
                        type="button"
                        onClick={() => handleToggleActive(shift)}
                        className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold transition-colors ${
                            shift.is_active
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400'
                                : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                        }`}
                    >
                        {shift.is_active ? (
                            <>
                                <Check className="h-3 w-3" /> Aktif
                            </>
                        ) : (
                            <>
                                <X className="h-3 w-3" /> Nonaktif
                            </>
                        )}
                    </button>
                );
            },
        },
        {
            id: 'actions',
            size: 100,
            minSize: 90,
            pinned: 'right',
            header: () => <div className="text-right">Aksi</div>,
            cell: ({ row }) => {
                const shift = row.original;
                return (
                    <div className="flex items-center justify-end gap-1.5">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleOpenEditShift(shift)}
                            className="h-7 w-7 text-zinc-500 hover:text-indigo-600"
                            title="Edit Shift"
                        >
                            <Edit3 className="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDeleteShift(shift)}
                            className="h-7 w-7 text-zinc-500 hover:text-rose-600"
                            title="Hapus Shift"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                );
            },
        },
    ], []);

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Clock className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Multi-Shift & Roster Management (Pondasi Jadwal)
                    </span>
                </div>
            }
        >
            <Head title="Multi-Shift & Roster Management - HCM NIS Group" />

            <div className="space-y-6">
                {/* Header Utama & Navigasi Tab */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <div className="p-1.5 rounded-lg bg-indigo-600 text-white shadow-xs">
                                <Clock className="h-5 w-5" />
                            </div>
                            <span>Multi-Shift & Roster Management</span>
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Pondasi jadwal jam kerja, rotasi shift lini produksi/kantor, dan matriks penugasan roster karyawan NIS Group.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 self-start md:self-auto flex-wrap">
                        {/* Switcher Tab */}
                        <div className="flex items-center gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700/60 shadow-xs">
                            <button
                                type="button"
                                onClick={() => {
                                    setCurrentTab('roster');
                                    applyFilters('roster');
                                }}
                                className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                    currentTab === 'roster'
                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                                }`}
                            >
                                <CalendarDays className="h-3.5 w-3.5" />
                                Matriks Jadwal Roster
                            </button>

                            <button
                                type="button"
                                onClick={() => {
                                    setCurrentTab('shifts');
                                    applyFilters('shifts');
                                }}
                                className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                    currentTab === 'shifts'
                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                                }`}
                            >
                                <SlidersHorizontal className="h-3.5 w-3.5" />
                                Master Shift Kerja ({shifts.length})
                            </button>
                        </div>

                        {/* Link ke Presensi Harian */}
                        <Link
                            href={route('hcm.attendance.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors shadow-xs"
                        >
                            <Calendar className="h-3.5 w-3.5 text-zinc-500" />
                            Ke Matriks Presensi
                        </Link>
                    </div>
                </div>

                {/* Top Metrics Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border border-zinc-200 dark:border-zinc-800 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-zinc-500 dark:text-zinc-400">Master Shift Aktif</p>
                                <h3 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mt-1">
                                    {metrics.active_shifts || 0}
                                    <span className="text-xs font-normal text-zinc-400 ml-1.5">/ {metrics.total_shifts || 0} shift</span>
                                </h3>
                            </div>
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                                <Clock className="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200 dark:border-zinc-800 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Karyawan Aktif</p>
                                <h3 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mt-1">
                                    {metrics.total_employees || 0}
                                    <span className="text-xs font-normal text-zinc-400 ml-1.5">orang</span>
                                </h3>
                            </div>
                            <div className="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                                <Users className="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200 dark:border-zinc-800 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-zinc-500 dark:text-zinc-400">Roster Khusus Terplot</p>
                                <h3 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 mt-1">
                                    {metrics.rostered_this_month || 0}
                                    <span className="text-xs font-normal text-zinc-400 ml-1.5">titik jadwal</span>
                                </h3>
                            </div>
                            <div className="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                                <CalendarDays className="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200 dark:border-zinc-800 shadow-xs bg-gradient-to-br from-indigo-50/50 to-white dark:from-zinc-900 dark:to-zinc-850">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-zinc-500 dark:text-zinc-400">Aksi Cepat Roster</p>
                                <div className="flex items-center gap-2 mt-2">
                                    <Button
                                        size="xs"
                                        onClick={() => setIsBulkRosterOpen(true)}
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] shadow-xs"
                                    >
                                        <Plus className="h-3 w-3 mr-1" />
                                        Atur Roster Massal
                                    </Button>
                                </div>
                            </div>
                            <div className="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400">
                                <Sparkles className="h-5 w-5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* TAB 1: MATRIKS JADWAL ROSTER */}
                {currentTab === 'roster' && (
                    <div className="space-y-4">
                        {/* Control Bar: Bulan, Filter, Legend & Actions */}
                        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-3 p-3.5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xs">
                            <div className="flex flex-wrap items-center gap-2">
                                {/* Month Navigator */}
                                <div className="flex items-center bg-zinc-100 dark:bg-zinc-800 rounded-lg p-0.5 border border-zinc-200 dark:border-zinc-700">
                                    <button
                                        type="button"
                                        onClick={() => handleMonthChange(-1)}
                                        className="p-1.5 hover:bg-white dark:hover:bg-zinc-700 rounded-md text-zinc-600 dark:text-zinc-300 transition-colors"
                                        title="Bulan Sebelumnya"
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                    </button>
                                    <span className="px-3 text-xs font-bold text-zinc-800 dark:text-zinc-200 min-w-[130px] text-center">
                                        {formatMonthIndo(currentMonth)}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => handleMonthChange(1)}
                                        className="p-1.5 hover:bg-white dark:hover:bg-zinc-700 rounded-md text-zinc-600 dark:text-zinc-300 transition-colors"
                                        title="Bulan Berikutnya"
                                    >
                                        <ChevronRight className="h-4 w-4" />
                                    </button>
                                </div>

                                {/* Filter Departemen */}
                                <select
                                    value={selectedDepartment}
                                    onChange={(e) => {
                                        setSelectedDepartment(e.target.value);
                                        router.get(
                                            route('hcm.shifts.index'),
                                            {
                                                tab: 'roster',
                                                month: currentMonth,
                                                department: e.target.value,
                                                division: selectedDivision,
                                                search: searchTerm,
                                            },
                                            { preserveState: true }
                                        );
                                    }}
                                    className="h-8 text-xs rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 focus:ring-indigo-500"
                                >
                                    <option value="all">Semua Departemen</option>
                                    {departments.map((dept) => (
                                        <option key={dept} value={dept}>
                                            {dept}
                                        </option>
                                    ))}
                                </select>

                                {/* Search Bar */}
                                <div className="relative">
                                    <Search className="h-3.5 w-3.5 absolute left-2.5 top-2.5 text-zinc-400" />
                                    <Input
                                        placeholder="Cari karyawan..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') applyFilters('roster');
                                        }}
                                        className="h-8 pl-8 text-xs w-[180px] sm:w-[220px]"
                                    />
                                </div>
                            </div>

                            {/* Tombol Aksi Tambahan */}
                            <div className="flex items-center gap-2 flex-wrap">
                                <Button
                                    variant="outline"
                                    size="xs"
                                    onClick={() => setIsDefaultShiftOpen(true)}
                                    className="text-xs shadow-xs"
                                >
                                    <Users className="h-3.5 w-3.5 mr-1 text-zinc-500" />
                                    Set Default Shift
                                </Button>
                                <Button
                                    variant="outline"
                                    size="xs"
                                    onClick={() => setIsClearRosterOpen(true)}
                                    className="text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/20 border-rose-200 dark:border-rose-900/50 shadow-xs"
                                >
                                    <RotateCcw className="h-3.5 w-3.5 mr-1" />
                                    Reset Roster
                                </Button>
                                <Button
                                    size="xs"
                                    onClick={() => setIsBulkRosterOpen(true)}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs shadow-xs"
                                >
                                    <Plus className="h-3.5 w-3.5 mr-1" />
                                    Atur Roster Massal
                                </Button>
                            </div>
                        </div>

                        {/* Legend Bar */}
                        <div className="flex items-center gap-2 px-3 py-2 bg-zinc-50 dark:bg-zinc-850 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs overflow-x-auto">
                            <span className="font-semibold text-zinc-600 dark:text-zinc-400 shrink-0 flex items-center gap-1">
                                <Info className="h-3.5 w-3.5" /> Legenda Shift:
                            </span>
                            <div className="flex items-center gap-2 flex-wrap">
                                {shifts.map((s) => (
                                    <div
                                        key={s.id}
                                        className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium border"
                                        style={{
                                            backgroundColor: `${s.color_code}15`,
                                            borderColor: `${s.color_code}40`,
                                            color: s.color_code,
                                        }}
                                    >
                                        <span
                                            className="w-2 h-2 rounded-full"
                                            style={{ backgroundColor: s.color_code }}
                                        />
                                        <span className="font-bold">{s.shift_code}</span>
                                        <span className="text-zinc-600 dark:text-zinc-300">
                                            ({s.is_off_day ? 'Off' : `${s.start_time?.substring(0, 5)} - ${s.end_time?.substring(0, 5)}`})
                                        </span>
                                    </div>
                                ))}
                                <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium border bg-zinc-200/50 border-zinc-300 text-zinc-600 dark:text-zinc-400">
                                    <span className="w-2 h-2 rounded-full bg-zinc-400" />
                                    <span className="font-bold">OFF</span>
                                    <span>(Libur)</span>
                                </div>
                            </div>
                        </div>

                        {/* Roster Calendar Matrix Table */}
                        <div className="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xs overflow-hidden">
                            <div className="overflow-x-auto max-h-[650px] relative">
                                <table className="w-full text-xs border-collapse">
                                    <thead className="sticky top-0 z-20 bg-zinc-50 dark:bg-zinc-850 border-b border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 font-semibold shadow-xs">
                                        <tr>
                                            <th className="sticky left-0 z-30 bg-zinc-50 dark:bg-zinc-850 px-3 py-2.5 text-left border-r border-zinc-200 dark:border-zinc-800 min-w-[200px] shadow-xs">
                                                Karyawan & Default
                                            </th>
                                            {daysList.map((day) => (
                                                <th
                                                    key={day.date}
                                                    className={`px-1.5 py-2 text-center border-r border-zinc-200 dark:border-zinc-800 min-w-[34px] ${
                                                        day.is_sunday
                                                            ? 'bg-rose-50/60 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400'
                                                            : day.is_saturday
                                                            ? 'bg-amber-50/60 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400'
                                                            : ''
                                                    }`}
                                                >
                                                    <div className="text-[10px] uppercase font-normal text-zinc-400">
                                                        {day.day_name}
                                                    </div>
                                                    <div className="text-xs font-bold">{day.day}</div>
                                                </th>
                                            ))}
                                            <th className="px-3 py-2.5 text-center min-w-[120px]">
                                                Total Shift
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800 text-zinc-700 dark:text-zinc-300">
                                        {rosterMatrix.length === 0 ? (
                                            <tr>
                                                <td
                                                    colSpan={daysList.length + 2}
                                                    className="py-12 text-center text-zinc-400"
                                                >
                                                    Tidak ada data karyawan yang cocok dengan kriteria pencarian.
                                                </td>
                                            </tr>
                                        ) : (
                                            rosterMatrix.map((emp) => (
                                                <tr
                                                    key={emp.employee_id}
                                                    className="hover:bg-zinc-50/70 dark:hover:bg-zinc-850/50 transition-colors group"
                                                >
                                                    {/* Frozen Left Column: Karyawan */}
                                                    <td className="sticky left-0 z-10 bg-white dark:bg-zinc-900 group-hover:bg-zinc-50 dark:group-hover:bg-zinc-850 px-3 py-2 border-r border-zinc-200 dark:border-zinc-800">
                                                        <div className="font-semibold text-zinc-900 dark:text-zinc-100 truncate max-w-[180px]">
                                                            {emp.name}
                                                        </div>
                                                        <div className="flex items-center gap-1.5 mt-0.5">
                                                            <span className="text-[10px] text-zinc-400 font-mono">
                                                                {emp.employee_code}
                                                            </span>
                                                            <span className="text-[10px] text-zinc-300">•</span>
                                                            <span className="text-[10px] text-zinc-500 truncate max-w-[90px]">
                                                                {emp.department}
                                                            </span>
                                                            <span
                                                                className="px-1.5 py-0.2 rounded text-[9px] font-bold"
                                                                style={{
                                                                    backgroundColor: '#2563eb15',
                                                                    color: '#2563eb',
                                                                }}
                                                                title={`Default Shift: ${emp.default_shift_name}`}
                                                            >
                                                                Def: {emp.default_shift_code}
                                                            </span>
                                                        </div>
                                                    </td>

                                                    {/* Day Cells 1..31 */}
                                                    {daysList.map((day) => {
                                                        const cell = emp.days[day.day] || {};
                                                        const isOff = cell.is_off_day || cell.shift_code === 'OFF';

                                                        return (
                                                            <td
                                                                key={day.date}
                                                                onClick={() => handleCellClick(emp, day.day, day)}
                                                                title={`${emp.name} (${day.date}): ${cell.shift_name} [${cell.shift_code}] ${cell.notes ? `\nCatatan: ${cell.notes}` : ''} \nKlik untuk ganti shift`}
                                                                className={`p-1 text-center border-r border-zinc-200 dark:border-zinc-800 cursor-pointer select-none transition-transform hover:scale-105 ${
                                                                    day.is_sunday
                                                                        ? 'bg-rose-50/30 dark:bg-rose-950/10'
                                                                        : day.is_saturday
                                                                        ? 'bg-amber-50/30 dark:bg-amber-950/10'
                                                                        : ''
                                                                }`}
                                                            >
                                                                <div
                                                                    className={`w-7 h-7 mx-auto rounded-md flex flex-col items-center justify-center font-bold text-[10px] transition-all shadow-2xs ${
                                                                        cell.is_explicit
                                                                            ? 'ring-1.5 ring-indigo-500 font-extrabold'
                                                                            : ''
                                                                    }`}
                                                                    style={{
                                                                        backgroundColor: isOff ? '#f1f5f9' : `${cell.color_code}22`,
                                                                        color: isOff ? '#64748b' : cell.color_code,
                                                                        border: `1px solid ${isOff ? '#cbd5e1' : `${cell.color_code}55`}`,
                                                                    }}
                                                                >
                                                                    <span>{cell.shift_code}</span>
                                                                </div>
                                                            </td>
                                                        );
                                                    })}

                                                    {/* Summary Counts Column */}
                                                    <td className="px-3 py-2 text-center text-[10px] text-zinc-500">
                                                        <div className="flex items-center justify-center gap-1.5 flex-wrap">
                                                            {Object.entries(emp.summary || {}).map(([code, count]) => (
                                                                <span
                                                                    key={code}
                                                                    className="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 font-mono text-[10px] text-zinc-700 dark:text-zinc-300 font-semibold"
                                                                >
                                                                    {code}:{count}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* TAB 2: MASTER SHIFT KERJA */}
                {currentTab === 'shifts' && (
                    <div className="space-y-4">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xs">
                            <div>
                                <h3 className="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    Daftar Master Shift Kerja
                                </h3>
                                <p className="text-xs text-zinc-500 mt-0.5">
                                    Definisikan nama shift, jam kerja standar, batas toleransi keterlambatan, dan warna penanda badge.
                                </p>
                            </div>
                            <Button
                                size="sm"
                                onClick={handleOpenCreateShift}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs"
                            >
                                <Plus className="h-4 w-4 mr-1.5" />
                                Tambah Master Shift
                            </Button>
                        </div>
<DataTable
                            columns={shiftColumns}
                            data={shifts}
                            searchPlaceholder="Cari kode atau nama shift..."
                            exportFileName={`Master_Shift_${new Date().toISOString().slice(0, 10)}`}
                            emptyMessage="Belum ada master shift kerja."
                        />
                    </div>
                )}
            </div>

            {/* MODAL 1: ATUR ROSTER MASSAL (BULK ASSIGN) */}
            <Dialog open={isBulkRosterOpen} onOpenChange={setIsBulkRosterOpen}>
                <DialogContent className="sm:max-w-[560px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-zinc-100">
                            <Plus className="h-5 w-5 text-indigo-600" />
                            Penugasan Roster Massal (Bulk Assign)
                        </DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            Plot jadwal shift kerja untuk banyak karyawan sekaligus dalam rentang tanggal dan pola hari tertentu.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveBulkRoster} className="space-y-4 py-2">
                        {/* Target Karyawan */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Departemen Target</Label>
                                <select
                                    value={bulkRosterForm.data.department}
                                    onChange={(e) => bulkRosterForm.setData('department', e.target.value)}
                                    className="w-full h-9 text-xs rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 focus:ring-indigo-500"
                                >
                                    <option value="all">Semua Departemen</option>
                                    {departments.map((dept) => (
                                        <option key={dept} value={dept}>
                                            {dept}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Pilih Shift Target *</Label>
                                <select
                                    value={bulkRosterForm.data.shift_id}
                                    onChange={(e) => bulkRosterForm.setData('shift_id', e.target.value)}
                                    className="w-full h-9 text-xs rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-semibold focus:ring-indigo-500"
                                    required
                                >
                                    {shifts.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            [{s.shift_code}] {s.name} ({s.time_range_formatted})
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Rentang Tanggal */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Dari Tanggal *</Label>
                                <Input
                                    type="date"
                                    value={bulkRosterForm.data.start_date}
                                    onChange={(e) => bulkRosterForm.setData('start_date', e.target.value)}
                                    className="h-9 text-xs"
                                    required
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Sampai Tanggal *</Label>
                                <Input
                                    type="date"
                                    value={bulkRosterForm.data.end_date}
                                    onChange={(e) => bulkRosterForm.setData('end_date', e.target.value)}
                                    className="h-9 text-xs"
                                    required
                                />
                            </div>
                        </div>

                        {/* Pola Hari dalam Minggu */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label className="text-xs font-semibold">Berlaku Pada Hari</Label>
                                <div className="flex items-center gap-2 text-[11px] text-indigo-600 font-medium">
                                    <button
                                        type="button"
                                        onClick={() => bulkRosterForm.setData('days_of_week', [1, 2, 3, 4, 5, 6, 0])}
                                        className="hover:underline"
                                    >
                                        Semua Hari
                                    </button>
                                    <span>•</span>
                                    <button
                                        type="button"
                                        onClick={() => bulkRosterForm.setData('days_of_week', [1, 2, 3, 4, 5])}
                                        className="hover:underline"
                                    >
                                        Senin-Jumat Saja
                                    </button>
                                </div>
                            </div>
                            <div className="flex items-center gap-1.5 flex-wrap">
                                {DAYS_OF_WEEK.map((d) => {
                                    const isSelected = bulkRosterForm.data.days_of_week.includes(d.id);
                                    return (
                                        <button
                                            key={d.id}
                                            type="button"
                                            onClick={() => {
                                                const current = bulkRosterForm.data.days_of_week;
                                                const next = isSelected
                                                    ? current.filter((x) => x !== d.id)
                                                    : [...current, d.id];
                                                bulkRosterForm.setData('days_of_week', next);
                                            }}
                                            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                                isSelected
                                                    ? 'bg-indigo-600 text-white shadow-xs'
                                                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200'
                                            }`}
                                        >
                                            {d.label}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Catatan Jadwal & Override */}
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Catatan Roster (Opsional)</Label>
                            <Input
                                placeholder="Misal: Penyesuaian shift lini sablon PO Ramadhan..."
                                value={bulkRosterForm.data.notes}
                                onChange={(e) => bulkRosterForm.setData('notes', e.target.value)}
                                className="h-9 text-xs"
                            />
                        </div>

                        <div className="flex items-center justify-between p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                            <div>
                                <p className="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                    Timpa Jadwal Roster yang Sudah Ada (Override)
                                </p>
                                <p className="text-[11px] text-zinc-500">
                                    Jika aktif, jadwal yang telah diplot sebelumnya pada rentang tanggal ini akan diperbarui.
                                </p>
                            </div>
                            <Switch
                                checked={bulkRosterForm.data.override_existing}
                                onCheckedChange={(val) => bulkRosterForm.setData('override_existing', val)}
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsBulkRosterOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={bulkRosterForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {bulkRosterForm.processing ? 'Menyimpan...' : 'Terapkan Penugasan Roster'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 2: QUICK EDIT SATUAN PER SEL HARI */}
            <Dialog open={isSingleCellOpen} onOpenChange={setIsSingleCellOpen}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-zinc-100">
                            <Calendar className="h-5 w-5 text-indigo-600" />
                            Ubah Shift Harian Karyawan
                        </DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            {selectedCell?.employee?.name} • Tanggal {selectedCell?.dayInfo?.date}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveSingleCell} className="space-y-4 py-2">
                        <div className="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 border text-xs space-y-1">
                            <p className="text-zinc-500">
                                Shift Default Karyawan: <span className="font-bold text-zinc-800 dark:text-zinc-200">{selectedCell?.employee?.default_shift_name}</span>
                            </p>
                            <p className="text-zinc-500">
                                Shift Saat Ini: <span className="font-bold text-indigo-600">{selectedCell?.cell?.shift_name} ({selectedCell?.cell?.shift_code})</span>
                            </p>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Tugaskan Shift Baru</Label>
                            <select
                                value={singleCellForm.data.shift_id}
                                onChange={(e) => singleCellForm.setData('shift_id', e.target.value)}
                                className="w-full h-9 text-xs rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-semibold focus:ring-indigo-500"
                            >
                                <option value="">-- Kembalikan ke Default Shift Karyawan --</option>
                                {shifts.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        [{s.shift_code}] {s.name} ({s.time_range_formatted})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Catatan Khusus (Opsional)</Label>
                            <Input
                                placeholder="Misal: Tukar shift dengan Sdr. Budi..."
                                value={singleCellForm.data.notes}
                                onChange={(e) => singleCellForm.setData('notes', e.target.value)}
                                className="h-9 text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsSingleCellOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={singleCellForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {singleCellForm.processing ? 'Menyimpan...' : 'Simpan Shift'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 3: TAMBAH / EDIT MASTER SHIFT */}
            <Dialog open={isShiftModalOpen} onOpenChange={setIsShiftModalOpen}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-zinc-100">
                            <SlidersHorizontal className="h-5 w-5 text-indigo-600" />
                            {editingShift ? 'Edit Master Shift Kerja' : 'Tambah Master Shift Baru'}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            Konfigurasi aturan jam kerja standar, kode badge, dan toleransi keterlambatan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveShift} className="space-y-4 py-2">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Nama Shift *</Label>
                                <Input
                                    placeholder="Misal: Shift 1 (Pagi Produksi)"
                                    value={shiftForm.data.name}
                                    onChange={(e) => shiftForm.setData('name', e.target.value)}
                                    className="h-9 text-xs"
                                    required
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Kode Singkat (Badge) *</Label>
                                <Input
                                    placeholder="Misal: S1, S2, REG, OFF"
                                    value={shiftForm.data.shift_code}
                                    onChange={(e) => shiftForm.setData('shift_code', e.target.value.toUpperCase())}
                                    className="h-9 text-xs font-mono font-bold uppercase"
                                    maxLength={10}
                                    required
                                />
                            </div>
                        </div>

                        {/* Switch Hari Libur / Off */}
                        <div className="flex items-center justify-between p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border">
                            <div>
                                <p className="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                    Ini adalah Shift Libur / Hari Off Rutin?
                                </p>
                                <p className="text-[11px] text-zinc-500">
                                    Jika aktif, jam masuk dan pulang akan dinonaktifkan.
                                </p>
                            </div>
                            <Switch
                                checked={shiftForm.data.is_off_day}
                                onCheckedChange={(val) => shiftForm.setData('is_off_day', val)}
                            />
                        </div>

                        {!shiftForm.data.is_off_day && (
                            <>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold">Jam Masuk (Format 24 Jam) *</Label>
                                        <Input
                                            type="time"
                                            value={shiftForm.data.start_time}
                                            onChange={(e) => shiftForm.setData('start_time', e.target.value)}
                                            className="h-9 text-xs font-mono"
                                            required={!shiftForm.data.is_off_day}
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold">Jam Pulang (Format 24 Jam) *</Label>
                                        <Input
                                            type="time"
                                            value={shiftForm.data.end_time}
                                            onChange={(e) => shiftForm.setData('end_time', e.target.value)}
                                            className="h-9 text-xs font-mono"
                                            required={!shiftForm.data.is_off_day}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold">Toleransi Keterlambatan (Menit)</Label>
                                        <Input
                                            type="number"
                                            min={0}
                                            max={180}
                                            value={shiftForm.data.late_tolerance_minutes}
                                            onChange={(e) => shiftForm.setData('late_tolerance_minutes', parseInt(e.target.value, 10) || 0)}
                                            className="h-9 text-xs"
                                        />
                                    </div>

                                    <div className="flex items-center justify-between p-2 rounded-lg bg-zinc-50 dark:bg-zinc-800 border self-end h-9">
                                        <span className="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                            Lintas Hari (+1 Hari)?
                                        </span>
                                        <Switch
                                            checked={shiftForm.data.is_overnight}
                                            onCheckedChange={(val) => shiftForm.setData('is_overnight', val)}
                                        />
                                    </div>
                                </div>
                            </>
                        )}

                        {/* Pilihan Warna Badge Preset */}
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Warna Badge Visual</Label>
                            <div className="flex items-center gap-2 flex-wrap">
                                {PRESET_COLORS.map((color) => (
                                    <button
                                        key={color.value}
                                        type="button"
                                        onClick={() => shiftForm.setData('color_code', color.value)}
                                        className={`w-7 h-7 rounded-lg transition-transform flex items-center justify-center ${
                                            shiftForm.data.color_code === color.value
                                                ? 'ring-2 ring-offset-2 ring-indigo-600 scale-110 shadow-xs'
                                                : 'hover:scale-105 opacity-80'
                                        }`}
                                        style={{ backgroundColor: color.value }}
                                        title={color.label}
                                    >
                                        {shiftForm.data.color_code === color.value && (
                                            <Check className="h-4 w-4 text-white" />
                                        )}
                                    </button>
                                ))}
                                <Input
                                    type="color"
                                    value={shiftForm.data.color_code}
                                    onChange={(e) => shiftForm.setData('color_code', e.target.value)}
                                    className="w-8 h-8 p-0 border-0 rounded cursor-pointer"
                                    title="Pilih warna khusus"
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Deskripsi / Aturan Shift</Label>
                            <Textarea
                                placeholder="Keterangan operasional shift..."
                                value={shiftForm.data.description}
                                onChange={(e) => shiftForm.setData('description', e.target.value)}
                                rows={2}
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsShiftModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={shiftForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {shiftForm.processing ? 'Menyimpan...' : editingShift ? 'Perbarui Shift' : 'Tambah Shift'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 4: SET DEFAULT SHIFT KARYAWAN */}
            <Dialog open={isDefaultShiftOpen} onOpenChange={setIsDefaultShiftOpen}>
                <DialogContent className="sm:max-w-[460px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-zinc-100">
                            <Users className="h-5 w-5 text-indigo-600" />
                            Atur Shift Bawaan (Default Shift)
                        </DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            Set shift standar harian karyawan jika tidak dijadwalkan khusus dalam roster harian.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveDefaultShift} className="space-y-4 py-2">
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Pilih Departemen Sasaran</Label>
                            <select
                                value={defaultShiftForm.data.department}
                                onChange={(e) => defaultShiftForm.setData('department', e.target.value)}
                                className="w-full h-9 text-xs rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 focus:ring-indigo-500"
                            >
                                <option value="all">-- Semua Departemen --</option>
                                {departments.map((dept) => (
                                    <option key={dept} value={dept}>
                                        {dept}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Pilih Shift Bawaan (Default Shift) *</Label>
                            <select
                                value={defaultShiftForm.data.shift_id}
                                onChange={(e) => defaultShiftForm.setData('shift_id', e.target.value)}
                                className="w-full h-9 text-xs rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-semibold focus:ring-indigo-500"
                                required
                            >
                                {shifts.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        [{s.shift_code}] {s.name} ({s.time_range_formatted})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsDefaultShiftOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={defaultShiftForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {defaultShiftForm.processing ? 'Menyimpan...' : 'Terapkan Default Shift'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 5: RESET / BERSIHKAN ROSTER */}
            <Dialog open={isClearRosterOpen} onOpenChange={setIsClearRosterOpen}>
                <DialogContent className="sm:max-w-[460px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-rose-600 dark:text-rose-400">
                            <RotateCcw className="h-5 w-5" />
                            Bersihkan / Reset Roster Khusus
                        </DialogTitle>
                        <DialogDescription className="text-xs text-zinc-500">
                            Hapus penugasan jadwal shift khusus pada rentang tanggal terpilih sehingga jadwal kembali ke default shift masing-masing karyawan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveClearRoster} className="space-y-4 py-2">
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Departemen</Label>
                            <select
                                value={clearRosterForm.data.department}
                                onChange={(e) => clearRosterForm.setData('department', e.target.value)}
                                className="w-full h-9 text-xs rounded-lg border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 focus:ring-indigo-500"
                            >
                                <option value="all">Semua Departemen</option>
                                {departments.map((dept) => (
                                    <option key={dept} value={dept}>
                                        {dept}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Dari Tanggal</Label>
                                <Input
                                    type="date"
                                    value={clearRosterForm.data.start_date}
                                    onChange={(e) => clearRosterForm.setData('start_date', e.target.value)}
                                    className="h-9 text-xs"
                                    required
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Sampai Tanggal</Label>
                                <Input
                                    type="date"
                                    value={clearRosterForm.data.end_date}
                                    onChange={(e) => clearRosterForm.setData('end_date', e.target.value)}
                                    className="h-9 text-xs"
                                    required
                                />
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsClearRosterOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={clearRosterForm.processing}
                                className="bg-rose-600 hover:bg-rose-700 text-white text-xs"
                            >
                                {clearRosterForm.processing ? 'Menghapus...' : 'Reset Jadwal Roster'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
