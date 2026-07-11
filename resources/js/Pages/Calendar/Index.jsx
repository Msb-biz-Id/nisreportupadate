import { Head, router, usePage } from '@inertiajs/react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { useState, useMemo, useCallback } from 'react';
import { Calendar as BigCalendar, dateFnsLocalizer } from 'react-big-calendar';
import {
    format,
    parse,
    startOfWeek,
    endOfWeek,
    startOfMonth,
    endOfMonth,
    getDay,
    addDays,
    subDays,
    addWeeks,
    subWeeks,
    addMonths,
    subMonths,
    isSameDay,
    isWithinInterval,
    startOfDay,
    endOfDay
} from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { AlertTriangle, X, ExternalLink, ChevronRight, Package2, Users, Clipboard, Check, Calendar as CalendarIcon, ChevronLeft, Copy } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';

const localizer = dateFnsLocalizer({
    format,
    parse,
    startOfWeek: () => startOfWeek(new Date(), { weekStartsOn: 1 }),
    getDay,
    locales: { id: idLocale },
});

const MESSAGES = {
    today: 'Hari Ini',
    previous: '‹',
    next: '›',
    month: 'Bulan',
    day: 'Hari',
    agenda: 'Agenda',
    date: 'Tanggal',
    time: 'Waktu',
    event: 'Order',
    noEventsInRange: 'Tidak ada order dalam rentang ini.',
};

