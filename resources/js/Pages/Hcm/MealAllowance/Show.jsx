import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import {
    ArrowLeft,
    UtensilsCrossed,
    Calendar,
    Users,
    ShieldCheck,
    CheckCircle2,
    DollarSign,
    Lock,
    Unlock,
    Landmark,
    Coins,
    Sparkles,
    AlertCircle,
    Check,
    AlertTriangle,
    Search,
    Filter,
    HelpCircle,
    Printer,
    FileSpreadsheet,
    FileText,
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

export default function MealAllowanceShow({ batch, settings }) {
    const [searchTerm, setSearchTerm] = useState('');
    const [departmentFilter, setDepartmentFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');
    const [isFinanceModalOpen, setIsFinanceModalOpen] = useState(false);
    const [isRulesModalOpen, setIsRulesModalOpen] = useState(false);

    // Form Sign-off Keuangan
    const financeForm = useForm({
        payment_method: 'Kas Tunai',
        coa_code: batch.coa_code || settings.coa_code || '5-50110',
        finance_notes: '',
    });

    const formatRp = (val) => {
        if (!val) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val);
    };

    const monthNames = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    // Daftar departemen unik dari items
    const departments = useMemo(() => {
        const set = new Set();
        (batch.items || []).forEach(item => {
            if (item.employee?.department) {
                set.add(item.employee.department);
            }
        });
        return Array.from(set).sort();
    }, [batch.items]);

    // Filter items
    const filteredItems = useMemo(() => {
        return (batch.items || []).filter(item => {
            const matchesSearch = !searchTerm ||
                item.employee?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                item.employee?.employee_code?.toLowerCase().includes(searchTerm.toLowerCase());

            const matchesDept = departmentFilter === 'all' || item.employee?.department === departmentFilter;

            const matchesStatus = statusFilter === 'all' ||
                (statusFilter === 'hold' && item.is_hold) ||
                (statusFilter === 'active' && !item.is_hold) ||
                (statusFilter === 'ineligible_bonus' && !item.bonus_eligible);

            return matchesSearch && matchesDept && matchesStatus;
        });
    }, [batch.items, searchTerm, departmentFilter, statusFilter]);

    // Handle Sign HCM
    const handleSignHcm = () => {
        if (confirm('Verifikasi rekap kehadiran uang makan ini dan teruskan ke Bagian Keuangan?')) {
            router.post(route('hcm.meal-allowance.sign-hcm', batch.batch_code || batch.id));
        }
    };

    // Handle Sign Finance
    const handleFinanceSubmit = (e) => {
        e.preventDefault();
        financeForm.post(route('hcm.meal-allowance.sign-finance', batch.batch_code || batch.id), {
            onSuccess: () => setIsFinanceModalOpen(false),
        });
    };

    return (
        <AppLayout title={`Detail Uang Makan - ${batch.batch_code}`}>
            <Head title={`Detail Uang Makan - ${batch.batch_code}`} />

            <div className="space-y-6">
                {/* 1. Header & Quick Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('hcm.meal-allowance.index')}
                            className="p-2 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                        >
                            <ArrowLeft className="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                    {batch.batch_code}
                                </h1>
                                <Badge
                                    className={
                                        batch.status === 'PAID_COMPLETED'
                                            ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                            : batch.status === 'PENDING_FINANCE_SIGN'
                                            ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30'
                                            : batch.status === 'APPROVED_BY_HCM'
                                            ? 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border border-sky-500/30'
                                            : 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30'
                                    }
                                >
                                    {batch.status === 'PAID_COMPLETED' ? (
                                        <span className="flex items-center gap-1 font-semibold">
                                            <CheckCircle2 className="w-3.5 h-3.5" /> Lunas &amp; Dicairkan
                                        </span>
                                    ) : batch.status === 'PENDING_FINANCE_SIGN' ? (
                                        <span className="flex items-center gap-1 font-semibold">
                                            <Clock className="w-3.5 h-3.5" /> Verifikasi Kas Keuangan
                                        </span>
                                    ) : batch.status === 'APPROVED_BY_HCM' ? (
                                        <span className="flex items-center gap-1 font-semibold">
                                            <Clock className="w-3.5 h-3.5" /> Menunggu Pencairan Keuangan
                                        </span>
                                    ) : (
                                        <span className="flex items-center gap-1 font-semibold">
                                            <Lock className="w-3.5 h-3.5" /> Draft - Review HCM
                                        </span>
                                    )}
                                </Badge>
                            </div>
                            <p className="text-xs sm:text-sm text-zinc-500 mt-1 flex items-center gap-2">
                                <span>Periode: <strong>{monthNames[batch.period_month]} {batch.period_year}</strong></span>
                                <span>•</span>
                                <span>Rencana Bayar: <strong>{batch.payout_date}</strong></span>
                                <span>•</span>
                                <span>COA: <strong className="font-mono">{batch.coa_code || settings.coa_code}</strong></span>
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setIsRulesModalOpen(true)}
                            className="gap-1.5 text-xs text-zinc-600 dark:text-zinc-400"
                        >
                            <HelpCircle className="w-3.5 h-3.5" />
                            Aturan Kedisiplinan
                        </Button>
                    </div>
                </div>

                {/* 2. KPI Metrics Grid */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <Users className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Total Karyawan</div>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {batch.total_employees} Orang
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                                <DollarSign className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Total Cair Bulan Ini</div>
                                <div className="text-xl font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    {formatRp(batch.total_amount)}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                                <AlertTriangle className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Dana Ditahan (Hold)</div>
                                <div className="text-xl font-bold text-amber-600 dark:text-amber-400 font-mono">
                                    {formatRp(batch.total_held_amount)}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600">
                                <Landmark className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Akun Biaya (COA)</div>
                                <div className="text-sm font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {batch.coa_code || settings.coa_code}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* 3. Double Sign-Off Workflow Status Banner */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-900/50 shadow-sm">
                    <CardContent className="p-5">
                        <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                            {/* Step 1: HCM */}
                            <div className="flex items-center gap-4 flex-1">
                                <div
                                    className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0 ${
                                        batch.hcm_signed_at
                                            ? 'bg-emerald-500 text-white'
                                            : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400'
                                    }`}
                                >
                                    {batch.hcm_signed_at ? <Check className="w-5 h-5" /> : '1'}
                                </div>
                                <div>
                                    <div className="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                                        Tahap 1 • Otorisasi HCM
                                    </div>
                                    <div className="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                        {batch.hcm_signed_at ? (
                                            <span className="text-emerald-600 dark:text-emerald-400">
                                                Diverifikasi oleh {batch.hcm_signer?.name || 'HCM'}
                                            </span>
                                        ) : (
                                            'Pemeriksaan Absensi & Potongan'
                                        )}
                                    </div>
                                    <div className="text-[11px] text-zinc-500">
                                        {batch.hcm_signed_at
                                            ? `Ditandatangani pada: ${batch.hcm_signed_at}`
                                            : 'Memastikan akurasi kehadiran, izin, dan status penalti.'}
                                    </div>
                                </div>
                            </div>

                            {/* Divider Arrow */}
                            <div className="hidden md:block text-zinc-300 dark:text-zinc-700">➔</div>

                            {/* Step 2: Keuangan */}
                            <div className="flex items-center gap-4 flex-1">
                                <div
                                    className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0 ${
                                        batch.finance_signed_at
                                            ? 'bg-emerald-500 text-white'
                                            : batch.hcm_signed_at
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400'
                                    }`}
                                >
                                    {batch.finance_signed_at ? <Check className="w-5 h-5" /> : '2'}
                                </div>
                                <div>
                                    <div className="text-xs font-medium text-zinc-400 uppercase tracking-wider">
                                        Tahap 2 • Otorisasi Keuangan
                                    </div>
                                    <div className="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                        {batch.finance_signed_at ? (
                                            <span className="text-emerald-600 dark:text-emerald-400">
                                                Dicairkan oleh {batch.finance_signer?.name || 'Keuangan'} ({batch.payment_method})
                                            </span>
                                        ) : (
                                            'Pencairan Dana & Integrasi COA'
                                        )}
                                    </div>
                                    <div className="text-[11px] text-zinc-500">
                                        {batch.finance_signed_at
                                            ? `Dicatat pada: ${batch.finance_signed_at}`
                                            : 'Penetapan metode pembayaran (Kas/Transfer) dan jurnal kas keluar.'}
                                    </div>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex items-center gap-2 w-full md:w-auto shrink-0 justify-end pt-3 md:pt-0 border-t md:border-t-0 border-zinc-200 dark:border-zinc-800">
                                {batch.status === 'DRAFT' && (
                                    <Button
                                        onClick={handleSignHcm}
                                        className="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white gap-1.5 text-xs font-semibold"
                                    >
                                        <ShieldCheck className="w-4 h-4" />
                                        Verifikasi HCM (Sign 1)
                                    </Button>
                                )}

                                {batch.status === 'APPROVED_BY_HCM' && (
                                    <Button
                                        onClick={() => router.post(route('hcm.meal-allowance.start-finance', batch.batch_code || batch.id), {}, { preserveScroll: true })}
                                        className="w-full md:w-auto bg-sky-600 hover:bg-sky-700 text-white gap-1.5 text-xs font-semibold"
                                    >
                                        <Landmark className="w-4 h-4" />
                                        Mulai Verifikasi Kas (Keuangan)
                                    </Button>
                                )}

                                {batch.status === 'PENDING_FINANCE_SIGN' && (
                                    <Button
                                        onClick={() => setIsFinanceModalOpen(true)}
                                        className="w-full md:w-auto bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5 text-xs font-semibold shadow-sm"
                                    >
                                        <Landmark className="w-4 h-4" />
                                        Sign &amp; Tandai Lunas
                                    </Button>
                                )}

                                {batch.status === 'PAID_COMPLETED' && (
                                    <Badge variant="outline" className="bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 border-emerald-300 py-1.5 px-3">
                                        <CheckCircle2 className="w-4 h-4 mr-1.5" />
                                        Arsip Telah Selesai
                                    </Badge>
                                )}

                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => window.open(route('hcm.meal-allowance.pdf', batch.batch_code || batch.id) + '?action=stream', '_blank')}
                                    className="w-full md:w-auto text-xs gap-1.5"
                                >
                                    <Printer className="w-3.5 h-3.5" /> Cetak PDF
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => { window.location.href = route('hcm.meal-allowance.export', batch.batch_code || batch.id); }}
                                    className="w-full md:w-auto text-xs gap-1.5 border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400"
                                >
                                    <FileSpreadsheet className="w-3.5 h-3.5" /> Excel
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* 4. Filter Toolbar & Search */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div className="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                        <div className="relative w-full sm:w-64">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                            <Input
                                placeholder="Cari nama / NIK..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-9 text-xs h-9"
                            />
                        </div>

                        <div className="w-full sm:w-48">
                            <SearchableSelect
                                value={departmentFilter}
                                onValueChange={setDepartmentFilter}
                                options={[
                                    { label: 'Semua Departemen', value: 'all' },
                                    ...departments.map(d => ({ label: d, value: d }))
                                ]}
                                placeholder="Filter Departemen"
                                className="h-9 text-xs"
                            />
                        </div>

                        <div className="w-full sm:w-44">
                            <SearchableSelect
                                value={statusFilter}
                                onValueChange={setStatusFilter}
                                options={[
                                    { label: 'Semua Status', value: 'all' },
                                    { label: 'Dana Normal / Cair', value: 'active' },
                                    { label: 'Dana Ditahan (Hold)', value: 'hold' },
                                    { label: 'Bonus Hangus (Izin > 2)', value: 'ineligible_bonus' },
                                ]}
                                placeholder="Status Pembayaran"
                                className="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <div className="text-xs text-zinc-500 self-end sm:self-center">
                        Menampilkan <strong>{filteredItems.length}</strong> dari {batch.items?.length || 0} karyawan
                    </div>
                </div>

                {/* 5. Tabel Lembar Rekapitulasi Uang Makan */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="w-[40px] text-center text-xs font-bold">No</TableHead>
                                    <TableHead className="min-w-[190px] text-xs font-bold">Karyawan</TableHead>
                                    <TableHead className="min-w-[70px] text-center text-xs font-bold">Hadir</TableHead>
                                    <TableHead className="min-w-[70px] text-center text-xs font-bold">Telat</TableHead>
                                    <TableHead className="min-w-[70px] text-center text-xs font-bold">½ Hari</TableHead>
                                    <TableHead className="min-w-[70px] text-center text-xs font-bold">Alpha</TableHead>
                                    <TableHead className="min-w-[70px] text-center text-xs font-bold">Izin</TableHead>
                                    <TableHead className="min-w-[100px] text-right text-xs font-bold">Potongan</TableHead>
                                    <TableHead className="min-w-[100px] text-right text-xs font-bold">Tunggakan Lalu</TableHead>
                                    <TableHead className="min-w-[110px] text-center text-xs font-bold">Status Hold</TableHead>
                                    <TableHead className="min-w-[100px] text-center text-xs font-bold">Bonus</TableHead>
                                    <TableHead className="min-w-[130px] text-right text-xs font-bold">Total Diterima</TableHead>
                                    <TableHead className="min-w-[150px] text-xs font-bold">Catatan Kedisiplinan</TableHead>
                                    <TableHead className="w-[80px] text-center text-xs font-bold">Slip</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredItems.length > 0 ? (
                                    filteredItems.map((item, idx) => (
                                        <TableRow
                                            key={item.id}
                                            className={`hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 ${
                                                item.is_hold ? 'bg-amber-50/30 dark:bg-amber-950/10' : ''
                                            }`}
                                        >
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

                                            <TableCell className="text-center text-xs font-mono font-medium">
                                                {item.present_days}
                                            </TableCell>

                                            <TableCell className="text-center text-xs font-mono">
                                                {item.late_days > 0 ? (
                                                    <span
                                                        className={`inline-block px-1.5 py-0.5 rounded font-bold ${
                                                            item.late_days >= 4
                                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400'
                                                                : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'
                                                        }`}
                                                    >
                                                        {item.late_days}x
                                                    </span>
                                                ) : (
                                                    <span className="text-zinc-400">0</span>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-center text-xs font-mono text-zinc-600">
                                                {item.half_days || 0}
                                            </TableCell>

                                            <TableCell className="text-center text-xs font-mono">
                                                {item.alpha_days > 0 ? (
                                                    <span className="text-rose-600 font-bold">{item.alpha_days}</span>
                                                ) : (
                                                    <span className="text-zinc-400">0</span>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-center text-xs font-mono">
                                                {item.permit_days > 0 ? (
                                                    <span
                                                        className={
                                                            item.permit_days > 2
                                                                ? 'text-amber-600 font-bold'
                                                                : 'text-zinc-600'
                                                        }
                                                    >
                                                        {item.permit_days}
                                                    </span>
                                                ) : (
                                                    <span className="text-zinc-400">0</span>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-right text-xs font-mono text-rose-600">
                                                {item.deduction_amount > 0 ? `-${formatRp(item.deduction_amount)}` : '-'}
                                            </TableCell>

                                            <TableCell className="text-right text-xs font-mono text-emerald-600">
                                                {item.previous_hold_amount > 0 ? `+${formatRp(item.previous_hold_amount)}` : '-'}
                                            </TableCell>

                                            <TableCell className="text-center">
                                                {item.is_hold ? (
                                                    <Badge className="bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-[10px] font-semibold">
                                                        DITAHAN
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="text-emerald-600 border-emerald-300 text-[10px]">
                                                        CAIR
                                                    </Badge>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-center">
                                                {item.bonus_eligible ? (
                                                    <span className="text-[11px] text-emerald-600 font-medium">Eligible</span>
                                                ) : (
                                                    <Badge variant="outline" className="border-rose-300 text-rose-600 text-[10px]">
                                                        Hangus
                                                    </Badge>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-right text-xs font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                                {formatRp(item.payable_amount)}
                                            </TableCell>

                                            <TableCell className="text-xs text-zinc-500">
                                                {item.notes ? (
                                                    <span className="text-[11px] italic">{item.notes}</span>
                                                ) : (
                                                    '-'
                                                )}
                                            </TableCell>

                                            <TableCell className="text-center">
                                                <a
                                                    href={route('hcm.meal-allowance.items.pdf', item.id)}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded border border-emerald-200 transition"
                                                    title="Cetak Slip Uang Makan"
                                                >
                                                    <FileText className="w-3.5 h-3.5" />
                                                    <span>Slip</span>
                                                </a>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={14} className="text-center py-8 text-zinc-400 text-xs">
                                            Tidak ada data karyawan yang cocok dengan kriteria filter.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>

                {/* 6. Modal Sign-Off Keuangan */}
                <Dialog open={isFinanceModalOpen} onOpenChange={setIsFinanceModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <Landmark className="w-5 h-5 text-emerald-600" />
                                Otorisasi Pembayaran Keuangan
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Batch <strong className="font-mono">{batch.batch_code}</strong> senilai{' '}
                                <strong className="text-emerald-600">{formatRp(batch.total_amount)}</strong> akan disetujui pencairannya.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleFinanceSubmit} className="space-y-4 pt-2">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Metode Pembayaran *</Label>
                                <SearchableSelect
                                    value={financeForm.data.payment_method}
                                    onValueChange={(val) => financeForm.setData('payment_method', val)}
                                    options={[
                                        { label: 'Kas Tunai (Petty Cash)', value: 'Kas Tunai' },
                                        { label: 'Transfer Bank (Payroll Account)', value: 'Transfer Bank' },
                                    ]}
                                    placeholder="Pilih metode pencairan"
                                />
                                {financeForm.errors.payment_method && (
                                    <p className="text-[11px] text-rose-500">{financeForm.errors.payment_method}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Kode Akun COA Akuntansi</Label>
                                <Input
                                    value={financeForm.data.coa_code}
                                    onChange={(e) => financeForm.setData('coa_code', e.target.value)}
                                    placeholder="5-50110"
                                    className="font-mono text-xs"
                                />
                                <p className="text-[11px] text-zinc-400">
                                    Default: {settings.coa_code} ({settings.coa_name || 'Beban Uang Makan'})
                                </p>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Catatan Keuangan (Opsional)</Label>
                                <Textarea
                                    value={financeForm.data.finance_notes}
                                    onChange={(e) => financeForm.setData('finance_notes', e.target.value)}
                                    placeholder="Contoh: Dicairkan melalui transfer Bank BCA batch 23..."
                                    rows={3}
                                    className="text-xs"
                                />
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsFinanceModalOpen(false)}
                                    disabled={financeForm.processing}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={financeForm.processing}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs"
                                >
                                    {financeForm.processing ? 'Menyimpan...' : 'Konfirmasi Pencairan Dana'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* 7. Modal Penjelasan Aturan Kedisiplinan */}
                <Dialog open={isRulesModalOpen} onOpenChange={setIsRulesModalOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <ShieldCheck className="w-5 h-5 text-blue-600" />
                                Pedoman Aturan Uang Makan & Kedisiplinan
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Ketentuan operasional perhitungan tunjangan makan dan pinalti absensi sesuai SK Direksi & HCM.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-3 text-xs text-zinc-600 dark:text-zinc-300 pt-2">
                            <div className="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 space-y-1">
                                <div className="font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <UtensilsCrossed className="w-3.5 h-3.5 text-blue-600" />
                                    1. Standar Tunjangan Dasar
                                </div>
                                <p className="text-[11px] text-zinc-500">
                                    Tunjangan pokok uang makan adalah <strong>{formatRp(settings.monthly_rate)} / bulan</strong>.
                                    Karyawan berhak menerima tunjangan penuh bila tidak memiliki ketidakhadiran tanpa izin.
                                </p>
                            </div>

                            <div className="p-3 rounded-lg border border-amber-200 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20 space-y-1">
                                <div className="font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                    <AlertTriangle className="w-3.5 h-3.5 text-amber-600" />
                                    2. Aturan Keterlambatan $\ge$ 4 Kali (Tunjangan Ditahan)
                                </div>
                                <p className="text-[11px] text-amber-700 dark:text-amber-400">
                                    Jika seorang karyawan terlambat $\ge$ 4 kali dalam satu bulan berjalan, pencairan uang makan bulan tersebut
                                    <strong> ditahan (HOLD)</strong> dan tidak dibayarkan pada periode tersebut.
                                    Dana yang ditahan akan <strong>dibayarkan dobel pada bulan berikutnya</strong> apabila karyawan tersebut berhasil memulihkan kedisiplinannya (terlambat $\le$ 3 kali).
                                </p>
                            </div>

                            <div className="p-3 rounded-lg border border-rose-200 dark:border-rose-900/40 bg-rose-50/50 dark:bg-rose-950/20 space-y-1">
                                <div className="font-semibold text-rose-800 dark:text-rose-300 flex items-center gap-1.5">
                                    <AlertCircle className="w-3.5 h-3.5 text-rose-600" />
                                    3. Aturan Izin &gt; 2 Kali (Bonus Bulanan Hangus)
                                </div>
                                <p className="text-[11px] text-rose-700 dark:text-rose-400">
                                    Karyawan yang mengajukan izin meninggalkan pekerjaan lebih dari 2 kali dalam sebulan
                                    secara otomatis dinyatakan <strong>tidak berhak atas bonus bulanan (Bonus Ineligible)</strong> pada periode terkait.
                                </p>
                            </div>

                            <div className="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 space-y-1">
                                <div className="font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <Coins className="w-3.5 h-3.5 text-emerald-600" />
                                    4. Potongan Absensi Riil
                                </div>
                                <ul className="text-[11px] text-zinc-500 list-disc list-inside space-y-0.5">
                                    <li>Alpha / Tanpa Keterangan: Potongan <strong>{formatRp(settings.alpha_deduction_rate)} / hari</strong></li>
                                    <li>Setengah Hari (Pulang Cepat): Potongan <strong>{formatRp(settings.half_day_deduction_rate)} / hari</strong></li>
                                </ul>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button variant="outline" size="sm" onClick={() => setIsRulesModalOpen(false)}>
                                Tutup
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
