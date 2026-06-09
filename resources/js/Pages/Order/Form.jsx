import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState, useEffect } from 'react';
import { Save, Plus, Trash2, ChevronDown, ChevronUp, Settings2, Users, CreditCard, ClipboardPaste, Package2, FileDown, Copy, ArrowUp, ArrowDown } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import ImageUploader from '@/Components/ImageUploader';
import { formatRupiah } from '@/lib/utils';

const NONE = '__none__';
const ACCENT_COLORS = ['red', 'blue', 'emerald', 'amber', 'purple', 'pink', 'teal'];

function newItem() {
    return {
        product_id: '',
        jenis_produk_id: '',
        nama_produk: '',
        varian_label: '',
        quantity: 1,
        harga_satuan: 0,
        is_addon: false,
        jenis_setelan_id: '',
        pola_produksi_id: '',
        bahan_kain_id: '',
        bahan_kain_ids: [],           // multiple bahan atasan
        bahan_kain_bawahan_id: '',
        bahan_kain_bawahan_ids: [],   // multiple bahan bawahan
        jenis_setelan: '',
        pola: '',
        logo_id: '',
        logo_ids: [],                 // multiple logo (dari MultiSelect)
        resleting_id: '',
        jenis_rib: '',
        tutup_kerah: '',
        list_kerah: '',
        list_lengan: '',
        list_samping_celana: '',
        list_bawah_celana: '',
        pola_jahitan_lengan_id: '',   // Jahitan List Lengan (dari master)
        pola_jahitan_id: '',          // Pola Jahitan (dari master)
        jahitan_list_lengan: '',      // fallback string lama
        warna: '',
        jml_atasan: '',
        jml_bawahan: '',
        jenis_kerah: '',
        catatan: '',
        gambar_desain: '',
        ket_atasan: '',
        ket_bawahan: '',
        gambar_kerah: '',
        gambar_ket_tambahan: '',
        namesets: [],
    };
}

function newNameset() {
    return {
        nama_punggung: '', nomor_punggung: '',
        nama_dada: '', nomor_dada: '',
        nama_lengan: '', nomor_lengan: '',
        nomor_punggung_2: '', nama_punggung_2: '',
        size_id: '', size_label: '',
        size_celana_id: '', size_celana_label: '',
        keterangan: '',
    };
}

function newPayment() {
    return { payment_type: 'dp', amount: 0, payment_date: new Date().toISOString().slice(0, 10), bank_id: '', notes: '' };
}

