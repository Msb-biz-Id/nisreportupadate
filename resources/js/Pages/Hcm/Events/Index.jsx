import { Head, router, useForm } from '@inertiajs/react';
import { formatDate } from '@/lib/utils';
import { useMemo, useState } from 'react';
import { CalendarDays, Plus, Edit2, Trash2 } from 'lucide-react';
import { createColumnHelper } from '@tanstack/react-table';
import AppLayout from '@/Layouts/AppLayout';
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

export default function HcmEventsIndex({
    events,
    filters,
    eventTypes,
    today,
}) {
    const [typeFilter, setTypeFilter] = useState(filters.type || 'all');
    const [monthFilter, setMonthFilter] = useState(filters.month || 'all');
    const [yearFilter, setYearFilter] = useState(filters.year || new Date().getFullYear().toString());

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingEvent, setEditingEvent] = useState(null);

    const currentYear = new Date().getFullYear();
    const months = [
        { label: 'Semua Bulan', value: 'all' },
        { label: 'Januari', value: '1' },
        { label: 'Februari', value: '2' },
        { label: 'Maret', value: '3' },
        { label: 'April', value: '4' },
        { label: 'Mei', value: '5' },
        { label: 'Juni', value: '6' },
        { label: 'Juli', value: '7' },
        { label: 'Agustus', value: '8' },
        { label: 'September', value: '9' },
        { label: 'Oktober', value: '10' },
        { label: 'November', value: '11' },
        { label: 'Desember', value: '12' },
    ];

    const yearOptions = [
        { label: 'Semua Tahun', value: 'all' },
        { label: `${currentYear + 1}`, value: `${currentYear + 1}` },
        { label: `${currentYear}`, value: `${currentYear}` },
        { label: `${currentYear - 1}`, value: `${currentYear - 1}` },
    ];

    const typeOptions = [
        { label: 'Semua Kategori', value: 'all' },
        ...eventTypes.map((t) => ({ label: t, value: t })),
    ];

    const emptyForm = {
        title: '',
        event_type: 'Acara Perusahaan',
        organizer_name: '',
        start_date: today,
        end_date: today,
        start_time: '09:00',
        end_time: '17:00',
        location: '',
        target_audience: 'Semua Tim',
        reminder_days: 3,
        is_annual_recurring: false,
        invitation_file_url: '',
        description: '',
        color_code: '#3b82f6',
        is_public: true,
    };

    const form = useForm(emptyForm);

    const handleFilterChange = (newType, newMonth, newYear) => {
        router.get(
            route('hcm.events.index'),
            { type: newType, month: newMonth, year: newYear },
            { preserveState: true, replace: true }
        );
    };

    const openAddModal = () => {
        setEditingEvent(null);
        form.reset({ ...emptyForm, start_date: today, end_date: today });
        setIsModalOpen(true);
    };

    const openEditModal = (ev) => {
        setEditingEvent(ev);
        form.setData({
            title: ev.title,
            event_type: ev.event_type,
            organizer_name: ev.organizer_name || '',
            start_date: (ev.start_date || '').slice(0, 10),
            end_date: (ev.end_date || '').slice(0, 10),
            start_time: ev.start_time || '',
            end_time: ev.end_time || '',
            location: ev.location || '',
            target_audience: ev.target_audience || 'Semua Tim',
            reminder_days: ev.reminder_days ?? 3,
            is_annual_recurring: !!ev.is_annual_recurring,
            invitation_file_url: ev.invitation_file_url || '',
            description: ev.description || '',
            color_code: ev.color_code || '#3b82f6',
            is_public: ev.is_public ?? true,
        });
        setIsModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingEvent) {
            form.put(route('hcm.events.update', editingEvent.uuid || editingEvent.id), {
                onSuccess: () => setIsModalOpen(false),
            });
        } else {
            form.post(route('hcm.events.store'), {
                onSuccess: () => setIsModalOpen(false),
            });
        }
    };

    const handleDelete = (ev) => {
        if (confirm(`Hapus kegiatan "${ev.title}"?`)) {
            router.delete(route('hcm.events.destroy', ev.uuid || ev.id));
        }
    };

    const isUpcoming = (ev) => new Date(ev.end_date) >= new Date(today);

    const columnHelper = createColumnHelper();

    const columns = useMemo(
        () => [
            columnHelper.display({
                id: 'no',
                header: '#',
                size: 44,
                cell: ({ row }) => (
                    <span className="font-mono text-zinc-400">{row.index + 1}</span>
                ),
            }),
            columnHelper.accessor('title', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Judul Kegiatan" />,
                size: 280,
                cell: ({ row }) => (
                    <div>
                        <div className="flex items-center gap-1.5 font-semibold text-zinc-900 dark:text-zinc-100">
                            {row.original.title}
                            {row.original.is_annual_recurring && (
                                <Badge variant="outline" className="text-[10px]">Tahunan</Badge>
                            )}
                        </div>
                        {row.original.description && (
                            <div className="text-[11px] text-zinc-500 line-clamp-1">{row.original.description}</div>
                        )}
                    </div>
                ),
            }),
            columnHelper.accessor('event_type', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
                size: 150,
                cell: ({ row }) => (
                    <Badge
                        variant="outline"
                        className="text-[10px] font-semibold"
                        style={{ borderColor: row.original.color_code, color: row.original.color_code }}
                    >
                        {row.original.event_type}
                    </Badge>
                ),
            }),
            columnHelper.accessor('start_date', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Tanggal & Waktu" />,
                size: 180,
                cell: ({ row }) => (
                    <div className="font-mono text-xs">
                        {formatDate(row.original.start_date)}
                        {row.original.start_date !== row.original.end_date && ` – ${formatDate(row.original.end_date)}`}
                        {row.original.start_time && (
                            <div className="text-[10px] text-zinc-400">
                                {row.original.start_time}{row.original.end_time ? ` – ${row.original.end_time}` : ''} WIB
                            </div>
                        )}
                    </div>
                ),
            }),
            columnHelper.accessor('location', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Lokasi" />,
                size: 150,
                cell: ({ getValue }) => <span className="truncate">{getValue() || '-'}</span>,
            }),
            columnHelper.accessor('organizer_name', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Penyelenggara" />,
                size: 150,
                cell: ({ row }) => (
                    <span className="truncate">{row.original.organizer_name || row.original.creator?.name || '-'}</span>
                ),
            }),
            columnHelper.display({
                id: 'status',
                header: 'Status',
                size: 100,
                cell: ({ row }) =>
                    isUpcoming(row.original) ? (
                        <Badge className="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-[10px]">
                            Mendatang
                        </Badge>
                    ) : (
                        <Badge variant="secondary" className="text-[10px]">Selesai</Badge>
                    ),
            }),
            columnHelper.display({
                id: 'actions',
                header: () => <span className="sr-only">Aksi</span>,
                size: 88,
                meta: { pinned: 'right' },
                cell: ({ row }) => (
                    <div className="flex items-center justify-end gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7 text-zinc-400 hover:text-blue-600"
                            onClick={() => openEditModal(row.original)}
                        >
                            <Edit2 className="w-3.5 h-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7 text-zinc-400 hover:text-rose-600"
                            onClick={() => handleDelete(row.original)}
                        >
                            <Trash2 className="w-3.5 h-3.5" />
                        </Button>
                    </div>
                ),
            }),
        ],
        [today]
    );

    return (
        <AppLayout
            title="Kalender & Event"
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <CalendarDays className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Agenda Kegiatan Perusahaan
                    </span>
                </div>
            }
        >
            <Head title="Agenda Kegiatan Perusahaan" />

            <div className="space-y-4">
                {/* Clean Top Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p className="text-xs text-zinc-500 dark:text-zinc-400">
                        Kegiatan korporat, gathering, rapat internal, pelatihan, dan hari libur nasional.
                    </p>

                    <Button
                        onClick={openAddModal}
                        className="bg-indigo-600 hover:bg-indigo-700 text-white gap-1.5 text-xs font-semibold shadow-xs w-full sm:w-auto"
                    >
                        <Plus className="w-4 h-4" />
                        Tambah Kegiatan
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={events || []}
                    tableClassName="table-fixed min-w-[850px] border-collapse whitespace-nowrap text-xs"
                    searchPlaceholder="Cari kegiatan / lokasi / deskripsi..."
                    exportFileName="Agenda_Kegiatan_Perusahaan"
                    emptyMessage="Tidak ada agenda kegiatan yang cocok dengan kriteria filter."
                    filterSlot={
                        <>
                            <div className="w-52">
                                <SearchableSelect
                                    value={typeFilter}
                                    onValueChange={(val) => {
                                        setTypeFilter(val);
                                        handleFilterChange(val, monthFilter, yearFilter);
                                    }}
                                    options={typeOptions}
                                    placeholder="Kategori Acara"
                                    className="h-9 text-xs"
                                />
                            </div>
                            <div className="w-40">
                                <SearchableSelect
                                    value={monthFilter}
                                    onValueChange={(val) => {
                                        setMonthFilter(val);
                                        handleFilterChange(typeFilter, val, yearFilter);
                                    }}
                                    options={months}
                                    placeholder="Bulan"
                                    className="h-9 text-xs"
                                />
                            </div>
                            <div className="w-36">
                                <SearchableSelect
                                    value={yearFilter}
                                    onValueChange={(val) => {
                                        setYearFilter(val);
                                        handleFilterChange(typeFilter, monthFilter, val);
                                    }}
                                    options={yearOptions}
                                    placeholder="Tahun"
                                    className="h-9 text-xs"
                                />
                            </div>
                        </>
                    }
                />

                <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <CalendarDays className="w-5 h-5 text-blue-600" />
                                {editingEvent ? 'Edit Agenda Acara' : 'Tambah Agenda Acara'}
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Masukkan rincian kegiatan ke kalender perusahaan.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSubmit} className="space-y-3 pt-1">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Judul Kegiatan *</Label>
                                <Input
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    placeholder="Contoh: Gathering Tahunan / Libur Idul Fitri"
                                    className="text-xs"
                                    required
                                />
                                {form.errors.title && (
                                    <p className="text-[11px] text-rose-500">{form.errors.title}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Kategori Kegiatan *</Label>
                                <SearchableSelect
                                    value={form.data.event_type}
                                    onValueChange={(val) => form.setData('event_type', val)}
                                    options={eventTypes.map((t) => ({ label: t, value: t }))}
                                    placeholder="Pilih kategori"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Tanggal Mulai *</Label>
                                    <Input
                                        type="date"
                                        value={form.data.start_date}
                                        onChange={(e) => form.setData('start_date', e.target.value)}
                                        className="text-xs"
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Tanggal Selesai *</Label>
                                    <Input
                                        type="date"
                                        value={form.data.end_date}
                                        onChange={(e) => form.setData('end_date', e.target.value)}
                                        className="text-xs"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Jam Mulai</Label>
                                    <Input
                                        type="time"
                                        value={form.data.start_time}
                                        onChange={(e) => form.setData('start_time', e.target.value)}
                                        className="text-xs"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Jam Selesai</Label>
                                    <Input
                                        type="time"
                                        value={form.data.end_time}
                                        onChange={(e) => form.setData('end_time', e.target.value)}
                                        className="text-xs"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Lokasi / Tempat</Label>
                                <Input
                                    value={form.data.location}
                                    onChange={(e) => form.setData('location', e.target.value)}
                                    placeholder="Contoh: Hall Pabrik / Villa Kaliurang"
                                    className="text-xs"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Penyelenggara / Pengundang</Label>
                                    <Input
                                        value={form.data.organizer_name}
                                        onChange={(e) => form.setData('organizer_name', e.target.value)}
                                        placeholder="Manajemen NISGroup / Sdr. Rian (Sewing)"
                                        className="text-xs"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Target Peserta</Label>
                                    <Input
                                        value={form.data.target_audience}
                                        onChange={(e) => form.setData('target_audience', e.target.value)}
                                        placeholder="Semua Tim / Divisi Produksi"
                                        className="text-xs"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Pengingat (H-N)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={form.data.reminder_days}
                                        onChange={(e) => form.setData('reminder_days', parseInt(e.target.value) || 0)}
                                        className="text-xs"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Lampiran Undangan (URL)</Label>
                                    <Input
                                        value={form.data.invitation_file_url}
                                        onChange={(e) => form.setData('invitation_file_url', e.target.value)}
                                        placeholder="https://..."
                                        className="text-xs"
                                    />
                                </div>
                                <div className="space-y-1.5 flex flex-col justify-end">
                                    <Label className="text-xs font-semibold">Berulang Tahunan</Label>
                                    <button
                                        type="button"
                                        onClick={() => form.setData('is_annual_recurring', !form.data.is_annual_recurring)}
                                        className={`h-9 rounded-md border px-3 text-xs text-left transition ${form.data.is_annual_recurring ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-white text-zinc-600'}`}
                                    >
                                        {form.data.is_annual_recurring ? 'Ya (agenda tahunan)' : 'Tidak'}
                                    </button>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Warna Label / Badge</Label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        type="color"
                                        value={form.data.color_code}
                                        onChange={(e) => form.setData('color_code', e.target.value)}
                                        className="w-12 h-8 p-1 cursor-pointer"
                                    />
                                    <span className="text-xs font-mono text-zinc-500">{form.data.color_code}</span>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Deskripsi Kegiatan</Label>
                                <Textarea
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder="Rincian acara, pakaian yang dikenakan, dll..."
                                    rows={2}
                                    className="text-xs"
                                />
                            </div>

                            <DialogFooter className="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsModalOpen(false)}
                                    disabled={form.processing}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    className="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs"
                                >
                                    {form.processing ? 'Menyimpan...' : (editingEvent ? 'Perbarui Acara' : 'Simpan Acara')}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
