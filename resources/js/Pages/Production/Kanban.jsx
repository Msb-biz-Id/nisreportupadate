import { Head, Link, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import axios from 'axios';
import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import { AlertTriangle, Calendar, Lock, Eye, Info } from 'lucide-react';
import { toast } from 'sonner';
import AppLayout from '@/Layouts/AppLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { formatDate } from '@/lib/utils';

// Mirror dari ProductionController::TRANSITIONS untuk client-side validation
const VALID_TRANSITIONS = {
    published: ['on_progress', 'hold'],
    on_progress: ['hold', 'delay'],
    selesai_produksi: ['siap_dikirim', 'hold'],
    siap_dikirim: ['sudah_dikirim', 'hold'],
    sudah_dikirim: [],
    delay: ['on_progress', 'hold'],
    hold: ['published', 'on_progress'],
};

function KanbanCard({ order, index }) {
    const days = order.days_remaining;
    const overdue = days !== null && days < 0;
    const urgent = days !== null && days <= 2 && days >= 0;

    return (
        <Draggable draggableId={order.id} index={index}>
            {(provided, snapshot) => (
                <div
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                    {...provided.dragHandleProps}
                    className={`rounded-lg border bg-card p-3 shadow-sm transition ${
                        snapshot.isDragging ? 'ring-2 ring-primary shadow-lg' : 'hover:shadow-md'
                    } ${overdue ? 'border-destructive ring-1 ring-destructive/30' : urgent ? 'border-amber-400 ring-1 ring-amber-200' : ''}`}
                    style={{ ...provided.draggableProps.style }}
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
            )}
        </Draggable>
    );
}

export default function Kanban({ columns: initialColumns }) {
    const [columns, setColumns] = useState(initialColumns);
    const [moving, setMoving] = useState(null);

    const validDestKeys = useMemo(() => {
        const map = {};
        for (const [from, tos] of Object.entries(VALID_TRANSITIONS)) {
            map[from] = new Set(tos);
        }
        return map;
    }, []);

    async function onDragEnd(result) {
        const { source, destination, draggableId } = result;
        if (!destination) return;
        if (source.droppableId === destination.droppableId && source.index === destination.index) return;

        const fromStatus = source.droppableId;
        const toStatus = destination.droppableId;

        // Validasi client-side transition
        if (fromStatus !== toStatus && !validDestKeys[fromStatus]?.has(toStatus)) {
            toast.error(`Transisi ${fromStatus} → ${toStatus} tidak diizinkan. Gunakan halaman progress untuk transisi otomatis.`);
            return;
        }

        // Optimistic update
        const prev = columns;
        const next = JSON.parse(JSON.stringify(columns));
        const movedCard = next[fromStatus].orders.splice(source.index, 1)[0];
        next[toStatus].orders.splice(destination.index, 0, movedCard);
        setColumns(next);
        setMoving(draggableId);

        if (fromStatus === toStatus) {
            // Reorder within same column - tidak perlu API call
            setMoving(null);
            return;
        }

        try {
            await axios.put(route('produksi.move-status', draggableId), { to_status: toStatus });
            toast.success(`PO dipindah: ${fromStatus} → ${toStatus}`);
        } catch (err) {
            // Rollback
            setColumns(prev);
            const msg = err?.response?.data?.error || 'Gagal memindah PO';
            toast.error(msg);
        } finally {
            setMoving(null);
        }
    }

    return (
        <AppLayout title="Kanban Produksi">
            <Head title="Kanban Produksi" />

            <Card className="mb-4 border-blue-200 bg-blue-50">
                <CardContent className="flex items-start gap-3 p-3 text-sm">
                    <Info className="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
                    <div className="text-blue-900">
                        <strong>Drag &amp; drop</strong> untuk pindah status manual (Hold, Sudah Dikirim, dll). Untuk transisi otomatis (Selesai Produksi, Siap Dikirim) gunakan tombol <em>Progress</em> di card.
                    </div>
                </CardContent>
            </Card>

            <DragDropContext onDragEnd={onDragEnd}>
                <div className="-mx-4 overflow-x-auto px-4 pb-4">
                    <div className="flex min-w-max gap-4">
                        {Object.entries(columns).map(([key, col]) => (
                            <Droppable key={key} droppableId={key}>
                                {(provided, snapshot) => (
                                    <div
                                        ref={provided.innerRef}
                                        {...provided.droppableProps}
                                        className={`flex w-72 shrink-0 flex-col rounded-lg p-2 transition ${snapshot.isDraggingOver ? 'bg-accent' : ''}`}
                                    >
                                        <div className="mb-2 flex items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-white" style={{ background: col.color }}>
                                            <span>{col.label}</span>
                                            <Badge variant="outline" className="border-white/40 bg-white/20 text-xs text-white">{col.orders.length}</Badge>
                                        </div>
                                        <div className="min-h-[200px] space-y-2">
                                            {col.orders.length === 0 && !snapshot.isDraggingOver && (
                                                <div className="rounded-lg border-2 border-dashed py-8 text-center text-xs text-muted-foreground">Kosong</div>
                                            )}
                                            {col.orders.map((o, idx) => (
                                                <KanbanCard key={o.id} order={o} index={idx} />
                                            ))}
                                            {provided.placeholder}
                                        </div>
                                    </div>
                                )}
                            </Droppable>
                        ))}
                    </div>
                </div>
            </DragDropContext>
        </AppLayout>
    );
}
