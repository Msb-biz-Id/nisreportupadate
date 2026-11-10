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
export function SearchableSelect({ value, onValueChange, options = [], placeholder = '— Pilih —', clearable = true, disabled = false, className }) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const ref = useRef(null);
    const inputRef = useRef(null);

    const selected = options.find((o) => o.value === value);
    const filtered = search
        ? options.filter((o) => {
            const q = search.toLowerCase().trim();
            const labelMatch = o.label?.toLowerCase().includes(q);
            const sublabelMatch = o.sublabel?.toLowerCase().includes(q);
            const searchKeyMatch = o.searchKey?.toLowerCase().includes(q);
            return Boolean(labelMatch || sublabelMatch || searchKeyMatch);
        })
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
        onValueChange(val);
        setOpen(false);
    }

    return (
        <div ref={ref} className={cn('relative', className)}>
            <button
                type="button"
                disabled={disabled}
                onClick={() => !disabled && setOpen((o) => !o)}
                className="flex h-8 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span className={cn('truncate', !selected && 'text-muted-foreground')}>
                    {selected ? selected.label : placeholder}
                </span>
                <span className="flex shrink-0 items-center gap-1 pl-1">
                    {clearable && selected && !disabled && (
                        <X
                            className="h-3.5 w-3.5 text-muted-foreground hover:text-foreground"
                            onClick={(e) => { e.stopPropagation(); onValueChange(''); }}
                        />
                    )}
                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                </span>
            </button>

            {open && (
                <div className="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-md">
                    <div className="flex items-center border-b px-2">
                        <Search className="mr-1 h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                        <input
                            ref={inputRef}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari..."
                            className="flex h-8 w-full bg-transparent py-1 text-sm outline-none placeholder:text-muted-foreground"
                        />
                    </div>
                    <div className="max-h-56 overflow-y-auto p-1">
                        {filtered.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">Tidak ditemukan.</p>
                        ) : (
                            filtered.map((o) => (
                                <button
                                    key={o.value}
                                    type="button"
                                    onClick={() => select(o.value)}
                                    className="flex w-full items-center justify-between rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground text-left"
                                >
                                    <div className="flex items-center gap-2 min-w-0">
                                        <Check className={cn('h-3.5 w-3.5 shrink-0', value === o.value ? 'opacity-100' : 'opacity-0')} />
                                        <div className="flex flex-col min-w-0">
                                            <span className="truncate font-medium leading-snug">{o.label}</span>
                                            {o.sublabel && (
                                                <span className="text-[11px] text-muted-foreground truncate leading-tight">
                                                    {o.sublabel}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    {o.badge && (
                                        <span className="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded ml-2 shrink-0">
                                            {o.badge}
                                        </span>
                                    )}
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
