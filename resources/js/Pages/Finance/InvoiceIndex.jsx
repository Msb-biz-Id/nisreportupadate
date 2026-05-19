import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Search, Receipt, CheckCircle2, Send } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_VARIANT = {
    draft: 'outline', validated: 'info', published: 'success',
    sent: 'info', paid: 'success', overdue: 'destructive', cancel: 'secondary',
};

export default function InvoiceIndex({ invoices, filters, statuses, can }) {
    const [search, setSearch] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');

    function applyFilters(overrides = {}) {
        router.get(route('invoices.index'), {
            q: overrides.q ?? search,
            status: (overrides.status ?? status) === 'all' ? '' : (overrides.status ?? status),
        }, { preserveScroll: true, preserveState: true });
    }

    function publish(invoice) {
        if (!confirm(`Publish invoice ${invoice.invoice_number}? Setelah dipublish, invoice siap dikirim ke customer.`)) return;
        router.post(route('invoices.publish', invoice.id), {}, { preserveScroll: true });
    }

    return (
        <AppLayout title="Invoice Management">
            <Head title="Invoice" />

            <div className="space-y-5">
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2 text-xl font-semibold">
                            <Receipt className="h-5 w-5 text-primary" /> Daftar Invoice
                        </div>
                        <p className="text-sm text-muted-foreground">Invoice dibuat manual dari PO oleh Admin Keuangan jika ada DP atau special order.</p>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input placeholder="Cari no invoice / no PO..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} className="pl-9" />
                            </div>
                            <Select value={status} onValueChange={(v) => { setStatus(v); applyFilters({ status: v }); }}>
                                <SelectTrigger className="sm:w-48"><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    {statuses.map((s) => (<SelectItem key={s} value={s}>{s}</SelectItem>))}
                                </SelectContent>
                            </Select>
                            <Button variant="outline" onClick={() => applyFilters()}>Terapkan</Button>
                        </div>

                        <div className="overflow-hidden rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. Invoice</TableHead>
                                        <TableHead>No. PO</TableHead>
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead>Tgl Terbit</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                        <TableHead className="text-right">Sisa</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoices.data.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-8 text-center text-sm text-muted-foreground">
                                                Belum ada invoice.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {invoices.data.map((iv) => (
                                        <TableRow key={iv.id}>
                                            <TableCell className="font-mono text-xs">{iv.invoice_number}</TableCell>
                                            <TableCell className="font-mono text-xs">{iv.order?.no_po}</TableCell>
                                            <TableCell>{iv.order?.pelanggan?.nama ?? '-'}</TableCell>
                                            <TableCell className="text-xs">{formatDate(iv.tanggal_terbit)}</TableCell>
                                            <TableCell className="text-right font-mono text-xs">{formatRupiah(iv.total_tagihan)}</TableCell>
                                            <TableCell className="text-right font-mono text-xs text-destructive">{formatRupiah(iv.sisa_pembayaran)}</TableCell>
                                            <TableCell><Badge variant={STATUS_VARIANT[iv.status] ?? 'outline'}>{iv.status}</Badge></TableCell>
                                            <TableCell className="text-right">
                                                {can?.publish && ['draft', 'validated'].includes(iv.status) && (
                                                    <Button size="sm" variant="outline" onClick={() => publish(iv)}>
                                                        <Send className="h-3.5 w-3.5" /> Publish
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
