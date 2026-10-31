import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
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
import { Label } from '@/Components/ui/label';
import {
    Landmark,
    Search,
    PlusCircle,
    Calendar,
    Users,
    Banknote,
    FileText,
    Trash2,
    Eye,
    CheckCircle2,
    Clock,
    ShieldCheck,
    RotateCcw,
    AlertCircle,
    ArrowRight,
    FileSpreadsheet,
    Copy,
    Check,
} from 'lucide-react';

const rp = (v) => {
    if (v === null || v === undefined || v === '') return 'Rp 0';
    return 'Rp ' + Number(v).toLocaleString('id-ID');
};

export default function PayrollIndex({
    payrolls,
    filters = {},
    metrics = {},
    suggestions = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [copied, setCopied] = useState(false);

    const handleCopyPayrollTable = () => {
        if (rows.length === 0) return;
        const headers = ['Kode Periode', 'Bulan Kinerja', 'Bulan Pencairan', 'Headcount', 'Gaji Pokok', 'Uang Makan', 'Upah Lembur', 'Potongan', 'Net Kas', 'Status'];
        const body = rows.map((p) => [
            p.period_code || '',
            p.work_period_month || '',
            p.payout_period_month || '',
            `${p.total_employees} org`,
            rp(p.total_base_salary),
            rp(p.total_meal_allowance),
            rp(p.total_overtime),
            rp(p.total_deductions),
            rp(p.total_net_payout),
            p.status || '',
        ].join('\t')).join('\n');
        navigator.clipboard.writeText(`${headers.join('\t')}\n${body}`);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    // Modals
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedPayroll, setSelectedPayroll] = useState(null);

    // Form Generate Batch
    const createForm = useForm({
        work_period_month: suggestions.work_period_month || '',
        payout_period_month: suggestions.payout_period_month || '',
        payout_date: suggestions.payout_date || '',
        notes: '',
    });

    const handleFilterSubmit = (e) => {
        if (e) e.preventDefault();
        router.get(
            route('hcm.payroll.index'),
            { search, status },
            { preserveState: true, replace: true }
        );
    };

    const applyPayrollFilter = (s = search, st = status, page = 1, perPage = payrolls?.per_page || 15) => {
        router.get(
            route('hcm.payroll.index'),
            {
                search: s || undefined,
                status: st,
                page: page > 1 ? page : undefined,
                per_page: perPage,
            },
            { preserveState: true, replace: true }
        );
    };

    const handleReset = () => {
        setSearch('');
        setStatus('all');
        router.get(
            route('hcm.payroll.index'),
            { search: '', status: 'all' },
            { preserveState: true, replace: true }
        );
    };

    const handleOpenCreate = () => {
        createForm.reset();
        createForm.setData({
            work_period_month: suggestions.work_period_month || '',
            payout_period_month: suggestions.payout_period_month || '',
            payout_date: suggestions.payout_date || '',
            notes: '',
        });
        setCreateModalOpen(true);
    };

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('hcm.payroll.store'), {
            onSuccess: () => {
                setCreateModalOpen(false);
                createForm.reset();
            },
        });
    };

    const handleOpenDelete = (p) => {
        setSelectedPayroll(p);
        setDeleteModalOpen(true);
    };

    const submitDelete = () => {
        if (!selectedPayroll) return;
        const key = selectedPayroll.uuid || selectedPayroll.period_code || selectedPayroll.id;
        router.delete(route('hcm.payroll.destroy', key), {
            onSuccess: () => {
                setDeleteModalOpen(false);
            },
        });
    };

    const getStatusBadge = (st) => {
        switch (st) {
            case 'PAID_COMPLETED':
                return (
                    <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 gap-1">
                        <CheckCircle2 className="h-3 w-3 shrink-0" />
                        <span>Selesai (Paid)</span>
                    </Badge>
                );
            case 'APPROVED_BY_HCM':
                return (
                    <Badge className="bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 gap-1">
                        <ShieldCheck className="h-3 w-3 shrink-0" />
                        <span>Disetujui HCM</span>
                    </Badge>
                );
            case 'DRAFT_HCM':
            default:
                return (
                    <Badge className="bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-300 gap-1">
                        <Clock className="h-3 w-3 shrink-0" />
                        <span>Draf HCM</span>
                    </Badge>
                );
        }
    };

    const payrollColumns = useMemo(() => [
        {
            id: 'period_code',
            size: 160,
            minSize: 150,
            accessorFn: (row) => row.period_code || '',
            meta: { title: 'Kode Periode' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Kode Periode" />,
            cell: ({ row }) => {
                const p = row.original;
                const code = p.period_code || p.uuid;
                return (
                    <Link href={route('hcm.payroll.show', code)} className="hover:underline inline-flex items-center gap-1 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                        <span>{p.period_code}</span>
                        <ArrowRight className="h-3 w-3 opacity-60" />
                    </Link>
                );
            },
        },
        {
            accessorKey: 'work_period_month',
            size: 150,
            minSize: 140,
            meta: { title: 'Bulan Kinerja Kerja' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Bulan Kinerja Kerja" />,
            cell: ({ row }) => <span className="font-medium">{row.original.work_period_month}</span>,
        },
        {
            id: 'payout',
            size: 160,
            minSize: 150,
            accessorFn: (row) => row.payout_period_month || '',
            meta: { title: 'Pencairan / Transfer' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Pencairan / Transfer" />,
            cell: ({ row }) => (
                <div>
                    <div>{row.original.payout_period_month}</div>
                    <div className="text-[10px] text-zinc-400">{row.original.payout_date || '-'}</div>
                </div>
            ),
        },
        {
            accessorKey: 'total_employees',
            size: 110,
            minSize: 100,
            meta: { title: 'Headcount', cellClassName: 'text-center' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Headcount" className="justify-center" />,
            cell: ({ row }) => <span className="font-semibold">{row.original.total_employees} org</span>,
        },
        {
            id: 'total_base_salary',
            size: 150,
            minSize: 140,
            accessorFn: (row) => row.total_base_salary ?? '',
            meta: { title: 'Gaji Pokok', cellClassName: 'text-right font-mono' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Gaji Pokok" className="justify-end" />,
            cell: ({ row }) => <span className="font-mono text-zinc-600 dark:text-zinc-400">{rp(row.original.total_base_salary)}</span>,
        },
        {
            id: 'total_meal_allowance',
            size: 140,
            minSize: 130,
            accessorFn: (row) => row.total_meal_allowance ?? '',
            meta: { title: 'Uang Makan', cellClassName: 'text-right font-mono' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Uang Makan" className="justify-end" />,
            cell: ({ row }) => <span className="font-mono text-zinc-600 dark:text-zinc-400">{rp(row.original.total_meal_allowance)}</span>,
        },
        {
            id: 'total_overtime_pay',
            size: 140,
            minSize: 130,
            accessorFn: (row) => row.total_overtime_pay ?? '',
            meta: { title: 'Upah Lembur', cellClassName: 'text-right font-mono' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Upah Lembur" className="justify-end" />,
            cell: ({ row }) => <span className="font-mono text-zinc-600 dark:text-zinc-400">{rp(row.original.total_overtime_pay)}</span>,
        },
        {
            id: 'total_deductions',
            size: 140,
            minSize: 130,
            accessorFn: (row) => row.total_deductions ?? '',
            meta: { title: 'Total Potongan', cellClassName: 'text-right font-mono' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Total Potongan" className="justify-end" />,
            cell: ({ row }) => <span className="font-mono text-rose-600">-{rp(row.original.total_deductions)}</span>,
        },
        {
            id: 'total_net_payout',
            size: 160,
            minSize: 150,
            accessorFn: (row) => row.total_net_payout ?? '',
            meta: { title: 'Total Net Kas', cellClassName: 'text-right font-mono font-bold' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Total Net Kas" className="justify-end" />,
            cell: ({ row }) => (
                <span className="font-mono font-bold text-emerald-600 dark:text-emerald-400">{rp(row.original.total_net_payout)}</span>
            ),
        },
        {
            accessorKey: 'status',
            size: 150,
            minSize: 140,
            meta: { title: 'Status Otorisasi', cellClassName: 'text-center' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status Otorisasi" className="justify-center" />,
            cell: ({ row }) => getStatusBadge(row.original.status),
        },
        {
            id: 'actions',
            size: 180,
            minSize: 160,
            pinned: 'right',
            meta: { title: 'Aksi' },
            header: () => <div className="text-right pr-2">Aksi</div>,
            cell: ({ row }) => {
                const p = row.original;
                const code = p.period_code || p.uuid;
                return (
                    <div className="flex items-center justify-end gap-1.5">
                        <a
                            href={route('hcm.payroll.export', code)}
                            className="h-7 text-[11px] px-2 gap-1 inline-flex items-center rounded border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition-colors"
                            title="Ekspor Excel (BRI & Rekap)"
                        >
                            <FileSpreadsheet className="h-3 w-3" />
                        </a>
                        <Link href={route('hcm.payroll.show', code)}>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="h-7 text-[11px] px-2 gap-1 text-indigo-600 hover:bg-indigo-50 border-indigo-200"
                            >
                                <Eye className="h-3 w-3" />
                                <span>Buka Rekap</span>
                            </Button>
                        </Link>
                        {p.status !== 'PAID_COMPLETED' && (
                            <button
                                type="button"
                                onClick={() => handleOpenDelete(p)}
                                className="p-1 rounded text-zinc-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                title="Hapus Batch Draf"
                            >
                                <Trash2 className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>
                );
            },
        },
    ], []);

    const rows = payrolls?.data || [];

    return (
        <AppLayout>
            <Head title="Penggajian Terpadu (Unified Payroll) - HCM" />

            <div className="space-y-5">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-2.5">
                        <span className="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                            <Landmark className="h-5 w-5" />
                        </span>
                        <div>
                            <h1 className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                Penggajian Terpadu (Unified Payroll Engine)
                            </h1>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400">
                                Rekapitulasi batch gaji bulanan: Gaji Pokok, Uang Makan, Upah Lembur, Penyesuaian, dan Pemotongan Gaji dengan otorisasi Double Sign-Off.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <Button
                            type="button"
                            onClick={handleOpenCreate}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold gap-1.5 h-9 text-xs shadow-xs"
                        >
                            <PlusCircle className="h-4 w-4" />
                            <span>+ Generate Batch Penggajian</span>
                        </Button>
                    </div>
                </div>

                {/* 4 Cards Metrik Finansial */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-600">
                                <Banknote className="h-3.5 w-3.5 text-emerald-500" />
                                <span>Total Belanja Gaji Lunas</span>
                            </div>
                            <div className="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                                {rp(metrics.total_payroll_expenditure)}
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Akumulasi seluruh batch terbayar</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-amber-500">
                                <Clock className="h-3.5 w-3.5 text-amber-500" />
                                <span>Menunggu Sign HCM</span>
                            </div>
                            <div className="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                                {metrics.waiting_hcm_approval ?? 0} Periode
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Status Draf dalam verifikasi</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-blue-500">
                                <ShieldCheck className="h-3.5 w-3.5 text-blue-500" />
                                <span>Menunggu Kas Keuangan</span>
                            </div>
                            <div className="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                                {metrics.waiting_finance_payment ?? 0} Periode
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Disetujui HCM &amp; siap transfer</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-500">
                                <FileText className="h-3.5 w-3.5 text-indigo-500" />
                                <span>Total Batch Periode</span>
                            </div>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                {metrics.total_batches_count ?? 0} Batch
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">{metrics.paid_completed ?? 0} batch telah dicairkan</div>
                        </CardContent>
                    </Card>
                </div>


                {/* Tabel Batch Payroll - DataTable (sticky filter, sticky footer, kolom aksi pin kanan) */}
                <DataTable
                    columns={payrollColumns}
                    data={rows}
                    serverPagination={payrolls}
                    onServerPageChange={(page) => applyPayrollFilter(search, status, page)}
                    onServerPerPageChange={(per_page) => applyPayrollFilter(search, status, 1, per_page)}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        applyPayrollFilter(val, status, 1);
                    }}
                    searchValue={search}
                    searchPlaceholder="Cari kode periode (PAY-...), bulan, atau catatan..."
                    exportFileName={`Payroll_Batch_${new Date().toISOString().slice(0, 10)}`}
                    emptyMessage="Belum ada batch penggajian terdata. Klik tombol Generate Batch Penggajian untuk membuat periode baru."
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-2">
                            <SearchableSelect
                                value={status}
                                onValueChange={(val) => {
                                    setStatus(val);
                                    applyPayrollFilter(search, val, 1);
                                }}
                                options={[
                                    { label: 'Semua Status Batch', value: 'all' },
                                    { label: 'Draf HCM (Belum Disetujui)', value: 'DRAFT_HCM' },
                                    { label: 'Disetujui HCM (Menunggu Finance)', value: 'APPROVED_BY_HCM' },
                                    { label: 'Selesai Dicairkan (PAID_COMPLETED)', value: 'PAID_COMPLETED' },
                                ]}
                                placeholder="Status Batch"
                                className="h-8 w-56 text-xs"
                            />
                            {(search || status !== 'all') && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleReset}
                                    className="h-8 px-2.5 text-xs text-zinc-500 hover:text-zinc-800 gap-1 shrink-0"
                                    title="Reset Filter"
                                >
                                    <RotateCcw className="h-3.5 w-3.5" />
                                    <span>Reset</span>
                                </Button>
                            )}
                        </div>
                    }
                />
            </div>

            {/* Modal Dialog Generate Batch */}
            <Dialog open={createModalOpen} onOpenChange={setCreateModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold flex items-center gap-2 text-indigo-600">
                            <Landmark className="h-4 w-4" />
                            <span>Generate Batch Penggajian Terpadu</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Sistem akan mengagregasi data seluruh karyawan aktif: Gaji Pokok, Uang Makan (dari cut-off absensi), Upah Lembur, dan Potongan Gaji bulanan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCreate} className="space-y-3.5">
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label className="text-xs font-semibold">Bulan Kinerja Kerja *</Label>
                                <Input
                                    type="month"
                                    value={createForm.data.work_period_month}
                                    onChange={(e) => createForm.setData('work_period_month', e.target.value)}
                                    className="mt-1 h-9 text-xs"
                                    required
                                />
                                <span className="text-[10px] text-zinc-400">Bulan presensi &amp; lembur</span>
                            </div>

                            <div>
                                <Label className="text-xs font-semibold">Bulan Pencairan *</Label>
                                <Input
                                    type="month"
                                    value={createForm.data.payout_period_month}
                                    onChange={(e) => createForm.setData('payout_period_month', e.target.value)}
                                    className="mt-1 h-9 text-xs"
                                    required
                                />
                                <span className="text-[10px] text-zinc-400">Bulan transfer gaji</span>
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Tanggal Rencana Transfer Gaji</Label>
                            <Input
                                type="date"
                                value={createForm.data.payout_date}
                                onChange={(e) => createForm.setData('payout_date', e.target.value)}
                                className="mt-1 h-9 text-xs"
                            />
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Catatan Periode (Opsional)</Label>
                            <Input
                                value={createForm.data.notes}
                                onChange={(e) => createForm.setData('notes', e.target.value)}
                                placeholder="Contoh: Penggajian reguler periode Oktober 2026"
                                className="mt-1 h-9 text-xs"
                            />
                        </div>

                        <div className="p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/40 text-[11px] text-blue-700 dark:text-blue-300 space-y-1">
                            <div className="font-semibold flex items-center gap-1">
                                <AlertCircle className="h-3.5 w-3.5" />
                                <span>Mekanisme Agregasi Otomatis</span>
                            </div>
                            <p>
                                Kode batch akan otomatis dibuat: <code>PAY-{createForm.data.work_period_month || 'YYYY-MM'}</code>. Status awal akan menjadi <strong>DRAFT_HCM</strong> dan memerlukan persetujuan HCM sebelum diteruskan ke Keuangan.
                            </p>
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
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold"
                            >
                                {createForm.processing ? 'Mengagregasi...' : 'Generate Batch'}
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
                            <Trash2 className="h-4 w-4" />
                            <span>Hapus Batch Penggajian</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Apakah Anda yakin ingin menghapus batch penggajian <strong>{selectedPayroll?.period_code}</strong>? Seluruh data rincian gaji karyawan dalam batch ini akan dihapus.
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
                            Hapus Batch
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
