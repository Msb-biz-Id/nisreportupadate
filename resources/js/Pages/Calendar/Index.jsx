import { Head, router } from '@inertiajs/react';
import { useState, useMemo, useCallback } from 'react';
import { Calendar as BigCalendar, dateFnsLocalizer } from 'react-big-calendar';
import { format, parse, startOfWeek, getDay, addDays } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { AlertTriangle, X } from 'lucide-react';
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
    week: 'Minggu',
    day: 'Hari',
    agenda: 'Agenda',
    date: 'Tanggal',
    time: 'Waktu',
    event: 'Order',
    noEventsInRange: 'Tidak ada order dalam rentang ini.',
};

function EventPopover({ event, onClose }) {
    if (!event) return null;
    const overdue = event.daysRemaining !== null && event.daysRemaining < 0;
    const urgent = event.daysRemaining !== null && event.daysRemaining <= 2 && event.daysRemaining >= 0;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" onClick={onClose}>
            <div
                className="relative w-full max-w-sm rounded-xl border bg-background shadow-xl p-5 space-y-3"
                onClick={(e) => e.stopPropagation()}
            >
                <button
                    onClick={onClose}
                    className="absolute right-3 top-3 rounded-sm opacity-60 hover:opacity-100"
                >
                    <X className="h-4 w-4" />
                </button>

                <div className="space-y-1">
                    <p className="font-mono text-xs text-muted-foreground">{event.noPo}</p>
                    <p className="font-semibold leading-tight">{event.namaPo || '—'}</p>
                    {event.pelanggan && <p className="text-sm text-muted-foreground">{event.pelanggan}</p>}
                </div>

                <div className="flex items-center gap-2">
                    <span
                        className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                        style={{ backgroundColor: event.color }}
                    >
                        {event.statusLabel}
                    </span>
                    {overdue && (
                        <span className="flex items-center gap-1 text-xs font-semibold text-red-600">
                            <AlertTriangle className="h-3 w-3" /> Overdue {Math.abs(event.daysRemaining)} hari
                        </span>
                    )}
                    {!overdue && urgent && (
                        <span className="text-xs font-semibold text-orange-500">
                            {event.daysRemaining === 0 ? 'Deadline hari ini' : `${event.daysRemaining} hari lagi`}
                        </span>
                    )}
                </div>

                <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                    <span className="text-muted-foreground">Masuk</span>
                    <span>{event.start ? format(new Date(event.start), 'dd MMM yyyy', { locale: idLocale }) : '—'}</span>
                    <span className="text-muted-foreground">Deadline</span>
                    <span className={overdue ? 'font-semibold text-red-600' : ''}>
                        {event.end ? format(new Date(event.end), 'dd MMM yyyy', { locale: idLocale }) : '—'}
                    </span>
                </div>

                <div className="flex gap-2 pt-1">
                    <Button size="sm" className="flex-1" onClick={() => router.visit(event.detailUrl)}>
                        Lihat Detail
                    </Button>
                    <Button size="sm" variant="outline" className="flex-1" onClick={() => router.visit(event.progressUrl)}>
                        Progress
                    </Button>
                </div>
            </div>
        </div>
    );
}

export default function CalendarIndex({ events, statusColors, statusLabels }) {
    const [selectedEvent, setSelectedEvent] = useState(null);
    const [filterStatus, setFilterStatus] = useState('all');
    const [currentDate, setCurrentDate] = useState(new Date());
    const [view, setView] = useState('month');

    // Konversi events ke format react-big-calendar
    // end date di rbc harus +1 hari supaya hari terakhir inklusif
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
        },
    }), []);

    const statusSummary = useMemo(() => {
        const counts = {};
        for (const e of events) counts[e.status] = (counts[e.status] ?? 0) + 1;
        return counts;
    }, [events]);

    const activeStatuses = Object.keys(statusLabels).filter((s) => statusSummary[s]);

    return (
        <AppLayout>
            <Head title="Kalender PO" />

            <EventPopover event={selectedEvent} onClose={() => setSelectedEvent(null)} />

            <div className="space-y-4">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Kalender PO</h1>
                        <p className="text-sm text-muted-foreground">{events.length} order aktif</p>
                    </div>
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

                {/* Calendar */}
                <Card>
                    <CardContent className="p-2 sm:p-4">
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
                                onSelectEvent={(event) => setSelectedEvent(event)}
                                popup
                                showMultiDayTimes={false}
                                views={['month', 'week', 'agenda']}
                                tooltipAccessor={null}
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <style>{`
                .rbc-wrapper .rbc-toolbar {
                    flex-wrap: wrap;
                    gap: 8px;
                    margin-bottom: 12px;
                }
                .rbc-wrapper .rbc-toolbar button {
                    border-radius: 6px;
                    border: 1px solid hsl(var(--border));
                    background: hsl(var(--background));
                    color: hsl(var(--foreground));
                    font-size: 13px;
                    padding: 4px 12px;
                    cursor: pointer;
                    transition: background 0.15s;
                }
                .rbc-wrapper .rbc-toolbar button:hover {
                    background: hsl(var(--muted));
                }
                .rbc-wrapper .rbc-toolbar button.rbc-active {
                    background: hsl(var(--primary));
                    color: hsl(var(--primary-foreground));
                    border-color: hsl(var(--primary));
                }
                .rbc-wrapper .rbc-toolbar-label {
                    font-size: 15px;
                    font-weight: 600;
                    color: hsl(var(--foreground));
                }
                .rbc-wrapper .rbc-header {
                    font-size: 12px;
                    font-weight: 600;
                    padding: 6px 4px;
                    color: hsl(var(--muted-foreground));
                    border-color: hsl(var(--border));
                }
                .rbc-wrapper .rbc-month-view,
                .rbc-wrapper .rbc-time-view,
                .rbc-wrapper .rbc-agenda-view {
                    border-color: hsl(var(--border));
                    border-radius: 8px;
                    overflow: hidden;
                }
                .rbc-wrapper .rbc-day-bg + .rbc-day-bg,
                .rbc-wrapper .rbc-month-row + .rbc-month-row {
                    border-color: hsl(var(--border));
                }
                .rbc-wrapper .rbc-off-range-bg {
                    background: hsl(var(--muted) / 0.4);
                }
                .rbc-wrapper .rbc-today {
                    background: hsl(var(--primary) / 0.07);
                }
                .rbc-wrapper .rbc-event:focus {
                    outline: none;
                }
                .rbc-wrapper .rbc-show-more {
                    font-size: 11px;
                    color: hsl(var(--primary));
                    font-weight: 500;
                }
                .rbc-wrapper .rbc-agenda-table th,
                .rbc-wrapper .rbc-agenda-table td {
                    border-color: hsl(var(--border));
                    font-size: 13px;
                }
                .rbc-wrapper .rbc-agenda-date-cell,
                .rbc-wrapper .rbc-agenda-time-cell {
                    color: hsl(var(--muted-foreground));
                }
            `}</style>
        </AppLayout>
    );
}
