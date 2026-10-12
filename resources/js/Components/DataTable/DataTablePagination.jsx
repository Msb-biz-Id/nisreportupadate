import React from 'react';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';

export function DataTablePagination({
    table,
    serverPagination = null,
    onServerPageChange = null,
    onServerPerPageChange = null,
}) {
    // Mode Server vs Client
    const isServer = !!serverPagination;

    const paginationState = (typeof table?.getState === 'function' ? table.getState()?.pagination : null) || table?.state?.pagination || { pageIndex: 0, pageSize: 15 };

    const totalRows = isServer
        ? (serverPagination.total ?? 0)
        : (typeof table.getFilteredRowModel === 'function' ? table.getFilteredRowModel().rows.length : 0);

    const currentPage = isServer
        ? (serverPagination.current_page ?? 1)
        : (paginationState.pageIndex ?? 0) + 1;

    const pageSize = isServer
        ? (serverPagination.per_page ?? 15)
        : (paginationState.pageSize ?? 15);

    const pageCount = isServer
        ? (serverPagination.last_page ?? 1)
        : (typeof table?.getPageCount === 'function' ? table.getPageCount() : 1);

    const from = isServer
        ? (serverPagination.from ?? (totalRows > 0 ? (currentPage - 1) * pageSize + 1 : 0))
        : (totalRows > 0 ? (paginationState.pageIndex ?? 0) * pageSize + 1 : 0);

    const to = isServer
        ? (serverPagination.to ?? Math.min(currentPage * pageSize, totalRows))
        : Math.min(((paginationState.pageIndex ?? 0) + 1) * pageSize, totalRows);

    const selectedCount = table.getFilteredSelectedRowModel().rows.length;

    const handlePageChange = (newPage) => {
        if (newPage < 1 || newPage > pageCount) return;
        if (isServer && onServerPageChange) {
            onServerPageChange(newPage);
        } else {
            table.setPageIndex(newPage - 1);
        }
    };

    const handlePerPageChange = (newSize) => {
        const sizeNum = Number(newSize);
        if (isServer && onServerPerPageChange) {
            onServerPerPageChange(sizeNum);
        } else {
            table.setPageSize(sizeNum);
        }
    };

    return (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 px-2 py-3 text-xs text-slate-600 dark:text-slate-400">
            {/* Sisi Kiri: Status Baris & Seleksi */}
            <div className="flex items-center gap-2 text-xs">
                {selectedCount > 0 ? (
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-semibold text-[11px] border border-indigo-200 dark:border-indigo-800">
                        {selectedCount} dari {totalRows} baris dipilih
                    </span>
                ) : (
                    <span>
                        Menampilkan <strong className="font-semibold text-slate-800 dark:text-slate-200">{from}</strong>–<strong className="font-semibold text-slate-800 dark:text-slate-200">{to}</strong> dari{' '}
                        <strong className="font-semibold text-slate-800 dark:text-slate-200">{totalRows}</strong> data
                    </span>
                )}
            </div>

            {/* Sisi Kanan: Paginasi & Page Size Selector */}
            <div className="flex items-center flex-wrap gap-4 sm:gap-6">
                {/* Selector Baris Per Halaman */}
                <div className="flex items-center space-x-2">
                    <span className="text-xs whitespace-nowrap">Baris per hal:</span>
                    <Select
                        value={String(pageSize)}
                        onValueChange={handlePerPageChange}
                    >
                        <SelectTrigger className="h-8 w-[72px] text-xs">
                            <SelectValue placeholder={String(pageSize)} />
                        </SelectTrigger>
                        <SelectContent side="top">
                            {[10, 15, 25, 50, 100].map((size) => (
                                <SelectItem key={size} value={String(size)} className="text-xs">
                                    {size}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Halaman X dari Y */}
                <div className="flex items-center justify-center text-xs font-medium whitespace-nowrap min-w-[90px]">
                    Hal <strong className="mx-1 text-slate-900 dark:text-slate-100">{currentPage}</strong> dari{' '}
                    <strong className="ml-1 text-slate-900 dark:text-slate-100">{Math.max(1, pageCount)}</strong>
                </div>

                {/* Tombol Navigasi << < > >> */}
                <div className="flex items-center space-x-1">
                    <Button
                        variant="outline"
                        className="hidden h-8 w-8 p-0 lg:flex"
                        onClick={() => handlePageChange(1)}
                        disabled={currentPage <= 1}
                        title="Halaman Pertama"
                    >
                        <span className="sr-only">Halaman pertama</span>
                        <ChevronsLeft className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        className="h-8 w-8 p-0"
                        onClick={() => handlePageChange(currentPage - 1)}
                        disabled={currentPage <= 1}
                        title="Halaman Sebelumnya"
                    >
                        <span className="sr-only">Halaman sebelumnya</span>
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        className="h-8 w-8 p-0"
                        onClick={() => handlePageChange(currentPage + 1)}
                        disabled={currentPage >= pageCount}
                        title="Halaman Berikutnya"
                    >
                        <span className="sr-only">Halaman berikutnya</span>
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        className="hidden h-8 w-8 p-0 lg:flex"
                        onClick={() => handlePageChange(pageCount)}
                        disabled={currentPage >= pageCount}
                        title="Halaman Terakhir"
                    >
                        <span className="sr-only">Halaman terakhir</span>
                        <ChevronsRight className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}

export default DataTablePagination;
