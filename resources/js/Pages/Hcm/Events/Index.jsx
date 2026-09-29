import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    CalendarDays,
    Calendar,
    Plus,
    Search,
    MapPin,
    Clock,
    Sparkles,
    PartyPopper,
    Edit2,
    Trash2,
    CheckCircle2,
    Users,
    Building2,
    Flag,
    Filter,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
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
    metrics,
    eventTypes,
    today,
}) {
    const [search, setSearch] = useState(filters.search || '');
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

    // Form Event
    const form = useForm({
        title: '',
        event_type: 'Acara Perusahaan',
        start_date: today,
        end_date: today,
        start_time: '09:00',
        end_time: '17:00',
        location: '',
        description: '',
        color_code: '#3b82f6',
        is_public: true,
    });

    const handleFilterChange = (newSearch, newType, newMonth, newYear) => {
        router.get(
            route('hcm.events.index'),
            {
                search: newSearch,
                type: newType,
                month: newMonth,
                year: newYear,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const openAddModal = () => {
        setEditingEvent(null);
        form.reset();
        form.setData({
            title: '',
            event_type: 'Acara Perusahaan',
            start_date: today,
            end_date: today,
            start_time: '09:00',
            end_time: '17:00',
            location: '',
            description: '',
            color_code: '#3b82f6',
            is_public: true,
        });
        setIsModalOpen(true);
    };

    const openEditModal = (ev) => {
        setEditingEvent(ev);
        form.setData({
            title: ev.title,
            event_type: ev.event_type,
            start_date: ev.start_date,
            end_date: ev.end_date,
            start_time: ev.start_time || '',
            end_time: ev.end_time || '',
            location: ev.location || '',
            description: ev.description || '',
            color_code: ev.color_code || '#3b82f6',
            is_public: ev.is_public ?? true,
        });
        setIsModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingEvent) {
            form.put(route('hcm.events.update', editingEvent.id), {
                onSuccess: () => {
                    setIsModalOpen(false);
                    form.reset();
                },
            });
        } else {
            form.post(route('hcm.events.store'), {
                onSuccess: () => {
                    setIsModalOpen(false);
                    form.reset();
                },
            });
        }
    };

    const handleDelete = (ev) => {
        if (confirm(`Hapus kegiatan "${ev.title}" dari kalender perusahaan?`)) {
            router.delete(route('hcm.events.destroy', ev.id));
        }
    };

    const isUpcoming = (endDate) => endDate >= today;

    return (
        <AppLayout title="Kalender & Agenda Kegiatan Perusahaan">
            <Head title="Kalender & Agenda Kegiatan Perusahaan" />

            <div className="space-y-6">
                {/* 1. Header & Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="p-2 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400">
                                <CalendarDays className="w-6 h-6" />
                            </div>
                            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Kalender & Agenda Perusahaan
                            </h1>
                        </div>
                        <p className="text-xs sm:text-sm text-zinc-500 mt-1">
                            Pencatatan kegiatan korporat, gathering, rapat internal, pelatihan, dan hari libur nasional.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            onClick={openAddModal}
                            className="bg-blue-600 hover:bg-blue-700 text-white gap-1.5 text-xs font-semibold shadow-sm"
                        >
                            <Plus className="w-4 h-4" />
                            Tambah Kegiatan Baru
                        </Button>
                    </div>
                </div>

                {/* 2. KPI Metrics Grid */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <CalendarDays className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Total Agenda</div>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono">
                                    {metrics.total_events} Acara
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                                <Sparkles className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Agenda Mendatang</div>
                                <div className="text-xl font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    {metrics.upcoming_events} Acara
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600">
                                <CheckCircle2 className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Telah Selesai</div>
                                <div className="text-xl font-bold text-zinc-700 dark:text-zinc-300 font-mono">
                                    {metrics.completed_events} Acara
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600">
                                <Flag className="w-5 h-5" />
                            </div>
                            <div>
                                <div className="text-xs text-zinc-500">Libur Nasional ({currentYear})</div>
                                <div className="text-xl font-bold text-rose-600 dark:text-rose-400 font-mono">
                                    {metrics.national_holidays} Hari
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
                                placeholder="Cari kegiatan / tempat..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        handleFilterChange(search, typeFilter, monthFilter, yearFilter);
                                    }
                                }}
                                className="pl-9 text-xs h-9"
                            />
                        </div>

                        <div className="w-full sm:w-40">
                            <SearchableSelect
                                value={monthFilter}
                                onValueChange={(val) => {
                                    setMonthFilter(val);
                                    handleFilterChange(search, typeFilter, val, yearFilter);
                                }}
                                options={months}
                                placeholder="Pilih Bulan"
                                className="h-9 text-xs"
                            />
                        </div>

                        <div className="w-full sm:w-36">
                            <SearchableSelect
                                value={yearFilter}
                                onValueChange={(val) => {
                                    setYearFilter(val);
                                    handleFilterChange(search, typeFilter, monthFilter, val);
                                }}
                                options={yearOptions}
                                placeholder="Pilih Tahun"
                                className="h-9 text-xs"
                            />
                        </div>

                        <div className="w-full sm:w-52">
                            <SearchableSelect
                                value={typeFilter}
                                onValueChange={(val) => {
                                    setTypeFilter(val);
                                    handleFilterChange(search, val, monthFilter, yearFilter);
                                }}
                                options={typeOptions}
                                placeholder="Kategori Acara"
                                className="h-9 text-xs"
                            />
                        </div>
                    </div>

                    <div className="text-xs text-zinc-500 self-end sm:self-center">
                        Total {events.length} agenda ditemukan
                    </div>
                </div>

                {/* 4. Timeline / Card Grid Agenda Acara */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {events && events.length > 0 ? (
                        events.map((ev) => {
                            const active = isUpcoming(ev.end_date);
                            return (
                                <Card
                                    key={ev.id}
                                    className={`border bg-white dark:bg-zinc-900 shadow-sm transition hover:shadow-md overflow-hidden flex flex-col justify-between ${
                                        active
                                            ? 'border-zinc-200 dark:border-zinc-800'
                                            : 'border-zinc-200/60 dark:border-zinc-800/60 opacity-80'
                                    }`}
                                >
                                    <div className="p-4 space-y-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <Badge
                                                variant="outline"
                                                className="text-[11px] font-semibold"
                                                style={{ borderColor: ev.color_code, color: ev.color_code }}
                                            >
                                                {ev.event_type}
                                            </Badge>
                                            <div className="flex items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-7 w-7 text-zinc-400 hover:text-blue-600"
                                                    onClick={() => openEditModal(ev)}
                                                >
                                                    <Edit2 className="w-3.5 h-3.5" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-7 w-7 text-zinc-400 hover:text-rose-600"
                                                    onClick={() => handleDelete(ev)}
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </Button>
                                            </div>
                                        </div>

                                        <div>
                                            <h3
                                                className="font-bold text-sm sm:text-base leading-snug"
                                                style={{ color: ev.color_code || '#18181b' }}
                                            >
                                                {ev.title}
                                            </h3>
                                            {ev.description && (
                                                <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">
                                                    {ev.description}
                                                </p>
                                            )}
                                        </div>

                                        <div className="space-y-1.5 pt-2 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-600 dark:text-zinc-400">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="w-3.5 h-3.5 text-zinc-400" />
                                                <span className="font-mono">
                                                    {ev.start_date} {ev.start_date !== ev.end_date ? `s.d ${ev.end_date}` : ''}
                                                </span>
                                            </div>

                                            {ev.start_time && (
                                                <div className="flex items-center gap-2">
                                                    <Clock className="w-3.5 h-3.5 text-zinc-400" />
                                                    <span className="font-mono">
                                                        {ev.start_time} {ev.end_time ? `- ${ev.end_time}` : 'WIB'}
                                                    </span>
                                                </div>
                                            )}

                                            {ev.location && (
                                                <div className="flex items-center gap-2">
                                                    <MapPin className="w-3.5 h-3.5 text-zinc-400" />
                                                    <span className="truncate">{ev.location}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="px-4 py-2.5 bg-zinc-50 dark:bg-zinc-850/50 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] text-zinc-400">
                                        <span>Dibuat oleh: {ev.creator?.name || 'HCM'}</span>
                                        <span>
                                            {active ? (
                                                <Badge className="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-[10px]">
                                                    Mendatang
                                                </Badge>
                                            ) : (
                                                <span className="text-zinc-400">Selesai</span>
                                            )}
                                        </span>
                                    </div>
                                </Card>
                            );
                        })
                    ) : (
                        <div className="col-span-full py-12 text-center text-zinc-400 text-xs bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                            Tidak ada agenda kegiatan yang cocok dengan kriteria filter.
                        </div>
                    )}
                </div>

                {/* 5. Modal Tambah / Edit Acara */}
                <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-base font-bold">
                                <CalendarDays className="w-5 h-5 text-blue-600" />
                                {editingEvent ? 'Edit Agenda Acara Perusahaan' : 'Tambah Agenda Acara Baru'}
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Masukkan rincian kegiatan ke kalender kegiatan perusahaan.
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
