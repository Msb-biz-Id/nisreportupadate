import React, { useState, useMemo } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import UniversalDocumentViewer from '@/Components/Hcm/UniversalDocumentViewer';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { DataTable } from '@/Components/DataTable/DataTable';
import { DataTableColumnHeader } from '@/Components/DataTable/DataTableColumnHeader';
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
    Mail,
    Send,
    Inbox,
    FileText,
    Search,
    Plus,
    Edit2,
    Trash2,
    Calendar,
    MapPin,
    ExternalLink,
    X,
    Check,
    Archive,
    BookOpen,
    Upload,
    Eye,
    RotateCcw,
} from 'lucide-react';

export default function LetterIndex({ auth, letters, filters, metrics, letterTypes }) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedType, setSelectedType] = useState(filters.type || 'all');
    const [selectedYear, setSelectedYear] = useState(filters.year || new Date().getFullYear().toString());

    // Modal State
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editingLetter, setEditingLetter] = useState(null);
    const [previewFile, setPreviewFile] = useState(null);

    const form = useForm({
        letter_type: 'SURAT_MASUK',
        letter_number: '',
        letter_date: new Date().toISOString().split('T')[0],
        received_or_sent_date: new Date().toISOString().split('T')[0],
        sender: '',
        recipient: '',
        subject: '',
        category: 'Umum',
        file_upload: null,
        file_url: '',
        physical_location: 'Ordner Arsip HCM',
        notes: '',
    });

    const handleFilter = (typeVal = selectedType, yearVal = selectedYear, searchVal = search, page = 1, perPage = letters?.per_page || 12) => {
        router.get(
            route('hcm.letters.index'),
            {
                search: searchVal,
                type: typeVal,
                year: yearVal,
                page,
                per_page: perPage,
            },
            { preserveState: true }
        );
    };

    const handleReset = () => {
        const curYear = new Date().getFullYear().toString();
        setSearch('');
        setSelectedType('all');
        setSelectedYear(curYear);
        router.get(
            route('hcm.letters.index'),
            { search: '', type: 'all', year: curYear, page: 1, per_page: letters?.per_page || 12 },
            { preserveState: true }
        );
    };

    const openCreateModal = () => {
        form.reset();
        form.setData({
            letter_type: 'SURAT_MASUK',
            letter_number: '',
            letter_date: new Date().toISOString().split('T')[0],
            received_or_sent_date: new Date().toISOString().split('T')[0],
            sender: '',
            recipient: '',
            subject: '',
            category: 'Umum',
            disposition_status: '',
            file_upload: null,
            file_url: '',
            physical_location: 'Ordner Arsip HCM',
            notes: '',
        });
        setEditingLetter(null);
        setIsCreateOpen(true);
    };

    const openEditModal = (letter) => {
        setEditingLetter(letter);
        form.setData({
            letter_type: letter.letter_type,
            letter_number: letter.letter_number,
            letter_date: letter.letter_date,
            received_or_sent_date: letter.received_or_sent_date || '',
            sender: letter.sender,
            recipient: letter.recipient,
            subject: letter.subject,
            category: letter.category || 'Umum',
            disposition_status: letter.disposition_status || '',
            file_upload: null,
            file_url: letter.file_url || '',
            physical_location: letter.physical_location || 'Ordner Arsip HCM',
            notes: letter.notes || '',
        });
        setIsCreateOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingLetter) {
            form.post(route('hcm.letters.update', editingLetter.agenda_number || editingLetter.id), {
                forceFormData: true,
                data: {
                    ...form.data,
                    _method: 'PUT',
                },
                onSuccess: () => {
                    setIsCreateOpen(false);
                    setEditingLetter(null);
                },
            });
        } else {
            form.post(route('hcm.letters.store'), {
                forceFormData: true,
                onSuccess: () => {
                    setIsCreateOpen(false);
                },
            });
        }
    };

    const handleDelete = (letter) => {
        if (confirm(`Yakin ingin menghapus agenda surat nomor "${letter.letter_number}"?`)) {
            router.delete(route('hcm.letters.destroy', letter.agenda_number || letter.id));
        }
    };

    const getTypeBadge = (type) => {
        switch (type) {
            case 'SURAT_MASUK':
                return (
                    <Badge className="bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-300 dark:border-sky-800 text-[11px] gap-1">
                        <Inbox className="w-3 h-3" /> Surat Masuk
                    </Badge>
                );
            case 'SURAT_KELUAR':
                return (
                    <Badge className="bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border-indigo-300 dark:border-indigo-800 text-[11px] gap-1">
                        <Send className="w-3 h-3" /> Surat Keluar
                    </Badge>
                );
            case 'INTERNAL_MEMO':
                return (
                    <Badge className="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-800 text-[11px] gap-1">
                        <FileText className="w-3 h-3" /> Internal Memo
                    </Badge>
                );
            case 'SK_DIREKSI':
                return (
                    <Badge className="bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-300 dark:border-purple-800 text-[11px] gap-1">
                        <BookOpen className="w-3 h-3" /> SK Direksi
                    </Badge>
                );
            default:
                return <Badge variant="outline" className="text-[11px]">{type}</Badge>;
        }
    };

    const columns = useMemo(() => [
        {
            accessorKey: 'agenda_number',
            header: ({ column }) => <DataTableColumnHeader column={column} title="No. Agenda" />,
            size: 120,
            minSize: 100,
            cell: ({ row }) => (
                <span className="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400">
                    {row.original.agenda_number}
                </span>
            ),
        },
        {
            accessorKey: 'letter_type',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Jenis" />,
            size: 140,
            minSize: 120,
            cell: ({ row }) => getTypeBadge(row.original.letter_type),
        },
        {
            id: 'subject',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nomor Surat & Perihal" />,
            size: 280,
            minSize: 220,
            cell: ({ row }) => {
                const letter = row.original;
                return (
                    <div>
                        <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                            {letter.subject}
                        </div>
                        <div className="text-[11px] font-mono text-zinc-500 mt-0.5">
                            No: {letter.letter_number}
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'parties',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Pengirim & Penerima" />,
            size: 220,
            minSize: 180,
            cell: ({ row }) => {
                const letter = row.original;
                return (
                    <div>
                        <div className="text-xs text-zinc-800 dark:text-zinc-200">
                            <span className="text-zinc-400 text-[10px]">Dari:</span> {letter.sender}
                        </div>
                        <div className="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                            <span className="text-zinc-400 text-[10px]">Kpd:</span> {letter.recipient}
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'dates',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Tanggal" />,
            size: 130,
            minSize: 110,
            cell: ({ row }) => {
                const letter = row.original;
                return (
                    <div className="text-xs text-zinc-600 dark:text-zinc-400">
                        <div>{letter.letter_date}</div>
                        {letter.received_or_sent_date && (
                            <div className="text-[10px] text-zinc-400">
                                Tercatat: {letter.received_or_sent_date}
                            </div>
                        )}
                    </div>
                );
            },
        },
        {
            accessorKey: 'disposition_status',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Disposisi" />,
            size: 130,
            minSize: 110,
            cell: ({ row }) => (
                <span className="text-[11px] text-zinc-600 dark:text-zinc-400">
                    {row.original.disposition_status || '-'}
                </span>
            ),
        },
        {
            accessorKey: 'physical_location',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Lokasi Fisik" />,
            size: 150,
            minSize: 130,
            cell: ({ row }) => (
                <div className="flex items-center gap-1 text-[11px] text-zinc-600 dark:text-zinc-400">
                    <MapPin className="w-3 h-3 text-zinc-400 shrink-0" />
                    <span className="truncate max-w-[120px]">{row.original.physical_location || '-'}</span>
                </div>
            ),
        },
        {
            id: 'file',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Berkas" className="justify-center" />,
            size: 110,
            minSize: 95,
            cell: ({ row }) => {
                const letter = row.original;
                return letter.file_url ? (
                    <div className="flex items-center justify-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                                const isPdf = letter.file_url.toLowerCase().endsWith('.pdf') || letter.file_url.includes('.pdf');
                                setPreviewFile({
                                    url: letter.file_url,
                                    name: `Surat ${letter.agenda_number} - ${letter.subject}`,
                                    isPdf: isPdf,
                                });
                            }}
                            className="h-6 text-[10px] px-1.5 gap-1 border-indigo-200 text-indigo-600 hover:bg-indigo-50"
                        >
                            <Eye className="h-3 w-3" />
                            Lihat
                        </Button>
                        <a
                            href={letter.file_url}
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
                );
            },
        },
        {
            id: 'actions',
            size: 100,
            minSize: 90,
            pinned: 'right',
            header: () => <div className="text-right">Aksi</div>,
            cell: ({ row }) => {
                const letter = row.original;
                return (
                    <div className="flex items-center justify-end gap-1">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => openEditModal(letter)}
                            className="h-7 w-7 p-0 text-zinc-500 hover:text-indigo-600"
                            title="Edit Surat"
                        >
                            <Edit2 className="h-3.5 w-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleDelete(letter)}
                            className="h-7 w-7 p-0 text-zinc-500 hover:text-rose-600"
                            title="Hapus Surat"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                );
            },
        },
    ], []);

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <BookOpen className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Buku Agenda Persuratan HCM
                    </span>
                </div>
            }
        >
            <Head title="Buku Agenda Persuratan - HCM" />

            <div className="space-y-4">
                {/* Header Banner Canvas */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <BookOpen className="h-5 w-5 text-indigo-600 shrink-0" />
                            <span>Buku Agenda Persuratan HCM</span>
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Pencatatan tertib surat masuk, surat keluar, memo internal dinas, dan arsip fisik SK Direksi.
                        </p>
                    </div>

                    <Button
                        onClick={openCreateModal}
                        size="sm"
                        className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5 shadow-xs shrink-0 self-start sm:self-auto"
                    >
                        <Plus className="h-3.5 w-3.5" />
                        <span>Catat Surat Baru</span>
                    </Button>
                </div>
                {/* 1. Baris Metrik Ringkasan */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <Mail className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-zinc-500 font-medium">Total Agenda</span>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">{metrics.total_letters}</div>
                        </div>
                    </Card>

                    <Card className="border border-sky-200/80 dark:border-sky-900/40 bg-sky-50/20 dark:bg-sky-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 flex items-center justify-center shrink-0">
                            <Inbox className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-sky-600 font-medium">Surat Masuk</span>
                            <div className="text-xl font-bold text-sky-700 dark:text-sky-300">{metrics.incoming_letters}</div>
                        </div>
                    </Card>

                    <Card className="border border-indigo-200/80 dark:border-indigo-900/40 bg-indigo-50/20 dark:bg-indigo-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 flex items-center justify-center shrink-0">
                            <Send className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-indigo-600 font-medium">Surat Keluar</span>
                            <div className="text-xl font-bold text-indigo-700 dark:text-indigo-300">{metrics.outgoing_letters}</div>
                        </div>
                    </Card>

                    <Card className="border border-purple-200/80 dark:border-purple-900/40 bg-purple-50/20 dark:bg-purple-950/20 shadow-xs p-3.5 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 flex items-center justify-center shrink-0">
                            <Archive className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] text-purple-600 font-medium">SK & Memo Internal</span>
                            <div className="text-xl font-bold text-purple-700 dark:text-purple-300">{metrics.internal_and_sk}</div>
                        </div>
                    </Card>
                </div>

                {/* 2. Tabel Agenda Persuratan with DataTable */}
                <DataTable
                    columns={columns}
                    data={letters.data || []}
                    serverPagination={letters}
                    onServerPageChange={(page) => handleFilter(selectedType, selectedYear, search, page)}
                    onServerPerPageChange={(per_page) => handleFilter(selectedType, selectedYear, search, 1, per_page)}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        handleFilter(selectedType, selectedYear, val, 1);
                    }}
                    searchValue={search}
                    searchPlaceholder="Cari perihal, nomor surat, instansi..."
                    exportFileName={`Agenda_Persuratan_${new Date().toISOString().slice(0, 10)}`}
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-2">
                            <SearchableSelect
                                options={[
                                    { value: 'all', label: 'Semua Jenis Surat' },
                                    { value: 'SURAT_MASUK', label: 'Surat Masuk' },
                                    { value: 'SURAT_KELUAR', label: 'Surat Keluar' },
                                    { value: 'INTERNAL_MEMO', label: 'Internal Memo' },
                                    { value: 'SK_DIREKSI', label: 'SK Direksi' },
                                ]}
                                value={selectedType}
                                onValueChange={(val) => {
                                    setSelectedType(val);
                                    handleFilter(val, selectedYear, search, 1);
                                }}
                                placeholder="Jenis Surat"
                                clearable={false}
                                className="h-8 text-xs w-[170px]"
                            />
                            <SearchableSelect
                                options={[
                                    { value: '2026', label: 'Tahun 2026' },
                                    { value: '2025', label: 'Tahun 2025' },
                                    { value: '2024', label: 'Tahun 2024' },
                                ]}
                                value={selectedYear}
                                onValueChange={(val) => {
                                    setSelectedYear(val);
                                    handleFilter(selectedType, val, search, 1);
                                }}
                                placeholder="Tahun"
                                clearable={false}
                                className="h-8 text-xs w-[120px]"
                            />
                            {(selectedType !== 'all' || search !== '') && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleReset}
                                    className="h-8 text-xs text-zinc-500 hover:text-rose-600 gap-1 px-2"
                                    title="Reset filter"
                                >
                                    <RotateCcw className="h-3 w-3" />
                                    <span>Reset</span>
                                </Button>
                            )}
                        </div>
                    }
                />
            </div>

            {/* MODAL 1: Catat / Edit Surat Agenda */}
            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-indigo-600">
                            <BookOpen className="h-5 w-5" />
                            {editingLetter ? 'Edit Data Agenda Surat' : 'Catat Surat Baru ke Buku Agenda'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Isi lembar disposisi / agenda persuratan fisik dan digital.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-3 pt-2">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Jenis Surat *</Label>
                                <SearchableSelect
                                    options={[
                                        { value: 'SURAT_MASUK', label: 'Surat Masuk' },
                                        { value: 'SURAT_KELUAR', label: 'Surat Keluar' },
                                        { value: 'INTERNAL_MEMO', label: 'Internal Memo Dinas' },
                                        { value: 'SK_DIREKSI', label: 'Surat Keputusan (SK) Direksi' },
                                    ]}
                                    value={form.data.letter_type}
                                    onChange={(val) => form.setData('letter_type', val)}
                                    placeholder="Pilih Jenis"
                                    className="w-full text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Nomor Surat Asli *</Label>
                                <Input
                                    type="text"
                                    value={form.data.letter_number}
                                    onChange={(e) => form.setData('letter_number', e.target.value)}
                                    required
                                    placeholder="e.g. 021/EXT/NIS/IX/2026"
                                    className="h-8 text-xs"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Perihal / Isi Ringkas Surat *</Label>
                            <Input
                                type="text"
                                value={form.data.subject}
                                onChange={(e) => form.setData('subject', e.target.value)}
                                required
                                placeholder="e.g. Surat Pemberitahuan Audit Mutu Kemenperin"
                                className="h-8 text-xs"
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Pengirim (Asal Surat) *</Label>
                                <Input
                                    type="text"
                                    value={form.data.sender}
                                    onChange={(e) => form.setData('sender', e.target.value)}
                                    required
                                    placeholder="e.g. Kementerian Tenaga Kerja RI"
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Penerima (Tujuan Surat) *</Label>
                                <Input
                                    type="text"
                                    value={form.data.recipient}
                                    onChange={(e) => form.setData('recipient', e.target.value)}
                                    required
                                    placeholder="e.g. Direktur Utama PT NIS"
                                    className="h-8 text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Surat *</Label>
                                <Input
                                    type="date"
                                    value={form.data.letter_date}
                                    onChange={(e) => form.setData('letter_date', e.target.value)}
                                    required
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Diterima / Dikirim</Label>
                                <Input
                                    type="date"
                                    value={form.data.received_or_sent_date}
                                    onChange={(e) => form.setData('received_or_sent_date', e.target.value)}
                                    className="h-8 text-xs"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Lokasi Arsip Fisik</Label>
                            <Input
                                type="text"
                                value={form.data.physical_location}
                                onChange={(e) => form.setData('physical_location', e.target.value)}
                                placeholder="Contoh: Ordner Surat Masuk 2026 - Lemari A"
                                className="h-8 text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Status Disposisi</Label>
                            <Input
                                type="text"
                                value={form.data.disposition_status}
                                onChange={(e) => form.setData('disposition_status', e.target.value)}
                                placeholder="Selesai / Diteruskan ke HCM Manager / Menunggu Tindak Lanjut"
                                className="h-8 text-xs"
                            />
                        </div>

                        {/* Upload Berkas Fisik */}
                        <div className="space-y-1.5 p-3 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            <Label className="text-xs font-semibold flex items-center gap-1.5 text-zinc-800 dark:text-zinc-200">
                                <Upload className="h-3.5 w-3.5 text-indigo-500" />
                                Unggah Scan Naskah Surat Fisik (PDF / JPG / PNG)
                            </Label>
                            <Input
                                type="file"
                                accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                                onChange={(e) => {
                                    if (e.target.files && e.target.files[0]) {
                                        form.setData('file_upload', e.target.files[0]);
                                    }
                                }}
                                className="text-xs file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:border-0 file:rounded file:px-2 file:py-1 hover:file:bg-indigo-100"
                            />
                            <p className="text-[10px] text-zinc-500">
                                Berkas otomatis tersimpan di penyimpanan sistem.
                            </p>

                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Catatan / Disposisi Singkat</Label>
                            <Textarea
                                rows={2}
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                                placeholder="Disposisi pimpinan / catatan khusus..."
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5"
                            >
                                {form.processing ? 'Menyimpan...' : editingLetter ? 'Simpan Perubahan' : 'Catat Surat'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 2: In-App Document Preview Modal */}
            <UniversalDocumentViewer
                isOpen={!!previewFile}
                onClose={() => setPreviewFile(null)}
                fileUrl={previewFile?.url}
                fileName={previewFile?.name || 'Surat Agenda'}
            />
        </AppLayout>
    );
}
