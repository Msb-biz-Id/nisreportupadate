import React, { useState, useRef, useEffect } from 'react';
import { Search, ChevronDown, Check, Sparkles, X, Tag } from 'lucide-react';

export default function SearchableItemCombobox({
    value = '',
    onChange,
    items = [],
    error,
    placeholder = 'Ketik untuk mencari dari katalog 383 item atau masukkan custom...',
    disabled = false,
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState(value || '');
    const containerRef = useRef(null);
    const inputRef = useRef(null);

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

    // Filter dynamic items
    const normalizedItems = items.map((item) => {
        if (typeof item === 'object') {
            return {
                id: item.id || item.code || item.name,
                name: item.name,
                code: item.code,
            };
        }
        return { id: item, name: String(item), code: '' };
    });

    const filteredItems = searchQuery.trim() === ''
        ? normalizedItems.slice(0, 30) // Show top 30 initially
        : normalizedItems.filter((item) =>
            item.name.toLowerCase().includes(searchQuery.toLowerCase())
        ).slice(0, 40);

    const handleSelect = (itemName) => {
        setSearchQuery(itemName);
        onChange(itemName);
        setIsOpen(false);
    };

    const handleInputChange = (e) => {
        const val = e.target.value;
        setSearchQuery(val);
        onChange(val);
        if (!isOpen) setIsOpen(true);
    };

    const handleClear = () => {
        setSearchQuery('');
        onChange('');
        inputRef.current?.focus();
    };

    return (
        <div ref={containerRef} className="relative w-full">
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
                    className={`w-full pl-9 pr-16 py-2 text-xs rounded-xl border transition-all ${
                        error
                            ? 'border-rose-400 focus:ring-rose-500'
                            : 'border-slate-300 dark:border-slate-700 dark:bg-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20'
                    }`}
                />

                <div className="absolute inset-y-0 right-0 pr-2 flex items-center gap-1">
                    {searchQuery && (
                        <button
                            type="button"
                            onClick={handleClear}
                            className="p-1 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <X className="w-3.5 h-3.5" />
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => setIsOpen(!isOpen)}
                        className="p-1 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <ChevronDown className={`w-3.5 h-3.5 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} />
                    </button>
                </div>
            </div>

            {error && <p className="text-xs text-rose-500 mt-1">{error}</p>}

            {/* Floating Suggestion Popover */}
            {isOpen && (
                <div className="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl overflow-hidden max-h-72 flex flex-col animate-in fade-in zoom-in-95 duration-150">
                    <div className="p-2.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
                        <span className="flex items-center gap-1.5 font-medium text-blue-600 dark:text-blue-400">
                            <Sparkles className="w-3.5 h-3.5" />
                            Katalog Dinamis Master NIS ({normalizedItems.length} Item)
                        </span>
                        <span>{filteredItems.length} hasil cocok</span>
                    </div>

                    <div className="overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 max-h-60">
                        {filteredItems.length > 0 ? (
                            filteredItems.map((item) => {
                                const isSelected = item.name.toLowerCase() === value.toLowerCase();
                                return (
                                    <button
                                        key={item.id}
                                        type="button"
                                        onClick={() => handleSelect(item.name)}
                                        className={`w-full px-3 py-2 text-left text-xs flex items-center justify-between hover:bg-blue-50/80 dark:hover:bg-slate-800 transition-colors ${
                                            isSelected
                                                ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold'
                                                : 'text-slate-700 dark:text-slate-200'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2 truncate pr-2">
                                            <Tag className="w-3 h-3 text-slate-400 shrink-0" />
                                            <span className="truncate">{item.name}</span>
                                        </div>
                                        {isSelected && <Check className="w-3.5 h-3.5 text-blue-600 shrink-0" />}
                                    </button>
                                );
                            })
                        ) : (
                            <div className="p-4 text-center">
                                <p className="text-xs text-slate-500">Tidak ada dalam katalog standar.</p>
                                {searchQuery && (
                                    <button
                                        type="button"
                                        onClick={() => handleSelect(searchQuery)}
                                        className="mt-2 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold"
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
