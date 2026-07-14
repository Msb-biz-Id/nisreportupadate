import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import {
    Search, Receipt, CheckCircle2, ExternalLink, Copy, Calendar,
    ShieldCheck, Clock, Banknote, AlertTriangle, Plus, Sparkles,
    Layers, RefreshCw, FileText, ChevronRight, CheckCircle, HelpCircle, XCircle,
    Info, Percent, Truck, Wallet, FileSpreadsheet, CheckCircle2 as CheckedIcon,
    MessageCircle, Send, Edit, Trash2
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

const StickyScrollbar = ({ targetRef, minWidth = '1200px' }) => {
    const scrollbarRef = useRef(null);
    const [show, setShow] = useState(false);
    const [width, setWidth] = useState(minWidth);

    useEffect(() => {
        const target = targetRef.current;
        if (!target) return;

        let frameId = null;
        const updateSize = () => {
            if (frameId) cancelAnimationFrame(frameId);
            frameId = requestAnimationFrame(() => {
                const scrollWidth = target.scrollWidth;
                const clientWidth = target.clientWidth;
                const hasOverflow = scrollWidth > clientWidth;
                setShow((prev) => (prev !== hasOverflow ? hasOverflow : prev));
                setWidth((prev) => {
                    const nextWidth = `${scrollWidth}px`;
                    return prev !== nextWidth ? nextWidth : prev;
                });
            });
        };

        updateSize();
        const resizeObserver = new ResizeObserver(updateSize);
        resizeObserver.observe(target);

        let isSyncingTarget = false;
        let isSyncingScrollbar = false;

        const handleTargetScroll = () => {
            if (isSyncingScrollbar) {
                isSyncingScrollbar = false;
                return;
            }
            if (scrollbarRef.current) {
                isSyncingTarget = true;
                scrollbarRef.current.scrollLeft = target.scrollLeft;
            }
        };

        const handleScrollbarScroll = () => {
            if (isSyncingTarget) {
                isSyncingTarget = false;
                return;
            }
            if (scrollbarRef.current) {
                isSyncingScrollbar = true;
                target.scrollLeft = scrollbarRef.current.scrollLeft;
            }
        };

        target.addEventListener('scroll', handleTargetScroll);
        const scrollbarEl = scrollbarRef.current;
        if (scrollbarEl) {
            scrollbarEl.addEventListener('scroll', handleScrollbarScroll);
        }

        return () => {
            if (frameId) cancelAnimationFrame(frameId);
            resizeObserver.disconnect();
            target.removeEventListener('scroll', handleTargetScroll);
            if (scrollbarEl) {
                scrollbarEl.removeEventListener('scroll', handleScrollbarScroll);
            }
        };
    }, [targetRef]);

    if (!show) return null;

    return (
        <>
            <style>{`
                .hide-scrollbar-x::-webkit-scrollbar {
                    height: 0px;
                    background: transparent;
                }
                .hide-scrollbar-x {
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                }
            `}</style>
            <div
                ref={scrollbarRef}
                className="sticky bottom-0 left-0 right-0 z-40 w-full overflow-x-auto bg-slate-50 border-t border-slate-200"
                style={{ height: '12px' }}
            >
                <div style={{ width, height: '1px' }} />
            </div>
        </>
    );
};

export default function InvoiceList({ 
    invoices, 
    all_filtered_invoices = [], 
    brands = [], 
    design_deposits = [], 
    available_orders = [], 
    bank_accounts = [], 
    customers = [],
    master_jenis_pembayarans = [],
    filters = {}, 
    statuses = [], 
    can = {} 
}) {
    const { auth, brandContext } = usePage().props;
    const tableContainerRef = useRef(null);
    const user = auth?.user;
    const hasFinanceView = user?.permissions?.includes('finance.view') || user?.roles?.includes('superadmin') || user?.roles?.includes('owner') || user?.roles?.includes('admin_keuangan');
    const dashboardUrl = hasFinanceView ? route('invoices.index') : route('dashboard');

    // Get initial tab from query params if available
    const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
    const tabParam = urlParams ? urlParams.get('tab') : null;
    const [activeTab, setActiveTab] = useState(tabParam || 'belum_lunas');

    useEffect(() => {
        if (tabParam && ['belum_lunas', 'sudah_lunas', 'tanda_jadi'].includes(tabParam)) {
            setActiveTab(tabParam);
        }
    }, [tabParam]);
    
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

    // Editing Payment state
    const [editingPayment, setEditingPayment] = useState(null);
    const [editPaymentForm, setEditPaymentForm] = useState({
        master_jenis_pembayaran_id: '',
        amount: '',
        payment_date: '',
        bank_id: '',
        notes: '',
        change_reason: ''
    });

    const currentInvoiceToValidate = selectedInvoiceToValidate
        ? (invoices?.data || []).find(inv => inv.id === selectedInvoiceToValidate.id) || selectedInvoiceToValidate
        : null;

    const currentFilterBrand = filters?.brand_id && filters.brand_id !== 'all' ? filters.brand_id : '';
    const initialBrandId = currentFilterBrand || brandContext?.current?.id || brands[0]?.id || '';
    const initialDepositBank = bank_accounts.find(b => b.brand_id === initialBrandId) || bank_accounts.find(b => !b.brand_id) || bank_accounts[0];

    // Validate Form State
    const [validationForm, setValidationForm] = useState({
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

    useEffect(() => {
        if (showAddDeposit) {
            const currentFilterBrand = filters?.brand_id && filters.brand_id !== 'all' ? filters.brand_id : '';
            const activeBrandId = currentFilterBrand || brandContext?.current?.id || brands[0]?.id || '';
            const activeBank = bank_accounts.find(b => b.brand_id === activeBrandId) || bank_accounts.find(b => !b.brand_id) || bank_accounts[0];
            setNewDeposit(prev => ({
                ...prev,
                brand_id: activeBrandId,
                bank_id: activeBank?.id || '',
                customer_id: '',
                customer_name: '',
                amount: '',
                description: '',
                notes: ''
            }));
        }
    }, [showAddDeposit, filters?.brand_id, brandContext?.current?.id]);

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
            bank_id: invoice.bank_id || firstBrandBank?.id || '',
            catatan: invoice.catatan || ''
        });
    }

    function submitValidation(e) {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(route('invoices.validate', currentInvoiceToValidate.id), validationForm, {
            onSuccess: () => {
                setSelectedInvoiceToValidate(null);
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false)
        });
    }

    useEffect(() => {
        if (editingPayment) {
            setEditPaymentForm({
                master_jenis_pembayaran_id: editingPayment.master_jenis_pembayaran_id || '',
                amount: String(editingPayment.amount || 0),
                payment_date: editingPayment.payment_date ? editingPayment.payment_date.split('T')[0] : '',
                bank_id: editingPayment.bank_id || '',
                notes: editingPayment.notes || '',
                change_reason: ''
            });
        }
    }, [editingPayment]);

    function submitEditPayment(e) {
        e.preventDefault();
        setIsSubmitting(true);
        router.put(route('invoices.payments.update', editingPayment.id), editPaymentForm, {
            onSuccess: () => {
                setEditingPayment(null);
                setIsSubmitting(false);
            },
            onError: () => setIsSubmitting(false)
        });
    }

    // Live calculation for preview during validation
    const getValidationPreview = () => {
        if (!currentInvoiceToValidate) return { rawSubtotal: 0, tagihan: 0, sisa: 0, diskon: 0, shipping: 0, totalPaid: 0 };
        
        const rawSubtotal = (currentInvoiceToValidate.order?.items || [])
            .reduce((sum, item) => sum + (Number(item.quantity) * Number(item.harga_satuan)), 0);

        const diskon = (currentInvoiceToValidate.order?.items || [])
            .reduce((sum, item) => sum + (Number(item.discount_amount) || 0), 0);

        const shipping = (currentInvoiceToValidate.order?.payments || [])
            .filter(p => p.verified_at !== null && (p.payment_type === 'ongkir' || p.master_jenis_pembayaran?.nama === 'Ongkir'))
            .reduce((sum, p) => sum + (Number(p.amount) || 0), 0);

        const tagihan = Number(currentInvoiceToValidate.order?.total_tagihan) || Number(currentInvoiceToValidate.total_tagihan) || 0;
        
        const totalPaid = (currentInvoiceToValidate.order?.payments || [])
            .filter(p => p.verified_at !== null)
            .reduce((sum, p) => {
                const amt = Number(p.amount) || 0;
                const isNeg = ['cashback', 'return'].includes(p.payment_type) || !p.is_debit;
                return isNeg ? sum - amt : sum + amt;
            }, 0);

        const sisa = Math.max(0, tagihan - totalPaid);

        return { rawSubtotal, tagihan, sisa, diskon, shipping, totalPaid };
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
                    <CardContent className="p-6 pt-0">
                        <div ref={tableContainerRef} className="overflow-auto max-h-[calc(100vh-320px)] rounded-lg border hide-scrollbar-x">
                            {activeTab === 'belum_lunas' && (
                            <Table className="min-w-[1200px] border-collapse">
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="sticky top-0 left-0 z-30 bg-slate-50 font-bold text-slate-700 min-w-[150px] w-[150px] shadow-[inset_-1px_0_0_0_#e2e8f0]">No. Invoice</TableHead>
                                        <TableHead className="sticky top-0 left-[150px] z-30 bg-slate-50 font-bold text-slate-700 min-w-[180px] w-[180px] shadow-[inset_-1px_0_0_0_#e2e8f0]">No. PO</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 min-w-[180px]">Nama PO</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 min-w-[180px]">Pelanggan</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 min-w-[120px]">Tgl Terbit</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 text-right min-w-[130px]">Total Tagihan</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 text-right text-orange-600 min-w-[130px]">Sisa Tagihan</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 min-w-[100px]">Status</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 text-right min-w-[160px]">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {unpaidInvoices.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={9} className="py-16 text-center text-sm text-slate-400 italic">
                                                Tidak ada invoice belum lunas.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        unpaidInvoices.map((iv) => (
                                            <TableRow key={iv.id} className="group hover:bg-slate-50/50">
                                                <TableCell className="sticky left-0 z-10 bg-white font-mono text-xs font-bold text-slate-800 min-w-[150px] w-[150px] shadow-[inset_-1px_0_0_0_#e2e8f0] group-hover:bg-slate-50 transition-colors">{iv.invoice_number}</TableCell>
                                                <TableCell className="sticky left-[150px] z-10 bg-white font-mono text-xs text-slate-500 min-w-[180px] w-[180px] shadow-[inset_-1px_0_0_0_#e2e8f0] group-hover:bg-slate-50 transition-colors">{iv.order?.no_po}</TableCell>
                                                <TableCell className="text-xs text-slate-600 font-medium min-w-[180px]">{iv.order?.nama_po ?? '-'}</TableCell>
                                                <TableCell className="font-bold text-slate-800 min-w-[180px]">{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                                <TableCell className="text-xs text-slate-500 min-w-[120px]">{formatDate(iv.tanggal_terbit)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-bold min-w-[130px]">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-black text-orange-600 min-w-[130px]">{formatRupiah(iv.sisa_pembayaran)}</TableCell>
                                                <TableCell className="min-w-[100px]"><Badge variant={STATUS_VARIANT[iv.status] ?? 'outline'}>{iv.status}</Badge></TableCell>
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
                                                        {can?.create && iv.status === 'validated' && (
                                                            <Button
                                                                size="xs"
                                                                variant="destructive"
                                                                className="text-xs font-bold py-1 rounded-lg shadow-sm font-sans"
                                                                onClick={() => {
                                                                    if (confirm(`Yakin ingin membatalkan validasi untuk Invoice ${iv.invoice_number}? Status akan dikembalikan ke draft.`)) {
                                                                        router.post(route('invoices.cancel-validation', iv.id));
                                                                    }
                                                                }}
                                                            >
                                                                Batal Validasi
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
                            <Table className="min-w-[1200px] border-collapse">
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="sticky top-0 left-0 z-30 bg-slate-50 font-bold text-slate-700 min-w-[150px] w-[150px] shadow-[inset_-1px_0_0_0_#e2e8f0]">No. Invoice</TableHead>
                                        <TableHead className="sticky top-0 left-[150px] z-30 bg-slate-50 font-bold text-slate-700 min-w-[180px] w-[180px] shadow-[inset_-1px_0_0_0_#e2e8f0]">No. PO</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 min-w-[180px]">Nama PO</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 min-w-[180px]">Pelanggan</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 min-w-[120px]">Tgl Terbit</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 text-right min-w-[130px]">Total Tagihan</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 text-right text-emerald-600 min-w-[130px]">Total Terbayar</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 min-w-[100px]">Status</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 text-right min-w-[160px]">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {paidInvoices.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={9} className="py-16 text-center text-sm text-slate-400 italic">
                                                Tidak ada invoice lunas.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        paidInvoices.map((iv) => (
                                            <TableRow key={iv.id} className="group hover:bg-slate-50/50">
                                                <TableCell className="sticky left-0 z-10 bg-white font-mono text-xs font-bold text-slate-800 min-w-[150px] w-[150px] shadow-[inset_-1px_0_0_0_#e2e8f0] group-hover:bg-slate-50 transition-colors">{iv.invoice_number}</TableCell>
                                                <TableCell className="sticky left-[150px] z-10 bg-white font-mono text-xs text-slate-500 min-w-[180px] w-[180px] shadow-[inset_-1px_0_0_0_#e2e8f0] group-hover:bg-slate-50 transition-colors">{iv.order?.no_po}</TableCell>
                                                <TableCell className="text-xs text-slate-600 font-medium min-w-[180px]">{iv.order?.nama_po ?? '-'}</TableCell>
                                                <TableCell className="font-bold text-slate-800 min-w-[180px]">{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                                <TableCell className="text-xs text-slate-500 min-w-[120px]">{formatDate(iv.tanggal_terbit)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-bold min-w-[130px]">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell className="text-right font-mono text-xs font-black text-emerald-600 min-w-[130px]">{formatRupiah(iv.total_tagihan)}</TableCell>
                                                <TableCell className="min-w-[100px]"><Badge variant="success">LUNAS</Badge></TableCell>
                                                <TableCell className="text-right min-w-[160px]">
                                                    <div className="flex justify-end gap-1.5">
                                                        <Button asChild size="xs" variant="outline" className="text-xs py-1 rounded-lg">
                                                            <a href={route('invoice.public', iv.invoice_number)} target="_blank" rel="noopener noreferrer">
                                                                <ExternalLink className="h-3 w-3 mr-1" /> PDF Detail
                                                            </a>
                                                        </Button>
                                                        {can?.create && (
                                                            <Button
                                                                size="xs"
                                                                variant="destructive"
                                                                className="text-xs font-bold py-1 rounded-lg shadow-sm font-sans"
                                                                onClick={() => {
                                                                    if (confirm(`Yakin ingin membatalkan validasi untuk Invoice ${iv.invoice_number}? Status akan dikembalikan ke draft.`)) {
                                                                        router.post(route('invoices.cancel-validation', iv.id));
                                                                    }
                                                                }}
                                                            >
                                                                Batal Validasi
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

                        {activeTab === 'tanda_jadi' && (
                            <Table className="min-w-[1200px] border-collapse">
                                <TableHeader className="bg-slate-50">
                                    <TableRow>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700">No. Tanda Jadi</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700">Brand</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700">Customer</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700">Deskripsi Desain</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700">Tanggal</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 text-right">Nominal</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700">Status</TableHead>
                                        <TableHead className="sticky top-0 z-20 bg-slate-50 font-bold text-slate-700 text-right">Aksi</TableHead>
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
                        </div>
                    </CardContent>
                </Card>
                <StickyScrollbar targetRef={tableContainerRef} minWidth="1200px" />

                {/* MODALS & DIALOGS */}

                <Dialog open={!!selectedInvoiceToValidate} onOpenChange={(open) => !open && setSelectedInvoiceToValidate(null)}>
                    <DialogContent className="lg:max-w-[1150px] md:max-w-[950px] sm:max-w-[850px] w-[95vw] max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-indigo-700 font-bold">
                                <ShieldCheck className="h-5 w-5 text-indigo-600" />
                                Validasi Invoice Draft ({currentInvoiceToValidate?.invoice_number})
                            </DialogTitle>
                            <DialogDescription>
                                Lengkapi rincian biaya tambahan (ongkir), diskon, dan kelola record pembayaran PO di panel sebelah kanan sebelum memvalidasi.
                            </DialogDescription>
                        </DialogHeader>
                        {currentInvoiceToValidate && (
                            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 py-2">
                                {/* Left side: Form & Financial Preview */}
                                <div className="lg:col-span-5 space-y-4">
                                    <form onSubmit={submitValidation} className="space-y-4">
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
                                                        {bank_accounts.filter(bank => !bank.brand_id || bank.brand_id === currentInvoiceToValidate?.brand_id).map(bank => (
                                                            <SelectItem key={bank.id} value={bank.id}>
                                                                {bank.bank} — {bank.nomor_rekening} ({bank.atas_nama})
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-1.5 col-span-2">
                                                <label className="text-xs font-bold text-slate-500">Jasa Pengiriman / Ekspedisi (dari Modul Produksi)</label>
                                                <Input 
                                                    value={currentInvoiceToValidate?.order?.nama_ekspedisi || 'Belum ditentukan di modul produksi'} 
                                                    disabled 
                                                    className="bg-slate-50 rounded-xl border-slate-200 text-slate-500 font-semibold"
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
                                                Rincian Keuangan PO (Database)
                                            </h4>
                                            <div className="space-y-1.5 text-xs font-semibold">
                                                <div className="flex justify-between text-slate-500">
                                                    <span>Subtotal Item PO</span>
                                                    <span className="font-mono">{formatRupiah(valPreview.rawSubtotal)}</span>
                                                </div>
                                                <div className="flex justify-between text-slate-500">
                                                     <span>Diskon PO (dari Item)</span>
                                                    <span className="font-mono text-red-600">-{formatRupiah(valPreview.diskon)}</span>
                                                </div>
                                                <div className="flex justify-between text-slate-500">
                                                    <span>Biaya Pengiriman (Ongkir)</span>
                                                    <span className="font-mono text-emerald-600">+{formatRupiah(valPreview.shipping)}</span>
                                                </div>
                                                <div className="flex justify-between border-t border-slate-200/60 pt-1.5 text-slate-700">
                                                    <span>Total Tagihan Akhir PO</span>
                                                    <span className="font-mono font-bold">{formatRupiah(valPreview.tagihan)}</span>
                                                </div>
                                                <div className="flex justify-between text-slate-500">
                                                    <span>Total Pembayaran (Verified)</span>
                                                    <span className="font-mono text-indigo-700">-{formatRupiah(valPreview.totalPaid)}</span>
                                                </div>
                                                <div className="flex justify-between border-t border-indigo-200 pt-2">
                                                    <span className="font-bold text-indigo-950">Sisa Piutang Akhir</span>
                                                    <span className="font-mono font-black text-indigo-900 text-sm">{formatRupiah(valPreview.sisa)}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <DialogFooter className="pt-2 flex justify-end gap-2">
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
                                </div>

                                {/* Right side: Payments Audit & Adjustment Ledger */}
                                <div className="lg:col-span-7 border-t lg:border-t-0 lg:border-l pt-6 lg:pt-0 lg:pl-6 border-slate-100 space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2">
                                            <Banknote className="h-4 w-4 text-emerald-600" />
                                            Record Pembayaran & Penyesuaian PO
                                        </h3>
                                        <span className="text-xs font-semibold text-slate-500">
                                            {(currentInvoiceToValidate.order?.payments || []).length} Record
                                        </span>
                                    </div>

                                    <div className="overflow-hidden rounded-xl border border-slate-100 bg-slate-50/50">
                                        <div>
                                            <table className="w-full text-left border-collapse text-xs">
                                                <thead>
                                                    <tr className="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                                        <th className="p-2.5">Tipe & Deskripsi</th>
                                                        <th className="p-2.5">Bank & Tanggal</th>
                                                        <th className="p-2.5 text-right">Nominal</th>
                                                        <th className="p-2.5 text-center">Status</th>
                                                        <th className="p-2.5 text-center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100 bg-white">
                                                    {(currentInvoiceToValidate.order?.payments || []).length === 0 ? (
                                                        <tr>
                                                            <td colSpan="5" className="p-6 text-center text-slate-400 font-medium italic">
                                                                Belum ada record pembayaran untuk PO ini.
                                                            </td>
                                                        </tr>
                                                    ) : (
                                                        (currentInvoiceToValidate.order?.payments || []).map((payment) => {
                                                            const isNeg = ['cashback', 'return'].includes(payment.payment_type) || !payment.is_debit;
                                                            const amt = Number(payment.amount) || 0;
                                                            return (
                                                                <tr key={payment.id} className="hover:bg-slate-50/50 transition-colors">
                                                                    <td className="p-2.5">
                                                                        <div className="font-bold text-slate-800">
                                                                            {payment.master_jenis_pembayaran?.nama === 'Return' || payment.master_jenis_pembayaran?.nama === 'Refurn'
                                                                                ? 'Refund'
                                                                                : (payment.master_jenis_pembayaran?.nama || (payment.payment_type === 'return' ? 'Refund' : payment.payment_type?.toUpperCase()))}
                                                                        </div>
                                                                        {payment.master_jenis_pembayaran?.deskripsi && (
                                                                            <div className="text-[10px] text-slate-500 max-w-[180px] truncate" title={payment.master_jenis_pembayaran.deskripsi}>
                                                                                {payment.master_jenis_pembayaran.deskripsi}
                                                                            </div>
                                                                        )}
                                                                        {payment.notes && (
                                                                            <div className="text-[10px] text-slate-400 italic max-w-[180px] truncate" title={payment.notes}>
                                                                                Note: {payment.notes}
                                                                            </div>
                                                                        )}
                                                                    </td>
                                                                    <td className="p-2.5 text-slate-600">
                                                                        <div className="font-medium">{payment.bank?.bank || '—'}</div>
                                                                        <div className="text-[10px] text-slate-400">{formatDate(payment.payment_date)}</div>
                                                                    </td>
                                                                    <td className="p-2.5 text-right font-mono font-bold">
                                                                        <span className={isNeg ? "text-red-600" : "text-emerald-700"}>
                                                                            {isNeg ? '-' : ''}{formatRupiah(amt)}
                                                                        </span>
                                                                    </td>
                                                                    <td className="p-2.5 text-center">
                                                                        {payment.verified_at ? (
                                                                            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                                                                                Verified
                                                                            </span>
                                                                        ) : (
                                                                            <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 border border-amber-200">
                                                                                Pending
                                                                            </span>
                                                                        )}
                                                                    </td>
                                                                    <td className="p-2.5">
                                                                        <div className="flex items-center justify-center gap-1">
                                                                            <Button
                                                                                type="button"
                                                                                size="icon"
                                                                                variant="ghost"
                                                                                className="h-7 w-7 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg"
                                                                                onClick={() => setEditingPayment(payment)}
                                                                                disabled={isSubmitting}
                                                                            >
                                                                                <Edit className="h-3.5 w-3.5" />
                                                                            </Button>
                                                                            <Button
                                                                                type="button"
                                                                                size="icon"
                                                                                variant="ghost"
                                                                                className="h-7 w-7 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg"
                                                                                onClick={() => {
                                                                                    if (confirm('Apakah Anda yakin ingin menghapus record pembayaran ini? Tindakan ini akan menghapus data di ledger jika sudah terverifikasi.')) {
                                                                                        setIsSubmitting(true);
                                                                                        router.delete(route('invoices.payments.destroy', payment.id), {
                                                                                            onSuccess: () => setIsSubmitting(false),
                                                                                            onError: () => setIsSubmitting(false)
                                                                                        });
                                                                                    }
                                                                                }}
                                                                                disabled={isSubmitting}
                                                                            >
                                                                                <Trash2 className="h-3.5 w-3.5" />
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
                                    </div>
                                </div>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>

                {/* Edit Payment Sub-dialog */}
                <Dialog open={!!editingPayment} onOpenChange={(open) => !open && setEditingPayment(null)}>
                    <DialogContent className="sm:max-w-[450px]">
                        <DialogHeader>
                            <DialogTitle className="text-slate-800 font-bold">Edit Record Pembayaran</DialogTitle>
                            <DialogDescription>
                                Sesuaikan detail pembayaran. Alasan perubahan wajib diisi untuk audit log.
                            </DialogDescription>
                        </DialogHeader>
                        {editingPayment && (
                            <form onSubmit={submitEditPayment} className="space-y-4 py-2">
                                <div className="space-y-3">
                                    {/* Select Master Jenis Pembayaran */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Jenis Pembayaran *</label>
                                        <Select 
                                            value={editPaymentForm.master_jenis_pembayaran_id}
                                            onValueChange={(v) => setEditPaymentForm({ ...editPaymentForm, master_jenis_pembayaran_id: v })}
                                        >
                                            <SelectTrigger className="bg-white rounded-xl"><SelectValue placeholder="Pilih Jenis" /></SelectTrigger>
                                            <SelectContent>
                                                {master_jenis_pembayarans.map(m => (
                                                    <SelectItem key={m.id} value={m.id}>{m.nama} ({m.tipe_keuangan.toUpperCase()})</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    
                                    {/* Amount */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Nominal *</label>
                                        <Input 
                                            type="number"
                                            min="0"
                                            value={editPaymentForm.amount}
                                            onChange={(e) => setEditPaymentForm({ ...editPaymentForm, amount: e.target.value })}
                                            className="bg-white rounded-xl"
                                            required
                                        />
                                    </div>
                                    
                                    {/* Date */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Tanggal Pembayaran *</label>
                                        <Input 
                                            type="date"
                                            value={editPaymentForm.payment_date}
                                            onChange={(e) => setEditPaymentForm({ ...editPaymentForm, payment_date: e.target.value })}
                                            className="bg-white rounded-xl"
                                            required
                                        />
                                    </div>

                                    {/* Bank Account */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Bank *</label>
                                        <Select 
                                            value={editPaymentForm.bank_id}
                                            onValueChange={(v) => setEditPaymentForm({ ...editPaymentForm, bank_id: v })}
                                        >
                                            <SelectTrigger className="bg-white rounded-xl"><SelectValue placeholder="Pilih Bank" /></SelectTrigger>
                                            <SelectContent>
                                                {bank_accounts.map(bank => (
                                                    <SelectItem key={bank.id} value={bank.id}>
                                                        {bank.bank} — {bank.nomor_rekening} ({bank.atas_nama})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    
                                    {/* Notes */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-slate-700">Catatan</label>
                                        <Input 
                                            value={editPaymentForm.notes}
                                            onChange={(e) => setEditPaymentForm({ ...editPaymentForm, notes: e.target.value })}
                                            className="bg-white rounded-xl"
                                            placeholder="Catatan pembayaran"
                                        />
                                    </div>

                                    {/* Change Reason */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-bold text-red-700">Alasan Perubahan * (Minimal 5 karakter)</label>
                                        <Input 
                                            value={editPaymentForm.change_reason}
                                            onChange={(e) => setEditPaymentForm({ ...editPaymentForm, change_reason: e.target.value })}
                                            className="bg-white rounded-xl border-red-200 focus:border-red-500"
                                            placeholder="Alasan mengubah data pembayaran ini..."
                                            required
                                        />
                                    </div>
                                </div>
                                
                                <DialogFooter className="pt-2">
                                    <Button type="button" variant="outline" onClick={() => setEditingPayment(null)} className="rounded-xl">Batal</Button>
                                    <Button 
                                        type="submit" 
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl"
                                        disabled={isSubmitting || editPaymentForm.change_reason.trim().length < 5}
                                    >
                                        {isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'}
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
