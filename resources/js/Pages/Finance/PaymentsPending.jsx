import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { 
    ShieldCheck, Calendar, User, Banknote, Landmark, FileText, 
    ArrowLeft, AlertCircle, Info, Check, Eye, Trash2, X, Sparkles
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { formatDate, formatRupiah } from '@/lib/utils';
import { toast } from 'sonner';

export default function PaymentsPending({ 
    pending_payments = [], 
    brands = [], 
    filters = {}, 
    can = {} 
}) {
    const [brandId, setBrandId] = useState(filters?.brand_id ?? 'all');
    const [selectedPayment, setSelectedPayment] = useState(null);
    const [isVerifyModalOpen, setIsVerifyModalOpen] = useState(false);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
    const [imagePreviewUrl, setImagePreviewUrl] = useState(null);
    const [verificationNotes, setVerificationNotes] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [checklist, setChecklist] = useState({
        bankMutasi: false,
        nominalCocok: false,
        buktiValid: false
    });

    function applyBrandFilter(v) {
        setBrandId(v);
        router.get(route('invoices.payments.pending'), {
            brand_id: v === 'all' ? '' : v,
        }, { preserveScroll: true, preserveState: true });
    }

    const totalPendingAmount = pending_payments.reduce((acc, curr) => acc + Number(curr.amount), 0);

    function handleOpenVerify(payment) {
        setSelectedPayment(payment);
        setVerificationNotes('');
        setChecklist({
            bankMutasi: false,
            nominalCocok: false,
            buktiValid: false
        });
        setIsVerifyModalOpen(true);
    }

    function handleApprove() {
        if (!checklist.bankMutasi || !checklist.nominalCocok || !checklist.buktiValid) {
            toast.error('Harap centang semua item checklist verifikasi sebelum menyetujui.');
            return;
        }

        setIsProcessing(true);
        router.post(route('invoices.payments.verify', selectedPayment.id), {
            bank_mutasi: checklist.bankMutasi,
            nominal_cocok: checklist.nominalCocok,
            bukti_valid: checklist.buktiValid,
            verification_notes: verificationNotes
        }, {
            onSuccess: () => {
                setIsVerifyModalOpen(false);
                setSelectedPayment(null);
                setIsProcessing(false);
                toast.success('Pembayaran berhasil diverifikasi.');
            },
            onError: (err) => {
                setIsProcessing(false);
                toast.error('Gagal memverifikasi pembayaran.');
            }
        });
    }

    return (
        <AppLayout title="Validasi Pembayaran Pending">
            <Head title="Validasi Pembayaran Pending (Mutasi Rekening Koran)" />

            <div className="space-y-6">
                {/* Header with Breadcrumb-like Back navigation */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2 text-xs text-muted-foreground font-semibold">
                            <Link href={route('invoices.index')} className="hover:text-indigo-600 transition-colors">
                                Dashboard Keuangan
                            </Link>
                            <span>/</span>
                            <span className="text-slate-600 font-bold">Validasi Pending</span>
                        </div>
                        <h1 className="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                            <ShieldCheck className="h-6 w-6 text-indigo-600" />
                            Validasi Pembayaran Pending
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Mutasi Rekening Koran — Verifikasi bukti transfer pelanggan secara presisi.
                        </p>
                    </div>

                    <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <div className="w-full sm:w-56">
                            <Select value={brandId} onValueChange={applyBrandFilter}>
                                <SelectTrigger className="bg-white border-slate-200 rounded-lg">
                                    <SelectValue placeholder="Pilih Brand" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Brand</SelectItem>
                                    {brands.map((b) => (
                                        <SelectItem key={b.id} value={b.id}>
                                            {b.nama_brand}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                {/* Info & Statistics */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card className="md:col-span-2 bg-indigo-950 text-white rounded-2xl shadow-md border-indigo-900 overflow-hidden relative">
                        <div className="absolute top-0 right-0 p-4 opacity-5">
                            <Sparkles className="h-40 w-40" />
                        </div>
                        <CardContent className="p-6 relative z-10 flex flex-col justify-between h-full min-h-[120px]">
                            <div>
                                <span className="bg-indigo-500/20 text-indigo-300 text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded border border-indigo-500/30">
                                    Total Pending Verification
                                </span>
                                <h2 className="text-3xl font-black font-mono tracking-tight mt-2">
                                    {formatRupiah(totalPendingAmount)}
                                </h2>
                            </div>
                            <p className="text-[11px] text-indigo-300/80 mt-4 leading-relaxed flex items-center gap-1">
                                <Info className="h-4 w-4 shrink-0 text-indigo-400" />
                                Pencocokan data ini berdampak langsung pada sisa tagihan Invoice & progress produksi PO.
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="bg-white rounded-2xl border shadow-sm flex flex-col justify-between">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-bold text-slate-800 uppercase tracking-wider">Antrean Mutasi</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col items-center justify-center flex-1 py-4">
                            <div className="text-4xl font-extrabold text-slate-800 font-mono">
                                {pending_payments.length}
                            </div>
                            <span className="text-[11px] font-semibold text-slate-500 mt-1">Transaksi Menunggu Validasi</span>
                        </CardContent>
                    </Card>
                </div>

                {/* Table containing the payments */}
                <Card className="rounded-2xl shadow-sm border overflow-hidden bg-white">
                    <CardHeader className="bg-slate-50/50 border-b pb-4">
                        <CardTitle className="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                            <Landmark className="h-4 w-4 text-indigo-600" />
                            Daftar Transaksi Rekening Koran (Pending)
                        </CardTitle>
                        <CardDescription className="text-xs">Daftar setoran masuk yang dicatat oleh Sales/Admin dan menunggu persetujuan Finance.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-slate-50/50">
                                <TableRow>
                                    <TableHead className="font-semibold text-xs text-slate-600">Pelanggan / PO</TableHead>
                                    <TableHead className="font-semibold text-xs text-slate-600">Brand</TableHead>
                                    <TableHead className="font-semibold text-xs text-slate-600">Metode / Bank</TableHead>
                                    <TableHead className="font-semibold text-xs text-slate-600">Jenis</TableHead>
                                    <TableHead className="font-semibold text-xs text-slate-600">Petugas Input</TableHead>
                                    <TableHead className="font-semibold text-xs text-slate-600">Tanggal Bayar</TableHead>
                                    <TableHead className="font-semibold text-xs text-slate-600 text-right">Nominal</TableHead>
                                    <TableHead className="font-semibold text-xs text-slate-600 text-center">Bukti Bayar</TableHead>
                                    <TableHead className="font-semibold text-xs text-slate-600 text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pending_payments.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={9} className="py-16 text-center text-sm text-slate-500 italic">
                                            Tidak ada transaksi pending yang perlu divalidasi.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    pending_payments.map((p) => (
                                        <TableRow key={p.id} className="hover:bg-slate-50/30 text-xs">
                                            <TableCell>
                                                <div className="font-bold text-slate-800">{p.order?.no_po ?? '—'}</div>
                                                <div className="text-[10px] text-slate-400">{p.order?.nama_po ?? '—'}</div>
                                                {/* DP progress indicator */}
                                                {p.dp_info && p.dp_info.order_status === 'draft' && (
                                                    <div className={`mt-1.5 flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold w-fit ${
                                                        p.dp_info.will_be_sufficient
                                                            ? 'bg-emerald-100 text-emerald-700'
                                                            : 'bg-amber-100 text-amber-700'
                                                    }`}>
                                                        {p.dp_info.will_be_sufficient
                                                            ? <Check className="h-3 w-3" />
                                                            : <AlertCircle className="h-3 w-3" />
                                                        }
                                                        DP {p.dp_info.current_pct}% → {p.dp_info.after_pct}%
                                                        {p.dp_info.will_be_sufficient && !p.dp_info.already_sufficient && ' ✓ Cukup'}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" className="font-bold text-indigo-700 bg-indigo-50">
                                                    {p.order?.brand?.kode ?? '—'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {p.bank ? (
                                                    <div>
                                                        <div className="font-bold text-slate-700">{p.bank.bank}</div>
                                                        <div className="text-[10px] text-slate-500">{p.bank.nomor_rekening} ({p.bank.atas_nama})</div>
                                                    </div>
                                                ) : (
                                                    <span className="font-mono text-slate-400 italic">CASH / MANUAL</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" className="text-[10px] font-bold uppercase">
                                                    {p.master_jenis_pembayaran?.nama === 'Return' || p.master_jenis_pembayaran?.nama === 'Refurn'
                                                        ? 'Refund'
                                                        : (p.master_jenis_pembayaran?.nama ?? (p.payment_type === 'return' ? 'Refund' : (p.payment_type ?? 'Lainnya')))}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="font-semibold text-slate-600">
                                                <div className="flex items-center gap-1">
                                                    <User className="h-3 w-3 text-slate-400" />
                                                    {p.recorder?.name ?? '—'}
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-semibold text-slate-600">
                                                {formatDate(p.payment_date)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono font-bold text-slate-800 text-sm">
                                                {formatRupiah(p.amount)}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {(p.proof_file || p.receipt_path) ? (
                                                    <Button 
                                                        onClick={() => setImagePreviewUrl(p.proof_file || p.receipt_path)} 
                                                        size="xs" 
                                                        variant="ghost" 
                                                        className="text-indigo-600 hover:text-indigo-800 font-bold"
                                                    >
                                                        <Eye className="h-4.5 w-4.5 mr-1" /> Bukti
                                                    </Button>
                                                ) : (
                                                    <span className="text-[10px] text-muted-foreground italic">No Attachment</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1.5">
                                                    <Button 
                                                        onClick={() => {
                                                            setSelectedPayment(p);
                                                            setIsDetailModalOpen(true);
                                                        }} 
                                                        size="sm" 
                                                        variant="outline"
                                                        className="text-slate-700 font-bold rounded-lg shadow-sm border-slate-200 bg-white hover:bg-slate-50 h-8"
                                                    >
                                                        <Eye className="h-4 w-4 mr-1" /> Detail
                                                    </Button>
                                                    {can.validate ? (
                                                        <>
                                                            <Button 
                                                                onClick={() => handleOpenVerify(p)} 
                                                                size="sm" 
                                                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm h-8"
                                                            >
                                                                <ShieldCheck className="h-4 w-4 mr-1" /> Validasi
                                                            </Button>
                                                            <Button 
                                                                onClick={() => {
                                                                    if (confirm('Yakin ingin menghapus record pembayaran ini?')) {
                                                                        router.delete(route('invoices.payments.destroy', p.id));
                                                                    }
                                                                }} 
                                                                size="sm" 
                                                                variant="destructive"
                                                                className="font-bold rounded-lg shadow-sm h-8"
                                                            >
                                                                <Trash2 className="h-4 w-4 mr-1" /> Hapus
                                                            </Button>
                                                        </>
                                                    ) : (
                                                        <Badge variant="secondary">No Permission</Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Detail Payment Dialog */}
                <Dialog open={isDetailModalOpen} onOpenChange={setIsDetailModalOpen}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle className="text-base font-bold text-slate-800 flex items-center gap-1.5">
                                <Info className="h-5 w-5 text-indigo-600" />
                                Detail Transaksi Pembayaran
                            </DialogTitle>
                            <DialogDescription className="text-xs">Rincian data transaksi pembayaran yang diajukan.</DialogDescription>
                        </DialogHeader>

                        {selectedPayment && (
                            <div className="space-y-4 py-2 text-xs">
                                <div className="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <div>
                                        <span className="text-slate-400 font-bold block uppercase tracking-wider text-[9px]">Nomor PO</span>
                                        <strong className="text-slate-800 text-xs font-mono">{selectedPayment.order?.no_po ?? '—'}</strong>
                                    </div>
                                    <div>
                                        <span className="text-slate-400 font-bold block uppercase tracking-wider text-[9px]">Nama PO</span>
                                        <span className="text-slate-700 font-medium block truncate">{selectedPayment.order?.nama_po ?? '—'}</span>
                                    </div>
                                    <div>
                                        <span className="text-slate-400 font-bold block uppercase tracking-wider text-[9px]">Brand / Divisi</span>
                                        <span className="text-slate-700 font-bold block">{selectedPayment.order?.brand?.nama_brand ?? '—'}</span>
                                    </div>
                                    <div>
                                        <span className="text-slate-400 font-bold block uppercase tracking-wider text-[9px]">Petugas Input</span>
                                        <span className="text-slate-700 font-medium block">{selectedPayment.recorder?.name ?? '—'}</span>
                                    </div>
                                </div>

                                <div className="space-y-2.5">
                                    <div className="flex justify-between items-center py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 font-semibold">Jenis Pembayaran:</span>
                                        <Badge variant="outline" className="text-[10px] font-bold bg-slate-50">
                                            {selectedPayment.master_jenis_pembayaran?.nama === 'Return' || selectedPayment.master_jenis_pembayaran?.nama === 'Refurn'
                                                ? 'Refund'
                                                : (selectedPayment.master_jenis_pembayaran?.nama ?? (selectedPayment.payment_type === 'return' ? 'Refund' : (selectedPayment.payment_type ?? 'Lainnya')))}
                                        </Badge>
                                    </div>

                                    <div className="flex justify-between items-center py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 font-semibold">Tanggal Bayar:</span>
                                        <span className="font-bold text-slate-800">{formatDate(selectedPayment.payment_date)}</span>
                                    </div>

                                    <div className="flex justify-between items-center py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 font-semibold">Nominal Transaksi:</span>
                                        <span className="font-bold text-slate-900 font-mono text-sm">{formatRupiah(selectedPayment.amount)}</span>
                                    </div>

                                    <div className="flex justify-between items-start py-1.5 border-b border-slate-100">
                                        <span className="text-slate-500 font-semibold">Bank Penerima (Rekening Kita):</span>
                                        <div className="text-right">
                                            {selectedPayment.bank ? (
                                                <>
                                                    <span className="font-bold text-slate-800 block">{selectedPayment.bank.bank}</span>
                                                    <span className="text-[10px] text-slate-500 block">{selectedPayment.bank.nomor_rekening} a.n {selectedPayment.bank.atas_nama}</span>
                                                </>
                                            ) : (
                                                <span className="font-mono text-slate-400 italic">CASH / MANUAL</span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Customer Bank Info (Tujuan Transfer Cashback/Return) */}
                                    {(selectedPayment.customer_bank_name || selectedPayment.customer_bank_account) && (
                                        <div className="bg-amber-50/60 border border-amber-100 rounded-xl p-3.5 space-y-1.5">
                                            <span className="text-amber-800 font-bold block uppercase tracking-wider text-[9px]">Rekening Customer (Tujuan Transfer Cashback/Return):</span>
                                            <div className="grid grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <span className="text-slate-500">Bank Customer:</span>
                                                    <span className="font-bold text-slate-800 block">{selectedPayment.customer_bank_name || '—'}</span>
                                                </div>
                                                <div>
                                                    <span className="text-slate-500">Nomor Rekening:</span>
                                                    <span className="font-bold text-slate-800 block font-mono">{selectedPayment.customer_bank_account || '—'}</span>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Notes / Memo */}
                                    <div className="space-y-1 py-1">
                                        <span className="text-slate-500 font-semibold block">Catatan / Memo:</span>
                                        <div className="bg-slate-50 border p-2.5 rounded-lg text-slate-700 min-h-[50px] whitespace-pre-wrap italic">
                                            {selectedPayment.notes || '—'}
                                        </div>
                                    </div>

                                    {/* Proof of Payment Preview */}
                                    {(selectedPayment.proof_file || selectedPayment.receipt_path) && (
                                        <div className="space-y-1.5 pt-1">
                                            <span className="text-slate-500 font-semibold block">Bukti Pembayaran:</span>
                                            <div className="flex items-center justify-center p-2 bg-slate-900 rounded-lg overflow-hidden max-h-[180px] border border-slate-800 cursor-pointer hover:opacity-90 transition-opacity" onClick={() => setImagePreviewUrl(selectedPayment.proof_file || selectedPayment.receipt_path)}>
                                                <img 
                                                    src={`/storage/\${selectedPayment.proof_file || selectedPayment.receipt_path}`} 
                                                    alt="Bukti Transfer" 
                                                    className="max-h-[160px] object-contain rounded" 
                                                />
                                            </div>
                                            <span className="text-[10px] text-slate-400 text-center block font-medium">Klik gambar untuk memperbesar</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        <DialogFooter className="pt-2 gap-2">
                            <Button 
                                variant="outline" 
                                onClick={() => setIsDetailModalOpen(false)}
                                className="rounded-lg text-xs"
                            >
                                Tutup
                            </Button>
                            {selectedPayment && can.validate && (
                                <Button 
                                    onClick={() => {
                                        setIsDetailModalOpen(false);
                                        handleOpenVerify(selectedPayment);
                                    }}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs"
                                >
                                    <ShieldCheck className="h-4 w-4 mr-1" /> Validasi Pembayaran
                                </Button>
                            )}
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Validation Dialog */}
                <Dialog open={isVerifyModalOpen} onOpenChange={setIsVerifyModalOpen}>
                    <DialogContent className="sm:max-w-[450px]">
                        <DialogHeader>
                            <DialogTitle className="text-base font-bold text-slate-800 flex items-center gap-1.5">
                                <ShieldCheck className="h-5 w-5 text-indigo-600" />
                                Rekonsiliasi Pembayaran
                            </DialogTitle>
                            <DialogDescription className="text-xs">Pastikan mutasi dana telah benar-benar masuk ke rekening koran Anda.</DialogDescription>
                        </DialogHeader>

                        {selectedPayment && (
                            <div className="space-y-4 py-2 text-xs">
                                <div className="bg-slate-50 p-4 rounded-xl space-y-2 border">
                                    <div className="flex justify-between">
                                        <span className="text-slate-500 font-semibold">Nomor PO:</span>
                                        <strong className="text-slate-800 font-bold">{selectedPayment.order?.no_po ?? '—'}</strong>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500 font-semibold">Brand / Divisi:</span>
                                        <span className="font-bold text-indigo-700">{selectedPayment.order?.brand?.nama_brand ?? '—'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500 font-semibold">Tanggal Kirim:</span>
                                        <span className="font-bold text-slate-800">{formatDate(selectedPayment.payment_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500 font-semibold">Bank Penerima:</span>
                                        <span className="font-bold text-slate-800">{selectedPayment.bank ? `${selectedPayment.bank.bank} — ${selectedPayment.bank.nomor_rekening}` : 'CASH'}</span>
                                    </div>
                                    <div className="border-t pt-2 flex justify-between items-center mt-2">
                                        <span className="text-slate-600 font-bold">Nominal Pembayaran:</span>
                                        <span className="text-lg font-black text-indigo-700 font-mono">{formatRupiah(selectedPayment.amount)}</span>
                                    </div>
                                </div>

                                {/* Verification Checklist */}
                                <div className="space-y-3 pt-2">
                                    <h4 className="font-bold text-slate-700">Checklist Validasi Keuangan:</h4>
                                    
                                    <label className="flex items-start gap-2.5 p-2 rounded-lg border hover:bg-slate-50 cursor-pointer transition-colors">
                                        <input 
                                            type="checkbox" 
                                            checked={checklist.bankMutasi} 
                                            onChange={(e) => setChecklist({...checklist, bankMutasi: e.target.checked})}
                                            className="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" 
                                        />
                                        <div>
                                            <span className="font-bold text-slate-800 block text-xs">Mutasi Bank Terkonfirmasi</span>
                                            <span className="text-[10px] text-slate-500 block">Dana sebesar {formatRupiah(selectedPayment.amount)} telah tertera di rekening koran mutasi bank terkait.</span>
                                        </div>
                                    </label>

                                    <label className="flex items-start gap-2.5 p-2 rounded-lg border hover:bg-slate-50 cursor-pointer transition-colors">
                                        <input 
                                            type="checkbox" 
                                            checked={checklist.nominalCocok} 
                                            onChange={(e) => setChecklist({...checklist, nominalCocok: e.target.checked})}
                                            className="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" 
                                        />
                                        <div>
                                            <span className="font-bold text-slate-800 block text-xs">Kesesuaian Nilai Nominal</span>
                                            <span className="text-[10px] text-slate-500 block">Jumlah dana yang masuk bernilai sama dengan nominal transaksi yang dicatat.</span>
                                        </div>
                                    </label>

                                    <label className="flex items-start gap-2.5 p-2 rounded-lg border hover:bg-slate-50 cursor-pointer transition-colors">
                                        <input 
                                            type="checkbox" 
                                            checked={checklist.buktiValid} 
                                            onChange={(e) => setChecklist({...checklist, buktiValid: e.target.checked})}
                                            className="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" 
                                        />
                                        <div>
                                            <span className="font-bold text-slate-800 block text-xs">Bukti Transfer Sah</span>
                                            <span className="text-[10px] text-slate-500 block">Bukti transfer yang diunggah valid dan tidak berpotensi duplikasi atau manipulasi.</span>
                                        </div>
                                    </label>

                                    {/* Verification Notes */}
                                    <div className="space-y-1.5 pt-1">
                                        <label className="font-bold text-slate-700 block text-xs">Catatan Verifikasi (Optional):</label>
                                        <textarea
                                            value={verificationNotes}
                                            onChange={(e) => setVerificationNotes(e.target.value)}
                                            placeholder="Masukkan catatan verifikasi atau memo..."
                                            className="w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 min-h-[60px] p-2 bg-white"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        <DialogFooter className="pt-2">
                            <Button 
                                variant="outline" 
                                onClick={() => setIsVerifyModalOpen(false)}
                                className="rounded-lg text-xs"
                            >
                                Batal
                            </Button>
                            <Button 
                                onClick={handleApprove} 
                                disabled={isProcessing || !checklist.bankMutasi || !checklist.nominalCocok || !checklist.buktiValid}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs"
                            >
                                {isProcessing ? 'Memproses...' : 'Setujui Pembayaran'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Bukti Bayar Preview Dialog */}
                <Dialog open={!!imagePreviewUrl} onOpenChange={() => setImagePreviewUrl(null)}>
                    <DialogContent className="max-w-2xl bg-slate-900 border-slate-800 text-white">
                        <DialogHeader>
                            <DialogTitle className="text-sm font-bold text-slate-100 flex items-center justify-between">
                                Bukti Transfer Pelanggan
                            </DialogTitle>
                        </DialogHeader>
                        <div className="flex items-center justify-center p-2 bg-slate-950 rounded-xl border border-slate-800 overflow-hidden min-h-[300px]">
                            {imagePreviewUrl && (
                                <img 
                                    src={`/storage/${imagePreviewUrl}`} 
                                    alt="Bukti Transfer" 
                                    className="max-h-[70vh] object-contain rounded-lg shadow-lg" 
                                />
                            )}
                        </div>
                        <DialogFooter className="sm:justify-center">
                            <Button 
                                variant="outline" 
                                onClick={() => setImagePreviewUrl(null)} 
                                className="bg-slate-800 text-white border-slate-700 hover:bg-slate-800/80 rounded-lg text-xs"
                            >
                                Tutup Preview
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
