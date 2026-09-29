import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
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
} from 'lucide-react';

export default function DocumentIndex({ auth, documents, filters, metrics, categories }) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'all');

    // Modal State
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingDoc, setEditingDoc] = useState(null);
    const [previewFile, setPreviewFile] = useState(null);

    const form = useForm({
        document_code: '',
        title: '',
        category: categories[0] || 'SOP (Standar Operasional Prosedur)',
        revision_number: 'Rev 00',
        effective_date: new Date().toISOString().split('T')[0],
        status: 'Aktif',
        file_upload: null,
        file_url: '',
        description: '',
    });

    const handleFilter = (catVal = selectedCategory, statVal = selectedStatus) => {
        router.get(
            route('hcm.documents.index'),
            {
                search,
                category: catVal,
                status: statVal,
            },
            { preserveState: true }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        handleFilter();
    };

    const openCreateModal = () => {
        form.reset();
        form.setData({
            document_code: '',
            title: '',
            category: categories[0] || 'SOP (Standar Operasional Prosedur)',
            revision_number: 'Rev 00',
            effective_date: new Date().toISOString().split('T')[0],
            status: 'Aktif',
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
            category: doc.category,
            revision_number: doc.revision_number,
            effective_date: doc.effective_date,
            status: doc.status,
            file_upload: null,
            file_url: doc.file_url || '',
            description: doc.description || '',
        });
        setIsModalOpen(true);
    };

    const handleSubmitForm = (e) => {
        e.preventDefault();
        if (editingDoc) {
            form.post(route('hcm.documents.update', editingDoc.id), {
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
            router.delete(route('hcm.documents.destroy', doc.id));
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'Aktif':
                return (
                    <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-300 dark:border-emerald-800 text-[11px]">
                        Aktif Berlaku
                    </Badge>
                );
            case 'Dalam Revisi':
                return (
                    <Badge className="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-800 text-[11px]">
                        Dalam Revisi
                    </Badge>
                );
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

    const categoryOptions = categories.map((cat) => ({ value: cat, label: cat }));

    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <ShieldCheck className="h-5 w-5 text-indigo-600" />
                            Dokumen Internal & SOP Perusahaan
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Pusat kendali dokumen resmi, Standar Operasional Prosedur (SOP), Formulir Kerja, dan Peraturan Perusahaan.
                        </p>
                    </div>

                    <Button
                        onClick={openCreateModal}
                        size="sm"
                        className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5 shadow-sm"
                    >
                        <Plus className="h-3.5 w-3.5" />
                        Tambah Dokumen SOP Baru
                    </Button>
                </div>
            }
        >
            <Head title="Dokumen Internal & SOP Perusahaan - HCM" />

            <div className="space-y-4">
                {/* 1. Baris Metrik Ringkasan */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <FileText className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-zinc-500 font-medium">Total Dokumen</span>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">{metrics.total_documents}</div>
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/20 dark:bg-emerald-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center shrink-0">
                            <FileCheck className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-emerald-600 font-medium">SOP Aktif</span>
                            <div className="text-xl font-bold text-emerald-700 dark:text-emerald-300">{metrics.active_sop}</div>
                        </div>
                    </Card>

                    <Card className="border border-sky-200/80 dark:border-sky-900/40 bg-sky-50/20 dark:bg-sky-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 flex items-center justify-center shrink-0">
                            <BookOpen className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-sky-600 font-medium">Peraturan Perusahaan</span>
                            <div className="text-xl font-bold text-sky-700 dark:text-sky-300">{metrics.company_regulations}</div>
                        </div>
                    </Card>

                    <Card className="border border-purple-200/80 dark:border-purple-900/40 bg-purple-50/20 dark:bg-purple-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 flex items-center justify-center shrink-0">
                            <Layers className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-purple-600 font-medium">Formulir Standar</span>
                            <div className="text-xl font-bold text-purple-700 dark:text-purple-300">{metrics.standard_forms}</div>
                        </div>
                    </Card>
                </div>

                {/* 2. Filter & Pencarian */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <CardContent className="p-3.5 flex flex-col md:flex-row items-center justify-between gap-3">
                        <form onSubmit={handleSearchSubmit} className="relative w-full md:w-80">
                            <Search className="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-2.5" />
                            <Input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari kode atau judul dokumen..."
                                className="pl-9 h-8 text-xs"
                            />
                        </form>

                        <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
                            <div className="w-full sm:w-60">
                                <SearchableSelect
                                    options={[
                                        { value: 'all', label: 'Semua Kategori' },
                                        ...categoryOptions,
                                    ]}
                                    value={selectedCategory}
                                    onChange={(val) => {
                                        setSelectedCategory(val);
                                        handleFilter(val, selectedStatus);
                                    }}
                                    placeholder="Pilih Kategori"
                                    className="w-full text-xs"
                                />
                            </div>

                            <div className="w-full sm:w-44">
                                <SearchableSelect
                                    options={[
                                        { value: 'all', label: 'Semua Status' },
                                        { value: 'Aktif', label: 'Aktif Berlaku' },
                                        { value: 'Dalam Revisi', label: 'Dalam Revisi' },
                                        { value: 'Arsip / Tidak Berlaku', label: 'Arsip' },
                                    ]}
                                    value={selectedStatus}
                                    onChange={(val) => {
                                        setSelectedStatus(val);
                                        handleFilter(selectedCategory, val);
                                    }}
                                    placeholder="Status"
                                    className="w-full text-xs"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* 3. Tabel Dokumen Internal */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-zinc-50/75 dark:bg-zinc-800/50">
                                    <TableHead className="w-28 text-xs">Kode</TableHead>
                                    <TableHead className="text-xs">Judul Dokumen & Deskripsi</TableHead>
                                    <TableHead className="text-xs">Kategori</TableHead>
                                    <TableHead className="w-24 text-xs">Revisi</TableHead>
                                    <TableHead className="w-28 text-xs">Tgl Berlaku</TableHead>
                                    <TableHead className="w-28 text-center text-xs">Status</TableHead>
                                    <TableHead className="w-28 text-center text-xs">Berkas</TableHead>
                                    <TableHead className="w-24 text-right text-xs">Aksi</TableHead>
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
                                                <Badge variant="outline" className="text-[11px] font-normal">
                                                    {doc.category}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-xs font-mono text-zinc-600 dark:text-zinc-400">
                                                {doc.revision_number}
                                            </TableCell>
                                            <TableCell className="text-xs text-zinc-600 dark:text-zinc-400">
                                                {doc.effective_date}
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
                                                                const isPdf = doc.file_url.toLowerCase().endsWith('.pdf') || doc.file_url.includes('.pdf');
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
                                        <TableCell colSpan={8} className="h-32 text-center text-xs text-zinc-400">
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

            {/* MODAL 1: Form Tambah / Edit Dokumen Internal */}
            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-indigo-600">
                            <ShieldCheck className="h-5 w-5" />
                            {editingDoc ? 'Edit Dokumen Internal / SOP' : 'Tambah Dokumen Internal Baru'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Isi metadata dokumen SOP atau peraturan perusahaan beserta lampiran fisik yang terhubung ke Google Drive.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmitForm} className="space-y-3.5 pt-2">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Kode Dokumen *</Label>
                                <Input
                                    type="text"
                                    value={form.data.document_code}
                                    onChange={(e) => form.setData('document_code', e.target.value)}
                                    required
                                    placeholder="e.g. SOP-HCM-001"
                                    className="h-8 text-xs"
                                />
                                {form.errors.document_code && (
                                    <p className="text-[11px] text-rose-500">{form.errors.document_code}</p>
                                )}
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Kategori Dokumen *</Label>
                                <SearchableSelect
                                    options={categoryOptions}
                                    value={form.data.category}
                                    onChange={(val) => form.setData('category', val)}
                                    placeholder="Pilih Kategori"
                                    className="w-full text-xs"
                                />
                                {form.errors.category && (
                                    <p className="text-[11px] text-rose-500">{form.errors.category}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Judul Dokumen *</Label>
                            <Input
                                type="text"
                                value={form.data.title}
                                onChange={(e) => form.setData('title', e.target.value)}
                                required
                                placeholder="Contoh: Prosedur Standar Lembur & Presensi Pabrik"
                                className="h-8 text-xs"
                            />
                            {form.errors.title && (
                                <p className="text-[11px] text-rose-500">{form.errors.title}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Nomor Revisi *</Label>
                                <Input
                                    type="text"
                                    value={form.data.revision_number}
                                    onChange={(e) => form.setData('revision_number', e.target.value)}
                                    required
                                    placeholder="Rev 00"
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Berlaku *</Label>
                                <Input
                                    type="date"
                                    value={form.data.effective_date}
                                    onChange={(e) => form.setData('effective_date', e.target.value)}
                                    required
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Status *</Label>
                                <SearchableSelect
                                    options={[
                                        { value: 'Aktif', label: 'Aktif Berlaku' },
                                        { value: 'Dalam Revisi', label: 'Dalam Revisi' },
                                        { value: 'Arsip / Tidak Berlaku', label: 'Arsip' },
                                    ]}
                                    value={form.data.status}
                                    onChange={(val) => form.setData('status', val)}
                                    placeholder="Status"
                                    className="w-full text-xs"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Deskripsi / Ruang Lingkup (Opsional)</Label>
                            <Textarea
                                rows={2}
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                placeholder="Penjelasan ringkas fungsi dokumen ini..."
                                className="text-xs"
                            />
                        </div>

                        {/* Upload Berkas Fisik ke Google Drive */}
                        <div className="space-y-1.5 p-3 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <Label className="text-xs font-semibold flex items-center gap-1.5 text-zinc-800 dark:text-zinc-200">
                                <Upload className="h-3.5 w-3.5 text-indigo-500" />
                                Unggah Berkas Dokumen Fisik (PDF / DOC / XLS)
                            </Label>
                            <Input
                                type="file"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg"
                                onChange={(e) => {
                                    if (e.target.files && e.target.files[0]) {
                                        form.setData('file_upload', e.target.files[0]);
                                    }
                                }}
                                className="text-xs file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:border-0 file:rounded file:px-2 file:py-1 hover:file:bg-indigo-100"
                            />
                            <p className="text-[10px] text-zinc-500">
                                Berkas otomatis tersimpan di storage server & disinkronkan ke Google Drive folder <code>01_Dokumen_Internal</code>.
                            </p>

                            <div className="pt-1.5 border-t border-zinc-200/60 dark:border-zinc-800">
                                <Label className="text-[11px] text-zinc-500">Atau Tautan Penyimpanan Cloud Eksternal (Opsional)</Label>
                                <Input
                                    type="url"
                                    value={form.data.file_url}
                                    onChange={(e) => form.setData('file_url', e.target.value)}
                                    placeholder="https://drive.google.com/..."
                                    className="text-xs h-7 mt-0.5"
                                />
                            </div>
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

            {/* MODAL 2: In-App Document Preview Modal */}
            <Dialog open={!!previewFile} onOpenChange={(open) => !open && setPreviewFile(null)}>
                <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col p-4">
                    <DialogHeader className="pb-2 border-b">
                        <div className="flex items-center justify-between">
                            <DialogTitle className="text-sm font-semibold flex items-center gap-2">
                                <FileText className="h-4 w-4 text-indigo-600" />
                                {previewFile?.name || 'Pratinjau Dokumen'}
                            </DialogTitle>
                            {previewFile?.url && (
                                <a
                                    href={previewFile.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-xs text-indigo-600 hover:underline flex items-center gap-1"
                                >
                                    <ExternalLink className="h-3.5 w-3.5" />
                                    Buka di Tab Baru
                                </a>
                            )}
                        </div>
                    </DialogHeader>
                    <div className="flex-1 w-full min-h-[500px] bg-zinc-100 dark:bg-zinc-900 rounded-lg overflow-hidden flex items-center justify-center p-2">
                        {previewFile?.isPdf ? (
                            <iframe
                                src={previewFile.url}
                                className="w-full h-full min-h-[500px] border-0 rounded"
                                title="Pratinjau PDF"
                            />
                        ) : (
                            <img
                                src={previewFile?.url}
                                alt="Pratinjau Berkas"
                                className="max-h-[600px] max-w-full object-contain rounded shadow"
                                onError={(e) => {
                                    e.currentTarget.style.display = 'none';
                                }}
                            />
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
