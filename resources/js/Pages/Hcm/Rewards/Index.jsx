import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Award,
    Plus,
    Search,
    Filter,
    Calendar,
    DollarSign,
    CheckCircle2,
    Clock,
    ExternalLink,
    Edit2,
    Trash2,
    Gift,
    Sparkles,
    UserCheck,
    FileText,
    FileSpreadsheet,
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

export default function RewardsIndex({
    rewards,
    filters,
    metrics,
    employees,
    distributionStatuses,
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [yearFilter, setYearFilter] = useState(filters.year || 'all');

    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [editingReward, setEditingReward] = useState(null);

    const currentYear = new Date().getFullYear();
    const yearOptions = [
        { label: 'Semua Tahun', value: 'all' },
        { label: `${currentYear + 1}`, value: `${currentYear + 1}` },
        { label: `${currentYear}`, value: `${currentYear}` },
        { label: `${currentYear - 1}`, value: `${currentYear - 1}` },
        { label: `${currentYear - 2}`, value: `${currentYear - 2}` },
    ];

    const employeeOptions = employees.map((emp) => ({
        label: `${emp.name} (${emp.employee_code}) - ${emp.department || 'Umum'}`,
        value: emp.id.toString(),
    }));

    const statusOptions = [
        { label: 'Semua Status Penyaluran', value: 'all' },
        ...distributionStatuses.map((st) => ({ label: st, value: st })),
    ];

    // Form Tambah / Edit Reward
    const form = useForm({
        employee_id: '',
        reward_name: '',
        reward_year: currentYear,
        distribution_status: 'Belum Diterima',
        received_date: '',
        document_status: 'Berita Acara Terlampir',
        budget_amount: 0,
        proof_url: '',
        notes: '',
    });

    const formatRp = (val) => {
        if (!val) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val);
    };

    const handleFilterChange = (newSearch, newStatus, newYear) => {
        router.get(
            route('hcm.rewards.index'),
            {
                search: newSearch,
                status: newStatus,
                year: newYear,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const openAddModal = () => {
        setEditingReward(null);
        form.reset();
        form.setData({
            employee_id: employees.length > 0 ? employees[0].id.toString() : '',
            reward_name: '',
            reward_year: currentYear,
            distribution_status: 'Belum Diterima',
            received_date: '',
            document_status: 'Berita Acara Terlampir',
            budget_amount: 0,
            proof_url: '',
            notes: '',
        });
        setIsFormModalOpen(true);
    };

    const openEditModal = (reward) => {
        setEditingReward(reward);
        form.setData({
            employee_id: reward.employee_id.toString(),
            reward_name: reward.reward_name,
            reward_year: reward.reward_year,
            distribution_status: reward.distribution_status,
            received_date: reward.received_date || '',
            document_status: reward.document_status || 'Berita Acara Terlampir',
            budget_amount: reward.budget_amount || 0,
            proof_url: reward.proof_url || '',
            notes: reward.notes || '',
        });
        setIsFormModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingReward) {
            form.put(route('hcm.rewards.update', editingReward.uuid || editingReward.id), {
                onSuccess: () => {
                    setIsFormModalOpen(false);
                    form.reset();
                },
            });
        } else {
            form.post(route('hcm.rewards.store'), {
                onSuccess: () => {
                    setIsFormModalOpen(false);
                    form.reset();
                },
            });
        }
    };

    const handleDelete = (reward) => {
        if (confirm(`Hapus catatan reward "${reward.reward_name}" untuk ${reward.employee?.name}?`)) {
            router.delete(route('hcm.rewards.destroy', reward.uuid || reward.id));
        }
    };

    const getStatusBadge = (st) => {
        if (st === 'Sudah Diterima (Serah Terima Langsung)') {
            return (
                <Badge className="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-[11px] font-semibold">
                    <CheckCircle2 className="w-3 h-3 mr-1" /> Serah Terima
                </Badge>
            );
        }
        if (st === 'Sudah Ditransfer') {
            return (
                <Badge className="bg-sky-500/15 text-sky-600 dark:text-sky-400 border border-sky-500/30 text-[11px] font-semibold">
                    <CheckCircle2 className="w-3 h-3 mr-1" /> Ditransfer
                </Badge>
            );
        }
        if (st === 'Tertunda / Pending') {
            return (
                <Badge className="bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-[11px] font-semibold">
                    <Clock className="w-3 h-3 mr-1" /> Tertunda
                </Badge>
            );
        }
        if (st === 'Dibatalkan') {
            return (
                <Badge variant="outline" className="border-rose-300 text-rose-600 text-[11px]">
                    Dibatalkan
                </Badge>
            );
        }
        return (
            <Badge variant="outline" className="border-zinc-300 text-zinc-600 text-[11px]">
                Belum Diterima
            </Badge>
        );
    };

    return (
        <AppLayout title="Reward & Apresiasi Karyawan">
            <Head title="Reward & Apresiasi Karyawan" />

            <div className="space-y-6">
                {/* 1. Header & Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                <Award className="w-6 h-6" />
                            </div>
                            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Reward & Apresiasi Karyawan
                            </h1>
                        </div>
                        <p className="text-xs sm:text-sm text-zinc-500 mt-1">
                            Pencatatan penghargaan, sertifikat prestasi, bonus loyalitas, dan berita acara penyerahan reward.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={() => { window.location.href = route('hcm.rewards.export', { status: statusFilter, year: yearFilter, search }); }}
                            className="gap-1.5 text-xs font-semibold"
                        >
                            <FileSpreadsheet className="w-4 h-4" />
                            Export Excel
                        </Button>
                        <Button
                            onClick={openAddModal}
                            className="bg-blue-600 hover:bg-blue-700 text-white gap-1.5 text-xs font-semibold shadow-sm"
                        >
                            <Plus className="w-4 h-4" />
                            Catat Reward Baru
                        </Button>
                    </div>
                </div>

                {/* 2. KPI Metrics Grid */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <Award className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Total Penghargaan</div>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {metrics.total_rewards} Kegiatan
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
                                <div className="text-xs text-zinc-500">Telah Diserahkan / Transfer</div>
                                <div className="text-xl font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    {(metrics.delivered || 0) + (metrics.transferred || 0)}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                                <Clock className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Pending / Tertunda</div>
                                <div className="text-xl font-bold text-amber-600 dark:text-amber-400 font-mono">
                                    {metrics.pending}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600">
                                <DollarSign className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Total Anggaran Reward</div>
                                <div className="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {formatRp(metrics.total_budget)}
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
                                placeholder="Cari reward / nama karyawan..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleFilterChange(search, statusFilter, yearFilter);
                                    }
                                }}
                                className="pl-9 text-xs h-9"
                            />
                        </div>

                        <div className="w-full sm:w-44">
                            <SearchableSelect
                                value={yearFilter}
                                onValueChange={(val) => {
                                    setYearFilter(val);
                                    handleFilterChange(search, statusFilter, val);
                                }}
                                options={yearOptions}
                                placeholder="Pilih Tahun"
                                className="h-9 text-xs"
                            />
                        </div>

                        <div className="w-full sm:w-56">
                            <SearchableSelect
                                value={statusFilter}
                                onValueChange={(val) => {
                                    setStatusFilter(val);
                                    handleFilterChange(search, val, yearFilter);
                                }}
                                options={statusOptions}
                                placeholder="Status Distribusi"
                                className="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <div className="text-xs text-zinc-500 self-end sm:self-center">
                        Total {rewards.total || 0} data tercatat
                    </div>
                </div>

                {/* 4. Tabel Daftar Reward */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                <TableRow>
                                    <TableHead className="w-[40px] text-center text-xs font-bold">No</TableHead>
                                    <TableHead className="min-w-[190px] text-xs font-bold">Karyawan</TableHead>
                                    <TableHead className="min-w-[170px] text-xs font-bold">Nama Apresiasi / Reward</TableHead>
                                    <TableHead className="min-w-[80px] text-center text-xs font-bold">Tahun</TableHead>
                                    <TableHead className="min-w-[130px] text-right text-xs font-bold">Nominal / Nilai</TableHead>
                                    <TableHead className="min-w-[140px] text-center text-xs font-bold">Status Penyaluran</TableHead>
                                    <TableHead className="min-w-[110px] text-xs font-bold">Tanggal Diterima</TableHead>
                                    <TableHead className="min-w-[150px] text-xs font-bold">Dokumen / Bukti</TableHead>
                                    <TableHead className="w-[90px] text-right text-xs font-bold">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rewards.data && rewards.data.length > 0 ? (
                                    rewards.data.map((item, idx) => (
                                        <TableRow key={item.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                            <TableCell className="text-center text-xs text-zinc-400 font-mono">
                                                {(rewards.current_page - 1) * rewards.per_page + idx + 1}
                                            </TableCell>

                                            <TableCell>
                                                <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                                    {item.employee?.name}
                                                </div>
                                                <div className="text-[10px] text-zinc-400">
                                                    {item.employee?.employee_code} • {item.employee?.department || '-'}
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div className="font-semibold text-xs text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                                                    <Gift className="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                                    {item.reward_name}
                                                </div>
                                                {item.notes && (
                                                    <div className="text-[10px] text-zinc-400 line-clamp-1 italic mt-0.5">
                                                        {item.notes}
                                                    </div>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-center text-xs font-mono font-bold">
                                                {item.reward_year}
                                            </TableCell>

                                            <TableCell className="text-right text-xs font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                                {item.budget_amount > 0 ? formatRp(item.budget_amount) : 'Non-Finansial'}
                                            </TableCell>

                                            <TableCell className="text-center">
                                                {getStatusBadge(item.distribution_status)}
                                            </TableCell>

                                            <TableCell className="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                                                {item.received_date || '-'}
                                            </TableCell>

                                            <TableCell>
                                                <div className="space-y-1">
                                                    <div className="text-[11px] text-zinc-600 dark:text-zinc-400">
                                                        {item.document_status || 'Belum Ada'}
                                                    </div>
                                                    {item.proof_url && (
                                                        <a
                                                            href={item.proof_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center gap-1 text-[10px] text-blue-600 hover:underline"
                                                        >
                                                            <ExternalLink className="w-3 h-3" />
                                                            Lihat Bukti
                                                        </a>
                                                    )}
                                                </div>
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-zinc-500 hover:text-blue-600"
                                                        onClick={() => openEditModal(item)}
                                                    >
                                                        <Edit2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-zinc-500 hover:text-rose-600"
                                                        onClick={() => handleDelete(item)}
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
                                            Belum ada catatan reward dan apresiasi karyawan.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {rewards.links && rewards.links.length > 3 && (
                        <div className="p-4 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                            <div className="text-xs text-zinc-500">
                                Halaman {rewards.current_page} dari {rewards.last_page}
                            </div>
                            <div className="flex gap-1">
                                {rewards.links.map((link, i) => (
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

                {/* 5. Modal Tambah / Edit Reward */}
                <Dialog open={isFormModalOpen} onOpenChange={setIsFormModalOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <Award className="w-5 h-5 text-amber-500" />
                                {editingReward ? 'Edit Data Apresiasi' : 'Catat Reward & Apresiasi Baru'}
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Masukkan rincian penghargaan dan berita acara penyerahan.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSubmit} className="space-y-3.5 pt-1">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Penerima Apresiasi (Karyawan) *</Label>
                                <SearchableSelect
                                    value={form.data.employee_id}
                                    onValueChange={(val) => form.setData('employee_id', val)}
                                    options={employeeOptions}
                                    placeholder="Pilih atau cari karyawan..."
                                />
                                {form.errors.employee_id && (
                                    <p className="text-[11px] text-rose-500">{form.errors.employee_id}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Nama / Jenis Apresiasi *</Label>
                                <Input
                                    value={form.data.reward_name}
                                    onChange={(e) => form.setData('reward_name', e.target.value)}
                                    placeholder="Contoh: Karyawan Teladan Semester 1 / Sertifikat Dedikasi"
                                    className="text-xs"
                                    required
                                />
                                {form.errors.reward_name && (
                                    <p className="text-[11px] text-rose-500">{form.errors.reward_name}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Tahun Penghargaan *</Label>
                                    <Input
                                        type="number"
                                        value={form.data.reward_year}
                                        onChange={(e) => form.setData('reward_year', parseInt(e.target.value))}
                                        className="text-xs font-mono"
                                        required
                                    />
                                    {form.errors.reward_year && (
                                        <p className="text-[11px] text-rose-500">{form.errors.reward_year}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Nominal / Nilai Hadiah (Rp)</Label>
                                    <Input
                                        type="number"
                                        value={form.data.budget_amount}
                                        onChange={(e) => form.setData('budget_amount', parseFloat(e.target.value) || 0)}
                                        placeholder="0 jika non-finansial"
                                        className="text-xs font-mono"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Status Penyaluran *</Label>
                                    <SearchableSelect
                                        value={form.data.distribution_status}
                                        onValueChange={(val) => form.setData('distribution_status', val)}
                                        options={distributionStatuses.map((st) => ({ label: st, value: st }))}
                                        placeholder="Pilih status"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Tanggal Diterima</Label>
                                    <Input
                                        type="date"
                                        value={form.data.received_date}
                                        onChange={(e) => form.setData('received_date', e.target.value)}
                                        className="text-xs"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Status Dokumen</Label>
                                    <Input
                                        value={form.data.document_status}
                                        onChange={(e) => form.setData('document_status', e.target.value)}
                                        placeholder="Berita Acara Terlampir"
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Tautan Dokumen / Foto Bukti</Label>
                                    <Input
                                        value={form.data.proof_url}
                                        onChange={(e) => form.setData('proof_url', e.target.value)}
                                        placeholder="https://drive.google.com/..."
                                        className="text-xs"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Catatan / Keterangan</Label>
                                <Textarea
                                    value={form.data.notes}
                                    onChange={(e) => form.setData('notes', e.target.value)}
                                    placeholder="Prestasi khusus, alasan penghargaan, atau catatan serah terima..."
                                    rows={2}
                                    className="text-xs"
                                />
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
                                    {form.processing ? 'Menyimpan...' : (editingReward ? 'Perbarui Data' : 'Simpan Data')}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
