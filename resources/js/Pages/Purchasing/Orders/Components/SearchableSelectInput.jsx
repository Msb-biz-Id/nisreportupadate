import React, { useState, useRef, useEffect, useMemo } from 'react';
import { Search, ChevronDown, Check, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Badge } from '@/Components/ui/badge';

export default function SearchableSelectInput({
    value = '',
    onChange,
    options = [],
    placeholder = '-- Pilih Opsi --',
    searchPlaceholder = 'Ketik untuk mencari...',
    disabled = false,
    error = null,
    allowClear = true,
    className = '',
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef(null);
    const inputRef = useRef(null);

    const selectedOption = useMemo(() => {
        return options.find((opt) => String(opt.value) === String(value));
    }, [options, value]);

    const filteredOptions = useMemo(() => {
        if (!search.trim()) return options;
        const q = search.toLowerCase();
        return options.filter((opt) => {
            const labelMatch = (opt.label || '').toLowerCase().includes(q);
            const sublabelMatch = (opt.sublabel || '').toLowerCase().includes(q);
            const badgeMatch = (opt.badge || '').toLowerCase().includes(q);
            return labelMatch || sublabelMatch || badgeMatch;
        });
    }, [options, search]);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        if (isOpen) {
            setTimeout(() => inputRef.current?.focus(), 50);
        } else {
            setSearch('');
        }
    }, [isOpen]);

    const handleSelect = (val, opt) => {
        onChange(val, opt);
        setIsOpen(false);
        setSearch('');
    };

    const handleClear = (e) => {
        e.stopPropagation();
        onChange('', null);
        setSearch('');
    };

    return (
        <div ref={containerRef} className={cn('relative w-full', className)}>
            <button
                type="button"
                disabled={disabled}
                onClick={() => !disabled && setIsOpen(!isOpen)}
                className={cn(
                    'flex h-9 w-full items-center justify-between rounded-md border bg-white dark:bg-slate-900 px-3 py-1.5 text-xs text-left shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-50',
                    error
                        ? 'border-rose-400 focus:border-rose-500'
                        : 'border-slate-300 dark:border-slate-700 hover:border-slate-400 focus:border-blue-500'
                )}
            >
                <div className="flex items-center gap-1.5 truncate pr-2">
                    {selectedOption ? (
                        <>
                            <span className="font-medium text-slate-800 dark:text-slate-200 truncate">
                                {selectedOption.label}
                            </span>
                            {selectedOption.badge && (
                                <Badge variant="outline" className="text-[10px] py-0 px-1.5 shrink-0 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                    {selectedOption.badge}
                                </Badge>
                            )}
                        </>
                    ) : (
                        <span className="text-slate-400 italic truncate">{placeholder}</span>
                    )}
                </div>

                <div className="flex items-center gap-1 shrink-0 text-slate-400">
                    {allowClear && selectedOption && !disabled && (
                        <span
                            role="button"
                            tabIndex={0}
                            onClick={handleClear}
                            className="p-0.5 rounded hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            <X className="w-3.5 h-3.5" />
                        </span>
                    )}
                    <ChevronDown className={cn('w-3.5 h-3.5 transition-transform duration-200', isOpen && 'rotate-180')} />
                </div>
            </button>

            {error && <p className="text-[11px] text-rose-500 mt-1">{error}</p>}

            {/* Dropdown Popover */}
            {isOpen && (
                <div className="absolute z-50 left-0 right-0 mt-1 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl overflow-hidden flex flex-col max-h-72 animate-in fade-in zoom-in-95 duration-100">
                    {/* Search Field */}
                    <div className="p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-center gap-2">
                        <Search className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                        <input
                            ref={inputRef}
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="w-full bg-transparent text-xs outline-none placeholder:text-slate-400 border-none p-0 focus:ring-0"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5"
                            >
                                <X className="w-3 h-3" />
                            </button>
                        )}
                    </div>

                    {/* Options List */}
                    <div className="overflow-y-auto max-h-56 p-1 divide-y divide-slate-100/50 dark:divide-slate-800/50">
                        {filteredOptions.length > 0 ? (
                            filteredOptions.map((opt) => {
                                const isSelected = String(opt.value) === String(value);
                                return (
                                    <button
                                        key={String(opt.value)}
                                        type="button"
                                        onClick={() => handleSelect(opt.value, opt)}
                                        className={cn(
                                            'w-full px-2.5 py-2 text-left text-xs flex items-center justify-between rounded-lg transition-colors group',
                                            isSelected
                                                ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold'
                                                : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'
                                        )}
                                    >
                                        <div className="flex flex-col min-w-0 pr-2">
                                            <div className="flex items-center gap-1.5">
                                                <span className="truncate">{opt.label}</span>
                                                {opt.badge && (
                                                    <span className="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-normal shrink-0">
                                                        {opt.badge}
                                                    </span>
                                                )}
                                            </div>
                                            {opt.sublabel && (
                                                <span className="text-[11px] text-slate-400 font-normal truncate mt-0.5">
                                                    {opt.sublabel}
                                                </span>
                                            )}
                                        </div>
                                        {isSelected && <Check className="w-3.5 h-3.5 text-blue-600 shrink-0" />}
                                    </button>
                                );
                            })
                        ) : (
                            <div className="py-6 text-center text-xs text-slate-400">
                                Tidak ada opsi yang cocok dengan "{search}".
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
