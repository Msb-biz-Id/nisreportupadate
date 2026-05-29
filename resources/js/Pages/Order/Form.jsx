import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Save, Plus, Trash2, ChevronDown, ChevronUp, Settings2, Users, CreditCard, ClipboardPaste, Package2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import ImageUploader from '@/Components/ImageUploader';
import { formatRupiah } from '@/lib/utils';

const NONE = '__none__';
const ACCENT_COLORS = ['red', 'blue', 'emerald', 'amber', 'purple', 'pink', 'teal'];

function newItem() {
    return {
        product_id: '',
        nama_produk: '',
        varian_label: '',
        quantity: 1,
        harga_satuan: 0,
        bahan_kain_id: '',
        jenis_setelan: '',
        logo_id: '',
        printing_id: '',
        resleting_id: '',
        pola_jahitan_lengan_id: '',
        pola_jahitan_kerah_id: '',
        pola_jahitan_bawah_id: '',
        pola_jahitan_pundak_id: '',
        warna: '',
        jenis_kerah: '',
        catatan: '',
        gambar_desain: '',
        gambar_kerah: '',
        namesets: [],
    };
}

function newNameset() {
    return { nama_punggung: '', nomor_punggung: '', size_id: '', size_label: '', keterangan: '' };
}

function newPayment() {
    return { payment_type: 'dp', amount: 0, payment_date: new Date().toISOString().slice(0, 10), bank_id: '', notes: '' };
}

function parseTsv(text, sizes) {
    const rows = text.trim().split('\n').filter(Boolean);
    const result = [];
    for (const row of rows) {
        const cols = row.split('\t').map((c) => c.trim());
        const [nama = '', nomor = '', ukuranRaw = '', keterangan = ''] = cols;
        const needle = ukuranRaw.toLowerCase();
        const matched = sizes.find(
            (s) =>
                s.ukuran?.toLowerCase() === needle ||
                `${s.kategori_size} - ${s.ukuran}`.toLowerCase() === needle,
        );
        result.push({
            nama_punggung: nama,
            nomor_punggung: nomor,
            size_id: matched?.id ?? '',
            size_label: matched ? `${matched.kategori_size} - ${matched.ukuran}` : ukuranRaw,
            keterangan,
        });
    }
    return result;
}

function FieldLabel({ children }) {
    return <p className="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">{children}</p>;
}

function SectionHeader({ children }) {
    return (
        <h2 className="text-sm font-black text-slate-800 border-b-2 border-slate-200 pb-2 mb-4 uppercase tracking-wide">
            {children}
        </h2>
    );
}

