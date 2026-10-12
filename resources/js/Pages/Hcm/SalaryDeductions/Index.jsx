import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import { DataTable } from '@/Components/DataTable/DataTable';
import { DataTableColumnHeader } from '@/Components/DataTable/DataTableColumnHeader';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import {
    Scissors,
    Search,
    PlusCircle,
    Calendar,
    Users,
    Banknote,
    FileText,
    Trash2,
    Edit3,
    ArrowLeft,
    CheckCircle2,
    AlertTriangle,
    Calculator,
    Percent,
    RotateCcw,
} from 'lucide-react';

const rp = (v) => {
    if (v === null || v === undefined || v === '') return 'Rp 0';
    return 'Rp ' + Number(v).toLocaleString('id-ID');
};

export default function SalaryDeductionIndex({
    deductions,
    filters = {},
    metrics = {},
    categories = [],
    employees = [],
}) {
    const [month, setMonth] = useState(filters.month || new Date().toISOString().slice(0, 7));
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || 'all');

    // Modals
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedDeduction, setSelectedDeduction] = useState(null);

    // Form Tambah
    const createForm = useForm({
        employee_id: '',
        effective_payroll_month: month,
        deduction_category: categories[0] || 'Pelanggaran (Disciplinary Penalty)',
        calculation_type: 'percent',
        percentage_rate: '25',
        deduction_amount: '',
        notes: '',
        status: 'APPROVED',
    });

    // Form Edit
    const editForm = useForm({
        effective_payroll_month: '',
        deduction_category: '',
        calculation_type: 'percent',
        percentage_rate: '',
        deduction_amount: '',
        notes: '',
        status: 'APPROVED',
    });

    // Employee options untuk SearchableSelect
    const employeeOptions = useMemo(() => {
        return employees.map((emp) => {
            const currentSalary = emp.compensation?.current_salary ? rp(emp.compensation.current_salary) : 'Gaji blm diset';
            return {
                value: String(emp.id),
                label: `${emp.name} (${emp.employee_code || '-'}) • ${emp.department || '-'} / ${emp.division || '-'} [${currentSalary}]`,
                employee: emp,
            };
        });
    }, [employees]);

    // Karyawan terpilih pada Form Create
    const selectedEmployeeData = useMemo(() => {
        if (!createForm.data.employee_id) return null;
        return employees.find((e) => String(e.id) === String(createForm.data.employee_id));
    }, [createForm.data.employee_id, employees]);

    // Live preview kalkulasi pada modal Tambah
    const calculationPreview = useMemo(() => {
        const base = Number(selectedEmployeeData?.compensation?.current_salary || 0);
        let amount = 0;
        if (createForm.data.calculation_type === 'percent') {
            const rate = Number(createForm.data.percentage_rate || 0);
            amount = Math.round((base * rate) / 100);
        } else {
            amount = Number(createForm.data.deduction_amount || 0);
        }
        const net = Math.max(0, base - amount);
        return {
            base,
            amount,
            net,
        };
    }, [selectedEmployeeData, createForm.data.calculation_type, createForm.data.percentage_rate, createForm.data.deduction_amount]);

    const handleApplyFilter = (newMonth = month, newSearch = search, newCat = category, page = 1, perPage = deductions?.per_page || 20) => {
        router.get(
            route('hcm.salary-deductions.index'),
            { month: newMonth, search: newSearch, category: newCat, page, per_page: perPage },
            { preserveState: true, replace: true }
        );
    };

    const handleReset = () => {
        const defaultMonth = new Date().toISOString().slice(0, 7);
        setMonth(defaultMonth);
        setSearch('');
        setCategory('all');
        router.get(
            route('hcm.salary-deductions.index'),
            { month: defaultMonth, search: '', category: 'all', page: 1, per_page: deductions?.per_page || 20 },
            { preserveState: true, replace: true }
        );
    };

    const handleOpenCreate = () => {
        createForm.reset();
        createForm.setData({
            employee_id: '',
            effective_payroll_month: month,
            deduction_category: categories[0] || 'Pelanggaran (Disciplinary Penalty)',
            calculation_type: 'percent',
            percentage_rate: '25',
            deduction_amount: '',
            notes: '',
            status: 'APPROVED',
        });
        setCreateModalOpen(true);
    };

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('hcm.salary-deductions.store'), {
            onSuccess: () => {
                setCreateModalOpen(false);
                createForm.reset();
            },
        });
    };

    const handleOpenEdit = (item) => {
        setSelectedDeduction(item);
        editForm.setData({
            effective_payroll_month: item.effective_payroll_month || month,
            deduction_category: item.deduction_category,
            calculation_type: item.calculation_type || 'fixed',
            percentage_rate: item.percentage_rate ?? '',
            deduction_amount: item.deduction_amount ?? '',
            notes: item.notes || '',
            status: item.status || 'APPROVED',
        });
        setEditModalOpen(true);
    };

    const submitEdit = (e) => {
        e.preventDefault();
        if (!selectedDeduction) return;
        const key = selectedDeduction.uuid || selectedDeduction.id;
        editForm.put(route('hcm.salary-deductions.update', key), {
            onSuccess: () => {
                setEditModalOpen(false);
                editForm.reset();
            },
        });
    };

    const handleOpenDelete = (item) => {
        setSelectedDeduction(item);
        setDeleteModalOpen(true);
    };

    const submitDelete = () => {
        if (!selectedDeduction) return;
        const key = selectedDeduction.uuid || selectedDeduction.id;
        router.delete(route('hcm.salary-deductions.destroy', key), {
            onSuccess: () => {
                setDeleteModalOpen(false);
            },
        });
    };

    const columns = useMemo(() => [
        {
            id: 'employee',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Karyawan" />,
            cell: ({ row }) => {
                const emp = row.original.employee;
                return (
                    <div>
                        <div className="font-semibold text-zinc-900 dark:text-zinc-100">
                            {emp?.name || '-'}
                        </div>
                        <div className="text-[10px] font-mono text-zinc-400">
                            {emp?.employee_code || '-'}
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'department',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Departemen & Divisi" />,
            cell: ({ row }) => {
                const emp = row.original.employee;
                return (
                    <div>
                        <div className="font-medium text-zinc-800 dark:text-zinc-200">
                            {emp?.department || '-'}
                        </div>
                        <div className="text-[10px] text-zinc-400">
                            {emp?.division || '-'}
                        </div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'deduction_category',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori Pemotongan" />,
            cell: ({ row }) => (
                <Badge
                    variant="outline"
                    className="text-[10px] font-semibold bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300"
                >
                    {row.original.deduction_category}
                </Badge>
            ),
        },
        {
            id: 'calculation_type',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Metode / Rate" className="justify-center" />,
            cell: ({ row }) => {
                const item = row.original;
                return (
                    <div className="text-center">
                        {item.calculation_type === 'percent' ? (
                            <span className="inline-flex items-center gap-0.5 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <Percent className="h-3 w-3" />
                                {item.percentage_rate}%
                            </span>
                        ) : (
                            <span className="text-[11px] font-medium text-zinc-600 dark:text-zinc-400">
                                Nominal Flat
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            accessorKey: 'base_salary_snapshot',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Gaji Pokok Snapshot" className="justify-end" />,
            cell: ({ row }) => (
                <div className="text-right font-mono text-zinc-600 dark:text-zinc-400">
                    {rp(row.original.base_salary_snapshot)}
                </div>
            ),
        },
        {
            accessorKey: 'deduction_amount',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nominal Potongan" className="justify-end" />,
            cell: ({ row }) => (
                <div className="text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                    -{rp(row.original.deduction_amount)}
                </div>
            ),
        },
        {
            accessorKey: 'net_salary_snapshot',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Proyeksi Take-Home" className="justify-end" />,
            cell: ({ row }) => (
                <div className="text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                    {rp(row.original.net_salary_snapshot)}
                </div>
            ),
        },
        {
            accessorKey: 'status',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status" className="justify-center" />,
            cell: ({ row }) => {
                const item = row.original;
                return (
                    <div className="text-center">
                        <Badge
                            className={`text-[10px] ${
                                item.status === 'APPROVED' || item.status === 'APPLIED'
                                    ? 'bg-emerald-500/10 text-emerald-700 border-emerald-200'
                                    : 'bg-zinc-100 text-zinc-700 border-zinc-200'
                            }`}
                        >
                            {item.status}
                        </Badge>
                    </div>
                );
            },
        },
        {
            accessorKey: 'notes',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Catatan / Keterangan" />,
            cell: ({ row }) => (
                <div className="max-w-xs truncate text-zinc-600 dark:text-zinc-400">
                    {row.original.notes || '-'}
                </div>
            ),
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Aksi</div>,
            cell: ({ row }) => {
                const item = row.original;
                return (
                    <div className="flex items-center justify-end gap-1">
                        <button
                            type="button"
                            onClick={() => handleOpenEdit(item)}
                            className="p-1 rounded text-zinc-500 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                            title="Edit Pemotongan"
                        >
                            <Edit3 className="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            onClick={() => handleOpenDelete(item)}
                            className="p-1 rounded text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                            title="Hapus Pemotongan"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                        </button>
                    </div>
                );
            },
        },
    ], []);

    const rows = deductions?.data || [];

    return (
        <AppLayout>
            <Head title="Pemotongan &amp; Penyesuaian Gaji Bulanan - HCM" />

            <div className="space-y-5 p-4 sm:p-6 max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <Link
                                href={route('hcm.compensations.index')}
                                className="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                <ArrowLeft className="h-3.5 w-3.5" />
                                <span>Kembali ke Master Sallary</span>
                            </Link>
                        </div>
                        <div className="flex items-center gap-2.5">
                            <span className="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/50">
                                <Scissors className="h-5 w-5" />
                            </span>
                            <div>
                                <h1 className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                    Penyesuaian &amp; Pemotongan Gaji Bulanan
                                </h1>
                                <p className="text-xs text-zinc-500 dark:text-zinc-400">
                                    Pencatatan sanksi pelanggaran, kelebihan cuti, dan cuti khusus berjenjang (Maternity Leave) terhadap payroll.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <Button
                            type="button"
                            onClick={handleOpenCreate}
                            className="bg-rose-600 hover:bg-rose-700 text-white font-semibold gap-1.5 h-9 text-xs shadow-xs"
                        >
                            <PlusCircle className="h-4 w-4" />
                            <span>+ Catat Pemotongan Gaji</span>
                        </Button>
                    </div>
                </div>

                {/* 4 Cards Metrik Finansial */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Calendar className="h-3.5 w-3.5 text-indigo-500" />
                                <span>Bulan Payroll</span>
                            </div>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
                                {metrics.selected_month || month}
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Periode pemotongan aktif</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-500">
                                <Banknote className="h-3.5 w-3.5 text-rose-500" />
                                <span>Total Akumulasi Potongan</span>
                            </div>
                            <div className="text-xl font-bold text-rose-600 dark:text-rose-400 mt-1 font-mono">
                                {rp(metrics.total_deductions_amount)}
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Total pengurang gaji bulan ini</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-amber-500">
                                <Users className="h-3.5 w-3.5 text-amber-500" />
                                <span>Karyawan Terdampak</span>
                            </div>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                {metrics.total_affected_employees ?? 0} Orang
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Menerima penyesuaian gaji</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-blue-500">
                                <FileText className="h-3.5 w-3.5 text-blue-500" />
                                <span>Total Kasus Potongan</span>
                            </div>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                {metrics.total_deductions_count ?? 0} Transaksi
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Tercatat di sistem payroll</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabel Data Potongan Gaji with DataTable */}
                <DataTable
                    columns={columns}
                    data={deductions.data || []}
                    serverPagination={deductions}
                    onServerPageChange={(page) => handleApplyFilter(month, search, category, page)}
                    onServerPerPageChange={(per_page) => handleApplyFilter(month, search, category, 1, per_page)}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        handleApplyFilter(month, val, category, 1);
                    }}
                    searchValue={search}
                    searchPlaceholder="Cari nama karyawan, NIK, departemen..."
                    exportFileName={`Potongan_Gaji_${month}_${new Date().toISOString().slice(0, 10)}`}
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="flex items-center gap-1.5">
                                <span className="text-xs text-zinc-500 font-medium">Bulan:</span>
                                <Input
                                    type="month"
                                    value={month}
                                    onChange={(e) => {
                                        setMonth(e.target.value);
                                        handleApplyFilter(e.target.value, search, category, 1);
                                    }}
                                    className="h-8 text-xs w-36"
                                />
                            </div>
                            <SearchableSelect
                                value={category}
                                onValueChange={(val) => {
                                    setCategory(val);
                                    handleApplyFilter(month, search, val, 1);
                                }}
                                options={[
                                    { label: 'Semua Kategori Potongan', value: 'all' },
                                    ...categories.map((c) => ({ label: c, value: c })),
                                ]}
                                placeholder="Kategori Potongan"
                                clearable={false}
                                className="h-8 text-xs min-w-[220px] w-auto"
                            />
                            {(category !== 'all' || search !== '') && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleReset}
                                    className="h-8 text-xs text-zinc-500 hover:text-rose-600 gap-1 px-2"
                                    title="Reset filter"
                                >
                                    <RotateCcw className="h-3 w-3" />
                                    <span>Reset</span>
                                </Button>
                            )}
                        </div>
                    }
                />
            </div>

            {/* Modal Tambah Pemotongan Gaji */}
            <Dialog open={createModalOpen} onOpenChange={setCreateModalOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold flex items-center gap-2">
                            <Scissors className="h-4 w-4 text-rose-600" />
                            <span>Catat Penyesuaian / Pemotongan Gaji Bulanan</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Masukkan data pemotongan sesuai kebijakan internal NISGroup (Pelanggaran, Kelebihan Cuti, atau Cuti Khusus Berjenjang).
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCreate} className="space-y-3.5">
                        <div>
                            <Label className="text-xs font-semibold">Pilih Karyawan *</Label>
                            <SearchableSelect
                                value={createForm.data.employee_id}
                                onValueChange={(val) => createForm.setData('employee_id', val)}
                                options={employeeOptions}
                                placeholder="Cari nama karyawan..."
                                className="mt-1 text-xs"
                            />
                            {createForm.errors.employee_id && (
                                <p className="text-[11px] text-rose-500 mt-1">{createForm.errors.employee_id}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label className="text-xs font-semibold">Bulan Penggajian *</Label>
                                <Input
                                    type="month"
                                    value={createForm.data.effective_payroll_month}
                                    onChange={(e) => createForm.setData('effective_payroll_month', e.target.value)}
                                    className="mt-1 h-9 text-xs"
                                    required
                                />
                            </div>

                            <div>
                                <Label className="text-xs font-semibold">Kategori Pemotongan *</Label>
                                <SearchableSelect
                                    value={createForm.data.deduction_category}
                                    onValueChange={(val) => createForm.setData('deduction_category', val)}
                                    options={categories.map((c) => ({ label: c, value: c }))}
                                    placeholder="Kategori"
                                    className="mt-1 text-xs"
                                />
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Metode Perhitungan *</Label>
                            <div className="grid grid-cols-2 gap-2 mt-1">
                                <button
                                    type="button"
                                    onClick={() => createForm.setData('calculation_type', 'percent')}
                                    className={`py-2 px-3 text-xs rounded-lg border font-medium flex items-center justify-center gap-1.5 transition ${
                                        createForm.data.calculation_type === 'percent'
                                            ? 'bg-rose-50 border-rose-300 text-rose-700 font-bold dark:bg-rose-950/40'
                                            : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50'
                                    }`}
                                >
                                    <Percent className="h-3.5 w-3.5" />
                                    <span>Persentase Gaji (%)</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => createForm.setData('calculation_type', 'fixed')}
                                    className={`py-2 px-3 text-xs rounded-lg border font-medium flex items-center justify-center gap-1.5 transition ${
                                        createForm.data.calculation_type === 'fixed'
                                            ? 'bg-rose-50 border-rose-300 text-rose-700 font-bold dark:bg-rose-950/40'
                                            : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50'
                                    }`}
                                >
                                    <Calculator className="h-3.5 w-3.5" />
                                    <span>Nominal Flat (Rp)</span>
                                </button>
                            </div>
                        </div>

                        {createForm.data.calculation_type === 'percent' ? (
                            <div>
                                <div className="flex items-center justify-between">
                                    <Label className="text-xs font-semibold">Persentase Potongan (%) *</Label>
                                    <div className="flex gap-1">
                                        <button
                                            type="button"
                                            onClick={() => createForm.setData('percentage_rate', '25')}
                                            className="px-2 py-0.5 text-[10px] rounded bg-zinc-100 hover:bg-zinc-200 text-zinc-700 font-medium"
                                        >
                                            Preset 25% (Bulan 1)
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => createForm.setData('percentage_rate', '50')}
                                            className="px-2 py-0.5 text-[10px] rounded bg-zinc-100 hover:bg-zinc-200 text-zinc-700 font-medium"
                                        >
                                            Preset 50% (Bulan 2)
                                        </button>
                                    </div>
                                </div>
                                <Input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value={createForm.data.percentage_rate}
                                    onChange={(e) => createForm.setData('percentage_rate', e.target.value)}
                                    placeholder="Contoh: 25 atau 50"
                                    className="mt-1 h-9 text-xs"
                                    required
                                />
                            </div>
                        ) : (
                            <div>
                                <Label className="text-xs font-semibold">Nominal Pemotongan (Rp) *</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={createForm.data.deduction_amount}
                                    onChange={(e) => createForm.setData('deduction_amount', e.target.value)}
                                    placeholder="Contoh: 250000"
                                    className="mt-1 h-9 text-xs"
                                    required
                                />
                            </div>
                        )}

                        {/* Live Projection Box */}
                        <div className="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/80 space-y-1.5 text-xs">
                            <div className="text-[11px] font-semibold text-zinc-500 uppercase tracking-wide">
                                Kalkulasi Proyeksi Payroll
                            </div>
                            <div className="flex justify-between text-zinc-600 dark:text-zinc-300">
                                <span>Gaji Berjalan (Base):</span>
                                <span className="font-mono font-medium">{rp(calculationPreview.base)}</span>
                            </div>
                            <div className="flex justify-between text-rose-600 font-semibold">
                                <span>Estimasi Potongan:</span>
                                <span className="font-mono">-{rp(calculationPreview.amount)}</span>
                            </div>
                            <div className="border-t border-zinc-200 dark:border-zinc-700 pt-1 flex justify-between font-bold text-emerald-600 dark:text-emerald-400">
                                <span>Proyeksi Gaji Diterima (Take Home):</span>
                                <span className="font-mono">{rp(calculationPreview.net)}</span>
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Alasan / Catatan Kebijakan</Label>
                            <Input
                                value={createForm.data.notes}
                                onChange={(e) => createForm.setData('notes', e.target.value)}
                                placeholder="Contoh: Pemotongan cuti melahirkan bulan ke-1 (25%)"
                                className="mt-1 h-9 text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setCreateModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={createForm.processing}
                                className="bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold"
                            >
                                {createForm.processing ? 'Menyimpan...' : 'Simpan Pemotongan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Edit Pemotongan Gaji */}
            <Dialog open={editModalOpen} onOpenChange={setEditModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold flex items-center gap-2">
                            <Edit3 className="h-4 w-4 text-indigo-600" />
                            <span>Edit Pemotongan Gaji</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Karyawan: <strong>{selectedDeduction?.employee?.name}</strong> ({selectedDeduction?.employee?.employee_code})
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitEdit} className="space-y-3">
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label className="text-xs font-semibold">Bulan Penggajian</Label>
                                <Input
                                    type="month"
                                    value={editForm.data.effective_payroll_month}
                                    onChange={(e) => editForm.setData('effective_payroll_month', e.target.value)}
                                    className="mt-1 h-9 text-xs"
                                    required
                                />
                            </div>
                            <div>
                                <Label className="text-xs font-semibold">Status</Label>
                                <SearchableSelect
                                    value={editForm.data.status}
                                    onValueChange={(val) => editForm.setData('status', val)}
                                    options={[
                                        { label: 'APPROVED', value: 'APPROVED' },
                                        { label: 'APPLIED', value: 'APPLIED' },
                                        { label: 'PENDING', value: 'PENDING' },
                                        { label: 'CANCELLED', value: 'CANCELLED' },
                                    ]}
                                    placeholder="Status"
                                    className="mt-1 text-xs"
                                />
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Kategori</Label>
                            <SearchableSelect
                                value={editForm.data.deduction_category}
                                onValueChange={(val) => editForm.setData('deduction_category', val)}
                                options={categories.map((c) => ({ label: c, value: c }))}
                                placeholder="Kategori"
                                className="mt-1 text-xs"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label className="text-xs font-semibold">Metode</Label>
                                <SearchableSelect
                                    value={editForm.data.calculation_type}
                                    onValueChange={(val) => editForm.setData('calculation_type', val)}
                                    options={[
                                        { label: 'Persentase (%)', value: 'percent' },
                                        { label: 'Nominal Flat (Rp)', value: 'fixed' },
                                    ]}
                                    className="mt-1 text-xs"
                                />
                            </div>
                            <div>
                                {editForm.data.calculation_type === 'percent' ? (
                                    <>
                                        <Label className="text-xs font-semibold">Rate (%)</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            value={editForm.data.percentage_rate}
                                            onChange={(e) => editForm.setData('percentage_rate', e.target.value)}
                                            className="mt-1 h-9 text-xs"
                                        />
                                    </>
                                ) : (
                                    <>
                                        <Label className="text-xs font-semibold">Nominal (Rp)</Label>
                                        <Input
                                            type="number"
                                            value={editForm.data.deduction_amount}
                                            onChange={(e) => editForm.setData('deduction_amount', e.target.value)}
                                            className="mt-1 h-9 text-xs"
                                        />
                                    </>
                                )}
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Catatan</Label>
                            <Input
                                value={editForm.data.notes}
                                onChange={(e) => editForm.setData('notes', e.target.value)}
                                className="mt-1 h-9 text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setEditModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={editForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold"
                            >
                                Simpan Perubahan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Konfirmasi Hapus */}
            <Dialog open={deleteModalOpen} onOpenChange={setDeleteModalOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="text-sm font-bold text-rose-600 flex items-center gap-1.5">
                            <AlertTriangle className="h-4 w-4" />
                            <span>Hapus Pemotongan Gaji</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Apakah Anda yakin ingin menghapus catatan potongan senilai{' '}
                            <strong>{rp(selectedDeduction?.deduction_amount)}</strong> untuk karyawan{' '}
                            <strong>{selectedDeduction?.employee?.name}</strong> bulan{' '}
                            <strong>{selectedDeduction?.effective_payroll_month}</strong>?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setDeleteModalOpen(false)}
                            className="text-xs"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            onClick={submitDelete}
                            className="bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold"
                        >
                            Hapus Sekarang
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
