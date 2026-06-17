import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { Search, Plus, CheckCircle2, XCircle, Receipt, Copy, Calendar } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_VARIANT = {
    draft: 'outline', pending_review: 'warning', approved: 'info',
    published: 'success', rejected: 'destructive',
};

function CreateRefundDialog({ open, onOpenChange, jenisOptions }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        order_id: '', alasan: '', jenis_masalah: 'produk_cacat',
        jumlah_item: 1, nominal_refund: 0, catatan: '',
    });
    const [orders, setOrders] = useState([]);
    const [searchPO, setSearchPO] = useState('');

    useEffect(() => {
        if (!open) return;
        axios.get(route('orders.index'), { params: { q: searchPO } })
            .catch(() => {});
    }, [open, searchPO]);

    function submit(e) {
        e.preventDefault();
        post(route('refunds.store'), {
            preserveScroll: true,
            onSuccess: () => { reset(); onOpenChange(false); },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Ajukan Refund</DialogTitle>
                        <DialogDescription>
                            Refund untuk PO yang sudah diterbitkan. Akan masuk ke review Admin Keuangan.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-3 py-4">
                        <div>
                            <Label>Nomor PO / Link PO / UUID PO <span className="text-destructive">*</span></Label>
                            <Input value={data.order_id} onChange={(e) => setData('order_id', e.target.value)} className="mt-1.5" placeholder="Contoh: PO-2026-0001 atau link/UUID PO" />
                            <p className="mt-1 text-xs text-muted-foreground">Sistem otomatis mensinkronkan data PO baik dari nomor PO, URL halaman PO, maupun UUID PO.</p>
                            {errors.order_id && <p className="text-xs text-destructive">{errors.order_id}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>Jenis Masalah</Label>
                                <Select value={data.jenis_masalah} onValueChange={(v) => setData('jenis_masalah', v)}>
                                    <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {jenisOptions.map((j) => (<SelectItem key={j} value={j}>{j.replace(/_/g, ' ')}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Jumlah Item</Label>
                                <Input type="number" min={1} value={data.jumlah_item} onChange={(e) => setData('jumlah_item', Number(e.target.value))} className="mt-1.5" />
                            </div>
                        </div>
                        <div>
                            <Label>Nominal Refund <span className="text-destructive">*</span></Label>
                            <Input type="number" min={0} value={data.nominal_refund} onChange={(e) => setData('nominal_refund', Number(e.target.value))} className="mt-1.5" />
                            {data.nominal_refund > 0 && (
                                <p className="mt-1 text-xs text-rose-600 font-semibold font-mono">
                                    Format: {formatRupiah(data.nominal_refund)}
                                </p>
                            )}
                            {errors.nominal_refund && <p className="text-xs text-destructive mt-1">{errors.nominal_refund}</p>}
                        </div>
                        <div>
                            <Label>Alasan <span className="text-destructive">*</span></Label>
                            <Textarea value={data.alasan} onChange={(e) => setData('alasan', e.target.value)} rows={2} className="mt-1.5" />
                            {errors.alasan && <p className="text-xs text-destructive">{errors.alasan}</p>}
                        </div>
                        <div>
                            <Label>Catatan</Label>
                            <Textarea value={data.catatan} onChange={(e) => setData('catatan', e.target.value)} rows={2} className="mt-1.5" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                        <Button type="submit" disabled={processing}>Ajukan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function RejectDialog({ refund, open, onOpenChange }) {
    const { data, setData, post, processing, errors, reset } = useForm({ rejected_reason: '' });
    function submit(e) {
        e.preventDefault();
        post(route('refunds.reject', refund.id), {
            preserveScroll: true,
            onSuccess: () => { reset(); onOpenChange(false); },
        });
    }
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit}>
                    <DialogHeader>
                        <DialogTitle>Tolak Refund {refund?.refund_number}</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <Label>Alasan Penolakan <span className="text-destructive">*</span></Label>
                        <Textarea value={data.rejected_reason} onChange={(e) => setData('rejected_reason', e.target.value)} rows={3} className="mt-1.5" />
                        {errors.rejected_reason && <p className="mt-1 text-xs text-destructive">{errors.rejected_reason}</p>}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                        <Button type="submit" variant="destructive" disabled={processing}>Tolak</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DetailRefundDialog({ refund, open, onOpenChange, can, onPublish, onReject }) {
    if (!refund) return null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <div className="flex items-center justify-between border-b pb-2">
                        <div>
                            <DialogTitle className="text-lg font-bold font-mono">{refund.refund_number}</DialogTitle>
                            <DialogDescription className="text-xs text-muted-foreground mt-0.5">
                                Diajukan oleh: <span className="font-medium text-foreground">{refund.creator?.name || '—'}</span> &bull; {formatDate(refund.created_at)}
                            </DialogDescription>
                        </div>
                        <Badge variant={STATUS_VARIANT[refund.status] ?? 'outline'} className="text-xs px-2.5 py-0.5 uppercase">
                            {refund.status}
                        </Badge>
                    </div>
                </DialogHeader>

                <div className="py-4 space-y-5 max-h-[65vh] overflow-y-auto pr-1">
                    {/* Main details grid */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Jenis Masalah</span>
                            <span className="text-sm font-medium border px-2 py-1 rounded bg-slate-50 inline-block capitalize">
                                {refund.jenis_masalah?.replace(/_/g, ' ')}
                            </span>
                        </div>
                        <div className="space-y-1">
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Jumlah Item</span>
                            <span className="text-sm font-semibold text-slate-700">{refund.jumlah_item} pcs</span>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4 border-t pt-3">
                        <div className="space-y-1">
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Nominal Refund</span>
                            <span className="text-base font-bold text-rose-600 font-mono">{formatRupiah(refund.nominal_refund)}</span>
                        </div>
                        <div className="space-y-1">
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Brand</span>
                            <span className="text-sm font-semibold text-slate-700">{refund.brand?.nama_brand || refund.order?.brand?.nama_brand || '—'}</span>
                        </div>
                    </div>

                    <div className="border-t pt-3 space-y-1.5">
                        <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Alasan Refund</span>
                        <p className="text-sm text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-100 whitespace-pre-wrap">{refund.alasan}</p>
                    </div>

                    {refund.catatan && (
                        <div className="border-t pt-3 space-y-1.5">
                            <span className="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Catatan Tambahan</span>
                            <p className="text-sm text-slate-600 bg-slate-50/50 p-2.5 rounded-lg border border-slate-100 whitespace-pre-wrap">{refund.catatan}</p>
                        </div>
                    )}

                    {/* Order Information section */}
                    <div className="border-t pt-4">
                        <h4 className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Informasi PO / Pesanan</h4>
                        <div className="bg-slate-50 rounded-xl p-3.5 border border-slate-200/60 grid grid-cols-2 gap-4">
                            <div>
                                <span className="text-xs text-slate-500 block">Nomor PO</span>
                                <span className="text-sm font-mono font-semibold text-primary">{refund.order?.no_po || '—'}</span>
                            </div>
                            <div>
                                <span className="text-xs text-slate-500 block">Nama Pelanggan</span>
                                <span className="text-sm font-medium text-slate-800">{refund.order?.pelanggan?.nama || '—'}</span>
                            </div>
                            <div className="col-span-2 border-t pt-2 mt-1 flex justify-between items-center">
                                <div>
                                    <span className="text-xs text-slate-500 block">Total Tagihan PO</span>
                                    <span className="text-sm font-semibold font-mono text-slate-700">{formatRupiah(refund.order?.total_tagihan)}</span>
                                </div>
                                {refund.order?.is_special_order && (
                                    <Badge variant="outline" className="text-amber-600 bg-amber-50 border-amber-200 text-xs">
                                        Special Order
                                    </Badge>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Review Logs if rejected/published */}
                    {(refund.reviewed_by || refund.published_by || refund.status === 'rejected') && (
                        <div className="border-t pt-4">
                            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Audit & Review Log</h4>
                            <div className="text-xs space-y-2 bg-slate-50 p-3 rounded-lg border">
                                {refund.status === 'rejected' && (
                                    <div className="p-2.5 bg-rose-50 border border-rose-100 rounded-md text-rose-800 space-y-1">
                                        <div className="font-semibold flex items-center gap-1">
                                            <XCircle className="h-3.5 w-3.5" /> Ditolak oleh Finance
                                        </div>
                                        {refund.rejected_reason && <p className="font-normal italic text-xs">Alasan: "{refund.rejected_reason}"</p>}
                                        {refund.reviewed_by && (
                                            <p className="text-[10px] text-rose-600 font-medium">
                                                Oleh: {refund.reviewer?.name || 'Sistem'} &bull; {formatDate(refund.reviewed_at)}
                                            </p>
                                        )}
                                    </div>
                                )}
                                {refund.status === 'published' && (
                                    <div className="p-2.5 bg-emerald-50 border border-emerald-100 rounded-md text-emerald-800 space-y-1">
                                        <div className="font-semibold flex items-center gap-1">
                                            <CheckCircle2 className="h-3.5 w-3.5" /> Berhasil Diterbitkan
                                        </div>
                                        {refund.published_by && (
                                            <p className="text-[10px] text-emerald-600 font-medium">
                                                Oleh: {refund.publisher?.name || 'Sistem'} &bull; {formatDate(refund.published_at)}
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                <DialogFooter className="border-t pt-3">
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Tutup</Button>
                    {can?.review && refund.status === 'pending_review' && (
                        <div className="flex gap-2">
                            <Button type="button" className="bg-emerald-600 hover:bg-emerald-700 text-white" onClick={() => { onOpenChange(false); onPublish(refund); }}>
                                <CheckCircle2 className="h-4 w-4 mr-1" /> Terbitkan
                            </Button>
                            <Button type="button" variant="destructive" onClick={() => { onOpenChange(false); onReject(refund); }}>
                                <XCircle className="h-4 w-4 mr-1" /> Tolak
                            </Button>
                        </div>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function RefundIndex({ refunds, all_filtered_refunds, brands, filters, statuses, jenis_options: jenisOptions, can }) {
    const [search, setSearch] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');
    const [brandId, setBrandId] = useState(filters?.brand_id ?? 'all');
    const [startDate, setStartDate] = useState(filters?.start_date ?? '');
    const [endDate, setEndDate] = useState(filters?.end_date ?? '');
    const [copied, setCopied] = useState(false);
    const [createOpen, setCreateOpen] = useState(false);
    const [rejecting, setRejecting] = useState(null);
    const [selectedRefund, setSelectedRefund] = useState(null);
    const [showDatePanel, setShowDatePanel] = useState(!!(filters?.start_date || filters?.end_date));

    function applyFilters(overrides = {}) {
        router.get(route('refunds.index'), {
            q: overrides.hasOwnProperty('q') ? overrides.q : search,
            status: (overrides.hasOwnProperty('status') ? overrides.status : status) === 'all' ? '' : (overrides.hasOwnProperty('status') ? overrides.status : status),
            brand_id: overrides.hasOwnProperty('brand_id') ? overrides.brand_id : brandId,
            start_date: overrides.hasOwnProperty('start_date') ? overrides.start_date : startDate,
            end_date: overrides.hasOwnProperty('end_date') ? overrides.end_date : endDate,
        }, { preserveScroll: true, preserveState: true });
    }

    const calculateDateRange = (preset) => {
        const today = new Date();
        const formatDateStr = (date) => {
            const offset = date.getTimezoneOffset();
            const localDate = new Date(date.getTime() - (offset * 60 * 1000));
            return localDate.toISOString().split('T')[0];
        };

        switch (preset) {
            case 'today':
                return { start: formatDateStr(today), end: formatDateStr(today) };
            case 'yesterday': {
                const yesterday = new Date(today);
                yesterday.setDate(today.getDate() - 1);
                return { start: formatDateStr(yesterday), end: formatDateStr(yesterday) };
            }
            case 'last_7': {
                const last7 = new Date(today);
                last7.setDate(today.getDate() - 6);
                return { start: formatDateStr(last7), end: formatDateStr(today) };
            }
            case 'last_30': {
                const last30 = new Date(today);
                last30.setDate(today.getDate() - 29);
                return { start: formatDateStr(last30), end: formatDateStr(today) };
            }
            case 'this_month': {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                return { start: formatDateStr(firstDay), end: formatDateStr(today) };
            }
            default:
                return { start: '', end: '' };
        }
    };

    const getActivePreset = () => {
        if (!startDate && !endDate) return 'all';

        const todayStr = new Date().toLocaleDateString('sv');
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toLocaleDateString('sv');

        if (startDate === todayStr && endDate === todayStr) return 'today';
        if (startDate === yesterdayStr && endDate === yesterdayStr) return 'yesterday';

        const last7 = new Date();
        last7.setDate(last7.getDate() - 6);
        if (startDate === last7.toLocaleDateString('sv') && endDate === todayStr) return 'last_7';

        const last30 = new Date();
        last30.setDate(last30.getDate() - 29);
        if (startDate === last30.toLocaleDateString('sv') && endDate === todayStr) return 'last_30';

        const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        if (startDate === firstDay.toLocaleDateString('sv') && endDate === todayStr) return 'this_month';

        return 'custom';
    };

    const handlePresetClick = (preset) => {
        const range = calculateDateRange(preset);
        setStartDate(range.start);
        setEndDate(range.end);
        applyFilters({ start_date: range.start, end_date: range.end });
    };

    const getDateFilterLabel = () => {
        if (!startDate && !endDate) return "Filter Tanggal";
        const active = getActivePreset();
        if (active === 'today') return "Hari Ini";
        if (active === 'yesterday') return "Kemarin";
        if (active === 'last_7') return "7 Hari Terakhir";
        if (active === 'last_30') return "30 Hari Terakhir";
        if (active === 'this_month') return "Bulan Ini";
        
        return `${startDate ? formatDate(startDate) : ''} - ${endDate ? formatDate(endDate) : ''}`;
    };

    const copyToClipboard = () => {
        const headers = ['No Refund', 'No PO', 'Jenis Masalah', 'Nominal Refund', 'Diajukan Pada', 'Status'];
        const rows = (all_filtered_refunds || []).map(ref => {
            return [
                ref.refund_number || '',
                ref.no_po || '',
                ref.jenis_masalah || '',
                ref.nominal_refund || '0',
                ref.created_at || '',
                ref.status || ''
            ].join('\t');
        });

        const tsvContent = [headers.join('\t'), ...rows].join('\n');

        navigator.clipboard.writeText(tsvContent).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    function publish(refund) {
        if (!confirm(`Terbitkan refund ${refund.refund_number}? Nominal akan jadi pengurangan pemasukan.`)) return;
        router.post(route('refunds.publish', refund.id), {}, { preserveScroll: true });
    }

    return (
        <AppLayout title="Refund Management">
            <Head title="Refund" />

            <div className="space-y-5">
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2 text-xl font-semibold">
                                <Receipt className="h-5 w-5 text-primary" /> Daftar Refund
                            </div>
                            <p className="text-sm text-muted-foreground">Pengajuan refund untuk PO bermasalah. Diterbitkan oleh Admin Keuangan.</p>
                        </div>
                        {can?.create && (
                            <Button onClick={() => setCreateOpen(true)}><Plus className="h-4 w-4" /> Ajukan Refund</Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        <div className="mb-6 space-y-4 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                                {/* Search */}
                                <div className="relative">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input 
                                        placeholder="Cari no refund..." 
                                        value={search} 
                                        onChange={(e) => setSearch(e.target.value)} 
                                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()} 
                                        className="pl-9 bg-white" 
                                    />
                                </div>

                                {/* Brand Filter */}
                                <Select value={brandId} onValueChange={(v) => { setBrandId(v); applyFilters({ brand_id: v }); }}>
                                    <SelectTrigger className="bg-white"><SelectValue placeholder="Pilih Brand" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Brand</SelectItem>
                                        {(brands || []).map((b) => (
                                            <SelectItem key={b.id} value={b.id}>{b.nama_brand} ({b.kode})</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                {/* Status Filter */}
                                <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                    <SelectTrigger className="bg-white"><SelectValue placeholder="Status" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        {statuses.map((s) => (<SelectItem key={s} value={s}>{s.toUpperCase()}</SelectItem>))}
                                    </SelectContent>
                                </Select>

                                {/* Collapsible Date Toggle */}
                                <Button 
                                    type="button" 
                                    variant={showDatePanel || startDate || endDate ? "secondary" : "outline"} 
                                    onClick={() => setShowDatePanel(!showDatePanel)}
                                    className="bg-white hover:bg-slate-50 border flex justify-between items-center text-slate-700 font-medium w-full"
                                >
                                    <span className="flex items-center gap-2">
                                        <Calendar className="h-4 w-4 text-slate-500" />
                                        {getDateFilterLabel()}
                                    </span>
                                </Button>

                                {/* Action Buttons (Terapkan & Reset) */}
                                <div className="flex gap-2">
                                    <Button className="flex-1" onClick={() => applyFilters()}>Filter</Button>
                                    <Button variant="outline" onClick={() => {
                                        setSearch('');
                                        setStatus('all');
                                        setBrandId('all');
                                        setStartDate('');
                                        setEndDate('');
                                        setShowDatePanel(false);
                                        router.get(route('refunds.index'), {}, { preserveScroll: true });
                                    }}>Reset</Button>
                                </div>
                            </div>

                            {/* Collapsible Date Filter Panel */}
                            {showDatePanel && (
                                <div className="p-4 bg-white rounded-lg border border-slate-200/60 mt-2 space-y-4 animate-in fade-in slide-in-from-top-2 duration-200">
                                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div>
                                            <span className="text-xs font-semibold text-slate-500 block mb-2">Pilih Cepat Rentang Tanggal</span>
                                            <div className="flex flex-wrap gap-1.5">
                                                {[
                                                    { label: 'Hari Ini', preset: 'today' },
                                                    { label: 'Kemarin', preset: 'yesterday' },
                                                    { label: '7 Hari Terakhir', preset: 'last_7' },
                                                    { label: '30 Hari Terakhir', preset: 'last_30' },
                                                    { label: 'Bulan Ini', preset: 'this_month' },
                                                    { label: 'Semua', preset: 'all' },
                                                ].map((opt) => (
                                                    <button
                                                        key={opt.preset}
                                                        type="button"
                                                        onClick={() => handlePresetClick(opt.preset)}
                                                        className={`text-xs px-3 py-1.5 rounded-full border transition font-medium ${
                                                            getActivePreset() === opt.preset
                                                                ? 'bg-primary text-white border-primary shadow-sm'
                                                                : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200'
                                                        }`}
                                                    >
                                                        {opt.label}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                        
                                        <div className="border-t md:border-t-0 md:border-l border-dashed border-slate-200 pt-3 md:pt-0 md:pl-6">
                                            <span className="text-xs font-semibold text-slate-500 block mb-2">Pilih Tanggal Manual</span>
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="date"
                                                    value={startDate}
                                                    onChange={(e) => { setStartDate(e.target.value); applyFilters({ start_date: e.target.value }); }}
                                                    className="text-xs border rounded-md px-3 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                                />
                                                <span className="text-slate-400 text-xs">s/d</span>
                                                <input
                                                    type="date"
                                                    value={endDate}
                                                    onChange={(e) => { setEndDate(e.target.value); applyFilters({ end_date: e.target.value }); }}
                                                    className="text-xs border rounded-md px-3 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="flex flex-col lg:flex-row items-center justify-between gap-4 pt-3 border-t border-slate-100">
                                {/* Active Filters indicator */}
                                <div className="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground w-full lg:w-auto">
                                    {(search || (status && status !== 'all') || (brandId && brandId !== 'all') || startDate || endDate) ? (
                                        <>
                                            <span className="font-semibold text-slate-500 mr-1">Filter Aktif:</span>
                                            {search && <Badge variant="secondary" className="px-2 py-0.5">{search}</Badge>}
                                            {brandId && brandId !== 'all' && (
                                                <Badge variant="secondary" className="px-2 py-0.5">
                                                    {brands.find(b => String(b.id) === String(brandId))?.nama_brand ?? 'Brand'}
                                                </Badge>
                                            )}
                                            {status && status !== 'all' && <Badge variant="secondary" className="px-2 py-0.5">{status.toUpperCase()}</Badge>}
                                            {(startDate || endDate) && <Badge variant="secondary" className="px-2 py-0.5">{getDateFilterLabel()}</Badge>}
                                        </>
                                    ) : (
                                        <span className="text-slate-400">Menampilkan semua data tanpa filter.</span>
                                    )}
                                </div>

                                {/* Export to Excel Button */}
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={copyToClipboard}
                                    disabled={!all_filtered_refunds || all_filtered_refunds.length === 0}
                                    className="w-full lg:w-auto flex items-center justify-center gap-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 hover:text-emerald-800"
                                >
                                    {copied ? (
                                        <>
                                            <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" />
                                            Berhasil Disalin ke Excel
                                        </>
                                    ) : (
                                        <>
                                            <Copy className="h-3.5 w-3.5" />
                                            Salin {all_filtered_refunds?.length || 0} Data Refund (Excel)
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. Refund</TableHead>
                                        <TableHead>No. PO</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead className="text-right">Nominal</TableHead>
                                        <TableHead>Diajukan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {refunds.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={7} className="py-8 text-center text-sm text-muted-foreground">
                                                Belum ada refund.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {refunds.data.map((r) => (
                                        <TableRow key={r.id} className="cursor-pointer hover:bg-slate-50/50" onClick={() => setSelectedRefund(r)}>
                                            <TableCell className="font-mono text-xs font-semibold">{r.refund_number}</TableCell>
                                            <TableCell className="font-mono text-xs">{r.order?.no_po}</TableCell>
                                            <TableCell><Badge variant="outline" className="capitalize">{r.jenis_masalah?.replace(/_/g, ' ')}</Badge></TableCell>
                                            <TableCell className="text-right font-mono text-xs font-bold text-slate-800">{formatRupiah(r.nominal_refund)}</TableCell>
                                            <TableCell className="text-xs">
                                                {r.creator?.name}<br />
                                                <span className="text-muted-foreground">{formatDate(r.created_at)}</span>
                                            </TableCell>
                                            <TableCell><Badge variant={STATUS_VARIANT[r.status] ?? 'outline'}>{r.status}</Badge></TableCell>
                                            <TableCell className="text-right" onClick={(e) => e.stopPropagation()}>
                                                <div className="flex justify-end gap-1.5">
                                                    <Button size="sm" variant="outline" onClick={() => setSelectedRefund(r)}>
                                                        Detail
                                                    </Button>
                                                    {can?.review && r.status === 'pending_review' && (
                                                        <>
                                                            <Button size="sm" variant="outline" className="text-emerald-600 border-emerald-200 hover:bg-emerald-50" onClick={() => publish(r)}>
                                                                <CheckCircle2 className="h-3.5 w-3.5" /> Terbitkan
                                                            </Button>
                                                            <Button size="sm" variant="outline" className="text-destructive border-destructive/20 hover:bg-destructive/5" onClick={() => setRejecting(r)}>
                                                                <XCircle className="h-3.5 w-3.5" /> Tolak
                                                            </Button>
                                                        </>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <CreateRefundDialog open={createOpen} onOpenChange={setCreateOpen} jenisOptions={jenisOptions} />
            {rejecting && (
                <RejectDialog key={rejecting.id} refund={rejecting} open={!!rejecting} onOpenChange={(v) => !v && setRejecting(null)} />
            )}
            <DetailRefundDialog 
                refund={selectedRefund} 
                open={!!selectedRefund} 
                onOpenChange={(v) => !v && setSelectedRefund(null)} 
                can={can} 
                onPublish={publish} 
                onReject={setRejecting} 
            />
        </AppLayout>
    );
}