function PasteNamesetDialog({ open, onClose, onConfirm, sizes }) {
    const [text, setText] = useState('');
    const preview = useMemo(() => (text.trim() ? parseTsv(text, sizes) : []), [text, sizes]);

    function handleConfirm() {
        if (!preview.length) return;
        onConfirm(preview);
        setText('');
        onClose();
    }

    return (
        <Dialog open={open} onOpenChange={(v) => { if (!v) { setText(''); onClose(); } }}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 uppercase text-sm font-black tracking-wide">
                        <ClipboardPaste className="h-4 w-4" /> Paste Nameset dari Excel
                    </DialogTitle>
                </DialogHeader>
                <div className="space-y-3">
                    <p className="text-xs text-muted-foreground">
                        Copy kolom dari Excel lalu paste di sini. Format: <span className="font-mono bg-muted px-1 rounded">Nama Punggung → No. Punggung → Ukuran → Keterangan</span>
                    </p>
                    <textarea
                        className="w-full h-36 rounded-md border bg-background px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                        placeholder={"Ahmad\t7\tS\t\nBudi\t10\tM\tLengan panjang"}
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        autoFocus
                    />
                    {preview.length > 0 && (
                        <div className="rounded-md border overflow-auto max-h-48">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-gray-100">
                                        <TableHead className="text-xs font-bold uppercase">Nama</TableHead>
                                        <TableHead className="text-xs font-bold uppercase">No.</TableHead>
                                        <TableHead className="text-xs font-bold uppercase">Ukuran</TableHead>
                                        <TableHead className="text-xs font-bold uppercase">Keterangan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {preview.map((r, i) => (
                                        <TableRow key={i}>
                                            <TableCell className="text-xs font-medium">{r.nama_punggung || '—'}</TableCell>
                                            <TableCell className="text-xs font-bold">{r.nomor_punggung || '—'}</TableCell>
                                            <TableCell className="text-xs">
                                                {r.size_id
                                                    ? <span className="text-emerald-600 font-bold">{r.size_label}</span>
                                                    : <span className="text-orange-500">{r.size_label || '—'} (tidak cocok)</span>
                                                }
                                            </TableCell>
                                            <TableCell className="text-xs">{r.keterangan || '—'}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </div>
                <DialogFooter>
                    <Button variant="outline" size="sm" onClick={() => { setText(''); onClose(); }}>Batal</Button>
                    <Button size="sm" disabled={!preview.length} onClick={handleConfirm}>
                        Tambah {preview.length} baris
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ItemCard({ index, item, masters, onChange, onRemove }) {
    const [specOpen, setSpecOpen] = useState(false);
    const [namesetOpen, setNamesetOpen] = useState(true);
    const [pasteOpen, setPasteOpen] = useState(false);
    const color = ACCENT_COLORS[index % ACCENT_COLORS.length];

    function patch(field, value) {
        const next = { ...item, [field]: value };
        if (field === 'product_id') {
            const p = masters.produk.find((x) => x.id === value);
            if (p) {
                next.nama_produk = p.nama;
                next.harga_satuan = Number(p.harga) || 0;
            }
        }
        onChange(index, next);
    }

    function addNameset() {
        onChange(index, { ...item, namesets: [...item.namesets, newNameset()] });
    }
    function removeNameset(i) {
        onChange(index, { ...item, namesets: item.namesets.filter((_, idx) => idx !== i) });
    }
    function patchNameset(i, field, value) {
        const next = [...item.namesets];
        next[i] = { ...next[i], [field]: value };
        if (field === 'size_id') {
            const s = masters.sizes.find((x) => x.id === value);
            next[i].size_label = s ? `${s.kategori_size} - ${s.ukuran}` : '';
        }
        onChange(index, { ...item, namesets: next });
    }

    const subtotal = (Number(item.quantity) || 0) * (Number(item.harga_satuan) || 0);
    const totalPcs = item.namesets.length;

    const polaByJenis = useMemo(() => {
        const groups = {};
        for (const p of masters.pola_jahitans) {
            groups[p.jenis_pola] = groups[p.jenis_pola] || [];
            groups[p.jenis_pola].push(p);
        }
        return groups;
    }, [masters.pola_jahitans]);

    function polaSelect(jenis, fieldKey) {
        const opts = polaByJenis[jenis] || [];
        return (
            <div className="flex flex-col">
                <FieldLabel>Jahitan {jenis}</FieldLabel>
                <Select value={item[fieldKey] || NONE} onValueChange={(v) => patch(fieldKey, v === NONE ? '' : v)}>
                    <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                        {opts.map((p) => (<SelectItem key={p.id} value={p.id}>{p.nama}</SelectItem>))}
                    </SelectContent>
                </Select>
            </div>
        );
    }

    const colorMap = {
        red: { border: 'border-t-red-500', bg: 'bg-red-50', text: 'text-red-800', badge: 'bg-red-100 text-red-800', headerBg: 'bg-red-50 border-red-200', chevron: 'text-red-600' },
        blue: { border: 'border-t-blue-500', bg: 'bg-blue-50', text: 'text-blue-800', badge: 'bg-blue-100 text-blue-800', headerBg: 'bg-blue-50 border-blue-200', chevron: 'text-blue-600' },
        emerald: { border: 'border-t-emerald-500', bg: 'bg-emerald-50', text: 'text-emerald-800', badge: 'bg-emerald-100 text-emerald-800', headerBg: 'bg-emerald-50 border-emerald-200', chevron: 'text-emerald-600' },
        amber: { border: 'border-t-amber-500', bg: 'bg-amber-50', text: 'text-amber-800', badge: 'bg-amber-100 text-amber-800', headerBg: 'bg-amber-50 border-amber-200', chevron: 'text-amber-600' },
        purple: { border: 'border-t-purple-500', bg: 'bg-purple-50', text: 'text-purple-800', badge: 'bg-purple-100 text-purple-800', headerBg: 'bg-purple-50 border-purple-200', chevron: 'text-purple-600' },
        pink: { border: 'border-t-pink-500', bg: 'bg-pink-50', text: 'text-pink-800', badge: 'bg-pink-100 text-pink-800', headerBg: 'bg-pink-50 border-pink-200', chevron: 'text-pink-600' },
        teal: { border: 'border-t-teal-500', bg: 'bg-teal-50', text: 'text-teal-800', badge: 'bg-teal-100 text-teal-800', headerBg: 'bg-teal-50 border-teal-200', chevron: 'text-teal-600' },
    };
    const c = colorMap[color];

    return (
        <div className={`bg-white border-2 border-slate-300 rounded-2xl overflow-hidden shadow-xl border-t-4 ${c.border}`}>
            {/* Module Header */}
            <div className="bg-slate-800 p-4 flex justify-between items-center">
                <div className="flex items-center gap-3 flex-1 min-w-0">
                    <span className="text-white font-black text-sm uppercase tracking-widest whitespace-nowrap">
                        PRODUK #{index + 1}
                    </span>
                    {item.nama_produk && (
                        <span className="text-slate-300 text-xs font-bold uppercase truncate">
                            — {item.nama_produk}
                            {item.varian_label && ` (${item.varian_label})`}
                        </span>
                    )}
                    {totalPcs > 0 && (
                        <span className="ml-auto bg-red-600 text-white text-xs font-black px-2 py-0.5 rounded-full whitespace-nowrap">
                            {totalPcs} PCS
                        </span>
                    )}
                </div>
                <button
                    type="button"
                    onClick={() => onRemove(index)}
                    className="ml-4 text-red-400 hover:text-red-300 bg-slate-700 hover:bg-slate-600 rounded p-1.5 transition"
                    title="Hapus Produk"
                >
                    <Trash2 className="h-4 w-4" />
                </button>
            </div>

            <div className="p-5 space-y-5 bg-slate-50">
                {/* Info Dasar Produk */}
                <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <SectionHeader>Identitas Produk</SectionHeader>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-12">
                        <div className="sm:col-span-5">
                            <FieldLabel>Produk dari Master</FieldLabel>
                            <Select value={item.product_id || NONE} onValueChange={(v) => patch('product_id', v === NONE ? '' : v)}>
                                <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="Pilih produk (atau isi nama manual)" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Manual —</SelectItem>
                                    {masters.produk.map((p) => (
                                        <SelectItem key={p.id} value={p.id}>{p.nama} — {formatRupiah(p.harga)}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="sm:col-span-4">
                            <FieldLabel>Nama Produk <span className="text-red-500">*</span></FieldLabel>
                            <Input value={item.nama_produk} onChange={(e) => patch('nama_produk', e.target.value)} className="h-8 text-xs font-bold uppercase" />
                        </div>
                        <div className="sm:col-span-3">
                            <FieldLabel>Varian / Label</FieldLabel>
                            <Input value={item.varian_label} onChange={(e) => patch('varian_label', e.target.value)} placeholder="Pemain Utama / Cadangan" className="h-8 text-xs uppercase" />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 mt-3">
                        <div>
                            <FieldLabel>Qty <span className="text-red-500">*</span></FieldLabel>
                            <Input type="number" min={1} value={item.quantity} onChange={(e) => patch('quantity', Number(e.target.value))} className="h-8 text-xs font-bold" />
                        </div>
                        <div>
                            <FieldLabel>Harga Satuan</FieldLabel>
                            <Input type="number" min={0} value={item.harga_satuan} onChange={(e) => patch('harga_satuan', Number(e.target.value))} className="h-8 text-xs" />
                        </div>
                        <div className="col-span-2 flex items-end justify-end">
                            <div className="rounded-lg border-2 border-slate-200 bg-slate-100 px-4 py-1.5 text-right">
                                <p className="text-[10px] font-bold text-slate-500 uppercase">Subtotal</p>
                                <p className="font-mono font-black text-sm text-slate-800">{formatRupiah(subtotal)}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Spesifikasi (collapsible) */}
                <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <button
                        type="button"
                        onClick={() => setSpecOpen((v) => !v)}
                        className="w-full flex justify-between items-center p-4 text-left hover:bg-slate-50 transition"
                    >
                        <span className="flex items-center gap-2 text-sm font-black text-slate-700 uppercase tracking-wide">
                            <Settings2 className="h-4 w-4 text-slate-500" />
                            Spesifikasi Produk
                        </span>
                        {specOpen ? <ChevronUp className="h-4 w-4 text-slate-500" /> : <ChevronDown className="h-4 w-4 text-slate-500" />}
                    </button>

                    {specOpen && (
                        <div className="border-t border-slate-200 p-4 space-y-4">
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <div className="flex flex-col">
                                    <FieldLabel>Bahan Kain</FieldLabel>
                                    <Select value={item.bahan_kain_id || NONE} onValueChange={(v) => patch('bahan_kain_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            {masters.bahan_kains.map((b) => (<SelectItem key={b.id} value={b.id}>{b.nama}</SelectItem>))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Jenis Setelan</FieldLabel>
                                    <Select value={item.jenis_setelan || NONE} onValueChange={(v) => patch('jenis_setelan', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            <SelectItem value="stell">Stell (Atasan + Bawahan)</SelectItem>
                                            <SelectItem value="non_stell">Non-Stell</SelectItem>
                                            <SelectItem value="atasan_saja">Atasan Saja</SelectItem>
                                            <SelectItem value="bawahan_saja">Bawahan Saja</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Warna</FieldLabel>
                                    <Input value={item.warna} onChange={(e) => patch('warna', e.target.value)} className="h-8 text-xs uppercase" placeholder="Merah / Biru / Mix" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Logo</FieldLabel>
                                    <Select value={item.logo_id || NONE} onValueChange={(v) => patch('logo_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            {masters.logos.map((l) => (<SelectItem key={l.id} value={l.id}>{l.nama}</SelectItem>))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Jenis Printing</FieldLabel>
                                    <Select value={item.printing_id || NONE} onValueChange={(v) => patch('printing_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            {masters.printings.map((l) => (<SelectItem key={l.id} value={l.id}>{l.nama}</SelectItem>))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Resleting</FieldLabel>
                                    <Select value={item.resleting_id || NONE} onValueChange={(v) => patch('resleting_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            {masters.resletings.map((l) => (<SelectItem key={l.id} value={l.id}>{l.nama}</SelectItem>))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="border-t border-slate-100 pt-3">
                                <p className="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-100 p-1.5 rounded text-center mb-3">Keterangan Jahitan</p>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {polaSelect('Lengan', 'pola_jahitan_lengan_id')}
                                    {polaSelect('Kerah', 'pola_jahitan_kerah_id')}
                                    {polaSelect('Bawah', 'pola_jahitan_bawah_id')}
                                    {polaSelect('Pundak', 'pola_jahitan_pundak_id')}
                                </div>
                            </div>

                            <div className="border-t border-slate-100 pt-3">
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div className="flex flex-col">
                                        <FieldLabel>Jenis Kerah (catatan)</FieldLabel>
                                        <Input value={item.jenis_kerah} onChange={(e) => patch('jenis_kerah', e.target.value)} className="h-8 text-xs uppercase" />
                                    </div>
                                    <div className="flex flex-col sm:col-span-2">
                                        <FieldLabel>Catatan Item</FieldLabel>
                                        <Textarea value={item.catatan} onChange={(e) => patch('catatan', e.target.value)} rows={2} className="text-xs resize-none" />
                                    </div>
                                </div>
                            </div>

                            <div className="border-t border-slate-100 pt-3">
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <FieldLabel>Referensi Desain</FieldLabel>
                                        <ImageUploader value={item.gambar_desain || null} onChange={(p) => patch('gambar_desain', p || '')} purpose="orders" aspect={4 / 3} />
                                    </div>
                                    <div>
                                        <FieldLabel>Referensi Kerah</FieldLabel>
                                        <ImageUploader value={item.gambar_kerah || null} onChange={(p) => patch('gambar_kerah', p || '')} purpose="orders" aspect={1} />
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Nameset (collapsible) */}
                <div className="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <div
                        className={`${c.headerBg} border-b p-3.5 flex flex-col sm:flex-row gap-3 justify-between items-center cursor-pointer select-none`}
                        onClick={() => setNamesetOpen((v) => !v)}
                    >
                        <div className="flex items-center gap-2">
                            {namesetOpen ? <ChevronUp className={`h-4 w-4 ${c.chevron}`} /> : <ChevronDown className={`h-4 w-4 ${c.chevron}`} />}
                            <h3 className={`font-black text-sm ${c.text} uppercase tracking-widest`}>
                                <Users className="h-4 w-4 inline mr-1" />
                                Nameset & Ukuran
                                <span className={`ml-2 ${c.badge} px-2 py-0.5 rounded text-xs`}>{totalPcs} PCS</span>
                            </h3>
                        </div>
                        <div className="flex flex-wrap gap-2 justify-end" onClick={(e) => e.stopPropagation()}>
                            <button
                                type="button"
                                onClick={() => setPasteOpen(true)}
                                className="flex items-center gap-1 bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 px-3 py-1.5 rounded-md text-xs font-bold transition shadow-sm"
                            >
                                <ClipboardPaste className="h-3.5 w-3.5" /> Paste Data
                            </button>
                            <button
                                type="button"
                                onClick={() => { setNamesetOpen(true); addNameset(); }}
                                className="flex items-center gap-1 bg-slate-800 text-white hover:bg-slate-700 px-3 py-1.5 rounded-md text-xs font-bold transition shadow-sm"
                            >
                                <Plus className="h-3.5 w-3.5" /> Tambah Baris
                            </button>
                        </div>
                    </div>

                    {namesetOpen && (
                        <div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider">
                                            <th className="p-3 border-b font-bold w-10 text-center">No</th>
                                            <th className="p-3 border-b font-bold">Nama Punggung</th>
                                            <th className="p-3 border-b font-bold w-28 text-center">No. Punggung</th>
                                            <th className="p-3 border-b font-bold w-40 text-center">Size</th>
                                            <th className="p-3 border-b font-bold">Keterangan</th>
                                            <th className="p-3 border-b font-bold w-10 text-center">X</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {item.namesets.length === 0 && (
                                            <tr>
                                                <td colSpan={6} className="p-6 text-center text-xs text-slate-400 italic">
                                                    Belum ada nameset. Klik "Tambah Baris" untuk mulai.
                                                </td>
                                            </tr>
                                        )}
                                        {item.namesets.map((ns, i) => (
                                            <tr key={i} className="border-b border-gray-100 hover:bg-gray-50">
                                                <td className="p-2 text-center text-xs font-bold text-slate-500">{i + 1}</td>
                                                <td className="p-2">
                                                    <Input value={ns.nama_punggung} onChange={(e) => patchNameset(i, 'nama_punggung', e.target.value)} className="h-7 text-xs font-medium uppercase" />
                                                </td>
                                                <td className="p-2 text-center">
                                                    <Input value={ns.nomor_punggung} onChange={(e) => patchNameset(i, 'nomor_punggung', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-2">
                                                    <Select value={ns.size_id || NONE} onValueChange={(v) => patchNameset(i, 'size_id', v === NONE ? '' : v)}>
                                                        <SelectTrigger className="h-7 text-xs"><SelectValue placeholder="Pilih" /></SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value={NONE}>— —</SelectItem>
                                                            {masters.sizes.map((s) => (
                                                                <SelectItem key={s.id} value={s.id}>{s.kategori_size} - {s.ukuran}</SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </td>
                                                <td className="p-2">
                                                    <Input value={ns.keterangan} onChange={(e) => patchNameset(i, 'keterangan', e.target.value)} className="h-7 text-xs" />
                                                </td>
                                                <td className="p-2 text-center">
                                                    <button
                                                        type="button"
                                                        onClick={() => removeNameset(i)}
                                                        className="text-red-400 hover:text-red-600 hover:bg-red-50 rounded p-1 transition"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {item.namesets.length > 0 && (
                                <div className="bg-slate-50 p-3 border-t border-gray-200 flex flex-wrap gap-2 justify-center items-center">
                                    <span className="text-xs font-black text-slate-700 uppercase">
                                        Jumlah: {totalPcs} PCS
                                    </span>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            <PasteNamesetDialog
                open={pasteOpen}
                onClose={() => setPasteOpen(false)}
                sizes={masters.sizes}
                onConfirm={(rows) => onChange(index, { ...item, namesets: [...item.namesets, ...rows] })}
            />
        </div>
    );
}

function PaymentRow({ index, payment, banks, onChange, onRemove }) {
    return (
        <div className="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-12 shadow-sm">
            <div className="sm:col-span-2">
                <FieldLabel>Tipe</FieldLabel>
                <Select value={payment.payment_type} onValueChange={(v) => onChange(index, { ...payment, payment_type: v })}>
                    <SelectTrigger className="mt-1 h-8 text-xs"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="dp">DP</SelectItem>
                        <SelectItem value="pelunasan">Pelunasan</SelectItem>
                        <SelectItem value="lainnya">Lainnya</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="sm:col-span-3">
                <FieldLabel>Nominal</FieldLabel>
                <Input type="number" min={0} value={payment.amount} onChange={(e) => onChange(index, { ...payment, amount: Number(e.target.value) })} className="mt-1 h-8 text-xs font-mono font-bold" />
            </div>
            <div className="sm:col-span-2">
                <FieldLabel>Tanggal</FieldLabel>
                <Input type="date" value={payment.payment_date} onChange={(e) => onChange(index, { ...payment, payment_date: e.target.value })} className="mt-1 h-8 text-xs" />
            </div>
            <div className="sm:col-span-3">
                <FieldLabel>Bank</FieldLabel>
                <Select value={payment.bank_id || NONE} onValueChange={(v) => onChange(index, { ...payment, bank_id: v === NONE ? '' : v })}>
                    <SelectTrigger className="mt-1 h-8 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                        {banks.map((b) => (<SelectItem key={b.id} value={b.id}>{b.bank} {b.nomor_rekening}</SelectItem>))}
                    </SelectContent>
                </Select>
            </div>
            <div className="sm:col-span-2 flex items-end justify-end">
                <button
                    type="button"
                    onClick={() => onRemove(index)}
                    className="text-red-400 hover:text-red-600 hover:bg-red-50 rounded p-1.5 transition"
                >
                    <Trash2 className="h-4 w-4" />
                </button>
            </div>
            <div className="sm:col-span-12">
                <FieldLabel>Catatan</FieldLabel>
                <Input value={payment.notes} onChange={(e) => onChange(index, { ...payment, notes: e.target.value })} className="mt-1 h-8 text-xs" />
            </div>
        </div>
    );
}

export default function OrderForm({ mode, masters, order }) {
    const isEdit = mode === 'edit';

    const { data, setData, post, put, processing, errors } = useForm({
        nama_po: order?.nama_po ?? '',
        is_special_order: order?.is_special_order ?? false,
        tanggal_masuk: order?.tanggal_masuk?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10),
        deadline_customer: order?.deadline_customer?.slice?.(0, 10) ?? '',
        kategori_order_id: order?.kategori_order_id ?? '',
        sumber_order_id: order?.sumber_order_id ?? '',
        pelanggan_id: order?.pelanggan_id ?? '',
        printing_id: order?.printing_id ?? '',
        iklan_id: order?.iklan_id ?? '',
        catatan: order?.catatan ?? '',
        items: (order?.items ?? []).map((i) => ({
            ...newItem(),
            ...i,
            namesets: i.namesets ?? [],
        })),
        payments: (order?.payments ?? []).map((p) => ({
            payment_type: p.payment_type,
            amount: Number(p.amount),
            payment_date: p.payment_date?.slice?.(0, 10) ?? '',
            bank_id: p.bank_id ?? '',
            notes: p.notes ?? '',
        })),
    });

    function patchItem(index, next) {
        const items = [...data.items];
        items[index] = next;
        setData('items', items);
    }

    // Toggle produk dari master — centang = tambah modul, uncentang = hapus modul
    function toggleProduct(produk) {
        const existingIdx = data.items.findIndex((i) => i.product_id === produk.id);
        if (existingIdx >= 0) {
            const item = data.items[existingIdx];
            if (item.namesets.length > 0 && !confirm(`Hapus modul "${produk.nama}"? Data nameset akan hilang.`)) return;
            setData('items', data.items.filter((_, i) => i !== existingIdx));
        } else {
            setData('items', [...data.items, {
                ...newItem(),
                product_id: produk.id,
                nama_produk: produk.nama,
                harga_satuan: Number(produk.harga) || 0,
            }]);
        }
    }

    // Tambah item manual (tanpa produk dari master)
    function addItem() { setData('items', [...data.items, newItem()]); }
    function removeItem(i) { setData('items', data.items.filter((_, idx) => idx !== i)); }

    function patchPayment(index, next) {
        const ps = [...data.payments];
        ps[index] = next;
        setData('payments', ps);
    }
    function addPayment() { setData('payments', [...data.payments, newPayment()]); }
    function removePayment(i) { setData('payments', data.payments.filter((_, idx) => idx !== i)); }

    function submit(e) {
        e.preventDefault();
        if (isEdit) {
            put(route('orders.update', order.id));
        } else {
            post(route('orders.store'));
        }
    }

    const totalHarga = data.items.reduce((s, i) => s + (Number(i.quantity) || 0) * (Number(i.harga_satuan) || 0), 0);
    const totalPcs = useMemo(() => data.items.reduce((s, i) => s + i.namesets.length, 0), [data.items]);

    const pageTitle = isEdit ? `Edit PO ${order.no_po}` : 'Buat PO Baru';

    return (
        <AppLayout title={pageTitle}>
            <Head title={isEdit ? `Edit ${order.no_po}` : 'Buat PO'} />

            <form onSubmit={submit} className="space-y-0">
                {/* ===== HEADER BAR ===== */}
                <div className="bg-slate-900 text-white px-6 py-4 flex flex-col md:flex-row justify-between items-center border-b-4 border-red-600 rounded-xl mb-5 shadow-xl">
                    <div className="flex items-center gap-3">
                        <Package2 className="h-6 w-6 text-red-500" />
                        <div>
                            <h1 className="text-xl font-black tracking-wider uppercase">
                                {isEdit ? `Edit PO` : 'Buat PO Baru'}
                                {isEdit && <span className="text-red-400 ml-2">{order.no_po}</span>}
                            </h1>
                            <p className="text-xs text-slate-400 font-medium">
                                {isEdit ? 'Perubahan hanya bisa dilakukan selama status masih draft.' : 'Isi informasi PO, produk & nameset, lalu simpan sebagai draft.'}
                            </p>
                        </div>
                    </div>
                    <div className="mt-3 md:mt-0 flex gap-2">
                        <Link
                            href={isEdit ? route('orders.show', order.id) : route('orders.index')}
                            className="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold rounded-lg transition uppercase tracking-wide"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex items-center gap-2 bg-red-600 hover:bg-red-500 text-white px-5 py-2 rounded-lg text-sm font-black transition shadow-lg shadow-red-600/30 uppercase tracking-wide disabled:opacity-50"
                        >
                            <Save className="h-4 w-4" />
                            {isEdit ? 'Simpan Perubahan' : 'Simpan Draft'}
                        </button>
                    </div>
                </div>

                {/* ===== MAIN LAYOUT: CONTENT + SIDEBAR ===== */}
                <div className="flex flex-col lg:flex-row gap-5 items-start">

                    {/* ===== KONTEN UTAMA ===== */}
                    <div className="flex-grow space-y-5 min-w-0">

                        {/* Section: Informasi PO */}
                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-6 shadow-sm">
                            <SectionHeader>Informasi Pesanan</SectionHeader>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div className="flex flex-col">
                                    <FieldLabel>Tanggal Masuk <span className="text-red-500">*</span></FieldLabel>
                                    <Input type="date" value={data.tanggal_masuk} onChange={(e) => setData('tanggal_masuk', e.target.value)} className="h-8 text-sm" />
                                    {errors.tanggal_masuk && <p className="mt-1 text-xs text-red-500">{errors.tanggal_masuk}</p>}
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Deadline Customer <span className="text-red-500">*</span></FieldLabel>
                                    <Input type="date" value={data.deadline_customer} onChange={(e) => setData('deadline_customer', e.target.value)} className="h-8 text-sm" />
                                    {errors.deadline_customer && <p className="mt-1 text-xs text-red-500">{errors.deadline_customer}</p>}
                                </div>
                                <div className="flex flex-col col-span-2">
                                    <FieldLabel>Nama PO (Tim / Order) <span className="text-red-500">*</span></FieldLabel>
                                    <Input value={data.nama_po} onChange={(e) => setData('nama_po', e.target.value)} className="h-8 text-sm font-bold uppercase" placeholder="Contoh: PO Klub Garuda Mei" />
                                    {errors.nama_po && <p className="mt-1 text-xs text-red-500">{errors.nama_po}</p>}
                                </div>

                                <div className="flex flex-col col-span-2">
                                    <FieldLabel>Pelanggan <span className="text-red-500">*</span></FieldLabel>
                                    <Select value={data.pelanggan_id || NONE} onValueChange={(v) => setData('pelanggan_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-sm"><SelectValue placeholder="Pilih pelanggan" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Pilih —</SelectItem>
                                            {masters.pelanggan.map((p) => (
                                                <SelectItem key={p.id} value={p.id}>{p.kode} — {p.nama}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.pelanggan_id && <p className="mt-1 text-xs text-red-500">{errors.pelanggan_id}</p>}
                                </div>
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Kategori Order</FieldLabel>
                                    <Select value={data.kategori_order_id || NONE} onValueChange={(v) => setData('kategori_order_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-sm"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            {masters.kategori_orders.map((k) => (<SelectItem key={k.id} value={k.id}>{k.nama}</SelectItem>))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Sumber Order</FieldLabel>
                                    <Select value={data.sumber_order_id || NONE} onValueChange={(v) => setData('sumber_order_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-sm"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            {masters.sumber_orders.map((s) => (<SelectItem key={s.id} value={s.id}>{s.nama}</SelectItem>))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Jenis Printing</FieldLabel>
                                    <Select value={data.printing_id || NONE} onValueChange={(v) => setData('printing_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-sm"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            {masters.printings.map((p) => (<SelectItem key={p.id} value={p.id}>{p.nama}</SelectItem>))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Iklan / Kampanye</FieldLabel>
                                    <Select value={data.iklan_id || NONE} onValueChange={(v) => setData('iklan_id', v === NONE ? '' : v)}>
                                        <SelectTrigger className="h-8 text-sm"><SelectValue placeholder="—" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                            {(masters.iklans ?? []).map((k) => (
                                                <SelectItem key={k.id} value={k.id}>
                                                    {k.nama}{k.platform ? ` (${k.platform})` : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex flex-col col-span-4">
                                    <FieldLabel>Catatan PO</FieldLabel>
                                    <Textarea value={data.catatan} onChange={(e) => setData('catatan', e.target.value)} rows={2} className="text-sm resize-none" />
                                </div>

                                <div className="col-span-4">
                                    <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-blue-50 p-3">
                                        <div>
                                            <p className="text-xs font-black text-slate-700 uppercase tracking-wide">PO Pesanan Khusus (Special Order)</p>
                                        </div>
                                        <Switch checked={data.is_special_order} onCheckedChange={(v) => setData('is_special_order', v)} />
                                    </div>
                                </div>
                            </div>

                            {/* Checkbox Pilih Produk */}
                            {masters.produk.length > 0 && (
                                <div className="mt-5 border-t border-slate-200 pt-5">
                                    <div className="bg-blue-50 border border-blue-100 rounded-xl p-4">
                                        <p className="text-xs font-black text-blue-900 uppercase tracking-wide mb-3">
                                            Kategori Order / Produk (Pilih Untuk Membuka Modul)
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {masters.produk.map((p) => {
                                                const isChecked = data.items.some((i) => i.product_id === p.id);
                                                return (
                                                    <label
                                                        key={p.id}
                                                        className={`flex items-center gap-1.5 cursor-pointer border px-3 py-2 rounded-lg text-[11px] font-bold transition-all shadow-sm select-none ${
                                                            isChecked
                                                                ? 'bg-blue-600 border-blue-700 text-white'
                                                                : 'bg-white border-slate-300 text-slate-600 hover:bg-blue-50 hover:border-blue-400'
                                                        }`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            className="w-4 h-4 rounded"
                                                            checked={isChecked}
                                                            onChange={() => toggleProduct(p)}
                                                        />
                                                        {p.nama.toUpperCase()}
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Section: Produk */}
                        <div>
                            <div className="flex items-center justify-between mb-3">
                                <h2 className="text-sm font-black text-slate-800 uppercase tracking-wide">
                                    Modul Produk ({data.items.length})
                                </h2>
                                <button
                                    type="button"
                                    onClick={addItem}
                                    className="flex items-center gap-1.5 bg-slate-600 hover:bg-slate-500 text-white px-4 py-2 rounded-lg text-xs font-black transition shadow-sm uppercase"
                                    title="Tambah produk tanpa memilih dari master (manual)"
                                >
                                    <Plus className="h-3.5 w-3.5" /> Tambah Manual
                                </button>
                            </div>
                            <div className="space-y-5">
                                {data.items.length === 0 && (
                                    <div className="rounded-xl border-2 border-dashed border-slate-300 py-12 text-center">
                                        <Package2 className="h-10 w-10 mx-auto text-slate-300 mb-2" />
                                        <p className="text-sm font-bold text-slate-400 uppercase tracking-wide">Belum ada modul produk</p>
                                        <p className="text-xs text-slate-400 mt-1">Pilih produk dari checkbox di atas untuk membuka modul.</p>
                                    </div>
                                )}
                                {data.items.map((item, idx) => (
                                    <ItemCard key={idx} index={idx} item={item} masters={masters} onChange={patchItem} onRemove={removeItem} />
                                ))}
                            </div>
                        </div>

                        {/* Section: Pembayaran */}
                        <div className="bg-white border-2 border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                            <div className="bg-slate-800 px-5 py-3.5 flex justify-between items-center">
                                <h2 className="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2">
                                    <CreditCard className="h-4 w-4 text-slate-400" />
                                    Pembayaran ({data.payments.length})
                                </h2>
                                <button
                                    type="button"
                                    onClick={addPayment}
                                    className="flex items-center gap-1 bg-slate-600 hover:bg-slate-500 text-white px-3 py-1.5 rounded text-xs font-bold transition uppercase"
                                >
                                    <Plus className="h-3.5 w-3.5" /> Tambah Pembayaran
                                </button>
                            </div>
                            <div className="p-4 space-y-3 bg-slate-50">
                                {data.payments.length === 0 && (
                                    <div className="rounded-xl border border-dashed border-slate-300 py-6 text-center">
                                        <p className="text-xs font-bold text-slate-400 uppercase">Belum ada pembayaran</p>
                                        <p className="text-xs text-slate-400 mt-1">PO tanpa DP tetap bisa diterbitkan.</p>
                                    </div>
                                )}
                                {data.payments.map((p, idx) => (
                                    <PaymentRow key={idx} index={idx} payment={p} banks={masters.banks} onChange={patchPayment} onRemove={removePayment} />
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* ===== SIDEBAR RINGKASAN ===== */}
                    <div className="lg:w-64 flex-shrink-0 space-y-4 lg:sticky lg:top-4">
                        <h2 className="text-sm font-black text-slate-800 border-b-2 border-slate-200 pb-2 uppercase tracking-wide">Ringkasan Total</h2>

                        {/* Total PCS */}
                        <div className="bg-slate-900 text-white p-5 rounded-xl shadow-lg text-center border-b-4 border-red-600">
                            <p className="text-xs font-bold text-slate-300 uppercase tracking-widest">Total Keseluruhan</p>
                            <p className="text-5xl font-black mt-2 tabular-nums">{totalPcs}</p>
                            <p className="text-sm font-bold text-slate-400 mt-0.5">PCS</p>
                            <div className="border-t border-slate-700 mt-3 pt-3">
                                <p className="text-xs font-bold text-slate-400 uppercase">Total Tagihan</p>
                                <p className="font-mono font-black text-sm text-white mt-0.5">{formatRupiah(totalHarga)}</p>
                            </div>
                        </div>

                        {/* Per produk */}
                        {data.items.length > 0 && (
                            <div className="space-y-2">
                                {data.items.map((item, idx) => {
                                    const color = ACCENT_COLORS[idx % ACCENT_COLORS.length];
                                    const dotMap = { red: 'bg-red-500', blue: 'bg-blue-500', emerald: 'bg-emerald-500', amber: 'bg-amber-500', purple: 'bg-purple-500', pink: 'bg-pink-500', teal: 'bg-teal-500' };
                                    return (
                                        <div key={idx} className="flex items-center justify-between bg-white border border-slate-200 rounded-lg px-3 py-2 shadow-sm">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <span className={`w-2 h-2 rounded-full flex-shrink-0 ${dotMap[color]}`} />
                                                <span className="text-xs font-bold text-slate-700 uppercase truncate">
                                                    {item.nama_produk || `Produk #${idx + 1}`}
                                                </span>
                                            </div>
                                            <span className="text-xs font-black text-slate-600 ml-2 whitespace-nowrap">
                                                {item.namesets.length} pcs
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        {/* Sticky action */}
                        <div className="pt-2 space-y-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-500 text-white py-2.5 rounded-lg text-sm font-black transition shadow-lg shadow-red-600/30 uppercase tracking-wide disabled:opacity-50"
                            >
                                <Save className="h-4 w-4" />
                                {isEdit ? 'Simpan Perubahan' : 'Simpan Draft'}
                            </button>
                            <Link
                                href={isEdit ? route('orders.show', order.id) : route('orders.index')}
                                className="w-full flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 rounded-lg text-xs font-bold transition uppercase tracking-wide"
                            >
                                Batal
                            </Link>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
