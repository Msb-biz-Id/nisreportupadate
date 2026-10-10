import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { X, ArrowRight, Building2, User, AlertCircle, Save } from 'lucide-react';

export default function MutateAssetModal({ 
    asset, 
    isOpen, 
    onClose, 
    departments = [], 
    employees = [],
    locations = []
}) {
    if (!isOpen || !asset) return null;

    const form = useForm({
        to_department: '',
        to_division: '',
        to_position: '',
        to_pic_employee_id: '',
        to_location_id: asset.location_id || '',
        mutation_date: new Date().toISOString().split('T')[0],
        notes: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        form.post(route('purchasing.assets.mutate', asset.asset_code), {
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
                <div className="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                    <div className="flex items-center gap-2.5">
                        <div className="p-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-lg">
                            <Building2 className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">Mutasi Aset Antar-Departemen</h3>
                            <p className="text-xs text-slate-500 font-mono">{asset.asset_code} • {asset.asset_name}</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-5 space-y-4">
                    {/* Notice box */}
                    <div className="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2">
                        <AlertCircle className="w-4 h-4 mt-0.5 shrink-0 text-amber-600" />
                        <div>
                            <p className="font-semibold">Perubahan Kode Resmi & Preservation of History</p>
                            <p className="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">
                                Kode aset otomatis digenerate ulang sesuai singkatan departemen baru. Seluruh riwayat kepemilikan sebelumnya akan tercatat permanen di audit trail mutasi.
                            </p>
                        </div>
                    </div>

                    {/* Departemen Asal vs Tujuan */}
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <div className="p-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <span className="text-[10px] uppercase font-bold text-slate-400">Departemen Asal</span>
                            <p className="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">{asset.department}</p>
                            <span className="text-[10px] text-slate-400 font-mono">({asset.department_code})</span>
                        </div>
                        <div className="space-y-1">
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Departemen Tujuan <span className="text-rose-500">*</span>
                            </label>
                            <select
                                required
                                value={form.data.to_department}
                                onChange={(e) => form.setData('to_department', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500"
                            >
                                <option value="">Pilih Departemen</option>
                                {departments
                                    .filter(d => d.name !== asset.department)
                                    .map((d, idx) => (
                                        <option key={idx} value={d.name}>{d.name} ({d.code})</option>
                                    ))}
                            </select>
                            {form.errors.to_department && (
                                <p className="text-[11px] text-rose-500">{form.errors.to_department}</p>
                            )}
                        </div>
                    </div>

                    {/* Divisi & Posisi Baru (Opsional) */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Divisi Baru</label>
                            <input
                                type="text"
                                placeholder="Misal: Operasional"
                                value={form.data.to_division}
                                onChange={(e) => form.setData('to_division', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Posisi / Jabatan Baru</label>
                            <input
                                type="text"
                                placeholder="Misal: Staff"
                                value={form.data.to_position}
                                onChange={(e) => form.setData('to_position', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                        </div>
                    </div>

                    {/* Pemegang Fisik Baru (PIC) */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Pemegang Fisik Baru (PIC Karyawan HRIS)
                        </label>
                        <select
                            value={form.data.to_pic_employee_id}
                            onChange={(e) => form.setData('to_pic_employee_id', e.target.value)}
                            className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                        >
                            <option value="">Tetap / Belum Ditentukan</option>
                            {employees.map((emp) => (
                                <option key={emp.id} value={emp.id}>{emp.name} ({emp.department} - {emp.position})</option>
                            ))}
                        </select>
                    </div>

                    {/* Tanggal & Lokasi Mutasi */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Mutasi</label>
                            <input
                                type="date"
                                required
                                value={form.data.mutation_date}
                                onChange={(e) => form.setData('mutation_date', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Lokasi Fisik Baru</label>
                            <select
                                value={form.data.to_location_id}
                                onChange={(e) => form.setData('to_location_id', e.target.value)}
                                className="w-full text-xs rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <option value="">Pilih Lokasi</option>
                                {locations.map((loc) => (
                                    <option key={loc.id} value={loc.id}>{loc.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Catatan / Alasan Mutasi */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Alasan Perpindahan</label>
                        <textarea
                            rows={2}
                            placeholder="Kebutuhan workstation tim desain baru, alih fungsi operasional..."
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
                            className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <Save className="w-4 h-4" />
                            Simpan & Regenerasi Kode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
