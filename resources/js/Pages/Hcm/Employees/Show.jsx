import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowLeft,
    User,
    FileText,
    DollarSign,
    CalendarCheck,
    Clock,
    Award,
    LogOut,
    Plus,
    Building2,
    Briefcase,
    CreditCard,
    Shield,
    Phone,
    Mail,
    MapPin,
    AlertCircle,
    CheckCircle2,
    ExternalLink,
    GraduationCap,
    TrendingUp,
    FileCheck,
    Camera,
    Printer,
    Trash2,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { SearchableSelect } from '@/Components/ui/searchable-select';

const toOptions = (arr) => (arr || []).map((v) => (typeof v === 'string' ? { value: v, label: v } : v));

export default function EmployeeShow({ employee, dropdowns }) {
    const [activeTab, setActiveTab] = useState('biodata');

    // State Modals
    const [isContractModalOpen, setIsContractModalOpen] = useState(false);
    const [isCompensationModalOpen, setIsCompensationModalOpen] = useState(false);

    // Form Tambah Kontrak Baru
    const contractForm = useForm({
        contract_number: '',
        employment_status: employee.employment_status || dropdowns.employment_statuses?.[0] || '',
        position: employee.position || dropdowns.positions?.[0] || '',
        legal_entity: employee.legal_entity || dropdowns.legal_entities?.[0] || '',
        duration_text: '1 Tahun',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        review_status: 'Aktif',
        file_contract: null,
        file_contract_url: '',
        notes: '',
    });

    // Form Tambah Kenaikan Gaji
    const compensationForm = useForm({
        new_salary: employee.compensation?.current_salary ? employee.compensation.current_salary + 200000 : 2500000,
        increment_amount: 200000,
        effective_date: new Date().toISOString().split('T')[0],
        reason: 'Evaluasi Siklus 6 Bulan',
    });

    const handleContractSubmit = (e) => {
        e.preventDefault();
        contractForm.post(route('hcm.employees.contracts.store', employee.id), {
            forceFormData: true,
            onSuccess: () => {
                setIsContractModalOpen(false);
                contractForm.reset();
            },
        });
    };

    const handleCompensationSubmit = (e) => {
        e.preventDefault();
        compensationForm.post(route('hcm.employees.compensation-histories.store', employee.id), {
            onSuccess: () => {
                setIsCompensationModalOpen(false);
                compensationForm.reset();
            },
        });
    };

    // Format Rupiah
    const formatRp = (val) => {
        if (!val) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val);
    };

    const tabs = [
        { id: 'biodata', label: '1. Biodata & Identitas', icon: User },
        { id: 'contracts', label: '2. Kontrak & Legal PKWT', icon: FileText, count: employee.contracts?.length || 0 },
        { id: 'compensation', label: '3. Kompensasi & Gaji', icon: DollarSign },
        { id: 'attendance', label: '4. Rekap Absensi', icon: CalendarCheck },
        { id: 'overtime', label: '5. Lembur & Reward', icon: Award },
        { id: 'offboarding', label: '6. Transisi & Status', icon: LogOut },
    ];

    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('hcm.employees.index')}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                        {employee.photo_url ? (
                            <img
                                src={employee.photo_url}
                                alt={employee.name}
                                className="h-10 w-10 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700 shadow-sm"
                            />
                        ) : (
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                                {employee.name.charAt(0).toUpperCase()}
                            </div>
                        )}
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                    {employee.name}
                                </h1>
                                <Badge variant="outline" className="text-xs font-mono font-normal">
                                    {employee.employee_code}
                                </Badge>
                                <Badge className={employee.is_active ? 'bg-emerald-500/10 text-emerald-600 border-emerald-200' : 'bg-zinc-500/10 text-zinc-500'}>
                                    {employee.is_active ? 'Aktif' : 'Non-Aktif'}
                                </Badge>
                            </div>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                {employee.position} • {employee.department} • {employee.legal_entity || 'NISGroup'}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <a
                            href={route('hcm.employees.pdf.dossier', employee.id)}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 shadow-sm transition"
                        >
                            <Printer className="h-3.5 w-3.5 text-zinc-600 dark:text-zinc-400" />
                            <span>Cetak PDF Dossier</span>
                        </a>

                        <Button
                            onClick={() => setIsContractModalOpen(true)}
                            size="sm"
                            variant="outline"
                            className="text-xs gap-1.5 border-zinc-300 dark:border-zinc-700"
                        >
                            <FileCheck className="h-3.5 w-3.5 text-indigo-600" />
                            <span>Perpanjang Kontrak</span>
                        </Button>

                        <Button
                            onClick={() => setIsCompensationModalOpen(true)}
                            size="sm"
                            className="text-xs gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white"
                        >
                            <TrendingUp className="h-3.5 w-3.5" />
                            <span>Catat Kenaikan Gaji</span>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`Buku Induk - ${employee.name}`} />

            <div className="space-y-6">
                {/* Tab Navigation Pill Bar */}
                <div className="flex flex-wrap items-center gap-1.5 border-b border-zinc-200 dark:border-zinc-800 pb-2">
                    {tabs.map((tab) => {
                        const Icon = tab.icon;
                        const isActive = activeTab === tab.id;
                        return (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-medium transition-all ${
                                    isActive
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 border border-zinc-200/70 dark:border-zinc-800/70'
                                }`}
                            >
                                <Icon className="h-3.5 w-3.5" />
                                <span>{tab.label}</span>
                                {tab.count !== undefined && tab.count > 0 && (
                                    <span className={`px-1.5 py-0.2 rounded-full text-[10px] ${isActive ? 'bg-indigo-700 text-white' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'}`}>
                                        {tab.count}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* TAB 1: Biodata & Identitas Lengkap */}
                {activeTab === 'biodata' && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Ringkasan Profil Card */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm md:col-span-1">
                            <CardHeader className="text-center pb-2">
                                <div className="relative mx-auto w-28">
                                    <div className="w-28 h-36 mx-auto rounded-lg overflow-hidden border-2 border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 shadow-sm flex items-center justify-center">
                                        {employee.photo_url ? (
                                            <img
                                                src={employee.photo_url}
                                                alt={employee.name}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex flex-col items-center justify-center text-zinc-400 p-2 text-center">
                                                <User className="h-10 w-10 mb-1 opacity-40" />
                                                <span className="text-[10px] font-medium leading-tight">Pasfoto 3x4 Resmi</span>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex items-center justify-center gap-1.5 mt-2">
                                        <label
                                            htmlFor="quick-photo-input"
                                            className="inline-flex items-center gap-1 px-2 py-1 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:hover:bg-indigo-900 dark:text-indigo-300 text-[11px] font-medium cursor-pointer border border-indigo-200 dark:border-indigo-800 transition"
                                            title="Unggah / Perbarui Pasfoto Resmi"
                                        >
                                            <Camera className="h-3 w-3" />
                                            <span>{employee.photo_url ? 'Ganti Foto' : 'Upload Foto'}</span>
                                            <input
                                                id="quick-photo-input"
                                                type="file"
                                                accept="image/*"
                                                className="hidden"
                                                onChange={(e) => {
                                                    if (e.target.files?.[0]) {
                                                        const formData = new FormData();
                                                        formData.append('photo', e.target.files[0]);
                                                        router.post(route('hcm.employees.photo.update', employee.id), formData, {
                                                            forceFormData: true,
                                                            preserveScroll: true,
                                                        });
                                                    }
                                                }}
                                            />
                                        </label>
                                        {employee.photo_url && (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    if (confirm('Yakin ingin menghapus foto profil ini?')) {
                                                        router.post(route('hcm.employees.photo.update', employee.id), {
                                                            remove_photo: true,
                                                        }, {
                                                            preserveScroll: true,
                                                        });
                                                    }
                                                }}
                                                className="p-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-950 dark:hover:bg-rose-900 dark:text-rose-400 border border-rose-200 dark:border-rose-800 transition"
                                                title="Hapus Foto"
                                            >
                                                <Trash2 className="h-3 w-3" />
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <CardTitle className="text-base font-bold mt-2 text-zinc-900 dark:text-zinc-100">
                                    {employee.name}
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {employee.nickname ? `Panggilan: "${employee.nickname}"` : '-'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 pt-2 text-xs">
                                <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                    <span className="text-zinc-400">Kode Karyawan</span>
                                    <span className="font-mono font-semibold">{employee.employee_code}</span>
                                </div>
                                <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                    <span className="text-zinc-400">Divisi</span>
                                    <span className="font-medium">{employee.department}</span>
                                </div>
                                <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                    <span className="text-zinc-400">Posisi</span>
                                    <span className="font-medium">{employee.position}</span>
                                </div>
                                <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                    <span className="text-zinc-400">Level / Jenjang</span>
                                    <Badge variant="outline" className="text-[11px]">{employee.job_level}</Badge>
                                </div>
                                <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                    <span className="text-zinc-400">Status Legal</span>
                                    <span className="font-medium">{employee.employment_status}</span>
                                </div>
                                <div className="flex justify-between py-1.5">
                                    <span className="text-zinc-400">Badan Usaha</span>
                                    <span className="font-medium">{employee.legal_entity || 'NISGroup'}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Rincian Detail Dokumen Legal & Rekening */}
                        <div className="md:col-span-2 space-y-6">
                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                        <Shield className="h-4 w-4 text-indigo-600" />
                                        Data Identitas Kependudukan & Kontak
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">Nomor KTP (NIK 16 Digit)</span>
                                        <span className="font-mono font-semibold text-zinc-900 dark:text-zinc-100 text-sm">
                                            {employee.nik_ktp}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">No. HP / WhatsApp</span>
                                        <span className="font-mono font-semibold text-zinc-900 dark:text-zinc-100 text-sm">
                                            {employee.phone_number}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">Tempat & Tanggal Lahir</span>
                                        <span className="text-zinc-800 dark:text-zinc-200">
                                            {employee.birth_place || '-'}, {employee.birth_date ? new Date(employee.birth_date).toLocaleDateString('id-ID', { dateStyle: 'long' }) : '-'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">Jenis Kelamin & Agama</span>
                                        <span className="text-zinc-800 dark:text-zinc-200">
                                            {employee.gender} • {employee.religion}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">Pendidikan Terakhir</span>
                                        <span className="text-zinc-800 dark:text-zinc-200">{employee.education}</span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">Status Pernikahan</span>
                                        <span className="text-zinc-800 dark:text-zinc-200">{employee.marital_status}</span>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <span className="text-zinc-400 block mb-0.5">Alamat Domisili</span>
                                        <span className="text-zinc-800 dark:text-zinc-200">{employee.address || '-'}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                        <CreditCard className="h-4 w-4 text-indigo-600" />
                                        Rekening Payroll, Jaminan Sosial & Seragam
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">Rekening Payroll ({employee.bank_name})</span>
                                        <span className="font-mono font-semibold text-zinc-900 dark:text-zinc-100 text-sm">
                                            {employee.bank_account_no || 'Belum Diisi'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">Ukuran Baju Seragam</span>
                                        <Badge className="bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-bold px-2 py-0.5">
                                            {employee.shirt_size}
                                        </Badge>
                                    </div>
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">BPJS Kesehatan</span>
                                        <span className="font-mono text-zinc-800 dark:text-zinc-200">
                                            {employee.bpjs_kesehatan_no || 'Tidak Terdaftar'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-400 block mb-0.5">BPJS Ketenagakerjaan (TK)</span>
                                        <span className="font-mono text-zinc-800 dark:text-zinc-200">
                                            {employee.bpjs_ketenagakerjaan_no || 'Tidak Terdaftar'}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Institusi Magang (Muncul jika ada) */}
                            {employee.intern && (
                                <Card className="border border-emerald-200/80 dark:border-emerald-800/80 bg-emerald-50/30 dark:bg-emerald-950/20 shadow-sm">
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-sm font-semibold text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                            <GraduationCap className="h-4 w-4" />
                                            Data Kemitraan Magang Siswa SMK (PKL)
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                        <div>
                                            <span className="text-zinc-400 block mb-0.5">Nama Sekolah</span>
                                            <span className="font-bold text-zinc-900 dark:text-zinc-100">{employee.intern.school_name}</span>
                                        </div>
                                        <div>
                                            <span className="text-zinc-400 block mb-0.5">Kelas & Jurusan</span>
                                            <span className="text-zinc-800 dark:text-zinc-200">Kelas {employee.intern.class} • {employee.intern.major || 'Semua Jurusan'}</span>
                                        </div>
                                        <div>
                                            <span className="text-zinc-400 block mb-0.5">No. Induk Siswa (NIS)</span>
                                            <span className="font-mono text-zinc-800 dark:text-zinc-200">{employee.intern.nis || '-'}</span>
                                        </div>
                                        <div>
                                            <span className="text-zinc-400 block mb-0.5">Durasi & Periode Magang</span>
                                            <span className="text-zinc-800 dark:text-zinc-200">
                                                {employee.intern.duration_text} ({employee.intern.start_date || '-'} s.d. {employee.intern.end_date || '-'})
                                            </span>
                                        </div>
                                        <div>
                                            <span className="text-zinc-400 block mb-0.5">Guru Pembimbing Sekolah</span>
                                            <span className="font-medium text-zinc-800 dark:text-zinc-200">{employee.intern.mentor_teacher_name || '-'}</span>
                                        </div>
                                        <div>
                                            <span className="text-zinc-400 block mb-0.5">Kontak Guru Pembimbing</span>
                                            <span className="font-mono text-zinc-800 dark:text-zinc-200">{employee.intern.mentor_teacher_phone || '-'}</span>
                                        </div>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    </div>
                )}

                {/* TAB 2: Riwayat Kontrak & Legalitas PKWT */}
                {activeTab === 'contracts' && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                    Riwayat Kontrak Kerja & Naskah PKWT
                                </h3>
                                <p className="text-xs text-zinc-500">
                                    Seluruh catatan perpanjangan kontrak kerja, masa berlaku, dan sisa hari aktif.
                                </p>
                            </div>
                            <Button
                                onClick={() => setIsContractModalOpen(true)}
                                size="sm"
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Terbitkan Kontrak Baru
                            </Button>
                        </div>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                            <Table>
                                <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                    <TableRow>
                                        <TableHead className="w-[80px] text-center font-bold">Urutan</TableHead>
                                        <TableHead className="min-w-[200px] font-bold">Nomor Kontrak</TableHead>
                                        <TableHead className="min-w-[140px] font-bold">Status & Posisi</TableHead>
                                        <TableHead className="min-w-[140px] font-bold">Badan Usaha (CV)</TableHead>
                                        <TableHead className="min-w-[160px] font-bold">Periode Masa Berlaku</TableHead>
                                        <TableHead className="min-w-[120px] text-center font-bold">Sisa Masa Aktif</TableHead>
                                        <TableHead className="min-w-[120px] text-center font-bold">Status Review</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {employee.contracts && employee.contracts.length > 0 ? (
                                        employee.contracts.map((contract) => (
                                            <TableRow key={contract.id}>
                                                <TableCell className="text-center font-mono font-bold text-xs">
                                                    Ke-{contract.contract_sequence}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-mono font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                                        {contract.contract_number}
                                                    </div>
                                                    <div className="text-[11px] text-zinc-400 mt-0.5">
                                                        Masa: {contract.duration_text}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                                        {contract.employment_status}
                                                    </div>
                                                    <div className="text-[11px] text-zinc-500">
                                                        {contract.position || employee.position}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-xs text-zinc-700 dark:text-zinc-300">
                                                    {contract.legal_entity}
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <div>
                                                        {contract.start_date || '-'} s.d. {contract.end_date || 'Tetap'}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {contract.end_date ? (
                                                        <Badge
                                                            variant="outline"
                                                            className={`text-[11px] ${
                                                                contract.days_remaining <= 30
                                                                    ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                                    : contract.days_remaining <= 60
                                                                    ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                            }`}
                                                        >
                                                            {contract.days_remaining > 0 ? `${contract.days_remaining} Hari Lagi` : 'Kedaluwarsa'}
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="bg-purple-50 text-purple-700 border-purple-200 text-[11px]">
                                                            Permanen (Tetap)
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge className="text-[11px] bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                                                        {contract.review_status}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-28 text-center text-xs text-zinc-400">
                                                Belum ada naskah kontrak kerja resmi yang diterbitkan untuk karyawan ini.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </Card>
                    </div>
                )}

                {/* TAB 3: Rekam Kompensasi & Gaji */}
                {activeTab === 'compensation' && (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-4">
                                <span className="text-xs text-zinc-400 uppercase tracking-wider font-medium">Honor / Gaji Awal</span>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                    {formatRp(employee.compensation?.initial_salary)}
                                </div>
                                <span className="text-[11px] text-zinc-500 mt-1 block">Saat pertama kali bergabung</span>
                            </Card>

                            <Card className="border border-indigo-200 dark:border-indigo-800 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-sm p-4">
                                <span className="text-xs text-indigo-600 dark:text-indigo-400 uppercase tracking-wider font-medium">Honor Saat Ini</span>
                                <div className="text-2xl font-bold text-indigo-700 dark:text-indigo-300 mt-1">
                                    {formatRp(employee.compensation?.current_salary)}
                                </div>
                                <span className="text-[11px] text-zinc-500 mt-1 block">
                                    Total Kenaikan: {employee.compensation?.salary_increment_count || 0} Kali
                                </span>
                            </Card>

                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-4">
                                <span className="text-xs text-zinc-400 uppercase tracking-wider font-medium">Siklus Evaluasi Kenaikan</span>
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                    Setiap {employee.compensation?.evaluation_cycle_months || 6} Bulan
                                </div>
                                <span className="text-[11px] text-zinc-500 mt-1 block">Sesuai aturan operasional NISGroup</span>
                            </Card>
                        </div>

                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Rekam Jejak Histori Kenaikan Honor / Gaji
                            </h3>
                            <Button
                                onClick={() => setIsCompensationModalOpen(true)}
                                size="sm"
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Catat Kenaikan Gaji
                            </Button>
                        </div>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                            <Table>
                                <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                    <TableRow>
                                        <TableHead className="min-w-[130px] font-bold">Tanggal Efektif</TableHead>
                                        <TableHead className="min-w-[140px] font-bold">Gaji Sebelumnya</TableHead>
                                        <TableHead className="min-w-[140px] font-bold">Nominal Kenaikan</TableHead>
                                        <TableHead className="min-w-[140px] font-bold">Gaji Baru</TableHead>
                                        <TableHead className="min-w-[200px] font-bold">Alasan / Catatan Peninjauan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {employee.compensation?.histories && employee.compensation.histories.length > 0 ? (
                                        employee.compensation.histories.map((hist) => (
                                            <TableRow key={hist.id}>
                                                <TableCell className="text-xs font-mono font-medium">
                                                    {hist.effective_date}
                                                </TableCell>
                                                <TableCell className="text-xs text-zinc-500">
                                                    {formatRp(hist.previous_salary)}
                                                </TableCell>
                                                <TableCell className="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                                    + {formatRp(hist.increment_amount)}
                                                </TableCell>
                                                <TableCell className="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                                    {formatRp(hist.new_salary)}
                                                </TableCell>
                                                <TableCell className="text-xs text-zinc-700 dark:text-zinc-300">
                                                    {hist.reason || 'Peninjauan Berkala'}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={5} className="h-24 text-center text-xs text-zinc-400">
                                                Belum ada rekam jejak kenaikan gaji yang dicatat.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </Card>
                    </div>
                )}

                {/* TAB 4: Rekap Kehadiran Bulanan */}
                {activeTab === 'attendance' && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <CalendarCheck className="h-4 w-4 text-indigo-600" />
                                Rekam Log Presensi Harian (31 Hari Terakhir)
                            </h3>
                            <Link
                                href={route('hcm.attendance.index')}
                                className="text-xs text-indigo-600 hover:underline flex items-center gap-1"
                            >
                                <ExternalLink className="h-3 w-3" />
                                Buka Matriks Presensi Massal
                            </Link>
                        </div>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                            <Table>
                                <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                    <TableRow>
                                        <TableHead className="min-w-[120px] font-bold">Tanggal</TableHead>
                                        <TableHead className="min-w-[130px] font-bold">Kategori Kehadiran</TableHead>
                                        <TableHead className="min-w-[100px] font-bold text-center">Jam Masuk</TableHead>
                                        <TableHead className="min-w-[100px] font-bold text-center">Jam Keluar</TableHead>
                                        <TableHead className="min-w-[200px] font-bold">Catatan / Alasan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {employee.attendances && employee.attendances.length > 0 ? (
                                        employee.attendances.map((att) => (
                                            <TableRow key={att.id}>
                                                <TableCell className="text-xs font-mono font-medium">
                                                    {att.attendance_date}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="outline"
                                                        className={`text-xs ${
                                                            att.attendance_category === 'Hadir'
                                                                ? 'border-emerald-300 text-emerald-600 bg-emerald-50/50'
                                                                : att.attendance_category === 'Terlambat'
                                                                ? 'border-amber-300 text-amber-600 bg-amber-50/50'
                                                                : att.attendance_category === 'Alpha/Mangkir'
                                                                ? 'border-rose-300 text-rose-600 bg-rose-50/50'
                                                                : 'border-blue-300 text-blue-600 bg-blue-50/50'
                                                        }`}
                                                    >
                                                        {att.attendance_category}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-xs text-center font-mono">
                                                    {att.clock_in ? att.clock_in.substring(0, 5) : '-'}
                                                </TableCell>
                                                <TableCell className="text-xs text-center font-mono">
                                                    {att.clock_out ? att.clock_out.substring(0, 5) : '-'}
                                                </TableCell>
                                                <TableCell className="text-xs text-zinc-600 dark:text-zinc-400">
                                                    {att.notes || '-'}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={5} className="h-24 text-center text-xs text-zinc-400">
                                                Belum ada rekam log presensi yang dicatat untuk karyawan ini.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </Card>

                        {/* Riwayat Pengajuan Cuti / Izin */}
                        {employee.leave_requests && employee.leave_requests.length > 0 && (
                            <div className="space-y-2 pt-2">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-zinc-500">
                                    Riwayat Pengajuan Cuti / Izin
                                </h4>
                                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                                    <Table>
                                        <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                            <TableRow>
                                                <TableHead className="font-bold">Jenis Izin</TableHead>
                                                <TableHead className="font-bold">Periode Tanggal</TableHead>
                                                <TableHead className="font-bold text-center">Durasi</TableHead>
                                                <TableHead className="font-bold">Alasan</TableHead>
                                                <TableHead className="font-bold text-center">Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {employee.leave_requests.map((lr) => (
                                                <TableRow key={lr.id}>
                                                    <TableCell className="text-xs font-medium">{lr.leave_type}</TableCell>
                                                    <TableCell className="text-xs font-mono">{lr.start_date} s.d. {lr.end_date}</TableCell>
                                                    <TableCell className="text-xs text-center font-semibold">{lr.total_days} Hari</TableCell>
                                                    <TableCell className="text-xs text-zinc-600">{lr.reason}</TableCell>
                                                    <TableCell className="text-center">
                                                        <Badge className={`text-xs ${lr.status === 'APPROVED' ? 'bg-emerald-500/10 text-emerald-600' : lr.status === 'REJECTED' ? 'bg-rose-500/10 text-rose-600' : 'bg-amber-500/10 text-amber-600'}`}>
                                                            {lr.status}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </Card>
                            </div>
                        )}
                    </div>
                )}

                {/* TAB 5: Rekam Lembur & Reward */}
                {activeTab === 'overtime' && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                <Clock className="h-4 w-4 text-amber-500" />
                                Rekam Jejak Lembur Mingguan Karyawan
                            </h3>
                            <Link
                                href={route('hcm.overtime.index')}
                                className="text-xs text-indigo-600 hover:underline flex items-center gap-1"
                            >
                                <ExternalLink className="h-3 w-3" />
                                Buka Rekap Lembur Mingguan
                            </Link>
                        </div>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                            <Table>
                                <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                    <TableRow>
                                        <TableHead className="min-w-[110px] font-bold">Tanggal</TableHead>
                                        <TableHead className="min-w-[120px] font-bold">Batch Mingguan</TableHead>
                                        <TableHead className="min-w-[130px] font-bold">Jenis Hari</TableHead>
                                        <TableHead className="min-w-[80px] font-bold text-center">Durasi</TableHead>
                                        <TableHead className="min-w-[120px] font-bold text-right">Upah Lembur</TableHead>
                                        <TableHead className="min-w-[130px] font-bold text-center">Status Bayar</TableHead>
                                        <TableHead className="min-w-[180px] font-bold">Uraian Tugas</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {employee.overtimes && employee.overtimes.length > 0 ? (
                                        employee.overtimes.map((ot) => (
                                            <TableRow key={ot.id}>
                                                <TableCell className="text-xs font-mono font-medium">
                                                    {ot.overtime_date}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-mono text-xs text-indigo-600 font-semibold">
                                                        {ot.batch?.batch_code || '-'}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className="text-[11px]">
                                                        {ot.day_type}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center text-xs font-mono font-semibold">
                                                    {parseFloat(ot.duration_hours).toFixed(1)} Jam
                                                </TableCell>
                                                <TableCell className="text-right text-xs font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                                    {formatRp(ot.total_amount)}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge
                                                        className={`text-[11px] ${
                                                            ot.batch?.status === 'PAID_COMPLETED'
                                                                ? 'bg-emerald-500/10 text-emerald-600 border-emerald-300'
                                                                : ot.batch?.status === 'APPROVED_BY_HCM'
                                                                ? 'bg-amber-500/10 text-amber-600 border-amber-300'
                                                                : 'bg-zinc-500/10 text-zinc-600'
                                                        }`}
                                                    >
                                                        {ot.batch?.status === 'PAID_COMPLETED' ? 'Lunas (Paid)' : ot.batch?.status === 'APPROVED_BY_HCM' ? 'Sign HCM' : 'Draf'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-xs text-zinc-600 dark:text-zinc-400">
                                                    {ot.task_description || '-'}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-24 text-center text-xs text-zinc-400">
                                                Belum ada rekam lembur yang tercatat untuk karyawan ini.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </Card>
                    </div>
                )}

                {/* TAB 6: Transisi & Offboarding */}
                {activeTab === 'offboarding' && (
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-6">
                        <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-2">
                            Status Transisi & Offboarding
                        </h3>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span className="text-zinc-400 block mb-0.5">Tanggal Bergabung (Join Date)</span>
                                <span className="font-medium text-zinc-900 dark:text-zinc-100">{employee.join_date || '-'}</span>
                            </div>
                            <div>
                                <span className="text-zinc-400 block mb-0.5">Status Karyawan Saat Ini</span>
                                <Badge className={employee.is_active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600'}>
                                    {employee.is_active ? 'Aktif Bekerja' : 'Offboarding / Keluar'}
                                </Badge>
                            </div>
                            <div className="sm:col-span-2">
                                <span className="text-zinc-400 block mb-0.5">Catatan Tambahan HCM</span>
                                <p className="text-zinc-700 dark:text-zinc-300 italic">{employee.notes || 'Tidak ada catatan khusus.'}</p>
                            </div>
                        </div>
                    </Card>
                )}
            </div>

            {/* MODAL 1: Tambah Kontrak PKWT Baru */}
            <Dialog open={isContractModalOpen} onOpenChange={setIsContractModalOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <FileText className="h-4 w-4 text-indigo-600" />
                            Terbitkan / Perpanjang Kontrak Kerja
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Penerbitan naskah PKWT baru untuk {employee.name}.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleContractSubmit} className="space-y-4 pt-2">
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Nomor Kontrak Resmi *</Label>
                            <Input
                                required
                                value={contractForm.data.contract_number}
                                onChange={(e) => contractForm.setData('contract_number', e.target.value)}
                                placeholder="004/OWR/PKWT/X/2026"
                            />
                            {contractForm.errors.contract_number && (
                                <p className="text-[11px] text-rose-500">{contractForm.errors.contract_number}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Posisi / Jabatan *</Label>
                                <SearchableSelect
                                    value={contractForm.data.position}
                                    onValueChange={(val) => contractForm.setData('position', val)}
                                    options={toOptions(dropdowns.positions)}
                                    placeholder="Pilih / Cari Posisi..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Status Ketenagakerjaan *</Label>
                                <SearchableSelect
                                    value={contractForm.data.employment_status}
                                    onValueChange={(val) => contractForm.setData('employment_status', val)}
                                    options={toOptions(dropdowns.employment_statuses)}
                                    placeholder="Pilih / Cari Status..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Badan Usaha (CV) *</Label>
                                <SearchableSelect
                                    value={contractForm.data.legal_entity}
                                    onValueChange={(val) => contractForm.setData('legal_entity', val)}
                                    options={toOptions(dropdowns.legal_entities)}
                                    placeholder="Pilih / Cari CV..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Masa Durasi Kontrak</Label>
                                <Input
                                    value={contractForm.data.duration_text}
                                    onChange={(e) => contractForm.setData('duration_text', e.target.value)}
                                    placeholder="e.g. 1 Tahun / 2 Tahun"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Status Review *</Label>
                                <SearchableSelect
                                    value={contractForm.data.review_status}
                                    onValueChange={(val) => contractForm.setData('review_status', val)}
                                    options={toOptions(dropdowns.contract_reviews?.length ? dropdowns.contract_reviews : ['Aktif', 'Habis', 'Dibatalkan', 'Menunggu Persetujuan'])}
                                    placeholder="Pilih Status Review..."
                                    clearable={false}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Tanggal Mulai Efektif *</Label>
                                <Input
                                    type="date"
                                    required
                                    value={contractForm.data.start_date}
                                    onChange={(e) => contractForm.setData('start_date', e.target.value)}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1 sm:col-span-2">
                                <Label className="text-xs font-medium">Tanggal Berakhir Kontrak (Kosongkan jika Tetap)</Label>
                                <Input
                                    type="date"
                                    value={contractForm.data.end_date}
                                    onChange={(e) => contractForm.setData('end_date', e.target.value)}
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        {/* Upload Berkas Fisik Naskah Kontrak */}
                        <div className="space-y-1.5 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                            <Label className="text-xs font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <FileText className="h-3.5 w-3.5 text-indigo-600" />
                                Berkas Scan Naskah Kontrak (PDF / JPG / PNG)
                            </Label>
                            <Input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                onChange={(e) => {
                                    if (e.target.files && e.target.files[0]) {
                                        contractForm.setData('file_contract', e.target.files[0]);
                                    }
                                }}
                                className="text-xs file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            />
                            <p className="text-[11px] text-zinc-500">
                                Berkas kontrak fisik otomatis tersinkronisasi ke Google Drive subfolder Kontrak PKWT.
                            </p>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Catatan Kontrak (Opsional)</Label>
                            <Input
                                value={contractForm.data.notes}
                                onChange={(e) => contractForm.setData('notes', e.target.value)}
                                placeholder="Keterangan perpanjangan atau penyesuaian pasal..."
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsContractModalOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={contractForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                {contractForm.processing ? 'Menyimpan...' : 'Terbitkan Kontrak'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 2: Tambah Kenaikan Gaji */}
            <Dialog open={isCompensationModalOpen} onOpenChange={setIsCompensationModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <TrendingUp className="h-4 w-4 text-indigo-600" />
                            Catat Kenaikan Honor / Gaji
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Pencatatan evaluasi kenaikan upah berkala {employee.name}.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCompensationSubmit} className="space-y-3 pt-2">
                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Nominal Kenaikan (Rp) *</Label>
                            <Input
                                type="number"
                                required
                                value={compensationForm.data.increment_amount}
                                onChange={(e) => {
                                    const inc = parseFloat(e.target.value) || 0;
                                    const current = employee.compensation?.current_salary || 0;
                                    compensationForm.setData({
                                        ...compensationForm.data,
                                        increment_amount: inc,
                                        new_salary: current + inc,
                                    });
                                }}
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Gaji Baru Setelah Kenaikan (Rp) *</Label>
                            <Input
                                type="number"
                                required
                                value={compensationForm.data.new_salary}
                                onChange={(e) => compensationForm.setData('new_salary', e.target.value)}
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Tanggal Efektif *</Label>
                            <Input
                                type="date"
                                required
                                value={compensationForm.data.effective_date}
                                onChange={(e) => compensationForm.setData('effective_date', e.target.value)}
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-medium">Alasan / Dasar Peninjauan *</Label>
                            <Input
                                required
                                value={compensationForm.data.reason}
                                onChange={(e) => compensationForm.setData('reason', e.target.value)}
                                placeholder="e.g. Evaluasi Siklus 6 Bulan (Kenaikan ke-1)"
                            />
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCompensationModalOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={compensationForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                {compensationForm.processing ? 'Menyimpan...' : 'Simpan Kenaikan Gaji'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
