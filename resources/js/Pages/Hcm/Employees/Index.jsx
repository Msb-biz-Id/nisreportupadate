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
import OffboardEmployeeDialog from '@/Components/Hcm/OffboardEmployeeDialog';

export default function EmployeeIndex({ employees, filters, metrics, dropdowns, schools = [] }) {
    const [activeCategory, setActiveCategory] = useState(filters.category || 'regular');
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department || 'all');
    const [selectedDivision, setSelectedDivision] = useState(filters.division || 'all');
    const [selectedTenureBucket, setSelectedTenureBucket] = useState(filters.tenure_bucket || 'all');
    const [selectedJobLevel, setSelectedJobLevel] = useState(filters.job_level || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'all');
    const [selectedSchool, setSelectedSchool] = useState(filters.school || 'all');

    // State Modals
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [targetEmployee, setTargetEmployee] = useState(null);
    const [offboardTarget, setOffboardTarget] = useState(null);

    const toOptions = (arr) => (arr || []).map((v) => (typeof v === 'string' ? { value: v, label: v } : v));
    const genderOptions = useMemo(() => toOptions(dropdowns.genders?.length ? dropdowns.genders : ['Laki-Laki', 'Perempuan']), [dropdowns.genders]);
    const sizeOptions = useMemo(() => toOptions(dropdowns.shirt_sizes?.length ? dropdowns.shirt_sizes : ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']), [dropdowns.shirt_sizes]);
    const maritalOptions = useMemo(() => toOptions(dropdowns.marital_statuses?.length ? dropdowns.marital_statuses : ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati']), [dropdowns.marital_statuses]);
    const religionOptions = useMemo(() => toOptions(dropdowns.religions?.length ? dropdowns.religions : ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']), [dropdowns.religions]);
    const educationOptions = useMemo(() => toOptions(dropdowns.educations?.length ? dropdowns.educations : ['SD / Sederajat', 'SMP / Sederajat', 'SMA / SMK / Sederajat', 'Diploma 3 (D3)', 'Strata 1 (S1)', 'Strata 2 (S2)']), [dropdowns.educations]);
    const bankOptions = useMemo(() => toOptions(dropdowns.banks?.length ? dropdowns.banks : ['Bank BRI', 'Bank Mandiri', 'Bank BCA', 'Bank BNI', 'BSI', 'Tunai / Kas']), [dropdowns.banks]);

    // Opsi divisi dinamis tergantung departemen yang dipilih
    const availableDivisions = useMemo(() => {
        if (selectedDepartment && selectedDepartment !== 'all' && dropdowns.department_division_map?.[selectedDepartment]) {
            return dropdowns.department_division_map[selectedDepartment];
        }
        return dropdowns.divisions || [];
    }, [selectedDepartment, dropdowns.department_division_map, dropdowns.divisions]);

    // Form Tambah Karyawan Baru
    const createForm = useForm({
        employee_category: filters.category === 'intern' ? 'INTERN' : 'REGULAR',
        name: '',
        nickname: '',
        department: dropdowns.departments?.[0] || 'Produksi',
        division: '',
        position: dropdowns.positions?.[0] || '',
        job_level: dropdowns.job_levels?.[0] || 'Kontrak',
        employment_status: dropdowns.employment_statuses?.[0] || 'PKWT',
        legal_entity: dropdowns.legal_entities?.[0] || '',
        phone_number: '',
        gender: dropdowns.genders?.[0] || 'Laki-Laki',
        religion: dropdowns.religions?.[0] || 'Islam',
        education: dropdowns.educations?.[0] || 'SMA / SMK / Sederajat',
        marital_status: dropdowns.marital_statuses?.[0] || 'Belum Menikah',
        birth_place: '',
        birth_date: '',
        nik_ktp: '',
        bpjs_kesehatan_no: '',
        bpjs_ketenagakerjaan_no: '',
        shirt_size: dropdowns.shirt_sizes?.[0] || 'L',
        address: '',
        bank_account_no: '',
        bank_name: 'Bank BRI',
        email: '',
        join_date: new Date().toISOString().split('T')[0],
        original_join_date: new Date().toISOString().split('T')[0],
        notes: '',
        photo: null,
        // Field Magang
        intern_school_name: '',
        intern_class: 'XII',
        intern_major: '',
        intern_nis: '',
        intern_student_phone: '',
        intern_student_address: '',
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
        employee_category: 'REGULAR',
        name: '',
        nickname: '',
        department: '',
        division: '',
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
        original_join_date: '',
        notes: '',
        photo: null,
        remove_photo: false,
        // Field Magang jika ada
        intern_school_name: '',
        intern_class: 'XII',
        intern_major: '',
        intern_nis: '',
        intern_student_phone: '',
        intern_student_address: '',
        intern_start_date: '',
        intern_end_date: '',
        intern_duration_text: '',
        intern_mentor_teacher: '',
        intern_mentor_phone: '',
    });

    // Filter submit handler
    const applyFilters = (
        dept = selectedDepartment,
        div = selectedDivision,
        tenure = selectedTenureBucket,
        level = selectedJobLevel,
        stat = selectedStatus,
        search = searchTerm,
        cat = activeCategory,
        sch = selectedSchool
    ) => {
        router.get(
            route('hcm.employees.index'),
            {
                category: cat,
                department: dept,
                division: div,
                tenure_bucket: tenure,
                job_level: level,
                status: stat,
                search: search,
                school: sch,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleCategoryChange = (cat) => {
        setActiveCategory(cat);
        applyFilters(selectedDepartment, selectedDivision, selectedTenureBucket, selectedJobLevel, selectedStatus, searchTerm, cat, selectedSchool);
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        applyFilters();
    };

    // Toggle status aktif karyawan
    const handleToggleStatus = (employee) => {
        // Off -> nonaktif: buka dialog offboarding agar data keluar tercatat lengkap.
        if (employee.is_active) {
            setOffboardTarget(employee);
            return;
        }
        // On -> aktifkan kembali (batalkan rekap offboarding).
        if (!confirm(`Aktifkan kembali ${employee.name}? Rekap offboarding akan dibatalkan.`)) return;
        router.post(
            route('hcm.employees.toggle', employee.employee_code || employee.id),
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
            employee_category: emp.employee_category || (emp.job_level === 'Magang' ? 'INTERN' : 'REGULAR'),
            name: emp.name || '',
            nickname: emp.nickname || '',
            department: emp.department || '',
            division: emp.division || '',
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
            original_join_date: emp.original_join_date ? emp.original_join_date.split('T')[0] : (emp.join_date ? emp.join_date.split('T')[0] : ''),
            notes: emp.notes || '',
            photo: null,
            remove_photo: false,
            intern_school_name: emp.intern?.school_name || '',
            intern_class: emp.intern?.class || 'XII',
            intern_major: emp.intern?.major || '',
            intern_nis: emp.intern?.nis || '',
        intern_student_phone: emp.intern?.student_phone || '',
        intern_student_address: emp.intern?.student_address || '',
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
        editForm.post(route('hcm.employees.update', targetEmployee.employee_code || targetEmployee.id), {
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
        router.delete(route('hcm.employees.destroy', targetEmployee.employee_code || targetEmployee.id), {
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

    const getInternDaysRemaining = (endDateStr) => {
        if (!endDateStr) return null;
        const end = new Date(endDateStr);
        const now = new Date();
        const diffTime = end.setHours(0, 0, 0, 0) - now.setHours(0, 0, 0, 0);
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    };

    return (
        <AppLayout
            title={activeCategory === 'intern' ? 'Peserta Magang SMK - NISGroup' : 'Master Karyawan - NISGroup'}
            header={
                <div className="flex items-center gap-2 min-w-0">
                    {activeCategory === 'intern' ? (
                        <GraduationCap className="h-4 w-4 text-indigo-600 shrink-0" />
                    ) : (
                        <Users className="h-4 w-4 text-indigo-600 shrink-0" />
                    )}
                    <span className="text-sm sm:text-base font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        1. Master Karyawan (Data Umum Karyawan)
                    </span>
                </div>
            }
        >
            <Head title={`Master Karyawan - ${activeCategory === 'intern' ? 'Data Peserta Magang NISGroup' : 'Data Karyawan Managerial/Kontrak/Borongan'}`} />

            <div className="space-y-5">
                {/* Banner Judul Modul */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-zinc-200/80 dark:border-zinc-800/80">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                            {activeCategory === 'intern' ? (
                                <GraduationCap className="h-5 w-5" />
                            ) : (
                                <Users className="h-5 w-5" />
                            )}
                        </div>
                        <div>
                            <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                1. Master Karyawan (Data Umum Karyawan)
                            </h1>
                            <p className="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
                                <span className="font-semibold text-indigo-600 dark:text-indigo-400">
                                    {activeCategory === 'intern' ? 'Kategori: Data Peserta Magang NISGroup' : 'Kategori: Data Karyawan Managerial/Kontrak/Borongan NISGroup'}
                                </span> — {activeCategory === 'intern'
                                    ? 'Monitoring data siswa PKL/vokasi, asal sekolah SMK, guru pendamping, dan sisa masa magang.'
                                    : 'Kelola biodata resmi, NIK KTP, BPJS, nomor rekening payroll BRI, ukuran seragam, dan buku induk NISGroup.'}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            onClick={() => {
                                createForm.setData((prev) => ({
                                    ...prev,
                                    employee_category: activeCategory === 'intern' ? 'INTERN' : 'REGULAR',
                                    job_level: activeCategory === 'intern' ? 'Magang' : (dropdowns.job_levels?.[0] || 'Kontrak'),
                                    employment_status: activeCategory === 'intern' ? 'Magang' : (dropdowns.employment_statuses?.[0] || 'PKWT'),
                                }));
                                setIsCreateModalOpen(true);
                            }}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm flex items-center gap-2 text-xs sm:text-sm h-9"
                        >
                            <UserPlus className="h-4 w-4" />
                            <span>{activeCategory === 'intern' ? 'Tambah Peserta Magang' : 'Tambah Karyawan'}</span>
                        </Button>
                    </div>
                </div>

                {/* Segmented Tab Bar: Model Karyawan Reguler vs Peserta Magang */}
                <div className="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-1">
                    <button
                        type="button"
                        onClick={() => handleCategoryChange('regular')}
                        className={`inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${
                            activeCategory === 'regular'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        }`}
                    >
                        <Users className="h-4 w-4 shrink-0" />
                        <span>Data Karyawan Managerial / Kontrak / Borongan</span>
                        <span className={`text-[11px] px-2 py-0.5 rounded-full ${
                            activeCategory === 'regular'
                                ? 'bg-indigo-700/80 text-white'
                                : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'
                        }`}>
                            {metrics?.total_active_regular ?? 0}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => handleCategoryChange('intern')}
                        className={`inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${
                            activeCategory === 'intern'
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 hover:bg-zinc-100 dark:hover:bg-zinc-800'
                        }`}
                    >
                        <GraduationCap className="h-4 w-4 shrink-0" />
                        <span>Data Peserta Magang NISGroup</span>
                        <span className={`text-[11px] px-2 py-0.5 rounded-full ${
                            activeCategory === 'intern'
                                ? 'bg-indigo-700/80 text-white'
                                : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'
                        }`}>
                            {metrics?.total_interns_active ?? 0}
                        </span>
                    </button>
                </div>

                {/* 1. Baris Kartu Metrik Statistik Sesuai Tab Aktif */}
                {activeCategory === 'regular' ? (
                    <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between pb-1.5 p-3 space-y-0">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Karyawan Aktif
                                </CardTitle>
                                <div className="rounded-md bg-emerald-50 dark:bg-emerald-950/50 p-1.5 text-emerald-600 dark:text-emerald-400">
                                    <UserCheck className="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent className="p-3 pt-0">
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {metrics?.total_active_regular ?? 0}
                                </div>
                                <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Status aktif di seluruh divisi
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between pb-1.5 p-3 space-y-0">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Karyawan Tetap
                                </CardTitle>
                                <div className="rounded-md bg-purple-50 dark:bg-purple-950/50 p-1.5 text-purple-600 dark:text-purple-400">
                                    <Briefcase className="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent className="p-3 pt-0">
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {metrics?.total_permanent ?? 0}
                                </div>
                                <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Perjanjian Tetap (PKWTT)
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between pb-1.5 p-3 space-y-0">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Kontrak (PKWT)
                                </CardTitle>
                                <div className="rounded-md bg-blue-50 dark:bg-blue-950/50 p-1.5 text-blue-600 dark:text-blue-400">
                                    <FileText className="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent className="p-3 pt-0">
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {metrics?.total_contract ?? 0}
                                </div>
                                <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    PKWT Utama & Lanjutan
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between pb-1.5 p-3 space-y-0">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Borongan & Harian
                                </CardTitle>
                                <div className="rounded-md bg-amber-50 dark:bg-amber-950/50 p-1.5 text-amber-600 dark:text-amber-400">
                                    <Users className="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent className="p-3 pt-0">
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {metrics?.total_freelance ?? 0}
                                </div>
                                <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Tenaga borongan & harian
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between pb-1.5 p-3 space-y-0">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Siswa Magang Aktif
                                </CardTitle>
                                <div className="rounded-md bg-emerald-50 dark:bg-emerald-950/50 p-1.5 text-emerald-600 dark:text-emerald-400">
                                    <GraduationCap className="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent className="p-3 pt-0">
                                <div className="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                                    {metrics?.total_interns_active ?? 0}
                                </div>
                                <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Sedang menjalani magang PKL
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border border-amber-200 bg-amber-50/40 dark:bg-amber-950/20 dark:border-amber-900/50 shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between pb-1.5 p-3 space-y-0">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Habis dlm 30 Hari
                                </CardTitle>
                                <div className="rounded-md bg-amber-100 dark:bg-amber-900/60 p-1.5 text-amber-700 dark:text-amber-300">
                                    <Clock className="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent className="p-3 pt-0">
                                <div className="text-xl font-bold text-amber-700 dark:text-amber-400">
                                    {metrics?.interns_expiring_soon ?? 0}
                                </div>
                                <p className="text-[11px] text-amber-600/80 mt-0.5">
                                    Segera penarikan kembali
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between pb-1.5 p-3 space-y-0">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Asal Sekolah SMK
                                </CardTitle>
                                <div className="rounded-md bg-sky-50 dark:bg-sky-950/50 p-1.5 text-sky-600 dark:text-sky-400">
                                    <Building2 className="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent className="p-3 pt-0">
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {metrics?.total_intern_schools ?? 0}
                                </div>
                                <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Mitra sekolah vokasi terdaftar
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <CardHeader className="flex flex-row items-center justify-between pb-1.5 p-3 space-y-0">
                                <CardTitle className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Magang Selesai
                                </CardTitle>
                                <div className="rounded-md bg-zinc-100 dark:bg-zinc-800 p-1.5 text-zinc-600 dark:text-zinc-400">
                                    <CheckCircle2 className="h-4 w-4" />
                                </div>
                            </CardHeader>
                            <CardContent className="p-3 pt-0">
                                <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {metrics?.interns_completed ?? 0}
                                </div>
                                <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Telah menuntaskan program
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* 2. Filter Bar Terintegrasi Sesuai Tab Aktif */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <CardContent className="p-3.5">
                        <form onSubmit={handleSearchSubmit} className="flex flex-col gap-2.5 lg:flex-row lg:items-center">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                                <Input
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder={
                                        activeCategory === 'intern'
                                            ? 'Cari nama siswa, NIS, nama sekolah SMK, jurusan...'
                                            : 'Cari nama karyawan, NIK, nama panggilan, nomor HP...'
                                    }
                                    className="pl-9 h-9 text-xs"
                                />
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                {activeCategory === 'regular' ? (
                                    <>
                                        {/* Filter Departemen */}
                                        <div className="w-[155px]">
                                            <SearchableSelect
                                                value={selectedDepartment}
                                                onValueChange={(val) => {
                                                    const finalVal = val || 'all';
                                                    setSelectedDepartment(finalVal);
                                                    setSelectedDivision('all');
                                                    applyFilters(finalVal, 'all', selectedTenureBucket, selectedJobLevel, selectedStatus, searchTerm, activeCategory, selectedSchool);
                                                }}
                                                options={[
                                                    { value: 'all', label: 'Semua Departemen' },
                                                    ...toOptions(dropdowns.departments),
                                                ]}
                                                placeholder="Pilih Departemen"
                                                clearable={false}
                                                className="text-xs h-9"
                                            />
                                        </div>

                                        {/* Filter Divisi (Dependent) */}
                                        <div className="w-[145px]">
                                            <SearchableSelect
                                                value={selectedDivision}
                                                onValueChange={(val) => {
                                                    const finalVal = val || 'all';
                                                    setSelectedDivision(finalVal);
                                                    applyFilters(selectedDepartment, finalVal, selectedTenureBucket, selectedJobLevel, selectedStatus, searchTerm, activeCategory, selectedSchool);
                                                }}
                                                options={[
                                                    { value: 'all', label: 'Semua Divisi' },
                                                    ...toOptions(availableDivisions),
                                                ]}
                                                placeholder="Pilih Divisi"
                                                clearable={false}
                                                className="text-xs h-9"
                                            />
                                        </div>

                                        {/* Filter Pengelompokan Masa Kerja (Tenure Bucket) */}
                                        <div className="w-[160px]">
                                            <SearchableSelect
                                                value={selectedTenureBucket}
                                                onValueChange={(val) => {
                                                    const finalVal = val || 'all';
                                                    setSelectedTenureBucket(finalVal);
                                                    applyFilters(selectedDepartment, selectedDivision, finalVal, selectedJobLevel, selectedStatus, searchTerm, activeCategory, selectedSchool);
                                                }}
                                                options={[
                                                    { value: 'all', label: 'Semua Masa Kerja' },
                                                    ...(dropdowns.tenure_buckets || [
                                                        { value: '<1_year', label: 'Kurang dari 1 Tahun' },
                                                        { value: '1_year', label: 'Kelompok 1 Tahun' },
                                                        { value: '2_years', label: 'Kelompok 2 Tahun' },
                                                        { value: '3_years', label: 'Kelompok 3+ Tahun' },
                                                    ]),
                                                ]}
                                                placeholder="Pilih Masa Kerja"
                                                clearable={false}
                                                className="text-xs h-9"
                                            />
                                        </div>

                                        {/* Filter Level/Jenjang */}
                                        <div className="w-[130px]">
                                            <SearchableSelect
                                                value={selectedJobLevel}
                                                onValueChange={(val) => {
                                                    const finalVal = val || 'all';
                                                    setSelectedJobLevel(finalVal);
                                                    applyFilters(selectedDepartment, selectedDivision, selectedTenureBucket, finalVal, selectedStatus, searchTerm, activeCategory, selectedSchool);
                                                }}
                                                options={[
                                                    { value: 'all', label: 'Semua Level' },
                                                    ...toOptions(dropdowns.job_levels?.filter((l) => l !== 'Magang')),
                                                ]}
                                                placeholder="Pilih Level"
                                                clearable={false}
                                                className="text-xs h-9"
                                            />
                                        </div>
                                    </>
                                ) : (
                                    /* Filter Sekolah SMK (Khusus Peserta Magang) */
                                    <div className="w-[200px]">
                                        <SearchableSelect
                                            value={selectedSchool}
                                            onValueChange={(val) => {
                                                const finalVal = val || 'all';
                                                setSelectedSchool(finalVal);
                                                applyFilters(selectedDepartment, selectedDivision, selectedTenureBucket, selectedJobLevel, selectedStatus, searchTerm, activeCategory, finalVal);
                                            }}
                                            options={[
                                                { value: 'all', label: 'Semua Sekolah SMK' },
                                                ...schools.map((sch) => ({ value: sch, label: sch })),
                                            ]}
                                            placeholder="Pilih Sekolah"
                                            clearable={false}
                                            className="text-xs h-9"
                                        />
                                    </div>
                                )}

                                {/* Filter Status Aktif */}
                                <div className="w-[125px]">
                                    <SearchableSelect
                                        value={selectedStatus}
                                        onValueChange={(val) => {
                                            const finalVal = val || 'all';
                                            setSelectedStatus(finalVal);
                                            applyFilters(selectedDepartment, selectedDivision, selectedTenureBucket, selectedJobLevel, finalVal, searchTerm, activeCategory, selectedSchool);
                                        }}
                                        options={[
                                            { value: 'all', label: 'Semua Status' },
                                            { value: 'active', label: 'Aktif Saja' },
                                            { value: 'inactive', label: 'Non-Aktif' },
                                        ]}
                                        placeholder="Pilih Status"
                                        clearable={false}
                                        className="text-xs h-9"
                                    />
                                </div>

                                <Button type="submit" variant="secondary" size="sm" className="h-9 text-xs px-3">
                                    Cari
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* 3. Tabel Data Tersegmentasi */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                    <Table>
                        <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                            {activeCategory === 'regular' ? (
                                <TableRow>
                                    <TableHead className="w-[45px] text-center font-bold">No</TableHead>
                                    <TableHead className="min-w-[200px] font-bold">Karyawan & Identitas</TableHead>
                                    <TableHead className="min-w-[170px] font-bold">Departemen & Divisi</TableHead>
                                    <TableHead className="min-w-[150px] font-bold">Masa Kerja (Loyalitas)</TableHead>
                                    <TableHead className="min-w-[130px] font-bold">Jenjang & Entitas</TableHead>
                                    <TableHead className="min-w-[150px] font-bold">Kontak & NIK KTP</TableHead>
                                    <TableHead className="min-w-[150px] font-bold">Rekening & BPJS</TableHead>
                                    <TableHead className="w-[80px] text-center font-bold">Seragam</TableHead>
                                    <TableHead className="min-w-[100px] text-center font-bold">Status Aktif</TableHead>
                                    <TableHead className="w-[150px] text-center font-bold">Aksi</TableHead>
                                </TableRow>
                            ) : (
                                <TableRow>
                                    <TableHead className="w-[45px] text-center font-bold">No</TableHead>
                                    <TableHead className="min-w-[180px] font-bold">Nama</TableHead>
                                    <TableHead className="min-w-[120px] font-bold">Nama Panggil</TableHead>
                                    <TableHead className="min-w-[180px] font-bold">Nama Sekolah</TableHead>
                                    <TableHead className="min-w-[80px] font-bold text-center">Kelas</TableHead>
                                    <TableHead className="min-w-[140px] font-bold">Jurusan</TableHead>
                                    <TableHead className="min-w-[130px] font-bold">No. Induk Siswa (NIS)</TableHead>
                                    <TableHead className="min-w-[120px] font-bold">No HP</TableHead>
                                    <TableHead className="min-w-[120px] font-bold">Tanggal Bergabung</TableHead>
                                    <TableHead className="min-w-[120px] font-bold">Tanggal Berakhir</TableHead>
                                    <TableHead className="min-w-[100px] font-bold">Durasi Magang</TableHead>
                                    <TableHead className="min-w-[150px] font-bold">Guru Pendamping</TableHead>
                                    <TableHead className="min-w-[140px] font-bold">No HP Guru Pendamping</TableHead>
                                    <TableHead className="min-w-[200px] font-bold">Alamat</TableHead>
                                    <TableHead className="w-[130px] text-center font-bold sticky right-0 bg-zinc-50/95 dark:bg-zinc-800/95 backdrop-blur-xs shadow-xs">Aksi</TableHead>
                                </TableRow>
                            )}
                        </TableHeader>
                        <TableBody>
                            {employees.data && employees.data.length > 0 ? (
                                employees.data.map((emp, index) => {
                                    const internDays = emp.intern?.end_date ? getInternDaysRemaining(emp.intern.end_date) : null;

                                    if (activeCategory === 'regular') {
                                        return (
                                            <TableRow key={emp.id} className="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/50 transition-colors">
                                                <TableCell className="text-center font-medium text-xs text-zinc-500">
                                                    {employees.from + index}
                                                </TableCell>

                                                {/* Karyawan & Identitas */}
                                                <TableCell>
                                                    <div className="flex items-center gap-2.5">
                                                        {emp.photo_url ? (
                                                            <img
                                                                src={emp.photo_url}
                                                                alt={emp.name}
                                                                className="h-9 w-9 shrink-0 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shadow-xs"
                                                            />
                                                        ) : (
                                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-500 to-violet-500 text-white font-bold text-xs shadow-xs">
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
                                                            <div className="text-[11px] font-mono text-zinc-400 mt-0.5">
                                                                {emp.employee_code}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                {/* Departemen & Divisi */}
                                                <TableCell>
                                                    <div className="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                                        {emp.division || emp.department}
                                                    </div>
                                                    <div className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                        Departemen {emp.department}
                                                    </div>
                                                </TableCell>

                                                {/* Masa Kerja & Tenure Bucket */}
                                                <TableCell>
                                                    <div className="flex flex-col gap-0.5 items-start">
                                                        <Badge variant="outline" className={`text-[10px] px-2 py-0 font-medium ${
                                                            (emp.tenure_months ?? 0) >= 36 ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' :
                                                            (emp.tenure_months ?? 0) >= 24 ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800' :
                                                            (emp.tenure_months ?? 0) >= 12 ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' :
                                                            'bg-zinc-500/10 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800'
                                                        }`}>
                                                            {emp.tenure_bucket || 'Kurang dari 1 Tahun'}
                                                        </Badge>
                                                        <span className="text-[11px] text-zinc-400 font-mono">
                                                            {emp.tenure_months ?? 0} bln (sejak {emp.original_join_date ? new Date(emp.original_join_date).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) : '-'})
                                                        </span>
                                                    </div>
                                                </TableCell>

                                                {/* Jenjang & Entitas */}
                                                <TableCell>
                                                    <div className="flex flex-col gap-1 items-start">
                                                        <Badge variant="outline" className={`text-[10px] px-2 py-0 font-medium ${getJobLevelBadge(emp.job_level)}`}>
                                                            {emp.job_level}
                                                        </Badge>
                                                        <span className="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                            {emp.legal_entity || emp.employment_status}
                                                        </span>
                                                    </div>
                                                </TableCell>

                                                {/* Kontak & NIK */}
                                                <TableCell>
                                                    <div className="text-xs font-mono text-zinc-800 dark:text-zinc-200">
                                                        {emp.phone_number}
                                                    </div>
                                                    <div className="text-[11px] font-mono text-zinc-400 mt-0.5">
                                                        NIK: {emp.nik_ktp}
                                                    </div>
                                                </TableCell>

                                                {/* Rekening & BPJS */}
                                                <TableCell>
                                                    <div className="text-xs text-zinc-800 dark:text-zinc-200">
                                                        {emp.bank_name ? `${emp.bank_name}: ` : ''}{emp.bank_account_no || '-'}
                                                    </div>
                                                    <div className="text-[11px] text-zinc-400 mt-0.5">
                                                        BPJS: {emp.bpjs_kesehatan_no || emp.bpjs_ketenagakerjaan_no ? 'Terdaftar' : 'Belum Ada'}
                                                    </div>
                                                </TableCell>

                                                {/* Ukuran Seragam */}
                                                <TableCell className="text-center">
                                                    <span className="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                        {emp.shirt_size || '-'}
                                                    </span>
                                                </TableCell>

                                                {/* Switch Toggle Status Aktif */}
                                                <TableCell className="text-center">
                                                    <div className="inline-flex items-center gap-1.5">
                                                        <Switch
                                                            checked={emp.is_active}
                                                            onCheckedChange={() => handleToggleStatus(emp)}
                                                        />
                                                        <span className={`text-[11px] font-medium ${emp.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400'}`}>
                                                            {emp.is_active ? 'Aktif' : 'Off'}
                                                        </span>
                                                    </div>
                                                </TableCell>

                                                {/* Tombol Aksi */}
                                                <TableCell className="text-center">
                                                    <div className="flex items-center justify-center gap-1">
                                                        <Link
                                                            href={route('hcm.employees.show', emp.employee_code || emp.id)}
                                                            className="inline-flex items-center justify-center h-7 px-2 rounded-md text-[11px] font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:hover:bg-indigo-900 dark:text-indigo-300 transition-colors"
                                                            title="Lihat Profil Dossier 360°"
                                                        >
                                                            <Eye className="h-3 w-3 mr-1" />
                                                            Profil
                                                        </Link>

                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            className="h-7 w-7 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"
                                                            onClick={() => openEditModal(emp)}
                                                            title="Edit Data"
                                                        >
                                                            <Pencil className="h-3 w-3" />
                                                        </Button>

                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            className="h-7 w-7 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                                            onClick={() => {
                                                                setTargetEmployee(emp);
                                                                setIsDeleteModalOpen(true);
                                                            }}
                                                            title="Hapus Karyawan"
                                                        >
                                                            <Trash2 className="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    } else {
                                        // Tab Peserta Magang SMK - 13 Kolom Sesuai Excel Sheet Database Row 9
                                        return (
                                            <TableRow key={emp.id} className="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/50 transition-colors">
                                                <TableCell className="text-center font-medium text-xs text-zinc-500">
                                                    {employees.from + index}
                                                </TableCell>

                                                {/* 1. Nama */}
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        {emp.photo_url ? (
                                                            <img
                                                                src={emp.photo_url}
                                                                alt={emp.name}
                                                                className="h-8 w-8 shrink-0 rounded-full object-cover border border-zinc-200 dark:border-zinc-700 shadow-xs"
                                                            />
                                                        ) : (
                                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-teal-500 to-emerald-500 text-white font-bold text-xs shadow-xs">
                                                                {emp.name.charAt(0).toUpperCase()}
                                                            </div>
                                                        )}
                                                        <div className="min-w-0">
                                                            <div className="font-semibold text-zinc-900 dark:text-zinc-100">{emp.name}</div>
                                                            <div className="text-[10px] font-mono text-zinc-400">{emp.employee_code}</div>
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                {/* 2. Nama Panggil */}
                                                <TableCell className="font-medium text-zinc-800 dark:text-zinc-200">
                                                    {emp.nickname || '-'}
                                                </TableCell>

                                                {/* 3. Nama Sekolah */}
                                                <TableCell className="font-medium text-indigo-700 dark:text-indigo-300">
                                                    {emp.intern?.school_name || '-'}
                                                </TableCell>

                                                {/* 4. Kelas */}
                                                <TableCell className="text-center font-medium text-zinc-800 dark:text-zinc-200">
                                                    {emp.intern?.class || '-'}
                                                </TableCell>

                                                {/* 5. Jurusan */}
                                                <TableCell className="text-zinc-700 dark:text-zinc-300">
                                                    {emp.intern?.major || '-'}
                                                </TableCell>

                                                {/* 6. No. Induk Siswa (NIS) */}
                                                <TableCell className="font-mono text-zinc-800 dark:text-zinc-200">
                                                    {emp.intern?.nis || '-'}
                                                </TableCell>

                                                {/* 7. No HP */}
                                                <TableCell className="font-mono text-zinc-700 dark:text-zinc-300">
                                                    {emp.intern?.student_phone || emp.phone_number || '-'}
                                                </TableCell>

                                                {/* 8. Tanggal Bergabung */}
                                                <TableCell className="font-mono text-zinc-700 dark:text-zinc-300">
                                                    {emp.intern?.start_date ? new Date(emp.intern.start_date).toLocaleDateString('id-ID') : (emp.join_date ? new Date(emp.join_date).toLocaleDateString('id-ID') : '-')}
                                                </TableCell>

                                                {/* 9. Tanggal Berakhir */}
                                                <TableCell className="font-mono text-zinc-700 dark:text-zinc-300">
                                                    {emp.intern?.end_date ? new Date(emp.intern.end_date).toLocaleDateString('id-ID') : '-'}
                                                </TableCell>

                                                {/* 10. Durasi Magang */}
                                                <TableCell className="text-zinc-700 dark:text-zinc-300">
                                                    {emp.intern?.duration_text || '-'}
                                                </TableCell>

                                                {/* 11. Guru Pendamping */}
                                                <TableCell className="text-zinc-800 dark:text-zinc-200">
                                                    {emp.intern?.mentor_teacher_name || '-'}
                                                </TableCell>

                                                {/* 12. No HP Guru Pendamping */}
                                                <TableCell className="font-mono text-zinc-700 dark:text-zinc-300">
                                                    {emp.intern?.mentor_teacher_phone || '-'}
                                                </TableCell>

                                                {/* 13. Alamat */}
                                                <TableCell className="text-zinc-600 dark:text-zinc-400 max-w-[250px] truncate" title={emp.intern?.student_address || emp.address || ''}>
                                                    {emp.intern?.student_address || emp.address || '-'}
                                                </TableCell>

                                                {/* Aksi */}
                                                <TableCell className="text-center sticky right-0 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xs shadow-xs">
                                                    <div className="flex items-center justify-center gap-1">
                                                        <Link
                                                            href={route('hcm.employees.show', emp.employee_code || emp.id)}
                                                            className="inline-flex items-center justify-center h-7 px-2 rounded-md text-[11px] font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:hover:bg-indigo-900 dark:text-indigo-300 transition-colors"
                                                            title="Lihat Profil Dossier Magang"
                                                        >
                                                            <Eye className="h-3 w-3 mr-1" />
                                                            Profil
                                                        </Link>

                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            className="h-7 w-7 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100"
                                                            onClick={() => openEditModal(emp)}
                                                            title="Edit Data Siswa"
                                                        >
                                                            <Pencil className="h-3 w-3" />
                                                        </Button>

                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            className="h-7 w-7 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                                                            onClick={() => {
                                                                setTargetEmployee(emp);
                                                                setIsDeleteModalOpen(true);
                                                            }}
                                                            title="Hapus Siswa Magang"
                                                        >
                                                            <Trash2 className="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    }
                                })
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={activeCategory === 'regular' ? 9 : 15} className="h-32 text-center text-zinc-500 dark:text-zinc-400">
                                        {activeCategory === 'intern'
                                            ? 'Tidak ada data peserta magang SMK yang cocok dengan kriteria filter.'
                                            : 'Tidak ada data karyawan yang cocok dengan kriteria filter.'}
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
                        {/* Pilihan Model Tenaga Kerja */}
                        <div className="flex items-center gap-2 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                            <button
                                type="button"
                                onClick={() => {
                                    createForm.setData((prev) => ({
                                        ...prev,
                                        employee_category: 'REGULAR',
                                        job_level: prev.job_level === 'Magang' ? (dropdowns.job_levels?.filter((l) => l !== 'Magang')[0] || 'Kontrak') : prev.job_level,
                                        employment_status: prev.employment_status === 'Magang' ? (dropdowns.employment_statuses?.filter((s) => s !== 'Magang')[0] || 'PKWT') : prev.employment_status,
                                    }));
                                }}
                                className={`flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-md text-xs font-semibold transition ${
                                    createForm.data.employee_category === 'REGULAR'
                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'
                                }`}
                            >
                                <Users className="h-4 w-4" />
                                <span>Karyawan Reguler (Managerial / Kontrak / Borongan)</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    createForm.setData((prev) => ({
                                        ...prev,
                                        employee_category: 'INTERN',
                                        job_level: 'Magang',
                                        employment_status: 'Magang',
                                    }));
                                }}
                                className={`flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-md text-xs font-semibold transition ${
                                    createForm.data.employee_category === 'INTERN'
                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'
                                }`}
                            >
                                <GraduationCap className="h-4 w-4" />
                                <span>Peserta Magang SMK (PKL)</span>
                            </button>
                        </div>

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
                                Departemen & Divisi Kerja
                            </h3>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Departemen *</Label>
                                    <SearchableSelect
                                        value={createForm.data.department}
                                        onValueChange={(val) => {
                                            createForm.setData('department', val);
                                            const divs = dropdowns.department_division_map?.[val];
                                            if (divs && divs.length > 0) {
                                                createForm.setData('division', divs[0]);
                                                createForm.setData('position', divs[0]);
                                            } else {
                                                createForm.setData('division', '');
                                                createForm.setData('position', val);
                                            }
                                        }}
                                        options={toOptions(dropdowns.departments)}
                                        placeholder="Pilih Departemen..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Divisi Kerja *</Label>
                                    <SearchableSelect
                                        value={createForm.data.division}
                                        onValueChange={(val) => {
                                            createForm.setData('division', val);
                                            createForm.setData('position', val);
                                        }}
                                        options={toOptions(
                                            createForm.data.department && dropdowns.department_division_map?.[createForm.data.department]
                                                ? dropdowns.department_division_map[createForm.data.department]
                                                : dropdowns.divisions
                                        )}
                                        placeholder="Pilih / Cari Divisi..."
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

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Anchor Masa Kerja Permanen</Label>
                                    <Input
                                        type="date"
                                        value={createForm.data.original_join_date}
                                        onChange={(e) => createForm.setData('original_join_date', e.target.value)}
                                    />
                                    <p className="text-[10px] text-zinc-400">Anchor tanggal awal bergabung untuk akumulasi masa kerja/loyalitas.</p>
                                </div>
                            </div>
                        </div>

                        {/* Seksi 3: Khusus Peserta Magang (Muncul Otomatis jika Kategori Magang atau Job Level Magang) */}
                        {(createForm.data.employee_category === 'INTERN' || createForm.data.job_level === 'Magang') && (
                            <div className="space-y-3 pt-2 border-t border-emerald-200 dark:border-emerald-800 bg-emerald-50/40 dark:bg-emerald-950/20 p-3 rounded-lg">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                                    <GraduationCap className="h-4 w-4" />
                                    Data Institusi Magang SMK / PKL
                                </h3>

                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Nama Sekolah SMK *</Label>
                                        <Input
                                            required={createForm.data.employee_category === 'INTERN'}
                                            value={createForm.data.intern_school_name}
                                            onChange={(e) => createForm.setData('intern_school_name', e.target.value)}
                                            placeholder="e.g. SMK Negeri 1 Sukorejo"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Kelas Siswa</Label>
                                        <Input
                                            value={createForm.data.intern_class}
                                            onChange={(e) => createForm.setData('intern_class', e.target.value)}
                                            placeholder="e.g. XI / XII"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Jurusan / Kompetensi Keahlian</Label>
                                        <Input
                                            value={createForm.data.intern_major}
                                            onChange={(e) => createForm.setData('intern_major', e.target.value)}
                                            placeholder="Tata Busana / Rekayasa Perangkat Lunak"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Nomor Induk Siswa (NIS)</Label>
                                        <Input
                                            value={createForm.data.intern_nis}
                                            onChange={(e) => createForm.setData('intern_nis', e.target.value)}
                                            placeholder="2024.12.xxx"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">No. HP Siswa / Peserta</Label>
                                        <Input
                                            value={createForm.data.intern_student_phone}
                                            onChange={(e) => createForm.setData('intern_student_phone', e.target.value)}
                                            placeholder="0812xxxxxxxx"
                                        />
                                    </div>

                                    <div className="space-y-1 sm:col-span-2">
                                        <Label className="text-xs font-medium">Alamat Siswa / Tempat Tinggal</Label>
                                        <Input
                                            value={createForm.data.intern_student_address}
                                            onChange={(e) => createForm.setData('intern_student_address', e.target.value)}
                                            placeholder="RT/RW, Desa, Kecamatan, Kab/Kota"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Guru Pembimbing Sekolah</Label>
                                        <Input
                                            value={createForm.data.intern_mentor_teacher}
                                            onChange={(e) => createForm.setData('intern_mentor_teacher', e.target.value)}
                                            placeholder="e.g. Bu Ningsih, S.Pd"
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
                                        <Label className="text-xs font-medium">Tanggal Mulai Magang</Label>
                                        <Input
                                            type="date"
                                            value={createForm.data.intern_start_date}
                                            onChange={(e) => createForm.setData('intern_start_date', e.target.value)}
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Tanggal Selesai Magang</Label>
                                        <Input
                                            type="date"
                                            value={createForm.data.intern_end_date}
                                            onChange={(e) => createForm.setData('intern_end_date', e.target.value)}
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Durasi Magang</Label>
                                        <Input
                                            value={createForm.data.intern_duration_text}
                                            onChange={(e) => createForm.setData('intern_duration_text', e.target.value)}
                                            placeholder="e.g. 3 Bulan / 6 Bulan"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Seksi 4: Rekening & Jaminan Sosial / Uang Saku */}
                        <div className="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">3</span>
                                {createForm.data.employee_category === 'INTERN'
                                    ? 'Rekening & Uang Saku Magang (Opsional)'
                                    : 'Rekening Payroll & Kompensasi Awal'}
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
                                    <Label className="text-xs font-medium">
                                        {createForm.data.employee_category === 'INTERN' ? 'Uang Saku / Honor (Rp)' : 'Gaji Pokok Awal (Rp)'}
                                    </Label>
                                    <Input
                                        type="number"
                                        value={createForm.data.initial_salary}
                                        onChange={(e) => createForm.setData('initial_salary', e.target.value)}
                                        placeholder={createForm.data.employee_category === 'INTERN' ? 'e.g. 500000' : 'e.g. 2500000'}
                                    />
                                </div>

                                {createForm.data.employee_category !== 'INTERN' && (
                                    <>
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
                                            <Label className="text-xs font-medium">Nomor Kontrak PKWT (Jika Ada)</Label>
                                            <Input
                                                value={createForm.data.contract_number}
                                                onChange={(e) => createForm.setData('contract_number', e.target.value)}
                                                placeholder="XXX/OWR/PKWT/X/XXXX"
                                            />
                                        </div>
                                    </>
                                )}
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
                                {createForm.processing
                                    ? 'Menyimpan...'
                                    : createForm.data.employee_category === 'INTERN'
                                    ? 'Simpan Peserta Magang'
                                    : 'Simpan Karyawan'}
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
                            {editForm.data.employee_category === 'INTERN'
                                ? 'Edit Data Peserta Magang SMK'
                                : 'Edit Data Profil Karyawan'}
                        </DialogTitle>
                        <DialogDescription>
                            Perbarui informasi biodata, kontak, dan penempatan kerja {targetEmployee?.name}.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleEditSubmit} className="space-y-6 pt-2">
                        {/* Model Switcher di Edit */}
                        <div className="flex items-center gap-2 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                            <button
                                type="button"
                                onClick={() => {
                                    editForm.setData((prev) => ({
                                        ...prev,
                                        employee_category: 'REGULAR',
                                        job_level: prev.job_level === 'Magang' ? (dropdowns.job_levels?.filter((l) => l !== 'Magang')[0] || 'Kontrak') : prev.job_level,
                                        employment_status: prev.employment_status === 'Magang' ? (dropdowns.employment_statuses?.filter((s) => s !== 'Magang')[0] || 'PKWT') : prev.employment_status,
                                    }));
                                }}
                                className={`flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-md text-xs font-semibold transition ${
                                    editForm.data.employee_category === 'REGULAR'
                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'
                                }`}
                            >
                                <Users className="h-4 w-4" />
                                <span>Karyawan Reguler (Managerial / Kontrak / Borongan)</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    editForm.setData((prev) => ({
                                        ...prev,
                                        employee_category: 'INTERN',
                                        job_level: 'Magang',
                                        employment_status: 'Magang',
                                    }));
                                }}
                                className={`flex-1 flex items-center justify-center gap-1.5 py-2 px-3 rounded-md text-xs font-semibold transition ${
                                    editForm.data.employee_category === 'INTERN'
                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs'
                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'
                                }`}
                            >
                                <GraduationCap className="h-4 w-4" />
                                <span>Peserta Magang SMK (PKL)</span>
                            </button>
                        </div>
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

                        {/* Seksi 2: Departemen & Divisi Kerja */}
                        <div className="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">2</span>
                                Departemen & Divisi Kerja
                            </h3>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Departemen *</Label>
                                    <SearchableSelect
                                        value={editForm.data.department}
                                        onValueChange={(val) => {
                                            editForm.setData('department', val);
                                            const divs = dropdowns.department_division_map?.[val];
                                            if (divs && divs.length > 0) {
                                                editForm.setData('division', divs[0]);
                                                editForm.setData('position', divs[0]);
                                            } else {
                                                editForm.setData('division', '');
                                                editForm.setData('position', val);
                                            }
                                        }}
                                        options={toOptions(dropdowns.departments)}
                                        placeholder="Pilih Departemen..."
                                        clearable={false}
                                        className="text-xs"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Divisi Kerja *</Label>
                                    <SearchableSelect
                                        value={editForm.data.division}
                                        onValueChange={(val) => {
                                            editForm.setData('division', val);
                                            editForm.setData('position', val);
                                        }}
                                        options={toOptions(
                                            editForm.data.department && dropdowns.department_division_map?.[editForm.data.department]
                                                ? dropdowns.department_division_map[editForm.data.department]
                                                : dropdowns.divisions
                                        )}
                                        placeholder="Pilih / Cari Divisi..."
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

                                <div className="space-y-1">
                                    <Label className="text-xs font-medium">Anchor Masa Kerja Permanen</Label>
                                    <Input
                                        type="date"
                                        value={editForm.data.original_join_date}
                                        onChange={(e) => editForm.setData('original_join_date', e.target.value)}
                                    />
                                    <p className="text-[10px] text-zinc-400">Anchor tanggal awal bergabung untuk akumulasi masa kerja/loyalitas.</p>
                                </div>
                            </div>
                        </div>

                        {/* Seksi 3: Khusus Peserta Magang (Jika Job Level Magang atau Kategori INTERN) */}
                        {(editForm.data.employee_category === 'INTERN' || editForm.data.job_level === 'Magang') && (
                            <div className="space-y-3 pt-2 border-t border-emerald-200 dark:border-emerald-800 bg-emerald-50/40 dark:bg-emerald-950/20 p-3 rounded-lg">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                                    <GraduationCap className="h-4 w-4" />
                                    Data Institusi Magang SMK / PKL
                                </h3>

                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Nama Sekolah SMK *</Label>
                                        <Input
                                            required={editForm.data.employee_category === 'INTERN'}
                                            value={editForm.data.intern_school_name}
                                            onChange={(e) => editForm.setData('intern_school_name', e.target.value)}
                                            placeholder="Asal Sekolah / Lembaga SMK"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Kelas Siswa</Label>
                                        <Input
                                            value={editForm.data.intern_class}
                                            onChange={(e) => editForm.setData('intern_class', e.target.value)}
                                            placeholder="e.g. XI / XII"
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
                                        <Label className="text-xs font-medium">Nomor Induk Siswa (NIS)</Label>
                                        <Input
                                            value={editForm.data.intern_nis}
                                            onChange={(e) => editForm.setData('intern_nis', e.target.value)}
                                            placeholder="2024.12.xxx"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">No. HP Siswa / Peserta</Label>
                                        <Input
                                            value={editForm.data.intern_student_phone}
                                            onChange={(e) => editForm.setData('intern_student_phone', e.target.value)}
                                            placeholder="0812xxxxxxxx"
                                        />
                                    </div>

                                    <div className="space-y-1 sm:col-span-2">
                                        <Label className="text-xs font-medium">Alamat Siswa / Tempat Tinggal</Label>
                                        <Input
                                            value={editForm.data.intern_student_address}
                                            onChange={(e) => editForm.setData('intern_student_address', e.target.value)}
                                            placeholder="RT/RW, Desa, Kecamatan, Kab/Kota"
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Guru Pembimbing Sekolah</Label>
                                        <Input
                                            value={editForm.data.intern_mentor_teacher}
                                            onChange={(e) => editForm.setData('intern_mentor_teacher', e.target.value)}
                                            placeholder="e.g. Bu Ningsih, S.Pd"
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
                                        <Label className="text-xs font-medium">Tanggal Mulai Magang</Label>
                                        <Input
                                            type="date"
                                            value={editForm.data.intern_start_date}
                                            onChange={(e) => editForm.setData('intern_start_date', e.target.value)}
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Tanggal Selesai Magang</Label>
                                        <Input
                                            type="date"
                                            value={editForm.data.intern_end_date}
                                            onChange={(e) => editForm.setData('intern_end_date', e.target.value)}
                                        />
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Durasi Magang</Label>
                                        <Input
                                            value={editForm.data.intern_duration_text}
                                            onChange={(e) => editForm.setData('intern_duration_text', e.target.value)}
                                            placeholder="e.g. 3 Bulan / 6 Bulan"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Seksi 4: Rekening & Jaminan Sosial */}
                        <div className="space-y-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-[11px]">3</span>
                                {editForm.data.employee_category === 'INTERN'
                                    ? 'Rekening & Catatan Khusus'
                                    : 'Rekening Payroll & Jaminan Sosial'}
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

                                {editForm.data.employee_category !== 'INTERN' && (
                                    <>
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
                                    </>
                                )}

                                <div className={`space-y-1 ${editForm.data.employee_category === 'INTERN' ? 'sm:col-span-3' : 'sm:col-span-2'}`}>
                                    <Label className="text-xs font-medium">Catatan Khusus</Label>
                                    <Input
                                        value={editForm.data.notes}
                                        onChange={(e) => editForm.setData('notes', e.target.value)}
                                        placeholder="Catatan tambahan mengenai tenaga kerja ini..."
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

            {/* MODAL PROSES KARYAWAN KELUAR (Modul 12) */}
            {offboardTarget && (
                <OffboardEmployeeDialog
                    isOpen={!!offboardTarget}
                    onClose={() => setOffboardTarget(null)}
                    employee={offboardTarget}
                    dropdowns={dropdowns}
                />
            )}
        </AppLayout>
    );
}
