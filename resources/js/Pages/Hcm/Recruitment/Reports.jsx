import React, { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import { DataTable } from '@/Components/DataTable/DataTable';
import { DataTableColumnHeader } from '@/Components/DataTable/DataTableColumnHeader';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    BarChart3,
    Printer,
    Eye,
    FileText,
    Users,
    TrendingUp,
    Target,
    Clock,
    ArrowLeft,
    RotateCcw,
} from 'lucide-react';

export default function RecruitmentReports({
    rows = [],
    summary = {},
    channels = [],
    selectedJob = null,
    selectedApplicants = [],
    filters = {},
}) {
    const [departmentFilter, setDepartmentFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

    const openDetail = (job) => {
        router.get(
            route('hcm.recruitment.reports.index'),
            { job_id: job.slug || job.job_code || job.id },
            { preserveState: true }
        );
    };

    const resetDetail = () => {
        router.get(route('hcm.recruitment.reports.index'), {}, { preserveState: true });
    };

    const printReport = (jobParam = null) => {
        const base = route('hcm.recruitment.reports.pdf');
        const url = jobParam
            ? `${base}?job_id=${encodeURIComponent(jobParam)}&action=stream`
            : `${base}?action=stream`;
        window.open(url, '_blank');
    };

    const exportExcel = (jobParam = null) => {
        const base = route('hcm.recruitment.reports.excel');
        window.location.href = jobParam ? `${base}?job_id=${encodeURIComponent(jobParam)}` : base;
    };

    const printApplicant = (app) => {
        window.open(
            route('hcm.recruitment.applicants.pdf', app.applicant_code || app.id) + '?action=stream',
            '_blank'
        );
    };

    const stageTag = (status) => {
        if (status === 'Terpenuhi') return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300';
        if (status === 'Ditutup') return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
        return 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300';
    };

    // Filter list for dropdowns
    const departmentOptions = useMemo(() => {
        const depts = Array.from(new Set(rows.map((r) => r.department).filter(Boolean)));
        return [
            { value: 'all', label: 'Semua Divisi' },
            ...depts.map((d) => ({ value: d, label: d })),
        ];
    }, [rows]);

    const statusOptions = useMemo(() => {
        const statuses = Array.from(new Set(rows.map((r) => r.status).filter(Boolean)));
        return [
            { value: 'all', label: 'Semua Status' },
            ...statuses.map((s) => ({ value: s, label: s })),
        ];
    }, [rows]);

    const filteredRows = useMemo(() => {
        return rows.filter((r) => {
            if (departmentFilter !== 'all' && r.department !== departmentFilter) return false;
            if (statusFilter !== 'all' && r.status !== statusFilter) return false;
            return true;
        });
    }, [rows, departmentFilter, statusFilter]);

    const columns = useMemo(
        () => [
            {
                accessorKey: 'job_code',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
                size: 110,
                minSize: 90,
                cell: ({ row }) => (
                    <span className="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                        {row.original.job_code || '-'}
                    </span>
                ),
            },
            {
                accessorKey: 'title',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Posisi / Judul Loker" />,
                size: 260,
                minSize: 200,
                cell: ({ row }) => (
                    <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100 max-w-xs truncate">
                        {row.original.title}
                    </div>
                ),
            },
            {
                accessorKey: 'department',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Divisi" />,
                size: 140,
                minSize: 120,
                cell: ({ row }) => (
                    <span className="text-xs text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                        {row.original.department}
                    </span>
                ),
            },
            {
                accessorKey: 'quota',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Kuota" className="text-center" />,
                size: 90,
                minSize: 80,
                cell: ({ row }) => (
                    <div className="text-center text-xs font-medium text-zinc-800 dark:text-zinc-200">
                        {row.original.quota}
                    </div>
                ),
            },
            {
                accessorKey: 'total_applicants',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Pelamar" className="text-center" />,
                size: 100,
                minSize: 85,
                cell: ({ row }) => (
                    <div className="text-center text-xs text-zinc-700 dark:text-zinc-300">
                        {row.original.total_applicants}
                    </div>
                ),
            },
            {
                accessorKey: 'interviewed',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Interview" className="text-center" />,
                size: 105,
                minSize: 90,
                cell: ({ row }) => (
                    <div className="text-center text-xs text-zinc-700 dark:text-zinc-300">
                        {row.original.interviewed}
                    </div>
                ),
            },
            {
                accessorKey: 'hired',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Hired" className="text-center" />,
                size: 90,
                minSize: 80,
                cell: ({ row }) => (
                    <div className="text-center text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        {row.original.hired}
                    </div>
                ),
            },
            {
                accessorKey: 'fulfillment_rate',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Pemenuhan" className="text-center" />,
                size: 110,
                minSize: 95,
                cell: ({ row }) => (
                    <div className="text-center text-xs text-zinc-700 dark:text-zinc-300">
                        {row.original.fulfillment_rate}%
                    </div>
                ),
            },
            {
                accessorKey: 'conversion_rate',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Konversi" className="text-center" />,
                size: 100,
                minSize: 85,
                cell: ({ row }) => (
                    <div className="text-center text-xs text-zinc-700 dark:text-zinc-300">
                        {row.original.conversion_rate}%
                    </div>
                ),
            },
            {
                accessorKey: 'avg_time_to_hire',
                header: ({ column }) => <DataTableColumnHeader column={column} title="TT Hire" className="text-center" />,
                size: 100,
                minSize: 85,
                cell: ({ row }) => (
                    <div className="text-center text-xs text-zinc-600 dark:text-zinc-400">
                        {row.original.avg_time_to_hire !== null ? `${row.original.avg_time_to_hire} hr` : '-'}
                    </div>
                ),
            },
            {
                accessorKey: 'status',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Status" className="text-center" />,
                size: 120,
                minSize: 105,
                cell: ({ row }) => (
                    <div className="text-center">
                        <Badge className={`text-[10px] font-medium border-0 ${stageTag(row.original.status)}`}>
                            {row.original.status}
                        </Badge>
                    </div>
                ),
            },
            {
                id: 'actions',
                size: 160,
                minSize: 150,
                pinned: 'right',
                header: () => <span className="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Aksi</span>,
                cell: ({ row }) => {
                    const r = row.original;
                    return (
                        <div className="flex items-center justify-end gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => openDetail(r)}
                                className="h-6 text-[10px] px-2 gap-1 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800"
                            >
                                <Eye className="h-3 w-3" /> Detail
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => printReport(r.slug || r.job_code || r.id)}
                                className="h-6 text-[10px] px-2 gap-1"
                            >
                                <Printer className="h-3 w-3" /> Cetak
                            </Button>
                        </div>
                    );
                },
            },
        ],
        []
    );

    const filterSlot = (
        <div className="flex flex-wrap items-center gap-2">
            <div className="w-40">
                <SearchableSelect
                    value={departmentFilter}
                    onValueChange={setDepartmentFilter}
                    options={departmentOptions}
                    placeholder="Divisi"
                    className="text-xs h-8"
                />
            </div>
            <div className="w-36">
                <SearchableSelect
                    value={statusFilter}
                    onValueChange={setStatusFilter}
                    options={statusOptions}
                    placeholder="Status"
                    className="text-xs h-8"
                />
            </div>
            {(departmentFilter !== 'all' || statusFilter !== 'all') && (
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                        setDepartmentFilter('all');
                        setStatusFilter('all');
                    }}
                    className="h-8 text-xs px-2 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 gap-1"
                >
                    <RotateCcw className="h-3.5 w-3.5" />
                    Reset
                </Button>
            )}
        </div>
    );

    // Columns for Detail Applicants Table
    const applicantColumns = useMemo(
        () => [
            {
                accessorKey: 'applicant_code',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Kode" />,
                size: 110,
                minSize: 90,
                cell: ({ row }) => (
                    <span className="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                        {row.original.applicant_code}
                    </span>
                ),
            },
            {
                accessorKey: 'name',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Nama Pelamar" />,
                size: 200,
                minSize: 160,
                cell: ({ row }) => (
                    <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                        {row.original.name}
                    </div>
                ),
            },
            {
                accessorKey: 'education',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Pendidikan" />,
                size: 130,
                minSize: 110,
                cell: ({ row }) => (
                    <span className="text-xs text-zinc-500">{row.original.education || '-'}</span>
                ),
            },
            {
                accessorKey: 'apply_date',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Tgl Melamar" />,
                size: 120,
                minSize: 100,
                cell: ({ row }) => (
                    <span className="text-xs text-zinc-500">
                        {(row.original.apply_date || '').slice(0, 10) || '-'}
                    </span>
                ),
            },
            {
                accessorKey: 'status',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Tahap" />,
                size: 120,
                minSize: 100,
                cell: ({ row }) => (
                    <Badge variant="outline" className="text-[10px] font-medium">
                        {row.original.status}
                    </Badge>
                ),
            },
            {
                id: 'interview_result',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Hasil Interview" />,
                size: 140,
                minSize: 120,
                cell: ({ row }) => {
                    const latest = row.original.interviews && row.original.interviews[0];
                    return (
                        <span className="text-xs text-zinc-600 dark:text-zinc-400">
                            {row.original.interview_result || latest?.interview_result || '-'}
                        </span>
                    );
                },
            },
            {
                id: 'offering_status',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Offering" />,
                size: 130,
                minSize: 110,
                cell: ({ row }) => {
                    const latest = row.original.interviews && row.original.interviews[0];
                    return (
                        <span className="text-xs text-zinc-600 dark:text-zinc-400">
                            {latest?.offering_status || '-'}
                        </span>
                    );
                },
            },
            {
                id: 'interviews_count',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Wawancara" className="text-center" />,
                size: 100,
                minSize: 85,
                cell: ({ row }) => (
                    <div className="text-center text-xs text-zinc-600 dark:text-zinc-400">
                        {(row.original.interviews || []).length}x
                    </div>
                ),
            },
            {
                id: 'actions',
                size: 100,
                minSize: 90,
                pinned: 'right',
                header: () => <span className="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Cetak</span>,
                cell: ({ row }) => (
                    <div className="flex items-center justify-end">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => printApplicant(row.original)}
                            className="h-6 text-[10px] px-2 gap-1"
                        >
                            <Printer className="h-3 w-3" /> Profil
                        </Button>
                    </div>
                ),
            },
        ],
        []
    );

    const channelColumns = useMemo(
        () => [
            {
                accessorKey: 'channel',
                meta: { title: 'Saluran / Sumber Kandidat' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Saluran / Sumber Kandidat" />,
                size: 200,
                minSize: 160,
                cell: ({ row }) => (
                    <span className="text-xs font-medium text-zinc-900 dark:text-zinc-100">{row.original.channel}</span>
                ),
            },
            {
                accessorKey: 'jobs',
                meta: { title: 'Jumlah Loker', cellClassName: 'text-center' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Jumlah Loker" className="justify-center" />,
                size: 120,
                minSize: 100,
                cell: ({ row }) => <span className="text-xs text-zinc-700 dark:text-zinc-300">{row.original.jobs}</span>,
            },
            {
                accessorKey: 'applicants',
                meta: { title: 'Total Pelamar', cellClassName: 'text-center' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Total Pelamar" className="justify-center" />,
                size: 120,
                minSize: 100,
                cell: ({ row }) => <span className="text-xs text-zinc-700 dark:text-zinc-300">{row.original.applicants}</span>,
            },
            {
                accessorKey: 'hired',
                meta: { title: 'Diterima', cellClassName: 'text-center' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Diterima" className="justify-center" />,
                size: 110,
                minSize: 90,
                cell: ({ row }) => (
                    <span className="text-xs font-bold text-emerald-600 dark:text-emerald-400">{row.original.hired}</span>
                ),
            },
            {
                id: 'conversion_rate',
                accessorFn: (row) => row.conversion_rate ?? '',
                meta: { title: 'Konversi', cellClassName: 'text-center' },
                header: ({ column }) => <DataTableColumnHeader column={column} title="Konversi" className="justify-center" />,
                size: 110,
                minSize: 90,
                cell: ({ row }) => (
                    <span className="text-xs text-zinc-700 dark:text-zinc-300">{row.original.conversion_rate}%</span>
                ),
            },
        ],
        []
    );

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <BarChart3 className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Laporan Performa Rekrutmen
                    </span>
                </div>
            }
        >
            <Head title="Laporan Rekrutmen" />

            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-bold text-zinc-900 dark:text-zinc-100">Laporan Performa Rekrutmen</h1>
                        <p className="text-xs text-zinc-500 mt-0.5">
                            Rekap pemenuhan kuota, funnel konversi, dan waktu rekrut per loker — siap cetak PDF.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {selectedJob && (
                            <Button variant="outline" size="sm" onClick={resetDetail} className="text-xs gap-1.5">
                                <ArrowLeft className="h-3.5 w-3.5" /> Semua Loker
                            </Button>
                        )}
                        <Button
                            size="sm"
                            onClick={() => printReport(selectedJob?.slug || selectedJob?.job_code || selectedJob?.id)}
                            className="text-xs gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs"
                        >
                            <Printer className="h-3.5 w-3.5" /> Cetak Laporan{selectedJob ? ' Loker Ini' : ' Semua'}
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => exportExcel(selectedJob?.slug || selectedJob?.job_code || selectedJob?.id)}
                            className="text-xs gap-1.5 border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400"
                        >
                            <FileText className="h-3.5 w-3.5" /> Export Excel
                        </Button>
                    </div>
                </div>

                {/* Ringkasan */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Target className="h-3.5 w-3.5 text-indigo-500" /> Total Loker
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{summary.total_jobs ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Users className="h-3.5 w-3.5 text-sky-500" /> Total Pelamar
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{summary.total_applicants ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <TrendingUp className="h-3.5 w-3.5 text-emerald-500" /> Total Diterima
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{summary.total_hired ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Clock className="h-3.5 w-3.5 text-amber-500" /> Rata-rata Pemenuhan
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{summary.avg_fulfillment ?? 0}%</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabel Performa per Loker */}
                <div className="space-y-2">
                    <div className="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        Performa Per Loker / Posisi
                    </div>
                    <DataTable
                        columns={columns}
                        data={filteredRows}
                        searchPlaceholder="Cari kode, posisi loker, atau divisi..."
                        filterSlot={filterSlot}
                        enableColumnVisibility={true}
                        enableExport={true}
                        exportFilename={`laporan_rekrutmen_${new Date().toISOString().slice(0, 10)}`}
                        exportTitle="Laporan Performa Rekrutmen"
                    />
                </div>

                {/* Detail Pelamar per Loker */}
                {selectedJob && (
                    <div className="space-y-2 pt-2">
                        <div className="flex items-center justify-between">
                            <div className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <FileText className="h-4 w-4 text-indigo-600" />
                                Detail Pelamar — {selectedJob.title} ({selectedJob.job_code})
                            </div>
                            <Badge className="text-[10px]">{selectedApplicants.length} pelamar</Badge>
                        </div>
                        <DataTable
                            columns={applicantColumns}
                            data={selectedApplicants}
                            searchPlaceholder="Cari nama atau kode pelamar..."
                            enableColumnVisibility={true}
                            enableExport={true}
                            exportFilename={`pelamar_${selectedJob.job_code || selectedJob.slug}_${new Date().toISOString().slice(0, 10)}`}
                            exportTitle={`Daftar Pelamar - ${selectedJob.title}`}
                        />
                    </div>
                )}

                {/* Performa per Saluran Rekrutmen (ROI Saluran) - DataTable (sticky filter, sticky footer, salin Excel) */}
                <DataTable
                    columns={channelColumns}
                    data={channels}
                    searchPlaceholder="Cari saluran / sumber kandidat..."
                    exportFileName={`ROI_Saluran_Rekrutmen_${new Date().toISOString().slice(0, 10)}`}
                    emptyMessage="Belum ada data saluran rekrutmen."
                />
            </div>
        </AppLayout>
    );
}
