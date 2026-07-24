import { Head, usePage } from '@inertiajs/react';
import { CheckCircle2, Circle, Clock, AlertTriangle, Package, ShieldCheck } from 'lucide-react';
import { formatDate } from '@/lib/utils';
import usePublicSecurity from '@/hooks/usePublicSecurity';

const STATUS_BADGE = {
    draft: { label: 'Draft', class: 'bg-gray-100 text-gray-700' },
    published: { label: 'Order Diterima', class: 'bg-blue-100 text-blue-700' },
    on_progress: { label: 'Sedang Diproduksi', class: 'bg-amber-100 text-amber-700' },
    selesai_produksi: { label: 'Selesai Produksi', class: 'bg-emerald-100 text-emerald-700' },
    siap_dikirim: { label: 'Siap Dikirim', class: 'bg-cyan-100 text-cyan-700' },
    sudah_dikirim: { label: 'Sudah Dikirim', class: 'bg-violet-100 text-violet-700' },
    selesai: { label: 'Produk Telah Diterima', class: 'bg-green-100 text-green-700' },
    delay: { label: 'Terlambat', class: 'bg-red-100 text-red-700' },
    hold: { label: 'Ditahan', class: 'bg-orange-100 text-orange-700' },
};

function ProgressIcon({ status, brandColor }) {
    if (status === 'selesai') return <CheckCircle2 className="h-5 w-5" style={{ color: brandColor }} />;
    if (status === 'on_progress') return <Clock className="h-5 w-5 animate-pulse text-amber-500" />;
    if (status === 'skipped') return <Circle className="h-5 w-5 text-gray-300" />;
    return <Circle className="h-5 w-5 text-gray-300" />;
}
export default function Track({ po_number, found, order, brand, invoice, invoices = [] }) {
    usePublicSecurity();
    const activeBrand = order?.brand || brand;

    // Helper to format WhatsApp API link
    const getWhatsAppUrl = () => {
        if (!activeBrand?.whatsapp) return null;
        let cleaned = activeBrand.whatsapp.replace(/\D/g, '');
        if (cleaned.startsWith('0')) {
            cleaned = '62' + cleaned.substring(1);
        } else if (cleaned.startsWith('8')) {
            cleaned = '62' + cleaned;
        }
        const text = `Halo tim *${activeBrand.nama_brand}*, saya ingin menanyakan status pesanan saya dengan nomor PO *${order?.no_po || po_number}*${order?.nama_po ? ` (*${order.nama_po}*)` : ''}.`;
        return `https://wa.me/${cleaned}?text=${encodeURIComponent(text)}`;
    };

    // Helper to format Instagram link
    const getInstagramUrl = () => {
        if (!activeBrand?.instagram) return null;
        const cleaned = activeBrand.instagram.replace('@', '').trim();
        return `https://instagram.com/${cleaned}`;
    };

    // Helper to format TikTok link
    const getTiktokUrl = () => {
        if (!activeBrand?.tiktok) return null;
        const cleaned = activeBrand.tiktok.replace('@', '').trim();
        return `https://tiktok.com/@${cleaned}`;
    };

    const whatsappUrl = getWhatsAppUrl();
    const instagramUrl = getInstagramUrl();
    const tiktokUrl = getTiktokUrl();

    const brandColor = activeBrand?.warna_primary || '#2563EB';

    const getAlphaColor = (hexColor, alpha) => {
        if (!hexColor) return `rgba(37, 99, 235, ${alpha})`;
        if (hexColor.startsWith('#')) {
            const hex = hexColor.replace('#', '');
            if (hex.length === 3) {
                const r = parseInt(hex[0] + hex[0], 16);
                const g = parseInt(hex[1] + hex[1], 16);
                const b = parseInt(hex[2] + hex[2], 16);
                return `rgba(${r}, ${g}, ${b}, ${alpha})`;
            }
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
        return hexColor;
    };

    return (
        <>
            <Head>
                <title>{`Tracking ${po_number}`}</title>
                {usePage().props.app?.favicon_url && <link rel="icon" href={usePage().props.app.favicon_url} />}
            </Head>
            <div 
                className="min-h-screen px-4 py-8 transition-all duration-300"
                style={{
                    background: `linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, ${getAlphaColor(brandColor, 0.05)} 100%)`
                }}
            >
                <div className="mx-auto max-w-2xl">
                    <div className="mb-6 flex items-center justify-center gap-3 text-center">
                        {activeBrand ? (
                            activeBrand.logo ? (
                                <img
                                    src={activeBrand.logo.startsWith('http') ? activeBrand.logo : `/storage/${activeBrand.logo}`}
                                    className="h-10 w-10 rounded-xl object-contain shadow bg-white border border-slate-100 p-1"
                                    alt={activeBrand.nama_brand}
                                />
                            ) : (
                                <div
                                    className="flex h-10 w-10 items-center justify-center rounded-xl text-white font-extrabold shadow text-base uppercase transition-all duration-300"
                                    style={{ backgroundColor: brandColor }}
                                >
                                    {activeBrand.nama_brand.substring(0, 2).toUpperCase()}
                                </div>
                            )
                        ) : (
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow">
                                <ShieldCheck className="h-5 w-5" />
                            </div>
                        )}
                        <div className="text-left">
                            <div className="text-lg font-extrabold tracking-tight text-slate-800">
                                {activeBrand?.nama_brand ? activeBrand.nama_brand : 'Secure Tracking'}
                            </div>
                            <div className="text-xs font-semibold text-slate-500 uppercase tracking-widest">Tracking PO</div>
                        </div>
                    </div>

                    {!found && (
                        <div className="space-y-4">
                            <div className="rounded-2xl border bg-white p-8 text-center shadow-sm">
                                <AlertTriangle className="mx-auto h-12 w-12 text-amber-500" />
                                <h2 className="mt-4 text-xl font-bold">PO Tidak Ditemukan</h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Nomor PO <span className="font-mono font-semibold">{po_number}</span> tidak ditemukan atau belum diterbitkan.
                                </p>
                            </div>

                            {activeBrand && (
                                <div className="rounded-2xl border bg-white p-6 shadow-sm flex flex-col items-center text-center space-y-4">
                                    <div className="space-y-1">
                                        <h4 className="text-sm font-bold text-slate-800">Butuh Bantuan?</h4>
                                        <p className="text-xs text-muted-foreground">Silakan hubungi customer service <strong>{activeBrand.nama_brand}</strong> di bawah ini:</p>
                                    </div>
                                    <div className="flex flex-wrap items-center justify-center gap-3 w-full sm:w-auto">
                                        {whatsappUrl && (
                                            <a
                                                href={whatsappUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 px-5 py-2.5 h-10 shadow-sm transition-all hover:-translate-y-0.5 duration-200"
                                            >
                                                <svg className="h-5 w-5 fill-current text-white" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12.031 2a9.965 9.965 0 0 0-9.96 9.96c0 1.603.38 3.123 1.106 4.479L2 22l5.733-1.503a9.92 9.92 0 0 0 4.298 1.002h.004a9.969 9.969 0 0 0 9.965-9.96c-.001-2.66-1.039-5.161-2.925-7.047A9.924 9.924 0 0 0 12.03 2Zm6.417 14.185c-.279.782-1.408 1.433-1.954 1.547-.488.102-.977.195-2.738-.492-2.257-.88-3.71-3.175-3.824-3.327-.113-.151-.925-1.227-.925-2.33 0-1.104.577-1.644.782-1.87.205-.226.45-.282.602-.282.15 0 .301 0 .432.007.136.006.32.016.49.424.173.418.594 1.45.647 1.557.052.106.088.23.017.371-.07.142-.106.23-.212.353-.106.124-.223.277-.318.371-.106.103-.217.215-.094.425.122.21.54 1.001 1.157 1.55.797.708 1.467.927 1.674 1.03.208.103.33.085.45-.053.123-.138.528-.616.67-.827.14-.21.282-.177.472-.106.19.07 1.205.568 1.412.674.208.106.347.16.398.248.053.088.053.513-.173 1.295Z" />
                                                </svg>
                                                Hubungi via WhatsApp
                                            </a>
                                        )}
                                        {instagramUrl && (
                                            <a
                                                href={instagramUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold text-white bg-gradient-to-tr from-yellow-500 via-red-500 to-purple-600 hover:opacity-90 px-5 py-2.5 h-10 shadow-sm transition-all hover:-translate-y-0.5 duration-200"
                                            >
                                                <svg className="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                                                </svg>
                                                Instagram @{activeBrand.instagram.replace('@', '')}
                                            </a>
                                        )}
                                        {tiktokUrl && (
                                            <a
                                                href={tiktokUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 px-5 py-2.5 h-10 shadow-sm transition-all hover:-translate-y-0.5 duration-200"
                                            >
                                                <svg className="h-4 w-4 fill-current text-white" viewBox="0 0 24 24">
                                                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.02.02.04.04.06.06.01.83.02 1.66.02 2.5-1.04-.37-1.99-1.01-2.73-1.84-.04-.04-.08-.09-.13-.13-.01 2.92-.01 5.84-.02 8.76-.08 1.63-.73 3.23-1.86 4.39-1.42 1.48-3.55 2.19-5.59 1.84-2.22-.35-4.14-1.99-4.73-4.18-.72-2.59.18-5.51 2.27-7.06.94-.71 2.09-1.09 3.28-1.12.01 1.48.01 2.97.02 4.45-.63.07-1.25.35-1.68.83-.56.61-.75 1.48-.5 2.27.27.86.99 1.51 1.87 1.69.96.22 2.04-.15 2.55-.99.27-.45.37-.99.35-1.52-.01-4.72-.01-9.44-.02-14.16z" />
                                                </svg>
                                                TikTok @{activeBrand.tiktok.replace('@', '')}
                                            </a>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    ) }

                    {found && order && (
                        <div className="space-y-4">
                            {/* Header card */}
                            <div className="rounded-2xl border bg-white p-6 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex-1">
                                        <div className="font-mono text-xs text-muted-foreground">{order.no_po}</div>
                                        <h1 className="mt-1 text-xl font-bold">{order.nama_po}</h1>
                                        <div className="mt-1 text-sm text-muted-foreground">{order.brand?.nama_brand}</div>
                                    </div>
                                    <div 
                                        className={`rounded-full px-3 py-1 text-xs font-semibold ${order.status_po !== 'published' ? (STATUS_BADGE[order.status_po]?.class ?? 'bg-gray-100') : ''}`}
                                        style={order.status_po === 'published' ? { backgroundColor: getAlphaColor(brandColor, 0.1), color: brandColor } : {}}
                                    >
                                        {STATUS_BADGE[order.status_po]?.label ?? order.status_po}
                                    </div>
                                </div>
                                <div className="mt-4 grid grid-cols-2 gap-2 text-xs">
                                    <div className="rounded-lg bg-muted/40 p-2">
                                        <div className="text-muted-foreground">Pelanggan</div>
                                        <div className="font-medium">{order.pelanggan?.nama_initial}</div>
                                        <div className="font-mono text-[10px] text-muted-foreground">{order.pelanggan?.hp_masked}</div>
                                    </div>
                                    <div className="rounded-lg bg-muted/40 p-2">
                                        <div className="text-muted-foreground">Deadline</div>
                                        <div className="font-medium">{formatDate(order.deadline_customer)}</div>
                                        {order.end_production_date && <div className="text-[10px] text-muted-foreground">Est selesai: {formatDate(order.end_production_date)}</div>}
                                    </div>
                                </div>
                            </div>

                            {/* Apology Alert Card */}
                            {order.is_missed_deadline && (
                                <div className="rounded-2xl border border-red-200 bg-red-50/50 p-5 shadow-sm space-y-2">
                                    <div className="flex items-center gap-2 font-bold text-red-800 text-sm">
                                        <AlertTriangle className="h-5 w-5 text-red-600" />
                                        Permohonan Maaf Keterlambatan
                                    </div>
                                    <p className="text-xs text-red-700 leading-relaxed font-medium">
                                        Kami memohon maaf yang sebesar-besarnya atas keterlambatan pengerjaan atau pengiriman pesanan Anda yang tidak sesuai dengan deadline awal. Tim kami sedang bekerja keras untuk menyelesaikan dan mengirimkan pesanan Anda secepat mungkin. Terima kasih atas pengertian dan kesabaran Anda.
                                    </p>
                                </div>
                            )}


                            {/* Invoice Card */}
                            {/* Invoice Card */}
                            {invoices && invoices.length > 0 ? (
                                <div className="space-y-3.5">
                                    {invoices.map((inv, idx) => (
                                        <div 
                                            key={inv.invoice_number} 
                                            className="rounded-2xl border p-5 shadow-sm space-y-4"
                                            style={{
                                                borderColor: getAlphaColor(brandColor, 0.2),
                                                backgroundColor: getAlphaColor(brandColor, 0.04)
                                            }}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div 
                                                    className="flex items-center gap-2 font-bold text-sm"
                                                    style={{ color: getAlphaColor(brandColor, 0.9) }}
                                                >
                                                    <svg 
                                                        className="h-5 w-5" 
                                                        style={{ color: brandColor }}
                                                        fill="none" 
                                                        viewBox="0 0 24 24" 
                                                        stroke="currentColor" 
                                                        strokeWidth={2.5}
                                                    >
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Invoice {idx === 0 ? 'Awal / DP' : 'Pelunasan / Final'}: <span className="font-mono select-all font-extrabold" style={{ color: brandColor }}>{inv.invoice_number}</span>
                                                </div>
                                                <span className="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                                    Ready to View
                                                </span>
                                            </div>
                                            <p className="text-xs text-slate-600 leading-relaxed">
                                                {idx === 0 
                                                    ? "Invoice awal (DP) resmi untuk PO ini telah diterbitkan oleh bagian Keuangan." 
                                                    : "Invoice pelunasan / final rekonsiliasi PO ini telah diterbitkan oleh bagian Keuangan."
                                                } Anda dapat melihat rincian lengkap pembayaran atau mengunduh dokumen PDF secara langsung.
                                            </p>
                                            <div className="grid grid-cols-2 gap-3 pt-1">
                                                <a
                                                    href={`/invoice/${inv.invoice_number}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    style={{ backgroundColor: brandColor }}
                                                    className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold text-white py-3 px-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:brightness-95 active:brightness-90"
                                                >
                                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Invoice Web
                                                </a>
                                                <a
                                                    href={`/invoice/${inv.invoice_number}/pdf`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    style={{
                                                        borderColor: getAlphaColor(brandColor, 0.2),
                                                        color: brandColor
                                                    }}
                                                    className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold bg-white border hover:bg-slate-50 active:bg-slate-100 py-3 px-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5"
                                                >
                                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                    Invoice PDF
                                                </a>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : invoice ? (
                                <div 
                                    className="rounded-2xl border p-5 shadow-sm space-y-4"
                                    style={{
                                        borderColor: getAlphaColor(brandColor, 0.2),
                                        backgroundColor: getAlphaColor(brandColor, 0.04)
                                    }}
                                >
                                    <div className="flex items-center justify-between">
                                        <div 
                                            className="flex items-center gap-2 font-bold text-sm"
                                            style={{ color: getAlphaColor(brandColor, 0.9) }}
                                        >
                                            <svg 
                                                className="h-5 w-5" 
                                                style={{ color: brandColor }}
                                                fill="none" 
                                                viewBox="0 0 24 24" 
                                                stroke="currentColor" 
                                                strokeWidth={2.5}
                                            >
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Invoice Terbit: <span className="font-mono select-all font-extrabold" style={{ color: brandColor }}>{invoice.invoice_number}</span>
                                        </div>
                                        <span className="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                            Ready to View
                                        </span>
                                    </div>
                                    <p className="text-xs text-slate-600 leading-relaxed">
                                        Invoice resmi untuk PO ini telah diterbitkan oleh bagian Keuangan. Anda dapat melihat rincian lengkap pembayaran atau mengunduh dokumen PDF secara langsung.
                                    </p>
                                    <div className="grid grid-cols-2 gap-3 pt-1">
                                        <a
                                            href={`/invoice/${invoice.invoice_number}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            style={{ backgroundColor: brandColor }}
                                            className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold text-white py-3 px-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:brightness-95 active:brightness-90"
                                        >
                                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Invoice Web
                                        </a>
                                        <a
                                            href={`/invoice/${invoice.invoice_number}/pdf`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            style={{
                                                borderColor: getAlphaColor(brandColor, 0.2),
                                                color: brandColor
                                            }}
                                            className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold bg-white border hover:bg-slate-50 active:bg-slate-100 py-3 px-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5"
                                        >
                                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Invoice PDF
                                        </a>
                                    </div>
                                </div>
                            ) : null}

                            {/* Ekspedisi & Resi */}
                            {(order.nama_ekspedisi || order.no_resi || order.tipe_pengiriman === 'pickup_cod' || order.is_free_ongkir) && (
                                <div className="rounded-2xl border bg-violet-50 border-violet-200 p-5 shadow-sm">
                                    <div className="mb-3 flex items-center justify-between font-semibold text-violet-800">
                                        <div className="flex items-center gap-2">
                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" /></svg>
                                            Info Pengiriman
                                        </div>
                                        {order.tipe_pengiriman === 'pickup_cod' ? (
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-black bg-cyan-100 text-cyan-800 border border-cyan-200">AMBIL DI TEMPAT / COD</span>
                                        ) : (order.is_free_ongkir || order.tipe_pengiriman === 'free_ongkir') ? (
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">GRATIS ONGKIR</span>
                                        ) : null}
                                    </div>
                                    <div className="grid grid-cols-2 gap-3 text-sm">
                                        {order.nama_ekspedisi && (
                                            <div className="rounded-lg bg-white border border-violet-100 p-3">
                                                <div className="text-xs text-violet-500 font-medium">Ekspedisi / Kurir</div>
                                                <div className="font-bold text-violet-900 mt-0.5">{order.nama_ekspedisi}</div>
                                            </div>
                                        )}
                                        {order.no_resi && (
                                            <div className="rounded-lg bg-white border border-violet-100 p-3">
                                                <div className="text-xs text-violet-500 font-medium">No. Resi / Catatan</div>
                                                <div className="font-mono font-bold text-violet-900 mt-0.5 break-all">{order.no_resi}</div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Items */}
                            <div className="rounded-2xl border bg-white p-5 shadow-sm">
                                <div className="mb-3 flex items-center gap-2 font-semibold">
                                    <Package className="h-4 w-4" style={{ color: brandColor }} /> Detail Pesanan
                                </div>
                                <ul className="space-y-2 text-sm">
                                    {(order.items ?? []).map((item, idx) => (
                                        <li key={idx} className="flex items-center justify-between rounded-lg border bg-muted/20 p-3">
                                            <span>{idx + 1}. {item.nama_produk}</span>
                                            <span className="font-mono text-xs font-semibold">{item.quantity} pcs</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            {/* Progress Timeline */}
                            <div className="rounded-2xl border bg-white p-5 shadow-sm">
                                <div className="mb-3 font-semibold">Progress Pengerjaan</div>
                                <ol className="space-y-3">
                                    {(order.progress ?? []).map((p, i) => (
                                        <li key={i} className="flex items-center gap-3">
                                            <ProgressIcon status={p.status} brandColor={brandColor} />
                                            <div className="flex-1">
                                                <div className={`text-sm font-medium ${p.status === 'pending' ? 'text-muted-foreground' : ''}`}>
                                                    {p.nama}
                                                </div>
                                                {p.completed_at && (
                                                    <div className="text-[10px] text-muted-foreground">Selesai {formatDate(p.completed_at)}</div>
                                                )}
                                            </div>
                                            {p.status === 'on_progress' && <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700">Sedang dikerjakan</span>}
                                            {p.status === 'skipped' && <span className="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500">Dilewati</span>}
                                        </li>
                                    ))}
                                </ol>
                            </div>

                            {/* Branded Contacts */}
                            <div className="rounded-2xl border bg-white p-6 shadow-sm flex flex-col items-center text-center space-y-4">
                                <div className="space-y-1">
                                    <h4 className="text-sm font-bold text-slate-800">Ada Pertanyaan Mengenai PO Ini?</h4>
                                    <p className="text-xs text-muted-foreground">Silakan hubungi customer service <strong>{order.brand?.nama_brand}</strong> di bawah ini:</p>
                                </div>
                                <div className="flex flex-wrap items-center justify-center gap-3 w-full sm:w-auto">
                                    {whatsappUrl && (
                                        <a
                                            href={whatsappUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 px-5 py-2.5 h-10 shadow-sm transition-all hover:-translate-y-0.5 duration-200"
                                        >
                                            <svg className="h-5 w-5 fill-current text-white" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12.031 2a9.965 9.965 0 0 0-9.96 9.96c0 1.603.38 3.123 1.106 4.479L2 22l5.733-1.503a9.92 9.92 0 0 0 4.298 1.002h.004a9.969 9.969 0 0 0 9.965-9.96c-.001-2.66-1.039-5.161-2.925-7.047A9.924 9.924 0 0 0 12.03 2Zm6.417 14.185c-.279.782-1.408 1.433-1.954 1.547-.488.102-.977.195-2.738-.492-2.257-.88-3.71-3.175-3.824-3.327-.113-.151-.925-1.227-.925-2.33 0-1.104.577-1.644.782-1.87.205-.226.45-.282.602-.282.15 0 .301 0 .432.007.136.006.32.016.49.424.173.418.594 1.45.647 1.557.052.106.088.23.017.371-.07.142-.106.23-.212.353-.106.124-.223.277-.318.371-.106.103-.217.215-.094.425.122.21.54 1.001 1.157 1.55.797.708 1.467.927 1.674 1.03.208.103.33.085.45-.053.123-.138.528-.616.67-.827.14-.21.282-.177.472-.106.19.07 1.205.568 1.412.674.208.106.347.16.398.248.053.088.053.513-.173 1.295Z" />
                                            </svg>
                                            Hubungi via WhatsApp
                                        </a>
                                    )}
                                    {instagramUrl && (
                                        <a
                                            href={instagramUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold text-white bg-gradient-to-tr from-yellow-500 via-red-500 to-purple-600 hover:opacity-90 px-5 py-2.5 h-10 shadow-sm transition-all hover:-translate-y-0.5 duration-200"
                                        >
                                            <svg className="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                                            </svg>
                                            Instagram @{order.brand.instagram.replace('@', '')}
                                        </a>
                                    )}
                                    {tiktokUrl && (
                                        <a
                                            href={tiktokUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center gap-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 px-5 py-2.5 h-10 shadow-sm transition-all hover:-translate-y-0.5 duration-200"
                                        >
                                            <svg className="h-4 w-4 fill-current text-white" viewBox="0 0 24 24">
                                                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.02.02.04.04.06.06.01.83.02 1.66.02 2.5-1.04-.37-1.99-1.01-2.73-1.84-.04-.04-.08-.09-.13-.13-.01 2.92-.01 5.84-.02 8.76-.08 1.63-.73 3.23-1.86 4.39-1.42 1.48-3.55 2.19-5.59 1.84-2.22-.35-4.14-1.99-4.73-4.18-.72-2.59.18-5.51 2.27-7.06.94-.71 2.09-1.09 3.28-1.12.01 1.48.01 2.97.02 4.45-.63.07-1.25.35-1.68.83-.56.61-.75 1.48-.5 2.27.27.86.99 1.51 1.87 1.69.96.22 2.04-.15 2.55-.99.27-.45.37-.99.35-1.52-.01-4.72-.01-9.44-.02-14.16z" />
                                            </svg>
                                            TikTok @{order.brand.tiktok.replace('@', '')}
                                        </a>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
