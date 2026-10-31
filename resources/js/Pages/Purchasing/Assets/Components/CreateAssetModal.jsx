import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { X, Plus, Sparkles, Building2, User, DollarSign, Calendar, Save } from 'lucide-react';

export default function CreateAssetModal({ 
    isOpen, 
    onClose, 
    categories = [], 
    departments = [], 
    employees = [], 
    locations = [], 
    units = [] 
}) {
    if (!isOpen) return null;

    const [suggestions, setSuggestions] = useState([]);
    const [isSuggesting, setIsSuggesting] = useState(false);

    const form = useForm({
        asset_name: '',
        category_code: '',
        department: '',
        department_code: '',
        division: '',
        position: '',
        pic_employee_id: '',
        location_id: '',
        unit: 'Unit',
        quantity: 1,
        purchase_date: new Date().toISOString().split('T')[0],
        acquisition_cost: '',
        specification: '',
        notes: '',
    });

    // Smart Suggestion Debounce saat mengetik nama barang
    useEffect(() => {
        const query = form.data.asset_name.trim();
        if (query.length < 2) {
            setSuggestions([]);
            return;
        }

        const timeout = setTimeout(async () => {
            setIsSuggesting(true);
            try {
                const res = await fetch(`/purchasing/assets/suggest-category?keyword=${encodeURIComponent(query)}`);
                const data = await res.json();
                setSuggestions(data.suggestions || []);
            } catch (err) {
                console.error('Failed to fetch suggestions', err);
            } finally {
                setIsSuggesting(false);
            }
        }, 300);

        return () => clearTimeout(timeout);
    }, [form.data.asset_name]);

    const handleSelectDepartment = (e) => {
        const selectedDeptName = e.target.value;
        const found = departments.find(d => d.name === selectedDeptName);
        form.setData({
            ...form.data,
            department: selectedDeptName,
            department_code: found ? found.code : ''
        });
    };

    const handleApplySuggestion = (catCode) => {
        form.setData('category_code', catCode);
        setSuggestions([]);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        form.post(route('purchasing.assets.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            }
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="w-full max-w-xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                    <div className="flex items-center gap-2.5">
                        <div className="p-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-lg">
                            <Plus className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Registrasi Cepat Aset Baru (Quick)</h3>
                            <p className="text-xs text-slate-500">Kode aset resmi akan digenerate otomatis: [Kategori].[Dept].[026].[Urut]</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-5 space-y-4">
                    {/* Nama Barang & Smart Suggestion Engine */}
                    <div>
                        <div className="flex items-center justify-between mb-1">
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Nama Barang / Aset <span className="text-rose-500">*</span>
                            </label>
                            {isSuggesting && (
                                <span className="text-[10px] text-indigo-500 flex items-center gap-1">
                                    <Sparkles className="w-3 h-3 animate-spin" /> Menganalisis kategori...
                                </span>
                            )}
                        </div>
                        <input
                            type="text"
                            required
                            placeholder="Contoh: Laptop ThinkPad T14, Mesin Jahit Singer, Meja Staff..."
                            value={form.data.asset_name}
                            onChange={(e) => form.setData('asset_name', e.target.value)}
                            className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        {form.errors.asset_name && (
                            <p className="text-[11px] text-rose-500 mt-1">{form.errors.asset_name}</p>
                        )}

                        {/* Chips Smart Suggestion */}
                        {suggestions.length > 0 && (
                            <div className="mt-2 p-2.5 bg-indigo-50/70 dark:bg-indigo-950/40 rounded-xl border border-indigo-100 dark:border-indigo-900/40">
                                <div className="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 flex items-center gap-1 mb-1.5">
                                    <Sparkles className="w-3 h-3" />
                                    Smart Suggestion Kategori Aset:
                                </div>
                                <div className="flex flex-wrap gap-1.5">
                                    {suggestions.map((s, idx) => (
                                        <button
                                            key={idx}
                                            type="button"
                                            onClick={() => handleApplySuggestion(s.category_code)}
                                            className="px-2 py-1 text-[11px] font-semibold bg-white dark:bg-slate-800 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 transition-colors shadow-xs"
                                        >
                                            [{s.category_code}] {s.category_name}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Kategori Resmi (20 Opsi) & Departemen (HRIS) */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kategori Aset Resmi <span className="text-rose-500">*</span>
                            </label>
                            <select
                                required
                                value={form.data.category_code}
                                onChange={(e) => form.setData('category_code', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500"
                            >
                                <option value="">Pilih Kategori</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.code}>[{c.code}] {c.name}</option>
                                ))}
                            </select>
                            {form.errors.category_code && (
                                <p className="text-[11px] text-rose-500">{form.errors.category_code}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Departemen Penanggung Jawab <span className="text-rose-500">*</span>
                            </label>
                            <select
                                required
                                value={form.data.department}
                                onChange={handleSelectDepartment}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500"
                            >
                                <option value="">Pilih Departemen (HRIS)</option>
                                {departments.map((d, idx) => (
                                    <option key={idx} value={d.name}>{d.name} ({d.code})</option>
                                ))}
                            </select>
                            {form.errors.department && (
                                <p className="text-[11px] text-rose-500">{form.errors.department}</p>
                            )}
                        </div>
                    </div>

                    {/* Pemegang Fisik (Karyawan) & Lokasi */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Pemegang Fisik (PIC Karyawan)
                            </label>
                            <select
                                value={form.data.pic_employee_id}
                                onChange={(e) => form.setData('pic_employee_id', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <option value="">Pilih Karyawan HRIS</option>
                                {employees.map((emp) => (
                                    <option key={emp.id} value={emp.id}>{emp.name} ({emp.department})</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Lokasi Ruko / Gudang
                            </label>
                            <select
                                value={form.data.location_id}
                                onChange={(e) => form.setData('location_id', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <option value="">Pilih Lokasi</option>
                                {locations.map((loc) => (
                                    <option key={loc.id} value={loc.id}>{loc.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Biaya Perolehan & Tanggal */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Biaya Perolehan (Rp)
                            </label>
                            <input
                                type="number"
                                min="0"
                                placeholder="0"
                                value={form.data.acquisition_cost}
                                onChange={(e) => form.setData('acquisition_cost', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Tanggal Perolehan
                            </label>
                            <input
                                type="date"
                                value={form.data.purchase_date}
                                onChange={(e) => form.setData('purchase_date', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                        </div>
                    </div>

                    {/* Satuan & Jumlah */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jumlah (Qty)</label>
                            <input
                                type="number"
                                min="1"
                                value={form.data.quantity}
                                onChange={(e) => form.setData('quantity', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Satuan Aset</label>
                            <select
                                value={form.data.unit}
                                onChange={(e) => form.setData('unit', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <option value="Unit">Unit</option>
                                {units.map((u) => (
                                    <option key={u.id} value={u.name}>{u.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Spesifikasi Singkat</label>
                        <textarea
                            rows={2}
                            placeholder="Merek, tipe atau catatan kondisi barang..."
                            value={form.data.specification}
                            onChange={(e) => form.setData('specification', e.target.value)}
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
                            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <Save className="w-4 h-4" />
                            Simpan & Generate Kode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
