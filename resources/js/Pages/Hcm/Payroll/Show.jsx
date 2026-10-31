import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
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
    ArrowLeft,
    CheckCircle2,
    Clock,
    ShieldCheck,
    Banknote,
    Users,
    ChevronDown,
    ChevronRight,
    Building2,
    FolderTree,
    FileCheck,
    CreditCard,
    Upload,
    ExternalLink,
    AlertCircle,
    FileSpreadsheet,
    FileText,
    Printer,
} from 'lucide-react';

const rp = (v) => {
    if (v === null || v === undefined || v === '') return 'Rp 0';
    return 'Rp ' + Number(v).toLocaleString('id-ID');
};

export default function PayrollShow({
    payroll,
    groupedData = [],
}) {
    // Accordion state per departemen (default semua terbuka)
    const [openDepts, setOpenDepts] = useState(() => {
        const initial = {};
        groupedData.forEach((dept) => {
            initial[dept.department_name] = true;
        });
        return initial;
    });

    const toggleDept = (deptName) => {
        setOpenDepts((prev) => ({
            ...prev,
            [deptName]: !prev[deptName],
        }));
    };

    // Modal Double Sign-Off HCM
    const [hcmModalOpen, setHcmModalOpen] = useState(false);
    const hcmForm = useForm({});

    // Modal Double Sign-Off Keuangan
    const [financeModalOpen, setFinanceModalOpen] = useState(false);
    const financeForm = useForm({
        payment_method: payroll.payment_method || 'Transfer Bank',
        payment_proof: null,
        finance_notes: '',
    });

    const submitHcmSign = (e) => {
        e.preventDefault();
        const key = payroll.uuid || payroll.period_code || payroll.id;
        hcmForm.post(route('hcm.payroll.sign-hcm', key), {
            onSuccess: () => {
                setHcmModalOpen(false);
            },
        });
    };

    const submitFinanceSign = (e) => {
        e.preventDefault();
        const key = payroll.uuid || payroll.period_code || payroll.id;
        financeForm.post(route('hcm.payroll.sign-finance', key), {
            onSuccess: () => {
                setFinanceModalOpen(false);
            },
        });
    };

    return (
        <AppLayout>
            <Head title={`Rekapitulasi Gaji ${payroll.period_code} - HCM`} />

            <div className="space-y-5 p-4 sm:p-6 max-w-7xl mx-auto">
                {/* Header & Breadcrumb */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <Link
                                href={route('hcm.payroll.index')}
                                className="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                <ArrowLeft className="h-3.5 w-3.5" />
                                <span>Kembali ke Daftar Batch</span>
                            </Link>
                        </div>
                        <div className="flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                                <Landmark className="h-5 w-5" />
                            </span>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                                        {payroll.period_code}
                                    </h1>
                                    <Badge
                                        className={`text-[10px] ${
                                            payroll.status === 'PAID_COMPLETED'
                                                ? 'bg-emerald-500/10 text-emerald-700 border-emerald-200'
                                                : payroll.status === 'APPROVED_BY_HCM'
                                                ? 'bg-blue-500/10 text-blue-700 border-blue-200'
                                                : 'bg-amber-500/15 text-amber-700 border-amber-300'
                                        }`}
                                    >
                                        {payroll.status === 'PAID_COMPLETED'
                                            ? 'PAID_COMPLETED (Lunas)'
                                            : payroll.status === 'APPROVED_BY_HCM'
                                            ? 'APPROVED_BY_HCM (Menunggu Keuangan)'
                                            : 'DRAFT_HCM (Menunggu HCM)'}
                                    </Badge>
                                </div>
                                <p className="text-xs text-zinc-500 dark:text-zinc-400">
                                    Bulan Kinerja: <strong>{payroll.work_period_month}</strong> • Realisasi Pencairan: <strong>{payroll.payout_period_month}</strong> ({payroll.payout_date || 'Belum Dijadwalkan'})
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Tombol Aksi Ekspor & Otorisasi Double Sign-Off */}
                    <div className="flex flex-wrap items-center gap-2 shrink-0">
                        <a
                            href={route('hcm.payroll.export', payroll.period_code)}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-xs font-semibold shadow-xs transition-colors"
                        >
                            <FileSpreadsheet className="h-4 w-4" />
                            <span>Ekspor Excel (BRI &amp; Rekap)</span>
                        </a>

                        <a
                            href={route('hcm.payroll.batch-pdf', payroll.period_code)}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/60 text-zinc-700 dark:text-zinc-200 text-xs font-semibold shadow-xs transition-colors"
                        >
                            <Printer className="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Cetak Rekap PDF</span>
                        </a>

                        {payroll.status === 'DRAFT_HCM' && (
                            <Button
                                type="button"
                                onClick={() => setHcmModalOpen(true)}
                                className="bg-blue-600 hover:bg-blue-700 text-white font-semibold gap-1.5 h-9 text-xs shadow-xs"
                            >
                                <ShieldCheck className="h-4 w-4" />
                                <span>Persetujuan HCM (Double Sign-Off Level 1)</span>
                            </Button>
                        )}

                        {payroll.status === 'APPROVED_BY_HCM' && (
                            <Button
                                type="button"
                                onClick={() => setFinanceModalOpen(true)}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold gap-1.5 h-9 text-xs shadow-xs"
                            >
                                <CreditCard className="h-4 w-4" />
                                <span>Verifikasi &amp; Cairkan (Sign Keuangan Level 2)</span>
                            </Button>
                        )}

                        {payroll.status === 'PAID_COMPLETED' && (
                            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/30 text-xs font-semibold">
                                <CheckCircle2 className="h-4 w-4" />
                                <span>Batch Telah Dicairkan Permanen</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Status Double Sign-Off Tracker Box */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {/* Level 1: HCM */}
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5 flex items-start gap-3">
                            <div className={`p-2 rounded-lg shrink-0 ${payroll.hcm_signed_at ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/40' : 'bg-zinc-100 text-zinc-400'}`}>
                                <ShieldCheck className="h-5 w-5" />
                            </div>
                            <div className="flex-1">
                                <div className="text-[11px] font-semibold text-zinc-400 uppercase tracking-wide">
                                    Otorisasi Level 1: Persetujuan HCM
                                </div>
                                <div className="text-xs font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                                    {payroll.hcm_signed_at ? (
                                        <span className="text-blue-600 font-bold">Disetujui oleh {payroll.hcm_signer?.name || 'Admin HCM'}</span>
                                    ) : (
                                        <span className="text-amber-600">Menunggu Verifikasi &amp; Tanda Tangan HCM</span>
                                    )}
                                </div>
                                <div className="text-[10px] text-zinc-400 mt-0.5">
                                    {payroll.hcm_signed_at ? `Ditandatangani pada: ${new Date(payroll.hcm_signed_at).toLocaleString('id-ID')}` : 'Kunci draf penggajian sebelum diserahkan ke bagian keuangan'}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Level 2: Keuangan */}
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5 flex items-start gap-3">
                            <div className={`p-2 rounded-lg shrink-0 ${payroll.finance_signed_at ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40' : 'bg-zinc-100 text-zinc-400'}`}>
                                <CreditCard className="h-5 w-5" />
                            </div>
                            <div className="flex-1">
                                <div className="text-[11px] font-semibold text-zinc-400 uppercase tracking-wide">
                                    Otorisasi Level 2: Pencairan Kas Keuangan
                                </div>
                                <div className="text-xs font-semibold text-zinc-900 dark:text-zinc-100 mt-0.5">
                                    {payroll.finance_signed_at ? (
                                        <span className="text-emerald-600 font-bold">Dicairkan oleh {payroll.finance_signer?.name || 'Finance'} ({payroll.payment_method})</span>
                                    ) : (
                                        <span className="text-zinc-500">Menunggu Pencairan &amp; Bukti Transfer Keuangan</span>
                                    )}
                                </div>
                                <div className="text-[10px] text-zinc-400 mt-0.5 flex items-center gap-2">
                                    <span>
                                        {payroll.finance_signed_at ? `Dicairkan pada: ${new Date(payroll.finance_signed_at).toLocaleString('id-ID')}` : 'Hanya dapat dicairkan setelah Level 1 disetujui'}
                                    </span>
                                    {payroll.payment_proof_url && (
                                        <a
                                            href={payroll.payment_proof_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-blue-600 hover:underline inline-flex items-center gap-0.5 font-medium"
                                        >
                                            <ExternalLink className="h-3 w-3" />
                                            <span>Bukti Transfer</span>
                                        </a>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* 6 Cards Metrik Ringkasan Finansial */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3">
                            <div className="text-[10px] uppercase font-semibold text-zinc-400">Total Karyawan</div>
                            <div className="text-base font-bold text-zinc-900 dark:text-zinc-100 mt-0.5 font-mono">
                                {payroll.total_employees} Org
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3">
                            <div className="text-[10px] uppercase font-semibold text-zinc-400">Gaji Pokok</div>
                            <div className="text-sm font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 font-mono">
                                {rp(payroll.total_base_salary)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3">
                            <div className="text-[10px] uppercase font-semibold text-zinc-400">Uang Makan</div>
                            <div className="text-sm font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 font-mono">
                                {rp(payroll.total_meal_allowance)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3">
                            <div className="text-[10px] uppercase font-semibold text-zinc-400">Upah Lembur</div>
                            <div className="text-sm font-bold text-zinc-800 dark:text-zinc-200 mt-0.5 font-mono">
                                {rp(payroll.total_overtime_pay)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3">
                            <div className="text-[10px] uppercase font-semibold text-rose-500">Total Potongan</div>
                            <div className="text-sm font-bold text-rose-600 mt-0.5 font-mono">
                                -{rp(payroll.total_deductions)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs bg-emerald-50/20 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900/50">
                        <CardContent className="p-3">
                            <div className="text-[10px] uppercase font-semibold text-emerald-600">Total Net Kas (Gaji)</div>
                            <div className="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-0.5 font-mono">
                                {rp(payroll.total_net_payout)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Multi-Level Grouping (Level 1: Departemen -> Level 2: Divisi -> Level 3: Individual) */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Building2 className="h-4 w-4 text-indigo-600" />
                            <h2 className="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                Rekapitulasi Gaji Berjenjang (Departemen &rarr; Divisi &rarr; Karyawan)
                            </h2>
                        </div>
                        <span className="text-[11px] text-zinc-400">
                            {groupedData.length} Departemen Terdata
                        </span>
                    </div>

                    {groupedData.map((dept) => {
                        const isOpen = !!openDepts[dept.department_name];
                        return (
                            <Card
                                key={dept.department_name}
                                className="border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden"
                            >
                                {/* Level 1 Header: Departemen Accordion */}
                                <div
                                    onClick={() => toggleDept(dept.department_name)}
                                    className="p-3.5 bg-zinc-50/80 dark:bg-zinc-800/60 border-b border-zinc-200/80 dark:border-zinc-700/80 flex items-center justify-between cursor-pointer hover:bg-zinc-100/70 transition"
                                >
                                    <div className="flex items-center gap-2.5">
                                        <button type="button" className="text-zinc-500">
                                            {isOpen ? (
                                                <ChevronDown className="h-4 w-4" />
                                            ) : (
                                                <ChevronRight className="h-4 w-4" />
                                            )}
                                        </button>
                                        <div className="flex items-center gap-2">
                                            <span className="font-bold text-zinc-900 dark:text-zinc-100 text-sm">
                                                {dept.department_name}
                                            </span>
                                            <Badge variant="outline" className="text-[10px] font-mono">
                                                {dept.total_employees} Pegawai
                                            </Badge>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4 text-xs font-mono">
                                        <div className="hidden sm:block text-zinc-500">
                                            Bruto: <span className="font-semibold text-zinc-800 dark:text-zinc-200">{rp(dept.total_earnings)}</span>
                                        </div>
                                        <div className="hidden sm:block text-rose-600">
                                            Potongan: <span>-{rp(dept.total_deductions)}</span>
                                        </div>
                                        <div className="font-bold text-emerald-600 dark:text-emerald-400">
                                            Net Kas: {rp(dept.total_net_salary)}
                                        </div>
                                    </div>
                                </div>

                                {/* Accordion Content: Level 2 Divisi & Level 3 Tabel Karyawan */}
                                {isOpen && (
                                    <CardContent className="p-3 sm:p-4 space-y-5">
                                        {dept.divisions.map((div) => (
                                            <div
                                                key={div.division_name}
                                                className="border border-zinc-200/70 dark:border-zinc-800 rounded-lg overflow-hidden"
                                            >
                                                {/* Level 2 Sub-header: Divisi */}
                                                <div className="p-2.5 bg-zinc-100/50 dark:bg-zinc-800/30 border-b border-zinc-200/70 dark:border-zinc-800 flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <FolderTree className="h-3.5 w-3.5 text-indigo-500" />
                                                        <span className="font-semibold text-xs text-zinc-800 dark:text-zinc-200">
                                                            {div.division_name}
                                                        </span>
                                                        <span className="text-[10px] text-zinc-400">
                                                            ({div.total_employees} orang)
                                                        </span>
                                                    </div>
                                                    <div className="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                                        Subtotal Divisi: {rp(div.total_net_salary)}
                                                    </div>
                                                </div>

                                                {/* Level 3 Table: Individual Karyawan */}
                                                <div className="overflow-x-auto">
                                                    <Table className="text-xs">
                                                        <TableHeader>
                                                            <tr className="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-500 text-[11px]">
                                                                <th className="py-2.5 px-3 text-left">Karyawan</th>
                                                                <th className="py-2.5 px-3 text-left">Rekening Bank BRI</th>
                                                                <th className="py-2.5 px-3 text-right">Gaji Pokok</th>
                                                                <th className="py-2.5 px-3 text-right">Uang Makan</th>
                                                                <th className="py-2.5 px-3 text-right">Upah Lembur</th>
                                                                <th className="py-2.5 px-3 text-right">Total Bruto</th>
                                                                <th className="py-2.5 px-3 text-right text-rose-600">Sanksi</th>
                                                                <th className="py-2.5 px-3 text-right text-rose-600">Cuti</th>
                                                                <th className="py-2.5 px-3 text-right text-rose-600">Maternity</th>
                                                                <th className="py-2.5 px-3 text-right font-bold text-emerald-600">Take-Home Pay</th>
                                                                <th className="py-2.5 px-3 text-center">Status</th>
                                                                <th className="py-2.5 px-3 text-center">Slip Gaji</th>
                                                            </tr>
                                                        </TableHeader>
                                                        <TableBody className="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                                                            {div.items.map((item) => {
                                                                const emp = item.employee;
                                                                return (
                                                                    <TableRow key={item.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                                                        <TableCell className="py-2.5 px-3">
                                                                            <div className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                                                {emp?.name || item.bank_account_name || '-'}
                                                                            </div>
                                                                            <div className="text-[10px] font-mono text-zinc-400">
                                                                                {emp?.employee_code || '-'}
                                                                            </div>
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 font-mono">
                                                                            <div className="text-zinc-800 dark:text-zinc-200">
                                                                                {item.bank_account_no || 'Belum diisi'}
                                                                            </div>
                                                                            <div className="text-[10px] text-zinc-400 truncate max-w-[130px]">
                                                                                {item.bank_account_name || emp?.name || '-'}
                                                                            </div>
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                                            {rp(item.base_salary)}
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                                            {rp(item.meal_allowance)}
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                                            {rp(item.overtime_pay)}
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-right font-mono font-semibold text-zinc-800 dark:text-zinc-200">
                                                                            {rp(item.total_earnings)}
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-right font-mono text-rose-600">
                                                                            {item.penalty_deduction > 0 ? `-${rp(item.penalty_deduction)}` : '-'}
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-right font-mono text-rose-600">
                                                                            {item.leave_deduction > 0 ? `-${rp(item.leave_deduction)}` : '-'}
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-right font-mono text-rose-600">
                                                                            {item.tiered_deduction > 0 ? `-${rp(item.tiered_deduction)}` : '-'}
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                                                            {rp(item.net_salary)}
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-center">
                                                                            <span
                                                                                className={`inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold ${
                                                                                    item.is_paid || payroll.status === 'PAID_COMPLETED'
                                                                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                                                        : 'bg-zinc-100 text-zinc-600 border border-zinc-200'
                                                                                }`}
                                                                            >
                                                                                {item.is_paid || payroll.status === 'PAID_COMPLETED' ? 'Lunas' : 'Menunggu'}
                                                                            </span>
                                                                        </TableCell>

                                                                        <TableCell className="py-2.5 px-3 text-center">
                                                                            <a
                                                                                href={route('hcm.payroll.items.pdf', item.uuid)}
                                                                                target="_blank"
                                                                                rel="noopener noreferrer"
                                                                                className="inline-flex items-center gap-1 px-2 py-1 rounded bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition-colors"
                                                                                title="Cetak Slip Gaji Digital"
                                                                            >
                                                                                <FileText className="h-3 w-3 text-indigo-600 dark:text-indigo-400" />
                                                                                <span>Slip PDF</span>
                                                                            </a>
                                                                        </TableCell>
                                                                    </TableRow>
                                                                );
                                                            })}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </div>
                                        ))}
                                    </CardContent>
                                )}
                            </Card>
                        );
                    })}
                </div>
            </div>

            {/* Modal Dialog Double Sign-Off HCM */}
            <Dialog open={hcmModalOpen} onOpenChange={setHcmModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold flex items-center gap-2 text-blue-600">
                            <ShieldCheck className="h-5 w-5" />
                            <span>Persetujuan Double Sign-Off (HCM)</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Dengan menandatangani batch penggajian <strong>{payroll.period_code}</strong>, Anda memverifikasi bahwa rekonsiliasi kehadiran, lembur, dan potongan gaji telah sah dan siap diteruskan ke Departemen Keuangan untuk realisasi pencairan kas.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitHcmSign} className="space-y-3.5">
                        <div className="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 text-xs space-y-1.5">
                            <div className="flex justify-between">
                                <span className="text-zinc-500">Total Karyawan:</span>
                                <span className="font-bold">{payroll.total_employees} Orang</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-zinc-500">Total Kas Yang Diajukan:</span>
                                <span className="font-mono font-bold text-emerald-600">{rp(payroll.total_net_payout)}</span>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setHcmModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={hcmForm.processing}
                                className="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold"
                            >
                                {hcmForm.processing ? 'Menandatangani...' : 'Setujui & Tanda Tangani (HCM)'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Dialog Double Sign-Off Keuangan */}
            <Dialog open={financeModalOpen} onOpenChange={setFinanceModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold flex items-center gap-2 text-emerald-600">
                            <CreditCard className="h-5 w-5" />
                            <span>Verifikasi &amp; Pencairan Kas (Keuangan)</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Pencairan final batch <strong>{payroll.period_code}</strong>. Tindakan ini akan mengunci seluruh data penggajian menjadi berstatus <strong>PAID_COMPLETED</strong>.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitFinanceSign} className="space-y-3.5">
                        <div className="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40 text-xs space-y-1 font-mono">
                            <div className="flex justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Nominal Pencairan:</span>
                                <span className="font-bold text-emerald-700 dark:text-emerald-400 text-sm">{rp(payroll.total_net_payout)}</span>
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Metode Pembayaran *</Label>
                            <select
                                value={financeForm.data.payment_method}
                                onChange={(e) => financeForm.setData('payment_method', e.target.value)}
                                className="mt-1 w-full h-9 rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-3 text-xs"
                                required
                            >
                                <option value="Transfer Bank">Transfer Bank (Rekening Bank BRI)</option>
                                <option value="Kas Tunai">Kas Tunai</option>
                                <option value="Cek / Giro">Cek / Giro Perusahaan</option>
                            </select>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Upload Bukti Transfer / Resi Pembayaran</Label>
                            <Input
                                type="file"
                                accept="application/pdf,image/*"
                                onChange={(e) => financeForm.setData('payment_proof', e.target.files[0])}
                                className="mt-1 text-xs"
                            />
                            <span className="text-[10px] text-zinc-400">Format PDF, JPG, PNG (Maks 10MB)</span>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Catatan Keuangan (Opsional)</Label>
                            <Input
                                value={financeForm.data.finance_notes}
                                onChange={(e) => financeForm.setData('finance_notes', e.target.value)}
                                placeholder="Contoh: Transfer massal batch payroll sukses diproses via CMS BRI"
                                className="mt-1 h-9 text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setFinanceModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={financeForm.processing}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold"
                            >
                                {financeForm.processing ? 'Memproses...' : 'Cairkan & Tandatangani (Finance)'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
