import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Briefcase,
    Plus,
    Search,
    Copy,
    Check,
    Edit2,
    Trash2,
    Users,
    MapPin,
    Calendar,
    QrCode,
    X,
    CheckCircle2,
    Building2,
    Globe2,
    DollarSign,
    Layers,
    Share2,
    ExternalLink,
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

export default function JobsIndex({
    jobs,
    filters = {},
    metrics = {},
    departments = [],
    positions = [],
    legalEntities = [],
    channels = [],
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [deptFilter, setDeptFilter] = useState(filters.department || 'all');

    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [editingJob, setEditingJob] = useState(null);
    const [copiedSlug, setCopiedSlug] = useState(null);
    const [qrJob, setQrJob] = useState(null);

    // Form Loker
    const form = useForm({
        title: '',
        department: departments[0] || 'Produksi',
        position: positions[0] || 'Staff',
        job_type: 'Penuh Waktu (Full Time)',
        location: 'Pabrik Klaten',
        quota: 1,
        min_education: 'SMA Sederajat',
        min_experience_years: 0,
        description: '',
        requirements: '',
        benefits: '',
        deadline: '',
        legal_entity: legalEntities[0] || '',
        salary_range_min: '',
        salary_range_max: '',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        recruitment_channel: channels[0] || 'Instagram',
        status: 'Aktif',
        is_active: true,
    });

    const handleFilterChange = (newSearch, newStatus, newDept) => {
        router.get(
            route('hcm.recruitment.jobs.index'),
            {
                search: newSearch,
                status: newStatus,
                department: newDept,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    // Public Career Link
    const getJobLink = (job) => {
        return `${window.location.origin}/karir/${job.slug}`;
    };

    const getQrUrl = (job) => {
        return route('hcm.recruitment.jobs.qr', job.slug || job.id);
    };

    const handleCopyLink = (job) => {
        navigator.clipboard.writeText(getJobLink(job));
        setCopiedSlug(job.slug);
        setTimeout(() => setCopiedSlug(null), 2500);
    };

    const openAddModal = () => {
        setEditingJob(null);
        form.reset();
        form.setData({
            title: '',
            department: departments[0] || 'Produksi',
            position: positions[0] || 'Staff',
            job_type: 'Penuh Waktu (Full Time)',
            location: 'Pabrik Klaten',
            quota: 1,
            min_education: 'SMA Sederajat',
            min_experience_years: 0,
            description: '',
            requirements: '',
            benefits: '',
            deadline: '',
            legal_entity: legalEntities[0] || '',
            salary_range_min: '',
            salary_range_max: '',
            start_date: new Date().toISOString().split('T')[0],
            end_date: '',
            recruitment_channel: channels[0] || 'Instagram',
            status: 'Aktif',
            is_active: true,
        });
        setIsFormModalOpen(true);
    };

    const openEditModal = (job) => {
        setEditingJob(job);
        form.setData({
            title: job.title,
            department: job.department,
            position: job.position,
            job_type: job.job_type || 'Penuh Waktu (Full Time)',
            location: job.location || 'Pabrik Klaten',
            quota: job.quota || 1,
            min_education: job.min_education || 'SMA Sederajat',
            min_experience_years: job.min_experience_years ?? 0,
            description: job.description || '',
            requirements: job.requirements || '',
            benefits: job.benefits || '',
            deadline: job.deadline || '',
            legal_entity: job.legal_entity || legalEntities[0] || '',
            salary_range_min: job.salary_range_min ?? '',
            salary_range_max: job.salary_range_max ?? '',
            start_date: job.start_date || '',
            end_date: job.end_date || '',
            recruitment_channel: job.recruitment_channel || channels[0] || 'Instagram',
            status: job.status || (job.is_active ? 'Aktif' : 'Ditutup'),
            is_active: Boolean(job.is_active),
        });
        setIsFormModalOpen(true);
    };

    const handleStatusChange = (val) => {
        form.setData((prev) => ({
            ...prev,
            status: val,
            is_active: val === 'Aktif',
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingJob) {
            form.put(route('hcm.recruitment.jobs.update', editingJob.slug || editingJob.id), {
                onSuccess: () => {
                    setIsFormModalOpen(false);
                    form.reset();
                },
            });
        } else {
            form.post(route('hcm.recruitment.jobs.store'), {
                onSuccess: () => {
                    setIsFormModalOpen(false);
                    form.reset();
                },
            });
        }
    };

    const handleDelete = (job) => {
        if (confirm(`Hapus lowongan kerja "${job.title}"? Seluruh data pelamar terkait juga akan terhapus.`)) {
            router.delete(route('hcm.recruitment.jobs.destroy', job.slug || job.id));
        }
    };

    const getJobStatusBadge = (status, isActive) => {
        if (status === 'Terpenuhi') {
            return (
                <Badge className="bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800 text-[10px]">
                    Terpenuhi
                </Badge>
            );
        }
        if (status === 'Aktif' || isActive) {
            return (
                <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 text-[10px]">
                    Aktif / Buka
                </Badge>
            );
        }
        if (status === 'Draft') {
            return (
                <Badge variant="outline" className="border-amber-300 text-amber-600 bg-amber-50/50 text-[10px]">
                    Draft
                </Badge>
            );
        }
        return (
            <Badge variant="outline" className="border-zinc-300 text-zinc-400 text-[10px]">
                Ditutup
            </Badge>
        );
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Briefcase className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Master Lowongan Kerja & Rekrutmen
                    </span>
                </div>
            }
        >
            <Head title="Master Lowongan Kerja (Loker) - HCM" />

            <div className="space-y-4">
                {/* Header Banner Canvas */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <Briefcase className="h-5 w-5 text-indigo-600 shrink-0" />
                            <span>Master Lowongan Kerja (Loker)</span>
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Kelola posisi loker pabrik & kantor serta dapatkan link formulir pendaftaran publik mandiri (/karir/[slug]).
                        </p>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <Link
                            href={route('hcm.recruitment.applicants.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 shadow-xs transition"
                        >
                            <Users className="w-3.5 h-3.5 text-indigo-600" />
                            Pipeline Pelamar ({metrics.total_applicants ?? 0})
                        </Link>
                        <Button
                            onClick={openAddModal}
                            size="sm"
                            className="bg-indigo-600 hover:bg-indigo-700 text-white gap-1.5 text-xs shadow-xs"
                        >
                            <Plus className="w-3.5 h-3.5" />
                            Buat Loker Baru
                        </Button>
                    </div>
                </div>

                {/* 1. Baris Metrik Ringkasan */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 flex items-center justify-center shrink-0">
                            <Briefcase className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-zinc-500 font-medium">Total Posisi Loker</span>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                {metrics.total_jobs ?? 0}
                            </div>
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/20 dark:bg-emerald-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center shrink-0">
                            <CheckCircle2 className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-emerald-600 font-medium">Loker Aktif Buka</span>
                            <div className="text-xl font-bold text-emerald-700 dark:text-emerald-300 font-mono">
                                {metrics.active_jobs ?? 0}
                            </div>
                        </div>
                    </Card>

                    <Card className="border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/20 dark:bg-amber-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 flex items-center justify-center shrink-0">
                            <Users className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-amber-600 font-medium">Total Pelamar Masuk</span>
                            <div className="text-xl font-bold text-amber-700 dark:text-amber-300 font-mono">
                                {metrics.total_applicants ?? 0}
                            </div>
                        </div>
                    </Card>

                    <Card className="border border-purple-200/80 dark:border-purple-900/40 bg-purple-50/20 dark:bg-purple-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 flex items-center justify-center shrink-0">
                            <Users className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-purple-600 font-medium">Pelamar Diterima (Hired)</span>
                            <div className="text-xl font-bold text-purple-700 dark:text-purple-300 font-mono">
                                {metrics.hired_applicants ?? 0}
                            </div>
                        </div>
                    </Card>
                </div>

                {/* 2. Filter & Pencarian */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <CardContent className="p-3.5 flex flex-col md:flex-row items-center justify-between gap-3">
                        <div className="relative w-full md:w-80">
                            <Search className="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-2.5" />
                            <Input
                                placeholder="Cari posisi / judul loker..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleFilterChange(search, statusFilter, deptFilter);
                                    }
                                }}
                                className="pl-9 text-xs h-8"
                            />
                        </div>

                        <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
                            <div className="w-full sm:w-44">
                                <SearchableSelect
                                    value={statusFilter}
                                    onValueChange={(val) => {
                                        setStatusFilter(val);
                                        handleFilterChange(search, val, deptFilter);
                                    }}
                                    options={[
                                        { label: 'Semua Status', value: 'all' },
                                        { label: 'Aktif / Buka', value: 'active' },
                                        { label: 'Nonaktif / Tutup', value: 'inactive' },
                                    ]}
                                    placeholder="Status Loker"
                                    className="text-xs"
                                />
                            </div>

                            <div className="w-full sm:w-52">
                                <SearchableSelect
                                    value={deptFilter}
                                    onValueChange={(val) => {
                                        setDeptFilter(val);
                                        handleFilterChange(search, statusFilter, val);
                                    }}
                                    options={[
                                        { label: 'Semua Departemen', value: 'all' },
                                        ...departments.map((d) => ({ label: d, value: d })),
                                    ]}
                                    placeholder="Departemen"
                                    className="text-xs"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* 3. Tabel Daftar Lowongan Kerja */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="w-[50px] text-center text-xs font-semibold">No</TableHead>
                                    <TableHead className="min-w-[200px] text-xs font-semibold">Posisi & Judul Loker</TableHead>
                                    <TableHead className="min-w-[140px] text-xs font-semibold">Divisi & Entitas CV</TableHead>
                                    <TableHead className="min-w-[80px] text-center text-xs font-semibold">Kuota</TableHead>
                                    <TableHead className="min-w-[90px] text-center text-xs font-semibold">Pelamar</TableHead>
                                    <TableHead className="min-w-[110px] text-xs font-semibold">Batas Lamaran</TableHead>
                                    <TableHead className="min-w-[100px] text-center text-xs font-semibold">Status</TableHead>
                                    <TableHead className="min-w-[160px] text-xs font-semibold">Tautan Pendaftaran Publik</TableHead>
                                    <TableHead className="w-[80px] text-right text-xs font-semibold">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {jobs.data && jobs.data.length > 0 ? (
                                    jobs.data.map((job, idx) => (
                                        <TableRow key={job.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                            <TableCell className="text-center text-xs text-zinc-400 font-mono">
                                                {(jobs.current_page - 1) * jobs.per_page + idx + 1}
                                            </TableCell>

                                            <TableCell>
                                                <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                                    {job.title}
                                                </div>
                                                <div className="text-[10px] text-zinc-400 mt-0.5">
                                                    {job.job_code ? <span className="font-mono text-indigo-600 font-semibold">{job.job_code} • </span> : ''}
                                                    {job.job_type} • Min. {job.min_education}
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div className="text-xs text-zinc-800 dark:text-zinc-200 font-medium">
                                                    {job.department}
                                                </div>
                                                <div className="text-[10px] text-zinc-500 flex items-center gap-1 mt-0.5">
                                                    <Building2 className="w-3 h-3 text-zinc-400" />
                                                    {job.legal_entity || 'NISGroup'}
                                                </div>
                                            </TableCell>

                                            <TableCell className="text-center text-xs font-mono font-semibold">
                                                <span className="text-indigo-600">{job.fulfilled_count || 0}</span> / {job.quota} Org
                                            </TableCell>

                                            <TableCell className="text-center">
                                                <Link
                                                    href={route('hcm.recruitment.applicants.index', { job_id: job.slug })}
                                                    className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-bold font-mono text-xs hover:underline"
                                                    title="Lihat pelamar posisi ini"
                                                >
                                                    <Users className="w-3 h-3" />
                                                    {job.applicants_count || 0}
                                                </Link>
                                            </TableCell>

                                            <TableCell className="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                                                {job.deadline ? (
                                                    <div>{job.deadline}</div>
                                                ) : (
                                                    <span className="text-zinc-400 italic">Terbuka</span>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-center">
                                                {getJobStatusBadge(job.status, job.is_active)}
                                            </TableCell>

                                            <TableCell>
                                                <div className="flex items-center gap-1.5">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleCopyLink(job)}
                                                        className="h-7 text-[11px] gap-1 px-2 text-zinc-700 hover:text-indigo-600 border-zinc-200 shadow-xs"
                                                        title="Salin link pendaftaran pelamar mandiri"
                                                    >
                                                        {copiedSlug === job.slug ? (
                                                            <>
                                                                <Check className="w-3 h-3 text-emerald-600" />
                                                                <span className="text-emerald-600 font-semibold">Tersalin!</span>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <Copy className="w-3 h-3" />
                                                                <span>Salin Link</span>
                                                            </>
                                                        )}
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-zinc-500 hover:text-indigo-600"
                                                        onClick={() => setQrJob(job)}
                                                        title="Unduh QR Code pendaftaran"
                                                    >
                                                        <QrCode className="w-3.5 h-3.5" />
                                                    </Button>

                                                    <a
                                                        href={getJobLink(job)}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="p-1 text-zinc-400 hover:text-zinc-700"
                                                        title="Buka Form Publik di Tab Baru"
                                                    >
                                                        <ExternalLink className="w-3.5 h-3.5" />
                                                    </a>
                                                </div>
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-zinc-500 hover:text-indigo-600"
                                                        onClick={() => openEditModal(job)}
                                                        title="Edit Loker"
                                                    >
                                                        <Edit2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-zinc-500 hover:text-rose-600"
                                                        onClick={() => handleDelete(job)}
                                                        title="Hapus Loker"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={9} className="text-center py-12 text-zinc-400 text-xs">
                                            Belum ada lowongan kerja yang terdaftar.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {jobs.links && jobs.links.length > 3 && (
                        <div className="p-3 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
                            <div>
                                Menampilkan {jobs.from || 0} - {jobs.to || 0} dari {jobs.total || 0} lowongan
                            </div>
                            <div className="flex items-center gap-1">
                                {jobs.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => link.url && router.get(link.url)}
                                        disabled={!link.url}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-2.5 py-1 text-xs rounded border ${
                                            link.active
                                                ? 'bg-indigo-600 text-white border-indigo-600 font-semibold'
                                                : link.url
                                                ? 'hover:bg-zinc-100 text-zinc-600 dark:text-zinc-300 border-zinc-200 dark:border-zinc-800'
                                                : 'text-zinc-400 border-transparent opacity-50 cursor-not-allowed'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal Tambah / Edit Lowongan Kerja */}
            <Dialog open={isFormModalOpen} onOpenChange={setIsFormModalOpen}>
                <DialogContent className="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-indigo-600">
                            <Briefcase className="w-5 h-5" />
                            {editingJob ? 'Edit Lowongan Kerja (Loker)' : 'Buat Lowongan Kerja Baru'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Isi detail lowongan kerja sesuai Blueprint HCM NISGroup. Link pendaftaran publik mandiri otomatis terbit.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-3.5 pt-1">
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Judul Lowongan Kerja *</Label>
                            <Input
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                placeholder="Contoh: Operator Jahit Kaos Polos / Admin Gudang"
                                className="h-8 text-xs"
                                required
                            />
                            {form.errors.title && (
                                <p className="text-[11px] text-rose-500">{form.errors.title}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Departemen / Divisi *</Label>
                                <SearchableSelect
                                    value={form.data.department}
                                    onValueChange={(val) => form.setData('department', val)}
                                    options={departments.map((d) => ({ label: d, value: d }))}
                                    placeholder="Pilih departemen"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Posisi / Jabatan *</Label>
                                <SearchableSelect
                                    value={form.data.position}
                                    onValueChange={(val) => form.setData('position', val)}
                                    options={positions.map((p) => ({ label: p, value: p }))}
                                    placeholder="Pilih posisi"
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Entitas Legal (CV) *</Label>
                                <SearchableSelect
                                    value={form.data.legal_entity}
                                    onValueChange={(val) => form.setData('legal_entity', val)}
                                    options={legalEntities.map((cv) => ({ label: cv, value: cv }))}
                                    placeholder="Pilih entitas CV"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Jenis Pekerjaan *</Label>
                                <SearchableSelect
                                    value={form.data.job_type}
                                    onValueChange={(val) => form.setData('job_type', val)}
                                    options={[
                                        { label: 'Penuh Waktu (Full Time)', value: 'Penuh Waktu (Full Time)' },
                                        { label: 'Kontrak (PKWT)', value: 'Kontrak (PKWT)' },
                                        { label: 'Harian Lepas / Borongan', value: 'Harian Lepas / Borongan' },
                                        { label: 'Magang / PKL', value: 'Magang / PKL' },
                                    ]}
                                    placeholder="Pilih tipe kerja"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Jumlah Kuota (Orang) *</Label>
                                <Input
                                    type="number"
                                    min="1"
                                    value={form.data.quota}
                                    onChange={(e) => form.setData('quota', parseInt(e.target.value) || 1)}
                                    className="h-8 text-xs font-mono"
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Min. Pendidikan *</Label>
                                <SearchableSelect
                                    value={form.data.min_education}
                                    onValueChange={(val) => form.setData('min_education', val)}
                                    options={[
                                        { label: 'Semua Jenjang', value: 'Semua Jenjang' },
                                        { label: 'SMP / Sederajat', value: 'SMP' },
                                        { label: 'SMA / SMK Sederajat', value: 'SMA Sederajat' },
                                        { label: 'Diploma (D3)', value: 'Diploma (D3)' },
                                        { label: 'Sarjana (S1)', value: 'Sarjana (S1)' },
                                    ]}
                                    placeholder="Pendidikan"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Min. Pengalaman (Thn)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={form.data.min_experience_years}
                                    onChange={(e) => form.setData('min_experience_years', parseInt(e.target.value) || 0)}
                                    className="h-8 text-xs font-mono"
                                    placeholder="0 (Fresh Graduate)"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Penempatan / Lokasi *</Label>
                                <Input
                                    value={form.data.location}
                                    onChange={(e) => form.setData('location', e.target.value)}
                                    placeholder="Pabrik Klaten"
                                    className="h-8 text-xs"
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Saluran Rekrutmen</Label>
                                <SearchableSelect
                                    value={form.data.recruitment_channel}
                                    onValueChange={(val) => form.setData('recruitment_channel', val)}
                                    options={channels.map((c) => ({ label: c, value: c }))}
                                    placeholder="Pilih saluran"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Batas Lamaran (Deadline)</Label>
                                <Input
                                    type="date"
                                    value={form.data.deadline}
                                    onChange={(e) => form.setData('deadline', e.target.value)}
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Status Loker *</Label>
                                <SearchableSelect
                                    value={form.data.status}
                                    onValueChange={handleStatusChange}
                                    options={[
                                        { label: 'Aktif / Buka', value: 'Aktif' },
                                        { label: 'Draft', value: 'Draft' },
                                        { label: 'Ditutup', value: 'Ditutup' },
                                        { label: 'Terpenuhi', value: 'Terpenuhi' },
                                    ]}
                                    placeholder="Pilih status"
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Rentang Gaji Min (Rp)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={form.data.salary_range_min}
                                    onChange={(e) => form.setData('salary_range_min', e.target.value)}
                                    placeholder="Contoh: 2000000"
                                    className="h-8 text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Rentang Gaji Max (Rp)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={form.data.salary_range_max}
                                    onChange={(e) => form.setData('salary_range_max', e.target.value)}
                                    placeholder="Contoh: 3000000"
                                    className="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Deskripsi Pekerjaan *</Label>
                            <Textarea
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                placeholder="Jelaskan gambaran tugas dan tanggung jawab utama posisi ini..."
                                rows={2}
                                className="text-xs"
                                required
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Kualifikasi & Persyaratan *</Label>
                            <Textarea
                                value={form.data.requirements}
                                onChange={(e) => form.setData('requirements', e.target.value)}
                                placeholder="Daftar keahlian, umur, berkas yang dibutuhkan (satu baris per poin)..."
                                rows={2}
                                className="text-xs"
                                required
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Fasilitas & Benefit (Opsional)</Label>
                            <Input
                                value={form.data.benefits}
                                onChange={(e) => form.setData('benefits', e.target.value)}
                                placeholder="Uang makan, bonus target lembur, BPJS, dll..."
                                className="h-8 text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsFormModalOpen(false)}
                                disabled={form.processing}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5"
                            >
                                {form.processing ? 'Menyimpan...' : (editingJob ? 'Simpan Perubahan' : 'Terbitkan Loker')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal QR Code Loker */}
            {qrJob && (
                <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-xs p-5 space-y-4 border border-zinc-200 dark:border-zinc-800 animate-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                    <QrCode className="w-4 h-4 text-indigo-600" />
                                    QR Code Pendaftaran Pelamar
                                </h3>
                                <p className="text-[11px] text-zinc-500 mt-0.5 font-medium">{qrJob.title}</p>
                            </div>
                            <button
                                onClick={() => setQrJob(null)}
                                className="p-1 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <div className="flex flex-col items-center gap-2.5">
                            <div className="p-3 bg-white border border-zinc-200 rounded-xl shadow-sm">
                                <img
                                    src={getQrUrl(qrJob)}
                                    alt={`QR Code ${qrJob.title}`}
                                    className="w-44 h-44 rounded"
                                />
                            </div>
                            <p className="text-[10px] text-zinc-500 font-mono break-all px-2 text-center">
                                {getJobLink(qrJob)}
                            </p>
                        </div>

                        <div className="flex gap-2">
                            <Button
                                className="flex-1 h-8 text-xs gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"
                                onClick={() => handleCopyLink(qrJob)}
                            >
                                {copiedSlug === qrJob.slug ? (
                                    <><Check className="w-3 h-3 text-emerald-300" /> Tersalin!</>
                                ) : (
                                    <><Copy className="w-3 h-3" /> Salin Link</>
                                )}
                            </Button>
                            <Button
                                variant="outline"
                                className="flex-1 h-8 text-xs"
                                onClick={() => {
                                    const a = document.createElement('a');
                                    a.href = getQrUrl(qrJob);
                                    a.download = `QR-Loker-${qrJob.slug}.svg`;
                                    a.click();
                                }}
                            >
                                Unduh QR
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
