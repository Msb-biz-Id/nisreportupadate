import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
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
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import {
    Wallet,
    Calendar,
    Plus,
    Trash2,
    CheckCircle2,
    AlertCircle,
    Clock,
    UploadCloud,
    ExternalLink,
    ShieldCheck,
    DollarSign,
    Sparkles,
} from 'lucide-react';

export default function PaymentTermsDrawer({
    isOpen,
    onClose,
    order,
    canManageTerms = false,
    canDisbursePayment = false,
}) {
    const [isPayModalOpen, setIsPayModalOpen] = useState(false);
    const [selectedPayment, setSelectedPayment] = useState(null);
    const [receiptFileName, setReceiptFileName] = useState('');

    const grandTotal = parseFloat(order?.grand_total || 0);

    // Initial terms builder form
    const { data: termsData, setData: setTermsData, post: postTerms, processing: processingTerms, errors: errorsTerms } = useForm({
        terms: order?.payments?.length > 0
            ? order.payments.map((p, idx) => ({
                term_step: p.term_step || (idx + 1),
                term_name: p.term_name || `Termin ${idx + 1}`,
                term_percentage: parseFloat(p.term_percentage) || 0,
                amount: parseFloat(p.amount) || 0,
                due_date: p.due_date ? p.due_date.split('T')[0] : '',
                notes: p.notes || '',
            }))
            : [
                {
                    term_step: 1,
                    term_name: 'Pelunasan 100%',
                    term_percentage: 100,
                    amount: grandTotal,
                    due_date: new Date().toISOString().split('T')[0],
                    notes: 'Pembayaran penuh',
                },
            ],
    });

    // Form Pencairan Pembayaran oleh Keuangan
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

    // Kalkulasi total nominal pada repeater
    const totalAllocated = termsData.terms.reduce((acc, curr) => acc + (parseFloat(curr.amount) || 0), 0);
    const diff = Math.round((grandTotal - totalAllocated) * 100) / 100;
    const isBalanced = Math.abs(diff) < 0.01;

    // Presets
    const applyPreset = (presetType) => {
        const todayStr = new Date().toISOString().split('T')[0];
        const next14Days = new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0];
        const next30Days = new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0];

        if (presetType === 'FULL') {
            setTermsData('terms', [
                {
                    term_step: 1,
                    term_name: 'Pelunasan 100%',
                    term_percentage: 100,
                    amount: grandTotal,
                    due_date: todayStr,
                    notes: 'Lunas 100%',
                },
            ]);
        } else if (presetType === 'DP50_50') {
            const half = Math.round((grandTotal / 2) * 100) / 100;
            const remainder = Math.round((grandTotal - half) * 100) / 100;
            setTermsData('terms', [
                {
                    term_step: 1,
                    term_name: 'Uang Muka (DP 50%)',
                    term_percentage: 50,
                    amount: half,
                    due_date: todayStr,
                    notes: 'DP saat pemesanan',
                },
                {
                    term_step: 2,
                    term_name: 'Pelunasan (50%)',
                    term_percentage: 50,
                    amount: remainder,
                    due_date: next14Days,
                    notes: 'Pelunasan barang tiba',
                },
            ]);
        } else if (presetType === '30_40_30') {
            const t1 = Math.round((grandTotal * 0.3) * 100) / 100;
            const t2 = Math.round((grandTotal * 0.4) * 100) / 100;
            const t3 = Math.round((grandTotal - t1 - t2) * 100) / 100;
            setTermsData('terms', [
                {
                    term_step: 1,
                    term_name: 'Uang Muka (DP 30%)',
                    term_percentage: 30,
                    amount: t1,
                    due_date: todayStr,
                    notes: 'DP saat pemesanan',
                },
                {
                    term_step: 2,
                    term_name: 'Termin 2 (40%)',
                    term_percentage: 40,
                    amount: t2,
                    due_date: next14Days,
                    notes: 'Saat progres fisik 50%',
                },
                {
                    term_step: 3,
                    term_name: 'Pelunasan (30%)',
                    term_percentage: 30,
                    amount: t3,
                    due_date: next30Days,
                    notes: 'Pelunasan serah terima',
                },
            ]);
        }
    };

    const addTermRow = () => {
        const nextStep = termsData.terms.length + 1;
        const remainingAmount = Math.max(0, diff);
        const remainingPct = grandTotal > 0 ? Math.round((remainingAmount / grandTotal) * 10000) / 100 : 0;

        setTermsData('terms', [
            ...termsData.terms,
            {
                term_step: nextStep,
                term_name: `Termin ${nextStep}`,
                term_percentage: remainingPct,
                amount: remainingAmount,
                due_date: new Date().toISOString().split('T')[0],
                notes: '',
            },
        ]);
    };

    const removeTermRow = (index) => {
        if (termsData.terms.length <= 1) return;
        const updated = termsData.terms.filter((_, i) => i !== index);
        setTermsData('terms', updated);
    };

    const handleTermChange = (index, field, value) => {
        const updated = [...termsData.terms];
        updated[index][field] = value;

        // Auto sync percentage & amount
        if (field === 'term_percentage') {
            const pct = parseFloat(value) || 0;
            updated[index].amount = Math.round(((grandTotal * pct) / 100) * 100) / 100;
        } else if (field === 'amount') {
            const amt = parseFloat(value) || 0;
            updated[index].term_percentage = grandTotal > 0 ? Math.round((amt / grandTotal) * 10000) / 100 : 0;
        }

        setTermsData('terms', updated);
    };

    const handleSaveTerms = (e) => {
        e.preventDefault();
        postTerms(route('purchasing.orders.terms.store', order.po_number), {
            onSuccess: () => {
                // Success
            },
        });
    };

    const openPayModal = (payment) => {
        setSelectedPayment(payment);
        setReceiptFileName('');
        setPayData({
            paid_at: new Date().toISOString().split('T')[0],
            payment_method: 'Transfer Bank',
            receipt_file: null,
            notes: '',
        });
        setIsPayModalOpen(true);
    };

    const handlePaySubmit = (e) => {
        e.preventDefault();
        if (!selectedPayment) return;
        postPay(route('purchasing.payments.pay', selectedPayment.uuid), {
            onSuccess: () => {
                setIsPayModalOpen(false);
                resetPay();
            },
        });
    };

    const getPaymentBadge = (status) => {
        switch (status) {
            case 'DUE_TODAY':
                return <Badge className="bg-red-500 text-white animate-pulse">Jatuh Tempo Hari Ini</Badge>;
            case 'OVERDUE':
                return <Badge className="bg-rose-600 text-white">Lewat Tempo (Overdue)</Badge>;
            case 'PAID':
                return <Badge className="bg-emerald-600 text-white">Lunas Terbayar</Badge>;
            case 'PENDING':
            default:
                return <Badge variant="outline" className="border-blue-300 text-blue-600 dark:border-blue-800 dark:text-blue-400">Menunggu Jatuh Tempo</Badge>;
        }
    };

    return (
        <Sheet open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-full sm:max-w-2xl overflow-y-auto p-6 space-y-6">
                <SheetHeader>
                    <div className="flex items-center gap-2">
                        <div className="p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 rounded-lg">
                            <Wallet className="w-5 h-5" />
                        </div>
                        <div>
                            <SheetTitle className="text-xl font-bold">
                                Manajemen Termin (Term of Payment)
                            </SheetTitle>
                            <SheetDescription className="text-xs">
                                PO {order?.po_number} • Total Kewajiban: <strong className="font-mono text-emerald-600">{formatRupiah(grandTotal)}</strong>
                            </SheetDescription>
                        </div>
                    </div>
                </SheetHeader>

                {/* Status Termin Yang Sudah Ada */}
                {order?.payments && order.payments.length > 0 && (
                    <div className="space-y-3">
                        <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Jadwal Cicilan Termin Aktif
                        </h3>

                        <div className="space-y-2">
                            {order.payments.map((p) => (
                                <div
                                    key={p.uuid}
                                    className="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                                >
                                    <div className="space-y-1 flex-1">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="font-semibold text-slate-900 dark:text-slate-100">
                                                {p.term_name}
                                            </span>
                                            {getPaymentBadge(p.status)}
                                            <span className="text-[11px] text-slate-400 font-mono">
                                                ({parseFloat(p.term_percentage)}%)
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-3 text-slate-500 text-[11px]">
                                            <span className="flex items-center gap-1">
                                                <Calendar className="w-3 h-3" />
                                                Jatuh Tempo: {formatDate(p.due_date)}
                                            </span>
                                            {p.paid_at && (
                                                <span className="text-emerald-600 flex items-center gap-1 font-medium">
                                                    <CheckCircle2 className="w-3 h-3" />
                                                    Dibayar: {formatDate(p.paid_at)} via {p.payment_method}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex items-center justify-between sm:justify-end gap-3 border-t sm:border-t-0 pt-2 sm:pt-0">
                                        <span className="text-sm font-bold font-mono text-slate-900 dark:text-slate-100">
                                            {formatRupiah(p.amount)}
                                        </span>

                                        {p.status !== 'PAID' && canDisbursePayment && (
                                            <Button
                                                size="sm"
                                                onClick={() => openPayModal(p)}
                                                className="h-8 gap-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs"
                                            >
                                                <ShieldCheck className="w-3.5 h-3.5" />
                                                Bayar
                                            </Button>
                                        )}

                                        {p.receipt_file_path && (
                                            <a
                                                href={`/storage/${p.receipt_file_path}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex items-center gap-1 text-[11px] text-blue-600 hover:underline"
                                            >
                                                <ExternalLink className="w-3 h-3" />
                                                Bukti TF
                                            </a>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Form Konfigurasi / Pembuatan Jadwal Termin */}
                {canManageTerms && (
                    <div className="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                                <Sparkles className="w-3.5 h-3.5 text-blue-600" />
                                Atur / Rekonfigurasi Jadwal Termin
                            </h3>

                            {/* Preset Buttons */}
                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => applyPreset('FULL')}
                                    className="h-7 text-[11px] px-2"
                                >
                                    Lunas 100%
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => applyPreset('DP50_50')}
                                    className="h-7 text-[11px] px-2"
                                >
                                    50% - 50%
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => applyPreset('30_40_30')}
                                    className="h-7 text-[11px] px-2"
                                >
                                    30-40-30
                                </Button>
                            </div>
                        </div>

                        <form onSubmit={handleSaveTerms} className="space-y-3">
                            {termsData.terms.map((row, index) => (
                                <div
                                    key={index}
                                    className="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40 space-y-2 text-xs"
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <Input
                                            value={row.term_name}
                                            onChange={(e) => handleTermChange(index, 'term_name', e.target.value)}
                                            placeholder="Nama Termin (e.g. DP 30%)"
                                            className="h-8 text-xs font-medium flex-1"
                                            required
                                        />
                                        {termsData.terms.length > 1 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => removeTermRow(index)}
                                                className="h-8 w-8 text-slate-400 hover:text-rose-500"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-3 gap-2">
                                        <div>
                                            <Label className="text-[10px] text-slate-400">Porsi (%)</Label>
                                            <Input
                                                type="number"
                                                step="any"
                                                min="0"
                                                max="100"
                                                value={row.term_percentage}
                                                onChange={(e) => handleTermChange(index, 'term_percentage', e.target.value)}
                                                className="h-8 text-xs font-mono"
                                            />
                                        </div>

                                        <div>
                                            <Label className="text-[10px] text-slate-400">Nominal (Rp)</Label>
                                            <Input
                                                type="number"
                                                step="any"
                                                min="1"
                                                value={row.amount}
                                                onChange={(e) => handleTermChange(index, 'amount', e.target.value)}
                                                className="h-8 text-xs font-mono font-semibold"
                                                required
                                            />
                                        </div>

                                        <div>
                                            <Label className="text-[10px] text-slate-400">Jatuh Tempo</Label>
                                            <Input
                                                type="date"
                                                value={row.due_date}
                                                onChange={(e) => handleTermChange(index, 'due_date', e.target.value)}
                                                className="h-8 text-xs"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                            ))}

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addTermRow}
                                className="w-full text-xs gap-1 border-dashed"
                            >
                                <Plus className="w-3.5 h-3.5" />
                                Tambah Tahap Termin
                            </Button>

                            {/* Ringkasan Balance Checker */}
                            <div className={`p-3 rounded-xl border text-xs flex items-center justify-between ${
                                isBalanced
                                    ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-300'
                                    : 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300'
                            }`}>
                                <div className="flex items-center gap-2">
                                    {isBalanced ? <CheckCircle2 className="w-4 h-4 text-emerald-600" /> : <AlertCircle className="w-4 h-4 text-rose-600" />}
                                    <span>
                                        {isBalanced
                                            ? 'Alokasi termin tepat 100% sama dengan Grand Total PO'
                                            : `Selisih nominal alokasi: ${formatRupiah(diff)}`}
                                    </span>
                                </div>
                                <span className="font-mono font-bold">
                                    {formatRupiah(totalAllocated)} / {formatRupiah(grandTotal)}
                                </span>
                            </div>
                            {errorsTerms.terms && <p className="text-xs text-rose-500">{errorsTerms.terms}</p>}

                            <Button
                                type="submit"
                                disabled={!isBalanced || processingTerms}
                                className="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs h-9"
                            >
                                {processingTerms ? 'Menyimpan Jadwal...' : 'Simpan Jadwal Termin'}
                            </Button>
                        </form>
                    </div>
                )}
            </SheetContent>

            {/* Modal Pencairan Pembayaran Keuangan */}
            <Dialog open={isPayModalOpen} onOpenChange={setIsPayModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="text-lg font-bold flex items-center gap-2">
                            <ShieldCheck className="w-5 h-5 text-emerald-600" />
                            Pencairan Dana {selectedPayment?.term_name}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Verifikasi pencairan pembayaran sebesar <strong className="font-mono text-emerald-600">{formatRupiah(selectedPayment?.amount)}</strong> untuk PO {order?.po_number}.
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
                                    placeholder="Contoh: Transfer BCA, Kas Operasional"
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
                                    if (file) {
                                        setPayData('receipt_file', file);
                                        setReceiptFileName(file.name);
                                    }
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
                                onClick={() => setIsPayModalOpen(false)}
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
        </Sheet>
    );
}
