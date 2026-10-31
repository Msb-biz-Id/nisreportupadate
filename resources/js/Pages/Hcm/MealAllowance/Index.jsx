import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import {
    UtensilsCrossed,
    Calendar,
    Settings,
    Plus,
    Search,
    ShieldCheck,
    CheckCircle2,
    DollarSign,
    Lock,
    Unlock,
    Landmark,
    AlertCircle,
    ArrowRight,
    Sparkles,
    SlidersHorizontal,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { SearchableSelect } from '@/Components/ui/searchable-select';

export default function MealAllowanceIndex({
    batches,
    filters,
    settings,
    metrics,
}) {
    const [status, setStatus] = useState(filters.status || 'all');
    const [search, setSearch] = useState(filters.search || '');

    const [isGenerateModalOpen, setIsGenerateModalOpen] = useState(false);

    // Form Generate Batch Bulanan
    const now = new Date();
    const generateForm = useForm({
        period_month: now.getMonth() + 1,
        period_year: now.getFullYear(),
        payout_date: new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0], // Akhir bulan
    });

    const formatRp = (val) => {
        if (!val) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val);
    };

    const handleGenerateSubmit = (e) => {
        e.preventDefault();
        generateForm.post(route('hcm.meal-allowance.generate'), {
            onSuccess: () => {
                setIsGenerateModalOpen(false);
            },
        });
    };

    const handleApplyFilter = (newStatus = status, newSearch = search, page = 1, per_page = batches.per_page) => {
        router.get(
            route('hcm.meal-allowance.index'),
            {
                status: newStatus,
                search: newSearch,
                page,
                per_page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const statusOptions = [
        { value: 'all', label: 'Semua Status Batch' },
        { value: 'DRAFT', label: 'Draf (Audit HCM)' },
        { value: 'APPROVED_BY_HCM', label: 'Menunggu Bayar Keuangan' },
        { value: 'PAID_COMPLETED', label: 'Selesai Dibayarkan' },
    ];

    const monthOptions = [
        { value: 1, label: 'Januari' },
        { value: 2, label: 'Februari' },
        { value: 3, label: 'Maret' },
        { value: 4, label: 'April' },
        { value: 5, label: 'Mei' },
        { value: 6, label: 'Juni' },
        { value: 7, label: 'Juli' },
        { value: 8, label: 'Agustus' },
        { value: 9, label: 'September' },
        { value: 10, label: 'Oktober' },
        { value: 11, label: 'November' },
        { value: 12, label: 'Desember' },
    ];

    const STATUS_BADGES = {
        DRAFT: {
            label: 'Draf (Audit HCM)',
            class: 'bg-zinc-500/10 text-zinc-600 border-zinc-300 dark:border-zinc-800',
            icon: Unlock,
        },
        APPROVED_BY_HCM: {
            label: 'Menunggu Bayar Keuangan',
            class: 'bg-amber-500/10 text-amber-600 border-amber-300 dark:border-amber-800',
            icon: ShieldCheck,
        },
        PAID_COMPLETED: {
            label: 'Lunas & Ditutup',
            class: 'bg-emerald-500/10 text-emerald-600 border-emerald-300 dark:border-emerald-800',
            icon: Lock,
        },
    };

    const columns = useMemo(
        () => [
            {
                accessorKey: 'batch_code',
                size: 160,
                minSize: 150,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Kode Batch" />,
                cell: ({ row }) => (
                    <span className="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400">
                        {row.original.batch_code}
                    </span>
                ),
                meta: { title: 'Kode Batch' },
            },
            {
                id: 'period',
                size: 160,
                minSize: 150,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Periode Bulan" />,
                cell: ({ row }) => (
                    <span className="text-xs font-medium text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                        Bulan {row.original.period_month} / {row.original.period_year}
                    </span>
                ),
                meta: { title: 'Periode Bulan' },
            },
            {
                accessorKey: 'total_employees',
                size: 130,
                minSize: 120,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Staf Terhitung" className="justify-center" />,
                cell: ({ row }) => (
                    <div className="text-center">
                        <Badge variant="outline" className="text-xs font-mono">
                            {row.original.total_employees} Orang
                        </Badge>
                    </div>
                ),
                meta: { title: 'Staf Terhitung' },
            },
            {
                accessorKey: 'total_amount',
                size: 160,
                minSize: 150,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Dana Dibayarkan" className="justify-end" />,
                cell: ({ row }) => (
                    <div className="text-right font-bold text-xs text-emerald-600 dark:text-emerald-400 font-mono">
                        {formatRp(row.original.total_amount)}
                    </div>
                ),
                meta: { title: 'Dana Dibayarkan' },
            },
            {
                accessorKey: 'total_held_amount',
                size: 160,
                minSize: 150,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Dana Ditahan (HOLD)" className="justify-end" />,
                cell: ({ row }) => (
                    <div className="text-right font-mono text-xs text-rose-500 font-medium">
                        {formatRp(row.original.total_held_amount)}
                    </div>
                ),
                meta: { title: 'Dana Ditahan' },
            },
            {
                accessorKey: 'status',
                size: 160,
                minSize: 150,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Status Sign-Off" className="justify-center" />,
                cell: ({ row }) => {
                    const b = row.original;
                    const badgeConfig = STATUS_BADGES[b.status] || STATUS_BADGES.DRAFT;
                    const BadgeIcon = badgeConfig.icon;
                    return (
                        <div className="text-center">
                            <Badge className={`text-xs gap-1 py-0.5 ${badgeConfig.class}`}>
                                <BadgeIcon className="h-3 w-3" />
                                {badgeConfig.label}
                            </Badge>
                        </div>
                    );
                },
                meta: { title: 'Status Sign-Off' },
            },
            {
                id: 'actions',
                size: 140,
                minSize: 130,
                pinned: 'right',
                header: () => <div className="text-center text-xs font-semibold">Aksi</div>,
                cell: ({ row }) => {
                    const b = row.original;
                    return (
                        <div className="text-center">
                            <Link
                                href={route('hcm.meal-allowance.show', b.batch_code || b.id)}
                                className="inline-flex items-center gap-1 text-xs text-indigo-600 font-medium hover:underline"
                            >
                                Lembar Rekap
                                <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    );
                },
                enableSorting: false,
                meta: { title: 'Aksi' },
            },
        ],
        []
    );

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <UtensilsCrossed className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Uang Makan Bulanan & Double Sign-Off
                    </span>
                </div>
            }
        >
            <Head title="Uang Makan Bulanan - Kepegawaian" />

            <div className="space-y-4">
                {/* Header Banner Canvas */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <UtensilsCrossed className="h-5 w-5 text-indigo-600 shrink-0" />
                            <span>Uang Makan Bulanan & Double Sign-Off</span>
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Kalkulasi uang makan otomatis, aturan penangguhan telat ≥ 4x (HOLD), dan double sign-off keuangan.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <Link
                            href={route('hcm.settings.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 bg-white border border-zinc-200 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 dark:hover:bg-zinc-750 transition shadow-xs"
                        >
                            <SlidersHorizontal className="h-3.5 w-3.5 text-indigo-600" />
                            <span>Pengaturan Plafon HCM</span>
                        </Link>

                        <Button
                            onClick={() => setIsGenerateModalOpen(true)}
                            size="sm"
                            className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5 shadow-xs"
                        >
                            <Sparkles className="h-3.5 w-3.5" />
                            <span>Generate Rekap Bulanan</span>
                        </Button>
                    </div>
                </div>
                {/* 1. Baris Metrik Ringkasan */}
                <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-4">
                        <span className="text-xs text-zinc-500 font-medium uppercase tracking-wider">Total Batch Dibuat</span>
                        <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {metrics.total_batches || 0} Periode
                        </div>
                    </Card>

                    <Card className="border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/20 shadow-sm p-4">
                        <span className="text-xs text-amber-600 dark:text-amber-400 font-medium uppercase tracking-wider">Menunggu Bayar Keuangan</span>
                        <div className="text-2xl font-bold text-amber-700 dark:text-amber-300 mt-1">
                            {metrics.pending_finance || 0} Batch
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-sm p-4">
                        <span className="text-xs text-emerald-600 dark:text-emerald-400 font-medium uppercase tracking-wider">Selesai Dibayarkan</span>
                        <div className="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">
                            {metrics.paid_completed || 0} Batch
                        </div>
                    </Card>

                    <Card className="border border-indigo-200/80 dark:border-indigo-900/40 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-sm p-4">
                        <span className="text-xs text-indigo-600 dark:text-indigo-400 font-medium uppercase tracking-wider">Total Uang Makan Lunas</span>
                        <div className="text-2xl font-bold text-indigo-700 dark:text-indigo-300 mt-1 font-mono">
                            {formatRp(metrics.total_paid_amount)}
                        </div>
                    </Card>
                </div>

                {/* 2. Banner Indikator Aturan Bisnis */}
                <Card className="border border-indigo-100 dark:border-indigo-950 bg-gradient-to-r from-indigo-50/50 to-purple-50/30 dark:from-indigo-950/20 dark:to-purple-950/10 p-3 text-xs">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div className="flex items-center gap-2">
                            <span className="font-semibold text-zinc-900 dark:text-zinc-100">Aturan Blueprint:</span>
                            <span className="text-zinc-600 dark:text-zinc-400">
                                Tarif: <strong>{formatRp(settings.monthly_rate)}/bln</strong> (Rp 70.000/mgg)
                            </span>
                            <span className="text-zinc-300 dark:text-zinc-700">•</span>
                            <span className="text-rose-600 dark:text-rose-400">
                                Sanksi Telat ≥ 4x: <strong>Uang Makan DITAHAN (HOLD)</strong>
                            </span>
                            <span className="text-zinc-300 dark:text-zinc-700">•</span>
                            <span className="text-amber-600 dark:text-amber-400">
                                Izin &gt; 2x: <strong>Batal Hak Bonus Bulanan</strong>
                            </span>
                        </div>
                        <div className="flex items-center gap-2 text-zinc-500">
                            <div className="flex items-center gap-1.5">
                                <Landmark className="h-3.5 w-3.5" />
                                <span>COA: <strong className="font-mono text-zinc-700 dark:text-zinc-300">{settings.coa_code}</strong></span>
                            </div>
                            <Link href={route('hcm.settings.index')} className="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium ml-1">
                                Kelola Aturan &rarr;
                            </Link>
                        </div>
                    </div>
                </Card>

                {/* 3. Tabel Batch Uang Makan Bulanan with DataTable */}
                <DataTable
                    columns={columns}
                    data={batches.data || []}
                    serverPagination={batches}
                    onServerPageChange={(page) => handleApplyFilter(status, search, page)}
                    onServerPerPageChange={(per_page) => handleApplyFilter(status, search, 1, per_page)}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        handleApplyFilter(status, val, 1);
                    }}
                    searchValue={search}
                    searchPlaceholder="Cari kode batch..."
                    exportFileName={`Batch_Uang_Makan_${new Date().toISOString().slice(0, 10)}`}
                    filterSlot={
                        <div className="flex items-center gap-2 min-w-[200px]">
                            <SearchableSelect
                                value={status}
                                onValueChange={(val) => {
                                    setStatus(val);
                                    handleApplyFilter(val, search, 1);
                                }}
                                options={statusOptions}
                                placeholder="Pilih Status..."
                                clearable={false}
                                className="h-8 text-xs w-[220px]"
                            />
                        </div>
                    }
                />
            </div>

            {/* MODAL 1: Generate Batch Uang Makan */}
            <Dialog open={isGenerateModalOpen} onOpenChange={setIsGenerateModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <Sparkles className="h-4 w-4 text-indigo-600" />
                            Generate Rekap Uang Makan Bulanan
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Sistem secara otomatis mengagregasi kehadiran riil seluruh staf dari tanggal 1 s.d. akhir bulan, menghitung potongan, dan mengecek aturan penangguhan telat ≥ 4x (HOLD).
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleGenerateSubmit} className="space-y-3 pt-2">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Bulan Periode *</Label>
                                <SearchableSelect
                                    value={generateForm.data.period_month}
                                    onValueChange={(val) => generateForm.setData('period_month', parseInt(val))}
                                    options={monthOptions}
                                    placeholder="Pilih Bulan..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tahun *</Label>
                                <Input
                                    type="number"
                                    required
                                    min={2024}
                                    max={2035}
                                    value={generateForm.data.period_year}
                                    onChange={(e) => generateForm.setData('period_year', parseInt(e.target.value))}
                                    className="text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Tanggal Pencairan (Akhir Bulan) *</Label>
                            <Input
                                type="date"
                                required
                                value={generateForm.data.payout_date}
                                onChange={(e) => generateForm.setData('payout_date', e.target.value)}
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsGenerateModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={generateForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {generateForm.processing ? 'Mengalkulasi...' : 'Generate Rekap'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
