import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Search, Receipt, CheckCircle2, ExternalLink, Copy, Calendar,
    ShieldCheck, Clock, Banknote, AlertTriangle, Plus, Sparkles,
    Layers, RefreshCw, FileText, ChevronRight, CheckCircle, HelpCircle, XCircle,
    Info, Percent, Truck, Wallet, FileSpreadsheet, CheckCircle2 as CheckedIcon,
    MessageCircle, Send
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_VARIANT = {
    draft: 'outline', 
    validated: 'info', 
    published: 'success',
    sent: 'info', 
    paid: 'success', 
    overdue: 'destructive', 
    cancel: 'secondary',
};

/** Tombol dropdown "Kirim WA" dengan 3 pilihan kondisi */
function WaDropdownButton({ invoice, onSend }) {
    const [open, setOpen] = useState(false);
    const alreadySent = !!invoice.sent_at;
    const options = [
        { key: 'new_invoice', label: '📄 Invoice Baru',         desc: 'Informasi invoice & link' },
        { key: 'reminder',    label: '⏰ Pengingat Jatuh Tempo', desc: 'Reminder sebelum/saat jatuh tempo' },
        { key: 'overdue',     label: '⚠️ Jatuh Tempo',           desc: 'Notifikasi sudah melewati jatuh tempo' },
    ];
    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => setOpen(v => !v)}
                onBlur={() => setTimeout(() => setOpen(false), 150)}
                className={`flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-bold transition shadow-sm ${
                    alreadySent
                        ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'
                        : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                }`}
                title={alreadySent ? `Terkirim ${invoice.sent_at ? new Date(invoice.sent_at).toLocaleString('id-ID') : ''}` : 'Kirim via WhatsApp'}
            >
                <MessageCircle className="h-3.5 w-3.5" />
                {alreadySent ? 'Kirim Ulang' : 'Kirim WA'}
                <span className="text-[10px] opacity-60">▾</span>
            </button>
            {open && (
                <div className="absolute right-0 top-full z-50 mt-1 w-56 rounded-xl border border-slate-200 bg-white shadow-xl">
                    {options.map(opt => (
                        <button
                            key={opt.key}
                            type="button"
                            className="flex w-full flex-col px-3 py-2.5 text-left hover:bg-slate-50 first:rounded-t-xl last:rounded-b-xl transition"
                            onClick={() => { setOpen(false); onSend(invoice, opt.key); }}
                        >
                            <span className="text-xs font-bold text-slate-800">{opt.label}</span>
                            <span className="text-[10px] text-slate-400">{opt.desc}</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

const DEPOSIT_STATUS_VARIANT = {
    pending: 'warning',
    verified: 'info',
    converted: 'success',
    refunded: 'destructive',
    expired: 'secondary'
};

export default function InvoiceList({ 
    invoices, 
    all_filtered_invoices = [], 
    brands = [], 
    design_deposits = [], 
    available_orders = [], 
    bank_accounts = [], 
    customers = [],
    filters = {}, 
    statuses = [], 
    can = {} 
}) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const hasFinanceView = user?.permissions?.includes('finance.view') || user?.roles?.includes('superadmin') || user?.roles?.includes('owner') || user?.roles?.includes('admin_keuangan');
    const dashboardUrl = hasFinanceView ? route('invoices.index') : route('dashboard');

    const [activeTab, setActiveTab] = useState('belum_lunas');
    
    // Dialogs & Modals state
    const [showAddDeposit, setShowAddDeposit] = useState(false);
    const [confirmDepositVerify, setConfirmDepositVerify] = useState(null);
    const [confirmDepositRefund, setConfirmDepositRefund] = useState(null);
    const [convertDeposit, setConvertDeposit] = useState(null);
    const [selectedOrderId, setSelectedOrderId] = useState('');
    
    // Invoice action states
    const [confirmPublish, setConfirmPublish] = useState(null);
    const [selectedInvoiceToValidate, setSelectedInvoiceToValidate] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const initialBrandId = brands[0]?.id || '';
    const initialDepositBank = bank_accounts.find(b => b.brand_id === initialBrandId) || bank_accounts.find(b => !b.brand_id) || bank_accounts[0];

    // Validate Form State
    const [validationForm, setValidationForm] = useState({
        diskon_type: 'nominal',
        diskon_value: '0',
        biaya_pengiriman: '0',
        jasa_pengiriman: '',
        bank_id: '',
        catatan: ''
    });

    // Design Deposit Form State
    const [newDeposit, setNewDeposit] = useState({
        brand_id: initialBrandId,
        customer_id: '',
        customer_name: '',
        description: '',
        amount: '',
        payment_date: new Date().toISOString().split('T')[0],
        bank_id: initialDepositBank?.id || '',
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
    const unpaidInvoices = (invoices?.data || []).filter(inv => inv.status !== 'paid' && Number(inv.sisa_pembayaran) > 0);
    const paidInvoices = (invoices?.data || []).filter(inv => inv.status === 'paid' || Number(inv.sisa_pembayaran) === 0);

    function applyFilters(overrides = {}) {
        router.get(route('invoices.list'), {
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

    // Design Deposit Actions
    function handleAddDeposit(e) {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('design-deposits.store'), newDeposit, {
            onSuccess: () => {
                setShowAddDeposit(false);
                setIsSubmitting(false);
                setNewDeposit({
                    brand_id: initialBrandId,
                    customer_id: '',
                    customer_name: '',
                    description: '',
                    amount: '',
                    payment_date: new Date().toISOString().split('T')[0],
                    bank_id: initialDepositBank?.id || '',
                    notes: ''
                });
            },
            onError: () => setIsSubmitting(false)
        });
    }

    function verifyDeposit(deposit) {
        setIsSubmitting(true);
        router.post(route('design-deposits.verify', deposit.id), {}, {
            onSuccess: () => {
                setConfirmDepositVerify(null);
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false)
        });
    }

    function refundDeposit(deposit) {
        setIsSubmitting(true);
        router.post(route('design-deposits.refund', deposit.id), {}, {
            onSuccess: () => {
                setConfirmDepositRefund(null);
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false)
        });
    }

    function handleConvertDeposit(e) {
        e.preventDefault();
        if (!selectedOrderId) return;
        setIsSubmitting(true);
        router.post(route('design-deposits.convert', convertDeposit.id), {
            order_id: selectedOrderId
        }, {
            onSuccess: () => {
                setConvertDeposit(null);
                setSelectedOrderId('');
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false)
        });
    }

    // Invoice Publishing & Validation
    function publishInvoice(invoice) {
        setIsSubmitting(true);
        router.post(route('invoices.publish', invoice.id), {}, {
            onSuccess: () => {
                setConfirmPublish(null);
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false)
        });
    }

    function sendInvoiceWa(invoice, condition = 'new_invoice') {
        const labels = {
            new_invoice: 'Kirim invoice baru ke WhatsApp pelanggan?',
            reminder: 'Kirim pengingat jatuh tempo ke WhatsApp pelanggan?',
            overdue: 'Kirim notifikasi jatuh tempo ke WhatsApp pelanggan?',
        };
        if (!confirm(labels[condition] ?? 'Kirim WA?')) return;
        router.post(route('invoices.send-wa', invoice.id), { condition }, { preserveScroll: true });
    }

    function handleOpenValidateModal(invoice) {
        const firstBrandBank = bank_accounts.find(bank => bank.brand_id === invoice.brand_id) || bank_accounts.find(bank => !bank.brand_id) || bank_accounts[0];
        setSelectedInvoiceToValidate(invoice);
        setValidationForm({
            diskon_type: invoice.diskon_type || 'nominal',
            diskon_value: String(invoice.diskon_value || 0),
            biaya_pengiriman: String(invoice.biaya_pengiriman || 0),
            jasa_pengiriman: invoice.jasa_pengiriman || '',
            bank_id: invoice.bank_id || firstBrandBank?.id || '',
            catatan: invoice.catatan || ''
        });
    }

    function submitValidation(e) {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('invoices.validate', selectedInvoiceToValidate.id), validationForm, {
            onSuccess: () => {
                setSelectedInvoiceToValidate(null);
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false)
        });
    }

    // Live calculation for preview during validation
    const getValidationPreview = () => {
        if (!selectedInvoiceToValidate) return { tagihan: 0, sisa: 0, diskon: 0 };
        const tagihan = Number(selectedInvoiceToValidate.total_tagihan) || 0;
        const dp = Number(selectedInvoiceToValidate.dp_amount) || 0;
        const diskVal = Number(validationForm.diskon_value) || 0;
        const shipping = Number(validationForm.biaya_pengiriman) || 0;

        let diskonNominal = 0;
        if (validationForm.diskon_type === 'persen') {
            diskonNominal = (tagihan * diskVal) / 100;
        } else {
            diskonNominal = diskVal;
        }

        const sisa = Math.max(0, tagihan - diskonNominal + shipping - dp);
        return { tagihan, sisa, diskon: diskonNominal, shipping };
    };

    const valPreview = getValidationPreview();

    return (
        <AppLayout title="Invoice Management">
            <Head title="List Invoice & Tanda Jadi" />

            <div className="space-y-6">
                {/* Executive Header Banner */}
                <div className="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-8 rounded-2xl shadow-xl text-white border border-indigo-900/50">
                    <div className="absolute top-0 right-0 p-4 opacity-15">
                        <Sparkles className="h-40 w-40 text-indigo-400" />
                    </div>
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="space-y-2">
                            <span className="bg-indigo-500/20 text-indigo-300 text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full border border-indigo-500/30 backdrop-blur-sm">
                                Invoice Operations
                            </span>
                            <h1 className="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-indigo-200 bg-clip-text text-transparent">
                                List Invoice & Tanda Jadi Desain
                            </h1>
                            <p className="text-slate-300 max-w-2xl text-sm leading-relaxed">
                                Lakukan penerbitan invoice draft, validasi invoice dengan tambahan ongkos kirim/diskon, publish invoice resmi, dan kelola tanda jadi sebelum PO terbit.
                            </p>
                        </div>
                        <div className="flex flex-col sm:flex-row gap-3 shrink-0">
                            <Button 
                                onClick={() => {
                                    setActiveTab('tanda_jadi');
                                    setShowAddDeposit(true);
                                }}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md gap-1.5"
                            >
                                <Plus className="h-4 w-4" />
                                Catat Tanda Jadi
                            </Button>
                            <Button asChild variant="outline" className="bg-slate-800/40 text-slate-200 border-slate-700 hover:bg-slate-800/80 hover:text-white rounded-xl">
                                <Link href={dashboardUrl}>
                                    <Receipt className="h-4 w-4 mr-2" />
                                    Kembali ke Dashboard
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Filter and Tab Panel */}
                <div className="flex flex-col gap-4 bg-white p-5 rounded-2xl border shadow-sm">
                    {/* Header bar: Tabs & Action */}
                    <div className="flex flex-col lg:flex-row justify-between lg:items-center gap-4">
                        {/* Tabs */}
                        <div className="flex space-x-1 p-1 bg-slate-100 rounded-xl max-w-md w-full sm:w-auto">
                            <button
                                onClick={() => setActiveTab('belum_lunas')}
                                className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-lg transition-all ${
                                    activeTab === 'belum_lunas' 
                                        ? 'bg-white text-indigo-700 shadow-sm border border-slate-200/50' 
                                        : 'text-slate-600 hover:text-slate-800'
                                }`}
                            >
                                <Clock className="h-3.5 w-3.5" />
                                Belum Lunas ({unpaidInvoices.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('sudah_lunas')}
                                className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-lg transition-all ${
                                    activeTab === 'sudah_lunas' 
                                        ? 'bg-white text-indigo-700 shadow-sm border border-slate-200/50' 
                                        : 'text-slate-600 hover:text-slate-800'
                                }`}
                            >
                                <CheckCircle className="h-3.5 w-3.5" />
                                Sudah Lunas ({paidInvoices.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('tanda_jadi')}
                                className={`flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-lg transition-all ${
                                    activeTab === 'tanda_jadi' 
                                        ? 'bg-white text-indigo-700 shadow-sm border border-slate-200/50' 
                                        : 'text-slate-600 hover:text-slate-800'
                                }`}
                            >
                                <Layers className="h-3.5 w-3.5" />
                                Tanda Jadi ({design_deposits.length})
                            </button>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-2 self-end lg:self-auto">
                            <Button 
                                onClick={copyToClipboard}
                                variant="outline"
                                className="border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-xl flex items-center gap-1.5"
                            >
                                <FileSpreadsheet className="h-4 w-4 text-emerald-600" />
                                {copied ? 'Tersalin!' : 'Copy ke Excel'}
                            </Button>
                            
                            {activeTab === 'tanda_jadi' && (
                                <Button 
                                    onClick={() => setShowAddDeposit(true)}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl shadow-md gap-1.5"
                                >
                                    <Plus className="h-4 w-4" />
                                    Catat Tanda Jadi
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Filter Inputs Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 border-t pt-4">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input 
                                placeholder="Cari..." 
                                value={search} 
                                onChange={(e) => setSearch(e.target.value)} 
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()} 
                                className="pl-9 bg-slate-50 border-slate-200 focus:bg-white text-xs rounded-xl" 
                            />
                        </div>

                        <Select value={brandId} onValueChange={(v) => { setBrandId(v); applyFilters({ brand_id: v }); }}>
                            <SelectTrigger className="bg-slate-50 border-slate-200 text-xs rounded-xl"><SelectValue placeholder="Pilih Brand" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Brand</SelectItem>
                                {brands.map((b) => (
                                    <SelectItem key={b.id} value={b.id}>{b.nama_brand} ({b.kode})</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {activeTab !== 'tanda_jadi' && (
                            <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                <SelectTrigger className="bg-slate-50 border-slate-200 text-xs rounded-xl"><SelectValue placeholder="Status" /></SelectTrigger>
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
                            className="bg-slate-50 hover:bg-slate-100 border border-slate-200 flex justify-between items-center text-slate-700 font-medium w-full text-xs rounded-xl"
                        >
                            <span className="flex items-center gap-2">
                                <Calendar className="h-4 w-4 text-slate-500" />
                                {getDateFilterLabel()}
                            </span>
                        </Button>

                        <div className="flex gap-2">
                            <Button className="flex-1 text-xs font-bold rounded-xl" onClick={() => applyFilters()}>Filter</Button>
                            <Button variant="outline" className="text-xs font-semibold rounded-xl border-slate-200" onClick={() => {
                                setSearch('');
                                setStatus('all');
                                setBrandId('all');
                                setStartDate('');
                                setEndDate('');
                                setShowDatePanel(false);
                                router.get(route('invoices.list'), {}, { preserveScroll: true });
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
                                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
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
                                            className="text-xs border rounded-xl px-3 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                        />
                                        <span className="text-slate-400 text-xs">s/d</span>
                                        <input
                                            type="date"
                                            value={endDate}
                                            onChange={(e) => { setEndDate(e.target.value); applyFilters({ end_date: e.target.value }); }}
                                            className="text-xs border rounded-xl px-3 py-1.5 text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-primary shadow-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Tabs Content */}
                <Card className="border border-slate-150 shadow-sm overflow-hidden rounded-2xl">
                    <CardContent className="p-0">
                        {activeTab === 'belum_lunas' && (
                            <Table>
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="font-bold text-slate-700">No. Invoice</TableHead>
                                        <TableHead className="font-bold text-slate-700">No. PO</TableHead>
                                        <TableHead className="font-bold text-slate-700">Pelanggan</TableHead>
                                        <TableHead className="font-bold text-slate-700">Tgl Terbit</TableHead>
                                        <TableHead className="font-bold text-slate-700 text-right">Total Tagihan</TableHead>
                                        <TableHead className="font-bold text-slate-700 text-right text-orange-600">Sisa Tagihan</TableHead>
                                        <TableHead className="font-bold text-slate-700">Status</TableHead>
                                        <TableHead className="font-bold text-slate-700 text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {unpaidInvoices.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-16 text-center text-sm text-slate-400 italic">
                                                Tidak ada invoice belum lunas.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        unpaidInvoices.map((iv) => (
                                            <TableRow key={iv.id} className="hover:bg-slate-50/50">
                                                <TableCell className="font-mono text-xs font-bold text-slate-800">{iv.invoice_number}</TableCell>
                                                <TableCell className="font-mono text-xs text-slate-500">{iv.order?.no_po}</TableCell>
                                                <TableCell className="font-bold text-slate-800">{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                                <TableCell className="text-xs text-slate-500">{formatDate(iv.tanggal_terbit)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-bold">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-black text-orange-600">{formatRupiah(iv.sisa_pembayaran)}</TableCell>
                                                <TableCell><Badge variant={STATUS_VARIANT[iv.status] ?? 'outline'}>{iv.status}</Badge></TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1.5">
                                                        <Button asChild size="xs" variant="outline" className="text-xs py-1 rounded-lg">
                                                            <a href={route('invoice.public', iv.invoice_number)} target="_blank" rel="noopener noreferrer">
                                                                <ExternalLink className="h-3 w-3 mr-1" /> PDF Detail
                                                            </a>
                                                        </Button>
                                                        {can?.create && iv.status === 'draft' && (
                                                            <Button 
                                                                size="xs" 
                                                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-1 rounded-lg shadow-sm" 
                                                                onClick={() => handleOpenValidateModal(iv)}
                                                            >
                                                                Validate
                                                            </Button>
                                                        )}
                                                        {can?.publish && iv.status === 'validated' && (
                                                            <Button
                                                                size="xs"
                                                                className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-1 rounded-lg shadow-sm"
                                                                onClick={() => setConfirmPublish(iv)}
                                                            >
                                                                Publish
                                                            </Button>
                                                        )}
                                                        {can?.publish && ['published', 'sent', 'overdue'].includes(iv.status) && (
                                                            <WaDropdownButton invoice={iv} onSend={sendInvoiceWa} />
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
                                        <TableHead className="font-bold text-slate-700">No. Invoice</TableHead>
                                        <TableHead className="font-bold text-slate-700">No. PO</TableHead>
                                        <TableHead className="font-bold text-slate-700">Pelanggan</TableHead>
                                        <TableHead className="font-bold text-slate-700">Tgl Terbit</TableHead>
                                        <TableHead className="font-bold text-slate-700 text-right">Total Tagihan</TableHead>
                                        <TableHead className="font-bold text-slate-700 text-right text-emerald-600">Total Terbayar</TableHead>
                                        <TableHead className="font-bold text-slate-700">Status</TableHead>
                                        <TableHead className="font-bold text-slate-700 text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {paidInvoices.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-16 text-center text-sm text-slate-400 italic">
                                                Tidak ada invoice lunas.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        paidInvoices.map((iv) => (
                                            <TableRow key={iv.id} className="hover:bg-slate-50/50">
                                                <TableCell className="font-mono text-xs font-bold text-slate-800">{iv.invoice_number}</TableCell>
                                                <TableCell className="font-mono text-xs text-slate-500">{iv.order?.no_po}</TableCell>
                                                <TableCell className="font-bold text-slate-800">{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                                <TableCell className="text-xs text-slate-500">{formatDate(iv.tanggal_terbit)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-bold">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-black text-emerald-600">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell><Badge variant="success">LUNAS</Badge></TableCell>
                                                <TableCell className="text-right">
                                                    <Button asChild size="xs" variant="outline" className="text-xs py-1 rounded-lg">
                                                        <a href={route('invoice.public', iv.invoice_number)} target="_blank" rel="noopener noreferrer">
                                                            <ExternalLink className="h-3 w-3 mr-1" /> PDF Detail
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
                                        <TableHead className="font-bold text-slate-700">No. Tanda Jadi</TableHead>
                                        <TableHead className="font-bold text-slate-700">Brand</TableHead>
                                        <TableHead className="font-bold text-slate-700">Customer</TableHead>
                                        <TableHead className="font-bold text-slate-700">Deskripsi Desain</TableHead>
                                        <TableHead className="font-bold text-slate-700">Tanggal</TableHead>
                                        <TableHead className="font-bold text-slate-700 text-right">Nominal</TableHead>
                                        <TableHead className="font-bold text-slate-700">Status</TableHead>
                                        <TableHead className="font-bold text-slate-700 text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {design_deposits.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-16 text-center text-sm text-slate-400 italic">
                                                Belum ada data Tanda Jadi (Design Deposit).
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        design_deposits.map((dep) => (
                                            <TableRow key={dep.id} className="hover:bg-slate-50/50">
                                                <TableCell className="font-mono text-xs font-bold text-slate-800">{dep.deposit_number}</TableCell>
                                                <TableCell><Badge variant="outline" className="font-bold text-indigo-700">{dep.brand?.kode ?? '-'}</Badge></TableCell>
                                                <TableCell className="font-bold text-slate-800">{dep.customer_name}</TableCell>
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
                                                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] py-1 px-2.5 rounded-lg shadow-sm"
                                                                onClick={() => setConfirmDepositVerify(dep)}
                                                            >
                                                                Validate TJ
                                                            </Button>
                                                        )}
                                                        {dep.status === 'verified' && (
                                                            <Button 
                                                                size="xs" 
                                                                className="bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] py-1 px-2.5 rounded-lg shadow-sm"
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
                                                                className="font-bold text-[11px] py-1 px-2.5 rounded-lg"
                                                                onClick={() => setConfirmDepositRefund(dep)}
                                                            >
                                                                Refund
                                                            </Button>
                                                        )}
                                                        {dep.status === 'pending' && can?.validate && (
                                                            <Button 
                                                                size="xs" 
                                                                variant="destructive"
                                                                className="font-bold text-[11px] py-1 px-2.5 rounded-lg ml-1"
                                                                onClick={() => {
                                                                    if (confirm('Yakin ingin menghapus record tanda jadi ini?')) {
                                                                        router.delete(route('design-deposits.destroy', dep.id));
                                                                    }
                                                                }}
                                                            >
                                                                Hapus
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

                {/* MODALS & DIALOGS */}

                {/* Validate Invoice Modal */}
                <Dialog open={!!selectedInvoiceToValidate} onOpenChange={(open) => !open && setSelectedInvoiceToValidate(null)}>
                    <DialogContent className="sm:max-w-[550px]">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-indigo-700 font-bold">
                                <ShieldCheck className="h-5 w-5 text-indigo-600" />
                                Validasi Invoice Draft ({selectedInvoiceToValidate?.invoice_number})
                            </DialogTitle>
                            <DialogDescription>
                                Lengkapi rincian biaya tambahan (ongkir), diskon, dan tentukan bank penerima pembayaran sebelum menerbitkan invoice resmi.
                            </DialogDescription>
                        </DialogHeader>
                        {selectedInvoiceToValidate && (
                            <form onSubmit={submitValidation} className="space-y-4 py-2">
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1.5 col-span-2">
                                        <label className="text-xs font-bold text-slate-700">Bank Penerima Pembayaran *</label>
                                        <Select 
                                            value={validationForm.bank_id} 
                                            onValueChange={(v) => setValidationForm({ ...validationForm, bank_id: v })}
                                            required
                                        >
                                            <SelectTrigger className="bg-white rounded-xl"><SelectValue placeholder="Pilih Rekening Bank" /></SelectTrigger>
                                            <SelectContent>
                                                {bank_accounts.filter(bank => !bank.brand_id || bank.brand_id === selectedInvoiceToValidate?.brand_id).map(bank => (
                                                    <SelectItem key={bank.id} value={bank.id}>
                                                        {bank.bank} — {bank.nomor_rekening} ({bank.atas_nama})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Tipe Diskon</label>
                                        <Select 
                                            value={validationForm.diskon_type} 
                                            onValueChange={(v) => setValidationForm({ ...validationForm, diskon_type: v })}
                                        >
                                            <SelectTrigger className="bg-white rounded-xl"><SelectValue placeholder="Diskon" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="nominal">Nominal (Rupiah)</SelectItem>
                                                <SelectItem value="persen">Persentase (%)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Nilai Diskon</label>
                                        <div className="relative">
                                            {validationForm.diskon_type === 'nominal' ? (
                                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                            ) : null}
                                            <Input 
                                                type="number"
                                                min="0"
                                                value={validationForm.diskon_value} 
                                                onChange={(e) => setValidationForm({ ...validationForm, diskon_value: e.target.value })}
                                                className={`bg-white rounded-xl ${validationForm.diskon_type === 'nominal' ? 'pl-8' : ''}`}
                                            />
                                            {validationForm.diskon_type === 'persen' ? (
                                                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                            ) : null}
                                        </div>
                                    </div>

                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Biaya Pengiriman</label>
                                        <div className="relative">
                                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                            <Input 
                                                type="number"
                                                min="0"
                                                value={validationForm.biaya_pengiriman} 
                                                onChange={(e) => setValidationForm({ ...validationForm, biaya_pengiriman: e.target.value })}
                                                className="pl-8 bg-white rounded-xl"
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Jasa Pengiriman / Ekspedisi</label>
                                        <Input 
                                            placeholder="Contoh: JNE / J&T" 
                                            value={validationForm.jasa_pengiriman} 
                                            onChange={(e) => setValidationForm({ ...validationForm, jasa_pengiriman: e.target.value })}
                                            className="bg-white rounded-xl"
                                        />
                                    </div>

                                    <div className="space-y-1.5 col-span-2">
                                        <label className="text-xs font-bold text-slate-700">Catatan Invoice</label>
                                        <Input 
                                            placeholder="Tambahkan catatan khusus invoice..." 
                                            value={validationForm.catatan} 
                                            onChange={(e) => setValidationForm({ ...validationForm, catatan: e.target.value })}
                                            className="bg-white rounded-xl"
                                        />
                                    </div>
                                </div>

                                {/* Financial Live Preview Box */}
                                <div className="rounded-xl border border-indigo-100 bg-indigo-50/20 p-4 space-y-2 mt-2">
                                    <h4 className="text-xs font-extrabold text-indigo-900 uppercase tracking-wider mb-2 flex items-center gap-1">
                                        <Info className="h-3.5 w-3.5 text-indigo-600" />
                                        Estimasi Perubahan Finansial PO
                                    </h4>
                                    <div className="grid grid-cols-2 gap-2 text-xs font-semibold">
                                        <div className="flex justify-between text-slate-500">
                                            <span>Subtotal Tagihan PO</span>
                                            <span className="font-mono">{formatRupiah(valPreview.tagihan)}</span>
                                        </div>
                                        <div className="flex justify-between text-slate-500">
                                            <span>Diskon Penjualan</span>
                                            <span className="font-mono text-red-600">-{formatRupiah(valPreview.diskon)}</span>
                                        </div>
                                        <div className="flex justify-between text-slate-500">
                                            <span>Biaya Pengiriman</span>
                                            <span className="font-mono text-emerald-600">+{formatRupiah(valPreview.shipping)}</span>
                                        </div>
                                        <div className="flex justify-between text-slate-500">
                                            <span>DP / Uang Muka Terbayar</span>
                                            <span className="font-mono text-indigo-700">-{formatRupiah(selectedInvoiceToValidate.dp_amount || 0)}</span>
                                        </div>
                                        <div className="flex justify-between border-t border-indigo-100 pt-2 col-span-2">
                                            <span className="font-bold text-indigo-950">Sisa Piutang Akhir</span>
                                            <span className="font-mono font-black text-indigo-900 text-sm">{formatRupiah(valPreview.sisa)}</span>
                                        </div>
                                    </div>
                                </div>

                                <DialogFooter className="pt-2">
                                    <Button type="button" variant="outline" onClick={() => setSelectedInvoiceToValidate(null)} className="rounded-xl">Batal</Button>
                                    <Button 
                                        type="submit" 
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl"
                                        disabled={isSubmitting}
                                    >
                                        {isSubmitting ? 'Memproses...' : 'Validasi Sekarang'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        )}
                    </DialogContent>
                </Dialog>

                {/* Publish Invoice Dialog */}
                <Dialog open={!!confirmPublish} onOpenChange={(open) => !open && setConfirmPublish(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-emerald-700 font-bold">
                                <CheckedIcon className="h-5 w-5" />
                                Publish Invoice Resmi
                            </DialogTitle>
                            <DialogDescription>
                                Apakah Anda yakin ingin mempublish invoice <strong>{confirmPublish?.invoice_number}</strong>? Status akan diubah ke PUBLISHED dan invoice siap dikirim ke customer.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setConfirmPublish(null)} className="rounded-xl">Batal</Button>
                            <Button 
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl"
                                onClick={() => publishInvoice(confirmPublish)}
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Memproses...' : 'Ya, Publish'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

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
                                    <label className="text-xs font-bold text-slate-700">Brand *</label>
                                    <Select 
                                        value={newDeposit.brand_id} 
                                        onValueChange={(v) => {
                                            const firstBank = bank_accounts.find(bank => bank.brand_id === v || !bank.brand_id);
                                            setNewDeposit({ 
                                                ...newDeposit, 
                                                brand_id: v,
                                                bank_id: firstBank?.id || '',
                                                customer_id: '',
                                                customer_name: ''
                                            });
                                        }}
                                    >
                                        <SelectTrigger className="bg-white rounded-xl"><SelectValue placeholder="Pilih Brand" /></SelectTrigger>
                                        <SelectContent>
                                            {brands.map(b => (
                                                <SelectItem key={b.id} value={b.id}>{b.nama_brand}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-xs font-bold text-slate-700">Nama Customer *</label>
                                    <Select
                                        value={newDeposit.customer_id}
                                        onValueChange={(v) => {
                                            const cust = customers.find(c => c.id === v);
                                            setNewDeposit({
                                                ...newDeposit,
                                                customer_id: v,
                                                customer_name: cust ? cust.nama : ''
                                            });
                                        }}
                                        required
                                    >
                                        <SelectTrigger className="bg-white rounded-xl">
                                            <SelectValue placeholder={newDeposit.brand_id ? "Pilih Customer" : "Pilih Brand Terlebih Dahulu"} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {customers.filter(c => c.brand_id === newDeposit.brand_id).map(cust => (
                                                <SelectItem key={cust.id} value={cust.id}>{cust.nama}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-slate-700">Deskripsi Desain (Contoh: Bomber Hoodie NIS)</label>
                                <Input 
                                    placeholder="Apa yang akan didesain?" 
                                    value={newDeposit.description} 
                                    onChange={(e) => setNewDeposit({ ...newDeposit, description: e.target.value })}
                                    className="bg-white rounded-xl"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <label className="text-xs font-bold text-slate-700">Nominal Tanda Jadi *</label>
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                                        <Input 
                                            required 
                                            type="number"
                                            placeholder="500000" 
                                            value={newDeposit.amount} 
                                            onChange={(e) => setNewDeposit({ ...newDeposit, amount: e.target.value })}
                                            className="pl-8 bg-white rounded-xl"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-xs font-bold text-slate-700">Tanggal Bayar *</label>
                                    <Input 
                                        required 
                                        type="date" 
                                        value={newDeposit.payment_date} 
                                        onChange={(e) => setNewDeposit({ ...newDeposit, payment_date: e.target.value })}
                                        className="bg-white rounded-xl"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-slate-700">Bank Tujuan Transfer *</label>
                                <Select 
                                    value={newDeposit.bank_id} 
                                    onValueChange={(v) => setNewDeposit({ ...newDeposit, bank_id: v })}
                                >
                                    <SelectTrigger className="bg-white rounded-xl"><SelectValue placeholder="Pilih Bank" /></SelectTrigger>
                                    <SelectContent>
                                        {bank_accounts.filter(bank => !bank.brand_id || bank.brand_id === newDeposit.brand_id).map(bank => (
                                            <SelectItem key={bank.id} value={bank.id}>{bank.bank} — {bank.nomor_rekening} ({bank.atas_nama})</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-slate-700">Catatan Lainnya</label>
                                <Input 
                                    placeholder="Tambahkan catatan jika diperlukan..." 
                                    value={newDeposit.notes} 
                                    onChange={(e) => setNewDeposit({ ...newDeposit, notes: e.target.value })}
                                    className="bg-white rounded-xl"
                                />
                            </div>

                            <DialogFooter className="pt-2">
                                <Button type="button" variant="outline" onClick={() => setShowAddDeposit(false)} className="rounded-xl">Batal</Button>
                                <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl" disabled={isSubmitting}>
                                    {isSubmitting ? 'Menyimpan...' : 'Simpan Tanda Jadi'}
                                </Button>
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
                            <Button variant="outline" onClick={() => setConfirmDepositVerify(null)} className="rounded-xl">Batal</Button>
                            <Button 
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl"
                                onClick={() => verifyDeposit(confirmDepositVerify)}
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Memproses...' : 'Ya, Validasi TJ'}
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
                            <Button variant="outline" onClick={() => setConfirmDepositRefund(null)} className="rounded-xl">Batal</Button>
                            <Button 
                                variant="destructive"
                                className="font-bold rounded-xl"
                                onClick={() => refundDeposit(confirmDepositRefund)}
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Memproses...' : 'Ya, Proses Refund'}
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
                                        <SelectTrigger className="bg-white rounded-xl"><SelectValue placeholder="Pilih Purchase Order" /></SelectTrigger>
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
                                    <Button type="button" variant="outline" onClick={() => setConvertDeposit(null)} className="rounded-xl">Batal</Button>
                                    <Button 
                                        type="submit" 
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl"
                                        disabled={!selectedOrderId || isSubmitting}
                                    >
                                        {isSubmitting ? 'Mengonversi...' : 'Konversikan Sekarang'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
