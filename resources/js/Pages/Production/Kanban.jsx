import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { AlertTriangle, Calendar, Lock, Eye } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/lib/utils';

function KanbanCard({ order }) {
    const days = order.days_remaining;
    const overdue = days !== null && days < 0;
    const urgent = days !== null && days <= 2 && days >= 0;

    return (
        <div
            className={`rounded-lg border bg-card p-3 shadow-sm hover:shadow-md transition ${
                overdue ? 'border-destructive ring-1 ring-destructive/30' : urgent ? 'border-amber-400 ring-1 ring-amber-200' : ''
            }`}
        >
            <div className="flex items-start justify-between gap-2">
                <div className="flex-1">
                    <div className="font-mono text-[11px] text-muted-foreground">{order.no_po}</div>
                    <div className="font-medium leading-tight">{order.nama_po}</div>
                    <div className="text-xs text-muted-foreground">{order.pelanggan}</div>
                </div>
                {order.is_locked && <Lock className="h-3 w-3 shrink-0 text-muted-foreground" title="Locked" />}
            </div>
            <div className="mt-2 flex items-center justify-between gap-2">
                <Badge variant={overdue ? 'destructive' : urgent ? 'warning' : 'outline'} className="text-[10px]">
                    <Calendar className="mr-1 h-3 w-3" />
                    {formatDate(order.deadline_customer)}
                </Badge>
                {(overdue || urgent) && (
                    <Badge variant={overdue ? 'destructive' : 'warning'} className="text-[10px]">
                        {overdue ? <><AlertTriangle className="mr-1 h-3 w-3" />{Math.abs(days)} hari telat</> : `H-${days}`}
                    </Badge>
                )}
            </div>
            <div className="mt-2 flex gap-1">
                <Button asChild size="sm" variant="ghost" className="h-7 flex-1 text-xs">
                    <Link href={route('orders.show', order.id)}><Eye className="h-3 w-3" /> Preview</Link>
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

    return (
        <AppLayout title="Kanban Produksi">
            <Head title="Kanban Produksi" />

            <div className="-mx-4 overflow-x-auto px-4 pb-4">
                <div className="flex min-w-max gap-4">
                    {Object.entries(columns).map(([key, col]) => (
                        <div key={key} className="flex w-72 shrink-0 flex-col rounded-lg p-2">
                            <div className="mb-2 flex items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-white" style={{ background: col.color }}>
                                <span>{col.label}</span>
                                <Badge variant="outline" className="border-white/40 bg-white/20 text-xs text-white">{col.orders.length}</Badge>
                            </div>
                            <div className="min-h-[200px] space-y-2">
                                {col.orders.length === 0 && (
                                    <div className="rounded-lg border-2 border-dashed py-8 text-center text-xs text-muted-foreground">Kosong</div>
                                )}
                                {col.orders.map((o) => (
                                    <KanbanCard key={o.id} order={o} />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
