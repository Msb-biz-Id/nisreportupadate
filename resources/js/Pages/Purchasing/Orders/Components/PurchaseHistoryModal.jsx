import React, { useState, useEffect } from 'react';
import axios from 'axios';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Search, Loader2, Phone, Calendar, ArrowRight, History } from 'lucide-react';

export default function PurchaseHistoryModal({ isOpen, onClose, onSelectBenchmark }) {
    const [keyword, setKeyword] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const [hasSearched, setHasSearched] = useState(false);

    useEffect(() => {
        if (!isOpen) {
            setKeyword('');
            setResults([]);
            setHasSearched(false);
            return;
        }
    }, [isOpen]);

    const handleSearch = async (e) => {
        if (e) e.preventDefault();
        const clean = keyword.trim();
        if (!clean) return;

        setLoading(true);
        setHasSearched(true);
        try {
            const resp = await axios.get(route('purchasing.orders.history'), {
                params: { q: clean },
            });
            if (resp.data && resp.data.success) {
                setResults(resp.data.data);
            }
        } catch (err) {
            console.error('Failed to search purchase history', err);
        } finally {
            setLoading(false);
        }
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(val || 0);
    };

    const getCleanPhone = (phone) => {
        if (!phone) return null;
        let clean = phone.replace(/[^0-9]/g, '');
        if (clean.startsWith('0')) clean = '62' + clean.slice(1);
        return clean;
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-4xl max-h-[85vh] flex flex-col p-6">
                <DialogHeader>
                    <div className="flex items-center gap-2">
                        <div className="p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-lg">
                            <History className="w-5 h-5" />
                        </div>
                        <div>
                            <DialogTitle className="text-xl font-bold text-slate-900 dark:text-slate-100">
                                Cari Riwayat Pembelian (Benchmark Katalog)
                            </DialogTitle>
                            <DialogDescription className="text-sm text-slate-500">
                                Periksa riwayat harga satuan historis, unit pemohon, dan vendor penyedia sebelum mengajukan pembelian.
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                {/* Form Input Pencarian */}
                <form onSubmit={handleSearch} className="flex gap-2 my-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <Input
                            type="text"
                            placeholder="Ketik nama barang, spesifikasi, nomor PO, atau nama vendor..."
                            value={keyword}
                            onChange={(e) => setKeyword(e.target.value)}
                            className="pl-9 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800"
                            autoFocus
                        />
                    </div>
                    <Button type="submit" disabled={loading} className="gap-2 bg-blue-600 hover:bg-blue-700 text-white">
                        {loading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Search className="w-4 h-4" />}
                        Cari
                    </Button>
                </form>

                {/* Hasil Pencarian */}
                <div className="flex-1 overflow-y-auto pr-1 space-y-3 min-h-[300px]">
                    {loading && (
                        <div className="flex flex-col items-center justify-center py-16 text-slate-400">
                            <Loader2 className="w-8 h-8 animate-spin mb-2 text-blue-600" />
                            <p className="text-sm">Mencari data riwayat pembelian katalog...</p>
                        </div>
                    )}

                    {!loading && hasSearched && results.length === 0 && (
                        <div className="text-center py-16 border border-dashed rounded-xl border-slate-200 dark:border-slate-800 text-slate-500">
                            <p className="font-semibold text-slate-700 dark:text-slate-300">Belum Ditemukan Riwayat Pembelian</p>
                            <p className="text-sm mt-1 text-slate-400">
                                Tidak ada transaksi pembelian sebelumnya yang cocok dengan kata kunci &quot;{keyword}&quot;.
                            </p>
                        </div>
                    )}

                    {!loading && !hasSearched && (
                        <div className="text-center py-16 border border-dashed rounded-xl border-slate-200 dark:border-slate-800 text-slate-400">
                            <Search className="w-10 h-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" />
                            <p className="text-sm">Ketikkan kata kunci di atas untuk menelusuri katalog harga historis.</p>
                        </div>
                    )}

                    {!loading && results.map((item) => {
                        const cleanPhone = getCleanPhone(item.vendor_phone);
                        return (
                            <div
                                key={item.uuid}
                                className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 hover:border-blue-400 dark:hover:border-blue-600 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                            >
                                <div className="space-y-1.5 flex-1">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="font-semibold text-slate-900 dark:text-slate-100 text-base">
                                            {item.item_name}
                                        </span>
                                        <Badge variant="outline" className="text-xs font-mono bg-slate-100 dark:bg-slate-800">
                                            {item.po_number}
                                        </Badge>
                                        <Badge variant="secondary" className="text-xs">
                                            {item.department}
                                        </Badge>
                                    </div>

                                    {item.specification && (
                                        <p className="text-xs text-slate-500 line-clamp-2">
                                            Spesifikasi: {item.specification}
                                        </p>
                                    )}

                                    <div className="flex items-center gap-4 text-xs text-slate-500 flex-wrap pt-1">
                                        <div className="flex items-center gap-1">
                                            <Calendar className="w-3.5 h-3.5" />
                                            <span>Terakhir Beli: {item.transaction_date}</span>
                                        </div>
                                        <div>
                                            Vendor: <span className="font-medium text-slate-700 dark:text-slate-300">{item.vendor_name}</span>
                                        </div>
                                        {cleanPhone && (
                                            <a
                                                href={`https://wa.me/${cleanPhone}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium"
                                            >
                                                <Phone className="w-3.5 h-3.5" />
                                                Hubungi Vendor via WA
                                            </a>
                                        )}
                                    </div>
                                </div>

                                <div className="flex sm:flex-col items-end justify-between sm:justify-center border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100 dark:border-slate-800">
                                    <div className="text-left sm:text-right mb-2">
                                        <span className="text-xs text-slate-400 block">Harga Satuan Terakhir</span>
                                        <span className="text-lg font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                            {formatRupiah(item.unit_price)}
                                        </span>
                                        <span className="text-xs text-slate-400 block">/ {item.unit}</span>
                                    </div>

                                    {onSelectBenchmark && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => {
                                                onSelectBenchmark(item);
                                                onClose();
                                            }}
                                            className="gap-1 border-blue-200 text-blue-600 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-400 dark:hover:bg-blue-950/40 text-xs"
                                        >
                                            Gunakan Referensi
                                            <ArrowRight className="w-3.5 h-3.5" />
                                        </Button>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </DialogContent>
        </Dialog>
    );
}
