import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { 
    X, 
    Box, 
    Cpu, 
    User, 
    Calendar, 
    ShieldCheck, 
    History, 
    FileText, 
    CheckCircle2, 
    AlertTriangle, 
    QrCode, 
    Building2,
    Save,
    ArrowRight
} from 'lucide-react';

export default function AssetDetailDrawer({ 
    asset, 
    isOpen, 
    onClose, 
    locations = [], 
    employees = [],
    onOpenMutate,
    onOpenRetire 
}) {
    if (!isOpen || !asset) return null;

    const [activeTab, setActiveTab] = useState(asset.registration_stage === 'QUICK_REGISTERED' ? 'deep_reg' : 'overview');

    const deepForm = useForm({
        specification: asset.specification || '',
        serial_number: asset.serial_number || '',
        barcode_qr_code: asset.barcode_qr_code || asset.asset_code,
        warranty_duration: asset.warranty_duration || '',
        warranty_expires_at: asset.warranty_expires_at || '',
        location_id: asset.location_id || '',
        pic_employee_id: asset.pic_employee_id || '',
        unit: asset.unit || 'Unit',
        notes: asset.notes || '',
    });

    const handleDeepSubmit = (e) => {
        e.preventDefault();
        deepForm.put(route('purchasing.assets.update', asset.asset_code), {
            preserveScroll: true,
            onSuccess: () => {
                setActiveTab('overview');
            }
        });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(val || 0);
    };

    return (
        <div className="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-sm flex justify-end transition-opacity duration-300">
            <div className="w-full max-w-2xl bg-white dark:bg-slate-900 h-full shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800 animate-in slide-in-from-right duration-300">
                {/* Header */}
                <div className="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="font-mono text-xs font-bold px-2.5 py-1 rounded bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                {asset.asset_code}
                            </span>
                            <span className={`text-xs px-2.5 py-0.5 rounded-full font-semibold ${
                                asset.status === 'ACTIVE' 
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                                    : 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-400 border border-rose-200 dark:border-rose-800'
                            }`}>
                                {asset.status}
                            </span>
                            <span className={`text-xs px-2.5 py-0.5 rounded-full font-medium ${
                                asset.registration_stage === 'COMPLETED'
                                    ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                                    : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200'
                            }`}>
                                {asset.registration_stage === 'COMPLETED' ? 'Data Lengkap (Deep)' : 'Registrasi Cepat (Quick)'}
                            </span>
                        </div>
                        <h2 className="text-xl font-bold text-slate-900 dark:text-slate-100">{asset.asset_name}</h2>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Kategori: {asset.category_code} • Dept: {asset.department} ({asset.department_code})
                        </p>
                    </div>
                    <button 
                        onClick={onClose}
                        className="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-200/50 dark:hover:bg-slate-700/50 transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Navigation Tabs */}
                <div className="flex border-b border-slate-200 dark:border-slate-800 px-6 bg-white dark:bg-slate-900">
                    <button
                        onClick={() => setActiveTab('overview')}
                        className={`py-3 px-4 text-xs font-semibold border-b-2 flex items-center gap-1.5 transition-colors ${
                            activeTab === 'overview'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                        }`}
                    >
                        <Box className="w-3.5 h-3.5" />
                        Ringkasan
                    </button>
                    {asset.status !== 'RETIRED' && (
                        <button
                            onClick={() => setActiveTab('deep_reg')}
                            className={`py-3 px-4 text-xs font-semibold border-b-2 flex items-center gap-1.5 transition-colors ${
                                activeTab === 'deep_reg'
                                    ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                            }`}
                        >
                            <Cpu className="w-3.5 h-3.5" />
                            Lengkapi Data Fisik
                            {asset.registration_stage === 'QUICK_REGISTERED' && (
                                <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            )}
                        </button>
                    )}
                    <button
                        onClick={() => setActiveTab('mutations')}
                        className={`py-3 px-4 text-xs font-semibold border-b-2 flex items-center gap-1.5 transition-colors ${
                            activeTab === 'mutations'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'
                        }`}
                    >
                        <History className="w-3.5 h-3.5" />
                        Riwayat Mutasi ({asset.mutations?.length || 0})
                    </button>
                    {asset.status === 'RETIRED' && (
                        <button
                            onClick={() => setActiveTab('retirement')}
                            className={`py-3 px-4 text-xs font-semibold border-b-2 flex items-center gap-1.5 transition-colors ${
                                activeTab === 'retirement'
                                    ? 'border-rose-600 text-rose-600 dark:text-rose-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-700'
                            }`}
                        >
                            <AlertTriangle className="w-3.5 h-3.5" />
                            Data Pelepasan (Retired)
                        </button>
                    )}
                </div>

                {/* Content Area */}
                <div className="flex-1 overflow-y-auto p-6 space-y-6">
                    {activeTab === 'overview' && (
                        <div className="space-y-6">
                            {/* Barcode & Info Kartu */}
                            <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <div className="space-y-1">
                                    <span className="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">Barcode / QR Tagging</span>
                                    <div className="font-mono text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                        <QrCode className="w-4 h-4 text-indigo-500" />
                                        {asset.barcode_qr_code || asset.asset_code}
                                    </div>
                                    <p className="text-[11px] text-slate-500">Nomor Seri: {asset.serial_number || '-'}</p>
                                </div>
                                <div className="text-right">
                                    <span className="text-[11px] font-semibold text-slate-400 uppercase">Nilai Perolehan</span>
                                    <div className="text-base font-bold text-slate-900 dark:text-slate-100">
                                        {formatRupiah(asset.acquisition_cost)}
                                    </div>
                                    <span className="text-[11px] text-slate-500">{asset.quantity} {asset.unit}</span>
                                </div>
                            </div>

                            {/* Informasi Alokasi & Penanggung Jawab */}
                            <div className="space-y-3">
                                <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <Building2 className="w-3.5 h-3.5" />
                                    Alokasi Penanggung Jawab Saat Ini
                                </h3>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="p-3.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800">
                                        <span className="text-[11px] text-slate-400">Departemen</span>
                                        <p className="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                            {asset.department} ({asset.department_code})
                                        </p>
                                        <p className="text-xs text-slate-500">{asset.division || '-'} • {asset.position}</p>
                                    </div>
                                    <div className="p-3.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800">
                                        <span className="text-[11px] text-slate-400">Pemegang Fisik (PIC)</span>
                                        <p className="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                            {asset.pic_employee?.name || asset.pic_employee_name || 'Belum Ditentukan'}
                                        </p>
                                        <p className="text-xs text-slate-500">{asset.pic_employee?.employee_code || '-'}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Spesifikasi & Garansi */}
                            <div className="space-y-3">
                                <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <Cpu className="w-3.5 h-3.5" />
                                    Spesifikasi Fisik & Garansi
                                </h3>
                                <div className="p-4 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800 space-y-3">
                                    <div>
                                        <span className="text-[11px] text-slate-400">Deskripsi / Spesifikasi Teknis:</span>
                                        <p className="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line mt-1">
                                            {asset.specification || 'Belum ada spesifikasi rinci. Klik tab "Lengkapi Data Fisik" untuk menginput.'}
                                        </p>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4 pt-3 border-t border-slate-100 dark:border-slate-700/50">
                                        <div>
                                            <span className="text-[11px] text-slate-400">Durasi Garansi:</span>
                                            <p className="text-xs font-medium text-slate-800 dark:text-slate-200 mt-0.5">
                                                {asset.warranty_duration || '-'}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-[11px] text-slate-400">Berlaku Sampai:</span>
                                            <p className="text-xs font-medium text-slate-800 dark:text-slate-200 mt-0.5 flex items-center gap-1">
                                                <Calendar className="w-3.5 h-3.5 text-slate-400" />
                                                {asset.warranty_expires_at || '-'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Tombol Aksi Mutasi & Retire */}
                            {asset.status !== 'RETIRED' && (
                                <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                                    <div>
                                        <h4 className="text-xs font-bold text-slate-800 dark:text-slate-200">Aksi Inventaris</h4>
                                        <p className="text-[11px] text-slate-500">Pindahkan kepemilikan antar-departemen atau catat pelepasan aset.</p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <button
                                            onClick={() => {
                                                onClose();
                                                onOpenMutate(asset);
                                            }}
                                            className="px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300 dark:hover:bg-indigo-900 rounded-lg transition-colors border border-indigo-200 dark:border-indigo-800"
                                        >
                                            Mutasi Departemen
                                        </button>
                                        <button
                                            onClick={() => {
                                                onClose();
                                                onOpenRetire(asset);
                                            }}
                                            className="px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950 dark:text-rose-300 dark:hover:bg-rose-900 rounded-lg transition-colors border border-rose-200 dark:border-rose-800"
                                        >
                                            Pensiunkan (Retire)
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'deep_reg' && (
                        <form onSubmit={handleDeepSubmit} className="space-y-4">
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-lg border border-indigo-100 dark:border-indigo-800 text-xs text-indigo-800 dark:text-indigo-300 flex items-start gap-2">
                                <Cpu className="w-4 h-4 mt-0.5 shrink-0" />
                                <div>
                                    <p className="font-semibold">Tahap 2: Deep Registration</p>
                                    <p className="text-[11px] text-indigo-600 dark:text-indigo-400">Lengkapi nomor seri fisik, masa garansi resmi, dan spesifikasi lengkap aset.</p>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Spesifikasi Lengkap / Konfigurasi</label>
                                <textarea
                                    value={deepForm.data.specification}
                                    onChange={(e) => deepForm.setData('specification', e.target.value)}
                                    rows={4}
                                    placeholder="Contoh: Core i7 13th Gen, 16GB RAM DDR5, 512GB SSD NVMe, Display 14 inch FHD..."
                                    className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500 focus:border-indigo-500"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nomor Seri Fisik (Serial No)</label>
                                    <input
                                        type="text"
                                        value={deepForm.data.serial_number}
                                        onChange={(e) => deepForm.setData('serial_number', e.target.value)}
                                        placeholder="Contoh: SN-8923490234"
                                        className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Barcode / QR Tag Code</label>
                                    <input
                                        type="text"
                                        value={deepForm.data.barcode_qr_code}
                                        onChange={(e) => deepForm.setData('barcode_qr_code', e.target.value)}
                                        className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Durasi Garansi</label>
                                    <input
                                        type="text"
                                        value={deepForm.data.warranty_duration}
                                        onChange={(e) => deepForm.setData('warranty_duration', e.target.value)}
                                        placeholder="Contoh: 1 Tahun Resmi"
                                        className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Habis Garansi</label>
                                    <input
                                        type="date"
                                        value={deepForm.data.warranty_expires_at}
                                        onChange={(e) => deepForm.setData('warranty_expires_at', e.target.value)}
                                        className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Lokasi Fisik Ruko/Gudang</label>
                                    <select
                                        value={deepForm.data.location_id}
                                        onChange={(e) => deepForm.setData('location_id', e.target.value)}
                                        className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <option value="">Pilih Lokasi</option>
                                        {locations.map((loc) => (
                                            <option key={loc.id} value={loc.id}>{loc.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Pemegang Fisik (Karyawan HRIS)</label>
                                    <select
                                        value={deepForm.data.pic_employee_id}
                                        onChange={(e) => deepForm.setData('pic_employee_id', e.target.value)}
                                        className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <option value="">Pilih Karyawan</option>
                                        {employees.map((emp) => (
                                            <option key={emp.id} value={emp.id}>{emp.name} ({emp.department})</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan</label>
                                <textarea
                                    value={deepForm.data.notes}
                                    onChange={(e) => deepForm.setData('notes', e.target.value)}
                                    rows={2}
                                    placeholder="Keterangan kelengkapan dus, charger, nota pembelian fisik..."
                                    className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                                />
                            </div>

                            <div className="pt-2 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={deepForm.processing}
                                    className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm flex items-center gap-1.5 disabled:opacity-50"
                                >
                                    <Save className="w-4 h-4" />
                                    Simpan & Selesaikan Pendaftaran
                                </button>
                            </div>
                        </form>
                    )}

                    {activeTab === 'mutations' && (
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Riwayat Perpindahan Departemen & Regenerasi Kode
                                </h3>
                            </div>

                            {(!asset.mutations || asset.mutations.length === 0) ? (
                                <div className="text-center py-8 text-slate-400 text-xs bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
                                    Aset ini belum pernah dimutasi antar-departemen. Kode aset asli masih aktif.
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {asset.mutations.map((m, idx) => (
                                        <div key={m.id || idx} className="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-2">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2 font-mono text-xs">
                                                    <span className="text-slate-500 line-through">{m.old_asset_code}</span>
                                                    <ArrowRight className="w-3.5 h-3.5 text-indigo-500" />
                                                    <span className="font-bold text-indigo-600 dark:text-indigo-400">{m.new_asset_code}</span>
                                                </div>
                                                <span className="text-[11px] text-slate-400">{m.mutation_date}</span>
                                            </div>
                                            <div className="text-xs text-slate-700 dark:text-slate-300">
                                                Dipindah dari <span className="font-semibold">{m.from_department_name} ({m.from_department_code})</span> ke <span className="font-semibold text-emerald-600 dark:text-emerald-400">{m.to_department_name} ({m.to_department_code})</span>
                                            </div>
                                            {m.notes && (
                                                <p className="text-[11px] text-slate-500 italic">"{m.notes}"</p>
                                            )}
                                            <div className="text-[10px] text-slate-400 pt-1 border-t border-slate-200 dark:border-slate-700/50">
                                                Diproses oleh: {m.pic_user?.name || 'Sistem'}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'retirement' && asset.retirement && (
                        <div className="space-y-4">
                            <div className="p-4 bg-rose-50 dark:bg-rose-950/40 rounded-xl border border-rose-200 dark:border-rose-900 text-xs space-y-3">
                                <div className="flex items-center gap-2 text-rose-800 dark:text-rose-300 font-bold">
                                    <AlertTriangle className="w-4 h-4 text-rose-600" />
                                    Aset Telah Dikeluarkan dari Inventaris Operasional
                                </div>
                                <div className="grid grid-cols-2 gap-3 text-slate-700 dark:text-slate-300">
                                    <div>
                                        <span className="text-[11px] text-slate-400 block">Tanggal Pelepasan:</span>
                                        <span className="font-semibold">{asset.retirement.retired_at}</span>
                                    </div>
                                    <div>
                                        <span className="text-[11px] text-slate-400 block">Metode Pelepasan:</span>
                                        <span className="font-semibold">{asset.retirement.disposal_method}</span>
                                    </div>
                                    <div>
                                        <span className="text-[11px] text-slate-400 block">Alasan Pensiun:</span>
                                        <span className="font-semibold">{asset.retirement.retirement_reason}</span>
                                    </div>
                                    <div>
                                        <span className="text-[11px] text-slate-400 block">Kondisi Akhir:</span>
                                        <span className="font-semibold">{asset.retirement.final_condition}</span>
                                    </div>
                                    <div>
                                        <span className="text-[11px] text-slate-400 block">Nilai Buku (Book Value):</span>
                                        <span className="font-semibold">{formatRupiah(asset.retirement.book_value)}</span>
                                    </div>
                                    <div>
                                        <span className="text-[11px] text-slate-400 block">Nilai Jual (Disposal Price):</span>
                                        <span className="font-semibold">{formatRupiah(asset.retirement.disposal_price)}</span>
                                    </div>
                                </div>
                                {asset.retirement.notes && (
                                    <div className="pt-2 border-t border-rose-100 dark:border-rose-900">
                                        <span className="text-[11px] text-slate-400 block">Catatan Berita Acara:</span>
                                        <p className="text-xs text-slate-600 dark:text-slate-300 italic">{asset.retirement.notes}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
