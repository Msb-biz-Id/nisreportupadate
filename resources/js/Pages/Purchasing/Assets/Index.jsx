import React, { useState } from 'react';
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
    ChevronLeft, 
    ChevronRight,
    RefreshCw
} from 'lucide-react';
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

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(val || 0);
    };

    return (
        <AppLayout title="Inventaris Aset Tetap">
            <Head title="Inventaris Aset Tetap - NIS Purchasing" />

            <div className="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
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

                {/* Filter and Search Bar */}
                <div className="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div className="md:col-span-4 relative">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Cari nama barang, kode aset, no seri, atau PIC..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilter({ search })}
                                className="w-full pl-9 text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500"
                            />
                        </div>

                        <div className="md:col-span-2">
                            <select
                                value={categoryFilter}
                                onChange={(e) => {
                                    setCategoryFilter(e.target.value);
                                    handleFilter({ category_code: e.target.value });
                                }}
                                className="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500"
                            >
                                <option value="">Semua Kategori</option>
                                {masterOptions.categories?.map((c) => (
                                    <option key={c.id} value={c.code}>[{c.code}] {c.name}</option>
                                ))}
                            </select>
                        </div>

                        <div className="md:col-span-2">
                            <select
                                value={deptFilter}
                                onChange={(e) => {
                                    setDeptFilter(e.target.value);
                                    handleFilter({ department: e.target.value });
                                }}
                                className="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500"
                            >
                                <option value="">Semua Departemen</option>
                                {masterOptions.departments?.map((d, idx) => (
                                    <option key={idx} value={d.name}>{d.name} ({d.code})</option>
                                ))}
                            </select>
                        </div>

                        <div className="md:col-span-2">
                            <select
                                value={statusFilter}
                                onChange={(e) => {
                                    setStatusFilter(e.target.value);
                                    handleFilter({ status: e.target.value });
                                }}
                                className="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500"
                            >
                                <option value="">Semua Status</option>
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="MAINTENANCE">MAINTENANCE</option>
                                <option value="RETIRED">RETIRED</option>
                            </select>
                        </div>

                        <div className="md:col-span-2 flex items-center gap-2">
                            <select
                                value={stageFilter}
                                onChange={(e) => {
                                    setStageFilter(e.target.value);
                                    handleFilter({ registration_stage: e.target.value });
                                }}
                                className="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 focus:ring-indigo-500"
                            >
                                <option value="">Semua Tahap</option>
                                <option value="QUICK_REGISTERED">Quick Registered</option>
                                <option value="COMPLETED">Completed (Deep)</option>
                            </select>

                            <button
                                onClick={handleResetFilter}
                                title="Reset Filter"
                                className="p-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            >
                                <RefreshCw className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                {/* Table Inventaris */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                    <th className="py-3.5 px-4">Kode Aset</th>
                                    <th className="py-3.5 px-4">Nama Barang & Detail</th>
                                    <th className="py-3.5 px-4">Kategori</th>
                                    <th className="py-3.5 px-4">Departemen & PIC</th>
                                    <th className="py-3.5 px-4">Nilai Perolehan</th>
                                    <th className="py-3.5 px-4">Tahap & Status</th>
                                    <th className="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                                {assets.data?.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="text-center py-12 text-slate-400">
                                            <Box className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                            Tidak ada aset yang sesuai dengan kriteria pencarian.
                                        </td>
                                    </tr>
                                ) : (
                                    assets.data?.map((asset) => (
                                        <tr key={asset.id} className="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                            {/* Clickable Asset Code */}
                                            <td className="py-3.5 px-4">
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
                                            </td>

                                            {/* Nama Barang & Spesifikasi */}
                                            <td className="py-3.5 px-4">
                                                <div className="font-semibold text-slate-900 dark:text-slate-100">
                                                    {asset.asset_name}
                                                </div>
                                                <div className="text-[11px] text-slate-500 line-clamp-1 max-w-xs">
                                                    {asset.specification || 'Belum ada spesifikasi'}
                                                </div>
                                                <div className="text-[10px] text-slate-400 mt-0.5 font-mono">
                                                    SN: {asset.serial_number || '-'} • {asset.quantity} {asset.unit}
                                                </div>
                                            </td>

                                            {/* Kategori */}
                                            <td className="py-3.5 px-4">
                                                <span className="font-semibold text-slate-700 dark:text-slate-300">
                                                    {asset.category_code}
                                                </span>
                                            </td>

                                            {/* Departemen & PIC */}
                                            <td className="py-3.5 px-4">
                                                <div className="font-medium text-slate-800 dark:text-slate-200">
                                                    {asset.department}
                                                </div>
                                                <div className="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                                    <User className="w-3 h-3 text-slate-400" />
                                                    {asset.pic_employee?.name || asset.pic_employee_name || 'Belum ada PIC'}
                                                </div>
                                            </td>

                                            {/* Nilai Perolehan */}
                                            <td className="py-3.5 px-4">
                                                <div className="font-semibold text-slate-800 dark:text-slate-200">
                                                    {formatRupiah(asset.acquisition_cost)}
                                                </div>
                                                <div className="text-[10px] text-slate-400">
                                                    {asset.purchase_date}
                                                </div>
                                            </td>

                                            {/* Tahap & Status */}
                                            <td className="py-3.5 px-4">
                                                <div className="flex flex-col gap-1 items-start">
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
                                            </td>

                                            {/* Aksi */}
                                            <td className="py-3.5 px-4 text-right">
                                                <div className="flex items-center justify-end gap-1">
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
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {assets.links?.length > 3 && (
                        <div className="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                            <div>
                                Menampilkan <span className="font-semibold text-slate-700 dark:text-slate-300">{assets.from || 0}</span> s.d. <span className="font-semibold text-slate-700 dark:text-slate-300">{assets.to || 0}</span> dari total <span className="font-semibold text-slate-700 dark:text-slate-300">{assets.total || 0}</span> aset
                            </div>
                            <div className="flex gap-1">
                                {assets.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        disabled={!link.url}
                                        onClick={() => router.get(link.url, {}, { preserveState: true })}
                                        className={`px-3 py-1.5 rounded-lg font-medium transition-colors ${
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 disabled:opacity-30'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
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
                employees={masterOptions.employees}
                locations={masterOptions.locations}
                units={masterOptions.units}
            />

            <MutateAssetModal
                asset={selectedAssetForMutate}
                isOpen={!!selectedAssetForMutate}
                onClose={() => setSelectedAssetForMutate(null)}
                departments={masterOptions.departments}
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
