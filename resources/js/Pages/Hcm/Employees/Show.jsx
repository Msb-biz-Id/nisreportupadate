import { Head, Link, router, useForm } from '@inertiajs/react';
import { formatDate } from '@/lib/utils';
import { useState, useMemo } from 'react';
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
    Edit2,
    Gift,
    Eye,
    ChevronLeft,
    ChevronRight,
    Scissors,
    XCircle,
    Search,
    Calendar,
    Paperclip,
    ShieldAlert,
    Filter,
    Sparkles,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import UniversalDocumentViewer from '@/Components/Hcm/UniversalDocumentViewer';
import OffboardEmployeeDialog from '@/Components/Hcm/OffboardEmployeeDialog';
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

export default function EmployeeShow({
    employee,
    dropdowns,
    attendanceYear = new Date().getFullYear(),
    attendanceMonth = 'all',
    attendanceMonthlySummaries = [],
    attendanceAnnualTotals = {},
    attendanceRecords = [],
    availableYears = [],
}) {
    const [activeTab, setActiveTab] = useState('biodata');

    // State Modals
    const [isContractModalOpen, setIsContractModalOpen] = useState(false);
    const [isCompensationModalOpen, setIsCompensationModalOpen] = useState(false);
    const [isOffboardModalOpen, setIsOffboardModalOpen] = useState(false);
    const [pdfPreview, setPdfPreview] = useState(null);

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
        contractForm.post(route('hcm.employees.contracts.store', employee.employee_code || employee.id), {
            forceFormData: true,
            onSuccess: () => {
                setIsContractModalOpen(false);
                contractForm.reset();
            },
        });
    };

    const handleCompensationSubmit = (e) => {
        e.preventDefault();
        compensationForm.post(route('hcm.employees.compensation-histories.store', employee.employee_code || employee.id), {
            onSuccess: () => {
                setIsCompensationModalOpen(false);
                compensationForm.reset();
            },
        });
    };

    // State & Form Reward & Apresiasi Karyawan
    const [isRewardModalOpen, setIsRewardModalOpen] = useState(false);
    const [editingReward, setEditingReward] = useState(null);

    const rewardForm = useForm({
        employee_id: employee.id,
        reward_name: '',
        reward_year: new Date().getFullYear(),
        distribution_status: 'Belum Diterima',
        received_date: '',
        document_status: 'Berita Acara Terlampir',
        budget_amount: 0,
        proof_url: '',
        notes: '',
    });

    const openAddRewardModal = () => {
        setEditingReward(null);
        rewardForm.reset();
        rewardForm.setData({
            employee_id: employee.id,
            reward_name: '',
            reward_year: new Date().getFullYear(),
            distribution_status: 'Belum Diterima',
            received_date: '',
            document_status: 'Berita Acara Terlampir',
            budget_amount: 0,
            proof_url: '',
            notes: '',
        });
        setIsRewardModalOpen(true);
    };

    const openEditRewardModal = (rw) => {
        setEditingReward(rw);
        rewardForm.setData({
            employee_id: employee.id,
            reward_name: rw.reward_name,
            reward_year: rw.reward_year,
            distribution_status: rw.distribution_status,
            received_date: rw.received_date ? String(rw.received_date).slice(0, 10) : '',
            document_status: rw.document_status || 'Berita Acara Terlampir',
            budget_amount: rw.budget_amount || 0,
            proof_url: rw.proof_url || '',
            notes: rw.notes || '',
        });
        setIsRewardModalOpen(true);
    };

    const handleRewardSubmit = (e) => {
        e.preventDefault();
        if (editingReward) {
            rewardForm.put(route('hcm.rewards.update', editingReward.uuid || editingReward.id), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsRewardModalOpen(false);
                    rewardForm.reset();
                },
            });
        } else {
            rewardForm.post(route('hcm.rewards.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsRewardModalOpen(false);
                    rewardForm.reset();
                },
            });
        }
    };

    const handleRewardDelete = (rw) => {
        if (confirm(`Hapus catatan penghargaan "${rw.reward_name}" untuk ${employee.name}?`)) {
            router.delete(route('hcm.rewards.destroy', rw.uuid || rw.id), {
                preserveScroll: true,
            });
        }
    };

    const getRewardStatusBadge = (st) => {
        if (st === 'Sudah Diterima (Serah Terima Langsung)') {
            return (
                <Badge className="bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-[11px] font-semibold gap-1">
                    <CheckCircle2 className="w-3 h-3" /> Serah Terima
                </Badge>
            );
        }
        if (st === 'Sudah Ditransfer') {
            return (
                <Badge className="bg-sky-500/15 text-sky-600 dark:text-sky-400 border border-sky-500/30 text-[11px] font-semibold gap-1">
                    <CheckCircle2 className="w-3 h-3" /> Ditransfer
                </Badge>
            );
        }
        if (st === 'Tertunda / Pending') {
            return (
                <Badge className="bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-[11px] font-semibold gap-1">
                    <Clock className="w-3 h-3" /> Tertunda
                </Badge>
            );
        }
        if (st === 'Dibatalkan') {
            return (
                <Badge variant="outline" className="border-rose-300 text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-950/20 text-[11px] gap-1">
                    <XCircle className="w-3 h-3" /> Dibatalkan
                </Badge>
            );
        }
        return (
            <Badge variant="outline" className="border-zinc-300 text-zinc-600 dark:text-zinc-400 text-[11px] gap-1">
                <Clock className="w-3 h-3" /> Belum Diterima
            </Badge>
        );
    };

    // Form Rekap Onboarding (Modul 12)
    const onboardingForm = useForm({
        position: employee.onboarding?.position || employee.position || '',
        department: employee.onboarding?.department || employee.department || '',
        join_date: (employee.onboarding?.join_date || employee.join_date || '').slice(0, 10),
        checklist: {
            ktp: !!employee.onboarding?.status_checklist?.ktp,
            bpjs: !!employee.onboarding?.status_checklist?.bpjs,
            kontrak: !!employee.onboarding?.status_checklist?.kontrak,
            seragam: !!employee.onboarding?.status_checklist?.seragam,
        },
        approve: false,
    });

    // Form Rekap Offboarding (Modul 12)
    const offboardingForm = useForm({
        position: employee.offboarding?.position || employee.position || '',
        exit_date: (employee.offboarding?.exit_date || new Date().toISOString()).slice(0, 10),
        exit_reason: employee.offboarding?.exit_reason || '',
        notice_compliance: employee.offboarding?.notice_compliance || '',
        rights_status: employee.offboarding?.rights_status || '',
        asset_clearance: employee.offboarding?.asset_clearance || '',
        clearance_status: employee.offboarding?.clearance_status || 'Pending',
        offboarding_notes: employee.offboarding?.offboarding_notes || '',
    });

    const onboardingChecklistItems = [
        { key: 'ktp', label: 'KTP / NIK' },
        { key: 'bpjs', label: 'BPJS Kesehatan / Ketenagakerjaan' },
        { key: 'kontrak', label: 'Tanda Tangan Kontrak' },
        { key: 'seragam', label: 'Seragam Kerja' },
    ];

    const handleOnboardingSubmit = (e) => {
        e.preventDefault();
        onboardingForm.put(route('hcm.employees.onboarding.update', employee.employee_code || employee.id), {
            preserveScroll: true,
            onSuccess: () => onboardingForm.setData('approve', false),
        });
    };

    const handleOffboardingSubmit = (e) => {
        e.preventDefault();
        offboardingForm.put(route('hcm.employees.offboarding.update', employee.employee_code || employee.id), {
            preserveScroll: true,
        });
    };

    const handleReactivate = () => {
        if (!confirm(`Aktifkan kembali ${employee.name}? Rekap offboarding akan dibatalkan.`)) return;
        router.post(route('hcm.employees.toggle', employee.employee_code || employee.id), {}, { preserveScroll: true });
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

    const isIntern = employee.employee_category === 'INTERN' || employee.job_level === 'Magang';

    // State Filter Tab Rekap Absensi Tahunan & Log Insiden
    const [selectedAttYear, setSelectedAttYear] = useState(attendanceYear);
    const [selectedAttMonth, setSelectedAttMonth] = useState(attendanceMonth);
    const [attRecordCategory, setAttRecordCategory] = useState('all');
    const [attRecordSearch, setAttRecordSearch] = useState('');
    const [attSubTab, setAttSubTab] = useState('recap'); // 'recap' | 'records'

    const handleAttendanceFilter = (newYear, newMonth) => {
        setSelectedAttYear(newYear);
        setSelectedAttMonth(newMonth);
        router.get(
            route('hcm.employees.show', employee.employee_code || employee.id),
            {
                year: newYear,
                month: newMonth || 'all',
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: [
                    'attendanceYear',
                    'attendanceMonth',
                    'attendanceMonthlySummaries',
                    'attendanceAnnualTotals',
                    'attendanceRecords',
                    'availableYears',
                ],
            }
        );
    };

    const formatFullDateIndo = (dateStr) => {
        if (!dateStr) return '-';
        try {
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            });
        } catch {
            return dateStr;
        }
    };

    const getAttCategoryBadge = (category, lateMinutes = 0) => {
        switch (category) {
            case 'Terlambat':
                return (
                    <Badge variant="outline" className="border-amber-300 bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-700 text-xs gap-1 font-semibold">
                        <Clock className="w-3 h-3 text-amber-600" />
                        Terlambat {lateMinutes > 0 ? `(+${lateMinutes} mnt)` : ''}
                    </Badge>
                );
            case 'Izin':
            case 'Dinas Luar':
                return (
                    <Badge variant="outline" className="border-sky-300 bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-700 text-xs gap-1 font-semibold">
                        <FileText className="w-3 h-3 text-sky-600" />
                        {category}
                    </Badge>
                );
            case 'Sakit':
                return (
                    <Badge variant="outline" className="border-indigo-300 bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-700 text-xs gap-1 font-semibold">
                        <AlertCircle className="w-3 h-3 text-indigo-600" />
                        Sakit
                    </Badge>
                );
            case 'Alpha/Mangkir':
                return (
                    <Badge variant="outline" className="border-rose-300 bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-700 text-xs gap-1 font-semibold">
                        <XCircle className="w-3 h-3 text-rose-600" />
                        Alpha / Mangkir
                    </Badge>
                );
            case 'Pulang Cepat':
                return (
                    <Badge variant="outline" className="border-orange-300 bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300 dark:border-orange-700 text-xs gap-1 font-semibold">
                        <Clock className="w-3 h-3 text-orange-600" />
                        Pulang Cepat
                    </Badge>
                );
            default:
                if (category?.includes('Cuti')) {
                    return (
                        <Badge variant="outline" className="border-purple-300 bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-700 text-xs gap-1 font-semibold">
                            <CalendarCheck className="w-3 h-3 text-purple-600" />
                            {category}
                        </Badge>
                    );
                }
                return (
                    <Badge variant="outline" className="text-xs">
                        {category || 'Pengecualian'}
                    </Badge>
                );
        }
    };

    const filteredAttendanceRecords = useMemo(() => {
        return (attendanceRecords || []).filter((rec) => {
            if (attRecordCategory !== 'all') {
                if (attRecordCategory === 'Terlambat' && rec.attendance_category !== 'Terlambat') return false;
                if (attRecordCategory === 'Izin' && !['Izin', 'Dinas Luar'].includes(rec.attendance_category)) return false;
                if (attRecordCategory === 'Sakit' && rec.attendance_category !== 'Sakit') return false;
                if (attRecordCategory === 'Cuti' && !rec.attendance_category?.includes('Cuti')) return false;
                if (attRecordCategory === 'Alpha' && rec.attendance_category !== 'Alpha/Mangkir') return false;
                if (attRecordCategory === 'Pulang Cepat' && rec.attendance_category !== 'Pulang Cepat') return false;
            }

            if (attRecordSearch.trim()) {
                const term = attRecordSearch.toLowerCase();
                const dateStr = (rec.attendance_date || '').toLowerCase();
                const notesStr = (rec.notes || '').toLowerCase();
                const catStr = (rec.attendance_category || '').toLowerCase();
                const shiftStr = (rec.shift?.name || '').toLowerCase();
                const creatorStr = (rec.creator?.name || '').toLowerCase();

                return (
                    dateStr.includes(term) ||
                    notesStr.includes(term) ||
                    catStr.includes(term) ||
                    shiftStr.includes(term) ||
                    creatorStr.includes(term)
                );
            }

            return true;
        });
    }, [attendanceRecords, attRecordCategory, attRecordSearch]);

    const tabs = isIntern
        ? [
              { id: 'biodata', label: '1. Biodata Siswa', icon: User },
              { id: 'school', label: '2. Sekolah & Kemitraan PKL', icon: GraduationCap },
              { id: 'attendance', label: '3. Rekap Kehadiran PKL', icon: CalendarCheck },
              { id: 'rewards', label: '4. Reward & Apresiasi', icon: Award, count: employee.rewards?.length || 0 },
              { id: 'offboarding', label: '5. Transisi & Status', icon: LogOut },
          ]
        : [
              { id: 'biodata', label: '1. Biodata & Identitas', icon: User },
              { id: 'contracts', label: '2. Kontrak & Legal PKWT', icon: FileText, count: employee.contracts?.length || 0 },
              { id: 'compensation', label: '3. Kompensasi & Gaji', icon: DollarSign },
              { id: 'attendance', label: '4. Rekap Absensi', icon: CalendarCheck },
              { id: 'overtime', label: '5. Lembur Mingguan', icon: Clock, count: employee.overtimes?.length || 0 },
              { id: 'rewards', label: '6. Reward & Apresiasi', icon: Award, count: employee.rewards?.length || 0 },
              { id: 'offboarding', label: '7. Transisi & Status', icon: LogOut },
          ];

    return (
        <AppLayout
            title={employee.name}
            header={
                <div className="flex items-center justify-between gap-3 min-w-0">
                    <div className="flex items-center gap-2 min-w-0">
                        <Link
                            href={route('hcm.employees.index')}
                            className="inline-flex h-8 w-8 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 shrink-0"
                            title="Kembali ke Daftar"
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                        {isIntern ? (
                            <GraduationCap className="h-4 w-4 text-emerald-600 shrink-0" />
                        ) : (
                            <User className="h-4 w-4 text-indigo-600 shrink-0" />
                        )}
                        <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                            {employee.name}
                        </span>
                        <Badge variant="outline" className="text-[10px] font-mono shrink-0 hidden sm:inline-flex">
                            {employee.employee_code}
                        </Badge>
                    </div>

                    <div className="flex items-center gap-1.5 shrink-0">
                        <Button
                            type="button"
                            onClick={() =>
                                setPdfPreview({
                                    url: route('hcm.employees.pdf.dossier', employee.employee_code || employee.id) + '?action=stream',
                                    name: `${isIntern ? 'Buku Riwayat Magang' : 'Buku Riwayat Karyawan'} - ${employee.name}`,
                                })
                            }
                            size="sm"
                            variant="outline"
                            className="h-8 text-xs gap-1.5 border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50"
                        >
                            <Eye className="h-3.5 w-3.5 text-indigo-600" />
                            <span className="hidden sm:inline">Buku Riwayat (PDF)</span>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`Buku Induk - ${employee.name}`} />

            <div className="space-y-6">
                {/* Profile Canvas Banner */}
                <div className="flex flex-col gap-4 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3.5 min-w-0">
                        {employee.photo_url ? (
                            <img
                                src={employee.photo_url}
                                alt={employee.name}
                                className="h-14 w-14 rounded-xl object-cover border border-zinc-200 dark:border-zinc-700 shadow-xs shrink-0"
                            />
                        ) : (
                            <div className={`flex h-14 w-14 items-center justify-center rounded-xl font-bold text-base shadow-xs shrink-0 ${
                                isIntern
                                    ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300'
                                    : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300'
                            }`}>
                                {employee.name.charAt(0).toUpperCase()}
                            </div>
                        )}
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <h1 className="text-base sm:text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100 truncate">
                                    {employee.name}
                                </h1>
                                <Badge variant="outline" className="text-xs font-mono font-normal">
                                    {employee.employee_code}
                                </Badge>
                                {isIntern ? (
                                    <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 text-[11px] gap-1 inline-flex items-center">
                                        <GraduationCap className="h-3 w-3" />
                                        <span>Peserta Magang SMK</span>
                                    </Badge>
                                ) : (
                                    <Badge className="bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-200 text-[11px] gap-1 inline-flex items-center">
                                        <Briefcase className="h-3 w-3" />
                                        <span>{employee.job_level}</span>
                                    </Badge>
                                )}
                                <Badge className={employee.is_active ? 'bg-emerald-500/10 text-emerald-600 border-emerald-200 text-[11px]' : 'bg-zinc-500/10 text-zinc-500 text-[11px]'}>
                                    {employee.is_active ? 'Aktif' : 'Non-Aktif'}
                                </Badge>
                            </div>
                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1 truncate">
                                {isIntern && employee.intern?.school_name
                                    ? `${employee.intern.school_name} • Jurusan ${employee.intern.major || 'Umum'} • Divisi ${employee.division || employee.department}`
                                    : `${employee.division ? `${employee.division} • ` : ''}${employee.department} • ${employee.legal_entity || 'NISGroup'}`}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 shrink-0">
                        <Button
                            type="button"
                            onClick={() =>
                                setPdfPreview({
                                    url: route('hcm.employees.pdf.dossier', employee.employee_code || employee.id) + '?action=stream',
                                    name: `${isIntern ? 'Buku Riwayat Magang' : 'Buku Riwayat Karyawan'} - ${employee.name}`,
                                })
                            }
                            size="sm"
                            variant="outline"
                            className="text-xs gap-1.5 border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 shadow-xs"
                        >
                            <Eye className="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                            <span>Lihat Buku Riwayat</span>
                        </Button>

                        <a
                            href={route('hcm.employees.pdf.dossier', employee.employee_code || employee.id)}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 shadow-xs transition"
                        >
                            <Printer className="h-3.5 w-3.5 text-zinc-600 dark:text-zinc-400" />
                            <span>Unduh PDF</span>
                        </a>

                        {!isIntern && (
                            <>
                                <Button
                                    type="button"
                                    onClick={() =>
                                        setPdfPreview({
                                            url: route('hcm.employees.pdf.paklaring', employee.employee_code || employee.id) + '?action=stream',
                                            name: `Surat Pengalaman Kerja - ${employee.name}`,
                                        })
                                    }
                                    size="sm"
                                    variant="outline"
                                    className="text-xs gap-1.5 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/50 shadow-xs"
                                >
                                    <FileText className="h-3.5 w-3.5 text-amber-600" />
                                    <span>Surat Pengalaman Kerja</span>
                                </Button>

                                <Button
                                    onClick={() => setIsContractModalOpen(true)}
                                    size="sm"
                                    variant="outline"
                                    className="text-xs gap-1.5 border-zinc-200 dark:border-zinc-700 shadow-xs"
                                >
                                    <FileCheck className="h-3.5 w-3.5 text-indigo-600" />
                                    <span>Perpanjang Kontrak</span>
                                </Button>

                                <Button
                                    onClick={() => setIsCompensationModalOpen(true)}
                                    size="sm"
                                    className="text-xs gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs"
                                >
                                    <TrendingUp className="h-3.5 w-3.5" />
                                    <span>Kenaikan Gaji</span>
                                </Button>
                            </>
                        )}

                        {employee.is_active ? (
                            <Button
                                type="button"
                                onClick={() => setIsOffboardModalOpen(true)}
                                size="sm"
                                variant="outline"
                                className="text-xs gap-1.5 border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 shadow-xs"
                            >
                                <LogOut className="h-3.5 w-3.5" />
                                <span>Proses Keluar</span>
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                onClick={handleReactivate}
                                size="sm"
                                variant="outline"
                                className="text-xs gap-1.5 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 shadow-xs"
                            >
                                <CheckCircle2 className="h-3.5 w-3.5" />
                                <span>Aktifkan Kembali</span>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Tab Navigation Pill Bar */}
                <div className="flex flex-wrap items-center gap-1.5 border-b border-zinc-200 dark:border-zinc-800 pb-2">
                    {tabs.map((tab) => {
                        const Icon = tab.icon;
                        const isActive = activeTab === tab.id;
                        return (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all ${
                                    isActive
                                        ? 'bg-indigo-600 text-white shadow-xs'
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
                                                        router.post(route('hcm.employees.photo.update', employee.employee_code || employee.id), formData, {
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
                                                        router.post(route('hcm.employees.photo.update', employee.employee_code || employee.id), {
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
                                    <span className="text-zinc-400">Departemen</span>
                                    <span className="font-medium">{employee.department}</span>
                                </div>
                                <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                    <span className="text-zinc-400">Divisi</span>
                                    <span className="font-medium">{employee.division || '-'}</span>
                                </div>
                                <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                    <span className="text-zinc-400">Masa Kerja (Tenure)</span>
                                    <div className="text-right">
                                        <Badge className="bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-300 text-[10px]">
                                            {employee.tenure_bucket || `${employee.tenure_months || 0} bln`}
                                        </Badge>
                                        <span className="block text-[10px] text-zinc-400 mt-0.5">{employee.tenure_months || 0} bulan</span>
                                    </div>
                                </div>
                                <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                    <span className="text-zinc-400">Tgl Bergabung Terkini</span>
                                    <span className="font-medium">{employee.join_date ? new Date(employee.join_date).toLocaleDateString('id-ID', { dateStyle: 'medium' }) : '-'}</span>
                                </div>
                                {employee.original_join_date && employee.original_join_date !== employee.join_date && (
                                    <div className="flex justify-between py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                                        <span className="text-zinc-400">Anchor Masa Kerja</span>
                                        <span className="font-medium text-indigo-600 dark:text-indigo-400">{new Date(employee.original_join_date).toLocaleDateString('id-ID', { dateStyle: 'medium' })}</span>
                                    </div>
                                )}
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

                {/* TAB KHUSUS MAGANG: Kemitraan Sekolah & Masa PKL */}
                {activeTab === 'school' && (
                    <div className="space-y-4">
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <GraduationCap className="h-4 w-4 text-emerald-600" />
                                    Data Asal Institusi & Program Praktek Kerja Lapangan (PKL)
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Informasi kemitraan sekolah SMK, jurusan vokasi, dan kontak pembimbing lapangan.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4 text-xs">
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 p-4 rounded-lg bg-emerald-50/30 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/60">
                                    <div>
                                        <span className="text-zinc-500 dark:text-zinc-400 block mb-0.5 font-medium">Asal Sekolah SMK</span>
                                        <span className="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                            {employee.intern?.school_name || 'Belum diisi'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-500 dark:text-zinc-400 block mb-0.5 font-medium">Kelas & Rombel</span>
                                        <span className="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                            Kelas {employee.intern?.class || '-'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-500 dark:text-zinc-400 block mb-0.5 font-medium">Jurusan / Kompetensi</span>
                                        <span className="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                            {employee.intern?.major || 'Semua Jurusan'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-500 dark:text-zinc-400 block mb-0.5 font-medium">Nomor Induk Siswa (NIS)</span>
                                        <span className="font-mono text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                            {employee.intern?.nis || '-'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-500 dark:text-zinc-400 block mb-0.5 font-medium">Periode Mulai s.d. Selesai</span>
                                        <span className="text-xs font-medium text-zinc-800 dark:text-zinc-200">
                                            {employee.intern?.start_date || '-'} s.d. {employee.intern?.end_date || '-'}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-zinc-500 dark:text-zinc-400 block mb-0.5 font-medium">Durasi Magang</span>
                                        <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 text-xs">
                                            {employee.intern?.duration_text || '3 Bulan'}
                                        </Badge>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                    <div className="p-3.5 rounded-lg border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/30">
                                        <span className="text-zinc-500 dark:text-zinc-400 block mb-1 font-medium">Guru Pembimbing Sekolah</span>
                                        <span className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 block">
                                            {employee.intern?.mentor_teacher_name || 'Belum Ditentukan'}
                                        </span>
                                        <span className="text-xs font-mono text-zinc-600 dark:text-zinc-400 mt-1 block">
                                            {employee.intern?.mentor_teacher_phone || '-'}
                                        </span>
                                    </div>

                                    <div className="p-3.5 rounded-lg border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/30">
                                        <span className="text-zinc-500 dark:text-zinc-400 block mb-1 font-medium">Penempatan Departemen NISGroup</span>
                                        <span className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 block">
                                            {employee.department}
                                        </span>
                                        <span className="text-xs text-zinc-500 mt-1 block">
                                            Status: {employee.job_level} ({employee.employment_status})
                                        </span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
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
                                        <TableHead className="min-w-[140px] font-bold">Status & Divisi</TableHead>
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
                                                    <div className="text-[11px] text-zinc-400 mt-0.5 flex items-center gap-2">
                                                        <span>Masa: {contract.duration_text || '-'}</span>
                                                        {contract.file_contract_url && (
                                                            <a
                                                                href={contract.file_contract_url}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex items-center gap-1 text-[11px] text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 underline font-medium"
                                                                title="Buka Berkas Scan Kontrak"
                                                            >
                                                                <FileText className="h-3 w-3" /> Berkas
                                                            </a>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                                        {contract.employment_status}
                                                    </div>
                                                    <div className="text-[11px] text-zinc-500">
                                                        {employee.division || employee.department || '-'}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-xs text-zinc-700 dark:text-zinc-300">
                                                    {contract.legal_entity || '-'}
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <div className="font-mono text-zinc-700 dark:text-zinc-300">
                                                        {(contract.start_date ? String(contract.start_date).slice(0, 10) : '-')} s.d. {(contract.end_date ? String(contract.end_date).slice(0, 10) : 'Tetap (PKWTT)')}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {contract.end_date ? (
                                                        <Badge
                                                            variant="outline"
                                                            className={`text-[11px] font-medium ${
                                                                contract.days_remaining < 0
                                                                    ? 'bg-zinc-100 text-zinc-600 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400'
                                                                    : contract.days_remaining <= 30
                                                                    ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                                    : contract.days_remaining <= 60
                                                                    ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                            }`}
                                                        >
                                                            {contract.days_remaining > 0
                                                                ? `${contract.days_remaining} Hari Lagi`
                                                                : contract.days_remaining === 0
                                                                ? 'Hari Terakhir'
                                                                : `Selesai (${Math.abs(contract.days_remaining)} Hari Lalu)`}
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="bg-purple-50 text-purple-700 border-purple-200 text-[11px]">
                                                            Permanen (Tetap)
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge className="text-[11px] bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                                                        {contract.review_status || 'Aktif'}
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
                                                    {formatDate(hist.effective_date)}
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

                        {/* Rekam Riwayat Pemotongan Gaji & Sanksi Disiplin (Section 5.C Audit Trail) */}
                        <div className="pt-2">
                            <div className="flex items-center justify-between mb-3">
                                <div>
                                    <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                        <Scissors className="h-4 w-4 text-rose-600" />
                                        <span>Rekam Riwayat Pemotongan Gaji &amp; Sanksi Disiplin (Audit Trail)</span>
                                    </h3>
                                    <p className="text-[11px] text-zinc-500 mt-0.5">
                                        Transparansi seluruh riwayat pemotongan disiplin, kelebihan cuti, dan cuti berjenjang dalam masa kontrak.
                                    </p>
                                </div>
                                <Link
                                    href={route('hcm.salary-deductions.index')}
                                    className="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-700 font-medium hover:underline"
                                >
                                    <span>Kelola Potongan Gaji Bulanan</span>
                                    <ExternalLink className="h-3 w-3" />
                                </Link>
                            </div>

                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                                <Table>
                                    <TableHeader className="bg-zinc-50 dark:bg-zinc-800/50">
                                        <TableRow>
                                            <TableHead className="min-w-[110px] font-bold">Bulan Berlaku</TableHead>
                                            <TableHead className="min-w-[160px] font-bold">Kategori Potongan</TableHead>
                                            <TableHead className="min-w-[110px] font-bold text-center">Metode / Rate</TableHead>
                                            <TableHead className="min-w-[130px] font-bold text-right">Gaji Snapshot</TableHead>
                                            <TableHead className="min-w-[130px] font-bold text-right">Nominal Potongan</TableHead>
                                            <TableHead className="min-w-[180px] font-bold">Keterangan / Alasan</TableHead>
                                            <TableHead className="min-w-[100px] font-bold text-center">Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {employee.salary_deductions && employee.salary_deductions.length > 0 ? (
                                            employee.salary_deductions.map((ded) => (
                                                <TableRow key={ded.id}>
                                                    <TableCell className="text-xs font-mono font-medium text-zinc-800 dark:text-zinc-200">
                                                        {ded.effective_payroll_month}
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        <Badge
                                                            variant="outline"
                                                            className={`text-[10px] font-semibold ${
                                                                (ded.deduction_category || '').toLowerCase().includes('pelanggaran')
                                                                    ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300'
                                                                    : (ded.deduction_category || '').toLowerCase().includes('berjenjang') || (ded.deduction_category || '').toLowerCase().includes('maternity')
                                                                    ? 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300'
                                                                    : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300'
                                                            }`}
                                                        >
                                                            {ded.deduction_category}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-center text-xs">
                                                        {ded.calculation_type === 'percent' ? (
                                                            <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                                {ded.percentage_rate}%
                                                            </span>
                                                        ) : (
                                                            <span className="text-[11px] text-zinc-500">Nominal Tetap</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-xs text-right font-mono text-zinc-500">
                                                        {formatRp(ded.base_salary_snapshot)}
                                                    </TableCell>
                                                    <TableCell className="text-xs text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                                        - {formatRp(ded.deduction_amount)}
                                                    </TableCell>
                                                    <TableCell className="text-xs text-zinc-700 dark:text-zinc-300">
                                                        {ded.notes || '-'}
                                                    </TableCell>
                                                    <TableCell className="text-center text-xs">
                                                        <Badge
                                                            className={`text-[10px] ${
                                                                ded.status === 'APPROVED' || ded.status === 'APPLIED'
                                                                    ? 'bg-emerald-500/10 text-emerald-700 border-emerald-200'
                                                                    : 'bg-zinc-100 text-zinc-700 border-zinc-200'
                                                            }`}
                                                        >
                                                            {ded.status}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={7} className="h-24 text-center text-xs text-zinc-400">
                                                    Tidak ada riwayat pemotongan gaji atau catatan sanksi kedisiplinan yang tercatat untuk karyawan ini.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </Card>
                        </div>
                    </div>
                )}

                {/* TAB 4: Rekap & Log Presensi Karyawan (Data Izin, Telat, Sakit, Cuti, Alpha) */}
                {activeTab === 'attendance' && (
                    <div className="space-y-5">
                        {/* 1. Header Kontrol & Filter Tahun / Bulan */}
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                            <div>
                                <h3 className="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    <CalendarCheck className="h-4 w-4 text-indigo-600" />
                                    Rekap & Log Insiden Presensi Karyawan
                                </h3>
                                <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Sistem auto-hadir: Fokus mencatat data pengecualian (izin, keterlambatan, sakit, cuti, atau alpha).
                                </p>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                {/* Filter Tahun */}
                                <div className="flex items-center gap-1.5 bg-zinc-50 dark:bg-zinc-800/80 px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <Calendar className="w-3.5 h-3.5 text-zinc-500" />
                                    <span className="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Tahun:</span>
                                    <select
                                        value={selectedAttYear}
                                        onChange={(e) => handleAttendanceFilter(parseInt(e.target.value), selectedAttMonth)}
                                        className="bg-transparent border-0 text-xs font-bold text-zinc-900 dark:text-zinc-100 p-0 focus:ring-0 cursor-pointer"
                                    >
                                        {(availableYears || [new Date().getFullYear()]).map((yr) => (
                                            <option key={yr} value={yr}>
                                                {yr}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <Link
                                    href={route('hcm.attendance.index')}
                                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 text-xs font-medium text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition"
                                >
                                    <ExternalLink className="h-3.5 w-3.5 text-indigo-600" />
                                    <span>Presensi Massal</span>
                                </Link>
                            </div>
                        </div>

                        {/* 2. Kartu Indikator Metrik Tahunan (Terlambat, Izin, Sakit, Cuti, Alpha, Total) */}
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                            <Card className="border border-amber-200/80 bg-amber-50/40 dark:bg-amber-950/20 dark:border-amber-900/40 shadow-xs">
                                <CardContent className="p-3">
                                    <div className="flex items-center justify-between text-amber-700 dark:text-amber-400">
                                        <span className="text-[11px] font-semibold">Terlambat</span>
                                        <Clock className="w-3.5 h-3.5" />
                                    </div>
                                    <div className="text-xl font-bold font-mono text-amber-800 dark:text-amber-300 mt-1">
                                        {attendanceAnnualTotals?.terlambat || 0}
                                        <span className="text-[10px] font-normal font-sans ml-1">kali</span>
                                    </div>
                                    <span className="text-[10px] text-amber-600/80">Disiplin jam kerja</span>
                                </CardContent>
                            </Card>

                            <Card className="border border-sky-200/80 bg-sky-50/40 dark:bg-sky-950/20 dark:border-sky-900/40 shadow-xs">
                                <CardContent className="p-3">
                                    <div className="flex items-center justify-between text-sky-700 dark:text-sky-400">
                                        <span className="text-[11px] font-semibold">Izin / Dinas</span>
                                        <FileText className="w-3.5 h-3.5" />
                                    </div>
                                    <div className="text-xl font-bold font-mono text-sky-800 dark:text-sky-300 mt-1">
                                        {attendanceAnnualTotals?.izin || 0}
                                        <span className="text-[10px] font-normal font-sans ml-1">hari</span>
                                    </div>
                                    <span className="text-[10px] text-sky-600/80">Izin resmi disetujui</span>
                                </CardContent>
                            </Card>

                            <Card className="border border-indigo-200/80 bg-indigo-50/40 dark:bg-indigo-950/20 dark:border-indigo-900/40 shadow-xs">
                                <CardContent className="p-3">
                                    <div className="flex items-center justify-between text-indigo-700 dark:text-indigo-400">
                                        <span className="text-[11px] font-semibold">Sakit</span>
                                        <AlertCircle className="w-3.5 h-3.5" />
                                    </div>
                                    <div className="text-xl font-bold font-mono text-indigo-800 dark:text-indigo-300 mt-1">
                                        {attendanceAnnualTotals?.sakit || 0}
                                        <span className="text-[10px] font-normal font-sans ml-1">hari</span>
                                    </div>
                                    <span className="text-[10px] text-indigo-600/80">Surat dokter / klinis</span>
                                </CardContent>
                            </Card>

                            <Card className="border border-purple-200/80 bg-purple-50/40 dark:bg-purple-950/20 dark:border-purple-900/40 shadow-xs">
                                <CardContent className="p-3">
                                    <div className="flex items-center justify-between text-purple-700 dark:text-purple-400">
                                        <span className="text-[11px] font-semibold">Cuti Tahunan</span>
                                        <CalendarCheck className="w-3.5 h-3.5" />
                                    </div>
                                    <div className="text-xl font-bold font-mono text-purple-800 dark:text-purple-300 mt-1">
                                        {attendanceAnnualTotals?.cuti || 0}
                                        <span className="text-[10px] font-normal font-sans ml-1">hari</span>
                                    </div>
                                    <span className="text-[10px] text-purple-600/80">Hak cuti karyawan</span>
                                </CardContent>
                            </Card>

                            <Card className={`border shadow-xs ${
                                (attendanceAnnualTotals?.alpha || 0) > 0
                                    ? 'border-rose-300 bg-rose-50/60 dark:bg-rose-950/30 dark:border-rose-800'
                                    : 'border-zinc-200/80 bg-white dark:bg-zinc-900 dark:border-zinc-800'
                            }`}>
                                <CardContent className="p-3">
                                    <div className={`flex items-center justify-between ${
                                        (attendanceAnnualTotals?.alpha || 0) > 0 ? 'text-rose-700 dark:text-rose-400 font-bold' : 'text-zinc-600 dark:text-zinc-400'
                                    }`}>
                                        <span className="text-[11px] font-semibold">Alpha / Mangkir</span>
                                        <XCircle className="w-3.5 h-3.5" />
                                    </div>
                                    <div className={`text-xl font-bold font-mono mt-1 ${
                                        (attendanceAnnualTotals?.alpha || 0) > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-zinc-800 dark:text-zinc-200'
                                    }`}>
                                        {attendanceAnnualTotals?.alpha || 0}
                                        <span className="text-[10px] font-normal font-sans ml-1">hari</span>
                                    </div>
                                    <span className={`text-[10px] ${(attendanceAnnualTotals?.alpha || 0) > 0 ? 'text-rose-600 font-semibold' : 'text-zinc-400'}`}>
                                        {(attendanceAnnualTotals?.alpha || 0) > 0 ? 'Wajib dipotong gaji' : 'Nihil mangkir'}
                                    </span>
                                </CardContent>
                            </Card>

                            <Card className="border border-zinc-200/80 bg-gradient-to-br from-zinc-50 to-indigo-50/30 dark:from-zinc-900 dark:to-indigo-950/20 dark:border-zinc-800 shadow-xs">
                                <CardContent className="p-3">
                                    <div className="flex items-center justify-between text-zinc-700 dark:text-zinc-300">
                                        <span className="text-[11px] font-semibold">Total Pengecualian</span>
                                        <ShieldAlert className="w-3.5 h-3.5 text-indigo-500" />
                                    </div>
                                    <div className="text-xl font-bold font-mono text-zinc-900 dark:text-zinc-100 mt-1">
                                        {attendanceAnnualTotals?.total_exceptions || 0}
                                        <span className="text-[10px] font-normal font-sans ml-1">catatan</span>
                                    </div>
                                    <span className="text-[10px] text-zinc-500">Tahun {selectedAttYear}</span>
                                </CardContent>
                            </Card>
                        </div>

                        {/* 3. Sub-Tab Switcher: Rekap Tahunan (12 Bulan) vs Log Record Pengecualian */}
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-200 dark:border-zinc-800 pb-2">
                            <div className="flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800/80 p-1 rounded-xl w-fit">
                                <button
                                    type="button"
                                    onClick={() => setAttSubTab('recap')}
                                    className={`flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition ${
                                        attSubTab === 'recap'
                                            ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-xs'
                                            : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                                    }`}
                                >
                                    <Calendar className="w-3.5 h-3.5 text-indigo-600" />
                                    <span>Rekapitulasi 12 Bulan (Tahunan)</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setAttSubTab('records')}
                                    className={`flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition ${
                                        attSubTab === 'records'
                                            ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 shadow-xs'
                                            : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200'
                                    }`}
                                >
                                    <FileText className="w-3.5 h-3.5 text-indigo-600" />
                                    <span>Log Record Pengecualian</span>
                                    <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ml-0.5 ${
                                        attSubTab === 'records'
                                            ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300'
                                            : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'
                                    }`}>
                                        {attendanceRecords?.length || 0}
                                    </span>
                                </button>
                            </div>

                            {attSubTab === 'records' && (
                                <span className="text-xs text-zinc-500">
                                    Menampilkan {filteredAttendanceRecords.length} catatan insiden presensi
                                </span>
                            )}
                        </div>

                        {/* TAB 1: Matriks Rekap 12 Bulan Tahunan (Januari - Desember) */}
                        {attSubTab === 'recap' && (
                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                                <div className="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <span className="font-bold text-xs text-zinc-900 dark:text-zinc-100">
                                            Matriks Presensi Tahunan per Bulan ({selectedAttYear})
                                        </span>
                                        <Badge variant="outline" className="text-[10px] text-zinc-500 font-normal">
                                            12 Bulan Kalender
                                        </Badge>
                                    </div>
                                    <span className="text-[11px] text-zinc-400 hidden sm:inline">
                                        Data tersinkron otomatis dari rekap absensi utama (klik baris dengan catatan untuk melihat rincian log)
                                    </span>
                                </div>

                                <div className="overflow-x-auto scrollbar-thin">
                                    <Table className="w-full text-xs">
                                        <TableHeader className="bg-slate-50/90 dark:bg-zinc-800/60">
                                            <TableRow>
                                                <TableHead className="font-bold min-w-[130px]">Bulan</TableHead>
                                                <TableHead className="font-bold text-center min-w-[90px]">Terlambat</TableHead>
                                                <TableHead className="font-bold text-center min-w-[90px]">Izin / Dinas</TableHead>
                                                <TableHead className="font-bold text-center min-w-[90px]">Sakit</TableHead>
                                                <TableHead className="font-bold text-center min-w-[90px]">Cuti</TableHead>
                                                <TableHead className="font-bold text-center min-w-[100px]">Alpha / Mangkir</TableHead>
                                                <TableHead className="font-bold text-center min-w-[100px]">Total Insiden</TableHead>
                                                <TableHead className="font-bold min-w-[140px]">Evaluasi Disiplin</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {(attendanceMonthlySummaries || []).map((m) => {
                                                const hasExc = m.total_exceptions > 0;
                                                return (
                                                    <TableRow
                                                        key={m.month_num}
                                                        onClick={() => {
                                                            if (hasExc) {
                                                                handleAttendanceFilter(selectedAttYear, String(m.month_num));
                                                                setAttSubTab('records');
                                                            }
                                                        }}
                                                        className={`transition ${hasExc ? 'cursor-pointer hover:bg-indigo-50/40 dark:hover:bg-indigo-950/30' : 'hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50'}`}
                                                        title={hasExc ? `Klik untuk melihat rincian ${m.total_exceptions} catatan log bulan ${m.month_name}` : `Bulan ${m.month_name} (Nihil Insiden)`}
                                                    >
                                                        <TableCell className="font-medium text-zinc-900 dark:text-zinc-100">
                                                            <div className="flex items-center gap-1.5">
                                                                <span>{m.month_name}</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-center font-mono">
                                                            {m.terlambat > 0 ? (
                                                                <span className="text-amber-600 dark:text-amber-400 font-bold bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded">
                                                                    {m.terlambat}x
                                                                </span>
                                                            ) : (
                                                                <span className="text-zinc-300 dark:text-zinc-600">-</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-center font-mono">
                                                            {m.izin > 0 ? (
                                                                <span className="text-sky-600 dark:text-sky-400 font-semibold">
                                                                    {m.izin} hari
                                                                </span>
                                                            ) : (
                                                                <span className="text-zinc-300 dark:text-zinc-600">-</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-center font-mono">
                                                            {m.sakit > 0 ? (
                                                                <span className="text-indigo-600 dark:text-indigo-400 font-semibold">
                                                                    {m.sakit} hari
                                                                </span>
                                                            ) : (
                                                                <span className="text-zinc-300 dark:text-zinc-600">-</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-center font-mono">
                                                            {m.cuti > 0 ? (
                                                                <span className="text-purple-600 dark:text-purple-400 font-semibold">
                                                                    {m.cuti} hari
                                                                </span>
                                                            ) : (
                                                                <span className="text-zinc-300 dark:text-zinc-600">-</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-center font-mono">
                                                            {m.alpha > 0 ? (
                                                                <span className="text-rose-600 dark:text-rose-400 font-bold bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded">
                                                                    {m.alpha} hari
                                                                </span>
                                                            ) : (
                                                                <span className="text-zinc-300 dark:text-zinc-600">-</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-center font-mono font-bold">
                                                            {hasExc ? (
                                                                <span className="text-zinc-900 dark:text-zinc-100">
                                                                    {m.total_exceptions}
                                                                </span>
                                                            ) : (
                                                                <span className="text-emerald-600 text-[11px] font-sans font-normal">0</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            {m.alpha > 0 ? (
                                                                <Badge variant="outline" className="border-rose-300 bg-rose-50 text-rose-700 text-[10px] gap-1">
                                                                    <XCircle className="w-2.5 h-2.5" /> Ada Alpha ({m.alpha}h)
                                                                </Badge>
                                                            ) : m.terlambat >= 4 ? (
                                                                <Badge variant="outline" className="border-amber-300 bg-amber-50 text-amber-700 text-[10px] gap-1">
                                                                    <Clock className="w-2.5 h-2.5" /> Sanksi Telat ≥4x
                                                                </Badge>
                                                            ) : m.terlambat > 0 ? (
                                                                <Badge variant="outline" className="border-amber-200 text-amber-600 text-[10px]">
                                                                    {m.terlambat}x Terlambat
                                                                </Badge>
                                                            ) : m.total_exceptions > 0 ? (
                                                                <Badge variant="outline" className="border-sky-200 text-sky-600 text-[10px]">
                                                                    {m.total_exceptions} Izin/Cuti
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="outline" className="border-emerald-200 bg-emerald-50/60 text-emerald-700 text-[10px] gap-1">
                                                                    <CheckCircle2 className="w-2.5 h-2.5 text-emerald-600" /> Sempurna (Nihil)
                                                                </Badge>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })}
                                        </TableBody>
                                    </Table>
                                </div>
                            </Card>
                        )}

                        {/* TAB 2: Log Record Data Pengecualian Presensi (Izin, Telat, Sakit, Cuti, Alpha) */}
                        {attSubTab === 'records' && (
                            <div className="space-y-3 pt-1">
                                {/* Filter Navigasi Bulan Cepat untuk Tab Log */}
                                <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
                                    <span className="text-zinc-400 font-semibold text-[11px] shrink-0 mr-1 flex items-center gap-1">
                                        <Filter className="w-3 h-3" /> Filter Bulan:
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => handleAttendanceFilter(selectedAttYear, 'all')}
                                        className={`px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                                            selectedAttMonth === 'all'
                                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs font-semibold'
                                                : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800'
                                        }`}
                                    >
                                        Semua Bulan ({selectedAttYear})
                                    </button>

                                    {(attendanceMonthlySummaries || []).map((m) => {
                                        const isSelected = String(selectedAttMonth) === String(m.month_num);
                                        const hasException = m.total_exceptions > 0;
                                        return (
                                            <button
                                                key={m.month_num}
                                                type="button"
                                                onClick={() => handleAttendanceFilter(selectedAttYear, String(m.month_num))}
                                                className={`px-2.5 py-1.5 rounded-lg text-xs font-medium transition shrink-0 flex items-center gap-1.5 border ${
                                                    isSelected
                                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs font-bold'
                                                        : hasException
                                                        ? 'bg-amber-50/50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800 hover:bg-amber-100/60'
                                                        : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800'
                                                }`}
                                            >
                                                <span>{m.month_short}</span>
                                                {hasException && (
                                                    <span className={`text-[10px] px-1 py-0.2 rounded-full font-bold ${
                                                        isSelected ? 'bg-indigo-800 text-white' : 'bg-amber-200 dark:bg-amber-900 text-amber-900 dark:text-amber-200'
                                                    }`}>
                                                        {m.total_exceptions}
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>

                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                        <FileCheck className="w-4 h-4 text-indigo-600" />
                                        Log Record Pengecualian Presensi {selectedAttMonth !== 'all' ? `(Bulan ${attendanceMonthlySummaries.find(m => String(m.month_num) === String(selectedAttMonth))?.month_name || ''} ${selectedAttYear})` : `(Sepanjang Tahun ${selectedAttYear})`}
                                    </h4>
                                    <p className="text-[11px] text-zinc-500 mt-0.5">
                                        Daftar kejadian izin, surat sakit, telat, dan ketidakhadiran yang tercatat dalam log absensi.
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    {/* Input Search Record */}
                                    <div className="relative w-44 sm:w-56">
                                        <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-zinc-400" />
                                        <Input
                                            value={attRecordSearch}
                                            onChange={(e) => setAttRecordSearch(e.target.value)}
                                            placeholder="Cari tanggal / catatan..."
                                            className="h-8 pl-8 text-xs bg-white dark:bg-zinc-900"
                                        />
                                    </div>

                                    {/* Filter Chip Kategori Record */}
                                    <div className="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-800 p-0.5 rounded-lg text-xs">
                                        {['all', 'Terlambat', 'Izin', 'Sakit', 'Cuti', 'Alpha'].map((cat) => (
                                            <button
                                                key={cat}
                                                type="button"
                                                onClick={() => setAttRecordCategory(cat)}
                                                className={`px-2 py-1 rounded-md text-[11px] font-medium transition ${
                                                    attRecordCategory === cat
                                                        ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-semibold'
                                                        : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'
                                                }`}
                                            >
                                                {cat === 'all' ? 'Semua' : cat}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                                <div className="overflow-x-auto scrollbar-thin">
                                    <Table className="w-full text-xs">
                                        <TableHeader className="bg-slate-50/90 dark:bg-zinc-800/60">
                                            <TableRow>
                                                <TableHead className="w-10 text-center font-bold">#</TableHead>
                                                <TableHead className="font-bold min-w-[150px]">Tanggal & Hari</TableHead>
                                                <TableHead className="font-bold min-w-[140px]">Kategori Presensi</TableHead>
                                                <TableHead className="font-bold min-w-[150px]">Jam & Keterlambatan</TableHead>
                                                <TableHead className="font-bold min-w-[140px]">Shift Kerja</TableHead>
                                                <TableHead className="font-bold min-w-[220px]">Keterangan / Alasan</TableHead>
                                                <TableHead className="font-bold min-w-[100px] text-center">Bukti</TableHead>
                                                <TableHead className="font-bold min-w-[130px]">Dicatat Oleh</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {filteredAttendanceRecords.length > 0 ? (
                                                filteredAttendanceRecords.map((rec, idx) => (
                                                    <TableRow key={rec.id || idx} className="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50">
                                                        <TableCell className="text-center font-mono text-zinc-400 text-xs">
                                                            {idx + 1}
                                                        </TableCell>
                                                        <TableCell className="font-medium text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                                            <div>{formatFullDateIndo(rec.attendance_date)}</div>
                                                            <div className="text-[10px] font-mono text-zinc-400 mt-0.5">
                                                                {rec.attendance_date}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {getAttCategoryBadge(rec.attendance_category, rec.late_minutes)}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap font-mono text-xs">
                                                            {rec.clock_in ? (
                                                                <div className="space-y-0.5">
                                                                    <div className="text-zinc-800 dark:text-zinc-200 font-semibold">
                                                                        {rec.clock_in.slice(0, 5)} {rec.clock_out ? `– ${rec.clock_out.slice(0, 5)}` : ''} WIB
                                                                    </div>
                                                                    {rec.late_minutes > 0 && (
                                                                        <div className="text-[10px] text-amber-600 font-sans">
                                                                            Telat {rec.late_minutes} menit
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            ) : (
                                                                <span className="text-zinc-400 font-sans text-[11px]">-</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-zinc-700 dark:text-zinc-300">
                                                            {rec.shift?.name || 'Shift Standar'}
                                                        </TableCell>
                                                        <TableCell>
                                                            {rec.notes ? (
                                                                <div className="text-zinc-700 dark:text-zinc-300 leading-snug whitespace-normal break-words max-w-[260px]">
                                                                    {rec.notes}
                                                                </div>
                                                            ) : (
                                                                <span className="text-zinc-400 italic text-[11px]">Tanpa catatan tambahan</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            {rec.attachment_url ? (
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => setPdfPreview({ url: rec.attachment_url, name: `Lampiran Presensi - ${rec.attendance_date}` })}
                                                                    className="h-7 px-2 text-[10px] gap-1 border-indigo-200 text-indigo-600"
                                                                >
                                                                    <Paperclip className="w-3 h-3" />
                                                                    <span>Bukti</span>
                                                                </Button>
                                                            ) : (
                                                                <span className="text-zinc-300 dark:text-zinc-700">-</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-zinc-600 dark:text-zinc-400 text-xs">
                                                            {rec.creator?.name || 'Admin HCM'}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={8} className="py-10 text-center">
                                                        <div className="max-w-md mx-auto space-y-2">
                                                            <div className="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                                                                <CheckCircle2 className="w-5 h-5" />
                                                            </div>
                                                            <div className="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                                                                Tidak Ada Catatan Insiden Presensi
                                                            </div>
                                                            <p className="text-[11px] text-zinc-500 leading-relaxed">
                                                                Karyawan memiliki rekam kehadiran sempurna (nihil izin, sakit, telat, atau mangkir) pada periode {selectedAttMonth !== 'all' ? `Bulan ${selectedAttMonth} / ${selectedAttYear}` : `Tahun ${selectedAttYear}`}.
                                                            </p>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </Card>
                        </div>
                        )}

                        {/* 6. Riwayat Pengajuan Cuti / Izin Formal */}
                        {employee.leave_requests && employee.leave_requests.length > 0 && (
                            <div className="space-y-2 pt-2">
                                <h4 className="text-xs font-bold uppercase tracking-wider text-zinc-500 flex items-center gap-1.5">
                                    <FileText className="w-3.5 h-3.5 text-indigo-600" />
                                    Riwayat Pengajuan Cuti / Izin Formal Karyawan
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
                                                    <TableCell className="text-xs font-mono">{formatDate(lr.start_date)} s.d. {formatDate(lr.end_date)}</TableCell>
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

                {/* TAB 5: Rekam Lembur Mingguan */}
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
                                                    {formatDate(ot.overtime_date)}
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

                {/* TAB 6: Reward & Apresiasi Karyawan */}
                {activeTab === 'rewards' && (
                    <div className="space-y-4">
                        {/* Header Bar */}
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                            <div className="min-w-0">
                                <h3 className="text-sm sm:text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    <Award className="h-5 w-5 text-amber-500 shrink-0" />
                                    Reward &amp; Apresiasi Karyawan
                                </h3>
                                <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    Rekam jejak penghargaan, bonus prestasi kerja, sertifikat keunggulan, dan apresiasi berkala untuk {employee.name}.
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center gap-2 shrink-0">
                                <Link
                                    href={route('hcm.rewards.index')}
                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 bg-zinc-50 dark:bg-zinc-800/60 hover:bg-zinc-100 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xs transition"
                                >
                                    <ExternalLink className="h-3.5 w-3.5 text-zinc-400" />
                                    <span>Semua Reward Perusahaan</span>
                                </Link>
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={openAddRewardModal}
                                    className="h-8 text-xs gap-1.5 bg-amber-600 hover:bg-amber-700 text-white shadow-xs font-medium"
                                >
                                    <Plus className="h-3.5 w-3.5" />
                                    Catat Reward Baru
                                </Button>
                            </div>
                        </div>

                        {/* Metrik Ringkasan Penghargaan Karyawan */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                                <CardContent className="p-4 flex items-center justify-between">
                                    <div>
                                        <p className="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Penghargaan</p>
                                        <p className="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                            {employee.rewards?.length || 0}
                                            <span className="text-xs font-normal text-zinc-400 ml-1.5 font-sans">Penghargaan</span>
                                        </p>
                                    </div>
                                    <div className="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/60">
                                        <Award className="w-5 h-5" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                                <CardContent className="p-4 flex items-center justify-between">
                                    <div>
                                        <p className="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Status Penyaluran</p>
                                        <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                                            {(employee.rewards || []).filter(r => ['Sudah Diterima (Serah Terima Langsung)', 'Sudah Ditransfer'].includes(r.distribution_status)).length}
                                            <span className="text-xs font-normal text-zinc-400 ml-1.5 font-sans">Tersalurkan</span>
                                        </p>
                                    </div>
                                    <div className="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/60">
                                        <CheckCircle2 className="w-5 h-5" />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                                <CardContent className="p-4 flex items-center justify-between">
                                    <div>
                                        <p className="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Nilai / Hadiah</p>
                                        <p className="text-xl sm:text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100 mt-1">
                                            {formatRp((employee.rewards || []).reduce((acc, r) => acc + (parseFloat(r.budget_amount) || 0), 0))}
                                        </p>
                                    </div>
                                    <div className="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-800/60">
                                        <DollarSign className="w-5 h-5" />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Tabel Data Reward Karyawan */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                            <div className="overflow-x-auto scrollbar-thin">
                                <Table>
                                    <TableHeader className="bg-slate-50/90 dark:bg-zinc-800/60">
                                        <TableRow>
                                            <TableHead className="w-12 text-center font-bold">#</TableHead>
                                            <TableHead className="font-bold min-w-[240px]">Penghargaan &amp; Keterangan</TableHead>
                                            <TableHead className="font-bold text-center min-w-[90px]">Tahun</TableHead>
                                            <TableHead className="font-bold text-right min-w-[130px]">Nilai / Hadiah</TableHead>
                                            <TableHead className="font-bold text-center min-w-[150px]">Status Penyaluran</TableHead>
                                            <TableHead className="font-bold text-center min-w-[130px]">Tanggal Diterima</TableHead>
                                            <TableHead className="font-bold text-center min-w-[100px]">Bukti</TableHead>
                                            <TableHead className="font-bold text-center min-w-[100px]">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {employee.rewards && employee.rewards.length > 0 ? (
                                            employee.rewards.map((rw, idx) => (
                                                <TableRow key={rw.id || idx} className="hover:bg-zinc-50/80 dark:hover:bg-zinc-800/50">
                                                    <TableCell className="text-center font-mono text-xs text-zinc-400">
                                                        {idx + 1}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-start gap-2.5">
                                                            <div className="p-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5 border border-amber-200/60 dark:border-amber-800/60">
                                                                <Award className="w-4 h-4" />
                                                            </div>
                                                            <div className="min-w-0">
                                                                <p className="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                                                    {rw.reward_name}
                                                                </p>
                                                                {rw.notes ? (
                                                                    <p className="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-2">
                                                                        {rw.notes}
                                                                    </p>
                                                                ) : (
                                                                    <p className="text-[10px] text-zinc-400 italic mt-0.5">
                                                                        {rw.document_status || 'Berita Acara Terlampir'}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Badge variant="outline" className="font-mono text-xs font-semibold">
                                                            {rw.reward_year}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <span className="font-mono text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                                            {formatRp(rw.budget_amount)}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-center whitespace-nowrap">
                                                        {getRewardStatusBadge(rw.distribution_status)}
                                                    </TableCell>
                                                    <TableCell className="text-center font-mono text-xs text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                                        {rw.received_date ? formatDate(rw.received_date) : '-'}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {rw.proof_url ? (
                                                            <a
                                                                href={rw.proof_url}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="inline-flex items-center gap-1 text-[11px] text-indigo-600 hover:text-indigo-700 hover:underline font-medium"
                                                            >
                                                                <ExternalLink className="w-3 h-3" />
                                                                <span>Lihat</span>
                                                            </a>
                                                        ) : (
                                                            <span className="text-zinc-400 text-xs">-</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <div className="flex items-center justify-center gap-1">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => openEditRewardModal(rw)}
                                                                className="h-7 w-7 p-0 text-zinc-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/50"
                                                                title="Edit Data Reward"
                                                            >
                                                                <Edit2 className="w-3.5 h-3.5" />
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleRewardDelete(rw)}
                                                                className="h-7 w-7 p-0 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50"
                                                                title="Hapus Data Reward"
                                                            >
                                                                <Trash2 className="w-3.5 h-3.5" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={8} className="py-12 text-center">
                                                    <div className="max-w-md mx-auto space-y-3">
                                                        <div className="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto border border-amber-200/60 dark:border-amber-800/60">
                                                            <Award className="w-6 h-6" />
                                                        </div>
                                                        <div>
                                                            <h4 className="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                                                Belum Ada Catatan Reward &amp; Apresiasi
                                                            </h4>
                                                            <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-1 leading-relaxed">
                                                                Karyawan ini belum memiliki riwayat reward atau penghargaan tercatat. Klik tombol di bawah untuk mencatat reward pertama.
                                                            </p>
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            onClick={openAddRewardModal}
                                                            className="bg-amber-600 hover:bg-amber-700 text-white text-xs gap-1.5 shadow-xs"
                                                        >
                                                            <Plus className="w-3.5 h-3.5" />
                                                            Catat Reward Pertama
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </Card>
                    </div>
                )}

                {/* TAB 7: Transisi & Offboarding (Modul 12) */}
                {activeTab === 'offboarding' && (
                    <div className="space-y-4">
                        {/* Rekap Onboarding Karyawan Baru */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-6">
                            <div className="flex flex-wrap items-center justify-between gap-2 mb-4">
                                <div>
                                    <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                        <FileCheck className="h-4 w-4 text-indigo-600" /> Rekap Onboarding Karyawan Baru
                                    </h3>
                                    <p className="text-[11px] text-zinc-400 mt-0.5">Kelengkapan berkas & approval masuk kerja.</p>
                                </div>
                                <Badge className={employee.onboarding?.approved_date ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600'}>
                                    {employee.onboarding?.approved_date ? 'Disetujui' : 'Dalam Proses'}
                                </Badge>
                            </div>

                            <form onSubmit={handleOnboardingSubmit} className="space-y-4">
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Divisi Saat Masuk</Label>
                                        <Input value={onboardingForm.data.position} onChange={(e) => onboardingForm.setData('position', e.target.value)} />
                                    </div>
                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Departemen</Label>
                                        <Input value={onboardingForm.data.department} onChange={(e) => onboardingForm.setData('department', e.target.value)} />
                                    </div>
                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Tanggal Masuk</Label>
                                        <Input type="date" value={onboardingForm.data.join_date || ''} onChange={(e) => onboardingForm.setData('join_date', e.target.value)} />
                                    </div>
                                </div>

                                <div>
                                    <span className="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">Kelengkapan Berkas</span>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                                        {onboardingChecklistItems.map((item) => {
                                            const checked = !!onboardingForm.data.checklist[item.key];
                                            return (
                                                <button
                                                    key={item.key}
                                                    type="button"
                                                    onClick={() => onboardingForm.setData('checklist', { ...onboardingForm.data.checklist, [item.key]: !checked })}
                                                    className={`flex items-center gap-2 rounded-md border px-3 py-2 text-xs text-left transition ${checked ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10' : 'border-zinc-200 bg-white text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300'}`}
                                                >
                                                    <CheckCircle2 className={`h-4 w-4 shrink-0 ${checked ? 'text-emerald-600' : 'text-zinc-300'}`} />
                                                    {item.label}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <span className="text-[11px] text-zinc-400">
                                        {employee.onboarding?.approved_date
                                            ? `Disetujui pada ${String(employee.onboarding.approved_date).slice(0, 10)}`
                                            : 'Approval otomatis saat seluruh berkas lengkap.'}
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <Button type="button" variant="outline" size="sm" onClick={() => onboardingForm.setData('approve', true)}>
                                            <CheckCircle2 className="h-3.5 w-3.5 mr-1.5" /> Approve
                                        </Button>
                                        <Button type="submit" size="sm" disabled={onboardingForm.processing} className="bg-indigo-600 hover:bg-indigo-700 text-white">
                                            Simpan
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </Card>

                        {/* Rekap Offboarding Karyawan Keluar */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm p-6">
                            <div className="flex flex-wrap items-center justify-between gap-2 mb-4">
                                <div>
                                    <h3 className="text-sm font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                        <LogOut className="h-4 w-4 text-rose-600" /> Rekap Offboarding Karyawan Keluar
                                    </h3>
                                    <p className="text-[11px] text-zinc-400 mt-0.5">Clearance, sisa hak, dan pengembalian aset.</p>
                                </div>
                                <Badge className={employee.is_active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600'}>
                                    {employee.is_active ? 'Aktif Bekerja' : 'Offboarding / Keluar'}
                                </Badge>
                            </div>

                            {employee.is_active ? (
                                <div className="rounded-md border border-dashed border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50 px-4 py-6 text-center text-xs text-zinc-500">
                                    Karyawan masih berstatus <strong>AKTIF</strong>. Nonaktifkan karyawan terlebih dahulu (tombol status di header) untuk membuka rekap offboarding.
                                </div>
                            ) : (
                                <form onSubmit={handleOffboardingSubmit} className="space-y-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div className="space-y-1">
                                            <Label className="text-xs font-medium">Divisi Terakhir</Label>
                                            <Input value={offboardingForm.data.position} onChange={(e) => offboardingForm.setData('position', e.target.value)} />
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs font-medium">Tanggal Keluar *</Label>
                                            <Input type="date" required value={offboardingForm.data.exit_date || ''} onChange={(e) => offboardingForm.setData('exit_date', e.target.value)} />
                                            {offboardingForm.errors.exit_date && <p className="text-[11px] text-rose-500">{offboardingForm.errors.exit_date}</p>}
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs font-medium">Alasan Keluar</Label>
                                            <Input value={offboardingForm.data.exit_reason} onChange={(e) => offboardingForm.setData('exit_reason', e.target.value)} placeholder="e.g. Habis kontrak, Menikah" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div className="space-y-1">
                                            <Label className="text-xs font-medium">Kepatuhan Notice Period</Label>
                                            <SearchableSelect
                                                value={offboardingForm.data.notice_compliance}
                                                onValueChange={(val) => offboardingForm.setData('notice_compliance', val)}
                                                options={toOptions(dropdowns.notice_compliance)}
                                                placeholder="Pilih Notice Period..."
                                                className="text-xs"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs font-medium">Hak Sisa Karyawan</Label>
                                            <SearchableSelect
                                                value={offboardingForm.data.rights_status}
                                                onValueChange={(val) => offboardingForm.setData('rights_status', val)}
                                                options={toOptions(dropdowns.rights_status)}
                                                placeholder="Pilih Status Hak..."
                                                className="text-xs"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs font-medium">Pengembalian Aset & Surat Pengalaman Kerja</Label>
                                            <SearchableSelect
                                                value={offboardingForm.data.asset_clearance}
                                                onValueChange={(val) => offboardingForm.setData('asset_clearance', val)}
                                                options={toOptions(dropdowns.asset_clearance)}
                                                placeholder="Pilih Status Aset..."
                                                className="text-xs"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label className="text-xs font-medium">Status Clearance Sheet</Label>
                                            <SearchableSelect
                                                value={offboardingForm.data.clearance_status}
                                                onValueChange={(val) => offboardingForm.setData('clearance_status', val)}
                                                options={toOptions(dropdowns.clearance_status?.length ? dropdowns.clearance_status : ['Pending', 'Selesai (Clear)'])}
                                                placeholder="Pilih Status Clearance..."
                                                className="text-xs"
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-1">
                                        <Label className="text-xs font-medium">Catatan / Keterangan</Label>
                                        <Textarea rows={3} value={offboardingForm.data.offboarding_notes} onChange={(e) => offboardingForm.setData('offboarding_notes', e.target.value)} placeholder="Contoh: Surat Pengalaman Kerja sudah diserahkan" />
                                    </div>

                                    <div className="flex justify-end">
                                        <Button type="submit" size="sm" disabled={offboardingForm.processing} className="bg-rose-600 hover:bg-rose-700 text-white">
                                            Simpan Offboarding
                                        </Button>
                                    </div>
                                </form>
                            )}
                        </Card>
                    </div>
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
                                <Label className="text-xs font-medium">Divisi *</Label>
                                <SearchableSelect
                                    value={contractForm.data.position}
                                    onValueChange={(val) => contractForm.setData('position', val)}
                                    options={toOptions(dropdowns.divisions || dropdowns.positions)}
                                    placeholder="Pilih / Cari Divisi..."
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
                                Berkas kontrak fisik otomatis tersimpan ke penyimpanan sistem.
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

            {/* MODAL 3: Catat / Edit Reward & Apresiasi Karyawan */}
            <Dialog open={isRewardModalOpen} onOpenChange={setIsRewardModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <Award className="h-4 w-4 text-amber-600" />
                            {editingReward ? 'Edit Data Reward / Apresiasi' : 'Catat Reward & Apresiasi Karyawan'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            {editingReward
                                ? `Perbarui informasi reward untuk ${employee.name}.`
                                : `Pencatatan bonus prestasi atau apresiasi kinerja untuk ${employee.name}.`}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleRewardSubmit} className="space-y-3 pt-2">
                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Nama Penghargaan / Apresiasi *</Label>
                            <Input
                                required
                                value={rewardForm.data.reward_name}
                                onChange={(e) => rewardForm.setData('reward_name', e.target.value)}
                                placeholder="e.g. Employee of the Month Q1 / Bonus Target"
                                className="text-xs"
                            />
                            {rewardForm.errors.reward_name && (
                                <p className="text-[11px] text-rose-500">{rewardForm.errors.reward_name}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Tahun Penghargaan *</Label>
                                <Input
                                    type="number"
                                    min="2020"
                                    max="2035"
                                    required
                                    value={rewardForm.data.reward_year}
                                    onChange={(e) => rewardForm.setData('reward_year', parseInt(e.target.value) || new Date().getFullYear())}
                                    className="text-xs font-mono"
                                />
                                {rewardForm.errors.reward_year && (
                                    <p className="text-[11px] text-rose-500">{rewardForm.errors.reward_year}</p>
                                )}
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Nilai Hadiah (Rp)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    step="50000"
                                    value={rewardForm.data.budget_amount}
                                    onChange={(e) => rewardForm.setData('budget_amount', parseFloat(e.target.value) || 0)}
                                    placeholder="0"
                                    className="text-xs font-mono"
                                />
                                {rewardForm.errors.budget_amount && (
                                    <p className="text-[11px] text-rose-500">{rewardForm.errors.budget_amount}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Status Penyaluran *</Label>
                                <Select
                                    value={rewardForm.data.distribution_status}
                                    onValueChange={(val) => rewardForm.setData('distribution_status', val)}
                                >
                                    <SelectTrigger className="text-xs">
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(dropdowns?.status_penyaluran_reward || [
                                            'Belum Diterima',
                                            'Sudah Diterima (Serah Terima Langsung)',
                                            'Sudah Ditransfer',
                                            'Tertunda / Pending',
                                            'Dibatalkan',
                                        ]).map((st) => (
                                            <SelectItem key={st} value={st} className="text-xs">
                                                {st}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {rewardForm.errors.distribution_status && (
                                    <p className="text-[11px] text-rose-500">{rewardForm.errors.distribution_status}</p>
                                )}
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Tanggal Diterima</Label>
                                <Input
                                    type="date"
                                    value={rewardForm.data.received_date}
                                    onChange={(e) => rewardForm.setData('received_date', e.target.value)}
                                    className="text-xs"
                                />
                                {rewardForm.errors.received_date && (
                                    <p className="text-[11px] text-rose-500">{rewardForm.errors.received_date}</p>
                                )}
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Tautan Dokumen / Foto Bukti (Opsional)</Label>
                            <Input
                                type="url"
                                value={rewardForm.data.proof_url}
                                onChange={(e) => rewardForm.setData('proof_url', e.target.value)}
                                placeholder="https://..."
                                className="text-xs font-mono"
                            />
                            {rewardForm.errors.proof_url && (
                                <p className="text-[11px] text-rose-500">{rewardForm.errors.proof_url}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Catatan / Keterangan Tambahan</Label>
                            <Textarea
                                rows={2}
                                value={rewardForm.data.notes}
                                onChange={(e) => rewardForm.setData('notes', e.target.value)}
                                placeholder="Dasar penilaian, prestasi yang dicapai..."
                                className="text-xs resize-none"
                            />
                            {rewardForm.errors.notes && (
                                <p className="text-[11px] text-rose-500">{rewardForm.errors.notes}</p>
                            )}
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsRewardModalOpen(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={rewardForm.processing}
                                className="bg-amber-600 hover:bg-amber-700 text-white font-medium"
                            >
                                {rewardForm.processing ? 'Menyimpan...' : (editingReward ? 'Simpan Perubahan' : 'Catat Reward')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL IN-APP DOCUMENT VIEWER */}
            <UniversalDocumentViewer
                isOpen={!!pdfPreview}
                onClose={() => setPdfPreview(null)}
                fileUrl={pdfPreview?.url}
                fileName={pdfPreview?.name || 'Dokumen Profil Karyawan'}
            />

            {/* MODAL PROSES KARYAWAN KELUAR (Modul 12) */}
            {isOffboardModalOpen && (
                <OffboardEmployeeDialog
                    isOpen={isOffboardModalOpen}
                    onClose={() => setIsOffboardModalOpen(false)}
                    employee={employee}
                    dropdowns={dropdowns}
                />
            )}
        </AppLayout>
    );
}
