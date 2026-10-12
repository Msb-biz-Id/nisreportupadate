import { useState, useRef, useEffect } from 'react';
import { ChevronDown, Search, Check, X } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Single-value searchable select.
 * Props:
 *   value        – selected value (string)
 *   onValueChange – (string) => void
 *   options      – [{ value, label }]
 *   placeholder  – string shown when nothing selected
 *   clearable    – show X button to clear
 *   className
 */
export function SearchableSelect({ value, onValueChange, onChange, options = [], placeholder = '— Pilih —', clearable = true, disabled = false, className }) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const ref = useRef(null);
    const inputRef = useRef(null);

    const triggerChange = (val) => {
        if (typeof onValueChange === 'function') onValueChange(val);
        if (typeof onChange === 'function') onChange(val);
    };

    const selected = options.find((o) => o.value === value);
    const filtered = search
        ? options.filter((o) => o.label.toLowerCase().includes(search.toLowerCase()))
        : options;

    useEffect(() => {
        if (open) setTimeout(() => inputRef.current?.focus(), 0);
        else setSearch('');
    }, [open]);

    useEffect(() => {
        function onClick(e) {
            if (ref.current && !ref.current.contains(e.target)) setOpen(false);
        }
        document.addEventListener('mousedown', onClick);
        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    function select(val) {
        triggerChange(val);
        setOpen(false);
    }

    return (
        <div ref={ref} className={cn('relative', className)}>
            <button
                type="button"
                disabled={disabled}
                onClick={() => !disabled && setOpen((o) => !o)}
                className="flex h-8 w-full items-center justify-between gap-1.5 rounded-md border border-input bg-background px-2.5 py-1 text-xs shadow-xs ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap"
            >
                <span className={cn('whitespace-nowrap', !selected && 'text-muted-foreground')}>
                    {selected ? selected.label : placeholder}
                </span>
                <span className="flex shrink-0 items-center gap-1 pl-1">
                    {clearable && selected && !disabled && (
                        <X
                            className="h-3.5 w-3.5 text-muted-foreground hover:text-foreground"
                            onClick={(e) => { e.stopPropagation(); triggerChange(''); }}
                        />
                    )}
                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                </span>
            </button>

            {open && (
                <div className="absolute z-50 mt-1 min-w-full w-max max-w-[360px] rounded-md border border-slate-200 dark:border-slate-800 bg-popover shadow-lg overflow-hidden">
                    <div className="flex items-center border-b border-slate-100 dark:border-slate-800 px-2">
                        <Search className="mr-1.5 h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                        <input
                            ref={inputRef}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari..."
                            className="flex h-8 w-full bg-transparent py-1 text-xs outline-none placeholder:text-muted-foreground"
                        />
                    </div>
                    <div className="max-h-60 overflow-y-auto p-1">
                        {filtered.length === 0 ? (
                            <p className="py-4 text-center text-xs text-muted-foreground">Tidak ditemukan.</p>
                        ) : (
                            filtered.map((o) => (
                                <button
                                    key={o.value}
                                    type="button"
                                    onClick={() => select(o.value)}
                                    className="flex w-full items-center gap-2 rounded-sm px-2.5 py-1.5 text-xs hover:bg-accent hover:text-accent-foreground text-left"
                                >
                                    <Check className={cn('h-3.5 w-3.5 shrink-0', value === o.value ? 'opacity-100 text-indigo-600' : 'opacity-0')} />
                                    <span className="whitespace-nowrap">{o.label}</span>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
