import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
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
    Eye,
    Pencil,
    Trash2,
    Filter,
    RotateCcw,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
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
import { SearchableSelect } from '@/Components/ui/searchable-select';

export default function OvertimeIndex({
    batches,
    filters,
    availableYears = [],
    rates,
    metrics,
}) {
    const [status, setStatus] = useState(filters.status || 'all');
    const [search, setSearch] = useState(filters.search || '');
    const [month, setMonth] = useState(filters.month || 'all');
    const [year, setYear] = useState(filters.year || 'all');

    // State Modals
    const [isCreateBatchModalOpen, setIsCreateBatchModalOpen] = useState(false);
    const [editBatchModal, setEditBatchModal] = useState({ isOpen: false, batch: null });
    const [deleteBatchModal, setDeleteBatchModal] = useState({ isOpen: false, batch: null });

    // Form Buat Batch Mingguan Baru
    const today = new Date();
    const batchForm = useForm({
        period_start: new Date(today.setDate(today.getDate() - today.getDay() - 1)).toISOString().split('T')[0], // Sabtu lalu
        period_end: new Date(today.setDate(today.getDate() + 6)).toISOString().split('T')[0], // Jumat ini
        payout_date: new Date(today.setDate(today.getDate() + 1)).toISOString().split('T')[0], // Sabtu payout
    });

    // Form Edit Batch
    const editForm = useForm({
        period_start: '',
        period_end: '',
        payout_date: '',
        coa_code: '',
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

    const openEditModal = (b) => {
        setEditBatchModal({ isOpen: true, batch: b });
        editForm.setData({
            period_start: b.period_start,
            period_end: b.period_end,
            payout_date: b.payout_date,
            coa_code: b.coa_code || rates.coa_code || '',
        });
    };

    const handleEditBatchSubmit = (e) => {
        e.preventDefault();
        if (!editBatchModal.batch) return;
        editForm.put(route('hcm.overtime.batches.update', editBatchModal.batch.batch_code || editBatchModal.batch.id), {
            onSuccess: () => {
                setEditBatchModal({ isOpen: false, batch: null });
            },
        });
    };

    const handleDeleteBatchSubmit = () => {
        if (!deleteBatchModal.batch) return;
        router.delete(route('hcm.overtime.batches.destroy', deleteBatchModal.batch.batch_code || deleteBatchModal.batch.id), {
            onSuccess: () => {
                setDeleteBatchModal({ isOpen: false, batch: null });
            },
        });
    };

    const handleApplyFilter = (newStatus = status, newSearch = search, newMonth = month, newYear = year, page = 1, perPage = batches?.per_page || 12) => {
        router.get(
            route('hcm.overtime.index'),
            {
                status: newStatus,
                search: newSearch,
                month: newMonth,
                year: newYear,
                page,
                per_page: perPage,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handleResetFilter = () => {
        setStatus('all');
        setSearch('');
        setMonth('all');
        setYear('all');
        router.get(
            route('hcm.overtime.index'),
            { status: 'all', search: '', month: 'all', year: 'all', page: 1, per_page: batches?.per_page || 12 },
            { preserveState: true, preserveScroll: true }
        );
    };

    const statusOptions = [
        { value: 'all', label: 'Semua Status Batch' },
        { value: 'DRAFT', label: 'Draf (Sedang Disusun HCM)' },
        { value: 'APPROVED_BY_HCM', label: 'Menunggu Bayar Keuangan' },
        { value: 'PAID_COMPLETED', label: 'Selesai Dibayarkan' },
    ];

    const monthOptions = [
        { value: 'all', label: 'Semua Bulan' },
        { value: '1', label: 'Januari' },
        { value: '2', label: 'Februari' },
        { value: '3', label: 'Maret' },
        { value: '4', label: 'April' },
        { value: '5', label: 'Mei' },
        { value: '6', label: 'Juni' },
        { value: '7', label: 'Juli' },
        { value: '8', label: 'Agustus' },
        { value: '9', label: 'September' },
        { value: '10', label: 'Oktober' },
        { value: '11', label: 'November' },
        { value: '12', label: 'Desember' },
    ];

    const yearOptions = [
        { value: 'all', label: 'Semua Tahun' },
        ...(availableYears.length > 0 ? availableYears : [new Date().getFullYear().toString()]).map((y) => ({
            value: y.toString(),
            label: y.toString(),
        })),
    ];

    const isFiltered = status !== 'all' || search !== '' || month !== 'all' || year !== 'all';

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

    const columns = useMemo(() => [
        {
            accessorKey: 'batch_code',
            size: 160,
            minSize: 150,
            header: ({ column }) => <DataTableColumnHeader column={column} title="Kode Batch" />,
            cell: ({ row }) => (
                <Link
                    href={route('hcm.overtime.show', row.original.batch_code || row.original.id)}
                    className="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    {row.original.batch_code}
                </Link>
            ),
        },
        {
            id: 'period',
            size: 200,
            minSize: 190,
            header: ({ column }) => <DataTableColumnHeader column={column} title="Periode Cut-Off" />,
            cell: ({ row }) => (
                <div className="text-xs font-mono font-medium text-zinc-700 dark:text-zinc-300">
                    {row.original.period_start} s.d. {row.original.period_end}
                </div>
            ),
        },
        {
            accessorKey: 'payout_date',
            size: 130,
            minSize: 120,
            header: ({ column }) => <DataTableColumnHeader column={column} title="Jadwal Cair" />,
            cell: ({ row }) => (
                <span className="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                    {row.original.payout_date}
                </span>
            ),
        },
        {
            accessorKey: 'total_hours',
            size: 120,
            minSize: 110,
            header: ({ column }) => <DataTableColumnHeader column={column} title="Total Jam" className="justify-center" />,
            cell: ({ row }) => (
                <div className="text-center">
                    <Badge variant="outline" className="text-xs font-mono">
                        {parseFloat(row.original.total_hours || 0).toFixed(1)} Jam
                    </Badge>
                </div>
            ),
        },
        {
            accessorKey: 'total_amount',
            size: 160,
            minSize: 150,
            header: ({ column }) => <DataTableColumnHeader column={column} title="Total Nominal" className="justify-end" />,
            cell: ({ row }) => (
                <div className="text-right">
                    <span className="font-bold text-xs text-zinc-900 dark:text-zinc-100 font-mono">
                        {formatRp(row.original.total_amount)}
                    </span>
                </div>
            ),
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
                    <div className="flex flex-col items-center">
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
                    </div>
                );
            },
        },
        {
            id: 'actions',
            size: 140,
            minSize: 130,
            pinned: 'right',
            meta: { title: 'Aksi' },
            header: () => <div className="text-right pr-2">Aksi</div>,
            cell: ({ row }) => {
                const b = row.original;
                const isDraft = b.status === 'DRAFT';
                return (
                    <div className="flex items-center justify-end gap-1.5">
                        <Link
                            href={route('hcm.overtime.show', b.batch_code || b.id)}
                            className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800 transition"
                            title="Buka Lembar Kerja"
                        >
                            <Eye className="h-3.5 w-3.5" />
                            <span>Lihat</span>
                        </Link>
                        {isDraft && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => openEditModal(b)}
                                className="h-7 px-2 text-xs text-zinc-700 hover:text-amber-600 hover:border-amber-300 dark:text-zinc-300"
                                title="Edit Batch"
                            >
                                <Pencil className="h-3.5 w-3.5" />
                            </Button>
                        )}
                        {isDraft && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setDeleteBatchModal({ isOpen: true, batch: b })}
                                className="h-7 px-2 text-xs text-zinc-500 hover:text-rose-600 hover:border-rose-300 dark:text-zinc-400"
                                title="Hapus Batch"
                            >
                                <Trash2 className="h-3.5 w-3.5" />
                            </Button>
                        )}
                    </div>
                );
            },
        },
    ], [STATUS_BADGES]);

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
                        <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
                            {metrics.draft_batches || 0} Batch
                        </div>
                    </Card>

                    <Card className="border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/20 shadow-sm p-4">
                        <span className="text-xs text-amber-600 dark:text-amber-400 font-medium uppercase tracking-wider">Menunggu Bayar Keuangan</span>
                        <div className="text-2xl font-bold text-amber-700 dark:text-amber-300 mt-1 font-mono">
                            {metrics.pending_finance_sign || 0} Batch
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-sm p-4">
                        <span className="text-xs text-emerald-600 dark:text-emerald-400 font-medium uppercase tracking-wider">Selesai Dibayarkan</span>
                        <div className="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1 font-mono">
                            {metrics.paid_completed || 0} Batch
                        </div>
                    </Card>

                    <Card className="border border-indigo-200/80 dark:border-indigo-900/40 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-sm p-4">
                        <span className="text-xs text-indigo-600 dark:text-indigo-400 font-medium uppercase tracking-wider">Total Dana Lembur Lunas</span>
                        <div className="text-2xl font-bold text-indigo-700 dark:text-indigo-300 mt-1 font-mono">
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

                {/* 3. Tabel Batch Lembur Mingguan with DataTable */}
                <DataTable
                    columns={columns}
                    data={batches.data || []}
                    serverPagination={batches}
                    onServerPageChange={(page) => handleApplyFilter(status, search, month, year, page)}
                    onServerPerPageChange={(per_page) => handleApplyFilter(status, search, month, year, 1, per_page)}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        handleApplyFilter(status, val, month, year, 1);
                    }}
                    searchValue={search}
                    searchPlaceholder="Cari kode batch..."
                    exportFileName={`Batch_Lembur_${new Date().toISOString().slice(0, 10)}`}
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-2">
                            <SearchableSelect
                                value={status}
                                onValueChange={(val) => {
                                    setStatus(val);
                                    handleApplyFilter(val, search, month, year, 1);
                                }}
                                options={statusOptions}
                                placeholder="Status..."
                                clearable={false}
                                className="h-8 text-xs min-w-[175px] w-auto"
                            />
                            <SearchableSelect
                                value={month}
                                onValueChange={(val) => {
                                    setMonth(val);
                                    handleApplyFilter(status, search, val, year, 1);
                                }}
                                options={monthOptions}
                                placeholder="Bulan..."
                                clearable={false}
                                className="h-8 text-xs min-w-[140px] w-auto"
                            />
                            <SearchableSelect
                                value={year}
                                onValueChange={(val) => {
                                    setYear(val);
                                    handleApplyFilter(status, search, month, val, 1);
                                }}
                                options={yearOptions}
                                placeholder="Tahun..."
                                clearable={false}
                                className="h-8 text-xs min-w-[110px] w-auto"
                            />
                            {isFiltered && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleResetFilter}
                                    className="h-8 text-xs text-zinc-500 hover:text-rose-600 gap-1 px-2"
                                    title="Reset semua filter"
                                >
                                    <RotateCcw className="h-3 w-3" />
                                    <span>Reset</span>
                                </Button>
                            )}
                        </div>
                    }
                />
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

            {/* MODAL 2: Edit Batch Mingguan (Hanya jika DRAFT) */}
            <Dialog open={editBatchModal.isOpen} onOpenChange={(open) => setEditBatchModal({ isOpen: open, batch: open ? editBatchModal.batch : null })}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2 text-amber-600">
                            <Pencil className="h-4 w-4" />
                            Edit Batch Lembur {editBatchModal.batch?.batch_code}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Perbarui periode cut-off dan jadwal pencairan untuk batch lembur berstatus Draf.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleEditBatchSubmit} className="space-y-3 pt-2">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Periode Mulai *</Label>
                                <Input
                                    type="date"
                                    required
                                    value={editForm.data.period_start}
                                    onChange={(e) => editForm.setData('period_start', e.target.value)}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Periode Selesai *</Label>
                                <Input
                                    type="date"
                                    required
                                    value={editForm.data.period_end}
                                    onChange={(e) => editForm.setData('period_end', e.target.value)}
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Tanggal Pencairan *</Label>
                            <Input
                                type="date"
                                required
                                value={editForm.data.payout_date}
                                onChange={(e) => editForm.setData('payout_date', e.target.value)}
                                className="text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Akun COA Akuntansi</Label>
                            <Input
                                value={editForm.data.coa_code}
                                onChange={(e) => editForm.setData('coa_code', e.target.value)}
                                placeholder="5-50100"
                                className="text-xs font-mono"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditBatchModal({ isOpen: false, batch: null })}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                                className="bg-amber-600 hover:bg-amber-700 text-white text-xs"
                            >
                                {editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 3: Konfirmasi Hapus Batch */}
            <Dialog open={deleteBatchModal.isOpen} onOpenChange={(open) => setDeleteBatchModal({ isOpen: open, batch: open ? deleteBatchModal.batch : null })}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2 text-rose-600">
                            <Trash2 className="h-4 w-4" />
                            Konfirmasi Hapus Batch
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Apakah Anda yakin ingin menghapus batch lembur <strong className="text-zinc-900 dark:text-zinc-100">{deleteBatchModal.batch?.batch_code}</strong>? Seluruh data rincian penugasan lembur di dalam batch ini juga akan dihapus permanen.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="gap-2 pt-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeleteBatchModal({ isOpen: false, batch: null })}
                            className="text-xs"
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            onClick={handleDeleteBatchSubmit}
                            className="bg-rose-600 hover:bg-rose-700 text-white text-xs gap-1.5"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                            <span>Ya, Hapus Batch Ini</span>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
