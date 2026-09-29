import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
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
    employees,
    departments,
}) {
    // State Filter Tim untuk Bulk Dispatcher
    const [filterDept, setFilterDept] = useState('all');
    const [selectedEmployeeIds, setSelectedEmployeeIds] = useState([]);
    const [isFinanceModalOpen, setIsFinanceModalOpen] = useState(false);

    // Form Tambah Item Lembur Massal
    const itemForm = useForm({
        overtime_date: batch.period_start || new Date().toISOString().split('T')[0],
        day_type: 'Lembur Hari Kerja',
        duration_hours: 1.0,
        task_description: '',
        employee_ids: [],
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

    // Kalkulasi tarif instan di antarmuka
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

    // Filter daftar karyawan berdasarkan departemen yang dipilih
    const filteredEmployees = (employees || []).filter((emp) => {
        if (filterDept === 'all') return true;
        return emp.department === filterDept;
    });

    // Handle pilih semua / batalkan semua karyawan
    const handleSelectAll = () => {
        if (selectedEmployeeIds.length === filteredEmployees.length) {
            setSelectedEmployeeIds([]);
        } else {
            setSelectedEmployeeIds(filteredEmployees.map((e) => e.id));
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
        })).post(route('hcm.overtime.items.store', batch.id), {
            onSuccess: () => {
                setSelectedEmployeeIds([]);
                itemForm.setData('task_description', '');
            },
        });
    };

    // Hapus item lembur
    const handleDeleteItem = (itemId) => {
        if (confirm('Hapus lembur karyawan ini dari batch?')) {
            router.delete(route('hcm.overtime.items.destroy', [batch.id, itemId]));
        }
    };

    // Otorisasi Sign-off HCM
    const handleSignHcm = () => {
        if (confirm('Konfirmasi persetujuan & tanda tangan HCM? Jam lembur akan dikunci dan diteruskan ke Tim Keuangan untuk pencairan.')) {
            router.post(route('hcm.overtime.sign-hcm', batch.id));
        }
    };

    // Otorisasi Sign-off Keuangan
    const handleFinanceSubmit = (e) => {
        e.preventDefault();
        financeForm.post(route('hcm.overtime.sign-finance', batch.id), {
            forceFormData: true,
            onSuccess: () => {
                setIsFinanceModalOpen(false);
            },
        });
    };

    const departmentOptions = [
        { value: 'all', label: 'Semua Divisi / Regu Kerja' },
        ...(departments || []).map((d) => ({ value: d, label: d })),
    ];

    const dayTypeOptions = [
        { value: 'Lembur Hari Kerja', label: 'Lembur Hari Kerja (Weekdays)' },
        { value: 'Lembur Hari Libur', label: 'Lembur Hari Libur / Weekend (Rp 15.000/jam)' },
    ];

    const paymentMethodOptions = [
        { value: 'Kas Tunai', label: 'Kas Tunai (Petty Cash)' },
        { value: 'Transfer Bank', label: 'Transfer Bank (Payroll BRI)' },
    ];

    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('hcm.overtime.index')}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
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
                                Cut-Off: {batch.period_start} s.d. {batch.period_end} • Tanggal Bayar: {batch.payout_date}
                            </p>
                        </div>
                    </div>

                    {/* Double Sign-Off Controls */}
                    <div className="flex items-center gap-2">
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
                                onClick={() => router.post(route('hcm.overtime.start-finance', batch.id), {}, { preserveScroll: true })}
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
                            onClick={() => window.open(route('hcm.overtime.pdf', batch.id) + '?action=stream', '_blank')}
                            className="text-xs gap-1.5"
                        >
                            <Printer className="h-3.5 w-3.5" /> Cetak PDF
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => { window.location.href = route('hcm.overtime.export', batch.id); }}
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
                {batch.status === 'DRAFT' && (
                    <Card className="border border-indigo-200/80 dark:border-indigo-900/40 bg-white dark:bg-zinc-900 shadow-sm p-4 space-y-4">
                        <div className="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2">
                            <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <Sparkles className="h-4 w-4 text-indigo-600" />
                                Bulk Overtime Dispatcher (Penugasan Lembur Massal Regu Kerja)
                            </h3>
                            <div className="text-xs text-indigo-600 font-medium">
                                Estimasi per orang: <strong>{formatRp(estimatedPerPerson)}</strong> ({itemForm.data.duration_hours} Jam)
                            </div>
                        </div>

                        <form onSubmit={handleItemSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Tanggal Lembur *</Label>
                                    <Input
                                        type="date"
                                        required
                                        min={batch.period_start}
                                        max={batch.period_end}
                                        value={itemForm.data.overtime_date}
                                        onChange={(e) => itemForm.setData('overtime_date', e.target.value)}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Jenis Hari *</Label>
                                    <SearchableSelect
                                        value={itemForm.data.day_type}
                                        onValueChange={(val) => itemForm.setData('day_type', val)}
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
                                        value={itemForm.data.duration_hours}
                                        onChange={(e) => itemForm.setData('duration_hours', parseFloat(e.target.value) || 0.5)}
                                        className="text-xs font-mono"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Catatan / Deskripsi Penugasan Kerja</Label>
                                <Input
                                    value={itemForm.data.task_description}
                                    onChange={(e) => itemForm.setData('task_description', e.target.value)}
                                    placeholder="e.g. Penyelesaian target pesanan / Penugasan divisi lembur"
                                    className="text-xs"
                                />
                            </div>

                            {/* Seleksi Anggota Regu Kerja (Multi-Select Checkboxes) */}
                            <div className="space-y-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div className="flex items-center gap-2">
                                        <Label className="text-xs font-bold text-zinc-900 dark:text-zinc-100 uppercase tracking-wider">
                                            Pilih Karyawan Lembur:
                                        </Label>
                                        <Badge variant="outline" className="text-xs font-mono">
                                            {selectedEmployeeIds.length} Terpilih
                                        </Badge>
                                        {selectedEmployeeIds.length > 0 && (
                                            <span className="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                = Total {formatRp(estimatedTotalBatch)}
                                            </span>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-2">
                                        {/* Filter Divisi */}
                                        <SearchableSelect
                                            value={filterDept}
                                            onValueChange={(val) => setFilterDept(val)}
                                            options={departmentOptions}
                                            placeholder="Filter Divisi..."
                                            clearable={false}
                                            className="h-7 text-xs w-[180px]"
                                        />

                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={handleSelectAll}
                                            className="h-7 text-xs px-2"
                                        >
                                            {selectedEmployeeIds.length === filteredEmployees.length ? 'Batal Semua' : 'Pilih Semua'}
                                        </Button>
                                    </div>
                                </div>

                                {/* Grid Pilihan Karyawan */}
                                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 max-h-[180px] overflow-y-auto p-2 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                    {filteredEmployees.map((emp) => {
                                        const isSelected = selectedEmployeeIds.includes(emp.id);
                                        return (
                                            <button
                                                type="button"
                                                key={emp.id}
                                                onClick={() => handleToggleEmployee(emp.id)}
                                                className={`flex items-center justify-between p-2 rounded text-left transition-all text-xs border ${
                                                    isSelected
                                                        ? 'bg-indigo-50 border-indigo-300 text-indigo-900 dark:bg-indigo-950/40 dark:border-indigo-800 dark:text-indigo-200'
                                                        : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300'
                                                }`}
                                            >
                                                <div className="truncate pr-1">
                                                    <div className="font-semibold truncate">{emp.name}</div>
                                                    <div className="text-[10px] text-zinc-400 truncate">{emp.department} • {emp.position}</div>
                                                </div>
                                                <div className={`h-4 w-4 rounded flex items-center justify-center border ${isSelected ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-zinc-300 dark:border-zinc-700'}`}>
                                                    {isSelected && <Check className="h-3 w-3" />}
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="flex justify-end pt-2">
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
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="w-[40px] text-center text-xs font-bold">No</TableHead>
                                    <TableHead className="min-w-[180px] text-xs font-bold">Karyawan</TableHead>
                                    <TableHead className="min-w-[110px] text-xs font-bold">Tanggal</TableHead>
                                    <TableHead className="min-w-[130px] text-xs font-bold">Jenis Hari</TableHead>
                                    <TableHead className="min-w-[90px] text-xs font-bold text-center">Durasi</TableHead>
                                    <TableHead className="min-w-[120px] text-xs font-bold text-right">Tarif / Jam</TableHead>
                                    <TableHead className="min-w-[130px] text-xs font-bold text-right">Total Upah</TableHead>
                                    <TableHead className="min-w-[200px] text-xs font-bold">Tugas / Penugasan</TableHead>
                                    {batch.status === 'DRAFT' && (
                                        <TableHead className="w-[60px] text-xs font-bold text-right">Aksi</TableHead>
                                    )}
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
                                                    {item.employee?.employee_code} • {item.employee?.department}
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

                                            {batch.status === 'DRAFT' && (
                                                <TableCell className="text-right">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => handleDeleteItem(item.id)}
                                                        className="h-7 px-1 text-zinc-400 hover:text-rose-600"
                                                        title="Hapus lembur ini"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={batch.status === 'DRAFT' ? 9 : 8} className="h-32 text-center text-xs text-zinc-400">
                                            Belum ada penugasan lembur yang dicatat pada batch ini. Silakan gunakan Bulk Overtime Dispatcher di atas.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            {/* MODAL: Otorisasi Pembayaran Keuangan (Double Sign-Off Step 2) */}
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
