import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
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
import { Label } from '@/Components/ui/label';
import {
    Wallet,
    Search,
    Eye,
    TrendingUp,
    Users,
    Banknote,
    History,
    PlusCircle,
    Edit3,
    ArrowUpRight,
    Trash2,
    CheckCircle2,
    Clock,
    AlertCircle,
    FileText,
    RotateCcw,
    DollarSign,
    Briefcase,
    Scissors,
} from 'lucide-react';

const rp = (v) => {
    if (v === null || v === undefined || v === '') return '-';
    return 'Rp ' + Number(v).toLocaleString('id-ID');
};

export default function CompensationIndex({
    compensations,
    filters = {},
    metrics = {},
    dropdowns = {},
    entities = [],
    statuses = [],
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [employmentStatus, setEmploymentStatus] = useState(filters.employment_status || 'all');
    const [entity, setEntity] = useState(filters.entity || 'all');

    // Modals
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [incrementModalOpen, setIncrementModalOpen] = useState(false);
    const [historyModalOpen, setHistoryModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [decisionModalOpen, setDecisionModalOpen] = useState(false);
    const [selectedCompensation, setSelectedCompensation] = useState(null);

    // Sync state if server filter changes
    useEffect(() => {
        setSearch(filters.search || '');
        setStatus(filters.status || 'all');
        setEmploymentStatus(filters.employment_status || 'all');
        setEntity(filters.entity || 'all');
    }, [filters]);

    // Form Tambah Kompensasi
    const createForm = useForm({
        employee_id: '',
        employment_status: 'PKWT',
        legal_entity: 'CV Jersey Ekonomis',
        contract_number: '',
        duration_text: '1 Tahun',
        trainee_duration_months: '',
        evaluation_cycle_months: 6,
        initial_salary: '',
        current_salary: '',
        salary_status: 'Telah berlaku',
        salary_increment_count: 0,
        increment_1_amount: '',
        increment_2_amount: '',
        increment_3_amount: '',
    });

    // Form Edit Kompensasi
    const editForm = useForm({
        employment_status: '',
        legal_entity: '',
        contract_number: '',
        duration_text: '',
        trainee_duration_months: '',
        evaluation_cycle_months: 6,
        initial_salary: '',
        current_salary: '',
        salary_status: '',
        salary_increment_count: 0,
        increment_1_amount: '',
        increment_2_amount: '',
        increment_3_amount: '',
    });

    // Form Kenaikan Gaji Berkala
    const incrementForm = useForm({
        new_salary: '',
        increment_amount: '',
        effective_date: new Date().toISOString().split('T')[0],
        reason: 'Kenaikan Gaji Berkala / Evaluasi Performa 6 Bulan',
    });

    // Form Keputusan Evaluasi Kenaikan Gaji (Siklus Milestone & ACC/Tunda)
    const decisionForm = useForm({
        decision_status: 'Sedang Diajukan',
        planned_increment: '',
        effective_date: '',
        custom_milestone_date: '',
        decision_notes: '',
        apply_immediately: false,
    });

    const handleOpenDecision = (c) => {
        setSelectedCompensation(c);
        decisionForm.setData({
            decision_status: c.decision_status || 'Sedang Diajukan',
            planned_increment: c.planned_increment ?? '',
            effective_date: c.effective_date ? String(c.effective_date).slice(0, 10) : new Date().toISOString().split('T')[0],
            custom_milestone_date: c.custom_milestone_date ? String(c.custom_milestone_date).slice(0, 10) : '',
            decision_notes: c.decision_notes || '',
            apply_immediately: false,
        });
        setDecisionModalOpen(true);
    };

    const submitDecision = (e) => {
        e.preventDefault();
        if (!selectedCompensation) return;
        const key = selectedCompensation.uuid || selectedCompensation.id;
        decisionForm.put(route('hcm.compensations.decision.update', key), {
            onSuccess: () => {
                setDecisionModalOpen(false);
                decisionForm.reset();
            },
        });
    };

    const applyFilter = (s = search, st = status, empSt = employmentStatus, e = entity) => {
        router.get(
            route('hcm.compensations.index'),
            {
                search: s,
                status: st,
                employment_status: empSt,
                entity: e,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        applyFilter(search, status, employmentStatus, entity);
    };

    const handleResetFilters = () => {
        setSearch('');
        setStatus('all');
        setEmploymentStatus('all');
        setEntity('all');
        applyFilter('', 'all', 'all', 'all');
    };

    // Auto-fill employee details when selected in Create Modal
    const handleSelectEmployee = (empId) => {
        createForm.setData('employee_id', empId);
        const emp = (dropdowns.employees || []).find((e) => String(e.id) === String(empId));
        if (emp) {
            if (emp.legal_entity) createForm.setData('legal_entity', emp.legal_entity);
            if (emp.employment_status) createForm.setData('employment_status', emp.employment_status);
        }
    };

    const handleOpenCreate = () => {
        createForm.reset();
        createForm.setData({
            employee_id: '',
            employment_status: 'PKWT',
            legal_entity: 'CV Jersey Ekonomis',
            contract_number: '',
            duration_text: '1 Tahun',
            trainee_duration_months: '',
            evaluation_cycle_months: 6,
            initial_salary: '',
            current_salary: '',
            salary_status: 'Telah berlaku',
            salary_increment_count: 0,
            increment_1_amount: '',
            increment_2_amount: '',
            increment_3_amount: '',
        });
        setCreateModalOpen(true);
    };

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('hcm.compensations.store'), {
            onSuccess: () => {
                setCreateModalOpen(false);
                createForm.reset();
            },
        });
    };

    const handleOpenEdit = (c) => {
        setSelectedCompensation(c);
        editForm.setData({
            employment_status: c.employment_status || c.employee?.employment_status || 'PKWT',
            legal_entity: c.legal_entity || c.employee?.legal_entity || 'CV Jersey Ekonomis',
            contract_number: c.contract_number || '',
            duration_text: c.duration_text || '1 Tahun',
            trainee_duration_months: c.trainee_duration_months ?? '',
            evaluation_cycle_months: c.evaluation_cycle_months ?? 6,
            initial_salary: c.initial_salary ?? '',
            current_salary: c.current_salary ?? '',
            salary_status: c.salary_status || 'Telah berlaku',
            salary_increment_count: c.salary_increment_count ?? 0,
            increment_1_amount: c.increment_1_amount ?? '',
            increment_2_amount: c.increment_2_amount ?? '',
            increment_3_amount: c.increment_3_amount ?? '',
        });
        setEditModalOpen(true);
    };

    const submitEdit = (e) => {
        e.preventDefault();
        if (!selectedCompensation) return;
        const key = selectedCompensation.uuid || selectedCompensation.id;
        editForm.put(route('hcm.compensations.update', key), {
            onSuccess: () => {
                setEditModalOpen(false);
                editForm.reset();
            },
        });
    };

    const handleOpenIncrement = (c) => {
        setSelectedCompensation(c);
        const curr = Number(c.current_salary || 0);
        const defaultInc = 200000;
        incrementForm.setData({
            new_salary: curr + defaultInc,
            increment_amount: defaultInc,
            effective_date: new Date().toISOString().split('T')[0],
            reason: 'Kenaikan Gaji Berkala / Evaluasi Performa 6 Bulan',
        });
        setIncrementModalOpen(true);
    };

    const handleIncrementSalaryChange = (newVal) => {
        const curr = Number(selectedCompensation?.current_salary || 0);
        const parsed = Number(newVal) || 0;
        incrementForm.setData({
            ...incrementForm.data,
            new_salary: newVal,
            increment_amount: parsed > curr ? parsed - curr : 0,
        });
    };

    const submitIncrement = (e) => {
        e.preventDefault();
        if (!selectedCompensation) return;
        const key = selectedCompensation.uuid || selectedCompensation.id;
        incrementForm.post(route('hcm.compensations.increment.store', key), {
            onSuccess: () => {
                setIncrementModalOpen(false);
                incrementForm.reset();
            },
        });
    };

    const handleOpenHistory = (c) => {
        setSelectedCompensation(c);
        setHistoryModalOpen(true);
    };

    const handleOpenDelete = (c) => {
        setSelectedCompensation(c);
        setDeleteModalOpen(true);
    };

    const submitDelete = () => {
        if (!selectedCompensation) return;
        const key = selectedCompensation.uuid || selectedCompensation.id;
        router.delete(route('hcm.compensations.destroy', key), {
            onSuccess: () => {
                setDeleteModalOpen(false);
            },
        });
    };

    const getStatusBadge = (statusVal) => {
        if (!statusVal) return <Badge variant="outline">-</Badge>;
        if (statusVal.includes('Telah berlaku') || statusVal.includes('Aktif')) {
            return (
                <Badge className="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 gap-1 inline-flex items-center">
                    <CheckCircle2 className="h-3 w-3 shrink-0" />
                    <span>Telah berlaku</span>
                </Badge>
            );
        }
        if (statusVal.includes('Sedang Diajukan') || statusVal.includes('Pending')) {
            return (
                <Badge className="bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-300 gap-1 inline-flex items-center">
                    <Clock className="h-3 w-3 shrink-0" />
                    <span>{statusVal}</span>
                </Badge>
            );
        }
        if (statusVal.includes('Draft')) {
            return (
                <Badge className="bg-zinc-500/10 text-zinc-600 dark:text-zinc-400 border-zinc-200 gap-1 inline-flex items-center">
                    <FileText className="h-3 w-3 shrink-0" />
                    <span>Draft</span>
                </Badge>
            );
        }
        return <Badge variant="outline">{statusVal}</Badge>;
    };

    const rows = compensations?.data || [];

    // Helper dropdown options
    const toOptions = (items = []) => items.map((i) => (typeof i === 'string' ? { label: i, value: i } : i));

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <Wallet className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        3. Data Kompensasi &amp; Honor/Gaji
                    </span>
                </div>
            }
        >
            <Head title="3. Data Kompensasi & Honor/Gaji (Sallary PKWT)" />

            <div className="p-4 sm:p-6 space-y-5">
                {/* Header Title & Action Button */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400">
                                <Banknote className="h-5 w-5" />
                            </span>
                            <div>
                                <h1 className="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                    3. Master Karyawan (Data Kompensasi &amp; Honor/Gaji)
                                </h1>
                                <p className="text-xs text-zinc-500 dark:text-zinc-400">
                                    <strong className="text-zinc-700 dark:text-zinc-300 font-semibold">
                                        Kategori: Data Sallary Karyawan NISGroup (Masa Kontrak PKWT)
                                    </strong>{' '}
                                    — Rekap honor awal, siklus evaluasi berkala kenaikan upah, dan status pengajuan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <Link href={route('hcm.salary-deductions.index')}>
                            <Button
                                type="button"
                                variant="outline"
                                className="border-rose-200 text-rose-700 hover:bg-rose-50 dark:border-rose-900/60 dark:text-rose-300 font-semibold gap-1.5 h-9 text-xs shrink-0"
                            >
                                <Scissors className="h-4 w-4 text-rose-600" />
                                <span>Potongan &amp; Penyesuaian Gaji</span>
                            </Button>
                        </Link>

                        <Button
                            type="button"
                            onClick={handleOpenCreate}
                            className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs font-semibold gap-1.5 h-9 text-xs shrink-0"
                        >
                            <PlusCircle className="h-4 w-4" />
                            <span>+ Tambah Data Kompensasi</span>
                        </Button>
                    </div>
                </div>

                {/* 4 Cards Metrik Finansial */}
                <div className="grid grid-cols-2 lg:grid-cols-5 gap-3">
                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <Users className="h-3.5 w-3.5 text-indigo-500" />
                                <span>Karyawan Terdata</span>
                            </div>
                            <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                                {metrics.total_employees ?? 0}
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Tercatat di modul kompensasi</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-400">
                                <DollarSign className="h-3.5 w-3.5 text-blue-500" />
                                <span>Akumulasi Honor Awal</span>
                            </div>
                            <div className="text-base font-bold text-zinc-900 dark:text-zinc-100 mt-1 font-mono">
                                {rp(metrics.total_initial)}
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Gaji pertama tanda tangan</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">
                                <Banknote className="h-3.5 w-3.5 text-emerald-500" />
                                <span>Honor Berjalan Saat Ini</span>
                            </div>
                            <div className="text-base font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                                {rp(metrics.total_current)}
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Total komitmen payroll bulanan</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-500">
                                <TrendingUp className="h-3.5 w-3.5 text-indigo-500" />
                                <span>Total Kenaikan Gaji</span>
                            </div>
                            <div className="text-base font-bold text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
                                +{rp(metrics.total_increment)}
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Pertumbuhan upah dievaluasi</div>
                        </CardContent>
                    </Card>

                    <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs col-span-2 lg:col-span-1">
                        <CardContent className="p-3.5">
                            <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-amber-500">
                                <CheckCircle2 className="h-3.5 w-3.5 text-amber-500" />
                                <span>Status Pengajuan</span>
                            </div>
                            <div className="flex items-baseline gap-2 mt-1">
                                <span className="text-sm font-bold text-emerald-600">{metrics.status_telah_berlaku ?? 0} Berlaku</span>
                                <span className="text-xs text-zinc-400">•</span>
                                <span className="text-sm font-bold text-amber-600">{metrics.status_sedang_diajukan ?? 0} Diajukan</span>
                            </div>
                            <div className="text-[10px] text-zinc-400 mt-0.5">Rata-rata: {rp(metrics.avg_current)}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Quick Filter Chips */}
                <div className="flex flex-wrap items-center gap-1.5 text-xs">
                    <span className="text-zinc-400 font-medium mr-1">Filter Cepat:</span>

                    <button
                        type="button"
                        onClick={() => {
                            setStatus('all');
                            setEmploymentStatus('all');
                            applyFilter(search, 'all', 'all', entity);
                        }}
                        className={`px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            status === 'all' && employmentStatus === 'all'
                                ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 border-zinc-900 dark:border-zinc-100 shadow-xs'
                                : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50'
                        }`}
                    >
                        Semua Status
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            setStatus('Telah berlaku');
                            applyFilter(search, 'Telah berlaku', employmentStatus, entity);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            status === 'Telah berlaku'
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs font-semibold'
                                : 'bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900 hover:bg-emerald-100/60'
                        }`}
                    >
                        <CheckCircle2 className="h-3 w-3 shrink-0" />
                        <span>Telah Berlaku</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            setStatus('Sedang Diajukan');
                            applyFilter(search, 'Sedang Diajukan', employmentStatus, entity);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            status === 'Sedang Diajukan'
                                ? 'bg-amber-600 text-white border-amber-600 shadow-xs font-semibold'
                                : 'bg-amber-50/50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-900 hover:bg-amber-100/60'
                        }`}
                    >
                        <Clock className="h-3 w-3 shrink-0" />
                        <span>Sedang Diajukan</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            setStatus('Pending');
                            applyFilter(search, 'Pending', employmentStatus, entity);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            status === 'Pending'
                                ? 'bg-rose-600 text-white border-rose-600 shadow-xs font-semibold'
                                : 'bg-rose-50/50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-900 hover:bg-rose-100/60'
                        }`}
                    >
                        <AlertCircle className="h-3 w-3 shrink-0" />
                        <span>Pending</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            setEmploymentStatus('Tetap');
                            applyFilter(search, status, 'Tetap', entity);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            employmentStatus === 'Tetap'
                                ? 'bg-purple-600 text-white border-purple-600 shadow-xs font-semibold'
                                : 'bg-purple-50/50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-900 hover:bg-purple-100/60'
                        }`}
                    >
                        <Briefcase className="h-3 w-3 shrink-0" />
                        <span>Karyawan Tetap</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            setEmploymentStatus('PKWT');
                            applyFilter(search, status, 'PKWT', entity);
                        }}
                        className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-medium transition shrink-0 border ${
                            employmentStatus === 'PKWT'
                                ? 'bg-blue-600 text-white border-blue-600 shadow-xs font-semibold'
                                : 'bg-blue-50/50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900 hover:bg-blue-100/60'
                        }`}
                    >
                        <FileText className="h-3 w-3 shrink-0" />
                        <span>PKWT</span>
                    </button>
                </div>

                {/* Filter Toolbar */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs">
                    <CardContent className="p-3.5">
                        <form onSubmit={handleSearchSubmit} className="flex flex-col sm:flex-row items-center gap-2.5">
                            <div className="relative flex-1 w-full">
                                <Search className="absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari nama karyawan, nama panggil, NIK, departemen, divisi, no kontrak..."
                                    className="pl-9 h-9 text-xs"
                                />
                            </div>

                            <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                                <SearchableSelect
                                    value={entity}
                                    onValueChange={(val) => {
                                        setEntity(val);
                                        applyFilter(search, status, employmentStatus, val);
                                    }}
                                    options={[
                                        { label: 'Semua CV / Badan Usaha', value: 'all' },
                                        ...toOptions(dropdowns.legal_entities || entities),
                                    ]}
                                    placeholder="Semua Badan Usaha"
                                    className="w-full sm:w-48 text-xs"
                                />

                                <SearchableSelect
                                    value={employmentStatus}
                                    onValueChange={(val) => {
                                        setEmploymentStatus(val);
                                        applyFilter(search, status, val, entity);
                                    }}
                                    options={[
                                        { label: 'Semua Status Ketenagakerjaan', value: 'all' },
                                        ...toOptions(dropdowns.employment_statuses || ['Karyawan Tetap', 'PKWT', 'PKWT Lanjutan', 'Trainee (Probation)']),
                                    ]}
                                    placeholder="Status Kerja"
                                    className="w-full sm:w-44 text-xs"
                                />

                                <SearchableSelect
                                    value={status}
                                    onValueChange={(val) => {
                                        setStatus(val);
                                        applyFilter(search, val, employmentStatus, entity);
                                    }}
                                    options={[
                                        { label: 'Semua Status Pengajuan', value: 'all' },
                                        ...toOptions(dropdowns.salary_statuses || statuses),
                                    ]}
                                    placeholder="Status Pengajuan"
                                    className="w-full sm:w-44 text-xs"
                                />

                                <Button type="submit" size="sm" className="h-9 px-3 text-xs bg-indigo-600 hover:bg-indigo-700 text-white gap-1 shrink-0">
                                    <Search className="h-3.5 w-3.5" />
                                    <span>Filter</span>
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleResetFilters}
                                    className="h-9 px-2.5 text-xs text-zinc-500 hover:text-zinc-800 gap-1 shrink-0"
                                    title="Reset Semua Filter"
                                >
                                    <RotateCcw className="h-3.5 w-3.5" />
                                    <span>Reset</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* 15 Kolom Baku Excel Blueprint Row 23 + Kolom Aksi */}
                <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr className="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/75 dark:bg-zinc-800/50 text-zinc-600 dark:text-zinc-400 font-semibold whitespace-nowrap">
                                        <th className="py-3 px-3">Status Ketenagakerjaan</th>
                                        <th className="py-3 px-3">Nama</th>
                                        <th className="py-3 px-3">Nama Panggil</th>
                                        <th className="py-3 px-3">Departemen / Divisi</th>
                                        <th className="py-3 px-3">CV</th>
                                        <th className="py-3 px-3">No. Kontrak</th>
                                        <th className="py-3 px-3">Masa Kontrak</th>
                                        <th className="py-3 px-3 text-center">Bulan Mulai Trainee</th>
                                        <th className="py-3 px-3 text-center">Siklus Evaluasi (Bulan)</th>
                                        <th className="py-3 px-3 text-right">Honor Awal Kontrak (Rp)</th>
                                        <th className="py-3 px-3 text-right">Honor Saat Ini (Rp)</th>
                                        <th className="py-3 px-3 text-center">Total Kenaikan (Kali)</th>
                                        <th className="py-3 px-3 text-right">Kenaikan 1</th>
                                        <th className="py-3 px-3 text-right">Kenaikan 3</th>
                                        <th className="py-3 px-3 text-center">Status Pengajuan/Honor</th>
                                        <th className="py-3 px-3.5 text-right sticky right-0 bg-zinc-50/95 dark:bg-zinc-800/95 backdrop-blur-xs shadow-xs">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-200/80 dark:divide-zinc-800/80 whitespace-nowrap">
                                    {rows.length > 0 ? (
                                        rows.map((c) => {
                                            const emp = c.employee;
                                            return (
                                                <tr
                                                    key={c.id}
                                                    className="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition"
                                                >
                                                    {/* 1. Status Ketenagakerjaan */}
                                                    <td className="py-3 px-3">
                                                        <Badge
                                                            variant="outline"
                                                            className={`text-[10px] font-semibold ${
                                                                (c.employment_status || emp?.employment_status || '').includes('Tetap')
                                                                    ? 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300'
                                                                    : 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300'
                                                            }`}
                                                        >
                                                            {c.employment_status || emp?.employment_status || 'PKWT'}
                                                        </Badge>
                                                    </td>

                                                    {/* 2. Nama */}
                                                    <td className="py-3 px-3">
                                                        <div className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                            {emp?.name || '-'}
                                                        </div>
                                                        <div className="text-[10px] font-mono text-zinc-400">
                                                            {emp?.employee_code || '-'}
                                                        </div>
                                                    </td>

                                                    {/* 3. Nama Panggil */}
                                                    <td className="py-3 px-3 text-zinc-700 dark:text-zinc-300 font-medium">
                                                        {emp?.nickname || '-'}
                                                    </td>

                                                    {/* 4. Departemen / Divisi */}
                                                    <td className="py-3 px-3">
                                                        <div className="font-semibold text-zinc-800 dark:text-zinc-200">
                                                            {emp?.department || '-'}
                                                        </div>
                                                        {emp?.division && (
                                                            <div className="text-[10px] text-zinc-400">
                                                                {emp?.division}
                                                            </div>
                                                        )}
                                                    </td>

                                                    {/* 5. CV */}
                                                    <td className="py-3 px-3 text-zinc-600 dark:text-zinc-400">
                                                        {c.legal_entity || emp?.legal_entity || '-'}
                                                    </td>

                                                    {/* 6. No. Kontrak */}
                                                    <td className="py-3 px-3 font-mono text-zinc-700 dark:text-zinc-300">
                                                        {c.contract_number || '-'}
                                                    </td>

                                                    {/* 7. Masa Kontrak */}
                                                    <td className="py-3 px-3 text-zinc-700 dark:text-zinc-300">
                                                        {c.duration_text || '1 Tahun'}
                                                    </td>

                                                    {/* 8. Bulan Mulai Trainee / Durasi Trainee */}
                                                    <td className="py-3 px-3 text-center font-mono text-zinc-700 dark:text-zinc-300">
                                                        {c.trainee_duration_months !== null && c.trainee_duration_months !== undefined
                                                            ? `${c.trainee_duration_months} Bulan`
                                                            : '-'}
                                                    </td>

                                                    {/* 9. Siklus Evaluasi (Bulan) */}
                                                    <td className="py-3 px-3 text-center font-mono text-zinc-700 dark:text-zinc-300">
                                                        {c.evaluation_cycle_months ? `${c.evaluation_cycle_months} Bulan` : '6 Bulan'}
                                                    </td>

                                                    {/* 10. Honor Awal Kontrak (Rp) */}
                                                    <td className="py-3 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                        {rp(c.initial_salary)}
                                                    </td>

                                                    {/* 11. Honor Saat Ini (Rp) */}
                                                    <td className="py-3 px-3 text-right font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                                        {rp(c.current_salary)}
                                                    </td>

                                                    {/* 12. Total Kenaikan (Kali) */}
                                                    <td className="py-3 px-3 text-center">
                                                        {c.salary_increment_count > 0 ? (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                                {c.salary_increment_count}x
                                                            </span>
                                                        ) : (
                                                            <span className="text-zinc-400">-</span>
                                                        )}
                                                    </td>

                                                    {/* 13. Kenaikan 1 */}
                                                    <td className="py-3 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                        {c.increment_1_amount ? rp(c.increment_1_amount) : '-'}
                                                    </td>

                                                    {/* 14. Kenaikan 3 */}
                                                    <td className="py-3 px-3 text-right font-mono text-zinc-600 dark:text-zinc-400">
                                                        {c.increment_3_amount ? rp(c.increment_3_amount) : '-'}
                                                    </td>

                                                    {/* 15. Status Pengajuan / Keputusan Evaluasi */}
                                                    <td className="py-3 px-3 text-center">
                                                        <div>{getStatusBadge(c.salary_status)}</div>
                                                        {c.decision_status && (
                                                            <div className="mt-1">
                                                                <span
                                                                    className={`inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border ${
                                                                        c.decision_status === 'Sudah Disetujui / ACC'
                                                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                                            : c.decision_status === 'Ditunda'
                                                                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                                            : c.decision_status === 'Tidak Naik'
                                                                            ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                                            : 'bg-blue-50 text-blue-700 border-blue-200'
                                                                    }`}
                                                                >
                                                                    {c.decision_status}
                                                                </span>
                                                            </div>
                                                        )}
                                                    </td>

                                                    {/* 16. Aksi */}
                                                    <td className="py-3 px-3.5 text-right sticky right-0 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xs shadow-xs">
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleOpenDecision(c)}
                                                                className="h-7 text-[11px] px-2 gap-1 border-indigo-200 text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-300"
                                                                title="Tentukan Keputusan Evaluasi Kenaikan Gaji (ACC/Tunda)"
                                                            >
                                                                <FileText className="h-3 w-3 text-indigo-500" />
                                                                <span>Evaluasi</span>
                                                            </Button>

                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleOpenHistory(c)}
                                                                className="h-7 text-[11px] px-2 gap-1 text-zinc-700 dark:text-zinc-300"
                                                                title="Lihat Riwayat Kenaikan Honor"
                                                            >
                                                                <History className="h-3 w-3 text-indigo-500" />
                                                                <span>Riwayat</span>
                                                            </Button>

                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                onClick={() => handleOpenIncrement(c)}
                                                                className="h-7 text-[11px] px-2 gap-1 bg-emerald-600 hover:bg-emerald-700 text-white"
                                                                title="Catat Kenaikan Honor Langsung"
                                                            >
                                                                <ArrowUpRight className="h-3 w-3" />
                                                                <span>Naikkan</span>
                                                            </Button>

                                                            <button
                                                                type="button"
                                                                onClick={() => handleOpenEdit(c)}
                                                                className="p-1 rounded text-zinc-500 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                                                                title="Edit Data Kompensasi"
                                                            >
                                                                <Edit3 className="h-3.5 w-3.5" />
                                                            </button>

                                                            {(emp?.employee_code || emp?.id) && (
                                                                <Link href={route('hcm.employees.show', emp.employee_code || emp.id)}>
                                                                    <button
                                                                        type="button"
                                                                        className="p-1 rounded text-zinc-500 hover:text-indigo-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                                                                        title="Buka Dossier 360 Karyawan"
                                                                    >
                                                                        <Eye className="h-3.5 w-3.5" />
                                                                    </button>
                                                                </Link>
                                                            )}

                                                            <button
                                                                type="button"
                                                                onClick={() => handleOpenDelete(c)}
                                                                className="p-1 rounded text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                                title="Hapus Data Kompensasi"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan={16} className="text-center text-zinc-400 py-12">
                                                <div className="flex flex-col items-center justify-center gap-2">
                                                    <Banknote className="h-8 w-8 text-zinc-300" />
                                                    <p className="text-xs">Tidak ada data kompensasi yang sesuai dengan filter.</p>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={handleResetFilters}
                                                        className="text-xs mt-1"
                                                    >
                                                        Reset Filter
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {compensations?.links && compensations.links.length > 3 && (
                    <div className="flex items-center justify-between pt-2">
                        <div className="text-xs text-zinc-500">
                            Menampilkan <strong>{compensations.from || 0}</strong> - <strong>{compensations.to || 0}</strong> dari{' '}
                            <strong>{compensations.total}</strong> karyawan
                        </div>
                        <div className="flex items-center gap-1">
                            {compensations.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    preserveScroll
                                    preserveState
                                    className={`px-2.5 py-1 text-xs rounded transition ${
                                        link.active
                                            ? 'bg-indigo-600 text-white font-semibold'
                                            : !link.url
                                            ? 'text-zinc-400 cursor-not-allowed'
                                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Modal Tambah Data Kompensasi Baru */}
            <Dialog open={createModalOpen} onOpenChange={setCreateModalOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <PlusCircle className="h-4 w-4 text-indigo-600" />
                            Tambah Data Kompensasi &amp; Honor Karyawan
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Input komitmen honor awal, masa kontrak PKWT, siklus peninjauan 6 bulan, dan penetapan status pengajuan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCreate} className="space-y-4 pt-1">
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Pilih Karyawan *</Label>
                            <SearchableSelect
                                value={createForm.data.employee_id}
                                onValueChange={handleSelectEmployee}
                                options={(dropdowns.employees || []).map((e) => ({
                                    label: `${e.name} (${e.nickname || '-'}) — ${e.employee_code} [${e.division || e.department || '-'}]`,
                                    value: String(e.id),
                                }))}
                                placeholder="Cari nama karyawan / NIK..."
                            />
                            {createForm.errors.employee_id && (
                                <p className="text-[11px] text-rose-500">{createForm.errors.employee_id}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Status Ketenagakerjaan *</Label>
                                <SearchableSelect
                                    value={createForm.data.employment_status}
                                    onValueChange={(v) => createForm.setData('employment_status', v)}
                                    options={toOptions(dropdowns.employment_statuses || ['Karyawan Tetap', 'PKWT', 'PKWT Lanjutan', 'Trainee (Probation)'])}
                                    placeholder="Pilih status"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Badan Usaha / CV *</Label>
                                <SearchableSelect
                                    value={createForm.data.legal_entity}
                                    onValueChange={(v) => createForm.setData('legal_entity', v)}
                                    options={toOptions(dropdowns.legal_entities || ['CV Jersey Ekonomis', 'CV Apparel Allegiant', 'CV Bawang Merah', 'CV Bawang Putih'])}
                                    placeholder="Pilih entitas legal"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">No. Kontrak Terkait</Label>
                                <Input
                                    value={createForm.data.contract_number}
                                    onChange={(e) => createForm.setData('contract_number', e.target.value)}
                                    placeholder="contoh: 001/OWR/PKWT/X/2026"
                                    className="text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Masa Kontrak</Label>
                                <Input
                                    value={createForm.data.duration_text}
                                    onChange={(e) => createForm.setData('duration_text', e.target.value)}
                                    placeholder="contoh: 1 Tahun, 2 Tahun, Tetap"
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Bulan Mulai Trainee / Durasi (Bulan)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={createForm.data.trainee_duration_months}
                                    onChange={(e) => createForm.setData('trainee_duration_months', e.target.value)}
                                    placeholder="contoh: 8, 12, 24"
                                    className="text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Siklus Evaluasi Kenaikan (Bulan) *</Label>
                                <Input
                                    type="number"
                                    min="1"
                                    value={createForm.data.evaluation_cycle_months}
                                    onChange={(e) => createForm.setData('evaluation_cycle_months', e.target.value)}
                                    placeholder="Default: 6 bulan"
                                    className="text-xs font-mono"
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Honor Awal Kontrak (Rp) *</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={createForm.data.initial_salary}
                                    onChange={(e) => {
                                        createForm.setData('initial_salary', e.target.value);
                                        if (!createForm.data.current_salary) {
                                            createForm.setData('current_salary', e.target.value);
                                        }
                                    }}
                                    placeholder="contoh: 2500000"
                                    className="text-xs font-mono"
                                    required
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Honor Saat Ini (Rp) *</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={createForm.data.current_salary}
                                    onChange={(e) => createForm.setData('current_salary', e.target.value)}
                                    placeholder="contoh: 2500000"
                                    className="text-xs font-mono font-bold"
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Status Pengajuan / Honor *</Label>
                            <SearchableSelect
                                value={createForm.data.salary_status}
                                onValueChange={(v) => createForm.setData('salary_status', v)}
                                options={toOptions(dropdowns.salary_statuses || ['Telah berlaku', 'Sedang Diajukan', 'Pending', 'Draft'])}
                                placeholder="Pilih status pengajuan"
                            />
                        </div>

                        <div className="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-lg space-y-2 border border-zinc-200/80 dark:border-zinc-700/80">
                            <div className="text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                                Rekam Jejak Kenaikan Honor Historis (Opsional)
                            </div>
                            <div className="grid grid-cols-3 gap-2">
                                <div className="space-y-1">
                                    <Label className="text-[10px] text-zinc-500">Kenaikan 1 (Rp)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={createForm.data.increment_1_amount}
                                        onChange={(e) => createForm.setData('increment_1_amount', e.target.value)}
                                        placeholder="0"
                                        className="text-xs font-mono h-8"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-[10px] text-zinc-500">Kenaikan 2 (Rp)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={createForm.data.increment_2_amount}
                                        onChange={(e) => createForm.setData('increment_2_amount', e.target.value)}
                                        placeholder="0"
                                        className="text-xs font-mono h-8"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-[10px] text-zinc-500">Kenaikan 3 (Rp)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={createForm.data.increment_3_amount}
                                        onChange={(e) => createForm.setData('increment_3_amount', e.target.value)}
                                        placeholder="0"
                                        className="text-xs font-mono h-8"
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setCreateModalOpen(false)}
                                disabled={createForm.processing}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                                disabled={createForm.processing}
                            >
                                {createForm.processing ? 'Menyimpan...' : 'Simpan Kompensasi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Edit Data Kompensasi */}
            <Dialog open={editModalOpen} onOpenChange={setEditModalOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <Edit3 className="h-4 w-4 text-indigo-600" />
                            Edit Kompensasi — {selectedCompensation?.employee?.name}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Perbarui rincian honor kontrak, status ketenagakerjaan, siklus evaluasi, dan status pengajuan.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitEdit} className="space-y-4 pt-1">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Status Ketenagakerjaan *</Label>
                                <SearchableSelect
                                    value={editForm.data.employment_status}
                                    onValueChange={(v) => editForm.setData('employment_status', v)}
                                    options={toOptions(dropdowns.employment_statuses || ['Karyawan Tetap', 'PKWT', 'PKWT Lanjutan', 'Trainee (Probation)'])}
                                    placeholder="Pilih status"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Badan Usaha / CV *</Label>
                                <SearchableSelect
                                    value={editForm.data.legal_entity}
                                    onValueChange={(v) => editForm.setData('legal_entity', v)}
                                    options={toOptions(dropdowns.legal_entities || ['CV Jersey Ekonomis', 'CV Apparel Allegiant', 'CV Bawang Merah', 'CV Bawang Putih'])}
                                    placeholder="Pilih entitas legal"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">No. Kontrak Terkait</Label>
                                <Input
                                    value={editForm.data.contract_number}
                                    onChange={(e) => editForm.setData('contract_number', e.target.value)}
                                    placeholder="001/OWR/PKWT/X/2026"
                                    className="text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Masa Kontrak</Label>
                                <Input
                                    value={editForm.data.duration_text}
                                    onChange={(e) => editForm.setData('duration_text', e.target.value)}
                                    placeholder="1 Tahun, 2 Tahun, Tetap"
                                    className="text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Bulan Mulai Trainee / Durasi (Bulan)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={editForm.data.trainee_duration_months}
                                    onChange={(e) => editForm.setData('trainee_duration_months', e.target.value)}
                                    placeholder="8, 12, 24"
                                    className="text-xs font-mono"
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Siklus Evaluasi Kenaikan (Bulan) *</Label>
                                <Input
                                    type="number"
                                    min="1"
                                    value={editForm.data.evaluation_cycle_months}
                                    onChange={(e) => editForm.setData('evaluation_cycle_months', e.target.value)}
                                    placeholder="6"
                                    className="text-xs font-mono"
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Honor Awal Kontrak (Rp) *</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={editForm.data.initial_salary}
                                    onChange={(e) => editForm.setData('initial_salary', e.target.value)}
                                    className="text-xs font-mono"
                                    required
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Honor Saat Ini (Rp) *</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={editForm.data.current_salary}
                                    onChange={(e) => editForm.setData('current_salary', e.target.value)}
                                    className="text-xs font-mono font-bold"
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold">Status Pengajuan / Honor *</Label>
                            <SearchableSelect
                                value={editForm.data.salary_status}
                                onValueChange={(v) => editForm.setData('salary_status', v)}
                                options={toOptions(dropdowns.salary_statuses || ['Telah berlaku', 'Sedang Diajukan', 'Pending', 'Draft'])}
                                placeholder="Pilih status"
                            />
                        </div>

                        <div className="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-lg space-y-2 border border-zinc-200/80 dark:border-zinc-700/80">
                            <div className="text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                                Histori Kenaikan Honor
                            </div>
                            <div className="grid grid-cols-3 gap-2">
                                <div className="space-y-1">
                                    <Label className="text-[10px] text-zinc-500">Kenaikan 1 (Rp)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={editForm.data.increment_1_amount}
                                        onChange={(e) => editForm.setData('increment_1_amount', e.target.value)}
                                        className="text-xs font-mono h-8"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-[10px] text-zinc-500">Kenaikan 2 (Rp)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={editForm.data.increment_2_amount}
                                        onChange={(e) => editForm.setData('increment_2_amount', e.target.value)}
                                        className="text-xs font-mono h-8"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-[10px] text-zinc-500">Kenaikan 3 (Rp)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={editForm.data.increment_3_amount}
                                        onChange={(e) => editForm.setData('increment_3_amount', e.target.value)}
                                        className="text-xs font-mono h-8"
                                    />
                                </div>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setEditModalOpen(false)}
                                disabled={editForm.processing}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                className="bg-indigo-600 hover:bg-indigo-700 text-white"
                                disabled={editForm.processing}
                            >
                                {editForm.processing ? 'Menyimpan...' : 'Perbarui Kompensasi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Riwayat Kenaikan Gaji */}
            <Dialog open={historyModalOpen} onOpenChange={setHistoryModalOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <History className="h-4 w-4 text-indigo-600" />
                            Riwayat Kenaikan Gaji — {selectedCompensation?.employee?.name}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Log kenaikan gaji berkala, evaluasi berkala 6 bulan, dan penyesuaian nominal upah.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-2 space-y-3">
                        <div className="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg text-xs">
                            <div>
                                <span className="text-zinc-400">Gaji Awal: </span>
                                <span className="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {rp(selectedCompensation?.initial_salary)}
                                </span>
                            </div>
                            <div>
                                <span className="text-zinc-400">Gaji Berjalan: </span>
                                <span className="font-semibold text-emerald-600">
                                    {rp(selectedCompensation?.current_salary)}
                                </span>
                            </div>
                            <div>
                                <span className="text-zinc-400">Total Kenaikan: </span>
                                <span className="font-semibold text-indigo-600 font-mono">
                                    +{rp(
                                        Number(selectedCompensation?.current_salary || 0) -
                                            Number(selectedCompensation?.initial_salary || 0)
                                    )}
                                </span>
                            </div>
                        </div>

                        {selectedCompensation?.histories && selectedCompensation.histories.length > 0 ? (
                            <div className="border border-zinc-200 dark:border-zinc-800 rounded-md divide-y divide-zinc-200 dark:divide-zinc-800 max-h-72 overflow-y-auto">
                                {selectedCompensation.histories.map((h) => (
                                    <div
                                        key={h.id}
                                        className="p-3 text-xs flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
                                    >
                                        <div>
                                            <div className="font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                                <span>{h.reason || 'Kenaikan Gaji'}</span>
                                                <Badge
                                                    variant="outline"
                                                    className="text-[10px] text-emerald-600 border-emerald-300"
                                                >
                                                    +{rp(h.increment_amount)}
                                                </Badge>
                                            </div>
                                            <div className="text-[11px] text-zinc-400 mt-0.5">
                                                Berlaku: {h.effective_date} • Dari {rp(h.previous_salary)} $\rightarrow${' '}
                                                {rp(h.new_salary)}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8 text-xs text-zinc-400 border border-dashed rounded-md">
                                Belum ada riwayat kenaikan gaji yang dicatat.
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setHistoryModalOpen(false)}
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Form Catat Kenaikan Gaji (Increment) */}
            <Dialog open={incrementModalOpen} onOpenChange={setIncrementModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base flex items-center gap-2">
                            <ArrowUpRight className="h-4 w-4 text-emerald-600" />
                            Catat Kenaikan Gaji Berkala
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Karyawan: <strong>{selectedCompensation?.employee?.name}</strong> • Gaji Saat Ini:{' '}
                            <span className="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                                {rp(selectedCompensation?.current_salary)}
                            </span>
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitIncrement} className="space-y-3 pt-2">
                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Gaji Baru (Rp) *</Label>
                            <Input
                                type="number"
                                min={Number(selectedCompensation?.current_salary || 0) + 1}
                                value={incrementForm.data.new_salary}
                                onChange={(e) => handleIncrementSalaryChange(e.target.value)}
                                className="text-xs font-mono font-bold"
                                required
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Nominal Kenaikan (Rp)</Label>
                            <Input
                                type="number"
                                min="0"
                                value={incrementForm.data.increment_amount}
                                onChange={(e) => handleIncrementAmountChange(e.target.value)}
                                className="text-xs font-mono text-emerald-600 font-semibold"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Tanggal Efektif Berlaku *</Label>
                            <Input
                                type="date"
                                value={incrementForm.data.effective_date}
                                onChange={(e) => incrementForm.setData('effective_date', e.target.value)}
                                className="text-xs"
                                required
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Alasan / Catatan Kenaikan</Label>
                            <Input
                                value={incrementForm.data.reason}
                                onChange={(e) => incrementForm.setData('reason', e.target.value)}
                                placeholder="contoh: Evaluasi Performa 6 Bulan / Kenaikan Upah Berkala"
                                className="text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setIncrementModalOpen(false)}
                                disabled={incrementForm.processing}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                className="bg-emerald-600 hover:bg-emerald-700 text-white"
                                disabled={incrementForm.processing}
                            >
                                {incrementForm.processing ? 'Menyimpan...' : 'Simpan Kenaikan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Keputusan Evaluasi Kenaikan Gaji (Siklus Milestone & ACC/Tunda) */}
            <Dialog open={decisionModalOpen} onOpenChange={setDecisionModalOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-base font-bold flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                            <FileText className="h-4 w-4" />
                            <span>Keputusan Evaluasi Kenaikan Gaji Berkala</span>
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Karyawan: <strong>{selectedCompensation?.employee?.name}</strong> ({selectedCompensation?.employee?.employee_code}) • {selectedCompensation?.employee?.department || '-'} / {selectedCompensation?.employee?.division || '-'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitDecision} className="space-y-3.5">
                        {/* Info Ringkas Masa Kerja & Gaji Berjalan */}
                        <div className="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/80 space-y-1.5 text-xs">
                            <div className="flex justify-between text-zinc-600 dark:text-zinc-300">
                                <span>Tanggal Masuk (Anchor):</span>
                                <span className="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {selectedCompensation?.employee?.original_join_date || selectedCompensation?.employee?.join_date || '-'}
                                </span>
                            </div>
                            <div className="flex justify-between text-zinc-600 dark:text-zinc-300">
                                <span>Gaji Berjalan Saat Ini:</span>
                                <span className="font-mono font-bold text-zinc-900 dark:text-zinc-100">
                                    {rp(selectedCompensation?.current_salary)}
                                </span>
                            </div>
                            <div className="flex justify-between text-zinc-600 dark:text-zinc-300">
                                <span>Siklus Review Standar:</span>
                                <span className="font-semibold text-indigo-600">
                                    Setiap {selectedCompensation?.evaluation_cycle_months ?? 12} Bulan
                                </span>
                            </div>
                        </div>

                        <div>
                            <Label className="text-xs font-semibold">Status Keputusan Evaluasi *</Label>
                            <SearchableSelect
                                value={decisionForm.data.decision_status}
                                onValueChange={(val) => decisionForm.setData('decision_status', val)}
                                options={[
                                    { label: 'Sedang Diajukan', value: 'Sedang Diajukan' },
                                    { label: 'Sudah Disetujui / ACC', value: 'Sudah Disetujui / ACC' },
                                    { label: 'Ditunda', value: 'Ditunda' },
                                    { label: 'Tidak Naik', value: 'Tidak Naik' },
                                ]}
                                placeholder="Pilih Status Keputusan"
                                className="mt-1 text-xs"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label className="text-xs font-semibold">Usulan / Nilai Kenaikan (Rp)</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    value={decisionForm.data.planned_increment}
                                    onChange={(e) => decisionForm.setData('planned_increment', e.target.value)}
                                    placeholder="Contoh: 200000"
                                    className="mt-1 h-9 text-xs font-mono"
                                />
                            </div>

                            <div>
                                <Label className="text-xs font-semibold">Tanggal Efektif</Label>
                                <Input
                                    type="date"
                                    value={decisionForm.data.effective_date}
                                    onChange={(e) => decisionForm.setData('effective_date', e.target.value)}
                                    className="mt-1 h-9 text-xs"
                                />
                            </div>
                        </div>

                        {decisionForm.data.decision_status === 'Ditunda' && (
                            <div className="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 space-y-1">
                                <Label className="text-xs font-semibold text-amber-800 dark:text-amber-300">
                                    Tanggal Review Baru (Custom Milestone) *
                                </Label>
                                <p className="text-[10px] text-amber-700 dark:text-amber-400">
                                    Jadwal evaluasi khusus (misal tunda 3 bulan) tanpa merusak siklus tahunan masa kerja.
                                </p>
                                <Input
                                    type="date"
                                    value={decisionForm.data.custom_milestone_date}
                                    onChange={(e) => decisionForm.setData('custom_milestone_date', e.target.value)}
                                    className="h-8 text-xs bg-white dark:bg-zinc-900 mt-1"
                                />
                            </div>
                        )}

                        <div>
                            <Label className="text-xs font-semibold">Catatan &amp; Notula Hasil Evaluasi</Label>
                            <Input
                                value={decisionForm.data.decision_notes}
                                onChange={(e) => decisionForm.setData('decision_notes', e.target.value)}
                                placeholder="Alasan penundaan / persetujuan / catatan performa..."
                                className="mt-1 h-9 text-xs"
                            />
                        </div>

                        {decisionForm.data.decision_status === 'Sudah Disetujui / ACC' && (
                            <div className="flex items-center gap-2 p-2 rounded bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40">
                                <input
                                    type="checkbox"
                                    id="apply_immediately"
                                    checked={decisionForm.data.apply_immediately}
                                    onChange={(e) => decisionForm.setData('apply_immediately', e.target.checked)}
                                    className="rounded border-emerald-300 text-emerald-600 h-4 w-4"
                                />
                                <Label htmlFor="apply_immediately" className="text-xs font-semibold text-emerald-800 dark:text-emerald-300 cursor-pointer">
                                    Langsung terapkan kenaikan ini ke Gaji Berjalan saat disimpan (Apply Increment)
                                </Label>
                            </div>
                        )}

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setDecisionModalOpen(false)}
                                className="text-xs"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={decisionForm.processing}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold"
                            >
                                {decisionForm.processing ? 'Menyimpan...' : 'Simpan Keputusan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Konfirmasi Hapus */}
            <Dialog open={deleteModalOpen} onOpenChange={setDeleteModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base text-rose-600 flex items-center gap-2">
                            <Trash2 className="h-4 w-4" /> Hapus Data Kompensasi
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Apakah Anda yakin ingin menghapus data kompensasi untuk{' '}
                            <strong>{selectedCompensation?.employee?.name}</strong>? Seluruh riwayat log kenaikan gaji
                            terkait juga akan dihapus.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => setDeleteModalOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            onClick={submitDelete}
                        >
                            Ya, Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
