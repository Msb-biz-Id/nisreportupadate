import React, { useState, useRef, useEffect, useMemo, useCallback } from 'react';
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
    const [highlightIndex, setHighlightIndex] = useState(-1);
    const containerRef = useRef(null);
    const inputRef = useRef(null);
    const listRef = useRef(null);

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

    // Close on click outside
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Close on window scroll to prevent dropdown floating awkwardly over sticky headers
    useEffect(() => {
        if (!isOpen) return;
        const handleWindowScroll = (e) => {
            // Only close if scrolling is outside the dropdown itself
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        window.addEventListener('scroll', handleWindowScroll, { passive: true, capture: true });
        return () => window.removeEventListener('scroll', handleWindowScroll, { capture: true });
    }, [isOpen]);

    // Reset search & focus input when opening
    useEffect(() => {
        if (isOpen) {
            setHighlightIndex(-1);
            const timer = setTimeout(() => {
                inputRef.current?.focus();
            }, 30);
            return () => clearTimeout(timer);
        } else {
            setSearch('');
            setHighlightIndex(-1);
        }
    }, [isOpen]);

    const handleSelect = useCallback((val, opt) => {
        onChange(val, opt);
        setIsOpen(false);
        setSearch('');
    }, [onChange]);

    const handleClear = (e) => {
        e.stopPropagation();
        onChange('', null);
        setSearch('');
    };

    // Keyboard navigation (ArrowDown, ArrowUp, Enter, Escape)
    const handleKeyDown = (e) => {
        if (!isOpen) {
            if (e.key === 'ArrowDown' || e.key === 'Enter') {
                e.preventDefault();
                setIsOpen(true);
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlightIndex((prev) => {
                const next = prev < filteredOptions.length - 1 ? prev + 1 : 0;
                scrollItemIntoView(next);
                return next;
            });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlightIndex((prev) => {
                const next = prev > 0 ? prev - 1 : filteredOptions.length - 1;
                scrollItemIntoView(next);
                return next;
            });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlightIndex >= 0 && highlightIndex < filteredOptions.length) {
                const opt = filteredOptions[highlightIndex];
                handleSelect(opt.value, opt);
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            setIsOpen(false);
        }
    };

    const scrollItemIntoView = (index) => {
        if (!listRef.current) return;
        const items = listRef.current.querySelectorAll('[data-dropdown-item]');
        if (items[index]) {
            items[index].scrollIntoView({ block: 'nearest' });
        }
    };

    return (
        <div ref={containerRef} className={cn('relative w-full antialiased [text-rendering:optimizeLegibility]', className)} onKeyDown={handleKeyDown}>
            {/* Trigger Button */}
            <button
                type="button"
                disabled={disabled}
                onClick={() => !disabled && setIsOpen(!isOpen)}
                className={cn(
                    'flex h-9 w-full items-center justify-between rounded-md border bg-white dark:bg-slate-900 px-3 py-1.5 text-xs text-left shadow-xs transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-50 select-none',
                    isOpen
                        ? 'border-blue-500 ring-2 ring-blue-500/20'
                        : error
                        ? 'border-rose-400 hover:border-rose-500'
                        : 'border-slate-300 dark:border-slate-700 hover:border-slate-400 focus:border-blue-500'
                )}
            >
                <div className="flex items-center gap-2 truncate pr-2 min-w-0">
                    {selectedOption ? (
                        <>
                            <span className="font-semibold text-slate-800 dark:text-slate-100 truncate">
                                {selectedOption.label}
                            </span>
                            {selectedOption.badge && (
                                <Badge
                                    variant="outline"
                                    className="text-[10px] py-0 px-1.5 shrink-0 bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border-blue-200/60 dark:border-blue-800/60 font-medium"
                                >
                                    {selectedOption.badge}
                                </Badge>
                            )}
                        </>
                    ) : (
                        <span className="text-slate-400 dark:text-slate-500 truncate">{placeholder}</span>
                    )}
                </div>

                <div className="flex items-center gap-1 shrink-0 text-slate-400">
                    {allowClear && selectedOption && !disabled && (
                        <span
                            role="button"
                            tabIndex={0}
                            onClick={handleClear}
                            title="Hapus pilihan"
                            className="p-1 rounded hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <X className="w-3.5 h-3.5" />
                        </span>
                    )}
                    <ChevronDown className={cn('w-4 h-4 text-slate-400 transition-transform duration-200', isOpen && 'rotate-180 text-blue-600')} />
                </div>
            </button>

            {error && <p className="text-xs text-rose-500 mt-1 font-medium">{error}</p>}

            {/* Dropdown Popover (No scale transforms to prevent ClearType blur) */}
            {isOpen && (
                <div
                    className="absolute z-50 left-0 mt-1 min-w-full w-full sm:min-w-[340px] max-w-[95vw] rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl overflow-hidden flex flex-col max-h-80 animate-in fade-in slide-in-from-top-1 duration-150 ring-1 ring-black/5 dark:ring-white/10"
                    style={{
                        WebkitFontSmoothing: 'antialiased',
                        MozOsxFontSmoothing: 'grayscale',
                        textRendering: 'optimizeLegibility',
                    }}
                >
                    {/* Search Field Header */}
                    <div className="p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 flex items-center gap-2">
                        <Search className="w-4 h-4 text-slate-400 shrink-0" />
                        <input
                            ref={inputRef}
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="w-full bg-transparent text-xs text-slate-800 dark:text-slate-100 outline-none placeholder:text-slate-400 dark:placeholder:text-slate-500 border-none p-0 focus:ring-0 font-medium"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded transition-colors"
                            >
                                <X className="w-3.5 h-3.5" />
                            </button>
                        )}
                        <span className="text-[10px] text-slate-400 dark:text-slate-500 font-mono shrink-0 pl-1">
                            {filteredOptions.length}
                        </span>
                    </div>

                    {/* Options List */}
                    <div
                        ref={listRef}
                        className="overflow-y-auto max-h-64 p-1.5 space-y-0.5 [scrollbar-width:thin] [scrollbar-color:theme(colors.slate.300)_transparent] dark:[scrollbar-color:theme(colors.slate.700)_transparent]"
                    >
                        {filteredOptions.length > 0 ? (
                            filteredOptions.map((opt, index) => {
                                const isSelected = String(opt.value) === String(value);
                                const isHighlighted = index === highlightIndex;
                                return (
                                    <button
                                        key={String(opt.value || index)}
                                        data-dropdown-item
                                        type="button"
                                        onClick={() => handleSelect(opt.value, opt)}
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
                                                    {opt.label}
                                                </span>
                                                {opt.badge && (
                                                    <span className="text-[10px] px-1.5 py-0.5 rounded font-mono font-medium shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                        {opt.badge}
                                                    </span>
                                                )}
                                            </div>
                                            {opt.sublabel && (
                                                <span className="text-[11px] text-slate-600 dark:text-slate-300 font-normal truncate mt-0.5 leading-tight">
                                                    {opt.sublabel}
                                                </span>
                                            )}
                                        </div>
                                        {isSelected && (
                                            <Check className="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 ml-2" />
                                        )}
                                    </button>
                                );
                            })
                        ) : (
                            <div className="py-6 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                Tidak ada opsi yang cocok dengan "{search}".
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
