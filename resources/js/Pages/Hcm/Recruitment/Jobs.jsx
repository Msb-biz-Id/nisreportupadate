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
    GraduationCap,
    Calendar,
    QrCode,
    Link as LinkIcon,
    X,
    Power,
    CheckCircle2,
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
    filters,
    metrics,
    departments,
    positions,
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [deptFilter, setDeptFilter] = useState(filters.department || 'all');

    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [editingJob, setEditingJob] = useState(null);
    const [copiedSlug, setCopiedSlug] = useState(null);
    const [qrJob, setQrJob] = useState(null); // Modal QR Code

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

    const getJobLink = (job) => {
        // Link pipeline pelamar internal untuk loker ini
        return `${window.location.origin}/hcm/recruitment/applicants?job_id=${job.id}`;
    };

    const getQrUrl = (job) => {
        // QR generate via Laravel simple-qrcode (SVG, tidak butuh package npm)
        return `/hcm/recruitment/jobs/${job.id}/qr`;
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
            job_type: job.job_type,
            location: job.location,
            quota: job.quota,
            min_education: job.min_education,
            min_experience_years: job.min_experience_years,
            description: job.description,
            requirements: job.requirements,
            benefits: job.benefits || '',
            deadline: job.deadline || '',
            is_active: Boolean(job.is_active),
        });
        setIsFormModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingJob) {
            form.put(route('hcm.recruitment.jobs.update', editingJob.id), {
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
            router.delete(route('hcm.recruitment.jobs.destroy', job.id));
        }
    };

    return (
        <>
        <AppLayout title="Master Lowongan Kerja">
            <Head title="Master Lowongan Kerja & Rekrutmen" />

            <div className="space-y-6">
                {/* 1. Header & Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="p-2 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400">
                                <Briefcase className="w-6 h-6" />
                            </div>
                            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Master Lowongan Kerja (Loker)
                            </h1>
                        </div>
                        <p className="text-xs sm:text-sm text-zinc-500 mt-1">
                            Kelola posisi loker pabrik & kantor serta dapatkan link formulir pendaftaran publik mandiri.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href={route('hcm.recruitment.applicants.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 shadow-sm transition"
                        >
                            <Users className="w-4 h-4 text-blue-600" />
                            Pipeline Pelamar ({metrics.total_applicants})
                        </Link>
                        <Button
                            onClick={openAddModal}
                            className="bg-blue-600 hover:bg-blue-700 text-white gap-1.5 text-xs font-semibold shadow-sm"
                        >
                            <Plus className="w-4 h-4" />
                            Buat Loker Baru
                        </Button>
                    </div>
                </div>

                {/* 2. KPI Metrics Grid */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <Briefcase className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Total Posisi Loker</div>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {metrics.total_jobs}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                                <CheckCircle2 className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Loker Aktif Tayang</div>
                                <div className="text-xl font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    {metrics.active_jobs}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                                <Users className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Total Pelamar Masuk</div>
                                <div className="text-xl font-bold text-amber-600 dark:text-amber-400 font-mono">
                                    {metrics.total_applicants}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600">
                                <Users className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Pelamar Diterima (Hired)</div>
                                <div className="text-xl font-bold text-purple-600 dark:text-purple-400 font-mono">
                                    {metrics.hired_applicants}
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
                                placeholder="Cari posisi / judul loker..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleFilterChange(search, statusFilter, deptFilter);
                                    }
                                }}
                                className="pl-9 text-xs h-9"
                            />
                        </div>

                        <div className="w-full sm:w-44">
                            <SearchableSelect
                                value={statusFilter}
                                onValueChange={(val) => {
                                    setStatusFilter(val);
                                    handleFilterChange(search, val, deptFilter);
                                }}
                                options={[
                                    { label: 'Semua Status', value: 'all' },
                                    { label: 'Aktif Tayang', value: 'active' },
                                    { label: 'Nonaktif / Tutup', value: 'inactive' },
                                ]}
                                placeholder="Status Loker"
                                className="h-9 text-xs"
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
                                className="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <div className="text-xs text-zinc-500 self-end sm:self-center">
                        Total {jobs.total} lowongan terdaftar
                    </div>
                </div>

                {/* 4. Tabel Daftar Lowongan Kerja */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="w-[40px] text-center text-xs font-bold">No</TableHead>
                                    <TableHead className="min-w-[200px] text-xs font-bold">Posisi & Judul Loker</TableHead>
                                    <TableHead className="min-w-[120px] text-xs font-bold">Divisi / Lokasi</TableHead>
                                    <TableHead className="min-w-[80px] text-center text-xs font-bold">Kuota</TableHead>
                                    <TableHead className="min-w-[90px] text-center text-xs font-bold">Pelamar</TableHead>
                                    <TableHead className="min-w-[110px] text-xs font-bold">Batas Akhir</TableHead>
                                    <TableHead className="min-w-[100px] text-center text-xs font-bold">Status</TableHead>
                                    <TableHead className="min-w-[140px] text-xs font-bold">Link & QR Code</TableHead>
                                    <TableHead className="w-[90px] text-right text-xs font-bold">Aksi</TableHead>
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
                                                <div className="text-[10px] text-zinc-400">
                                                    {job.job_type} • Min. {job.min_education}
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div className="text-xs text-zinc-800 dark:text-zinc-200 font-medium">
                                                    {job.department}
                                                </div>
                                                <div className="text-[10px] text-zinc-400 flex items-center gap-1">
                                                    <MapPin className="w-3 h-3" /> {job.location}
                                                </div>
                                            </TableCell>

                                            <TableCell className="text-center text-xs font-mono font-semibold">
                                                {job.quota} Org
                                            </TableCell>

                                            <TableCell className="text-center">
                                                <Link
                                                    href={route('hcm.recruitment.applicants.index', { job_id: job.id })}
                                                    className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 font-bold font-mono text-xs hover:underline"
                                                >
                                                    <Users className="w-3 h-3" />
                                                    {job.applicants_count || 0}
                                                </Link>
                                            </TableCell>

                                            <TableCell className="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                                                {job.deadline || 'Tanpa Batas'}
                                            </TableCell>

                                            <TableCell className="text-center">
                                                {job.is_active ? (
                                                    <Badge className="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-[10px]">
                                                        Tayang
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="border-zinc-300 text-zinc-400 text-[10px]">
                                                        Tutup
                                                    </Badge>
                                                )}
                                            </TableCell>

                                            <TableCell>
                                                <div className="flex items-center gap-1.5">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleCopyLink(job)}
                                                        className="h-7 text-[11px] gap-1 px-2 text-zinc-600"
                                                        title="Salin link loker"
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
                                                        title="Tampilkan QR Code Loker"
                                                    >
                                                        <QrCode className="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-zinc-500 hover:text-blue-600"
                                                        onClick={() => openEditModal(job)}
                                                    >
                                                        <Edit2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-zinc-500 hover:text-rose-600"
                                                        onClick={() => handleDelete(job)}
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={9} className="text-center py-10 text-zinc-400 text-xs">
                                            Belum ada data lowongan kerja. Klik tombol "Buat Loker Baru" untuk mulai membuka rekrutmen.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {jobs.links && jobs.links.length > 3 && (
                        <div className="p-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <div className="text-xs text-zinc-500">
                                Halaman {jobs.current_page} dari {jobs.last_page}
                            </div>
                            <div className="flex gap-1">
                                {jobs.links.map((link, i) => (
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

                {/* 5. Modal Tambah / Edit Lowongan Kerja */}
                <Dialog open={isFormModalOpen} onOpenChange={setIsFormModalOpen}>
                    <DialogContent className="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <Briefcase className="w-5 h-5 text-blue-600" />
                                {editingJob ? 'Edit Lowongan Kerja' : 'Buat Lowongan Kerja Baru'}
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Posisi ini akan otomatis mendapatkan link formulir pendaftaran mandiri pelamar.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSubmit} className="space-y-3.5 pt-1">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Judul Lowongan Kerja *</Label>
                                <Input
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    placeholder="Contoh: Staff Penjahit Konveksi / Admin Logistik"
                                    className="text-xs"
                                    required
                                />
                                {form.errors.title && (
                                    <p className="text-[11px] text-rose-500">{form.errors.title}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Departemen / Divisi *</Label>
                                    <SearchableSelect
                                        value={form.data.department}
                                        onValueChange={(val) => form.setData('department', val)}
                                        options={departments.map((d) => ({ label: d, value: d }))}
                                        placeholder="Pilih departemen"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Posisi / Jabatan *</Label>
                                    <SearchableSelect
                                        value={form.data.position}
                                        onValueChange={(val) => form.setData('position', val)}
                                        options={positions.map((p) => ({ label: p, value: p }))}
                                        placeholder="Pilih posisi"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Jenis Pekerjaan *</Label>
                                    <SearchableSelect
                                        value={form.data.job_type}
                                        onValueChange={(val) => form.setData('job_type', val)}
                                        options={[
                                            { label: 'Penuh Waktu (Full Time)', value: 'Penuh Waktu (Full Time)' },
                                            { label: 'Kontrak (PKWT)', value: 'Kontrak (PKWT)' },
                                            { label: 'Harian Lepas / Borongan', value: 'Harian Lepas / Borongan' },
                                            { label: 'Magang / PKL', value: 'Magang / PKL' },
                                        ]}
                                        placeholder="Tipe kerja"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Penempatan *</Label>
                                    <Input
                                        value={form.data.location}
                                        onChange={(e) => form.setData('location', e.target.value)}
                                        placeholder="Pabrik Klaten"
                                        className="text-xs"
                                        required
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Jumlah Kuota (Org) *</Label>
                                    <Input
                                        type="number"
                                        min="1"
                                        value={form.data.quota}
                                        onChange={(e) => form.setData('quota', parseInt(e.target.value) || 1)}
                                        className="text-xs font-mono"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Min. Pendidikan *</Label>
                                    <SearchableSelect
                                        value={form.data.min_education}
                                        onValueChange={(val) => form.setData('min_education', val)}
                                        options={[
                                            { label: 'SMP', value: 'SMP' },
                                            { label: 'SMA / SMK Sederajat', value: 'SMA Sederajat' },
                                            { label: 'Diploma (D3)', value: 'Diploma (D3)' },
                                            { label: 'Sarjana (S1)', value: 'Sarjana (S1)' },
                                        ]}
                                        placeholder="Pendidikan"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Min. Pengalaman (Thn)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={form.data.min_experience_years}
                                        onChange={(e) => form.setData('min_experience_years', parseInt(e.target.value) || 0)}
                                        className="text-xs font-mono"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Batas Akhir (Deadline)</Label>
                                    <Input
                                        type="date"
                                        value={form.data.deadline}
                                        onChange={(e) => form.setData('deadline', e.target.value)}
                                        className="text-xs"
                                    />
                                </div>
                            </div>



                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Deskripsi Pekerjaan *</Label>
                                <Textarea
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder="Jelaskan gambaran tugas dan tanggung jawab utama posisi ini..."
                                    rows={3}
                                    className="text-xs"
                                    required
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Kualifikasi & Persyaratan *</Label>
                                <Textarea
                                    value={form.data.requirements}
                                    onChange={(e) => form.setData('requirements', e.target.value)}
                                    placeholder="Daftar keahlian, umur, berkas yang dibutuhkan (satu baris per poin)..."
                                    rows={3}
                                    className="text-xs"
                                    required
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Fasilitas & Benefit (Opsional)</Label>
                                <Textarea
                                    value={form.data.benefits}
                                    onChange={(e) => form.setData('benefits', e.target.value)}
                                    placeholder="Uang makan, bonus target, BPJS Ketenagakerjaan, dll..."
                                    rows={2}
                                    className="text-xs"
                                />
                            </div>

                            <div className="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={form.data.is_active}
                                    onChange={(e) => form.setData('is_active', e.target.checked)}
                                    className="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 h-4 w-4"
                                />
                                <Label htmlFor="is_active" className="text-xs cursor-pointer">
                                    Aktifkan status tayang publik untuk lowongan ini
                                </Label>
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsFormModalOpen(false)}
                                    disabled={form.processing}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    className="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs"
                                >
                                    {form.processing ? 'Menyimpan...' : (editingJob ? 'Perbarui Loker' : 'Terbitkan Loker')}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>

        {/* Modal QR Code Loker */}
        {qrJob && (
            <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
                <div className="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-xs p-6 space-y-4 border border-zinc-200 dark:border-zinc-800 animate-in zoom-in-95 duration-150">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <QrCode className="w-4 h-4 text-indigo-500" />
                                QR Code Loker
                            </h3>
                            <p className="text-[11px] text-zinc-500 mt-0.5">{qrJob.title}</p>
                        </div>
                        <button onClick={() => setQrJob(null)} className="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <X className="w-4 h-4" />
                        </button>
                    </div>

                    <div className="flex flex-col items-center gap-3">
                        <div className="p-2 bg-white border border-zinc-200 rounded-xl shadow-sm">
                            {/* SVG QR via Laravel simple-qrcode */}
                            <img
                                src={getQrUrl(qrJob)}
                                alt={`QR Code ${qrJob.title}`}
                                className="w-48 h-48 rounded"
                            />
                        </div>
                        <p className="text-[10px] text-zinc-400 font-mono break-all px-2 text-center">{getJobLink(qrJob)}</p>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            className="flex-1 h-8 text-xs gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"
                            onClick={() => handleCopyLink(qrJob)}
                        >
                            {copiedSlug === qrJob.slug ? (
                                <><Check className="w-3.5 h-3.5" /> Tersalin!</>
                            ) : (
                                <><Copy className="w-3.5 h-3.5" /> Salin Link</>
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
        </>
    );
}
