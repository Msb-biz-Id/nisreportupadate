import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { AlertTriangle, Calendar, Lock, Eye, Star, XCircle, Layers } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/lib/utils';

function KanbanCard({ order }) {
    const days      = order.days_remaining;
    const overdue   = days !== null && days < 0;
    const urgent    = days !== null && days <= 2 && days >= 0;
    const nearDeadline = days !== null && days <= 7 && days > 2;

    // Visual border based on priority
    const borderClass = overdue
        ? 'border-red-400 ring-1 ring-red-300/50 bg-red-50/30'
        : urgent
        ? 'border-amber-400 ring-1 ring-amber-200 bg-amber-50/20'
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
                {/* Special Order */}
                {order.is_special_order && !order.paket_order && (
                    <Badge className="text-[9px] px-1.5 py-0 h-4 bg-violet-600 text-white">
                        ⭐ SPECIAL
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
                <span className="flex items-center gap-1" title={order.end_production_date ? `Deadline Produksi: ${formatDate(order.end_production_date)} (Deadline Cust: ${formatDate(order.deadline_customer)})` : `Deadline Customer: ${formatDate(order.deadline_customer)}`}>
                    <Calendar className={`h-3 w-3 ${order.end_production_date ? 'text-indigo-600' : 'text-slate-400'}`} />
                    {order.end_production_date ? (
                        <span className="font-bold text-indigo-700">
                            {formatDate(order.end_production_date)}
                        </span>
                    ) : (
                        <span>{formatDate(order.deadline_customer)}</span>
                    )}
                </span>
                {order.total_items > 0 && (
                    <span className="flex items-center gap-1">
                        <Layers className="h-3 w-3" />
                        {order.total_items} pcs
                    </span>
                )}
            </div>

            <div className="mt-2 flex gap-1">
                <Button asChild size="sm" variant="ghost" className="h-7 flex-1 text-xs">
                    <Link href={route('orders.show', order.id)}>
                        <Eye className="h-3 w-3" /> Preview
                    </Link>
                </Button>
                <Button asChild size="sm" variant="outline" className="h-7 text-xs">
                    <Link href={route('produksi.progress', order.id)}>Progress</Link>
                </Button>
            </div>
        </div>
    );
}

export default function Kanban({ columns: initialColumns }) {
    const [columns] = useState(initialColumns);

    // Count indicators for column header
    const totalOrders = Object.values(columns).reduce((s, c) => s + c.orders.length, 0);

    return (
        <AppLayout title="Kanban Produksi">
            <Head title="Kanban Produksi" />

            {/* Legend */}
            <div className="mb-3 flex flex-wrap gap-2 text-[11px] text-muted-foreground">
                <span className="flex items-center gap-1">🔴 = Terlambat</span>
                <span className="flex items-center gap-1">🟡 = Deadline ≤2 hari</span>
                <span className="flex items-center gap-1">❌ = Ada Rijek</span>
                <span className="flex items-center gap-1">⭐ = Special/Prioritas</span>
                <span className="flex items-center gap-1 ml-auto font-medium text-slate-600">Total: {totalOrders} PO</span>
            </div>

            <div className="-mx-4 overflow-x-auto px-4 pb-4">
                <div className="flex min-w-max gap-4">
                    {Object.entries(columns).map(([key, col]) => {
                        const overdueCount = col.orders.filter(o => o.days_remaining !== null && o.days_remaining < 0).length;
                        const totalPcs = col.orders.reduce((sum, o) => sum + (o.total_items || 0), 0);

                        return (
                            <div key={key} className="flex w-72 shrink-0 flex-col rounded-xl border bg-slate-50/50 p-2">
                                {/* Column Header */}
                                <div
                                    className="mb-2 flex flex-col gap-0.5 rounded-lg px-3 py-2 text-white shadow-sm"
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
                                <div className="min-h-[200px] space-y-2">
                                    {col.orders.length === 0 && (
                                        <div className="rounded-lg border-2 border-dashed py-8 text-center text-xs text-muted-foreground">
                                            Kosong
                                        </div>
                                    )}
                                    {col.orders.map((o) => (
                                        <KanbanCard key={o.id} order={o} />
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
