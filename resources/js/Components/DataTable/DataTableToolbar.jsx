import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Copy, Download, FileSpreadsheet, Search, X, Check, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { toast } from 'sonner';
import DataTableViewOptions from './DataTableViewOptions';

export function DataTableToolbar({
    table,
    searchValue = '',
    onSearchChange = null,
    searchPlaceholder = 'Cari data...',
    enableExportExcel = true,
    exportFileName = 'export_data',
    enableColumnVisibility = true,
    serverPagination = null,
    onServerPageChange = null,
    children = null,
}) {
    const [copied, setCopied] = useState(false);

    // Hitung halaman aktif & total halaman untuk Quick Navigator di Toolbar
    const isServer = !!serverPagination;
    const paginationState = (typeof table?.getState === 'function' ? table.getState()?.pagination : null) || table?.state?.pagination || { pageIndex: 0, pageSize: 15 };
    const currentPage = isServer
        ? (serverPagination.current_page ?? 1)
        : (paginationState.pageIndex ?? 0) + 1;
    const pageCount = isServer
        ? (serverPagination.last_page ?? 1)
        : (typeof table?.getPageCount === 'function' ? table.getPageCount() : 1);

    const handleQuickPage = (newPage) => {
        if (newPage < 1 || newPage > pageCount) return;
        if (isServer && onServerPageChange) {
            onServerPageChange(newPage);
        } else if (isServer && typeof window !== 'undefined') {
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('page', String(newPage));
            router.get(`${window.location.pathname}?${searchParams.toString()}`, {}, {
                preserveState: true,
                replace: true,
            });
        } else {
            table.setPageIndex(newPage - 1);
        }
    };

    // Ambil kolom yang terlihat (abaikan kolom aksi / checkbox)
    const getExportableColumns = () => {
        return table.getVisibleLeafColumns().filter((col) => {
            const id = col.id;
            return id !== 'select' && id !== 'actions' && id !== 'action';
        });
    };

    // Ambil baris target (prioritaskan baris terseleksi jika ada)
    const getTargetRows = () => {
        const selected = table.getFilteredSelectedRowModel().rows;
        if (selected.length > 0) {
            return { rows: selected, isSelectedOnly: true };
        }
        return { rows: table.getFilteredRowModel().rows, isSelectedOnly: false };
    };

    // 1. Ekspor / Salin ke Clipboard (TSV format untuk Excel & Google Sheets)
    const handleCopyForExcel = async () => {
        const columns = getExportableColumns();
        const { rows, isSelectedOnly } = getTargetRows();

        if (rows.length === 0) {
            toast.error('Tidak ada data untuk disalin.');
            return;
        }

        // Header
        const headerRow = columns
            .map((col) => {
                const header = col.columnDef.meta?.title || col.columnDef.header;
                return typeof header === 'string' ? header : col.id;
            })
            .join('\t');

        // Body baris data
        const bodyRows = rows.map((row) => {
            return columns
                .map((col) => {
                    const cellVal = row.getValue(col.id);
                    if (cellVal === null || cellVal === undefined) return '';
                    if (typeof cellVal === 'object') return JSON.stringify(cellVal);
                    // Bersihkan newline atau tab di dalam isi sel
                    return String(cellVal).replace(/[\t\n\r]+/g, ' ');
                })
                .join('\t');
        });

        const tsvText = `${headerRow}\n${bodyRows.join('\n')}`;

        try {
            await navigator.clipboard.writeText(tsvText);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);

            const scopeText = isSelectedOnly ? `${rows.length} baris terpilih` : `${rows.length} baris data`;
            toast.success(`Berhasil menyalin ${scopeText} ke clipboard!`, {
                description: 'Buka Microsoft Excel / Google Sheets lalu tekan Ctrl + V.',
            });
        } catch (err) {
            console.error('Gagal menyalin:', err);
            toast.error('Gagal mengakses clipboard sistem.');
        }
    };

    // 2. Unduh Berkas Excel (.xls — HTML table ber-mime Excel, tanpa dependensi)
    const handleDownloadExcel = () => {
        const columns = getExportableColumns();
        const { rows } = getTargetRows();

        if (rows.length === 0) {
            toast.error('Tidak ada data untuk diunduh.');
            return;
        }

        const escapeHtml = (val) => {
            if (val === null || val === undefined) return '';
            const str = typeof val === 'object' ? JSON.stringify(val) : String(val);
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        };

        const headers = columns.map((col) => {
            const header = col.columnDef.meta?.title || col.columnDef.header;
            return typeof header === 'string' ? header : col.id;
        });

        const bodyRows = rows.map((row) => columns.map((col) => row.getValue(col.id)));

        const html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="utf-8">
<style>
th { background-color: #1e293b; color: #ffffff; font-weight: bold; border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
td { border: 1px solid #cbd5e1; padding: 4px; vertical-align: top; }
</style></head>
<body><table>
<thead><tr>${headers.map((h) => `<th>${escapeHtml(h)}</th>`).join('')}</tr></thead>
<tbody>${bodyRows.map((r) => `<tr>${r.map((v) => `<td>${escapeHtml(v)}</td>`).join('')}</tr>`).join('')}</tbody>
</table></body></html>`;

        const blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', `${exportFileName}_${new Date().toISOString().slice(0, 10)}.xls`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        toast.success(`Berkas Excel (.xls) berhasil diunduh (${rows.length} baris).`);
    };

    const selectedCount = table.getFilteredSelectedRowModel().rows.length;

    return (
        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 py-2">
            {/* Sisi Kiri: Search Input & Custom Filters */}
            <div className="flex flex-1 flex-wrap items-center gap-2">
                <div className="relative w-full sm:w-64 lg:w-72">
                    <Search className="w-4 h-4 absolute left-2.5 top-2.5 text-slate-400" />
                    <Input
                        placeholder={searchPlaceholder}
                        value={searchValue}
                        onChange={(e) => onSearchChange && onSearchChange(e.target.value)}
                        className="h-8 pl-8 text-xs rounded-lg border-slate-200 dark:border-slate-800"
                    />
                    {searchValue && (
                        <button
                            type="button"
                            onClick={() => onSearchChange && onSearchChange('')}
                            className="absolute right-2 top-2 p-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            <X className="w-3.5 h-3.5" />
                        </button>
                    )}
                </div>

                {/* Slot Custom Faceted Filters (misal kategori, status, departemen) */}
                {children}
            </div>

            {/* Sisi Kanan: Quick Page Navigator, Tombol Salin ke Excel & Opsi Kolom */}
            <div className="flex items-center gap-2 self-stretch sm:self-auto justify-end flex-wrap">
                {/* Quick Page Indicator & Flipper di Toolbar Atas */}
                {pageCount > 1 && (
                    <div className="flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100/90 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 text-xs">
                        <span className="font-semibold text-slate-700 dark:text-slate-300 text-[11px] whitespace-nowrap">
                            Hal {currentPage}/{pageCount}
                        </span>
                        <div className="flex items-center gap-0.5 ml-0.5">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-6 w-6 p-0 text-slate-600 hover:text-slate-900 dark:hover:text-slate-100"
                                onClick={() => handleQuickPage(currentPage - 1)}
                                disabled={currentPage <= 1}
                                title="Halaman Sebelumnya"
                            >
                                <ChevronLeft className="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-6 w-6 p-0 text-slate-600 hover:text-slate-900 dark:hover:text-slate-100"
                                onClick={() => handleQuickPage(currentPage + 1)}
                                disabled={currentPage >= pageCount}
                                title="Halaman Berikutnya"
                            >
                                <ChevronRight className="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                )}

                {enableExportExcel && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="outline"
                                size="sm"
                                className="h-8 gap-2 border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 hover:text-emerald-800 text-xs font-semibold"
                            >
                                {copied ? (
                                    <Check className="h-3.5 w-3.5 text-emerald-600 animate-in zoom-in" />
                                ) : (
                                    <FileSpreadsheet className="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" />
                                )}
                                <span>
                                    {selectedCount > 0 ? `Excel (${selectedCount} Dipilih)` : 'Salin ke Excel'}
                                </span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56 text-xs">
                            <DropdownMenuLabel className="text-[11px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <FileSpreadsheet className="w-3.5 h-3.5 text-emerald-600" />
                                Interoperabilitas Excel
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={handleCopyForExcel} className="gap-2.5 cursor-pointer py-2">
                                <Copy className="h-3.5 w-3.5 text-indigo-600" />
                                <div>
                                    <p className="font-semibold text-slate-800 dark:text-slate-200">
                                        {selectedCount > 0 ? `Salin ${selectedCount} Baris Terpilih` : 'Salin Data Halaman Ini'}
                                    </p>
                                    <p className="text-[10px] text-slate-400">Siap tempel (Ctrl+V) di Excel / Sheets</p>
                                </div>
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={handleDownloadExcel} className="gap-2.5 cursor-pointer py-2">
                                <Download className="h-3.5 w-3.5 text-emerald-600" />
                                <div>
                                    <p className="font-semibold text-slate-800 dark:text-slate-200">Unduh Berkas Excel (.xls)</p>
                                    <p className="text-[10px] text-slate-400">Format Excel langsung (tanpa CSV)</p>
                                </div>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}

                {enableColumnVisibility && <DataTableViewOptions table={table} />}
            </div>
        </div>
    );
}

export default DataTableToolbar;
