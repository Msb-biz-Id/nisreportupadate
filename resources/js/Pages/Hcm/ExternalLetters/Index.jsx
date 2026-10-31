import React, { useState, useMemo } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { SearchableSelect } from '@/Components/ui/searchable-select';
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
import { Mail, Plus, Edit2, Trash2, ExternalLink, Inbox, Send, RotateCcw } from 'lucide-react';

export default function ExternalLetterIndex({ letters, filters = {}, metrics = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [direction, setDirection] = useState(filters.direction || 'all');
    const [year, setYear] = useState(filters.year || new Date().getFullYear().toString());

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const form = useForm({
        letter_date: new Date().toISOString().split('T')[0],
        direction: 'Surat Masuk',
        external_letter_no: '',
        sender: '',
        recipient: '',
        subject: '',
        file_upload: null,
        file_scan_url: '',
        notes: '',
    });

    const applyFilter = (s = search, d = direction, y = year, page = 1, perPage = letters?.per_page || 15) => {
        router.get(
            route('hcm.external-letters.index'),
            {
                search: s,
                direction: d,
                year: y,
                page,
                per_page: perPage,
            },
            { preserveState: true }
        );
    };

    const handleReset = () => {
        const curYear = new Date().getFullYear().toString();
        setSearch('');
        setDirection('all');
        setYear(curYear);
        router.get(
            route('hcm.external-letters.index'),
            { search: '', direction: 'all', year: curYear, page: 1, per_page: letters?.per_page || 15 },
            { preserveState: true }
        );
    };

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.setData({
            letter_date: new Date().toISOString().split('T')[0],
            direction: 'Surat Masuk',
            external_letter_no: '',
            sender: '',
            recipient: '',
            subject: '',
            file_upload: null,
            file_scan_url: '',
            notes: '',
        });
        setIsModalOpen(true);
    };

    const openEdit = (l) => {
        setEditing(l);
        form.setData({
            letter_date: l.letter_date,
            direction: l.direction,
            external_letter_no: l.external_letter_no,
            sender: l.sender,
            recipient: l.recipient,
            subject: l.subject,
            file_upload: null,
            file_scan_url: l.file_scan_url || '',
            notes: l.notes || '',
        });
        setIsModalOpen(true);
    };

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            form.put(route('hcm.external-letters.update', editing.registration_no || editing.id), {
                forceFormData: true,
                onSuccess: () => setIsModalOpen(false),
            });
        } else {
            form.post(route('hcm.external-letters.store'), {
                forceFormData: true,
                onSuccess: () => setIsModalOpen(false),
            });
        }
    };

    const destroy = (l) => {
        if (!confirm(`Hapus surat eksternal ${l.registration_no}?`)) return;
        router.delete(route('hcm.external-letters.destroy', l.registration_no || l.id), { preserveScroll: true });
    };

    const rows = letters?.data || [];

    const columns = useMemo(
        () => [
            {
                accessorKey: 'registration_no',
                header: ({ column }) => <DataTableColumnHeader column={column} title="No. Registrasi" />,
                size: 130,
                minSize: 110,
                cell: ({ row }) => (
                    <span className="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                        {row.original.registration_no}
                    </span>
                ),
            },
            {
                accessorKey: 'direction',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Arah" />,
                size: 120,
                minSize: 105,
                cell: ({ row }) => {
                    const dir = row.original.direction;
                    const isMasuk = dir === 'Surat Masuk';
                    return (
                        <Badge
                            className={`text-[10px] font-medium border-0 ${
                                isMasuk
                                    ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300'
                                    : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                            }`}
                        >
                            {dir}
                        </Badge>
                    );
                },
            },
            {
                accessorKey: 'external_letter_no',
                header: ({ column }) => <DataTableColumnHeader column={column} title="No. Surat & Perihal" />,
                size: 280,
                minSize: 220,
                cell: ({ row }) => (
                    <div className="max-w-xs sm:max-w-md">
                        <div className="text-xs font-semibold text-zinc-900 dark:text-zinc-100 line-clamp-1">
                            {row.original.external_letter_no}
                        </div>
                        <div className="text-[11px] text-zinc-500 dark:text-zinc-400 line-clamp-1">
                            {row.original.subject}
                        </div>
                    </div>
                ),
            },
            {
                id: 'parties',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Pengirim / Penerima" />,
                size: 220,
                minSize: 180,
                cell: ({ row }) => (
                    <div className="text-xs">
                        <div>
                            <span className="text-zinc-400">Dari: </span>
                            <span className="font-medium text-zinc-800 dark:text-zinc-200">{row.original.sender}</span>
                        </div>
                        <div className="text-[11px] text-zinc-500 dark:text-zinc-400">
                            <span className="text-zinc-400">Kpd: </span>
                            {row.original.recipient}
                        </div>
                    </div>
                ),
            },
            {
                accessorKey: 'letter_date',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Tanggal Surat" />,
                size: 120,
                minSize: 105,
                cell: ({ row }) => (
                    <span className="text-xs text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                        {(row.original.letter_date || '').slice(0, 10)}
                    </span>
                ),
            },
            {
                id: 'file_scan',
                header: ({ column }) => <DataTableColumnHeader column={column} title="Berkas" />,
                size: 110,
                minSize: 95,
                cell: ({ row }) => {
                    const url = row.original.file_scan_url;
                    if (!url) return <span className="text-[11px] text-zinc-400">—</span>;
                    return (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.open(url, '_blank')}
                            className="h-6 text-[10px] px-2 gap-1 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800"
                        >
                            <ExternalLink className="h-3 w-3" /> Lihat
                        </Button>
                    );
                },
            },
            {
                id: 'actions',
                size: 100,
                minSize: 90,
                pinned: 'right',
                header: () => <span className="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Aksi</span>,
                cell: ({ row }) => {
                    const l = row.original;
                    return (
                        <div className="flex items-center justify-end gap-1">
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => openEdit(l)}
                                className="h-7 w-7 p-0 text-zinc-600 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400"
                                title="Edit"
                            >
                                <Edit2 className="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => destroy(l)}
                                className="h-7 w-7 p-0 text-rose-500 hover:text-rose-700 dark:hover:text-rose-400"
                                title="Hapus"
                            >
                                <Trash2 className="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    );
                },
            },
        ],
        []
    );

    const filterSlot = (
        <div className="flex flex-wrap items-center gap-2">
            <div className="min-w-[160px] w-auto">
                <SearchableSelect
                    value={direction}
                    onValueChange={(v) => {
                        setDirection(v);
                        applyFilter(search, v, year, 1, letters?.per_page);
                    }}
                    options={[
                        { value: 'all', label: 'Semua Arah' },
                        { value: 'Surat Masuk', label: 'Surat Masuk' },
                        { value: 'Surat Keluar', label: 'Surat Keluar' },
                    ]}
                    placeholder="Arah Surat"
                    className="text-xs h-8"
                />
            </div>
            <div className="min-w-[110px] w-28">
                <Input
                    type="number"
                    value={year}
                    onChange={(e) => setYear(e.target.value)}
                    onBlur={() => applyFilter(search, direction, year, 1, letters?.per_page)}
                    placeholder="Tahun"
                    className="h-8 text-xs"
                />
            </div>
            {(direction !== 'all' || search || year !== new Date().getFullYear().toString()) && (
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={handleReset}
                    className="h-8 text-xs px-2 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 gap-1"
                >
                    <RotateCcw className="h-3.5 w-3.5" />
                    Reset
                </Button>
            )}
        </div>
    );

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Mail className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">Surat Eksternal</span>
                </div>
            }
        >
            <Head title="Surat Eksternal" />

            <div className="space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-bold text-zinc-900 dark:text-zinc-100">Arsip Korespondensi Eksternal</h1>
                        <p className="text-xs text-zinc-500 mt-0.5">Surat-menyurat dengan instansi eksternal (BPJS-TK, Disnaker, Bank, Mitra).</p>
                    </div>
                    <Button onClick={openCreate} size="sm" className="gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs shadow-xs">
                        <Plus className="h-4 w-4" /> Catat Surat Eksternal
                    </Button>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400">
                                <Mail className="h-5 w-5" />
                            </div>
                            <div>
                                <div className="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Total Surat</div>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">{metrics.total_letters ?? 0}</div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400">
                                <Inbox className="h-5 w-5" />
                            </div>
                            <div>
                                <div className="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Surat Masuk</div>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">{metrics.incoming ?? 0}</div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                                <Send className="h-5 w-5" />
                            </div>
                            <div>
                                <div className="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Surat Keluar</div>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">{metrics.outgoing ?? 0}</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <DataTable
                    columns={columns}
                    data={rows}
                    searchPlaceholder="Cari no. surat, perihal, atau pengirim..."
                    filterSlot={filterSlot}
                    serverPagination={letters}
                    onServerPageChange={(newPage) => applyFilter(search, direction, year, newPage, letters?.per_page)}
                    onServerPerPageChange={(newPerPage) => applyFilter(search, direction, year, 1, newPerPage)}
                    serverSearch={search}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        applyFilter(val, direction, year, 1, letters?.per_page);
                    }}
                    enableColumnVisibility={true}
                    enableExport={true}
                    exportFilename={`hcm_surat_eksternal_${new Date().toISOString().slice(0, 10)}`}
                    exportTitle="Daftar Surat Eksternal HCM"
                />
            </div>

            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold">
                            <Mail className="w-5 h-5 text-indigo-600" /> {editing ? 'Edit' : 'Catat'} Surat Eksternal
                        </DialogTitle>
                        <DialogDescription className="text-xs">Korespondensi resmi dengan instansi eksternal.</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-3 pt-1">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Arah Surat *</Label>
                                <SearchableSelect
                                    value={form.data.direction}
                                    onValueChange={(v) => form.setData('direction', v)}
                                    options={[
                                        { value: 'Surat Masuk', label: 'Surat Masuk' },
                                        { value: 'Surat Keluar', label: 'Surat Keluar' },
                                    ]}
                                    placeholder="Pilih"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Tanggal Dokumen *</Label>
                                <Input
                                    type="date"
                                    value={form.data.letter_date}
                                    onChange={(e) => form.setData('letter_date', e.target.value)}
                                    className="text-xs"
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Nomor Surat Eksternal *</Label>
                            <Input
                                value={form.data.external_letter_no}
                                onChange={(e) => form.setData('external_letter_no', e.target.value)}
                                placeholder="055/BPJS-TK/IX/2024"
                                className="text-xs"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Pengirim / Instansi *</Label>
                                <Input
                                    value={form.data.sender}
                                    onChange={(e) => form.setData('sender', e.target.value)}
                                    placeholder="BPJS Ketenagakerjaan"
                                    className="text-xs"
                                    required
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Penerima / Tujuan *</Label>
                                <Input
                                    value={form.data.recipient}
                                    onChange={(e) => form.setData('recipient', e.target.value)}
                                    placeholder="HCM Dept"
                                    className="text-xs"
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Perihal Dokumen *</Label>
                            <Textarea
                                value={form.data.subject}
                                onChange={(e) => form.setData('subject', e.target.value)}
                                rows={2}
                                className="text-xs"
                                placeholder="Undangan Sosialisasi Program JKP"
                                required
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Scan Berkas (PDF/JPG/PNG)</Label>
                            <Input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                onChange={(e) => e.target.files?.[0] && form.setData('file_upload', e.target.files[0])}
                                className="text-xs file:text-xs"
                            />
                            <p className="text-[10px] text-zinc-500">Berkas tersimpan di penyimpanan sistem.</p>
                        </div>

                        <DialogFooter className="pt-2 gap-2">
                            <Button type="button" variant="outline" size="sm" onClick={() => setIsModalOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" size="sm" disabled={form.processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {form.processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
