import { Head } from '@inertiajs/react';
import { Download, ExternalLink, ShieldCheck } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { formatDate, formatRupiah } from '@/lib/utils';

const STATUS_BADGE = {
    draft: { label: 'Draft', class: 'bg-gray-100 text-gray-700' },
    published: { label: 'Diterbitkan', class: 'bg-blue-100 text-blue-700' },
    sent: { label: 'Terkirim', class: 'bg-cyan-100 text-cyan-700' },
    paid: { label: 'Lunas', class: 'bg-emerald-100 text-emerald-700' },
    overdue: { label: 'Lewat Jatuh Tempo', class: 'bg-red-100 text-red-700' },
};

export default function PublicInvoice({ invoice, qr_code, tracking_url }) {
    const brand = invoice.brand ?? {};
    const status = STATUS_BADGE[invoice.status] ?? { label: invoice.status, class: 'bg-gray-100 text-gray-700' };

    return (
        <>
            <Head title={`Invoice ${invoice.invoice_number}`} />
            <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 px-4 py-6">
                <div className="mx-auto max-w-3xl space-y-4">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2">
                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <ShieldCheck className="h-5 w-5" />
                            </div>
                            <div className="text-sm">
                                <div className="font-bold tracking-tight">NISReport</div>
                                <div className="text-xs text-muted-foreground">Invoice Publik</div>
                            </div>
                        </div>
                        <Button asChild size="sm">
                            <a href={route('invoice.public.pdf', invoice.invoice_number)}><Download className="h-4 w-4" /> Download PDF</a>
                        </Button>
                    </div>

                    <div className="overflow-hidden rounded-2xl border bg-white shadow-sm">
                        <div className="flex flex-col gap-3 border-b p-6 sm:flex-row sm:items-center sm:justify-between" style={{ borderBottomColor: brand.warna_primary || '#3B82F6' }}>
                            <div>
                                <div className="text-2xl font-bold tracking-tight" style={{ color: brand.warna_primary || '#1E40AF' }}>{brand.nama_brand}</div>
                                <div className="text-xs text-muted-foreground">{brand.tagline}</div>
                                <div className="mt-2 text-xs text-muted-foreground">{brand.alamat} · {brand.no_hp}</div>
                            </div>
                            <div className="text-right">
                                <div className="text-3xl font-black tracking-tight" style={{ color: brand.warna_primary || '#1E40AF' }}>INVOICE</div>
                                <div className="font-mono text-sm text-muted-foreground">{invoice.invoice_number}</div>
                                <div className={`mt-1 inline-block rounded-full px-3 py-0.5 text-xs font-semibold ${status.class}`}>{status.label}</div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-3 border-b p-6 sm:grid-cols-2">
                            <div>
                                <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Tagihan kepada</div>
                                <div className="mt-1 font-semibold">{invoice.order?.pelanggan?.nama}</div>
                                <div className="text-xs text-muted-foreground">{invoice.order?.pelanggan?.nomor_hp}</div>
                                <div className="text-xs text-muted-foreground">{invoice.order?.pelanggan?.email}</div>
                            </div>
                            <div>
                                <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Referensi & Tanggal</div>
                                <div className="mt-1 font-mono text-sm">{invoice.order?.no_po}</div>
                                <div className="text-xs">Terbit: <strong>{formatDate(invoice.tanggal_terbit)}</strong></div>
                                {invoice.jatuh_tempo && <div className="text-xs">Jatuh Tempo: <strong>{formatDate(invoice.jatuh_tempo)}</strong></div>}
                            </div>
                        </div>

                        <div className="p-6">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                        <th className="py-2">#</th>
                                        <th className="py-2">Produk</th>
                                        <th className="py-2 text-right">Qty</th>
                                        <th className="py-2 text-right">Harga</th>
                                        <th className="py-2 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(invoice.items ?? []).map((item, i) => (
                                        <tr key={item.id} className="border-b">
                                            <td className="py-2 text-xs">{i + 1}</td>
                                            <td className="py-2">{item.produk}</td>
                                            <td className="py-2 text-right font-mono">{item.jumlah}</td>
                                            <td className="py-2 text-right font-mono text-xs">{formatRupiah(item.harga_satuan)}</td>
                                            <td className="py-2 text-right font-mono text-xs">{formatRupiah(item.subtotal)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="ml-auto mt-4 w-full max-w-xs space-y-1.5 text-sm">
                                <div className="flex justify-between"><span className="text-muted-foreground">Subtotal</span><span className="font-mono">{formatRupiah(invoice.total_tagihan)}</span></div>
                                {invoice.dp_amount > 0 && <div className="flex justify-between"><span className="text-muted-foreground">DP Diterima</span><span className="font-mono text-emerald-600">- {formatRupiah(invoice.dp_amount)}</span></div>}
                                {invoice.biaya_pengiriman > 0 && <div className="flex justify-between"><span className="text-muted-foreground">Ongkir</span><span className="font-mono">{formatRupiah(invoice.biaya_pengiriman)}</span></div>}
                                <div className="flex justify-between border-t pt-2 text-base"><span className="font-bold">Sisa Tagihan</span><span className="font-bold font-mono">{formatRupiah(invoice.sisa_pembayaran)}</span></div>
                            </div>
                        </div>

                        {invoice.bank && (
                            <div className="flex flex-col gap-3 border-t bg-muted/30 p-6 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Transfer ke</div>
                                    <div className="mt-1 font-semibold">{invoice.bank.bank}</div>
                                    <div className="text-sm">{invoice.bank.atas_nama}</div>
                                    <div className="font-mono text-lg">{invoice.bank.nomor_rekening}</div>
                                </div>
                                {qr_code && (
                                    <div className="text-center">
                                        <img src={qr_code} alt="QR Tracking" className="mx-auto h-24 w-24" />
                                        <div className="mt-1 text-[10px] text-muted-foreground">Scan untuk tracking PO</div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="rounded-2xl border bg-muted/40 p-4 text-center">
                        <Button asChild variant="outline" size="sm">
                            <a href={tracking_url} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="h-4 w-4" /> Lacak Pesanan Anda
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