function PasteNamesetDialog({ open, onClose, onConfirm, sizes }) {
    const [text, setText] = useState('');
    const [hasHeaderRow, setHasHeaderRow] = useState(false);
    const [mappings, setMappings] = useState([]);
    const [prevMaxCols, setPrevMaxCols] = useState(0);

    const MAPPING_OPTIONS = [
        { value: 'ignore', label: 'Abaikan Kolom' },
        { value: 'nama_punggung', label: 'Nama Punggung' },
        { value: 'nomor_punggung', label: 'No. Punggung' },
        { value: 'nama_dada', label: 'Nama Dada' },
        { value: 'nomor_dada', label: 'No. Dada' },
        { value: 'nama_lengan', label: 'Nama Lengan' },
        { value: 'nomor_lengan', label: 'No. Lengan' },
        { value: 'nomor_punggung_2', label: 'No. Punggung 2' },
        { value: 'nama_punggung_2', label: 'Nama Punggung 2' },
        { value: 'size_id', label: 'Size Atasan' },
        { value: 'size_celana_id', label: 'Size Celana' },
        { value: 'keterangan', label: 'Keterangan' },
    ];

    function splitLine(line) {
        if (line.includes('\t')) {
            return line.split('\t').map((c) => c.trim());
        }
        if (line.includes(';')) {
            return line.split(';').map((c) => c.trim());
        }
        if (line.includes(',')) {
            return line.split(',').map((c) => c.trim());
        }
        if (/\s{2,}/.test(line)) {
            return line.split(/\s{2,}/).map((c) => c.trim());
        }
        return [line.trim()];
    }

    function detectHeaders(firstRowCols) {
        const matchedFields = [];
        let matchCount = 0;
        
        for (let i = 0; i < firstRowCols.length; i++) {
            const col = firstRowCols[i].toLowerCase();
            let matched = 'ignore';
            
            if (col.includes('nama') && col.includes('punggung')) {
                if (col.includes('2')) matched = 'nama_punggung_2';
                else matched = 'nama_punggung';
            }
            else if (col.includes('nomor') && col.includes('punggung') || col.includes('no') && col.includes('punggung')) {
                if (col.includes('2')) matched = 'nomor_punggung_2';
                else matched = 'nomor_punggung';
            }
            else if (col.includes('nama') && col.includes('dada')) matched = 'nama_dada';
            else if (col.includes('nomor') && col.includes('dada') || col.includes('no') && col.includes('dada')) matched = 'nomor_dada';
            else if (col.includes('nama') && col.includes('lengan')) matched = 'nama_lengan';
            else if (col.includes('nomor') && col.includes('lengan') || col.includes('no') && col.includes('lengan')) matched = 'nomor_lengan';
            else if (col.includes('size celana') || col.includes('ukuran celana') || col.includes('sz celana') || col.includes('celana')) matched = 'size_celana_id';
            else if (col.includes('size') || col.includes('ukuran') || col === 'sz') matched = 'size_id';
            else if (col.includes('keterangan') || col.includes('ket') || col.includes('note') || col.includes('catatan')) matched = 'keterangan';
            else if (col === 'nama') matched = 'nama_punggung';
            else if (col === 'nomor' || col === 'no' || col === 'no.') matched = 'nomor_punggung';
            
            if (matched !== 'ignore') {
                matchCount++;
            }
            matchedFields.push(matched);
        }
        
        return {
            isHeader: matchCount >= 2 || (firstRowCols.length === 1 && matchCount === 1),
            fields: matchedFields
        };
    }

    function getDefaultMappings(numCols) {
        const defaults = [];
        const fields = [
            'nama_punggung',
            'nomor_punggung',
            'size_id',
            'size_celana_id',
            'keterangan',
            'nama_dada',
            'nomor_dada',
            'nama_lengan',
            'nomor_lengan',
            'nomor_punggung_2',
            'nama_punggung_2',
        ];
        
        for (let i = 0; i < numCols; i++) {
            defaults.push(fields[i] || 'ignore');
        }
        return defaults;
    }

    const parsedRows = useMemo(() => {
        if (!text.trim()) return [];
        return text.split('\n')
            .map((line) => splitLine(line))
            .filter((r) => r.length > 0 && r.some(Boolean));
    }, [text]);

    const maxCols = useMemo(() => {
        return parsedRows.length > 0 ? Math.max(...parsedRows.map((r) => r.length)) : 0;
    }, [parsedRows]);

    useEffect(() => {
        if (maxCols === 0) {
            setMappings([]);
            setHasHeaderRow(false);
            setPrevMaxCols(0);
            return;
        }

        if (maxCols !== prevMaxCols) {
            const firstRow = parsedRows[0] || [];
            const detection = detectHeaders(firstRow);
            
            if (detection.isHeader) {
                setHasHeaderRow(true);
                setMappings(detection.fields);
            } else {
                setHasHeaderRow(false);
                setMappings(getDefaultMappings(maxCols));
            }
            setPrevMaxCols(maxCols);
        }
    }, [maxCols, parsedRows, prevMaxCols]);

    const finalRows = useMemo(() => {
        if (parsedRows.length === 0) return [];
        const startIndex = hasHeaderRow ? 1 : 0;
        const rowsToProcess = parsedRows.slice(startIndex);
        
        return rowsToProcess.map((cols) => {
            const rowData = {
                nama_punggung: '',
                nomor_punggung: '',
                nama_dada: '',
                nomor_dada: '',
                nama_lengan: '',
                nomor_lengan: '',
                nomor_punggung_2: '',
                nama_punggung_2: '',
                size_id: '',
                size_label: '',
                size_celana_id: '',
                size_celana_label: '',
                keterangan: '',
            };
            
            cols.forEach((val, colIdx) => {
                const field = mappings[colIdx];
                if (!field || field === 'ignore') return;
                
                if (field === 'size_id') {
                    const needle = val.toLowerCase();
                    const matched = sizes.find(
                        (s) =>
                            s.ukuran?.toLowerCase() === needle ||
                            `${s.kategori_size} - ${s.ukuran}`.toLowerCase() === needle
                    );
                    rowData.size_id = matched?.id ?? '';
                    rowData.size_label = matched ? `${matched.kategori_size} - ${matched.ukuran}` : val;
                } else if (field === 'size_celana_id') {
                    const needle = val.toLowerCase();
                    const matched = sizes.find(
                        (s) =>
                            s.ukuran?.toLowerCase() === needle ||
                            `${s.kategori_size} - ${s.ukuran}`.toLowerCase() === needle
                    );
                    rowData.size_celana_id = matched?.id ?? '';
                    rowData.size_celana_label = matched ? `${matched.kategori_size} - ${matched.ukuran}` : val;
                } else {
                    rowData[field] = val;
                }
            });
            
            return rowData;
        });
    }, [parsedRows, mappings, hasHeaderRow, sizes]);

    function handleConfirm() {
        if (!finalRows.length) return;
        onConfirm(finalRows);
        setText('');
        setMappings([]);
        setHasHeaderRow(false);
        setPrevMaxCols(0);
        onClose();
    }

    return (
        <Dialog open={open} onOpenChange={(v) => { if (!v) { setText(''); setMappings([]); setHasHeaderRow(false); setPrevMaxCols(0); onClose(); } }}>
            <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col p-6">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 uppercase text-sm font-black tracking-wide">
                        <ClipboardPaste className="h-4 w-4 text-red-600" /> Paste Nameset dari Excel
                    </DialogTitle>
                </DialogHeader>
                
                <div className="space-y-4 flex-1 overflow-y-auto pr-1 py-1">
                    <p className="text-xs text-muted-foreground leading-relaxed">
                        Copy kolom data nameset dari Excel atau spreadsheet Anda, lalu paste di area teks di bawah. Sistem akan mendeteksi kolom secara dinamis dan Anda dapat menyesuaikan pemetaannya.
                    </p>
                    
                    <textarea
                        className="w-full h-28 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none shadow-inner"
                        placeholder={"Ahmad\t7\tS\tLengan Panjang\nBudi\t10\tM\tKeterangan tambahan"}
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        autoFocus
                    />
                    
                    {parsedRows.length > 0 && (
                        <div className="space-y-3 pt-2">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 border border-slate-200 p-3 rounded-xl">
                                <label className="flex items-center gap-2 cursor-pointer select-none text-xs font-bold text-slate-700 uppercase">
                                    <input
                                        type="checkbox"
                                        className="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500"
                                        checked={hasHeaderRow}
                                        onChange={(e) => setHasHeaderRow(e.target.checked)}
                                    />
                                    Baris pertama adalah Header (Abaikan baris pertama)
                                </label>
                                <span className="text-xs font-black text-slate-500 bg-white px-2.5 py-1 border border-slate-200 rounded-lg shadow-sm">
                                    DATA SIAP IMPORT: {finalRows.length} BARIS
                                </span>
                            </div>
                            
                            <div className="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                <div className="overflow-x-auto max-h-60">
                                    <Table className="min-w-[800px] relative">
                                        <TableHeader className="sticky top-0 bg-slate-100 shadow-[0_1px_0_rgba(0,0,0,0.1)] z-10">
                                            <TableRow>
                                                <TableHead className="w-12 text-center text-xs font-bold uppercase text-slate-500">No</TableHead>
                                                {Array.from({ length: maxCols }).map((_, colIdx) => (
                                                    <TableHead key={colIdx} className="p-2 min-w-[150px] border-l border-slate-200">
                                                        <div className="space-y-1 py-1">
                                                            <span className="text-[10px] font-black text-slate-500 uppercase block">Kolom {colIdx + 1}</span>
                                                            <Select
                                                                value={mappings[colIdx] || 'ignore'}
                                                                onValueChange={(val) => {
                                                                    const next = [...mappings];
                                                                    next[colIdx] = val;
                                                                    setMappings(next);
                                                                }}
                                                            >
                                                                <SelectTrigger className="h-7 text-[11px] bg-white border-slate-300 font-semibold focus:ring-1 focus:ring-red-500">
                                                                    <SelectValue />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {MAPPING_OPTIONS.map((opt) => (
                                                                        <SelectItem key={opt.value} value={opt.value} className="text-xs">
                                                                            {opt.label}
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                    </TableHead>
                                                ))}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody className="bg-white">
                                            {parsedRows.slice(hasHeaderRow ? 1 : 0).map((rowCols, rIdx) => (
                                                <TableRow key={rIdx} className="hover:bg-slate-50/50">
                                                    <TableCell className="p-2 text-center text-xs font-bold text-slate-400">{rIdx + 1}</TableCell>
                                                    {Array.from({ length: maxCols }).map((_, cIdx) => {
                                                        const val = rowCols[cIdx] || '';
                                                        const field = mappings[cIdx] || 'ignore';
                                                        
                                                        if (field === 'ignore') {
                                                            return (
                                                                <TableCell key={cIdx} className="p-2 text-xs text-slate-400 bg-slate-50/30 italic border-l border-slate-100">
                                                                    {val || '—'}
                                                                </TableCell>
                                                            );
                                                        }
                                                        
                                                        if (field === 'size_id' || field === 'size_celana_id') {
                                                            const needle = val.toLowerCase();
                                                            const matched = sizes.find(
                                                                (s) =>
                                                                    s.ukuran?.toLowerCase() === needle ||
                                                                    `${s.kategori_size} - ${s.ukuran}`.toLowerCase() === needle
                                                            );
                                                            return (
                                                                <TableCell key={cIdx} className="p-2 text-xs border-l border-slate-100">
                                                                    {matched ? (
                                                                        <span className="text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                                                            {matched.kategori_size} - {matched.ukuran}
                                                                        </span>
                                                                    ) : (
                                                                        <span className="text-orange-500 font-semibold bg-orange-50 px-1.5 py-0.5 rounded border border-orange-200">
                                                                            {val || '—'} (tidak cocok)
                                                                        </span>
                                                                    )}
                                                                </TableCell>
                                                            );
                                                        }
                                                        
                                                        return (
                                                            <TableCell key={cIdx} className="p-2 text-xs font-medium text-slate-800 border-l border-slate-100">
                                                                {val || '—'}
                                                            </TableCell>
                                                        );
                                                    })}
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
                
                <DialogFooter className="mt-4 pt-4 border-t border-slate-100">
                    <Button variant="outline" size="sm" onClick={() => { setText(''); setMappings([]); setHasHeaderRow(false); setPrevMaxCols(0); onClose(); }}>
                        Batal
                    </Button>
                    <Button size="sm" className="bg-red-600 hover:bg-red-500 text-white font-bold" disabled={!finalRows.length} onClick={handleConfirm}>
                        Impor {finalRows.length} Baris Nameset
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
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

function ItemCard({ index, item, masters, onChange, onRemove, onDuplicate, onMoveUp, onMoveDown, isFirst, isLast, namaPo = '' }) {
    const [cardOpen, setCardOpen] = useState(true);
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
        const nextNamesets = [...item.namesets, newNameset()];
        onChange(index, { ...item, namesets: nextNamesets, quantity: nextNamesets.length });
    }
    function removeNameset(i) {
        const nextNamesets = item.namesets.filter((_, idx) => idx !== i);
        onChange(index, { ...item, namesets: nextNamesets, quantity: nextNamesets.length });
    }
    function patchNameset(i, field, value) {
        const next = [...item.namesets];
        next[i] = { ...next[i], [field]: value };
        if (field === 'size_id') {
            const s = masters.sizes.find((x) => x.id === value);
            next[i].size_label = s ? `${s.kategori_size} - ${s.ukuran}` : '';
        }
        if (field === 'size_celana_id') {
            const s = masters.sizes.find((x) => x.id === value);
            next[i].size_celana_label = s ? `${s.kategori_size} - ${s.ukuran}` : '';
        }
        onChange(index, { ...item, namesets: next });
    }

    const subtotal = (Number(item.quantity) || 0) * (Number(item.harga_satuan) || 0);
    const totalPcs = item.is_addon ? (Number(item.quantity) || 0) : item.namesets.length;

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
            <div 
                className="bg-slate-800 p-4 flex justify-between items-center cursor-pointer select-none hover:bg-slate-700 transition"
                onClick={() => setCardOpen((v) => !v)}
            >
                <div className="flex items-center gap-3 flex-1 min-w-0">
                    {cardOpen ? <ChevronUp className="h-4 w-4 text-slate-300 shrink-0" /> : <ChevronDown className="h-4 w-4 text-slate-300 shrink-0" />}
                    <span className="text-white font-black text-sm uppercase tracking-widest whitespace-nowrap">
                        {item.is_addon ? `ADD-ON #${index + 1}` : `PRODUK #${index + 1}`}
                    </span>
                    {item.nama_produk && (
                        <span className="text-slate-300 text-xs font-bold uppercase truncate">
                            — {item.nama_produk}
                            {item.varian_label && ` (${item.varian_label})`}
                        </span>
                    )}
                    {totalPcs > 0 && (
                        <span className="ml-2 bg-red-600 text-white text-xs font-black px-2 py-0.5 rounded-full whitespace-nowrap">
                            {totalPcs} PCS
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-2" onClick={(e) => e.stopPropagation()}>
                    <button
                        type="button"
                        onClick={() => onMoveUp(index)}
                        disabled={isFirst}
                        className="text-slate-300 hover:text-white bg-slate-700 hover:bg-slate-600 disabled:opacity-30 disabled:cursor-not-allowed rounded p-1.5 transition"
                        title="Pindahkan ke Atas"
                    >
                        <ArrowUp className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        onClick={() => onMoveDown(index)}
                        disabled={isLast}
                        className="text-slate-300 hover:text-white bg-slate-700 hover:bg-slate-600 disabled:opacity-30 disabled:cursor-not-allowed rounded p-1.5 transition"
                        title="Pindahkan ke Bawah"
                    >
                        <ArrowDown className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        onClick={() => onDuplicate(index)}
                        className="text-blue-400 hover:text-blue-300 bg-slate-700 hover:bg-slate-600 rounded p-1.5 transition"
                        title="Gandakan Produk"
                    >
                        <Copy className="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        onClick={() => onRemove(index)}
                        className="text-red-400 hover:text-red-300 bg-slate-700 hover:bg-slate-600 rounded p-1.5 transition"
                        title="Hapus Produk"
                    >
                        <Trash2 className="h-4 w-4" />
                    </button>
                </div>
            </div>

            {cardOpen && (
                <div className="p-5 space-y-5 bg-slate-50">
                {/* Info Dasar Produk */}
                <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <div className="flex items-center justify-between border-b-2 border-slate-200 pb-2 mb-4">
                        <span className="text-sm font-black text-slate-800 uppercase tracking-wide">Identitas Produk</span>
                        <label className="flex items-center gap-1.5 cursor-pointer select-none text-[11px] font-black text-slate-700 uppercase">
                            <input
                                type="checkbox"
                                className="w-3.5 h-3.5 rounded border-slate-300 text-red-600 focus:ring-red-500"
                                checked={!!item.is_addon}
                                onChange={(e) => patch('is_addon', e.target.checked)}
                            />
                            Add-on Produk
                        </label>
                    </div>
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
                            <Input 
                                type="number" 
                                min={1} 
                                value={item.quantity === 0 ? '' : item.quantity} 
                                onChange={(e) => {
                                    const val = e.target.value === '' ? 0 : Number(e.target.value);
                                    if (item.is_addon) {
                                        patch('quantity', val);
                                    } else {
                                        const currentLen = item.namesets.length;
                                        let nextNamesets = [...item.namesets];
                                        if (val > currentLen) {
                                            const diff = val - currentLen;
                                            for (let i = 0; i < diff; i++) {
                                                nextNamesets.push(newNameset());
                                            }
                                        } else if (val < currentLen) {
                                            nextNamesets = nextNamesets.slice(0, val);
                                        }
                                        onChange(index, { 
                                            ...item, 
                                            quantity: val, 
                                            namesets: nextNamesets 
                                        });
                                    }
                                }} 
                                className="h-8 text-xs font-bold" 
                            />
                        </div>
                        <div>
                            <FieldLabel>Harga Satuan</FieldLabel>
                            <Input type="number" min={0} value={item.harga_satuan === 0 ? '' : item.harga_satuan} onChange={(e) => patch('harga_satuan', e.target.value === '' ? 0 : Number(e.target.value))} className="h-8 text-xs" />
                        </div>
                        <div className="col-span-2 flex items-end justify-end">
                            <div className="rounded-lg border-2 border-slate-200 bg-slate-100 px-4 py-1.5 text-right">
                                <p className="text-[10px] font-bold text-slate-500 uppercase">Subtotal</p>
                                <p className="font-mono font-black text-sm text-slate-800">{formatRupiah(subtotal)}</p>
                            </div>
                        </div>
                    </div>
                    {item.is_addon && (
                        <div className="mt-3 border-t border-slate-100 pt-3">
                            <FieldLabel>Keterangan Add-on</FieldLabel>
                            <Input value={item.catatan || ''} onChange={(e) => patch('catatan', e.target.value)} className="h-8 text-xs" placeholder="Misal: Nameset Polyflex, upgrade bahan, dll..." />
                        </div>
                    )}
                </div>

                {!item.is_addon && (
                    <>
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
                            {/* Spesifikasi Produk */}
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <div className="flex flex-col">
                                    <FieldLabel>Jenis Setelan</FieldLabel>
                                    <SearchableSelect
                                        value={item.jenis_setelan_id || ''}
                                        onValueChange={(v) => patch('jenis_setelan_id', v)}
                                        options={(masters.jenis_setelan ?? []).map((j) => ({ value: j.id, label: j.nama }))}
                                        placeholder="— Pilih jenis setelan —"
                                        className="h-8 text-xs"
                                    />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Pola Produksi</FieldLabel>
                                    <SearchableSelect
                                        value={item.pola_produksi_id || ''}
                                        onValueChange={(v) => patch('pola_produksi_id', v)}
                                        options={(masters.pola_produksi ?? []).map((p) => ({ value: p.id, label: p.nama }))}
                                        placeholder="— Pilih pola —"
                                        className="h-8 text-xs"
                                    />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Bahan Atasan</FieldLabel>
                                    <MultiSelect
                                        value={item.bahan_kain_ids || []}
                                        onChange={(ids) => patch('bahan_kain_ids', ids)}
                                        options={masters.bahan_kains.map((b) => ({ value: b.id, label: b.nama }))}
                                        placeholder="— Pilih bahan atasan —"
                                        className="text-xs"
                                    />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Bahan Bawahan</FieldLabel>
                                    <MultiSelect
                                        value={item.bahan_kain_bawahan_ids || []}
                                        onChange={(ids) => patch('bahan_kain_bawahan_ids', ids)}
                                        options={masters.bahan_kains.map((b) => ({ value: b.id, label: b.nama }))}
                                        placeholder="— Pilih bahan bawahan —"
                                        className="text-xs"
                                    />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Warna</FieldLabel>
                                    <Input value={item.warna} onChange={(e) => patch('warna', e.target.value)} className="h-8 text-xs uppercase" placeholder="Merah / Biru / Mix" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Jml Atasan</FieldLabel>
                                    <Input value={item.jml_atasan} onChange={(e) => patch('jml_atasan', e.target.value)} className="h-8 text-xs" placeholder="Jumlah atasan" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Jml Bawahan</FieldLabel>
                                    <Input value={item.jml_bawahan} onChange={(e) => patch('jml_bawahan', e.target.value)} className="h-8 text-xs" placeholder="Jumlah bawahan" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Logo</FieldLabel>
                                    <MultiSelect
                                        value={item.logo_ids || []}
                                        onChange={(ids) => patch('logo_ids', ids)}
                                        options={masters.logos.map((l) => ({ value: l.id, label: l.nama }))}
                                        placeholder="— Pilih logo —"
                                        className="text-xs"
                                    />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Jenis RIB</FieldLabel>
                                    <Input value={item.jenis_rib} onChange={(e) => patch('jenis_rib', e.target.value)} className="h-8 text-xs" placeholder="Jenis RIB" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>Tutup Kerah</FieldLabel>
                                    <Input value={item.tutup_kerah} onChange={(e) => patch('tutup_kerah', e.target.value)} className="h-8 text-xs" placeholder="Tutup kerah" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>List Kerah</FieldLabel>
                                    <Input value={item.list_kerah} onChange={(e) => patch('list_kerah', e.target.value)} className="h-8 text-xs" placeholder="List kerah" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>List Lengan</FieldLabel>
                                    <Input value={item.list_lengan} onChange={(e) => patch('list_lengan', e.target.value)} className="h-8 text-xs" placeholder="List lengan" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>List Samping Celana</FieldLabel>
                                    <Input value={item.list_samping_celana} onChange={(e) => patch('list_samping_celana', e.target.value)} className="h-8 text-xs" placeholder="List samping celana" />
                                </div>
                                <div className="flex flex-col">
                                    <FieldLabel>List Bawah Celana</FieldLabel>
                                    <Input value={item.list_bawah_celana} onChange={(e) => patch('list_bawah_celana', e.target.value)} className="h-8 text-xs" placeholder="List bawah celana" />
                                </div>
                            </div>

                            {/* Keterangan Jahitan — 2 field dari Master Pola Jahitan */}
                            <div className="border-t border-slate-100 pt-3">
                                <p className="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-100 p-1.5 rounded text-center mb-3">Keterangan Jahitan</p>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="flex flex-col">
                                        <FieldLabel>Pola Jahitan</FieldLabel>
                                        <SearchableSelect
                                            value={item.pola_jahitan_id || ''}
                                            onValueChange={(v) => patch('pola_jahitan_id', v)}
                                            options={masters.pola_jahitans.map((p) => ({ value: p.id, label: `[${p.jenis_pola}] ${p.nama}` }))}
                                            placeholder="— Cari pola jahitan —"
                                            className="text-xs"
                                        />
                                    </div>
                                    <div className="flex flex-col">
                                        <FieldLabel>Jahitan List Lengan</FieldLabel>
                                        <SearchableSelect
                                            value={item.pola_jahitan_lengan_id || ''}
                                            onValueChange={(v) => patch('pola_jahitan_lengan_id', v)}
                                            options={(masters.pola_jahitans_lengan ?? masters.pola_jahitans).map((p) => ({ value: p.id, label: p.nama }))}
                                            placeholder="— Cari jahitan lengan —"
                                            className="text-xs"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Keterangan Resleting */}
                            <div className="border-t border-slate-100 pt-3">
                                <p className="text-[10px] font-black text-slate-500 uppercase tracking-wider bg-slate-100 p-1.5 rounded text-center mb-3">Keterangan Resleting</p>
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div className="flex flex-col">
                                        <FieldLabel>Resleting</FieldLabel>
                                        <Select value={item.resleting_id || NONE} onValueChange={(v) => patch('resleting_id', v === NONE ? '' : v)}>
                                            <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="—" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value={NONE}>— Tidak diset —</SelectItem>
                                                {masters.resletings.map((r) => (<SelectItem key={r.id} value={r.id}>{r.nama}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </div>

                            {/* Referensi Desain & Kerah */}
                            <div className="border-t border-slate-100 pt-3">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    {/* Kolom kiri: Desain */}
                                    <div className="space-y-2">
                                        <FieldLabel>1. Referensi Desain</FieldLabel>
                                        <ImageUploader
                                            value={item.gambar_desain || null}
                                            onChange={(p) => patch('gambar_desain', p || '')}
                                            purpose="orders"
                                            aspect={4 / 3}
                                            namaPo={namaPo || null}
                                            label="Upload Gambar Desain"
                                        />
                                        <div className="flex flex-col">
                                            <FieldLabel>Ket. Atasan</FieldLabel>
                                            <Input value={item.ket_atasan} onChange={(e) => patch('ket_atasan', e.target.value)} className="h-8 text-xs uppercase" placeholder="Keterangan atasan" />
                                        </div>
                                        <div className="flex flex-col">
                                            <FieldLabel>Ket. Bawahan</FieldLabel>
                                            <Input value={item.ket_bawahan} onChange={(e) => patch('ket_bawahan', e.target.value)} className="h-8 text-xs uppercase" placeholder="Keterangan bawahan" />
                                        </div>
                                    </div>

                                    {/* Kolom kanan: Kerah + Ket Tambahan */}
                                    <div className="space-y-2">
                                        <FieldLabel>2. Referensi Kerah</FieldLabel>
                                        <ImageUploader
                                            value={item.gambar_kerah || null}
                                            onChange={(p) => patch('gambar_kerah', p || '')}
                                            purpose="orders"
                                            aspect={1}
                                            namaPo={namaPo || null}
                                            label="Upload Gambar Kerah"
                                        />
                                        <div className="flex flex-col">
                                            <FieldLabel>Jenis Kerah</FieldLabel>
                                            <Input value={item.jenis_kerah} onChange={(e) => patch('jenis_kerah', e.target.value)} className="h-8 text-xs uppercase" placeholder="Jenis kerah" />
                                        </div>
                                        <FieldLabel className="mt-2">3. Keterangan Tambahan Gambar</FieldLabel>
                                        <ImageUploader
                                            value={item.gambar_ket_tambahan || null}
                                            onChange={(p) => patch('gambar_ket_tambahan', p || '')}
                                            purpose="orders"
                                            aspect={4 / 3}
                                            namaPo={namaPo || null}
                                            label="Upload Gambar Ket. Tambahan (Opsional)"
                                        />
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
                                            <th className="p-2 border-b font-bold w-8 text-center">No</th>
                                            <th className="p-2 border-b font-bold min-w-[120px]">Nama Punggung</th>
                                            <th className="p-2 border-b font-bold w-20 text-center">No. Punggung</th>
                                            <th className="p-2 border-b font-bold min-w-[120px]">Nama Dada</th>
                                            <th className="p-2 border-b font-bold w-20 text-center">No. Dada</th>
                                            <th className="p-2 border-b font-bold min-w-[120px]">Nama Lengan</th>
                                            <th className="p-2 border-b font-bold w-20 text-center">No. Lengan</th>
                                            <th className="p-2 border-b font-bold w-20 text-center">No. Punggung 2</th>
                                            <th className="p-2 border-b font-bold min-w-[120px]">Nama Punggung 2</th>
                                            <th className="p-2 border-b font-bold w-36 text-center">Size Atasan</th>
                                            <th className="p-2 border-b font-bold w-36 text-center">Size Celana</th>
                                            <th className="p-2 border-b font-bold min-w-[100px]">Keterangan</th>
                                            <th className="p-2 border-b font-bold w-8 text-center">X</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {item.namesets.length === 0 && (
                                            <tr>
                                                <td colSpan={13} className="p-6 text-center text-xs text-slate-400 italic">
                                                    Belum ada nameset. Klik "Tambah Baris" untuk mulai.
                                                </td>
                                            </tr>
                                        )}
                                        {item.namesets.map((ns, i) => (
                                            <tr key={i} className="border-b border-gray-100 hover:bg-gray-50">
                                                <td className="p-1.5 text-center text-xs font-bold text-slate-500">{i + 1}</td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nama_punggung || ''} onChange={(e) => patchNameset(i, 'nama_punggung', e.target.value)} className="h-7 text-xs font-medium uppercase" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nomor_punggung || ''} onChange={(e) => patchNameset(i, 'nomor_punggung', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nama_dada || ''} onChange={(e) => patchNameset(i, 'nama_dada', e.target.value)} className="h-7 text-xs font-medium uppercase" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nomor_dada || ''} onChange={(e) => patchNameset(i, 'nomor_dada', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nama_lengan || ''} onChange={(e) => patchNameset(i, 'nama_lengan', e.target.value)} className="h-7 text-xs font-medium uppercase" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nomor_lengan || ''} onChange={(e) => patchNameset(i, 'nomor_lengan', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nomor_punggung_2 || ''} onChange={(e) => patchNameset(i, 'nomor_punggung_2', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nama_punggung_2 || ''} onChange={(e) => patchNameset(i, 'nama_punggung_2', e.target.value)} className="h-7 text-xs font-medium uppercase" />
                                                </td>
                                                <td className="p-1.5">
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
                                                <td className="p-1.5">
                                                    <Select value={ns.size_celana_id || NONE} onValueChange={(v) => patchNameset(i, 'size_celana_id', v === NONE ? '' : v)}>
                                                        <SelectTrigger className="h-7 text-xs"><SelectValue placeholder="Pilih" /></SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value={NONE}>— —</SelectItem>
                                                            {masters.sizes.map((s) => (
                                                                <SelectItem key={s.id} value={s.id}>{s.kategori_size} - {s.ukuran}</SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.keterangan || ''} onChange={(e) => patchNameset(i, 'keterangan', e.target.value)} className="h-7 text-xs" />
                                                </td>
                                                <td className="p-1.5 text-center">
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
                    </>
                )}
            </div>
            )}

            <PasteNamesetDialog
                open={pasteOpen}
                onClose={() => setPasteOpen(false)}
                sizes={masters.sizes}
                onConfirm={(rows) => {
                    const nextNamesets = [...item.namesets, ...rows];
                    onChange(index, { ...item, namesets: nextNamesets, quantity: nextNamesets.length });
                }}
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
                        <SelectItem value="ongkir">Ongkir</SelectItem>
                        <SelectItem value="cashback">Cashback</SelectItem>
                        <SelectItem value="tambahan_produk">Tambahan Produk</SelectItem>
                        <SelectItem value="return">Return</SelectItem>
                        <SelectItem value="lainnya">Lainnya</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div className="sm:col-span-3">
                <FieldLabel>Nominal</FieldLabel>
                <Input type="number" min={0} value={payment.amount === 0 ? '' : payment.amount} onChange={(e) => onChange(index, { ...payment, amount: e.target.value === '' ? 0 : Number(e.target.value) })} className="mt-1 h-8 text-xs font-mono font-bold" />
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

export default function OrderForm({ mode, masters, order, reseller_branches = [], is_reseller_hub = false }) {
    const isEdit = mode === 'edit';

    const { data, setData, post, put, processing, errors } = useForm({
        nama_po: order?.nama_po ?? '',
        is_special_order: order?.is_special_order ?? false,
        tanggal_masuk: order?.tanggal_masuk?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10),
        deadline_customer: order?.deadline_customer?.slice?.(0, 10) ?? '',
        jenis_order_id: order?.jenis_order_id ?? '',
        sumber_order_id: order?.sumber_order_id ?? '',
        paket_order_id: order?.paket_order_id ?? '',
        pelanggan_id: order?.pelanggan_id ?? '',
        branch_brand_id: '',
        printing_ids: order?.printing_ids ?? [],
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

    // Toggle jenis produk — centang = tambah modul produksi, uncentang = hapus modul
    function toggleProduct(jenisProduk) {
        const existingIdx = data.items.findIndex((i) => i.jenis_produk_id === jenisProduk.id);
        if (existingIdx >= 0) {
            const item = data.items[existingIdx];
            if (item.namesets.length > 0 && !confirm(`Hapus modul "${jenisProduk.nama}"? Data nameset akan hilang.`)) return;
            setData('items', data.items.filter((_, i) => i !== existingIdx));
        } else {
            setData('items', [...data.items, {
                ...newItem(),
                jenis_produk_id: jenisProduk.id,
                nama_produk: jenisProduk.nama,
                // harga_satuan: 0 — diisi manual atau pilih dari dropdown Produk Brand
            }]);
        }
    }

    // Tambah item manual (tanpa produk dari master)
    function addItem() { setData('items', [...data.items, newItem()]); }
    function removeItem(i) { setData('items', data.items.filter((_, idx) => idx !== i)); }
    function duplicateItem(index) {
        const itemToCopy = data.items[index];
        const copiedItem = {
            ...itemToCopy,
            namesets: itemToCopy.namesets.map((ns) => ({ ...ns })),
        };
        const nextItems = [...data.items];
        nextItems.splice(index + 1, 0, copiedItem);
        setData('items', nextItems);
    }

    function moveItemUp(index) {
        if (index === 0) return;
        const nextItems = [...data.items];
        const temp = nextItems[index];
        nextItems[index] = nextItems[index - 1];
        nextItems[index - 1] = temp;
        setData('items', nextItems);
    }

    // Move item down
    function moveItemDown(index) {
        if (index === data.items.length - 1) return;
        const nextItems = [...data.items];
        const temp = nextItems[index];
        nextItems[index] = nextItems[index + 1];
        nextItems[index + 1] = temp;
        setData('items', nextItems);
    }

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

    const coreItems = useMemo(() => data.items.filter(i => !i.is_addon), [data.items]);
    const addonItems = useMemo(() => data.items.filter(i => i.is_addon), [data.items]);

    const totalCorePcs = useMemo(() => coreItems.reduce((s, i) => s + i.namesets.length, 0), [coreItems]);
    const totalAddonPcs = useMemo(() => addonItems.reduce((s, i) => s + (Number(i.quantity) || 0), 0), [addonItems]);
    const totalPcs = totalCorePcs + totalAddonPcs;

    const totalCoreHarga = useMemo(() => coreItems.reduce((s, i) => s + (Number(i.quantity) || 0) * (Number(i.harga_satuan) || 0), 0), [coreItems]);
    const totalAddonHarga = useMemo(() => addonItems.reduce((s, i) => s + (Number(i.quantity) || 0) * (Number(i.harga_satuan) || 0), 0), [addonItems]);
    const totalHarga = totalCoreHarga + totalAddonHarga;

    const pageTitle = isEdit ? `Edit PO ${order.no_po}` : 'Buat PO Baru';

    const [pdfLoading, setPdfLoading] = useState(false);
    async function downloadDraftPdf() {
        setPdfLoading(true);
        try {
            const xsrf = document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '';
            const res = await fetch(route('orders.pdf-draft'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(xsrf) },
                body: JSON.stringify(data),
            });
            if (!res.ok) { alert('Gagal generate PDF'); return; }
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `SPK-DRAFT-${new Date().toISOString().slice(0,10)}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } finally {
            setPdfLoading(false);
        }
    }

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
                        <button
                            type="button"
                            onClick={downloadDraftPdf}
                            disabled={pdfLoading}
                            className="flex items-center gap-2 bg-slate-600 hover:bg-slate-500 text-white px-4 py-2 rounded-lg text-sm font-bold transition uppercase tracking-wide disabled:opacity-50"
                        >
                            <FileDown className="h-4 w-4" />
                            {pdfLoading ? 'Loading...' : 'Download PDF'}
                        </button>
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
                                    <FieldLabel>Nama Order (Tim / PO) <span className="text-red-500">*</span></FieldLabel>
                                    <Input value={data.nama_po} onChange={(e) => setData('nama_po', e.target.value)} className="h-8 text-sm font-bold uppercase" placeholder="Contoh: PO Klub Garuda Mei" />
                                    {errors.nama_po && <p className="mt-1 text-xs text-red-500">{errors.nama_po}</p>}
                                </div>

                                <div className="flex flex-col col-span-2">
                                    <FieldLabel>Pelanggan <span className="text-red-500">*</span></FieldLabel>
                                    <SearchableSelect
                                        value={data.pelanggan_id}
                                        onValueChange={(v) => setData('pelanggan_id', v)}
                                        options={masters.pelanggan.map((p) => ({ value: p.id, label: `${p.kode} — ${p.nama}` }))}
                                        placeholder="Pilih pelanggan"
                                    />
                                    {errors.pelanggan_id && <p className="mt-1 text-xs text-red-500">{errors.pelanggan_id}</p>}
                                </div>
                                {/* Selector Brand Reseller: tampil ketika admin_reseller punya multiple brands */}
                                {is_reseller_hub && !isEdit && reseller_branches.length > 0 && (
                                    <div className="flex flex-col col-span-2">
                                        <FieldLabel>
                                            Brand Reseller
                                            <span className="ml-1 text-[10px] text-muted-foreground font-normal">(kosongkan = pakai brand aktif saat ini)</span>
                                        </FieldLabel>
                                        <SearchableSelect
                                            value={data.branch_brand_id}
                                            onValueChange={(v) => setData('branch_brand_id', v)}
                                            options={reseller_branches.map((b) => ({ value: b.id, label: `${b.kode} — ${b.nama_brand}` }))}
                                            placeholder="— Pakai brand aktif (default) —"
                                        />
                                        {errors.branch_brand_id && <p className="mt-1 text-xs text-red-500">{errors.branch_brand_id}</p>}
                                    </div>
                                )}
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Jenis Order</FieldLabel>
                                    <SearchableSelect
                                        value={data.jenis_order_id}
                                        onValueChange={(v) => setData('jenis_order_id', v)}
                                        options={(masters.jenis_orders ?? []).map((j) => ({ value: j.id, label: j.nama }))}
                                        placeholder="— Tidak diset —"
                                    />
                                </div>
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Sumber Order</FieldLabel>
                                    <SearchableSelect
                                        value={data.sumber_order_id}
                                        onValueChange={(v) => setData('sumber_order_id', v)}
                                        options={masters.sumber_orders.map((s) => ({ value: s.id, label: s.nama }))}
                                        placeholder="— Tidak diset —"
                                    />
                                </div>
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Paket Order</FieldLabel>
                                    <SearchableSelect
                                        value={data.paket_order_id}
                                        onValueChange={(v) => setData('paket_order_id', v)}
                                        options={(masters.paket_orders ?? []).map((p) => ({
                                            value: p.id,
                                            label: p.nama,
                                            // warna ditampilkan sebagai dot di label
                                        }))}
                                        placeholder="— Normal (default) —"
                                        renderOption={(p) => (
                                            <span className="flex items-center gap-2">
                                                <span className="h-2.5 w-2.5 rounded-full shrink-0" style={{ background: p.warna || '#6B7280' }} />
                                                {p.label}
                                            </span>
                                        )}
                                    />
                                </div>
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Jenis Printing</FieldLabel>
                                    <MultiSelect
                                        value={data.printing_ids}
                                        onChange={(v) => setData('printing_ids', v)}
                                        options={masters.printings.map((p) => ({ value: p.id, label: p.nama }))}
                                        placeholder="— Tidak diset —"
                                    />
                                </div>
                                <div className="flex flex-col col-span-1">
                                    <FieldLabel>Iklan / Kampanye</FieldLabel>
                                    <SearchableSelect
                                        value={data.iklan_id}
                                        onValueChange={(v) => setData('iklan_id', v)}
                                        options={(masters.iklans ?? []).map((k) => ({ value: k.id, label: k.nama + (k.platform ? ` (${k.platform})` : '') }))}
                                        placeholder="— Tidak diset —"
                                    />
                                </div>

                                <div className="flex flex-col col-span-4">
                                    <FieldLabel>Catatan PO</FieldLabel>
                                    <Textarea value={data.catatan} placeholder="Catatan Khusus Pelanggan.." onChange={(e) => setData('catatan', e.target.value)} rows={2} className="text-sm resize-none" />
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
                            {(masters.jenis_produk ?? []).length > 0 && (
                                <div className="mt-5 border-t border-slate-200 pt-5">
                                    <div className="bg-blue-50 border border-blue-100 rounded-xl p-4">
                                        <p className="text-xs font-black text-blue-900 uppercase tracking-wide mb-3">
                                            Jenis Produk / Kategori Order (Pilih Untuk Membuka Modul)
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {(masters.jenis_produk ?? []).map((jp) => {
                                                const isChecked = data.items.some((i) => i.jenis_produk_id === jp.id);
                                                return (
                                                    <label
                                                        key={jp.id}
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
                                                            onChange={() => toggleProduct(jp)}
                                                        />
                                                        {jp.nama.toUpperCase()}
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
                                    <ItemCard
                                        key={idx}
                                        index={idx}
                                        item={item}
                                        masters={masters}
                                        onChange={patchItem}
                                        onRemove={removeItem}
                                        onDuplicate={duplicateItem}
                                        onMoveUp={moveItemUp}
                                        onMoveDown={moveItemDown}
                                        isFirst={idx === 0}
                                        isLast={idx === data.items.length - 1}
                                        namaPo={data.nama_po}
                                    />
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

                        {/* Produk Inti */}
                        {coreItems.length > 0 && (
                            <div className="space-y-2">
                                <p className="text-[10px] font-black text-slate-500 uppercase tracking-wide">Produk Inti ({totalCorePcs} pcs)</p>
                                {coreItems.map((item) => {
                                    const origIdx = data.items.indexOf(item);
                                    const color = ACCENT_COLORS[origIdx % ACCENT_COLORS.length];
                                    const dotMap = { red: 'bg-red-500', blue: 'bg-blue-500', emerald: 'bg-emerald-500', amber: 'bg-amber-500', purple: 'bg-purple-500', pink: 'bg-pink-500', teal: 'bg-teal-500' };
                                    return (
                                        <div key={origIdx} className="flex items-center justify-between bg-white border border-slate-200 rounded-lg px-3 py-2 shadow-sm">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <span className={`w-2 h-2 rounded-full flex-shrink-0 ${dotMap[color]}`} />
                                                <span className="text-xs font-bold text-slate-700 uppercase truncate">
                                                    {item.nama_produk || `Produk #${origIdx + 1}`}
                                                </span>
                                            </div>
                                            <div className="text-right ml-2 whitespace-nowrap">
                                                <p className="text-xs font-black text-slate-600">{item.namesets.length} pcs</p>
                                                <p className="text-[10px] font-mono text-slate-400">{formatRupiah((Number(item.quantity) || 0) * (Number(item.harga_satuan) || 0))}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                                <div className="text-right pr-2 pb-1">
                                    <p className="text-[10px] font-bold text-slate-500 uppercase">Subtotal Produk: <span className="font-mono font-black text-slate-700">{formatRupiah(totalCoreHarga)}</span></p>
                                </div>
                            </div>
                        )}

                        {/* Add-ons */}
                        {addonItems.length > 0 && (
                            <div className="space-y-2 pt-2 border-t border-dashed border-slate-200">
                                <p className="text-[10px] font-black text-slate-500 uppercase tracking-wide">Add-ons ({totalAddonPcs} pcs)</p>
                                {addonItems.map((item) => {
                                    const origIdx = data.items.indexOf(item);
                                    const color = ACCENT_COLORS[origIdx % ACCENT_COLORS.length];
                                    const dotMap = { red: 'bg-red-500', blue: 'bg-blue-500', emerald: 'bg-emerald-500', amber: 'bg-amber-500', purple: 'bg-purple-500', pink: 'bg-pink-500', teal: 'bg-teal-500' };
                                    return (
                                        <div key={origIdx} className="flex items-center justify-between bg-white border border-slate-200 rounded-lg px-3 py-2 shadow-sm">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <span className={`w-2 h-2 rounded-full flex-shrink-0 ${dotMap[color]}`} />
                                                <span className="text-xs font-bold text-slate-700 uppercase truncate">
                                                    {item.nama_produk || `Add-on #${origIdx + 1}`}
                                                </span>
                                            </div>
                                            <div className="text-right ml-2 whitespace-nowrap">
                                                <p className="text-xs font-black text-slate-600">{item.quantity} pcs</p>
                                                <p className="text-[10px] font-mono text-slate-400">{formatRupiah((Number(item.quantity) || 0) * (Number(item.harga_satuan) || 0))}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                                <div className="text-right pr-2">
                                    <p className="text-[10px] font-bold text-slate-500 uppercase">Subtotal Add-on: <span className="font-mono font-black text-slate-700">{formatRupiah(totalAddonHarga)}</span></p>
                                </div>
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
