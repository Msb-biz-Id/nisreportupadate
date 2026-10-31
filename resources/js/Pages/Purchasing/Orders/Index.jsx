import React, { useState } from 'react';
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
} from 'lucide-react';
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
    const [orderType, setOrderType] = useState(filters.order_type || 'all');
    const [department, setDepartment] = useState(filters.department || 'all');
    const [locationId, setLocationId] = useState(filters.location_id ? String(filters.location_id) : 'all');
    const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);

    const handleFilterChange = (newFilters = {}) => {
        const queryParams = {
            search: search || undefined,
            status: status !== 'all' ? status : undefined,
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

    const getStatusBadge = (st) => {
        switch (st) {
            case 'DRAFT':
                return <Badge variant="outline" className="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Draft</Badge>;
            case 'PENDING_PIC_CHECK':
                return <Badge className="bg-blue-500/10 text-blue-600 border border-blue-200 dark:border-blue-800">Menunggu PIC</Badge>;
            case 'APPROVED_BY_PIC':
                return <Badge className="bg-amber-500/10 text-amber-600 border border-amber-200 dark:border-amber-800">Menunggu Finance</Badge>;
            case 'PURCHASE_COMPLETED':
            case 'PAID_COMPLETED':
                return <Badge className="bg-emerald-500/10 text-emerald-600 border border-emerald-200 dark:border-emerald-800">Selesai / Lunas</Badge>;
            case 'REJECTED':
                return <Badge className="bg-rose-500/10 text-rose-600 border border-rose-200 dark:border-rose-800">Ditolak</Badge>;
            default:
                return <Badge variant="secondary">{st}</Badge>;
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

    return (
        <AppLayout>
            <Head title="Daftar Pembelian & Pengadaan (PO)" />

            <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
                {/* Header Page */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <ShoppingBag className="w-6 h-6 text-blue-600" />
                            Pembelian Operasional & Pengadaan (PO)
                        </h1>
                        <p className="text-sm text-slate-500">
                            Kelola alur pengadaan barang/jasa rutin Opex dan investasi Capex dengan Double Sign-Off governance.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        <Button
                            variant="outline"
                            onClick={() => setIsHistoryModalOpen(true)}
                            className="gap-2 border-blue-200 text-blue-600 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-400"
                        >
                            <History className="w-4 h-4" />
                            Cari Riwayat Harga Pasar
                        </Button>

                        <Link href={route('purchasing.orders.create')}>
                            <Button className="gap-2 bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                                <Plus className="w-4 h-4" />
                                Ajukan Pembelian Baru
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Kartu Metrik Ringkas */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <Card className="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs text-slate-500 font-medium">Total Pesanan</p>
                                <p className="text-xl font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                                    {metrics.total_orders || 0}
                                </p>
                            </div>
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <ShoppingBag className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs text-slate-500 font-medium">Menunggu PIC</p>
                                <p className="text-xl font-bold text-blue-600 mt-0.5">
                                    {metrics.pending_pic || 0}
                                </p>
                            </div>
                            <div className="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                <Clock className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs text-slate-500 font-medium">Menunggu Finance</p>
                                <p className="text-xl font-bold text-amber-600 mt-0.5">
                                    {metrics.pending_finance || 0}
                                </p>
                            </div>
                            <div className="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                                <Clock className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs text-slate-500 font-medium">Disetujui / Selesai</p>
                                <p className="text-xl font-bold text-emerald-600 mt-0.5">
                                    {metrics.completed || 0}
                                </p>
                            </div>
                            <div className="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                                <CheckCircle2 className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="col-span-2 sm:col-span-1 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs text-slate-500 font-medium">Total Realisasi Opex</p>
                                <p className="text-lg font-bold text-emerald-600 font-mono mt-0.5">
                                    {formatRupiah(metrics.total_opex_amount || 0)}
                                </p>
                            </div>
                            <div className="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                                <DollarSign className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter Bar */}
                <Card className="border-slate-200 dark:border-slate-800">
                    <CardContent className="p-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                            {/* Input Pencarian */}
                            <form onSubmit={handleSearchSubmit} className="lg:col-span-2 flex gap-2">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                    <Input
                                        placeholder="Cari No PO, barang, pemohon, atau vendor..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-9 text-xs"
                                    />
                                </div>
                                <Button type="submit" size="sm" variant="secondary" className="px-3">
                                    Cari
                                </Button>
                            </form>

                            {/* Filter Status */}
                            <div>
                                <Select
                                    value={status}
                                    onValueChange={(val) => {
                                        setStatus(val);
                                        handleFilterChange({ status: val !== 'all' ? val : undefined });
                                    }}
                                >
                                    <SelectTrigger className="text-xs">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        <SelectItem value="DRAFT">Draft</SelectItem>
                                        <SelectItem value="PENDING_PIC_CHECK">Menunggu PIC</SelectItem>
                                        <SelectItem value="APPROVED_BY_PIC">Menunggu Finance</SelectItem>
                                        <SelectItem value="PURCHASE_COMPLETED">Selesai</SelectItem>
                                        <SelectItem value="REJECTED">Ditolak</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Filter Tipe Belanja */}
                            <div>
                                <Select
                                    value={orderType}
                                    onValueChange={(val) => {
                                        setOrderType(val);
                                        handleFilterChange({ order_type: val !== 'all' ? val : undefined });
                                    }}
                                >
                                    <SelectTrigger className="text-xs">
                                        <SelectValue placeholder="Tipe Belanja" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Tipe</SelectItem>
                                        <SelectItem value="OPEX">OPEX (Operasional)</SelectItem>
                                        <SelectItem value="CAPEX">CAPEX (Aset Tetap)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Filter Departemen */}
                            <div>
                                <Select
                                    value={department}
                                    onValueChange={(val) => {
                                        setDepartment(val);
                                        handleFilterChange({ department: val !== 'all' ? val : undefined });
                                    }}
                                >
                                    <SelectTrigger className="text-xs">
                                        <SelectValue placeholder="Departemen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Dept</SelectItem>
                                        {departments.map((d, idx) => (
                                            <SelectItem key={idx} value={d}>
                                                {d}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Tombol Reset Filter */}
                            <div className="flex justify-end">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={resetFilters}
                                    className="text-xs text-slate-500 hover:text-slate-800 w-full"
                                >
                                    <X className="w-3.5 h-3.5 mr-1" />
                                    Reset Filter
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabel Pesanan Pembelian */}
                <Card className="border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-xs text-left">
                            <thead className="bg-slate-50 dark:bg-slate-900/60 text-slate-500 border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[11px]">
                                <tr>
                                    <th className="p-3.5 font-semibold">No. PO & Tanggal</th>
                                    <th className="p-3.5 font-semibold">Tipe & Pemohon</th>
                                    <th className="p-3.5 font-semibold">Barang / Jasa</th>
                                    <th className="p-3.5 font-semibold">Vendor & Kontak</th>
                                    <th className="p-3.5 font-semibold text-right">Nilai Grand Total</th>
                                    <th className="p-3.5 font-semibold text-center">Status</th>
                                    <th className="p-3.5 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {orders.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="text-center py-16 text-slate-400">
                                            <ShoppingBag className="w-10 h-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" />
                                            <p className="font-semibold text-slate-700 dark:text-slate-300 text-sm">
                                                Tidak Ada Data Order Pembelian
                                            </p>
                                            <p className="text-xs text-slate-400 mt-1">
                                                Belum ada pesanan pembelian yang sesuai dengan kriteria filter saat ini.
                                            </p>
                                        </td>
                                    </tr>
                                ) : (
                                    orders.data.map((order) => {
                                        const vendorName = order.vendor?.name || order.vendor_name_manual || '-';
                                        const vendorPhone = order.vendor?.primary_contact?.phone || order.vendor?.phone;
                                        const cleanPhone = getCleanPhone(vendorPhone);

                                        return (
                                            <tr key={order.uuid} className="hover:bg-slate-50/60 dark:hover:bg-slate-900/40 transition-colors">
                                                {/* No PO & Tanggal */}
                                                <td className="p-3.5">
                                                    <Link
                                                        href={route('purchasing.orders.show', order.po_number)}
                                                        className="font-mono font-bold text-blue-600 hover:text-blue-700 hover:underline block"
                                                    >
                                                        {order.po_number}
                                                    </Link>
                                                    <span className="text-[11px] text-slate-400">
                                                        {formatDate(order.transaction_date)}
                                                    </span>
                                                </td>

                                                {/* Tipe & Pemohon */}
                                                <td className="p-3.5">
                                                    <div className="flex items-center gap-1.5 mb-1">
                                                        <Badge variant="outline" className="text-[10px] px-1.5 py-0 font-medium">
                                                            {order.order_type}
                                                        </Badge>
                                                        <span className="font-medium text-slate-800 dark:text-slate-200">
                                                            {order.requester_name}
                                                        </span>
                                                    </div>
                                                    <p className="text-[11px] text-slate-400">
                                                        {order.department} ({order.position})
                                                    </p>
                                                </td>

                                                {/* Barang & Spek */}
                                                <td className="p-3.5 max-w-xs">
                                                    <p className="font-semibold text-slate-900 dark:text-slate-100 truncate">
                                                        {order.item_name}
                                                    </p>
                                                    <p className="text-[11px] text-slate-400">
                                                        {parseFloat(order.quantity)} {order.unit} @ {formatRupiah(order.unit_price)}
                                                    </p>
                                                </td>

                                                {/* Vendor & Kontak */}
                                                <td className="p-3.5">
                                                    <p className="font-medium text-slate-800 dark:text-slate-200">
                                                        {vendorName}
                                                    </p>
                                                    {cleanPhone ? (
                                                        <a
                                                            href={`https://wa.me/${cleanPhone}`}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="inline-flex items-center gap-1 text-[11px] text-emerald-600 hover:underline mt-0.5"
                                                        >
                                                            <Phone className="w-3 h-3" />
                                                            {vendorPhone}
                                                        </a>
                                                    ) : (
                                                        <span className="text-[11px] text-slate-400">-</span>
                                                    )}
                                                </td>

                                                {/* Nilai Grand Total */}
                                                <td className="p-3.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">
                                                    {formatRupiah(order.grand_total)}
                                                </td>

                                                {/* Status */}
                                                <td className="p-3.5 text-center">
                                                    {getStatusBadge(order.status)}
                                                </td>

                                                {/* Aksi */}
                                                <td className="p-3.5 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link href={route('purchasing.orders.show', order.po_number)}>
                                                            <Button variant="ghost" size="icon" className="h-7 w-7 text-slate-500 hover:text-blue-600">
                                                                <Eye className="w-3.5 h-3.5" />
                                                            </Button>
                                                        </Link>

                                                        {['DRAFT', 'PENDING_PIC_CHECK', 'REJECTED'].includes(order.status) && (
                                                            <Link href={route('purchasing.orders.edit', order.po_number)}>
                                                                <Button variant="ghost" size="icon" className="h-7 w-7 text-slate-500 hover:text-amber-600">
                                                                    <Pencil className="w-3.5 h-3.5" />
                                                                </Button>
                                                            </Link>
                                                        )}

                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleDelete(order.po_number)}
                                                            className="h-7 w-7 text-slate-500 hover:text-rose-600"
                                                        >
                                                            <Trash2 className="w-3.5 h-3.5" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination Links */}
                    {orders.links && orders.links.length > 3 && (
                        <div className="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                            <div>
                                Menampilkan {orders.from || 0} - {orders.to || 0} dari {orders.total} order
                            </div>
                            <div className="flex gap-1">
                                {orders.links.map((link, i) => (
                                    <button
                                        key={i}
                                        onClick={() => link.url && router.visit(link.url, { preserveState: true })}
                                        disabled={!link.url}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-2.5 py-1 rounded text-xs transition-colors ${
                                            link.active
                                                ? 'bg-blue-600 text-white font-semibold'
                                                : link.url
                                                ? 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'
                                                : 'opacity-40 cursor-not-allowed'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>
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
