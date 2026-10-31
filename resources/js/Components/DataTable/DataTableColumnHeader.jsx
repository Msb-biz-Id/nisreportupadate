import React from 'react';
import { ArrowDown, ArrowUp, ChevronsUpDown, EyeOff } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export function DataTableColumnHeader({ column, title, className }) {
    const alignRight = typeof className === 'string' && className.includes('justify-end');

    if (!column.getCanSort()) {
        return (
            <div className={cn('text-xs font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap', alignRight && 'text-right', className)}>
                {title}
            </div>
        );
    }

    const isSorted = column.getIsSorted();

    const handleToggleSort = (e) => {
        e.stopPropagation();
        if (!isSorted) {
            column.toggleSorting(false); // Ascending (A-Z / terkecil)
        } else if (isSorted === 'asc') {
            column.toggleSorting(true); // Descending (Z-A / terbesar)
        } else {
            column.clearSorting(); // Clear / Normal
        }
    };

    const sortIcon =
        isSorted === 'desc' ? (
            <ArrowDown className="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400 font-bold shrink-0" />
        ) : isSorted === 'asc' ? (
            <ArrowUp className="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400 font-bold shrink-0" />
        ) : (
            <ChevronsUpDown className="h-3.5 w-3.5 text-slate-400 opacity-60 group-hover/header:opacity-100 transition-opacity shrink-0" />
        );

    const sortButton = (
        <Button
            variant="ghost"
            size="sm"
            onClick={handleToggleSort}
            className={cn(
                'h-8 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center gap-1.5 cursor-pointer select-none transition-colors whitespace-nowrap',
                alignRight ? 'pr-0 pl-2' : '-ml-2 px-2'
            )}
            title="Klik untuk mengurutkan (A-Z / Z-A / Normal)"
        >
            {alignRight ? (
                <>
                    {sortIcon}
                    <span className="whitespace-nowrap">{title}</span>
                </>
            ) : (
                <>
                    <span className="whitespace-nowrap">{title}</span>
                    {sortIcon}
                </>
            )}
        </Button>
    );

    const chevronMenu = (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-7 w-6 p-0 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 opacity-0 group-hover/header:opacity-100 data-[state=open]:opacity-100 transition-opacity shrink-0"
                    title="Opsi pengurutan dan kolom"
                >
                    <span className="text-[10px] leading-none">▾</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align={alignRight ? 'end' : 'start'} className="w-44 text-xs">
                <DropdownMenuItem onClick={() => column.toggleSorting(false)} className="gap-2 cursor-pointer">
                    <ArrowUp className="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                    Urutkan A-Z / Terkecil
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => column.toggleSorting(true)} className="gap-2 cursor-pointer">
                    <ArrowDown className="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                    Urutkan Z-A / Terbesar
                </DropdownMenuItem>
                {isSorted && (
                    <DropdownMenuItem onClick={() => column.clearSorting()} className="gap-2 cursor-pointer text-slate-500">
                        <ChevronsUpDown className="h-3.5 w-3.5" />
                        Reset Urutan Normal
                    </DropdownMenuItem>
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={() => column.toggleVisibility(false)} className="gap-2 cursor-pointer text-slate-500">
                    <EyeOff className="h-3.5 w-3.5" />
                    Sembunyikan Kolom Ini
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );

    return (
        <div className={cn('flex items-center space-x-0.5 group/header whitespace-nowrap', alignRight && 'justify-end', className)}>
            {alignRight ? (
                sortButton
            ) : (
                <>
                    {sortButton}
                    {chevronMenu}
                </>
            )}
        </div>
    );
}

export default DataTableColumnHeader;
