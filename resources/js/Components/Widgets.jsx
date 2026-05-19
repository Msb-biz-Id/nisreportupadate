import { Link } from '@inertiajs/react';
import * as Icons from 'lucide-react';
import { ArrowUpRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { formatDate, formatRupiah } from '@/lib/utils';

const ACCENT_CLASS = {
    blue: 'bg-blue-50 text-blue-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    violet: 'bg-violet-50 text-violet-600',
    amber: 'bg-amber-50 text-amber-600',
    pink: 'bg-pink-50 text-pink-600',
    cyan: 'bg-cyan-50 text-cyan-600',
    orange: 'bg-orange-50 text-orange-600',
    red: 'bg-red-50 text-red-600',
};

export function StatCard({ label, value, hint, icon, accent = 'blue', currency = false }) {
    const Icon = Icons[icon] ?? Icons.BarChart3;
    const display = currency ? formatRupiah(value) : (value === 0 || value ? value.toLocaleString('id-ID') : '—');

    return (
        <Card className="overflow-hidden transition hover:shadow-md">
            <CardContent className="p-5">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                        <div className="text-sm font-medium text-muted-foreground">{label}</div>
                        <div className="mt-1 text-2xl font-bold tracking-tight">{display}</div>
                        {hint && <div className="mt-1 text-xs text-muted-foreground">{hint}</div>}
                    </div>
                    <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${ACCENT_CLASS[accent] || ACCENT_CLASS.blue}`}>
                        <Icon className="h-5 w-5" />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export function StatGrid({ cards }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {cards.map((c, i) => <StatCard key={i} {...c} />)}
        </div>
    );
}

export function StatusBreakdown({ items }) {
    const totalCount = items.reduce((s, i) => s + i.count, 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Status PO</CardTitle>
                <CardDescription>Distribusi PO berdasarkan status pengerjaan.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-2">
                {items.map((s) => {
                    const pct = totalCount > 0 ? (s.count / totalCount) * 100 : 0;
                    return (
                        <div key={s.key} className="space-y-1">
                            <div className="flex items-center justify-between text-xs">
                                <span className="flex items-center gap-2">
                                    <span className="h-2.5 w-2.5 rounded-full" style={{ background: s.color }} />
                                    {s.label}
                                </span>
                                <span className="font-mono font-semibold">{s.count}</span>
                            </div>
                            <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                                <div className="h-full rounded-full transition-all" style={{ width: `${pct}%`, background: s.color }} />
                            </div>
                        </div>
                    );
                })}
            </CardContent>
        </Card>
    );
}

export function POListWidget({ title, description, items, link, statusVariant, columns = ['no_po', 'pelanggan', 'meta'] }) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <div>
                    <CardTitle className="text-base">{title}</CardTitle>
                    {description && <CardDescription>{description}</CardDescription>}
                </div>
                {link && (
                    <Button asChild variant="ghost" size="sm">
                        <Link href={link.href}>{link.label || 'Lihat'} <ArrowUpRight className="h-4 w-4" /></Link>
                    </Button>
                )}
            </CardHeader>
            <CardContent className="pt-0">
                {items.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">Tidak ada data.</p>
                ) : (
                    <ul className="space-y-2">
                        {items.map((o, i) => (
                            <li key={o.id ?? i}>
                                {o.id ? (
                                    <Link href={route('orders.show', o.id)} className="flex items-center justify-between gap-3 rounded-lg border bg-card/60 p-2.5 transition hover:bg-accent">
                                        <POListRow item={o} columns={columns} statusVariant={statusVariant} />
                                    </Link>
                                ) : (
                                    <div className="flex items-center justify-between gap-3 rounded-lg border bg-card/60 p-2.5">
                                        <POListRow item={o} columns={columns} statusVariant={statusVariant} />
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}

function POListRow({ item, columns, statusVariant }) {
    return (
        <>
            <div className="min-w-0 flex-1">
                <div className="font-mono text-[11px] text-muted-foreground">{item.no_po}</div>
                <div className="truncate font-medium text-sm">{item.nama_po || item.kode}</div>
                <div className="truncate text-xs text-muted-foreground">{item.pelanggan ?? '-'}</div>
            </div>
            <div className="text-right text-xs">
                {item.deadline && <div className="text-muted-foreground">{formatDate(item.deadline)}</div>}
                {item.days_remaining !== undefined && item.days_remaining !== null && (
                    <Badge variant={item.days_remaining < 0 ? 'destructive' : item.days_remaining <= 2 ? 'warning' : 'outline'} className="mt-1 text-[10px]">
                        {item.days_remaining < 0 ? `${Math.abs(item.days_remaining)} hari telat` : `H-${item.days_remaining}`}
                    </Badge>
                )}
                {item.days_late !== undefined && (
                    <Badge variant="destructive" className="mt-1 text-[10px]">{item.days_late} hari telat</Badge>
                )}
                {item.total_tagihan !== undefined && !item.deadline && (
                    <div className="font-mono font-semibold text-sm">{formatRupiah(item.total_tagihan)}</div>
                )}
            </div>
        </>
    );
}

export function TopList({ title, description, items, link, valueLabel = 'Order', valueKey = 'total_order', currencyKey, accent = 'primary' }) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <div>
                    <CardTitle className="text-base">{title}</CardTitle>
                    {description && <CardDescription>{description}</CardDescription>}
                </div>
                {link && (
                    <Button asChild variant="ghost" size="sm">
                        <Link href={link.href}>{link.label || 'Lihat'} <ArrowUpRight className="h-4 w-4" /></Link>
                    </Button>
                )}
            </CardHeader>
            <CardContent className="pt-0">
                {items.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">Belum ada data.</p>
                ) : (
                    <ol className="space-y-2">
                        {items.map((row, i) => (
                            <li key={i} className="flex items-center gap-3 rounded-lg border bg-card/60 p-2">
                                <span className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-xs font-bold ${
                                    i === 0 ? 'bg-amber-100 text-amber-700' :
                                    i === 1 ? 'bg-slate-200 text-slate-700' :
                                    i === 2 ? 'bg-orange-100 text-orange-700' :
                                    'bg-muted text-muted-foreground'
                                }`}>
                                    {i + 1}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <div className="truncate font-medium text-sm">{row.nama || row.label}</div>
                                    {row.kode && <div className="text-xs text-muted-foreground">{row.kode}</div>}
                                    {row.provinsi && <div className="text-xs text-muted-foreground">{row.provinsi}</div>}
                                </div>
                                <div className="text-right">
                                    {currencyKey && row[currencyKey] !== undefined && (
                                        <div className="font-mono text-xs font-semibold">{formatRupiah(row[currencyKey])}</div>
                                    )}
                                    {valueKey && row[valueKey] !== undefined && (
                                        <div className="text-xs text-muted-foreground">{row[valueKey]} {valueLabel}</div>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}
