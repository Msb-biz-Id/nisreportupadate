import React, { useState, useRef, useEffect, useCallback } from 'react';
import { Search, ChevronDown, Check, Sparkles, X, Tag } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function SearchableItemCombobox({
    value = '',
    onChange,
    items = [],
    error,
    placeholder = 'Ketik untuk mencari dari katalog item atau masukkan custom...',
    disabled = false,
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState(value || '');
    const [highlightIndex, setHighlightIndex] = useState(-1);
    const containerRef = useRef(null);
    const inputRef = useRef(null);
    const listRef = useRef(null);

    // Sync input when prop value changes externally
    useEffect(() => {
        setSearchQuery(value || '');
    }, [value]);

    // Close dropdown on click outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (containerRef.current && !containerRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Close on window scroll so dropdown doesn't float disconnectedly over sticky headers
    useEffect(() => {
        if (!isOpen) return;
        const handleWindowScroll = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        window.addEventListener('scroll', handleWindowScroll, { passive: true, capture: true });
        return () => window.removeEventListener('scroll', handleWindowScroll, { capture: true });
    }, [isOpen]);

    // Filter dynamic items
    const normalizedItems = React.useMemo(() => {
        return items.map((item) => {
            if (typeof item === 'object') {
                return {
                    id: item.id || item.code || item.name,
                    name: item.name,
                    code: item.code,
                };
            }
            return { id: item, name: String(item), code: '' };
        });
    }, [items]);

    const filteredItems = React.useMemo(() => {
        if (searchQuery.trim() === '') {
            return normalizedItems.slice(0, 30);
        }
        return normalizedItems.filter((item) =>
            item.name.toLowerCase().includes(searchQuery.toLowerCase())
        ).slice(0, 40);
    }, [normalizedItems, searchQuery]);

    const handleSelect = useCallback((itemName) => {
        setSearchQuery(itemName);
        onChange(itemName);
        setIsOpen(false);
    }, [onChange]);

    const handleInputChange = (e) => {
        const val = e.target.value;
        setSearchQuery(val);
        onChange(val);
        if (!isOpen) setIsOpen(true);
        setHighlightIndex(-1);
    };

    const handleClear = () => {
        setSearchQuery('');
        onChange('');
        inputRef.current?.focus();
    };

    // Keyboard navigation
    const handleKeyDown = (e) => {
        if (!isOpen) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setIsOpen(true);
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlightIndex((prev) => {
                const next = prev < filteredItems.length - 1 ? prev + 1 : 0;
                scrollItemIntoView(next);
                return next;
            });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlightIndex((prev) => {
                const next = prev > 0 ? prev - 1 : filteredItems.length - 1;
                scrollItemIntoView(next);
                return next;
            });
        } else if (e.key === 'Enter') {
            if (highlightIndex >= 0 && highlightIndex < filteredItems.length) {
                e.preventDefault();
                handleSelect(filteredItems[highlightIndex].name);
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            setIsOpen(false);
        }
    };

    const scrollItemIntoView = (index) => {
        if (!listRef.current) return;
        const domItems = listRef.current.querySelectorAll('[data-combobox-item]');
        if (domItems[index]) {
            domItems[index].scrollIntoView({ block: 'nearest' });
        }
    };

    return (
        <div ref={containerRef} className="relative w-full antialiased [text-rendering:optimizeLegibility]" onKeyDown={handleKeyDown}>
            <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <Search className="w-4 h-4" />
                </div>

                <input
                    ref={inputRef}
                    type="text"
                    disabled={disabled}
                    value={searchQuery}
                    onChange={handleInputChange}
                    onFocus={() => setIsOpen(true)}
                    placeholder={placeholder}
                    className={cn(
                        'w-full h-9 pl-9 pr-16 text-xs rounded-md border transition-all duration-150 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none',
                        isOpen ? 'border-blue-500 ring-2 ring-blue-500/20' : '',
                        error
                            ? 'border-rose-400 focus:ring-rose-500'
                            : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20'
                    )}
                />

                <div className="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                    {searchQuery && (
                        <button
                            type="button"
                            onClick={handleClear}
                            title="Bersihkan input"
                            className="p-1 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <X className="w-3.5 h-3.5" />
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => setIsOpen(!isOpen)}
                        title={isOpen ? 'Tutup daftar' : 'Buka katalog item'}
                        className="p-1 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <ChevronDown className={cn('w-4 h-4 transition-transform duration-200', isOpen && 'rotate-180 text-blue-600')} />
                    </button>
                </div>
            </div>

            {error && <p className="text-xs text-rose-500 mt-1 font-medium">{error}</p>}

            {/* Floating Suggestion Popover (No scale transforms to prevent ClearType blur) */}
            {isOpen && (
                <div
                    className="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl overflow-hidden max-h-80 flex flex-col animate-in fade-in slide-in-from-top-1 duration-150 ring-1 ring-black/5 dark:ring-white/10"
                    style={{
                        WebkitFontSmoothing: 'antialiased',
                        MozOsxFontSmoothing: 'grayscale',
                        textRendering: 'optimizeLegibility',
                    }}
                >
                    <div className="p-2.5 bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
                        <span className="flex items-center gap-1.5 font-semibold text-blue-600 dark:text-blue-400">
                            <Sparkles className="w-3.5 h-3.5" />
                            Katalog Item Pengadaan ({normalizedItems.length} Item)
                        </span>
                        <span className="font-mono text-slate-400">{filteredItems.length} hasil cocok</span>
                    </div>

                    <div
                        ref={listRef}
                        className="overflow-y-auto max-h-64 p-1.5 space-y-0.5 [scrollbar-width:thin] [scrollbar-color:theme(colors.slate.300)_transparent] dark:[scrollbar-color:theme(colors.slate.700)_transparent]"
                    >
                        {filteredItems.length > 0 ? (
                            filteredItems.map((item, index) => {
                                const isSelected = item.name.toLowerCase() === (value || '').toLowerCase();
                                const isHighlighted = index === highlightIndex;
                                return (
                                    <button
                                        key={item.id}
                                        data-combobox-item
                                        type="button"
                                        onClick={() => handleSelect(item.name)}
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
                                        <div className="flex items-center gap-2 truncate pr-2">
                                            <Tag className="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-500 shrink-0" />
                                            <span className="text-xs font-semibold truncate leading-tight">{item.name}</span>
                                        </div>
                                        {isSelected && <Check className="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 ml-2" />}
                                    </button>
                                );
                            })
                        ) : (
                            <div className="p-4 text-center">
                                <p className="text-xs text-slate-500 font-medium">Tidak ada item yang cocok dalam katalog.</p>
                                {searchQuery && (
                                    <button
                                        type="button"
                                        onClick={() => handleSelect(searchQuery)}
                                        className="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition-colors"
                                    >
                                        + Gunakan "{searchQuery}" sebagai Nama Baru
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
