import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Clock,
    Calendar,
    Settings,
    Plus,
    Search,
    CheckCircle2,
    ShieldCheck,
    Coins,
    DollarSign,
    FileText,
    ArrowRight,
    Lock,
    Unlock,
    Landmark,
    SlidersHorizontal,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
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
import { SearchableSelect } from '@/Components/ui/searchable-select';

export default function OvertimeIndex({
    batches,
    filters,
    rates,
    metrics,
}) {
    const [status, setStatus] = useState(filters.status || 'all');
    const [search, setSearch] = useState(filters.search || '');

    // State Modals
    const [isCreateBatchModalOpen, setIsCreateBatchModalOpen] = useState(false);

    // Form Buat Batch Mingguan Baru
    // Helper default cut-off: Sabtu minggu ini s.d. Jumat minggu depan
    const today = new Date();
    const batchForm = useForm({
        period_start: new Date(today.setDate(today.getDate() - today.getDay() - 1)).toISOString().split('T')[0], // Sabtu lalu
        period_end: new Date(today.setDate(today.getDate() + 6)).toISOString().split('T')[0], // Jumat ini
        payout_date: new Date(today.setDate(today.getDate() + 1)).toISOString().split('T')[0], // Sabtu payout
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

    const handleCreateBatchSubmit = (e) => {
        e.preventDefault();
        batchForm.post(route('hcm.overtime.batches.store'), {
            onSuccess: () => {
                setIsCreateBatchModalOpen(false);
            },
        });
    };

    const handleApplyFilter = (newStatus = status, newSearch = search) => {
        router.get(
            route('hcm.overtime.index'),
            {
                status: newStatus,
                search: newSearch,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const statusOptions = [
        { value: 'all', label: 'Semua Status Batch' },
        { value: 'DRAFT', label: 'Draf (Sedang Disusun HCM)' },
        { value: 'APPROVED_BY_HCM', label: 'Menunggu Bayar Keuangan' },
        { value: 'PAID_COMPLETED', label: 'Selesai Dibayarkan' },
    ];

    const STATUS_BADGES = {
        DRAFT: {
            label: 'Draf (Penyusunan)',
            class: 'bg-zinc-500/10 text-zinc-600 border-zinc-300 dark:border-zinc-800',
            icon: Unlock,
        },
        APPROVED_BY_HCM: {
            label: 'Menunggu Sign Keuangan',
            class: 'bg-amber-500/10 text-amber-600 border-amber-300 dark:border-amber-800',
            icon: ShieldCheck,
        },
        PAID_COMPLETED: {
            label: 'Lunas & Ditutup',
            class: 'bg-emerald-500/10 text-emerald-600 border-emerald-300 dark:border-emerald-800',
            icon: Lock,
        },
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Clock className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Rekap Lembur Mingguan & Double Sign-Off
                    </span>
                </div>
            }
        >
            <Head title="Lembur Mingguan - Kepegawaian" />

            <div className="space-y-4">
                {/* Header Banner Canvas */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <Clock className="h-5 w-5 text-indigo-600 shrink-0" />
                            <span>Rekap Lembur Mingguan & Double Sign-Off</span>
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Otomatisasi kalkulasi upah lembur reguler & akhir pekan dengan validasi ganda HCM & Keuangan.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <Link
                            href={route('hcm.settings.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 bg-white border border-zinc-200 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 dark:hover:bg-zinc-750 transition shadow-xs"
                        >
                            <SlidersHorizontal className="h-3.5 w-3.5 text-indigo-600" />
                            <span>Pengaturan Tarif HCM</span>
                        </Link>

                        <Button
                            onClick={() => setIsCreateBatchModalOpen(true)}
                            size="sm"
                            className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5 shadow-xs"
                        >
                            <Plus className="h-3.5 w-3.5" />
                            <span>Buat Batch Mingguan</span>
                        </Button>
                    </div>
                </div>
                {/* 1. Baris Metrik Ringkasan */}
                <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-4">
                        <span className="text-xs text-zinc-500 font-medium uppercase tracking-wider">Draf Aktif HCM</span>
                        <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {metrics.draft_batches || 0} Batch
                        </div>
                    </Card>

                    <Card className="border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/20 shadow-sm p-4">
                        <span className="text-xs text-amber-600 dark:text-amber-400 font-medium uppercase tracking-wider">Menunggu Bayar Keuangan</span>
                        <div className="text-2xl font-bold text-amber-700 dark:text-amber-300 mt-1">
                            {metrics.pending_finance_sign || 0} Batch
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-sm p-4">
                        <span className="text-xs text-emerald-600 dark:text-emerald-400 font-medium uppercase tracking-wider">Selesai Dibayarkan</span>
                        <div className="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">
                            {metrics.paid_completed || 0} Batch
                        </div>
                    </Card>

                    <Card className="border border-indigo-200/80 dark:border-indigo-900/40 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-sm p-4">
                        <span className="text-xs text-indigo-600 dark:text-indigo-400 font-medium uppercase tracking-wider">Total Dana Lembur Lunas</span>
                        <div className="text-2xl font-bold text-indigo-700 dark:text-indigo-300 mt-1">
                            {formatRp(metrics.total_paid_amount)}
                        </div>
                    </Card>
                </div>

                {/* 2. Banner Indikator Tarif Dinamis yang Berlaku Saat Ini */}
                <Card className="border border-indigo-100 dark:border-indigo-950 bg-gradient-to-r from-indigo-50/60 to-purple-50/40 dark:from-indigo-950/20 dark:to-purple-950/10 p-3">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                        <div className="flex items-center gap-2">
                            <Coins className="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                            <span className="font-semibold text-zinc-900 dark:text-zinc-100">Tarif Dinamis Aktif:</span>
                            <span className="text-zinc-600 dark:text-zinc-400">
                                Hari Kerja: <strong>{formatRp(rates.weekday_hourly_rate)}/jam</strong> (30m awal: {formatRp(rates.weekday_first_half_rate)})
                            </span>
                            <span className="text-zinc-300 dark:text-zinc-700">•</span>
                            <span className="text-zinc-600 dark:text-zinc-400">
                                Weekend/Libur: <strong>{formatRp(rates.weekend_hourly_rate)}/jam</strong> (30m awal: {formatRp(rates.weekend_first_half_rate)})
                            </span>
                        </div>
                        <div className="flex items-center gap-2 text-zinc-500">
                            <div className="flex items-center gap-1.5">
                                <Landmark className="h-3.5 w-3.5" />
                                <span>COA Akuntansi: <strong className="font-mono text-zinc-700 dark:text-zinc-300">{rates.coa_code}</strong></span>
                            </div>
                            <Link href={route('hcm.settings.index')} className="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium ml-1">
                                Kelola Tarif &rarr;
                            </Link>
                        </div>
                    </div>
                </Card>

                {/* 3. Filter Bar */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-3">
                    <div className="flex flex-col md:flex-row gap-3 items-center justify-between">
                        <div className="flex items-center gap-2 min-w-[200px] w-full md:w-auto">
                            <Label className="text-xs font-medium text-zinc-500 whitespace-nowrap">Status:</Label>
                            <SearchableSelect
                                value={status}
                                onValueChange={(val) => {
                                    setStatus(val);
                                    handleApplyFilter(val, search);
                                }}
                                options={statusOptions}
                                placeholder="Pilih Status..."
                                clearable={false}
                                className="h-8 text-xs w-full md:w-[220px]"
                            />
                        </div>

                        <div className="relative w-full md:w-[260px]">
                            <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-zinc-400" />
                            <Input
                                placeholder="Cari kode batch..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleApplyFilter(status, search);
                                    }
                                }}
                                className="h-8 pl-8 text-xs"
                            />
                        </div>
                    </div>
                </Card>

                {/* 4. Tabel Batch Lembur Mingguan */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="min-w-[140px] text-xs font-bold">Kode Batch</TableHead>
                                    <TableHead className="min-w-[180px] text-xs font-bold">Periode Cut-Off (Sabtu - Jumat)</TableHead>
                                    <TableHead className="min-w-[120px] text-xs font-bold">Jadwal Pencairan</TableHead>
                                    <TableHead className="min-w-[100px] text-xs font-bold text-center">Total Jam</TableHead>
                                    <TableHead className="min-w-[140px] text-xs font-bold text-right">Total Nominal</TableHead>
                                    <TableHead className="min-w-[160px] text-xs font-bold text-center">Status Sign-Off</TableHead>
                                    <TableHead className="min-w-[120px] text-xs font-bold text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {batches.data && batches.data.length > 0 ? (
                                    batches.data.map((b) => {
                                        const badgeConfig = STATUS_BADGES[b.status] || STATUS_BADGES.DRAFT;
                                        const BadgeIcon = badgeConfig.icon;

                                        return (
                                            <TableRow key={b.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                                <TableCell>
                                                    <span className="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400">
                                                        {b.batch_code}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <div className="text-xs font-mono font-medium text-zinc-700 dark:text-zinc-300">
                                                        {b.period_start} s.d. {b.period_end}
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <span className="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                                                        {b.payout_date}
                                                    </span>
                                                </TableCell>

                                                <TableCell className="text-center">
                                                    <Badge variant="outline" className="text-xs font-mono">
                                                        {parseFloat(b.total_hours).toFixed(1)} Jam
                                                    </Badge>
                                                </TableCell>

                                                <TableCell className="text-right">
                                                    <span className="font-bold text-xs text-zinc-900 dark:text-zinc-100 font-mono">
                                                        {formatRp(b.total_amount)}
                                                    </span>
                                                </TableCell>

                                                <TableCell className="text-center">
                                                    <Badge className={`text-xs gap-1 py-0.5 ${badgeConfig.class}`}>
                                                        <BadgeIcon className="h-3 w-3" />
                                                        {badgeConfig.label}
                                                    </Badge>
                                                    {b.hcm_signer && (
                                                        <div className="text-[10px] text-zinc-400 mt-0.5">
                                                            Sign HCM: {b.hcm_signer.name}
                                                        </div>
                                                    )}
                                                    {b.finance_signer && (
                                                        <div className="text-[10px] text-emerald-600 dark:text-emerald-400">
                                                            Paid: {b.finance_signer.name}
                                                        </div>
                                                    )}
                                                </TableCell>

                                                <TableCell className="text-right">
                                                    <Link
                                                        href={route('hcm.overtime.show', b.id)}
                                                        className="inline-flex items-center gap-1 text-xs text-indigo-600 font-medium hover:underline"
                                                    >
                                                        Buka Lembar Kerja
                                                        <ArrowRight className="h-3.5 w-3.5" />
                                                    </Link>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-32 text-center text-xs text-zinc-400">
                                            Belum ada batch lembur mingguan yang dibuat.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            {/* MODAL 1: Buat Batch Mingguan Baru */}
            <Dialog open={isCreateBatchModalOpen} onOpenChange={setIsCreateBatchModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <Clock className="h-4 w-4 text-indigo-600" />
                            Buat Batch Lembur Mingguan Baru
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Siklus cut-off mingguan berjalan mulai Sabtu (00:00:00) s.d. Jumat (23:59:59) dengan jadwal pencairan hari Sabtu berikutnya.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCreateBatchSubmit} className="space-y-3 pt-2">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Cut-Off Mulai (Sabtu) *</Label>
                                <Input
                                    type="date"
                                    required
                                    value={batchForm.data.period_start}
                                    onChange={(e) => batchForm.setData('period_start', e.target.value)}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Cut-Off Berakhir (Jumat) *</Label>
                                <Input
                                    type="date"
                                    required
                                    value={batchForm.data.period_end}
                                    onChange={(e) => batchForm.setData('period_end', e.target.value)}
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Tanggal Pencairan / Pembayaran (Sabtu) *</Label>
                            <Input
                                type="date"
                                required
                                value={batchForm.data.payout_date}
                                onChange={(e) => batchForm.setData('payout_date', e.target.value)}
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateBatchModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={batchForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {batchForm.processing ? 'Memproses...' : 'Buat Batch'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
