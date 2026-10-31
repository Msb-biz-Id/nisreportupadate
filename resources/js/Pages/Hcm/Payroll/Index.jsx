import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { SearchableSelect } from '@/Components/ui/searchable-select';
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

    const rows = payrolls?.data || [];

    return (
        <AppLayout>
            <Head title="Penggajian Terpadu (Unified Payroll) - HCM" />

            <div className="space-y-5 p-4 sm:p-6 max-w-7xl mx-auto">
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

                {/* Toolbar Filter */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <CardContent className="p-3.5">
                        <form onSubmit={handleFilterSubmit} className="flex flex-col sm:flex-row items-center gap-2.5">
                            <div className="relative flex-1 w-full">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari kode periode (PAY-...), bulan, atau catatan..."
                                    className="pl-9 h-9 text-xs"
                                />
                            </div>

                            <div className="flex items-center gap-2 w-full sm:w-auto">
                                <SearchableSelect
                                    value={status}
                                    onValueChange={(val) => setStatus(val)}
                                    options={[
                                        { label: 'Semua Status Batch', value: 'all' },
                                        { label: 'Draf HCM (Belum Disetujui)', value: 'DRAFT_HCM' },
                                        { label: 'Disetujui HCM (Menunggu Finance)', value: 'APPROVED_BY_HCM' },
                                        { label: 'Selesai Dicairkan (PAID_COMPLETED)', value: 'PAID_COMPLETED' },
                                    ]}
                                    placeholder="Status Batch"
                                    className="w-full sm:w-56 text-xs"
                                />

                                <Button
                                    type="submit"
                                    size="sm"
                                    className="h-9 px-3 text-xs bg-indigo-600 hover:bg-indigo-700 text-white gap-1 shrink-0"
                                >
                                    <Search className="h-3.5 w-3.5" />
                                    <span>Filter</span>
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleReset}
                                    className="h-9 px-2.5 text-xs text-zinc-500 hover:text-zinc-800 gap-1 shrink-0"
                                    title="Reset Filter"
                                >
                                    <RotateCcw className="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Tabel Batch Payroll */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table className="text-xs">
                                <TableHeader>
                                    <TableRow className="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/75 dark:bg-zinc-800/50">
                                        <TableHead className="py-3 px-3">Kode Periode</TableHead>
                                        <TableHead className="py-3 px-3">Bulan Kinerja Kerja</TableHead>
                                        <TableHead className="py-3 px-3">Pencairan / Transfer</TableHead>
                                        <TableHead className="py-3 px-3 text-center">Headcount</TableHead>
                                        <TableHead className="py-3 px-3 text-right">Gaji Pokok</TableHead>
                                        <TableHead className="py-3 px-3 text-right">Uang Makan</TableHead>
                                        <TableHead className="py-3 px-3 text-right">Upah Lembur</TableHead>
                                        <TableHead className="py-3 px-3 text-right text-rose-600">Total Potongan</TableHead>
                                        <TableHead className="py-3 px-3 text-right font-bold text-emerald-600">Total Net Kas</TableHead>
                                        <TableHead className="py-3 px-3 text-center">Status Otorisasi</TableHead>
                                        <TableHead className="py-3 px-3 text-right sticky right-0 bg-zinc-50/95 dark:bg-zinc-800/95">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody className="divide-y divide-zinc-200/80 dark:divide-zinc-800/80">
                                    {rows.length > 0 ? (
                                        rows.map((p) => {
                                            const code = p.period_code || p.uuid;
                                            return (
                                                <TableRow key={p.id} className="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40">
                                                    <TableCell className="py-3 px-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                                        <Link href={route('hcm.payroll.show', code)} className="hover:underline flex items-center gap-1">
                                                            <span>{p.period_code}</span>
                                                            <ArrowRight className="h-3 w-3 opacity-60" />
                                                        </Link>
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 font-medium">
                                                        {p.work_period_month}
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3">
                                                        <div>{p.payout_period_month}</div>
                                                        <div className="text-[10px] text-zinc-400">{p.payout_date || '-'}</div>
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 text-center font-semibold">
                                                        {p.total_employees} org
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                        {rp(p.total_base_salary)}
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                        {rp(p.total_meal_allowance)}
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                        {rp(p.total_overtime_pay)}
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 text-right font-mono text-rose-600">
                                                        -{rp(p.total_deductions)}
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                                        {rp(p.total_net_payout)}
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 text-center">
                                                        {getStatusBadge(p.status)}
                                                    </TableCell>

                                                    <TableCell className="py-3 px-3 text-right sticky right-0 bg-white/95 dark:bg-zinc-900/95">
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
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={11} className="py-12 text-center text-zinc-400">
                                                Belum ada batch penggajian terdata. Klik tombol "+ Generate Batch Penggajian" untuk membuat periode baru.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
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
