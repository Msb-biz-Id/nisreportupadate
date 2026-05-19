import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Save, Plus, Trash2, ChevronDown, ChevronUp, Settings2, Users, Package, CreditCard, ClipboardPaste } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import { Separator } from '@/Components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import ImageUploader from '@/Components/ImageUploader';
import { formatRupiah } from '@/lib/utils';

const NONE = '__none__';

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
        // Cari size: cocokkan dengan "ukuran" atau "kategori_size - ukuran"
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
                    <DialogTitle className="flex items-center gap-2">
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
                                    <TableRow>
                                        <TableHead className="text-xs">Nama</TableHead>
                                        <TableHead className="text-xs">No.</TableHead>
                                        <TableHead className="text-xs">Ukuran</TableHead>
                                        <TableHead className="text-xs">Keterangan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {preview.map((r, i) => (
                                        <TableRow key={i}>
                                            <TableCell className="text-xs">{r.nama_punggung || '—'}</TableCell>
                                            <TableCell className="text-xs">{r.nomor_punggung || '—'}</TableCell>
                                            <TableCell className="text-xs">
                                                {r.size_id
                                                    ? <span className="text-green-600">{r.size_label}</span>
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
    const [namesetOpen, setNamesetOpen] = useState(false);
    const [pasteOpen, setPasteOpen] = useState(false);

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
            <div>
                <Label className="text-xs">Pola {jenis}</Label>
                <Select value={item[fieldKey] || NONE} onValueChange={(v) => patch(fieldKey, v === NONE ? '' : v)}>
                    <SelectTrigger className="mt-1 h-8"><SelectValue placeholder="—" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                        {opts.map((p) => (<SelectItem key={p.id} value={p.id}>{p.nama}</SelectItem>))}
                    </SelectContent>
                </Select>
            </div>
        );
    }

    return (
        <Card className="border-l-4 border-l-primary/40">
            <CardHeader className="pb-2">
                <div className="flex items-start justify-between gap-2">
                    <CardTitle className="text-base">Produk #{index + 1}</CardTitle>
                    <Button type="button" size="icon" variant="ghost" className="text-destructive hover:text-destructive" onClick={() => onRemove(index)}>
                        <Trash2 className="h-4 w-4" />
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="space-y-3 pt-0">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-12">
                    <div className="sm:col-span-5">
                        <Label>Produk dari Master</Label>
                        <Select value={item.product_id || NONE} onValueChange={(v) => patch('product_id', v === NONE ? '' : v)}>
                            <SelectTrigger className="mt-1.5"><SelectValue placeholder="Pilih produk (atau isi nama manual)" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE}>— Manual —</SelectItem>
                                {masters.produk.map((p) => (
                                    <SelectItem key={p.id} value={p.id}>{p.nama} — {formatRupiah(p.harga)}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="sm:col-span-4">
                        <Label>Nama Produk <span className="text-destructive">*</span></Label>
                        <Input value={item.nama_produk} onChange={(e) => patch('nama_produk', e.target.value)} className="mt-1.5" />
                    </div>
                    <div className="sm:col-span-3">
                        <Label>Varian / Label</Label>
                        <Input value={item.varian_label} onChange={(e) => patch('varian_label', e.target.value)} placeholder="Pemain Utama / Cadangan" className="mt-1.5" />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <Label>Qty <span className="text-destructive">*</span></Label>
                        <Input type="number" min={1} value={item.quantity} onChange={(e) => patch('quantity', Number(e.target.value))} className="mt-1.5" />
                    </div>
                    <div>
                        <Label>Harga Satuan</Label>
                        <Input type="number" min={0} value={item.harga_satuan} onChange={(e) => patch('harga_satuan', Number(e.target.value))} className="mt-1.5" />
                    </div>
                    <div className="col-span-2 sm:col-span-2 flex items-end justify-end">
                        <div className="rounded-md border bg-muted/40 px-3 py-1.5 text-sm">
                            <div className="text-xs text-muted-foreground">Subtotal</div>
                            <div className="font-mono font-semibold">{formatRupiah(subtotal)}</div>
                        </div>
                    </div>
                </div>

                <Button type="button" variant="ghost" size="sm" onClick={() => setSpecOpen((v) => !v)} className="w-full justify-between">
                    <span className="flex items-center gap-2"><Settings2 className="h-4 w-4" /> Spesifikasi Produk</span>
                    {specOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                </Button>

                {specOpen && (
                    <div className="grid grid-cols-1 gap-3 rounded-lg border bg-muted/20 p-3 sm:grid-cols-3">
                        <div>
                            <Label className="text-xs">Bahan Kain</Label>
                            <Select value={item.bahan_kain_id || NONE} onValueChange={(v) => patch('bahan_kain_id', v === NONE ? '' : v)}>
                                <SelectTrigger className="mt-1 h-8"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                    {masters.bahan_kains.map((b) => (<SelectItem key={b.id} value={b.id}>{b.nama}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-xs">Jenis Setelan</Label>
                            <Select value={item.jenis_setelan || NONE} onValueChange={(v) => patch('jenis_setelan', v === NONE ? '' : v)}>
                                <SelectTrigger className="mt-1 h-8"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                    <SelectItem value="stell">Stell (Atasan + Bawahan)</SelectItem>
                                    <SelectItem value="non_stell">Non-Stell</SelectItem>
                                    <SelectItem value="atasan_saja">Atasan Saja</SelectItem>
                                    <SelectItem value="bawahan_saja">Bawahan Saja</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-xs">Logo</Label>
                            <Select value={item.logo_id || NONE} onValueChange={(v) => patch('logo_id', v === NONE ? '' : v)}>
                                <SelectTrigger className="mt-1 h-8"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                    {masters.logos.map((l) => (<SelectItem key={l.id} value={l.id}>{l.nama}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-xs">Jenis Printing</Label>
                            <Select value={item.printing_id || NONE} onValueChange={(v) => patch('printing_id', v === NONE ? '' : v)}>
                                <SelectTrigger className="mt-1 h-8"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                    {masters.printings.map((l) => (<SelectItem key={l.id} value={l.id}>{l.nama}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-xs">Resleting</Label>
                            <Select value={item.resleting_id || NONE} onValueChange={(v) => patch('resleting_id', v === NONE ? '' : v)}>
                                <SelectTrigger className="mt-1 h-8"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                    {masters.resletings.map((l) => (<SelectItem key={l.id} value={l.id}>{l.nama}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label className="text-xs">Warna</Label>
                            <Input value={item.warna} onChange={(e) => patch('warna', e.target.value)} className="mt-1 h-8" placeholder="Merah / Biru / Mix" />
                        </div>
                        {polaSelect('Lengan', 'pola_jahitan_lengan_id')}
                        {polaSelect('Kerah', 'pola_jahitan_kerah_id')}
                        {polaSelect('Bawah', 'pola_jahitan_bawah_id')}
                        {polaSelect('Pundak', 'pola_jahitan_pundak_id')}
                        <div>
                            <Label className="text-xs">Jenis Kerah (catatan)</Label>
                            <Input value={item.jenis_kerah} onChange={(e) => patch('jenis_kerah', e.target.value)} className="mt-1 h-8" />
                        </div>
                        <div className="sm:col-span-3">
                            <Label className="text-xs">Catatan Item</Label>
                            <Textarea value={item.catatan} onChange={(e) => patch('catatan', e.target.value)} rows={2} className="mt-1" />
                        </div>
                        <div>
                            <Label className="text-xs">Gambar Desain</Label>
                            <ImageUploader value={item.gambar_desain || null} onChange={(p) => patch('gambar_desain', p || '')} purpose="orders" aspect={4 / 3} />
                        </div>
                        <div>
                            <Label className="text-xs">Gambar Kerah</Label>
                            <ImageUploader value={item.gambar_kerah || null} onChange={(p) => patch('gambar_kerah', p || '')} purpose="orders" aspect={1} />
                        </div>
                    </div>
                )}

                <Button type="button" variant="ghost" size="sm" onClick={() => setNamesetOpen((v) => !v)} className="w-full justify-between">
                    <span className="flex items-center gap-2"><Users className="h-4 w-4" /> Nameset & Nomor Punggung ({item.namesets.length})</span>
                    {namesetOpen ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                </Button>

                {namesetOpen && (
                    <div className="space-y-2 rounded-lg border bg-muted/20 p-3">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-10">#</TableHead>
                                    <TableHead>Nama Punggung</TableHead>
                                    <TableHead>No. Punggung</TableHead>
                                    <TableHead>Ukuran</TableHead>
                                    <TableHead>Keterangan</TableHead>
                                    <TableHead className="w-10"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {item.namesets.length === 0 && (
                                    <TableRow><TableCell colSpan={6} className="text-center text-xs text-muted-foreground">Belum ada nameset. Klik tambah untuk mulai.</TableCell></TableRow>
                                )}
                                {item.namesets.map((ns, i) => (
                                    <TableRow key={i}>
                                        <TableCell className="text-center text-xs">{i + 1}</TableCell>
                                        <TableCell><Input value={ns.nama_punggung} onChange={(e) => patchNameset(i, 'nama_punggung', e.target.value)} className="h-8" /></TableCell>
                                        <TableCell><Input value={ns.nomor_punggung} onChange={(e) => patchNameset(i, 'nomor_punggung', e.target.value)} className="h-8 w-20" /></TableCell>
                                        <TableCell>
                                            <Select value={ns.size_id || NONE} onValueChange={(v) => patchNameset(i, 'size_id', v === NONE ? '' : v)}>
                                                <SelectTrigger className="h-8 w-40"><SelectValue placeholder="Pilih" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value={NONE}>— —</SelectItem>
                                                    {masters.sizes.map((s) => (
                                                        <SelectItem key={s.id} value={s.id}>{s.kategori_size} - {s.ukuran}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell><Input value={ns.keterangan} onChange={(e) => patchNameset(i, 'keterangan', e.target.value)} className="h-8" /></TableCell>
                                        <TableCell>
                                            <Button type="button" size="icon" variant="ghost" className="text-destructive hover:text-destructive" onClick={() => removeNameset(i)}>
                                                <Trash2 className="h-3.5 w-3.5" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <div className="flex gap-2">
                            <Button type="button" variant="outline" size="sm" onClick={addNameset}><Plus className="h-3.5 w-3.5 mr-1" /> Tambah Baris</Button>
                            <Button type="button" variant="outline" size="sm" onClick={() => setPasteOpen(true)}><ClipboardPaste className="h-3.5 w-3.5 mr-1" /> Paste dari Excel</Button>
                        </div>
                        <PasteNamesetDialog
                            open={pasteOpen}
                            onClose={() => setPasteOpen(false)}
                            sizes={masters.sizes}
                            onConfirm={(rows) => onChange(index, { ...item, namesets: [...item.namesets, ...rows] })}
                        />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function PaymentRow({ index, payment, banks, onChange, onRemove }) {
    return (
        <div className="grid grid-cols-1 gap-2 rounded-lg border bg-muted/20 p-3 sm:grid-cols-12">
            <div className="sm:col-span-2">
                <Label className="text-xs">Tipe</Label>
                <Select value={payment.payment_type} onValueChange={(v) => onChange(index, { ...payment, payment_type: v })}>
                    <SelectTrigger className="mt-1 h-8"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="dp">DP</SelectItem>
                        <SelectItem value="pelunasan">Pelunasan</SelectItem>
                        <SelectItem value="lainnya">Lainnya</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="sm:col-span-3">
                <Label className="text-xs">Nominal</Label>
                <Input type="number" min={0} value={payment.amount} onChange={(e) => onChange(index, { ...payment, amount: Number(e.target.value) })} className="mt-1 h-8" />
            </div>
            <div className="sm:col-span-2">
                <Label className="text-xs">Tanggal</Label>
                <Input type="date" value={payment.payment_date} onChange={(e) => onChange(index, { ...payment, payment_date: e.target.value })} className="mt-1 h-8" />
            </div>
            <div className="sm:col-span-3">
                <Label className="text-xs">Bank</Label>
                <Select value={payment.bank_id || NONE} onValueChange={(v) => onChange(index, { ...payment, bank_id: v === NONE ? '' : v })}>
                    <SelectTrigger className="mt-1 h-8"><SelectValue placeholder="—" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                        {banks.map((b) => (<SelectItem key={b.id} value={b.id}>{b.bank} {b.nomor_rekening}</SelectItem>))}
                    </SelectContent>
                </Select>
            </div>
            <div className="sm:col-span-2 flex items-end justify-end">
                <Button type="button" size="icon" variant="ghost" className="text-destructive hover:text-destructive" onClick={() => onRemove(index)}>
                    <Trash2 className="h-4 w-4" />
                </Button>
            </div>
            <div className="sm:col-span-12">
                <Label className="text-xs">Catatan</Label>
                <Input value={payment.notes} onChange={(e) => onChange(index, { ...payment, notes: e.target.value })} className="mt-1 h-8" />
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

    const totalItems = data.items.reduce((s, i) => s + (Number(i.quantity) || 0) * (Number(i.harga_satuan) || 0), 0);

    return (
        <AppLayout title={isEdit ? `Edit PO ${order.no_po}` : 'Buat PO Baru'}>
            <Head title={isEdit ? `Edit ${order.no_po}` : 'Buat PO'} />

            <form onSubmit={submit} className="space-y-5">
                {/* Section 1: Info PO */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Package className="h-5 w-5 text-primary" /> Informasi PO</CardTitle>
                        <CardDescription>Identitas dasar Purchase Order — bisa diedit selama masih draft.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Label>Nama PO <span className="text-destructive">*</span></Label>
                            <Input value={data.nama_po} onChange={(e) => setData('nama_po', e.target.value)} className="mt-1.5" placeholder="Contoh: PO Klub Garuda Mei" />
                            {errors.nama_po && <p className="mt-1 text-xs text-destructive">{errors.nama_po}</p>}
                        </div>
                        <div>
                            <Label>Tanggal Masuk <span className="text-destructive">*</span></Label>
                            <Input type="date" value={data.tanggal_masuk} onChange={(e) => setData('tanggal_masuk', e.target.value)} className="mt-1.5" />
                            {errors.tanggal_masuk && <p className="mt-1 text-xs text-destructive">{errors.tanggal_masuk}</p>}
                        </div>
                        <div>
                            <Label>Deadline Customer <span className="text-destructive">*</span></Label>
                            <Input type="date" value={data.deadline_customer} onChange={(e) => setData('deadline_customer', e.target.value)} className="mt-1.5" />
                            {errors.deadline_customer && <p className="mt-1 text-xs text-destructive">{errors.deadline_customer}</p>}
                        </div>
                        <div>
                            <Label>Pelanggan <span className="text-destructive">*</span></Label>
                            <Select value={data.pelanggan_id || NONE} onValueChange={(v) => setData('pelanggan_id', v === NONE ? '' : v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue placeholder="Pilih pelanggan" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Pilih —</SelectItem>
                                    {masters.pelanggan.map((p) => (
                                        <SelectItem key={p.id} value={p.id}>{p.kode} — {p.nama}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.pelanggan_id && <p className="mt-1 text-xs text-destructive">{errors.pelanggan_id}</p>}
                            <p className="mt-1 text-xs text-muted-foreground">Tambah pelanggan dari menu Master Data.</p>
                        </div>
                        <div>
                            <Label>Kategori Order</Label>
                            <Select value={data.kategori_order_id || NONE} onValueChange={(v) => setData('kategori_order_id', v === NONE ? '' : v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                    {masters.kategori_orders.map((k) => (<SelectItem key={k.id} value={k.id}>{k.nama}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Sumber Order</Label>
                            <Select value={data.sumber_order_id || NONE} onValueChange={(v) => setData('sumber_order_id', v === NONE ? '' : v)}>
                                <SelectTrigger className="mt-1.5"><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                    {masters.sumber_orders.map((s) => (<SelectItem key={s.id} value={s.id}>{s.nama}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="sm:col-span-2">
                            <Label>Catatan PO</Label>
                            <Textarea value={data.catatan} onChange={(e) => setData('catatan', e.target.value)} rows={2} className="mt-1.5" />
                        </div>
                        <div className="sm:col-span-2">
                            <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                                <div>
                                    <Label className="text-sm">PO Pesanan Khusus (Special Order)</Label>
                                    <p className="text-xs text-muted-foreground">Ditandai agar Admin Keuangan bisa bikin invoice walau belum ada DP.</p>
                                </div>
                                <Switch checked={data.is_special_order} onCheckedChange={(v) => setData('is_special_order', v)} />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Section 2: Items */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-2">
                        <div>
                            <CardTitle className="flex items-center gap-2"><Package className="h-5 w-5 text-primary" /> Detail Produk ({data.items.length})</CardTitle>
                            <CardDescription>Setiap baris adalah 1 produk dengan spesifikasi & nameset sendiri.</CardDescription>
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={addItem}><Plus className="h-4 w-4" /> Tambah Produk</Button>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {data.items.length === 0 && (
                            <div className="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground">
                                Belum ada produk. Klik "Tambah Produk".
                            </div>
                        )}
                        {data.items.map((item, idx) => (
                            <ItemCard key={idx} index={idx} item={item} masters={masters} onChange={patchItem} onRemove={removeItem} />
                        ))}
                        <div className="flex items-center justify-between rounded-lg bg-muted/40 p-3">
                            <span className="text-sm font-medium">Total Tagihan</span>
                            <span className="text-lg font-bold font-mono">{formatRupiah(totalItems)}</span>
                        </div>
                    </CardContent>
                </Card>

                {/* Section 3: Payments */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-2">
                        <div>
                            <CardTitle className="flex items-center gap-2"><CreditCard className="h-5 w-5 text-primary" /> Pembayaran ({data.payments.length})</CardTitle>
                            <CardDescription>DP atau pelunasan yang sudah masuk. Setelah PO published, pembayaran tambahan diinput dari halaman preview.</CardDescription>
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={addPayment}><Plus className="h-4 w-4" /> Tambah Pembayaran</Button>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {data.payments.length === 0 && (
                            <div className="rounded-lg border border-dashed py-6 text-center text-sm text-muted-foreground">
                                Belum ada pembayaran. PO tanpa DP tetap bisa diterbitkan, invoice dibuat manual saat DP masuk.
                            </div>
                        )}
                        {data.payments.map((p, idx) => (
                            <PaymentRow key={idx} index={idx} payment={p} banks={masters.banks} onChange={patchPayment} onRemove={removePayment} />
                        ))}
                    </CardContent>
                </Card>

                <div className="sticky bottom-0 z-10 flex flex-col-reverse gap-2 rounded-lg border bg-background/90 p-3 shadow backdrop-blur sm:flex-row sm:justify-end">
                    <Button asChild type="button" variant="outline">
                        <Link href={isEdit ? route('orders.show', order.id) : route('orders.index')}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="h-4 w-4" />
                        {isEdit ? 'Simpan Perubahan' : 'Simpan Draft'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
