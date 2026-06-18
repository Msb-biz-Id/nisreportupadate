import { Head, Link } from '@inertiajs/react';
import { Printer, Download, X, ChevronLeft } from 'lucide-react';
import { Button } from '@/Components/ui/button';

const chunkArray = (arr, size) => {
    const chunks = [];
    if (!arr) return chunks;
    for (let i = 0; i < arr.length; i += size) {
        chunks.push(arr.slice(i, i + size));
    }
    return chunks;
};

export default function FoPreview({ order, printings, progresses }) {
    const brand = order.brand || {};
    const nonAddonItems = (order.items || []).filter(item => !item.is_addon);
    const grandTotal = nonAddonItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
    const totalAtasan = (order.items || []).reduce((sum, item) => sum + Number(item.jml_atasan || 0), 0) || grandTotal;
    const totalBawahan = (order.items || []).reduce((sum, item) => sum + Number(item.jml_bawahan || 0), 0);

    const printingStr = printings && printings.length > 0 ? printings.join(', ') : '.......';

    const standarSizes = [
        'XS ANAK', 'S ANAK', 'M ANAK', 'L ANAK', 'XL ANAK',
        'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL', '7XL', '8XL', '9XL', '10XL'
    ];

    // Helper to format date
    const formatDateIndo = (dateStr) => {
        if (!dateStr) return '';
        try {
            const date = new Date(dateStr);
            const months = [
                'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
                'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
            ];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
        } catch (e) {
            return dateStr;
        }
    };

    return (
        <>
            <Head title={`FO ${order.no_po} - ${brand.nama_brand || ''}`} />
            
            {/* Toolbar - Hidden when printing */}
            <div className="fixed top-0 left-0 right-0 h-16 bg-slate-900 text-white flex items-center justify-between px-6 z-[9999] shadow-md print:hidden font-sans">
                <div className="flex items-center gap-3">
                    <Button asChild variant="ghost" size="sm" className="text-slate-400 hover:text-white rounded-lg p-2">
                        <Link href={`/orders/${order.id}`}>
                            <ChevronLeft className="h-5 w-5 mr-1" />
                            Kembali ke PO
                        </Link>
                    </Button>
                    <div className="h-4 w-[1px] bg-slate-700"></div>
                    <span className="font-extrabold text-sm tracking-widest text-slate-200">
                        PREVIEW FO: {order.no_po}
                    </span>
                </div>
                
                <div className="flex items-center gap-3">
                    <Button onClick={() => window.print()} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold flex items-center gap-1.5 shadow-sm text-xs">
                        <Printer className="h-4 w-4" />
                        Cetak / Simpan PDF
                    </Button>
                    
                    <Button asChild variant="outline" className="border-slate-700 bg-slate-800 text-slate-200 hover:bg-slate-700 hover:text-white rounded-xl font-bold flex items-center gap-1.5 text-xs">
                        <a href={route('orders.fo.pdf', order.id)} target="_blank" rel="noopener noreferrer">
                            <Download className="h-4 w-4" />
                            Unduh PDF
                        </a>
                    </Button>
                    
                    <Button onClick={() => window.close()} variant="destructive" className="rounded-xl font-bold flex items-center gap-1.5 text-xs">
                        <X className="h-4 w-4" />
                        Tutup
                    </Button>
                </div>
            </div>

            {/* Main Page Container */}
            <div className="min-h-screen bg-slate-100/60 pt-20 pb-12 print:pt-0 print:pb-0 print:bg-white font-mono text-[13px] text-black uppercase tracking-tight select-none">
                
                {/* Paper sheet representation */}
                <div className="mx-auto w-[210mm] min-h-[297mm] bg-white border border-slate-200 p-[15mm] shadow-lg print:border-none print:shadow-none print:p-0 print:m-0 print:w-full">
                    
                    {/* Header - Fixed height simulation */}
                    <div className="border-b border-slate-400 pb-2 mb-4">
                        <div className="flex justify-between items-end">
                            <div className="w-[30%] text-left font-normal text-slate-600 text-[10px]">
                                MESIN PRINT: ....................
                            </div>
                            <div className="w-[40%] text-center font-black text-lg underline">
                                FORMAT ORDER {brand.nama_brand || 'BRAND'}
                            </div>
                            <div className="w-[30%] text-right font-normal text-slate-600 text-[10px]">
                                MESIN PRES: ....................
                            </div>
                        </div>
                    </div>

                    {/* PO Details Block */}
                    <div className="grid grid-cols-12 gap-4 mb-4">
                        {/* Left Column */}
                        <div className="col-span-7 space-y-1">
                            <table className="w-full">
                                <tbody>
                                    <tr>
                                        <td className="w-36 font-black py-0.5">TANGGAL MASUK</td>
                                        <td className="w-4 py-0.5">:</td>
                                        <td className="font-bold py-0.5">{formatDateIndo(order.tanggal_masuk)}</td>
                                    </tr>
                                    <tr className="text-red-600">
                                        <td className="font-black py-0.5">DATELINE</td>
                                        <td className="py-0.5">:</td>
                                        <td className="font-bold py-0.5">{formatDateIndo(order.deadline_customer)}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-black py-0.5">NAMA ORDER</td>
                                        <td className="py-0.5">:</td>
                                        <td className="font-bold py-0.5">{order.nama_po}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-black py-0.5">TOTAL ATASAN</td>
                                        <td className="py-0.5">:</td>
                                        <td className="font-bold py-0.5">{totalAtasan} PCS</td>
                                    </tr>
                                    <tr>
                                        <td className="font-black py-0.5">TOTAL BAWAHAN</td>
                                        <td className="py-0.5">:</td>
                                        <td className="font-bold py-0.5">{totalBawahan > 0 ? `${totalBawahan} PCS` : '....... PCS'}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {/* Right Column Box */}
                        <div className="col-span-5">
                            <div className="border-[2px] border-black p-3 text-center bg-white min-h-[110px] flex flex-col justify-center">
                                <div className="text-xl font-black leading-tight">
                                    {brand.nama_brand || 'BRAND'}
                                </div>
                                <div className="text-[12px] font-bold mt-2 pt-2 border-t border-black">
                                    JENIS PRINTING:<br />
                                    <span className="text-[13px] font-black text-red-600">{printingStr}</span>
                                </div>
                                {order.paket_order && (
                                    <div className="text-[12px] font-bold mt-2 pt-2 border-t border-black">
                                        PAKET ORDER:<br />
                                        <span className="text-[14px] font-black">{order.paket_order.nama}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Catatan Order */}
                    {order.catatan && (
                        <div className="mb-4">
                            <div className="bg-slate-300 font-black text-[13.5px] border border-black border-b-0 px-2.5 py-1.5 text-left">
                                CATATAN ORDER
                            </div>
                            <div className="border border-black px-2.5 py-1.5 font-bold text-[12.5px] bg-white">
                                {order.catatan}
                            </div>
                        </div>
                    )}

                    {/* Keterangan Material */}
                    {nonAddonItems.length > 0 && (
                        <div className="mb-4">
                            <div className="font-black text-[14px] mb-1">KETERANGAN MATERIAL</div>
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse border border-black">
                                    <thead>
                                        <tr className="bg-slate-300 font-bold">
                                            <th className="border border-black p-1.5 text-left w-1/4">JENIS PESANAN</th>
                                            {nonAddonItems.map(item => (
                                                <th key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.varian_label || item.nama_produk}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {/* BAHAN */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">BAHAN</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.bahan_kains_names || item.bahan_kain?.nama || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* JUMLAH ATASAN */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">JUMLAH ATASAN</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.jml_atasan || item.quantity}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* JUMLAH BAWAHAN */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">JUMLAH BAWAHAN</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.jml_bawahan || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* WARNA */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">WARNA</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.warna || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* JENIS LOGO */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">JENIS LOGO</td>
                                            {nonAddonItems.map(item => {
                                                const logoStr = item.logo_names && item.logo_names.length > 0
                                                    ? item.logo_names.join(', ')
                                                    : (item.logo?.nama || '.......');
                                                return (
                                                    <td key={item.id} className="border border-black p-1.5 text-center">
                                                        {logoStr}
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                        {/* JENIS RIB */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">JENIS RIB</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.jenis_rib || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* LIST KERAH */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">LIST KERAH</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.list_kerah || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* LIST LENGAN */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">LIST LENGAN</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.list_lengan || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* LIST SAMPING CELANA */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">LIST SAMPING CELANA</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.list_samping_celana || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* LIST BAWAH CELANA */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">LIST BAWAH CELANA</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.list_bawah_celana || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* TUTUP KERAH */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold bg-slate-100">TUTUP KERAH</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.tutup_kerah || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* Keterangan Jahitan */}
                    {nonAddonItems.length > 0 && (
                        <div className="mb-4">
                            <div className="font-black text-[14px] mb-1">KETERANGAN JAHITAN</div>
                            <div className="overflow-x-auto">
                                <table className="w-full border-collapse border border-black">
                                    <thead>
                                        <tr className="bg-slate-300 font-bold">
                                            <th className="border border-black p-1.5 text-left w-1/4">JAHITAN / DETAIL</th>
                                            {nonAddonItems.map(item => (
                                                <th key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.varian_label || item.nama_produk}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {/* POLA JAHITAN */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold text-left bg-slate-100">POLA JAHITAN</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.pola_jahitan?.nama || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* JAHITAN LIST LENGAN */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold text-left bg-slate-100">JAHITAN LIST LENGAN</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.pola_jahitan_lengan?.nama || item.jahitan_list_lengan || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                        {/* JENIS RESLETING */}
                                        <tr>
                                            <td className="border border-black p-1.5 font-bold text-left bg-slate-100">JENIS RESLETING</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-1.5 text-center">
                                                    {item.resleting?.nama || '.......'}
                                                </td>
                                            ))}
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* Referensi Desain per item */}
                    {nonAddonItems.map(item => {
                        const hasImages = item.gambar_desain || item.ket_atasan || item.ket_bawahan || item.jenis_kerah || item.gambar_kerah || item.gambar_ket_tambahan;
                        if (!hasImages) return null;

                        return (
                            <div key={item.id} className="mt-8 border-t-2 border-dashed border-slate-400 pt-8 print:break-before-page print:pt-4">
                                <div className="border-2 border-black p-1.5 mb-3 bg-white">
                                    <div className="bg-slate-300 font-bold text-[13px] border border-black p-1.5 text-center">
                                        REFERENSI DESAIN {item.nama_produk} {item.varian_label ? `— ${item.varian_label}` : ''}
                                    </div>
                                    
                                    <div className="border border-black border-t-0 p-1 mb-2 bg-white flex justify-center items-center min-h-[200px]">
                                        {item.gambar_desain ? (
                                            <img src={`/storage/${item.gambar_desain}`} className="max-w-full max-h-[500px] object-contain block mx-auto" alt="Desain" />
                                        ) : (
                                            <div className="text-slate-400 font-bold text-center">[ GAMBAR DESAIN BELUM DIUNGGAH ]</div>
                                        )}
                                    </div>

                                    <table className="w-full border-collapse border border-black">
                                        <tbody>
                                            <tr>
                                                <td className="w-1/2 border-r border-black p-2 bg-white text-center">
                                                    <div className="bg-slate-300 font-bold p-1 text-center border border-black mb-1">KETERANGAN ATASAN</div>
                                                    <div className="font-bold py-1">{item.ket_atasan || '.......'}</div>
                                                </td>
                                                <td className="w-1/2 p-2 bg-white text-center">
                                                    <div className="bg-slate-300 font-bold p-1 text-center border border-black mb-1">KETERANGAN BAWAHAN</div>
                                                    <div className="font-bold py-1">{item.ket_bawahan || '.......'}</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {/* Kerah & Tambahan side by side */}
                                {(item.jenis_kerah || item.gambar_kerah || item.gambar_ket_tambahan) && (
                                    <div className="grid grid-cols-2 gap-2 mt-2">
                                        {/* Kerah Box */}
                                        {(item.jenis_kerah || item.gambar_kerah) && (
                                            <div className="border-2 border-black p-1.5 bg-white">
                                                <div className="bg-slate-300 font-bold text-[11px] border border-black p-1 text-center">
                                                    JENIS KERAH: {item.jenis_kerah || '.......'}
                                                </div>
                                                <div className="border border-black border-t-0 p-1 flex justify-center items-center min-h-[140px] bg-white">
                                                    {item.gambar_kerah ? (
                                                        <img src={`/storage/${item.gambar_kerah}`} className="max-w-full max-h-[130px] object-contain block mx-auto" alt="Kerah" />
                                                    ) : (
                                                        <div className="text-slate-400 font-bold text-[11px]">[ GAMBAR KERAH BELUM DIUNGGAH ]</div>
                                                    )}
                                                </div>
                                            </div>
                                        )}

                                        {/* Tambahan Box */}
                                        {item.gambar_ket_tambahan && (
                                            <div className="border-2 border-black p-1.5 bg-white">
                                                <div className="bg-slate-300 font-bold text-[11px] border border-black p-1 text-center">
                                                    KETERANGAN TAMBAHAN
                                                </div>
                                                <div className="border border-black border-t-0 p-1 flex justify-center items-center min-h-[140px] bg-white">
                                                    <img src={`/storage/${item.gambar_ket_tambahan}`} className="max-w-full max-h-[130px] object-contain block mx-auto" alt="Tambahan" />
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })}

                    {/* Namesets List per item */}
                    {nonAddonItems.map(item => {
                        const filledNamesets = (item.namesets || []).filter(ns => 
                            ns.nama_punggung || ns.nomor_punggung ||
                            ns.nama_dada || ns.nomor_dada ||
                            ns.nama_lengan || ns.nomor_lengan ||
                            ns.nama_punggung_2 || ns.nomor_punggung_2 ||
                            ns.size_id || ns.size_label ||
                            ns.size_celana_id || ns.size_celana_label ||
                            ns.keterangan
                        );
                        if (filledNamesets.length === 0) return null;

                        const hasNP = filledNamesets.some(ns => ns.nama_punggung || ns.nomor_punggung);
                        const hasND = filledNamesets.some(ns => ns.nama_dada || ns.nomor_dada);
                        const hasNL = filledNamesets.some(ns => ns.nama_lengan || ns.nomor_lengan);
                        const hasNP2 = filledNamesets.some(ns => ns.nama_punggung_2 || ns.nomor_punggung_2);
                        const hasSA = filledNamesets.some(ns => ns.size_id || ns.size_label);
                        const hasSB = filledNamesets.some(ns => ns.size_celana_id || ns.size_celana_label);
                        const hasKet = filledNamesets.some(ns => ns.keterangan);

                        const cols = [{ type: 'no', label: 'NO.', weight: 5 }];
                        if (hasNP) {
                            cols.push({ type: 'nama_punggung', label: 'NAMA PUNGGUNG', weight: 22, align: 'text-left pl-1.5' });
                            cols.push({ type: 'no_punggung', label: 'NO. PUNGGUNG', weight: 8 });
                        }
                        if (hasND) {
                            cols.push({ type: 'nama_dada', label: 'NAMA DADA', weight: 16, align: 'text-left pl-1.5' });
                            cols.push({ type: 'no_dada', label: 'NO. DADA', weight: 8 });
                        }
                        if (hasNL) {
                            cols.push({ type: 'nama_lengan', label: 'NAMA LENGAN', weight: 16, align: 'text-left pl-1.5' });
                            cols.push({ type: 'no_lengan', label: 'NO. LENGAN', weight: 8 });
                        }
                        if (hasNP2) {
                            cols.push({ type: 'nama_punggung_2', label: 'NAMA PUNGGUNG 2', weight: 22, align: 'text-left pl-1.5' });
                            cols.push({ type: 'no_punggung_2', label: 'NO. PUNGGUNG 2', weight: 8 });
                        }
                        if (hasSA) cols.push({ type: 'size', label: 'SIZE', weight: 8 });
                        if (hasSB) cols.push({ type: 'size_celana', label: 'SIZE CELANA', weight: 9 });
                        if (hasKet) cols.push({ type: 'keterangan', label: 'KETERANGAN', weight: 20, align: 'text-left pl-1.5' });

                        const totalWeight = cols.reduce((sum, col) => sum + col.weight, 0);
                        const finalCols = cols.map(col => ({
                            ...col,
                            pct: ((col.weight / totalWeight) * 100).toFixed(1)
                        }));

                        const useDense = finalCols.length > 7;

                        // Size counts
                        const sizeAtasanRaw = {};
                        const sizeBawahanRaw = {};
                        filledNamesets.forEach(ns => {
                            if (ns.size_id || ns.size_label) {
                                const sz = ns.size ? ns.size.ukuran : ns.size_label?.split('-').pop()?.trim();
                                if (sz) sizeAtasanRaw[sz] = (sizeAtasanRaw[sz] || 0) + 1;
                            }
                            if (ns.size_celana_id || ns.size_celana_label) {
                                const sz = ns.size_celana ? ns.size_celana.ukuran : ns.size_celana_label?.split('-').pop()?.trim();
                                if (sz) sizeBawahanRaw[sz] = (sizeBawahanRaw[sz] || 0) + 1;
                            }
                        });

                        const sizeAtasanRecap = [];
                        standarSizes.forEach(s => {
                            if (sizeAtasanRaw[s]) sizeAtasanRecap.push({ size: s, count: sizeAtasanRaw[s] });
                        });
                        Object.keys(sizeAtasanRaw).forEach(s => {
                            if (!standarSizes.includes(s)) sizeAtasanRecap.push({ size: s, count: sizeAtasanRaw[s] });
                        });

                        const sizeBawahanRecap = [];
                        standarSizes.forEach(s => {
                            if (sizeBawahanRaw[s]) sizeBawahanRecap.push({ size: s, count: sizeBawahanRaw[s] });
                        });
                        Object.keys(sizeBawahanRaw).forEach(s => {
                            if (!standarSizes.includes(s)) sizeBawahanRecap.push({ size: s, count: sizeBawahanRaw[s] });
                        });

                        return (
                            <div key={item.id} className="mt-8 border-t-2 border-dashed border-slate-400 pt-8 print:break-before-page print:pt-4">
                                <div className="bg-slate-300 font-bold text-[12.5px] border border-black p-1.5 text-center">
                                    DATA PESANAN {item.nama_produk} {item.varian_label ? `— ${item.varian_label}` : ''}
                                </div>
                                <table className="w-full table-fixed border-collapse border border-black border-t-0 text-center">
                                    <colgroup>
                                        {finalCols.map((col, idx) => (
                                            <col key={idx} style={{ width: `${col.pct}%` }} />
                                        ))}
                                    </colgroup>
                                    <thead>
                                        <tr className="bg-slate-300 border-b border-black font-bold">
                                            {finalCols.map((col, idx) => (
                                                <th key={idx} className={`border-r border-black p-1 ${col.align || ''} ${useDense ? 'text-[10px] py-0.5' : 'text-[11.5px] py-1'}`}>
                                                    {col.label}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filledNamesets.map((ns, idx) => (
                                            <tr key={ns.id} className="border-b border-black bg-white hover:bg-slate-50/50">
                                                {finalCols.map((col, cidx) => {
                                                    let val = '';
                                                    if (col.type === 'no') val = `${idx + 1}.`;
                                                    else if (col.type === 'nama_punggung') val = ns.nama_punggung || '';
                                                    else if (col.type === 'no_punggung') val = ns.nomor_punggung || '';
                                                    else if (col.type === 'nama_dada') val = ns.nama_dada || '';
                                                    else if (col.type === 'no_dada') val = ns.nomor_dada || '';
                                                    else if (col.type === 'nama_lengan') val = ns.nama_lengan || '';
                                                    else if (col.type === 'no_lengan') val = ns.nomor_lengan || '';
                                                    else if (col.type === 'nama_punggung_2') val = ns.nama_punggung_2 || '';
                                                    else if (col.type === 'no_punggung_2') val = ns.nomor_punggung_2 || '';
                                                    else if (col.type === 'size') val = ns.size ? ns.size.ukuran : ns.size_label?.split('-').pop()?.trim() || '';
                                                    else if (col.type === 'size_celana') val = ns.size_celana ? ns.size_celana.ukuran : ns.size_celana_label?.split('-').pop()?.trim() || '';
                                                    else if (col.type === 'keterangan') val = ns.keterangan || '';

                                                    return (
                                                        <td key={cidx} className={`border-r border-black p-1 ${col.align || ''} ${useDense ? 'text-[10px] py-0.5' : 'text-[11.5px] py-1'}`}>
                                                            {val}
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>

                                {/* Rekap Size */}
                                <div className="mt-4 text-center">
                                    <div className="font-bold text-[12px] underline mb-1.5">JUMLAH KESELURUHAN: {filledNamesets.length} PCS</div>
                                    
                                    {sizeAtasanRecap.length > 0 && (
                                        <div className="mb-2">
                                            {sizeBawahanRecap.length > 0 && <div className="text-[10.5px] font-bold mb-1">REKAP SIZE ATASAN</div>}
                                            {chunkArray(sizeAtasanRecap, 10).map((chunk, cidx) => (
                                                <table key={cidx} className="mx-auto border-collapse border-2 border-black text-center mb-1.5">
                                                    <thead>
                                                        <tr className="bg-slate-300 font-bold border-b border-black">
                                                            {chunk.map((rec, idx) => (
                                                                <th key={idx} className="border-r border-black px-3 py-1 font-bold text-[11px]">{rec.size}</th>
                                                            ))}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr className="bg-white">
                                                            {chunk.map((rec, idx) => (
                                                                <td key={idx} className="border-r border-black px-3 py-1 text-[11px]">{rec.count}</td>
                                                            ))}
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            ))}
                                        </div>
                                    )}

                                    {sizeBawahanRecap.length > 0 && (
                                        <div className="mt-3">
                                            <div className="text-[10.5px] font-bold mb-1">REKAP SIZE BAWAHAN</div>
                                            {chunkArray(sizeBawahanRecap, 10).map((chunk, cidx) => (
                                                <table key={cidx} className="mx-auto border-collapse border-2 border-black text-center mb-1.5">
                                                    <thead>
                                                        <tr className="bg-slate-300 font-bold border-b border-black">
                                                            {chunk.map((rec, idx) => (
                                                                <th key={idx} className="border-r border-black px-3 py-1 font-bold text-[11px]">{rec.size}</th>
                                                            ))}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr className="bg-white">
                                                            {chunk.map((rec, idx) => (
                                                                <td key={idx} className="border-r border-black px-3 py-1 text-[11px]">{rec.count}</td>
                                                            ))}
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}

                    {/* Checklist Produksi */}
                    {progresses && progresses.length > 0 && nonAddonItems.length > 0 && (
                        <div className="mt-8 border-t-2 border-dashed border-slate-400 pt-8 print:break-before-page print:pt-4">
                            <div className="text-center font-black text-[14.5px] underline mb-3 border-b-2 border-black pb-1">
                                CHECKLIST PRODUKSI — {order.nama_po}
                            </div>
                            <table className="w-full border-collapse border-2 border-black text-left">
                                <thead>
                                    <tr className="bg-slate-300 border-b border-black font-bold">
                                        <th className="border border-black p-1.5 text-center w-10">NO</th>
                                        <th className="border border-black p-1.5 w-60">PROSES</th>
                                        {nonAddonItems.map((item, idx) => (
                                            <th key={item.id} className="border border-black p-1.5 text-center text-[10.5px]">
                                                NAMA {idx + 1}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {/* Manual static rows: ADMIN, DESAIN, FORMAT ORDER */}
                                    {['ADMIN', 'DESAIN', 'FORMAT ORDER'].map((label, idx) => (
                                        <tr key={`manual-${idx}`} className={idx % 2 === 0 ? 'bg-slate-50/40' : 'bg-white'}>
                                            <td className="border border-black p-2 text-center font-bold">{idx + 1}</td>
                                            <td className="border border-black p-2 font-black text-[12px]">{label}</td>
                                            {nonAddonItems.map(item => (
                                                <td key={item.id} className="border border-black p-2 text-center">&nbsp;</td>
                                            ))}
                                        </tr>
                                    ))}
                                    {/* Dynamic progress rows from database (SETTING, etc.) */}
                                    {progresses.map((prog, idx) => {
                                        const rowNum = 3 + idx + 1;
                                        const totalIdx = 3 + idx;
                                        return (
                                            <tr key={prog.id} className={totalIdx % 2 === 0 ? 'bg-slate-50/40' : 'bg-white'}>
                                                <td className="border border-black p-2 text-center font-bold">{rowNum}</td>
                                                <td className="border border-black p-2 font-black text-[12px]">{prog.nama_progress}</td>
                                                {nonAddonItems.map(item => (
                                                    <td key={item.id} className="border border-black p-2 text-center">&nbsp;</td>
                                                ))}
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                            <div className="mt-3 font-bold text-[10px] text-slate-500 text-right">
                                DICETAK: {new Date().toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })} · {brand.nama_brand || ''}
                            </div>
                        </div>
                    )}

                    {/* Lampiran Data Pesanan */}
                    <div className="mt-8 border-t-2 border-dashed border-slate-400 pt-8 print:break-before-page print:pt-4">
                        <div className="text-center font-black text-[14.5px] underline mb-3 border-b-2 border-black pb-1 text-left">
                            LAMPIRAN: DATA PESANAN
                        </div>
                        
                        {nonAddonItems.map(item => {
                            const filledNamesets = (item.namesets || []).filter(ns => 
                                ns.nama_punggung || ns.nomor_punggung ||
                                ns.nama_dada || ns.nomor_dada ||
                                ns.nama_lengan || ns.nomor_lengan ||
                                ns.nama_punggung_2 || ns.nomor_punggung_2 ||
                                ns.size_id || ns.size_label ||
                                ns.size_celana_id || ns.size_celana_label ||
                                ns.keterangan
                            );
                            if (filledNamesets.length === 0) return null;

                            const hasNP = filledNamesets.some(ns => ns.nama_punggung || ns.nomor_punggung);
                            const hasND = filledNamesets.some(ns => ns.nama_dada || ns.nomor_dada);
                            const hasNL = filledNamesets.some(ns => ns.nama_lengan || ns.nomor_lengan);
                            const hasNP2 = filledNamesets.some(ns => ns.nama_punggung_2 || ns.nomor_punggung_2);
                            const hasSA = filledNamesets.some(ns => ns.size_id || ns.size_label);
                            const hasSB = filledNamesets.some(ns => ns.size_celana_id || ns.size_celana_label);
                            const hasKet = filledNamesets.some(ns => ns.keterangan);

                            const cols = [{ type: 'no', label: 'NO.', weight: 5 }];
                            if (hasNP) {
                                cols.push({ type: 'nama_punggung', label: 'NAMA PUNGGUNG', weight: 22, align: 'text-left pl-1.5' });
                                cols.push({ type: 'no_punggung', label: 'NO. PUNGGUNG', weight: 8 });
                            }
                            if (hasND) {
                                cols.push({ type: 'nama_dada', label: 'NAMA DADA', weight: 16, align: 'text-left pl-1.5' });
                                cols.push({ type: 'no_dada', label: 'NO. DADA', weight: 8 });
                            }
                            if (hasNL) {
                                cols.push({ type: 'nama_lengan', label: 'NAMA LENGAN', weight: 16, align: 'text-left pl-1.5' });
                                cols.push({ type: 'no_lengan', label: 'NO. LENGAN', weight: 8 });
                            }
                            if (hasNP2) {
                                cols.push({ type: 'nama_punggung_2', label: 'NAMA PUNGGUNG 2', weight: 22, align: 'text-left pl-1.5' });
                                cols.push({ type: 'no_punggung_2', label: 'NO. PUNGGUNG 2', weight: 8 });
                            }
                            if (hasSA) cols.push({ type: 'size', label: 'SIZE', weight: 8 });
                            if (hasSB) cols.push({ type: 'size_celana', label: 'SIZE CELANA', weight: 9 });
                            if (hasKet) cols.push({ type: 'keterangan', label: 'KETERANGAN', weight: 20, align: 'text-left pl-1.5' });

                            const totalWeight = cols.reduce((sum, col) => sum + col.weight, 0);
                            const finalCols = cols.map(col => ({
                                ...col,
                                pct: ((col.weight / totalWeight) * 100).toFixed(1)
                            }));

                            const useDense = finalCols.length > 7;

                            return (
                                <div key={item.id} className="mb-6">
                                    <div className="font-bold text-[11px] mb-1">
                                        DATA PESANAN: {item.nama_produk} {item.varian_label ? `— ${item.varian_label}` : ''}
                                    </div>
                                    <table className="w-full table-fixed border-collapse border border-black text-center mb-3">
                                        <colgroup>
                                            {finalCols.map((col, idx) => (
                                                <col key={idx} style={{ width: `${col.pct}%` }} />
                                            ))}
                                        </colgroup>
                                        <thead>
                                            <tr className="bg-slate-300 border-b border-black font-bold">
                                                {finalCols.map((col, idx) => (
                                                    <th key={idx} className={`border-r border-black p-1 ${col.align || ''} ${useDense ? 'text-[10px] py-0.5' : 'text-[11.5px] py-1'}`}>
                                                        {col.label}
                                                    </th>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {filledNamesets.map((ns, idx) => (
                                                <tr key={ns.id} className="border-b border-black bg-white hover:bg-slate-50/50">
                                                    {finalCols.map((col, cidx) => {
                                                        let val = '';
                                                        if (col.type === 'no') val = `${idx + 1}.`;
                                                        else if (col.type === 'nama_punggung') val = ns.nama_punggung || '.......';
                                                        else if (col.type === 'no_punggung') val = ns.nomor_punggung || '.......';
                                                        else if (col.type === 'nama_dada') val = ns.nama_dada || '';
                                                        else if (col.type === 'no_dada') val = ns.nomor_dada || '';
                                                        else if (col.type === 'nama_lengan') val = ns.nama_lengan || '.......';
                                                        else if (col.type === 'no_lengan') val = ns.nomor_lengan || '.......';
                                                        else if (col.type === 'nama_punggung_2') val = ns.nama_punggung_2 || '.......';
                                                        else if (col.type === 'no_punggung_2') val = ns.nomor_punggung_2 || '.......';
                                                        else if (col.type === 'size') val = ns.size ? ns.size.ukuran : ns.size_label?.split('-').pop()?.trim() || '.......';
                                                        else if (col.type === 'size_celana') val = ns.size_celana ? ns.size_celana.ukuran : ns.size_celana_label?.split('-').pop()?.trim() || '.......';
                                                        else if (col.type === 'keterangan') val = ns.keterangan || '.......';

                                                        return (
                                                            <td key={cidx} className={`border-r border-black p-1 ${col.align || ''} ${useDense ? 'text-[10px] py-0.5' : 'text-[11.5px] py-1'}`}>
                                                                {val}
                                                            </td>
                                                        );
                                                    })}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </>
    );
}
