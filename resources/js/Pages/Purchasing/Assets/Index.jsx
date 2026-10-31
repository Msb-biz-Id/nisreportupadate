import React, { useState, useMemo, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    Box, 
    Plus, 
    Search, 
    Filter, 
    Building2, 
    Cpu, 
    User, 
    AlertCircle, 
    CheckCircle2, 
    History, 
    AlertTriangle, 
    Eye, 
    ArrowRightLeft, 
    Trash2, 
    QrCode, 
    RefreshCw
} from 'lucide-react';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
import { formatDate, formatRupiah } from '@/lib/utils';
import AssetDetailDrawer from './Components/AssetDetailDrawer';
import CreateAssetModal from './Components/CreateAssetModal';
import MutateAssetModal from './Components/MutateAssetModal';
import RetireAssetModal from './Components/RetireAssetModal';

export default function PurchasingAssetsIndex({ 
    assets = { data: [], links: [] }, 
    stats = {}, 
    filters = {}, 
    masterOptions = {} 
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [categoryFilter, setCategoryFilter] = useState(filters.category_code || '');
    const [deptFilter, setDeptFilter] = useState(filters.department || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [stageFilter, setStageFilter] = useState(filters.registration_stage || '');

    // State Modals & Drawers
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [selectedAssetForDrawer, setSelectedAssetForDrawer] = useState(null);
    const [selectedAssetForMutate, setSelectedAssetForMutate] = useState(null);
    const [selectedAssetForRetire, setSelectedAssetForRetire] = useState(null);

    // Auto open drawer jika URL menyertakan open_asset
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const openCode = params.get('open_asset');
        if (openCode && assets.data) {
            const found = assets.data.find((a) => a.asset_code === openCode);
            if (found) {
                setSelectedAssetForDrawer(found);
            }
        }
    }, [assets.data]);

    const handleFilter = (newFilters = {}) => {
        router.get(
            route('purchasing.assets.index'),
            {
                search,
                category_code: categoryFilter,
                department: deptFilter,
                status: statusFilter,
                registration_stage: stageFilter,
                ...newFilters,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    };

    const handleResetFilter = () => {
        setSearch('');
        setCategoryFilter('');
        setDeptFilter('');
        setStatusFilter('');
        setStageFilter('');
        router.get(route('purchasing.assets.index'), {}, { replace: true });
    };

    // Definisi Kolom TanStack Table v9
    const columns = useMemo(() => [
        {
            accessorKey: 'asset_code',
            size: 145,
            minSize: 130,
            meta: { title: 'Kode Aset' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Kode Aset" />,
            cell: ({ row }) => {
                const asset = row.original;
                return (
                    <div className="min-w-[125px]">
                        <button
                            onClick={() => setSelectedAssetForDrawer(asset)}
                            className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 hover:underline flex items-center gap-1.5 group"
                        >
                            <span className="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-900 group-hover:bg-indigo-100 transition-colors">
                                {asset.asset_code}
                            </span>
                        </button>
                        {asset.mutations?.length > 0 && (
                            <span className="text-[10px] text-amber-600 dark:text-amber-400 flex items-center gap-1 mt-1">
                                <History className="w-3 h-3" /> Dimutasi {asset.mutations.length}x
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            accessorKey: 'asset_name',
            size: 240,
            minSize: 200,
            meta: { title: 'Nama Barang' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nama Barang & Detail" />,
            cell: ({ row }) => {
                const asset = row.original;
                return (
                    <div className="min-w-[190px]">
                        <div className="font-semibold text-slate-900 dark:text-slate-100">
                            {asset.asset_name}
                        </div>
                        <div className="text-[11px] text-slate-500 line-clamp-1 max-w-xs">
                            {asset.specification || 'Belum ada spesifikasi'}
                        </div>
                        <div className="text-[10px] text-slate-400 mt-0.5 font-mono">
                            SN: {asset.serial_number || '-'} • {asset.quantity} {asset.unit}
                        </div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'category_code',
            size: 120,
            minSize: 100,
            meta: { title: 'Kategori' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Kategori" />,
            cell: ({ row }) => (
                <span className="font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                    {row.original.category_code}
                </span>
            ),
        },
        {
            accessorKey: 'department',
            size: 180,
            minSize: 150,
            meta: { title: 'Departemen' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Departemen & PIC" />,
            cell: ({ row }) => {
                const asset = row.original;
                return (
                    <div className="min-w-[140px]">
                        <div className="font-medium text-slate-800 dark:text-slate-200">
                            {asset.department}
                        </div>
                        <div className="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                            <User className="w-3 h-3 text-slate-400 shrink-0" />
                            <span className="truncate">{asset.pic_employee?.name || asset.pic_employee_name || 'Belum ada PIC'}</span>
                        </div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'acquisition_cost',
            size: 160,
            minSize: 140,
            meta: { title: 'Nilai Perolehan' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Nilai Perolehan" />,
            cell: ({ row }) => {
                const asset = row.original;
                return (
                    <div className="min-w-[130px]">
                        <div className="font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                            {formatRupiah(asset.acquisition_cost)}
                        </div>
                        <div className="text-[10px] text-slate-400 whitespace-nowrap">
                            {formatDate(asset.purchase_date)}
                        </div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'status',
            size: 170,
            minSize: 150,
            meta: { title: 'Status' },
            header: ({ column }) => <DataTableColumnHeader column={column} title="Tahap & Status" />,
            cell: ({ row }) => {
                const asset = row.original;
                return (
                    <div className="flex flex-col gap-1 items-start whitespace-nowrap">
                        <span className={`text-[10px] px-2 py-0.5 rounded-full font-bold ${
                            asset.status === 'ACTIVE'
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200'
                                : asset.status === 'MAINTENANCE'
                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-400 border border-blue-200'
                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-400 border border-rose-200'
                        }`}>
                            {asset.status}
                        </span>

                        <span className={`text-[10px] px-2 py-0.5 rounded-md font-medium ${
                            asset.registration_stage === 'COMPLETED'
                                ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/40'
                                : 'text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60'
                        }`}>
                            {asset.registration_stage === 'COMPLETED' ? 'Lengkap (Deep)' : 'Quick (Perlu Lengkapi)'}
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'actions',
            size: 110,
            minSize: 100,
            meta: { pinned: 'right' },
            header: () => <div className="text-right whitespace-nowrap pr-2">Aksi</div>,
            cell: ({ row }) => {
                const asset = row.original;
                return (
                    <div className="flex items-center justify-end gap-1 whitespace-nowrap">
                        <button
                            onClick={() => setSelectedAssetForDrawer(asset)}
                            title="Buka Detail Aset"
                            className="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        >
                            <Eye className="w-4 h-4" />
                        </button>
                        {asset.status !== 'RETIRED' && (
                            <>
                                <button
                                    onClick={() => setSelectedAssetForMutate(asset)}
                                    title="Mutasi Departemen"
                                    className="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                >
                                    <ArrowRightLeft className="w-4 h-4" />
                                </button>
                                <button
                                    onClick={() => setSelectedAssetForRetire(asset)}
                                    title="Pensiunkan Aset (Retire)"
                                    className="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </>
                        )}
                    </div>
                );
            },
        },
    ], [setSelectedAssetForDrawer, setSelectedAssetForMutate, setSelectedAssetForRetire]);


    return (
        <AppLayout title="Inventaris Aset Tetap">
            <Head title="Inventaris Aset Tetap - NIS Purchasing" />

            <div className="space-y-6">
                {/* Header & Hero Title */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                Sheet: Kode Barang
                            </span>
                            <span className="text-xs text-slate-400">• Standar Baku Kodifikasi NIS</span>
                        </div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 mt-1">
                            Inventaris Aset Tetap & Kodifikasi Resmi
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Format Kode: <code className="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">[Kategori].[Dept].[Tahun3Digit].[Urut3Digit]</code> (contoh: <span className="font-mono font-semibold">IT.HCM.026.001</span>).
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => setIsCreateOpen(true)}
                            className="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-all hover:shadow-indigo-500/20 active:scale-95"
                        >
                            <Plus className="w-4 h-4" />
                            Registrasi Aset Baru (Quick)
                        </button>
                    </div>
                </div>

                {/* Hero Stats */}
                <div className="grid grid-cols-2 md:grid-cols-5 gap-3.5">
                    <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
                        <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Aset</span>
                        <div className="text-xl font-bold text-slate-900 dark:text-slate-100">{stats.total_assets || 0} Unit</div>
                        <p className="text-[11px] text-slate-500">Seluruh aset tercatat</p>
                    </div>

                    <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
                        <span className="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">Aset Aktif</span>
                        <div className="text-xl font-bold text-emerald-600 dark:text-emerald-400">{stats.active_assets || 0} Unit</div>
                        <p className="text-[11px] text-slate-500">Digunakan di unit kerja</p>
                    </div>

                    <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
                        <span className="text-[11px] font-semibold text-amber-600 uppercase tracking-wider">Perlu Lengkapi Data</span>
                        <div className="text-xl font-bold text-amber-600 dark:text-amber-400">{stats.quick_registered || 0} Unit</div>
                        <p className="text-[11px] text-slate-500">Stage Quick Registered</p>
                    </div>

                    <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
                        <span className="text-[11px] font-semibold text-rose-600 uppercase tracking-wider">Pensiun (Retired)</span>
                        <div className="text-xl font-bold text-rose-600 dark:text-rose-400">{stats.retired_assets || 0} Unit</div>
                        <p className="text-[11px] text-slate-500">Dilepas / Scrap / Dijual</p>
                    </div>

                    <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 col-span-2 md:col-span-1">
                        <span className="text-[11px] font-semibold text-indigo-600 uppercase tracking-wider">Nilai Perolehan</span>
                        <div className="text-lg font-bold text-indigo-700 dark:text-indigo-400">
                            {formatRupiah(stats.total_acquisition_cost)}
                        </div>
                        <p className="text-[11px] text-slate-500">Akumulasi biaya Capex</p>
                    </div>
                </div>

                {/* TanStack Table v9 - Inventaris Aset Tetap */}
                <DataTable
                    columns={columns}
                    data={assets.data || []}
                    serverPagination={assets}
                    onServerPageChange={(page) => handleFilter({ page })}
                    onServerPerPageChange={(per_page) => handleFilter({ per_page, page: 1 })}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        handleFilter({ search: val, page: 1 });
                    }}
                    searchValue={search}
                    searchPlaceholder="Cari kode aset, nama barang, nomor seri, PIC..."
                    exportFileName="inventaris_aset_nis"
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-2">
                            <select
                                value={categoryFilter}
                                onChange={(e) => {
                                    setCategoryFilter(e.target.value);
                                    handleFilter({ category_code: e.target.value, page: 1 });
                                }}
                                className="h-8 text-xs rounded-lg border-slate-200 dark:border-slate-800 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-1 pl-2.5 pr-7"
                            >
                                <option value="">Semua Kategori</option>
                                {masterOptions.categories?.map((c, idx) => {
                                    const code = typeof c === 'object' ? c.code : c;
                                    const name = typeof c === 'object' ? c.name : c;
                                    const id = typeof c === 'object' ? c.id : idx;
                                    return (
                                        <option key={id} value={code}>
                                            [{code}] {name}
                                        </option>
                                    );
                                })}
                            </select>

                            <select
                                value={deptFilter}
                                onChange={(e) => {
                                    setDeptFilter(e.target.value);
                                    handleFilter({ department: e.target.value, page: 1 });
                                }}
                                className="h-8 text-xs rounded-lg border-slate-200 dark:border-slate-800 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-1 pl-2.5 pr-7"
                            >
                                <option value="">Semua Dept</option>
                                {masterOptions.departments?.map((d, idx) => (
                                    <option key={idx} value={d.name}>{d.name} ({d.code})</option>
                                ))}
                            </select>

                            <select
                                value={statusFilter}
                                onChange={(e) => {
                                    setStatusFilter(e.target.value);
                                    handleFilter({ status: e.target.value, page: 1 });
                                }}
                                className="h-8 text-xs rounded-lg border-slate-200 dark:border-slate-800 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-1 pl-2.5 pr-7"
                            >
                                <option value="">Semua Status</option>
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="MAINTENANCE">MAINTENANCE</option>
                                <option value="RETIRED">RETIRED</option>
                            </select>

                            <select
                                value={stageFilter}
                                onChange={(e) => {
                                    setStageFilter(e.target.value);
                                    handleFilter({ registration_stage: e.target.value, page: 1 });
                                }}
                                className="h-8 text-xs rounded-lg border-slate-200 dark:border-slate-800 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-1 pl-2.5 pr-7"
                            >
                                <option value="">Semua Tahap</option>
                                <option value="QUICK_REGISTERED">Quick Registered</option>
                                <option value="COMPLETED">Completed (Deep)</option>
                            </select>

                            {(categoryFilter || deptFilter || statusFilter || stageFilter || search) && (
                                <button
                                    onClick={handleResetFilter}
                                    title="Reset Filter"
                                    className="h-8 px-2.5 text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-medium rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors flex items-center gap-1 border border-rose-200 dark:border-rose-900"
                                >
                                    <RefreshCw className="w-3.5 h-3.5" />
                                    Reset
                                </button>
                            )}
                        </div>
                    }
                />
            </div>

            {/* Drawers and Modals */}
            <AssetDetailDrawer
                asset={selectedAssetForDrawer}
                isOpen={!!selectedAssetForDrawer}
                onClose={() => setSelectedAssetForDrawer(null)}
                locations={masterOptions.locations}
                employees={masterOptions.employees}
                onOpenMutate={(a) => setSelectedAssetForMutate(a)}
                onOpenRetire={(a) => setSelectedAssetForRetire(a)}
            />

            <CreateAssetModal
                isOpen={isCreateOpen}
                onClose={() => setIsCreateOpen(false)}
                categories={masterOptions.categories}
                departments={masterOptions.departments}
                departmentDivisionMap={masterOptions.department_division_map}
                employees={masterOptions.employees}
                locations={masterOptions.locations}
                units={masterOptions.units}
            />

            <MutateAssetModal
                asset={selectedAssetForMutate}
                isOpen={!!selectedAssetForMutate}
                onClose={() => setSelectedAssetForMutate(null)}
                departments={masterOptions.departments}
                departmentDivisionMap={masterOptions.department_division_map}
                employees={masterOptions.employees}
                locations={masterOptions.locations}
            />

            <RetireAssetModal
                asset={selectedAssetForRetire}
                isOpen={!!selectedAssetForRetire}
                onClose={() => setSelectedAssetForRetire(null)}
                retirementReasons={masterOptions.retirement_reasons}
                finalConditions={masterOptions.final_conditions}
                disposalMethods={masterOptions.disposal_methods}
            />
        </AppLayout>
    );
}
