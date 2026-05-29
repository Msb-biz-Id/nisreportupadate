import { Head } from '@inertiajs/react';
import { CheckCircle2, Circle, Clock, AlertTriangle, Package, ShieldCheck } from 'lucide-react';
import { formatDate } from '@/lib/utils';

const STATUS_BADGE = {
    draft: { label: 'Draft', class: 'bg-gray-100 text-gray-700' },
    published: { label: 'Order Diterima', class: 'bg-blue-100 text-blue-700' },
    on_progress: { label: 'Sedang Diproduksi', class: 'bg-amber-100 text-amber-700' },
    selesai_produksi: { label: 'Selesai Produksi', class: 'bg-emerald-100 text-emerald-700' },
    siap_dikirim: { label: 'Siap Dikirim', class: 'bg-cyan-100 text-cyan-700' },
    sudah_dikirim: { label: 'Sudah Dikirim', class: 'bg-violet-100 text-violet-700' },
    delay: { label: 'Terlambat', class: 'bg-red-100 text-red-700' },
    hold: { label: 'Ditahan', class: 'bg-orange-100 text-orange-700' },
};

function ProgressIcon({ status }) {
    if (status === 'selesai') return <CheckCircle2 className="h-5 w-5 text-emerald-500" />;
    if (status === 'on_progress') return <Clock className="h-5 w-5 animate-pulse text-amber-500" />;
    if (status === 'skipped') return <Circle className="h-5 w-5 text-gray-300" />;
    return <Circle className="h-5 w-5 text-gray-300" />;
}

export default function Track({ po_number, found, order }) {
    return (
        <>
            <Head title={`Tracking ${po_number}`} />
            <div className="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 px-4 py-8">
                <div className="mx-auto max-w-2xl">
                    <div className="mb-6 flex items-center justify-center gap-2 text-center">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow">
                            <ShieldCheck className="h-5 w-5" />
                        </div>
                        <div className="text-left">
                            <div className="text-lg font-bold tracking-tight">NISReport</div>
                            <div className="text-xs text-muted-foreground">Tracking PO</div>
                        </div>
                    </div>

                    {!found && (
                        <div className="rounded-2xl border bg-white p-8 text-center shadow-sm">
                            <AlertTriangle className="mx-auto h-12 w-12 text-amber-500" />
                            <h2 className="mt-4 text-xl font-bold">PO Tidak Ditemukan</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Nomor PO <span className="font-mono font-semibold">{po_number}</span> tidak ditemukan atau belum diterbitkan.
                            </p>
                        </div>
                    )}

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
                                    <div className={`rounded-full px-3 py-1 text-xs font-semibold ${STATUS_BADGE[order.status_po]?.class ?? 'bg-gray-100'}`}>
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

                            {/* Ekspedisi & Resi */}
                            {(order.nama_ekspedisi || order.no_resi) && (
                                <div className="rounded-2xl border bg-violet-50 border-violet-200 p-5 shadow-sm">
                                    <div className="mb-3 flex items-center gap-2 font-semibold text-violet-800">
                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" /></svg>
                                        Info Pengiriman
                                    </div>
                                    <div className="grid grid-cols-2 gap-3 text-sm">
                                        {order.nama_ekspedisi && (
                                            <div className="rounded-lg bg-white border border-violet-100 p-3">
                                                <div className="text-xs text-violet-500 font-medium">Ekspedisi</div>
                                                <div className="font-bold text-violet-900 mt-0.5">{order.nama_ekspedisi}</div>
                                            </div>
                                        )}
                                        {order.no_resi && (
                                            <div className="rounded-lg bg-white border border-violet-100 p-3">
                                                <div className="text-xs text-violet-500 font-medium">No. Resi</div>
                                                <div className="font-mono font-bold text-violet-900 mt-0.5 break-all">{order.no_resi}</div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Items */}
                            <div className="rounded-2xl border bg-white p-5 shadow-sm">
                                <div className="mb-3 flex items-center gap-2 font-semibold">
                                    <Package className="h-4 w-4 text-primary" /> Detail Pesanan
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
                                            <ProgressIcon status={p.status} />
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

                            <div className="rounded-2xl border bg-muted/40 p-4 text-center text-xs text-muted-foreground">
                                Punya pertanyaan? Hubungi tim {order.brand?.nama_brand} via WhatsApp / Instagram.
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