function DayPanel({ date, events, onClose }) {
    if (!date) return null;

    // Cari events yang aktif pada tanggal ini (start <= date <= end, sebelum +1 adj)
    const dayEvents = events.filter((e) => {
        const start = startOfDay(new Date(e.start));
        // end sudah +1 hari dari CalendarController, kurangi 1 untuk cek inklusif
        const end = endOfDay(addDays(new Date(e.end), -1));
        const d = startOfDay(date);
        return d >= start && d <= end;
    });

    const totalPcs = dayEvents.reduce((s, e) => s + (e.totalPcs ?? 0), 0);
    const isToday = isSameDay(date, new Date());

    return (
        <div className="fixed inset-0 z-50 flex justify-end" onClick={onClose}>
            <div
                className="relative h-full w-full max-w-sm bg-background border-l shadow-2xl flex flex-col"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="flex items-center justify-between px-5 py-4 border-b bg-slate-50">
                    <div>
                        <p className="text-xs font-bold text-slate-400 uppercase tracking-widest">
                            {format(date, 'EEEE', { locale: idLocale })}
                        </p>
                        <p className={`text-3xl font-black leading-none ${isToday ? 'text-primary' : 'text-slate-800'}`}>
                            {format(date, 'd')}
                        </p>
                        <p className="text-sm text-slate-500 mt-0.5">
                            {format(date, 'MMMM yyyy', { locale: idLocale })}
                        </p>
                    </div>
                    <button onClick={onClose} className="rounded-full p-1.5 hover:bg-slate-200 transition">
                        <X className="h-5 w-5 text-slate-500" />
                    </button>
                </div>

                {/* Summary */}
                {dayEvents.length > 0 && (
                    <div className="flex gap-3 px-5 py-3 border-b bg-white">
                        <div className="flex items-center gap-1.5 text-sm">
                            <Package2 className="h-4 w-4 text-slate-400" />
                            <span className="font-black text-slate-800">{dayEvents.length}</span>
                            <span className="text-slate-500">PO berjalan</span>
                        </div>
                        {totalPcs > 0 && (
                            <div className="flex items-center gap-1.5 text-sm">
                                <Users className="h-4 w-4 text-slate-400" />
                                <span className="font-black text-slate-800">{totalPcs}</span>
                                <span className="text-slate-500">PCS total</span>
                            </div>
                        )}
                    </div>
                )}

                {/* List PO */}
                <div className="flex-1 overflow-y-auto">
                    {dayEvents.length === 0 ? (
                        <div className="flex flex-col items-center justify-center h-40 text-muted-foreground">
                            <Package2 className="h-8 w-8 mb-2 opacity-30" />
                            <p className="text-sm">Tidak ada PO pada tanggal ini</p>
                        </div>
                    ) : (
                        <ul className="divide-y">
                            {dayEvents.map((e) => {
                                const overdue = e.daysRemaining !== null && e.daysRemaining < 0;
                                const urgent = e.daysRemaining !== null && e.daysRemaining <= 2 && e.daysRemaining >= 0;
                                return (
                                    <li key={e.id} className="px-5 py-3.5 hover:bg-slate-50 transition">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2 mb-1">
                                                    <span
                                                        className="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0"
                                                        style={{ backgroundColor: e.color }}
                                                    />
                                                    <span className="font-mono text-xs text-slate-400">{e.noPo}</span>
                                                </div>
                                                <p className="font-semibold text-sm text-slate-800 truncate">{e.namaPo || '—'}</p>
                                                {e.pelanggan && (
                                                    <p className="text-xs text-slate-500 mt-0.5">{e.pelanggan}</p>
                                                )}
                                                <div className="flex items-center gap-2 mt-1.5 flex-wrap">
                                                    <span
                                                        className="text-[10px] font-bold px-2 py-0.5 rounded-full text-white"
                                                        style={{ backgroundColor: e.color }}
                                                    >
                                                        {e.statusLabel}
                                                    </span>
                                                    {e.totalPcs > 0 && (
                                                        <span className="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                                            {e.totalPcs} pcs
                                                        </span>
                                                    )}
                                                    {overdue && (
                                                        <span className="flex items-center gap-0.5 text-[10px] font-bold text-red-600">
                                                            <AlertTriangle className="h-3 w-3" />
                                                            Terlambat {Math.abs(e.daysRemaining)} hari
                                                        </span>
                                                    )}
                                                    {!overdue && urgent && (
                                                        <span className="text-[10px] font-bold text-orange-500">
                                                            {e.daysRemaining === 0 ? 'Deadline hari ini' : `${e.daysRemaining} hari lagi`}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex flex-col gap-1 flex-shrink-0">
                                                <a
                                                    href={e.detailUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-[10px] font-bold text-primary hover:underline flex items-center gap-0.5"
                                                >
                                                    Detail <ExternalLink className="h-3 w-3" />
                                                </a>
                                                <a
                                                    href={e.progressUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-[10px] font-bold text-slate-500 hover:underline flex items-center gap-0.5"
                                                >
                                                    Progress <ChevronRight className="h-3 w-3" />
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>
            </div>
        </div>
    );
}

function CustomAgenda({ events, statusColors, statusLabels, filterStatus, currentDate, setCurrentDate, setView }) {
    const [filterType, setFilterType] = useState('bulan'); // 'hari' | 'minggu' | 'bulan' | 'range'
    const [agendaDate, setAgendaDate] = useState(currentDate || new Date());
    const [copied, setCopied] = useState(false);

    // Custom date range state
    const [customStart, setCustomStart] = useState(format(agendaDate, 'yyyy-MM-dd'));
    const [customEnd, setCustomEnd] = useState(format(addDays(agendaDate, 30), 'yyyy-MM-dd'));

    const { filterStart, filterEnd } = useMemo(() => {
        let start, end;
        if (filterType === 'hari') {
            start = startOfDay(agendaDate);
            end = endOfDay(agendaDate);
        } else if (filterType === 'minggu') {
            start = startOfWeek(agendaDate, { weekStartsOn: 1 });
            end = endOfWeek(agendaDate, { weekStartsOn: 1 });
        } else if (filterType === 'bulan') {
            start = startOfMonth(agendaDate);
            end = endOfMonth(agendaDate);
        } else {
            start = startOfDay(new Date(customStart));
            end = endOfDay(new Date(customEnd));
        }
        return { filterStart: start, filterEnd: end };
    }, [filterType, agendaDate, customStart, customEnd]);

    const isEventInInterval = (event, startInterval, endInterval) => {
        const eventStart = startOfDay(new Date(event.start));
        const eventEnd = endOfDay(new Date(event.end));
        return eventStart <= endInterval && eventEnd >= startInterval;
    };

    const filteredEvents = useMemo(() => {
        return events
            .filter((e) => filterStatus === 'all' || e.status === filterStatus)
            .filter((e) => isEventInInterval(e, filterStart, filterEnd));
    }, [events, filterStatus, filterStart, filterEnd]);

    const handlePrev = () => {
        if (filterType === 'hari') {
            setAgendaDate(prev => subDays(prev, 1));
        } else if (filterType === 'minggu') {
            setAgendaDate(prev => subDays(prev, 7));
        } else if (filterType === 'bulan') {
            setAgendaDate(prev => subMonths(prev, 1));
        }
    };

    const handleNext = () => {
        if (filterType === 'hari') {
            setAgendaDate(prev => addDays(prev, 1));
        } else if (filterType === 'minggu') {
            setAgendaDate(prev => addDays(prev, 7));
        } else if (filterType === 'bulan') {
            setAgendaDate(prev => addMonths(prev, 1));
        }
    };

    const handleToday = () => {
        const today = new Date();
        setAgendaDate(today);
        if (filterType === 'range') {
            setCustomStart(format(today, 'yyyy-MM-dd'));
            setCustomEnd(format(addDays(today, 30), 'yyyy-MM-dd'));
        }
    };

    const copyToClipboard = () => {
        const headers = ['Tanggal', 'No PO', 'Nama PO', 'Pelanggan', 'Status', 'Jumlah Pcs'];
        const rows = filteredEvents.map(e => {
            const dateStr = `${format(new Date(e.start), 'dd MMM yyyy')} s.d. ${format(new Date(e.end), 'dd MMM yyyy')}`;
            return [
                dateStr,
                e.noPo || '',
                e.namaPo || '',
                e.pelanggan || '',
                e.statusLabel || '',
                e.totalPcs || '0'
            ].join('\t');
        });
        
        const tsvContent = [headers.join('\t'), ...rows].join('\n');
        
        navigator.clipboard.writeText(tsvContent).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    const rangeLabel = useMemo(() => {
        if (filterType === 'hari') {
            return format(agendaDate, 'EEEE, dd MMMM yyyy', { locale: idLocale });
        } else if (filterType === 'minggu') {
            const startStr = format(filterStart, 'dd MMM yyyy', { locale: idLocale });
            const endStr = format(filterEnd, 'dd MMM yyyy', { locale: idLocale });
            return `${startStr} - ${endStr}`;
        } else if (filterType === 'bulan') {
            return format(agendaDate, 'MMMM yyyy', { locale: idLocale });
        } else {
            const startStr = format(new Date(customStart), 'dd MMM yyyy', { locale: idLocale });
            const endStr = format(new Date(customEnd), 'dd MMM yyyy', { locale: idLocale });
            return `${startStr} - ${endStr}`;
        }
    }, [filterType, agendaDate, filterStart, filterEnd, customStart, customEnd]);

    const totalPcsSum = useMemo(() => {
        return filteredEvents.reduce((sum, e) => sum + (e.totalPcs || 0), 0);
    }, [filteredEvents]);

    return (
        <div className="space-y-4 animate-in fade-in duration-300">
            {/* Custom Agenda Toolbar */}
            <div className="flex flex-col gap-4 border-b pb-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    {/* Left: Navigasi (only visible for non-range) */}
                    <div className="flex items-center gap-1.5">
                        <Button variant="outline" size="sm" onClick={handleToday} className="text-xs font-semibold">
                            Hari Ini
                        </Button>
                        {filterType !== 'range' && (
                            <>
                                <Button variant="outline" size="icon" className="h-8 w-8" onClick={handlePrev}>
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>
                                <Button variant="outline" size="icon" className="h-8 w-8" onClick={handleNext}>
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </>
                        )}
                        <span className="text-sm font-bold text-slate-700 ml-2">
                            {rangeLabel}
                        </span>
                    </div>

                    {/* Right: View Switcher */}
                    <div className="flex items-center border rounded-lg p-0.5 bg-slate-50">
                        <button
                            type="button"
                            onClick={() => setView('month')}
                            className="px-3 py-1 text-xs font-semibold text-slate-500 rounded-md hover:text-slate-800 transition"
                        >
                            Bulan
                        </button>
                        <button
                            type="button"
                            className="px-3 py-1 text-xs font-bold text-white bg-primary rounded-md shadow-sm transition"
                        >
                            Agenda
                        </button>
                    </div>
                </div>

                {/* Sub-toolbar: Filters and Actions */}
                <div className="flex flex-wrap items-center justify-between gap-3 bg-slate-50/50 p-2.5 rounded-xl border border-slate-100">
                    <div className="flex flex-wrap items-center gap-3">
                        {/* Filter Type Selector */}
                        <div className="flex items-center border rounded-lg p-0.5 bg-white shadow-sm">
                            {[
                                { key: 'hari', label: 'Harian' },
                                { key: 'minggu', label: 'Mingguan' },
                                { key: 'bulan', label: 'Bulanan' },
                                { key: 'range', label: 'Range Tanggal' }
                            ].map((tab) => (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => setFilterType(tab.key)}
                                    className={`px-3 py-1 text-xs font-semibold rounded-md transition ${
                                        filterType === tab.key
                                            ? 'bg-slate-100 text-slate-800 font-bold'
                                            : 'text-slate-500 hover:text-slate-800'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>

                        {/* Date Pickers for Custom Range */}
                        {filterType === 'range' && (
                            <div className="flex items-center gap-2 animate-in fade-in duration-200">
                                <div className="flex items-center gap-1.5">
                                    <span className="text-[10px] font-bold text-slate-400 uppercase">Mulai:</span>
                                    <input
                                        type="date"
                                        value={customStart}
                                        onChange={(e) => setCustomStart(e.target.value)}
                                        className="text-xs border rounded-md px-2 py-1 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                    />
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <span className="text-[10px] font-bold text-slate-400 uppercase">Selesai:</span>
                                    <input
                                        type="date"
                                        value={customEnd}
                                        onChange={(e) => setCustomEnd(e.target.value)}
                                        className="text-xs border rounded-md px-2 py-1 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Copy to Excel Action */}
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={filteredEvents.length === 0}
                            onClick={copyToClipboard}
                            className="flex items-center gap-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 hover:text-emerald-800"
                        >
                            {copied ? (
                                <>
                                    <Check className="h-3.5 w-3.5 text-emerald-600" />
                                    Berhasil Disalin
                                </>
                            ) : (
                                <>
                                    <Copy className="h-3.5 w-3.5" />
                                    Salin Data Agenda (Excel)
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </div>

            {/* Summary Bar */}
            <div className="flex items-center justify-between px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[10px] font-bold text-slate-500 tracking-wider">
                <span>MENAMPILKAN {filteredEvents.length} ORDER PO</span>
                <span>TOTAL PRODUKSI: {totalPcsSum} PCS</span>
            </div>

            {/* Agenda List */}
            <div className="space-y-2.5 max-h-[550px] overflow-y-auto pr-1">
                {filteredEvents.length === 0 ? (
                    <div className="flex flex-col items-center justify-center h-48 border border-dashed rounded-xl bg-slate-50/50 text-slate-400">
                        <CalendarIcon className="h-10 w-10 mb-2 opacity-30" />
                        <p className="text-sm font-medium">Tidak ada PO dalam rentang waktu terpilih</p>
                    </div>
                ) : (
                    filteredEvents.map((e) => {
                        return (
                            <div
                                key={e.id}
                                className="group relative flex items-center justify-between border border-slate-100 bg-white p-4 shadow-sm transition-all hover:bg-slate-50/30 hover:shadow rounded-xl overflow-hidden"
                            >
                                {/* Left Side: Details */}
                                <div className="flex-1 min-w-0 flex flex-col md:flex-row md:items-center gap-4">
                                    {/* Date Column */}
                                    <div className="flex-shrink-0 flex flex-col min-w-[140px]">
                                        <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            Deadline PO
                                        </span>
                                        <span className="text-sm font-bold text-slate-700 mt-0.5">
                                            {format(new Date(e.end), 'dd MMMM yyyy', { locale: idLocale })}
                                        </span>
                                    </div>

                                    {/* Info Column */}
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="font-mono text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                                {e.noPo}
                                            </span>
                                            {e.pelanggan && (
                                                <span className="text-xs font-semibold text-slate-500 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">
                                                    {e.pelanggan}
                                                </span>
                                            )}
                                        </div>
                                        <h3 className="font-bold text-sm md:text-base text-slate-800 mt-1.5 truncate group-hover:text-primary transition-colors">
                                            {e.namaPo || '—'}
                                        </h3>
                                    </div>
                                </div>

                                {/* Right Side: Actions and Color Indicator */}
                                <div className="flex items-center gap-6 flex-shrink-0 mr-4">
                                    {/* Total PCS Column */}
                                    <div className="text-right min-w-[80px]">
                                        <span className="text-[10px] text-slate-400 uppercase tracking-widest block">Jumlah</span>
                                        <span className="font-black text-slate-800 text-sm md:text-base">
                                            {e.totalPcs || 0} <span className="text-xs font-semibold text-slate-400">pcs</span>
                                        </span>
                                    </div>

                                    {/* Status Info */}
                                    <div className="hidden lg:flex flex-col items-end min-w-[120px]">
                                        <span className="text-[10px] text-slate-400 uppercase tracking-widest block">Status</span>
                                        <span className="text-xs font-bold text-slate-700 mt-0.5 flex items-center gap-1.5">
                                            <span className="w-2 h-2 rounded-full" style={{ backgroundColor: e.color }} />
                                            {e.statusLabel}
                                        </span>
                                    </div>

                                    {/* Action Links */}
                                    <div className="flex flex-col md:flex-row items-end md:items-center gap-2">
                                        <a
                                            href={e.detailUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center rounded-md text-xs font-bold text-primary hover:bg-primary/5 hover:text-primary px-3 py-1.5 h-8 border border-transparent hover:border-primary/10 transition-colors"
                                        >
                                            Detail <ExternalLink className="h-3 w-3 ml-0.5" />
                                        </a>
                                        <a
                                            href={e.progressUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center rounded-md text-xs font-bold text-slate-500 hover:bg-slate-100 hover:text-slate-700 px-3 py-1.5 h-8 border border-transparent hover:border-slate-200 transition-colors"
                                        >
                                            Progress <ChevronRight className="h-3 w-3 ml-0.5" />
                                        </a>
                                    </div>
                                </div>

                                {/* Color indicator on the poll kanan (vertical accent bar) */}
                                <div
                                    style={{ backgroundColor: e.color }}
                                    className="w-1.5 absolute right-0 top-0 bottom-0 rounded-r-xl"
                                    title={e.statusLabel}
                                />
                            </div>
                        );
                    })
                )}
            </div>
        </div>
    );
}

export default function CalendarIndex({ events, statusColors, statusLabels, filters }) {
    const { brandContext } = usePage().props;
    const [selectedDate, setSelectedDate]   = useState(null);
    const [filterStatus, setFilterStatus]   = useState('all');
    const [currentDate, setCurrentDate]     = useState(new Date());
    const [view, setView]                   = useState('month');

    const rbcEvents = useMemo(() =>
        events
            .filter((e) => filterStatus === 'all' || e.status === filterStatus)
            .map((e) => ({
                ...e,
                start: e.start ? new Date(e.start) : new Date(),
                end: e.end ? addDays(new Date(e.end), 1) : new Date(),
            })),
        [events, filterStatus],
    );

    const eventStyleGetter = useCallback((event) => ({
        style: {
            backgroundColor: event.color,
            border: 'none',
            borderRadius: '4px',
            color: '#fff',
            fontSize: '11px',
            padding: '1px 4px',
            opacity: event.daysRemaining !== null && event.daysRemaining < 0 ? 0.85 : 1,
            cursor: 'pointer',
        },
    }), []);

    const statusSummary = useMemo(() => {
        const counts = {};
        for (const e of events) counts[e.status] = (counts[e.status] ?? 0) + 1;
        return counts;
    }, [events]);

    const activeStatuses = Object.keys(statusLabels).filter((s) => statusSummary[s]);

    // Klik tanggal di view Bulan → buka DayPanel
    const handleSelectSlot = useCallback(({ start }) => {
        setSelectedDate(start);
    }, []);

    // Klik event → buka DayPanel untuk tanggal start event
    const handleSelectEvent = useCallback((event) => {
        setSelectedDate(event.start);
    }, []);

    // Hitung dot per tanggal untuk custom date cell
    const dateCellWrapper = useCallback(({ value, children }) => {
        const dayEvts = events.filter((e) => {
            const start = startOfDay(new Date(e.start));
            const end = endOfDay(new Date(e.end)); // events sudah raw (sebelum +1 adj)
            return value >= start && value <= end;
        });
        const isSelected = selectedDate && isSameDay(value, selectedDate);

        return (
            <div
                className={`rbc-day-bg ${isSelected ? 'rbc-selected-day' : ''}`}
                style={{ position: 'relative', cursor: 'pointer' }}
            >
                {children}
                {dayEvts.length > 0 && view === 'month' && (
                    <div style={{
                        position: 'absolute', bottom: 2, left: 0, right: 0,
                        display: 'flex', justifyContent: 'center', gap: 2, pointerEvents: 'none',
                    }}>
                        {dayEvts.slice(0, 5).map((e, i) => (
                            <span key={i} style={{
                                width: 5, height: 5, borderRadius: '50%',
                                backgroundColor: e.color, display: 'inline-block',
                            }} />
                        ))}
                        {dayEvts.length > 5 && (
                            <span style={{ fontSize: 8, color: '#64748b', lineHeight: '5px' }}>+{dayEvts.length - 5}</span>
                        )}
                    </div>
                )}
            </div>
        );
    }, [events, selectedDate, view]);

    return (
        <AppLayout>
            <Head title="Kalender PO" />

            {selectedDate && (
                <DayPanel
                    date={selectedDate}
                    events={rbcEvents}
                    onClose={() => setSelectedDate(null)}
                />
            )}

            <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Kalender PO</h1>
                        <p className="text-sm text-muted-foreground">{events.length} order aktif — klik tanggal untuk detail</p>
                    </div>
                    {brandContext?.available && brandContext.available.length > 1 && (
                        <div className="w-56">
                            <Select
                                value={filters?.brand_id || 'all'}
                                onValueChange={(v) => {
                                    router.get(
                                        route('kalender.index'),
                                        { brand_id: v },
                                        { preserveState: true, replace: true }
                                    );
                                }}
                            >
                                <SelectTrigger className="bg-white">
                                    <SelectValue placeholder="Pilih Brand" />
                                </SelectTrigger>
                                <SelectContent>
                                    {brandContext.available.map((b) => (
                                        <SelectItem key={b.id} value={b.id}>
                                            {b.nama_brand}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>

                {/* Status filter badges */}
                <div className="flex flex-wrap gap-2">
                    <button
                        onClick={() => setFilterStatus('all')}
                        className={`rounded-full px-3 py-1 text-xs font-medium border transition-colors ${
                            filterStatus === 'all'
                                ? 'bg-foreground text-background border-foreground'
                                : 'border-border text-muted-foreground hover:border-foreground'
                        }`}
                    >
                        Semua ({events.length})
                    </button>
                    {activeStatuses.map((s) => (
                        <button
                            key={s}
                            onClick={() => setFilterStatus(filterStatus === s ? 'all' : s)}
                            className="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium text-white transition-opacity hover:opacity-80"
                            style={{
                                backgroundColor: statusColors[s],
                                opacity: filterStatus !== 'all' && filterStatus !== s ? 0.4 : 1,
                            }}
                        >
                            {statusLabels[s]}
                            <span className="rounded-full bg-white/30 px-1.5">{statusSummary[s]}</span>
                        </button>
                    ))}
                </div>

                <Card>
                    <CardContent className="p-2 sm:p-4">
                        {view === 'agenda' ? (
                            <CustomAgenda
                                events={events}
                                statusColors={statusColors}
                                statusLabels={statusLabels}
                                filterStatus={filterStatus}
                                currentDate={currentDate}
                                setCurrentDate={setCurrentDate}
                                setView={setView}
                            />
                        ) : (
                            <div className="rbc-wrapper">
                                <BigCalendar
                                    localizer={localizer}
                                    events={rbcEvents}
                                    startAccessor="start"
                                    endAccessor="end"
                                    style={{ height: 640 }}
                                    view={view}
                                    onView={setView}
                                    date={currentDate}
                                    onNavigate={setCurrentDate}
                                    messages={MESSAGES}
                                    culture="id"
                                    eventPropGetter={eventStyleGetter}
                                    onSelectEvent={handleSelectEvent}
                                    onSelectSlot={handleSelectSlot}
                                    selectable
                                    popup={false}
                                    showMultiDayTimes={false}
                                    views={['month', 'agenda']}
                                    tooltipAccessor={null}
                                    components={{ dateCellWrapper }}
                                />
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <style>{`
                .rbc-wrapper .rbc-toolbar {
                    flex-wrap: wrap; gap: 8px; margin-bottom: 12px;
                }
                .rbc-wrapper .rbc-toolbar button {
                    border-radius: 6px;
                    border: 1px solid hsl(var(--border));
                    background: hsl(var(--background));
                    color: hsl(var(--foreground));
                    font-size: 13px; padding: 4px 12px;
                    cursor: pointer; transition: background 0.15s;
                }
                .rbc-wrapper .rbc-toolbar button:hover { background: hsl(var(--muted)); }
                .rbc-wrapper .rbc-toolbar button.rbc-active {
                    background: hsl(var(--primary));
                    color: hsl(var(--primary-foreground));
                    border-color: hsl(var(--primary));
                }
                .rbc-wrapper .rbc-toolbar-label {
                    font-size: 15px; font-weight: 600; color: hsl(var(--foreground));
                }
                .rbc-wrapper .rbc-header {
                    font-size: 12px; font-weight: 600; padding: 6px 4px;
                    color: hsl(var(--muted-foreground)); border-color: hsl(var(--border));
                }
                .rbc-wrapper .rbc-month-view,
                .rbc-wrapper .rbc-agenda-view {
                    border-color: hsl(var(--border)); border-radius: 8px; overflow: hidden;
                }
                .rbc-wrapper .rbc-day-bg + .rbc-day-bg,
                .rbc-wrapper .rbc-month-row + .rbc-month-row { border-color: hsl(var(--border)); }
                .rbc-wrapper .rbc-off-range-bg { background: hsl(var(--muted) / 0.4); }
                .rbc-wrapper .rbc-today { background: hsl(var(--primary) / 0.07); }
                .rbc-wrapper .rbc-selected-day { background: hsl(var(--primary) / 0.12) !important; }
                .rbc-wrapper .rbc-event:focus { outline: none; }
                .rbc-wrapper .rbc-show-more {
                    font-size: 11px; color: hsl(var(--primary)); font-weight: 500;
                }
                .rbc-wrapper .rbc-agenda-table th,
                .rbc-wrapper .rbc-agenda-table td {
                    border-color: hsl(var(--border)); font-size: 13px;
                }
                .rbc-wrapper .rbc-agenda-date-cell,
                .rbc-wrapper .rbc-agenda-time-cell { color: hsl(var(--muted-foreground)); }
                .rbc-wrapper .rbc-date-cell { padding-bottom: 14px; }
                .rbc-wrapper .rbc-event,
                .rbc-wrapper .rbc-show-more {
                    display: none !important;
                }
            `}</style>
        </AppLayout>
    );
}
