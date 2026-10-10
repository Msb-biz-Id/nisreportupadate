import React from 'react';
import { useForm } from '@inertiajs/react';
import { X, AlertTriangle, FileText, DollarSign, Save } from 'lucide-react';

export default function RetireAssetModal({ 
    asset, 
    isOpen, 
    onClose, 
    retirementReasons = [], 
    finalConditions = [], 
    disposalMethods = [] 
}) {
    if (!isOpen || !asset) return null;

    const form = useForm({
        retired_at: new Date().toISOString().split('T')[0],
        retirement_reason: '',
        final_condition: '',
        disposal_method: '',
        book_value: asset.acquisition_cost || 0,
        disposal_price: '',
        handover_document: null,
        notes: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        form.post(route('purchasing.assets.retire', asset.asset_code), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            }
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="w-full max-w-lg bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-rose-50/50 dark:bg-rose-950/30">
                    <div className="flex items-center gap-2.5">
                        <div className="p-2 bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-400 rounded-lg">
                            <AlertTriangle className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Pelepasan / Pensiun Aset (Retirement)</h3>
                            <p className="text-xs text-slate-500 font-mono">{asset.asset_code} • {asset.asset_name}</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-5 space-y-4">
                    <div className="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 rounded-xl text-xs text-rose-800 dark:text-rose-300">
                        <p className="font-semibold">Konfirmasi Pelepasan Aset</p>
                        <p className="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">
                            Aset ini akan berstatus non-aktif (RETIRED) dan dikeluarkan dari aset aktif operasional NIS Group. Nilai buku sisa akan dicatat untuk rekonsiliasi Keuangan.
                        </p>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Tanggal Pelepasan <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="date"
                                required
                                value={form.data.retired_at}
                                onChange={(e) => form.setData('retired_at', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                            {form.errors.retired_at && (
                                <p className="text-[11px] text-rose-500">{form.errors.retired_at}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Alasan Pensiun <span className="text-rose-500">*</span>
                            </label>
                            <select
                                required
                                value={form.data.retirement_reason}
                                onChange={(e) => form.setData('retirement_reason', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <option value="">Pilih Alasan</option>
                                {retirementReasons.map((r) => (
                                    <option key={r.id} value={r.name}>{r.name}</option>
                                ))}
                            </select>
                            {form.errors.retirement_reason && (
                                <p className="text-[11px] text-rose-500">{form.errors.retirement_reason}</p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kondisi Fisik Akhir <span className="text-rose-500">*</span>
                            </label>
                            <select
                                required
                                value={form.data.final_condition}
                                onChange={(e) => form.setData('final_condition', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <option value="">Pilih Kondisi</option>
                                {finalConditions.map((c) => (
                                    <option key={c.id} value={c.name}>{c.name}</option>
                                ))}
                            </select>
                            {form.errors.final_condition && (
                                <p className="text-[11px] text-rose-500">{form.errors.final_condition}</p>
                            )}
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Metode Pelepasan <span className="text-rose-500">*</span>
                            </label>
                            <select
                                required
                                value={form.data.disposal_method}
                                onChange={(e) => form.setData('disposal_method', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <option value="">Pilih Metode</option>
                                {disposalMethods.map((m) => (
                                    <option key={m.id} value={m.name}>{m.name}</option>
                                ))}
                            </select>
                            {form.errors.disposal_method && (
                                <p className="text-[11px] text-rose-500">{form.errors.disposal_method}</p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Nilai Buku (Book Value Rp)
                            </label>
                            <input
                                type="number"
                                min="0"
                                value={form.data.book_value}
                                onChange={(e) => form.setData('book_value', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Nilai Jual / Pelepasan (Rp)
                            </label>
                            <input
                                type="number"
                                min="0"
                                placeholder="0 jika dibuang/scrap"
                                value={form.data.disposal_price}
                                onChange={(e) => form.setData('disposal_price', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Upload Berita Acara / Dokumen Pelepasan (PDF/JPG, Max 5MB)
                        </label>
                        <input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            onChange={(e) => form.setData('handover_document', e.target.files[0])}
                            className="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan</label>
                        <textarea
                            rows={2}
                            placeholder="Keterangan kondisi fisik, kerusakan komponen, persetujuan..."
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                        />
                    </div>

                    <div className="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold shadow-sm flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <Save className="w-4 h-4" />
                            Pensiunkan Aset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
