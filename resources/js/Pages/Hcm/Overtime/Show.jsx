import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import {
    ArrowLeft,
    Clock,
    Calendar,
    Users,
    ShieldCheck,
    CheckCircle2,
    DollarSign,
    Lock,
    Unlock,
    Trash2,
    Plus,
    Landmark,
    Coins,
    Sparkles,
    AlertCircle,
    Check,
    Printer,
    FileSpreadsheet,
    Eye,
    Pencil,
    Search,
    X,
    UserCheck,
    Briefcase,
    Building2,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { SearchableSelect } from '@/Components/ui/searchable-select';

export default function OvertimeShow({
    batch,
    rates,
    employees = [],
    positions = [],
    departments = [],
}) {
    // State Filter untuk Employee Picker
    const [filterPosition, setFilterPosition] = useState('all');
    const [filterDept, setFilterDept] = useState('all');
    const [searchEmployee, setSearchEmployee] = useState('');
    const [selectedEmployeeIds, setSelectedEmployeeIds] = useState([]);

    // State Modals
    const [isFinanceModalOpen, setIsFinanceModalOpen] = useState(false);
    const [viewItemModal, setViewItemModal] = useState({ isOpen: false, item: null });
    const [editItemModal, setEditItemModal] = useState({ isOpen: false, item: null });
    const [deleteItemModal, setDeleteItemModal] = useState({ isOpen: false, item: null });

    // Form Tambah Item Lembur Massal
    const itemForm = useForm({
        overtime_date: batch.period_start || new Date().toISOString().split('T')[0],
        day_type: 'Lembur Hari Kerja',
        duration_hours: 1.0,
        task_description: '',
        employee_ids: [],
    });

    // Form Edit Item Lembur
    const editItemForm = useForm({
        overtime_date: '',
        day_type: 'Lembur Hari Kerja',
        duration_hours: 1.0,
        task_description: '',
    });

    // Form Sign-off Keuangan
    const financeForm = useForm({
        payment_method: 'Kas Tunai',
        coa_code: batch.coa_code || rates.coa_code || '5-50100',
        finance_notes: '',
        payout_proof: null,
    });

    // Format Rupiah
    const formatRp = (val) => {
        if (!val) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val);
    };

    // Kalkulasi tarif instan di antarmuka dispatcher
    const currentRatePerHour = itemForm.data.day_type === 'Lembur Hari Libur'
        ? rates.weekend_hourly_rate
        : rates.weekday_hourly_rate;

    const currentFirstHalfRate = itemForm.data.day_type === 'Lembur Hari Libur'
        ? rates.weekend_first_half_rate
        : rates.weekday_first_half_rate;

    const estimatedPerPerson = itemForm.data.duration_hours < 0.5
        ? 0
        : itemForm.data.duration_hours === 0.5
        ? currentFirstHalfRate
        : Math.round(itemForm.data.duration_hours * currentRatePerHour);

    const estimatedTotalBatch = estimatedPerPerson * selectedEmployeeIds.length;

    // Kalkulasi tarif untuk form edit
    const editRatePerHour = editItemForm.data.day_type === 'Lembur Hari Libur'
        ? rates.weekend_hourly_rate
        : rates.weekday_hourly_rate;

    const editFirstHalfRate = editItemForm.data.day_type === 'Lembur Hari Libur'
        ? rates.weekend_first_half_rate
        : rates.weekday_first_half_rate;

    const editEstimatedAmount = editItemForm.data.duration_hours < 0.5
        ? 0
        : editItemForm.data.duration_hours === 0.5
        ? editFirstHalfRate
        : Math.round(editItemForm.data.duration_hours * editRatePerHour);

    // Filter daftar karyawan berdasarkan Posisi, Departemen, dan Search
    const filteredEmployees = useMemo(() => {
        return employees.filter((emp) => {
            const matchPosition = filterPosition === 'all' || emp.position === filterPosition;
            const matchDept = filterDept === 'all' || emp.department === filterDept;
            const query = searchEmployee.toLowerCase().trim();
            const matchSearch = !query ||
                (emp.name && emp.name.toLowerCase().includes(query)) ||
                (emp.employee_code && emp.employee_code.toLowerCase().includes(query)) ||
                (emp.position && emp.position.toLowerCase().includes(query));
            return matchPosition && matchDept && matchSearch;
        });
    }, [employees, filterPosition, filterDept, searchEmployee]);

    // Handle pilih semua / batalkan semua hasil filter
    const handleSelectAllFiltered = () => {
        const filteredIds = filteredEmployees.map((e) => e.id);
        const allFilteredSelected = filteredIds.length > 0 && filteredIds.every((id) => selectedEmployeeIds.includes(id));

        if (allFilteredSelected) {
            setSelectedEmployeeIds((prev) => prev.filter((id) => !filteredIds.includes(id)));
        } else {
            setSelectedEmployeeIds((prev) => Array.from(new Set([...prev, ...filteredIds])));
        }
    };

    const handleToggleEmployee = (empId) => {
        setSelectedEmployeeIds((prev) => {
            if (prev.includes(empId)) {
                return prev.filter((id) => id !== empId);
            } else {
                return [...prev, empId];
            }
        });
    };

    const handleRemoveSelectedChip = (empId) => {
        setSelectedEmployeeIds((prev) => prev.filter((id) => id !== empId));
    };

    // Submit tambah lembur massal
    const handleItemSubmit = (e) => {
        e.preventDefault();
        if (selectedEmployeeIds.length === 0) {
            alert('Pilih minimal 1 karyawan untuk ditugaskan lembur.');
            return;
        }

        itemForm.transform((data) => ({
            ...data,
            employee_ids: selectedEmployeeIds,
        })).post(route('hcm.overtime.items.store', batch.batch_code || batch.id), {
            onSuccess: () => {
                setSelectedEmployeeIds([]);
                itemForm.setData('task_description', '');
            },
        });
    };

    // Open Edit Modal
    const openEditModal = (item) => {
        setEditItemModal({ isOpen: true, item });
        editItemForm.setData({
            overtime_date: item.overtime_date,
            day_type: item.day_type,
            duration_hours: parseFloat(item.duration_hours) || 1.0,
            task_description: item.task_description || '',
        });
    };

    const handleEditItemSubmit = (e) => {
        e.preventDefault();
        if (!editItemModal.item) return;

        editItemForm.put(route('hcm.overtime.items.update', [batch.batch_code || batch.id, editItemModal.item.uuid || editItemModal.item.id]), {
            onSuccess: () => {
                setEditItemModal({ isOpen: false, item: null });
            },
        });
    };

    // Delete item lembur
    const handleDeleteItemSubmit = () => {
        if (!deleteItemModal.item) return;

        router.delete(route('hcm.overtime.items.destroy', [batch.batch_code || batch.id, deleteItemModal.item.uuid || deleteItemModal.item.id]), {
            onSuccess: () => {
                setDeleteItemModal({ isOpen: false, item: null });
            },
        });
    };

    // Otorisasi Sign-off HCM
    const handleSignHcm = () => {
        if (confirm('Konfirmasi persetujuan & tanda tangan HCM? Jam lembur akan dikunci dan diteruskan ke Tim Keuangan untuk pencairan.')) {
            router.post(route('hcm.overtime.sign-hcm', batch.batch_code || batch.id));
        }
    };

    // Otorisasi Sign-off Keuangan
    const handleFinanceSubmit = (e) => {
        e.preventDefault();
        financeForm.post(route('hcm.overtime.sign-finance', batch.batch_code || batch.id), {
            forceFormData: true,
            onSuccess: () => {
                setIsFinanceModalOpen(false);
            },
        });
    };

    const positionOptions = [
        { value: 'all', label: 'Semua Posisi / Jabatan' },
        ...(positions || []).map((p) => ({ value: p, label: p })),
    ];

    const departmentOptions = [
        { value: 'all', label: 'Semua Divisi' },
        ...(departments || []).map((d) => ({ value: d, label: d })),
    ];

    const dayTypeOptions = [
        { value: 'Lembur Hari Kerja', label: 'Lembur Hari Kerja (Weekdays - Rp 10.000/jam)' },
        { value: 'Lembur Hari Libur', label: 'Lembur Hari Libur / Weekend (Rp 15.000/jam)' },
    ];

    const paymentMethodOptions = [
        { value: 'Kas Tunai', label: 'Kas Tunai (Petty Cash)' },
        { value: 'Transfer Bank', label: 'Transfer Bank (Payroll BRI)' },
    ];

    // Helper selected employee objects
    const selectedEmployeeObjects = useMemo(() => {
        const map = new Map(employees.map((e) => [e.id, e]));
        return selectedEmployeeIds.map((id) => map.get(id)).filter(Boolean);
    }, [employees, selectedEmployeeIds]);

    const isDraft = batch.status === 'DRAFT';

    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('hcm.overtime.index')}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 shadow-xs"
                            title="Kembali ke Daftar Batch"
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 font-mono">
                                    {batch.batch_code}
                                </h1>
                                <Badge
                                    className={`text-xs ${
                                        batch.status === 'PAID_COMPLETED'
                                            ? 'bg-emerald-500/10 text-emerald-600 border-emerald-300'
                                            : batch.status === 'APPROVED_BY_HCM'
                                            ? 'bg-amber-500/10 text-amber-600 border-amber-300'
                                            : batch.status === 'PENDING_FINANCE_SIGN'
                                            ? 'bg-sky-500/10 text-sky-600 border-sky-300'
                                            : 'bg-zinc-500/10 text-zinc-600'
                                    }`}
                                >
                                    {batch.status === 'PAID_COMPLETED'
                                        ? 'LUNAS (PAID)'
                                        : batch.status === 'APPROVED_BY_HCM'
                                        ? 'SIGN HCM (MENUNGGU BAYAR)'
                                        : batch.status === 'PENDING_FINANCE_SIGN'
                                        ? 'VERIFIKASI KAS (KEUANGAN)'
                                        : 'DRAF (HCM)'}
                                </Badge>
                            </div>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Cut-Off: <span className="font-mono text-zinc-700 dark:text-zinc-300">{batch.period_start}</span> s.d. <span className="font-mono text-zinc-700 dark:text-zinc-300">{batch.period_end}</span> • Tanggal Bayar: <span className="font-mono text-zinc-700 dark:text-zinc-300">{batch.payout_date}</span>
                            </p>
                        </div>
                    </div>

                    {/* Double Sign-Off Controls */}
                    <div className="flex items-center gap-2 flex-wrap">
                        {batch.status === 'DRAFT' && (
                            <Button
                                onClick={handleSignHcm}
                                size="sm"
                                className="bg-amber-600 hover:bg-amber-700 text-white text-xs gap-1.5 shadow-sm"
                            >
                                <ShieldCheck className="h-4 w-4" />
                                1. Approve & Sign HCM
                            </Button>
                        )}

                        {batch.status === 'APPROVED_BY_HCM' && (
                            <Button
                                onClick={() => router.post(route('hcm.overtime.start-finance', batch.batch_code || batch.id), {}, { preserveScroll: true })}
                                size="sm"
                                className="bg-sky-600 hover:bg-sky-700 text-white text-xs gap-1.5 shadow-sm"
                            >
                                <DollarSign className="h-4 w-4" />
                                2. Mulai Verifikasi Kas (Keuangan)
                            </Button>
                        )}

                        {batch.status === 'PENDING_FINANCE_SIGN' && (
                            <Button
                                onClick={() => setIsFinanceModalOpen(true)}
                                size="sm"
                                className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs gap-1.5 shadow-sm"
                            >
                                <DollarSign className="h-4 w-4" />
                                3. Sign & Tandai Lunas
                            </Button>
                        )}

                        {batch.status === 'PAID_COMPLETED' && (
                            <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300 text-xs gap-1 py-1 px-2.5">
                                <Lock className="h-3.5 w-3.5" />
                                Terkunci Permanen (Lunas)
                            </Badge>
                        )}

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.open(route('hcm.overtime.pdf', batch.batch_code || batch.id) + '?action=stream', '_blank')}
                            className="text-xs gap-1.5"
                        >
                            <Printer className="h-3.5 w-3.5" /> Cetak PDF
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => { window.location.href = route('hcm.overtime.export', batch.batch_code || batch.id); }}
                            className="text-xs gap-1.5 border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400"
                        >
                            <FileSpreadsheet className="h-3.5 w-3.5" /> Excel
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`Lembar Lembur ${batch.batch_code} - Kepegawaian`} />

            <div className="space-y-4">
                {/* 1. Header Banner & Status Double Sign-Off */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-4">
                        <span className="text-xs text-zinc-400 font-medium uppercase tracking-wider">Total Jam Lembur</span>
                        <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
                            {parseFloat(batch.total_hours).toFixed(1)} Jam
                        </div>
                        <span className="text-[11px] text-zinc-500 mt-0.5 block">
                            Total {batch.overtimes?.length || 0} entri penugasan
                        </span>
                    </Card>

                    <Card className="border border-indigo-200/80 dark:border-indigo-900/40 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-sm p-4">
                        <span className="text-xs text-indigo-600 dark:text-indigo-400 font-medium uppercase tracking-wider">Total Anggaran Upah Lembur</span>
                        <div className="text-2xl font-bold text-indigo-700 dark:text-indigo-300 mt-1 font-mono">
                            {formatRp(batch.total_amount)}
                        </div>
                        <span className="text-[11px] text-zinc-500 mt-0.5 block">
                            Akun COA: <strong className="font-mono text-zinc-700 dark:text-zinc-300">{batch.coa_code || rates.coa_code}</strong>
                        </span>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-4 space-y-2">
                        <div className="flex items-center justify-between text-xs">
                            <span className="font-medium text-zinc-500">Sign-Off HCM:</span>
                            {batch.hcm_signer ? (
                                <Badge variant="outline" className="text-[11px] text-emerald-600 border-emerald-300 bg-emerald-50">
                                    ✓ {batch.hcm_signer.name} ({batch.hcm_signed_at?.substring(0, 10)})
                                </Badge>
                            ) : (
                                <Badge variant="outline" className="text-[11px] text-zinc-400">
                                    Menunggu Persetujuan
                                </Badge>
                            )}
                        </div>

                        <div className="flex items-center justify-between text-xs">
                            <span className="font-medium text-zinc-500">Sign-Off Keuangan:</span>
                            {batch.finance_signer ? (
                                <Badge variant="outline" className="text-[11px] text-emerald-600 border-emerald-300 bg-emerald-50">
                                    ✓ {batch.finance_signer.name} ({batch.payment_method})
                                </Badge>
                            ) : (
                                <Badge variant="outline" className="text-[11px] text-zinc-400">
                                    Menunggu Pembayaran
                                </Badge>
                            )}
                        </div>
                    </Card>
                </div>

                {/* 2. Bulk Overtime Dispatcher (Hanya jika DRAFT) */}
                {isDraft && (
                    <Card className="border border-indigo-200/80 dark:border-indigo-900/40 bg-white dark:bg-zinc-900 shadow-sm p-4 space-y-4">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-2">
                            <div>
                                <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <Sparkles className="h-4 w-4 text-indigo-600" />
                                    Bulk Overtime Dispatcher (Penugasan Lembur Massal)
                                </h3>
                                <p className="text-xs text-zinc-500 dark:text-zinc-400">
                                    Tentukan tanggal lembur, pilih posisi/divisi dan centang karyawan yang ditugaskan lembur.
                                </p>
                            </div>
                            <div className="text-xs text-indigo-600 font-medium bg-indigo-50 dark:bg-indigo-950/40 px-2.5 py-1 rounded border border-indigo-200 dark:border-indigo-800/60 shrink-0">
                                Estimasi per orang: <strong>{formatRp(estimatedPerPerson)}</strong> ({itemForm.data.duration_hours} Jam)
                            </div>
                        </div>

                        <form onSubmit={handleItemSubmit} className="space-y-4">
                            {/* Baris Parameter Penugasan (Tanggal, Jenis Hari, Durasi) */}
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-zinc-50/70 dark:bg-zinc-800/40 p-3 rounded-lg border border-zinc-200/70 dark:border-zinc-800">
                                <div className="space-y-1">
                                    <Label className="text-xs font-semibold text-zinc-800 dark:text-zinc-200 flex items-center gap-1">
                                        <Calendar className="h-3.5 w-3.5 text-indigo-600" />
                                        <span>Tanggal Lembur *</span>
                                    </Label>
                                    <Input
                                        type="date"
                                        required
                                        min={batch.period_start}
                                        max={batch.period_end}
                                        value={itemForm.data.overtime_date}
                                        onChange={(e) => itemForm.setData('overtime_date', e.target.value)}
                                        className="text-xs bg-white dark:bg-zinc-900"
                                    />
                                    <span className="text-[10px] text-zinc-400 block">
                                        Rentang cut-off: {batch.period_start} s.d. {batch.period_end}
                                    </span>
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-semibold text-zinc-800 dark:text-zinc-200 flex items-center gap-1">
                                        <Clock className="h-3.5 w-3.5 text-indigo-600" />
                                        <span>Jenis Hari *</span>
                                    </Label>
                                    <SearchableSelect
                                        value={itemForm.data.day_type}
                                        onValueChange={(val) => itemForm.setData('day_type', val)}
                                        options={dayTypeOptions}
                                        placeholder="Pilih Jenis Hari..."
                                        clearable={false}
                                        className="text-xs bg-white dark:bg-zinc-900"
                                    />
                                    <span className="text-[10px] text-zinc-400 block">
                                        {itemForm.data.day_type === 'Lembur Hari Libur' ? 'Weekend/Libur: Rp 15.000/jam' : 'Hari Kerja: Rp 10.000/jam'}
                                    </span>
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-semibold text-zinc-800 dark:text-zinc-200 flex items-center gap-1">
                                        <Coins className="h-3.5 w-3.5 text-indigo-600" />
                                        <span>Durasi Lembur (Jam) *</span>
                                    </Label>
                                    <div className="flex items-center gap-1.5">
                                        <Input
                                            type="number"
                                            required
                                            step={0.5}
                                            min={0.5}
                                            max={24}
                                            value={itemForm.data.duration_hours}
                                            onChange={(e) => itemForm.setData('duration_hours', parseFloat(e.target.value) || 0.5)}
                                            className="text-xs font-mono bg-white dark:bg-zinc-900 w-24"
                                        />
                                        <div className="flex items-center gap-1">
                                            {[1, 1.5, 2, 3, 4].map((hr) => (
                                                <button
                                                    type="button"
                                                    key={hr}
                                                    onClick={() => itemForm.setData('duration_hours', hr)}
                                                    className={`px-1.5 py-1 text-[11px] rounded font-mono transition border ${
                                                        itemForm.data.duration_hours === hr
                                                            ? 'bg-indigo-600 text-white border-indigo-600 font-bold'
                                                            : 'bg-white dark:bg-zinc-900 text-zinc-600 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100'
                                                    }`}
                                                >
                                                    {hr}j
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    Catatan / Tugas Penugasan Kerja
                                </Label>
                                <Input
                                    value={itemForm.data.task_description}
                                    onChange={(e) => itemForm.setData('task_description', e.target.value)}
                                    placeholder="e.g. Penyelesaian target pesanan jahitan / Lembur sortir kain"
                                    className="text-xs"
                                />
                            </div>

                            {/* Seleksi Anggota Regu Kerja (User Friendly Selector) */}
                            <div className="space-y-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                {/* Bar Filter & Pilihan */}
                                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
                                    <div className="flex items-center gap-2">
                                        <Users className="h-4 w-4 text-indigo-600" />
                                        <Label className="text-xs font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">
                                            Pilih Karyawan Lembur:
                                        </Label>
                                        <Badge variant="outline" className="text-xs font-mono font-bold bg-indigo-50/50 text-indigo-700 border-indigo-200">
                                            {selectedEmployeeIds.length} Terpilih
                                        </Badge>
                                        {selectedEmployeeIds.length > 0 && (
                                            <span className="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                                = {formatRp(estimatedTotalBatch)}
                                            </span>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        {/* Filter Posisi (Jabatan) */}
                                        <div className="w-[180px]">
                                            <SearchableSelect
                                                value={filterPosition}
                                                onValueChange={(val) => setFilterPosition(val)}
                                                options={positionOptions}
                                                placeholder="Filter Posisi..."
                                                clearable={false}
                                                className="h-8 text-xs"
                                            />
                                        </div>

                                        {/* Filter Divisi */}
                                        <div className="w-[160px]">
                                            <SearchableSelect
                                                value={filterDept}
                                                onValueChange={(val) => setFilterDept(val)}
                                                options={departmentOptions}
                                                placeholder="Filter Divisi..."
                                                clearable={false}
                                                className="h-8 text-xs"
                                            />
                                        </div>

                                        {/* Search Karyawan */}
                                        <div className="relative w-[180px]">
                                            <Search className="absolute left-2.5 top-2 h-3.5 w-3.5 text-zinc-400" />
                                            <Input
                                                placeholder="Cari nama / NIK..."
                                                value={searchEmployee}
                                                onChange={(e) => setSearchEmployee(e.target.value)}
                                                className="h-8 pl-8 text-xs"
                                            />
                                        </div>

                                        {/* Pilih Semua / Batal Semua */}
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={handleSelectAllFiltered}
                                            className="h-8 text-xs px-2.5 gap-1"
                                        >
                                            <CheckCircle2 className="h-3.5 w-3.5 text-indigo-600" />
                                            <span>
                                                {filteredEmployees.length > 0 &&
                                                filteredEmployees.every((e) => selectedEmployeeIds.includes(e.id))
                                                    ? 'Batal Semua'
                                                    : `Pilih Semua (${filteredEmployees.length})`}
                                            </span>
                                        </Button>
                                    </div>
                                </div>

                                {/* Chips Karyawan yang Dipilih (Preview Cepat) */}
                                {selectedEmployeeObjects.length > 0 && (
                                    <div className="flex flex-wrap items-center gap-1.5 p-2 bg-indigo-50/40 dark:bg-indigo-950/20 rounded-md border border-indigo-100 dark:border-indigo-900/50 max-h-[85px] overflow-y-auto">
                                        <span className="text-[11px] font-semibold text-indigo-700 dark:text-indigo-300 mr-1">
                                            Karyawan Ditugaskan:
                                        </span>
                                        {selectedEmployeeObjects.map((emp) => (
                                            <span
                                                key={emp.id}
                                                className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-white dark:bg-zinc-800 border border-indigo-200 dark:border-indigo-800 text-zinc-800 dark:text-zinc-200 shadow-2xs"
                                            >
                                                <span>{emp.name}</span>
                                                <span className="text-[10px] text-zinc-400 font-mono">({emp.position || emp.department})</span>
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveSelectedChip(emp.id)}
                                                    className="text-zinc-400 hover:text-rose-600"
                                                >
                                                    <X className="h-3 w-3" />
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                )}

                                {/* Daftar Karyawan (Scrollable Table / List) */}
                                <div className="max-h-[260px] overflow-y-auto border border-zinc-200 dark:border-zinc-800 rounded-lg bg-white dark:bg-zinc-900">
                                    <Table>
                                        <TableHeader className="bg-zinc-50 dark:bg-zinc-800/60 sticky top-0 z-10">
                                            <TableRow>
                                                <TableHead className="w-[45px] text-center text-xs">Pilih</TableHead>
                                                <TableHead className="min-w-[180px] text-xs font-bold">Nama Karyawan</TableHead>
                                                <TableHead className="min-w-[130px] text-xs font-bold">Posisi / Jabatan</TableHead>
                                                <TableHead className="min-w-[130px] text-xs font-bold">Divisi</TableHead>
                                                <TableHead className="w-[100px] text-center text-xs font-bold">Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {filteredEmployees.length > 0 ? (
                                                filteredEmployees.map((emp) => {
                                                    const isSelected = selectedEmployeeIds.includes(emp.id);
                                                    return (
                                                        <TableRow
                                                            key={emp.id}
                                                            onClick={() => handleToggleEmployee(emp.id)}
                                                            className={`cursor-pointer transition-colors ${
                                                                isSelected
                                                                    ? 'bg-indigo-50/70 dark:bg-indigo-950/40 hover:bg-indigo-100/60'
                                                                    : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/40'
                                                            }`}
                                                        >
                                                            <TableCell className="text-center p-2">
                                                                <input
                                                                    type="checkbox"
                                                                    checked={isSelected}
                                                                    onChange={() => {}} // handled by row onClick
                                                                    className="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                                />
                                                            </TableCell>
                                                            <TableCell className="p-2">
                                                                <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                                                    {emp.name}
                                                                </div>
                                                                <div className="text-[10px] text-zinc-400 font-mono">
                                                                    {emp.employee_code}
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="p-2">
                                                                <Badge variant="outline" className="text-[11px] font-normal border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                                                                    {emp.position || '-'}
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell className="p-2 text-xs text-zinc-600 dark:text-zinc-400">
                                                                {emp.department || '-'}
                                                            </TableCell>
                                                            <TableCell className="text-center p-2">
                                                                {isSelected ? (
                                                                    <Badge className="bg-indigo-600 text-white text-[10px] py-0 px-2">
                                                                        Terpilih
                                                                    </Badge>
                                                                ) : (
                                                                    <span className="text-[10px] text-zinc-400">-</span>
                                                                )}
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={5} className="h-24 text-center text-xs text-zinc-400">
                                                        Tidak ada karyawan yang sesuai dengan filter Posisi / Divisi / Pencarian.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            <div className="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                <div className="text-xs text-zinc-500">
                                    {selectedEmployeeIds.length > 0 ? (
                                        <span>
                                            Menugaskan <strong>{selectedEmployeeIds.length} karyawan</strong> pada tanggal <strong>{itemForm.data.overtime_date}</strong> durasi <strong>{itemForm.data.duration_hours} jam</strong>.
                                        </span>
                                    ) : (
                                        <span>Centang karyawan di atas untuk mengaktifkan tombol simpan penugasan.</span>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    disabled={itemForm.processing || selectedEmployeeIds.length === 0}
                                    size="sm"
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5 shadow-sm"
                                >
                                    <Plus className="h-3.5 w-3.5" />
                                    {itemForm.processing ? 'Menambahkan...' : `Tambahkan Lembur (${selectedEmployeeIds.length} Karyawan)`}
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                {/* 3. Lembar Rincian Lembur Karyawan */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="p-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Clock className="h-4 w-4 text-indigo-600" />
                            <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Lembar Rincian Penugasan Lembur Karyawan
                            </h3>
                            <Badge variant="outline" className="text-xs font-mono">
                                {batch.overtimes?.length || 0} Entri
                            </Badge>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="w-[40px] text-center text-xs font-bold">No</TableHead>
                                    <TableHead className="min-w-[180px] text-xs font-bold">Karyawan</TableHead>
                                    <TableHead className="min-w-[110px] text-xs font-bold">Tanggal</TableHead>
                                    <TableHead className="min-w-[130px] text-xs font-bold">Jenis Hari</TableHead>
                                    <TableHead className="min-w-[90px] text-xs font-bold text-center">Durasi</TableHead>
                                    <TableHead className="min-w-[110px] text-xs font-bold text-right">Tarif / Jam</TableHead>
                                    <TableHead className="min-w-[120px] text-xs font-bold text-right">Total Upah</TableHead>
                                    <TableHead className="min-w-[180px] text-xs font-bold">Tugas / Penugasan</TableHead>
                                    <TableHead className="w-[120px] text-xs font-bold text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {batch.overtimes && batch.overtimes.length > 0 ? (
                                    batch.overtimes.map((item, idx) => (
                                        <TableRow key={item.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                            <TableCell className="text-center text-xs text-zinc-400 font-mono">
                                                {idx + 1}
                                            </TableCell>

                                            <TableCell>
                                                <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                                    {item.employee?.name}
                                                </div>
                                                <div className="text-[10px] text-zinc-400">
                                                    {item.employee?.employee_code} • {item.position || item.employee?.position || item.employee?.department}
                                                </div>
                                            </TableCell>

                                            <TableCell className="text-xs font-mono">
                                                {item.overtime_date}
                                            </TableCell>

                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className={`text-[11px] ${
                                                        item.day_type === 'Lembur Hari Libur'
                                                            ? 'border-amber-300 text-amber-600 bg-amber-50/50'
                                                            : 'border-zinc-200 text-zinc-600'
                                                    }`}
                                                >
                                                    {item.day_type === 'Lembur Hari Libur' ? 'Weekend / Libur' : 'Hari Kerja'}
                                                </Badge>
                                            </TableCell>

                                            <TableCell className="text-center font-mono text-xs font-semibold">
                                                {parseFloat(item.duration_hours).toFixed(1)} Jam
                                            </TableCell>

                                            <TableCell className="text-right text-xs font-mono text-zinc-500">
                                                {formatRp(item.hourly_rate)}
                                            </TableCell>

                                            <TableCell className="text-right text-xs font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                                {formatRp(item.total_amount)}
                                            </TableCell>

                                            <TableCell className="text-xs text-zinc-600 dark:text-zinc-400">
                                                {item.task_description || '-'}
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    {/* AKSI: LIHAT */}
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => setViewItemModal({ isOpen: true, item })}
                                                        className="h-7 px-1.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                                                        title="Lihat Rincian"
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                    </Button>

                                                    {/* AKSI: EDIT (Hanya jika DRAFT) */}
                                                    {isDraft && (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => openEditModal(item)}
                                                            className="h-7 px-1.5 text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/40"
                                                            title="Edit Rincian Lembur"
                                                        >
                                                            <Pencil className="h-3.5 w-3.5" />
                                                        </Button>
                                                    )}

                                                    {/* AKSI: HAPUS (Hanya jika DRAFT) */}
                                                    {isDraft && (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => setDeleteItemModal({ isOpen: true, item })}
                                                            className="h-7 px-1.5 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                                            title="Hapus lembur ini"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={9} className="h-32 text-center text-xs text-zinc-400">
                                            Belum ada penugasan lembur yang dicatat pada batch ini. Silakan gunakan Bulk Overtime Dispatcher di atas.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            {/* MODAL 1: Detail Rincian Lembur (LIHAT) */}
            <Dialog open={viewItemModal.isOpen} onOpenChange={(open) => setViewItemModal({ isOpen: open, item: open ? viewItemModal.item : null })}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2 text-indigo-600">
                            <Eye className="h-4 w-4" />
                            Detail Rincian Penugasan Lembur
                        </DialogTitle>
                    </DialogHeader>

                    {viewItemModal.item && (
                        <div className="space-y-3 text-xs pt-1">
                            <div className="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg space-y-1.5 border border-zinc-200/80 dark:border-zinc-700">
                                <div className="flex justify-between items-center">
                                    <span className="text-zinc-500">Nama Karyawan:</span>
                                    <strong className="text-zinc-900 dark:text-zinc-100">{viewItemModal.item.employee?.name}</strong>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-zinc-500">NIK / Kode:</span>
                                    <span className="font-mono">{viewItemModal.item.employee?.employee_code || '-'}</span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-zinc-500">Posisi / Jabatan:</span>
                                    <Badge variant="outline">{viewItemModal.item.position || viewItemModal.item.employee?.position || '-'}</Badge>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-zinc-500">Divisi / Unit:</span>
                                    <span>{viewItemModal.item.employee?.department || '-'}</span>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-2">
                                <div className="p-2.5 bg-zinc-50 dark:bg-zinc-800/40 rounded border border-zinc-200/60 dark:border-zinc-700">
                                    <span className="text-zinc-400 block text-[10px]">TANGGAL LEMBUR</span>
                                    <span className="font-mono font-bold text-zinc-800 dark:text-zinc-200">{viewItemModal.item.overtime_date}</span>
                                </div>
                                <div className="p-2.5 bg-zinc-50 dark:bg-zinc-800/40 rounded border border-zinc-200/60 dark:border-zinc-700">
                                    <span className="text-zinc-400 block text-[10px]">JENIS HARI</span>
                                    <span className="font-medium text-zinc-800 dark:text-zinc-200">{viewItemModal.item.day_type}</span>
                                </div>
                                <div className="p-2.5 bg-zinc-50 dark:bg-zinc-800/40 rounded border border-zinc-200/60 dark:border-zinc-700">
                                    <span className="text-zinc-400 block text-[10px]">DURASI LEMBUR</span>
                                    <span className="font-mono font-bold text-zinc-800 dark:text-zinc-200">{parseFloat(viewItemModal.item.duration_hours).toFixed(1)} Jam</span>
                                </div>
                                <div className="p-2.5 bg-indigo-50/60 dark:bg-indigo-950/30 rounded border border-indigo-200/60 dark:border-indigo-800">
                                    <span className="text-indigo-500 block text-[10px]">TOTAL UPAH LEMBUR</span>
                                    <span className="font-mono font-bold text-indigo-700 dark:text-indigo-300">{formatRp(viewItemModal.item.total_amount)}</span>
                                </div>
                            </div>

                            <div className="p-2.5 bg-zinc-50 dark:bg-zinc-800/40 rounded border border-zinc-200/60 dark:border-zinc-700">
                                <span className="text-zinc-400 block text-[10px]">TUGAS / DESKRIPSI PENUGASAN</span>
                                <p className="mt-0.5 text-zinc-700 dark:text-zinc-300">
                                    {viewItemModal.item.task_description || 'Tidak ada catatan spesifik.'}
                                </p>
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setViewItemModal({ isOpen: false, item: null })}
                                    className="text-xs"
                                >
                                    Tutup
                                </Button>
                            </DialogFooter>
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            {/* MODAL 2: Edit Rincian Lembur (EDIT) */}
            <Dialog open={editItemModal.isOpen} onOpenChange={(open) => setEditItemModal({ isOpen: open, item: open ? editItemModal.item : null })}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2 text-amber-600">
                            <Pencil className="h-4 w-4" />
                            Edit Penugasan Lembur Karyawan
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Mengedit lembur untuk <strong>{editItemModal.item?.employee?.name}</strong> ({editItemModal.item?.employee?.employee_code}).
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleEditItemSubmit} className="space-y-3 pt-2">
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Tanggal Lembur *</Label>
                            <Input
                                type="date"
                                required
                                min={batch.period_start}
                                max={batch.period_end}
                                value={editItemForm.data.overtime_date}
                                onChange={(e) => editItemForm.setData('overtime_date', e.target.value)}
                                className="text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Jenis Hari *</Label>
                            <SearchableSelect
                                value={editItemForm.data.day_type}
                                onValueChange={(val) => editItemForm.setData('day_type', val)}
                                options={dayTypeOptions}
                                placeholder="Pilih Jenis Hari..."
                                clearable={false}
                                className="text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Durasi Lembur (Jam) *</Label>
                            <Input
                                type="number"
                                required
                                step={0.5}
                                min={0.5}
                                max={24}
                                value={editItemForm.data.duration_hours}
                                onChange={(e) => editItemForm.setData('duration_hours', parseFloat(e.target.value) || 0.5)}
                                className="text-xs font-mono"
                            />
                            <div className="text-[11px] text-indigo-600 mt-1">
                                Estimasi Upah Baru: <strong>{formatRp(editEstimatedAmount)}</strong>
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Catatan / Deskripsi Penugasan</Label>
                            <Input
                                value={editItemForm.data.task_description}
                                onChange={(e) => editItemForm.setData('task_description', e.target.value)}
                                placeholder="Tugas kerja lembur"
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditItemModal({ isOpen: false, item: null })}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={editItemForm.processing}
                                className="bg-amber-600 hover:bg-amber-700 text-white text-xs"
                            >
                                {editItemForm.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 3: Konfirmasi Hapus Rincian Lembur (HAPUS) */}
            <Dialog open={deleteItemModal.isOpen} onOpenChange={(open) => setDeleteItemModal({ isOpen: open, item: open ? deleteItemModal.item : null })}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2 text-rose-600">
                            <Trash2 className="h-4 w-4" />
                            Konfirmasi Hapus Rincian Lembur
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Hapus penugasan lembur karyawan <strong>{deleteItemModal.item?.employee?.name}</strong> pada tanggal <strong>{deleteItemModal.item?.overtime_date}</strong> ({deleteItemModal.item?.duration_hours} jam - {formatRp(deleteItemModal.item?.total_amount)}) dari batch ini?
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="gap-2 pt-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeleteItemModal({ isOpen: false, item: null })}
                            className="text-xs"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            onClick={handleDeleteItemSubmit}
                            className="bg-rose-600 hover:bg-rose-700 text-white text-xs gap-1.5"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                            <span>Hapus Lembur</span>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* MODAL 4: Otorisasi Pembayaran Keuangan (Double Sign-Off Step 2) */}
            <Dialog open={isFinanceModalOpen} onOpenChange={setIsFinanceModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2 text-emerald-600">
                            <DollarSign className="h-5 w-5" />
                            Otorisasi Bayar & Double Sign-Off Keuangan
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Pencairan upah lembur batch <strong>{batch.batch_code}</strong> sebesar <strong>{formatRp(batch.total_amount)}</strong>. Setelah disetujui, lembar lembur akan terkunci permanen.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleFinanceSubmit} className="space-y-3 pt-2">
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Metode Pembayaran Dana *</Label>
                            <SearchableSelect
                                value={financeForm.data.payment_method}
                                onValueChange={(val) => financeForm.setData('payment_method', val)}
                                options={paymentMethodOptions}
                                placeholder="Pilih Metode..."
                                clearable={false}
                                className="text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Akun Akuntansi / COA Beban</Label>
                            <Input
                                value={financeForm.data.coa_code}
                                onChange={(e) => financeForm.setData('coa_code', e.target.value)}
                                className="text-xs font-mono"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Catatan Keuangan / No. Voucher Kas</Label>
                            <Textarea
                                rows={2}
                                value={financeForm.data.finance_notes}
                                onChange={(e) => financeForm.setData('finance_notes', e.target.value)}
                                placeholder="e.g. Dicairkan via kas kasir produksi / Diserahkan Sabtu 3 Okt"
                                className="text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Bukti Bayar / Transfer (Opsional)</Label>
                            <Input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                onChange={(e) => {
                                    if (e.target.files && e.target.files[0]) {
                                        financeForm.setData('payout_proof', e.target.files[0]);
                                    }
                                }}
                                className="text-xs file:text-xs"
                            />
                            <p className="text-[10px] text-zinc-500">
                                Tanda terima / slip transfer. Berkas dialirkan ke Google Drive; jika kosong, cukup catatan voucher.
                            </p>
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsFinanceModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={financeForm.processing}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs gap-1"
                            >
                                <CheckCircle2 className="h-4 w-4" />
                                {financeForm.processing ? 'Memproses...' : 'Sign & Tandai Lunas'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
