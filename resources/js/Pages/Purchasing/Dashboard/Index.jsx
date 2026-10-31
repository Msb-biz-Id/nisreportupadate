import React, { useState } from 'react';
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
} from 'lucide-react';
import PurchaseHistoryModal from '../Orders/Components/PurchaseHistoryModal';

export default function Index({
    metrics = {},
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

    return (
        <AppLayout>
            <Head title="Dashboard Purchasing & Alert Center" />

            <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
                {/* Header Page & Quick Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                <LayoutDashboard className="w-6 h-6 text-blue-600" />
                                Dashboard Purchasing & Pengadaan
                            </h1>
                            <Badge variant="outline" className="bg-blue-50/50 text-blue-700 border-blue-200 dark:border-blue-900 text-xs font-semibold">
                                5-Tier Alert Center
                            </Badge>
                        </div>
                        <p className="text-xs text-slate-500 mt-1">
                            Pusat kendali pengadaan operasional, termin pembayaran (TOP), jatuh tempo, dan pemantauan aset perusahaan.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setIsHistoryModalOpen(true)}
                            className="gap-1.5 border-blue-200 text-blue-600 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-400 text-xs"
                        >
                            <History className="w-3.5 h-3.5" />
                            Cari Riwayat Harga
                        </Button>

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
                                <p className="text-xs text-slate-500 font-medium">Realisasi Opex Bulan Ini</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-slate-100 font-mono">
                                    {formatRupiah(metrics.monthly_opex || 0)}
                                </p>
                                <p className="text-[10px] text-slate-400">
                                    Capex: {formatRupiah(metrics.monthly_capex || 0)}
                                </p>
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

                {/* THE 5-TIER COLOR CODING ALERT CENTER */}
                <Card className="border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <CardTitle className="text-base font-bold flex items-center gap-2">
                                    <div className="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping" />
                                    Alert Center Berbasis 5 Skema Warna
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Peringatan dini multi-skema otomatis berdasarkan tanggal jatuh tempo, status approval, dan stok.
                                </CardDescription>
                            </div>

                            {/* 5-Color Filter Tabs */}
                            <div className="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                                <Button
                                    size="sm"
                                    variant={activeAlertTab === 'red' ? 'default' : 'outline'}
                                    onClick={() => setActiveAlertTab('red')}
                                    className={`h-7 text-xs gap-1.5 ${
                                        activeAlertTab === 'red'
                                            ? 'bg-red-600 hover:bg-red-700 text-white'
                                            : 'border-red-200 text-red-600 dark:border-red-900'
                                    }`}
                                >
                                    🔴 Red ({redCount})
                                </Button>

                                <Button
                                    size="sm"
                                    variant={activeAlertTab === 'amber' ? 'default' : 'outline'}
                                    onClick={() => setActiveAlertTab('amber')}
                                    className={`h-7 text-xs gap-1.5 ${
                                        activeAlertTab === 'amber'
                                            ? 'bg-amber-600 hover:bg-amber-700 text-white'
                                            : 'border-amber-200 text-amber-600 dark:border-amber-900'
                                    }`}
                                >
                                    🟡 Amber ({amberCount})
                                </Button>

                                <Button
                                    size="sm"
                                    variant={activeAlertTab === 'blue' ? 'default' : 'outline'}
                                    onClick={() => setActiveAlertTab('blue')}
                                    className={`h-7 text-xs gap-1.5 ${
                                        activeAlertTab === 'blue'
                                            ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                            : 'border-blue-200 text-blue-600 dark:border-blue-900'
                                    }`}
                                >
                                    🔵 Blue ({blueCount})
                                </Button>

                                <Button
                                    size="sm"
                                    variant={activeAlertTab === 'green' ? 'default' : 'outline'}
                                    onClick={() => setActiveAlertTab('green')}
                                    className={`h-7 text-xs gap-1.5 ${
                                        activeAlertTab === 'green'
                                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                            : 'border-emerald-200 text-emerald-600 dark:border-emerald-900'
                                    }`}
                                >
                                    🟢 Green ({greenCount})
                                </Button>

                                <Button
                                    size="sm"
                                    variant={activeAlertTab === 'grey' ? 'default' : 'outline'}
                                    onClick={() => setActiveAlertTab('grey')}
                                    className={`h-7 text-xs gap-1.5 ${
                                        activeAlertTab === 'grey'
                                            ? 'bg-slate-700 hover:bg-slate-800 text-white'
                                            : 'border-slate-200 text-slate-600 dark:border-slate-800'
                                    }`}
                                >
                                    ⚪ Grey ({greyCount})
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="p-4 sm:p-5">
                        {/* Render active alert items */}
                        {alerts[activeAlertTab]?.length === 0 ? (
                            <div className="text-center py-12 text-slate-400">
                                <CheckCircle2 className="w-8 h-8 mx-auto text-slate-300 dark:text-slate-700 mb-2" />
                                <p className="text-xs font-medium">Tidak ada pemberitahuan pada skema ini saat ini.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                {alerts[activeAlertTab]?.map((item, idx) => (
                                    <div
                                        key={idx}
                                        className="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 hover:shadow-sm transition-all flex flex-col justify-between gap-3 text-xs"
                                    >
                                        <div className="space-y-1">
                                            <div className="flex items-center justify-between">
                                                <span className="font-semibold text-slate-900 dark:text-slate-100">
                                                    {item.title}
                                                </span>
                                                {item.amount && (
                                                    <span className="font-mono font-bold text-slate-800 dark:text-slate-200">
                                                        {formatRupiah(item.amount)}
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-[11px] text-slate-500">
                                                {item.subtitle}
                                            </p>
                                        </div>

                                        <div className="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                                            <span className="text-slate-400">{item.created_at || '-'}</span>
                                            {item.target_url && (
                                                <Link
                                                    href={item.target_url}
                                                    className="inline-flex items-center gap-1 font-semibold text-blue-600 hover:underline"
                                                >
                                                    Lihat Dokumen
                                                    <ArrowUpRight className="w-3 h-3" />
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* TABEL TAGIHAN TOP JATUH TEMPO MENDATANG */}
                <Card className="border-slate-200 dark:border-slate-800 overflow-hidden">
                    <CardHeader className="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-base font-bold flex items-center gap-2">
                                <Wallet className="w-4 h-4 text-purple-600" />
                                Tagihan Termin (TOP) Jatuh Tempo Terdekat
                            </CardTitle>
                            <CardDescription className="text-xs">
                                10 cicilan pembayaran supplier terdekat yang memerlukan pencairan oleh Keuangan.
                            </CardDescription>
                        </div>

                        <Link href={route('purchasing.orders.index')}>
                            <Button variant="ghost" size="sm" className="text-xs text-blue-600 gap-1">
                                Semua Orders
                                <ArrowUpRight className="w-3.5 h-3.5" />
                            </Button>
                        </Link>
                    </CardHeader>

                    <div className="overflow-x-auto">
                        <table className="w-full text-xs text-left">
                            <thead className="bg-slate-50 dark:bg-slate-900/60 text-slate-500 border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th className="p-3.5 font-semibold">No. PO & Termin</th>
                                    <th className="p-3.5 font-semibold">Vendor & Kontak WA</th>
                                    <th className="p-3.5 font-semibold">Jatuh Tempo</th>
                                    <th className="p-3.5 font-semibold text-right">Nominal Tagihan</th>
                                    <th className="p-3.5 font-semibold text-center">Status</th>
                                    <th className="p-3.5 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {upcomingPayments.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="text-center py-12 text-slate-400">
                                            <CheckCircle2 className="w-8 h-8 mx-auto text-slate-300 dark:text-slate-700 mb-2" />
                                            <p className="font-semibold text-slate-700 dark:text-slate-300 text-xs">
                                                Tidak Ada Tagihan Jatuh Tempo Aktif
                                            </p>
                                            <p className="text-[11px] text-slate-400 mt-0.5">
                                                Semua cicilan termin supplier telah diselesaikan atau belum jatuh tempo.
                                            </p>
                                        </td>
                                    </tr>
                                ) : (
                                    upcomingPayments.map((p) => {
                                        const cleanPhone = getCleanPhone(p.vendor_phone);

                                        return (
                                            <tr key={p.uuid} className="hover:bg-slate-50/60 dark:hover:bg-slate-900/40 transition-colors">
                                                <td className="p-3.5">
                                                    <Link
                                                        href={route('purchasing.orders.show', p.po_number)}
                                                        className="font-mono font-bold text-blue-600 hover:underline block"
                                                    >
                                                        {p.po_number}
                                                    </Link>
                                                    <span className="text-[11px] text-slate-500 font-medium">
                                                        {p.term_name}
                                                    </span>
                                                </td>

                                                <td className="p-3.5">
                                                    <p className="font-semibold text-slate-800 dark:text-slate-200">
                                                        {p.vendor_name}
                                                    </p>
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
                                                </td>

                                                <td className="p-3.5">
                                                    <span className="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                                        <Calendar className="w-3.5 h-3.5" />
                                                        {formatDate(p.due_date)}
                                                    </span>
                                                </td>

                                                <td className="p-3.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">
                                                    {formatRupiah(p.amount)}
                                                </td>

                                                <td className="p-3.5 text-center">
                                                    {getPaymentBadge(p.status)}
                                                </td>

                                                <td className="p-3.5 text-right">
                                                    {canDisbursePayment && (
                                                        <Button
                                                            size="sm"
                                                            onClick={() => openPayModal(p)}
                                                            className="h-7 text-xs gap-1 bg-emerald-600 hover:bg-emerald-700 text-white"
                                                        >
                                                            <ShieldCheck className="w-3 h-3" />
                                                            Bayar
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
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
