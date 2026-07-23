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
        discount_type: '',
        discount_value: 0,
        discount_amount: 0,
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

function getCalculatedSubtotal(item) {
    const qty = Number(item.quantity) || 0;
    const price = Number(item.harga_satuan) || 0;
    const raw = qty * price;
    const discountType = item.discount_type || '';
    const discountValue = Number(item.discount_value) || 0;
    let amount = 0;
    if (discountType === 'persen') {
        amount = raw * (discountValue / 100);
    } else if (discountType === 'nominal') {
        amount = qty * discountValue;
    }
    return Math.max(0, raw - amount);
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
    return { master_jenis_pembayaran_id: '', amount: 0, payment_date: new Date().toISOString().slice(0, 10), bank_id: '', notes: '' };
}

function PasteNamesetDialog({ open, onClose, onConfirm, sizes = [], item = null, polaProduksi = [] }) {
    const [text, setText] = useState('');
    const [hasHeaderRow, setHasHeaderRow] = useState(false);
    const [mappings, setMappings] = useState([]);
    const [prevMaxCols, setPrevMaxCols] = useState(0);
    const [sizeOverrides, setSizeOverrides] = useState({});
    const [importMode, setImportMode] = useState('replace');

    const detectedDelimiter = useMemo(() => {
        if (!text) return null;
        let tabs = 0;
        let semicolons = 0;
        let commas = 0;
        for (let i = 0; i < text.length; i++) {
            const char = text[i];
            if (char === '\t') tabs++;
            else if (char === ';') semicolons++;
            else if (char === ',') commas++;
        }
        if (tabs > 0) return '\t';
        if (semicolons > 0) return ';';
        if (commas > 0) return ',';

        const lines = text.split(/\r\n|\r|\n/);
        let multiSpaceLinesCount = 0;
        let nonBlankLines = 0;
        lines.forEach(line => {
            const cleaned = line.replace(/\r$/, '');
            if (cleaned.trim()) {
                nonBlankLines++;
                if (/\s{2,}/.test(cleaned)) {
                    multiSpaceLinesCount++;
                }
            }
        });
        if (nonBlankLines > 0 && (multiSpaceLinesCount / nonBlankLines) >= 0.5) {
            return /\s{2,}/;
        }
        return null;
    }, [text]);

    const safeSizes = useMemo(() => {
        const arr = Array.isArray(sizes) ? [...sizes] : [];
        arr.sort((a, b) => (a.urutan || 0) - (b.urutan || 0));
        return arr;
    }, [sizes]);

    const parseInput = (inputStr) => {
        let clean = inputStr.toLowerCase().trim();
        if (!clean) return null;

        clean = clean.replace(/\bxxl\b/g, '2xl')
                     .replace(/\bxxxl\b/g, '3xl')
                     .replace(/\bxxxxl\b/g, '4xl')
                     .replace(/\bxxxxxl\b/g, '5xl')
                     .replace(/\s+/g, ' ');

        let sizePart = clean.replace(/\s+/g, '').toUpperCase();
        if (sizePart === 'XXL') sizePart = '2XL';
        if (sizePart === 'XXXL') sizePart = '3XL';
        if (sizePart === 'XXXXL') sizePart = '4XL';
        if (sizePart === 'XXXXXL') sizePart = '5XL';

        return { sizePart };
    };

    const resolveSize = (val) => {
        if (!val) return null;
        const parsed = parseInput(val);
        if (!parsed) return null;

        const { sizePart } = parsed;

        // Exact match
        const matched = safeSizes.find(
            (s) => s.ukuran?.toUpperCase() === sizePart
        );
        if (matched) return matched;

        // Fuzzy match
        const fuzzyMatches = safeSizes.filter(
            (s) => s.ukuran?.toLowerCase().replace(/\s+/g, '') === val.toLowerCase().replace(/\s+/g, '')
        );

        if (fuzzyMatches.length > 0) {
            return fuzzyMatches[0];
        }

        return null;
    };

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

    function splitLine(line, delimiter) {
        if (!line) return [];
        const cleanedLine = line.replace(/\r$/, '');
        if (delimiter) {
            return cleanedLine.split(delimiter).map((c) => c.trim());
        }
        return [cleanedLine.trim()];
    }

    function guessColumnField(colIdx, rows, mappedFieldsCount) {
        const values = rows.map(r => r[colIdx]).filter(v => v !== undefined && v !== null && v.trim() !== '');
        if (values.length === 0) return 'ignore';

        let sizeMatchCount = 0;
        let numericCount = 0;
        let stringCount = 0;

        const sizeWords = new Set(['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl', '8xl', '9xl', '10xl', 'xxl', 'xxxl', 's anak', 'm anak', 'l anak', 'xl anak', 'xs anak']);

        values.forEach(val => {
            const clean = val.toLowerCase().trim();
            if (sizeWords.has(clean)) {
                sizeMatchCount++;
            }
            if (/^\d+$/.test(clean)) {
                numericCount++;
            } else {
                stringCount++;
            }
        });

        if (sizeMatchCount / values.length >= 0.5) {
            if (mappedFieldsCount.size_id > 0) {
                return 'size_celana_id';
            }
            return 'size_id';
        }

        if (numericCount / values.length >= 0.7) {
            if (mappedFieldsCount.nomor_punggung === 0) return 'nomor_punggung';
            if (mappedFieldsCount.nomor_dada === 0) return 'nomor_dada';
            if (mappedFieldsCount.nomor_lengan === 0) return 'nomor_lengan';
            if (mappedFieldsCount.nomor_punggung_2 === 0) return 'nomor_punggung_2';
            return 'ignore';
        }

        if (stringCount / values.length >= 0.5) {
            const avgLength = values.reduce((sum, v) => sum + v.length, 0) / values.length;
            if (avgLength > 15) {
                return 'keterangan';
            }
            
            if (mappedFieldsCount.nama_punggung === 0) return 'nama_punggung';
            if (mappedFieldsCount.nama_dada === 0) return 'nama_dada';
            if (mappedFieldsCount.nama_lengan === 0) return 'nama_lengan';
            if (mappedFieldsCount.nama_punggung_2 === 0) return 'nama_punggung_2';
            return 'keterangan';
        }

        return 'ignore';
    }

    function detectHeaders(firstRowCols) {
        const matchedFields = [];
        let matchCount = 0;
        
        for (let i = 0; i < firstRowCols.length; i++) {
            const col = firstRowCols[i].toLowerCase().trim();
            let matched = 'ignore';
            
            if (col === 'no' || col === 'no.' || col === 'no_urut' || col === 'nomor urut' || col === 'number') {
                matched = 'ignore';
            }
            else if (col.includes('nama') && col.includes('punggung')) {
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
            isHeader: matchCount >= 1,
            fields: matchedFields
        };
    }

    function getDefaultMappings(rows, numCols) {
        const defaults = [];
        const mappedFieldsCount = {
            nama_punggung: 0,
            nomor_punggung: 0,
            nama_dada: 0,
            nomor_dada: 0,
            nama_lengan: 0,
            nomor_lengan: 0,
            nomor_punggung_2: 0,
            nama_punggung_2: 0,
            size_id: 0,
            size_celana_id: 0,
            keterangan: 0
        };
        
        for (let i = 0; i < numCols; i++) {
            const guessed = guessColumnField(i, rows, mappedFieldsCount);
            defaults.push(guessed);
            if (guessed !== 'ignore') {
                mappedFieldsCount[guessed] = (mappedFieldsCount[guessed] || 0) + 1;
            }
        }
        return defaults;
    }

    const parsedRows = useMemo(() => {
        try {
            if (!text.trim()) return [];
            const rows = text.split(/\r\n|\r|\n/)
                .map((line) => splitLine(line, detectedDelimiter))
                .filter((r) => r && r.length > 0 && r.some(Boolean));
            console.log('Parsed Excel Rows:', rows);
            return rows;
        } catch (e) {
            console.error('Error parsing pasted text:', e);
            return [];
        }
    }, [text, detectedDelimiter]);

    const maxCols = useMemo(() => {
        return parsedRows.length > 0 ? Math.max(...parsedRows.map((r) => r.length)) : 0;
    }, [parsedRows]);

    const handleHeaderRowToggle = (checked) => {
        setHasHeaderRow(checked);
        const firstRow = parsedRows[0] || [];
        if (checked) {
            const detection = detectHeaders(firstRow);
            setMappings(detection.fields);
        } else {
            setMappings(getDefaultMappings(parsedRows, maxCols));
        }
        setSizeOverrides({});
    };

    useEffect(() => {
        if (maxCols === 0) {
            setMappings([]);
            setHasHeaderRow(false);
            setPrevMaxCols(0);
            setSizeOverrides({});
            return;
        }

        if (maxCols !== prevMaxCols) {
            const firstRow = parsedRows[0] || [];
            const detection = detectHeaders(firstRow);
            
            setHasHeaderRow(detection.isHeader);
            setMappings(detection.isHeader ? detection.fields : getDefaultMappings(parsedRows, maxCols));
            setPrevMaxCols(maxCols);
            setSizeOverrides({});
        }
    }, [maxCols, parsedRows, prevMaxCols]);

    const finalRows = useMemo(() => {
        try {
            if (parsedRows.length === 0) return [];
            const startIndex = hasHeaderRow ? 1 : 0;
            const rowsToProcess = parsedRows.slice(startIndex);
            
            const results = rowsToProcess.map((cols, rIdx) => {
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
                        const overrideVal = sizeOverrides[`${rIdx}-size_id`];
                        if (overrideVal) {
                            const matched = safeSizes.find((s) => s.id === overrideVal);
                            if (matched) {
                                rowData.size_id = matched.id;
                                rowData.size_label = matched.ukuran;
                            }
                        } else {
                            // Skip empty values untuk mencegah data bergeser akibat gap
                            if (!val || val.trim() === '') {
                                rowData.size_id = '';
                                rowData.size_label = '';
                            } else {
                                const matched = resolveSize(val);
                                if (matched) {
                                    rowData.size_id = matched.id;
                                    rowData.size_label = matched.ukuran;
                                } else {
                                    rowData.size_id = '';
                                    rowData.size_label = val;
                                }
                            }
                        }
                    } else if (field === 'size_celana_id') {
                        const overrideVal = sizeOverrides[`${rIdx}-size_celana_id`];
                        if (overrideVal) {
                            const matched = safeSizes.find((s) => s.id === overrideVal);
                            if (matched) {
                                rowData.size_celana_id = matched.id;
                                rowData.size_celana_label = matched.ukuran;
                            }
                        } else {
                            // Skip empty values untuk mencegah data bergeser akibat gap
                            if (!val || val.trim() === '') {
                                rowData.size_celana_id = '';
                                rowData.size_celana_label = '';
                            } else {
                                const matched = resolveSize(val);
                                if (matched) {
                                    rowData.size_celana_id = matched.id;
                                    rowData.size_celana_label = matched.ukuran;
                                } else {
                                    rowData.size_celana_id = '';
                                    rowData.size_celana_label = val;
                                }
                            }
                        }
                    } else {
                        // Skip empty values untuk mencegah data bergeser akibat gap
                        if (val && val.trim() !== '') {
                            rowData[field] = val;
                        }
                    }
                });
                
                return rowData;
            });
            console.log('Final Prepared Rows:', results);
            return results;
        } catch (e) {
            console.error('Error generating final rows:', e);
            return [];
        }
    }, [parsedRows, mappings, hasHeaderRow, safeSizes, sizeOverrides]);

    function handleConfirm() {
        if (!finalRows.length) return;
        onConfirm(finalRows, importMode);
        setText('');
        setMappings([]);
        setHasHeaderRow(false);
        setPrevMaxCols(0);
        setSizeOverrides({});
        setImportMode('replace');
        onClose();
    }

    return (
        <Dialog open={open} onOpenChange={(v) => { if (!v) { setText(''); setMappings([]); setHasHeaderRow(false); setPrevMaxCols(0); setSizeOverrides({}); setImportMode('replace'); onClose(); } }}>
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
                        placeholder={"No\tNama Punggung\tNo. Punggung\tNama Dada\tNo. Dada\tNama Lengan\tNo. Lengan\tNo. Punggung 2\tNama Punggung 2\tSize Atasan\tSize Celana\tKeterangan\n1\tAhmad\t7\tAmd\t7\tAh\t7\t7B\tAhmad B\tS\tS\tLengan Panjang\n2\tBudi\t10\tBud\t10\tBu\t10\t10B\tBudi B\tM\tM\tKeterangan tambahan"}
                        value={text}
                        onChange={(e) => {
                            setText(e.target.value);
                            setSizeOverrides({});
                        }}
                        autoFocus
                    />
                    
                    {parsedRows.length > 0 && (
                        <div className="space-y-3 pt-2">
                            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-slate-50 border border-slate-200 p-3 rounded-xl">
                                <div className="flex flex-wrap items-center gap-4">
                                    <label className="flex items-center gap-2 cursor-pointer select-none text-xs font-bold text-slate-700 uppercase">
                                        <input
                                            type="checkbox"
                                            className="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500"
                                            checked={hasHeaderRow}
                                            onChange={(e) => {
                                                handleHeaderRowToggle(e.target.checked);
                                            }}
                                        />
                                        Baris pertama adalah Header (Abaikan baris pertama)
                                    </label>
                                    
                                    <div className="h-4 w-[1px] bg-slate-300 hidden sm:block"></div>
                                    
                                    <div className="flex items-center gap-3">
                                        <span className="text-[10px] font-black text-slate-500 uppercase">Metode Impor:</span>
                                        <label className="flex items-center gap-1.5 cursor-pointer select-none text-xs font-bold text-slate-700">
                                            <input
                                                type="radio"
                                                name="import_mode"
                                                value="replace"
                                                className="w-3.5 h-3.5 text-red-600 focus:ring-red-500 cursor-pointer"
                                                checked={importMode === 'replace'}
                                                onChange={() => setImportMode('replace')}
                                            />
                                            Ganti Data yang Ada
                                        </label>
                                        <label className="flex items-center gap-1.5 cursor-pointer select-none text-xs font-bold text-slate-700">
                                            <input
                                                type="radio"
                                                name="import_mode"
                                                value="append"
                                                className="w-3.5 h-3.5 text-red-600 focus:ring-red-500 cursor-pointer"
                                                checked={importMode === 'append'}
                                                onChange={() => setImportMode('append')}
                                            />
                                            Tambahkan
                                        </label>
                                    </div>
                                </div>
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
                                                                    setSizeOverrides({});
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
                                                            const overrideKey = `${rIdx}-${field}`;
                                                            const currentOverride = sizeOverrides[overrideKey];
                                                            const matched = currentOverride 
                                                                ? safeSizes.find(s => s.id === currentOverride)
                                                                : resolveSize(val);
                                                            
                                                            return (
                                                                <TableCell key={cIdx} className="p-1 min-w-[190px] border-l border-slate-100">
                                                                    <Select
                                                                        value={matched ? matched.id : NONE}
                                                                        onValueChange={(newId) => {
                                                                            setSizeOverrides(prev => ({
                                                                                ...prev,
                                                                                [overrideKey]: newId === NONE ? '' : newId
                                                                            }));
                                                                        }}
                                                                    >
                                                                        <SelectTrigger className={`h-8 text-xs font-bold ${matched ? 'text-emerald-700 bg-emerald-50 border-emerald-300' : 'text-orange-700 bg-orange-50 border-orange-300'}`}>
                                                                            <SelectValue />
                                                                        </SelectTrigger>
                                                                        <SelectContent className="max-h-56">
                                                                            <SelectItem value={NONE} className="text-xs italic">— Tidak Cocok ({val || 'kosong'}) —</SelectItem>
                                                                            {safeSizes.map((s) => (
                                                                                <SelectItem key={s.id} value={s.id} className="text-xs">
                                                                                    {s.ukuran}
                                                                                </SelectItem>
                                                                            ))}
                                                                        </SelectContent>
                                                                    </Select>
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
                    <Button variant="outline" size="sm" onClick={() => { setText(''); setMappings([]); setHasHeaderRow(false); setPrevMaxCols(0); setSizeOverrides({}); onClose(); }}>
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
    function clearNameset() {
        if (window.confirm("Apakah Anda yakin ingin menghapus semua data nameset untuk produk ini?")) {
            onChange(index, { ...item, namesets: [], quantity: 0 });
        }
    }
    function patchNameset(i, field, value) {
        const next = [...item.namesets];
        next[i] = { ...next[i], [field]: value };
        if (field === 'size_id') {
            const s = masters.sizes.find((x) => x.id === value);
            next[i].size_label = s ? s.ukuran : '';
        }
        if (field === 'size_celana_id') {
            const s = masters.sizes.find((x) => x.id === value);
            next[i].size_celana_label = s ? s.ukuran : '';
        }
        onChange(index, { ...item, namesets: next });
    }

    const subtotal = getCalculatedSubtotal(item);
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
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-5 mt-3">
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
                        <div>
                            <FieldLabel>Tipe Diskon</FieldLabel>
                            <Select value={item.discount_type || NONE} onValueChange={(v) => {
                                const nextVal = v === NONE ? '' : v;
                                const next = { ...item, discount_type: nextVal };
                                if (nextVal === '') {
                                    next.discount_value = 0;
                                    next.discount_amount = 0;
                                }
                                onChange(index, next);
                            }}>
                                <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="Tanpa Diskon" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NONE}>Tanpa Diskon</SelectItem>
                                    <SelectItem value="persen">Persen (%)</SelectItem>
                                    <SelectItem value="nominal">Nominal (Rp)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <FieldLabel>Nilai Diskon</FieldLabel>
                            <Input 
                                type="number" 
                                min={0} 
                                disabled={!item.discount_type} 
                                value={item.discount_value === 0 ? '' : item.discount_value} 
                                onChange={(e) => {
                                    const val = e.target.value === '' ? 0 : Number(e.target.value);
                                    patch('discount_value', val);
                                }} 
                                className="h-8 text-xs font-bold" 
                                placeholder={item.discount_type === 'persen' ? '%' : 'Rp'}
                            />
                        </div>
                        <div className="flex items-end justify-end">
                            <div className="rounded-lg border-2 border-slate-200 bg-slate-100 px-4 py-1.5 text-right w-full">
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
                                            options={masters.pola_jahitans.filter((p) => p.jenis_pola === 'Pola').map((p) => ({ value: p.id, label: p.nama }))}
                                            placeholder="— Cari pola jahitan —"
                                            className="text-xs"
                                        />
                                    </div>
                                    <div className="flex flex-col">
                                        <FieldLabel>Jahitan List Lengan</FieldLabel>
                                        <SearchableSelect
                                            value={item.pola_jahitan_lengan_id || ''}
                                            onValueChange={(v) => patch('pola_jahitan_lengan_id', v)}
                                            options={(masters.pola_jahitans_lengan ?? masters.pola_jahitans).filter((p) => p.jenis_pola === 'Lengan').map((p) => ({ value: p.id, label: p.nama }))}
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
                                onClick={clearNameset}
                                disabled={item.namesets.length === 0}
                                className={`flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-bold transition shadow-sm ${
                                    item.namesets.length > 0
                                        ? 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 cursor-pointer'
                                        : 'bg-gray-50 text-gray-400 border border-gray-200 cursor-not-allowed opacity-60'
                                }`}
                            >
                                <Trash2 className="h-3.5 w-3.5" /> Hapus Data
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
                                                    <Input value={ns.nama_punggung || ''} onChange={(e) => patchNameset(i, 'nama_punggung', e.target.value)} className="h-7 text-xs font-medium" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nomor_punggung || ''} onChange={(e) => patchNameset(i, 'nomor_punggung', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nama_dada || ''} onChange={(e) => patchNameset(i, 'nama_dada', e.target.value)} className="h-7 text-xs font-medium" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nomor_dada || ''} onChange={(e) => patchNameset(i, 'nomor_dada', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nama_lengan || ''} onChange={(e) => patchNameset(i, 'nama_lengan', e.target.value)} className="h-7 text-xs font-medium" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nomor_lengan || ''} onChange={(e) => patchNameset(i, 'nomor_lengan', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nomor_punggung_2 || ''} onChange={(e) => patchNameset(i, 'nomor_punggung_2', e.target.value)} className="h-7 text-xs font-black text-center" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Input value={ns.nama_punggung_2 || ''} onChange={(e) => patchNameset(i, 'nama_punggung_2', e.target.value)} className="h-7 text-xs font-medium" />
                                                </td>
                                                <td className="p-1.5">
                                                    <Select value={ns.size_id || NONE} onValueChange={(v) => patchNameset(i, 'size_id', v === NONE ? '' : v)}>
                                                        <SelectTrigger className="h-7 text-xs"><SelectValue placeholder="Pilih" /></SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value={NONE}>— —</SelectItem>
                                                            {masters.sizes.map((s) => (
                                                                <SelectItem key={s.id} value={s.id}>{s.ukuran}</SelectItem>
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
                                                                <SelectItem key={s.id} value={s.id}>{s.ukuran}</SelectItem>
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
                item={item}
                polaProduksi={masters.pola_produksi}
                onConfirm={(rows, mode = 'replace') => {
                    let nextNamesets;
                    if (mode === 'replace') {
                        nextNamesets = rows;
                    } else {
                        const currentFilled = item.namesets.filter(
                            (ns) =>
                                ns.nama_punggung ||
                                ns.nomor_punggung ||
                                ns.nama_dada ||
                                ns.nomor_dada ||
                                ns.nama_lengan ||
                                ns.nomor_lengan ||
                                ns.nomor_punggung_2 ||
                                ns.nama_punggung_2 ||
                                ns.size_id ||
                                ns.size_celana_id ||
                                ns.keterangan
                        );
                        nextNamesets = [...currentFilled, ...rows];
                    }
                    onChange(index, { ...item, namesets: nextNamesets, quantity: nextNamesets.length });
                }}
            />
        </div>
    );
}


export default function OrderForm({ mode, masters, order, current_brand_id, reseller_branches = [], is_reseller_hub = false, reseller_hubs = [] }) {
    const isEdit = mode === 'edit';
    const [showSummaryDropdown, setShowSummaryDropdown] = useState(false);

    const activeResellerHubId = useMemo(() => {
        if (!current_brand_id || !reseller_hubs) return '';
        const matchingHub = reseller_hubs.find(h => h.id === current_brand_id);
        return matchingHub ? matchingHub.id : '';
    }, [current_brand_id, reseller_hubs]);

    const { data, setData, post, put, processing, errors } = useForm({
        nama_po: order?.nama_po ?? '',
        is_special_order: order?.is_special_order ?? false,
        is_free_ongkir: order?.is_free_ongkir ?? false,
        tipe_pengiriman: order?.tipe_pengiriman ?? (order?.is_free_ongkir ? 'free_ongkir' : (Number(order?.ongkir) > 0 ? 'ongkir' : 'ongkir')),
        is_reseller_price: order?.is_reseller_price ?? false,
        ongkir: order?.ongkir !== undefined ? Number(order.ongkir) : 0,
        voucher_discount_amount: order?.voucher_discount_amount !== undefined ? Number(order.voucher_discount_amount) : 0,
        tanggal_masuk: order?.tanggal_masuk?.slice?.(0, 10) ?? new Date().toISOString().slice(0, 10),
        deadline_customer: order?.deadline_customer?.slice?.(0, 10) ?? '',
        jenis_order_id: order?.jenis_order_id ?? '',
        sumber_order_id: order?.sumber_order_id ?? '',
        paket_order_id: order?.paket_order_id ?? '',
        pelanggan_id: order?.pelanggan_id ?? '',
        branch_brand_id: order?.branch_brand_id ?? '',
        reseller_display_brand_id: order ? (order.reseller_display_brand_id ?? '') : activeResellerHubId,
        bank_id: order?.bank_id ?? '',
        printing_ids: order?.printing_ids ?? [],
        iklan_id: order?.iklan_id ?? '',
        catatan: order?.catatan ?? '',
        items: (order?.items ?? []).map((i) => ({
            ...newItem(),
            product_id: i.product_id ?? '',
            jenis_produk_id: i.jenis_produk_id ?? '',
            nama_produk: i.nama_produk ?? '',
            varian_label: i.varian_label ?? '',
            quantity: Number(i.quantity) || 1,
            harga_satuan: Number(i.harga_satuan) || 0,
            discount_type: i.discount_type ?? '',
            discount_value: i.discount_value !== undefined ? Number(i.discount_value) : 0,
            discount_amount: i.discount_amount !== undefined ? Number(i.discount_amount) : 0,
            is_addon: i.is_addon ?? false,
            jenis_setelan_id: i.jenis_setelan_id ?? '',
            pola_produksi_id: i.pola_produksi_id ?? '',
            bahan_kain_id: i.bahan_kain_id ?? '',
            bahan_kain_ids: i.bahan_kain_ids ?? [],
            bahan_kain_bawahan_id: i.bahan_kain_bawahan_id ?? '',
            bahan_kain_bawahan_ids: i.bahan_kain_bawahan_ids ?? [],
            jenis_setelan: i.jenis_setelan ?? '',
            pola: i.pola ?? '',
            logo_id: i.logo_id ?? '',
            logo_ids: i.logo_ids ?? [],
            resleting_id: i.resleting_id ?? '',
            jenis_rib: i.jenis_rib ?? '',
            tutup_kerah: i.tutup_kerah ?? '',
            list_kerah: i.list_kerah ?? '',
            list_lengan: i.list_lengan ?? '',
            list_samping_celana: i.list_samping_celana ?? '',
            list_bawah_celana: i.list_bawah_celana ?? '',
            pola_jahitan_lengan_id: i.pola_jahitan_lengan_id ?? '',
            pola_jahitan_id: i.pola_jahitan_id ?? '',
            jahitan_list_lengan: i.jahitan_list_lengan ?? '',
            warna: i.warna ?? '',
            jml_atasan: i.jml_atasan ?? '',
            jml_bawahan: i.jml_bawahan ?? '',
            jenis_kerah: i.jenis_kerah ?? '',
            catatan: i.catatan ?? '',
            gambar_desain: i.gambar_desain ?? '',
            ket_atasan: i.ket_atasan ?? '',
            ket_bawahan: i.ket_bawahan ?? '',
            gambar_kerah: i.gambar_kerah ?? '',
            gambar_ket_tambahan: i.gambar_ket_tambahan ?? '',
            acc: i.acc ?? '',
            namesets: (i.namesets ?? []).map((ns) => ({
                id: ns.id ?? undefined,
                nama_punggung: ns.nama_punggung ?? '',
                nomor_punggung: ns.nomor_punggung ?? '',
                nama_dada: ns.nama_dada ?? '',
                nomor_dada: ns.nomor_dada ?? '',
                nama_lengan: ns.nama_lengan ?? '',
                nomor_lengan: ns.nomor_lengan ?? '',
                nomor_punggung_2: ns.nomor_punggung_2 ?? '',
                nama_punggung_2: ns.nama_punggung_2 ?? '',
                size_id: ns.size_id ?? '',
                size_celana_id: ns.size_celana_id ?? '',
                keterangan: ns.keterangan ?? '',
            })),
        })),
    });

    const filteredBanks = useMemo(() => {
        if (!masters.banks) return [];
        const activeId = data.branch_brand_id || current_brand_id;
        const brandBanks = masters.banks.filter(b => b.brand_id === activeId);
        if (brandBanks.length > 0) {
            return brandBanks;
        }
        return masters.banks;
    }, [masters.banks, data.branch_brand_id, current_brand_id]);

    useEffect(() => {
        if (data.bank_id && filteredBanks.length > 0) {
            const exists = filteredBanks.some(b => b.id === data.bank_id);
            if (!exists) {
                setData('bank_id', '');
            }
        }
    }, [filteredBanks]);

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



    function submit(e) {
        e.preventDefault();

        if (!navigator.onLine) {
            const offlineDrafts = JSON.parse(localStorage.getItem('offline_order_drafts') || '[]');
            const newDraft = {
                id: isEdit ? `edit_${order.id}_${Date.now()}` : `create_${Date.now()}`,
                isEdit: isEdit,
                orderId: isEdit ? order.id : null,
                data: { ...data },
                timestamp: new Date().toISOString(),
                nama_po: data.nama_po || 'Pesanan Tanpa Nama'
            };
            offlineDrafts.push(newDraft);
            localStorage.setItem('offline_order_drafts', JSON.stringify(offlineDrafts));
            
            toast.success('Draf disimpan secara offline di browser Anda!', {
                description: 'Data akan otomatis disinkronkan saat terhubung ke internet kembali.',
                duration: 8000
            });
            
            router.visit(route('orders.index'));
            return;
        }

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

    const totalCoreHarga = useMemo(() => coreItems.reduce((s, i) => s + getCalculatedSubtotal(i), 0), [coreItems]);
    const totalAddonHarga = useMemo(() => addonItems.reduce((s, i) => s + getCalculatedSubtotal(i), 0), [addonItems]);
    const totalHarga = totalCoreHarga + totalAddonHarga;
    const finalTotal = Math.max(0, totalHarga + (Number(data.ongkir) || 0) - (Number(data.voucher_discount_amount) || 0));

    const pageTitle = isEdit ? `Edit PO ${order.no_po}` : 'Buat PO Baru';

    const [pdfLoading, setPdfLoading] = useState(false);
    async function downloadDraftPdf() {
        if (!navigator.onLine) {
            toast.error('Tidak dapat mengunduh PDF dalam mode offline.', {
                description: 'Koneksi internet/server diperlukan untuk membuat dokumen PDF FO.'
            });
            return;
        }

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
            a.download = `FO-DRAFT-${new Date().toISOString().slice(0,10)}.pdf`;
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
                {/* ===== STICKY HEADER BAR ===== */}
                <div className="sticky top-16 z-30 bg-slate-900/95 text-white px-6 py-4 flex flex-col xl:flex-row justify-between items-start xl:items-center border-b-4 border-red-600 rounded-b-xl mb-6 shadow-xl backdrop-blur-sm gap-4">
                    <div className="flex flex-col lg:flex-row lg:items-center gap-4 w-full xl:w-auto">
                        {/* Interactive Switches directly in the header */}
                        <div className="flex flex-wrap items-center gap-3 py-1 text-xs">
                            {/* Special Order Switch */}
                            <div className="flex items-center gap-2 bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-700">
                                <span className="font-extrabold text-[10px] text-slate-300 uppercase tracking-wide">Pesanan Khusus</span>
                                <Switch 
                                    className="scale-90"
                                    checked={data.is_special_order} 
                                    onCheckedChange={(v) => setData('is_special_order', v)} 
                                />
                            </div>

                            {/* Harga Reseller Switch */}
                            <div className="flex items-center gap-2 bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-700">
                                <span className="font-extrabold text-[10px] text-slate-300 uppercase tracking-wide">Harga Reseller</span>
                                <Switch 
                                    className="scale-90"
                                    checked={data.is_reseller_price} 
                                    onCheckedChange={(v) => setData('is_reseller_price', v)} 
                                />
                            </div>

                            {/* 3-Way Mode Pengiriman Selector */}
                            <div className="flex items-center gap-1.5 bg-slate-800/90 p-1 rounded-lg border border-slate-700 flex-wrap sm:flex-nowrap">
                                <div className="flex items-center gap-1 bg-slate-900/60 p-0.5 rounded-md">
                                    <button
                                        type="button"
                                        onClick={() => setData((prev) => ({ ...prev, tipe_pengiriman: 'ongkir', is_free_ongkir: false }))}
                                        className={`px-2.5 py-1 text-[10px] font-black rounded uppercase tracking-wider transition-all flex items-center gap-1 ${
                                            (data.tipe_pengiriman === 'ongkir' || (!data.tipe_pengiriman && !data.is_free_ongkir))
                                                ? 'bg-red-600 text-white shadow-sm'
                                                : 'text-slate-300 hover:text-white hover:bg-slate-800'
                                        }`}
                                        title="Ekspedisi / Ongkir Berbayar"
                                    >
                                        🚚 Ongkir
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setData((prev) => ({ ...prev, tipe_pengiriman: 'free_ongkir', is_free_ongkir: true, ongkir: 0 }))}
                                        className={`px-2.5 py-1 text-[10px] font-black rounded uppercase tracking-wider transition-all flex items-center gap-1 ${
                                            (data.tipe_pengiriman === 'free_ongkir' || data.is_free_ongkir)
                                                ? 'bg-emerald-600 text-white shadow-sm'
                                                : 'text-slate-300 hover:text-white hover:bg-slate-800'
                                        }`}
                                        title="Gratis Ongkir (Ditanggung Penjual)"
                                    >
                                        🎁 Free Ongkir
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setData((prev) => ({ ...prev, tipe_pengiriman: 'pickup_cod', is_free_ongkir: false, ongkir: 0 }))}
                                        className={`px-2.5 py-1 text-[10px] font-black rounded uppercase tracking-wider transition-all flex items-center gap-1 ${
                                            data.tipe_pengiriman === 'pickup_cod'
                                                ? 'bg-cyan-600 text-white shadow-sm'
                                                : 'text-slate-300 hover:text-white hover:bg-slate-800'
                                        }`}
                                        title="Pelanggan Ambil Sendiri di Tempat / COD"
                                    >
                                        🏠 Ambil di Tempat / COD
                                    </button>
                                </div>

                                {(data.tipe_pengiriman === 'ongkir' || (!data.tipe_pengiriman && !data.is_free_ongkir)) && (
                                    <div className="flex items-center gap-1.5 border-t sm:border-t-0 sm:border-l border-slate-700 pt-1.5 sm:pt-0 sm:pl-2">
                                        <span className="text-[9px] text-slate-400 uppercase font-extrabold">Biaya:</span>
                                        <input 
                                            type="text"
                                            value={data.ongkir ? `Rp ${new Intl.NumberFormat('id-ID').format(data.ongkir)}` : ''} 
                                            placeholder="Rp 0" 
                                            onChange={(e) => {
                                                const rawValue = e.target.value.replace(/\D/g, '');
                                                const numericValue = rawValue ? parseInt(rawValue, 10) : 0;
                                                setData('ongkir', numericValue);
                                            }} 
                                            className="w-28 bg-white border border-slate-300 rounded px-2 py-0.5 text-center text-xs font-semibold text-slate-950 focus:outline-none focus:border-red-500 shadow-sm" 
                                        />
                                    </div>
                                )}
                            </div>

                            {/* Voucher Input */}
                            <div className="flex items-center gap-1.5 bg-slate-800/90 p-1 rounded-lg border border-slate-700">
                                <span className="text-[9px] text-slate-400 uppercase font-extrabold pl-1">Voucher:</span>
                                <input 
                                    type="text"
                                    value={data.voucher_discount_amount ? `Rp ${new Intl.NumberFormat('id-ID').format(data.voucher_discount_amount)}` : ''} 
                                    placeholder="Rp 0" 
                                    onChange={(e) => {
                                        const rawValue = e.target.value.replace(/\D/g, '');
                                        const numericValue = rawValue ? parseInt(rawValue, 10) : 0;
                                        setData('voucher_discount_amount', numericValue);
                                    }} 
                                    className="w-28 bg-white border border-slate-300 rounded px-2 py-0.5 text-center text-xs font-semibold text-slate-950 focus:outline-none focus:border-red-500 shadow-sm" 
                                />
                            </div>
                        </div>
                    </div>

                    {/* ===== STICKY ACTIONS & DETAILS GROUP ===== */}
                    <div className="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-between lg:justify-end">
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setShowSummaryDropdown(!showSummaryDropdown)}
                                onMouseEnter={() => setShowSummaryDropdown(true)}
                                className="flex items-center gap-3 bg-slate-800 hover:bg-slate-700/80 px-4 py-2 rounded-xl border border-slate-700 transition select-none shadow-inner"
                            >
                                <div className="text-left">
                                    <p className="text-[9px] font-bold text-slate-400 uppercase tracking-widest leading-none">Total Tagihan ({totalPcs} Pcs)</p>
                                    <p className="font-mono font-black text-sm text-emerald-400 mt-1 leading-none">{formatRupiah(finalTotal)}</p>
                                </div>
                                <ChevronDown className={`h-4 w-4 text-slate-400 shrink-0 transition-transform duration-200 ${showSummaryDropdown ? 'rotate-180' : ''}`} />
                            </button>

                            {showSummaryDropdown && (
                                <div 
                                    className="absolute left-1/2 -translate-x-1/2 lg:left-auto lg:right-0 lg:translate-x-0 mt-2 w-80 bg-white border border-slate-200 text-slate-800 rounded-2xl shadow-2xl p-4 z-50 space-y-3"
                                    onMouseLeave={() => setShowSummaryDropdown(false)}
                                >
                                    <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                                        <h3 className="text-xs font-black text-slate-800 uppercase tracking-wide">Rincian Modul</h3>
                                        <span className="bg-slate-900 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{totalPcs} PCS</span>
                                    </div>
                                    
                                    <div className="max-h-60 overflow-y-auto space-y-3 pr-1">
                                        {coreItems.length > 0 && (
                                            <div className="space-y-1.5">
                                                <p className="text-[9px] font-black text-slate-400 uppercase tracking-wider">Produk Inti ({totalCorePcs} pcs)</p>
                                                {coreItems.map((item, idx) => {
                                                    const origIdx = data.items.indexOf(item);
                                                    return (
                                                        <div key={origIdx} className="flex justify-between items-center text-xs">
                                                            <div className="flex flex-col min-w-0">
                                                                <span className="font-bold text-slate-700 uppercase truncate max-w-[170px]" title={item.nama_produk}>
                                                                    {item.nama_produk || `Produk #${origIdx + 1}`}
                                                                </span>
                                                                {Number(item.discount_value) > 0 && (
                                                                    <span className="text-[10px] text-amber-600 font-medium">
                                                                        Diskon: {item.discount_type === 'persen' ? `${Number(item.discount_value)}%` : `${formatRupiah(Number(item.discount_value))}/pcs`}
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <span className="font-mono font-bold text-slate-500 whitespace-nowrap ml-2">
                                                                {item.namesets.length} pcs — {formatRupiah(getCalculatedSubtotal(item))}
                                                                {Number(item.discount_value) > 0 && (
                                                                    <span className="text-[10px] text-slate-400 block text-right font-normal">
                                                                        (hemat {formatRupiah(item.discount_type === 'persen' ? (item.namesets.length * item.harga_satuan * item.discount_value / 100) : (item.namesets.length * item.discount_value))})
                                                                    </span>
                                                                )}
                                                            </span>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}

                                        {addonItems.length > 0 && (
                                            <div className="space-y-1.5 pt-2 border-t border-slate-100">
                                                <p className="text-[9px] font-black text-slate-400 uppercase tracking-wider">Add-ons ({totalAddonPcs} pcs)</p>
                                                {addonItems.map((item, idx) => {
                                                    const origIdx = data.items.indexOf(item);
                                                    return (
                                                        <div key={origIdx} className="flex justify-between items-center text-xs">
                                                            <div className="flex flex-col min-w-0">
                                                                <span className="font-bold text-slate-700 uppercase truncate max-w-[170px]" title={item.nama_produk}>
                                                                    {item.nama_produk || `Add-on #${origIdx + 1}`}
                                                                </span>
                                                                {Number(item.discount_value) > 0 && (
                                                                    <span className="text-[10px] text-amber-600 font-medium">
                                                                        Diskon: {item.discount_type === 'persen' ? `${Number(item.discount_value)}%` : `${formatRupiah(Number(item.discount_value))}/pcs`}
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <span className="font-mono font-bold text-slate-500 whitespace-nowrap ml-2">
                                                                {item.quantity} pcs — {formatRupiah(getCalculatedSubtotal(item))}
                                                                {Number(item.discount_value) > 0 && (
                                                                    <span className="text-[10px] text-slate-400 block text-right font-normal">
                                                                        (hemat {formatRupiah(item.discount_type === 'persen' ? (item.quantity * item.harga_satuan * item.discount_value / 100) : (item.quantity * item.discount_value))})
                                                                    </span>
                                                                )}
                                                            </span>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>

                                    <div className="border-t border-slate-100 pt-2.5 space-y-1.5 text-xs">
                                        <div className="flex justify-between items-center text-slate-500 font-medium">
                                            <span>Subtotal</span>
                                            <span className="font-mono">{formatRupiah(totalHarga)}</span>
                                        </div>
                                        {Number(data.ongkir) > 0 && (
                                            <div className="flex justify-between items-center text-slate-500 font-medium">
                                                <span>Ongkir</span>
                                                <span className="font-mono">+ {formatRupiah(Number(data.ongkir))}</span>
                                            </div>
                                        )}
                                        {Number(data.voucher_discount_amount) > 0 && (
                                            <div className="flex justify-between items-center text-rose-600 font-medium">
                                                <span>Voucher</span>
                                                <span className="font-mono">- {formatRupiah(Number(data.voucher_discount_amount))}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between items-center font-black pt-1.5 border-t border-dashed border-slate-150">
                                            <span className="uppercase text-slate-700">Total Keseluruhan</span>
                                            <span className="font-mono text-red-650 text-sm">{formatRupiah(finalTotal)}</span>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="flex flex-wrap gap-2 items-center">
                            <button
                                type="button"
                                onClick={downloadDraftPdf}
                                disabled={pdfLoading}
                                className="flex items-center gap-1.5 bg-slate-700 hover:bg-slate-600 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition uppercase tracking-wide disabled:opacity-50 shadow-md"
                            >
                                <FileDown className="h-4 w-4" />
                                {pdfLoading ? 'Loading...' : 'Download PDF'}
                            </button>
                            <Link
                                href={isEdit ? route('orders.show', order.id) : route('orders.index')}
                                className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl transition uppercase tracking-wide shadow-md border border-slate-700"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="flex items-center gap-1.5 bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-xl text-xs font-black transition shadow-lg shadow-red-600/30 uppercase tracking-wide disabled:opacity-50"
                            >
                                <Save className="h-4 w-4" />
                                {isEdit ? 'Simpan Perubahan' : 'Simpan Draft'}
                            </button>
                        </div>
                    </div>
                </div>

                {/* ===== MAIN LAYOUT: FULL WIDTH ===== */}
                <div className="w-full space-y-5">

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
                                    <Input value={data.nama_po} onChange={(e) => setData('nama_po', e.target.value)} className="h-8 text-sm font-bold" placeholder="Contoh: PO Klub Garuda Mei" />
                                    {errors.nama_po && <p className="mt-1 text-xs text-red-500">{errors.nama_po}</p>}
                                </div>

                                <div className="flex flex-col col-span-2">
                                    <FieldLabel>Pelanggan <span className="text-red-500">*</span></FieldLabel>
                                    <SearchableSelect
                                        value={data.pelanggan_id}
                                        onValueChange={(v) => setData('pelanggan_id', v)}
                                        options={masters.pelanggan.map((p) => ({ value: p.id, label: p.nama }))}
                                        placeholder="Pilih pelanggan"
                                    />
                                    {errors.pelanggan_id && <p className="mt-1 text-xs text-red-500">{errors.pelanggan_id}</p>}
                                </div>

                                <div className="flex flex-col col-span-2">
                                    <FieldLabel>Target Rekening Pembayaran <span className="text-red-500">*</span></FieldLabel>
                                    <SearchableSelect
                                        value={data.bank_id}
                                        onValueChange={(v) => setData('bank_id', v)}
                                        options={filteredBanks.map((b) => ({ value: b.id, label: `${b.bank} — ${b.nomor_rekening} (${b.atas_nama})` }))}
                                        placeholder="Pilih rekening target untuk invoice"
                                    />
                                    {errors.bank_id && <p className="mt-1 text-xs text-red-500">{errors.bank_id}</p>}
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
                                {/* Selector Reseller Hub: tampil ketika brand regular memiliki reseller hub terkait */}
                                {reseller_hubs && reseller_hubs.length > 0 && (
                                    <div className="flex flex-col col-span-2">
                                        <FieldLabel>
                                            Reseller Hub
                                            <span className="ml-1 text-[10px] text-muted-foreground font-normal">(pilih reseller hub untuk ditampilkan di FO)</span>
                                        </FieldLabel>
                                        <SearchableSelect
                                            value={data.reseller_display_brand_id}
                                            onValueChange={(v) => setData('reseller_display_brand_id', v)}
                                            options={reseller_hubs.map((b) => ({ value: b.id, label: `${b.kode} — ${b.nama_brand}` }))}
                                            placeholder="— Tidak menampilkan reseller hub (default) —"
                                        />
                                        {errors.reseller_display_brand_id && <p className="mt-1 text-xs text-red-500">{errors.reseller_display_brand_id}</p>}
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
            </form>
        </AppLayout>
    );
}
