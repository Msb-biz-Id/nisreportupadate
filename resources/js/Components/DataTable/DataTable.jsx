import React, { useState, useMemo } from 'react';
import {
    useTable,
    stockFeatures,
    createCoreRowModel,
    createSortedRowModel,
    createFilteredRowModel,
    createPaginatedRowModel,
    flexRender,
} from '@tanstack/react-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import DataTableToolbar from './DataTableToolbar';
import DataTablePagination from './DataTablePagination';
import { Loader2, Inbox } from 'lucide-react';
import { cn } from '@/lib/utils';

export function DataTable({
    columns,
    data = [],
    serverPagination = null,
    onServerPageChange = null,
    onServerPerPageChange = null,
    onServerSearchChange = null,
    searchValue = '',
    searchPlaceholder = 'Cari data...',
    filterSlot = null,
    enableExportExcel = true,
    exportFileName = 'export_data',
    enableColumnVisibility = true,
    enableRowSelection = false,
    isLoading = false,
    emptyMessage = 'Tidak ada data ditemukan.',
    className = '',
    tableClassName = '',
}) {
    const isServer = !!serverPagination;

    // Table State
    const [sorting, setSorting] = useState([]);
    const [columnVisibility, setColumnVisibility] = useState({});
    const [rowSelection, setRowSelection] = useState({});
    const [clientGlobalFilter, setClientGlobalFilter] = useState('');
    const [clientPagination, setClientPagination] = useState({
        pageIndex: 0,
        pageSize: 15,
    });

    const activeSearch = isServer ? searchValue : clientGlobalFilter;
    const handleSearchChange = (val) => {
        if (isServer) {
            if (onServerSearchChange) onServerSearchChange(val);
        } else {
            setClientGlobalFilter(val);
        }
    };

    const tableFeaturesConfig = useMemo(() => {
        const feat = {
            ...stockFeatures,
            sortedRowModel: createSortedRowModel(),
            filteredRowModel: createFilteredRowModel(),
        };
        if (!isServer) {
            feat.paginatedRowModel = createPaginatedRowModel();
        }
        return feat;
    }, [isServer]);

    const table = useTable(
        {
            features: tableFeaturesConfig,
            data: data || [],
            columns,
            state: {
                sorting,
                columnVisibility,
                rowSelection,
                pagination: isServer
                    ? {
                          pageIndex: Math.max(0, (serverPagination?.current_page || 1) - 1),
                          pageSize: serverPagination?.per_page || 15,
                      }
                    : clientPagination,
                globalFilter: isServer ? undefined : clientGlobalFilter,
            },
            enableRowSelection: enableRowSelection,
            onSortingChange: setSorting,
            onColumnVisibilityChange: setColumnVisibility,
            onRowSelectionChange: setRowSelection,
            onPaginationChange: isServer ? undefined : setClientPagination,
            onGlobalFilterChange: isServer ? undefined : setClientGlobalFilter,
            manualPagination: isServer,
            manualSorting: false,
            manualFiltering: isServer,
            pageCount: isServer ? (serverPagination?.last_page ?? -1) : undefined,
        },
        (state) => state
    );

    // Provide getState() helper for compatibility with standard TanStack v8/v9 toolbar and pagination
    if (typeof table.getState !== 'function') {
        table.getState = () => ({
            ...table.state,
            pagination: isServer
                ? {
                      pageIndex: Math.max(0, (serverPagination?.current_page || 1) - 1),
                      pageSize: serverPagination?.per_page || 15,
                  }
                : clientPagination,
            sorting,
            columnVisibility,
            rowSelection,
        });
    }

    const headerGroups = table.getHeaderGroups();
    const rows = table.getRowModel().rows;

    return (
        <div className={cn('space-y-0 w-full relative', className)}>
            {/* Top Toolbar: Search, Filters, Excel Export, Column Visibility & Quick Pagination */}
            <div className="bg-white dark:bg-zinc-900 px-3.5 py-2.5 rounded-t-xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <DataTableToolbar
                    table={table}
                    searchValue={activeSearch}
                    onSearchChange={handleSearchChange}
                    searchPlaceholder={searchPlaceholder}
                    enableExportExcel={enableExportExcel}
                    exportFileName={exportFileName}
                    enableColumnVisibility={enableColumnVisibility}
                    serverPagination={serverPagination}
                    onServerPageChange={onServerPageChange}
                >
                    {filterSlot}
                </DataTableToolbar>
            </div>

            {/* Container Tabel dengan border & rounded */}
            <div className="relative border-x border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-xs overflow-x-auto">
                {isLoading && (
                    <div className="absolute inset-0 z-20 flex items-center justify-center bg-white/70 dark:bg-slate-950/70 backdrop-blur-[1px] transition-all">
                        <div className="flex items-center gap-2.5 px-4 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md">
                            <Loader2 className="h-4 w-4 animate-spin text-indigo-600 dark:text-indigo-400" />
                            <span className="text-xs font-medium text-slate-700 dark:text-slate-300">
                                Memuat data...
                            </span>
                        </div>
                    </div>
                )}

                <Table containerClassName="overflow-visible w-full" className={cn('w-full', tableClassName)}>
                        <TableHeader className="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            {headerGroups.map((headerGroup) => (
                                <TableRow key={headerGroup.id} className="hover:bg-transparent">
                                    {headerGroup.headers.map((header) => {
                                        const isPinnedRight = header.column.columnDef.meta?.pinned === 'right';
                                        const isPinnedLeft = header.column.columnDef.meta?.pinned === 'left';

                                        return (
                                            <TableHead
                                                key={header.id}
                                                colSpan={header.colSpan}
                                                style={header.column.columnDef.size ? { width: `${header.column.columnDef.size}px`, maxWidth: `${header.column.columnDef.size}px` } : undefined}
                                                className={cn(
                                                    'h-10 px-3 text-xs font-semibold text-slate-700 dark:text-slate-300',
                                                    header.column.columnDef.meta?.headerClassName,
                                                    isPinnedRight &&
                                                        'sticky right-0 z-20 bg-slate-50 dark:bg-slate-800 border-l border-slate-200/80 dark:border-slate-800',
                                                    isPinnedLeft &&
                                                        'sticky left-0 z-20 bg-slate-50 dark:bg-slate-800 border-r border-slate-200/80 dark:border-slate-800'
                                                )}
                                            >
                                                {header.isPlaceholder
                                                    ? null
                                                    : flexRender(
                                                          header.column.columnDef.header,
                                                          header.getContext()
                                                      )}
                                            </TableHead>
                                        );
                                    })}
                                </TableRow>
                            ))}
                        </TableHeader>
                        <TableBody>
                            {rows.length > 0 ? (
                                rows.map((row) => (
                                    <TableRow
                                        key={row.id}
                                        data-state={row.getIsSelected() && 'selected'}
                                        className="group border-b border-slate-100 dark:border-slate-800/80 transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40"
                                    >
                                        {row.getVisibleCells().map((cell) => {
                                            const isPinnedRight = cell.column.columnDef.meta?.pinned === 'right';
                                            const isPinnedLeft = cell.column.columnDef.meta?.pinned === 'left';

                                            return (
                                                <TableCell
                                                    key={cell.id}
                                                    style={cell.column.columnDef.size ? { width: `${cell.column.columnDef.size}px`, maxWidth: `${cell.column.columnDef.size}px` } : undefined}
                                                    className={cn(
                                                        'px-3 py-2.5 text-xs text-slate-700 dark:text-slate-300 align-middle',
                                                        cell.column.columnDef.meta?.cellClassName,
                                                        isPinnedRight &&
                                                            'sticky right-0 z-10 bg-white dark:bg-slate-900 border-l border-slate-200/80 dark:border-slate-800 group-hover:bg-slate-50 dark:group-hover:bg-slate-800/90 transition-colors',
                                                        isPinnedLeft &&
                                                            'sticky left-0 z-10 bg-white dark:bg-slate-900 border-r border-slate-200/80 dark:border-slate-800 group-hover:bg-slate-50 dark:group-hover:bg-slate-800/90 transition-colors'
                                                    )}
                                                >
                                                    {flexRender(
                                                        cell.column.columnDef.cell,
                                                        cell.getContext()
                                                    )}
                                                </TableCell>
                                            );
                                        })}
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={columns.length}
                                        className="h-40 text-center text-slate-400 dark:text-slate-500"
                                    >
                                        <div className="flex flex-col items-center justify-center gap-2 py-6">
                                            <div className="p-3 rounded-full bg-slate-100 dark:bg-slate-800/60 text-slate-400">
                                                <Inbox className="w-6 h-6" />
                                            </div>
                                            <p className="text-xs font-medium text-slate-600 dark:text-slate-400">
                                                {emptyMessage}
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
            </div>

            {/* Bottom Pagination Bar (Solid Attached Footer) */}
            <div className="bg-white dark:bg-zinc-900 border-x border-b border-slate-200 dark:border-slate-800 px-3.5 py-2.5 rounded-b-xl shadow-xs">
                <DataTablePagination
                    table={table}
                    serverPagination={serverPagination}
                    onServerPageChange={onServerPageChange}
                    onServerPerPageChange={onServerPerPageChange}
                />
            </div>
        </div>
    );
}

export default DataTable;
