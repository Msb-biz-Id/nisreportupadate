import { Head, router, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
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

    useEffect(() => {
        setSearchTerm(filters.search || '');
        setSelectedEntity(filters.legal_entity || 'all');
        setSelectedReviewStatus(filters.review_status || 'all');
        setSelectedStatus(filters.employment_status || 'all');
    }, [filters]);

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
        trainee_start_month: '',
        trainee_end_month: '',
        contract_month: '',
        start_year: new Date().getFullYear(),
        end_year: new Date().getFullYear() + 1,
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
        const firstEmp = dropdowns.employees?.[0];
        const now = new Date();
        const startY = now.getFullYear();
        const indonesianMonths = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        form.setData({
            employee_id: firstEmp?.id || '',
            contract_number: '',
            contract_sequence: 1,
            employment_status: firstEmp?.employment_status || 'Kontrak (PKWT)',
            position: firstEmp?.position || dropdowns.positions?.[0] || '',
            legal_entity: firstEmp?.legal_entity || dropdowns.legal_entities?.[0] || '',
            duration_text: '1 Tahun',
            trainee_start_month: '',
            trainee_end_month: '',
            contract_month: indonesianMonths[now.getMonth()],
            start_year: startY,
            end_year: startY + 1,
            start_date: now.toISOString().split('T')[0],
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
            trainee_start_month: contract.trainee_start_month || '',
            trainee_end_month: contract.trainee_end_month || '',
            contract_month: contract.contract_month || '',
            start_year: contract.start_year || (contract.start_date ? new Date(contract.start_date).getFullYear() : ''),
            end_year: contract.end_year || (contract.end_date ? new Date(contract.end_date).getFullYear() : ''),
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
                route('hcm.contracts.update', editingContract.uuid || editingContract.id),
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
            router.delete(route('hcm.contracts.destroy', contract.uuid || contract.id));
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
        if (status === 'Perpanjang' || status?.includes('Perpanjang')) {
            return (
                <Badge className="bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-200 gap-1 inline-flex items-center">
                    <CheckCircle2 className="h-3 w-3 shrink-0" />
                    <span>Perpanjang Kontrak</span>
                </Badge>
            );
        }
        if (status === 'Diputus' || status?.includes('Diputus')) {
            return (
                <Badge className="bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 gap-1 inline-flex items-center">
                    <XCircle className="h-3 w-3 shrink-0" />
                    <span>Diputus</span>
                </Badge>
            );
        }
        if (status === 'Selesai Kontrak' || (days !== null && days < 0)) {
            return (
                <Badge className="bg-rose-500/10 text-rose-600 border-rose-200 gap-1 inline-flex items-center">
                    <XCircle className="h-3 w-3 shrink-0" />
                    <span>Kedaluwarsa</span>
                </Badge>
            );
        }
        if (days === null || days === undefined) {
            return (
                <Badge className="bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-200 gap-1 inline-flex items-center">
                    <Briefcase className="h-3 w-3 shrink-0" />
                    <span>Tetap (PKWTT)</span>
                </Badge>
            );
        }
        if (days <= 14 || status?.includes('H-14') || status?.includes('Kritis')) {
            return (
                <Badge className="bg-rose-500 text-white animate-pulse gap-1 inline-flex items-center">
                    <AlertTriangle className="h-3 w-3 shrink-0" />
                    <span>Kritis (H-14)</span>
                </Badge>
            );
        }
        if (days <= 30 || status?.includes('H-30') || status?.includes('Review')) {
            return (
                <Badge className="bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-300 gap-1 inline-flex items-center">
                    <Clock className="h-3 w-3 shrink-0" />
                    <span>Wajib Review (H-30)</span>
                </Badge>
            );
        }
        if (days <= 60 || status?.includes('H-60') || status?.includes('Evaluasi')) {
            return (
                <Badge className="bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-200 gap-1 inline-flex items-center">
                    <Clock className="h-3 w-3 shrink-0" />
                    <span>Evaluasi (H-60)</span>
                </Badge>
            );
        }
        if (status === 'Aktif' || status?.includes('Aktif') || days > 60) {
            return (
                <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 gap-1 inline-flex items-center">
                    <CheckCircle2 className="h-3 w-3 shrink-0" />
                    <span>Aman (Aktif)</span>
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
                        2. Master Karyawan (Data Kontrak &amp; Legal)
                    </span>
                </div>
            }
        >
            <Head title="Master Karyawan (Data Kontrak & Legal)" />

            <div className="space-y-5">
                {/* Banner Judul Modul */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-zinc-200/80 dark:border-zinc-800/80">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                            <FileText className="h-5 w-5" />
                        </div>
                        <div>
                            <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                2. Master Karyawan (Data Kontrak &amp; Legal)
                            </h1>
                            <p className="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
                                <span className="font-semibold text-indigo-600 dark:text-indigo-400">Kategori: Data Kontrak Karyawan NISGroup (Masa Kontrak PKWT)</span> — Monitoring masa kontrak, sisa durasi PKWT, evaluasi perpanjangan, dan naskah perjanjian kerja.
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
                            setSelectedStatus('all');
                            applyFilters(searchTerm, selectedEntity, 'all', 'all');
                        }}
                        className={`px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'all' && selectedStatus === 'all'
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs font-semibold'
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
                            setSelectedStatus('all');
                            applyFilters(searchTerm, selectedEntity, val, 'all');
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'Masa Tenggang H-14' || selectedReviewStatus === 'H-14' || selectedReviewStatus.includes('H-14')
                                ? 'bg-rose-600 text-white border-rose-600 shadow-xs font-semibold'
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
                            setSelectedStatus('all');
                            applyFilters(searchTerm, selectedEntity, val, 'all');
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'Wajib Review H-30' || selectedReviewStatus === 'H-30' || selectedReviewStatus.includes('H-30')
                                ? 'bg-amber-600 text-white border-amber-600 shadow-xs font-semibold'
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
                            setSelectedStatus('all');
                            applyFilters(searchTerm, selectedEntity, val, 'all');
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'Mendekati H-60' || selectedReviewStatus === 'H-60' || selectedReviewStatus.includes('H-60')
                                ? 'bg-sky-600 text-white border-sky-600 shadow-xs font-semibold'
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
                            setSelectedStatus('all');
                            applyFilters(searchTerm, selectedEntity, val, 'all');
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedReviewStatus === 'Aktif' || selectedReviewStatus.includes('Aktif')
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs font-semibold'
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
                            setSelectedReviewStatus('all');
                            applyFilters(searchTerm, selectedEntity, 'all', val);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            selectedStatus === 'Tetap (PKWTT)' || selectedStatus.includes('Tetap') || selectedReviewStatus.includes('Tetap')
                                ? 'bg-purple-600 text-white border-purple-600 shadow-xs font-semibold'
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
                                    placeholder="Cari no kontrak, nama karyawan, nama panggil, NIK..."
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

                                {/* Filter Status Kontrak (Status Ketenagakerjaan) */}
                                <SearchableSelect
                                    value={selectedStatus}
                                    onValueChange={(val) => {
                                        setSelectedStatus(val);
                                        applyFilters(searchTerm, selectedEntity, selectedReviewStatus, val);
                                    }}
                                    options={[{ label: 'Semua Status Kontrak', value: 'all' }, ...toOptions(dropdowns.employment_statuses)]}
                                    placeholder="Status Kontrak"
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
                                    className="h-9 w-44 text-xs"
                                    clearable={false}
                                />

                                <Button type="submit" variant="secondary" size="sm" className="h-9 text-xs gap-1.5">
                                    <Filter className="h-3.5 w-3.5" />
                                    Filter
                                </Button>

                                {(searchTerm || selectedEntity !== 'all' || selectedReviewStatus !== 'all' || selectedStatus !== 'all') && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            setSearchTerm('');
                                            setSelectedEntity('all');
                                            setSelectedReviewStatus('all');
                                            setSelectedStatus('all');
                                            router.get(route('hcm.contracts.index'), {}, { preserveState: true, replace: true });
                                        }}
                                        className="h-9 text-xs gap-1 text-zinc-500 hover:text-rose-600 hover:border-rose-200"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                        Reset
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Tabel Kontrak - 15 Kolom Sesuai Blueprint Database Row 16 */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-xs text-left whitespace-nowrap">
                            <thead className="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400">
                                <tr>
                                    <th className="py-3 px-3.5 font-semibold">Nama</th>
                                    <th className="py-3 px-3.5 font-semibold">Nama Panggil</th>
                                    <th className="py-3 px-3.5 font-semibold">Departemen / Divisi</th>
                                    <th className="py-3 px-3.5 font-semibold">Status Ketenagakerjaan</th>
                                    <th className="py-3 px-3 font-semibold text-center">Kontrak Ke</th>
                                    <th className="py-3 px-3.5 font-semibold">No. Kontrak</th>
                                    <th className="py-3 px-3.5 font-semibold">CV</th>
                                    <th className="py-3 px-3.5 font-semibold">Masa Kontrak</th>
                                    <th className="py-3 px-3 font-semibold">Bulan Mulai Trainee</th>
                                    <th className="py-3 px-3 font-semibold">Bulan Berakhir Trainee</th>
                                    <th className="py-3 px-3 font-semibold">Bulan Kontrak</th>
                                    <th className="py-3 px-3 font-semibold text-center">Tahun Mulai Kontrak</th>
                                    <th className="py-3 px-3 font-semibold text-center">Tahun Berakhir Kontrak</th>
                                    <th className="py-3 px-3.5 font-semibold text-center">Sisa Masa Kontrak (Hari)</th>
                                    <th className="py-3 px-3.5 font-semibold">Status Review</th>
                                    <th className="py-3 px-3.5 font-semibold text-right sticky right-0 bg-zinc-50/95 dark:bg-zinc-800/95 backdrop-blur-xs shadow-xs">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-200 dark:divide-zinc-800">
                                {contracts.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={16} className="py-12 text-center text-zinc-400">
                                            Tidak ada data naskah kontrak kerja yang sesuai dengan filter.
                                        </td>
                                    </tr>
                                ) : (
                                    contracts.data.map((c) => (
                                        <tr key={c.id} className="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition">
                                            {/* 1. Nama */}
                                            <td className="py-3 px-3.5">
                                                <div className="flex items-center gap-2">
                                                    {c.employee?.photo_url ? (
                                                        <img
                                                            src={c.employee.photo_url}
                                                            alt={c.employee.name}
                                                            className="w-7 h-7 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shrink-0"
                                                        />
                                                    ) : (
                                                        <div className="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center text-xs shrink-0">
                                                            {c.employee?.name?.charAt(0) || 'E'}
                                                        </div>
                                                    )}
                                                    <div className="min-w-0">
                                                        <div className="font-semibold text-zinc-900 dark:text-zinc-100">{c.employee?.name || '-'}</div>
                                                        <div className="text-[10px] font-mono text-zinc-400">{c.employee?.employee_code || '-'}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            {/* 2. Nama Panggil */}
                                            <td className="py-3 px-3.5 font-medium text-zinc-800 dark:text-zinc-200">
                                                {c.employee?.nickname || '-'}
                                            </td>

                                            {/* 3. Departemen / Divisi */}
                                            <td className="py-3 px-3.5">
                                                <div className="font-semibold text-zinc-800 dark:text-zinc-200">{c.employee?.department || '-'}</div>
                                                <div className="text-[10px] text-zinc-400">{c.employee?.division || '-'}</div>
                                            </td>

                                            {/* 4. Status Ketenagakerjaan */}
                                            <td className="py-3 px-3.5">
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200/60 dark:border-zinc-700/60">
                                                    {c.employment_status || '-'}
                                                </span>
                                            </td>

                                            {/* 5. Kontrak Ke */}
                                            <td className="py-3 px-3 text-center">
                                                <span className="inline-flex items-center justify-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                                    {c.contract_sequence !== null && c.contract_sequence !== undefined ? c.contract_sequence : '-'}
                                                </span>
                                            </td>

                                            {/* 6. No. Kontrak */}
                                            <td className="py-3 px-3.5 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                                {c.contract_number}
                                            </td>

                                            {/* 7. CV */}
                                            <td className="py-3 px-3.5 font-medium text-zinc-800 dark:text-zinc-200">
                                                {c.legal_entity || '-'}
                                            </td>

                                            {/* 8. Masa Kontrak */}
                                            <td className="py-3 px-3.5 text-zinc-700 dark:text-zinc-300">
                                                {c.duration_text || '-'}
                                            </td>

                                            {/* 9. Bulan Mulai Trainee */}
                                            <td className="py-3 px-3 text-zinc-600 dark:text-zinc-400">
                                                {c.trainee_start_month || '-'}
                                            </td>

                                            {/* 10. Bulan Berakhir Trainee */}
                                            <td className="py-3 px-3 text-zinc-600 dark:text-zinc-400">
                                                {c.trainee_end_month || '-'}
                                            </td>

                                            {/* 11. Bulan Kontrak */}
                                            <td className="py-3 px-3 text-zinc-700 dark:text-zinc-300">
                                                {c.contract_month || (c.start_date ? new Date(c.start_date).toLocaleDateString('id-ID', { month: 'long' }) : '-')}
                                            </td>

                                            {/* 12. Tahun Mulai Kontrak */}
                                            <td className="py-3 px-3 text-center font-mono text-zinc-700 dark:text-zinc-300">
                                                {c.start_year || (c.start_date ? new Date(c.start_date).getFullYear() : '-')}
                                            </td>

                                            {/* 13. Tahun Berakhir Kontrak */}
                                            <td className="py-3 px-3 text-center font-mono text-zinc-700 dark:text-zinc-300">
                                                {c.end_year || (c.end_date ? new Date(c.end_date).getFullYear() : '-')}
                                            </td>

                                            {/* 14. Sisa Masa Kontrak (Hari) */}
                                            <td className="py-3 px-3.5 text-center">
                                                {c.days_remaining !== null && c.days_remaining !== undefined ? (
                                                    <span
                                                        className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${
                                                            c.days_remaining < 0
                                                                ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900'
                                                                : c.days_remaining <= 14
                                                                ? 'bg-rose-500 text-white animate-pulse'
                                                                : c.days_remaining <= 30
                                                                ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800'
                                                                : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                                        }`}
                                                    >
                                                        {c.days_remaining} Hari
                                                    </span>
                                                ) : (
                                                    <span className="text-zinc-400 italic">Tetap</span>
                                                )}
                                            </td>

                                            {/* 15. Status Review */}
                                            <td className="py-3 px-3.5">
                                                {getReviewBadge(c.review_status, c.days_remaining)}
                                            </td>

                                            {/* 16. Aksi */}
                                            <td className="py-3 px-3.5 text-right sticky right-0 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xs shadow-xs">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    {c.file_contract_url ? (
                                                        <button
                                                            type="button"
                                                            onClick={() => openDocumentViewer(c.file_contract_url, `Kontrak ${c.contract_number}`)}
                                                            className="inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition"
                                                            title="Lihat Berkas Digital"
                                                        >
                                                            <Eye className="h-3 w-3" />
                                                            <span>Berkas</span>
                                                        </button>
                                                    ) : (
                                                        <label
                                                            className="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-medium text-zinc-500 hover:text-zinc-800 border border-dashed border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition"
                                                            title="Upload Berkas Scan Kontrak Fisik"
                                                        >
                                                            <Upload className="h-3 w-3" />
                                                            <span>Upload</span>
                                                            <input
                                                                type="file"
                                                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                                className="hidden"
                                                                onChange={(e) => {
                                                                    if (e.target.files?.[0]) {
                                                                        const formData = new FormData();
                                                                        formData.append('file_contract', e.target.files[0]);
                                                                        router.post(route('hcm.contracts.upload', c.uuid || c.id), formData, {
                                                                            forceFormData: true,
                                                                            preserveScroll: true,
                                                                        });
                                                                    }
                                                                }}
                                                            />
                                                        </label>
                                                    )}
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
                            <Label className="text-xs font-medium">Karyawan Terikat (Nama &amp; Nama Panggil) *</Label>
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
                                    label: `${e.name} (${e.nickname ? `Panggilan: ${e.nickname}` : '-'}) | ${e.employee_code} - ${e.department}`,
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
                                <Label className="text-xs font-medium">Divisi Karyawan</Label>
                                <SearchableSelect
                                    value={form.data.division || form.data.position}
                                    onValueChange={(val) => {
                                        form.setData({
                                            ...form.data,
                                            division: val,
                                            position: val,
                                        });
                                    }}
                                    options={toOptions(dropdowns.divisions || dropdowns.positions)}
                                    placeholder="Pilih Divisi..."
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

                        {/* Periode Trainee (Blueprint Excel Sheet Database) */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/60 dark:border-zinc-800/60">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Bulan Mulai Trainee (Opsional)</Label>
                                <Input
                                    value={form.data.trainee_start_month}
                                    onChange={(e) => form.setData('trainee_start_month', e.target.value)}
                                    placeholder="e.g. Agustus"
                                    className="text-xs"
                                />
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Bulan Berakhir Trainee (Opsional)</Label>
                                <Input
                                    value={form.data.trainee_end_month}
                                    onChange={(e) => form.setData('trainee_end_month', e.target.value)}
                                    placeholder="e.g. Oktober"
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
                                    onChange={(e) => {
                                        const val = e.target.value;
                                        const updates = { start_date: val };
                                        if (val) {
                                            const d = new Date(val);
                                            if (!isNaN(d.getTime())) {
                                                updates.start_year = d.getFullYear();
                                                const indonesianMonths = [
                                                    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                                                ];
                                                if (!form.data.contract_month) {
                                                    updates.contract_month = indonesianMonths[d.getMonth()];
                                                }
                                            }
                                        }
                                        form.setData({ ...form.data, ...updates });
                                    }}
                                    required
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Berakhir (Kosongkan jika Tetap)</Label>
                                <Input
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(e) => {
                                        const val = e.target.value;
                                        const updates = { end_date: val };
                                        if (val) {
                                            const d = new Date(val);
                                            if (!isNaN(d.getTime())) {
                                                updates.end_year = d.getFullYear();
                                            }
                                        } else {
                                            updates.end_year = '';
                                        }
                                        form.setData({ ...form.data, ...updates });
                                    }}
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        {/* Rincian Bulan & Tahun Kontrak (Sesuai Kolom Excel) */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/60 dark:border-zinc-800/60">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Bulan Kontrak</Label>
                                <Input
                                    value={form.data.contract_month}
                                    onChange={(e) => form.setData('contract_month', e.target.value)}
                                    placeholder="e.g. September"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tahun Mulai Kontrak</Label>
                                <Input
                                    type="number"
                                    value={form.data.start_year}
                                    onChange={(e) => form.setData('start_year', e.target.value)}
                                    placeholder="e.g. 2026"
                                    className="text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tahun Berakhir Kontrak</Label>
                                <Input
                                    type="number"
                                    value={form.data.end_year}
                                    onChange={(e) => form.setData('end_year', e.target.value)}
                                    placeholder="e.g. 2027 (Kosong jika tetap)"
                                    className="text-xs font-mono"
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
