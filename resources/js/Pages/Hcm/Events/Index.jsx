import { Head, router, useForm } from '@inertiajs/react';
import { formatDate } from '@/lib/utils';
import { useMemo, useState } from 'react';
import {
    CalendarDays,
    Calendar,
    Plus,
    Edit2,
    Trash2,
    MapPin,
    Users,
    Clock,
    CheckCircle2,
    Sparkles,
    AlertCircle,
    Layers,
    Tag,
} from 'lucide-react';
import { createColumnHelper } from '@tanstack/react-table';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent } from '@/Components/ui/card';
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
    events = [],
    filters = {},
    metrics = {},
    eventTypes = [],
    today,
}) {
    const todayStr = today || new Date().toISOString().slice(0, 10);

    // Filter state
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all'); // 'all', 'upcoming', 'ongoing', 'completed'
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

    const statusOptions = [
        { label: 'Semua Status Pelaksanaan', value: 'all' },
        { label: '⏳ Belum Terlaksana (Mendatang)', value: 'upcoming' },
        { label: '🚀 Sedang Berlangsung', value: 'ongoing' },
        { label: '✅ Sudah Terlaksana (Selesai)', value: 'completed' },
    ];

    const emptyForm = {
        title: '',
        event_type: 'Acara Perusahaan',
        organizer_name: '',
        start_date: todayStr,
        end_date: todayStr,
        start_time: '09:00',
        end_time: '17:00',
        location: '',
        target_audience: 'Semua Tim',
        reminder_days: 3,
        is_annual_recurring: false,
        invitation_file_url: '',
        description: '',
        color_code: '#6366f1',
        is_public: true,
    };

    const form = useForm(emptyForm);

    // Helper status event
    const getEventStatus = (ev) => {
        const evStart = (ev.start_date || '').slice(0, 10);
        const evEnd = (ev.end_date || '').slice(0, 10);

        if (evStart <= todayStr && evEnd >= todayStr) {
            return {
                code: 'ongoing',
                label: 'Sedang Berlangsung',
                badgeClass: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
                dotClass: 'bg-blue-500 animate-pulse',
            };
        }
        if (evStart > todayStr) {
            return {
                code: 'upcoming',
                label: 'Belum Terlaksana',
                badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
                dotClass: 'bg-emerald-500',
            };
        }
        return {
            code: 'completed',
            label: 'Sudah Selesai',
            badgeClass: 'bg-zinc-100 text-zinc-600 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700',
            dotClass: 'bg-zinc-400',
        };
    };

    // Hitung counter status untuk tab
    const eventCounts = useMemo(() => {
        let upcoming = 0;
        let ongoing = 0;
        let completed = 0;

        (events || []).forEach((ev) => {
            const status = getEventStatus(ev).code;
            if (status === 'upcoming') upcoming++;
            else if (status === 'ongoing') ongoing++;
            else if (status === 'completed') completed++;
        });

        return {
            total: (events || []).length,
            upcoming,
            ongoing,
            completed,
        };
    }, [events, todayStr]);

    // Data yang difilter secara instan untuk responsivitas maksimal
    const filteredEvents = useMemo(() => {
        if (statusFilter === 'all') return events;
        return (events || []).filter((ev) => getEventStatus(ev).code === statusFilter);
    }, [events, statusFilter, todayStr]);

    const handleFilterChange = (newStatus, newType, newMonth, newYear) => {
        router.get(
            route('hcm.events.index'),
            { status: newStatus, type: newType, month: newMonth, year: newYear },
            { preserveState: true, replace: true }
        );
    };

    const handleTabChange = (newStatus) => {
        setStatusFilter(newStatus);
        handleFilterChange(newStatus, typeFilter, monthFilter, yearFilter);
    };

    const openAddModal = () => {
        setEditingEvent(null);
        form.reset({ ...emptyForm, start_date: todayStr, end_date: todayStr });
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
            color_code: ev.color_code || '#6366f1',
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

    const columnHelper = createColumnHelper();

    const columns = useMemo(
        () => [
            columnHelper.display({
                id: 'no',
                header: '#',
                size: 48,
                minSize: 44,
                cell: ({ row }) => (
                    <div className="font-mono text-zinc-400 text-center font-medium">
                        {row.index + 1}
                    </div>
                ),
            }),
            columnHelper.accessor('title', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Judul Kegiatan" />,
                size: 320,
                minSize: 260,
                cell: ({ row }) => (
                    <div className="max-w-[340px] whitespace-normal break-words py-0.5 space-y-1">
                        <div className="font-semibold text-zinc-900 dark:text-zinc-100 text-xs leading-snug">
                            {row.original.title}
                        </div>
                        <div className="flex flex-wrap items-center gap-1.5 pt-0.5">
                            {row.original.is_annual_recurring && (
                                <Badge variant="outline" className="text-[10px] h-4 px-1.5 py-0 border-amber-300 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-700">
                                    Tahunan
                                </Badge>
                            )}
                            {row.original.is_public === false && (
                                <Badge variant="outline" className="text-[10px] h-4 px-1.5 py-0 border-zinc-300 text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">
                                    Internal
                                </Badge>
                            )}
                        </div>
                        {row.original.description && (
                            <p className="text-[11px] text-zinc-500 dark:text-zinc-400 line-clamp-2 leading-relaxed">
                                {row.original.description}
                            </p>
                        )}
                    </div>
                ),
            }),
            columnHelper.accessor('event_type', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
                size: 150,
                minSize: 130,
                cell: ({ row }) => {
                    const color = row.original.color_code || '#6366f1';
                    return (
                        <div className="whitespace-normal py-0.5">
                            <span
                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold border"
                                style={{
                                    backgroundColor: `${color}14`,
                                    borderColor: `${color}40`,
                                    color: color,
                                }}
                            >
                                <span className="w-1.5 h-1.5 rounded-full" style={{ backgroundColor: color }} />
                                {row.original.event_type}
                            </span>
                        </div>
                    );
                },
            }),
            columnHelper.accessor('start_date', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Tanggal & Waktu" />,
                size: 190,
                minSize: 160,
                cell: ({ row }) => {
                    const isMultiDay = row.original.start_date !== row.original.end_date;
                    return (
                        <div className="whitespace-normal py-0.5 space-y-1">
                            <div className="flex items-center gap-1.5 text-xs font-medium text-zinc-800 dark:text-zinc-200">
                                <Calendar className="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                                <span>
                                    {formatDate(row.original.start_date)}
                                    {isMultiDay && ` – ${formatDate(row.original.end_date)}`}
                                </span>
                            </div>
                            {row.original.start_time && (
                                <div className="flex items-center gap-1.5 text-[11px] text-zinc-500 font-mono">
                                    <Clock className="w-3 h-3 text-zinc-400 shrink-0" />
                                    <span>
                                        {row.original.start_time}
                                        {row.original.end_time ? ` – ${row.original.end_time}` : ''} WIB
                                    </span>
                                </div>
                            )}
                        </div>
                    );
                },
            }),
            columnHelper.accessor('location', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Lokasi" />,
                size: 180,
                minSize: 140,
                cell: ({ getValue }) => {
                    const val = getValue();
                    if (!val) return <span className="text-zinc-400">-</span>;
                    return (
                        <div className="flex items-start gap-1.5 whitespace-normal break-words max-w-[200px] text-xs text-zinc-700 dark:text-zinc-300 py-0.5">
                            <MapPin className="w-3.5 h-3.5 text-rose-500 shrink-0 mt-0.5" />
                            <span className="line-clamp-2">{val}</span>
                        </div>
                    );
                },
            }),
            columnHelper.accessor('organizer_name', {
                header: ({ column }) => <DataTableColumnHeader column={column} title="Penyelenggara" />,
                size: 170,
                minSize: 140,
                cell: ({ row }) => {
                    const organizer = row.original.organizer_name || row.original.creator?.name || '-';
                    const audience = row.original.target_audience;
                    return (
                        <div className="whitespace-normal break-words max-w-[190px] py-0.5 space-y-1">
                            <div className="text-xs font-medium text-zinc-800 dark:text-zinc-200">
                                {organizer}
                            </div>
                            {audience && audience !== 'Semua Tim' && (
                                <div className="flex items-center gap-1 text-[10px] text-zinc-500">
                                    <Users className="w-3 h-3 text-zinc-400 shrink-0" />
                                    <span className="truncate">{audience}</span>
                                </div>
                            )}
                        </div>
                    );
                },
            }),
            columnHelper.display({
                id: 'status',
                header: 'Status Pelaksanaan',
                size: 140,
                minSize: 130,
                cell: ({ row }) => {
                    const st = getEventStatus(row.original);
                    return (
                        <div className="whitespace-normal py-0.5">
                            <Badge
                                variant="outline"
                                className={`text-[11px] font-medium px-2.5 py-0.5 gap-1.5 border ${st.badgeClass}`}
                            >
                                <span className={`w-1.5 h-1.5 rounded-full shrink-0 ${st.dotClass}`} />
                                {st.label}
                            </Badge>
                        </div>
                    );
                },
            }),
            columnHelper.display({
                id: 'actions',
                header: () => <span className="sr-only">Aksi</span>,
                size: 80,
                minSize: 80,
                meta: { pinned: 'right' },
                cell: ({ row }) => (
                    <div className="flex items-center justify-end gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7 text-zinc-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                            onClick={() => openEditModal(row.original)}
                            title="Edit Kegiatan"
                        >
                            <Edit2 className="w-3.5 h-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-7 w-7 text-zinc-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                            onClick={() => handleDelete(row.original)}
                            title="Hapus Kegiatan"
                        >
                            <Trash2 className="w-3.5 h-3.5" />
                        </Button>
                    </div>
                ),
            }),
        ],
        [todayStr]
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
                {/* Header Deskripsi & Tombol Tambah */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 className="text-base font-bold text-zinc-900 dark:text-zinc-100">
                            Agenda & Kalender Kegiatan Perusahaan
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Pencatatan kegiatan korporat, gathering, rapat internal, pelatihan, dan hari libur nasional.
                        </p>
                    </div>

                    <Button
                        onClick={openAddModal}
                        className="bg-indigo-600 hover:bg-indigo-700 text-white gap-1.5 text-xs font-semibold shadow-xs w-full sm:w-auto"
                    >
                        <Plus className="w-4 h-4" />
                        Tambah Kegiatan
                    </Button>
                </div>

                {/* Status Tabs Bar: Filter Yang Sudah vs Yang Belum */}
                <div className="flex flex-wrap items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2.5 pt-1">
                    <button
                        type="button"
                        onClick={() => handleTabChange('all')}
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                            statusFilter === 'all'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'
                        }`}
                    >
                        <Layers className="w-3.5 h-3.5" />
                        <span>Semua Agenda</span>
                        <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
                            statusFilter === 'all' ? 'bg-indigo-700/80 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'
                        }`}>
                            {eventCounts.total}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('upcoming')}
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                            statusFilter === 'upcoming'
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'
                        }`}
                    >
                        <Clock className="w-3.5 h-3.5" />
                        <span>Belum Terlaksana (Mendatang)</span>
                        <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
                            statusFilter === 'upcoming' ? 'bg-emerald-700/80 text-white' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                        }`}>
                            {eventCounts.upcoming}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('ongoing')}
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                            statusFilter === 'ongoing'
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'
                        }`}
                    >
                        <Sparkles className="w-3.5 h-3.5" />
                        <span>Sedang Berlangsung</span>
                        <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
                            statusFilter === 'ongoing' ? 'bg-blue-700/80 text-white' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300'
                        }`}>
                            {eventCounts.ongoing}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleTabChange('completed')}
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                            statusFilter === 'completed'
                                ? 'bg-zinc-700 text-white shadow-xs'
                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'
                        }`}
                    >
                        <CheckCircle2 className="w-3.5 h-3.5" />
                        <span>Sudah Terlaksana (Selesai)</span>
                        <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
                            statusFilter === 'completed' ? 'bg-zinc-800 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'
                        }`}>
                            {eventCounts.completed}
                        </span>
                    </button>
                </div>

                {/* DataTable Agenda Kegiatan */}
                <div className="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
                    <DataTable
                        columns={columns}
                        data={filteredEvents || []}
                        tableClassName="w-full text-xs border-collapse"
                        searchPlaceholder="Cari judul kegiatan, lokasi, atau deskripsi..."
                        exportFileName="Agenda_Kegiatan_Perusahaan"
                        emptyMessage={
                            statusFilter === 'upcoming'
                                ? 'Tidak ada agenda kegiatan mendatang yang belum terlaksana.'
                                : statusFilter === 'completed'
                                ? 'Tidak ada agenda kegiatan yang sudah selesai pada periode filter ini.'
                                : statusFilter === 'ongoing'
                                ? 'Tidak ada kegiatan yang sedang berlangsung hari ini.'
                                : 'Tidak ada agenda kegiatan yang cocok dengan kriteria filter.'
                        }
                        filterSlot={
                            <>
                                <div className="w-48 sm:w-52">
                                    <SearchableSelect
                                        value={typeFilter}
                                        onValueChange={(val) => {
                                            setTypeFilter(val);
                                            handleFilterChange(statusFilter, val, monthFilter, yearFilter);
                                        }}
                                        options={typeOptions}
                                        placeholder="Pilih Kategori"
                                        className="h-9 text-xs"
                                    />
                                </div>
                                <div className="w-36 sm:w-40">
                                    <SearchableSelect
                                        value={monthFilter}
                                        onValueChange={(val) => {
                                            setMonthFilter(val);
                                            handleFilterChange(statusFilter, typeFilter, val, yearFilter);
                                        }}
                                        options={months}
                                        placeholder="Bulan"
                                        className="h-9 text-xs"
                                    />
                                </div>
                                <div className="w-32 sm:w-36">
                                    <SearchableSelect
                                        value={yearFilter}
                                        onValueChange={(val) => {
                                            setYearFilter(val);
                                            handleFilterChange(statusFilter, typeFilter, monthFilter, val);
                                        }}
                                        options={yearOptions}
                                        placeholder="Tahun"
                                        className="h-9 text-xs"
                                    />
                                </div>
                            </>
                        }
                    />
                </div>

                {/* MODAL FORM: Tambah / Edit Kegiatan */}
                <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold text-zinc-900 dark:text-zinc-100">
                                <CalendarDays className="w-5 h-5 text-indigo-600" />
                                {editingEvent ? 'Edit Agenda Acara' : 'Tambah Agenda Acara Baru'}
                            </DialogTitle>
                            <DialogDescription className="text-xs text-zinc-500">
                                Masukkan rincian kegiatan ke kalender dan agenda perusahaan.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleSubmit} className="space-y-3.5 pt-1">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Judul Kegiatan *</Label>
                                <Input
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    placeholder="Contoh: Gathering Tahunan / Libur Idul Fitri / Workshop KPI"
                                    className="text-xs h-9"
                                    required
                                />
                                {form.errors.title && (
                                    <p className="text-[11px] text-rose-500">{form.errors.title}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Kategori Kegiatan *</Label>
                                    <SearchableSelect
                                        value={form.data.event_type}
                                        onValueChange={(val) => form.setData('event_type', val)}
                                        options={eventTypes.map((t) => ({ label: t, value: t }))}
                                        placeholder="Pilih kategori"
                                        className="h-9 text-xs"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Target Peserta</Label>
                                    <Input
                                        value={form.data.target_audience}
                                        onChange={(e) => form.setData('target_audience', e.target.value)}
                                        placeholder="Semua Tim / Divisi Produksi"
                                        className="text-xs h-9"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Tanggal Mulai *</Label>
                                    <Input
                                        type="date"
                                        value={form.data.start_date}
                                        onChange={(e) => form.setData('start_date', e.target.value)}
                                        className="text-xs h-9"
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Tanggal Selesai *</Label>
                                    <Input
                                        type="date"
                                        value={form.data.end_date}
                                        onChange={(e) => form.setData('end_date', e.target.value)}
                                        className="text-xs h-9"
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
                                        className="text-xs h-9 font-mono"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Jam Selesai</Label>
                                    <Input
                                        type="time"
                                        value={form.data.end_time}
                                        onChange={(e) => form.setData('end_time', e.target.value)}
                                        className="text-xs h-9 font-mono"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Lokasi / Tempat</Label>
                                <Input
                                    value={form.data.location}
                                    onChange={(e) => form.setData('location', e.target.value)}
                                    placeholder="Contoh: Hall Pabrik / Villa Kaliurang / Ruang Rapat Lt. 2"
                                    className="text-xs h-9"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Penyelenggara</Label>
                                    <Input
                                        value={form.data.organizer_name}
                                        onChange={(e) => form.setData('organizer_name', e.target.value)}
                                        placeholder="Panitia HUT / Manajemen NISGroup"
                                        className="text-xs h-9"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Pengingat (H-N Hari)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={form.data.reminder_days}
                                        onChange={(e) => form.setData('reminder_days', parseInt(e.target.value) || 0)}
                                        className="text-xs h-9"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Warna Label / Badge</Label>
                                    <div className="flex items-center gap-2">
                                        <Input
                                            type="color"
                                            value={form.data.color_code}
                                            onChange={(e) => form.setData('color_code', e.target.value)}
                                            className="w-12 h-9 p-1 cursor-pointer"
                                        />
                                        <span className="text-xs font-mono text-zinc-500">{form.data.color_code}</span>
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Pengulangan Tahunan</Label>
                                    <button
                                        type="button"
                                        onClick={() => form.setData('is_annual_recurring', !form.data.is_annual_recurring)}
                                        className={`w-full h-9 rounded-md border px-3 text-xs text-left font-medium transition ${
                                            form.data.is_annual_recurring
                                                ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                : 'border-zinc-200 bg-white text-zinc-600 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-300'
                                        }`}
                                    >
                                        {form.data.is_annual_recurring ? '✓ Ya (Agenda tahunan)' : '✕ Tidak (Acara sekali jalan)'}
                                    </button>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Deskripsi Kegiatan</Label>
                                <Textarea
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder="Rincian acara, susunan acara, dresscode pakaian, dll..."
                                    rows={2}
                                    className="text-xs"
                                />
                            </div>

                            <DialogFooter className="pt-2 gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setIsModalOpen(false)}
                                    disabled={form.processing}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={form.processing}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs"
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
