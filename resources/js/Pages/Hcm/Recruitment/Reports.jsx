import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
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
} from 'lucide-react';

export default function RecruitmentReports({ rows = [], summary = {}, channels = [], selectedJob = null, selectedApplicants = [], filters = {} }) {
    const openDetail = (job) => {
        router.get(route('hcm.recruitment.reports.index'), { job_id: job.slug || job.job_code || job.id }, { preserveState: true });
    };

    const resetDetail = () => {
        router.get(route('hcm.recruitment.reports.index'), {}, { preserveState: true });
    };

    const printReport = (jobParam = null) => {
        const base = route('hcm.recruitment.reports.pdf');
        const url = jobParam ? `${base}?job_id=${encodeURIComponent(jobParam)}&action=stream` : `${base}?action=stream`;
        window.open(url, '_blank');
    };

    const exportExcel = (jobParam = null) => {
        const base = route('hcm.recruitment.reports.excel');
        window.location.href = jobParam ? `${base}?job_id=${encodeURIComponent(jobParam)}` : base;
    };

    const printApplicant = (app) => {
        window.open(route('hcm.recruitment.applicants.pdf', app.applicant_code || app.id) + '?action=stream', '_blank');
    };

    const stageTag = (status) => {
        if (status === 'Terpenuhi') return 'bg-emerald-500/10 text-emerald-600';
        if (status === 'Ditutup') return 'bg-zinc-500/10 text-zinc-500';
        return 'bg-sky-500/10 text-sky-600';
    };

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

            <div className="p-4 sm:p-6 space-y-5">
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
                            className="text-xs gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"
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
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Target className="h-3.5 w-3.5 text-indigo-500" /> Total Loker
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{summary.total_jobs ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Users className="h-3.5 w-3.5 text-sky-500" /> Total Pelamar
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{summary.total_applicants ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <TrendingUp className="h-3.5 w-3.5 text-emerald-500" /> Total Diterima
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{summary.total_hired ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Clock className="h-3.5 w-3.5 text-amber-500" /> Rata-rata Pemenuhan
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{summary.avg_fulfillment ?? 0}%</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabel per loker */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-zinc-50/75 dark:bg-zinc-800/50">
                                        <TableHead className="text-xs">Kode</TableHead>
                                        <TableHead className="min-w-[200px] text-xs">Posisi / Judul Loker</TableHead>
                                        <TableHead className="text-xs">Divisi</TableHead>
                                        <TableHead className="text-center text-xs">Kuota</TableHead>
                                        <TableHead className="text-center text-xs">Pelamar</TableHead>
                                        <TableHead className="text-center text-xs">Interview</TableHead>
                                        <TableHead className="text-center text-xs">Hired</TableHead>
                                        <TableHead className="text-center text-xs">Pemenuhan</TableHead>
                                        <TableHead className="text-center text-xs">Konversi</TableHead>
                                        <TableHead className="text-center text-xs">TT Hire</TableHead>
                                        <TableHead className="text-center text-xs">Status</TableHead>
                                        <TableHead className="text-right text-xs">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length > 0 ? (
                                        rows.map((r) => (
                                            <TableRow key={r.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                                <TableCell className="font-mono text-xs text-indigo-600 dark:text-indigo-400">{r.job_code || '-'}</TableCell>
                                                <TableCell className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">{r.title}</TableCell>
                                                <TableCell className="text-xs text-zinc-500">{r.department}</TableCell>
                                                <TableCell className="text-center text-xs">{r.quota}</TableCell>
                                                <TableCell className="text-center text-xs">{r.total_applicants}</TableCell>
                                                <TableCell className="text-center text-xs">{r.interviewed}</TableCell>
                                                <TableCell className="text-center text-xs font-bold">{r.hired}</TableCell>
                                                <TableCell className="text-center text-xs">{r.fulfillment_rate}%</TableCell>
                                                <TableCell className="text-center text-xs">{r.conversion_rate}%</TableCell>
                                                <TableCell className="text-center text-xs">{r.avg_time_to_hire !== null ? `${r.avg_time_to_hire} hr` : '-'}</TableCell>
                                                <TableCell className="text-center">
                                                    <Badge className={`text-[10px] ${stageTag(r.status)}`}>{r.status}</Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button variant="outline" size="sm" onClick={() => openDetail(r)} className="h-6 text-[10px] px-2 gap-1">
                                                            <Eye className="h-3 w-3" /> Detail
                                                        </Button>
                                                        <Button variant="outline" size="sm" onClick={() => printReport(r.slug || r.job_code || r.id)} className="h-6 text-[10px] px-2 gap-1">
                                                            <Printer className="h-3 w-3" /> Cetak
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={12} className="text-center text-xs text-zinc-400 py-10">
                                                Belum ada data loker.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                {/* Performa per saluran rekrutmen (ROI saluran) */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                    <CardContent className="p-0">
                        <div className="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            Performa per Saluran Rekrutmen
                        </div>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-zinc-50/75 dark:bg-zinc-800/50">
                                        <TableHead className="text-xs">Saluran / Sumber Kandidat</TableHead>
                                        <TableHead className="text-center text-xs">Jumlah Loker</TableHead>
                                        <TableHead className="text-center text-xs">Total Pelamar</TableHead>
                                        <TableHead className="text-center text-xs">Diterima</TableHead>
                                        <TableHead className="text-center text-xs">Konversi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {channels.length > 0 ? (
                                        channels.map((ch) => (
                                            <TableRow key={ch.channel}>
                                                <TableCell className="text-xs font-medium">{ch.channel}</TableCell>
                                                <TableCell className="text-center text-xs">{ch.jobs}</TableCell>
                                                <TableCell className="text-center text-xs">{ch.applicants}</TableCell>
                                                <TableCell className="text-center text-xs font-bold">{ch.hired}</TableCell>
                                                <TableCell className="text-center text-xs">{ch.conversion_rate}%</TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center text-xs text-zinc-400 py-8">
                                                Belum ada data saluran rekrutmen.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                {/* Detail pelamar per loker */}
                {selectedJob && (
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-0">
                            <div className="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
                                <div className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-indigo-600" />
                                    Detail Pelamar — {selectedJob.title} ({selectedJob.job_code})
                                </div>
                                <Badge className="text-[10px]">{selectedApplicants.length} pelamar</Badge>
                            </div>
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-zinc-50/75 dark:bg-zinc-800/50">
                                            <TableHead className="text-xs">Kode</TableHead>
                                            <TableHead className="text-xs">Nama</TableHead>
                                            <TableHead className="text-xs">Pendidikan</TableHead>
                                            <TableHead className="text-xs">Tgl Melamar</TableHead>
                                            <TableHead className="text-xs">Tahap</TableHead>
                                            <TableHead className="text-xs">Hasil Interview</TableHead>
                                            <TableHead className="text-xs">Offering</TableHead>
                                            <TableHead className="text-center text-xs">Wawancara</TableHead>
                                            <TableHead className="text-right text-xs">Cetak</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {selectedApplicants.length > 0 ? (
                                            selectedApplicants.map((a) => {
                                                const latest = a.interviews && a.interviews[0];
                                                return (
                                                    <TableRow key={a.id}>
                                                        <TableCell className="font-mono text-xs">{a.applicant_code}</TableCell>
                                                        <TableCell className="font-semibold text-xs">{a.name}</TableCell>
                                                        <TableCell className="text-xs text-zinc-500">{a.education || '-'}</TableCell>
                                                        <TableCell className="text-xs text-zinc-500">{(a.apply_date || '').slice(0, 10) || '-'}</TableCell>
                                                        <TableCell className="text-xs">{a.status}</TableCell>
                                                        <TableCell className="text-xs">{a.interview_result || latest?.interview_result || '-'}</TableCell>
                                                        <TableCell className="text-xs">{latest?.offering_status || '-'}</TableCell>
                                                        <TableCell className="text-center text-xs">{(a.interviews || []).length}x</TableCell>
                                                        <TableCell className="text-right">
                                                            <Button variant="outline" size="sm" onClick={() => printApplicant(a)} className="h-6 text-[10px] px-2 gap-1">
                                                                <Printer className="h-3 w-3" /> Profil
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={9} className="text-center text-xs text-zinc-400 py-8">
                                                    Belum ada pelamar untuk loker ini.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
