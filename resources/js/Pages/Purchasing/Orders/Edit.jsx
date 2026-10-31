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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    ArrowLeft,
    ShoppingBag,
    History,
    UploadCloud,
    FileText,
    Calculator,
    Building2,
    UserCheck,
    CheckCircle2,
    X,
    ExternalLink,
} from 'lucide-react';
import PurchaseHistoryModal from './Components/PurchaseHistoryModal';

export default function Edit({
    order,
    vendors = [],
    itemCategories = [],
    units = [],
    locations = [],
    paymentTypes = [],
    hrisEmployees = [],
    hrisReferences = {},
}) {
    const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
    const [invoiceFileName, setInvoiceFileName] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        transaction_date: order.transaction_date ? order.transaction_date.split('T')[0] : '',
        order_type: order.order_type || 'OPEX',
        requester_employee_id: order.requester_employee_id ? String(order.requester_employee_id) : '',
        requester_name: order.requester_name || '',
        department: order.department || '',
        division: order.division || '',
        position: order.position || '',
        location_id: order.location_id ? String(order.location_id) : '',
        vendor_id: order.vendor_id ? String(order.vendor_id) : '',
        vendor_name_manual: order.vendor_name_manual || '',
        item_category_id: order.item_category_id ? String(order.item_category_id) : '',
        item_name: order.item_name || '',
        specification: order.specification || '',
        unit: order.unit || 'Pcs',
        quantity: order.quantity || 1,
        unit_price: order.unit_price || 0,
        discount_amount: order.discount_amount || 0,
        shipping_cost: order.shipping_cost || 0,
        tax_amount: order.tax_amount || 0,
        payment_type: order.payment_type || 'Tunai / Cash',
        invoice_number: order.invoice_number || '',
        invoice_file: null,
        notes: order.notes || '',
    });

    // Kalkulasi matematika real-time
    const quantity = parseFloat(data.quantity) || 0;
    const unitPrice = parseFloat(data.unit_price) || 0;
    const discount = parseFloat(data.discount_amount) || 0;
    const shipping = parseFloat(data.shipping_cost) || 0;
    const tax = parseFloat(data.tax_amount) || 0;

    const subtotal = Math.round(quantity * unitPrice * 100) / 100;
    const grandTotal = Math.max(0, Math.round((subtotal - discount + shipping + tax) * 100) / 100);

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(val || 0);
    };

    const handleRequesterChange = (empId) => {
        if (!empId || empId === 'manual') {
            setData((prev) => ({
                ...prev,
                requester_employee_id: '',
            }));
            return;
        }

        const selectedEmp = hrisEmployees.find((e) => String(e.id) === String(empId));
        if (selectedEmp) {
            setData((prev) => ({
                ...prev,
                requester_employee_id: selectedEmp.id,
                requester_name: selectedEmp.name || selectedEmp.full_name || '',
                department: selectedEmp.department || '',
                division: selectedEmp.division || '',
                position: selectedEmp.position || '',
            }));
        }
    };

    const handleBenchmarkSelect = (item) => {
        setData((prev) => ({
            ...prev,
            item_name: item.item_name || prev.item_name,
            specification: item.specification || prev.specification,
            unit: item.unit || prev.unit,
            unit_price: item.unit_price || prev.unit_price,
            vendor_id: item.vendor_id ? String(item.vendor_id) : prev.vendor_id,
            vendor_name_manual: !item.vendor_id ? item.vendor_name : prev.vendor_name_manual,
            payment_type: item.payment_type || prev.payment_type,
        }));
    };

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('invoice_file', file);
            setInvoiceFileName(file.name);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('purchasing.orders.update', order.po_number));
    };

    const selectedVendor = vendors.find((v) => String(v.id) === String(data.vendor_id));

    return (
        <AppLayout>
            <Head title={`Edit Order ${order.po_number}`} />

            <div className="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link href={route('purchasing.orders.show', order.po_number)}>
                            <Button variant="outline" size="icon" className="h-9 w-9">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                <ShoppingBag className="w-6 h-6 text-blue-600" />
                                Edit Purchase Order {order.po_number}
                            </h1>
                            <p className="text-sm text-slate-500">
                                Perbarui spesifikasi atau kalkulasi sebelum diverifikasi oleh PIC Purchasing.
                            </p>
                        </div>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setIsHistoryModalOpen(true)}
                        className="gap-2 border-blue-200 text-blue-600 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-400"
                    >
                        <History className="w-4 h-4" />
                        Cari Riwayat Harga Pasar
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Seksi 1: Tipe & Lokasi Pengadaan */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <Building2 className="w-4 h-4 text-blue-600" />
                                Parameter Pengadaan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <Label htmlFor="transaction_date">Tanggal Pengajuan *</Label>
                                <Input
                                    id="transaction_date"
                                    type="date"
                                    value={data.transaction_date}
                                    onChange={(e) => setData('transaction_date', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                                {errors.transaction_date && <p className="text-xs text-rose-500 mt-1">{errors.transaction_date}</p>}
                            </div>

                            <div>
                                <Label htmlFor="order_type">Tipe Belanja *</Label>
                                <Select
                                    value={data.order_type}
                                    onValueChange={(val) => setData('order_type', val)}
                                >
                                    <SelectTrigger id="order_type" className="mt-1">
                                        <SelectValue placeholder="Pilih Tipe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="OPEX">OPEX (Beban Operasional Rutin)</SelectItem>
                                        <SelectItem value="CAPEX">CAPEX (Belanja Modal / Aset Tetap)</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.order_type && <p className="text-xs text-rose-500 mt-1">{errors.order_type}</p>}
                            </div>

                            <div>
                                <Label htmlFor="location_id">Lokasi Ruko / Gudang Penempatan</Label>
                                <Select
                                    value={data.location_id ? String(data.location_id) : 'none'}
                                    onValueChange={(val) => setData('location_id', val === 'none' ? '' : val)}
                                >
                                    <SelectTrigger id="location_id" className="mt-1">
                                        <SelectValue placeholder="Pilih Lokasi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">-- Tanpa Lokasi Khusus --</SelectItem>
                                        {locations.map((loc) => (
                                            <SelectItem key={loc.id} value={String(loc.id)}>
                                                {loc.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Seksi 2: Identitas Pemohon */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <UserCheck className="w-4 h-4 text-emerald-600" />
                                Identitas Karyawan Pemohon (HRIS)
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="sm:col-span-2">
                                <Label htmlFor="requester_employee">Pilih Karyawan HRIS</Label>
                                <Select
                                    value={data.requester_employee_id ? String(data.requester_employee_id) : 'manual'}
                                    onValueChange={handleRequesterChange}
                                >
                                    <SelectTrigger id="requester_employee" className="mt-1">
                                        <SelectValue placeholder="Pilih Karyawan Terdaftar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="manual">-- Input Manual / Non-Karyawan --</SelectItem>
                                        {hrisEmployees.map((emp) => (
                                            <SelectItem key={emp.id} value={String(emp.id)}>
                                                {emp.name || emp.full_name} ({emp.department} - {emp.position})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label htmlFor="requester_name">Nama Pemohon *</Label>
                                <Input
                                    id="requester_name"
                                    value={data.requester_name}
                                    onChange={(e) => setData('requester_name', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                                {errors.requester_name && <p className="text-xs text-rose-500 mt-1">{errors.requester_name}</p>}
                            </div>

                            <div>
                                <Label htmlFor="department">Departemen *</Label>
                                <Input
                                    id="department"
                                    value={data.department}
                                    onChange={(e) => setData('department', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                                {errors.department && <p className="text-xs text-rose-500 mt-1">{errors.department}</p>}
                            </div>

                            <div>
                                <Label htmlFor="division">Divisi (Opsional)</Label>
                                <Input
                                    id="division"
                                    value={data.division}
                                    onChange={(e) => setData('division', e.target.value)}
                                    className="mt-1"
                                />
                            </div>

                            <div>
                                <Label htmlFor="position">Jabatan Fungsional *</Label>
                                <Input
                                    id="position"
                                    value={data.position}
                                    onChange={(e) => setData('position', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                                {errors.position && <p className="text-xs text-rose-500 mt-1">{errors.position}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Seksi 3: Suplier / Vendor */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <Building2 className="w-4 h-4 text-purple-600" />
                                Vendor / Suplier
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="vendor_id">Pilih Vendor Terdaftar</Label>
                                    <Select
                                        value={data.vendor_id ? String(data.vendor_id) : 'manual'}
                                        onValueChange={(val) => {
                                            if (val === 'manual') {
                                                setData('vendor_id', '');
                                            } else {
                                                setData((prev) => ({
                                                    ...prev,
                                                    vendor_id: val,
                                                    vendor_name_manual: '',
                                                }));
                                            }
                                        }}
                                    >
                                        <SelectTrigger id="vendor_id" className="mt-1">
                                            <SelectValue placeholder="Pilih Vendor dari Direktori" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="manual">-- Vendor Lain / Belanja Langsung --</SelectItem>
                                            {vendors.map((v) => (
                                                <SelectItem key={v.id} value={String(v.id)}>
                                                    {v.name} ({v.category || 'Vendor'})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {!data.vendor_id && (
                                    <div>
                                        <Label htmlFor="vendor_name_manual">Nama Vendor Manual / Toko</Label>
                                        <Input
                                            id="vendor_name_manual"
                                            value={data.vendor_name_manual}
                                            onChange={(e) => setData('vendor_name_manual', e.target.value)}
                                            className="mt-1"
                                        />
                                    </div>
                                )}
                            </div>

                            {selectedVendor && (
                                <div className="p-3 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs flex flex-wrap gap-4 text-slate-600 dark:text-slate-400">
                                    <div>
                                        Kode: <span className="font-mono font-medium text-slate-900 dark:text-slate-100">{selectedVendor.vendor_code}</span>
                                    </div>
                                    {selectedVendor.primary_contact && (
                                        <div>
                                            PIC: <span className="font-medium text-slate-900 dark:text-slate-100">{selectedVendor.primary_contact.pic_name}</span> ({selectedVendor.primary_contact.phone})
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Seksi 4: Rincian Barang */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <FileText className="w-4 h-4 text-amber-600" />
                                Rincian Barang / Jasa
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="item_category_id">Kategori Item</Label>
                                    <Select
                                        value={data.item_category_id ? String(data.item_category_id) : 'none'}
                                        onValueChange={(val) => setData('item_category_id', val === 'none' ? '' : val)}
                                    >
                                        <SelectTrigger id="item_category_id" className="mt-1">
                                            <SelectValue placeholder="Pilih Kategori" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">-- Pilih Kategori --</SelectItem>
                                            {itemCategories.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>
                                                    {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="sm:col-span-2">
                                    <Label htmlFor="item_name">Nama Barang / Jasa *</Label>
                                    <Input
                                        id="item_name"
                                        value={data.item_name}
                                        onChange={(e) => setData('item_name', e.target.value)}
                                        className="mt-1"
                                        required
                                    />
                                    {errors.item_name && <p className="text-xs text-rose-500 mt-1">{errors.item_name}</p>}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="specification">Spesifikasi Detail / Keterangan Teknis</Label>
                                <Textarea
                                    id="specification"
                                    value={data.specification}
                                    onChange={(e) => setData('specification', e.target.value)}
                                    rows={2}
                                    className="mt-1"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Seksi 5: Kalkulasi Biaya */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <Calculator className="w-4 h-4 text-indigo-600" />
                                Perhitungan Nilai Pengadaan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="quantity">Kuantitas *</Label>
                                    <Input
                                        id="quantity"
                                        type="number"
                                        step="any"
                                        min="0.01"
                                        value={data.quantity}
                                        onChange={(e) => setData('quantity', e.target.value)}
                                        className="mt-1"
                                        required
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="unit">Satuan Barang *</Label>
                                    <Select
                                        value={data.unit}
                                        onValueChange={(val) => setData('unit', val)}
                                    >
                                        <SelectTrigger id="unit" className="mt-1">
                                            <SelectValue placeholder="Pilih Satuan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {units.map((u) => (
                                                <SelectItem key={u.id} value={u.name}>
                                                    {u.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="unit_price">Harga Satuan (Rp) *</Label>
                                    <Input
                                        id="unit_price"
                                        type="number"
                                        min="0"
                                        value={data.unit_price}
                                        onChange={(e) => setData('unit_price', e.target.value)}
                                        className="mt-1 font-mono"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="discount_amount">Potongan Diskon (Rp)</Label>
                                    <Input
                                        id="discount_amount"
                                        type="number"
                                        min="0"
                                        value={data.discount_amount}
                                        onChange={(e) => setData('discount_amount', e.target.value)}
                                        className="mt-1 font-mono"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="shipping_cost">Ongkos Kirim (Rp)</Label>
                                    <Input
                                        id="shipping_cost"
                                        type="number"
                                        min="0"
                                        value={data.shipping_cost}
                                        onChange={(e) => setData('shipping_cost', e.target.value)}
                                        className="mt-1 font-mono"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="tax_amount">Pajak / PPN (Rp)</Label>
                                    <Input
                                        id="tax_amount"
                                        type="number"
                                        min="0"
                                        value={data.tax_amount}
                                        onChange={(e) => setData('tax_amount', e.target.value)}
                                        className="mt-1 font-mono"
                                    />
                                </div>
                            </div>

                            <div className="p-4 rounded-xl bg-slate-900 text-white flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
                                <div className="space-y-1 text-xs text-slate-300">
                                    <div className="flex gap-4">
                                        <span>Subtotal: <strong className="font-mono text-white">{formatRupiah(subtotal)}</strong></span>
                                        <span>Diskon: <strong className="font-mono text-rose-300">-{formatRupiah(discount)}</strong></span>
                                    </div>
                                    <div className="flex gap-4">
                                        <span>Ongkir: <strong className="font-mono text-white">+{formatRupiah(shipping)}</strong></span>
                                        <span>PPN: <strong className="font-mono text-white">+{formatRupiah(tax)}</strong></span>
                                    </div>
                                </div>

                                <div className="text-right">
                                    <span className="text-xs text-slate-400 block uppercase tracking-wider">Grand Total Pembelian</span>
                                    <span className="text-2xl sm:text-3xl font-extrabold text-emerald-400 font-mono">
                                        {formatRupiah(grandTotal)}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Seksi 6: Pembayaran & Dokumen Nota */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <UploadCloud className="w-4 h-4 text-blue-600" />
                                Pembayaran & Lampiran Berkas
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="payment_type">Jenis Transaksi Pembayaran *</Label>
                                    <Select
                                        value={data.payment_type}
                                        onValueChange={(val) => setData('payment_type', val)}
                                    >
                                        <SelectTrigger id="payment_type" className="mt-1">
                                            <SelectValue placeholder="Pilih Jenis Pembayaran" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {paymentTypes.map((p) => (
                                                <SelectItem key={p.id} value={p.name}>
                                                    {p.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="invoice_number">Nomor Faktur / Nota Toko</Label>
                                    <Input
                                        id="invoice_number"
                                        value={data.invoice_number}
                                        onChange={(e) => setData('invoice_number', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            {/* File Nota Eksisting & Upload Baru */}
                            <div>
                                <Label htmlFor="invoice_file">Nota / Faktur Saat Ini</Label>
                                {order.invoice_file_path && (
                                    <div className="mb-2 p-2.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                                        <span className="font-mono text-slate-700 dark:text-slate-300">
                                            {order.invoice_file_path.split('/').pop()}
                                        </span>
                                        <a
                                            href={`/storage/${order.invoice_file_path}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-blue-600 hover:text-blue-700 flex items-center gap-1 font-medium"
                                        >
                                            <ExternalLink className="w-3.5 h-3.5" />
                                            Lihat Berkas
                                        </a>
                                    </div>
                                )}

                                <div className="border-2 border-dashed rounded-xl border-slate-300 dark:border-slate-700 p-4 text-center hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors relative">
                                    <input
                                        type="file"
                                        id="invoice_file"
                                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                                        onChange={handleFileChange}
                                        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    />
                                    {invoiceFileName ? (
                                        <div className="flex items-center justify-center gap-2 text-emerald-600 dark:text-emerald-400 font-medium text-sm">
                                            <CheckCircle2 className="w-5 h-5" />
                                            <span>Ganti Dengan: {invoiceFileName}</span>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    setData('invoice_file', null);
                                                    setInvoiceFileName('');
                                                }}
                                                className="h-6 w-6 p-0 text-slate-400 hover:text-rose-500"
                                            >
                                                <X className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="space-y-1 text-slate-500">
                                            <UploadCloud className="w-8 h-8 mx-auto text-slate-400" />
                                            <p className="text-sm">Klik untuk mengganti berkas nota / invoice</p>
                                        </div>
                                    )}
                                </div>
                                {errors.invoice_file && <p className="text-xs text-rose-500 mt-1">{errors.invoice_file}</p>}
                            </div>

                            <div>
                                <Label htmlFor="notes">Catatan Tambahan</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    rows={2}
                                    className="mt-1"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tombol Aksi */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={route('purchasing.orders.show', order.po_number)}>
                            <Button type="button" variant="outline" disabled={processing}>
                                Batal
                            </Button>
                        </Link>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-blue-600 hover:bg-blue-700 text-white min-w-[140px]"
                        >
                            {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                    </div>
                </form>
            </div>

            <PurchaseHistoryModal
                isOpen={isHistoryModalOpen}
                onClose={() => setIsHistoryModalOpen(false)}
                onSelectBenchmark={handleBenchmarkSelect}
            />
        </AppLayout>
    );
}
