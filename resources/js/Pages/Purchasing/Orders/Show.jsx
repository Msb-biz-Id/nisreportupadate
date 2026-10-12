import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import {
    Card,
    CardContent,
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
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import {
    ArrowLeft,
    CheckCircle2,
    XCircle,
    Building2,
    Calendar,
    Clock,
    Download,
    ExternalLink,
    FileText,
    Pencil,
    Printer,
    ShieldCheck,
    ShoppingBag,
    Trash2,
    UserCheck,
    Wallet,
    Box,
} from 'lucide-react';
import PaymentTermsDrawer from './Components/PaymentTermsDrawer';

export default function Show({ order, auth }) {
    const [isTermsDrawerOpen, setIsTermsDrawerOpen] = useState(false);
    const [actionModal, setActionModal] = useState({
        isOpen: false,
        action: '',
        title: '',
        description: '',
    });

    const { data, setData, post, processing, reset } = useForm({
        action: '',
        notes: '',
        rejection_reason: '',
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
            month: 'long',
            year: 'numeric',
        });
    };

    const formatDateTime = (dateStr) => {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getStatusBadge = (st) => {
        switch (st) {
            case 'DRAFT':
                return <Badge variant="outline" className="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Draft Pengajuan</Badge>;
            case 'PENDING_PIC_CHECK':
                return <Badge className="bg-blue-500/10 text-blue-600 border border-blue-200 dark:border-blue-800">Menunggu Verifikasi PIC</Badge>;
            case 'APPROVED_BY_PIC':
                return <Badge className="bg-amber-500/10 text-amber-600 border border-amber-200 dark:border-amber-800">Menunggu Approval Finance</Badge>;
            case 'PURCHASE_COMPLETED':
            case 'PAID_COMPLETED':
                return <Badge className="bg-emerald-500/10 text-emerald-600 border border-emerald-200 dark:border-emerald-800">Disetujui & Selesai</Badge>;
            case 'REJECTED':
                return <Badge className="bg-rose-500/10 text-rose-600 border border-rose-200 dark:border-rose-800">Pengadaan Ditolak</Badge>;
            default:
                return <Badge variant="secondary">{st}</Badge>;
        }
    };

    const openActionModal = (actionType) => {
        setData({
            action: actionType,
            notes: '',
            rejection_reason: '',
        });

        if (actionType === 'approve_pic') {
            setActionModal({
                isOpen: true,
                action: 'approve_pic',
                title: 'Verifikasi Teknis & Kebutuhan oleh PIC Purchasing',
                description: 'Konfirmasi bahwa spesifikasi teknis barang, kebutuhan unit kerja, dan kesesuaian harga telah divalidasi.',
            });
        } else if (actionType === 'approve_finance') {
            setActionModal({
                isOpen: true,
                action: 'approve_finance',
                title: 'Otorisasi Anggaran oleh Keuangan (Double Sign-Off)',
                description: 'Konfirmasi ketersediaan anggaran dan persetujuan pencairan dana untuk order pembelian ini.',
            });
        } else if (actionType === 'reject') {
            setActionModal({
                isOpen: true,
                action: 'reject',
                title: 'Tolak Pengajuan Pembelian',
                description: 'Berikan alasan yang jelas mengapa order pembelian ini ditolak.',
            });
        }
    };

    const handleActionSubmit = (e) => {
        e.preventDefault();
        post(route('purchasing.orders.approve', order.po_number), {
            onSuccess: () => {
                setActionModal({ isOpen: false, action: '', title: '', description: '' });
                reset();
            },
        });
    };

    const canApprovePic = (order.status === 'PENDING_PIC_CHECK' || order.status === 'DRAFT') &&
        (auth?.user?.is_superadmin || auth?.user?.permissions?.includes('purchasing.approve-pic') || auth?.user?.roles?.includes('admin_purchasing'));

    const canApproveFinance = order.status === 'APPROVED_BY_PIC' &&
        (auth?.user?.is_superadmin || auth?.user?.permissions?.includes('purchasing.approve-finance') || auth?.user?.roles?.includes('admin_keuangan'));

    const canEdit = inArray(['DRAFT', 'PENDING_PIC_CHECK', 'REJECTED'], order.status) &&
        (auth?.user?.is_superadmin || auth?.user?.permissions?.includes('purchasing.manage-orders') || auth?.user?.roles?.includes('admin_purchasing'));

    function inArray(arr, val) {
        return arr.indexOf(val) !== -1;
    }

    const isSplitPayment = order.payment_mode === 'SPLIT';
    const isFullyPaid = inArray(['PAID_COMPLETED', 'PURCHASE_COMPLETED'], order.status) ||
        (order.payments && order.payments.length > 0 && order.payments.every((p) => p.status === 'PAID'));
    const isTermsLocked = isFullyPaid ||
        inArray(['APPROVED_BY_PIC', 'PAID_COMPLETED', 'PURCHASE_COMPLETED'], order.status) ||
        (order.payments && order.payments.some((p) => p.status === 'PAID')) ||
        (!isSplitPayment);

    return (
        <AppLayout>
            <Head title={`Purchase Order ${order.po_number}`} />

            <div className="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto space-y-6">
                {/* Top Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link href={route('purchasing.orders.index')}>
                            <Button variant="outline" size="icon" className="h-9 w-9">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-mono">
                                    {order.po_number}
                                </h1>
                                {getStatusBadge(order.status)}
                                <Badge
                                    variant="outline"
                                    className={`font-semibold text-xs ${
                                        order.order_type === 'CAPEX'
                                            ? 'border-purple-300 text-purple-700 bg-purple-50/50 dark:border-purple-800 dark:text-purple-300'
                                            : 'border-slate-300 text-slate-700 bg-slate-50 dark:border-slate-700 dark:text-slate-300'
                                    }`}
                                >
                                    {order.order_type === 'CAPEX' ? 'Aset Tetap' : 'Operasional'}
                                </Badge>
                            </div>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Dibuat pada {formatDate(order.transaction_date)} • ID Unik: {order.uuid}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        {canEdit && (
                            <Link href={route('purchasing.orders.edit', order.po_number)}>
                                <Button variant="outline" size="sm" className="gap-1.5">
                                    <Pencil className="w-3.5 h-3.5" />
                                    Edit PO
                                </Button>
                            </Link>
                        )}

                        {isSplitPayment && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setIsTermsDrawerOpen(true)}
                                className={`gap-1.5 ${
                                    isFullyPaid
                                        ? 'border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-400'
                                        : 'border-purple-200 text-purple-600 hover:bg-purple-50 dark:border-purple-900 dark:text-purple-400'
                                }`}
                            >
                                <Wallet className="w-3.5 h-3.5" />
                                {isFullyPaid ? 'Riwayat Termin (Lunas)' : 'Termin (TOP)'}
                                {order.payments && order.payments.length > 0 && (
                                    <Badge
                                        variant={isFullyPaid ? 'default' : 'secondary'}
                                        className={`ml-1 text-[10px] px-1.5 py-0 ${isFullyPaid ? 'bg-emerald-600 text-white' : ''}`}
                                    >
                                        {isFullyPaid ? 'Lunas' : order.payments.length}
                                    </Badge>
                                )}
                            </Button>
                        )}

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.print()}
                            className="gap-1.5"
                        >
                            <Printer className="w-3.5 h-3.5" />
                            Cetak
                        </Button>

                        {/* Tombol Approval Segregation of Duties */}
                        {canApprovePic && (
                            <Button
                                size="sm"
                                onClick={() => openActionModal('approve_pic')}
                                className="gap-1.5 bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                <CheckCircle2 className="w-3.5 h-3.5" />
                                Verifikasi PIC
                            </Button>
                        )}

                        {canApproveFinance && (
                            <Button
                                size="sm"
                                onClick={() => openActionModal('approve_finance')}
                                className="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white"
                            >
                                <ShieldCheck className="w-3.5 h-3.5" />
                                Otorisasi Finance (Sign & Paid)
                            </Button>
                        )}

                        {(canApprovePic || canApproveFinance) && (
                            <Button
                                size="sm"
                                variant="destructive"
                                onClick={() => openActionModal('reject')}
                                className="gap-1.5"
                            >
                                <XCircle className="w-3.5 h-3.5" />
                                Tolak
                            </Button>
                        )}
                    </div>
                </div>

                {/* Timeline Status Double Sign-Off */}
                <Card>
                    <CardHeader className="pb-3 border-b border-slate-100 dark:border-slate-800">
                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                            <Clock className="w-4 h-4 text-blue-600" />
                            Alur Persetujuan & Double Sign-Off
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            {/* Step 1: Pengajuan */}
                            <div className="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                                <div className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <div className="w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 flex items-center justify-center text-[10px]">
                                        1
                                    </div>
                                    <span>Pengajuan Pemohon</span>
                                </div>
                                <div className="mt-2 text-xs space-y-0.5 text-slate-500">
                                    <p className="font-medium text-slate-800 dark:text-slate-200">{order.requester_name}</p>
                                    <p>{order.department} ({order.position})</p>
                                    <p className="text-[10px] text-slate-400">{formatDate(order.transaction_date)}</p>
                                </div>
                            </div>

                            {/* Step 2: PIC Purchasing Check */}
                            <div className={`p-3.5 rounded-xl border ${
                                order.pic_approved_at
                                    ? 'border-blue-200 dark:border-blue-900 bg-blue-50/30 dark:bg-blue-950/20'
                                    : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 opacity-70'
                            }`}>
                                <div className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <div className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] ${
                                        order.pic_approved_at
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                                    }`}>
                                        2
                                    </div>
                                    <span>Verifikasi PIC Purchasing</span>
                                </div>
                                <div className="mt-2 text-xs space-y-0.5 text-slate-500">
                                    {order.pic_approved_at ? (
                                        <>
                                            <p className="font-medium text-blue-700 dark:text-blue-300">
                                                Disetujui: {order.pic_approver?.name || 'PIC Purchasing'}
                                            </p>
                                            <p className="text-[10px] text-slate-400">{formatDateTime(order.pic_approved_at)}</p>
                                        </>
                                    ) : (
                                        <p className="text-slate-400 italic">Menunggu verifikasi fisik/teknis</p>
                                    )}
                                </div>
                            </div>

                            {/* Step 3: Finance Clearance */}
                            <div className={`p-3.5 rounded-xl border ${
                                order.finance_approved_at
                                    ? 'border-emerald-200 dark:border-emerald-900 bg-emerald-50/30 dark:bg-emerald-950/20'
                                    : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 opacity-70'
                            }`}>
                                <div className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <div className={`w-5 h-5 rounded-full flex items-center justify-center text-[10px] ${
                                        order.finance_approved_at
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                                    }`}>
                                        3
                                    </div>
                                    <span>Otorisasi Keuangan (Finance)</span>
                                </div>
                                <div className="mt-2 text-xs space-y-0.5 text-slate-500">
                                    {order.finance_approved_at ? (
                                        <>
                                            <p className="font-medium text-emerald-700 dark:text-emerald-300">
                                                Anggaran Disetujui: {order.finance_approver?.name || 'Keuangan'}
                                            </p>
                                            <p className="text-[10px] text-slate-400">{formatDateTime(order.finance_approved_at)}</p>
                                        </>
                                    ) : (
                                        <p className="text-slate-400 italic">Menunggu otorisasi pencairan dana</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Informasi Barang & Vendor */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {/* Data Pemohon & Lokasi */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <UserCheck className="w-4 h-4 text-emerald-600" />
                                Pemohon & Penempatan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-xs space-y-2 text-slate-600 dark:text-slate-400">
                            <div className="flex justify-between border-b pb-1.5 border-slate-100 dark:border-slate-800">
                                <span className="text-slate-400">Nama Pemohon:</span>
                                <span className="font-semibold text-slate-900 dark:text-slate-100">{order.requester_name}</span>
                            </div>
                            <div className="flex justify-between border-b pb-1.5 border-slate-100 dark:border-slate-800">
                                <span className="text-slate-400">Departemen / Divisi:</span>
                                <span className="font-medium text-slate-900 dark:text-slate-100">
                                    {order.department} {order.division ? `(${order.division})` : ''}
                                </span>
                            </div>
                            <div className="flex justify-between border-b pb-1.5 border-slate-100 dark:border-slate-800">
                                <span className="text-slate-400">Jabatan:</span>
                                <span className="font-medium text-slate-900 dark:text-slate-100">{order.position}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-400">Lokasi Penempatan:</span>
                                <span className="font-medium text-slate-900 dark:text-slate-100">
                                    {order.location?.name || 'Kantor Pusat / Umum'}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Data Suplier / Vendor */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Building2 className="w-4 h-4 text-purple-600" />
                                Vendor / Suplier
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-xs space-y-2 text-slate-600 dark:text-slate-400">
                            <div className="flex justify-between border-b pb-1.5 border-slate-100 dark:border-slate-800">
                                <span className="text-slate-400">Nama Vendor:</span>
                                <span className="font-semibold text-slate-900 dark:text-slate-100">
                                    {order.vendor?.name || order.vendor_name_manual || 'Vendor Langsung'}
                                </span>
                            </div>
                            {order.vendor && (
                                <>
                                    <div className="flex justify-between border-b pb-1.5 border-slate-100 dark:border-slate-800">
                                        <span className="text-slate-400">Kode Vendor:</span>
                                        <span className="font-mono font-medium text-slate-900 dark:text-slate-100">
                                            {order.vendor.vendor_code}
                                        </span>
                                    </div>
                                    <div className="flex justify-between border-b pb-1.5 border-slate-100 dark:border-slate-800">
                                        <span className="text-slate-400">Kategori:</span>
                                        <span className="font-medium text-slate-900 dark:text-slate-100">
                                            {order.vendor.category}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-400">Kontak PIC:</span>
                                        <span className="font-medium text-slate-900 dark:text-slate-100">
                                            {order.vendor.primary_contact ? `${order.vendor.primary_contact.pic_name} (${order.vendor.primary_contact.phone})` : order.vendor.phone || '-'}
                                        </span>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Aset Terdaftar (Capex) dengan Clickable Asset Code */}
                {order.assets && order.assets.length > 0 && (
                    <Card className="border-indigo-200 dark:border-indigo-900 bg-indigo-50/20 dark:bg-indigo-950/20">
                        <CardHeader className="pb-2.5 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-semibold flex items-center gap-2 text-indigo-950 dark:text-indigo-200">
                                    <Box className="w-4 h-4 text-indigo-600" />
                                    Aset Terdaftar dari PO Ini ({order.assets.length} Unit Aset)
                                </CardTitle>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    Klik kode aset untuk melihat spesifikasi lengkap, riwayat mutasi, dan log servis.
                                </p>
                            </div>
                            <Badge variant="outline" className="text-xs border-indigo-200 text-indigo-700 bg-indigo-50 font-semibold">
                                Capex Registered
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                {order.assets.map((ast) => (
                                    <Link
                                        key={ast.id}
                                        href={route('purchasing.assets.show', ast.asset_code)}
                                        className="p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-800 hover:border-indigo-300 hover:shadow-xs transition-all block group"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 group-hover:underline flex items-center gap-1">
                                                {ast.asset_code}
                                                <ExternalLink className="w-3 h-3 text-indigo-400 group-hover:text-indigo-600" />
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className={`text-[10px] px-1.5 py-0 ${
                                                    ast.status === 'ACTIVE'
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                        : ast.status === 'MAINTENANCE'
                                                        ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                        : 'bg-rose-50 text-rose-700 border-rose-200'
                                                }`}
                                            >
                                                {ast.status}
                                            </Badge>
                                        </div>
                                        <p className="text-xs font-semibold text-slate-800 dark:text-slate-200 mt-1.5 truncate">
                                            {ast.asset_name}
                                        </p>
                                        <div className="text-[11px] text-slate-500 mt-0.5 flex justify-between">
                                            <span>{ast.department}</span>
                                            <span className="font-mono text-slate-400">{ast.serial_number ? `SN: ${ast.serial_number}` : '-'}</span>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Rincian Item Barang & Kalkulasi Angka */}
                <Card>
                    <CardHeader className="pb-2 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <FileText className="w-4 h-4 text-blue-600" />
                                Rincian Barang / Jasa & Nilai Pengadaan
                            </CardTitle>
                            <p className="text-xs text-slate-500 mt-0.5">
                                {order.items && order.items.length > 0
                                    ? `Total ${order.items.length} item barang / jasa terdaftar pada PO ini`
                                    : '1 item barang / jasa terdaftar'}
                            </p>
                        </div>

                        <Badge variant="outline" className="text-xs font-medium">
                            {order.payment_mode === 'SPLIT' ? 'Pembayaran Bertahap (Split TOP)' : '1 Kali Pembayaran (Lunas)'}
                        </Badge>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <table className="w-full text-xs text-left">
                                <thead className="bg-slate-50 dark:bg-slate-900/50 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th className="p-3 font-semibold w-10 text-center">No</th>
                                        <th className="p-3 font-semibold">Nama Barang / Jasa & Spesifikasi</th>
                                        <th className="p-3 font-semibold">Kategori</th>
                                        <th className="p-3 font-semibold text-center">Kuantitas</th>
                                        <th className="p-3 font-semibold text-right">Harga Satuan</th>
                                        <th className="p-3 font-semibold text-right">Diskon</th>
                                        <th className="p-3 font-semibold text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {order.items && order.items.length > 0 ? (
                                        order.items.map((it, idx) => (
                                            <tr key={it.id || idx} className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50">
                                                <td className="p-3 text-center text-slate-400 font-mono font-medium">{idx + 1}</td>
                                                <td className="p-3">
                                                    <p className="font-semibold text-slate-900 dark:text-slate-100">{it.item_name}</p>
                                                    {it.specification && (
                                                        <p className="text-[11px] text-slate-400 mt-0.5">{it.specification}</p>
                                                    )}
                                                </td>
                                                <td className="p-3 text-slate-500">
                                                    {it.item_category?.name || '-'}
                                                </td>
                                                <td className="p-3 text-center font-mono font-medium">
                                                    {parseFloat(it.quantity)} {it.unit}
                                                </td>
                                                <td className="p-3 text-right font-mono">
                                                    {formatRupiah(it.unit_price)}
                                                </td>
                                                <td className="p-3 text-right font-mono text-rose-500">
                                                    {parseFloat(it.discount_amount) > 0 ? `-${formatRupiah(it.discount_amount)}` : '-'}
                                                </td>
                                                <td className="p-3 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">
                                                    {formatRupiah(it.subtotal)}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr className="border-b border-slate-100 dark:border-slate-800">
                                            <td className="p-3 text-center text-slate-400 font-mono">1</td>
                                            <td className="p-3">
                                                <p className="font-semibold text-slate-900 dark:text-slate-100">{order.item_name}</p>
                                                {order.specification && (
                                                    <p className="text-[11px] text-slate-400 mt-0.5">{order.specification}</p>
                                                )}
                                            </td>
                                            <td className="p-3 text-slate-500">
                                                {order.item_category?.name || '-'}
                                            </td>
                                            <td className="p-3 text-center font-mono font-medium">
                                                {parseFloat(order.quantity)} {order.unit}
                                            </td>
                                            <td className="p-3 text-right font-mono">
                                                {formatRupiah(order.unit_price)}
                                            </td>
                                            <td className="p-3 text-right font-mono text-rose-500">
                                                {parseFloat(order.discount_amount) > 0 ? `-${formatRupiah(order.discount_amount)}` : '-'}
                                            </td>
                                            <td className="p-3 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">
                                                {formatRupiah(order.subtotal)}
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Breakdown Biaya Finansial */}
                        <div className="flex flex-col sm:flex-row justify-between items-start gap-4 pt-2">
                            <div className="text-xs space-y-1.5 text-slate-500 max-w-sm">
                                <p><strong>Metode Pembayaran:</strong> {order.payment_type}</p>
                                {order.invoice_number && <p><strong>Nomor Faktur:</strong> {order.invoice_number}</p>}
                                {order.notes && <p className="text-slate-400 italic">Catatan: {order.notes}</p>}
                            </div>

                            <div className="w-full sm:w-80 space-y-2 text-xs">
                                <div className="flex justify-between text-slate-600 dark:text-slate-400">
                                    <span>Subtotal Item:</span>
                                    <span className="font-mono">{formatRupiah(order.subtotal)}</span>
                                </div>
                                {parseFloat(order.discount_amount) > 0 && (
                                    <div className="flex justify-between text-rose-500">
                                        <span>Potongan Diskon:</span>
                                        <span className="font-mono">-{formatRupiah(order.discount_amount)}</span>
                                    </div>
                                )}
                                {parseFloat(order.shipping_cost) > 0 && (
                                    <div className="flex justify-between text-slate-600 dark:text-slate-400">
                                        <span>Ongkos Kirim:</span>
                                        <span className="font-mono">+{formatRupiah(order.shipping_cost)}</span>
                                    </div>
                                )}
                                {parseFloat(order.tax_amount) > 0 && (
                                    <div className="flex justify-between text-slate-600 dark:text-slate-400">
                                        <span>Pajak (PPN):</span>
                                        <span className="font-mono">+{formatRupiah(order.tax_amount)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between pt-2 border-t border-slate-200 dark:border-slate-800 text-sm font-bold text-slate-900 dark:text-slate-100">
                                    <span>Grand Total:</span>
                                    <span className="font-mono text-emerald-600 dark:text-emerald-400 text-base">
                                        {formatRupiah(order.grand_total)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Jadwal Termin Pembayaran Live Display (HANYA untuk SPLIT) */}
                        {isSplitPayment && order.payments && order.payments.length > 0 ? (
                            <div className="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2">
                                <div className="flex items-center justify-between">
                                    <h4 className="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                        <Wallet className="w-3.5 h-3.5 text-indigo-600" />
                                        {isFullyPaid ? 'Riwayat Pembayaran Termin (Lunas 100%)' : `Jadwal Termin Pembayaran (${order.payments.length} Termin)`}
                                    </h4>
                                    <span className={`text-[11px] font-medium ${isFullyPaid ? 'text-emerald-600' : 'text-slate-500'}`}>
                                        {isFullyPaid ? 'Semua Termin Selesai Terbayar' : 'Semua Termin Tersinkronisasi 100%'}
                                    </span>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    {order.payments.map((p, pIdx) => (
                                        <div
                                            key={p.id || pIdx}
                                            className="p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 text-xs space-y-1.5"
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="font-semibold text-slate-900 dark:text-slate-100">
                                                    {p.term_name || `Termin ${p.term_step}`}
                                                </span>
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        p.status === 'PAID'
                                                            ? 'bg-emerald-50 text-emerald-600 border-emerald-300'
                                                            : 'bg-indigo-50 text-indigo-600 border-indigo-300'
                                                    }
                                                >
                                                    {p.status === 'PAID' ? 'LUNAS' : `${p.term_percentage ? `${p.term_percentage}%` : 'TERJADWAL'}`}
                                                </Badge>
                                            </div>
                                            <div className="text-sm font-bold font-mono text-indigo-600 dark:text-indigo-400">
                                                {formatRupiah(p.amount)}
                                            </div>
                                            <div className="text-[11px] text-slate-400 flex items-center gap-1">
                                                <Calendar className="w-3 h-3" />
                                                Jatuh Tempo: {formatDate(p.due_date)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ) : !isSplitPayment ? (
                            <div className="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className={`w-4 h-4 ${isFullyPaid ? 'text-emerald-600' : 'text-slate-400'}`} />
                                    <span className="font-medium text-slate-700 dark:text-slate-300">
                                        Skema Pembayaran: 1 Kali Pembayaran Langsung ({order.payment_type || 'Transfer Bank'})
                                    </span>
                                </div>
                                <Badge
                                    variant={isFullyPaid ? 'default' : 'secondary'}
                                    className={isFullyPaid ? 'bg-emerald-600 text-white text-xs' : 'text-xs'}
                                >
                                    {isFullyPaid ? 'Lunas 100%' : 'Menunggu Otorisasi Finance'}
                                </Badge>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                {/* Lampiran Nota / Invoice */}
                {order.invoice_file_path && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Download className="w-4 h-4 text-blue-600" />
                                Lampiran Nota / Invoice Pembelian
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <FileText className="w-5 h-5 text-blue-600" />
                                    <div>
                                        <p className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                            {order.invoice_file_path.split('/').pop()}
                                        </p>
                                        <p className="text-[11px] text-slate-400">Dokumen bukti transaksi fisik</p>
                                    </div>
                                </div>

                                <a
                                    href={`/storage/${order.invoice_file_path}`}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-400"
                                >
                                    <ExternalLink className="w-3.5 h-3.5" />
                                    Buka Berkas
                                </a>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Modal Dialog Approval / Rejection */}
            <Dialog open={actionModal.isOpen} onOpenChange={(open) => !open && setActionModal({ isOpen: false, action: '', title: '', description: '' })}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold">{actionModal.title}</DialogTitle>
                        <DialogDescription className="text-xs text-slate-500">
                            {actionModal.description}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleActionSubmit} className="space-y-4 my-2">
                        {actionModal.action === 'reject' ? (
                            <div>
                                <Label htmlFor="rejection_reason" className="text-rose-600 font-semibold">
                                    Alasan Penolakan (Wajib) *
                                </Label>
                                <Textarea
                                    id="rejection_reason"
                                    value={data.rejection_reason}
                                    onChange={(e) => setData('rejection_reason', e.target.value)}
                                    placeholder="Jelaskan alasan penolakan order pengadaan ini..."
                                    rows={3}
                                    className="mt-1"
                                    required
                                />
                            </div>
                        ) : (
                            <div>
                                <Label htmlFor="notes">Catatan Tambahan (Opsional)</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Tuliskan catatan verifikasi atau arahan pencairan dana..."
                                    rows={3}
                                    className="mt-1"
                                />
                            </div>
                        )}

                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setActionModal({ isOpen: false, action: '', title: '', description: '' })}
                                disabled={processing}
                            >
                                Batal
                            </Button>

                            <Button
                                type="submit"
                                disabled={processing}
                                className={
                                    actionModal.action === 'reject'
                                        ? 'bg-rose-600 hover:bg-rose-700 text-white'
                                        : 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                }
                            >
                                {processing ? 'Memproses...' : 'Konfirmasi & Terapkan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Drawer Termin Pembayaran (TOP) */}
            <PaymentTermsDrawer
                isOpen={isTermsDrawerOpen}
                onClose={() => setIsTermsDrawerOpen(false)}
                order={order}
                canManageTerms={isSplitPayment && !isTermsLocked && (auth?.user?.is_superadmin || auth?.user?.permissions?.includes('purchasing.manage-orders') || auth?.user?.roles?.includes('admin_purchasing'))}
                canDisbursePayment={auth?.user?.is_superadmin || auth?.user?.permissions?.includes('purchasing.approve-finance') || auth?.user?.roles?.includes('admin_keuangan')}
            />
        </AppLayout>
    );
}
