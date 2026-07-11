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

export function POSiapDikirimWidget({ title = "📦 PO Siap Dikirim (Perlu Tindak Lanjut)", items }) {
    return (
        <Card className="border-t-4 border-t-emerald-500 shadow-md">
            <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2">
                    <span className="text-emerald-500 font-bold">📦</span> {title}
                </CardTitle>
                <CardDescription>
                    PO dengan status <b>Siap Dikirim</b>. Harap verifikasi sisa pembayaran dan hubungi customer sebelum pengiriman dilakukan.
                </CardDescription>
            </CardHeader>
            <CardContent className="pt-2">
                {items.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">Tidak ada PO siap dikirim saat ini.</p>
                ) : (
                    <ul className="space-y-3">
                        {items.map((o) => (
                            <li key={o.id} className="group relative">
                                <Link
                                    href={route('orders.show', o.id)}
                                    className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border bg-card/60 p-3.5 transition duration-200 hover:bg-accent hover:border-emerald-300 shadow-sm"
                                >
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-mono text-xs font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                                {o.no_po}
                                            </span>
                                            {o.brand && (
                                                <Badge variant="outline" className="text-[10px] uppercase font-semibold">
                                                    {o.brand}
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="truncate font-semibold text-sm text-slate-900 group-hover:text-emerald-600 transition">
                                            {o.nama_po || 'Tanpa Nama PO'}
                                        </div>
                                        <div className="text-xs text-muted-foreground flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                            <span>Pelanggan: <b>{o.pelanggan || '-'}</b></span>
                                            {o.pelanggan_hp && (
                                                <>
                                                    <span className="hidden sm:inline text-slate-300">•</span>
                                                    <a
                                                        href={`https://wa.me/${o.pelanggan_hp.replace(/\D/g, '')}`}
                                                        onClick={(e) => e.stopPropagation()}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center text-xs text-emerald-600 hover:text-emerald-700 font-semibold hover:underline gap-1 mt-0.5 sm:mt-0"
                                                    >
                                                        <span>📞 WhatsApp: {o.pelanggan_hp}</span>
                                                    </a>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-center border-t sm:border-0 pt-2 sm:pt-0 gap-2 shrink-0">
                                        <div className="text-left sm:text-right">
                                            <div className="text-[10px] text-muted-foreground uppercase font-medium">Tagihan</div>
                                            <div className="font-mono font-bold text-sm text-slate-800">
                                                {formatRupiah(o.total_tagihan)}
                                            </div>
                                        </div>
                                        <div>
                                            {o.is_lunas ? (
                                                <Badge className="bg-emerald-100 hover:bg-emerald-100 text-emerald-800 border-emerald-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full flex items-center gap-1">
                                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                                    Lunas (Aman Dikirim)
                                                </Badge>
                                            ) : (
                                                <div className="flex flex-col items-end gap-0.5">
                                                    <Badge variant="destructive" className="bg-rose-100 hover:bg-rose-100 text-rose-800 border-rose-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full flex items-center gap-1">
                                                        <span className="h-1.5 w-1.5 rounded-full bg-rose-500" />
                                                        Belum Lunas
                                                    </Badge>
                                                    <span className="text-[10px] text-rose-600 font-mono font-semibold">
                                                        Sisa: {formatRupiah(o.sisa_tagihan)}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
