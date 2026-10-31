import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    ShoppingBag,
    Plus,
    History,
    Search,
    Filter,
    Calendar,
    ArrowUpDown,
    Eye,
    Pencil,
    Trash2,
    Clock,
    CheckCircle2,
    DollarSign,
    Phone,
    X,
    Wallet,
    AlertTriangle,
} from 'lucide-react';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
import PurchaseHistoryModal from './Components/PurchaseHistoryModal';

export default function Index({
    orders,
    metrics = {},
    statuses = [],
    locations = [],
    departments = [],
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [settlementStatus, setSettlementStatus] = useState(filters.settlement_status || 'all');
    const [orderType, setOrderType] = useState(filters.order_type || 'all');
    const [department, setDepartment] = useState(filters.department || 'all');
    const [locationId, setLocationId] = useState(filters.location_id ? String(filters.location_id) : 'all');
    const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);

    const handleFilterChange = (newFilters = {}) => {
        const queryParams = {
            search: search || undefined,
            status: status !== 'all' ? status : undefined,
            settlement_status: settlementStatus !== 'all' ? settlementStatus : undefined,
            order_type: orderType !== 'all' ? orderType : undefined,
            department: department !== 'all' ? department : undefined,
            location_id: locationId !== 'all' ? locationId : undefined,
            ...newFilters,
        };

        router.get(route('purchasing.orders.index'), queryParams, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        handleFilterChange({ search: search || undefined });
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('all');
        setSettlementStatus('all');
        setOrderType('all');
        setDepartment('all');
        setLocationId('all');
        router.get(route('purchasing.orders.index'), {}, {
            preserveState: true,
            replace: true,
        });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    const getStatusBadge = (st) => {
        switch (st) {
            case 'DRAFT':
                return <Badge variant="outline" className="bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 text-xs font-medium">Draft</Badge>;
            case 'PENDING_PIC_CHECK':
                return <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-300 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800 text-xs font-semibold">Menunggu PIC</Badge>;
            case 'APPROVED_BY_PIC':
                return <Badge variant="outline" className="bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800 text-xs font-semibold">Menunggu Finance</Badge>;
            case 'PURCHASE_COMPLETED':
            case 'PAID_COMPLETED':
                return <Badge variant="outline" className="bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800 text-xs font-semibold">Selesai / Lunas</Badge>;
            case 'REJECTED':
                return <Badge variant="outline" className="bg-rose-50 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 text-xs font-semibold">Ditolak</Badge>;
            default:
                return <Badge variant="outline">{st}</Badge>;
        }
    };

    const getCleanPhone = (phone) => {
        if (!phone) return null;
        let clean = phone.replace(/[^0-9]/g, '');
        if (clean.startsWith('0')) clean = '62' + clean.slice(1);
        return clean;
    };

    const handleDelete = (poNumber) => {
        if (window.confirm(`Yakin ingin menghapus Order ${poNumber}?`)) {
            router.delete(route('purchasing.orders.destroy', poNumber));
        }
    };

    // Definisi Kolom TanStack Table v9
    const columns = useMemo(() => [
        {
            accessorKey: 'po_number',
            header: ({ column }) => <DataTableColumnHeader column={column} title="No. PO & Tanggal" />,
            meta: { title: 'No. PO' },
            size: 145,
            minSize: 135,
            cell: ({ row }) => {
                const order = row.original;
                return (
                    <div className="min-w-[130px]">
                        <Link
                            href={route('purchasing.orders.show', order.po_number)}
                            className="font-mono font-bold text-blue-600 hover:text-blue-700 hover:underline block"
                        >
                            {order.po_number}
                        </Link>
                        <span className="text-[11px] text-slate-400">
                            {formatDate(order.transaction_date)}
                        </span>
                    </div>
                );
            },
        },
        {
            accessorKey: 'requester_name',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Tipe & Pemohon" />,
            meta: { title: 'Pemohon' },
            size: 190,
            minSize: 180,
            cell: ({ row }) => {
                const order = row.original;
                return (
                    <div className="min-w-[175px]">
                        <div className="flex items-center gap-1.5 mb-1">
                            <Badge variant="outline" className={`text-[10px] px-1.5 py-0 font-medium shrink-0 ${order.order_type === 'CAPEX' ? 'border-purple-300 text-purple-700 bg-purple-50/50 dark:border-purple-800 dark:text-purple-300' : 'border-slate-300 text-slate-700 bg-slate-50 dark:border-slate-700 dark:text-slate-300'}`}>
                                {order.order_type === 'CAPEX' ? 'Aset Tetap' : 'Operasional'}
                            </Badge>
                            <span className="font-medium text-slate-800 dark:text-slate-200 truncate text-xs" title={order.requester_name}>
                                {order.requester_name}
                            </span>
                        </div>
                        <p className="text-[11px] text-slate-400 truncate" title={`${order.department} (${order.position})`}>
                            {order.department} ({order.position})
                        </p>
                    </div>
                );
            },
        },
        {
            accessorKey: 'item_name',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Barang / Kebutuhan" />,
            meta: { title: 'Barang' },
            size: 220,
            minSize: 200,
            cell: ({ row }) => {
                const order = row.original;
                return (
                    <div className="min-w-[195px]">
                        <p className="font-semibold text-slate-900 dark:text-slate-100 truncate text-xs" title={order.item_name}>
                            {order.item_name}
                        </p>
                        <p className="text-[11px] text-slate-400 truncate">
                            {parseFloat(order.quantity)} {order.unit} @ {formatRupiah(order.unit_price)}
                        </p>
                    </div>
                );
            },
        },
        {
            accessorKey: 'dates_timeline',
            header: () => <div className="text-left font-semibold text-xs whitespace-nowrap">Rekam 3 Tanggal</div>,
            meta: { title: '3 Tanggal' },
            size: 200,
            minSize: 190,
            cell: ({ row }) => {
                const o = row.original;
                return (
                    <div className="min-w-[185px] space-y-1 text-[11px]">
                        <div className="flex items-center justify-between gap-1 text-slate-600 dark:text-slate-300">
                            <span className="text-slate-400 font-medium">1. Pengajuan:</span>
                            <span className="font-semibold">{formatDate(o.transaction_date)}</span>
                        </div>
                        <div className="flex items-center justify-between gap-1">
                            <span className="text-slate-400 font-medium">2. Diterima:</span>
                            <span className={`font-semibold ${o.disbursed_at ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 italic'}`}>
                                {o.disbursed_at ? formatDate(o.disbursed_at) : 'Belum cair'}
                            </span>
                        </div>
                        <div className="flex items-center justify-between gap-1">
                            <span className="text-slate-400 font-medium">3. Realisasi:</span>
                            <span className={`font-semibold ${o.realized_at ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 italic'}`}>
                                {o.realized_at ? formatDate(o.realized_at) : 'Belum ada nota'}
                            </span>
                        </div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'financial_settlement',
            header: () => <div className="text-right font-semibold text-xs pr-1 whitespace-nowrap">Kas Bon & Realisasi</div>,
            meta: { title: 'Kas Bon & Realisasi' },
            size: 180,
            minSize: 170,
            cell: ({ row }) => {
                const o = row.original;
                return (
                    <div className="min-w-[165px] text-right space-y-0.5 text-[11px] font-mono">
                        <div className="flex items-center justify-between text-slate-500">
                            <span className="font-sans text-[10px] text-slate-400">Pengajuan:</span>
                            <span>{formatRupiah(o.grand_total)}</span>
                        </div>
                        <div className="flex items-center justify-between text-blue-600 dark:text-blue-400">
                            <span className="font-sans text-[10px] text-slate-400">Diterima:</span>
                            <span className="font-semibold">{formatRupiah(o.disbursed_amount || 0)}</span>
                        </div>
                        <div className="flex items-center justify-between text-slate-800 dark:text-slate-200">
                            <span className="font-sans text-[10px] text-slate-400">Realisasi:</span>
                            <span className="font-semibold">{formatRupiah(o.realized_amount || 0)}</span>
                        </div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'remaining_amount',
            header: () => <div className="text-right font-semibold text-xs pr-1 whitespace-nowrap">Sisa Uang & Status</div>,
            meta: { title: 'Sisa Uang' },
            size: 170,
            minSize: 160,
            cell: ({ row }) => {
                const o = row.original;
                const rem = parseFloat(o.remaining_amount || 0);
                const status = o.settlement_status || 'NONE';

                let badge = null;
                if (status === 'DISBURSED_PENDING_RECEIPT') {
                    badge = <Badge variant="outline" className="bg-amber-50 text-amber-700 border-amber-300 text-[10px] px-1.5 py-0 font-medium">Nota Belum Diunggah</Badge>;
                } else if (status === 'REFUND_PENDING') {
                    badge = <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-300 text-[10px] px-1.5 py-0 font-semibold">Setor Kembalian</Badge>;
                } else if (status === 'REIMBURSE_PENDING') {
                    badge = <Badge variant="outline" className="bg-rose-50 text-rose-700 border-rose-300 text-[10px] px-1.5 py-0 font-semibold">Klaim Reimburse</Badge>;
                } else if (status === 'SETTLED') {
                    badge = <Badge variant="outline" className="bg-slate-100 text-slate-700 border-slate-300 text-[10px] px-1.5 py-0 font-medium">Selesai / Pas</Badge>;
                } else {
                    badge = <span className="text-[10px] text-slate-400">-</span>;
                }

                return (
                    <div className="min-w-[155px] text-right space-y-1">
                        <div className="font-mono font-bold text-xs whitespace-nowrap">
                            {rem > 0 ? (
                                <span className="text-emerald-600 dark:text-emerald-400">+{formatRupiah(rem)}</span>
                            ) : rem < 0 ? (
                                <span className="text-rose-600 dark:text-rose-400">-{formatRupiah(Math.abs(rem))}</span>
                            ) : (
                                <span className="text-slate-400">Rp 0</span>
                            )}
                        </div>
                        <div>{badge}</div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'status',
            header: ({ column }) => <DataTableColumnHeader column={column} title="Approval" className="justify-center" />,
            meta: { title: 'Approval' },
            size: 140,
            minSize: 130,
            cell: ({ row }) => (
                <div className="min-w-[125px] text-center whitespace-nowrap">
                    {getStatusBadge(row.original.status)}
                </div>
            ),
        },
        {
            id: 'actions',
            header: () => <div className="text-right pr-2 whitespace-nowrap">Aksi</div>,
            meta: { pinned: 'right' },
            size: 105,
            minSize: 100,
            cell: ({ row }) => {
                const order = row.original;
                return (
                    <div className="flex items-center justify-end gap-1 whitespace-nowrap">
                        <Link href={route('purchasing.orders.show', order.po_number)}>
                            <Button variant="ghost" size="icon" className="h-7 w-7 text-slate-500 hover:text-blue-600" title="Lihat Detail PO">
                                <Eye className="w-3.5 h-3.5" />
                            </Button>
                        </Link>

                        {['DRAFT', 'PENDING_PIC_CHECK', 'REJECTED'].includes(order.status) && (
                            <Link href={route('purchasing.orders.edit', order.po_number)}>
                                <Button variant="ghost" size="icon" className="h-7 w-7 text-slate-500 hover:text-amber-600" title="Edit Pengajuan">
                                    <Pencil className="w-3.5 h-3.5" />
                                </Button>
                            </Link>
                        )}

                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDelete(order.po_number)}
                            className="h-7 w-7 text-slate-500 hover:text-rose-600"
                            title="Hapus PO"
                        >
                            <Trash2 className="w-3.5 h-3.5" />
                        </Button>
                    </div>
                );
            },
        },
    ], []);


    return (
        <AppLayout>
            <Head title="Daftar Pembelian & Pengadaan (PO)" />

            <div className="space-y-6">
                {/* Header Page */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <ShoppingBag className="w-6 h-6 text-blue-600" />
                            Pembelian Operasional & Pengadaan (PO)
                        </h1>
                        <p className="text-sm text-slate-500">
                            Kelola alur pengadaan barang/jasa operasional dan aset inventaris dengan Double Sign-Off governance.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        <Link href={route('purchasing.orders.create')}>
                            <Button className="gap-2 bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                                <Plus className="w-4 h-4" />
                                Ajukan Pembelian Baru
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Kartu Metrik Ringkas (4 Kolom Utama) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs text-slate-500 font-medium">Total Pesanan</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                                    {metrics.total_orders || 0}
                                </p>
                                <p className="text-[11px] text-emerald-600 font-medium mt-1">
                                    {metrics.completed || 0} Selesai / Lunas
                                </p>
                            </div>
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <ShoppingBag className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Menunggu Persetujuan (Gabungan PIC & Finance) */}
                    <Card
                        className={`bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 transition-all cursor-pointer hover:border-blue-400 ${status === 'pending_approval' ? 'ring-2 ring-blue-500' : ''}`}
                        onClick={() => {
                            const newStatus = status === 'pending_approval' ? 'all' : 'pending_approval';
                            setStatus(newStatus);
                            handleFilterChange({ status: newStatus !== 'all' ? newStatus : undefined, page: 1 });
                        }}
                    >
                        <CardContent className="p-4 flex items-center justify-between">
                            <div className="space-y-1">
                                <p className="text-xs text-slate-500 font-medium">Menunggu Persetujuan</p>
                                <p className="text-xl font-bold text-blue-600 mt-0.5">
                                    {(metrics.pending_approval ?? ((metrics.pending_pic || 0) + (metrics.pending_finance || 0)))}
                                </p>
                                <div className="text-[11px] text-slate-500 dark:text-slate-400 space-x-1.5 mt-1">
                                    <span>PIC: <strong className="text-blue-600 font-semibold">{metrics.pending_pic || 0}</strong></span>
                                    <span>•</span>
                                    <span>Finance: <strong className="text-amber-600 font-semibold">{metrics.pending_finance || 0}</strong></span>
                                </div>
                            </div>
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 shrink-0">
                                <Clock className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* 1 Ruang Berisi Realisasi Operasional & Aset yang Nyata */}
                    <Card className="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div className="space-y-1 min-w-0 pr-2">
                                <p className="text-xs text-slate-500 font-medium truncate">Realisasi Operasional & Aset</p>
                                <p className="text-base sm:text-lg font-bold text-emerald-600 font-mono">
                                    {formatRupiah(metrics.total_realized || 0)}
                                </p>
                                <div className="text-[11px] text-slate-500 dark:text-slate-400 space-y-0.5 leading-tight">
                                    <div className="flex items-center justify-between gap-2">
                                        <span>Operasional:</span>
                                        <span className="font-semibold text-slate-700 dark:text-slate-200">{formatRupiah(metrics.realized_opex || 0)}</span>
                                    </div>
                                    <div className="flex items-center justify-between gap-2">
                                        <span>Aset Tetap:</span>
                                        <span className="font-semibold text-slate-700 dark:text-slate-200">{formatRupiah(metrics.realized_capex || 0)}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 shrink-0">
                                <Wallet className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tunggakan Tagihan & Jatuh Tempo */}
                    <Card className="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div className="space-y-1 min-w-0 pr-2">
                                <div className="flex items-center gap-1.5">
                                    <p className="text-xs text-slate-500 font-medium truncate">Tunggakan Jatuh Tempo</p>
                                    {(metrics.overdue_count || 0) > 0 && (
                                        <Badge variant="destructive" className="text-[9px] px-1 py-0 h-4">
                                            {metrics.overdue_count}
                                        </Badge>
                                    )}
                                </div>
                                <p className={`text-base sm:text-lg font-bold font-mono ${(metrics.overdue_amount || 0) > 0 ? 'text-rose-600' : 'text-slate-800 dark:text-slate-200'}`}>
                                    {formatRupiah(metrics.overdue_amount || 0)}
                                </p>
                                <div className="text-[11px] text-slate-400 leading-tight">
                                    <span>Belum Lunas: </span>
                                    <span className="font-semibold text-slate-700 dark:text-slate-300">{formatRupiah(metrics.total_unpaid || 0)}</span>
                                </div>
                            </div>
                            <div className={`p-2.5 rounded-xl shrink-0 ${(metrics.overdue_amount || 0) > 0 ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'}`}>
                                <AlertTriangle className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Banner Pengingat Penyelesaian Sisa Belanja Operasional (Settlement Reminder) */}
                {(metrics.total_unsettled_proposals || 0) > 0 && (
                    <div className="rounded-xl border border-amber-300 bg-amber-50/70 dark:bg-amber-950/30 dark:border-amber-800/80 p-4 shadow-xs">
                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <div className="flex items-start gap-3">
                                <div className="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300 shrink-0 mt-0.5">
                                    <AlertTriangle className="w-5 h-5" />
                                </div>
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <h3 className="font-bold text-amber-900 dark:text-amber-200 text-sm">
                                            Pengingat Penyelesaian Sisa Belanja & LPJ Operasional
                                        </h3>
                                        <Badge variant="outline" className="bg-amber-200/60 dark:bg-amber-900 text-amber-900 dark:text-amber-200 border-amber-400 font-bold text-[10px]">
                                            {metrics.total_unsettled_proposals} Pengajuan Belum Tuntas
                                        </Badge>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-amber-900/80 dark:text-amber-300/80">
                                        <span>
                                            • Nota Belum Diunggah: <strong>{metrics.pending_receipt_count || 0}</strong>
                                            {(metrics.overdue_receipt_count || 0) > 0 && (
                                                <span className="text-rose-600 font-bold ml-1">({metrics.overdue_receipt_count} Terlambat &gt; H+3)</span>
                                            )}
                                        </span>
                                        <span>
                                            • Sisa Kembalian Wajib Disetor: <strong>{metrics.pending_refund_count || 0} PO</strong> ({formatRupiah(metrics.total_refund_amount || 0)})
                                        </span>
                                        <span>
                                            • Klaim Reimburse Kekurangan: <strong>{metrics.pending_reimburse_count || 0} PO</strong> ({formatRupiah(metrics.total_reimburse_amount || 0)})
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 shrink-0 self-end md:self-center">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => {
                                        setSettlementStatus('DISBURSED_PENDING_RECEIPT');
                                        handleFilterChange({ settlement_status: 'DISBURSED_PENDING_RECEIPT', page: 1 });
                                    }}
                                    className="bg-white dark:bg-slate-900 text-amber-900 dark:text-amber-200 border-amber-300 text-xs h-8"
                                >
                                    Cek Nota Belum Masuk
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={() => {
                                        setSettlementStatus('REFUND_PENDING');
                                        handleFilterChange({ settlement_status: 'REFUND_PENDING', page: 1 });
                                    }}
                                    className="bg-amber-600 hover:bg-amber-700 text-white text-xs h-8 font-medium shadow-xs"
                                >
                                    Cek Sisa Kembalian
                                </Button>
                            </div>
                        </div>
                    </div>
                )}

                {/* TanStack Table v9 - Order Pembelian */}
                <DataTable
                    columns={columns}
                    data={orders.data || []}
                    serverPagination={orders}
                    onServerPageChange={(page) => handleFilterChange({ page })}
                    onServerPerPageChange={(per_page) => handleFilterChange({ per_page, page: 1 })}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        handleFilterChange({ search: val, page: 1 });
                    }}
                    searchValue={search}
                    searchPlaceholder="Cari No PO, barang, pemohon, vendor..."
                    exportFileName="order_pembelian_po"
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-2">
                            {/* Filter Status */}
                            <select
                                value={status}
                                onChange={(e) => {
                                    setStatus(e.target.value);
                                    handleFilterChange({ status: e.target.value !== 'all' ? e.target.value : undefined, page: 1 });
                                }}
                                className="h-8 text-xs rounded-lg border-slate-200 dark:border-slate-800 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-1 pl-2.5 pr-7"
                            >
                                <option value="all">Semua Approval</option>
                                <option value="pending_approval">Menunggu Persetujuan (Semua)</option>
                                <option value="PENDING_PIC_CHECK">Menunggu PIC</option>
                                <option value="APPROVED_BY_PIC">Menunggu Finance</option>
                                <option value="DRAFT">Draft</option>
                                <option value="PURCHASE_COMPLETED">Selesai</option>
                                <option value="REJECTED">Ditolak</option>
                            </select>

                            {/* Filter Status Settlement / Sisa Uang */}
                            <select
                                value={settlementStatus}
                                onChange={(e) => {
                                    setSettlementStatus(e.target.value);
                                    handleFilterChange({ settlement_status: e.target.value !== 'all' ? e.target.value : undefined, page: 1 });
                                }}
                                className="h-8 text-xs rounded-lg border-amber-300 dark:border-amber-800 bg-amber-50/50 dark:bg-slate-800 text-amber-900 dark:text-amber-200 font-medium py-1 pl-2.5 pr-7"
                            >
                                <option value="all">Semua Status Sisa</option>
                                <option value="DISBURSED_PENDING_RECEIPT">Nota Belum Diunggah</option>
                                <option value="REFUND_PENDING">Menunggu Setor Kembalian</option>
                                <option value="REIMBURSE_PENDING">Menunggu Cair Reimburse</option>
                                <option value="SETTLED">Selesai (Settled / Pas)</option>
                            </select>

                            {/* Filter Tipe Belanja */}
                            <select
                                value={orderType}
                                onChange={(e) => {
                                    setOrderType(e.target.value);
                                    handleFilterChange({ order_type: e.target.value !== 'all' ? e.target.value : undefined, page: 1 });
                                }}
                                className="h-8 text-xs rounded-lg border-slate-200 dark:border-slate-800 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-1 pl-2.5 pr-7"
                            >
                                <option value="all">Semua Tipe</option>
                                <option value="OPEX">Operasional</option>
                                <option value="CAPEX">Aset Tetap</option>
                            </select>

                            {/* Filter Departemen */}
                            <select
                                value={department}
                                onChange={(e) => {
                                    setDepartment(e.target.value);
                                    handleFilterChange({ department: e.target.value !== 'all' ? e.target.value : undefined, page: 1 });
                                }}
                                className="h-8 text-xs rounded-lg border-slate-200 dark:border-slate-800 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-1 pl-2.5 pr-7"
                            >
                                <option value="all">Semua Dept</option>
                                {departments.map((d, idx) => (
                                    <option key={idx} value={d}>
                                        {d}
                                    </option>
                                ))}
                            </select>

                            {(search || status !== 'all' || orderType !== 'all' || department !== 'all') && (
                                <button
                                    onClick={resetFilters}
                                    title="Reset Filter"
                                    className="h-8 px-2.5 text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-medium rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors flex items-center gap-1 border border-rose-200 dark:border-rose-900"
                                >
                                    <X className="w-3.5 h-3.5" />
                                    Reset
                                </button>
                            )}
                        </div>
                    }
                />
            </div>

            {/* Modal Live Search Benchmark */}
            <PurchaseHistoryModal
                isOpen={isHistoryModalOpen}
                onClose={() => setIsHistoryModalOpen(false)}
                onSelectBenchmark={(item) => {
                    router.visit(route('purchasing.orders.create'));
                }}
            />
        </AppLayout>
    );
}
