import React, { useState, useMemo } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    LayoutDashboard,
    ShoppingBag,
    Building2,
    SlidersHorizontal,
    History,
    Plus,
    AlertTriangle,
    Clock,
    DollarSign,
    CheckCircle2,
    Calendar,
    Phone,
    ArrowUpRight,
    ExternalLink,
    ShieldCheck,
    Wallet,
    Boxes,
    FileText,
    Eye,
    AlertOctagon,
    Info,
    Archive,
    Flame,
    ShieldAlert,
    RefreshCw,
    ShoppingCart,
} from 'lucide-react';
import PurchaseHistoryModal from '../Orders/Components/PurchaseHistoryModal';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';

export default function Index({
    metrics = {},
    urgentActions = {},
    upcomingMonitoring = {},
    alerts = {},
    upcomingPayments = [],
    auth,
}) {
    const [activeAlertTab, setActiveAlertTab] = useState('red');
    const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
    const [payModal, setPayModal] = useState({ isOpen: false, payment: null });

    const { data: payData, setData: setPayData, post: postPay, processing: processingPay, reset: resetPay } = useForm({
        paid_at: new Date().toISOString().split('T')[0],
        payment_method: 'Transfer Bank',
        receipt_file: null,
        notes: '',
    });

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
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

    const getCleanPhone = (phone) => {
        if (!phone) return null;
        let clean = phone.replace(/[^0-9]/g, '');
        if (clean.startsWith('0')) clean = '62' + clean.slice(1);
        return clean;
    };

    const getPaymentBadge = (status) => {
        switch (status) {
            case 'DUE_TODAY':
                return <Badge className="bg-red-500 text-white animate-pulse">Hari H (Jatuh Tempo)</Badge>;
            case 'OVERDUE':
                return <Badge className="bg-rose-600 text-white">Lewat Tempo (Overdue)</Badge>;
            case 'PAID':
                return <Badge className="bg-emerald-600 text-white">Lunas</Badge>;
            case 'PENDING':
            default:
                return <Badge variant="outline" className="border-blue-300 text-blue-600 dark:border-blue-800 dark:text-blue-400">Menunggu</Badge>;
        }
    };

    const openPayModal = (item) => {
        setPayModal({ isOpen: true, payment: item });
        setPayData({
            paid_at: new Date().toISOString().split('T')[0],
            payment_method: 'Transfer Bank',
            receipt_file: null,
            notes: '',
        });
    };

    const handlePaySubmit = (e) => {
        e.preventDefault();
        if (!payModal.payment) return;
        postPay(route('purchasing.payments.pay', payModal.payment.uuid), {
            onSuccess: () => {
                setPayModal({ isOpen: false, payment: null });
                resetPay();
            },
        });
    };

    const canDisbursePayment = auth?.user?.is_superadmin ||
        auth?.user?.permissions?.includes('purchasing.approve-finance') ||
        auth?.user?.roles?.includes('admin_keuangan');

    const redCount = alerts.red?.length || 0;
    const amberCount = alerts.amber?.length || 0;
    const blueCount = alerts.blue?.length || 0;
    const greenCount = alerts.green?.length || 0;
    const greyCount = alerts.grey?.length || 0;

    const paymentColumns = useMemo(() => [
        {
            id: 'po',
            accessorFn: (row) => row.po_number || '',
            meta: { title: 'No. PO & Termin' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="No. PO & Termin" />,
            cell: ({ row }) => (
                <div>
                    <Link
                        href={route('purchasing.orders.show', row.original.po_number)}
                        className="font-mono font-bold text-blue-600 hover:underline block"
                    >
                        {row.original.po_number}
                    </Link>
                    <span className="text-[11px] text-slate-500 font-medium">{row.original.term_name}</span>
                </div>
            ),
        },
        {
            id: 'vendor',
            accessorFn: (row) => row.vendor_name || '',
            meta: { title: 'Vendor & Kontak WA' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Vendor & Kontak WA" />,
            cell: ({ row }) => {
                const p = row.original;
                const cleanPhone = getCleanPhone(p.vendor_phone);
                return (
                    <div>
                        <p className="font-semibold text-slate-800 dark:text-slate-200">{p.vendor_name}</p>
                        {cleanPhone && (
                            <a
                                href={`https://wa.me/${cleanPhone}`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-1 text-[11px] text-emerald-600 hover:underline mt-0.5"
                            >
                                <Phone className="w-3 h-3" />
                                {p.vendor_phone}
                            </a>
                        )}
                    </div>
                );
            },
        },
        {
            id: 'due_date',
            accessorFn: (row) => row.due_date || '',
            meta: { title: 'Jatuh Tempo' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Jatuh Tempo" />,
            cell: ({ row }) => (
                <span className="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                    <Calendar className="w-3.5 h-3.5" />
                    {formatDate(row.original.due_date)}
                </span>
            ),
        },
        {
            id: 'amount',
            accessorFn: (row) => row.amount ?? 0,
            meta: { title: 'Nominal Tagihan', cellClassName: 'text-right font-mono font-bold' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nominal Tagihan" className="justify-end" />,
            cell: ({ row }) => (
                <span className="font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">{formatRupiah(row.original.amount)}</span>
            ),
        },
        {
            accessorKey: 'status',
            meta: { title: 'Status', cellClassName: 'text-center' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Status" className="justify-center" />,
            cell: ({ row }) => getPaymentBadge(row.original.status),
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Aksi</div>,
            cell: ({ row }) => (
                <div className="flex items-center justify-end gap-1.5">
                    <Link href={route('purchasing.orders.show', row.original.po_number)}>
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-7 text-xs gap-1 border-slate-200 text-slate-700 hover:bg-slate-100 dark:border-slate-800 dark:text-slate-300"
                        >
                            <Eye className="w-3 h-3" />
                            Detail
                        </Button>
                    </Link>
                    {canDisbursePayment && (
                        <Button
                            size="sm"
                            onClick={() => openPayModal(row.original)}
                            className="h-7 text-xs gap-1 bg-emerald-600 hover:bg-emerald-700 text-white"
                        >
                            <ShieldCheck className="w-3 h-3" />
                            Bayar
                        </Button>
                    )}
                </div>
            ),
        },
    ], []);

    return (
        <AppLayout>
            <Head title="Dashboard Purchasing & Alert Center" />

            <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
                {/* Header Page & Quick Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <LayoutDashboard className="w-5 h-5 text-blue-600" />
                            Dashboard Purchasing
                        </h1>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Ringkasan pengadaan operasional, termin pembayaran, dan pemantauan stok.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        <Link href={route('purchasing.vendors.index')}>
                            <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                                <Building2 className="w-3.5 h-3.5" />
                                Direktori Supplier
                            </Button>
                        </Link>

                        <Link href={route('purchasing.orders.create')}>
                            <Button size="sm" className="gap-1.5 bg-blue-600 hover:bg-blue-700 text-white shadow-sm text-xs">
                                <Plus className="w-3.5 h-3.5" />
                                Ajukan PO Baru
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* KPI Metrics Ringkas (4 Kolom Utama) */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div className="space-y-1">
                                <p className="text-xs text-slate-500 font-medium">Realisasi Belanja Bulan Ini</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-slate-100 font-mono">
                                    {formatRupiah((metrics.monthly_opex || 0) + (metrics.monthly_capex || 0))}
                                </p>
                                <div className="text-[10px] text-slate-400 space-x-1.5">
                                    <span>Operasional: <strong className="text-slate-600 dark:text-slate-300 font-semibold">{formatRupiah(metrics.monthly_opex || 0)}</strong></span>
                                    <span>•</span>
                                    <span>Aset: <strong className="text-slate-600 dark:text-slate-300 font-semibold">{formatRupiah(metrics.monthly_capex || 0)}</strong></span>
                                </div>
                            </div>
                            <div className="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <DollarSign className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div className="space-y-1">
                                <p className="text-xs text-slate-500 font-medium">Kewajiban Tagihan TOP</p>
                                <p className="text-xl font-bold text-amber-600 font-mono">
                                    {formatRupiah(metrics.unpaid_top_amount || 0)}
                                </p>
                                <p className="text-[10px] text-emerald-600 font-medium">
                                    Lunas Bulan Ini: {formatRupiah(metrics.paid_this_month || 0)}
                                </p>
                            </div>
                            <div className="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                                <Wallet className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div className="space-y-1">
                                <p className="text-xs text-slate-500 font-medium">Jatuh Tempo & Overdue</p>
                                <div className="flex items-center gap-2">
                                    <span className="text-xl font-bold text-rose-600 font-mono">
                                        {(metrics.due_today_count || 0) + (metrics.overdue_count || 0)}
                                    </span>
                                    {(metrics.due_today_count > 0 || metrics.overdue_count > 0) && (
                                        <Badge variant="destructive" className="text-[10px] px-1.5 py-0">
                                            Perhatian
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-[10px] text-slate-400">
                                    Hari ini: {metrics.due_today_count || 0} • Overdue: {metrics.overdue_count || 0}
                                </p>
                            </div>
                            <div className="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600">
                                <AlertTriangle className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div className="space-y-1">
                                <p className="text-xs text-slate-500 font-medium">Pending Approvals</p>
                                <p className="text-xl font-bold text-blue-600 font-mono">
                                    {(metrics.pending_pic_count || 0) + (metrics.pending_finance_count || 0)}
                                </p>
                                <p className="text-[10px] text-slate-400">
                                    PIC: {metrics.pending_pic_count || 0} • Finance: {metrics.pending_finance_count || 0}
                                </p>
                            </div>
                            <div className="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <Clock className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* 1. URGENT & ACTION NEEDED (Butuh Tindakan Immediate) */}
                <Card className="border-rose-200 dark:border-rose-900/50 shadow-sm overflow-hidden bg-rose-50/20 dark:bg-rose-950/10">
                    <CardHeader className="p-4 sm:p-5 border-b border-rose-100 dark:border-rose-900/40 bg-white/70 dark:bg-slate-900/70">
                        <div className="flex items-center gap-2">
                            <div className="p-2 rounded-lg bg-rose-100 dark:bg-rose-900/50 text-rose-600">
                                <Flame className="w-4 h-4" />
                            </div>
                            <div>
                                <CardTitle className="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                    1. Urgent & Action Needed (Butuh Tindakan Immediate)
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500 mt-0.5">
                                    Prioritas tinggi: Tagihan jatuh tempo hari ini, pembayaran terlambat, dan pengajuan menunggu persetujuan.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="p-4 sm:p-5">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {/* A. Pending Pembayaran (TOP Hari Ini) */}
                            <div className="p-4 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-white dark:bg-slate-900 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                        <div className="flex items-center gap-1.5">
                                            <Wallet className="w-4 h-4 text-rose-600" />
                                            <h4 className="text-xs font-bold text-slate-900 dark:text-slate-100">
                                                A. Tagihan Jatuh Tempo Hari Ini
                                            </h4>
                                        </div>
                                        <Badge variant="destructive" className="text-[10px] px-1.5 py-0 h-4 font-bold">
                                            {(urgentActions.due_today || []).length}
                                        </Badge>
                                    </div>

                                    <div className="mt-3 space-y-2.5">
                                        {(urgentActions.due_today || []).length === 0 ? (
                                            <p className="text-xs text-slate-400 py-4 text-center italic">
                                                Tidak ada tagihan jatuh tempo hari ini.
                                            </p>
                                        ) : (
                                            (urgentActions.due_today || []).map((item, idx) => (
                                                <div key={idx} className="p-2.5 rounded-lg bg-rose-50/50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30 text-xs space-y-1.5">
                                                    <div className="flex items-center justify-between gap-1">
                                                        <span className="font-semibold text-slate-900 dark:text-slate-100 truncate">
                                                            {item.item_name}
                                                        </span>
                                                        <span className="font-mono font-bold text-rose-600 shrink-0">
                                                            {formatRupiah(item.amount)}
                                                        </span>
                                                    </div>
                                                    <p className="text-[11px] text-slate-500">
                                                        {item.vendor_name} • {item.term_name}
                                                    </p>
                                                    <div className="flex items-center justify-between pt-1">
                                                        <span className="text-[10px] text-rose-600 font-medium">Jatuh tempo hari ini</span>
                                                        <Link href={item.target_url}>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="h-6 text-[11px] px-2.5 border-rose-300 text-rose-700 hover:bg-rose-50"
                                                            >
                                                                Lihat Detail
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* B. Tagihan Terlambat (Overdue Payments) */}
                            <div className="p-4 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-white dark:bg-slate-900 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                        <div className="flex items-center gap-1.5">
                                            <AlertTriangle className="w-4 h-4 text-rose-600" />
                                            <h4 className="text-xs font-bold text-slate-900 dark:text-slate-100">
                                                B. Tagihan Terlambat (Overdue)
                                            </h4>
                                        </div>
                                        <Badge variant="destructive" className="text-[10px] px-1.5 py-0 h-4 font-bold">
                                            {(urgentActions.overdue || []).length}
                                        </Badge>
                                    </div>

                                    <div className="mt-3 space-y-2.5">
                                        {(urgentActions.overdue || []).length === 0 ? (
                                            <p className="text-xs text-slate-400 py-4 text-center italic">
                                                Tidak ada tagihan yang melewati batas tempo.
                                            </p>
                                        ) : (
                                            (urgentActions.overdue || []).map((item, idx) => (
                                                <div key={idx} className="p-2.5 rounded-lg bg-rose-50/50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30 text-xs space-y-1.5">
                                                    <div className="flex items-center justify-between gap-1">
                                                        <span className="font-semibold text-slate-900 dark:text-slate-100 truncate">
                                                            {item.invoice_number || item.po_number}
                                                        </span>
                                                        <span className="font-mono font-bold text-rose-600 shrink-0">
                                                            {formatRupiah(item.amount)}
                                                        </span>
                                                    </div>
                                                    <p className="text-[11px] text-slate-500">
                                                        {item.vendor_name} ({item.item_name})
                                                    </p>
                                                    <div className="flex items-center justify-between pt-1">
                                                        <Badge variant="outline" className="text-[10px] px-1.5 py-0 border-rose-300 text-rose-700 bg-rose-100/60 font-semibold">
                                                            {item.days_late} Hari Terlambat • Perlu Segera Dilunasi
                                                        </Badge>
                                                        <Link href={item.target_url}>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="h-6 text-[11px] px-2.5 border-rose-300 text-rose-700 hover:bg-rose-50"
                                                            >
                                                                Lihat Detail
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* C. Pending Approvals (Menunggu Persetujuan) */}
                            <div className="p-4 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-white dark:bg-slate-900 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                        <div className="flex items-center gap-1.5">
                                            <Clock className="w-4 h-4 text-amber-600" />
                                            <h4 className="text-xs font-bold text-slate-900 dark:text-slate-100">
                                                C. Pending Approvals (Menunggu)
                                            </h4>
                                        </div>
                                        <Badge variant="outline" className="text-[10px] px-1.5 py-0 h-4 bg-amber-50 text-amber-700 border-amber-300 font-bold">
                                            {(urgentActions.pending_approvals || []).length}
                                        </Badge>
                                    </div>

                                    <div className="mt-3 space-y-2.5">
                                        {(urgentActions.pending_approvals || []).length === 0 ? (
                                            <p className="text-xs text-slate-400 py-4 text-center italic">
                                                Tidak ada pengajuan yang menunggu persetujuan.
                                            </p>
                                        ) : (
                                            (urgentActions.pending_approvals || []).map((item, idx) => (
                                                <div key={idx} className="p-2.5 rounded-lg bg-amber-50/40 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30 text-xs space-y-1.5">
                                                    <div className="flex items-center justify-between gap-1">
                                                        <span className="font-mono font-bold text-blue-600">
                                                            {item.po_number}
                                                        </span>
                                                        <span className="font-mono font-bold text-slate-800 dark:text-slate-200 shrink-0">
                                                            {formatRupiah(item.grand_total)}
                                                        </span>
                                                    </div>
                                                    <p className="text-[11px] text-slate-700 dark:text-slate-300 font-medium truncate">
                                                        {item.item_name}
                                                    </p>
                                                    <p className="text-[10px] text-slate-400">
                                                        {item.requester_name} • {item.department}
                                                    </p>
                                                    <div className="flex items-center justify-between pt-1">
                                                        <Badge variant="outline" className="text-[10px] px-1.5 py-0 border-amber-300 text-amber-700 bg-amber-50">
                                                            {item.stage_label}
                                                        </Badge>
                                                        <Link href={item.target_url}>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                className="h-6 text-[11px] px-2.5 border-blue-200 text-blue-700 hover:bg-blue-50"
                                                            >
                                                                Review & Setujui
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* 2. UPCOMING SCHEDULES & MONITORING (Jadwal & Peringatan Mendatang) */}
                <Card className="border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden bg-slate-50/30 dark:bg-slate-900/20">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70">
                        <div className="flex items-center gap-2">
                            <div className="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600">
                                <Calendar className="w-4 h-4" />
                            </div>
                            <div>
                                <CardTitle className="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                    2. Upcoming Schedules & Monitoring (Jadwal & Peringatan Mendatang)
                                </CardTitle>
                                <CardDescription className="text-xs text-slate-500 mt-0.5">
                                    Pantauan preventif: Masa garansi aset, jatuh tempo langganan software/kontrak, dan batas kritis stok material.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="p-4 sm:p-5">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {/* A. Masa Garansi Aset Berakhir */}
                            <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                        <div className="flex items-center gap-1.5">
                                            <ShieldAlert className="w-4 h-4 text-amber-500" />
                                            <h4 className="text-xs font-bold text-slate-900 dark:text-slate-100">
                                                A. Masa Garansi Aset Berakhir
                                            </h4>
                                        </div>
                                        <Badge variant="secondary" className="text-[10px] px-1.5 py-0 h-4 font-bold">
                                            {(upcomingMonitoring.warranty_expiring || []).length}
                                        </Badge>
                                    </div>

                                    <div className="mt-3 space-y-2.5">
                                        {(upcomingMonitoring.warranty_expiring || []).length === 0 ? (
                                            <p className="text-xs text-slate-400 py-4 text-center italic">
                                                Semua garansi aset masih dalam masa aman.
                                            </p>
                                        ) : (
                                            (upcomingMonitoring.warranty_expiring || []).map((item, idx) => (
                                                <div key={idx} className="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 text-xs space-y-1.5">
                                                    <div className="flex items-center justify-between gap-1">
                                                        <Link
                                                            href={item.target_url}
                                                            className="font-mono font-bold text-blue-600 hover:underline"
                                                        >
                                                            {item.asset_code}
                                                        </Link>
                                                        <Badge variant="outline" className="text-[10px] px-1.5 py-0 border-amber-300 text-amber-700 bg-amber-50">
                                                            {item.days_left} Hari Lagi
                                                        </Badge>
                                                    </div>
                                                    <p className="font-semibold text-slate-900 dark:text-slate-100 truncate">
                                                        {item.asset_name}
                                                    </p>
                                                    <p className="text-[11px] text-slate-400">
                                                        {item.department} ({item.location_name}) • Habis: {item.expiry_date}
                                                    </p>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* B. Perpanjangan Langganan / Kontrak Berakhir */}
                            <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                        <div className="flex items-center gap-1.5">
                                            <RefreshCw className="w-4 h-4 text-purple-500" />
                                            <h4 className="text-xs font-bold text-slate-900 dark:text-slate-100">
                                                B. Langganan / Kontrak Berakhir
                                            </h4>
                                        </div>
                                        <Badge variant="secondary" className="text-[10px] px-1.5 py-0 h-4 font-bold">
                                            {(upcomingMonitoring.subscriptions || []).length}
                                        </Badge>
                                    </div>

                                    <div className="mt-3 space-y-2.5">
                                        {(upcomingMonitoring.subscriptions || []).length === 0 ? (
                                            <p className="text-xs text-slate-400 py-4 text-center italic">
                                                Tidak ada langganan/kontrak yang segera berakhir.
                                            </p>
                                        ) : (
                                            (upcomingMonitoring.subscriptions || []).map((item, idx) => (
                                                <div key={idx} className="p-2.5 rounded-lg border border-purple-100 dark:border-purple-900/30 bg-purple-50/30 dark:bg-purple-950/20 text-xs space-y-1.5">
                                                    <div className="flex items-center justify-between gap-1">
                                                        <span className="font-semibold text-slate-900 dark:text-slate-100 truncate">
                                                            {item.item_name}
                                                        </span>
                                                        <span className="font-mono font-bold text-slate-800 dark:text-slate-200 shrink-0">
                                                            {formatRupiah(item.grand_total)}
                                                        </span>
                                                    </div>
                                                    <p className="text-[11px] text-slate-500">
                                                        {item.vendor_name} ({item.department}) • Siklus {item.expiry_date}
                                                    </p>
                                                    <div className="flex items-center justify-between pt-1">
                                                        <Badge variant="outline" className="text-[10px] px-1.5 py-0 border-purple-200 text-purple-700 bg-purple-50">
                                                            {item.decision_note}
                                                        </Badge>
                                                        <Link href={item.target_url}>
                                                            <Button variant="ghost" size="sm" className="h-6 text-[11px] text-blue-600">
                                                                Detail
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* C. Stok Bahan Baku Kritis (Minimum Stock Alert) */}
                            <div className="p-4 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-white dark:bg-slate-900 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                                        <div className="flex items-center gap-1.5">
                                            <Boxes className="w-4 h-4 text-rose-600" />
                                            <h4 className="text-xs font-bold text-slate-900 dark:text-slate-100">
                                                C. Stok Bahan Baku Kritis
                                            </h4>
                                        </div>
                                        <Badge variant="destructive" className="text-[10px] px-1.5 py-0 h-4 font-bold">
                                            {(upcomingMonitoring.critical_stocks || []).length}
                                        </Badge>
                                    </div>

                                    <div className="mt-3 space-y-2.5">
                                        {(upcomingMonitoring.critical_stocks || []).length === 0 ? (
                                            <p className="text-xs text-slate-400 py-4 text-center italic">
                                                Semua stok material dalam batas aman.
                                            </p>
                                        ) : (
                                            (upcomingMonitoring.critical_stocks || []).map((item, idx) => (
                                                <div key={idx} className="p-2.5 rounded-lg bg-rose-50/40 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30 text-xs space-y-1.5">
                                                    <div className="flex items-center justify-between gap-1">
                                                        <span className="font-semibold text-slate-900 dark:text-slate-100 truncate">
                                                            {item.name}
                                                        </span>
                                                        <Badge variant="destructive" className="text-[10px] px-1.5 py-0">
                                                            Kritis
                                                        </Badge>
                                                    </div>
                                                    <p className="text-[11px] text-slate-500">
                                                        <span className="font-bold text-rose-600">Sisa {item.current_stock} {item.unit}</span> (Batas Min: {item.minimum_threshold} {item.unit})
                                                    </p>
                                                    <div className="flex items-center justify-end pt-1">
                                                        <Link href={item.target_url}>
                                                            <Button
                                                                size="sm"
                                                                className="h-6 text-[11px] px-2.5 bg-rose-600 hover:bg-rose-700 text-white gap-1"
                                                            >
                                                                <ShoppingCart className="w-3 h-3" />
                                                                Segera Beli
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* TABEL TAGIHAN TOP JATUH TEMPO */}
                <Card className="border-slate-200 dark:border-slate-800 overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Wallet className="w-4 h-4 text-purple-600" />
                                Tagihan Termin (TOP) Jatuh Tempo
                            </CardTitle>
                            <CardDescription className="text-xs text-slate-500 mt-0.5">
                                Daftar pembayaran termin terdekat yang perlu diproses.
                            </CardDescription>
                        </div>

                        <Link href={route('purchasing.orders.index')}>
                            <Button variant="ghost" size="sm" className="text-xs text-blue-600 gap-1">
                                Semua Orders
                                <ArrowUpRight className="w-3.5 h-3.5" />
                            </Button>
                        </Link>
                    </CardHeader>

                    <DataTable
                        columns={paymentColumns}
                        data={upcomingPayments}
                        searchPlaceholder="Cari No. PO, vendor, termin..."
                        exportFileName={`Tagihan_Jatuh_Tempo_${new Date().toISOString().slice(0, 10)}`}
                        emptyMessage="Tidak ada tagihan jatuh tempo aktif."
                    />

                </Card>
            </div>

            {/* Modal Pencairan Pembayaran Langsung */}
            <Dialog open={payModal.isOpen} onOpenChange={(open) => !open && setPayModal({ isOpen: false, payment: null })}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold flex items-center gap-2">
                            <ShieldCheck className="w-5 h-5 text-emerald-600" />
                            Pencairan Dana {payModal.payment?.term_name}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Verifikasi pembayaran sebesar <strong className="font-mono text-emerald-600">{formatRupiah(payModal.payment?.amount)}</strong> untuk PO {payModal.payment?.po_number}.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handlePaySubmit} className="space-y-4 my-2">
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label htmlFor="paid_at">Tanggal Pencairan / Transfer *</Label>
                                <Input
                                    id="paid_at"
                                    type="date"
                                    value={payData.paid_at}
                                    onChange={(e) => setPayData('paid_at', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                            </div>

                            <div>
                                <Label htmlFor="payment_method">Metode Pembayaran *</Label>
                                <Input
                                    id="payment_method"
                                    value={payData.payment_method}
                                    onChange={(e) => setPayData('payment_method', e.target.value)}
                                    placeholder="Contoh: Transfer BCA, Kas Tunai"
                                    className="mt-1"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="receipt_file">Upload Bukti Transfer Bank (PDF / Gambar, Max 5MB)</Label>
                            <Input
                                id="receipt_file"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                onChange={(e) => {
                                    const file = e.target.files?.[0];
                                    if (file) setPayData('receipt_file', file);
                                }}
                                className="mt-1"
                            />
                        </div>

                        <div>
                            <Label htmlFor="pay_notes">Catatan Transaksi Keuangan</Label>
                            <Textarea
                                id="pay_notes"
                                value={payData.notes}
                                onChange={(e) => setPayData('notes', e.target.value)}
                                placeholder="Nomor referensi mutasi bank, nama rekening pengirim, dll..."
                                rows={2}
                                className="mt-1"
                            />
                        </div>

                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setPayModal({ isOpen: false, payment: null })}
                                disabled={processingPay}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={processingPay}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white"
                            >
                                {processingPay ? 'Memproses...' : 'Konfirmasi Pembayaran Lunas'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Live Search Benchmark */}
            <PurchaseHistoryModal
                isOpen={isHistoryModalOpen}
                onClose={() => setIsHistoryModalOpen(false)}
            />
        </AppLayout>
    );
}
