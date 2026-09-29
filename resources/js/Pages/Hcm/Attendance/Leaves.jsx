import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    CalendarDays,
    Calendar,
    Users,
    CheckCircle2,
    Clock,
    XCircle,
    Plus,
    Search,
    Filter,
    FileText,
    ExternalLink,
    AlertCircle,
    Check,
    X,
    Trash2,
    Upload,
    Eye,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import UniversalDocumentViewer from '@/Components/Hcm/UniversalDocumentViewer';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
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

export default function LeavesIndex({
    leaveRequests,
    filters,
    metrics,
    employees,
    leaveTypes,
}) {
    // State Filter
    const [status, setStatus] = useState(filters.status || 'all');
    const [leaveType, setLeaveType] = useState(filters.leave_type || 'all');
    const [search, setSearch] = useState(filters.search || '');

    // State Modals
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
    const [selectedRequest, setSelectedRequest] = useState(null);
    const [previewFile, setPreviewFile] = useState(null); // { url, name, isPdf }

    // Form Pengajuan Cuti / Izin Baru
    const createForm = useForm({
        employee_id: employees?.[0]?.id || '',
        leave_type: 'Cuti Tahunan',
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
        reason: '',
        attachment_file: null,
        attachment_url: '',
    });

    // Form Penolakan Cuti / Izin
    const rejectForm = useForm({
        rejection_reason: '',
    });

    // Handle submit pengajuan cuti
    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createForm.post(route('hcm.leaves.store'), {
            forceFormData: true,
            onSuccess: () => {
                setIsCreateModalOpen(false);
                createForm.reset();
            },
        });
    };

    // Handle persetujuan cuti
    const handleApprove = (id) => {
        if (confirm('Setujui permohonan izin ini? Seluruh tanggal pada durasi izin akan otomatis tercatat di log presensi harian.')) {
            router.post(route('hcm.leaves.approve', id));
        }
    };

    // Buka modal tolak
    const openRejectModal = (req) => {
        setSelectedRequest(req);
        rejectForm.setData('rejection_reason', '');
        setIsRejectModalOpen(true);
    };

    // Handle submit penolakan
    const handleRejectSubmit = (e) => {
        e.preventDefault();
        if (!selectedRequest) return;

        rejectForm.post(route('hcm.leaves.reject', selectedRequest.id), {
            onSuccess: () => {
                setIsRejectModalOpen(false);
                setSelectedRequest(null);
            },
        });
    };

    // Handle hapus tiket
    const handleDelete = (id) => {
        if (confirm('Hapus tiket permohonan cuti ini?')) {
            router.delete(route('hcm.leaves.destroy', id));
        }
    };

    // Filter perubahan
    const handleApplyFilter = (newStatus = status, newType = leaveType, newSearch = search) => {
        router.get(
            route('hcm.leaves.index'),
            {
                status: newStatus,
                leave_type: newType,
                search: newSearch,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Format opsi karyawan untuk SearchableSelect
    const employeeOptions = (employees || []).map((emp) => ({
        value: emp.id,
        label: `${emp.name} (${emp.employee_code}) - ${emp.department}`,
    }));

    // Format opsi jenis cuti untuk SearchableSelect
    const leaveTypeOptions = [
        { value: 'all', label: 'Semua Kategori Izin' },
        ...(leaveTypes || []).map((t) => ({ value: t, label: t })),
    ];

    const leaveTypeFormOptions = (leaveTypes || []).map((t) => ({ value: t, label: t }));

    const statusOptions = [
        { value: 'all', label: 'Semua Status' },
        { value: 'PENDING_REVIEW', label: 'Menunggu Persetujuan' },
        { value: 'APPROVED', label: 'Disetujui' },
        { value: 'REJECTED', label: 'Ditolak' },
    ];

    // Status styling badge
    const STATUS_BADGES = {
        PENDING_REVIEW: {
            label: 'Menunggu Review',
            class: 'bg-amber-500/10 text-amber-600 border-amber-300 dark:border-amber-800',
            icon: Clock,
        },
        APPROVED: {
            label: 'Disetujui',
            class: 'bg-emerald-500/10 text-emerald-600 border-emerald-300 dark:border-emerald-800',
            icon: CheckCircle2,
        },
        REJECTED: {
            label: 'Ditolak',
            class: 'bg-rose-500/10 text-rose-600 border-rose-300 dark:border-rose-800',
            icon: XCircle,
        },
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <CalendarDays className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Cuti & Perizinan Karyawan
                    </span>
                </div>
            }
        >
            <Head title="Cuti & Perizinan - Kepegawaian" />

            <div className="space-y-4">
                {/* Header Banner Canvas */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <CalendarDays className="h-5 w-5 text-indigo-600 shrink-0" />
                            <span>Cuti & Perizinan Karyawan</span>
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Manajemen pengajuan cuti tahunan, izin sakit, dinas luar, dan persetujuan atasan.
                        </p>
                    </div>

                    <Button
                        onClick={() => setIsCreateModalOpen(true)}
                        size="sm"
                        className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5 shadow-xs shrink-0 self-start sm:self-auto"
                    >
                        <Plus className="h-3.5 w-3.5" />
                        <span>Ajukan Cuti / Izin Baru</span>
                    </Button>
                </div>
                {/* 1. Baris Metrik Ringkasan */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <Card className="border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/30 dark:bg-amber-950/20 shadow-sm p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <span className="text-xs text-amber-600 dark:text-amber-400 font-medium uppercase tracking-wider">
                                    Menunggu Persetujuan
                                </span>
                                <div className="text-2xl font-bold text-amber-700 dark:text-amber-300 mt-1">
                                    {metrics.total_pending || 0}
                                </div>
                            </div>
                            <Clock className="h-8 w-8 text-amber-500/40" />
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-sm p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <span className="text-xs text-emerald-600 dark:text-emerald-400 font-medium uppercase tracking-wider">
                                    Disetujui Bulan Ini
                                </span>
                                <div className="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">
                                    {metrics.approved_this_month || 0}
                                </div>
                            </div>
                            <CheckCircle2 className="h-8 w-8 text-emerald-500/40" />
                        </div>
                    </Card>

                    <Card className="border border-rose-200/80 dark:border-rose-900/40 bg-rose-50/30 dark:bg-rose-950/20 shadow-sm p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <span className="text-xs text-rose-600 dark:text-rose-400 font-medium uppercase tracking-wider">
                                    Ditolak Bulan Ini
                                </span>
                                <div className="text-2xl font-bold text-rose-700 dark:text-rose-300 mt-1">
                                    {metrics.rejected_this_month || 0}
                                </div>
                            </div>
                            <XCircle className="h-8 w-8 text-rose-500/40" />
                        </div>
                    </Card>
                </div>

                {/* 2. Filter Bar */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-3">
                    <div className="flex flex-col md:flex-row gap-3 items-center justify-between">
                        <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
                            {/* Filter Status SearchableSelect */}
                            <div className="flex items-center gap-2 min-w-[180px]">
                                <Label className="text-xs font-medium text-zinc-500 whitespace-nowrap">Status:</Label>
                                <SearchableSelect
                                    value={status}
                                    onValueChange={(val) => {
                                        setStatus(val);
                                        handleApplyFilter(val, leaveType, search);
                                    }}
                                    options={statusOptions}
                                    placeholder="Semua Status..."
                                    clearable={false}
                                    className="h-8 text-xs w-full"
                                />
                            </div>

                            {/* Filter Jenis Cuti SearchableSelect */}
                            <div className="flex items-center gap-2 min-w-[180px]">
                                <Label className="text-xs font-medium text-zinc-500 whitespace-nowrap">Kategori:</Label>
                                <SearchableSelect
                                    value={leaveType}
                                    onValueChange={(val) => {
                                        setLeaveType(val);
                                        handleApplyFilter(status, val, search);
                                    }}
                                    options={leaveTypeOptions}
                                    placeholder="Semua Kategori..."
                                    clearable={false}
                                    className="h-8 text-xs w-full"
                                />
                            </div>
                        </div>

                        {/* Search Box */}
                        <div className="relative w-full md:w-[260px]">
                            <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-zinc-400" />
                            <Input
                                placeholder="Cari nama karyawan / alasan..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleApplyFilter(status, leaveType, search);
                                    }
                                }}
                                className="h-8 pl-8 text-xs"
                            />
                        </div>
                    </div>
                </Card>

                {/* 3. Tabel Riwayat Pengajuan Cuti */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="min-w-[180px] text-xs font-bold">Karyawan</TableHead>
                                    <TableHead className="min-w-[130px] text-xs font-bold">Jenis Izin</TableHead>
                                    <TableHead className="min-w-[170px] text-xs font-bold">Periode Tanggal</TableHead>
                                    <TableHead className="min-w-[80px] text-xs font-bold text-center">Durasi</TableHead>
                                    <TableHead className="min-w-[200px] text-xs font-bold">Alasan / Catatan</TableHead>
                                    <TableHead className="min-w-[130px] text-xs font-bold text-center">Status</TableHead>
                                    <TableHead className="min-w-[140px] text-xs font-bold text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {leaveRequests.data && leaveRequests.data.length > 0 ? (
                                    leaveRequests.data.map((req) => {
                                        const badgeConfig = STATUS_BADGES[req.status] || STATUS_BADGES.PENDING_REVIEW;
                                        const BadgeIcon = badgeConfig.icon;

                                        return (
                                            <TableRow key={req.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                                {/* Karyawan Info */}
                                                <TableCell>
                                                    <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                                        {req.employee?.name}
                                                    </div>
                                                    <div className="text-[10px] text-zinc-400">
                                                        {req.employee?.employee_code} • {req.employee?.department}
                                                    </div>
                                                </TableCell>

                                                {/* Jenis Izin */}
                                                <TableCell>
                                                    <Badge variant="outline" className="text-xs font-medium">
                                                        {req.leave_type}
                                                    </Badge>
                                                </TableCell>

                                                {/* Periode Tanggal */}
                                                <TableCell>
                                                    <div className="text-xs font-mono font-medium text-zinc-700 dark:text-zinc-300">
                                                        {req.start_date}
                                                        {req.start_date !== req.end_date && ` s.d. ${req.end_date}`}
                                                    </div>
                                                </TableCell>

                                                {/* Durasi */}
                                                <TableCell className="text-center">
                                                    <Badge className="bg-indigo-500/10 text-indigo-600 border-indigo-200 text-xs">
                                                        {req.total_days} Hari
                                                    </Badge>
                                                </TableCell>

                                                {/* Alasan & Catatan */}
                                                <TableCell>
                                                    <div className="text-xs text-zinc-700 dark:text-zinc-300">
                                                        {req.reason}
                                                    </div>
                                                    {req.attachment_url && (
                                                        <div className="flex items-center gap-1.5 mt-1">
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    const isPdf = req.attachment_url.toLowerCase().endsWith('.pdf') || req.attachment_url.includes('.pdf');
                                                                    setPreviewFile({
                                                                        url: req.attachment_url,
                                                                        name: `Surat/Bukti - ${req.employee?.name} (${req.leave_type})`,
                                                                        isPdf: isPdf,
                                                                    });
                                                                }}
                                                                className="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:underline bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-800"
                                                            >
                                                                <Eye className="h-3 w-3" />
                                                                Pratinjau Berkas
                                                            </button>
                                                            <a
                                                                href={req.attachment_url}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="text-zinc-400 hover:text-zinc-600 p-0.5"
                                                                title="Buka di tab baru"
                                                            >
                                                                <ExternalLink className="h-3 w-3" />
                                                            </a>
                                                        </div>
                                                    )}
                                                    {req.status === 'REJECTED' && req.rejection_reason && (
                                                        <div className="mt-1 text-[11px] text-rose-500 italic bg-rose-50 dark:bg-rose-950/30 p-1.5 rounded">
                                                            Alasan Penolakan: {req.rejection_reason}
                                                        </div>
                                                    )}
                                                </TableCell>

                                                {/* Status Badge */}
                                                <TableCell className="text-center">
                                                    <Badge className={`text-xs gap-1 py-0.5 ${badgeConfig.class}`}>
                                                        <BadgeIcon className="h-3 w-3" />
                                                        {badgeConfig.label}
                                                    </Badge>
                                                    {req.reviewer && (
                                                        <div className="text-[10px] text-zinc-400 mt-1">
                                                            oleh {req.reviewer.name}
                                                        </div>
                                                    )}
                                                </TableCell>

                                                {/* Aksi Operasional */}
                                                <TableCell className="text-right">
                                                    {req.status === 'PENDING_REVIEW' ? (
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Button
                                                                size="sm"
                                                                onClick={() => handleApprove(req.id)}
                                                                className="h-7 px-2 text-xs bg-emerald-600 hover:bg-emerald-700 text-white gap-1"
                                                                title="Setujui dan sinkronkan ke presensi harian"
                                                            >
                                                                <Check className="h-3 w-3" />
                                                                Setujui
                                                            </Button>

                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => openRejectModal(req)}
                                                                className="h-7 px-2 text-xs border-rose-300 text-rose-600 hover:bg-rose-50 dark:border-rose-800 gap-1"
                                                                title="Tolak permohonan"
                                                            >
                                                                <X className="h-3 w-3" />
                                                                Tolak
                                                            </Button>

                                                            <Button
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() => handleDelete(req.id)}
                                                                className="h-7 px-1.5 text-zinc-400 hover:text-rose-600"
                                                                title="Hapus permohonan"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </Button>
                                                        </div>
                                                    ) : (
                                                        <span className="text-[11px] text-zinc-400 italic">Selesai Ditinjau</span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-32 text-center text-xs text-zinc-400">
                                            Belum ada permohonan Cuti / Izin yang tercatat.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {leaveRequests.links && leaveRequests.links.length > 3 && (
                        <div className="flex items-center justify-between p-3 border-t border-zinc-200 dark:border-zinc-800 text-xs">
                            <span className="text-zinc-500">
                                Menampilkan {leaveRequests.from || 0} - {leaveRequests.to || 0} dari {leaveRequests.total} data
                            </span>
                            <div className="flex gap-1">
                                {leaveRequests.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-2.5 py-1 rounded text-xs transition-colors ${
                                            link.active
                                                ? 'bg-indigo-600 text-white font-medium'
                                                : link.url
                                                ? 'hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400'
                                                : 'text-zinc-300 dark:text-zinc-700 pointer-events-none'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* MODAL 1: Ajukan Cuti / Izin Baru */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <CalendarDays className="h-4 w-4 text-indigo-600" />
                            Formulir Pengajuan Cuti / Izin / Sakit
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Pencatatan tiket permohonan ketidakhadiran kerja karyawan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCreateSubmit} className="space-y-3 pt-2">
                        {/* Pilih Karyawan */}
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Karyawan Pemohon *</Label>
                            <SearchableSelect
                                value={createForm.data.employee_id}
                                onValueChange={(val) => createForm.setData('employee_id', val)}
                                options={employeeOptions}
                                placeholder="Pilih / Cari Karyawan..."
                                clearable={false}
                                className="text-xs"
                            />
                        </div>

                        {/* Jenis Izin */}
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Kategori Izin *</Label>
                            <SearchableSelect
                                value={createForm.data.leave_type}
                                onValueChange={(val) => createForm.setData('leave_type', val)}
                                options={leaveTypeFormOptions}
                                placeholder="Pilih Kategori Izin..."
                                clearable={false}
                                className="text-xs"
                            />
                        </div>

                        {/* Tanggal Mulai & Tanggal Selesai */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Mulai *</Label>
                                <Input
                                    type="date"
                                    required
                                    value={createForm.data.start_date}
                                    onChange={(e) => createForm.setData('start_date', e.target.value)}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Selesai *</Label>
                                <Input
                                    type="date"
                                    required
                                    value={createForm.data.end_date}
                                    onChange={(e) => createForm.setData('end_date', e.target.value)}
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        {/* Alasan */}
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Alasan / Keperluan *</Label>
                            <Textarea
                                required
                                rows={2}
                                value={createForm.data.reason}
                                onChange={(e) => createForm.setData('reason', e.target.value)}
                                placeholder="e.g. Acara keluarga di luar kota / Sakit demam tinggi..."
                                className="text-xs"
                            />
                        </div>

                        {/* Upload Berkas Bukti / Surat Dokter */}
                        <div className="space-y-1.5 p-3 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <Label className="text-xs font-semibold flex items-center gap-1.5 text-zinc-800 dark:text-zinc-200">
                                <Upload className="h-3.5 w-3.5 text-indigo-500" />
                                Unggah Berkas Bukti / Surat Dokter Fisik (Opsional)
                            </Label>
                            <Input
                                type="file"
                                accept=".pdf,.png,.jpg,.jpeg"
                                onChange={(e) => {
                                    if (e.target.files && e.target.files[0]) {
                                        createForm.setData('attachment_file', e.target.files[0]);
                                    }
                                }}
                                className="text-xs file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:border-0 file:rounded file:px-2 file:py-1 hover:file:bg-indigo-100"
                            />
                            <p className="text-[10px] text-zinc-500">
                                Format: PDF, PNG, JPG (Maks. 5MB). Otomatis diarsipkan ke sistem storage & sinkronisasi Google Drive.
                            </p>

                            <div className="pt-1.5 border-t border-zinc-200/60 dark:border-zinc-800">
                                <Label className="text-[11px] text-zinc-500">Atau Tautan Berkas Eksternal (Opsional)</Label>
                                <Input
                                    value={createForm.data.attachment_url}
                                    onChange={(e) => createForm.setData('attachment_url', e.target.value)}
                                    placeholder="https://drive.google.com/..."
                                    className="text-xs h-7 mt-0.5"
                                />
                            </div>
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={createForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {createForm.processing ? 'Menyimpan...' : 'Ajukan Permohonan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 2: Tolak Permohonan Cuti */}
            <Dialog open={isRejectModalOpen} onOpenChange={setIsRejectModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2 text-rose-600">
                            <AlertCircle className="h-4 w-4" />
                            Tolak Permohonan Cuti / Izin
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Sesuai aturan operasional Blueprint HCM, Anda wajib memberikan alasan penolakan secara jelas.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleRejectSubmit} className="space-y-3 pt-2">
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Alasan Penolakan *</Label>
                            <Textarea
                                required
                                rows={3}
                                value={rejectForm.data.rejection_reason}
                                onChange={(e) => rejectForm.setData('rejection_reason', e.target.value)}
                                placeholder="e.g. Jadwal produksi padat / Kuota izin regu sewing telah maksimal..."
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsRejectModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={rejectForm.processing}
                                variant="destructive"
                                className="text-xs"
                            >
                                {rejectForm.processing ? 'Menolak...' : 'Konfirmasi Tolak Permohonan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 3: In-App Document Preview Modal */}
            <UniversalDocumentViewer
                isOpen={!!previewFile}
                onClose={() => setPreviewFile(null)}
                fileUrl={previewFile?.url}
                fileName={previewFile?.name || 'Bukti Surat Dokter / Izin'}
            />
        </AppLayout>
    );
}
