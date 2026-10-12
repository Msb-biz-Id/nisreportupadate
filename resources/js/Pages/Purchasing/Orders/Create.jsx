import React, { useState, useEffect, useMemo } from 'react';
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
    AlertCircle,
    Plus,
    Trash2,
    CreditCard,
    Split,
    Check,
    X,
    Calendar,
    Layers,
    DollarSign,
} from 'lucide-react';
import PurchaseHistoryModal from './Components/PurchaseHistoryModal';
import SearchableItemCombobox from './Components/SearchableItemCombobox';
import SearchableSelectInput from './Components/SearchableSelectInput';

export default function Create({
    vendors = [],
    itemCategories = [],
    itemNames = [],
    units = [],
    locations = [],
    paymentTypes = [],
    hrisEmployees = [],
    hrisReferences = {},
}) {
    const [isHistoryModalOpen, setIsHistoryModalOpen] = useState(false);
    const [benchmarkTargetIndex, setBenchmarkTargetIndex] = useState(0);
    const [invoiceFileName, setInvoiceFileName] = useState('');

    // Format Rupiah Helper
    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    // Form State
    const { data, setData, post, processing, errors } = useForm({
        transaction_date: new Date().toISOString().split('T')[0],
        order_type: 'OPEX',
        requester_employee_id: '',
        requester_name: '',
        department: '',
        division: '',
        position: '',
        location_id: '',
        vendor_id: '',
        vendor_name_manual: '',
        shipping_cost: 0,
        tax_amount: 0,
        payment_type: 'Tunai / Cash',
        payment_mode: 'SINGLE', // SINGLE | SPLIT
        invoice_number: '',
        invoice_file: null,
        status: 'PENDING_PIC_CHECK',
        notes: '',
        // Multi-Item Repeater
        items: [
            {
                item_category_id: '',
                item_name: '',
                specification: '',
                unit: 'Pcs',
                quantity: 1,
                unit_price: 0,
                discount_amount: 0,
                subtotal: 0,
            },
        ],
        // Split / Termin Pembayaran
        terms: [
            {
                term_step: 1,
                term_name: 'Pembayaran Penuh (Lunas)',
                term_percentage: 100,
                amount: 0,
                due_date: new Date().toISOString().split('T')[0],
                notes: 'Pembayaran tunggal lunas',
            },
        ],
    });

    // Option mapping untuk SearchableSelectInput
    const employeeOptions = useMemo(() => {
        const list = [
            {
                value: 'manual',
                label: '-- Input Manual / Non-Karyawan --',
                sublabel: 'Ketik nama dan jabatan manual',
                badge: 'Manual',
            },
        ];
        hrisEmployees.forEach((emp) => {
            list.push({
                value: String(emp.id),
                label: emp.name || emp.full_name,
                sublabel: `${emp.department || 'Dept'} - ${emp.position || 'Staf'}`,
                badge: emp.nik || 'HRIS',
            });
        });
        return list;
    }, [hrisEmployees]);

    const vendorOptions = useMemo(() => {
        const list = [
            {
                value: 'manual',
                label: '-- Vendor Lain / Belanja Langsung --',
                sublabel: 'Ketik nama toko / vendor manual',
                badge: 'Langsung',
            },
        ];
        vendors.forEach((v) => {
            list.push({
                value: String(v.id),
                label: v.name,
                sublabel: v.vendor_code ? `Kode: ${v.vendor_code}` : (v.category || 'Vendor'),
                badge: v.term_days > 0 ? `TOP ${v.term_days} Hari` : (v.category || 'Vendor'),
            });
        });
        return list;
    }, [vendors]);

    const categoryOptions = useMemo(() => {
        const list = [
            {
                value: '',
                label: '-- Pilih Kategori --',
                sublabel: 'Pilih kategori item yang sesuai',
            },
        ];
        itemCategories.forEach((c) => {
            const id = typeof c === 'object' ? String(c.id) : String(c);
            const name = typeof c === 'object' ? c.name : c;
            list.push({
                value: id,
                label: name,
                sublabel: 'Kategori Pengadaan',
            });
        });
        return list;
    }, [itemCategories]);

    // Kalkulasi Total Akumulatif Item
    const calculatedItems = useMemo(() => {
        return data.items.map((it) => {
            const qty = parseFloat(it.quantity) || 0;
            const price = parseFloat(it.unit_price) || 0;
            const disc = parseFloat(it.discount_amount) || 0;
            const sub = Math.max(0, Math.round((qty * price - disc) * 100) / 100);
            return { ...it, subtotal: sub };
        });
    }, [data.items]);

    const itemsSubtotal = useMemo(() => {
        return calculatedItems.reduce((acc, curr) => acc + (curr.subtotal || 0), 0);
    }, [calculatedItems]);

    const shipping = parseFloat(data.shipping_cost) || 0;
    const tax = parseFloat(data.tax_amount) || 0;
    const grandTotal = Math.max(0, Math.round((itemsSubtotal + shipping + tax) * 100) / 100);

    // Hitung total termin pembayaran
    const totalTermsAmount = useMemo(() => {
        if (data.payment_mode === 'SINGLE') {
            return grandTotal;
        }
        return data.terms.reduce((acc, t) => acc + (parseFloat(t.amount) || 0), 0);
    }, [data.payment_mode, data.terms, grandTotal]);

    const termsDifference = Math.round((grandTotal - totalTermsAmount) * 100) / 100;
    const isTermsSynced = Math.abs(termsDifference) < 1;

    // Sinkronisasi otomatis saat Grand Total berubah pada mode SINGLE
    useEffect(() => {
        if (data.payment_mode === 'SINGLE') {
            setData((prev) => ({
                ...prev,
                terms: [
                    {
                        term_step: 1,
                        term_name: 'Pembayaran Penuh (Lunas Sekaligus)',
                        term_percentage: 100,
                        amount: grandTotal,
                        due_date: prev.terms?.[0]?.due_date || prev.transaction_date,
                        notes: '1 kali pembayaran langsung',
                    },
                ],
            }));
        }
    }, [grandTotal, data.payment_mode]);

    // Handler Baris Item
    const handleItemChange = (index, field, value) => {
        const updated = [...data.items];
        updated[index] = { ...updated[index], [field]: value };
        setData('items', updated);
    };

    const handleAddItem = () => {
        setData('items', [
            ...data.items,
            {
                item_category_id: data.items[0]?.item_category_id || '',
                item_name: '',
                specification: '',
                unit: 'Pcs',
                quantity: 1,
                unit_price: 0,
                discount_amount: 0,
                subtotal: 0,
            },
        ]);
    };

    const handleRemoveItem = (index) => {
        if (data.items.length <= 1) return;
        const updated = data.items.filter((_, i) => i !== index);
        setData('items', updated);
    };

    // Handler Mode Pembayaran (SINGLE / SPLIT)
    const handlePaymentModeToggle = (mode) => {
        if (mode === 'SINGLE') {
            setData((prev) => ({
                ...prev,
                payment_mode: 'SINGLE',
                terms: [
                    {
                        term_step: 1,
                        term_name: 'Pembayaran Penuh (Lunas Sekaligus)',
                        term_percentage: 100,
                        amount: grandTotal,
                        due_date: prev.terms?.[0]?.due_date || prev.transaction_date,
                        notes: '1 kali pembayaran langsung',
                    },
                ],
            }));
        } else {
            // Inisialisasi 2 termin jika belum ada split
            const dpAmount = Math.round(grandTotal * 0.5);
            const remainAmount = grandTotal - dpAmount;
            const today = new Date();
            const nextMonth = new Date();
            nextMonth.setDate(today.getDate() + 30);

            setData((prev) => ({
                ...prev,
                payment_mode: 'SPLIT',
                terms: prev.terms && prev.terms.length > 1 ? prev.terms : [
                    {
                        term_step: 1,
                        term_name: 'Termin 1 (Uang Muka / DP 50%)',
                        term_percentage: 50,
                        amount: dpAmount,
                        due_date: prev.transaction_date,
                        notes: 'Pembayaran uang muka DP 50%',
                    },
                    {
                        term_step: 2,
                        term_name: 'Termin 2 (Pelunasan 50%)',
                        term_percentage: 50,
                        amount: remainAmount,
                        due_date: nextMonth.toISOString().split('T')[0],
                        notes: 'Pelunasan sisa setelah barang diterima',
                    },
                ],
            }));
        }
    };

    // Handler Preset Termin
    const applyTermsPreset = (presetType) => {
        const today = new Date();
        const datePlusDays = (days) => {
            const d = new Date();
            d.setDate(today.getDate() + days);
            return d.toISOString().split('T')[0];
        };

        if (presetType === '2_EQUAL') {
            const half = Math.round(grandTotal * 0.5);
            setData('terms', [
                {
                    term_step: 1,
                    term_name: 'Termin 1 (50%)',
                    term_percentage: 50,
                    amount: half,
                    due_date: data.transaction_date,
                    notes: 'Termin pembayaran pertama',
                },
                {
                    term_step: 2,
                    term_name: 'Termin 2 (50%)',
                    term_percentage: 50,
                    amount: grandTotal - half,
                    due_date: datePlusDays(30),
                    notes: 'Pelunasan termin kedua',
                },
            ]);
        } else if (presetType === '3_EQUAL') {
            const t1 = Math.round(grandTotal / 3);
            const t2 = Math.round(grandTotal / 3);
            const t3 = grandTotal - t1 - t2;
            setData('terms', [
                {
                    term_step: 1,
                    term_name: 'Termin 1 (33.3%)',
                    term_percentage: 33.33,
                    amount: t1,
                    due_date: data.transaction_date,
                    notes: 'Uang Muka termin 1',
                },
                {
                    term_step: 2,
                    term_name: 'Termin 2 (33.3%)',
                    term_percentage: 33.33,
                    amount: t2,
                    due_date: datePlusDays(30),
                    notes: 'Termin 2 progress',
                },
                {
                    term_step: 3,
                    term_name: 'Termin 3 (Pelunasan)',
                    term_percentage: 33.34,
                    amount: t3,
                    due_date: datePlusDays(60),
                    notes: 'Pelunasan akhir',
                },
            ]);
        } else if (presetType === 'DP_30_70') {
            const dp = Math.round(grandTotal * 0.3);
            setData('terms', [
                {
                    term_step: 1,
                    term_name: 'Uang Muka / DP 30%',
                    term_percentage: 30,
                    amount: dp,
                    due_date: data.transaction_date,
                    notes: 'Uang muka pengerjaan / pemesanan',
                },
                {
                    term_step: 2,
                    term_name: 'Pelunasan 70%',
                    term_percentage: 70,
                    amount: grandTotal - dp,
                    due_date: datePlusDays(30),
                    notes: 'Pelunasan saat serah terima',
                },
            ]);
        }
    };

    // Handler Edit Baris Termin
    const handleTermChange = (index, field, value) => {
        const updated = [...data.terms];
        const current = { ...updated[index], [field]: value };

        if (field === 'term_percentage') {
            const pct = parseFloat(value) || 0;
            current.amount = Math.round((grandTotal * pct) / 100);
        } else if (field === 'amount') {
            const amt = parseFloat(value) || 0;
            current.term_percentage = grandTotal > 0 ? Math.round(((amt / grandTotal) * 100) * 100) / 100 : 0;
        }

        updated[index] = current;
        setData('terms', updated);
    };

    const handleAddTerm = () => {
        const currentTotal = data.terms.reduce((acc, t) => acc + (parseFloat(t.amount) || 0), 0);
        const remainder = Math.max(0, grandTotal - currentTotal);
        const step = data.terms.length + 1;
        const d = new Date();
        d.setDate(d.getDate() + 30 * step);

        setData('terms', [
            ...data.terms,
            {
                term_step: step,
                term_name: `Termin ${step}`,
                term_percentage: grandTotal > 0 ? Math.round(((remainder / grandTotal) * 100) * 100) / 100 : 0,
                amount: remainder,
                due_date: d.toISOString().split('T')[0],
                notes: `Pembayaran termin ke-${step}`,
            },
        ]);
    };

    const handleRemoveTerm = (index) => {
        if (data.terms.length <= 1) return;
        const updated = data.terms.filter((_, i) => i !== index).map((t, idx) => ({
            ...t,
            term_step: idx + 1,
        }));
        setData('terms', updated);
    };

    // Auto-fix perataan sisa ke termin terakhir
    const handleSyncRemainderToLastTerm = () => {
        if (data.terms.length === 0) return;
        const updated = [...data.terms];
        const lastIdx = updated.length - 1;
        const totalOther = updated.slice(0, lastIdx).reduce((acc, t) => acc + (parseFloat(t.amount) || 0), 0);
        const newLastAmount = Math.max(0, grandTotal - totalOther);
        updated[lastIdx] = {
            ...updated[lastIdx],
            amount: newLastAmount,
            term_percentage: grandTotal > 0 ? Math.round(((newLastAmount / grandTotal) * 100) * 100) / 100 : 0,
        };
        setData('terms', updated);
    };

    // Auto-populate data pemohon dari HRIS
    const handleRequesterSelect = (empId) => {
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

    // Callback dari History Benchmark Modal
    const handleBenchmarkSelect = (item) => {
        const targetIdx = benchmarkTargetIndex || 0;
        const updated = [...data.items];
        if (updated[targetIdx]) {
            updated[targetIdx] = {
                ...updated[targetIdx],
                item_name: item.item_name || updated[targetIdx].item_name,
                specification: item.specification || updated[targetIdx].specification,
                unit: item.unit || updated[targetIdx].unit,
                unit_price: item.unit_price || updated[targetIdx].unit_price,
            };
            setData((prev) => ({
                ...prev,
                items: updated,
                vendor_id: item.vendor_id ? String(item.vendor_id) : prev.vendor_id,
                vendor_name_manual: !item.vendor_id ? item.vendor_name : prev.vendor_name_manual,
                payment_type: item.payment_type || prev.payment_type,
            }));
        }
    };

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('invoice_file', file);
            setInvoiceFileName(file.name);
        }
    };

    const handleSubmit = (statusToSet = 'PENDING_PIC_CHECK') => (e) => {
        e.preventDefault();

        // Validasi sinkronisasi termin jika mode split
        if (data.payment_mode === 'SPLIT' && !isTermsSynced) {
            alert(`Jadwal termin pembayaran belum sinkron dengan Grand Total!\nSelisih: ${formatRupiah(Math.abs(termsDifference))}.\nSilakan klik tombol "Ratakan Sisa ke Termin Terakhir" terlebih dahulu.`);
            return;
        }

        setData('status', statusToSet);
        post(route('purchasing.orders.store'));
    };

    const selectedVendor = vendors.find((v) => String(v.id) === String(data.vendor_id));

    return (
        <AppLayout>
            <Head title="Ajukan Pembelian Baru (PO)" />

            <div className="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-6">
                {/* Header Page */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link href={route('purchasing.orders.index')}>
                            <Button variant="outline" size="icon" className="h-9 w-9">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                <ShoppingBag className="w-6 h-6 text-blue-600" />
                                Pengajuan Pembelian Operasional
                            </h1>
                            <p className="text-sm text-slate-500">
                                Buat purchase order (PO) baru untuk kebutuhan operasional atau investasi aset dengan multi-item dan skema pembayaran terpadu.
                            </p>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit('PENDING_PIC_CHECK')} className="space-y-6">
                    {/* Seksi 1: Parameter Pengadaan */}
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
                                        <SelectItem position="popper" value="OPEX">Operasional (Barang Rutin / Habis Pakai)</SelectItem>
                                        <SelectItem position="popper" value="CAPEX">Aset Tetap (Peralatan / Mesin / Inventaris)</SelectItem>
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
                                        {locations.map((loc, idx) => {
                                            const id = typeof loc === 'object' ? String(loc.id) : String(loc);
                                            const name = typeof loc === 'object' ? loc.name : loc;
                                            return (
                                                <SelectItem key={id || idx} value={id}>
                                                    {name}
                                                </SelectItem>
                                            );
                                        })}
                                    </SelectContent>
                                </Select>
                                {errors.location_id && <p className="text-xs text-rose-500 mt-1">{errors.location_id}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Seksi 2: Identitas Pemohon (HRIS Searchable) */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <UserCheck className="w-4 h-4 text-emerald-600" />
                                Identitas Karyawan Pemohon (HRIS)
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Cari karyawan terdaftar untuk mengisi Departemen, Divisi, dan Jabatan secara otomatis atau pilih input manual.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="sm:col-span-2">
                                <Label>Pilih Karyawan HRIS (Ada Pencarian)</Label>
                                <div className="mt-1">
                                    <SearchableSelectInput
                                        value={data.requester_employee_id ? String(data.requester_employee_id) : (data.requester_name ? 'manual' : '')}
                                        onChange={(val) => handleRequesterSelect(val)}
                                        options={employeeOptions}
                                        placeholder="Ketik untuk mencari nama karyawan / divisi..."
                                        searchPlaceholder="Cari nama, NIK, atau divisi karyawan..."
                                        error={errors.requester_employee_id}
                                    />
                                </div>
                                {errors.requester_employee_id && <p className="text-xs text-rose-500 mt-1">{errors.requester_employee_id}</p>}
                            </div>

                            <div>
                                <Label htmlFor="requester_name">Nama Pemohon *</Label>
                                <Input
                                    id="requester_name"
                                    value={data.requester_name}
                                    onChange={(e) => setData('requester_name', e.target.value)}
                                    placeholder="Nama lengkap pemohon"
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
                                    placeholder="Contoh: Operasional, IT, HRD"
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
                                    placeholder="Divisi spesifik"
                                    className="mt-1"
                                />
                            </div>

                            <div className="sm:col-span-3">
                                <Label htmlFor="position">Jabatan Fungsional *</Label>
                                <Input
                                    id="position"
                                    value={data.position}
                                    onChange={(e) => setData('position', e.target.value)}
                                    placeholder="Posisi pemohon"
                                    className="mt-1"
                                    required
                                />
                                {errors.position && <p className="text-xs text-rose-500 mt-1">{errors.position}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Seksi 3: Suplier / Vendor (Searchable) */}
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
                                    <Label>Pilih Vendor Terdaftar (Ada Pencarian)</Label>
                                    <div className="mt-1">
                                        <SearchableSelectInput
                                            value={data.vendor_id ? String(data.vendor_id) : (data.vendor_name_manual ? 'manual' : '')}
                                            onChange={(val) => {
                                                if (val === 'manual' || !val) {
                                                    setData((prev) => ({ ...prev, vendor_id: '' }));
                                                } else {
                                                    setData((prev) => ({
                                                        ...prev,
                                                        vendor_id: val,
                                                        vendor_name_manual: '',
                                                    }));
                                                }
                                            }}
                                            options={vendorOptions}
                                            placeholder="Ketik untuk mencari nama vendor / kategori..."
                                            searchPlaceholder="Cari vendor atau penyedia..."
                                            error={errors.vendor_id}
                                        />
                                    </div>
                                    {errors.vendor_id && <p className="text-xs text-rose-500 mt-1">{errors.vendor_id}</p>}
                                </div>

                                {!data.vendor_id && (
                                    <div>
                                        <Label htmlFor="vendor_name_manual">Nama Vendor Manual / Toko</Label>
                                        <Input
                                            id="vendor_name_manual"
                                            value={data.vendor_name_manual}
                                            onChange={(e) => setData('vendor_name_manual', e.target.value)}
                                            placeholder="Contoh: Toko Buku Karunia, Mitra Fotokopi"
                                            className="mt-1"
                                        />
                                    </div>
                                )}
                            </div>

                            {selectedVendor && (
                                <div className="p-3 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs flex flex-wrap gap-4 text-slate-600 dark:text-slate-400">
                                    <div>
                                        Kode Vendor: <span className="font-mono font-medium text-slate-900 dark:text-slate-100">{selectedVendor.vendor_code}</span>
                                    </div>
                                    {selectedVendor.primary_contact && (
                                        <div>
                                            PIC Utama: <span className="font-medium text-slate-900 dark:text-slate-100">{selectedVendor.primary_contact.pic_name}</span> ({selectedVendor.primary_contact.phone})
                                        </div>
                                    )}
                                    {selectedVendor.term_days > 0 && (
                                        <div>
                                            Fasilitas TOP: <span className="font-medium text-blue-600">{selectedVendor.term_days} Hari</span>
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Seksi 4 & 5 DIGABUNG: Rincian Barang / Jasa & Nilai Pengadaan */}
                    <Card className="border-blue-200 dark:border-blue-900/50 shadow-sm">
                        <CardHeader className="pb-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <CardTitle className="text-base font-semibold flex items-center gap-2">
                                        <FileText className="w-4 h-4 text-blue-600" />
                                        Rincian Barang / Jasa & Perhitungan Pengadaan
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Dapat menambah lebih dari 1 item barang/jasa. Perhitungan akumulatif otomatis dihitung secara real-time.
                                    </CardDescription>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        setBenchmarkTargetIndex(0);
                                        setIsHistoryModalOpen(true);
                                    }}
                                    className="h-8 text-xs gap-1.5 border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-400 dark:hover:bg-amber-950/40"
                                >
                                    <History className="w-3.5 h-3.5" />
                                    Cari Riwayat Harga PO
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 sm:p-6 space-y-6">
                            {/* Daftar Baris Item */}
                            <div className="space-y-4">
                                {data.items.map((item, idx) => {
                                    const currentSubtotal = Math.max(
                                        0,
                                        (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0) - (parseFloat(item.discount_amount) || 0)
                                    );

                                    return (
                                        <div
                                            key={idx}
                                            className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 shadow-xs space-y-4 relative"
                                        >
                                            <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                                                <div className="flex items-center gap-2">
                                                    <span className="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 text-xs font-bold">
                                                        {idx + 1}
                                                    </span>
                                                    <span className="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                                        Item Pengadaan #{idx + 1}
                                                    </span>
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => {
                                                            setBenchmarkTargetIndex(idx);
                                                            setIsHistoryModalOpen(true);
                                                        }}
                                                        className="h-7 text-[11px] px-2 text-slate-500 hover:text-amber-600 gap-1"
                                                        title="Cari benchmark harga untuk item ini"
                                                    >
                                                        <History className="w-3 h-3" />
                                                        Benchmark
                                                    </Button>

                                                    {data.items.length > 1 && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleRemoveItem(idx)}
                                                            className="h-7 text-[11px] px-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 gap-1"
                                                        >
                                                            <Trash2 className="w-3 h-3" />
                                                            Hapus Baris
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Baris 1: Kategori Item (Searchable) & Nama Item (Searchable Combobox) */}
                                            <div className="grid grid-cols-1 sm:grid-cols-12 gap-3">
                                                <div className="sm:col-span-4">
                                                    <Label className="text-xs">Kategori Item (Ada Pencarian)</Label>
                                                    <div className="mt-1">
                                                        <SearchableSelectInput
                                                            value={item.item_category_id ? String(item.item_category_id) : ''}
                                                            onChange={(val) => handleItemChange(idx, 'item_category_id', val)}
                                                            options={categoryOptions}
                                                            placeholder="Pilih / Cari Kategori..."
                                                            searchPlaceholder="Ketik nama kategori..."
                                                        />
                                                    </div>
                                                </div>

                                                <div className="sm:col-span-8">
                                                    <Label className="text-xs">Nama Barang / Jasa *</Label>
                                                    <div className="mt-1">
                                                        <SearchableItemCombobox
                                                            value={item.item_name}
                                                            onChange={(val) => handleItemChange(idx, 'item_name', val)}
                                                            items={itemNames}
                                                            error={errors[`items.${idx}.item_name`]}
                                                            placeholder="Ketik untuk mencari katalog item atau masukkan nama baru..."
                                                        />
                                                    </div>
                                                    {errors[`items.${idx}.item_name`] && (
                                                        <p className="text-xs text-rose-500 mt-1">{errors[`items.${idx}.item_name`]}</p>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Baris 2: Spesifikasi Detail */}
                                            <div>
                                                <Label className="text-xs">Spesifikasi Detail / Keterangan Teknis</Label>
                                                <Input
                                                    value={item.specification}
                                                    onChange={(e) => handleItemChange(idx, 'specification', e.target.value)}
                                                    placeholder="Tulis merek, tipe, nomor seri, ukuran, atau spesifikasi teknis..."
                                                    className="mt-1 h-8 text-xs"
                                                />
                                            </div>

                                            {/* Baris 3: Kuantitas, Satuan, Harga Satuan, Diskon Item, Subtotal Item */}
                                            <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-1 bg-slate-50/50 dark:bg-slate-950/40 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800">
                                                <div>
                                                    <Label className="text-[11px]">Kuantitas *</Label>
                                                    <Input
                                                        type="number"
                                                        step="any"
                                                        min="0.01"
                                                        value={item.quantity}
                                                        onChange={(e) => handleItemChange(idx, 'quantity', e.target.value)}
                                                        className="mt-1 h-8 text-xs"
                                                        required
                                                    />
                                                </div>

                                                <div>
                                                    <Label className="text-[11px]">Satuan *</Label>
                                                    <Select
                                                        value={item.unit}
                                                        onValueChange={(val) => handleItemChange(idx, 'unit', val)}
                                                    >
                                                        <SelectTrigger className="mt-1 h-8 text-xs">
                                                            <SelectValue placeholder="Satuan" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {units.map((u, uIdx) => {
                                                                const val = typeof u === 'object' ? u.name : u;
                                                                return (
                                                                    <SelectItem key={val || uIdx} value={val}>
                                                                        {val}
                                                                    </SelectItem>
                                                                );
                                                            })}
                                                        </SelectContent>
                                                    </Select>
                                                </div>

                                                <div>
                                                    <Label className="text-[11px]">Harga Satuan (Rp) *</Label>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        value={item.unit_price}
                                                        onChange={(e) => handleItemChange(idx, 'unit_price', e.target.value)}
                                                        className="mt-1 h-8 text-xs font-mono"
                                                        required
                                                    />
                                                </div>

                                                <div>
                                                    <Label className="text-[11px]">Diskon Item (Rp)</Label>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        value={item.discount_amount}
                                                        onChange={(e) => handleItemChange(idx, 'discount_amount', e.target.value)}
                                                        className="mt-1 h-8 text-xs font-mono"
                                                    />
                                                </div>

                                                <div className="col-span-2 sm:col-span-1">
                                                    <Label className="text-[11px] text-slate-500">Subtotal Item</Label>
                                                    <div className="mt-1 h-8 px-2.5 rounded-md border border-slate-200 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-900 text-xs font-mono font-bold text-slate-900 dark:text-slate-100 flex items-center justify-end">
                                                        {formatRupiah(currentSubtotal)}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Tombol Tambah Baris Barang */}
                            <div className="flex justify-start">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleAddItem}
                                    className="gap-1.5 text-xs border-dashed border-blue-400 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40"
                                >
                                    <Plus className="w-3.5 h-3.5" />
                                    Tambah Baris Barang / Jasa Lainnya
                                </Button>
                            </div>

                            {/* Panel Kalkulasi Pengadaan Terpadu */}
                            <div className="pt-4 border-t border-slate-200 dark:border-slate-800">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <Label htmlFor="shipping_cost" className="text-xs">Ongkos Kirim / Ekspedisi (Rp)</Label>
                                            <Input
                                                id="shipping_cost"
                                                type="number"
                                                min="0"
                                                value={data.shipping_cost}
                                                onChange={(e) => setData('shipping_cost', e.target.value)}
                                                className="mt-1 h-9 font-mono text-xs"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="tax_amount" className="text-xs">Pajak / PPN (Rp)</Label>
                                            <Input
                                                id="tax_amount"
                                                type="number"
                                                min="0"
                                                value={data.tax_amount}
                                                onChange={(e) => setData('tax_amount', e.target.value)}
                                                className="mt-1 h-9 font-mono text-xs"
                                            />
                                        </div>
                                    </div>

                                    {/* Ringkasan Akumulasi Grand Total */}
                                    <div className="p-4 rounded-xl bg-slate-900 text-white flex flex-col justify-between gap-2 shadow-inner">
                                        <div className="flex items-center justify-between text-xs text-slate-300">
                                            <span>Subtotal ({data.items.length} Item):</span>
                                            <strong className="font-mono text-white">{formatRupiah(itemsSubtotal)}</strong>
                                        </div>
                                        <div className="flex items-center justify-between text-xs text-slate-300">
                                            <span>Ongkir & PPN:</span>
                                            <strong className="font-mono text-slate-200">
                                                +{formatRupiah(shipping + tax)}
                                            </strong>
                                        </div>
                                        <div className="pt-2 border-t border-slate-800 flex items-center justify-between">
                                            <span className="text-xs uppercase tracking-wider text-slate-400 font-semibold">Grand Total PO</span>
                                            <span className="text-2xl font-extrabold text-emerald-400 font-mono">
                                                {formatRupiah(grandTotal)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Seksi 5: Skema & Jadwal Pembayaran (1 Kali Pembayaran vs Split / Termin TOP) */}
                    <Card className="border-indigo-100 dark:border-indigo-900/40">
                        <CardHeader className="pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <CardTitle className="text-base font-semibold flex items-center gap-2">
                                        <CreditCard className="w-4 h-4 text-indigo-600" />
                                        Skema Pembayaran (1 Kali Bayar / Split Termin TOP)
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Berlaku untuk seluruh tipe belanja (OPEX maupun CAPEX). Atur pembayaran langsung terbit atau cicil bertahap.
                                    </CardDescription>
                                </div>

                                {/* Toggle Pilihan Pembayaran */}
                                <div className="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-medium">
                                    <button
                                        type="button"
                                        onClick={() => handlePaymentModeToggle('SINGLE')}
                                        className={`px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5 ${
                                            data.payment_mode === 'SINGLE'
                                                ? 'bg-white dark:bg-slate-900 text-blue-600 shadow-xs font-semibold'
                                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                        }`}
                                    >
                                        <DollarSign className="w-3.5 h-3.5" />
                                        1 Kali Pembayaran (Lunas)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handlePaymentModeToggle('SPLIT')}
                                        className={`px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5 ${
                                            data.payment_mode === 'SPLIT'
                                                ? 'bg-white dark:bg-slate-900 text-indigo-600 shadow-xs font-semibold'
                                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                                        }`}
                                    >
                                        <Split className="w-3.5 h-3.5" />
                                        Split / Termin / Tempo (TOP)
                                    </button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 sm:p-6 space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="payment_type">Metode / Jenis Transaksi *</Label>
                                    <Select
                                        value={data.payment_type}
                                        onValueChange={(val) => setData('payment_type', val)}
                                    >
                                        <SelectTrigger id="payment_type" className="mt-1">
                                            <SelectValue placeholder="Pilih Jenis Pembayaran" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {paymentTypes.map((p, idx) => {
                                                const val = typeof p === 'object' ? p.name : p;
                                                return (
                                                    <SelectItem key={val || idx} value={val}>
                                                        {val}
                                                    </SelectItem>
                                                );
                                            })}
                                        </SelectContent>
                                    </Select>
                                    {errors.payment_type && <p className="text-xs text-rose-500 mt-1">{errors.payment_type}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="invoice_number">Nomor Faktur / Nota Toko</Label>
                                    <Input
                                        id="invoice_number"
                                        value={data.invoice_number}
                                        onChange={(e) => setData('invoice_number', e.target.value)}
                                        placeholder="Contoh: INV/2026/00192"
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            {/* Mode SINGLE */}
                            {data.payment_mode === 'SINGLE' && (
                                <div className="p-4 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-lg">
                                            <CheckCircle2 className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <h4 className="text-sm font-semibold text-emerald-900 dark:text-emerald-200">
                                                1 Kali Pembayaran Langsung (Lunas Penuh)
                                            </h4>
                                            <p className="text-xs text-emerald-700 dark:text-emerald-400">
                                                Seluruh nilai PO akan ditagihkan dan dibayarkan sekaligus sebesar {formatRupiah(grandTotal)}.
                                            </p>
                                        </div>
                                    </div>

                                    <div className="w-full sm:w-auto">
                                        <Label className="text-[11px] text-emerald-800 dark:text-emerald-300">Tanggal Jatuh Tempo Pembayaran</Label>
                                        <Input
                                            type="date"
                                            value={data.terms[0]?.due_date || data.transaction_date}
                                            onChange={(e) => {
                                                const updated = [...data.terms];
                                                if (updated[0]) {
                                                    updated[0].due_date = e.target.value;
                                                    setData('terms', updated);
                                                }
                                            }}
                                            className="mt-1 h-8 text-xs bg-white dark:bg-slate-900 border-emerald-300 dark:border-emerald-700"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Mode SPLIT / TEMPO */}
                            {data.payment_mode === 'SPLIT' && (
                                <div className="space-y-4 pt-2">
                                    {/* Preset Cepat */}
                                    <div className="flex flex-wrap items-center gap-2 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-800">
                                        <span className="text-xs text-slate-500 font-medium">Preset Cepat:</span>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => applyTermsPreset('2_EQUAL')}
                                            className="h-7 text-xs"
                                        >
                                            Bagi Rata (2x Termin 50% - 50%)
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => applyTermsPreset('DP_30_70')}
                                            className="h-7 text-xs"
                                        >
                                            DP 30% + Pelunasan 70%
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => applyTermsPreset('3_EQUAL')}
                                            className="h-7 text-xs"
                                        >
                                            Bagi Rata (3x Termin)
                                        </Button>
                                    </div>

                                    {/* Tabel Termin */}
                                    <div className="space-y-3">
                                        {data.terms.map((term, tIdx) => (
                                            <div
                                                key={tIdx}
                                                className="p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/30 dark:bg-indigo-950/20 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center"
                                            >
                                                <div className="sm:col-span-1 flex items-center justify-center">
                                                    <span className="w-6 h-6 rounded-full bg-indigo-200 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 font-bold text-xs flex items-center justify-center">
                                                        {tIdx + 1}
                                                    </span>
                                                </div>

                                                <div className="sm:col-span-4">
                                                    <Label className="text-[11px]">Nama Termin *</Label>
                                                    <Input
                                                        value={term.term_name}
                                                        onChange={(e) => handleTermChange(tIdx, 'term_name', e.target.value)}
                                                        placeholder="Misal: Termin 1 (DP 50%)"
                                                        className="mt-1 h-8 text-xs bg-white dark:bg-slate-900"
                                                        required
                                                    />
                                                </div>

                                                <div className="sm:col-span-2">
                                                    <Label className="text-[11px]">Persen (%)</Label>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="100"
                                                        value={term.term_percentage}
                                                        onChange={(e) => handleTermChange(tIdx, 'term_percentage', e.target.value)}
                                                        className="mt-1 h-8 text-xs font-mono bg-white dark:bg-slate-900"
                                                    />
                                                </div>

                                                <div className="sm:col-span-2">
                                                    <Label className="text-[11px]">Nominal (Rp) *</Label>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        value={term.amount}
                                                        onChange={(e) => handleTermChange(tIdx, 'amount', e.target.value)}
                                                        className="mt-1 h-8 text-xs font-mono font-bold bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300"
                                                        required
                                                    />
                                                </div>

                                                <div className="sm:col-span-2">
                                                    <Label className="text-[11px]">Jatuh Tempo *</Label>
                                                    <Input
                                                        type="date"
                                                        value={term.due_date}
                                                        onChange={(e) => handleTermChange(tIdx, 'due_date', e.target.value)}
                                                        className="mt-1 h-8 text-xs bg-white dark:bg-slate-900"
                                                        required
                                                    />
                                                </div>

                                                <div className="sm:col-span-1 flex items-end justify-center">
                                                    {data.terms.length > 1 && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleRemoveTerm(tIdx)}
                                                            className="h-8 w-8 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                                            title="Hapus termin ini"
                                                        >
                                                            <Trash2 className="w-3.5 h-3.5" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Action Bar Termin: Tambah & Indikator Sinkronisasi */}
                                    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={handleAddTerm}
                                            className="gap-1.5 text-xs border-dashed border-indigo-400 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                                        >
                                            <Plus className="w-3.5 h-3.5" />
                                            Tambah Termin Pembayaran
                                        </Button>

                                        {/* Status Sinkronisasi */}
                                        <div className="flex items-center gap-3">
                                            {isTermsSynced ? (
                                                <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-100/70 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 text-xs font-medium">
                                                    <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                                                    <span>Total Termin Sinkron: <strong>{formatRupiah(totalTermsAmount)}</strong> (100%)</span>
                                                </div>
                                            ) : (
                                                <div className="flex items-center gap-2">
                                                    <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-100/70 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 text-xs font-medium">
                                                        <AlertCircle className="w-4 h-4 text-amber-600" />
                                                        <span>
                                                            Belum Sinkron: Selisih <strong>{formatRupiah(Math.abs(termsDifference))}</strong>
                                                        </span>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="secondary"
                                                        size="sm"
                                                        onClick={handleSyncRemainderToLastTerm}
                                                        className="h-8 text-xs bg-amber-500 hover:bg-amber-600 text-white font-medium"
                                                    >
                                                        Ratakan Sisa ke Termin Terakhir
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Upload Nota & Catatan Tambahan */}
                            <div className="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                                <div>
                                    <Label htmlFor="invoice_file">Upload Nota / Struk / Invoice (PDF / Gambar, Max 5MB)</Label>
                                    <div className="mt-1 border-2 border-dashed rounded-xl border-slate-300 dark:border-slate-700 p-4 text-center hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors relative">
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
                                                <span>Berkas Terpilih: {invoiceFileName}</span>
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
                                                <p className="text-sm">Klik atau seret file nota belanja ke area ini</p>
                                                <p className="text-xs text-slate-400">Format yang didukung: PDF, PNG, JPG, WEBP (Maksimal 5MB)</p>
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
                                        placeholder="Keterangan urgensi, instruksi khusus, atau catatan pengadaan..."
                                        rows={2}
                                        className="mt-1"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tombol Aksi */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={route('purchasing.orders.index')}>
                            <Button type="button" variant="outline" disabled={processing}>
                                Batal
                            </Button>
                        </Link>

                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handleSubmit('DRAFT')}
                            disabled={processing}
                        >
                            Simpan Sebagai Draft
                        </Button>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-blue-600 hover:bg-blue-700 text-white min-w-[140px]"
                        >
                            {processing ? 'Menyimpan...' : 'Kirim Pengajuan PO'}
                        </Button>
                    </div>
                </form>
            </div>

            {/* Modal Live Search Benchmark */}
            <PurchaseHistoryModal
                isOpen={isHistoryModalOpen}
                onClose={() => setIsHistoryModalOpen(false)}
                onSelectBenchmark={handleBenchmarkSelect}
            />
        </AppLayout>
    );
}
