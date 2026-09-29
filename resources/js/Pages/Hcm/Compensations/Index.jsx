import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { Wallet, Search, Eye, TrendingUp, Users, Banknote } from 'lucide-react';

const rp = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');

export default function CompensationIndex({ compensations, filters = {}, metrics = {}, entities = [], statuses = [] }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [entity, setEntity] = useState(filters.entity || 'all');

    const applyFilter = (s = search, st = status, e = entity) => {
        router.get(route('hcm.compensations.index'), { search: s, status: st, entity: e }, { preserveState: true });
    };

    const rows = compensations?.data || [];

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Wallet className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">Kompensasi &amp; Gaji</span>
                </div>
            }
        >
            <Head title="Kompensasi & Gaji" />

            <div className="p-4 sm:p-6 space-y-5">
                <div>
                    <h1 className="text-lg font-bold text-zinc-900 dark:text-zinc-100">Kompensasi &amp; Gaji</h1>
                    <p className="text-xs text-zinc-500 mt-0.5">Rekap gaji/honor awal vs berjalan, kenaikan, dan status pengupahan karyawan.</p>
                </div>

                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Users className="h-3.5 w-3.5 text-indigo-500" /> Karyawan Terdata
                            </div>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{metrics.total_employees ?? 0}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Banknote className="h-3.5 w-3.5 text-sky-500" /> Total Gaji Berjalan
                            </div>
                            <div className="text-lg font-bold text-zinc-900 dark:text-zinc-100 mt-1">{rp(metrics.total_current)}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <TrendingUp className="h-3.5 w-3.5 text-emerald-500" /> Total Kenaikan
                            </div>
                            <div className="text-lg font-bold text-emerald-600 mt-1">{rp(metrics.total_increment)}</div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Wallet className="h-3.5 w-3.5 text-amber-500" /> Rata-rata Gaji
                            </div>
                            <div className="text-lg font-bold text-zinc-900 dark:text-zinc-100 mt-1">{rp(metrics.avg_current)}</div>
                        </CardContent>
                    </Card>
                </div>

                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                    <CardContent className="p-4 space-y-3">
                        <form onSubmit={(e) => { e.preventDefault(); applyFilter(); }} className="flex flex-wrap items-end gap-2">
                            <div className="flex-1 min-w-[200px]">
                                <div className="text-[11px] font-medium text-zinc-500 mb-1">Cari karyawan / no. kontrak</div>
                                <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nama / kode karyawan / no. kontrak" className="h-9 text-xs" />
                            </div>
                            <div className="w-48">
                                <div className="text-[11px] font-medium text-zinc-500 mb-1">Status Pengupahan</div>
                                <SearchableSelect
                                    value={status}
                                    onValueChange={(v) => { setStatus(v); applyFilter(search, v, entity); }}
                                    options={[{ value: 'all', label: 'Semua Status' }, ...statuses.map((s) => ({ value: s, label: s }))]}
                                    className="text-xs"
                                />
                            </div>
                            <div className="w-48">
                                <div className="text-[11px] font-medium text-zinc-500 mb-1">Entitas</div>
                                <SearchableSelect
                                    value={entity}
                                    onValueChange={(v) => { setEntity(v); applyFilter(search, status, v); }}
                                    options={[{ value: 'all', label: 'Semua Entitas' }, ...entities.map((s) => ({ value: s, label: s }))]}
                                    className="text-xs"
                                />
                            </div>
                            <Button type="submit" size="sm" className="h-9 gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white">
                                <Search className="h-3.5 w-3.5" /> Cari
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-zinc-50/75 dark:bg-zinc-800/50">
                                        <TableHead className="text-xs">Karyawan</TableHead>
                                        <TableHead className="text-xs">Entitas</TableHead>
                                        <TableHead className="text-right text-xs">Gaji Awal</TableHead>
                                        <TableHead className="text-right text-xs">Gaji Berjalan</TableHead>
                                        <TableHead className="text-right text-xs">Kenaikan</TableHead>
                                        <TableHead className="text-center text-xs">Status</TableHead>
                                        <TableHead className="text-right text-xs">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length > 0 ? (
                                        rows.map((c) => {
                                            const inc = Number(c.current_salary || 0) - Number(c.initial_salary || 0);
                                            return (
                                                <TableRow key={c.id} className="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                                    <TableCell>
                                                        <div className="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{c.employee?.name || '-'}</div>
                                                        <div className="text-[10px] text-zinc-400 font-mono">{c.employee?.employee_code || '-'} • {c.employee?.position || '-'} / {c.employee?.department || '-'}</div>
                                                    </TableCell>
                                                    <TableCell className="text-xs text-zinc-500">{c.legal_entity || '-'}</TableCell>
                                                    <TableCell className="text-right text-xs">{rp(c.initial_salary)}</TableCell>
                                                    <TableCell className="text-right text-xs font-semibold">{rp(c.current_salary)}</TableCell>
                                                    <TableCell className={`text-right text-xs font-semibold ${inc >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                                                        {inc >= 0 ? '+' : '-'}{rp(Math.abs(inc))}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Badge variant="outline" className="text-[10px]">{c.salary_status || '-'}</Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {c.employee?.id && (
                                                            <Link href={route('hcm.employees.show', c.employee.id)}>
                                                                <Button variant="outline" size="sm" className="h-6 text-[10px] px-2 gap-1">
                                                                    <Eye className="h-3 w-3" /> Profil
                                                                </Button>
                                                            </Link>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center text-xs text-zinc-400 py-10">
                                                Belum ada data kompensasi.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
