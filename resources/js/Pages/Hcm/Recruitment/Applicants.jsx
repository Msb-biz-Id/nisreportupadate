import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Users,
    Search,
    Filter,
    Calendar,
    Phone,
    Mail,
    FileText,
    ExternalLink,
    CheckCircle2,
    Clock,
    XCircle,
    UserCheck,
    Briefcase,
    Sparkles,
    Eye,
    Rocket,
    Building2,
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

export default function ApplicantsIndex({
    applicants,
    filters,
    metrics,
    jobOptions,
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [jobFilter, setJobFilter] = useState(filters.job_id || 'all');

    const [selectedApplicant, setSelectedApplicant] = useState(null);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
    const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
    const [isConvertModalOpen, setIsConvertModalOpen] = useState(false);

    // Form Status & Jadwal Interview
    const statusForm = useForm({
        status: 'SUBMITTED',
        interview_date: '',
        interview_location: 'Kantor / Pabrik Klaten',
        interviewer_notes: '',
        rejection_reason: '',
    });

    // Form 1-Klik Konversi ke Karyawan
    const convertForm = useForm({
        employment_status: 'Probation',
        job_level: 'Staff',
        department: '',
        position: '',
        join_date: new Date().toISOString().split('T')[0],
    });

    const formatRp = (val) => {
        if (!val) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val);
    };

    const handleFilterChange = (newSearch, newStatus, newJob) => {
        router.get(
            route('hcm.recruitment.applicants.index'),
            {
                search: newSearch,
                status: newStatus,
                job_id: newJob,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const openDetailModal = (app) => {
        setSelectedApplicant(app);
        setIsDetailModalOpen(true);
    };

    const openStatusModal = (app) => {
        setSelectedApplicant(app);
        statusForm.setData({
            status: app.status,
            interview_date: app.interview_date ? app.interview_date.replace(' ', 'T').slice(0, 16) : '',
            interview_location: app.interview_location || 'Kantor / Pabrik Klaten',
            interviewer_notes: app.interviewer_notes || '',
            rejection_reason: app.rejection_reason || '',
        });
        setIsStatusModalOpen(true);
    };

    const openConvertModal = (app) => {
        setSelectedApplicant(app);
        convertForm.setData({
            employment_status: 'Probation',
            job_level: 'Staff',
            department: app.job_posting?.department || 'Produksi',
            position: app.job_posting?.position || 'Operator',
            join_date: new Date().toISOString().split('T')[0],
        });
        setIsConvertModalOpen(true);
    };

    const handleStatusSubmit = (e) => {
        e.preventDefault();
        statusForm.patch(route('hcm.recruitment.applicants.update-status', selectedApplicant.id), {
            onSuccess: () => {
                setIsStatusModalOpen(false);
            },
        });
    };

    const handleConvertSubmit = (e) => {
        e.preventDefault();
        convertForm.post(route('hcm.recruitment.applicants.convert', selectedApplicant.id), {
            onSuccess: () => {
                setIsConvertModalOpen(false);
            },
        });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'SUBMITTED':
                return (
                    <Badge variant="outline" className="border-blue-300 text-blue-600 bg-blue-50/50 text-[10px]">
                        Berkas Masuk
                    </Badge>
                );
            case 'SCREENING':
                return (
                    <Badge className="bg-sky-500/15 text-sky-600 dark:text-sky-400 border border-sky-500/30 text-[10px]">
                        Review Berkas
                    </Badge>
                );
            case 'INTERVIEW':
                return (
                    <Badge className="bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-[10px]">
                        <Clock className="w-3 h-3 mr-1" /> Wawancara
                    </Badge>
                );
            case 'ACCEPTED':
                return (
                    <Badge className="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-[10px] font-semibold">
                        <CheckCircle2 className="w-3 h-3 mr-1" /> Diterima
                    </Badge>
                );
            case 'REJECTED':
                return (
                    <Badge variant="outline" className="border-rose-300 text-rose-600 text-[10px]">
                        <XCircle className="w-3 h-3 mr-1" /> Ditolak
                    </Badge>
                );
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AppLayout title="Pipeline Pelamar Rekrutmen">
            <Head title="Pipeline Pelamar Rekrutmen" />

            <div className="space-y-6">
                {/* 1. Header & Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="p-2 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400">
                                <Users className="w-6 h-6" />
                            </div>
                            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Pipeline Seleksi Pelamar (Recruitment Funnel)
                            </h1>
                        </div>
                        <p className="text-xs sm:text-sm text-zinc-500 mt-1">
                            Pemeriksaan berkas CV, penentuan jadwal wawancara, dan konversi instan 1-klik pelamar menjadi karyawan baru.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href={route('hcm.recruitment.jobs.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition"
                        >
                            <Briefcase className="w-4 h-4" />
                            Master Loker & Link Publik
                        </Link>
                    </div>
                </div>

                {/* 2. KPI Funnel Metrics Grid */}
                <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-3.5 flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <Users className="w-4 h-4" />
                            </div>
                            <div>
                                <div className="text-[11px] text-zinc-500">Total Pelamar</div>
                                <div className="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {metrics.total_applicants}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-3.5 flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600">
                                <FileText className="w-4 h-4" />
                            </div>
                            <div>
                                <div className="text-[11px] text-zinc-500">Berkas Masuk</div>
                                <div className="text-lg font-bold text-sky-600 dark:text-sky-400 font-mono">
                                    {metrics.submitted}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-3.5 flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                                <Clock className="w-4 h-4" />
                            </div>
                            <div>
                                <div className="text-[11px] text-zinc-500">Tahap Wawancara</div>
                                <div className="text-lg font-bold text-amber-600 dark:text-amber-400 font-mono">
                                    {metrics.interview}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-3.5 flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                                <CheckCircle2 className="w-4 h-4" />
                            </div>
                            <div>
                                <div className="text-[11px] text-zinc-500">Diterima (Join)</div>
                                <div className="text-lg font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    {metrics.accepted}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-3.5 flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600">
                                <Rocket className="w-4 h-4" />
                            </div>
                            <div>
                                <div className="text-[11px] text-zinc-500">Telah Dikonversi</div>
                                <div className="text-lg font-bold text-purple-600 dark:text-purple-400 font-mono">
                                    {metrics.converted} Org
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* 3. Filter Toolbar */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div className="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                        <div className="relative w-full sm:w-64">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                            <Input
                                placeholder="Cari nama / kode pelamar / telepon..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleFilterChange(search, statusFilter, jobFilter);
                                    }
                                }}
                                className="pl-9 text-xs h-9"
                            />
                        </div>

                        <div className="w-full sm:w-48">
                            <SearchableSelect
                                value={statusFilter}
                                onValueChange={(val) => {
                                    setStatusFilter(val);
                                    handleFilterChange(search, val, jobFilter);
                                }}
                                options={[
                                    { label: 'Semua Status Tahapan', value: 'all' },
                                    { label: 'Berkas Masuk (Submitted)', value: 'SUBMITTED' },
                                    { label: 'Review Berkas (Screening)', value: 'SCREENING' },
                                    { label: 'Wawancara (Interview)', value: 'INTERVIEW' },
                                    { label: 'Diterima (Accepted)', value: 'ACCEPTED' },
                                    { label: 'Ditolak (Rejected)', value: 'REJECTED' },
                                ]}
                                placeholder="Status Pipeline"
                                className="h-9 text-xs"
                            />
                        </div>

                        <div className="w-full sm:w-60">
                            <SearchableSelect
                                value={jobFilter}
                                onValueChange={(val) => {
                                    setJobFilter(val);
                                    handleFilterChange(search, statusFilter, val);
                                }}
                                options={[
                                    { label: 'Semua Lowongan Kerja', value: 'all' },
                                    ...jobOptions.map((j) => ({ label: `${j.title} (${j.department})`, value: j.id.toString() })),
                                ]}
                                placeholder="Posisi Loker"
                                className="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <div className="text-xs text-zinc-500 self-end sm:self-center">
                        Total {applicants.total} pelamar tercatat
                    </div>
                </div>

                {/* 4. Tabel Pipeline Pelamar */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="w-[40px] text-center text-xs font-bold">No</TableHead>
                                    <TableHead className="min-w-[190px] text-xs font-bold">Pelamar</TableHead>
                                    <TableHead className="min-w-[170px] text-xs font-bold">Posisi Dilamar</TableHead>
                                    <TableHead className="min-w-[120px] text-xs font-bold">Kontak</TableHead>
                                    <TableHead className="min-w-[110px] text-xs font-bold">Pendidikan</TableHead>
                                    <TableHead className="min-w-[110px] text-center text-xs font-bold">Tahapan</TableHead>
                                    <TableHead className="min-w-[120px] text-xs font-bold">Tanggal Daftar</TableHead>
                                    <TableHead className="min-w-[140px] text-center text-xs font-bold">Aksi Onboarding</TableHead>
                                    <TableHead className="w-[70px] text-right text-xs font-bold">Detail</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {applicants.data && applicants.data.length > 0 ? (
                                    applicants.data.map((app, idx) => (
                                        <TableRow key={app.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                            <TableCell className="text-center text-xs text-zinc-400 font-mono">
                                                {(applicants.current_page - 1) * applicants.per_page + idx + 1}
                                            </TableCell>

                                            <TableCell>
                                                <div className="flex items-center gap-2.5">
                                                    {app.photo_url ? (
                                                        <img
                                                            src={app.photo_url}
                                                            alt={app.name}
                                                            className="w-9 h-9 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shrink-0 shadow-xs"
                                                        />
                                                    ) : (
                                                        <div className="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-200/50">
                                                            {app.name.charAt(0)}
                                                        </div>
                                                    )}
                                                    <div>
                                                        <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                                            {app.name}
                                                        </div>
                                                        <div className="text-[10px] text-zinc-400 font-mono">
                                                            {app.applicant_code} • {app.gender}
                                                        </div>
                                                        {app.expected_salary && (
                                                            <div className="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                                                Gaji: Rp {Number(app.expected_salary).toLocaleString('id-ID')}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div className="font-medium text-xs text-zinc-800 dark:text-zinc-200">
                                                    {app.job_posting?.title || '-'}
                                                </div>
                                                <div className="text-[10px] text-zinc-400">
                                                    {app.job_posting?.department || '-'}
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div className="text-xs text-zinc-600 dark:text-zinc-400 flex items-center gap-1 font-mono">
                                                    <Phone className="w-3 h-3 text-emerald-600" />
                                                    {app.phone_number}
                                                </div>
                                                {app.email && (
                                                    <div className="text-[10px] text-zinc-400 truncate max-w-[140px]">
                                                        {app.email}
                                                    </div>
                                                )}
                                            </TableCell>

                                            <TableCell>
                                                <div className="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                    {app.education}
                                                </div>
                                                {app.major && (
                                                    <div className="text-[10px] text-zinc-400 truncate max-w-[120px]">
                                                        {app.major}
                                                    </div>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-center">
                                                <button
                                                    onClick={() => openStatusModal(app)}
                                                    className="hover:opacity-80 transition"
                                                    title="Klik untuk ubah tahapan seleksi"
                                                >
                                                    {getStatusBadge(app.status)}
                                                </button>
                                            </TableCell>

                                            <TableCell className="text-xs font-mono text-zinc-500">
                                                {app.created_at ? app.created_at.slice(0, 10) : '-'}
                                            </TableCell>

                                            <TableCell className="text-center">
                                                {app.converted_employee ? (
                                                    <Link
                                                        href={route('hcm.employees.show', app.converted_employee.id)}
                                                        className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 text-[10px] font-bold hover:underline"
                                                    >
                                                        <CheckCircle2 className="w-3 h-3 text-purple-600" />
                                                        NIK: {app.converted_employee.employee_code}
                                                    </Link>
                                                ) : (
                                                    <Button
                                                        size="sm"
                                                        onClick={() => openConvertModal(app)}
                                                        className="h-7 text-[10px] bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold gap-1 shadow-sm px-2.5"
                                                    >
                                                        <Rocket className="w-3 h-3" />
                                                        Konversi Karyawan
                                                    </Button>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-7 w-7 text-zinc-500 hover:text-blue-600"
                                                    onClick={() => openDetailModal(app)}
                                                >
                                                    <Eye className="w-3.5 h-3.5" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={9} className="text-center py-10 text-zinc-400 text-xs">
                                            Belum ada berkas pelamar yang masuk untuk kriteria ini.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {applicants.links && applicants.links.length > 3 && (
                        <div className="p-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <div className="text-xs text-zinc-500">
                                Halaman {applicants.current_page} dari {applicants.last_page}
                            </div>
                            <div className="flex gap-1">
                                {applicants.links.map((link, i) => (
                                    <Button
                                        key={i}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        className="h-8 text-xs"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>

                {/* 5. Modal Detail Berkas Pelamar */}
                <Dialog open={isDetailModalOpen} onOpenChange={setIsDetailModalOpen}>
                    <DialogContent className="sm:max-w-lg max-h-[85vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <FileText className="w-5 h-5 text-blue-600" />
                                Profil & Berkas Pelamar
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                No. Registrasi: <strong className="font-mono">{selectedApplicant?.applicant_code}</strong>
                            </DialogDescription>
                        </DialogHeader>

                        {selectedApplicant && (
                            <div className="space-y-4 pt-2 text-xs">
                                <div className="flex items-center gap-3.5 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-850">
                                    {selectedApplicant.photo_url ? (
                                        <img
                                            src={selectedApplicant.photo_url}
                                            alt={selectedApplicant.name}
                                            className="w-14 h-16 rounded-lg object-cover border border-zinc-300 dark:border-zinc-700 shadow-sm shrink-0"
                                        />
                                    ) : (
                                        <div className="w-14 h-16 rounded-lg bg-zinc-200 dark:bg-zinc-800 text-zinc-400 flex flex-col items-center justify-center text-[10px] font-medium shrink-0 border border-dashed border-zinc-300 dark:border-zinc-700">
                                            <User className="w-5 h-5 mb-0.5 text-zinc-400" />
                                            No Foto
                                        </div>
                                    )}
                                    <div className="space-y-0.5 min-w-0">
                                        <div className="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate">
                                            {selectedApplicant.name}
                                        </div>
                                        <div className="text-[11px] text-zinc-500 font-mono">
                                            {selectedApplicant.applicant_code} • {selectedApplicant.gender}
                                        </div>
                                        <div className="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                            Ekspektasi Gaji: {selectedApplicant.expected_salary ? `Rp ${Number(selectedApplicant.expected_salary).toLocaleString('id-ID')} / bulan` : 'Tidak dicantumkan'}
                                        </div>
                                    </div>
                                </div>

                                <div className="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-850/50 grid grid-cols-2 gap-2">
                                    <div>
                                        <div className="text-[10px] text-zinc-400">Posisi Dilamar</div>
                                        <div className="font-bold text-blue-600">{selectedApplicant.job_posting?.title}</div>
                                    </div>
                                    <div>
                                        <div className="text-[10px] text-zinc-400">No. WhatsApp / HP</div>
                                        <div className="font-mono font-medium">{selectedApplicant.phone_number}</div>
                                    </div>
                                    <div>
                                        <div className="text-[10px] text-zinc-400">Email</div>
                                        <div>{selectedApplicant.email || '-'}</div>
                                    </div>
                                    <div>
                                        <div className="text-[10px] text-zinc-400">Pendidikan & Jurusan</div>
                                        <div>{selectedApplicant.education} {selectedApplicant.major ? `(${selectedApplicant.major})` : ''}</div>
                                    </div>
                                    <div>
                                        <div className="text-[10px] text-zinc-400">Ketersediaan Mulai Bekerja</div>
                                        <div className="font-medium">{selectedApplicant.available_start_date || 'Segera / Fleksibel'}</div>
                                    </div>
                                    <div className="col-span-2">
                                        <div className="text-[10px] text-zinc-400">Alamat Tempat Tinggal</div>
                                        <div>{selectedApplicant.address || '-'}</div>
                                    </div>
                                </div>

                                {selectedApplicant.experience_summary && (
                                    <div className="space-y-1">
                                        <div className="font-semibold text-zinc-700 dark:text-zinc-300">Ringkasan Pengalaman Kerja:</div>
                                        <p className="text-zinc-600 dark:text-zinc-400 text-xs bg-zinc-50 dark:bg-zinc-800/40 p-2.5 rounded border border-zinc-200 dark:border-zinc-800">
                                            {selectedApplicant.experience_summary}
                                        </p>
                                    </div>
                                )}

                                {selectedApplicant.skills && (
                                    <div className="space-y-1">
                                        <div className="font-semibold text-zinc-700 dark:text-zinc-300">Keahlian & Keterampilan:</div>
                                        <p className="text-zinc-600 dark:text-zinc-400 text-xs bg-zinc-50 dark:bg-zinc-800/40 p-2.5 rounded border border-zinc-200 dark:border-zinc-800">
                                            {selectedApplicant.skills}
                                        </p>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <div className="font-semibold text-zinc-700 dark:text-zinc-300">Berkas Terlampir:</div>
                                    <div className="flex flex-wrap gap-2">
                                        {selectedApplicant.resume_file_url ? (
                                            <a
                                                href={selectedApplicant.resume_file_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-950/40 text-blue-600 font-semibold hover:underline text-xs"
                                            >
                                                <ExternalLink className="w-3.5 h-3.5" />
                                                Buka Berkas CV / Resume
                                            </a>
                                        ) : (
                                            <span className="text-zinc-400 italic">CV tidak dilampirkan</span>
                                        )}

                                        {selectedApplicant.ktp_file_url && (
                                            <a
                                                href={selectedApplicant.ktp_file_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-200 bg-zinc-50 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-semibold hover:underline text-xs"
                                            >
                                                <ExternalLink className="w-3.5 h-3.5" />
                                                Buka Scan KTP
                                            </a>
                                        )}

                                        {selectedApplicant.portfolio_file_url && (
                                            <a
                                                href={selectedApplicant.portfolio_file_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-purple-200 bg-purple-50 dark:bg-purple-950/40 text-purple-600 font-semibold hover:underline text-xs"
                                            >
                                                <ExternalLink className="w-3.5 h-3.5" />
                                                Tautan Portofolio
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        <DialogFooter className="pt-2">
                            <Button variant="outline" size="sm" onClick={() => setIsDetailModalOpen(false)}>
                                Tutup
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* 6. Modal Ubah Status & Jadwal Interview */}
                <Dialog open={isStatusModalOpen} onOpenChange={setIsStatusModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <Clock className="w-5 h-5 text-amber-500" />
                                Perbarui Tahapan Seleksi Pelamar
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Pelamar: <strong>{selectedApplicant?.name}</strong> ({selectedApplicant?.applicant_code})
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleStatusSubmit} className="space-y-3 pt-1">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Tahapan Seleksi *</Label>
                                <SearchableSelect
                                    value={statusForm.data.status}
                                    onValueChange={(val) => statusForm.setData('status', val)}
                                    options={[
                                        { label: 'Berkas Masuk (Submitted)', value: 'SUBMITTED' },
                                        { label: 'Review Berkas (Screening)', value: 'SCREENING' },
                                        { label: 'Panggilan Wawancara (Interview)', value: 'INTERVIEW' },
                                        { label: 'Diterima Bekerja (Accepted)', value: 'ACCEPTED' },
                                        { label: 'Ditolak / Belum Sesuai (Rejected)', value: 'REJECTED' },
                                    ]}
                                    placeholder="Pilih status"
                                />
                            </div>

                            {statusForm.data.status === 'INTERVIEW' && (
                                <>
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold">Jadwal Tanggal & Jam Wawancara</Label>
                                        <Input
                                            type="datetime-local"
                                            value={statusForm.data.interview_date}
                                            onChange={(e) => statusForm.setData('interview_date', e.target.value)}
                                            className="text-xs"
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold">Lokasi Wawancara</Label>
                                        <Input
                                            value={statusForm.data.interview_location}
                                            onChange={(e) => statusForm.setData('interview_location', e.target.value)}
                                            placeholder="Ruang HRD Pabrik Klaten / Google Meet"
                                            className="text-xs"
                                        />
                                    </div>
                                </>
                            )}

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Catatan Pewawancara / HRD</Label>
                                <Textarea
                                    value={statusForm.data.interviewer_notes}
                                    onChange={(e) => statusForm.setData('interviewer_notes', e.target.value)}
                                    placeholder="Hasil wawancara, nilai tes teknis jahit/desain, dsb..."
                                    rows={3}
                                    className="text-xs"
                                />
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsStatusModalOpen(false)}
                                    disabled={statusForm.processing}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={statusForm.processing}
                                    className="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs"
                                >
                                    {statusForm.processing ? 'Menyimpan...' : 'Simpan Status'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* 7. Modal 1-Klik Konversi Pelamar ke Karyawan Baru */}
                <Dialog open={isConvertModalOpen} onOpenChange={setIsConvertModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <Rocket className="w-5 h-5 text-indigo-600" />
                                1-Klik Konversi Jadi Karyawan Baru
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Data pelamar <strong>{selectedApplicant?.name}</strong> akan otomatis ditransformasikan ke Master Data Karyawan.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleConvertSubmit} className="space-y-3.5 pt-1">
                            <div className="p-3 rounded-lg border border-blue-200 bg-blue-50/50 dark:bg-blue-950/20 text-xs text-blue-900 dark:text-blue-200">
                                💡 Sistem akan otomatis membuatkan <strong>NIK Karyawan Baru</strong>, mentransfer riwayat TTL, kontak, pendidikan, alamat, dan langsung membuka Dossier 360° Karyawan.
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Status Ketenagakerjaan Awal *</Label>
                                <SearchableSelect
                                    value={convertForm.data.employment_status}
                                    onValueChange={(val) => convertForm.setData('employment_status', val)}
                                    options={[
                                        { label: 'Masa Percobaan (Probation 3 Bulan)', value: 'Probation' },
                                        { label: 'Kontrak PKWT', value: 'Kontrak (PKWT)' },
                                        { label: 'Karyawan Tetap (PKWTT)', value: 'Tetap (PKWTT)' },
                                        { label: 'Peserta Magang / PKL', value: 'Magang' },
                                    ]}
                                    placeholder="Pilih status"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Jenjang Jabatan (Job Level) *</Label>
                                <SearchableSelect
                                    value={convertForm.data.job_level}
                                    onValueChange={(val) => convertForm.setData('job_level', val)}
                                    options={[
                                        { label: 'Staff', value: 'Staff' },
                                        { label: 'Operator / Harian', value: 'Harian' },
                                        { label: 'Borongan', value: 'Borongan' },
                                        { label: 'Trainee', value: 'Trainee' },
                                        { label: 'Leader', value: 'Leader' },
                                        { label: 'Supervisor', value: 'Supervisor' },
                                    ]}
                                    placeholder="Pilih jenjang"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Divisi / Departemen *</Label>
                                    <Input
                                        value={convertForm.data.department}
                                        onChange={(e) => convertForm.setData('department', e.target.value)}
                                        className="text-xs"
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Posisi / Jabatan *</Label>
                                    <Input
                                        value={convertForm.data.position}
                                        onChange={(e) => convertForm.setData('position', e.target.value)}
                                        className="text-xs"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Tanggal Mulai Bekerja (Join Date) *</Label>
                                <Input
                                    type="date"
                                    value={convertForm.data.join_date}
                                    onChange={(e) => convertForm.setData('join_date', e.target.value)}
                                    className="text-xs font-mono"
                                    required
                                />
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsConvertModalOpen(false)}
                                    disabled={convertForm.processing}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={convertForm.processing}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs gap-1.5"
                                >
                                    <Rocket className="w-3.5 h-3.5" />
                                    {convertForm.processing ? 'Mengonversi...' : 'Konfirmasi & Terbitkan Karyawan'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
