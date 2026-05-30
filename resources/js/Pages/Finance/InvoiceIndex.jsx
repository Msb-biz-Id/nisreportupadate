import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { 
    Search, Receipt, CheckCircle2, ExternalLink, Copy, Calendar, 
    ShieldCheck, Clock, Banknote, AlertTriangle, Plus, Sparkles, 
    Layers, RefreshCw, FileText, ChevronRight, CheckCircle, HelpCircle, XCircle 
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_VARIANT = {
    draft: 'outline', validated: 'info', published: 'success',
    sent: 'info', paid: 'success', overdue: 'destructive', cancel: 'secondary',
};

const DEPOSIT_STATUS_VARIANT = {
    pending: 'warning',
    verified: 'info',
    converted: 'success',
    refunded: 'destructive',
    expired: 'secondary'
};

export default function InvoiceIndex({ 
    invoices, 
    all_filtered_invoices, 
    brands, 
    pending_payments, 
    design_deposits = [], 
    available_orders = [], 
    bank_accounts = [], 
    filters, 
    statuses, 
    can 
}) {
    const [activeTab, setActiveTab] = useState('belum_lunas');
    
    // Payment Verification Dialogs
    const [confirmPayment, setConfirmPayment] = useState(null);
    const [verifying, setVerifying] = useState(false);

    // Tanda Jadi (Design Deposit) Modals
    const [showAddDeposit, setShowAddDeposit] = useState(false);
    const [confirmDepositVerify, setConfirmDepositVerify] = useState(null);
    const [confirmDepositRefund, setConfirmDepositRefund] = useState(null);
    const [convertDeposit, setConvertDeposit] = useState(null);
    const [selectedOrderId, setSelectedOrderId] = useState('');

    // Design Deposit Form State
    const [newDeposit, setNewDeposit] = useState({
        brand_id: brands[0]?.id || '',
        customer_name: '',
        description: '',
        amount: '',
        payment_date: new Date().toISOString().split('T')[0],
        bank_id: bank_accounts[0]?.id || '',
        notes: ''
    });

    const [search, setSearch] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');
    const [brandId, setBrandId] = useState(filters?.brand_id ?? 'all');
    const [startDate, setStartDate] = useState(filters?.start_date ?? '');
    const [endDate, setEndDate] = useState(filters?.end_date ?? '');
    const [copied, setCopied] = useState(false);
    const [showDatePanel, setShowDatePanel] = useState(!!(filters?.start_date || filters?.end_date));

    // Calculate Invoices tab segments
    const unpaidInvoices = (invoices.data || []).filter(inv => inv.status !== 'paid' && Number(inv.sisa_pembayaran) > 0);
    const paidInvoices = (invoices.data || []).filter(inv => inv.status === 'paid' || Number(inv.sisa_pembayaran) === 0);

    function verifyPayment(payment) {
        setVerifying(true);
        router.post(route('invoices.payments.verify', payment.id), {}, {
            preserveScroll: true,
            onFinish: () => { setVerifying(false); setConfirmPayment(null); },
        });
    }

    function applyFilters(overrides = {}) {
        router.get(route('invoices.index'), {
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
        const headers = ['No Invoice', 'No PO', 'Pelanggan', 'Tanggal Terbit', 'Total Tagihan', 'Sisa Pembayaran', 'Status'];
        const rows = (all_filtered_invoices || []).map(inv => [
            inv.invoice_number || '',
            inv.no_po || '',
            inv.pelanggan || '',
            inv.tanggal_terbit || '',
            inv.total_tagihan || '0',
            inv.sisa_pembayaran || '0',
            inv.status || ''
        ].join('\t'));

        const tsvContent = [headers.join('\t'), ...rows].join('\n');
        navigator.clipboard.writeText(tsvContent).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    // Design Deposit Action handlers
    function handleAddDeposit(e) {
        e.preventDefault();
        router.post(route('design-deposits.store'), newDeposit, {
            onSuccess: () => {
                setShowAddDeposit(false);
                setNewDeposit({
                    brand_id: brands[0]?.id || '',
                    customer_name: '',
                    description: '',
                    amount: '',
                    payment_date: new Date().toISOString().split('T')[0],
                    bank_id: bank_accounts[0]?.id || '',
                    notes: ''
                });
            }
        });
    }

    function verifyDeposit(deposit) {
        router.post(route('design-deposits.verify', deposit.id), {}, {
            onSuccess: () => setConfirmDepositVerify(null)
        });
    }

    function refundDeposit(deposit) {
        router.post(route('design-deposits.refund', deposit.id), {}, {
            onSuccess: () => setConfirmDepositRefund(null)
        });
    }

    function handleConvertDeposit(e) {
        e.preventDefault();
        if (!selectedOrderId) return;
        router.post(route('design-deposits.convert', convertDeposit.id), {
            order_id: selectedOrderId
        }, {
            onSuccess: () => {
                setConvertDeposit(null);
                setSelectedOrderId('');
            }
        });
    }

    return (
        <AppLayout title="Invoice Management">
            <Head title="Invoice & Keuangan" />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="relative overflow-hidden bg-gradient-to-r from-blue-700 via-indigo-700 to-indigo-800 p-8 rounded-2xl shadow-xl text-white">
                    <div className="absolute top-0 right-0 p-4 opacity-15">
                        <Sparkles className="h-40 w-40" />
                    </div>
                    <div className="relative z-10 space-y-2">
                        <span className="bg-white/20 text-white text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full backdrop-blur-sm">
                            Financial Center
                        </span>
                        <h1 className="text-3xl font-extrabold tracking-tight">Invoice, Payment Ledger & Tanda Jadi</h1>
                        <p className="text-indigo-100 max-w-2xl text-sm leading-relaxed">
                            Konsolidasi seluruh transaksi PO (DP, Pelunasan, Return, Ongkir, Cashback, Tambahan Produk) 
                            serta pengelolaan Tanda Jadi Desain (Design Deposit) sebelum PO terbit.
                        </p>
                    </div>
                </div>

                {/* Dashboard Key Metrics */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card className="border-l-4 border-l-orange-500 bg-white/70 backdrop-blur-md shadow-sm">
                        <CardContent className="pt-6">
                            <div className="flex justify-between items-start">
                                <div className="space-y-1">
                                    <span className="text-xs text-muted-foreground font-semibold block uppercase">Belum Lunas</span>
                                    <h3 className="text-2xl font-bold text-slate-800">
                                        {formatRupiah(all_filtered_invoices?.reduce((s, x) => s + (x.status !== 'paid' ? Number(x.sisa_pembayaran) : 0), 0) || 0)}
                                    </h3>
                                </div>
                                <div className="h-8 w-8 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600">
                                    <Clock className="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-emerald-500 bg-white/70 backdrop-blur-md shadow-sm">
                        <CardContent className="pt-6">
                            <div className="flex justify-between items-start">
                                <div className="space-y-1">
                                    <span className="text-xs text-muted-foreground font-semibold block uppercase">Sudah Lunas</span>
                                    <h3 className="text-2xl font-bold text-slate-800">
                                        {formatRupiah(all_filtered_invoices?.reduce((s, x) => s + (x.status === 'paid' ? Number(x.total_tagihan) : 0), 0) || 0)}
                                    </h3>
                                </div>
                                <div className="h-8 w-8 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600">
                                    <CheckCircle2 className="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-blue-500 bg-white/70 backdrop-blur-md shadow-sm">
                        <CardContent className="pt-6">
                            <div className="flex justify-between items-start">
                                <div className="space-y-1">
                                    <span className="text-xs text-muted-foreground font-semibold block uppercase">Tanda Jadi (Desain)</span>
                                    <h3 className="text-2xl font-bold text-slate-800">
                                        {formatRupiah(design_deposits.reduce((s, x) => s + (['pending', 'verified'].includes(x.status) ? Number(x.amount) : 0), 0))}
                                    </h3>
                                </div>
                                <div className="h-8 w-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                                    <Banknote className="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-l-4 border-l-amber-500 bg-white/70 backdrop-blur-md shadow-sm">
                        <CardContent className="pt-6">
                            <div className="flex justify-between items-start">
                                <div className="space-y-1">
                                    <span className="text-xs text-muted-foreground font-semibold block uppercase">Pending Validation</span>
                                    <h3 className="text-2xl font-bold text-slate-800">
                                        {formatRupiah(pending_payments?.reduce((s, x) => s + Number(x.amount), 0) || 0)}
                                    </h3>
                                </div>
                                <div className="h-8 w-8 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600">
                                    <AlertTriangle className="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter and Tab Panel */}
                <div className="flex flex-col gap-4 bg-white p-5 rounded-2xl border shadow-sm">
                    {/* Header bar: Filter & Action */}
                    <div className="flex flex-col lg:flex-row justify-between lg:items-center gap-4">
                        {/* Tabs */}
                        <div className="flex space-x-1 p-1 bg-slate-100 rounded-xl max-w-md">
                            <button
                                onClick={() => setActiveTab('belum_lunas')}
                                className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-lg transition-all ${
                                    activeTab === 'belum_lunas' 
                                        ? 'bg-white text-indigo-700 shadow-sm' 
                                        : 'text-slate-600 hover:text-slate-800'
                                }`}
                            >
                                <Clock className="h-3.5 w-3.5" />
                                Belum Lunas ({unpaidInvoices.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('sudah_lunas')}
                                className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-lg transition-all ${
                                    activeTab === 'sudah_lunas' 
                                        ? 'bg-white text-indigo-700 shadow-sm' 
                                        : 'text-slate-600 hover:text-slate-800'
                                }`}
                            >
                                <CheckCircle className="h-3.5 w-3.5" />
                                Sudah Lunas ({paidInvoices.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('tanda_jadi')}
                                className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-lg transition-all ${
                                    activeTab === 'tanda_jadi' 
                                        ? 'bg-white text-indigo-700 shadow-sm' 
                                        : 'text-slate-600 hover:text-slate-800'
                                }`}
                            >
                                <Layers className="h-3.5 w-3.5" />
                                Tanda Jadi ({design_deposits.length})
                            </button>
                        </div>

                        {/* Tanda Jadi new entry button */}
                        {activeTab === 'tanda_jadi' && (
                            <Button 
                                onClick={() => setShowAddDeposit(true)}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-md gap-1.5 self-end lg:self-auto"
                            >
                                <Plus className="h-4 w-4" />
                                Catat Tanda Jadi
                            </Button>
                        )}
                    </div>

                    {/* Filter Inputs Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 border-t pt-4">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input 
                                placeholder="Cari..." 
                                value={search} 
                                onChange={(e) => setSearch(e.target.value)} 
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()} 
                                className="pl-9 bg-slate-50 border-slate-200" 
                            />
                        </div>

                        <Select value={brandId} onValueChange={(v) => { setBrandId(v); applyFilters({ brand_id: v }); }}>
                            <SelectTrigger className="bg-slate-50 border-slate-200"><SelectValue placeholder="Pilih Brand" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Brand</SelectItem>
                                {(brands || []).map((b) => (
                                    <SelectItem key={b.id} value={b.id}>{b.nama_brand} ({b.kode})</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {activeTab !== 'tanda_jadi' && (
                            <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                <SelectTrigger className="bg-slate-50 border-slate-200"><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    {statuses.map((s) => (<SelectItem key={s} value={s}>{s.toUpperCase()}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        )}

                        <Button 
                            type="button" 
                            variant={showDatePanel || startDate || endDate ? "secondary" : "outline"} 
                            onClick={() => setShowDatePanel(!showDatePanel)}
                            className="bg-slate-50 hover:bg-slate-100 border flex justify-between items-center text-slate-700 font-medium w-full text-xs"
                        >
                            <span className="flex items-center gap-2">
                                <Calendar className="h-4 w-4 text-slate-500" />
                                {getDateFilterLabel()}
                            </span>
                        </Button>

                        <div className="flex gap-2">
                            <Button className="flex-1 text-xs" onClick={() => applyFilters()}>Filter</Button>
                            <Button variant="outline" className="text-xs" onClick={() => {
                                setSearch('');
                                setStatus('all');
                                setBrandId('all');
                                setStartDate('');
                                setEndDate('');
                                setShowDatePanel(false);
                                router.get(route('invoices.index'), {}, { preserveScroll: true });
                            }}>Reset</Button>
                        </div>
                    </div>

                    {/* Date Collapsible */}
                    {showDatePanel && (
                        <div className="p-4 bg-slate-50/50 rounded-xl border border-slate-200/60 space-y-4 animate-in fade-in slide-in-from-top-2 duration-200">
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
                                                        : 'bg-white hover:bg-slate-100 text-slate-600 border-slate-200'
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
                </div>

                {/* Tabs Content */}
                <Card className="border border-slate-150 shadow-sm overflow-hidden">
                    <CardContent className="p-0">
                        {activeTab === 'belum_lunas' && (
                            <Table>
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="font-semibold">No. Invoice</TableHead>
                                        <TableHead className="font-semibold">No. PO</TableHead>
                                        <TableHead className="font-semibold">Pelanggan</TableHead>
                                        <TableHead className="font-semibold">Tgl Terbit</TableHead>
                                        <TableHead className="font-semibold text-right">Total Tagihan</TableHead>
                                        <TableHead className="font-semibold text-right text-orange-600">Sisa Pembayaran</TableHead>
                                        <TableHead className="font-semibold">Status</TableHead>
                                        <TableHead className="font-semibold text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {unpaidInvoices.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-12 text-center text-sm text-muted-foreground italic">
                                                Tidak ada invoice belum lunas.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        unpaidInvoices.map((iv) => (
                                            <TableRow key={iv.id} className="hover:bg-slate-50/50">
                                                <TableCell className="font-mono text-xs font-semibold text-slate-800">{iv.invoice_number}</TableCell>
                                                <TableCell className="font-mono text-xs text-slate-500">{iv.order?.no_po}</TableCell>
                                                <TableCell className="font-medium">{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                                <TableCell className="text-xs text-slate-500">{formatDate(iv.tanggal_terbit)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-semibold">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-bold text-orange-600">{formatRupiah(iv.sisa_pembayaran)}</TableCell>
                                                <TableCell><Badge variant={STATUS_VARIANT[iv.status] ?? 'outline'}>{iv.status}</Badge></TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1.5">
                                                        <Button asChild size="xs" variant="outline" className="text-xs py-1">
                                                            <a href={route('invoice.public', iv.invoice_number)} target="_blank" rel="noopener noreferrer">
                                                                <ExternalLink className="h-3 w-3 mr-1" /> Detail
                                                            </a>
                                                        </Button>
                                                        {can?.publish && iv.status === 'draft' && (
                                                            <Button size="xs" className="bg-indigo-600 text-white hover:bg-indigo-700 text-xs py-1" onClick={() => publish(iv)}>
                                                                Publish
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        )}

                        {activeTab === 'sudah_lunas' && (
                            <Table>
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="font-semibold">No. Invoice</TableHead>
                                        <TableHead className="font-semibold">No. PO</TableHead>
                                        <TableHead className="font-semibold">Pelanggan</TableHead>
                                        <TableHead className="font-semibold">Tgl Terbit</TableHead>
                                        <TableHead className="font-semibold text-right">Total Tagihan</TableHead>
                                        <TableHead className="font-semibold text-right text-emerald-600">Total Terbayar</TableHead>
                                        <TableHead className="font-semibold">Status</TableHead>
                                        <TableHead className="font-semibold text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {paidInvoices.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-12 text-center text-sm text-muted-foreground italic">
                                                Tidak ada invoice lunas.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        paidInvoices.map((iv) => (
                                            <TableRow key={iv.id} className="hover:bg-slate-50/50">
                                                <TableCell className="font-mono text-xs font-semibold text-slate-800">{iv.invoice_number}</TableCell>
                                                <TableCell className="font-mono text-xs text-slate-500">{iv.order?.no_po}</TableCell>
                                                <TableCell className="font-medium">{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                                <TableCell className="text-xs text-slate-500">{formatDate(iv.tanggal_terbit)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-semibold">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-bold text-emerald-600">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell><Badge variant="success">LUNAS</Badge></TableCell>
                                                <TableCell className="text-right">
                                                    <Button asChild size="xs" variant="outline" className="text-xs py-1">
                                                        <a href={route('invoice.public', iv.invoice_number)} target="_blank" rel="noopener noreferrer">
                                                            <ExternalLink className="h-3 w-3 mr-1" /> Detail
                                                        </a>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        )}

                        {activeTab === 'tanda_jadi' && (
                            <Table>
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="font-semibold">No. Tanda Jadi</TableHead>
                                        <TableHead className="font-semibold">Brand</TableHead>
                                        <TableHead className="font-semibold">Customer</TableHead>
                                        <TableHead className="font-semibold">Deskripsi Desain</TableHead>
                                        <TableHead className="font-semibold">Tanggal</TableHead>
                                        <TableHead className="font-semibold text-right">Nominal</TableHead>
                                        <TableHead className="font-semibold">Status</TableHead>
                                        <TableHead className="font-semibold text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {design_deposits.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-12 text-center text-sm text-muted-foreground italic">
                                                Belum ada data Tanda Jadi (Design Deposit).
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        design_deposits.map((dep) => (
                                            <TableRow key={dep.id} className="hover:bg-slate-50/50">
                                                <TableCell className="font-mono text-xs font-semibold text-slate-800">{dep.deposit_number}</TableCell>
                                                <TableCell><Badge variant="outline" className="font-bold text-indigo-700">{dep.brand?.kode ?? '-'}</Badge></TableCell>
                                                <TableCell className="font-medium">{dep.customer_name}</TableCell>
                                                <TableCell className="text-xs text-slate-600 font-medium">{dep.description ?? '—'}</TableCell>
                                                <TableCell className="text-xs text-slate-500">{formatDate(dep.payment_date)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-bold text-slate-800">{formatRupiah(dep.amount)}</TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col gap-1">
                                                        <Badge variant={DEPOSIT_STATUS_VARIANT[dep.status] ?? 'outline'}>{dep.status.toUpperCase()}</Badge>
                                                        {dep.status === 'converted' && dep.order && (
                                                            <span className="text-[10px] text-emerald-600 font-bold block">
                                                                Linked: PO {dep.order.no_po}
                                                            </span>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1.5">
                                                        {dep.status === 'pending' && can?.validate && (
                                                            <Button 
                                                                size="xs" 
                                                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] py-1 px-2.5 rounded-lg shadow-sm"
                                                                onClick={() => setConfirmDepositVerify(dep)}
                                                            >
                                                                Validate TJ
                                                            </Button>
                                                        )}
                                                        {dep.status === 'verified' && (
                                                            <Button 
                                                                size="xs" 
                                                                className="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-[11px] py-1 px-2.5 rounded-lg shadow-sm"
                                                                onClick={() => {
                                                                    setConvertDeposit(dep);
                                                                    const matchedOrder = available_orders.find(o => o.brand_id === dep.brand_id);
                                                                    setSelectedOrderId(matchedOrder?.id || '');
                                                                }}
                                                            >
                                                                Konversi ke PO
                                                            </Button>
                                                        )}
                                                        {['pending', 'verified'].includes(dep.status) && can?.validate && (
                                                            <Button 
                                                                size="xs" 
                                                                variant="destructive"
                                                                className="font-semibold text-[11px] py-1 px-2.5 rounded-lg"
                                                                onClick={() => setConfirmDepositRefund(dep)}
                                                            >
                                                                Refund
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Pending Payments Validation Panel */}
                {can?.validate && (pending_payments ?? []).length > 0 && (
                    <Card className="border-amber-200 bg-amber-50/20 shadow-sm rounded-2xl overflow-hidden">
                        <CardHeader className="bg-amber-50/50 border-b border-amber-100">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 shadow-inner">
                                        <Clock className="h-4 w-4 text-amber-600 animate-pulse" />
                                    </div>
                                    <div>
                                        <div className="text-base font-bold flex items-center gap-2 text-slate-800">
                                            Validasi Pembayaran Pending (Mutasi Rekening Koran)
                                            <Badge variant="warning" className="text-xs font-bold">{pending_payments.length} menunggu</Badge>
                                        </div>
                                        <p className="text-xs text-slate-500">Pembayaran dari Admin Brand yang harus dicocokkan sebelum masuk cashflow.</p>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="text-xs text-muted-foreground uppercase tracking-wider font-semibold">Total Pending</div>
                                    <div className="font-mono font-black text-amber-700 text-lg">
                                        {formatRupiah(pending_payments.reduce((s, p) => s + Number(p.amount), 0))}
                                    </div>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-4">
                            <div className="overflow-hidden rounded-xl border border-slate-200">
                                <Table>
                                    <TableHeader className="bg-slate-50">
                                        <TableRow>
                                            <TableHead className="font-semibold">No. PO</TableHead>
                                            <TableHead className="font-semibold">Brand</TableHead>
                                            <TableHead className="font-semibold">Tipe</TableHead>
                                            <TableHead className="font-semibold">Tanggal</TableHead>
                                            <TableHead className="font-semibold">Bank Tujuan</TableHead>
                                            <TableHead className="font-semibold">Dicatat Oleh</TableHead>
                                            <TableHead className="font-semibold text-right">Nominal</TableHead>
                                            <TableHead className="font-semibold text-right">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {pending_payments.map((p) => (
                                            <TableRow key={p.id} className="hover:bg-slate-50/50">
                                                <TableCell className="font-mono text-xs font-bold text-slate-800">{p.order?.no_po ?? '-'}</TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className="font-semibold">{p.order?.brand?.kode ?? '-'}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={p.payment_type === 'dp' ? 'info' : p.payment_type === 'pelunasan' ? 'success' : 'secondary'} className="text-xs font-semibold">
                                                        {p.payment_type?.toUpperCase()} {p.dp_sequence ? `#${p.dp_sequence}` : ''}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-xs text-slate-500">{formatDate(p.payment_date)}</TableCell>
                                                <TableCell className="text-xs font-medium">
                                                    {p.bank ? (
                                                        <span>{p.bank.bank_name} — {p.bank.account_number}</span>
                                                    ) : (
                                                        <span className="text-muted-foreground italic">—</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-xs text-slate-500">{p.recorder?.name ?? '-'}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-bold text-amber-700">
                                                    {formatRupiah(p.amount)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        size="sm"
                                                        className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs gap-1 py-1 px-3 rounded-lg shadow-sm"
                                                        onClick={() => setConfirmPayment(p)}
                                                    >
                                                        <ShieldCheck className="h-3.5 w-3.5" />
                                                        Validasi
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            <div className="mt-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-xs text-amber-900 leading-relaxed shadow-sm">
                                <AlertTriangle className="h-4 w-4 shrink-0 mt-0.5 text-amber-600 animate-bounce" />
                                <div>
                                    <strong className="block font-bold mb-0.5">Peringatan Rekonsiliasi Keuangan:</strong>
                                    <span>Pastikan nominal pembayaran, bank tujuan, dan bukti transfer telah diverifikasi dan sepenuhnya sesuai dengan rekening koran mutasi bank Anda sebelum memencet tombol validasi. Pembayaran yang divalidasi tidak dapat dibatalkan secara otomatis.</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Modals & Dialogs */}

                {/* Tambah Tanda Jadi Modal */}
                <Dialog open={showAddDeposit} onOpenChange={setShowAddDeposit}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-indigo-700 font-bold">
                                <Plus className="h-5 w-5 text-indigo-600" />
                                Catat Tanda Jadi Baru (Design Deposit)
                            </DialogTitle>
                            <DialogDescription>
                                Masukkan pembayaran uang tanda jadi/booking fee desain untuk customer sebelum masuk PO.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleAddDeposit} className="space-y-4 py-2">
                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <label className="text-xs font-semibold text-slate-700">Brand *</label>
                                    <Select 
                                        value={newDeposit.brand_id} 
                                        onValueChange={(v) => setNewDeposit({ ...newDeposit, brand_id: v })}
                                    >
                                        <SelectTrigger className="bg-white"><SelectValue placeholder="Pilih Brand" /></SelectTrigger>
                                        <SelectContent>
                                            {brands.map(b => (
                                                <SelectItem key={b.id} value={b.id}>{b.nama_brand}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-xs font-semibold text-slate-700">Nama Customer *</label>
                                    <Input 
                                        required 
                                        placeholder="Nama Pelanggan" 
                                        value={newDeposit.customer_name} 
                                        onChange={(e) => setNewDeposit({ ...newDeposit, customer_name: e.target.value })}
                                        className="bg-white"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-slate-700">Deskripsi Desain (Contoh: Bomber Hoodie NIS)</label>
                                <Input 
                                    placeholder="Apa yang akan didesain?" 
                                    value={newDeposit.description} 
                                    onChange={(e) => setNewDeposit({ ...newDeposit, description: e.target.value })}
                                    className="bg-white"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <label className="text-xs font-semibold text-slate-700">Nominal Tanda Jadi *</label>
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                        <Input 
                                            required 
                                            type="number"
                                            placeholder="500000" 
                                            value={newDeposit.amount} 
                                            onChange={(e) => setNewDeposit({ ...newDeposit, amount: e.target.value })}
                                            className="pl-8 bg-white"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-xs font-semibold text-slate-700">Tanggal Bayar *</label>
                                    <Input 
                                        required 
                                        type="date" 
                                        value={newDeposit.payment_date} 
                                        onChange={(e) => setNewDeposit({ ...newDeposit, payment_date: e.target.value })}
                                        className="bg-white"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-slate-700">Bank Tujuan Transfer *</label>
                                <Select 
                                    value={newDeposit.bank_id} 
                                    onValueChange={(v) => setNewDeposit({ ...newDeposit, bank_id: v })}
                                >
                                    <SelectTrigger className="bg-white"><SelectValue placeholder="Pilih Bank" /></SelectTrigger>
                                    <SelectContent>
                                        {bank_accounts.map(bank => (
                                            <SelectItem key={bank.id} value={bank.id}>{bank.bank_name} — {bank.account_number} ({bank.account_name})</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-slate-700">Catatan Lainnya</label>
                                <Input 
                                    placeholder="Tambahkan catatan jika diperlukan..." 
                                    value={newDeposit.notes} 
                                    onChange={(e) => setNewDeposit({ ...newDeposit, notes: e.target.value })}
                                    className="bg-white"
                                />
                            </div>

                            <DialogFooter className="pt-2">
                                <Button type="button" variant="outline" onClick={() => setShowAddDeposit(false)}>Batal</Button>
                                <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Simpan Tanda Jadi</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Validasi Tanda Jadi Confirmation */}
                <Dialog open={!!confirmDepositVerify} onOpenChange={(open) => !open && setConfirmDepositVerify(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-emerald-700 font-bold">
                                <ShieldCheck className="h-5 w-5" />
                                Validasi Pembayaran Tanda Jadi
                            </DialogTitle>
                            <DialogDescription>
                                Apakah Anda yakin ingin memvalidasi Tanda Jadi ini? Nominal akan langsung masuk ledger pemasukan keuangan.
                            </DialogDescription>
                        </DialogHeader>
                        {confirmDepositVerify && (
                            <div className="rounded-xl border bg-slate-50 p-4 space-y-2 text-xs leading-relaxed font-semibold">
                                <div className="flex justify-between">
                                    <span className="text-slate-500">No. Tanda Jadi</span>
                                    <span className="font-mono text-slate-800">{confirmDepositVerify.deposit_number}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-slate-500">Brand</span>
                                    <span>{confirmDepositVerify.brand?.nama_brand ?? '-'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-slate-500">Customer</span>
                                    <span>{confirmDepositVerify.customer_name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-slate-500">Deskripsi</span>
                                    <span>{confirmDepositVerify.description || '—'}</span>
                                </div>
                                <div className="flex justify-between border-t pt-2 mt-1">
                                    <span className="text-slate-500 font-bold">Nominal</span>
                                    <span className="font-mono font-black text-emerald-700 text-base">{formatRupiah(confirmDepositVerify.amount)}</span>
                                </div>
                            </div>
                        )}
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setConfirmDepositVerify(null)}>Batal</Button>
                            <Button 
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
                                onClick={() => verifyDeposit(confirmDepositVerify)}
                            >
                                Ya, Validasi TJ
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Refund Tanda Jadi Confirmation */}
                <Dialog open={!!confirmDepositRefund} onOpenChange={(open) => !open && setConfirmDepositRefund(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-destructive font-bold">
                                <XCircle className="h-5 w-5" />
                                Refund Tanda Jadi Desain
                            </DialogTitle>
                            <DialogDescription>
                                Tindakan ini akan mengembalikan uang tanda jadi ke customer, status di-refund dan tercatat sebagai pengeluaran otomatis.
                            </DialogDescription>
                        </DialogHeader>
                        {confirmDepositRefund && (
                            <div className="rounded-xl border bg-slate-50 p-4 space-y-2 text-xs font-semibold">
                                <div className="flex justify-between">
                                    <span className="text-slate-500">No. Tanda Jadi</span>
                                    <span className="font-mono text-slate-800">{confirmDepositRefund.deposit_number}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-slate-500">Customer</span>
                                    <span>{confirmDepositRefund.customer_name}</span>
                                </div>
                                <div className="flex justify-between border-t pt-2 mt-1">
                                    <span className="text-slate-500 font-bold">Nominal Pengembalian</span>
                                    <span className="font-mono font-black text-red-700 text-base">{formatRupiah(confirmDepositRefund.amount)}</span>
                                </div>
                            </div>
                        )}
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setConfirmDepositRefund(null)}>Batal</Button>
                            <Button 
                                variant="destructive"
                                className="font-semibold"
                                onClick={() => refundDeposit(confirmDepositRefund)}
                            >
                                Ya, Proses Refund
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Konversi ke PO Modal */}
                <Dialog open={!!convertDeposit} onOpenChange={(open) => !open && setConvertDeposit(null)}>
                    <DialogContent className="sm:max-w-[450px]">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-indigo-700 font-bold">
                                <RefreshCw className="h-5 w-5 text-indigo-600 animate-spin" />
                                Konversi Tanda Jadi ke PO
                            </DialogTitle>
                            <DialogDescription>
                                Hubungkan uang tanda jadi desain ini ke PO yang sudah terbit. Jumlah nominal akan otomatis masuk sebagai DP pembayaran PO tersebut.
                            </DialogDescription>
                        </DialogHeader>
                        {convertDeposit && (
                            <form onSubmit={handleConvertDeposit} className="space-y-4 py-2">
                                <div className="rounded-xl border bg-slate-50 p-4 space-y-2 text-xs font-semibold mb-3">
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">No. Tanda Jadi</span>
                                        <span className="font-mono text-slate-800">{convertDeposit.deposit_number}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">Customer</span>
                                        <span>{convertDeposit.customer_name}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-500">Nominal Tanda Jadi</span>
                                        <span className="font-mono text-indigo-700">{formatRupiah(convertDeposit.amount)}</span>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <label className="text-xs font-bold text-slate-700">Hubungkan ke Purchase Order (PO) *</label>
                                    <Select 
                                        value={selectedOrderId} 
                                        onValueChange={setSelectedOrderId}
                                        required
                                    >
                                        <SelectTrigger className="bg-white"><SelectValue placeholder="Pilih Purchase Order" /></SelectTrigger>
                                        <SelectContent>
                                            {available_orders
                                                .filter(o => o.brand_id === convertDeposit.brand_id)
                                                .map(order => (
                                                    <SelectItem key={order.id} value={order.id}>
                                                        {order.no_po} — {order.nama_po} ({formatRupiah(order.total_tagihan)})
                                                    </SelectItem>
                                                ))
                                            }
                                            {available_orders.filter(o => o.brand_id === convertDeposit.brand_id).length === 0 && (
                                                <SelectItem disabled value="none">Tidak ada PO terbit untuk brand ini</SelectItem>
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <span className="text-[10px] text-slate-400 leading-normal block">
                                        * Hanya menampilkan PO yang telah diterbitkan (Published) dan cocok dengan Brand dari Tanda Jadi.
                                    </span>
                                </div>

                                <DialogFooter className="pt-2">
                                    <Button type="button" variant="outline" onClick={() => setConvertDeposit(null)}>Batal</Button>
                                    <Button 
                                        type="submit" 
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold"
                                        disabled={!selectedOrderId}
                                    >
                                        Konversikan Sekarang
                                    </Button>
                                </DialogFooter>
                            </form>
                        )}
                    </DialogContent>
                </Dialog>

                {/* Verify Payment Confirmation Dialog */}
                <Dialog open={!!confirmPayment} onOpenChange={(open) => !open && setConfirmPayment(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <ShieldCheck className="h-5 w-5 text-emerald-600" />
                                Konfirmasi Validasi Pembayaran PO
                            </DialogTitle>
                            <DialogDescription>
                                Apakah Anda yakin ingin memvalidasi pembayaran ini? Sisa tagihan akan otomatis berkurang.
                            </DialogDescription>
                        </DialogHeader>
                        {confirmPayment && (
                            <div className="space-y-3 py-2">
                                <div className="rounded-lg border bg-slate-50 p-4 space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">No. PO</span>
                                        <span className="font-mono font-medium">{confirmPayment.order?.no_po}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Brand</span>
                                        <span>{confirmPayment.order?.brand?.nama_brand ?? '-'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Tipe Pembayaran</span>
                                        <Badge variant="info">
                                            {confirmPayment.payment_type?.toUpperCase()} {confirmPayment.dp_sequence ? `#${confirmPayment.dp_sequence}` : ''}
                                        </Badge>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Tanggal Bayar</span>
                                        <span>{formatDate(confirmPayment.payment_date)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Bank Tujuan</span>
                                        <span>{confirmPayment.bank ? `${confirmPayment.bank.bank_name} — ${confirmPayment.bank.account_number}` : '—'}</span>
                                    </div>
                                    <div className="flex justify-between border-t pt-2">
                                        <span className="text-muted-foreground font-semibold">Nominal</span>
                                        <span className="font-mono font-bold text-lg text-emerald-700">{formatRupiah(confirmPayment.amount)}</span>
                                    </div>
                                    {confirmPayment.notes && (
                                        <div className="border-t pt-2">
                                            <span className="text-muted-foreground text-xs">Catatan:</span>
                                            <p className="text-xs mt-0.5">{confirmPayment.notes}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setConfirmPayment(null)} disabled={verifying}>Batal</Button>
                            <Button
                                className="bg-emerald-600 hover:bg-emerald-700 text-white gap-1"
                                onClick={() => verifyPayment(confirmPayment)}
                                disabled={verifying}
                            >
                                <ShieldCheck className="h-4 w-4" />
                                {verifying ? 'Memvalidasi...' : 'Ya, Validasi Pembayaran'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
