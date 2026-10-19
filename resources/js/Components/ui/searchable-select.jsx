import React, { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { ChevronDown, Search, Check, X } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Single-value searchable select for HCM and general UI.
 * Props:
 *   value        – selected value (string/number)
 *   onValueChange – (string) => void
 *   onChange     – (string) => void
 *   options      – [{ value, label, sublabel?, badge? }]
 *   placeholder  – string shown when nothing selected
 *   clearable    – show X button to clear
 *   disabled     – boolean
 *   className    – string
 */
export function SearchableSelect({
    value = '',
    onValueChange,
    onChange,
    options = [],
    placeholder = '— Pilih —',
    clearable = true,
    disabled = false,
    className = '',
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [highlightIndex, setHighlightIndex] = useState(-1);
    const ref = useRef(null);
    const inputRef = useRef(null);
    const listRef = useRef(null);

    const triggerChange = useCallback((val) => {
        if (typeof onValueChange === 'function') onValueChange(val);
        if (typeof onChange === 'function') onChange(val);
    }, [onValueChange, onChange]);

    // Normalize option shapes gracefully
    const normalizedOptions = useMemo(() => {
        return options.map((opt) => {
            if (typeof opt === 'object' && opt !== null) {
                const val = opt.value !== undefined ? String(opt.value) : (opt.id !== undefined ? String(opt.id) : '');
                const lbl = opt.label !== undefined ? String(opt.label) : (opt.name !== undefined ? String(opt.name) : val);
                return {
                    value: val,
                    label: lbl,
                    sublabel: opt.sublabel || opt.description || '',
                    badge: opt.badge || '',
                };
            }
            return {
                value: String(opt),
                label: String(opt),
                sublabel: '',
                badge: '',
            };
        });
    }, [options]);

    const selected = useMemo(() => {
        return normalizedOptions.find((o) => String(o.value) === String(value));
    }, [normalizedOptions, value]);

    const filtered = useMemo(() => {
        if (!search.trim()) return normalizedOptions;
        const q = search.toLowerCase();
        return normalizedOptions.filter((o) => {
            const labelMatch = (o.label || '').toLowerCase().includes(q);
            const subMatch = (o.sublabel || '').toLowerCase().includes(q);
            const badgeMatch = (o.badge || '').toLowerCase().includes(q);
            return labelMatch || subMatch || badgeMatch;
        });
    }, [normalizedOptions, search]);

    // Auto focus search input on open
    useEffect(() => {
        if (open) {
            setHighlightIndex(-1);
            const timer = setTimeout(() => inputRef.current?.focus(), 30);
            return () => clearTimeout(timer);
        } else {
            setSearch('');
            setHighlightIndex(-1);
        }
    }, [open]);

    // Close on click outside
    useEffect(() => {
        function onClick(e) {
            if (ref.current && !ref.current.contains(e.target)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', onClick);
        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    // Close on window scroll to prevent dropdown floating over sticky headers / tables
    useEffect(() => {
        if (!open) return;
        const handleWindowScroll = (e) => {
            if (ref.current && !ref.current.contains(e.target)) {
                setOpen(false);
            }
        };
        window.addEventListener('scroll', handleWindowScroll, { passive: true, capture: true });
        return () => window.removeEventListener('scroll', handleWindowScroll, { capture: true });
    }, [open]);

    function select(val) {
        triggerChange(val);
        setOpen(false);
        setSearch('');
    }

    // Keyboard navigation
    const handleKeyDown = (e) => {
        if (!open) {
            if (e.key === 'ArrowDown' || e.key === 'Enter') {
                e.preventDefault();
                setOpen(true);
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlightIndex((prev) => {
                const next = prev < filtered.length - 1 ? prev + 1 : 0;
                scrollItemIntoView(next);
                return next;
            });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlightIndex((prev) => {
                const next = prev > 0 ? prev - 1 : filtered.length - 1;
                scrollItemIntoView(next);
                return next;
            });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlightIndex >= 0 && highlightIndex < filtered.length) {
                select(filtered[highlightIndex].value);
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            setOpen(false);
        }
    };

    const scrollItemIntoView = (index) => {
        if (!listRef.current) return;
        const domItems = listRef.current.querySelectorAll('[data-select-item]');
        if (domItems[index]) {
            domItems[index].scrollIntoView({ block: 'nearest' });
        }
    };

    return (
        <div
            ref={ref}
            className={cn('relative w-full antialiased [text-rendering:optimizeLegibility]', className)}
            onKeyDown={handleKeyDown}
        >
            <button
                type="button"
                disabled={disabled}
                onClick={() => !disabled && setOpen((o) => !o)}
                className={cn(
                    'flex h-9 w-full items-center justify-between gap-1.5 rounded-md border bg-white dark:bg-slate-900 px-3 py-1.5 text-xs text-left shadow-xs transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-50 select-none',
                    open
                        ? 'border-blue-500 ring-2 ring-blue-500/20'
                        : 'border-slate-300 dark:border-slate-700 hover:border-slate-400 focus:border-blue-500'
                )}
            >
                <div className="flex items-center gap-1.5 truncate pr-1 flex-1 min-w-0">
                    {selected ? (
                        <span className="font-semibold text-slate-800 dark:text-slate-100 truncate">
                            {selected.label}
                        </span>
                    ) : (
                        <span className="text-slate-400 dark:text-slate-500 truncate">{placeholder}</span>
                    )}
                    {selected?.badge && (
                        <span className="text-[10px] px-1.5 py-0.2 rounded font-mono font-medium shrink-0 bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60">
                            {selected.badge}
                        </span>
                    )}
                </div>
                <div className="flex shrink-0 items-center gap-1 pl-1 text-slate-400">
                    {clearable && selected && !disabled && (
                        <span
                            role="button"
                            tabIndex={0}
                            title="Hapus pilihan"
                            onClick={(e) => {
                                e.stopPropagation();
                                triggerChange('');
                                setSearch('');
                            }}
                            className="p-1 rounded hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <X className="h-3.5 w-3.5" />
                        </span>
                    )}
                    <ChevronDown className={cn('h-4 w-4 text-slate-400 transition-transform duration-200', open && 'rotate-180 text-blue-600')} />
                </div>
            </button>

            {/* Dropdown Popover (No scale transforms to keep text ClearType crisp) */}
            {open && (
                <div
                    className="absolute z-50 left-0 mt-1 min-w-full w-max max-w-[380px] rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl overflow-hidden flex flex-col max-h-80 animate-in fade-in slide-in-from-top-1 duration-150 ring-1 ring-black/5 dark:ring-white/10"
                    style={{
                        WebkitFontSmoothing: 'antialiased',
                        MozOsxFontSmoothing: 'grayscale',
                        textRendering: 'optimizeLegibility',
                    }}
                >
                    {/* Search Field Header */}
                    <div className="p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 flex items-center gap-2">
                        <Search className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                        <input
                            ref={inputRef}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Ketik untuk mencari..."
                            className="w-full bg-transparent text-xs text-slate-800 dark:text-slate-100 outline-none placeholder:text-slate-400 dark:placeholder:text-slate-500 border-none p-0 focus:ring-0 font-medium"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded transition-colors"
                            >
                                <X className="h-3.5 w-3.5" />
                            </button>
                        )}
                        <span className="text-[10px] text-slate-400 dark:text-slate-500 font-mono shrink-0 pl-1">
                            {filtered.length}
                        </span>
                    </div>

                    {/* Options List */}
                    <div
                        ref={listRef}
                        className="overflow-y-auto max-h-64 p-1.5 space-y-0.5 [scrollbar-width:thin] [scrollbar-color:theme(colors.slate.300)_transparent] dark:[scrollbar-color:theme(colors.slate.700)_transparent]"
                    >
                        {filtered.length === 0 ? (
                            <p className="py-5 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                Tidak ada opsi yang cocok dengan "{search}".
                            </p>
                        ) : (
                            filtered.map((o, index) => {
                                const isSelected = String(value) === String(o.value);
                                const isHighlighted = index === highlightIndex;
                                return (
                                    <button
                                        key={o.value || index}
                                        data-select-item
                                        type="button"
                                        onClick={() => select(o.value)}
                                        onMouseEnter={() => setHighlightIndex(index)}
                                        className={cn(
                                            'w-full px-3 py-2 text-left rounded-lg transition-colors flex items-center justify-between group cursor-pointer select-none',
                                            isSelected
                                                ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 font-semibold border border-blue-200/60 dark:border-blue-900/60'
                                                : isHighlighted
                                                ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100'
                                                : 'hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-800 dark:text-slate-200'
                                        )}
                                    >
                                        <div className="flex flex-col min-w-0 pr-2">
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs font-semibold truncate leading-tight text-slate-900 dark:text-slate-100">
                                                    {o.label}
                                                </span>
                                                {o.badge && (
                                                    <span className="text-[10px] px-1.5 py-0.5 rounded font-mono font-medium shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                        {o.badge}
                                                    </span>
                                                )}
                                            </div>
                                            {o.sublabel && (
                                                <span className="text-[11px] text-slate-600 dark:text-slate-300 font-normal truncate mt-0.5 leading-tight">
                                                    {o.sublabel}
                                                </span>
                                            )}
                                        </div>
                                        {isSelected && (
                                            <Check className="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0 ml-2" />
                                        )}
                                    </button>
                                );
                            })
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

export default SearchableSelect;
