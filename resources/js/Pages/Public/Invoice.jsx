import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Download, ExternalLink, ShieldCheck, CheckCircle2, AlertCircle, ArrowDownLeft, ArrowUpRight, HelpCircle, Globe, Printer, FileText } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { formatDate, formatRupiah, renderFormattedText } from '@/lib/utils';
import usePublicSecurity from '@/hooks/usePublicSecurity';

const STATUS_BADGE = {
    draft: { label: 'Draft', class: 'bg-slate-100 text-slate-700' },
    published: { label: 'Diterbitkan', class: 'bg-blue-100 text-blue-700 border-blue-200' },
    sent: { label: 'Terkirim', class: 'bg-cyan-100 text-cyan-700 border-cyan-200' },
    paid: { label: 'Lunas', class: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    overdue: { label: 'Lewat Jatuh Tempo', class: 'bg-red-100 text-red-700 border-red-200' },
};

const maskPhone = (phone) => {
    if (!phone) return '—';
    const clean = phone.trim();
    if (clean.length < 8) return clean.replace(/./g, '*');
    return clean.slice(0, 4) + '*'.repeat(clean.length - 8) + clean.slice(-4);
};

const maskEmail = (email) => {
    if (!email) return '—';
    const parts = email.split('@');
    if (parts.length !== 2) return email;
    const [local, domain] = parts;
    if (local.length <= 2) return local[0] + '***@' + domain;
    return local.slice(0, 2) + '*'.repeat(local.length - 4) + local.slice(-2) + '@' + domain;
};

const maskDetailAlamat = (address) => {
    if (!address) return '';
    const clean = address.trim();
    if (clean.length <= 8) return '*'.repeat(clean.length);
    return clean.slice(0, 5) + '*'.repeat(clean.length - 8) + clean.slice(-3);
};

export default function PublicInvoice({ invoice, qr_code, tracking_url }) {
    usePublicSecurity();
    const brand = invoice.brand ?? {};
    const status = STATUS_BADGE[invoice.status] ?? { label: invoice.status, class: 'bg-slate-100 text-slate-700' };

    // Get verified payments / ledger history
    const payments = (invoice.order?.payments ?? [])
        .filter(p => p.verified_at !== null)
        .sort((a, b) => new Date(a.payment_date) - new Date(b.payment_date));

    // Helper definitions for deduction and penambahan payments
    const DEDUCTION_TYPES = ['cashback', 'return', 'refund'];
    const DEDUCTION_NAMES = ['Cashback', 'Refund', 'Return', 'Refurn'];

    const isDeductionPayment = (p) => {
        if (p.is_debit !== null && p.is_debit !== undefined) return !Boolean(p.is_debit);
        return DEDUCTION_TYPES.includes(p.payment_type) ||
            DEDUCTION_NAMES.includes(p.master_jenis_pembayaran?.nama);
    };

    const isPenambahanPayment = (p) => {
        if (p.payment_type === 'ongkir') return false;
        if (p.master_jenis_pembayaran?.efek_tagihan === 'penambahan') return true;
        if (p.master_jenis_pembayaran_id === null && p.payment_type === 'tambahan_produk') return true;
        return false;
    };

    // Dynamic totals calculation matches the Order model
    const returnSum = payments
        .filter(p =>
            p.payment_type === 'return' ||
            p.payment_type === 'refund' ||
            ['Refund', 'Return', 'Refurn'].includes(p.master_jenis_pembayaran?.nama)
        )
        .reduce((s, p) => s + (Number(p.amount) || 0), 0);

    const cashbackSum = payments
        .filter(p => p.payment_type === 'cashback' || p.master_jenis_pembayaran?.nama === 'Cashback')
        .reduce((s, p) => s + (Number(p.amount) || 0), 0);

    const additionPayments = payments.filter(p => isPenambahanPayment(p));
    const additionSum = additionPayments.reduce((s, p) => s + (Number(p.amount) || 0), 0);

    const totalReceived = payments
        .filter(p => !isDeductionPayment(p))
        .reduce((s, p) => s + (Number(p.amount) || 0), 0);

    const totalRefunded = returnSum + cashbackSum;
    const totalPaidNet = Math.max(0, totalReceived - totalRefunded);

    const mainItems = (invoice.items ?? []).filter(item => !item.is_addon);
    const addonItems = (invoice.items ?? []).filter(item => item.is_addon);
    const mainSubtotalGross = mainItems.reduce((s, x) => s + (Number(x.jumlah) * Number(x.harga_satuan)), 0);
    const addonSubtotalGross = addonItems.reduce((s, x) => s + (Number(x.jumlah) * Number(x.harga_satuan)), 0);
    const grossSubtotal = mainSubtotalGross + addonSubtotalGross;

    const itemDiskonSum = (invoice.items ?? []).reduce((s, x) => s + Number(x.discount_amount || 0), 0);
    const diskonValue = Number(invoice.diskon_value || 0);
    const diskonNominal = itemDiskonSum > 0
        ? itemDiskonSum
        : (invoice.diskon_type === 'persen'
            ? (grossSubtotal * diskonValue / 100)
            : diskonValue);

    const grossInvoiceTotal = Number(invoice.total_tagihan) || 0;

    const renderThermalReceipt = () => {
        return (
            <div className="w-full max-w-full bg-white text-black font-mono text-xs leading-relaxed select-none">
                {/* Header */}
                <div className="text-center space-y-1 mb-4">
                    {brand.logo ? (
                        <img
                            src={`/storage/${brand.logo}`}
                            alt={brand.nama_brand}
                            className="h-16 w-16 mx-auto object-contain mb-1 filter grayscale"
                        />
                    ) : (
                        <div className="h-12 w-12 mx-auto rounded-full bg-black text-white flex items-center justify-center font-bold text-xl mb-1">
                            {brand.kode || 'B'}
                        </div>
                    )}
                    <h2 className="text-sm font-black uppercase tracking-widest text-black">{brand.nama_brand}</h2>
                    {brand.tagline && <div className="text-[9px] font-bold leading-none tracking-wide text-black">{brand.tagline}</div>}
                    <div className="text-[9px] leading-tight max-w-[280px] mx-auto text-black mt-1">
                        {brand.alamat && <div className="text-black">{brand.alamat}</div>}
                        <div className="flex justify-center gap-2 flex-wrap mt-0.5 font-bold text-black">
                            {brand.no_hp && <div>WA: {brand.no_hp}</div>}
                            {brand.email && <div>Email: {brand.email}</div>}
                        </div>
                    </div>
                </div>

                {/* Status Stamp */}
                {status.label && (
                    <div className="my-3 flex justify-center">
                        <div className="border-2 border-black border-dashed px-3 py-1 inline-block text-[10px] font-black uppercase tracking-widest rotate-[-3deg] text-black">
                            * {status.label} *
                        </div>
                    </div>
                )}

                {/* Double Border POS Divider */}
                <div className="flex flex-col gap-[2px] my-3">
                    <div className="border-t border-black"></div>
                    <div className="border-t border-black"></div>
                </div>

                {/* Invoice Info */}
                <div className="space-y-2 text-xs text-black">
                    <div className="border-b border-dashed border-black pb-1.5">
                        <div className="text-[9px] font-bold uppercase tracking-wider text-black">No. Invoice:</div>
                        <div className="font-extrabold text-[11px] text-black break-all leading-tight">{invoice.invoice_number}</div>
                    </div>
                    {invoice.order?.no_po && (
                        <div className="border-b border-dashed border-black pb-1.5">
                            <div className="text-[9px] font-bold uppercase tracking-wider text-black">No. PO:</div>
                            <div className="font-extrabold text-[11px] text-black break-all leading-tight">{invoice.order.no_po}</div>
                        </div>
                    )}
                    <div className="grid grid-cols-2 gap-2 text-[10px]">
                        <div>
                            <div className="font-bold uppercase tracking-wider text-black">Tanggal:</div>
                            <div className="font-semibold text-black">{formatDate(invoice.tanggal_terbit)}</div>
                        </div>
                        <div>
                            <div className="font-bold uppercase tracking-wider text-black">Pelanggan:</div>
                            <div className="font-semibold text-black truncate">{invoice.order?.pelanggan?.nama || '—'}</div>
                        </div>
                    </div>
                    {invoice.order?.pelanggan?.nomor_hp && (
                        <div className="text-[10px] flex justify-between border-t border-dotted border-black pt-1">
                            <span className="font-bold uppercase tracking-wider text-black">No. HP:</span>
                            <span className="font-semibold text-black">{maskPhone(invoice.order.pelanggan.nomor_hp)}</span>
                        </div>
                    )}
                </div>

                {/* Dashed Line */}
                <div className="border-t border-dashed border-black my-3"></div>

                {/* Items */}
                <div className="space-y-3 text-xs">
                    <div className="font-bold text-center tracking-widest uppercase text-[10px] bg-black text-white py-0.5">[ RINCIAN BELANJA ]</div>
                    {(() => {
                        const mainItems = (invoice.items ?? []).filter(item => !item.is_addon);
                        const addonItems = (invoice.items ?? []).filter(item => item.is_addon);
                        
                        return (
                            <div className="space-y-3 text-black">
                                {mainItems.length > 0 && (
                                    <div className="space-y-2">
                                        <div className="font-bold text-[9px] tracking-wider uppercase text-black border-b border-black pb-0.5">PRODUK UTAMA</div>
                                        {mainItems.map((item, idx) => (
                                            <div key={item.id} className="space-y-0.5">
                                                <div className="font-bold text-black uppercase">{item.produk}</div>
                                                <div className="flex justify-between pl-1 text-black">
                                                    <span>{item.jumlah}x {formatRupiah(item.harga_satuan)}</span>
                                                    <span className="font-bold">{formatRupiah(item.subtotal)}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {addonItems.length > 0 && (
                                    <div className="space-y-2 pt-1">
                                        <div className="font-bold text-[9px] tracking-wider uppercase text-black border-b border-black pb-0.5">TAMBAHAN / ADD-ON</div>
                                        {addonItems.map((item, idx) => (
                                            <div key={item.id} className="space-y-0.5">
                                                <div className="font-bold text-black uppercase">{item.produk}</div>
                                                <div className="flex justify-between pl-1 text-black">
                                                    <span>{item.jumlah}x {formatRupiah(item.harga_satuan)}</span>
                                                    <span className="font-bold">{formatRupiah(item.subtotal)}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    })()}
                </div>

                {/* Double Border POS Divider */}
                <div className="flex flex-col gap-[2px] my-3">
                    <div className="border-t border-black"></div>
                    <div className="border-t border-black"></div>
                </div>

                {/* Calculations */}
                <div className="space-y-1.5 text-xs text-black">
                    <div className="flex justify-between text-black">
                        <span>Total Harga</span>
                        <span>{formatRupiah(grossSubtotal)}</span>
                    </div>
                    {diskonNominal > 0 && (
                        <div className="flex justify-between text-black">
                            <span>Diskon</span>
                            <span>- {formatRupiah(diskonNominal)}</span>
                        </div>
                    )}
                    {invoice.order?.tipe_pengiriman === 'pickup_cod' ? (
                        <div className="flex justify-between text-black">
                            <span>Pengiriman</span>
                            <span className="font-bold">Ambil di Tempat / COD</span>
                        </div>
                    ) : (invoice.order?.is_free_ongkir || invoice.order?.tipe_pengiriman === 'free_ongkir') ? (
                        <div className="flex justify-between text-black">
                            <span>Ongkir {invoice.jasa_pengiriman ? `(${invoice.jasa_pengiriman})` : ''}</span>
                            <span className="font-bold">GRATIS</span>
                        </div>
                    ) : (
                        Number(invoice.biaya_pengiriman || 0) > 0 && (
                            <div className="flex justify-between text-black">
                                <span>Ongkir {invoice.jasa_pengiriman ? `(${invoice.jasa_pengiriman})` : ''}</span>
                                <span>+ {formatRupiah(Number(invoice.biaya_pengiriman))}</span>
                            </div>
                        )
                    )}
                    {additionSum > 0 && (
                        <div className="flex justify-between text-black">
                            <span>Tambahan Produk</span>
                            <span>+ {formatRupiah(additionSum)}</span>
                        </div>
                    )}
                    <div className="flex justify-between font-bold border-y border-dashed border-black py-1.5 my-2 text-black">
                        <span>TOTAL AKHIR</span>
                        <span className="font-black text-sm">{formatRupiah(grossInvoiceTotal)}</span>
                    </div>
                    {totalReceived > 0 && (
                        <div className="flex justify-between text-black">
                            <span>Total Bayar</span>
                            <span>{formatRupiah(totalReceived)}</span>
                        </div>
                    )}
                    {returnSum > 0 && (
                        <div className="flex justify-between text-black">
                            <span>Refund</span>
                            <span>- {formatRupiah(returnSum)}</span>
                        </div>
                    )}
                    {cashbackSum > 0 && (
                        <div className="flex justify-between text-black">
                            <span>Cashback</span>
                            <span>- {formatRupiah(cashbackSum)}</span>
                        </div>
                    )}
                    {(returnSum > 0 || cashbackSum > 0) && (
                        <div className="flex justify-between font-bold border-t border-dashed border-black pt-1 text-black">
                            <span>Neto Pembayaran</span>
                            <span>{formatRupiah(totalReceived - returnSum - cashbackSum)}</span>
                        </div>
                    )}
                    {(() => {
                        const netPayment = totalReceived - returnSum - cashbackSum;
                        const calculatedSisa = Math.max(0, grossInvoiceTotal - netPayment);
                        return (
                            <div className="flex justify-between font-black border-y-2 border-black py-1.5 text-sm my-2 text-black">
                                <span>SISA TAGIHAN</span>
                                <span>{formatRupiah(calculatedSisa)}</span>
                            </div>
                        );
                    })()}
                </div>

                {/* Dashed Line */}
                <div className="border-t border-dashed border-black my-3"></div>

                {/* Bank / Payment Info */}
                {invoice.bank && (
                    <div className="text-[10px] text-center space-y-1 py-2 border border-black border-dashed rounded">
                        {invoice.bank.bank === 'CASH' ? (
                            <div className="font-bold tracking-wider text-black">PEMBAYARAN: TUNAI (LUNAS)</div>
                        ) : (
                            <div className="space-y-0.5 text-black">
                                <div className="font-bold tracking-wider text-[9px] text-black">REKENING TRANSFER RESMI</div>
                                <div className="font-black text-black text-[11px]">{invoice.bank.bank} - {invoice.bank.nomor_rekening}</div>
                                <div className="font-medium text-black">A.N: {invoice.bank.atas_nama}</div>
                            </div>
                        )}
                    </div>
                )}

                {/* Imbauan Keamanan Pembayaran */}
                <div className="text-[8px] text-center leading-relaxed space-y-1 my-3 border border-black p-2">
                    <div className="font-bold text-[9px] tracking-widest text-black">⚠️ PERINGATAN KEAMANAN ⚠️</div>
                    {invoice.bank && invoice.bank.bank === 'CASH' ? (
                        <p className="text-justify leading-tight text-black">
                            Demi keamanan transaksi, lakukan pembayaran tunai secara langsung hanya melalui kasir/sales resmi brand kami. Jangan melakukan transfer ke rekening perorangan yang tidak terdaftar secara resmi.
                        </p>
                    ) : (
                        <p className="text-justify leading-tight text-black">
                            Demi keamanan, mohon TIDAK MELAKUKAN transfer ke rekening mana pun selain rekening resmi atas nama {invoice.bank ? invoice.bank.atas_nama : brand.nama_brand}. Jangan pernah mengirimkan dana ke rekening perorangan/sales di luar informasi resmi tertera.
                        </p>
                    )}
                </div>

                {/* Barcode Accent */}
                <div className="flex justify-center items-center gap-[1px] h-6 my-4 overflow-hidden select-none">
                    <div className="bg-black w-[2px] h-full"></div>
                    <div className="bg-black w-[1px] h-full"></div>
                    <div className="bg-black w-[3px] h-full"></div>
                    <div className="bg-black w-[1px] h-full"></div>
                    <div className="bg-black w-[4px] h-full"></div>
                    <div className="bg-black w-[2px] h-full"></div>
                    <div className="bg-black w-[1px] h-full"></div>
                    <div className="bg-black w-[3px] h-full"></div>
                    <div className="bg-black w-[1px] h-full"></div>
                    <div className="bg-black w-[2px] h-full"></div>
                    <div className="bg-black w-[4px] h-full"></div>
                    <div className="bg-black w-[1px] h-full"></div>
                    <div className="bg-black w-[3px] h-full"></div>
                    <div className="bg-black w-[2px] h-full"></div>
                    <div className="bg-black w-[1px] h-full"></div>
                    <div className="bg-black w-[3px] h-full"></div>
                    <div className="bg-black w-[1px] h-full"></div>
                    <div className="bg-black w-[4px] h-full"></div>
                    <div className="bg-black w-[2px] h-full"></div>
                    <div className="bg-black w-[1px] h-full"></div>
                    <div className="bg-black w-[3px] h-full"></div>
                </div>

                {/* Footer and QR Code */}
                <div className="text-center space-y-3">
                    {qr_code && (
                        <div className="bg-white p-1 inline-block border border-black mx-auto">
                            <img src={qr_code} alt="QR Tracking" className="h-24 w-24 filter grayscale" />
                        </div>
                    )}
                    <div className="text-[9px] leading-tight space-y-1 text-black">
                        <div className="font-bold text-[10px] tracking-wide text-black">* TERIMA KASIH ATAS KERJASAMANYA *</div>
                        <div className="text-black">Scan QR untuk Lacak Status Produksi</div>
                        {invoice.peraturan && <div className="text-[8px] text-black mt-2 max-w-[280px] mx-auto text-justify border-t border-dashed border-black pt-1.5">{invoice.peraturan}</div>}
                    </div>
                </div>

                {/* Tear cut accent */}
                <div className="text-center text-[8px] tracking-widest text-black mt-4 border-t border-dashed border-black pt-2 select-none">
                    - - - - - GUNTING DI SINI - - - - -
                </div>
            </div>
        );
    };

    return (
        <>
            <Head>
                <title>{`Invoice ${invoice.invoice_number} - ${brand.nama_brand}`}</title>
                <meta name="robots" content="noindex, nofollow, noarchive, nosnippet" />
                <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet" />
                {usePage().props.app?.favicon_url && <link rel="icon" href={usePage().props.app.favicon_url} />}
            </Head>
            <style dangerouslySetInnerHTML={{ __html: `
                @media print {
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                    html, body {
                        background: #ffffff !important;
                        color: #000000 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        width: 80mm !important;
                        max-width: 80mm !important;
                    }
                    * {
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    .print\\:hidden {
                        display: none !important;
                    }
                    .print\\:block {
                        display: block !important;
                        width: 80mm !important;
                        max-width: 80mm !important;
                        margin: 0 auto !important;
                        box-sizing: border-box !important;
                        background: #ffffff !important;
                    }
                }
            ` }} />
            <div className="min-h-screen bg-slate-50/50 px-4 py-8 md:py-12 print:hidden">
                <div className="mx-auto max-w-4xl space-y-6">

                    {/* Public Header Bar */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-2xl border shadow-sm print:hidden">
                        <div className="flex items-center gap-3">
                            {brand.logo ? (
                                <img
                                    src={`/storage/${brand.logo}`}
                                    alt={brand.nama_brand}
                                    className="h-10 w-10 rounded-xl object-contain bg-white p-1 border shadow-md"
                                />
                            ) : (
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-md" style={{ background: brand.warna_primary || '#4F46E5' }}>
                                    <ShieldCheck className="h-5 w-5" />
                                </div>
                            )}
                            <div>
                                <div className="font-extrabold text-slate-800 tracking-tight">{brand.nama_brand || 'Secure'} Invoice Portal</div>
                                <div className="text-xs text-muted-foreground flex items-center gap-1">
                                    <span className="h-2 w-2 rounded-full bg-emerald-500 inline-block animate-ping"></span>
                                    Invoice Terverifikasi Sistem
                                </div>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button asChild variant="outline" size="sm" className="rounded-xl font-semibold">
                                <a href={route('invoice.public.pdf', invoice.invoice_number)}>
                                    <Download className="h-4 w-4 mr-1.5" /> PDF
                                </a>
                            </Button>
                            <Button asChild size="sm" className="text-white rounded-xl font-semibold" style={{ background: brand.warna_primary || '#4F46E5' }}>
                                <a href={tracking_url} target="_blank" rel="noopener noreferrer">
                                    <ExternalLink className="h-4 w-4 mr-1.5" /> Lacak PO
                                </a>
                            </Button>
                        </div>
                    </div>

                            {/* Master Invoice Card */}
                            <div className="overflow-hidden rounded-3xl border border-slate-150 bg-white shadow-lg">
                                {/* Brand Banner Accent */}
                                <div
                                    className="h-3"
                                    style={{ background: brand.warna_primary || '#4F46E5' }}
                                ></div>

                        {/* Invoice Header */}
                        <div className="flex flex-col gap-6 border-b p-6 md:p-8 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex items-start gap-4">
                                {brand.logo ? (
                                    <div className="h-20 w-20 overflow-hidden rounded-2xl border bg-white flex items-center justify-center p-2 shadow-sm">
                                        <img
                                            src={`/storage/${brand.logo}`}
                                            alt={brand.nama_brand}
                                            className="h-full w-full object-contain"
                                        />
                                    </div>
                                ) : (
                                    <div
                                        className="h-20 w-20 rounded-2xl flex items-center justify-center font-black text-white text-2xl shadow-md"
                                        style={{ background: brand.warna_primary || '#4F46E5' }}
                                    >
                                        {brand.kode || 'B'}
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <h2 className="text-2xl font-black tracking-tight text-slate-800">{brand.nama_brand}</h2>
                                    {brand.tagline && (
                                        <div className="text-xs font-bold tracking-wide uppercase" style={{ color: brand.warna_primary || '#4F46E5' }}>
                                            {brand.tagline}
                                        </div>
                                    )}
                                    <div className="text-xs text-slate-600 leading-relaxed max-w-md space-y-1">
                                        {brand.alamat && <div><span className="font-bold text-slate-500">Alamat:</span> {brand.alamat}</div>}
                                        <div className="flex flex-wrap gap-x-4 gap-y-1">
                                            {brand.no_hp && <div><span className="font-bold text-slate-500">WA/Telp:</span> {brand.no_hp}</div>}
                                            {brand.email && <div><span className="font-bold text-slate-500">Email:</span> {brand.email}</div>}
                                        </div>

                                        {/* Social Media & Website Info */}
                                        <div className="flex flex-wrap gap-2 pt-1">
                                            {brand.website && (
                                                <a href={brand.website.startsWith('http') ? brand.website : `https://${brand.website}`} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded text-[10px] transition-colors border border-slate-200">
                                                    <Globe className="h-3 w-3" /> {brand.website}
                                                </a>
                                            )}
                                            {brand.instagram && (
                                                <a href={`https://instagram.com/${brand.instagram.replace('@', '')}`} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 bg-pink-50 hover:bg-pink-100 text-pink-750 font-bold px-2 py-0.5 rounded text-[10px] transition-colors border border-pink-100">
                                                    <svg className="h-3 w-3 text-pink-600 fill-current" viewBox="0 0 24 24">
                                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                                                    </svg> @{brand.instagram.replace('@', '')}
                                                </a>
                                            )}
                                            {brand.facebook && (
                                                <a href={`https://facebook.com/${brand.facebook}`} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded text-[10px] transition-colors border border-blue-100">
                                                    <svg className="h-3 w-3 text-blue-600 fill-current" viewBox="0 0 24 24">
                                                        <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z" />
                                                    </svg> {brand.facebook}
                                                </a>
                                            )}
                                            {brand.tiktok && (
                                                <a href={`https://tiktok.com/@${brand.tiktok.replace('@', '')}`} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 bg-slate-900 hover:bg-slate-800 text-white font-bold px-2 py-0.5 rounded text-[10px] transition-colors border border-slate-900">
                                                    <svg className="h-3 w-3 text-white fill-current" viewBox="0 0 24 24">
                                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.02.02.04.04.06.06.01.83.02 1.66.02 2.5-1.04-.37-1.99-1.01-2.73-1.84-.04-.04-.08-.09-.13-.13-.01 2.92-.01 5.84-.02 8.76-.08 1.63-.73 3.23-1.86 4.39-1.42 1.48-3.55 2.19-5.59 1.84-2.22-.35-4.14-1.99-4.73-4.18-.72-2.59.18-5.51 2.27-7.06.94-.71 2.09-1.09 3.28-1.12.01 1.48.01 2.97.02 4.45-.63.07-1.25.35-1.68.83-.56.61-.75 1.48-.5 2.27.27.86.99 1.51 1.87 1.69.96.22 2.04-.15 2.55-.99.27-.45.37-.99.35-1.52-.01-4.72-.01-9.44-.02-14.16z" />
                                                    </svg> @{brand.tiktok.replace('@', '')}
                                                </a>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="sm:text-right space-y-1.5">
                                <div className="text-[10px] font-extrabold tracking-widest text-muted-foreground uppercase">Master Invoice</div>
                                <h3 className="text-lg md:text-xl font-extrabold tracking-tight text-slate-800">{invoice.invoice_number}</h3>
                                <div className="flex sm:justify-end gap-2">
                                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border ${status.class}`}>
                                        {status.label.toUpperCase()}
                                    </span>
                                    {invoice.order?.tipe_pengiriman === 'pickup_cod' ? (
                                        <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border border-cyan-200 bg-cyan-50 text-cyan-800 shadow-sm">
                                            AMBIL DI TEMPAT / COD
                                        </span>
                                    ) : (invoice.order?.is_free_ongkir || invoice.order?.tipe_pengiriman === 'free_ongkir') ? (
                                        <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border border-emerald-250 bg-emerald-150 text-emerald-750 shadow-sm">
                                            FREE ONGKIR
                                        </span>
                                    ) : null}
                                </div>
                            </div>
                        </div>

                        {/* Customer & PO Info */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 border-b p-6 md:p-8 bg-slate-50/50">
                            <div className="space-y-1 min-w-0">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">DIBAYAR OLEH</span>
                                <h4 className="font-bold text-slate-800 text-base">{renderFormattedText(invoice.order?.pelanggan?.nama || '—')}</h4>
                                <div className="text-xs text-slate-600 font-medium">{maskPhone(invoice.order?.pelanggan?.nomor_hp)}</div>
                                {invoice.order?.pelanggan?.email && (
                                    <div className="text-xs text-slate-500 font-medium">{maskEmail(invoice.order?.pelanggan?.email)}</div>
                                )}
                                {(() => {
                                    const pelanggan = invoice.order?.pelanggan ?? {};
                                    const formattedAddress = [
                                        maskDetailAlamat(pelanggan.detail_alamat),
                                        pelanggan.kabupaten_nama,
                                        pelanggan.provinsi_nama,
                                        pelanggan.kodepos
                                    ].filter(Boolean).join(', ');
                                    return formattedAddress ? (
                                        <div className="text-[11px] text-slate-500 font-medium leading-relaxed max-w-sm mt-1 break-all">
                                            {formattedAddress}
                                        </div>
                                    ) : null;
                                })()}
                            </div>

                            <div className="space-y-1 md:text-right min-w-0">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">REFERENSI ORDER</span>
                                <div className="font-mono text-sm font-bold text-indigo-700">{invoice.order?.no_po || '—'}</div>
                                <div className="text-xs text-slate-600 font-medium">Tanggal PO: <strong>{formatDate(invoice.tanggal_terbit)}</strong></div>
                                {invoice.jatuh_tempo && (
                                    <div className="text-xs text-slate-500">Jatuh Tempo: <strong className="text-red-600">{formatDate(invoice.jatuh_tempo)}</strong></div>
                                )}
                                {invoice.order?.iklan && (
                                    <div className="text-xs text-slate-600 mt-1 font-medium">
                                        Promo: <strong className="text-emerald-700">{invoice.order.iklan.nama}{invoice.order.iklan.platform ? ` (${invoice.order.iklan.platform})` : ''}</strong>
                                    </div>
                                )}
                                {invoice.order?.tipe_pengiriman === 'pickup_cod' ? (
                                    <div className="text-xs text-slate-600 mt-1 font-medium">
                                        Status: <strong className="text-cyan-700">Ambil di Tempat / COD</strong>
                                    </div>
                                ) : (invoice.order?.is_free_ongkir || invoice.order?.tipe_pengiriman === 'free_ongkir') ? (
                                    <div className="text-xs text-slate-600 mt-1 font-medium">
                                        Status: <strong className="text-emerald-700">Bebas Ongkir (Free Ongkir)</strong>
                                    </div>
                                ) : null}
                            </div>
                        </div>

                        {/* Items Purchased */}
                        <div className="p-6 md:p-8 space-y-4">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400">Rincian Produk</h4>
                            <div className="overflow-hidden rounded-2xl border border-slate-150">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50">
                                        <tr className="border-b text-left text-xs uppercase tracking-wide text-slate-500 font-semibold">
                                            <th className="py-3 px-4">No</th>
                                            <th className="py-3 px-4">Produk / Item</th>
                                            <th className="py-3 px-4 text-right">Quantity</th>
                                            <th className="py-3 px-4 text-right">Harga Satuan</th>
                                            <th className="py-3 px-4 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {(() => {
                                            const mainItems = (invoice.items ?? []).filter(item => !item.is_addon);
                                            const addonItems = (invoice.items ?? []).filter(item => item.is_addon);
                                            const mainOrderItems = (invoice.order?.items ?? []).filter(item => !item.is_addon);
                                            const addonOrderItems = (invoice.order?.items ?? []).filter(item => item.is_addon);
                                            let rowNum = 1;
                                            return (
                                                <>
                                                    {mainItems.length > 0 && (
                                                        <>
                                                            <tr className="bg-slate-50/50 font-bold">
                                                                <td colSpan={5} className="py-2 px-4 text-xs text-slate-600 font-extrabold uppercase tracking-wide">
                                                                    Produk Inti
                                                                </td>
                                                            </tr>
                                                            {mainItems.map((item, index) => {
                                                                const orderItem = mainOrderItems[index];
                                                                return (
                                                                    <tr key={item.id} className="hover:bg-slate-50/20">
                                                                        <td className="py-3 px-4 text-xs font-medium text-slate-400">{rowNum++}</td>
                                                                        <td className="py-3 px-4">
                                                                            <div className="font-semibold text-slate-800">{renderFormattedText(item.produk)}</div>
                                                                            {orderItem?.bahan_formatted && (
                                                                                <div className="text-[11px] text-slate-500 font-medium mt-0.5">
                                                                                    Bahan: {orderItem.bahan_formatted}
                                                                                </div>
                                                                            )}
                                                                            {Number(item.discount_amount) > 0 && (
                                                                                <div className="text-[10px] text-rose-500 font-bold mt-0.5">
                                                                                    Diskon: {item.discount_type === 'persen' ? `${Number(item.discount_value)}%` : `${formatRupiah(item.discount_value)}/pcs`} (-{formatRupiah(item.discount_amount)})
                                                                                </div>
                                                                            )}
                                                                        </td>
                                                                        <td className="py-3 px-4 text-right font-mono font-medium">{item.jumlah} pcs</td>
                                                                        <td className="py-3 px-4 text-right font-mono text-xs">{formatRupiah(item.harga_satuan)}</td>
                                                                        <td className="py-3 px-4 text-right font-mono text-xs font-semibold text-slate-800">{formatRupiah(item.subtotal)}</td>
                                                                    </tr>
                                                                );
                                                            })}
                                                        </>
                                                    )}
                                                    {addonItems.length > 0 && (
                                                        <>
                                                            <tr className="bg-slate-50/50 font-bold">
                                                                <td colSpan={5} className="py-2 px-4 text-xs text-slate-600 font-extrabold uppercase tracking-wide border-t">
                                                                    Add-on
                                                                </td>
                                                            </tr>
                                                            {addonItems.map((item, index) => {
                                                                const orderItem = addonOrderItems[index];
                                                                return (
                                                                    <tr key={item.id} className="hover:bg-slate-50/20">
                                                                        <td className="py-3 px-4 text-xs font-medium text-slate-400">{rowNum++}</td>
                                                                        <td className="py-3 px-4">
                                                                            <div className="font-semibold text-slate-800">{renderFormattedText(item.produk)}</div>
                                                                            {orderItem?.bahan_formatted && (
                                                                                <div className="text-[11px] text-slate-500 font-medium mt-0.5">
                                                                                    Bahan: {orderItem.bahan_formatted}
                                                                                </div>
                                                                            )}
                                                                            {Number(item.discount_amount) > 0 && (
                                                                                <div className="text-[10px] text-rose-500 font-bold mt-0.5">
                                                                                    Diskon: {item.discount_type === 'persen' ? `${Number(item.discount_value)}%` : `${formatRupiah(item.discount_value)}/pcs`} (-{formatRupiah(item.discount_amount)})
                                                                                </div>
                                                                            )}
                                                                        </td>
                                                                        <td className="py-3 px-4 text-right font-mono font-medium">{item.jumlah} pcs</td>
                                                                        <td className="py-3 px-4 text-right font-mono text-xs">{formatRupiah(item.harga_satuan)}</td>
                                                                        <td className="py-3 px-4 text-right font-mono text-xs font-semibold text-slate-800">{formatRupiah(item.subtotal)}</td>
                                                                    </tr>
                                                                );
                                                            })}
                                                        </>
                                                    )}
                                                </>
                                            );
                                        })()}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Ledger Transaction History (Incoming/Outgoing Payments) */}
                        <div className="px-6 md:px-8 pb-6 space-y-4">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400">Riwayat Pembayaran & Penyesuaian (Verified)</h4>
                            <div className="overflow-hidden rounded-2xl border border-slate-150">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50">
                                        <tr className="border-b text-left text-xs uppercase tracking-wide text-slate-500 font-semibold">
                                            <th className="py-3 px-4">Tanggal</th>
                                            <th className="py-3 px-4">Deskripsi / Jenis Transaksi</th>
                                            <th className="py-3 px-4">Tujuan Mutasi Bank</th>
                                            <th className="py-3 px-4 text-right">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {payments.length === 0 ? (
                                            <tr>
                                                <td colSpan={4} className="py-4 px-4 text-center text-xs text-slate-400 italic">
                                                    Belum ada riwayat pembayaran yang diverifikasi oleh Admin Keuangan.
                                                </td>
                                            </tr>
                                        ) : (
                                            payments.map((p) => {
                                                const isDebit = ['dp', 'pelunasan', 'tambahan_produk', 'ongkir'].includes(p.payment_type);

                                                let displayType = p.master_jenis_pembayaran?.nama?.toUpperCase() ?? p.payment_type.toUpperCase();
                                                if (p.payment_type === 'dp') {
                                                    displayType = `DP SEQUENCE (DP #${p.dp_sequence || '1'})`;
                                                } else if (p.payment_type === 'tambahan_produk') {
                                                    displayType = 'TAMBAHAN PRODUK';
                                                } else if (displayType === 'RETURN' || displayType === 'REFURN') {
                                                    displayType = 'REFUND';
                                                }

                                                return (
                                                    <tr key={p.id} className="hover:bg-slate-50/20 text-xs">
                                                        <td className="py-3 px-4 text-slate-500 font-medium">{formatDate(p.payment_date)}</td>
                                                        <td className="py-3 px-4">
                                                            <div className="flex items-center gap-2">
                                                                {isDebit ? (
                                                                    <div className="h-5 w-5 bg-emerald-100 text-emerald-700 rounded-md flex items-center justify-center">
                                                                        <ArrowDownLeft className="h-3 w-3" />
                                                                    </div>
                                                                ) : (
                                                                    <div className="h-5 w-5 bg-red-100 text-red-700 rounded-md flex items-center justify-center">
                                                                        <ArrowUpRight className="h-3 w-3" />
                                                                    </div>
                                                                )}
                                                                <div>
                                                                    <span className="font-bold text-slate-800">{displayType}</span>
                                                                    {p.notes && <span className="block text-[10px] text-slate-500 mt-0.5">{renderFormattedText(p.notes)}</span>}
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="py-3 px-4 font-medium text-slate-600">
                                                            {p.customer_bank_name || p.customer_bank_account ? (
                                                                <div className="flex flex-col">
                                                                    <span className="font-semibold text-slate-800">{p.customer_bank_name || '—'}</span>
                                                                    <span className="text-[10px] text-slate-500 font-mono">{p.customer_bank_account || '—'}</span>
                                                                </div>
                                                            ) : p.bank ? (
                                                                `${p.bank.bank} — ${p.bank.nomor_rekening}`
                                                            ) : (
                                                                '—'
                                                            )}
                                                        </td>
                                                        <td className={`py-3 px-4 text-right font-mono font-bold ${isDebit ? 'text-emerald-600' : 'text-red-600'}`}>
                                                            {isDebit ? '+' : '-'} {formatRupiah(p.amount)}
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Totals Summary */}
                        <div className="border-t bg-slate-50/40 p-6 md:p-8 flex flex-col md:flex-row justify-between gap-6">
                            <div className="space-y-4 max-w-sm">
                                {invoice.bank && (
                                    invoice.bank.bank === 'CASH' ? (
                                        <div className="space-y-1.5 p-4 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                            <span className="text-[10px] font-bold tracking-wider text-slate-400 block uppercase">Metode Pembayaran Resmi</span>
                                            <div className="text-sm font-extrabold text-slate-800">TUNAI / CASH</div>
                                            <div className="text-xs font-semibold text-slate-500">
                                                Pembayaran secara tunai langsung ke kasir atau outlet resmi.
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="space-y-1.5 p-4 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                            <span className="text-[10px] font-bold tracking-wider text-slate-400 block uppercase">Rekening Pembayaran Resmi</span>
                                            <div className="text-sm font-extrabold text-slate-800">{invoice.bank.bank}</div>
                                            <div className="text-xs font-semibold text-slate-500">Atas Nama: {invoice.bank.atas_nama}</div>
                                            <div className="font-mono font-black text-indigo-700 text-base select-all bg-indigo-50/50 py-1.5 px-3 rounded-lg border border-indigo-100/50 inline-block">
                                                {invoice.bank.nomor_rekening}
                                            </div>
                                        </div>
                                    )
                                )}
                                <div className="space-y-2 p-4 bg-amber-50/75 rounded-2xl border border-amber-100 shadow-sm text-amber-800 text-[11px] leading-relaxed">
                                    <div className="flex items-center gap-1.5 font-bold text-amber-900">
                                        <AlertCircle className="h-3.5 w-3.5 text-amber-600 shrink-0" />
                                        ⚠️ Imbauan Keamanan Pembayaran
                                    </div>
                                    {invoice.bank && invoice.bank.bank === 'CASH' ? (
                                        <p>
                                            Demi keamanan transaksi, mohon lakukan pembayaran tunai secara langsung hanya melalui kasir atau sales resmi brand kami. Jangan melakukan transfer ke rekening perorangan/rekening lain yang tidak terdaftar secara resmi. Selalu konfirmasi transaksi melalui kontak resmi brand kami.
                                        </p>
                                    ) : (
                                        <p>
                                            Demi keamanan transaksi, mohon <strong>TIDAK MELAKUKAN</strong> transfer ke rekening mana pun selain rekening resmi atas nama {invoice.bank ? <strong>{invoice.bank.atas_nama}</strong> : <strong>{brand.nama_brand}</strong>}. Jangan pernah mengirimkan dana ke rekening perorangan/sales/rekening lain di luar informasi resmi yang tertera. Selalu konfirmasi transaksi melalui kontak resmi brand kami.
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="w-full md:max-w-sm space-y-2.5 text-slate-700">
                                {(() => {
                                    const mainItems = (invoice.items ?? []).filter(item => !item.is_addon);
                                    const addonItems = (invoice.items ?? []).filter(item => item.is_addon);
                                    const mainSubtotalGross = mainItems.reduce((s, x) => s + (Number(x.jumlah) * Number(x.harga_satuan)), 0);
                                    const addonSubtotalGross = addonItems.reduce((s, x) => s + (Number(x.jumlah) * Number(x.harga_satuan)), 0);
                                    const grossSubtotal = mainSubtotalGross + addonSubtotalGross;

                                    return (
                                        <div className="flex justify-between items-center text-xs">
                                            <span className="font-semibold text-slate-400">Total Harga</span>
                                            <span className="font-mono font-bold text-slate-700">{formatRupiah(grossSubtotal)}</span>
                                        </div>
                                    );
                                })()}

                                {(() => {
                                    const mainItems = (invoice.items ?? []).filter(item => !item.is_addon);
                                    const addonItems = (invoice.items ?? []).filter(item => item.is_addon);
                                    const mainSubtotalGross = mainItems.reduce((s, x) => s + (Number(x.jumlah) * Number(x.harga_satuan)), 0);
                                    const addonSubtotalGross = addonItems.reduce((s, x) => s + (Number(x.jumlah) * Number(x.harga_satuan)), 0);
                                    const grossSubtotal = mainSubtotalGross + addonSubtotalGross;

                                    const itemDiskonSum = (invoice.items ?? []).reduce((s, x) => s + Number(x.discount_amount || 0), 0);
                                    const diskonValue = Number(invoice.diskon_value || 0);
                                    const diskonNominal = itemDiskonSum > 0
                                        ? itemDiskonSum
                                        : (invoice.diskon_type === 'persen'
                                            ? (grossSubtotal * diskonValue / 100)
                                            : diskonValue);

                                    return (
                                        <div className="flex justify-between items-center text-xs text-slate-700">
                                            <span className="font-semibold text-slate-400">Total Diskon</span>
                                            <span className="font-mono">{diskonNominal > 0 ? '-' : ''} {formatRupiah(diskonNominal)}</span>
                                        </div>
                                    );
                                })()}

                                {invoice.order?.tipe_pengiriman === 'pickup_cod' ? (
                                    <div className="flex justify-between items-center text-xs font-bold text-cyan-700">
                                        <span>Metode Pengiriman</span>
                                        <span className="font-mono">Ambil di Tempat / COD</span>
                                    </div>
                                ) : (invoice.order?.is_free_ongkir || invoice.order?.tipe_pengiriman === 'free_ongkir') ? (
                                    <div className="flex justify-between items-center text-xs font-bold text-emerald-600">
                                        <span>Ongkir {invoice.jasa_pengiriman ? `(${invoice.jasa_pengiriman})` : ''}</span>
                                        <span className="font-mono">Gratis Ongkir</span>
                                    </div>
                                ) : (
                                    Number(invoice.biaya_pengiriman || 0) > 0 && (
                                        <div className="flex justify-between items-center text-xs font-bold text-slate-700">
                                            <span>Ongkir {invoice.jasa_pengiriman ? `(${invoice.jasa_pengiriman})` : ''}</span>
                                            <span className="font-mono">+ {formatRupiah(Number(invoice.biaya_pengiriman))}</span>
                                        </div>
                                    )
                                )}

                                {additionPayments.length > 0 && (
                                    <div className="flex justify-between items-center text-xs">
                                        <span className="font-semibold text-slate-400">Tambahan Produk</span>
                                        <span className="font-mono font-bold text-slate-700">+ {formatRupiah(additionPayments.reduce((s, x) => s + Number(x.amount), 0))}</span>
                                    </div>
                                )}

                                <div className="flex justify-between items-center text-xs font-bold border-t pt-2 border-slate-150">
                                    <span className="text-slate-800">Total yang Harus Dibayar</span>
                                    <span className="font-mono text-slate-800">{formatRupiah(grossInvoiceTotal)}</span>
                                </div>

                                {totalReceived > 0 && (
                                    <div className="flex justify-between items-center text-xs text-slate-700 font-bold">
                                        <span>Total Terbayar</span>
                                        <span className="font-mono">{formatRupiah(totalReceived)}</span>
                                    </div>
                                )}

                                {returnSum > 0 && (
                                    <div className="flex justify-between items-center text-xs text-rose-600 font-bold">
                                        <span>Refund</span>
                                        <span className="font-mono">- {formatRupiah(returnSum)}</span>
                                    </div>
                                )}

                                {cashbackSum > 0 && (
                                    <div className="flex justify-between items-center text-xs text-rose-600 font-bold">
                                        <span>Cashback</span>
                                        <span className="font-mono">- {formatRupiah(cashbackSum)}</span>
                                    </div>
                                )}

                                {(returnSum > 0 || cashbackSum > 0) && (
                                    <div className="flex justify-between items-center text-xs text-emerald-600 font-bold border-t border-dashed pt-2 border-slate-150">
                                        <span>Neto Pembayaran</span>
                                        <span className="font-mono">{formatRupiah(totalReceived - returnSum - cashbackSum)}</span>
                                    </div>
                                )}

                                {(() => {
                                    const netPayment = totalReceived - returnSum - cashbackSum;
                                    const calculatedSisa = Math.max(0, grossInvoiceTotal - netPayment);
                                    return (
                                        <div
                                            className={`flex justify-between items-center border-t border-slate-200 pt-3 text-lg font-black ${
                                                calculatedSisa > 0 ? 'text-rose-600' : 'text-emerald-600'
                                            }`}
                                        >
                                            <span>Sisa</span>
                                            <span className="font-mono">{formatRupiah(calculatedSisa)}</span>
                                        </div>
                                    );
                                })()}

                                {returnSum > 0 && (
                                    <div className="mt-3 p-3 bg-rose-50 border border-rose-100 rounded-2xl flex justify-between items-center text-xs font-bold text-rose-700">
                                        <span>Informasi Refund</span>
                                        <span className="font-mono text-sm font-black text-rose-650">{formatRupiah(returnSum)}</span>
                                    </div>
                                )}

                                {cashbackSum > 0 && (
                                    <div className="mt-3 p-3 bg-amber-50 border border-amber-100 rounded-2xl flex justify-between items-center text-xs font-bold text-amber-700">
                                        <span>Informasi Cashback</span>
                                        <span className="font-mono text-sm font-black text-amber-650">{formatRupiah(cashbackSum)}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Footer Tracking Banner */}
                        {qr_code && (
                            <div className="flex flex-col gap-6 border-t bg-slate-50 p-6 md:p-8 md:flex-row md:items-start md:justify-between">
                                <div className="space-y-4 max-w-xl">
                                    <div>
                                        <h4 className="text-sm font-bold text-slate-800">Terima kasih atas pembayaran Anda!</h4>
                                        {invoice.peraturan && (
                                            <p className="text-xs text-slate-500 mt-1 leading-relaxed">{renderFormattedText(invoice.peraturan)}</p>
                                        )}
                                    </div>
                                    <div className="text-xs text-slate-600 leading-relaxed space-y-1">
                                        <div><strong>Cara Cek Pesanan:</strong></div>
                                        <div>
                                            Kunjungi link <a href={tracking_url} className="text-indigo-600 hover:underline font-semibold">{window.location.origin}/track/{invoice.order?.no_po}</a> atau scan QR code di samping untuk memantau status pesanan secara langsung.
                                        </div>
                                    </div>
                                </div>
                                <div className="text-center bg-white p-4 rounded-2xl border shadow-sm shrink-0 self-center md:self-start">
                                    <img src={qr_code} alt="QR Tracking" className="mx-auto h-28 w-28" />
                                    <div className="mt-2 text-[10px] font-extrabold text-slate-700 tracking-wide uppercase">
                                        Scan QR untuk cek pesanan
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Ketentuan Lainnya */}
                        <div className="border-t border-slate-100 bg-slate-50/20 px-6 py-4 md:px-8 text-[11px] text-slate-600 leading-relaxed">
                            <div className="font-extrabold text-slate-800 mb-2">Ketentuan Lainnya:</div>
                            <ul className="space-y-1.5 font-bold text-slate-700">
                                <li className="flex items-start gap-1.5">
                                    <span className="shrink-0">1.</span>
                                    <span>Bukti pembayaran wajib dikirimkan ke WhatsApp Admin untuk proses verifikasi.</span>
                                </li>
                                <li className="flex items-start gap-1.5">
                                    <span className="shrink-0">2.</span>
                                    <span>Mohon lakukan video unboxing saat paket diterima sebagai bukti apabila terjadi kendala pada produk.</span>
                                </li>
                                <li className="flex items-start gap-1.5">
                                    <span className="shrink-0">3.</span>
                                    <span>Pengajuan komplain maksimal 3 × 24 jam sejak barang diterima dan wajib disertai bukti berupa video unboxing serta foto produk.</span>
                                </li>
                            </ul>
                        </div>
                    </div>


                    {/* Disclaimer */}
                    <div className="rounded-2xl border border-slate-200/50 bg-slate-100/50 p-4 text-center text-[10px] text-slate-500 leading-normal">
                        Seluruh transaksi dan data yang tercantum dalam dokumen ini diterbitkan secara resmi oleh sistem master ledger keuangan <strong>{brand.nama_brand || 'Konveksi'}</strong> dan dilindungi oleh syarat & ketentuan pengerjaan konveksi. Hubungi Admin Brand jika terdapat selisih.
                    </div>

                </div>
            </div>

            {/* Thermal Receipt Print Only */}
            <div 
                className="hidden print:block bg-white text-black font-mono text-xs leading-relaxed mx-auto"
                style={{ width: '80mm', maxWidth: '80mm', margin: '0 auto', padding: '4mm', boxSizing: 'border-box' }}
            >
                {renderThermalReceipt()}
            </div>
        </>
    );
}
