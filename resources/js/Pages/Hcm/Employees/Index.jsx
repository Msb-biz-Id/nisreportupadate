import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Users,
    UserPlus,
    Search,
    Filter,
    Eye,
    Pencil,
    Trash2,
    GraduationCap,
    Briefcase,
    Building2,
    Phone,
    CreditCard,
    CheckCircle2,
    XCircle,
    SlidersHorizontal,
    Sparkles,
    Calendar,
    ChevronRight,
    UserCheck,
    User,
    Clock,
    FileText,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Switch } from '@/Components/ui/switch';
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

export default function EmployeeIndex({ employees, filters, metrics, dropdowns }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || 'all');
    const [selectedJobLevel, setSelectedJobLevel] = useState(filters.job_level || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'all');

    // State Modals
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [targetEmployee, setTargetEmployee] = useState(null);

    const toOptions = (arr) => (arr || []).map((v) => (typeof v === 'string' ? { value: v, label: v } : v));
    const genderOptions = useMemo(() => toOptions(dropdowns.genders?.length ? dropdowns.genders : ['Laki-Laki', 'Perempuan']), [dropdowns.genders]);
    const sizeOptions = useMemo(() => toOptions(dropdowns.shirt_sizes?.length ? dropdowns.shirt_sizes : ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']), [dropdowns.shirt_sizes]);
    const maritalOptions = useMemo(() => toOptions(dropdowns.marital_statuses?.length ? dropdowns.marital_statuses : ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati']), [dropdowns.marital_statuses]);
    const religionOptions = useMemo(() => toOptions(dropdowns.religions?.length ? dropdowns.religions : ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']), [dropdowns.religions]);
    const educationOptions = useMemo(() => toOptions(dropdowns.educations?.length ? dropdowns.educations : ['SD / Sederajat', 'SMP / Sederajat', 'SMA / SMK / Sederajat', 'Diploma 3 (D3)', 'Strata 1 (S1)', 'Strata 2 (S2)']), [dropdowns.educations]);
    const bankOptions = useMemo(() => toOptions(dropdowns.banks?.length ? dropdowns.banks : ['Bank BRI', 'Bank Mandiri', 'Bank BCA', 'Bank BNI', 'BSI', 'Tunai / Kas']), [dropdowns.banks]);

    // Form Tambah Karyawan Baru
    const createForm = useForm({
        name: '',
        nickname: '',
        department: dropdowns.departments?.[0] || '',
        position: dropdowns.positions?.[0] || '',
        job_level: dropdowns.job_levels?.[0] || '',
        employment_status: dropdowns.employment_statuses?.[0] || '',
        legal_entity: dropdowns.legal_entities?.[0] || '',
        phone_number: '',
        gender: dropdowns.genders?.[0] || '',
        religion: dropdowns.religions?.[0] || '',
        education: dropdowns.educations?.[0] || '',
        marital_status: dropdowns.marital_statuses?.[0] || '',
        birth_place: '',
        birth_date: '',
        nik_ktp: '',
        bpjs_kesehatan_no: '',
        bpjs_ketenagakerjaan_no: '',
        shirt_size: dropdowns.shirt_sizes?.[0] || '',
        address: '',
        bank_account_no: '',
        bank_name: '',
        email: '',
        join_date: new Date().toISOString().split('T')[0],
        notes: '',
        photo: null,
        // Field Magang
        intern_school_name: '',
        intern_class: 'XII',
        intern_major: '',
        intern_nis: '',
        intern_start_date: '',
        intern_end_date: '',
        intern_duration_text: '3 Bulan',
        intern_mentor_teacher: '',
        intern_mentor_phone: '',
        // Field Kontrak & Gaji Awal
        contract_number: '',
        contract_duration_text: '1 Tahun',
        contract_start_date: '',
        contract_end_date: '',
        initial_salary: '',
    });

    // Form Edit Karyawan
    const editForm = useForm({
        name: '',
        nickname: '',
        department: '',
        position: '',
        job_level: '',
        employment_status: '',
        legal_entity: '',
        phone_number: '',
        gender: 'Laki Laki',
        religion: 'Islam',
        education: 'SMA Sederajat',
        marital_status: 'Belum Menikah',
        birth_place: '',
        birth_date: '',
        nik_ktp: '',
        bpjs_kesehatan_no: '',
        bpjs_ketenagakerjaan_no: '',
        shirt_size: 'L',
        address: '',
        bank_account_no: '',
        bank_name: 'Bank BRI',
        email: '',
        join_date: '',
        notes: '',
        photo: null,
        remove_photo: false,
        // Field Magang jika ada
        intern_school_name: '',
        intern_class: 'XII',
        intern_major: '',
        intern_nis: '',
        intern_start_date: '',
        intern_end_date: '',
        intern_duration_text: '',
        intern_mentor_teacher: '',
        intern_mentor_phone: '',
    });

    // Filter submit handler
    const applyFilters = (dept = selectedDepartment, level = selectedJobLevel, stat = selectedStatus, search = searchTerm) => {
        router.get(
            route('hcm.employees.index'),
            {
                department: dept,
                job_level: level,
                status: stat,
                search: search,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        applyFilters();
    };

    // Toggle status aktif karyawan
    const handleToggleStatus = (employee) => {
        router.post(
            route('hcm.employees.toggle', employee.id),
            {},
            {
                preserveScroll: true,
            }
        );
    };

    // Buka Modal Edit
    const openEditModal = (emp) => {
        setTargetEmployee(emp);
        editForm.setData({
            name: emp.name || '',
            nickname: emp.nickname || '',
            department: emp.department || '',
            position: emp.position || '',
            job_level: emp.job_level || '',
            employment_status: emp.employment_status || '',
            legal_entity: emp.legal_entity || '',
            phone_number: emp.phone_number || '',
            gender: emp.gender || 'Laki Laki',
            religion: emp.religion || 'Islam',
            education: emp.education || 'SMA Sederajat',
            marital_status: emp.marital_status || 'Belum Menikah',
            birth_place: emp.birth_place || '',
            birth_date: emp.birth_date ? emp.birth_date.split('T')[0] : '',
            nik_ktp: emp.nik_ktp || '',
            bpjs_kesehatan_no: emp.bpjs_kesehatan_no || '',
            bpjs_ketenagakerjaan_no: emp.bpjs_ketenagakerjaan_no || '',
            shirt_size: emp.shirt_size || 'L',
            address: emp.address || '',
            bank_account_no: emp.bank_account_no || '',
            bank_name: emp.bank_name || 'Bank BRI',
            email: emp.email || '',
            join_date: emp.join_date ? emp.join_date.split('T')[0] : '',
            notes: emp.notes || '',
            photo: null,
            remove_photo: false,
            intern_school_name: emp.intern?.school_name || '',
            intern_class: emp.intern?.class || 'XII',
            intern_major: emp.intern?.major || '',
            intern_nis: emp.intern?.nis || '',
            intern_start_date: emp.intern?.start_date ? emp.intern.start_date.split('T')[0] : '',
            intern_end_date: emp.intern?.end_date ? emp.intern.end_date.split('T')[0] : '',
            intern_duration_text: emp.intern?.duration_text || '',
            intern_mentor_teacher: emp.intern?.mentor_teacher_name || '',
            intern_mentor_phone: emp.intern?.mentor_teacher_phone || '',
        });
        setIsEditModalOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createForm.post(route('hcm.employees.store'), {
            forceFormData: true,
            onSuccess: () => {
                setIsCreateModalOpen(false);
                createForm.reset();
            },
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        if (!targetEmployee) return;
        editForm.post(route('hcm.employees.update', targetEmployee.id), {
            forceFormData: true,
            data: {
                ...editForm.data,
                _method: 'PUT',
            },
            onSuccess: () => {
                setIsEditModalOpen(false);
            },
        });
    };

    const handleDeleteSubmit = () => {
        if (!targetEmployee) return;
        router.delete(route('hcm.employees.destroy', targetEmployee.id), {
            onSuccess: () => {
                setIsDeleteModalOpen(false);
            },
        });
    };

    // Styling badge jenjang
    const getJobLevelBadge = (level) => {
        switch (level) {
            case 'Managerial':
                return 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800';
            case 'Kontrak':
                return 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800';
            case 'Borongan':
                return 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800';
            case 'Magang':
                return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
            default:
                return 'bg-zinc-500/10 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-800';
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="text-2xl">👥</span>
                            <h1 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                Karyawan & Peserta Magang
                            </h1>
                        </div>
                        <p className="text-sm text-zinc-500 dark:text-zinc-400">
                            Kelola biodata, data legalitas PKWT, riwayat kompensasi, dan buku induk tenaga kerja NISGroup.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            onClick={() => setIsCreateModalOpen(true)}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm flex items-center gap-2"
                        >
                            <UserPlus className="h-4 w-4" />
                            <span>Tambah Tenaga Kerja</span>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Master Karyawan & Magang - NISGroup" />

            <div className="space-y-6">
                {/* 1. Baris Kartu Metrik Statistik */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="border border-zinc-200/80 bg-white/70 backdrop-blur dark:border-zinc-800/80 dark:bg-zinc-900/70 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Tenaga Kerja Aktif
                            </CardTitle>
                            <div className="rounded-full bg-emerald-100 p-2 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                <UserCheck className="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                                {metrics?.total_active ?? 0}
                            </div>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                Status aktif terdaftar di seluruh divisi
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 bg-white/70 backdrop-blur dark:border-zinc-800/80 dark:bg-zinc-900/70 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Karyawan Tetap
                            </CardTitle>
                            <div className="rounded-full bg-purple-100 p-2 text-purple-600 dark:bg-purple-950 dark:text-purple-400">
                                <Briefcase className="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                                {metrics?.total_permanent ?? 0}
                            </div>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                Perjanjian Kerja Tetap (PKWTT)
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 bg-white/70 backdrop-blur dark:border-zinc-800/80 dark:bg-zinc-900/70 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Kontrak (PKWT)
                            </CardTitle>
                            <div className="rounded-full bg-blue-100 p-2 text-blue-600 dark:bg-blue-950 dark:text-blue-400">
                                <FileText className="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                                {metrics?.total_contract ?? 0}
                            </div>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                PKWT Utama & PKWT Lanjutan
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 bg-white/70 backdrop-blur dark:border-zinc-800/80 dark:bg-zinc-900/70 shadow-sm">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Siswa Magang (PKL)
                            </CardTitle>
                            <div className="rounded-full bg-teal-100 p-2 text-teal-600 dark:bg-teal-950 dark:text-teal-400">
                                <GraduationCap className="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                                {metrics?.total_interns ?? 0}
                            </div>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                Siswa SMK & Vokasi magang aktif
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* 2. Filter Bar Terintegrasi */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm">
                    <CardContent className="p-4">
                        <form onSubmit={handleSearchSubmit} className="flex flex-col gap-3 lg:flex-row lg:items-center">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                                <Input
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Cari nama karyawan, NIK, nama panggilan, nomor HP..."
                                    className="pl-9 h-10 border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50"
                                />
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                {/* Filter Divisi */}
                                <div className="w-[160px]">
                                    <SearchableSelect
                                        value={selectedDepartment}
                                        onValueChange={(val) => {
                                            const finalVal = val || 'all';
                                            setSelectedDepartment(finalVal);
                                            applyFilters(finalVal, selectedJobLevel, selectedStatus);
                                        }}
                                        options={[
                                            { value: 'all', label: 'Semua Divisi' },
                                            ...toOptions(dropdowns.departments),
                                        ]}
                                        placeholder="Pilih Divisi"
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                {/* Filter Level/Jenjang */}
                                <div className="w-[160px]">
                                    <SearchableSelect
                                        value={selectedJobLevel}
                                        onValueChange={(val) => {
                                            const finalVal = val || 'all';
                                            setSelectedJobLevel(finalVal);
                                            applyFilters(selectedDepartment, finalVal, selectedStatus);
                                        }}
                                        options={[
                                            { value: 'all', label: 'Semua Level' },
                                            ...toOptions(dropdowns.job_levels),
                                        ]}
                                        placeholder="Pilih Level"
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                {/* Filter Status Aktif */}
                                <div className="w-[140px]">
                                    <SearchableSelect
                                        value={selectedStatus}
                                        onValueChange={(val) => {
                                            const finalVal = val || 'all';
                                            setSelectedStatus(finalVal);
                                            applyFilters(selectedDepartment, selectedJobLevel, finalVal);
                                        }}
                                        options={[
                                            { value: 'all', label: 'Semua Status' },
                                            { value: 'active', label: '🟢 Aktif Saja' },
                                            { value: 'inactive', label: '⚪ Non-Aktif' },
                                        ]}
                                        placeholder="Pilih Status"
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <Button type="submit" variant="secondary" className="h-10 text-xs px-4">
                                    Cari
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* 3. Tabel Data Karyawan & Magang */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                    <Table>
                        <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                            <TableRow>
                                <TableHead className="w-[50px] text-center font-bold">No</TableHead>
                                <TableHead className="min-w-[220px] font-bold">Karyawan & Identitas</TableHead>
                                <TableHead className="min-w-[160px] font-bold">Divisi & Posisi</TableHead>
                                <TableHead className="min-w-[140px] font-bold">Jenjang & Entitas</TableHead>
                                <TableHead className="min-w-[160px] font-bold">Kontak & NIK</TableHead>
                                <TableHead className="min-w-[120px] text-center font-bold">Status Aktif</TableHead>
                                <TableHead className="w-[160px] text-center font-bold">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {employees.data && employees.data.length > 0 ? (
                                employees.data.map((emp, index) => (
                                    <TableRow key={emp.id} className="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/50 transition-colors">
                                        <TableCell className="text-center font-medium text-xs text-zinc-500">
                                            {employees.from + index}
                                        </TableCell>

                                        {/* Karyawan & Identitas */}
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                {emp.photo_url ? (
                                                    <img
                                                        src={emp.photo_url}
                                                        alt={emp.name}
                                                        className="h-10 w-10 shrink-0 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shadow-xs"
                                                    />
                                                ) : (
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-500 to-violet-500 text-white font-bold text-sm shadow-sm">
                                                        {emp.name.charAt(0).toUpperCase()}
                                                    </div>
                                                )}
                                                <div>
                                                    <div className="font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                                        <span>{emp.name}</span>
                                                        {emp.nickname && (
                                                            <span className="text-xs text-zinc-400 font-normal">
                                                                ({emp.nickname})
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-2 mt-0.5">
                                                        <span className="font-mono bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded text-[11px]">
                                                            {emp.employee_code}
                                                        </span>
                                                        {emp.intern && (
                                                            <span className="text-emerald-600 dark:text-emerald-400 font-medium text-[11px]">
                                                                🎓 {emp.intern.school_name}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>

                                        {/* Divisi & Posisi */}
                                        <TableCell>
                                            <div className="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {emp.position}
                                            </div>
                                            <div className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                {emp.department}
                                            </div>
                                        </TableCell>

                                        {/* Jenjang & Entitas */}
                                        <TableCell>
                                            <div className="flex flex-col gap-1 items-start">
                                                <Badge variant="outline" className={`text-xs px-2 py-0.5 font-medium ${getJobLevelBadge(emp.job_level)}`}>
                                                    {emp.job_level}
                                                </Badge>
                                                <span className="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {emp.legal_entity || emp.employment_status}
                                                </span>
                                            </div>
                                        </TableCell>

                                        {/* Kontak & NIK */}
                                        <TableCell>
                                            <div className="text-xs font-mono text-zinc-800 dark:text-zinc-200">
                                                {emp.phone_number}
                                            </div>
                                            <div className="text-xs font-mono text-zinc-400 mt-0.5">
                                                NIK: {emp.nik_ktp}
                                            </div>
                                        </TableCell>

                                        {/* Switch Toggle Status Aktif */}
                                        <TableCell className="text-center">
                                            <div className="inline-flex items-center gap-2">
                                                <Switch
                                                    checked={emp.is_active}
                                                    onCheckedChange={() => handleToggleStatus(emp)}
                                                />
                                                <span className={`text-xs font-medium ${emp.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400'}`}>
                                                    {emp.is_active ? 'Aktif' : 'Off'}
                                                </span>
                                            </div>
                                        </TableCell>

                                        {/* Tombol Aksi */}
                                        <TableCell className="text-center">
                                            <div className="flex items-center justify-center gap-1.5">
                                                <Link
                                                    href={route('hcm.employees.show', emp.id)}
                                                    className="inline-flex items-center justify-center h-8 px-2.5 rounded-md text-xs font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:hover:bg-indigo-900 dark:text-indigo-300 transition-colors"
                                                    title="Lihat Profil Dossier 360°"
                                                >
                                                    <Eye className="h-3.5 w-3.5 mr-1" />
                                                    Buku Induk
                                                </Link>

                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    className="h-8 w-8 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"
                                                    onClick={() => openEditModal(emp)}
                                                    title="Edit Data"
                                                >
                                                    <Pencil className="h-3.5 w-3.5" />
                                                </Button>

                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    className="h-8 w-8 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                                    onClick={() => {
                                                        setTargetEmployee(emp);
                                                        setIsDeleteModalOpen(true);
                                                    }}
                                                    title="Hapus Karyawan"
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={7} className="h-32 text-center text-zinc-500 dark:text-zinc-400">
                                        Tidak ada data karyawan yang cocok dengan kriteria filter.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>

                    {/* Pagination Bar */}
                    {employees.links && employees.links.length > 3 && (
                        <div className="flex items-center justify-between px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 text-xs text-zinc-500">
                            <div>
                                Menampilkan {employees.from || 0} - {employees.to || 0} dari {employees.total || 0} data
                            </div>
                            <div className="flex gap-1">
                                {employees.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1 rounded text-xs transition-colors ${
                                            link.active
                                                ? 'bg-indigo-600 text-white font-medium'
                                                : link.url
                                                ? 'hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300'
                                                : 'text-zinc-300 dark:text-zinc-600 cursor-not-allowed'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* MODAL 1: Tambah Tenaga Kerja Baru */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-lg">
                            <UserPlus className="h-5 w-5 text-indigo-600" />
                            Pendaftaran Tenaga Kerja Baru
                        </DialogTitle>
                        <DialogDescription>
                            Lengkapi biodata lengkap, status penempatan kerja, dan naskah kontrak legalitas awal.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCreateSubmit} className="space-y-6 pt-2">
                        {/* Seksi 1: Data Identitas Diri */}
                        <div className="space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">1</span>
                                Identitas Pribadi & Biodata
                            </h3>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Nama Lengkap *</Label>
                                    <Input
                                        required
                                        value={createForm.data.name}
                                        onChange={(e) => createForm.setData('name', e.target.value)}
                                        placeholder="e.g. Bambang Sadewo"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Nama Panggilan *</Label>
                                    <Input
                                        required
                                        value={createForm.data.nickname}
                                        onChange={(e) => createForm.setData('nickname', e.target.value)}
                                        placeholder="e.g. Bambang"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">NIK KTP (16 Digit) *</Label>
                                    <Input
                                        required
                                        maxLength={16}
                                        value={createForm.data.nik_ktp}
                                        onChange={(e) => createForm.setData('nik_ktp', e.target.value)}
                                        placeholder="3524xxxxxxxxxxxx"
                                    />
                                    {createForm.errors.nik_ktp && (
                                        <p className="text-[11px] text-rose-500">{createForm.errors.nik_ktp}</p>
                                    )}
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">No. HP / WhatsApp *</Label>
                                    <Input
                                        required
                                        value={createForm.data.phone_number}
                                        onChange={(e) => createForm.setData('phone_number', e.target.value)}
                                        placeholder="0852xxxxxxxx"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Jenis Kelamin *</Label>
                                    <SearchableSelect
                                        value={createForm.data.gender}
                                        onValueChange={(val) => createForm.setData('gender', val)}
                                        options={genderOptions}
                                        placeholder="Pilih Gender"
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Ukuran Baju Seragam *</Label>
                                    <SearchableSelect
                                        value={createForm.data.shirt_size}
                                        onValueChange={(val) => createForm.setData('shirt_size', val)}
                                        options={sizeOptions}
                                        placeholder="Pilih Ukuran"
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Tempat Lahir</Label>
                                    <Input
                                        value={createForm.data.birth_place}
                                        onChange={(e) => createForm.setData('birth_place', e.target.value)}
                                        placeholder="Kota / Tempat Lahir"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Tanggal Lahir</Label>
                                    <Input
                                        type="date"
                                        value={createForm.data.birth_date}
                                        onChange={(e) => createForm.setData('birth_date', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Agama *</Label>
                                    <SearchableSelect
                                        value={createForm.data.religion}
                                        onValueChange={(val) => createForm.setData('religion', val)}
                                        options={religionOptions}
                                        placeholder="Pilih Agama"
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Pendidikan Terakhir *</Label>
                                    <SearchableSelect
                                        value={createForm.data.education}
                                        onValueChange={(val) => createForm.setData('education', val)}
                                        options={educationOptions}
                                        placeholder="Pilih Pendidikan"
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Status Pernikahan</Label>
                                    <SearchableSelect
                                        value={createForm.data.marital_status}
                                        onValueChange={(val) => createForm.setData('marital_status', val)}
                                        options={maritalOptions}
                                        placeholder="Pilih Status"
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Alamat Domisili Lengkap</Label>
                                <Textarea
                                    rows={2}
                                    value={createForm.data.address}
                                    onChange={(e) => createForm.setData('address', e.target.value)}
                                    placeholder="RT/RW, Desa/Kelurahan, Kecamatan, Kab/Kota..."
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Pasfoto Karyawan (Opsional)</Label>
                                <Input
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    onChange={(e) => createForm.setData('photo', e.target.files[0])}
                                    className="text-xs file:mr-3 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                />
                                <p className="text-[10px] text-zinc-400">Format gambar JPG, PNG, atau WebP (Maksimal 3MB)</p>
                            </div>
                        </div>

                        {/* Seksi 2: Penempatan & Organisasi */}
                        <div className="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">2</span>
                                Posisi & Penempatan Organisasi
                            </h3>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Divisi Kerja *</Label>
                                    <SearchableSelect
                                        value={createForm.data.department}
                                        onValueChange={(val) => createForm.setData('department', val)}
                                        options={toOptions(dropdowns.departments)}
                                        placeholder="Pilih / Cari Divisi..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Posisi / Jabatan *</Label>
                                    <SearchableSelect
                                        value={createForm.data.position}
                                        onValueChange={(val) => createForm.setData('position', val)}
                                        options={toOptions(dropdowns.positions)}
                                        placeholder="Pilih / Cari Posisi..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Jenjang / Level *</Label>
                                    <SearchableSelect
                                        value={createForm.data.job_level}
                                        onValueChange={(val) => {
                                            createForm.setData('job_level', val);
                                            if (val === 'Magang') {
                                                createForm.setData('employment_status', 'Magang');
                                            }
                                        }}
                                        options={toOptions(dropdowns.job_levels)}
                                        placeholder="Pilih / Cari Level..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Status Ketenagakerjaan *</Label>
                                    <SearchableSelect
                                        value={createForm.data.employment_status}
                                        onValueChange={(val) => createForm.setData('employment_status', val)}
                                        options={toOptions(dropdowns.employment_statuses)}
                                        placeholder="Pilih / Cari Status..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Badan Usaha / Legal Entity</Label>
                                    <SearchableSelect
                                        value={createForm.data.legal_entity}
                                        onValueChange={(val) => createForm.setData('legal_entity', val)}
                                        options={toOptions(dropdowns.legal_entities)}
                                        placeholder="Pilih / Cari CV..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Tanggal Mulai Bergabung</Label>
                                    <Input
                                        type="date"
                                        value={createForm.data.join_date}
                                        onChange={(e) => createForm.setData('join_date', e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Seksi 3: Khusus Peserta Magang (Muncul Otomatis jika Job Level Magang) */}
                        {createForm.data.job_level === 'Magang' && (
                            <div className="space-y-3 pt-2 border-t border-emerald-200 dark:border-emerald-800 bg-emerald-50/40 dark:bg-emerald-950/20 p-3 rounded-lg">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                                    <GraduationCap className="h-4 w-4" />
                                    Data Institusi Magang SMK / PKL
                                </h3>

                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Nama Sekolah SMK *</Label>
                                        <Input
                                            value={createForm.data.intern_school_name}
                                            onChange={(e) => createForm.setData('intern_school_name', e.target.value)}
                                            placeholder="e.g. Asal Sekolah / Lembaga SMK"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Jurusan Siswa</Label>
                                        <Input
                                            value={createForm.data.intern_major}
                                            onChange={(e) => createForm.setData('intern_major', e.target.value)}
                                            placeholder="Tata Busana / Multimedia"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">No. Induk Siswa (NIS)</Label>
                                        <Input
                                            value={createForm.data.intern_nis}
                                            onChange={(e) => createForm.setData('intern_nis', e.target.value)}
                                            placeholder="2024.12.xxx"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Guru Pembimbing Sekolah</Label>
                                        <Input
                                            value={createForm.data.intern_mentor_teacher}
                                            onChange={(e) => createForm.setData('intern_mentor_teacher', e.target.value)}
                                            placeholder="e.g. Bu Ningsih"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">No. HP Guru Pembimbing</Label>
                                        <Input
                                            value={createForm.data.intern_mentor_phone}
                                            onChange={(e) => createForm.setData('intern_mentor_phone', e.target.value)}
                                            placeholder="0812xxxxxxxx"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Durasi Magang</Label>
                                        <Input
                                            value={createForm.data.intern_duration_text}
                                            onChange={(e) => createForm.setData('intern_duration_text', e.target.value)}
                                            placeholder="3 Bulan"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Seksi 4: Rekening & Jaminan Sosial */}
                        <div className="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">3</span>
                                Rekening Payroll & Kompensasi Awal
                            </h3>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Bank Pembayaran</Label>
                                    <SearchableSelect
                                        value={createForm.data.bank_name}
                                        onValueChange={(val) => createForm.setData('bank_name', val)}
                                        options={bankOptions}
                                        placeholder="Pilih Bank..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">No. Rekening</Label>
                                    <Input
                                        value={createForm.data.bank_account_no}
                                        onChange={(e) => createForm.setData('bank_account_no', e.target.value)}
                                        placeholder="e.g. 0011-01-xxxxxx-xx-x"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">No. BPJS Kesehatan</Label>
                                    <Input
                                        value={createForm.data.bpjs_kesehatan_no}
                                        onChange={(e) => createForm.setData('bpjs_kesehatan_no', e.target.value)}
                                        placeholder="000xxxxxxxx"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">No. BPJS Ketenagakerjaan</Label>
                                    <Input
                                        value={createForm.data.bpjs_ketenagakerjaan_no}
                                        onChange={(e) => createForm.setData('bpjs_ketenagakerjaan_no', e.target.value)}
                                        placeholder="000xxxxxxxx"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Honor / Gaji Awal (Rp)</Label>
                                    <Input
                                        type="number"
                                        value={createForm.data.initial_salary}
                                        onChange={(e) => createForm.setData('initial_salary', e.target.value)}
                                        placeholder="e.g. 2500000"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Nomor Kontrak PKWT (Jika Ada)</Label>
                                    <Input
                                        value={createForm.data.contract_number}
                                        onChange={(e) => createForm.setData('contract_number', e.target.value)}
                                        placeholder="XXX/OWR/PKWT/X/XXXX"
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateModalOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={createForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                {createForm.processing ? 'Menyimpan...' : 'Simpan Tenaga Kerja'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 2: Edit Karyawan */}
            <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
                <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-lg">
                            <Pencil className="h-5 w-5 text-indigo-600" />
                            Edit Data Profil Karyawan
                        </DialogTitle>
                        <DialogDescription>
                            Perbarui informasi biodata, kontak, dan penempatan kerja {targetEmployee?.name}.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleEditSubmit} className="space-y-6 pt-2">
                        {/* Seksi 1: Identitas & Biodata Pribadi */}
                        <div className="space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">1</span>
                                Biodata & Identitas Pribadi
                            </h3>

                            {/* Foto Profil Preview & Ganti Foto */}
                            <div className="flex flex-col sm:flex-row items-center gap-4 p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                <div className="relative">
                                    <div className="w-16 h-16 rounded-full overflow-hidden bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center border-2 border-indigo-500 shadow-sm">
                                        {editForm.data.photo ? (
                                            <img
                                                src={URL.createObjectURL(editForm.data.photo)}
                                                alt="Preview"
                                                className="w-full h-full object-cover"
                                            />
                                        ) : targetEmployee?.photo_url && !editForm.data.remove_photo ? (
                                            <img
                                                src={targetEmployee.photo_url}
                                                alt={targetEmployee.name}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <User className="w-8 h-8 text-indigo-400" />
                                        )}
                                    </div>
                                </div>
                                <div className="space-y-1.5 flex-1">
                                    <Label className="text-xs font-medium">Ubah Pasfoto Profil (Opsional)</Label>
                                    <Input
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        onChange={(e) => {
                                            if (e.target.files && e.target.files[0]) {
                                                editForm.setData({
                                                    ...editForm.data,
                                                    photo: e.target.files[0],
                                                    remove_photo: false,
                                                });
                                            }
                                        }}
                                        className="text-xs file:mr-3 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    />
                                    <div className="flex items-center justify-between text-[11px] text-zinc-500">
                                        <span>JPG, PNG, atau WebP (Maks 3MB)</span>
                                        {targetEmployee?.photo_url && !editForm.data.remove_photo && (
                                            <button
                                                type="button"
                                                onClick={() => editForm.setData({ ...editForm.data, remove_photo: true, photo: null })}
                                                className="text-rose-600 hover:text-rose-700 underline text-[11px]"
                                            >
                                                Hapus Pasfoto
                                            </button>
                                        )}
                                        {editForm.data.remove_photo && (
                                            <span className="text-rose-500 text-[11px] font-medium">Foto akan dihapus saat disimpan</span>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Nama Lengkap *</Label>
                                    <Input
                                        required
                                        value={editForm.data.name}
                                        onChange={(e) => editForm.setData('name', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Nama Panggilan *</Label>
                                    <Input
                                        required
                                        value={editForm.data.nickname}
                                        onChange={(e) => editForm.setData('nickname', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">NIK KTP (16 Digit) *</Label>
                                    <Input
                                        required
                                        maxLength={16}
                                        value={editForm.data.nik_ktp}
                                        onChange={(e) => editForm.setData('nik_ktp', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">No. HP / WhatsApp *</Label>
                                    <Input
                                        required
                                        value={editForm.data.phone_number}
                                        onChange={(e) => editForm.setData('phone_number', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Email Pribadi</Label>
                                    <Input
                                        type="email"
                                        value={editForm.data.email}
                                        onChange={(e) => editForm.setData('email', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Jenis Kelamin *</Label>
                                    <SearchableSelect
                                        value={editForm.data.gender}
                                        onValueChange={(val) => editForm.setData('gender', val)}
                                        options={genderOptions}
                                        placeholder="Pilih Gender..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Ukuran Baju Seragam *</Label>
                                    <SearchableSelect
                                        value={editForm.data.shirt_size}
                                        onValueChange={(val) => editForm.setData('shirt_size', val)}
                                        options={sizeOptions}
                                        placeholder="Pilih Ukuran..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Agama *</Label>
                                    <SearchableSelect
                                        value={editForm.data.religion}
                                        onValueChange={(val) => editForm.setData('religion', val)}
                                        options={religionOptions}
                                        placeholder="Pilih Agama..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Pendidikan Terakhir *</Label>
                                    <SearchableSelect
                                        value={editForm.data.education}
                                        onValueChange={(val) => editForm.setData('education', val)}
                                        options={educationOptions}
                                        placeholder="Pilih Pendidikan..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Tempat Lahir</Label>
                                    <Input
                                        value={editForm.data.birth_place}
                                        onChange={(e) => editForm.setData('birth_place', e.target.value)}
                                        placeholder="Kota / Tempat Lahir"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Tanggal Lahir</Label>
                                    <Input
                                        type="date"
                                        value={editForm.data.birth_date}
                                        onChange={(e) => editForm.setData('birth_date', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Status Pernikahan</Label>
                                    <SearchableSelect
                                        value={editForm.data.marital_status}
                                        onValueChange={(val) => editForm.setData('marital_status', val)}
                                        options={maritalOptions}
                                        placeholder="Pilih Status..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-medium">Alamat Domisili Lengkap</Label>
                                <Textarea
                                    rows={2}
                                    value={editForm.data.address}
                                    onChange={(e) => editForm.setData('address', e.target.value)}
                                    placeholder="RT/RW, Desa, Kecamatan, Kab/Kota..."
                                />
                            </div>
                        </div>

                        {/* Seksi 2: Posisi & Penempatan Organisasi */}
                        <div className="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">2</span>
                                Posisi & Penempatan Organisasi
                            </h3>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Divisi Kerja *</Label>
                                    <SearchableSelect
                                        value={editForm.data.department}
                                        onValueChange={(val) => editForm.setData('department', val)}
                                        options={toOptions(dropdowns.departments)}
                                        placeholder="Pilih / Cari Divisi..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Posisi / Jabatan *</Label>
                                    <SearchableSelect
                                        value={editForm.data.position}
                                        onValueChange={(val) => editForm.setData('position', val)}
                                        options={toOptions(dropdowns.positions)}
                                        placeholder="Pilih / Cari Posisi..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Level / Jenjang *</Label>
                                    <SearchableSelect
                                        value={editForm.data.job_level}
                                        onValueChange={(val) => {
                                            editForm.setData('job_level', val);
                                            if (val === 'Magang') {
                                                editForm.setData('employment_status', 'Magang');
                                            }
                                        }}
                                        options={toOptions(dropdowns.job_levels)}
                                        placeholder="Pilih / Cari Jenjang..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Status Ketenagakerjaan *</Label>
                                    <SearchableSelect
                                        value={editForm.data.employment_status}
                                        onValueChange={(val) => editForm.setData('employment_status', val)}
                                        options={toOptions(dropdowns.employment_statuses)}
                                        placeholder="Pilih / Cari Status..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Badan Usaha / Legal Entity</Label>
                                    <SearchableSelect
                                        value={editForm.data.legal_entity}
                                        onValueChange={(val) => editForm.setData('legal_entity', val)}
                                        options={toOptions(dropdowns.legal_entities)}
                                        placeholder="Pilih / Cari CV..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Tanggal Mulai Bergabung</Label>
                                    <Input
                                        type="date"
                                        value={editForm.data.join_date}
                                        onChange={(e) => editForm.setData('join_date', e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Seksi 3: Khusus Peserta Magang (Jika Job Level Magang) */}
                        {editForm.data.job_level === 'Magang' && (
                            <div className="space-y-3 pt-2 border-t border-emerald-200 dark:border-emerald-800 bg-emerald-50/40 dark:bg-emerald-950/20 p-3 rounded-lg">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                                    <GraduationCap className="h-4 w-4" />
                                    Data Institusi Magang SMK / PKL
                                </h3>

                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Nama Sekolah SMK *</Label>
                                        <Input
                                            value={editForm.data.intern_school_name}
                                            onChange={(e) => editForm.setData('intern_school_name', e.target.value)}
                                            placeholder="Asal Sekolah / Lembaga SMK"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Jurusan Siswa</Label>
                                        <Input
                                            value={editForm.data.intern_major}
                                            onChange={(e) => editForm.setData('intern_major', e.target.value)}
                                            placeholder="Tata Busana / Multimedia"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">No. Induk Siswa (NIS)</Label>
                                        <Input
                                            value={editForm.data.intern_nis}
                                            onChange={(e) => editForm.setData('intern_nis', e.target.value)}
                                            placeholder="2024.12.xxx"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Guru Pembimbing Sekolah</Label>
                                        <Input
                                            value={editForm.data.intern_mentor_teacher}
                                            onChange={(e) => editForm.setData('intern_mentor_teacher', e.target.value)}
                                            placeholder="e.g. Bu Ningsih"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">No. HP Guru Pembimbing</Label>
                                        <Input
                                            value={editForm.data.intern_mentor_phone}
                                            onChange={(e) => editForm.setData('intern_mentor_phone', e.target.value)}
                                            placeholder="0812xxxxxxxx"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Durasi Magang</Label>
                                        <Input
                                            value={editForm.data.intern_duration_text}
                                            onChange={(e) => editForm.setData('intern_duration_text', e.target.value)}
                                            placeholder="3 Bulan"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Seksi 4: Rekening & Jaminan Sosial */}
                        <div className="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">3</span>
                                Rekening Payroll & Jaminan Sosial
                            </h3>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Bank Pembayaran</Label>
                                    <SearchableSelect
                                        value={editForm.data.bank_name}
                                        onValueChange={(val) => editForm.setData('bank_name', val)}
                                        options={bankOptions}
                                        placeholder="Pilih Bank..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">No. Rekening Bank</Label>
                                    <Input
                                        value={editForm.data.bank_account_no}
                                        onChange={(e) => editForm.setData('bank_account_no', e.target.value)}
                                        placeholder="0011-01-xxxxxx-xx-x"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">No. BPJS Kesehatan</Label>
                                    <Input
                                        value={editForm.data.bpjs_kesehatan_no}
                                        onChange={(e) => editForm.setData('bpjs_kesehatan_no', e.target.value)}
                                        placeholder="000xxxxxxxx"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">No. BPJS Ketenagakerjaan</Label>
                                    <Input
                                        value={editForm.data.bpjs_ketenagakerjaan_no}
                                        onChange={(e) => editForm.setData('bpjs_ketenagakerjaan_no', e.target.value)}
                                        placeholder="000xxxxxxxx"
                                    />
                                </div>

                                <div className="space-y-1 sm:col-span-2">
                                    <Label className="text-xs font-medium">Catatan Khusus Karyawan</Label>
                                    <Input
                                        value={editForm.data.notes}
                                        onChange={(e) => editForm.setData('notes', e.target.value)}
                                        placeholder="Catatan tambahan mengenai karyawan ini..."
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsEditModalOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                {editForm.processing ? 'Menyimpan...' : 'Perbarui Profil'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 3: Konfirmasi Hapus */}
            <Dialog open={isDeleteModalOpen} onOpenChange={setIsDeleteModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-rose-600">
                            <Trash2 className="h-5 w-5" />
                            Konfirmasi Hapus Data Karyawan
                        </DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus data <strong>{targetEmployee?.name}</strong>? Seluruh riwayat kontrak, kompensasi, dan magang terkait akan ikut terhapus secara permanen.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="gap-2 mt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setIsDeleteModalOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={handleDeleteSubmit}
                        >
                            Ya, Hapus Permanen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
