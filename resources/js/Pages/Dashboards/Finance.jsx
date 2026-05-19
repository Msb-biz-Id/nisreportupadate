import { Link } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ArrowUpRight } from 'lucide-react';
import { StatGrid } from '@/Components/Widgets';
import { formatDate, formatRupiah } from '@/lib/utils';

export default function Finance({ stats }) {
    const paymentStatus = stats.payment_status ?? [];

    return (
        <div className="space-y-6">
            <StatGrid cards={stats.cards ?? []} />

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Status Pembayaran PO</CardTitle>
                        <CardDescription>Distribusi PO berdasarkan kondisi pembayaran.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {paymentStatus.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">Belum ada data.</p>
                        ) : (
                            <Chart
                                type="donut"
                                height={280}
                                series={paymentStatus.map((p) => p.value)}
                                options={{
                                    labels: paymentStatus.map((p) => p.label),
                                    colors: ['#10B981', '#F59E0B', '#EF4444'],
                                    legend: { position: 'bottom' },
                                }}
                            />
                        )}
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="text-base">Invoice Pending Validasi</CardTitle>
                            <CardDescription>Invoice draft atau menunggu publish.</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={route('invoices.index') + '?status=draft'}>Lihat semua <ArrowUpRight className="h-4 w-4" /></Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {(stats.invoice_pending_list ?? []).length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">Tidak ada invoice pending.</p>
                        ) : (
                            <ul className="space-y-2">
                                {(stats.invoice_pending_list ?? []).map((iv) => (
                                    <li key={iv.id} className="flex items-center justify-between gap-3 rounded-lg border bg-card/60 p-2.5">
                                        <div className="min-w-0 flex-1">
                                            <div className="font-mono text-[11px] text-muted-foreground">{iv.invoice_number}</div>
                                            <div className="truncate text-sm font-medium">{iv.order?.no_po}</div>
                                            <div className="truncate text-xs text-muted-foreground">{iv.order?.pelanggan?.nama ?? '-'}</div>
                                        </div>
                                        <div className="text-right">
                                            <div className="font-mono text-xs font-semibold">{formatRupiah(iv.total_tagihan)}</div>
                                            <Badge variant="warning" className="mt-1 text-[10px]">{iv.status}</Badge>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle className="text-base">Refund Pending Review</CardTitle>
                        <CardDescription>Pengajuan refund yang menunggu verifikasi.</CardDescription>
                    </div>
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('refunds.index') + '?status=pending_review'}>Lihat semua <ArrowUpRight className="h-4 w-4" /></Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    {(stats.refund_pending_list ?? []).length === 0 ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">Tidak ada refund menunggu review.</p>
                    ) : (
                        <div className="overflow-hidden rounded-lg border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-2 text-left">No. Refund</th>
                                        <th className="px-4 py-2 text-left">No. PO</th>
                                        <th className="px-4 py-2 text-left">Jenis</th>
                                        <th className="px-4 py-2 text-right">Nominal</th>
                                        <th className="px-4 py-2 text-left">Diajukan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(stats.refund_pending_list ?? []).map((r) => (
                                        <tr key={r.id} className="border-t">
                                            <td className="px-4 py-2 font-mono text-xs">{r.refund_number}</td>
                                            <td className="px-4 py-2 font-mono text-xs">{r.order?.no_po}</td>
                                            <td className="px-4 py-2"><Badge variant="outline" className="text-[10px]">{r.jenis_masalah?.replace(/_/g, ' ')}</Badge></td>
                                            <td className="px-4 py-2 text-right font-mono font-semibold">{formatRupiah(r.nominal_refund)}</td>
                                            <td className="px-4 py-2 text-xs">{r.creator?.name}<br /><span className="text-muted-foreground">{formatDate(r.created_at)}</span></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
