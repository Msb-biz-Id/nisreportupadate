import { Head, Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { 
    AlertTriangle, 
    Calendar, 
    LayoutGrid, 
    Package, 
    CheckCircle2, 
    Truck,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
import ShippingDialog from '@/Components/Order/ShippingDialog';

const STATUS_ORDER = ['baru_masuk', 'review', 'published', 'on_progress', 'delay', 'hold', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'];
const ALWAYS_VISIBLE_STATUSES = ['baru_masuk', 'review', 'on_progress'];

function DaysRemaining({ days }) {
    if (days === null) return null;
    if (days < 0) return <span className="text-xs font-semibold text-red-600 dark:text-red-400">Terlambat {Math.abs(days)} hari</span>;
    if (days === 0) return <span className="text-xs font-semibold text-amber-600 dark:text-amber-400">Hari ini</span>;
    if (days <= 2) return <span className="text-xs font-semibold text-yellow-600 dark:text-yellow-400">{days} hari lagi</span>;
    return <span className="text-xs text-muted-foreground">{days} hari lagi</span>;
}

export default function Gantt({ items = [], statusColors = {}, statusLabels = {}, ekspedisiList = [] }) {
    const [filterStatus, setFilterStatus] = useState('all');
    const [sortOrder, setSortOrder] = useState('newest');
    const [shippingModalOpen, setShippingModalOpen] = useState(false);
    const [selectedOrderForShipping, setSelectedOrderForShipping] = useState(null);

    const handleOpenShippingModal = (order) => {
        setSelectedOrderForShipping(order);
        setShippingModalOpen(true);
    };

    // Filter berdasarkan status & diurutkan (pencarian ditangani DataTable)
    const filtered = useMemo(() => {
        const res = items.filter((item) => {
            const isItemDelayed = item.status_po === 'delay' || (item.days_remaining !== null && item.days_remaining < 0 && !['selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'].includes(item.status_po));
            const matchesStatus = filterStatus === 'all'
                ? true
                : filterStatus === 'delay'
                ? isItemDelayed
                : item.status_po === filterStatus;

            return matchesStatus;
        });

        return [...res].sort((a, b) => {
            const timeA = a.created_at ? new Date(a.created_at).getTime() : 0;
            const timeB = b.created_at ? new Date(b.created_at).getTime() : 0;
            return sortOrder === 'newest' ? timeB - timeA : timeA - timeB;
        });
    }, [items, filterStatus, sortOrder]);

    // Ringkasan KPI & status
    const summary = useMemo(() => {
        const counts = {};
        let totalPcs = 0;
        let overdueCount = 0;
        let finishedCount = 0;

        for (const item of items) {
            const isItemDelayed = item.status_po === 'delay' || (item.days_remaining !== null && item.days_remaining < 0 && !['selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'].includes(item.status_po));

            counts[item.status_po] = (counts[item.status_po] ?? 0) + 1;
            totalPcs += Number(item.total_pcs || 0);

            if (isItemDelayed) {
                overdueCount++;
            }
            if (['selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'].includes(item.status_po)) {
                finishedCount++;
            }
        }

        // Unify delay count with overdue count so tab badge matches KPI card
        if (overdueCount > 0) {
            counts['delay'] = overdueCount;
        }

        return { counts, totalPcs, overdueCount, finishedCount };
    }, [items]);


    const activeStatuses = STATUS_ORDER.filter((s) => summary.counts[s] || ALWAYS_VISIBLE_STATUSES.includes(s));

    const poColumns = useMemo(() => [
        {
            id: 'no_po',
            accessorFn: (row) => row.no_po || '',
            meta: { title: 'No. PO', cellClassName: 'font-mono text-xs font-semibold text-primary' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="No. PO" />,
            cell: ({ row }) => <span className="font-mono text-xs font-semibold text-primary">{row.original.no_po}</span>,
        },
        {
            id: 'nama_po',
            accessorFn: (row) => row.nama_po || '',
            meta: { title: 'Nama PO', cellClassName: 'font-medium' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nama PO" />,
            cell: ({ row }) => <span className="font-medium">{row.original.nama_po ?? '-'}</span>,
        },
        {
            id: 'pelanggan',
            accessorFn: (row) => row.pelanggan || '',
            meta: { title: 'Pelanggan', cellClassName: 'text-muted-foreground' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Pelanggan" />,
            cell: ({ row }) => <span className="text-muted-foreground">{row.original.pelanggan ?? '-'}</span>,
        },
        {
            id: 'brand',
            accessorFn: (row) => row.brand_kode || '',
            meta: { title: 'Brand', cellClassName: 'text-xs text-muted-foreground font-medium' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Brand" />,
            cell: ({ row }) => <span className="text-xs text-muted-foreground font-medium">{row.original.brand_kode ?? '-'}</span>,
        },
        {
            id: 'total_pcs',
            accessorFn: (row) => row.total_pcs ?? 0,
            meta: { title: 'Qty Pcs', cellClassName: 'text-center font-semibold text-xs' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Qty Pcs" className="justify-center" />,
            cell: ({ row }) => (
                <span className="text-center font-semibold text-xs block">{row.original.total_pcs ? `${row.original.total_pcs} Pcs` : '-'}</span>
            ),
        },
        {
            id: 'status',
            accessorFn: (row) => row.status_label || row.status_po || '',
            meta: { title: 'Status' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
            cell: ({ row }) => (
                <span
                    className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold text-white shadow-xs"
                    style={{ backgroundColor: row.original.status_po === 'delay' ? '#F59E0B' : row.original.color }}
                >
                    {row.original.status_po === 'delay' ? 'Sedang Produksi' : row.original.status_label}
                </span>
            ),
        },
        {
            id: 'current_progress',
            accessorFn: (row) => row.current_progress?.nama || '',
            meta: { title: 'Tahap Progress' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Tahap Progress" />,
            cell: ({ row }) => {
                const cp = row.original.current_progress;
                if (!cp) return <span className="text-muted-foreground text-xs">-</span>;
                return (
                    <span
                        className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold text-white shadow-xs whitespace-nowrap"
                        style={{ backgroundColor: cp.warna || '#64748B' }}
                    >
                        {cp.status === 'on_progress' && (
                            <span className="h-1.5 w-1.5 rounded-full bg-white animate-pulse" />
                        )}
                        {cp.nama}
                    </span>
                );
            },
        },
        {
            id: 'ekspedisi',
            accessorFn: (row) => `${row.nama_ekspedisi || ''} ${row.no_resi || ''}`,
            meta: { title: 'Ekspedisi & Resi' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Ekspedisi & Resi" />,
            cell: ({ row }) => {
                const ekspedisi = row.original.nama_ekspedisi;
                const resi = row.original.no_resi;
                if (!ekspedisi && !resi) {
                    return (
                        <span className="inline-flex items-center text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md font-medium">
                            Belum diisi
                        </span>
                    );
                }
                return (
                    <div className="flex flex-col gap-0.5 min-w-[120px]">
                        <span className="font-semibold text-xs text-slate-800">{ekspedisi || '-'}</span>
                        {resi ? (
                            <span className="font-mono text-[11px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 px-1.5 py-0.2 rounded w-fit">
                                {resi}
                            </span>
                        ) : (
                            <span className="text-[10px] text-amber-600 italic">No. resi belum diisi</span>
                        )}
                    </div>
                );
            },
        },
        {
            id: 'start',
            accessorFn: (row) => row.start || '',
            meta: { title: 'Mulai', cellClassName: 'text-xs text-muted-foreground' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Mulai" />,
            cell: ({ row }) => (
                <span className="text-xs text-muted-foreground">
                    {row.original.start ? new Date(row.original.start).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) : '-'}
                </span>
            ),
        },
        {
            id: 'deadline',
            accessorFn: (row) => row.end_production_date || row.deadline_customer || '',
            meta: { title: 'Deadline', cellClassName: 'text-xs font-medium' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Deadline" />,
            cell: ({ row }) => (
                <span className="text-xs font-medium">
                    {(row.original.end_production_date || row.original.deadline_customer)
                        ? new Date(row.original.end_production_date || row.original.deadline_customer).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                        : '-'}
                </span>
            ),
        },
        {
            id: 'days_remaining',
            accessorFn: (row) => row.days_remaining ?? '',
            meta: { title: 'Sisa Waktu' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Sisa Waktu" />,
            cell: ({ row }) => {
                const overdue = row.original.days_remaining !== null && row.original.days_remaining < 0;
                return (
                    <>
                        {overdue && <AlertTriangle className="h-3.5 w-3.5 inline text-red-500 mr-1 animate-pulse" />}
                        <DaysRemaining days={row.original.days_remaining} />
                    </>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Aksi</div>,
            cell: ({ row }) => (
                <div className="text-right flex items-center justify-end gap-1.5">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleOpenShippingModal(row.original)}
                        className="h-7 text-xs border-indigo-200 text-indigo-700 hover:bg-indigo-50 hover:text-indigo-800 px-2 flex items-center gap-1 font-semibold"
                        title="Input / Edit Ekspedisi & Resi"
                    >
                        <Truck className="h-3.5 w-3.5 text-indigo-600" />
                        <span>{row.original.no_resi ? 'Edit Resi' : 'Input Resi'}</span>
                    </Button>
                    <Button variant="ghost" size="sm" asChild className="h-7 text-xs text-primary hover:text-primary px-2">
                        <Link href={row.original.detail_url}>Detail &rarr;</Link>
                    </Button>
                </div>
            ),
        },
    ], []);

    return (
        <AppLayout>
            <Head title="Order Produksi" />
            <div className="space-y-5">
                {/* Header + Statistik Terpadu */}
                <div className="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div className="flex flex-wrap items-center justify-between gap-3 p-4 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h1 className="text-xl font-bold tracking-tight flex items-center gap-2">
                                <Calendar className="h-5 w-5 text-primary" />
                                Order Produksi
                            </h1>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Daftar &amp; status order produksi aktif (real-time)
                            </p>
                        </div>

                        <Button variant="default" size="sm" asChild className="h-9 gap-1.5">
                            <Link href={route('produksi.kanban')}>
                                <LayoutGrid className="h-4 w-4" />
                                <span className="font-medium text-xs">Board Kanban</span>
                            </Link>
                        </Button>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 dark:divide-slate-800">
                        <div className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                <Calendar className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Order Aktif</p>
                                <p className="text-lg font-bold">{items.length} <span className="text-xs font-normal text-muted-foreground">PO</span></p>
                            </div>
                        </div>

                        <div className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                                <Package className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Total Pcs</p>
                                <p className="text-lg font-bold">{summary.totalPcs.toLocaleString('id-ID')} <span className="text-xs font-normal text-muted-foreground">Pcs</span></p>
                            </div>
                        </div>

                        <div
                            onClick={() => setFilterStatus(filterStatus === 'delay' ? 'all' : 'delay')}
                            className={`p-4 flex items-center gap-3 cursor-pointer transition-all ${filterStatus === 'delay' ? 'bg-red-50/40 dark:bg-red-950/20' : ''}`}
                            title="Klik untuk menyaring semua PO yang terlambat/delay"
                        >
                            <div className="p-2.5 rounded-lg bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">
                                <AlertTriangle className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Terlambat / Delay</p>
                                <p className="text-lg font-bold text-red-600 dark:text-red-400">{summary.overdueCount} <span className="text-xs font-normal text-muted-foreground">PO</span></p>
                            </div>
                        </div>

                        <div className="p-4 flex items-center gap-3">
                            <div className="p-2.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <CheckCircle2 className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Selesai / Siap</p>
                                <p className="text-lg font-bold text-emerald-600 dark:text-emerald-400">{summary.finishedCount} <span className="text-xs font-normal text-muted-foreground">PO</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Daftar Order Produksi - DataTable (sticky filter atas, sticky footer pagination, pin aksi, Salin ke Excel) */}
                <DataTable
                    columns={poColumns}
                    data={filtered}
                    searchPlaceholder="Cari No. PO, nama PO, pelanggan..."
                    exportFileName={`Order_Produksi_${new Date().toISOString().slice(0, 10)}`}
                    emptyMessage="Tidak ada order produksi."
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-1.5">
                            <button
                                onClick={() => setFilterStatus('all')}
                                className={`rounded-full px-3 py-1 text-xs font-semibold transition-all ${
                                    filterStatus === 'all'
                                        ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 shadow-sm'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300'
                                }`}
                            >
                                Semua ({items.length})
                            </button>
                            {activeStatuses.map((s) => (
                                <button
                                    key={s}
                                    onClick={() => setFilterStatus(filterStatus === s ? 'all' : s)}
                                    className="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium text-white transition-opacity hover:opacity-90 shadow-sm"
                                    style={{
                                        backgroundColor: statusColors[s],
                                        opacity: filterStatus !== 'all' && filterStatus !== s ? 0.35 : 1,
                                    }}
                                >
                                    {statusLabels[s]}
                                    <span className="rounded-full bg-white/30 px-1.5 py-0.2 text-[10px] font-bold">{summary.counts[s]}</span>
                                </button>
                            ))}
                            <span className="text-xs text-muted-foreground whitespace-nowrap ml-1">Urutkan:</span>
                            <Select value={sortOrder} onValueChange={setSortOrder}>
                                <SelectTrigger className="h-8 text-xs w-[145px] bg-background">
                                    <SelectValue placeholder="Pilih urutan" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="newest">Terbaru Pertama</SelectItem>
                                    <SelectItem value="oldest">Terlama Pertama</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    }
                />
            </div>

            <ShippingDialog
                open={shippingModalOpen}
                onOpenChange={setShippingModalOpen}
                order={selectedOrderForShipping}
                ekspedisiList={ekspedisiList}
            />
        </AppLayout>
    );
}
