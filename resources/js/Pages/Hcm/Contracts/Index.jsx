import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    FileCheck,
    Calendar,
    AlertTriangle,
    Clock,
    Plus,
    Search,
    Edit2,
    Trash2,
    Eye,
    Upload,
    Building2,
    Briefcase,
    Shield,
    ExternalLink,
    X,
    Filter,
    FileText,
    CheckCircle2,
    XCircle,
    UserCheck,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import UniversalDocumentViewer from '@/Components/Hcm/UniversalDocumentViewer';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

export default function ContractIndex({ contracts, filters, metrics, dropdowns }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedEntity, setSelectedEntity] = useState(filters.legal_entity || 'all');
    const [selectedReviewStatus, setSelectedReviewStatus] = useState(filters.review_status || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.employment_status || 'all');

    // State Modals
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [editingContract, setEditingContract] = useState(null);
    const [isViewerOpen, setIsViewerOpen] = useState(false);
    const [viewingFileUrl, setViewingFileUrl] = useState(null);
    const [viewingFileName, setViewingFileName] = useState('');

    const form = useForm({
        employee_id: '',
        contract_number: '',
        contract_sequence: 1,
        employment_status: dropdowns.employment_statuses?.[0] || 'Kontrak (PKWT)',
        position: dropdowns.positions?.[0] || 'Operator Sewing',
        legal_entity: dropdowns.legal_entities?.[0] || '',
        duration_text: '1 Tahun',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        review_status: 'Aktif',
        file_contract: null,
        notes: '',
    });

    const applyFilters = (search = searchTerm, entity = selectedEntity, rev = selectedReviewStatus, stat = selectedStatus) => {
        router.get(
            route('hcm.contracts.index'),
            {
                search: search || undefined,
                legal_entity: entity !== 'all' ? entity : undefined,
                review_status: rev !== 'all' ? rev : undefined,
                employment_status: stat !== 'all' ? stat : undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        applyFilters(searchTerm);
    };

    const openCreateModal = () => {
        form.reset();
        form.setData({
            employee_id: dropdowns.employees?.[0]?.id || '',
            contract_number: '',
            contract_sequence: 1,
            employment_status: 'Kontrak (PKWT)',
            position: dropdowns.employees?.[0]?.position || dropdowns.positions?.[0] || '',
            legal_entity: dropdowns.employees?.[0]?.legal_entity || dropdowns.legal_entities?.[0] || '',
            duration_text: '1 Tahun',
            start_date: new Date().toISOString().split('T')[0],
            end_date: '',
            review_status: 'Aktif',
            file_contract: null,
            notes: '',
        });
        setEditingContract(null);
        setIsFormModalOpen(true);
    };

    const openEditModal = (contract) => {
        setEditingContract(contract);
        form.setData({
            employee_id: contract.employee_id,
            contract_number: contract.contract_number,
            contract_sequence: contract.contract_sequence,
            employment_status: contract.employment_status,
            position: contract.position,
            legal_entity: contract.legal_entity,
            duration_text: contract.duration_text,
            start_date: contract.start_date ? contract.start_date.split('T')[0] : '',
            end_date: contract.end_date ? contract.end_date.split('T')[0] : '',
            review_status: contract.review_status,
            file_contract: null,
            notes: contract.notes || '',
        });
        setIsFormModalOpen(true);
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        if (editingContract) {
            router.post(
                route('hcm.contracts.update', editingContract.id),
                {
                    ...form.data,
                    _method: 'PUT',
                },
                {
                    forceFormData: true,
                    onSuccess: () => {
                        setIsFormModalOpen(false);
                        setEditingContract(null);
                        form.reset();
                    },
                }
            );
        } else {
            form.post(route('hcm.contracts.store'), {
                forceFormData: true,
                onSuccess: () => {
                    setIsFormModalOpen(false);
                    form.reset();
                },
            });
        }
    };

    const handleDelete = (contract) => {
        if (confirm(`Yakin ingin menghapus berkas kontrak ${contract.contract_number}?`)) {
            router.delete(route('hcm.contracts.destroy', contract.id));
        }
    };

    const openDocumentViewer = (url, name) => {
        setViewingFileUrl(url);
        setViewingFileName(name);
        setIsViewerOpen(true);
    };

    // Helper options for SearchableSelect
    const toOptions = (items = []) => items.map((i) => (typeof i === 'string' ? { label: i, value: i } : i));

    const getReviewBadge = (status, days) => {
        if (status === 'Selesai Kontrak' || (days !== null && days < 0)) {
            return (
                <Badge className="bg-rose-500/10 text-rose-600 border-rose-200 gap-1 inline-flex items-center">
                    <XCircle className="h-3 w-3 shrink-0" />
                    <span>Kedaluwarsa</span>
                </Badge>
            );
        }
        if (days !== null && days <= 14) {
            return (
                <Badge className="bg-rose-500 text-white animate-pulse gap-1 inline-flex items-center">
                    <AlertTriangle className="h-3 w-3 shrink-0" />
                    <span>Sisa {days} Hari (Kritis)</span>
                </Badge>
            );
        }
        if (days !== null && days <= 30) {
            return (
                <Badge className="bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-300 gap-1 inline-flex items-center">
                    <Clock className="h-3 w-3 shrink-0" />
                    <span>Sisa {days} Hari (Review)</span>
                </Badge>
            );
        }
        if (days !== null && days <= 60) {
            return (
                <Badge className="bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-200 gap-1 inline-flex items-center">
                    <Clock className="h-3 w-3 shrink-0" />
                    <span>Sisa {days} Hari (Evaluasi)</span>
                </Badge>
            );
        }
        if (status === 'Aktif') {
            return (
                <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 gap-1 inline-flex items-center">
                    <CheckCircle2 className="h-3 w-3 shrink-0" />
                    <span>Aktif ({days ? `${days} hr` : 'Tetap'})</span>
                </Badge>
            );
        }
        return <Badge variant="outline">{status}</Badge>;
    };

    return (
        <AppLayout
            title="Master Karyawan (Data Kontrak & Legal)"
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <FileText className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm sm:text-base font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Master Karyawan (Data Kontrak & Legal PKWT)
                    </span>
                </div>
            }
        >
            <Head title="Master Karyawan - Data Kontrak & Legalitas PKWT" />

            <div className="space-y-5">
                {/* Banner Judul Modul */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-zinc-200/80 dark:border-zinc-800/80">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                            <FileText className="h-5 w-5" />
                        </div>
                        <div>
                            <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Master Karyawan: Data Kontrak & Legalitas PKWT
                            </h1>
                            <p className="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
                                Monitoring masa berlaku kontrak, sisa durasi PKWT, evaluasi perpanjangan, dan arsip naskah perjanjian kerja NISGroup.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            onClick={openCreateModal}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm flex items-center gap-2 text-xs sm:text-sm h-9"
                        >
                            <Plus className="h-4 w-4" />
                            <span>Terbitkan Kontrak Baru</span>
                        </Button>
                    </div>
                </div>

                {/* Metrik Statistik Kontrak */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-zinc-500">Total Naskah</CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">{metrics.total_contracts}</div>
                            <span className="text-[10px] text-zinc-400">Tercatat di sistem</span>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-emerald-600">Kontrak Berjalan</CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-xl font-bold text-emerald-600">{metrics.active_contracts}</div>
                            <span className="text-[10px] text-zinc-400">Status aktif saat ini</span>
                        </CardContent>
                    </Card>

                    <Card className="border border-amber-200 bg-amber-50/50 dark:bg-amber-950/20 dark:border-amber-900 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-amber-700 dark:text-amber-400">Habis dlm 30 Hari</CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-xl font-bold text-amber-700 dark:text-amber-400">{metrics.expiring_30_days}</div>
                            <span className="text-[10px] text-amber-600/80">Wajib review perpanjangan</span>
                        </CardContent>
                    </Card>

                    <Card className="border border-rose-200 bg-rose-50/50 dark:bg-rose-950/20 dark:border-rose-900 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-rose-700 dark:text-rose-400">Kritis H-14</CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-xl font-bold text-rose-700 dark:text-rose-400">{metrics.expiring_14_days}</div>
                            <span className="text-[10px] text-rose-600/80">Perlu keputusan segera</span>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-zinc-500">Kedaluwarsa</CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-xl font-bold text-zinc-500">{metrics.expired}</div>
                            <span className="text-[10px] text-zinc-400">Perjanjian telah lewat</span>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-indigo-600">Tetap (PKWTT)</CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-xl font-bold text-indigo-600">{metrics.permanent}</div>
                            <span className="text-[10px] text-zinc-400">Permanen tanpa batas</span>
                        </CardContent>
                    </Card>
                </div>

                {/* Quick Filter Chips Evaluasi Masa PKWT */}
                <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
                    <span className="text-zinc-400 font-medium text-[11px] shrink-0 mr-1">Filter Evaluasi:</span>
                    <button
                        type="button"
                        onClick={() => {
                            setSelectedReviewStatus('all');
                            applyFilters(searchTerm, selectedEntity, 'all', selectedStatus);
                        }}
                        className={`px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'all'
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                                : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800'
                        }`}
                    >
                        Semua Status
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            const val = 'Masa Tenggang H-14';
                            setSelectedReviewStatus(val);
                            applyFilters(searchTerm, selectedEntity, val, selectedStatus);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'Masa Tenggang H-14'
                                ? 'bg-rose-600 text-white border-rose-600 shadow-xs'
                                : 'bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900 hover:bg-rose-100/60'
                        }`}
                    >
                        <AlertTriangle className="h-3 w-3 shrink-0" />
                        <span>Kritis H-14</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            const val = 'Wajib Review H-30';
                            setSelectedReviewStatus(val);
                            applyFilters(searchTerm, selectedEntity, val, selectedStatus);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'Wajib Review H-30'
                                ? 'bg-amber-600 text-white border-amber-600 shadow-xs'
                                : 'bg-amber-50/50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900 hover:bg-amber-100/60'
                        }`}
                    >
                        <Clock className="h-3 w-3 shrink-0" />
                        <span>Review H-30</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            const val = 'Mendekati H-60';
                            setSelectedReviewStatus(val);
                            applyFilters(searchTerm, selectedEntity, val, selectedStatus);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'Mendekati H-60'
                                ? 'bg-sky-600 text-white border-sky-600 shadow-xs'
                                : 'bg-sky-50/50 dark:bg-sky-950/20 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-900 hover:bg-sky-100/60'
                        }`}
                    >
                        <Clock className="h-3 w-3 shrink-0" />
                        <span>Evaluasi H-60</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            const val = 'Aktif';
                            setSelectedReviewStatus(val);
                            applyFilters(searchTerm, selectedEntity, val, selectedStatus);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'Aktif'
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                : 'bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900 hover:bg-emerald-100/60'
                        }`}
                    >
                        <CheckCircle2 className="h-3 w-3 shrink-0" />
                        <span>Aktif Berjalan</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            const val = 'Tetap (PKWTT)';
                            setSelectedStatus(val);
                            applyFilters(searchTerm, selectedEntity, selectedReviewStatus, val);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedStatus === 'Tetap (PKWTT)'
                                ? 'bg-purple-600 text-white border-purple-600 shadow-xs'
                                : 'bg-purple-50/50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-900 hover:bg-purple-100/60'
                        }`}
                    >
                        <Briefcase className="h-3 w-3 shrink-0" />
                        <span>Tetap (PKWTT)</span>
                    </button>
                </div>

                {/* Filter Toolbar */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <CardContent className="p-3.5">
                        <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row items-center gap-2.5">
                            <div className="relative flex-1 w-full">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                                <Input
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Cari no kontrak, nama karyawan, NIK..."
                                    className="pl-9 h-9 text-xs"
                                />
                            </div>

                            <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                <SearchableSelect
                                    value={selectedEntity}
                                    onValueChange={(val) => {
                                        setSelectedEntity(val);
                                        applyFilters(searchTerm, val, selectedReviewStatus, selectedStatus);
                                    }}
                                    options={[{ label: 'Semua Badan Usaha (CV)', value: 'all' }, ...toOptions(dropdowns.legal_entities)]}
                                    placeholder="Semua CV"
                                    className="h-9 w-44 text-xs"
                                    clearable={false}
                                />

                                <SearchableSelect
                                    value={selectedReviewStatus}
                                    onValueChange={(val) => {
                                        setSelectedReviewStatus(val);
                                        applyFilters(searchTerm, selectedEntity, val, selectedStatus);
                                    }}
                                    options={[{ label: 'Semua Status Review', value: 'all' }, ...toOptions(dropdowns.review_statuses)]}
                                    placeholder="Status Review"
                                    className="h-9 w-40 text-xs"
                                    clearable={false}
                                />

                                <Button type="submit" variant="secondary" size="sm" className="h-9 text-xs gap-1.5">
                                    <Filter className="h-3.5 w-3.5" />
                                    Filter
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Tabel Kontrak */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-xs text-left">
                            <thead className="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400">
                                <tr>
                                    <th className="py-3 px-3.5 font-semibold">No. Kontrak & Urutan</th>
                                    <th className="py-3 px-3.5 font-semibold">Karyawan Terikat</th>
                                    <th className="py-3 px-3.5 font-semibold">Badan Usaha & Posisi</th>
                                    <th className="py-3 px-3.5 font-semibold">Masa Berlaku</th>
                                    <th className="py-3 px-3.5 font-semibold text-center">Status & Countdown</th>
                                    <th className="py-3 px-3.5 font-semibold text-center">Berkas Naskah</th>
                                    <th className="py-3 px-3.5 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800">
                                {contracts.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="py-10 text-center text-zinc-400">
                                            Tidak ada data naskah kontrak kerja yang sesuai dengan filter.
                                        </td>
                                    </tr>
                                ) : (
                                    contracts.data.map((c) => (
                                        <tr key={c.id} className="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition">
                                            <td className="py-3 px-3.5">
                                                <div className="font-mono font-semibold text-zinc-900 dark:text-zinc-100">{c.contract_number}</div>
                                                <div className="text-[11px] text-zinc-500 flex flex-wrap items-center gap-1.5 mt-1">
                                                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                        Kontrak Ke-{c.contract_sequence}
                                                    </span>
                                                    <span>•</span>
                                                    <span>{c.duration_text}</span>
                                                    {c.trainee_start_month && (
                                                        <span className="text-[10px] text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-900/50">
                                                            Trainee: {c.trainee_start_month} - {c.trainee_end_month}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>

                                            <td className="py-3 px-3.5">
                                                <div className="flex items-center gap-2.5">
                                                    {c.employee?.photo_url ? (
                                                        <img
                                                            src={c.employee.photo_url}
                                                            alt={c.employee.name}
                                                            className="w-8 h-8 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700"
                                                        />
                                                    ) : (
                                                        <div className="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center text-xs">
                                                            {c.employee?.name?.charAt(0) || 'E'}
                                                        </div>
                                                    )}
                                                    <div>
                                                        <div className="font-medium text-zinc-900 dark:text-zinc-100">{c.employee?.name || '-'}</div>
                                                        <div className="text-[11px] font-mono text-zinc-400">{c.employee?.employee_code || '-'}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td className="py-3 px-3.5">
                                                <div className="font-medium text-zinc-800 dark:text-zinc-200">{c.legal_entity}</div>
                                                <div className="text-[11px] text-zinc-500">{c.position}</div>
                                            </td>

                                            <td className="py-3 px-3.5">
                                                <div className="flex items-center gap-1 font-mono text-zinc-700 dark:text-zinc-300">
                                                    <Calendar className="h-3 w-3 text-zinc-400 shrink-0" />
                                                    <span>{c.start_date ? new Date(c.start_date).toLocaleDateString('id-ID') : '-'}</span>
                                                    <span>s/d</span>
                                                    <span>{c.end_date ? new Date(c.end_date).toLocaleDateString('id-ID') : 'Seterusnya (Tetap)'}</span>
                                                </div>
                                            </td>

                                            <td className="py-3 px-3.5 text-center">
                                                {getReviewBadge(c.review_status, c.days_remaining)}
                                            </td>

                                            <td className="py-3 px-3.5 text-center">
                                                {c.file_contract_url ? (
                                                    <button
                                                        type="button"
                                                        onClick={() => openDocumentViewer(c.file_contract_url, `Kontrak ${c.contract_number}`)}
                                                        className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition"
                                                    >
                                                        <Eye className="h-3 w-3" />
                                                        <span>Lihat Berkas</span>
                                                    </button>
                                                ) : (
                                                    <label
                                                        className="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-medium text-zinc-500 hover:text-zinc-800 border border-dashed border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition"
                                                        title="Upload Berkas Scan Kontrak Fisik"
                                                    >
                                                        <Upload className="h-3 w-3" />
                                                        <span>Upload Berkas</span>
                                                        <input
                                                            type="file"
                                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                            className="hidden"
                                                            onChange={(e) => {
                                                                if (e.target.files?.[0]) {
                                                                    const formData = new FormData();
                                                                    formData.append('file_contract', e.target.files[0]);
                                                                    router.post(route('hcm.contracts.upload', c.id), formData, {
                                                                        forceFormData: true,
                                                                        preserveScroll: true,
                                                                    });
                                                                }
                                                            }}
                                                        />
                                                    </label>
                                                )}
                                            </td>

                                            <td className="py-3 px-3.5 text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => openEditModal(c)}
                                                        className="p-1 rounded text-zinc-500 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                                                        title="Edit Data Kontrak"
                                                    >
                                                        <Edit2 className="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(c)}
                                                        className="p-1 rounded text-zinc-500 hover:text-rose-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                                                        title="Hapus Kontrak"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {contracts.links && contracts.links.length > 3 && (
                        <div className="p-3 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between text-xs">
                            <span className="text-zinc-500">
                                Menampilkan {contracts.from || 0} - {contracts.to || 0} dari {contracts.total} berkas
                            </span>
                            <div className="flex gap-1">
                                {contracts.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        disabled={!link.url || link.active}
                                        onClick={() => link.url && router.visit(link.url, { preserveState: true })}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-2.5 py-1 rounded text-xs transition ${
                                            link.active
                                                ? 'bg-indigo-600 text-white font-bold'
                                                : link.url
                                                ? 'bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700'
                                                : 'text-zinc-400 opacity-50 cursor-not-allowed'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* MODAL FORM: Tambah / Edit Kontrak */}
            <Dialog open={isFormModalOpen} onOpenChange={setIsFormModalOpen}>
                <DialogContent className="max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileCheck className="h-5 w-5 text-indigo-600" />
                            {editingContract ? 'Perbarui Naskah Kontrak Kerja' : 'Terbitkan Kontrak Kerja Baru'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Lengkapi rincian perjanjian kerja waktu tertentu (PKWT) atau tetap (PKWTT) beserta upload scan dokumen fisik.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleFormSubmit} className="space-y-4 pt-2">
                        {/* Karyawan Terikat */}
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Karyawan Terikat *</Label>
                            <SearchableSelect
                                value={form.data.employee_id}
                                onValueChange={(val) => {
                                    form.setData('employee_id', val);
                                    const selectedEmp = dropdowns.employees?.find((e) => e.id.toString() === val.toString());
                                    if (selectedEmp) {
                                        form.setData({
                                            ...form.data,
                                            employee_id: val,
                                            position: selectedEmp.position || form.data.position,
                                            legal_entity: selectedEmp.legal_entity || form.data.legal_entity,
                                        });
                                    }
                                }}
                                options={(dropdowns.employees || []).map((e) => ({
                                    label: `${e.name} (${e.employee_code}) - ${e.department}`,
                                    value: e.id,
                                }))}
                                placeholder="Pilih Karyawan..."
                                clearable={false}
                                className="text-xs"
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Nomor Kontrak Resmi *</Label>
                                <Input
                                    value={form.data.contract_number}
                                    onChange={(e) => form.setData('contract_number', e.target.value)}
                                    placeholder="e.g. 012/OWR/PKWT/IX/2026"
                                    required
                                    className="text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Urutan Kontrak Ke- *</Label>
                                <Input
                                    type="number"
                                    min="1"
                                    value={form.data.contract_sequence}
                                    onChange={(e) => form.setData('contract_sequence', e.target.value)}
                                    required
                                    className="text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Badan Usaha / CV *</Label>
                                <SearchableSelect
                                    value={form.data.legal_entity}
                                    onValueChange={(val) => form.setData('legal_entity', val)}
                                    options={toOptions(dropdowns.legal_entities)}
                                    placeholder="Pilih CV..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Posisi / Jabatan *</Label>
                                <SearchableSelect
                                    value={form.data.position}
                                    onValueChange={(val) => form.setData('position', val)}
                                    options={toOptions(dropdowns.positions)}
                                    placeholder="Pilih Posisi..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Status Kontrak *</Label>
                                <SearchableSelect
                                    value={form.data.employment_status}
                                    onValueChange={(val) => form.setData('employment_status', val)}
                                    options={toOptions(dropdowns.employment_statuses)}
                                    placeholder="Pilih Status..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Masa Durasi *</Label>
                                <Input
                                    value={form.data.duration_text}
                                    onChange={(e) => form.setData('duration_text', e.target.value)}
                                    placeholder="e.g. 1 Tahun, Tetap"
                                    required
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Status Review *</Label>
                                <SearchableSelect
                                    value={form.data.review_status}
                                    onValueChange={(val) => form.setData('review_status', val)}
                                    options={toOptions(dropdowns.review_statuses)}
                                    placeholder="Pilih Review..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Mulai Efektif *</Label>
                                <Input
                                    type="date"
                                    value={form.data.start_date}
                                    onChange={(e) => form.setData('start_date', e.target.value)}
                                    required
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Berakhir (Kosongkan jika Tetap)</Label>
                                <Input
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(e) => form.setData('end_date', e.target.value)}
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        {/* File Upload Box Fisik */}
                        <div className="space-y-1.5 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                            <Label className="text-xs font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <Upload className="h-3.5 w-3.5 text-indigo-600" />
                                Berkas Scan Naskah Kontrak Fisik (PDF / Gambar)
                            </Label>
                            <Input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                onChange={(e) => form.setData('file_contract', e.target.files[0])}
                                className="text-xs file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            <p className="text-[11px] text-zinc-500">
                                Berkas akan disimpan secara aman dan otomatis disinkronkan ke Google Drive subfolder Kontrak PKWT.
                            </p>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Catatan Khusus</Label>
                            <Textarea
                                rows={2}
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                                placeholder="Klausul khusus, evaluasi masa probation, dll..."
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsFormModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {form.processing ? 'Menyimpan...' : editingContract ? 'Simpan Perubahan' : 'Terbitkan Kontrak'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL IN-APP DOCUMENT VIEWER */}
            <UniversalDocumentViewer
                isOpen={isViewerOpen}
                onClose={() => setIsViewerOpen(false)}
                fileUrl={viewingFileUrl}
                fileName={`Kontrak: ${viewingFileName}`}
            />
        </AppLayout>
    );
}
