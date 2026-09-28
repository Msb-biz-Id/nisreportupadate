import React, { useState, useEffect } from 'react';
import { Calendar, ChevronDown } from 'lucide-react';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export function getPresetDates(preset, customMonth = null) {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const toYMD = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (preset === 'today') {
        const todayStr = toYMD(now);
        return { from: todayStr, to: todayStr };
    }
    if (preset === 'yesterday') {
        const y = new Date(now);
        y.setDate(y.getDate() - 1);
        const yStr = toYMD(y);
        return { from: yStr, to: yStr };
    }
    if (preset === 'last_7') {
        const from = new Date(now);
        from.setDate(from.getDate() - 6);
        return { from: toYMD(from), to: toYMD(now) };
    }
    if (preset === 'last_30') {
        const from = new Date(now);
        from.setDate(from.getDate() - 29);
        return { from: toYMD(from), to: toYMD(now) };
    }
    if (preset === 'this_month') {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        return { from: toYMD(start), to: toYMD(end) };
    }
    if (preset === 'last_month') {
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        return { from: toYMD(start), to: toYMD(end) };
    }
    if (preset === 'specific_month' && customMonth) {
        const [year, month] = customMonth.split('-').map(Number);
        if (year && month) {
            const start = new Date(year, month - 1, 1);
            const end = new Date(year, month, 0);
            return { from: toYMD(start), to: toYMD(end) };
        }
    }
    if (preset === 'this_year') {
        const start = new Date(now.getFullYear(), 0, 1);
        const end = new Date(now.getFullYear(), 11, 31);
        return { from: toYMD(start), to: toYMD(end) };
    }
    return null;
}

export function detectActivePreset(from, to) {
    if (!from && !to) return 'all';

    const presets = ['today', 'yesterday', 'this_month', 'last_month', 'last_7', 'last_30', 'this_year'];
    for (const p of presets) {
        const range = getPresetDates(p);
        if (range && range.from === from && range.to === to) {
            return p;
        }
    }

    // Cek apakah persis mencakup satu bulan kalender
    if (from && to && from.length === 10 && to.length === 10) {
        const [y1, m1, d1] = from.split('-').map(Number);
        const [y2, m2, d2] = to.split('-').map(Number);
        if (y1 === y2 && m1 === m2 && d1 === 1) {
            const lastDay = new Date(y1, m1, 0).getDate();
            if (d2 === lastDay) {
                return 'specific_month';
            }
        }
    }

    return 'custom';
}

export default function DateRangeFilter({
    from = '',
    to = '',
    onChange,
    label = 'Periode Tanggal',
    showManualInputs = true,
    className = ''
}) {
    const [selectedMonth, setSelectedMonth] = useState(() => {
        if (from) {
            return from.substring(0, 7); // 'YYYY-MM'
        }
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}`;
    });

    const activePreset = detectActivePreset(from, to);

    const handlePresetChange = (preset) => {
        if (preset === 'custom') {
            return;
        }
        if (preset === 'specific_month') {
            const range = getPresetDates('specific_month', selectedMonth);
            if (range && onChange) {
                onChange(range.from, range.to);
            }
            return;
        }
        const range = getPresetDates(preset);
        if (range && onChange) {
            onChange(range.from, range.to);
        }
    };

    const handleMonthChange = (e) => {
        const monthVal = e.target.value;
        setSelectedMonth(monthVal);
        if (monthVal) {
            const range = getPresetDates('specific_month', monthVal);
            if (range && onChange) {
                onChange(range.from, range.to);
            }
        }
    };

    return (
        <div className={`space-y-1.5 ${className}`}>
            <div className="flex items-center justify-between">
                <Label className="text-xs font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <Calendar className="h-3.5 w-3.5 text-primary" />
                    {label}
                </Label>
                {activePreset === 'specific_month' && (
                    <span className="text-[11px] text-primary font-semibold">
                        Filter Bulanan Aktif
                    </span>
                )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {/* Dropdown Preset & Filter Bulan */}
                <div>
                    <Select value={activePreset} onValueChange={handlePresetChange}>
                        <SelectTrigger className="h-9 text-xs bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                            <SelectValue placeholder="Pilih Periode..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="this_month">📅 Bulan Ini</SelectItem>
                            <SelectItem value="last_month">📅 Bulan Lalu</SelectItem>
                            <SelectItem value="specific_month">🗓️ Pilih Bulan Tertentu...</SelectItem>
                            <SelectItem value="today">☀️ Hari Ini</SelectItem>
                            <SelectItem value="yesterday">⏪ Kemarin</SelectItem>
                            <SelectItem value="last_7">⚡ 7 Hari Terakhir</SelectItem>
                            <SelectItem value="last_30">📊 30 Hari Terakhir</SelectItem>
                            <SelectItem value="this_year">📆 Tahun Ini</SelectItem>
                            <SelectItem value="custom">✏️ Kustom (Rentang Bebas)</SelectItem>
                        </SelectContent>
                    </Select>

                    {/* Input Pemilih Bulan jika mode Pilih Bulan Tertentu aktif */}
                    {activePreset === 'specific_month' && (
                        <div className="mt-1.5 flex items-center gap-1.5">
                            <span className="text-[11px] text-muted-foreground whitespace-nowrap">Bulan:</span>
                            <Input
                                type="month"
                                value={selectedMonth}
                                onChange={handleMonthChange}
                                className="h-8 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-primary/40 focus:border-primary"
                            />
                        </div>
                    )}
                </div>

                {/* Kolom Tanggal Dari & Sampai (Selalu sinkron dan transparan) */}
                {showManualInputs && (
                    <div className="flex items-center gap-1.5">
                        <div className="flex-1">
                            <Input
                                type="date"
                                value={from || ''}
                                onChange={(e) => onChange && onChange(e.target.value, to)}
                                className="h-9 text-xs bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800"
                                title="Dari Tanggal"
                            />
                        </div>
                        <span className="text-slate-400 text-xs font-semibold">s/d</span>
                        <div className="flex-1">
                            <Input
                                type="date"
                                value={to || ''}
                                onChange={(e) => onChange && onChange(from, e.target.value)}
                                className="h-9 text-xs bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800"
                                title="Sampai Tanggal"
                            />
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
