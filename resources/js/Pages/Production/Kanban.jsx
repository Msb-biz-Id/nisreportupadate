import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { AlertTriangle, Calendar, Lock, Eye, Star, XCircle, Layers, CheckCircle2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/lib/utils';
import SetTimelineDialog from '@/Components/Order/SetTimelineDialog';

function KanbanCard({ order, onSetDeadline }) {
    const isFinished   = ['selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'].includes(order.status_po);
    const days         = order.days_remaining;
    const overdue      = !isFinished && (order.status_po === 'delay' || (days !== null && days < 0));
    const urgent       = !isFinished && !overdue && days !== null && days <= 2 && days >= 0;
    const nearDeadline = !isFinished && !overdue && !urgent && days !== null && days <= 7 && days > 2;
    const needsDeadline = order.needs_deadline || order.stage_status === 'baru_masuk';
    const isReview = order.stage_status === 'review';

    // Visual border based on priority
    const borderClass = overdue
        ? 'border-red-400 ring-1 ring-red-300/50 bg-red-50/30'
        : urgent
        ? 'border-amber-400 ring-1 ring-amber-200 bg-amber-50/20'
        : needsDeadline
        ? 'border-blue-400 ring-1 ring-blue-200 bg-blue-50/15'
        : isReview
        ? 'border-purple-300 ring-1 ring-purple-200 bg-purple-50/15'
        : order.has_rijek
        ? 'border-orange-300 bg-orange-50/20'
        : order.is_special_order
        ? 'border-violet-300 bg-violet-50/20'
        : '';

    return (
        <div className={`rounded-lg border bg-card p-3 shadow-sm hover:shadow-md transition-shadow ${borderClass}`}>
            {/* Header */}
            <div className="flex items-start justify-between gap-2">
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-1 flex-wrap">
                        <span className="font-mono text-[10px] text-muted-foreground">{order.no_po}</span>
                        {/* Brand badge (multi-brand view) */}
                        {order.brand_kode && (
                            <span
                                className="text-[9px] font-bold text-white px-1.5 py-0.5 rounded"
                                style={{ background: order.brand_warna || '#64748b' }}
                            >
                                {order.brand_kode}
                            </span>
                        )}
                    </div>
                    <div className="font-semibold text-sm leading-tight truncate mt-0.5">{order.nama_po}</div>
                    <div className="text-xs text-muted-foreground truncate">{order.pelanggan}</div>
                </div>
                <div className="flex flex-col items-end gap-0.5 shrink-0">
                    {order.is_locked && (
                        <Lock className="h-3 w-3 text-muted-foreground" title="Locked" />
                    )}
                </div>
            </div>

            {/* Visual Indicator Badges */}
            <div className="mt-2 flex flex-wrap gap-1">
                {/* Needs Deadline Badge */}
                {needsDeadline && (
                    <Badge className="text-[9px] px-1.5 py-0 h-4 bg-blue-600 hover:bg-blue-700 text-white font-bold">
                        ⚠️ Belum Ada Deadline Prod
                    </Badge>
                )}
                {/* Review Stage Badge */}
                {isReview && (
                    <Badge className="text-[9px] px-1.5 py-0 h-4 bg-purple-600 hover:bg-purple-700 text-white font-bold">
                        🔍 Review PO &amp; Mockup
                    </Badge>
                )}
                {/* Delay / Urgent / Deadline */}
                {overdue && (
                    <Badge variant="destructive" className="text-[9px] px-1.5 py-0 h-4">
                        🔴 {Math.abs(days)}h Telat
                    </Badge>
                )}
                {urgent && !overdue && (
                    <Badge variant="warning" className="text-[9px] px-1.5 py-0 h-4">
                        🟡 H-{days} URGENT
                    </Badge>
                )}
                {nearDeadline && !urgent && !overdue && (
                    <Badge variant="outline" className="text-[9px] px-1.5 py-0 h-4 border-amber-300 text-amber-700">
                        ⏰ H-{days}
                    </Badge>
                )}
                {/* Paket Order badge — warna dari master data */}
                {order.paket_order && order.paket_order.prioritas > 0 && (
                    <span
                        className="inline-flex items-center gap-0.5 rounded px-1.5 py-0 text-[9px] font-bold text-white h-4"
                        style={{ background: order.paket_order.warna }}
                        title={`Paket: ${order.paket_order.nama}`}
                    >
                        {order.paket_order.prioritas >= 2 ? '🚨' : '⚡'} {order.paket_order.nama}
                    </span>
                )}
                {/* Rijek indicator */}
                {order.has_rijek && (
                    <Badge variant="destructive" className="text-[9px] px-1.5 py-0 h-4 bg-orange-500">
                        ❌ Ada Rijek
                    </Badge>
                )}
                {/* PO Support */}
                {order.is_special_order && !order.paket_order && (
                    <Badge className="text-[9px] px-1.5 py-0 h-4 bg-amber-600 text-white font-extrabold">
                        ⭐ PO SUPPORT
                    </Badge>
                )}
            </div>
            
            {/* Active Stages / Tahapan Aktif */}
            {order.active_stages && order.active_stages.length > 0 && (
                <div className="mt-2 pt-2 border-t border-dashed border-slate-200">
                    <div className="text-[10px] font-medium text-slate-500 mb-1 flex items-center gap-1">
                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Tahap:
                    </div>
                    <div className="flex flex-wrap gap-1">
                        {order.active_stages.map((stage, idx) => (
                            <span
                                key={idx}
                                className="text-[9px] font-bold px-1.5 py-0.5 rounded border"
                                style={{
                                    backgroundColor: stage.warna ? `${stage.warna}15` : '#f1f5f9',
                                    color: stage.warna || '#475569',
                                    borderColor: stage.warna ? `${stage.warna}30` : '#cbd5e1'
                                }}
                            >
                                {stage.nama}
                            </span>
                        ))}
                    </div>
                </div>
            )}

            {/* Footer: deadline + qty + actions */}
            <div className="mt-2 flex items-center justify-between gap-1 text-[10px] text-muted-foreground">
                <span className="flex items-center gap-1" title={order.end_production_date ? `Deadline Produksi: ${formatDate(order.end_production_date)} (Deadline Cust: ${formatDate(order.deadline_customer)})` : `Deadline Customer: ${formatDate(order.deadline_customer)} (Deadline Produksi: Belum Set)`}>
                    <Calendar className={`h-3 w-3 ${order.end_production_date ? 'text-indigo-600' : 'text-amber-500'}`} />
                    {order.end_production_date ? (
                        <span className="font-bold text-indigo-700">
                            {formatDate(order.end_production_date)}
                        </span>
                    ) : (
                        <span className="font-medium text-amber-600 italic">
                            Cust: {formatDate(order.deadline_customer)}
                        </span>
                    )}
                </span>
                {order.total_items > 0 && (
                    <span className="flex items-center gap-1">
                        <Layers className="h-3 w-3" />
                        {order.total_items} pcs
                    </span>
                )}
            </div>

            {/* Primary Action Button for Baru Masuk */}
            {needsDeadline && (
                <div className="mt-2">
                    <Button
                        type="button"
                        size="sm"
                        onClick={() => onSetDeadline(order)}
                        className="h-7 w-full text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white gap-1 shadow-xs"
                    >
                        <Calendar className="h-3.5 w-3.5" /> + Set Deadline Produksi
                    </Button>
                </div>
            )}

            {/* Quick Action Button for Review Produksi */}
            {isReview && (
                <div className="mt-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => onSetDeadline(order)}
                        className="h-6.5 w-full text-[11px] font-semibold border-purple-300 text-purple-700 hover:bg-purple-50 gap-1 shadow-2xs"
                        title="Ubah Target Deadline Produksi"
                    >
                        <Calendar className="h-3 w-3 text-purple-600" /> Atur / Ubah Deadline
                    </Button>
                </div>
            )}

            <div className="mt-2 flex gap-1.5">
                <Button asChild size="sm" variant="ghost" className="h-7 flex-1 text-xs">
                    <Link href={route('orders.show', order.id)}>
                        <Eye className="h-3 w-3" /> Preview
                    </Link>
                </Button>
                <Button asChild size="sm" variant="outline" className="h-7 flex-1 text-xs">
                    <Link href={route('produksi.progress', order.id)}>Progress</Link>
                </Button>
            </div>
        </div>
    );
}

export default function Kanban({ columns: initialColumns }) {
    const [columns, setColumns] = useState(initialColumns);
    const [visibleCounts, setVisibleCounts] = useState({});
    const [timelineModalOpen, setTimelineModalOpen] = useState(false);
    const [selectedOrderForTimeline, setSelectedOrderForTimeline] = useState(null);

    // Keep state updated when props reload
    useEffect(() => {
        setColumns(initialColumns);
    }, [initialColumns]);

    const handleOpenTimeline = (order) => {
        setSelectedOrderForTimeline(order);
        setTimelineModalOpen(true);
    };

    // Count indicators for column header
    const totalOrders = Object.values(columns).reduce((s, c) => s + c.orders.length, 0);

    const handleLoadMore = (key, total) => {
        setVisibleCounts(prev => ({
            ...prev,
            [key]: (prev[key] || 30) + 30
        }));
    };

    return (
        <AppLayout title="Kanban Produksi">
            <Head title="Kanban Produksi" />

            {/* Legend */}
            <div className="mb-3 flex flex-wrap items-center gap-2 text-[11px] text-muted-foreground">
                <span className="flex items-center gap-1 font-semibold text-blue-700">🟦 Baru Masuk (Isi Deadline)</span>
                <span className="flex items-center gap-1 font-semibold text-purple-700">🟪 Review PO</span>
                <span className="flex items-center gap-1 font-semibold text-amber-700">🟨 Sedang Produksi</span>
                <span className="flex items-center gap-1">🔴 = Terlambat</span>
                <span className="flex items-center gap-1">🟡 = Deadline ≤2 hari</span>
                <span className="flex items-center gap-1">❌ = Ada Rijek</span>
                <span className="flex items-center gap-1">⭐ = Prioritas</span>
                <span className="flex items-center gap-1 ml-auto font-medium text-slate-600">Total: {totalOrders} PO</span>
            </div>

            <style>{`
                .kanban-column-scroll::-webkit-scrollbar {
                    width: 5px;
                    height: 5px;
                }
                .kanban-column-scroll::-webkit-scrollbar-track {
                    background: transparent;
                }
                .kanban-column-scroll::-webkit-scrollbar-thumb {
                    background-color: #cbd5e1;
                    border-radius: 20px;
                }
                .kanban-column-scroll::-webkit-scrollbar-thumb:hover {
                    background-color: #94a3b8;
                }
            `}</style>

            <div className="-mx-4 overflow-x-auto px-4 pb-2">
                <div className="flex min-w-max gap-4 h-[calc(100vh-175px)] min-h-[450px] pb-2">
                    {Object.entries(columns).map(([key, col]) => {
                        const overdueCount = col.orders.filter(o => o.days_remaining !== null && o.days_remaining < 0).length;
                        const totalPcs = col.orders.reduce((sum, o) => sum + (o.total_items || 0), 0);
                        const limit = visibleCounts[key] || 30;
                        const displayedOrders = col.orders.slice(0, limit);
                        const hasMore = col.orders.length > limit;

                        return (
                            <div key={key} className="flex w-72 shrink-0 flex-col rounded-xl border bg-slate-50/50 p-2 h-full max-h-full">
                                {/* Column Header */}
                                <div
                                    className="mb-2 flex flex-col gap-0.5 rounded-lg px-3 py-2 text-white shadow-sm shrink-0"
                                    style={{ background: col.color }}
                                >
                                    <div className="flex items-center justify-between text-sm font-semibold">
                                        <span>{col.label}</span>
                                        <div className="flex items-center gap-1.5">
                                            {overdueCount > 0 && (
                                                <span className="flex h-4 w-4 items-center justify-center rounded-full bg-white/30 text-[9px] font-black" title="Overdue">
                                                    {overdueCount}⚠
                                                </span>
                                            )}
                                            <Badge variant="outline" className="border-white/40 bg-white/20 text-[10px] font-bold text-white px-1.5 py-0">
                                                {col.orders.length} PO
                                            </Badge>
                                        </div>
                                    </div>
                                    <div className="text-[10px] text-white/85 font-medium flex justify-between">
                                        <span>Total Qty:</span>
                                        <span className="font-bold">{totalPcs} pcs</span>
                                    </div>
                                </div>

                                {/* Cards */}
                                <div className="flex-1 overflow-y-auto space-y-2 pr-1.5 min-h-0 kanban-column-scroll">
                                    {col.orders.length === 0 && (
                                        <div className="rounded-lg border-2 border-dashed py-8 text-center text-xs text-muted-foreground shrink-0">
                                            Kosong
                                        </div>
                                    )}
                                    {displayedOrders.map((o) => (
                                        <KanbanCard key={o.id} order={o} onSetDeadline={handleOpenTimeline} />
                                    ))}
                                    {hasMore && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleLoadMore(key, col.orders.length)}
                                            className="w-full text-xs text-muted-foreground hover:text-foreground py-2 h-auto"
                                        >
                                            + Tampilkan {col.orders.length - limit} PO lainnya
                                        </Button>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            <SetTimelineDialog
                open={timelineModalOpen}
                onOpenChange={setTimelineModalOpen}
                order={selectedOrderForTimeline}
            />
        </AppLayout>
    );
}

