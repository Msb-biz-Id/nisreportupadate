import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import {
    Users,
    Search,
    Phone,
    Mail,
    FileText,
    ExternalLink,
    CheckCircle2,
    Clock,
    XCircle,
    UserCheck,
    Briefcase,
    Eye,
    Rocket,
    Building2,
    Trash2,
    Printer,
    FileSpreadsheet,
    Edit2,
    AlertTriangle,
    ShieldAlert,
    MessageCircle,
    UserX,
    Calendar,
    Award,
    RotateCcw,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import UniversalDocumentViewer from '@/Components/Hcm/UniversalDocumentViewer';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { DataTable, DataTableColumnHeader } from '@/Components/DataTable';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { SearchableSelect } from '@/Components/ui/searchable-select';

export default function ApplicantsIndex({
    applicants,
    filters = {},
    metrics = {},
    jobOptions = [],
    dropdowns = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [jobFilter, setJobFilter] = useState(filters.job_id || 'all');
    const [blacklistFilter, setBlacklistFilter] = useState(filters.blacklist || 'all');
    const [activeTab, setActiveTab] = useState('pipeline'); // 'pipeline' or 'interviews'

    const [selectedApplicant, setSelectedApplicant] = useState(null);
    const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
    const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
    const [isConvertModalOpen, setIsConvertModalOpen] = useState(false);
    const [previewDoc, setPreviewDoc] = useState(null);

    // Form Status & Evaluasi Pelamar
    const statusForm = useForm({
        status: 'SUBMITTED',
        invitation_status: 'Belum Diundang',
        interview_result: '',
        onboarding_attendance: '',
        is_blacklisted: false,
        hcm_notes: '',
        interview_date: '',
        interview_location: 'Pabrik Klaten',
        interviewer_notes: '',
        rejection_reason: '',
    });

    // Form 1-Klik Konversi ke Karyawan
    const convertForm = useForm({
        employment_status: 'Probation',
        job_level: 'Staff',
        department: '',
        position: '',
        join_date: new Date().toISOString().split('T')[0],
    });

    // Form Rekap Wawancara
    const interviewForm = useForm({
        interview_round: '',
        interviewer_name: '',
        interview_date: new Date().toISOString().split('T')[0],
        interview_result: '',
        offering_status: 'Pending',
        interview_decision: 'Pending',
        salary_expectation: '',
        offering_notes: '',
    });
    const [editingInterview, setEditingInterview] = useState(null);

    const handleFilterChange = (newSearch, newStatus, newJob, newBlacklist, page = 1, per_page = applicants.per_page) => {
        router.get(
            route('hcm.recruitment.applicants.index'),
            {
                search: newSearch,
                status: newStatus,
                job_id: newJob,
                blacklist: newBlacklist,
                page,
                per_page,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleResetFilters = () => {
        setSearch('');
        setStatusFilter('all');
        setJobFilter('all');
        setBlacklistFilter('all');
        router.get(route('hcm.recruitment.applicants.index'), {}, { preserveState: true });
    };

    const isFiltered =
        search !== '' || statusFilter !== 'all' || jobFilter !== 'all' || blacklistFilter !== 'all';

    const openDetailModal = (app) => {
        setSelectedApplicant(app);
        resetInterviewForm();
        setIsDetailModalOpen(true);
    };

    const openStatusModal = (app) => {
        setSelectedApplicant(app);
        statusForm.setData({
            status: app.status || 'SUBMITTED',
            invitation_status: app.invitation_status || 'Belum Diundang',
            interview_result: app.interview_result || '',
            onboarding_attendance: app.onboarding_attendance || '',
            is_blacklisted: Boolean(app.is_blacklisted),
            hcm_notes: app.hcm_notes || '',
            interview_date: app.interview_date ? app.interview_date.replace(' ', 'T').slice(0, 16) : '',
            interview_location: app.interview_location || 'Pabrik Klaten',
            interviewer_notes: app.interviewer_notes || '',
            rejection_reason: app.rejection_reason || '',
        });
        setIsStatusModalOpen(true);
    };

    const openConvertModal = (app) => {
        setSelectedApplicant(app);
        convertForm.setData({
            employment_status: dropdowns.employment_statuses?.[0] || 'Probation',
            job_level: dropdowns.job_levels?.[0] || 'Staff',
            department: app.job_posting?.department || dropdowns.departments?.[0] || 'Produksi',
            position: app.job_posting?.position || dropdowns.positions?.[0] || 'Operator',
            join_date: new Date().toISOString().split('T')[0],
        });
        setIsConvertModalOpen(true);
    };

    const handleStatusSubmit = (e) => {
        e.preventDefault();
        statusForm.patch(
            route('hcm.recruitment.applicants.update-status', selectedApplicant.applicant_code || selectedApplicant.id),
            {
                onSuccess: () => {
                    setIsStatusModalOpen(false);
                },
            }
        );
    };

    const handleConvertSubmit = (e) => {
        e.preventDefault();
        convertForm.post(
            route('hcm.recruitment.applicants.convert', selectedApplicant.applicant_code || selectedApplicant.id),
            {
                onSuccess: () => {
                    setIsConvertModalOpen(false);
                },
            }
        );
    };

    const resetInterviewForm = () => {
        setEditingInterview(null);
        interviewForm.reset();
        interviewForm.setData({
            interview_round: '',
            interviewer_name: '',
            interview_date: new Date().toISOString().split('T')[0],
            interview_result: '',
            offering_status: 'Pending',
            interview_decision: 'Pending',
            salary_expectation: '',
            offering_notes: '',
        });
    };

    const openEditInterview = (iv) => {
        setEditingInterview(iv);
        interviewForm.setData({
            interview_round: iv.interview_round || '',
            interviewer_name: iv.interviewer_name || '',
            interview_date: (iv.interview_date || '').slice(0, 10),
            interview_result: iv.interview_result || '',
            offering_status: iv.offering_status || 'Pending',
            interview_decision: iv.interview_decision || 'Pending',
            salary_expectation: iv.salary_expectation ?? '',
            offering_notes: iv.offering_notes || '',
        });
    };

    const handleInterviewSubmit = (e) => {
        e.preventDefault();
        if (editingInterview) {
            interviewForm.put(
                route('hcm.recruitment.interviews.update', editingInterview.uuid || editingInterview.id),
                {
                    preserveScroll: true,
                    onSuccess: () => resetInterviewForm(),
                }
            );
        } else {
            interviewForm.post(
                route(
                    'hcm.recruitment.applicants.interviews.store',
                    selectedApplicant.applicant_code || selectedApplicant.id
                ),
                {
                    preserveScroll: true,
                    onSuccess: () => resetInterviewForm(),
                }
            );
        }
    };

    const handleDeleteInterview = (iv) => {
        if (!confirm('Hapus rekap wawancara ini?')) return;
        if (editingInterview?.id === iv.id || editingInterview?.uuid === iv.uuid) setEditingInterview(null);
        router.delete(route('hcm.recruitment.interviews.destroy', iv.uuid || iv.id), { preserveScroll: true });
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'SUBMITTED':
                return (
                    <Badge variant="outline" className="border-blue-300 text-blue-600 bg-blue-50/50 text-[10px]">
                        Berkas Masuk
                    </Badge>
                );
            case 'SCREENING':
                return (
                    <Badge className="bg-sky-500/10 text-sky-700 dark:text-sky-400 border border-sky-200 text-[10px]">
                        Review Berkas
                    </Badge>
                );
            case 'INTERVIEW':
                return (
                    <Badge className="bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 text-[10px]">
                        <Clock className="w-3 h-3 mr-1" /> Wawancara
                    </Badge>
                );
            case 'ACCEPTED':
                return (
                    <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 text-[10px] font-semibold">
                        <CheckCircle2 className="w-3 h-3 mr-1" /> Diterima (Join)
                    </Badge>
                );
            case 'REJECTED':
                return (
                    <Badge variant="outline" className="border-rose-300 text-rose-600 bg-rose-50/50 text-[10px]">
                        <XCircle className="w-3 h-3 mr-1" /> Ditolak
                    </Badge>
                );
            default:
                return <Badge variant="outline" className="text-[10px]">{status}</Badge>;
        }
    };

    const getWaLink = (phone) => {
        if (!phone) return null;
        let clean = phone.replace(/[^0-9]/g, '');
        if (clean.startsWith('0')) clean = '62' + clean.slice(1);
        return `https://wa.me/${clean}`;
    };

    const columnsPipeline = useMemo(
        () => [
            {
                accessorKey: 'apply_date',
                size: 120,
                minSize: 110,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Tgl Apply" />,
                cell: ({ row }) => (
                    <span className="text-xs font-mono text-zinc-500 whitespace-nowrap">
                        {row.original.apply_date || row.original.created_at?.slice(0, 10) || '-'}
                    </span>
                ),
                meta: { title: 'Tgl Apply' },
            },
            {
                accessorKey: 'name',
                size: 220,
                minSize: 200,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Nama Pelamar" />,
                cell: ({ row }) => {
                    const app = row.original;
                    return (
                        <div className="min-w-[160px]">
                            <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                {app.name}
                            </div>
                            <div className="text-[10px] text-zinc-400 font-mono">
                                {app.applicant_code} • {app.job_posting?.title || 'Umum'}
                            </div>
                        </div>
                    );
                },
                meta: { title: 'Nama Pelamar' },
            },
            {
                accessorKey: 'phone_number',
                size: 160,
                minSize: 150,
                header: ({ column }) => <DataTableColumnHeader column={column} title="No HP / WA" />,
                cell: ({ row }) => {
                    const app = row.original;
                    return (
                        <div className="flex items-center gap-1.5 whitespace-nowrap">
                            <span className="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                {app.phone_number || '-'}
                            </span>
                            {app.phone_number && (
                                <a
                                    href={getWaLink(app.phone_number)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="p-1 rounded bg-emerald-50 text-emerald-600 hover:bg-emerald-100"
                                    title="Chat WhatsApp langsung"
                                >
                                    <MessageCircle className="w-3 h-3" />
                                </a>
                            )}
                        </div>
                    );
                },
                meta: { title: 'No HP / WA' },
            },
            {
                accessorKey: 'status',
                size: 140,
                minSize: 130,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Status Talent" className="justify-center" />,
                cell: ({ row }) => {
                    const app = row.original;
                    return (
                        <div className="text-center">
                            <button
                                onClick={() => openStatusModal(app)}
                                className="hover:opacity-80 transition"
                                title="Klik untuk ubah status"
                            >
                                {getStatusBadge(app.status)}
                            </button>
                        </div>
                    );
                },
                meta: { title: 'Status Talent' },
            },
            {
                accessorKey: 'invitation_status',
                size: 130,
                minSize: 120,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Status Undangan" className="justify-center" />,
                cell: ({ row }) => (
                    <div className="text-center">
                        <Badge variant="outline" className="text-[10px]">
                            {row.original.invitation_status || 'Belum Diundang'}
                        </Badge>
                    </div>
                ),
                meta: { title: 'Status Undangan' },
            },
            {
                accessorKey: 'interview_result',
                size: 150,
                minSize: 140,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Hasil Interview" className="justify-center" />,
                cell: ({ row }) => {
                    const res = row.original.interview_result;
                    if (!res) return <div className="text-center text-zinc-400 text-xs">-</div>;
                    return (
                        <div className="text-center">
                            <Badge
                                className={`text-[10px] ${
                                    res === 'Disarankan Diterima'
                                        ? 'bg-emerald-500/10 text-emerald-700 border-emerald-200'
                                        : res === 'Dipertimbangkan'
                                        ? 'bg-amber-500/10 text-amber-700 border-amber-200'
                                        : 'bg-rose-500/10 text-rose-700 border-rose-200'
                                }`}
                            >
                                {res}
                            </Badge>
                        </div>
                    );
                },
                meta: { title: 'Hasil Interview' },
            },
            {
                accessorKey: 'onboarding_attendance',
                size: 130,
                minSize: 120,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Kehadiran Kerja" className="justify-center" />,
                cell: ({ row }) => {
                    const att = row.original.onboarding_attendance;
                    return (
                        <div className="text-center">
                            {att === 'Hadir' ? (
                                <Badge className="bg-emerald-500/10 text-emerald-700 text-[10px]">Hadir</Badge>
                            ) : att === 'Tidak Hadir' ? (
                                <Badge className="bg-rose-500/10 text-rose-700 text-[10px]">Tidak Hadir</Badge>
                            ) : (
                                <span className="text-zinc-400 text-xs">-</span>
                            )}
                        </div>
                    );
                },
                meta: { title: 'Kehadiran Kerja' },
            },
            {
                accessorKey: 'is_blacklisted',
                size: 110,
                minSize: 100,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Blacklist" className="justify-center" />,
                cell: ({ row }) => (
                    <div className="text-center">
                        {row.original.is_blacklisted ? (
                            <Badge className="bg-rose-600 text-white font-bold text-[10px] gap-1 shadow-xs">
                                <ShieldAlert className="w-3 h-3" />
                                Blacklist
                            </Badge>
                        ) : (
                            <span className="text-zinc-400 text-xs">Tidak</span>
                        )}
                    </div>
                ),
                meta: { title: 'Blacklist' },
            },
            {
                accessorKey: 'hcm_notes',
                size: 200,
                minSize: 180,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Catatan HCM" />,
                cell: ({ row }) => (
                    <div className="text-[11px] text-zinc-600 dark:text-zinc-400 line-clamp-2 max-w-[200px]">
                        {row.original.hcm_notes || row.original.rejection_reason || '-'}
                    </div>
                ),
                meta: { title: 'Catatan HCM' },
            },
            {
                id: 'actions',
                size: 100,
                minSize: 90,
                pinned: 'right',
                header: () => <div className="text-center text-xs font-semibold">Aksi</div>,
                cell: ({ row }) => {
                    const app = row.original;
                    return (
                        <div className="flex items-center justify-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-7 w-7 text-zinc-500 hover:text-indigo-600"
                                onClick={() => openDetailModal(app)}
                                title="Detail Berkas & Wawancara"
                            >
                                <Eye className="w-3.5 h-3.5" />
                            </Button>
                            {app.converted_employee ? (
                                <Link
                                    href={route(
                                        'hcm.employees.show',
                                        app.converted_employee.employee_code || app.converted_employee.id
                                    )}
                                    className="p-1 rounded text-purple-600 hover:bg-purple-50"
                                    title={`Lihat Karyawan: ${app.converted_employee.employee_code}`}
                                >
                                    <CheckCircle2 className="w-4 h-4" />
                                </Link>
                            ) : (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-7 w-7 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50"
                                    onClick={() => openConvertModal(app)}
                                    title="1-Klik Konversi Karyawan"
                                >
                                    <Rocket className="w-3.5 h-3.5" />
                                </Button>
                            )}
                        </div>
                    );
                },
                enableSorting: false,
                meta: { title: 'Aksi' },
            },
        ],
        []
    );

    const columnsInterviews = useMemo(
        () => [
            {
                accessorKey: 'apply_date',
                size: 120,
                minSize: 110,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Tgl Apply" />,
                cell: ({ row }) => (
                    <span className="text-xs font-mono text-zinc-500 whitespace-nowrap">
                        {row.original.apply_date || row.original.created_at?.slice(0, 10) || '-'}
                    </span>
                ),
                meta: { title: 'Tgl Apply' },
            },
            {
                accessorKey: 'name',
                size: 200,
                minSize: 180,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Nama Pelamar" />,
                cell: ({ row }) => {
                    const app = row.original;
                    return (
                        <div className="min-w-[150px]">
                            <div className="font-semibold text-xs text-zinc-900 dark:text-zinc-100">
                                {app.name}
                            </div>
                            <div className="text-[10px] text-zinc-400 font-mono">
                                {app.applicant_code}
                            </div>
                        </div>
                    );
                },
                meta: { title: 'Nama Pelamar' },
            },
            {
                id: 'age',
                size: 90,
                minSize: 80,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Umur" className="justify-center" />,
                cell: ({ row }) => {
                    const app = row.original;
                    const latestIv = app.interviews?.[0] || {};
                    const age =
                        latestIv.age ||
                        (app.birth_date
                            ? Math.floor((new Date() - new Date(app.birth_date)) / 31557600000)
                            : '-');
                    return <div className="text-center text-xs font-mono">{age} Thn</div>;
                },
                meta: { title: 'Umur' },
            },
            {
                id: 'marital_status',
                size: 120,
                minSize: 110,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Status Nikah" />,
                cell: ({ row }) => {
                    const app = row.original;
                    const latestIv = app.interviews?.[0] || {};
                    return (
                        <div className="text-xs text-zinc-600 whitespace-nowrap">
                            {latestIv.marital_status || app.marital_status || 'Belum Menikah'}
                        </div>
                    );
                },
                meta: { title: 'Status Nikah' },
            },
            {
                id: 'education',
                size: 120,
                minSize: 110,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Pendidikan" />,
                cell: ({ row }) => {
                    const app = row.original;
                    const latestIv = app.interviews?.[0] || {};
                    return (
                        <div className="text-xs text-zinc-700 whitespace-nowrap">
                            {latestIv.education || app.education || '-'}
                        </div>
                    );
                },
                meta: { title: 'Pendidikan' },
            },
            {
                id: 'last_experience',
                size: 160,
                minSize: 150,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Pengalaman Terakhir" />,
                cell: ({ row }) => {
                    const app = row.original;
                    const latestIv = app.interviews?.[0] || {};
                    return (
                        <div className="text-xs text-zinc-600 max-w-[150px] truncate" title={latestIv.last_experience || app.experience_summary}>
                            {latestIv.last_experience || app.experience_summary || 'Fresh Graduate'}
                        </div>
                    );
                },
                meta: { title: 'Pengalaman Terakhir' },
            },
            {
                id: 'daily_activity',
                size: 150,
                minSize: 140,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Kegiatan Saat Ini" />,
                cell: ({ row }) => {
                    const app = row.original;
                    const latestIv = app.interviews?.[0] || {};
                    return (
                        <div className="text-xs text-zinc-600 max-w-[140px] truncate" title={latestIv.daily_activity}>
                            {latestIv.daily_activity || 'Mencari Kerja'}
                        </div>
                    );
                },
                meta: { title: 'Kegiatan Saat Ini' },
            },
            {
                id: 'core_skills',
                size: 150,
                minSize: 140,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Skill Utama" />,
                cell: ({ row }) => {
                    const app = row.original;
                    const latestIv = app.interviews?.[0] || {};
                    return (
                        <div className="text-xs text-zinc-700 max-w-[140px] truncate" title={latestIv.core_skills || app.skills}>
                            {latestIv.core_skills || app.skills || '-'}
                        </div>
                    );
                },
                meta: { title: 'Skill Utama' },
            },
            {
                accessorKey: 'expected_salary',
                size: 140,
                minSize: 130,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Ekspektasi Gaji" />,
                cell: ({ row }) => {
                    const val = row.original.expected_salary;
                    return (
                        <div className="text-xs font-semibold text-emerald-600 whitespace-nowrap">
                            {val ? `Rp ${Number(val).toLocaleString('id-ID')}` : '-'}
                        </div>
                    );
                },
                meta: { title: 'Ekspektasi Gaji' },
            },
            {
                id: 'offering_status',
                size: 150,
                minSize: 140,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Offering Response" className="justify-center" />,
                cell: ({ row }) => {
                    const latestIv = row.original.interviews?.[0] || {};
                    const st = latestIv.offering_status;
                    if (!st) return <div className="text-center text-zinc-400 text-xs">Pending</div>;
                    return (
                        <div className="text-center">
                            <Badge
                                className={`text-[10px] ${
                                    st === 'Diterima (Join)'
                                        ? 'bg-emerald-500/10 text-emerald-700 border-emerald-200'
                                        : st === 'Dipertimbangkan Kembali'
                                        ? 'bg-amber-500/10 text-amber-700 border-amber-200'
                                        : 'bg-rose-500/10 text-rose-700 border-rose-200'
                                }`}
                            >
                                {st}
                            </Badge>
                        </div>
                    );
                },
                meta: { title: 'Offering Response' },
            },
            {
                id: 'interview_decision',
                size: 130,
                minSize: 120,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Keputusan" className="justify-center" />,
                cell: ({ row }) => {
                    const app = row.original;
                    const latestIv = app.interviews?.[0] || {};
                    return (
                        <div className="text-center">
                            <Badge variant="outline" className="text-[10px]">
                                {latestIv.interview_decision || app.interview_result || 'Pending'}
                            </Badge>
                        </div>
                    );
                },
                meta: { title: 'Keputusan' },
            },
            {
                id: 'offering_notes',
                size: 180,
                minSize: 160,
                header: ({ column }) => <DataTableColumnHeader column={column} title="Catatan Kandidat" />,
                cell: ({ row }) => {
                    const app = row.original;
                    const latestIv = app.interviews?.[0] || {};
                    return (
                        <div className="text-[11px] text-zinc-600 line-clamp-2 max-w-[180px]">
                            {latestIv.offering_notes || app.hcm_notes || '-'}
                        </div>
                    );
                },
                meta: { title: 'Catatan Kandidat' },
            },
            {
                id: 'actions',
                size: 100,
                minSize: 90,
                pinned: 'right',
                header: () => <div className="text-center text-xs font-semibold">Aksi</div>,
                cell: ({ row }) => {
                    const app = row.original;
                    return (
                        <div className="flex items-center justify-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-7 w-7 text-zinc-500 hover:text-indigo-600"
                                onClick={() => openDetailModal(app)}
                                title="Detail Berkas & Wawancara"
                            >
                                <Eye className="w-3.5 h-3.5" />
                            </Button>
                            {app.converted_employee ? (
                                <Link
                                    href={route(
                                        'hcm.employees.show',
                                        app.converted_employee.employee_code || app.converted_employee.id
                                    )}
                                    className="p-1 rounded text-purple-600 hover:bg-purple-50"
                                    title={`Lihat Karyawan: ${app.converted_employee.employee_code}`}
                                >
                                    <CheckCircle2 className="w-4 h-4" />
                                </Link>
                            ) : (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-7 w-7 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50"
                                    onClick={() => openConvertModal(app)}
                                    title="1-Klik Konversi Karyawan"
                                >
                                    <Rocket className="w-3.5 h-3.5" />
                                </Button>
                            )}
                        </div>
                    );
                },
                enableSorting: false,
                meta: { title: 'Aksi' },
            },
        ],
        []
    );

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Users className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Pipeline Pelamar & Wawancara - HCM
                    </span>
                </div>
            }
        >
            <Head title="Pipeline Pelamar & Rekrutmen - HCM" />

            <div className="space-y-4">
                {/* Header Banner Canvas */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <Users className="h-5 w-5 text-indigo-600 shrink-0" />
                            <span>Pipeline Seleksi Pelamar & Talent Acquisition</span>
                        </h1>
                        <p className="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Pemeriksaan berkas, rekap wawancara, pemantauan kehadiran kerja, dan 1-Klik konversi pelamar menjadi karyawan baru.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <Link
                            href={route('hcm.recruitment.jobs.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 shadow-xs transition"
                        >
                            <Briefcase className="w-3.5 h-3.5 text-indigo-600" />
                            Master Loker & Link Publik
                        </Link>
                    </div>
                </div>

                {/* 1. Baris Metrik Ringkasan (Blueprint HRIS) */}
                <div className="grid grid-cols-2 sm:grid-cols-6 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs p-3">
                        <span className="text-[11px] text-zinc-500 font-medium">Total Pelamar</span>
                        <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">
                            {metrics.total_applicants ?? 0}
                        </div>
                    </Card>

                    <Card className="border border-blue-200/80 dark:border-blue-900/40 bg-blue-50/20 dark:bg-blue-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-blue-600 font-medium">Berkas Masuk</span>
                        <div className="text-xl font-bold text-blue-700 dark:text-blue-300 font-mono mt-0.5">
                            {metrics.submitted ?? 0}
                        </div>
                    </Card>

                    <Card className="border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/20 dark:bg-amber-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-amber-600 font-medium">Tahap Wawancara</span>
                        <div className="text-xl font-bold text-amber-700 dark:text-amber-300 font-mono mt-0.5">
                            {metrics.interview ?? 0}
                        </div>
                    </Card>

                    <Card className="border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/20 dark:bg-emerald-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-emerald-600 font-medium">Diterima (Join)</span>
                        <div className="text-xl font-bold text-emerald-700 dark:text-emerald-300 font-mono mt-0.5">
                            {metrics.accepted ?? 0}
                        </div>
                    </Card>

                    <Card className="border border-purple-200/80 dark:border-purple-900/40 bg-purple-50/20 dark:bg-purple-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-purple-600 font-medium">Dikonversi Karyawan</span>
                        <div className="text-xl font-bold text-purple-700 dark:text-purple-300 font-mono mt-0.5">
                            {metrics.converted ?? 0} Org
                        </div>
                    </Card>

                    <Card className="border border-rose-200/80 dark:border-rose-900/40 bg-rose-50/20 dark:bg-rose-950/20 shadow-xs p-3">
                        <span className="text-[11px] text-rose-600 font-medium">Blacklist (Mangkir)</span>
                        <div className="text-xl font-bold text-rose-700 dark:text-rose-300 font-mono mt-0.5">
                            {metrics.blacklisted ?? 0}
                        </div>
                    </Card>
                </div>

                {/* 2. Tab Switcher */}
                <div className="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-1">
                    <button
                        onClick={() => setActiveTab('pipeline')}
                        className={`pb-2 px-3 text-xs font-semibold border-b-2 transition flex items-center gap-1.5 ${
                            activeTab === 'pipeline'
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100'
                        }`}
                    >
                        <Users className="w-3.5 h-3.5" />
                        Tabel 1: Pipeline Pelamar (Talent Pool)
                    </button>
                    <button
                        onClick={() => setActiveTab('interviews')}
                        className={`pb-2 px-3 text-xs font-semibold border-b-2 transition flex items-center gap-1.5 ${
                            activeTab === 'interviews'
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100'
                        }`}
                    >
                        <FileText className="w-3.5 h-3.5" />
                        Tabel 2: Rekap Hasil Wawancara & Evaluasi
                    </button>
                </div>

                {/* 3. DataTable Pelamar */}
                <DataTable
                    columns={activeTab === 'pipeline' ? columnsPipeline : columnsInterviews}
                    data={applicants.data || []}
                    serverPagination={applicants}
                    onServerPageChange={(page) => handleFilterChange(search, statusFilter, jobFilter, blacklistFilter, page)}
                    onServerPerPageChange={(per_page) => handleFilterChange(search, statusFilter, jobFilter, blacklistFilter, 1, per_page)}
                    onServerSearchChange={(val) => {
                        setSearch(val);
                        handleFilterChange(val, statusFilter, jobFilter, blacklistFilter, 1);
                    }}
                    searchValue={search}
                    searchPlaceholder="Cari nama, kode registrasi, no. WhatsApp..."
                    exportFileName={`Pelamar_${activeTab === 'pipeline' ? 'Pipeline' : 'Wawancara'}_${new Date().toISOString().slice(0, 10)}`}
                    filterSlot={
                        <div className="flex flex-wrap items-center gap-2">
                            <div className="min-w-[190px] w-auto">
                                <SearchableSelect
                                    value={jobFilter}
                                    onValueChange={(val) => {
                                        setJobFilter(val);
                                        handleFilterChange(search, statusFilter, val, blacklistFilter, 1);
                                    }}
                                    options={[
                                        { label: 'Semua Lowongan Kerja', value: 'all' },
                                        ...jobOptions.map((j) => ({
                                            label: `${j.title} (${j.department})`,
                                            value: j.slug,
                                        })),
                                    ]}
                                    placeholder="Pilih Loker"
                                    className="text-xs h-8"
                                />
                            </div>

                            <div className="min-w-[180px] w-auto">
                                <SearchableSelect
                                    value={statusFilter}
                                    onValueChange={(val) => {
                                        setStatusFilter(val);
                                        handleFilterChange(search, val, jobFilter, blacklistFilter, 1);
                                    }}
                                    options={[
                                        { label: 'Semua Status Tahapan', value: 'all' },
                                        { label: 'Berkas Masuk (Submitted)', value: 'SUBMITTED' },
                                        { label: 'Review Berkas (Screening)', value: 'SCREENING' },
                                        { label: 'Wawancara (Interview)', value: 'INTERVIEW' },
                                        { label: 'Diterima (Accepted)', value: 'ACCEPTED' },
                                        { label: 'Ditolak (Rejected)', value: 'REJECTED' },
                                    ]}
                                    placeholder="Tahapan Seleksi"
                                    className="text-xs h-8"
                                />
                            </div>

                            <div className="min-w-[150px] w-auto">
                                <SearchableSelect
                                    value={blacklistFilter}
                                    onValueChange={(val) => {
                                        setBlacklistFilter(val);
                                        handleFilterChange(search, statusFilter, jobFilter, val, 1);
                                    }}
                                    options={[
                                        { label: 'Semua Talent', value: 'all' },
                                        { label: 'Bukan Blacklist', value: 'no' },
                                        { label: '⚠️ Masuk Blacklist', value: 'yes' },
                                    ]}
                                    placeholder="Filter Blacklist"
                                    className="text-xs h-8"
                                />
                            </div>

                            {isFiltered && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleResetFilters}
                                    className="h-8 text-xs text-zinc-500 hover:text-zinc-900 gap-1 px-2"
                                >
                                    <RotateCcw className="h-3.5 w-3.5" />
                                    Reset
                                </Button>
                            )}
                        </div>
                    }
                />
            </div>

            {/* MODAL 1: Detail Berkas & Rekap Wawancara Pelamar */}
            <Dialog open={isDetailModalOpen} onOpenChange={setIsDetailModalOpen}>
                <DialogContent className="sm:max-w-xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-indigo-600">
                            <FileText className="w-5 h-5" />
                            Dossier Berkas & Wawancara Pelamar
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            No. Registrasi: <strong className="font-mono">{selectedApplicant?.applicant_code}</strong>
                        </DialogDescription>
                    </DialogHeader>

                    {selectedApplicant && (
                        <div className="space-y-4 pt-1 text-xs">
                            {/* Profile Header */}
                            <div className="flex items-center gap-3 p-3 rounded-xl border border-zinc-200 bg-zinc-50/50">
                                {selectedApplicant.photo_url ? (
                                    <img
                                        src={selectedApplicant.photo_url}
                                        alt={selectedApplicant.name}
                                        className="w-14 h-16 rounded-lg object-cover border border-zinc-300 shadow-xs shrink-0"
                                    />
                                ) : (
                                    <div className="w-14 h-16 rounded-lg bg-zinc-200 text-zinc-500 flex flex-col items-center justify-center text-[10px] font-medium shrink-0">
                                        No Foto
                                    </div>
                                )}
                                <div className="space-y-0.5 min-w-0">
                                    <div className="font-bold text-sm text-zinc-900 truncate">
                                        {selectedApplicant.name}
                                    </div>
                                    <div className="text-[11px] text-zinc-500 font-mono">
                                        {selectedApplicant.applicant_code} • {selectedApplicant.gender}
                                    </div>
                                    <div className="text-[11px] font-semibold text-emerald-600">
                                        Ekspektasi Gaji:{' '}
                                        {selectedApplicant.expected_salary
                                            ? `Rp ${Number(selectedApplicant.expected_salary).toLocaleString('id-ID')} / bln`
                                            : 'Tidak dicantumkan'}
                                    </div>
                                </div>
                            </div>

                            {/* Details Grid */}
                            <div className="p-3 rounded-lg border border-zinc-200 bg-zinc-50/30 grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <div className="text-[10px] text-zinc-400">Posisi Dilamar</div>
                                    <div className="font-bold text-indigo-600">{selectedApplicant.job_posting?.title || 'Umum'}</div>
                                </div>
                                <div>
                                    <div className="text-[10px] text-zinc-400">No. WhatsApp / HP</div>
                                    <div className="font-mono font-medium">{selectedApplicant.phone_number}</div>
                                </div>
                                <div>
                                    <div className="text-[10px] text-zinc-400">Email</div>
                                    <div>{selectedApplicant.email || '-'}</div>
                                </div>
                                <div>
                                    <div className="text-[10px] text-zinc-400">Pendidikan</div>
                                    <div>{selectedApplicant.education} {selectedApplicant.major ? `(${selectedApplicant.major})` : ''}</div>
                                </div>
                                <div className="col-span-2">
                                    <div className="text-[10px] text-zinc-400">Alamat Tempat Tinggal</div>
                                    <div>{selectedApplicant.address || '-'}</div>
                                </div>
                            </div>

                            {/* Attachments */}
                            <div className="space-y-1.5">
                                <div className="font-semibold text-zinc-700">Berkas Dokumen:</div>
                                <div className="flex flex-wrap gap-2">
                                    {selectedApplicant.resume_file_url ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setPreviewDoc({
                                                    url: selectedApplicant.resume_file_url,
                                                    name: `CV / Resume - ${selectedApplicant.name}`,
                                                })
                                            }
                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 font-semibold hover:bg-indigo-100 text-xs transition"
                                        >
                                            <Eye className="w-3.5 h-3.5" />
                                            Lihat CV / Resume (In-App)
                                        </button>
                                    ) : (
                                        <span className="text-zinc-400 italic text-xs">CV tidak dilampirkan</span>
                                    )}

                                    {selectedApplicant.ktp_file_url && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setPreviewDoc({
                                                    url: selectedApplicant.ktp_file_url,
                                                    name: `KTP - ${selectedApplicant.name}`,
                                                })
                                            }
                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-zinc-200 bg-zinc-50 text-zinc-700 font-semibold hover:bg-zinc-100 text-xs transition"
                                        >
                                            <Eye className="w-3.5 h-3.5" />
                                            Lihat KTP
                                        </button>
                                    )}
                                </div>
                            </div>

                            {/* Sesi Wawancara */}
                            <div className="space-y-2 pt-2 border-t border-zinc-200">
                                <div className="font-semibold text-zinc-800">Catatan & Sesi Wawancara:</div>
                                <div className="space-y-2">
                                    {(selectedApplicant.interviews || []).length > 0 ? (
                                        selectedApplicant.interviews.map((iv) => (
                                            <div key={iv.id} className="rounded-lg border border-zinc-200 p-2.5 text-xs bg-zinc-50/50">
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="font-semibold text-zinc-800">
                                                        {iv.interview_round || 'Wawancara'}
                                                        {iv.interviewer_name ? ` • ${iv.interviewer_name}` : ''}
                                                    </span>
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-[10px] text-zinc-400">
                                                            {(iv.interview_date || '').slice(0, 10) || '-'}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => openEditInterview(iv)}
                                                            className="text-indigo-600 hover:text-indigo-800"
                                                        >
                                                            <Edit2 className="w-3.5 h-3.5" />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDeleteInterview(iv)}
                                                            className="text-rose-600 hover:text-rose-800"
                                                        >
                                                            <Trash2 className="w-3.5 h-3.5" />
                                                        </button>
                                                    </div>
                                                </div>
                                                <div className="mt-1 flex flex-wrap gap-1.5">
                                                    {iv.interview_result && (
                                                        <Badge className="text-[10px] bg-sky-500/10 text-sky-700">
                                                            {iv.interview_result}
                                                        </Badge>
                                                    )}
                                                    {iv.offering_status && (
                                                        <Badge className="text-[10px] bg-emerald-500/10 text-emerald-700">
                                                            {iv.offering_status}
                                                        </Badge>
                                                    )}
                                                </div>
                                                {iv.offering_notes && (
                                                    <p className="mt-1 text-zinc-600 text-[11px]">{iv.offering_notes}</p>
                                                )}
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-zinc-400 italic text-xs">Belum ada sesi wawancara yang dicatat.</p>
                                    )}
                                </div>

                                {/* Form Tambah / Edit Wawancara */}
                                <form
                                    onSubmit={handleInterviewSubmit}
                                    className={`rounded-lg border p-3 space-y-2 ${
                                        editingInterview ? 'border-indigo-300 bg-indigo-50/30' : 'border-dashed border-zinc-200'
                                    }`}
                                >
                                    <div className="text-xs font-semibold text-zinc-700">
                                        {editingInterview ? 'Edit Sesi Wawancara' : '+ Catat Sesi Wawancara Baru'}
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <Input
                                            value={interviewForm.data.interview_round}
                                            onChange={(e) => interviewForm.setData('interview_round', e.target.value)}
                                            placeholder="Tahap (HRD / User / Praktik Jahit)"
                                            className="h-8 text-xs"
                                        />
                                        <Input
                                            value={interviewForm.data.interviewer_name}
                                            onChange={(e) => interviewForm.setData('interviewer_name', e.target.value)}
                                            placeholder="Nama Pewawancara"
                                            className="h-8 text-xs"
                                        />
                                        <Input
                                            type="date"
                                            value={interviewForm.data.interview_date}
                                            onChange={(e) => interviewForm.setData('interview_date', e.target.value)}
                                            className="h-8 text-xs"
                                        />
                                        <SearchableSelect
                                            value={interviewForm.data.interview_result}
                                            onValueChange={(v) => interviewForm.setData('interview_result', v)}
                                            options={[
                                                { label: 'Disarankan Diterima', value: 'Disarankan Diterima' },
                                                { label: 'Dipertimbangkan', value: 'Dipertimbangkan' },
                                                { label: 'Ditolak', value: 'Ditolak' },
                                            ]}
                                            placeholder="Hasil Wawancara"
                                            className="text-xs"
                                        />
                                        <SearchableSelect
                                            value={interviewForm.data.offering_status}
                                            onValueChange={(v) => interviewForm.setData('offering_status', v)}
                                            options={[
                                                { label: 'Diterima (Join)', value: 'Diterima (Join)' },
                                                { label: 'Dipertimbangkan Kembali', value: 'Dipertimbangkan Kembali' },
                                                { label: 'Ditolak Pelamar', value: 'Ditolak Pelamar' },
                                                { label: 'Pending', value: 'Pending' },
                                            ]}
                                            placeholder="Status Offering"
                                            className="text-xs"
                                        />
                                        <Input
                                            type="number"
                                            min="0"
                                            value={interviewForm.data.salary_expectation}
                                            onChange={(e) => interviewForm.setData('salary_expectation', e.target.value)}
                                            placeholder="Ekspektasi Gaji (Rp)"
                                            className="h-8 text-xs font-mono"
                                        />
                                    </div>
                                    <Textarea
                                        value={interviewForm.data.offering_notes}
                                        onChange={(e) => interviewForm.setData('offering_notes', e.target.value)}
                                        placeholder="Catatan hasil wawancara & offering..."
                                        rows={2}
                                        className="text-xs"
                                    />
                                    <div className="flex justify-end gap-2 pt-1">
                                        {editingInterview && (
                                            <Button type="button" variant="outline" size="sm" onClick={resetInterviewForm} className="text-xs">
                                                Batal
                                            </Button>
                                        )}
                                        <Button
                                            type="submit"
                                            size="sm"
                                            disabled={interviewForm.processing}
                                            className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                                        >
                                            {editingInterview ? 'Perbarui Wawancara' : 'Simpan Wawancara'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="pt-2 gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                window.open(
                                    route('hcm.recruitment.applicants.pdf', selectedApplicant?.applicant_code || selectedApplicant?.id) +
                                        '?action=stream',
                                    '_blank'
                                )
                            }
                            className="text-xs gap-1.5"
                        >
                            <Printer className="w-3.5 h-3.5" /> Cetak Profil
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => {
                                window.location.href = route(
                                    'hcm.recruitment.applicants.excel',
                                    selectedApplicant?.applicant_code || selectedApplicant?.id
                                );
                            }}
                            className="text-xs gap-1.5 text-emerald-700 hover:text-emerald-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/30"
                        >
                            <FileSpreadsheet className="w-3.5 h-3.5" /> Unduh Excel
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => setIsDetailModalOpen(false)} className="text-xs">
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* MODAL 2: Ubah Status & Blacklist */}
            <Dialog open={isStatusModalOpen} onOpenChange={setIsStatusModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-indigo-600">
                            <Clock className="w-5 h-5" />
                            Tahapan Seleksi & Evaluasi Pelamar
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Pelamar: <strong>{selectedApplicant?.name}</strong> ({selectedApplicant?.applicant_code})
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleStatusSubmit} className="space-y-3 pt-1">
                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Tahapan Seleksi *</Label>
                            <SearchableSelect
                                value={statusForm.data.status}
                                onValueChange={(val) => statusForm.setData('status', val)}
                                options={[
                                    { label: 'Berkas Masuk (Submitted)', value: 'SUBMITTED' },
                                    { label: 'Review Berkas (Screening)', value: 'SCREENING' },
                                    { label: 'Panggilan Wawancara (Interview)', value: 'INTERVIEW' },
                                    { label: 'Diterima Bekerja (Accepted)', value: 'ACCEPTED' },
                                    { label: 'Ditolak / Belum Sesuai (Rejected)', value: 'REJECTED' },
                                ]}
                                placeholder="Pilih status"
                                className="text-xs"
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Status Undangan</Label>
                                <SearchableSelect
                                    value={statusForm.data.invitation_status}
                                    onValueChange={(val) => statusForm.setData('invitation_status', val)}
                                    options={[
                                        { label: 'Belum Diundang', value: 'Belum Diundang' },
                                        { label: 'Diundang', value: 'Diundang' },
                                        { label: 'Hadir Interview', value: 'Hadir Interview' },
                                        { label: 'Tidak Diundang', value: 'Tidak Diundang' },
                                    ]}
                                    placeholder="Pilih status undangan"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Hasil Interview</Label>
                                <SearchableSelect
                                    value={statusForm.data.interview_result}
                                    onValueChange={(val) => statusForm.setData('interview_result', val)}
                                    options={[
                                        { label: '- (Belum ada)', value: '' },
                                        { label: 'Disarankan Diterima', value: 'Disarankan Diterima' },
                                        { label: 'Dipertimbangkan', value: 'Dipertimbangkan' },
                                        { label: 'Ditolak', value: 'Ditolak' },
                                    ]}
                                    placeholder="Pilih hasil interview"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Status Kehadiran Kerja</Label>
                                <SearchableSelect
                                    value={statusForm.data.onboarding_attendance}
                                    onValueChange={(val) => statusForm.setData('onboarding_attendance', val)}
                                    options={[
                                        { label: '- (Belum ada)', value: '' },
                                        { label: 'Hadir', value: 'Hadir' },
                                        { label: 'Tidak Hadir', value: 'Tidak Hadir' },
                                    ]}
                                    placeholder="Pilih kehadiran"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1 flex flex-col justify-end">
                                <Label className="text-xs font-semibold">Status Blacklist</Label>
                                <button
                                    type="button"
                                    onClick={() => statusForm.setData('is_blacklisted', !statusForm.data.is_blacklisted)}
                                    className={`h-8 rounded-md border px-3 text-xs text-left transition font-semibold ${
                                        statusForm.data.is_blacklisted
                                            ? 'border-rose-300 bg-rose-50 text-rose-700'
                                            : 'border-zinc-200 bg-white text-zinc-600'
                                    }`}
                                >
                                    {statusForm.data.is_blacklisted ? '⚠️ Masuk Blacklist' : 'Bukan Blacklist'}
                                </button>
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Catatan Rekam Jejak HCM / Alasan</Label>
                            <Textarea
                                value={statusForm.data.hcm_notes}
                                onChange={(e) => statusForm.setData('hcm_notes', e.target.value)}
                                placeholder="Contoh: Dipanggil kerja/onboarding tapi tidak hadir..."
                                rows={2}
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsStatusModalOpen(false)}
                                disabled={statusForm.processing}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={statusForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs"
                            >
                                {statusForm.processing ? 'Menyimpan...' : 'Simpan Status'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* MODAL 3: 1-Klik Konversi Pelamar ke Karyawan Baru */}
            <Dialog open={isConvertModalOpen} onOpenChange={setIsConvertModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-base font-bold text-indigo-600">
                            <Rocket className="w-5 h-5" />
                            1-Klik Konversi Jadi Karyawan Baru
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Pelamar: <strong>{selectedApplicant?.name}</strong> akan otomatis diterbitkan NIK & data master karyawan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleConvertSubmit} className="space-y-3 pt-1">
                        <div className="p-2.5 rounded-lg border border-indigo-200 bg-indigo-50/50 text-xs text-indigo-900 leading-relaxed">
                            💡 Data biodata, kontak, dan foto pelamar akan disalin ke Master Karyawan. Kuota loker otomatis bertambah 1.
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Status Ketenagakerjaan Awal *</Label>
                            <SearchableSelect
                                value={convertForm.data.employment_status}
                                onValueChange={(val) => convertForm.setData('employment_status', val)}
                                options={(dropdowns.employment_statuses || ['Probation', 'Kontrak (PKWT)', 'Tetap (PKWTT)', 'Magang']).map(
                                    (s) => ({ label: s, value: s })
                                )}
                                placeholder="Pilih status"
                                className="text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Jenjang Jabatan (Job Level) *</Label>
                        
                            <SearchableSelect
                                value={convertForm.data.job_level}
                                onValueChange={(val) => convertForm.setData('job_level', val)}
                                options={(dropdowns.job_levels || ['Staff', 'Operator', 'Trainee', 'Leader', 'Supervisor']).map((lvl) => ({
                                    label: lvl,
                                    value: lvl,
                                }))}
                                placeholder="Pilih jenjang"
                                className="text-xs"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Divisi / Departemen *</Label>
                                <SearchableSelect
                                    value={convertForm.data.department}
                                    onValueChange={(val) => convertForm.setData('department', val)}
                                    options={(dropdowns.departments || ['Produksi', 'Marketing', 'HCM']).map((d) => ({
                                        label: d,
                                        value: d,
                                    }))}
                                    placeholder="Departemen"
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-semibold">Posisi / Jabatan *</Label>
                                <SearchableSelect
                                    value={convertForm.data.position}
                                    onValueChange={(val) => convertForm.setData('position', val)}
                                    options={(dropdowns.positions || ['Operator', 'Staff', 'Jahit']).map((p) => ({
                                        label: p,
                                        value: p,
                                    }))}
                                    placeholder="Posisi"
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Tanggal Mulai Bekerja (Join Date) *</Label>
                            <Input
                                type="date"
                                value={convertForm.data.join_date}
                                onChange={(e) => convertForm.setData('join_date', e.target.value)}
                                className="h-8 text-xs font-mono"
                                required
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsConvertModalOpen(false)}
                                disabled={convertForm.processing}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={convertForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1.5 font-semibold"
                            >
                                <Rocket className="w-3.5 h-3.5" />
                                {convertForm.processing ? 'Mengonversi...' : 'Konfirmasi & Terbitkan Karyawan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Universal Document Viewer Modal */}
            <UniversalDocumentViewer
                isOpen={!!previewDoc}
                onClose={() => setPreviewDoc(null)}
                fileUrl={previewDoc?.url}
                fileName={previewDoc?.name || 'Dokumen Pelamar'}
            />
        </AppLayout>
    );
}
