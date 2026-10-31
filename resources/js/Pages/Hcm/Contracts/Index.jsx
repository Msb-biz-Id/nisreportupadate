import { Head, router, useForm } from '@inertiajs/react';
import { useState, useEffect, useMemo } from 'react';
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
    TrendingUp,
    Banknote,
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
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

const rp = (val) => {
    if (val === null || val === undefined || val === '') return '-';
    return 'Rp ' + Number(val).toLocaleString('id-ID');
};

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

    // State Modal Keputusan Evaluasi Kenaikan Gaji
    const [decisionModalOpen, setDecisionModalOpen] = useState(false);
    const [selectedContractForDecision, setSelectedContractForDecision] = useState(null);
    const decisionForm = useForm({
        decision_status: 'Sedang Diajukan',
        planned_increment: '',
        effective_date: '',
        custom_milestone_date: '',
        decision_notes: '',
        apply_immediately: false,
    });

    const handleOpenDecision = (c) => {
        const comp = c.employee?.compensation;
        if (!comp) {
            if (confirm(`Data kompensasi untuk karyawan ${c.employee?.name} belum dicatat. Apakah Anda ingin membuka halaman Kompensasi untuk menambahkannya?`)) {
                router.get(route('hcm.compensations.index'), { search: c.employee?.name });
            }
            return;
        }
        setSelectedContractForDecision(c);
        decisionForm.setData({
            decision_status: comp.decision_status || 'Sedang Diajukan',
            planned_increment: comp.planned_increment ?? '',
            effective_date: comp.effective_date ? String(comp.effective_date).slice(0, 10) : new Date().toISOString().split('T')[0],
            custom_milestone_date: comp.custom_milestone_date ? String(comp.custom_milestone_date).slice(0, 10) : '',
            decision_notes: comp.decision_notes || '',
            apply_immediately: false,
        });
        setDecisionModalOpen(true);
    };

    const submitDecision = (e) => {
        e.preventDefault();
        const comp = selectedContractForDecision?.employee?.compensation;
        if (!comp) return;
        const key = comp.uuid || comp.id;
        decisionForm.put(route('hcm.compensations.decision.update', key), {
            preserveScroll: true,
            onSuccess: () => {
                setDecisionModalOpen(false);
                decisionForm.reset();
            },
        });
    };

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

    const applyFilters = (search = searchTerm, entity = selectedEntity, rev = selectedReviewStatus, stat = selectedStatus, page = 1, perPage = contracts?.per_page || 15) => {
        router.get(
            route('hcm.contracts.index'),
            {
                search: search || undefined,
                legal_entity: entity !== 'all' ? entity : undefined,
                review_status: rev !== 'all' ? rev : undefined,
                employment_status: stat !== 'all' ? stat : undefined,
                page: page > 1 ? page : undefined,
                per_page: perPage,
            },
            { preserveState: true, replace: true }
        );
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

    const columns = useMemo(() => [
        {
            id: 'employee',
            size: 200,
            minSize: 180,
            accessorFn: (row) => row.employee?.name || '',
            meta: { title: 'Nama' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nama" />,
            cell: ({ row }) => {
                const c = row.original;
                return (
                    <div className="flex items-center gap-2 min-w-[170px]">
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
                            <div className="font-semibold text-zinc-900 dark:text-zinc-100 truncate">{c.employee?.name || '-'}</div>
                            <div className="text-[10px] font-mono text-zinc-400">{c.employee?.employee_code || '-'}</div>
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'nickname',
            size: 120,
            minSize: 100,
            accessorFn: (row) => row.employee?.nickname || '',
            meta: { title: 'Nama Panggil' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nama Panggil" />,
            cell: ({ row }) => (
                <span className="font-medium text-zinc-800 dark:text-zinc-200 whitespace-nowrap">{row.original.employee?.nickname || '-'}</span>
            ),
        },
        {
            id: 'department',
            size: 170,
            minSize: 140,
            accessorFn: (row) => row.employee?.department || '',
            meta: { title: 'Departemen / Divisi' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Departemen / Divisi" />,
            cell: ({ row }) => (
                <div className="min-w-[130px]">
                    <div className="font-semibold text-zinc-800 dark:text-zinc-200 truncate">{row.original.employee?.department || '-'}</div>
                    <div className="text-[10px] text-zinc-400 truncate">{row.original.employee?.division || '-'}</div>
                </div>
            ),
        },
        {
            accessorKey: 'employment_status',
            size: 160,
            minSize: 140,
            meta: { title: 'Status Ketenagakerjaan' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status Ketenagakerjaan" />,
            cell: ({ row }) => (
                <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200/60 dark:border-zinc-700/60 whitespace-nowrap">
                    {row.original.employment_status || '-'}
                </span>
            ),
        },
        {
            accessorKey: 'contract_sequence',
            size: 95,
            minSize: 85,
            meta: { title: 'Kontrak Ke', cellClassName: 'text-center' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Kontrak Ke" className="justify-center" />,
            cell: ({ row }) => (
                <span className="inline-flex items-center justify-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 whitespace-nowrap">
                    {row.original.contract_sequence ?? '-'}
                </span>
            ),
        },
        {
            accessorKey: 'contract_number',
            size: 200,
            minSize: 180,
            meta: { title: 'No. Kontrak' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="No. Kontrak" />,
            cell: ({ row }) => (
                <span className="font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">{row.original.contract_number}</span>
            ),
        },
        {
            accessorKey: 'legal_entity',
            size: 130,
            minSize: 110,
            meta: { title: 'CV' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="CV" />,
            cell: ({ row }) => <span className="font-medium text-zinc-800 dark:text-zinc-200 whitespace-nowrap">{row.original.legal_entity || '-'}</span>,
        },
        {
            accessorKey: 'duration_text',
            size: 130,
            minSize: 110,
            meta: { title: 'Masa Kontrak' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Masa Kontrak" />,
            cell: ({ row }) => <span className="text-zinc-700 dark:text-zinc-300 whitespace-nowrap">{row.original.duration_text || '-'}</span>,
        },
        {
            accessorKey: 'trainee_start_month',
            size: 150,
            minSize: 130,
            meta: { title: 'Bulan Mulai Trainee' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Bulan Mulai Trainee" />,
            cell: ({ row }) => <span className="text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{row.original.trainee_start_month || '-'}</span>,
        },
        {
            accessorKey: 'trainee_end_month',
            size: 150,
            minSize: 130,
            meta: { title: 'Bulan Berakhir Trainee' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Bulan Berakhir Trainee" />,
            cell: ({ row }) => <span className="text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{row.original.trainee_end_month || '-'}</span>,
        },
        {
            id: 'contract_month',
            size: 130,
            minSize: 110,
            accessorFn: (row) => row.contract_month || row.start_date || '',
            meta: { title: 'Bulan Kontrak' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Bulan Kontrak" />,
            cell: ({ row }) => (
                <span className="text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                    {row.original.contract_month || (row.original.start_date ? new Date(row.original.start_date).toLocaleDateString('id-ID', { month: 'long' }) : '-')}
                </span>
            ),
        },
        {
            accessorKey: 'start_year',
            size: 130,
            minSize: 110,
            meta: { title: 'Tahun Mulai Kontrak', cellClassName: 'text-center' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Tahun Mulai Kontrak" className="justify-center" />,
            cell: ({ row }) => (
                <span className="font-mono text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                    {row.original.start_year || (row.original.start_date ? new Date(row.original.start_date).getFullYear() : '-')}
                </span>
            ),
        },
        {
            accessorKey: 'end_year',
            size: 130,
            minSize: 110,
            meta: { title: 'Tahun Berakhir Kontrak', cellClassName: 'text-center' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Tahun Berakhir Kontrak" className="justify-center" />,
            cell: ({ row }) => (
                <span className="font-mono text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                    {row.original.end_year || (row.original.end_date ? new Date(row.original.end_date).getFullYear() : '-')}
                </span>
            ),
        },
        {
            id: 'days_remaining',
            size: 150,
            minSize: 130,
            accessorFn: (row) => (row.days_remaining === null || row.days_remaining === undefined ? null : row.days_remaining),
            meta: { title: 'Sisa Masa Kontrak (Hari)', cellClassName: 'text-center' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Sisa Masa Kontrak (Hari)" className="justify-center" />,
            cell: ({ row }) => {
                const d = row.original.days_remaining;
                if (d === null || d === undefined) {
                    return <span className="text-zinc-400 italic whitespace-nowrap">Tetap</span>;
                }
                const cls =
                    d < 0
                        ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900'
                        : d <= 14
                        ? 'bg-rose-500 text-white animate-pulse'
                        : d <= 30
                        ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800'
                        : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
                return <span className={`inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold whitespace-nowrap ${cls}`}>{d} Hari</span>;
            },
        },
        {
            accessorKey: 'review_status',
            size: 175,
            minSize: 155,
            meta: { title: 'Status Review' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status Review" />,
            cell: ({ row }) => (
                <div className="whitespace-nowrap">
                    {getReviewBadge(row.original.review_status, row.original.days_remaining)}
                </div>
            ),
        },
        {
            id: 'actions',
            size: 240,
            minSize: 220,
            meta: { pinned: 'right' },
            header: () => <div className="text-right whitespace-nowrap pr-2">Aksi</div>,
            cell: ({ row }) => {
                const c = row.original;
                return (
                    <div className="flex items-center justify-end gap-1.5 whitespace-nowrap">
                        <button
                            type="button"
                            onClick={() => handleOpenDecision(c)}
                            className="inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-medium bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition shrink-0"
                            title="Evaluasi Kenaikan Gaji Karyawan"
                        >
                            <TrendingUp className="h-3 w-3" />
                            <span>Evaluasi Gaji</span>
                        </button>
                        {c.file_contract_url ? (
                            <button
                                type="button"
                                onClick={() => openDocumentViewer(c.file_contract_url, `Kontrak ${c.contract_number}`)}
                                className="inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition shrink-0"
                                title="Lihat Berkas Digital"
                            >
                                <Eye className="h-3 w-3" />
                                <span>Berkas</span>
                            </button>
                        ) : (
                            <label
                                className="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-medium text-zinc-500 hover:text-zinc-800 border border-dashed border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer transition shrink-0"
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
                            className="p-1 rounded text-zinc-500 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition shrink-0"
                            title="Edit Data Kontrak"
                        >
                            <Edit2 className="h-3.5 w-3.5" />
                        </button>
                        <button
                            type="button"
                            onClick={() => handleDelete(c)}
                            className="p-1 rounded text-zinc-500 hover:text-rose-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition shrink-0"
                            title="Hapus Kontrak"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                        </button>
                    </div>
                );
            },
        },
    ], []);

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


                {/* Tabel Kontrak - DataTable (sticky filter, sticky footer pagination, kolom aksi pin kanan) */}
                <DataTable
                    columns={columns}
                    data={contracts.data || []}
                    serverPagination={contracts}
                    onServerPageChange={(page) => applyFilters(searchTerm, selectedEntity, selectedReviewStatus, selectedStatus, page)}
                    onServerPerPageChange={(per_page) => applyFilters(searchTerm, selectedEntity, selectedReviewStatus, selectedStatus, 1, per_page)}
                    onServerSearchChange={(val) => {
                        setSearchTerm(val);
                        applyFilters(val, selectedEntity, selectedReviewStatus, selectedStatus, 1);
                    }}
                    searchValue={searchTerm}
                    searchPlaceholder="Cari no kontrak, nama karyawan, nama panggil, NIK..."
                    exportFileName={`Kontrak_Karyawan_${new Date().toISOString().slice(0, 10)}`}
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-2">
                            <SearchableSelect
                                value={selectedEntity}
                                onValueChange={(val) => {
                                    setSelectedEntity(val);
                                    applyFilters(searchTerm, val, selectedReviewStatus, selectedStatus, 1);
                                }}
                                options={[{ label: 'Semua Badan Usaha (CV)', value: 'all' }, ...toOptions(dropdowns.legal_entities)]}
                                placeholder="Semua CV"
                                className="h-8 w-44 text-xs"
                                clearable={false}
                            />
                            <SearchableSelect
                                value={selectedStatus}
                                onValueChange={(val) => {
                                    setSelectedStatus(val);
                                    applyFilters(searchTerm, selectedEntity, selectedReviewStatus, val, 1);
                                }}
                                options={[{ label: 'Semua Status Kontrak', value: 'all' }, ...toOptions(dropdowns.employment_statuses)]}
                                placeholder="Status Kontrak"
                                className="h-8 w-44 text-xs"
                                clearable={false}
                            />
                            <SearchableSelect
                                value={selectedReviewStatus}
                                onValueChange={(val) => {
                                    setSelectedReviewStatus(val);
                                    applyFilters(searchTerm, selectedEntity, val, selectedStatus, 1);
                                }}
                                options={[{ label: 'Semua Status Review', value: 'all' }, ...toOptions(dropdowns.review_statuses)]}
                                placeholder="Status Review"
                                className="h-8 w-44 text-xs"
                                clearable={false}
                            />
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
                                    className="h-8 text-xs gap-1 text-zinc-500 hover:text-rose-600 hover:border-rose-200"
                                >
                                    <X className="h-3.5 w-3.5" />
                                    Reset
                                </Button>
                            )}
                        </div>
                    }
                />
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
                                Berkas akan disimpan secara aman pada penyimpanan sistem.
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

            {/* MODAL KEPUTUSAN EVALUASI KENAIKAN GAJI BERKALA */}
            <Dialog open={decisionModalOpen} onOpenChange={setDecisionModalOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                            <TrendingUp className="h-4 w-4" />
                            <span>Keputusan Evaluasi Kenaikan Gaji Berkala</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Karyawan: <strong>{selectedContractForDecision?.employee?.name}</strong> ({selectedContractForDecision?.employee?.employee_code}) • {selectedContractForDecision?.employee?.department || '-'} / {selectedContractForDecision?.employee?.division || '-'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitDecision} className="space-y-3.5">
                        {/* Info Ringkas Masa Kerja & Gaji Berjalan */}
                        <div className="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/80 space-y-1.5 text-xs">
                            <div className="flex justify-between text-zinc-600 dark:text-zinc-300">
                                <span>No. Kontrak & Urutan:</span>
                                <span className="font-semibold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {selectedContractForDecision?.contract_number} (Kontrak ke-{selectedContractForDecision?.contract_sequence || 1})
                                </span>
                            </div>
                            <div className="flex justify-between text-zinc-600 dark:text-zinc-300">
                                <span>Gaji Berjalan Saat Ini:</span>
                                <span className="font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                    {rp(selectedContractForDecision?.employee?.compensation?.current_salary)}
                                </span>
                            </div>
                            <div className="flex justify-between text-zinc-600 dark:text-zinc-300">
                                <span>Siklus Review Standar:</span>
                                <span className="font-semibold text-indigo-600">
                                    Setiap {selectedContractForDecision?.employee?.compensation?.evaluation_cycle_months ?? 6} Bulan
                                </span>
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Status Keputusan Evaluasi *</Label>
                            <SearchableSelect
                                value={decisionForm.data.decision_status}
                                onValueChange={(val) => decisionForm.setData('decision_status', val)}
                                options={[
                                    { label: 'Sedang Diajukan', value: 'Sedang Diajukan' },
                                    { label: 'Sudah Disetujui / ACC', value: 'Sudah Disetujui / ACC' },
                                    { label: 'Ditunda', value: 'Ditunda' },
                                    { label: 'Tidak Naik', value: 'Tidak Naik' },
                                ]}
                                placeholder="Pilih Status Keputusan"
                                className="mt-1 text-xs"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label className="text-xs font-semibold">Usulan / Nilai Kenaikan (Rp)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={decisionForm.data.planned_increment}
                                    onChange={(e) => decisionForm.setData('planned_increment', e.target.value)}
                                    placeholder="Contoh: 200000"
                                    className="mt-1 h-9 text-xs font-mono"
                                />
                            </div>

                            <div>
                                <Label className="text-xs font-semibold">Tanggal Efektif</Label>
                                <Input
                                    type="date"
                                    value={decisionForm.data.effective_date}
                                    onChange={(e) => decisionForm.setData('effective_date', e.target.value)}
                                    className="mt-1 h-9 text-xs"
                                />
                            </div>
                        </div>

                        {decisionForm.data.decision_status === 'Ditunda' && (
                            <div className="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 space-y-1">
                                <Label className="text-xs font-semibold text-amber-800 dark:text-amber-300">
                                    Tanggal Review Baru (Custom Milestone) *
                                </Label>
                                <p className="text-[10px] text-amber-700 dark:text-amber-400">
                                    Jadwal evaluasi khusus (misal tunda 3 bulan) tanpa merusak siklus tahunan masa kerja.
                                </p>
                                <Input
                                    type="date"
                                    value={decisionForm.data.custom_milestone_date}
                                    onChange={(e) => decisionForm.setData('custom_milestone_date', e.target.value)}
                                    className="h-8 text-xs bg-white dark:bg-zinc-900 mt-1"
                                />
                            </div>
                        )}

                        <div>
                            <Label className="text-xs font-semibold">Catatan & Notula Hasil Evaluasi</Label>
                            <Input
                                value={decisionForm.data.decision_notes}
                                onChange={(e) => decisionForm.setData('decision_notes', e.target.value)}
                                placeholder="Alasan penundaan / persetujuan / catatan performa..."
                                className="mt-1 h-9 text-xs"
                            />
                        </div>

                        {decisionForm.data.decision_status === 'Sudah Disetujui / ACC' && (
                            <div className="flex items-center gap-2 p-2 rounded bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40">
                                <input
                                    type="checkbox"
                                    id="apply_immediately_contract"
                                    checked={decisionForm.data.apply_immediately}
                                    onChange={(e) => decisionForm.setData('apply_immediately', e.target.checked)}
                                    className="rounded border-emerald-300 text-emerald-600 h-4 w-4"
                                />
                                <Label htmlFor="apply_immediately_contract" className="text-xs font-semibold text-emerald-800 dark:text-emerald-300 cursor-pointer">
                                    Langsung terapkan kenaikan ini ke Gaji Berjalan saat disimpan (Apply Increment)
                                </Label>
                            </div>
                        )}

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setDecisionModalOpen(false)}
                                disabled={decisionForm.processing}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                                disabled={decisionForm.processing}
                            >
                                {decisionForm.processing ? 'Menyimpan...' : 'Simpan Keputusan Evaluasi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
