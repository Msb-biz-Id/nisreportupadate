import { Head, Link } from '@inertiajs/react';
import {
    LayoutDashboard,
    Users,
    Briefcase,
    CalendarCheck,
    CalendarDays,
    Clock,
    UtensilsCrossed,
    Award,
    Calendar,
    AlertTriangle,
    ShieldAlert,
    Cake,
    Sparkles,
    HeartHandshake,
    ArrowRight,
    ArrowUpRight,
    CheckCircle2,
    DollarSign,
    PartyPopper,
    Building2,
    ChevronRight,
    Bell,
    MapPin,
    AlertCircle,
    UserX,
    FileText,
    Send,
    PlusCircle,
    Info,
} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

export default function HcmDashboardIndex({
    today,
    moduleTotals,
    legalEntities = [],
    probationAlerts = [],
    contractAlerts = [],
    unexcusedAbsenceAlerts = [],
    overtimeReminder = {},
    payrollCutoffReminder = {},
    pendingLeaves = [],
    activeLeavesToday = [],
    birthdayAlerts = [],
    anniversaryAlerts = [],
    upcomingEvents = [],
    daysToCutoff = 0,
}) {
    const formatRp = (val) => {
        if (!val) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val);
    };

    const totalUrgentIssues =
        (probationAlerts?.length || 0) +
        (contractAlerts?.length || 0) +
        (unexcusedAbsenceAlerts?.length || 0) +
        (moduleTotals.leaves?.pending || 0) +
        (moduleTotals.overtime?.pending_batches || 0) +
        (moduleTotals.meal_allowance?.pending_batches || 0);

    return (
        <AppLayout title="Command Center Kepegawaian">
            <Head title="Command Center Kepegawaian (HCM) - NISGroup" />

            <div className="space-y-6">
                {/* 1. Header Command Center */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-blue-900/10 via-indigo-900/5 to-transparent p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20">
                                <LayoutDashboard className="w-6 h-6" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                        Command Center Kepegawaian
                                    </h1>
                                    <Badge className="bg-blue-600/10 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-[10px]">
                                        NISGroup HRIS
                                    </Badge>
                                </div>
                                <p className="text-xs text-zinc-500 mt-0.5">
                                    Agregasi data real-time seluruh unit operasional dan entitas legal terdaftar{legalEntities && legalEntities.length > 0 ? ` (${legalEntities.join(', ')})` : ''}.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <div className={`px-3 py-2 rounded-xl border text-xs flex items-center gap-2 shadow-sm font-medium ${
                            daysToCutoff <= 1 
                                ? 'bg-rose-50 border-rose-300 text-rose-700 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-300 animate-pulse'
                                : daysToCutoff <= 3 
                                    ? 'bg-amber-50 border-amber-300 text-amber-700 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-300'
                                    : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300'
                        }`}>
                            <Clock className={`w-4 h-4 ${daysToCutoff <= 3 ? 'text-rose-600' : 'text-purple-600'}`} />
                            <span>Cut-Off Payroll:</span>
                            <strong className="font-mono">{daysToCutoff <= 0 ? 'Hari Ini!' : `H-${daysToCutoff}`}</strong>
                        </div>

                        <Link
                            href={route('hcm.events.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Tambah Agenda/Undangan
                        </Link>
                    </div>
                </div>

                {/* 2. PAYROLL & OVERTIME TIMELINE REMINDER BANNERS (SESUAI BLUEPRINT SHEET 0) */}
                <div className="space-y-3">
                    {/* A. Validasi Total Lembur Mingguan (Jumat/Sabtu Alert) */}
                    {(overtimeReminder?.is_weekend_cutoff || overtimeReminder?.draft_batches_count > 0) && (
                        <div className={`p-4 rounded-xl border flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-sm ${
                            overtimeReminder?.is_saturday && overtimeReminder?.draft_batches_count > 0
                                ? 'bg-rose-50/90 border-rose-300 text-rose-950 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-200'
                                : 'bg-amber-50/90 border-amber-300 text-amber-950 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-200'
                        }`}>
                            <div className="flex items-start gap-3">
                                <div className={`p-2 rounded-lg mt-0.5 ${
                                    overtimeReminder?.is_saturday && overtimeReminder?.draft_batches_count > 0
                                        ? 'bg-rose-600 text-white'
                                        : 'bg-amber-500 text-white'
                                }`}>
                                    <Clock className="w-5 h-5" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="font-bold text-sm">
                                            Pengingat Validasi Lembur Mingguan (Pencairan Hari Sabtu)
                                        </span>
                                        <Badge className={overtimeReminder?.is_saturday && overtimeReminder?.draft_batches_count > 0 ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white'}>
                                            {overtimeReminder?.is_saturday ? 'Batas Waktu Hari Ini' : 'H-1 Persiapan'}
                                        </Badge>
                                    </div>
                                    <p className="text-xs mt-1 text-zinc-700 dark:text-zinc-300">
                                        Mohon lakukan validasi & persetujuan total jam lembur minggu ini sebelum diteruskan ke Tim Keuangan untuk pencairan kas mingguan.
                                    </p>
                                    {overtimeReminder?.latest_batch && (
                                        <div className="mt-2 text-xs font-mono font-medium flex items-center gap-3">
                                            <span>Batch: {overtimeReminder.latest_batch.batch_code}</span>
                                            <span>• Total: {overtimeReminder.latest_batch.total_hours} Jam ({formatRp(overtimeReminder.latest_batch.total_amount)})</span>
                                            <span className="font-semibold text-rose-600 dark:text-rose-400">Status: Belum Divalidasi</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center gap-2 self-end md:self-center">
                                <Link
                                    href={overtimeReminder?.latest_batch ? route('hcm.overtime.show', overtimeReminder.latest_batch.id) : route('hcm.overtime.index')}
                                    className={`px-3.5 py-2 rounded-lg text-xs font-semibold text-white shadow-sm flex items-center gap-1.5 transition ${
                                        overtimeReminder?.is_saturday && overtimeReminder?.draft_batches_count > 0
                                            ? 'bg-rose-600 hover:bg-rose-700'
                                            : 'bg-amber-600 hover:bg-amber-700'
                                    }`}
                                >
                                    Validasi & Kirim ke Keuangan <ArrowRight className="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* B. Validasi Kehadiran & Rekap Absensi Bulanan (H-3 Akhir Bulan Alert) */}
                    {(payrollCutoffReminder?.is_cutoff_window || payrollCutoffReminder?.draft_batches_count > 0) && (
                        <div className={`p-4 rounded-xl border flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-sm ${
                            payrollCutoffReminder?.is_cutoff_day
                                ? 'bg-rose-50/90 border-rose-300 text-rose-950 dark:bg-rose-950/30 dark:border-rose-800 dark:text-rose-200'
                                : 'bg-amber-50/90 border-amber-300 text-amber-950 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-200'
                        }`}>
                            <div className="flex items-start gap-3">
                                <div className={`p-2 rounded-lg mt-0.5 ${
                                    payrollCutoffReminder?.is_cutoff_day
                                        ? 'bg-rose-600 text-white'
                                        : 'bg-amber-500 text-white'
                                }`}>
                                    <UtensilsCrossed className="w-5 h-5" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="font-bold text-sm">
                                            Pengingat Cut-Off & Validasi Kehadiran Bulanan (Uang Makan & Payroll)
                                        </span>
                                        <Badge className={payrollCutoffReminder?.is_cutoff_day ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white'}>
                                            {payrollCutoffReminder?.is_cutoff_day ? 'Hari H Cut-Off' : `H-${payrollCutoffReminder?.days_to_cutoff} Cut-Off`}
                                        </Badge>
                                    </div>
                                    <p className="text-xs mt-1 text-zinc-700 dark:text-zinc-300">
                                        Batas akhir validasi kehadiran, cuti, dan izin bulan ini. Setelah divalidasi oleh HCM, data akan dikunci untuk eksekusi penggajian & pencairan uang makan oleh Tim Keuangan.
                                    </p>
                                    {payrollCutoffReminder?.latest_batch && (
                                        <div className="mt-2 text-xs font-mono font-medium flex items-center gap-3">
                                            <span>Batch: {payrollCutoffReminder.latest_batch.batch_code}</span>
                                            <span>• Nominal: {formatRp(payrollCutoffReminder.latest_batch.total_amount)}</span>
                                            <span className="font-semibold text-amber-700 dark:text-amber-400">Status: Pending Check</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center gap-2 self-end md:self-center">
                                <Link
                                    href={route('hcm.meal-allowance.index')}
                                    className="px-3.5 py-2 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm flex items-center gap-1.5 transition"
                                >
                                    Kunci Data & Transfer ke Keuangan <ArrowRight className="w-3.5 h-3.5" />
                                </Link>
                            </div>
                        </div>
                    )}
                </div>

                {/* 3. RINGKASAN TOTAL DARI SELURUH 8 MODUL KEPEGAWAIAN (GRID 4x2) */}
                <div>
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="text-xs font-bold uppercase tracking-wider text-zinc-500 flex items-center gap-1.5">
                            <Building2 className="w-3.5 h-3.5" />
                            Ringkasan Agregasi Seluruh Modul Kepegawaian
                        </h2>
                        <span className="text-[11px] text-zinc-400 font-medium">8 Modul Terintegrasi Faktual</span>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        {/* Modul 1: Karyawan & Magang */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm hover:border-blue-300 dark:hover:border-blue-700 transition">
                            <CardContent className="p-4 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                        <Users className="w-4 h-4" />
                                    </div>
                                    <Link
                                        href={route('hcm.employees.index')}
                                        className="text-[11px] text-blue-600 hover:underline flex items-center font-medium"
                                    >
                                        Buka Modul <ArrowUpRight className="w-3 h-3 ml-0.5" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="text-xs text-zinc-500">Karyawan & Magang</div>
                                    <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">
                                        {moduleTotals.employees?.total_active}{' '}
                                        <span className="text-xs font-normal text-zinc-400">Aktif</span>
                                    </div>
                                </div>
                                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] text-zinc-500">
                                    <span>Tetap: <strong>{moduleTotals.employees?.pkwtt}</strong></span>
                                    <span>Kontrak: <strong>{moduleTotals.employees?.pkwt}</strong></span>
                                    <span>Magang: <strong>{moduleTotals.employees?.magang}</strong></span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Modul 2: Kontrak Kerja PKWT */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm hover:border-amber-300 dark:hover:border-amber-700 transition">
                            <CardContent className="p-4 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                                        <Briefcase className="w-4 h-4" />
                                    </div>
                                    <Link
                                        href={route('hcm.employees.index')}
                                        className="text-[11px] text-amber-600 hover:underline flex items-center font-medium"
                                    >
                                        Buka Modul <ArrowUpRight className="w-3 h-3 ml-0.5" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="text-xs text-zinc-500">Kontrak Legalitas PKWT</div>
                                    <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">
                                        {moduleTotals.contracts?.active}{' '}
                                        <span className="text-xs font-normal text-zinc-400">Aktif</span>
                                    </div>
                                </div>
                                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px]">
                                    <span className="text-zinc-500">Total: <strong>{moduleTotals.contracts?.total_contracts}</strong></span>
                                    <span className={moduleTotals.contracts?.expiring_h30 > 0 ? 'text-amber-600 font-bold' : 'text-zinc-400'}>
                                        Kritis H-30: <strong>{moduleTotals.contracts?.expiring_h30}</strong>
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Modul 3: Presensi Harian */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm hover:border-emerald-300 dark:hover:border-emerald-700 transition">
                            <CardContent className="p-4 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                                        <CalendarCheck className="w-4 h-4" />
                                    </div>
                                    <Link
                                        href={route('hcm.attendance.index')}
                                        className="text-[11px] text-emerald-600 hover:underline flex items-center font-medium"
                                    >
                                        Buka Matriks <ArrowUpRight className="w-3 h-3 ml-0.5" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="text-xs text-zinc-500">Kehadiran Hari Ini ({today})</div>
                                    <div className="text-xl font-bold text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">
                                        {moduleTotals.attendance?.today_present}{' '}
                                        <span className="text-xs font-normal text-zinc-400">Hadir</span>
                                    </div>
                                </div>
                                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px]">
                                    <span className="text-amber-600">Telat: <strong>{moduleTotals.attendance?.today_late}</strong></span>
                                    <span className="text-blue-600">Izin/Cuti: <strong>{moduleTotals.attendance?.today_leave_permit}</strong></span>
                                    <span className={moduleTotals.attendance?.today_alpha > 0 ? 'text-rose-600 font-bold' : 'text-zinc-400'}>
                                        Alpha: <strong>{moduleTotals.attendance?.today_alpha}</strong>
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Modul 4: Cuti & Perizinan */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm hover:border-sky-300 dark:hover:border-sky-700 transition">
                            <CardContent className="p-4 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="p-2 rounded-lg bg-sky-50 dark:bg-sky-950/40 text-sky-600">
                                        <CalendarDays className="w-4 h-4" />
                                    </div>
                                    <Link
                                        href={route('hcm.leaves.index')}
                                        className="text-[11px] text-sky-600 hover:underline flex items-center font-medium"
                                    >
                                        Buka Modul <ArrowUpRight className="w-3 h-3 ml-0.5" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="text-xs text-zinc-500">Pengajuan Cuti & Izin</div>
                                    <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">
                                        {moduleTotals.leaves?.total_year}{' '}
                                        <span className="text-xs font-normal text-zinc-400">Tahun Ini</span>
                                    </div>
                                </div>
                                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px]">
                                    <span className={moduleTotals.leaves?.pending > 0 ? 'text-amber-600 font-bold' : 'text-zinc-400'}>
                                        Pending: <strong>{moduleTotals.leaves?.pending}</strong>
                                    </span>
                                    <span className="text-emerald-600">Disetujui: <strong>{moduleTotals.leaves?.approved_month}</strong></span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Modul 5: Lembur Mingguan */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm hover:border-purple-300 dark:hover:border-purple-700 transition">
                            <CardContent className="p-4 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="p-2 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600">
                                        <Clock className="w-4 h-4" />
                                    </div>
                                    <Link
                                        href={route('hcm.overtime.index')}
                                        className="text-[11px] text-purple-600 hover:underline flex items-center font-medium"
                                    >
                                        Buka Modul <ArrowUpRight className="w-3 h-3 ml-0.5" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="text-xs text-zinc-500">Lembur Mingguan (Bulan Ini)</div>
                                    <div className="text-xl font-bold text-purple-700 dark:text-purple-400 font-mono mt-0.5">
                                        {formatRp(moduleTotals.overtime?.month_cost)}
                                    </div>
                                </div>
                                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px]">
                                    <span className="text-zinc-500 font-mono">{moduleTotals.overtime?.month_hours} Jam</span>
                                    <span className={moduleTotals.overtime?.pending_batches > 0 ? 'text-amber-600 font-bold' : 'text-zinc-400'}>
                                        Pending Sign: <strong>{moduleTotals.overtime?.pending_batches} Batch</strong>
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Modul 6: Uang Makan Bulanan */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm hover:border-teal-300 dark:hover:border-teal-700 transition">
                            <CardContent className="p-4 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="p-2 rounded-lg bg-teal-50 dark:bg-teal-950/40 text-teal-600">
                                        <UtensilsCrossed className="w-4 h-4" />
                                    </div>
                                    <Link
                                        href={route('hcm.meal-allowance.index')}
                                        className="text-[11px] text-teal-600 hover:underline flex items-center font-medium"
                                    >
                                        Buka Modul <ArrowUpRight className="w-3 h-3 ml-0.5" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="text-xs text-zinc-500">Uang Makan (Total Cair)</div>
                                    <div className="text-xl font-bold text-teal-700 dark:text-teal-400 font-mono mt-0.5">
                                        {formatRp(moduleTotals.meal_allowance?.total_disbursed)}
                                    </div>
                                </div>
                                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px]">
                                    <span className="text-amber-600 font-medium">
                                        Hold: <strong>{formatRp(moduleTotals.meal_allowance?.total_held)}</strong>
                                    </span>
                                    <span className="text-zinc-400 font-mono">
                                        {moduleTotals.meal_allowance?.total_batches} Batch
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Modul 7: Reward & Apresiasi */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm hover:border-amber-300 dark:hover:border-amber-700 transition">
                            <CardContent className="p-4 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                                        <Award className="w-4 h-4" />
                                    </div>
                                    <Link
                                        href={route('hcm.rewards.index')}
                                        className="text-[11px] text-amber-600 hover:underline flex items-center font-medium"
                                    >
                                        Buka Modul <ArrowUpRight className="w-3 h-3 ml-0.5" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="text-xs text-zinc-500">Reward & Apresiasi</div>
                                    <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">
                                        {moduleTotals.rewards?.total_rewards}{' '}
                                        <span className="text-xs font-normal text-zinc-400">Kegiatan</span>
                                    </div>
                                </div>
                                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px]">
                                    <span className="text-zinc-500">Anggaran: <strong>{formatRp(moduleTotals.rewards?.total_budget)}</strong></span>
                                    <span className="text-emerald-600 font-medium">Diserahkan: <strong>{moduleTotals.rewards?.delivered}</strong></span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Modul 8: Kalender & Agenda Acara */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 shadow-sm hover:border-blue-300 dark:hover:border-blue-700 transition">
                            <CardContent className="p-4 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                                        <Calendar className="w-4 h-4" />
                                    </div>
                                    <Link
                                        href={route('hcm.events.index')}
                                        className="text-[11px] text-blue-600 hover:underline flex items-center font-medium"
                                    >
                                        Buka Modul <ArrowUpRight className="w-3 h-3 ml-0.5" />
                                    </Link>
                                </div>
                                <div>
                                    <div className="text-xs text-zinc-500">Kalender Agenda Acara</div>
                                    <div className="text-xl font-bold text-zinc-900 dark:text-zinc-100 font-mono mt-0.5">
                                        {moduleTotals.events?.total_year}{' '}
                                        <span className="text-xs font-normal text-zinc-400">Tahun Ini</span>
                                    </div>
                                </div>
                                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px]">
                                    <span className="text-emerald-600 font-medium">Mendatang: <strong>{moduleTotals.events?.upcoming}</strong></span>
                                    <span className="text-rose-600">Libur: <strong>{moduleTotals.events?.holidays} Hari</strong></span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* 4. ALERT SENTRAL & ACTION CENTER (Dua Kolom Responsif) */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Kolom Kiri: Urgent Actions, Peringatan Absensi & Schedule (7 Cols) */}
                    <div className="lg:col-span-7 space-y-6">
                        {/* 1. Urgent & Action Needed (Probation H-7, Kontrak H-30 & Pending Approvals) */}
                        <Card className="border border-rose-200/80 dark:border-rose-900/50 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                            <CardHeader className="bg-rose-50/50 dark:bg-rose-950/20 p-4 border-b border-rose-100 dark:border-rose-900/40 flex flex-row items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="p-1.5 rounded-lg bg-rose-600 text-white">
                                        <AlertTriangle className="w-4 h-4" />
                                    </div>
                                    <div>
                                        <CardTitle className="text-sm font-bold text-rose-950 dark:text-rose-200">
                                            1. Urgent & Action Needed (Butuh Tindakan Segera)
                                        </CardTitle>
                                        <div className="text-[11px] text-rose-600 dark:text-rose-400">
                                            Masa probation H-7, jatuh tempo kontrak PKWT H-30, dan tiket persetujuan cuti/izin.
                                        </div>
                                    </div>
                                </div>
                                <Badge className="bg-rose-600 text-white font-mono text-xs">
                                    {totalUrgentIssues} Butuh Tindakan
                                </Badge>
                            </CardHeader>

                            <CardContent className="p-4 space-y-4">
                                {/* A. Masa Evaluasi Probation / Training */}
                                {probationAlerts && probationAlerts.length > 0 && (
                                    <div className="space-y-2">
                                        <div className="text-xs font-semibold text-rose-700 dark:text-rose-400 flex items-center justify-between">
                                            <span className="flex items-center gap-1.5">
                                                <ShieldAlert className="w-3.5 h-3.5" />
                                                A. Masa Evaluasi Probation / Training (H-7):
                                            </span>
                                            <span className="text-[10px] text-zinc-400 font-normal">
                                                {probationAlerts.length} Karyawan
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            {probationAlerts.map((p) => {
                                                const isCritical = p.days_remaining <= 3;
                                                return (
                                                    <div
                                                        key={p.employee_id}
                                                        className={`p-2.5 rounded-lg border text-xs flex items-center justify-between ${
                                                            isCritical
                                                                ? 'border-rose-300 bg-rose-50/70 dark:bg-rose-950/20'
                                                                : 'border-amber-200 bg-amber-50/40 dark:bg-amber-950/20'
                                                        }`}
                                                    >
                                                        <div>
                                                            <div className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                                {p.name}
                                                            </div>
                                                            <div className="text-[10px] text-zinc-500">
                                                                {p.employee_code} • {p.department}
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <Badge
                                                                className={`font-mono text-[10px] ${
                                                                    isCritical
                                                                        ? 'bg-rose-600 text-white'
                                                                        : 'bg-amber-500 text-white'
                                                                }`}
                                                            >
                                                                {p.days_remaining <= 0
                                                                    ? 'Hari Ini!'
                                                                    : `${p.days_remaining} Hari Lagi`}
                                                            </Badge>
                                                            <Link
                                                                href={route('hcm.employees.show', p.employee_id)}
                                                                className="block text-[10px] text-blue-600 hover:underline mt-1 font-medium"
                                                            >
                                                                Dossier ➔
                                                            </Link>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {/* B. Masa Berakhir Kontrak (PKWT) */}
                                {contractAlerts && contractAlerts.length > 0 && (
                                    <div className="space-y-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                        <div className="text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center justify-between">
                                            <span className="flex items-center gap-1.5">
                                                <Briefcase className="w-3.5 h-3.5" />
                                                B. Masa Berakhir Kontrak (PKWT H-30):
                                            </span>
                                            <span className="text-[10px] text-zinc-400 font-normal">
                                                {contractAlerts.length} Kontrak (Perlu Keputusan)
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            {contractAlerts.map((c) => {
                                                const isCritical = c.days_remaining <= 7;
                                                return (
                                                    <div
                                                        key={c.contract_id}
                                                        className={`p-2.5 rounded-lg border text-xs flex items-center justify-between ${
                                                            isCritical
                                                                ? 'border-rose-300 bg-rose-50/70 dark:bg-rose-950/20'
                                                                : 'border-amber-200 bg-amber-50/40 dark:bg-amber-950/20'
                                                        }`}
                                                    >
                                                        <div>
                                                            <div className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                                {c.name}
                                                            </div>
                                                            <div className="text-[10px] text-zinc-500 font-mono">
                                                                No: {c.contract_number} ({c.department})
                                                            </div>
                                                        </div>
                                                        <div className="text-right">
                                                            <Badge
                                                                className={`font-mono text-[10px] ${
                                                                    isCritical
                                                                        ? 'bg-rose-600 text-white'
                                                                        : 'bg-amber-500 text-white'
                                                                }`}
                                                            >
                                                                {c.days_remaining} Hari Lagi
                                                            </Badge>
                                                            <Link
                                                                href={route('hcm.employees.show', c.employee_id)}
                                                                className="block text-[10px] text-amber-700 dark:text-amber-400 hover:underline mt-1 font-medium"
                                                            >
                                                                Perpanjang ➔
                                                            </Link>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {/* C. Pending Approvals (Tiket Cuti / Izin Baru Menunggu Persetujuan Atasan/HR) */}
                                {pendingLeaves && pendingLeaves.length > 0 && (
                                    <div className="space-y-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                        <div className="text-xs font-semibold text-blue-700 dark:text-blue-400 flex items-center justify-between">
                                            <span className="flex items-center gap-1.5">
                                                <FileText className="w-3.5 h-3.5" />
                                                C. Pending Approvals ({pendingLeaves.length} Pengajuan Menunggu HR):
                                            </span>
                                            <Link
                                                href={route('hcm.leaves.index')}
                                                className="text-[11px] text-blue-600 hover:underline flex items-center font-medium"
                                            >
                                                Semua Pengajuan ➔
                                            </Link>
                                        </div>
                                        <div className="space-y-2">
                                            {pendingLeaves.map((leave) => (
                                                <div
                                                    key={leave.id}
                                                    className="p-2.5 rounded-lg border border-blue-200 dark:border-blue-900/50 bg-blue-50/40 dark:bg-blue-950/20 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                                                >
                                                    <div>
                                                        <div className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                            {leave.employee_name}{' '}
                                                            <span className="font-normal text-zinc-500">
                                                                ({leave.department}) – {leave.leave_type} ({leave.total_days} hari)
                                                            </span>
                                                        </div>
                                                        <div className="text-[10px] text-zinc-500 italic mt-0.5">
                                                            "{leave.reason}" • <span className="font-mono">Diajukan: {leave.applied_at}</span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2 self-end sm:self-center">
                                                        <Badge variant="outline" className="border-blue-300 text-blue-700 text-[10px]">
                                                            Menunggu HR
                                                        </Badge>
                                                        <Link
                                                            href={route('hcm.leaves.index')}
                                                            className="px-2 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-semibold transition"
                                                        >
                                                            Tinjau
                                                        </Link>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {(!probationAlerts || probationAlerts.length === 0) &&
                                 (!contractAlerts || contractAlerts.length === 0) &&
                                 (!pendingLeaves || pendingLeaves.length === 0) && (
                                    <div className="text-xs text-emerald-600 flex items-center gap-2 py-3">
                                        <CheckCircle2 className="w-4 h-4" />
                                        Seluruh masa probation, kontrak PKWT, dan persetujuan perizinan dalam status terkendali dan aman.
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* 2. Daily Schedule & Attendance (Agenda & Absensi Hari Ini) */}
                        <div className="space-y-4">
                            {/* B. Peringatan Absensi (Unexcused Absence / Mangkir - Red System) */}
                            {unexcusedAbsenceAlerts && unexcusedAbsenceAlerts.length > 0 && (
                                <Card className="border border-rose-300 dark:border-rose-900/60 bg-rose-50/40 dark:bg-rose-950/20 shadow-sm overflow-hidden">
                                    <CardHeader className="p-3.5 border-b border-rose-200 dark:border-rose-900/40 flex flex-row items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <div className="p-1 rounded bg-rose-600 text-white">
                                                <UserX className="w-3.5 h-3.5" />
                                            </div>
                                            <CardTitle className="text-xs font-bold text-rose-900 dark:text-rose-200">
                                                Peringatan Absensi (Unexcused Absence / Mangkir Hari Ini)
                                            </CardTitle>
                                        </div>
                                        <Link
                                            href={route('hcm.attendance.index')}
                                            className="text-[11px] font-semibold text-rose-700 hover:underline flex items-center"
                                        >
                                            Matriks Absensi ➔
                                        </Link>
                                    </CardHeader>
                                    <CardContent className="p-3.5">
                                        <div className="text-xs text-rose-800 dark:text-rose-300 mb-2 font-medium">
                                            {unexcusedAbsenceAlerts.length} Karyawan belum melakukan konfirmasi ketidakhadiran hari ini tanpa keterangan:
                                        </div>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            {unexcusedAbsenceAlerts.slice(0, 6).map((u) => (
                                                <div
                                                    key={u.employee_id}
                                                    className="p-2 rounded bg-white dark:bg-zinc-900 border border-rose-200 dark:border-rose-900/50 text-xs flex items-center justify-between"
                                                >
                                                    <div>
                                                        <div className="font-semibold text-zinc-900 dark:text-zinc-100">
                                                            {u.name}
                                                        </div>
                                                        <div className="text-[10px] text-zinc-500">
                                                            {u.department} • {u.position}
                                                        </div>
                                                    </div>
                                                    <Badge className="bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300 text-[10px] font-mono">
                                                        {u.status}
                                                    </Badge>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* A. Rekap Izin & Cuti Hari Ini (Scheduled Leave Alert - Blue System #2563EB) */}
                            <Card className="border border-blue-200 dark:border-blue-900/50 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                                <CardHeader className="bg-blue-50/40 dark:bg-blue-950/20 p-4 border-b border-blue-100 dark:border-blue-900/40 flex flex-row items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="p-1.5 rounded-lg bg-blue-600 text-white">
                                            <CalendarCheck className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                                Status Izin & Cuti Hari Ini ({today})
                                            </CardTitle>
                                            <div className="text-[11px] text-blue-600 dark:text-blue-400">
                                                Otomatis menampilkan siapa saja yang berizin hari ini berdasarkan tiket yang disetujui.
                                            </div>
                                        </div>
                                    </div>
                                    <Badge className="bg-blue-600 text-white font-mono text-xs">
                                        Total: {activeLeavesToday?.length || 0} Orang
                                    </Badge>
                                </CardHeader>

                                <CardContent className="p-4">
                                    {activeLeavesToday && activeLeavesToday.length > 0 ? (
                                        <div className="space-y-2">
                                            {activeLeavesToday.map((item, index) => (
                                                <div
                                                    key={item.id}
                                                    className="p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-zinc-50/50 dark:bg-zinc-800/30 hover:border-blue-300 transition"
                                                >
                                                    <div>
                                                        <div className="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                                            <span className="font-mono text-blue-600 font-bold">{index + 1}.</span>
                                                            <span>{item.employee_name}</span>
                                                            <span className="text-[11px] font-normal text-zinc-500">
                                                                ({item.department} – {item.position})
                                                            </span>
                                                        </div>
                                                        <div className="text-[11px] text-zinc-600 dark:text-zinc-400 mt-1 flex items-center gap-2">
                                                            <Badge variant="outline" className="text-[10px] border-blue-300 text-blue-700 bg-blue-50/50">
                                                                {item.leave_type}
                                                            </Badge>
                                                            <span>"{item.reason}"</span>
                                                        </div>
                                                    </div>
                                                    <div className="text-right self-end sm:self-center font-mono text-[10px] text-zinc-500">
                                                        <div>Diajukan: {item.applied_at}</div>
                                                        <div className="text-blue-600 font-medium">s.d {item.end_date}</div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-xs text-zinc-400 italic py-3 text-center">
                                            Tidak ada karyawan yang sedang cuti atau izin terjadwal hari ini.
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    {/* Kolom Kanan: Apresiasi, Ulang Tahun & Kalender Kegiatan (5 Cols) */}
                    <div className="lg:col-span-5 space-y-6">
                        {/* 3. Apresiasi & Milestone Sosial Karyawan (Green / Amber System) */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                            <CardHeader className="bg-gradient-to-r from-amber-500/10 to-emerald-500/10 p-4 border-b border-zinc-100 dark:border-zinc-800 flex flex-row items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="p-1.5 rounded-lg bg-amber-500 text-white">
                                        <PartyPopper className="w-4 h-4" />
                                    </div>
                                    <div>
                                        <CardTitle className="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                            Apresiasi & Milestone Sosial
                                        </CardTitle>
                                        <div className="text-[11px] text-zinc-500">
                                            Ulang tahun kelahiran & dedikasi masa kerja karyawan.
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent className="p-4 space-y-4">
                                {/* A. Pengingat Ulang Tahun Karyawan (H-3 Dinamis) */}
                                <div className="space-y-2">
                                    <div className="text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center justify-between">
                                        <span className="flex items-center gap-1.5">
                                            <Cake className="w-3.5 h-3.5 text-amber-600" />
                                            Pengingat Ulang Tahun Mendatang (H-3):
                                        </span>
                                        <span className="text-[10px] text-zinc-400 font-mono">
                                            {birthdayAlerts.length} Karyawan
                                        </span>
                                    </div>
                                    {birthdayAlerts && birthdayAlerts.length > 0 ? (
                                        <div className="space-y-1.5">
                                            {birthdayAlerts.map((b) => (
                                                <div
                                                    key={b.employee_id}
                                                    className="p-2.5 rounded-xl border border-amber-200/80 bg-amber-50/40 dark:bg-amber-950/20 text-xs flex items-center justify-between"
                                                >
                                                    <div className="flex items-center gap-2">
                                                        <Sparkles className="w-4 h-4 text-amber-500 animate-spin" />
                                                        <div>
                                                            <span className="font-bold text-zinc-900 dark:text-zinc-100">{b.name}</span>
                                                            <span className="text-[10px] text-zinc-500 ml-1">({b.department})</span>
                                                        </div>
                                                    </div>
                                                    <Badge className="bg-amber-500 text-white font-mono text-[10px]">
                                                        {b.is_today ? 'Hari Ini! 🎂' : `${b.days_remaining} hari lagi (${b.birth_date})`}
                                                    </Badge>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-xs text-zinc-400 italic py-1">
                                            Tidak ada karyawan yang berulang tahun dalam 3 hari ke depan.
                                        </div>
                                    )}
                                </div>

                                {/* B. Ulang Tahun Masa Kerja (Work Anniversary) */}
                                <div className="space-y-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                    <div className="text-xs font-semibold text-emerald-700 dark:text-emerald-400 flex items-center justify-between">
                                        <span className="flex items-center gap-1.5">
                                            <HeartHandshake className="w-3.5 h-3.5 text-emerald-600" />
                                            Ulang Tahun Masa Kerja (Work Anniversary):
                                        </span>
                                        <span className="text-[10px] text-zinc-400 font-mono">
                                            {anniversaryAlerts.length} Karyawan
                                        </span>
                                    </div>
                                    {anniversaryAlerts && anniversaryAlerts.length > 0 ? (
                                        <div className="space-y-1.5">
                                            {anniversaryAlerts.map((a) => (
                                                <div
                                                    key={a.employee_id}
                                                    className="p-2.5 rounded-xl border border-emerald-200/80 bg-emerald-50/40 dark:bg-emerald-950/20 text-xs flex items-center justify-between"
                                                >
                                                    <div>
                                                        <span className="font-bold text-zinc-900 dark:text-zinc-100">{a.name}</span>
                                                        <span className="text-[10px] text-zinc-500 ml-1">({a.department})</span>
                                                    </div>
                                                    <Badge className="bg-emerald-600 text-white font-mono text-[10px]">
                                                        Genap {a.years_of_service} Tahun Bekerja
                                                    </Badge>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-xs text-zinc-400 italic py-1">
                                            Tidak ada peringatan anniversary masa kerja dalam minggu ini.
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* 4. Company Events & Social Calendar (Agenda, Event & Undangan Sosial) */}
                        <Card className="border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                            <CardHeader className="p-4 border-b border-zinc-100 dark:border-zinc-800 flex flex-row items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="p-1.5 rounded-lg bg-blue-600 text-white">
                                        <CalendarDays className="w-4 h-4" />
                                    </div>
                                    <div>
                                        <CardTitle className="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                            Company Events & Social Calendar
                                        </CardTitle>
                                        <div className="text-[11px] text-zinc-500">
                                            Agenda perusahaan & undangan sosial karyawan.
                                        </div>
                                    </div>
                                </div>
                                <Link
                                    href={route('hcm.events.index')}
                                    className="text-xs text-blue-600 font-semibold hover:underline flex items-center"
                                >
                                    Buka Kalender <ChevronRight className="w-3.5 h-3.5 ml-0.5" />
                                </Link>
                            </CardHeader>

                            <CardContent className="p-4">
                                {upcomingEvents && upcomingEvents.length > 0 ? (
                                    <div className="space-y-2.5">
                                        {upcomingEvents.map((ev) => (
                                            <div
                                                key={ev.id}
                                                className="p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 hover:border-blue-300 transition text-xs space-y-1 bg-zinc-50/40 dark:bg-zinc-850"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <span
                                                        className="font-bold text-xs"
                                                        style={{ color: ev.color_code || '#2563eb' }}
                                                    >
                                                        {ev.title}
                                                    </span>
                                                    <Badge variant="outline" className="text-[10px]">
                                                        {ev.event_type}
                                                    </Badge>
                                                </div>
                                                <div className="flex flex-wrap items-center gap-2 text-[10px] text-zinc-500 font-mono">
                                                    <span>
                                                        {ev.start_date} {ev.start_date !== ev.end_date ? `s.d ${ev.end_date}` : ''}
                                                    </span>
                                                    {ev.start_time && <span>• Pukul {ev.start_time} WIB</span>}
                                                    {ev.location && (
                                                        <span className="flex items-center gap-0.5 text-zinc-600 dark:text-zinc-400">
                                                            <MapPin className="w-3 h-3" />
                                                            {ev.location}
                                                        </span>
                                                    )}
                                                </div>
                                                {ev.description && (
                                                    <p className="text-[11px] text-zinc-600 dark:text-zinc-400 pt-1 line-clamp-1 italic">
                                                        "{ev.description}"
                                                    </p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-xs text-zinc-400 italic py-3 text-center">
                                        Belum ada agenda kegiatan dalam waktu dekat.
                                    </div>
                                )}

                                <div className="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-center">
                                    <Link
                                        href={route('hcm.events.index')}
                                        className="inline-flex items-center gap-1.5 text-xs text-blue-600 font-semibold hover:underline"
                                    >
                                        <PlusCircle className="w-3.5 h-3.5" />
                                        + Tambah Agenda Baru / Undangan Acara
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
