import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { SearchableSelect } from '@/Components/ui/searchable-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Mail, Plus, Search, Edit2, Trash2, ExternalLink, Inbox, Send } from 'lucide-react';

export default function ExternalLetterIndex({ letters, filters = {}, metrics = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [direction, setDirection] = useState(filters.direction || 'all');
    const [year, setYear] = useState(filters.year || new Date().getFullYear());

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const form = useForm({
        letter_date: new Date().toISOString().split('T')[0],
        direction: 'Surat Masuk',
        external_letter_no: '',
        sender: '',
        recipient: '',
        subject: '',
        file_upload: null,
        file_scan_url: '',
        notes: '',
    });

    const applyFilter = (s = search, d = direction, y = year) => {
        router.get(route('hcm.external-letters.index'), { search: s, direction: d, year: y }, { preserveState: true });
    };

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.setData({
            letter_date: new Date().toISOString().split('T')[0],
            direction: 'Surat Masuk',
            external_letter_no: '',
            sender: '',
            recipient: '',
            subject: '',
            file_upload: null,
            file_scan_url: '',
            notes: '',
        });
        setIsModalOpen(true);
    };

    const openEdit = (l) => {
        setEditing(l);
        form.setData({
            letter_date: l.letter_date,
            direction: l.direction,
            external_letter_no: l.external_letter_no,
            sender: l.sender,
            recipient: l.recipient,
            subject: l.subject,
            file_upload: null,
            file_scan_url: l.file_scan_url || '',
            notes: l.notes || '',
        });
        setIsModalOpen(true);
    };

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            form.put(route('hcm.external-letters.update', editing.id), { forceFormData: true, onSuccess: () => setIsModalOpen(false) });
        } else {
            form.post(route('hcm.external-letters.store'), { forceFormData: true, onSuccess: () => setIsModalOpen(false) });
        }
    };

    const destroy = (l) => {
        if (!confirm(`Hapus surat eksternal ${l.registration_no}?`)) return;
        router.delete(route('hcm.external-letters.destroy', l.id), { preserveScroll: true });
    };

    const rows = letters?.data || [];

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Mail className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">Surat Eksternal</span>
                </div>
            }
        >
            <Head title="Surat Eksternal" />

            <div className="p-4 sm:p-6 space-y-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-bold text-zinc-900 dark:text-zinc-100">Arsip Korespondensi Eksternal</h1>
                        <p className="text-xs text-zinc-500 mt-0.5">Surat-menyurat dengan instansi eksternal (BPJS-TK, Disnaker, Bank, Mitra).</p>
                    </div>
                    <Button onClick={openCreate} size="sm" className="gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs">
                        <Plus className="h-4 w-4" /> Catat Surat Eksternal
                    </Button>
                </div>

                <div className="grid grid-cols-3 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <Mail className="h-5 w-5 text-indigo-500" />
                            <div><div className="text-[11px] text-zinc-500">Total</div><div className="text-xl font-bold">{metrics.total_letters ?? 0}</div></div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <Inbox className="h-5 w-5 text-sky-500" />
                            <div><div className="text-[11px] text-zinc-500">Surat Masuk</div><div className="text-xl font-bold">{metrics.incoming ?? 0}</div></div>
                        </CardContent>
                    </Card>
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                        <CardContent className="p-4 flex items-center gap-3">
                            <Send className="h-5 w-5 text-emerald-500" />
                            <div><div className="text-[11px] text-zinc-500">Surat Keluar</div><div className="text-xl font-bold">{metrics.outgoing ?? 0}</div></div>
                        </CardContent>
                    </Card>
                </div>

                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                    <CardContent className="p-4">
                        <form onSubmit={(e) => { e.preventDefault(); applyFilter(); }} className="flex flex-wrap items-end gap-2">
                            <div className="flex-1 min-w-[200px]">
                                <div className="text-[11px] font-medium text-zinc-500 mb-1">Cari</div>
                                <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="No. surat / perihal / pengirim" className="h-9 text-xs" />
                            </div>
                            <div className="w-44">
                                <div className="text-[11px] font-medium text-zinc-500 mb-1">Arah</div>
                                <SearchableSelect
                                    value={direction}
                                    onValueChange={(v) => { setDirection(v); applyFilter(search, v, year); }}
                                    options={[{ value: 'all', label: 'Semua' }, { value: 'Surat Masuk', label: 'Surat Masuk' }, { value: 'Surat Keluar', label: 'Surat Keluar' }]}
                                    className="text-xs"
                                />
                            </div>
                            <div className="w-32">
                                <div className="text-[11px] font-medium text-zinc-500 mb-1">Tahun</div>
                                <Input type="number" value={year} onChange={(e) => setYear(e.target.value)} onBlur={() => applyFilter(search, direction, year)} className="h-9 text-xs" />
                            </div>
                            <Button type="submit" size="sm" className="h-9 gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"><Search className="h-3.5 w-3.5" /> Cari</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-zinc-50/75 dark:bg-zinc-800/50">
                                        <TableHead className="text-xs">No. Registrasi</TableHead>
                                        <TableHead className="text-xs">Arah</TableHead>
                                        <TableHead className="text-xs">No. Surat & Perihal</TableHead>
                                        <TableHead className="text-xs">Pengirim / Penerima</TableHead>
                                        <TableHead className="text-xs">Tanggal</TableHead>
                                        <TableHead className="text-center text-xs">Berkas</TableHead>
                                        <TableHead className="text-right text-xs">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length > 0 ? (
                                        rows.map((l) => (
                                            <TableRow key={l.id}>
                                                <TableCell className="font-mono text-xs text-indigo-600 dark:text-indigo-400">{l.registration_no}</TableCell>
                                                <TableCell>
                                                    <Badge className={`text-[10px] ${l.direction === 'Surat Masuk' ? 'bg-sky-500/10 text-sky-600' : 'bg-emerald-500/10 text-emerald-600'}`}>{l.direction}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{l.external_letter_no}</div>
                                                    <div className="text-[10px] text-zinc-400 line-clamp-1">{l.subject}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-xs">Dari: {l.sender}</div>
                                                    <div className="text-[10px] text-zinc-400">Kpd: {l.recipient}</div>
                                                </TableCell>
                                                <TableCell className="text-xs text-zinc-500">{(l.letter_date || '').slice(0, 10)}</TableCell>
                                                <TableCell className="text-center">
                                                    {l.file_scan_url ? (
                                                        <Button variant="outline" size="sm" onClick={() => window.open(l.file_scan_url, '_blank')} className="h-6 text-[10px] px-2 gap-1">
                                                            <ExternalLink className="h-3 w-3" /> Lihat
                                                        </Button>
                                                    ) : <span className="text-[10px] text-zinc-300">—</span>}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button variant="outline" size="sm" onClick={() => openEdit(l)} className="h-6 w-6 p-0"><Edit2 className="h-3 w-3" /></Button>
                                                        <Button variant="outline" size="sm" onClick={() => destroy(l)} className="h-6 w-6 p-0 text-rose-600"><Trash2 className="h-3 w-3" /></Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center text-xs text-zinc-400 py-10">Belum ada surat eksternal.</TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold">
                            <Mail className="w-5 h-5 text-indigo-600" /> {editing ? 'Edit' : 'Catat'} Surat Eksternal
                        </DialogTitle>
                        <DialogDescription className="text-xs">Korespondensi resmi dengan instansi eksternal.</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-3 pt-1">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Arah Surat *</Label>
                                <SearchableSelect
                                    value={form.data.direction}
                                    onValueChange={(v) => form.setData('direction', v)}
                                    options={[{ value: 'Surat Masuk', label: 'Surat Masuk' }, { value: 'Surat Keluar', label: 'Surat Keluar' }]}
                                    placeholder="Pilih"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Tanggal Dokumen *</Label>
                                <Input type="date" value={form.data.letter_date} onChange={(e) => form.setData('letter_date', e.target.value)} className="text-xs" required />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Nomor Surat Eksternal *</Label>
                            <Input value={form.data.external_letter_no} onChange={(e) => form.setData('external_letter_no', e.target.value)} placeholder="055/BPJS-TK/IX/2024" className="text-xs" required />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Pengirim / Instansi *</Label>
                                <Input value={form.data.sender} onChange={(e) => form.setData('sender', e.target.value)} placeholder="BPJS Ketenagakerjaan" className="text-xs" required />
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Penerima / Tujuan *</Label>
                                <Input value={form.data.recipient} onChange={(e) => form.setData('recipient', e.target.value)} placeholder="HCM Dept" className="text-xs" required />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Perihal Dokumen *</Label>
                            <Textarea value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} rows={2} className="text-xs" placeholder="Undangan Sosialisasi Program JKP" required />
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Scan Berkas (PDF/JPG/PNG)</Label>
                            <Input type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onChange={(e) => e.target.files?.[0] && form.setData('file_upload', e.target.files[0])} className="text-xs file:text-xs" />
                            <p className="text-[10px] text-zinc-500">Berkas dialirkan ke Google Drive folder 02_Surat_Masuk_Keluar.</p>
                        </div>

                        <DialogFooter className="pt-2 gap-2">
                            <Button type="button" variant="outline" size="sm" onClick={() => setIsModalOpen(false)}>Batal</Button>
                            <Button type="submit" size="sm" disabled={form.processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                {form.processing ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
