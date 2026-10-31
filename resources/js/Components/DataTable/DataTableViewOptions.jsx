import React from 'react';
import { SlidersHorizontal } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export function DataTableViewOptions({ table }) {
    const columns = table
        .getAllColumns()
        .filter((column) => typeof column.accessorFn !== 'undefined' && column.getCanHide());

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="h-8 gap-2 border-slate-200 dark:border-slate-800 text-xs font-medium"
                >
                    <SlidersHorizontal className="h-3.5 w-3.5 text-slate-500" />
                    Opsi Kolom
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48 text-xs max-h-72 overflow-y-auto">
                <DropdownMenuLabel className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    Tampilkan Kolom
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {columns.map((column) => {
                    const header = column.columnDef.meta?.title || column.columnDef.header;
                    const label = typeof header === 'string' ? header : column.id;

                    return (
                        <DropdownMenuCheckboxItem
                            key={column.id}
                            className="capitalize cursor-pointer text-xs"
                            checked={column.getIsVisible()}
                            onCheckedChange={(value) => column.toggleVisibility(!!value)}
                        >
                            {label}
                        </DropdownMenuCheckboxItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export default DataTableViewOptions;
