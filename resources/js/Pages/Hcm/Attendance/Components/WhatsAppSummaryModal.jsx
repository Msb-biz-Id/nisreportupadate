import { useState, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import {
    Copy,
    Check,
    ExternalLink,
    MessageSquare,
    Calendar,
    Users,
    AlertCircle,
    CheckCircle2,
    Clock,
    RefreshCw,
} from 'lucide-react';

export default function WhatsAppSummaryModal({
    isOpen,
    onClose,
    defaultType = 'daily',
    initialDate = new Date().toISOString().split('T')[0],
    initialMonth = new Date().toISOString().substring(0, 7),
    initialCategory = 'all',
    initialDepartment = 'all',
}) {
    const [reportType, setReportType] = useState(defaultType); // 'daily' | 'monthly'
    const [targetDate, setTargetDate] = useState(initialDate);
    const [targetMonth, setTargetMonth] = useState(initialMonth);
    const [targetCategory, setTargetCategory] = useState(initialCategory);
    const [targetDepartment, setTargetDepartment] = useState(initialDepartment);

    const [isLoading, setIsLoading] = useState(false);
    const [waText, setWaText] = useState('');
    const [stats, setStats] = useState(null);
    const [isCopied, setIsCopied] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setReportType(defaultType);
            setTargetDate(initialDate);
            setTargetMonth(initialMonth);
            setTargetCategory(initialCategory);
            setTargetDepartment(initialDepartment);
            fetchSummary(defaultType, initialDate, initialMonth, initialCategory, initialDepartment);
        }
    }, [isOpen, defaultType, initialDate, initialMonth, initialCategory, initialDepartment]);

    const fetchSummary = async (type, date, month, cat, dept) => {
        setIsLoading(true);
        setIsCopied(false);
        try {
            const params = new URLSearchParams({
                type,
                date,
                month,
                category: cat,
                department: dept,
            });
            const res = await fetch(`/hcm/attendance/wa-summary?${params.toString()}`);
            const data = await res.json();
            if (data.success) {
                setWaText(data.text);
                setStats(data.stats);
            }
        } catch (err) {
            console.error('Failed to fetch WhatsApp summary:', err);
            setWaText('Gagal memuat ringkasan. Silakan coba kembali.');
        } finally {
            setIsLoading(false);
        }
    };

    const handleCopy = async () => {
        if (!waText) return;
        try {
            await navigator.clipboard.writeText(waText);
            setIsCopied(true);
            setTimeout(() => setIsCopied(false), 2500);
        } catch (err) {
            console.error('Failed to copy text:', err);
        }
    };

    const handleOpenWhatsAppWeb = () => {
        if (!waText) return;
        const encoded = encodeURIComponent(waText);
        window.open(`https://web.whatsapp.com/send?text=${encoded}`, '_blank');
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl max-h-[90vh] flex flex-col p-0 overflow-hidden">
                {/* Header */}
                <DialogHeader className="p-5 pb-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2.5">
                            <div className="p-2 rounded-xl bg-emerald-600 text-white shadow-xs">
                                <MessageSquare className="h-5 w-5" />
                            </div>
                            <div>
                                <DialogTitle className="text-base font-bold text-zinc-900 dark:text-zinc-100">
                                    Generator Ringkasan WhatsApp Presensi
                                </DialogTitle>
                                <DialogDescription className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Salin format laporan siap blast ke grup Manajemen, Direksi, atau Pembimbing Magang.
                                </DialogDescription>
                            </div>
                        </div>
                    </div>

                    {/* Filter Kontrol Mini di Header */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-4">
                        {/* Tab Switcher Tipe */}
                        <div className="flex items-center p-1 bg-zinc-200/70 dark:bg-zinc-800 rounded-lg">
                            <button
                                type="button"
                                onClick={() => {
                                    setReportType('daily');
                                    fetchSummary('daily', targetDate, targetMonth, targetCategory, targetDepartment);
                                }}
                                className={`flex-1 py-1 text-xs font-semibold rounded-md transition-all ${
                                    reportType === 'daily'
                                        ? 'bg-white dark:bg-zinc-900 text-emerald-700 dark:text-emerald-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'
                                }`}
                            >
                                Rekap Harian
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setReportType('monthly');
                                    fetchSummary('monthly', targetDate, targetMonth, targetCategory, targetDepartment);
                                }}
                                className={`flex-1 py-1 text-xs font-semibold rounded-md transition-all ${
                                    reportType === 'monthly'
                                        ? 'bg-white dark:bg-zinc-900 text-emerald-700 dark:text-emerald-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'
                                }`}
                            >
                                Rekap Bulanan
                            </button>
                        </div>

                        {/* Tanggal / Bulan Selector */}
                        <div>
                            {reportType === 'daily' ? (
                                <Input
                                    type="date"
                                    value={targetDate}
                                    onChange={(e) => {
                                        setTargetDate(e.target.value);
                                        fetchSummary('daily', e.target.value, targetMonth, targetCategory, targetDepartment);
                                    }}
                                    className="h-8 text-xs font-medium"
                                />
                            ) : (
                                <Input
                                    type="month"
                                    value={targetMonth}
                                    onChange={(e) => {
                                        setTargetMonth(e.target.value);
                                        fetchSummary('monthly', targetDate, e.target.value, targetCategory, targetDepartment);
                                    }}
                                    className="h-8 text-xs font-medium"
                                />
                            )}
                        </div>

                        {/* Kategori Switcher */}
                        <div>
                            <select
                                value={targetCategory}
                                onChange={(e) => {
                                    setTargetCategory(e.target.value);
                                    fetchSummary(reportType, targetDate, targetMonth, e.target.value, targetDepartment);
                                }}
                                className="w-full h-8 text-xs rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-2 font-medium"
                            >
                                <option value="all">Semua Personel</option>
                                <option value="REGULAR">Karyawan Reguler</option>
                                <option value="INTERN">Peserta Magang SMK</option>
                            </select>
                        </div>
                    </div>
                </DialogHeader>

                {/* Body Content */}
                <div className="flex-1 overflow-y-auto p-5 space-y-4">
                    {/* Ringkasan Metrik Singkat */}
                    {stats && (
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div className="p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60">
                                <span className="text-[10px] text-zinc-500 font-medium block">Total Personel</span>
                                <span className="text-base font-bold text-zinc-900 dark:text-zinc-100">
                                    {stats.total_personel ?? 0} Orang
                                </span>
                            </div>

                            {reportType === 'daily' ? (
                                <>
                                    <div className="p-2.5 rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20">
                                        <span className="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium block">Hadir Tepat</span>
                                        <span className="text-base font-bold text-emerald-700 dark:text-emerald-300">
                                            {stats.hadir ?? 0}
                                        </span>
                                    </div>
                                    <div className="p-2.5 rounded-lg border border-amber-200 dark:border-amber-900/50 bg-amber-50/50 dark:bg-amber-950/20">
                                        <span className="text-[10px] text-amber-700 dark:text-amber-400 font-medium block">Terlambat</span>
                                        <span className="text-base font-bold text-amber-700 dark:text-amber-300">
                                            {stats.terlambat ?? 0}
                                        </span>
                                    </div>
                                    <div className="p-2.5 rounded-lg border border-rose-200 dark:border-rose-900/50 bg-rose-50/50 dark:bg-rose-950/20">
                                        <span className="text-[10px] text-rose-700 dark:text-rose-400 font-medium block">Alpha / Mangkir</span>
                                        <span className="text-base font-bold text-rose-700 dark:text-rose-300">
                                            {stats.alpha ?? 0}
                                        </span>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div className="p-2.5 rounded-lg border border-indigo-200 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/20">
                                        <span className="text-[10px] text-indigo-700 dark:text-indigo-400 font-medium block">Rata-rata Presensi</span>
                                        <span className="text-base font-bold text-indigo-700 dark:text-indigo-300">
                                            {stats.avg_rate ?? 0}%
                                        </span>
                                    </div>
                                    <div className="p-2.5 rounded-lg border border-amber-200 dark:border-amber-900/50 bg-amber-50/50 dark:bg-amber-950/20">
                                        <span className="text-[10px] text-amber-700 dark:text-amber-400 font-medium block">Akumulasi Lembur</span>
                                        <span className="text-base font-bold text-amber-700 dark:text-amber-300">
                                            {stats.total_lembur ?? 0} Jam
                                        </span>
                                    </div>
                                    <div className="p-2.5 rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20">
                                        <span className="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium block">Disiplin 100%</span>
                                        <span className="text-base font-bold text-emerald-700 dark:text-emerald-300">
                                            {stats.perfect_count ?? 0} Orang
                                        </span>
                                    </div>
                                </>
                            )}
                        </div>
                    )}

                    {/* Kotak Teks WhatsApp Siap Salin */}
                    <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                            <Label className="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Pratinjau Teks WhatsApp:
                            </Label>
                            {isLoading && (
                                <span className="text-[11px] text-zinc-400 flex items-center gap-1">
                                    <RefreshCw className="h-3 w-3 animate-spin" /> Memuat format...
                                </span>
                            )}
                        </div>

                        <div className="relative">
                            <textarea
                                value={waText}
                                readOnly
                                rows={13}
                                className="w-full text-xs font-mono p-3.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950/70 text-zinc-800 dark:text-zinc-200 leading-relaxed focus:outline-none resize-none select-all"
                            />
                        </div>
                    </div>
                </div>

                {/* Footer Actions */}
                <DialogFooter className="p-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <span className="text-[11px] text-zinc-500">
                        {isCopied ? (
                            <span className="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                <Check className="h-3.5 w-3.5" /> Berhasil disalin ke clipboard!
                            </span>
                        ) : (
                            'Klik Salin Teks untuk menempelkan ke WhatsApp Group'
                        )}
                    </span>

                    <div className="flex items-center gap-2 w-full sm:w-auto">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={onClose}
                            className="text-xs flex-1 sm:flex-none"
                        >
                            Tutup
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            onClick={handleCopy}
                            className={`text-xs gap-1.5 flex-1 sm:flex-none ${
                                isCopied
                                    ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                    : 'bg-indigo-600 text-white hover:bg-indigo-700'
                            }`}
                        >
                            {isCopied ? <Check className="h-3.5 w-3.5" /> : <Copy className="h-3.5 w-3.5" />}
                            {isCopied ? 'Tersalin!' : 'Salin Teks Ringkasan'}
                        </Button>

                        <Button
                            type="button"
                            size="sm"
                            onClick={handleOpenWhatsAppWeb}
                            className="text-xs gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white flex-1 sm:flex-none"
                            title="Buka WhatsApp Web dan tempel pesan"
                        >
                            <ExternalLink className="h-3.5 w-3.5" />
                            Buka WhatsApp Web
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
