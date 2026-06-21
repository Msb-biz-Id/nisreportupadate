import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Search, Shield, User as UserIcon, Copy, Check } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { formatDateTime } from '@/lib/utils';

const ACTIVITY_VARIANT = {
    create: 'success', update: 'info', delete: 'destructive',
    publish: 'success', login: 'outline', logout: 'secondary',
    export: 'info', test_connection: 'outline',
    unlock: 'warning', relock: 'secondary',
};

const MODULE_LABEL = {
    auth: 'Auth', brand: 'Brand', user: 'User', master: 'Master',
    order: 'Order', production: 'Produksi', refund: 'Refund',
    invoice: 'Invoice', finance: 'Keuangan', ai: 'AI', settings: 'Pengaturan',
};

export default function AuditIndex({ logs, filters, modules, activities }) {
    const [search, setSearch] = useState(filters?.q ?? '');
    const [module, setModule] = useState(filters?.module ?? 'all');
    const [activity, setActivity] = useState(filters?.activity ?? 'all');
    const [from, setFrom] = useState(filters?.from ?? '');
    const [to, setTo] = useState(filters?.to ?? '');

    const [copied, setCopied] = useState(false);
    const handleCopyExcel = () => {
        const headers = ['Waktu', 'User', 'Email', 'Brand', 'Modul', 'Aktivitas', 'Deskripsi', 'IP Address', 'User Agent'];
        const rows = [headers.join('\t')];
        
        logs.data.forEach((l) => {
            const row = [
                formatDateTime(l.created_at),
                l.user?.name ?? 'System',
                l.user?.email ?? '—',
                l.brand?.kode ?? '—',
                MODULE_LABEL[l.module] ?? l.module,
                l.activity,
                l.description ?? '—',
                l.ip_address ?? '—',
                l.user_agent ?? '—'
            ];
            rows.push(row.join('\t'));
        });
        
        const textToCopy = rows.join('\n');
        navigator.clipboard.writeText(textToCopy)
            .then(() => {
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            })
            .catch(err => {
                console.error('Failed to copy audit log data:', err);
            });
    };

    function apply(overrides = {}) {
        router.get(route('audit.index'), {
            q: overrides.q ?? search,
            module: (overrides.module ?? module) === 'all' ? '' : (overrides.module ?? module),
            activity: (overrides.activity ?? activity) === 'all' ? '' : (overrides.activity ?? activity),
            from: overrides.from ?? from,
            to: overrides.to ?? to,
        }, { preserveScroll: true, preserveState: true });
    }

    return (
        <AppLayout title="Audit Log">
            <Head title="Audit Log" />

            <div className="space-y-5">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Shield className="h-4 w-4 text-primary" /> Audit Log
                        </CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Riwayat aktivitas user di sistem (login, perubahan data, ekspor, dll).
                            Total <strong>{logs.total}</strong> entri.
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-6">
                            <div className="relative lg:col-span-2">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari di deskripsi..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && apply()}
                                    className="pl-9"
                                />
                            </div>
                            <Select value={module} onValueChange={(v) => { setModule(v); apply({ module: v }); }}>
                                <SelectTrigger><SelectValue placeholder="Modul" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Modul</SelectItem>
                                    {modules.map((m) => (<SelectItem key={m} value={m}>{MODULE_LABEL[m] ?? m}</SelectItem>))}
                                </SelectContent>
                            </Select>
                            <Select value={activity} onValueChange={(v) => { setActivity(v); apply({ activity: v }); }}>
                                <SelectTrigger><SelectValue placeholder="Aktivitas" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Aktivitas</SelectItem>
                                    {activities.map((a) => (<SelectItem key={a} value={a}>{a}</SelectItem>))}
                                </SelectContent>
                            </Select>
                            <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} title="Dari" />
                            <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} title="Sampai" />
                        </div>

                        <div className="mb-3 flex justify-between items-center">
                            <span className="text-xs text-muted-foreground italic">
                                *Menyalin data log pada halaman aktif ini
                            </span>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleCopyExcel}
                                    className="flex items-center gap-1.5"
                                >
                                    {copied ? (
                                        <>
                                            <Check className="h-4 w-4 text-emerald-600 animate-in fade-in zoom-in duration-200" />
                                            Tersalin!
                                        </>
                                    ) : (
                                        <>
                                            <Copy className="h-4 w-4 text-slate-500" />
                                            Salin untuk Excel
                                        </>
                                    )}
                                </Button>
                                <Button size="sm" onClick={() => apply()}>Terapkan</Button>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Modul</TableHead>
                                        <TableHead>Aktivitas</TableHead>
                                        <TableHead>Deskripsi</TableHead>
                                        <TableHead>IP</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {logs.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={6} className="py-8 text-center text-sm text-muted-foreground">
                                                Tidak ada log untuk filter ini.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {logs.data.map((l) => (
                                        <TableRow key={l.id}>
                                            <TableCell className="font-mono text-xs">{formatDateTime(l.created_at)}</TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2 text-sm">
                                                    <UserIcon className="h-3 w-3 text-muted-foreground" />
                                                    <span>{l.user?.name ?? <span className="text-muted-foreground">System</span>}</span>
                                                </div>
                                                {l.brand && <div className="text-[10px] text-muted-foreground">{l.brand.kode}</div>}
                                            </TableCell>
                                            <TableCell><Badge variant="outline" className="text-[10px]">{MODULE_LABEL[l.module] ?? l.module}</Badge></TableCell>
                                            <TableCell><Badge variant={ACTIVITY_VARIANT[l.activity] ?? 'outline'}>{l.activity}</Badge></TableCell>
                                            <TableCell className="max-w-xs truncate text-sm">{l.description ?? '-'}</TableCell>
                                            <TableCell className="font-mono text-[10px] text-muted-foreground">{l.ip_address ?? '-'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {logs.last_page > 1 && (
                            <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span className="text-muted-foreground">
                                    Menampilkan {logs.from ?? 0}–{logs.to ?? 0} dari {logs.total}
                                </span>
                                <div className="flex gap-1">
                                    {logs.links.map((link, i) => (
                                        <Button key={i} variant={link.active ? 'default' : 'outline'} size="sm" disabled={!link.url}
                                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                                            dangerouslySetInnerHTML={{ __html: link.label }} />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
