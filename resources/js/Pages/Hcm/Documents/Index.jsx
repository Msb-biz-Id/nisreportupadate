import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import UniversalDocumentViewer from '@/Components/Hcm/UniversalDocumentViewer';
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
import {
    FileText,
    ShieldCheck,
    Plus,
    Edit2,
    Trash2,
    Search,
    Calendar,
    CheckCircle,
    ExternalLink,
    X,
    FileCheck,
    BookOpen,
    Layers,
    Upload,
    Eye,
    FolderKanban,
    Building2,
    Coins,
    RotateCcw,
} from 'lucide-react';

export default function DocumentIndex({
    auth,
    documents,
    filters = {},
    metrics = {},
    departments = [],
    categories = [],
    categoriesByStage = {},
    stages = ['Pengajuan', 'Realisasi', 'SOP & Kebijakan'],
    statuses = ['Berlaku', 'Selesai', 'Dalam Revisi', 'Dibatalkan', 'Arsip'],
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'all');
    const [selectedStage, setSelectedStage] = useState(filters.stage || 'all');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || 'all');

    // Modal State
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingDoc, setEditingDoc] = useState(null);
    const [previewFile, setPreviewFile] = useState(null);

    const form = useForm({
        document_code: '',
        title: '',
        document_stage: 'Pengajuan',
        category: categoriesByStage?.['Pengajuan']?.[0] || categories[0] || 'Pengajuan RAB (Rencana Anggaran Biaya)',
        department: departments[0] || '',
        revision_number: 'Rev 00',
        proposed_budget: '',
        actual_budget: '',
        effective_date: new Date().toISOString().split('T')[0],
        submission_date: new Date().toISOString().split('T')[0],
        approval_date: '',
        status: 'Berlaku',
        file_upload: null,
        file_url: '',
        description: '',
    });

    const handleFilter = (
        catVal = selectedCategory,
        statVal = selectedStatus,
        stageVal = selectedStage,
        deptVal = selectedDepartment,
        searchVal = search
    ) => {
        router.get(
            route('hcm.documents.index'),
            {
                search: searchVal,
                category: catVal,
                status: statVal,
                stage: stageVal,
                department: deptVal,
            },
            { preserveState: true }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        handleFilter(selectedCategory, selectedStatus, selectedStage, selectedDepartment, search);
    };

    const handleResetFilters = () => {
        setSearch('');
        setSelectedCategory('all');
        setSelectedStatus('all');
        setSelectedStage('all');
        setSelectedDepartment('all');
        router.get(
            route('hcm.documents.index'),
            {},
            { preserveState: true }
        );
    };

    const isFiltered =
        search !== '' ||
        selectedCategory !== 'all' ||
        selectedStatus !== 'all' ||
        selectedStage !== 'all' ||
        selectedDepartment !== 'all';

    const openCreateModal = () => {
        form.reset();
        const initialStage = 'Pengajuan';
        const initialCategory = categoriesByStage?.[initialStage]?.[0] || categories[0] || '';
        form.setData({
            document_code: '',
            title: '',
            document_stage: initialStage,
            category: initialCategory,
            department: departments[0] || '',
            revision_number: 'Rev 00',
            proposed_budget: '',
            actual_budget: '',
            effective_date: new Date().toISOString().split('T')[0],
            submission_date: new Date().toISOString().split('T')[0],
            approval_date: '',
            status: 'Berlaku',
            file_upload: null,
            file_url: '',
            description: '',
        });
        setEditingDoc(null);
        setIsModalOpen(true);
    };

    const openEditModal = (doc) => {
        setEditingDoc(doc);
        form.setData({
            document_code: doc.document_code,
            title: doc.title,
            document_stage: doc.document_stage || 'Pengajuan',
            category: doc.category,
            department: doc.department || '',
            revision_number: doc.revision_number || 'Rev 00',
            proposed_budget: doc.proposed_budget ?? '',
            actual_budget: doc.actual_budget ?? '',
            effective_date: doc.effective_date || '',
            submission_date: doc.submission_date || '',
            approval_date: doc.approval_date || '',
            status: doc.status || 'Berlaku',
            file_upload: null,
            file_url: doc.file_url || '',
            description: doc.description || '',
        });
        setIsModalOpen(true);
    };

    const handleStageChange = (newStage) => {
        const nextCats = categoriesByStage[newStage] || categories;
        const nextCategory = nextCats.includes(form.data.category) ? form.data.category : (nextCats[0] || '');
        form.setData((prev) => ({
            ...prev,
            document_stage: newStage,
            category: nextCategory,
        }));
    };

    const handleSubmitForm = (e) => {
        e.preventDefault();
        if (editingDoc) {
            form.post(route('hcm.documents.update', editingDoc.document_code || editingDoc.id), {
                forceFormData: true,
                data: {
                    ...form.data,
                    _method: 'PUT',
                },
                onSuccess: () => {
                    setIsModalOpen(false);
                    setEditingDoc(null);
                },
            });
        } else {
            form.post(route('hcm.documents.store'), {
                forceFormData: true,
                onSuccess: () => {
                    setIsModalOpen(false);
                },
            });
        }
    };

    const handleDelete = (doc) => {
        if (confirm(`Yakin ingin menghapus dokumen internal "${doc.document_code} - ${doc.title}"?`)) {
            router.delete(route('hcm.documents.destroy', doc.document_code || doc.id));
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'Berlaku':
            case 'Aktif':
                return (
                    <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-300 dark:border-emerald-800 text-[11px]">
                        Berlaku
                    </Badge>
                );
            case 'Selesai':
                return (
                    <Badge className="bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-300 dark:border-blue-800 text-[11px]">
                        Selesai
                    </Badge>
                );
            case 'Dalam Revisi':
            case 'Revisi':
                return (
                    <Badge className="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-800 text-[11px]">
                        Dalam Revisi
                    </Badge>
                );
            case 'Dibatalkan':
                return (
                    <Badge className="bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-800 text-[11px]">
                        Dibatalkan
                    </Badge>
                );
            case 'Arsip':
            case 'Arsip / Tidak Berlaku':
                return (
                    <Badge variant="outline" className="text-zinc-500 text-[11px]">
                        Arsip
                    </Badge>
                );
            default:
                return (
                    <Badge variant="outline" className="text-zinc-600 text-[11px]">
                        {status}
                    </Badge>
                );
        }
    };

    // Filter Options
    const categoryFilterOptions = categories.map((cat) => ({ value: cat, label: cat }));
    const stageFilterOptions = stages.map((s) => ({ value: s, label: s }));
    const departmentFilterOptions = departments.map((d) => ({ value: d, label: d }));
    const statusFilterOptions = statuses.map((st) => ({ value: st, label: st }));

    // Modal Form Options (Dynamic per chosen Stage)
    const modalAvailableCategories = categoriesByStage[form.data.document_stage] || categories;
    const modalCategoryOptions = modalAvailableCategories.map((cat) => ({ value: cat, label: cat }));

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <ShieldCheck className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Dokumen Internal & SOP Perusahaan
                    </span>
                </div>
            }
        >
            <Head title="Dokumen Internal & SOP Perusahaan - HCM" />

            <div className="space-y-4">
                {/* Header Banner Canvas */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <ShieldCheck className="h-5 w-5 text-indigo-600 shrink-0" />
                            <span>Arsip Dokumen Internal & SOP Perusahaan</span>
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Pusat kendali dokumen resmi: Pengajuan RAB, Proposal, Realisasi LPJ, dan SOP Kebijakan Perusahaan terpadu.
                        </p>
                    </div>

                    <Button
                        onClick={openCreateModal}
                        size="sm"
                        className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5 shadow-xs shrink-0 self-start sm:self-auto"
                    >
                        <Plus className="h-3.5 w-3.5" />
                        <span>Tambah Dokumen Baru</span>
                    </Button>
                </div>

                {/* 1. Baris Metrik Ringkasan (Blueprint HRIS) */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <FileText className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-zinc-500 font-medium">Total Dokumen</span>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                {metrics.total_documents ?? 0}
                            </div>
                        </div>
                    </Card>

                    <Card className="border border-sky-200/80 dark:border-sky-900/40 bg-sky-50/20 dark:bg-sky-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 flex items-center justify-center shrink-0">
                            <Layers className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-sky-600 font-medium">Dokumen Pengajuan</span>
                            <div className="text-xl font-bold text-sky-700 dark:text-sky-300">
                                {metrics.pengajuan_count ?? 0}
                            </div>
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/20 dark:bg-emerald-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center shrink-0">
                            <FileCheck className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-emerald-600 font-medium">Dokumen Realisasi</span>
                            <div className="text-xl font-bold text-emerald-700 dark:text-emerald-300">
                                {metrics.realisasi_count ?? 0}
                            </div>
                        </div>
                    </Card>

                    <Card className="border border-purple-200/80 dark:border-purple-900/40 bg-purple-50/20 dark:bg-purple-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 flex items-center justify-center shrink-0">
                            <BookOpen className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-purple-600 font-medium">SOP & Kebijakan Aktif</span>
                            <div className="text-xl font-bold text-purple-700 dark:text-purple-300">
                                {metrics.active_sop ?? 0}
                            </div>
                        </div>
                    </Card>
                </div>

                {/* 2. Filter & Pencarian Dinamis */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <CardContent className="p-3.5 space-y-3">
                        <div className="flex flex-col lg:flex-row items-center justify-between gap-3">
                            <form onSubmit={handleSearchSubmit} className="relative w-full lg:w-72">
                                <Search className="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-2.5" />
                                <Input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari kode, judul, atau departemen..."
                                    className="pl-9 h-8 text-xs"
                                />
                            </form>

                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 w-full lg:w-auto">
                                {/* Filter Tahap Siklus */}
                                <div className="w-full">
                                    <SearchableSelect
                                        options={[
                                            { value: 'all', label: 'Semua Tahap' },
                                            ...stageFilterOptions,
                                        ]}
                                        value={selectedStage}
                                        onValueChange={(val) => {
                                            setSelectedStage(val);
                                            handleFilter(selectedCategory, selectedStatus, val, selectedDepartment, search);
                                        }}
                                        placeholder="Tahap Siklus"
                                        className="w-full text-xs"
                                    />
                                </div>

                                {/* Filter Kategori Dokumen */}
                                <div className="w-full">
                                    <SearchableSelect
                                        options={[
                                            { value: 'all', label: 'Semua Kategori' },
                                            ...categoryFilterOptions,
                                        ]}
                                        value={selectedCategory}
                                        onValueChange={(val) => {
                                            setSelectedCategory(val);
                                            handleFilter(val, selectedStatus, selectedStage, selectedDepartment, search);
                                        }}
                                        placeholder="Kategori Dokumen"
                                        className="w-full text-xs"
                                    />
                                </div>

                                {/* Filter Departemen (Dinamis dari Master Option 'divisi') */}
                                <div className="w-full">
                                    <SearchableSelect
                                        options={[
                                            { value: 'all', label: 'Semua Departemen' },
                                            ...departmentFilterOptions,
                                        ]}
                                        value={selectedDepartment}
                                        onValueChange={(val) => {
                                            setSelectedDepartment(val);
                                            handleFilter(selectedCategory, selectedStatus, selectedStage, val, search);
                                        }}
                                        placeholder="Departemen"
                                        className="w-full text-xs"
                                    />
                                </div>

                                {/* Filter Status Dokumen */}
                                <div className="w-full">
                                    <SearchableSelect
                                        options={[
                                            { value: 'all', label: 'Semua Status' },
                                            ...statusFilterOptions,
                                        ]}
                                        value={selectedStatus}
                                        onValueChange={(val) => {
                                            setSelectedStatus(val);
                                            handleFilter(selectedCategory, val, selectedStage, selectedDepartment, search);
                                        }}
                                        placeholder="Status"
                                        className="w-full text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Reset Filter Button if active */}
                        {isFiltered && (
                            <div className="flex items-center justify-between pt-1 border-t border-zinc-100 dark:border-zinc-800 text-xs">
                                <span className="text-zinc-500 text-[11px]">Filter aktif diterapkan.</span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleResetFilters}
                                    className="h-6 text-[11px] text-zinc-500 hover:text-zinc-900 gap-1 px-2"
                                >
                                    <RotateCcw className="h-3 w-3" />
                                    Reset Semua Filter
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* 3. Tabel Dokumen Internal Sesuai Blueprint */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-zinc-50/75 dark:bg-zinc-800/50">
                                    <TableHead className="w-28 text-xs font-semibold">Kode Dokumen</TableHead>
                                    <TableHead className="text-xs font-semibold">Judul & Ringkasan</TableHead>
                                    <TableHead className="text-xs font-semibold">Kategori</TableHead>
                                    <TableHead className="w-24 text-xs font-semibold">Tahap</TableHead>
                                    <TableHead className="w-28 text-xs font-semibold">Departemen</TableHead>
                                    <TableHead className="w-36 text-xs font-semibold">Anggaran (Ajuan / Realisasi)</TableHead>
                                    <TableHead className="w-20 text-xs font-semibold">Revisi</TableHead>
                                    <TableHead className="w-28 text-xs font-semibold">Tgl Diajukan / Berlaku</TableHead>
                                    <TableHead className="w-24 text-center text-xs font-semibold">Status</TableHead>
                                    <TableHead className="w-24 text-center text-xs font-semibold">Berkas</TableHead>
                                    <TableHead className="w-20 text-right text-xs font-semibold">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {documents.data && documents.data.length > 0 ? (
                                    documents.data.map((doc) => (
                                        <TableRow key={doc.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                            <TableCell className="font-mono font-semibold text-xs text-indigo-600 dark:text-indigo-400">
                                                {doc.document_code}
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                                    {doc.title}
                                                </div>
                                                {doc.description && (
                                                    <div className="text-[11px] text-zinc-500 line-clamp-1 mt-0.5">
                                                        {doc.description}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" className="text-[11px] font-normal max-w-[200px] truncate">
                                                    {doc.category}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    className={`text-[10px] ${
                                                        doc.document_stage === 'Realisasi'
                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                                            : doc.document_stage === 'SOP & Kebijakan'
                                                            ? 'bg-purple-500/10 text-purple-700 dark:text-purple-400'
                                                            : 'bg-sky-500/10 text-sky-700 dark:text-sky-400'
                                                    }`}
                                                >
                                                    {doc.document_stage || 'Pengajuan'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-xs text-zinc-700 dark:text-zinc-300 font-medium">
                                                {doc.department ? (
                                                    <span className="inline-flex items-center gap-1">
                                                        <Building2 className="h-3 w-3 text-zinc-400" />
                                                        {doc.department}
                                                    </span>
                                                ) : (
                                                    <span className="text-zinc-400 italic text-[11px]">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-[11px] text-zinc-600 dark:text-zinc-400">
                                                <div className="font-medium text-zinc-800 dark:text-zinc-200">
                                                    Ajuan: Rp {Number(doc.proposed_budget || 0).toLocaleString('id-ID')}
                                                </div>
                                                {doc.actual_budget > 0 && (
                                                    <div className="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">
                                                        Realisasi: Rp {Number(doc.actual_budget).toLocaleString('id-ID')}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                                                {doc.revision_number || 'Rev 00'}
                                            </TableCell>
                                            <TableCell className="text-xs text-zinc-600 dark:text-zinc-400">
                                                {doc.submission_date ? (
                                                    <div>Diajukan: {doc.submission_date}</div>
                                                ) : null}
                                                {doc.effective_date && (
                                                    <div className="text-[10px] text-zinc-400">Berlaku: {doc.effective_date}</div>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {getStatusBadge(doc.status)}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {doc.file_url ? (
                                                    <div className="flex items-center justify-center gap-1">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => {
                                                                const isPdf =
                                                                    doc.file_url.toLowerCase().endsWith('.pdf') ||
                                                                    doc.file_url.includes('.pdf');
                                                                setPreviewFile({
                                                                    url: doc.file_url,
                                                                    name: `${doc.document_code} - ${doc.title}`,
                                                                    isPdf: isPdf,
                                                                });
                                                            }}
                                                            className="h-6 text-[10px] px-1.5 gap-1 border-indigo-200 text-indigo-600 hover:bg-indigo-50"
                                                        >
                                                            <Eye className="h-3 w-3" />
                                                            Lihat
                                                        </Button>
                                                        <a
                                                            href={doc.file_url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="text-zinc-400 hover:text-zinc-600 p-0.5"
                                                            title="Buka Tab Baru"
                                                        >
                                                            <ExternalLink className="h-3 w-3" />
                                                        </a>
                                                    </div>
                                                ) : (
                                                    <span className="text-[11px] text-zinc-400 italic">Belum ada</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openEditModal(doc)}
                                                        className="h-7 w-7 p-0 text-zinc-500 hover:text-indigo-600"
                                                        title="Edit Dokumen"
                                                    >
                                                        <Edit2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(doc)}
                                                        className="h-7 w-7 p-0 text-zinc-500 hover:text-rose-600"
                                                        title="Hapus Dokumen"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={11} className="h-32 text-center text-xs text-zinc-400">
                                            Tidak ada dokumen internal yang sesuai dengan kriteria filter.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {documents.links && documents.links.length > 3 && (
                        <div className="p-3 border-t border-zinc-200/80 dark:border-zinc-800 flex items-center justify-between text-xs text-zinc-500">
                            <div>
                                Menampilkan {documents.from || 0} - {documents.to || 0} dari {documents.total || 0} dokumen
                            </div>
                            <div className="flex items-center gap-1">
                                {documents.links.map((link, idx) => (
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

            {/* MODAL: Form Tambah / Edit Dokumen Internal Sesuai Blueprint */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-indigo-600">
                            <ShieldCheck className="h-5 w-5" />
                            {editingDoc ? 'Edit Dokumen Internal & SOP' : 'Tambah Dokumen Internal Baru'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Isi metadata dokumen sesuai Blueprint HCM NIS (Pengajuan RAB/Proposal, Realisasi LPJ/Aset, atau SOP Perusahaan).
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmitForm} className="space-y-3.5 pt-2">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {/* Kode Dokumen / No. Registrasi */}
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Nomor Dokumen / Registrasi *</Label>
                                <Input
                                    type="text"
                                    value={form.data.document_code}
                                    onChange={(e) => form.setData('document_code', e.target.value)}
                                    required
                                    placeholder="e.g. RAB_Produksi_Juni26_v1 / SOP-HCM-001"
                                    className="h-8 text-xs font-mono"
                                />
                                {form.errors.document_code && (
                                    <p className="text-[11px] text-rose-500">{form.errors.document_code}</p>
                                )}
                            </div>

                            {/* Tahap Siklus Dokumen */}
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tahap Siklus Dokumen *</Label>
                                <SearchableSelect
                                    options={stages.map((st) => ({ value: st, label: st }))}
                                    value={form.data.document_stage}
                                    onValueChange={handleStageChange}
                                    placeholder="Pilih Tahap Siklus"
                                    className="w-full text-xs"
                                />
                                {form.errors.document_stage && (
                                    <p className="text-[11px] text-rose-500">{form.errors.document_stage}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {/* Kategori Dokumen (Dinamis Berdasarkan Tahap Siklus) */}
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Kategori Dokumen *</Label>
                                <SearchableSelect
                                    options={modalCategoryOptions}
                                    value={form.data.category}
                                    onValueChange={(val) => form.setData('category', val)}
                                    placeholder="Pilih Kategori Dokumen"
                                    className="w-full text-xs"
                                />
                                {form.errors.category && (
                                    <p className="text-[11px] text-rose-500">{form.errors.category}</p>
                                )}
                            </div>

                            {/* Departemen Pembuat (Dinamis dari Master Option 'divisi') */}
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Departemen Pembuat *</Label>
                                <SearchableSelect
                                    options={departmentFilterOptions}
                                    value={form.data.department}
                                    onValueChange={(val) => form.setData('department', val)}
                                    placeholder="Pilih Departemen Pembuat"
                                    className="w-full text-xs"
                                />
                                {form.errors.department && (
                                    <p className="text-[11px] text-rose-500">{form.errors.department}</p>
                                )}
                            </div>
                        </div>

                        {/* Judul Dokumen */}
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Judul Dokumen *</Label>
                            <Input
                                type="text"
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                required
                                placeholder="Contoh: Pengajuan Anggaran Pengadaan Mesin Jahit Batch 2"
                                className="h-8 text-xs"
                            />
                            {form.errors.title && (
                                <p className="text-[11px] text-rose-500">{form.errors.title}</p>
                            )}
                        </div>

                        {/* Anggaran Diajukan & Realisasi Anggaran */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Anggaran Diajukan (Rp)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={form.data.proposed_budget}
                                    onChange={(e) => form.setData('proposed_budget', e.target.value)}
                                    placeholder="0"
                                    className="h-8 text-xs font-mono"
                                />
                                {form.errors.proposed_budget && (
                                    <p className="text-[11px] text-rose-500">{form.errors.proposed_budget}</p>
                                )}
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Realisasi Anggaran (Rp)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={form.data.actual_budget}
                                    onChange={(e) => form.setData('actual_budget', e.target.value)}
                                    placeholder="0"
                                    className="h-8 text-xs font-mono"
                                />
                                {form.errors.actual_budget && (
                                    <p className="text-[11px] text-rose-500">{form.errors.actual_budget}</p>
                                )}
                            </div>
                        </div>

                        {/* Tanggal & Revisi */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Diajukan</Label>
                                <Input
                                    type="date"
                                    value={form.data.submission_date}
                                    onChange={(e) => form.setData('submission_date', e.target.value)}
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Disetujui</Label>
                                <Input
                                    type="date"
                                    value={form.data.approval_date}
                                    onChange={(e) => form.setData('approval_date', e.target.value)}
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Berlaku</Label>
                                <Input
                                    type="date"
                                    value={form.data.effective_date}
                                    onChange={(e) => form.setData('effective_date', e.target.value)}
                                    className="h-8 text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Nomor Revisi</Label>
                                <Input
                                    type="text"
                                    value={form.data.revision_number}
                                    onChange={(e) => form.setData('revision_number', e.target.value)}
                                    placeholder="Rev 00"
                                    className="h-8 text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Status Dokumen *</Label>
                                <SearchableSelect
                                    options={statusFilterOptions}
                                    value={form.data.status}
                                    onValueChange={(val) => form.setData('status', val)}
                                    placeholder="Pilih Status"
                                    className="w-full text-xs"
                                />
                                {form.errors.status && (
                                    <p className="text-[11px] text-rose-500">{form.errors.status}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Deskripsi / Ruang Lingkup Dokumen</Label>
                            <Textarea
                                rows={2}
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                placeholder="Penjelasan ringkas perihal, latar belakang, atau ruang lingkup dokumen..."
                                className="text-xs"
                            />
                        </div>

                        {/* Upload Berkas Fisik / Google Drive */}
                        <div className="space-y-1.5 p-3 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <Label className="text-xs font-semibold flex items-center gap-1.5 text-zinc-800 dark:text-zinc-200">
                                <Upload className="h-3.5 w-3.5 text-indigo-500" />
                                Unggah Berkas Dokumen Fisik (PDF / DOC / XLS / Gambar)
                            </Label>
                            <Input
                                type="file"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                                onChange={(e) => {
                                    if (e.target.files && e.target.files[0]) {
                                        form.setData('file_upload', e.target.files[0]);
                                    }
                                }}
                                className="text-xs file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:border-0 file:rounded file:px-2 file:py-1 hover:file:bg-indigo-100"
                            />
                            {editingDoc && editingDoc.file_url && (
                                <p className="text-[11px] text-indigo-600 flex items-center gap-1 mt-1">
                                    <FileText className="h-3 w-3" />
                                    Berkas saat ini terpasang. Unggah file baru hanya jika ingin menggantinya.
                                </p>
                            )}
                            <p className="text-[10px] text-zinc-500">
                                Berkas otomatis tersimpan di storage server & disinkronkan ke Google Drive folder <code>01_Dokumen_Internal</code>.
                            </p>
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5"
                            >
                                {form.processing ? 'Menyimpan...' : editingDoc ? 'Simpan Perubahan' : 'Tambah Dokumen'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* In-App Document Preview Modal */}
            <UniversalDocumentViewer
                isOpen={!!previewFile}
                onClose={() => setPreviewFile(null)}
                fileUrl={previewFile?.url}
                fileName={previewFile?.name || 'Dokumen Internal & SOP'}
            />
        </AppLayout>
    );
}
