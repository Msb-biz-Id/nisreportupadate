import React, { useState, useMemo } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import {
    Building2,
    Clock,
    UtensilsCrossed,
    SlidersHorizontal,
    UploadCloud,
    Trash2,
    Eye,
    Save,
    Calculator,
    CheckCircle2,
    Info,
    Mail,
    Phone,
    Globe,
    Share2,
    FileText,
    Calendar,
    ArrowRight,
    Cloud,
    HardDrive,
    FolderTree,
    AlertTriangle,
    RefreshCw,
    Key,
    ExternalLink,
    PenTool,
    Stamp,
    CheckSquare,
} from 'lucide-react';
import axios from 'axios';

export default function HcmSettingsIndex({ profile = {}, overtime = {}, meal_allowance = {}, payroll = {}, storage = {} }) {
    const [activeTab, setActiveTab] = useState('profile');
    const [logoPreview, setLogoPreview] = useState(profile.logo_url || null);
    const [signaturePreview, setSignaturePreview] = useState(profile.signature_url || null);
    const [stampPreview, setStampPreview] = useState(profile.stamp_url || null);

    // Form 1: Profil Divisi, Instansi, Kop Dokumen & Penandatangan Otomatis
    const profileForm = useForm({
        company_name: profile.company_name || '',
        division_name: profile.division_name || 'Divisi Human Capital Management',
        company_tagline: profile.company_tagline || '',
        company_address: profile.company_address || '',
        company_city: profile.company_city || 'Klaten',
        company_email: profile.company_email || '',
        company_phone: profile.company_phone || '',
        company_website: profile.company_website || '',
        company_socials: profile.company_socials || '',
        kop_header_line1: profile.kop_header_line1 || '',
        kop_header_line2: profile.kop_header_line2 || '',
        document_footer_text: profile.document_footer_text || '',
        document_footer_disclaimer: profile.document_footer_disclaimer || '',

        // Penandatangan Otomatis & TTD Digital
        signer_name: profile.signer_name || 'Ahmad Fauzi, S.Psi., CHRP',
        signer_role: profile.signer_role || 'Head of Human Capital Management',
        signer_nik: profile.signer_nik || 'HCM-2021-001',
        show_signature_on_pdf: profile.show_signature_on_pdf ?? true,
        show_stamp_on_pdf: profile.show_stamp_on_pdf ?? true,

        logo: null,
        remove_logo: false,
        signature: null,
        remove_signature: false,
        stamp: null,
        remove_stamp: false,
    });

    // Form 2: Biaya Lembur Dinamis
    const overtimeForm = useForm({
        weekday_hourly_rate: overtime.weekday_hourly_rate ?? 10000,
        weekday_first_half_rate: overtime.weekday_first_half_rate ?? 5000,
        weekend_hourly_rate: overtime.weekend_hourly_rate ?? 15000,
        weekend_first_half_rate: overtime.weekend_first_half_rate ?? 10000,
        coa_code: overtime.coa_code || '5-50100',
        coa_name: overtime.coa_name || 'Beban Upah Lembur Karyawan Pabrik',
    });

    // Form 3: Uang Makan & Payroll Cut-off
    const mealForm = useForm({
        monthly_rate: meal_allowance.monthly_rate ?? 250000,
        alpha_deduction_rate: meal_allowance.alpha_deduction_rate ?? 25000,
        half_day_deduction_rate: meal_allowance.half_day_deduction_rate ?? 12500,
        max_late_tolerance: meal_allowance.max_late_tolerance ?? 3,
        max_permit_bonus_limit: meal_allowance.max_permit_bonus_limit ?? 2,
        coa_code: meal_allowance.coa_code || '5-50200',
        coa_name: meal_allowance.coa_name || 'Beban Uang Makan Karyawan Pabrik',
        cutoff_day: payroll.cutoff_day ?? 25,
    });

    // Interactive Simulator State for Overtime
    const [simHours, setSimHours] = useState(2.5);
    const [simIsWeekend, setSimIsWeekend] = useState(false);

    const calculatedSimAmount = useMemo(() => {
        const hours = parseFloat(simHours) || 0;
        const rates = {
            hourly: simIsWeekend
                ? Number(overtimeForm.data.weekend_hourly_rate)
                : Number(overtimeForm.data.weekday_hourly_rate),
            firstHalf: simIsWeekend
                ? Number(overtimeForm.data.weekend_first_half_rate)
                : Number(overtimeForm.data.weekday_first_half_rate),
        };

        if (hours < 0.5) return 0;
        if (hours === 0.5) return rates.firstHalf;
        return Math.round(hours * rates.hourly);
    }, [simHours, simIsWeekend, overtimeForm.data]);

    const handleLogoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            profileForm.setData((prev) => ({
                ...prev,
                logo: file,
                remove_logo: false,
            }));
            const reader = new FileReader();
            reader.onloadend = () => {
                setLogoPreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleRemoveLogo = () => {
        profileForm.setData((prev) => ({
            ...prev,
            logo: null,
            remove_logo: true,
        }));
        setLogoPreview(null);
    };

    const handleSignatureChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            profileForm.setData((prev) => ({
                ...prev,
                signature: file,
                remove_signature: false,
            }));
            const reader = new FileReader();
            reader.onloadend = () => {
                setSignaturePreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleRemoveSignature = () => {
        profileForm.setData((prev) => ({
            ...prev,
            signature: null,
            remove_signature: true,
        }));
        setSignaturePreview(null);
    };

    const handleStampChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            profileForm.setData((prev) => ({
                ...prev,
                stamp: file,
                remove_stamp: false,
            }));
            const reader = new FileReader();
            reader.onloadend = () => {
                setStampPreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleRemoveStamp = () => {
        profileForm.setData((prev) => ({
            ...prev,
            stamp: null,
            remove_stamp: true,
        }));
        setStampPreview(null);
    };

    const submitProfile = (e) => {
        e.preventDefault();
        profileForm.post(route('hcm.settings.profile.update'), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const submitOvertime = (e) => {
        e.preventDefault();
        overtimeForm.post(route('hcm.settings.overtime.update'), {
            preserveScroll: true,
        });
    };

    const submitMeal = (e) => {
        e.preventDefault();
        mealForm.post(route('hcm.settings.meal-allowance.update'), {
            preserveScroll: true,
        });
    };

    // Form 4: Google Drive & Cloud Storage
    const storageForm = useForm({
        drive_sync_enabled: storage.drive_sync_enabled ?? false,
        root_folder_id: storage.root_folder_id || '',
        auto_unlink_local: storage.auto_unlink_local ?? false,
        service_account_file: null,
        service_account_raw: '',
        clear_service_account: false,
    });

    const [testState, setTestState] = useState({ loading: false, result: null });

    const handleTestConnection = async () => {
        setTestState({ loading: true, result: null });
        try {
            const res = await axios.post(route('hcm.settings.storage.test-connection'));
            setTestState({ loading: false, result: res.data });
        } catch (err) {
            setTestState({
                loading: false,
                result: {
                    success: false,
                    message: err.response?.data?.message || 'Gagal menghubungi server Google Drive.',
                },
            });
        }
    };

    const submitStorage = (e) => {
        e.preventDefault();
        storageForm.post(route('hcm.settings.storage.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                storageForm.reset('service_account_file', 'service_account_raw');
            },
        });
    };

    return (
        <AppLayout
            title="Pengaturan Kepegawaian (HCM)"
            header={
                <div className="flex items-center gap-2 min-w-0">
                    <SlidersHorizontal className="h-4 w-4 text-indigo-600 shrink-0" />
                    <span className="text-sm font-semibold truncate text-zinc-900 dark:text-zinc-100">
                        Pengaturan Modul Kepegawaian
                    </span>
                </div>
            }
        >
            <Head title="Pengaturan Kepegawaian (HCM) - Profil & Biaya Lembur" />

            <div className="space-y-6">
                {/* Header Sub-Modul */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300">
                                Sub-Modul HCM
                            </span>
                            <span className="text-xs text-zinc-400">&bull;</span>
                            <span className="text-xs text-zinc-500 dark:text-zinc-400">Konfigurasi Sentral Kepegawaian</span>
                        </div>
                        <h1 className="text-lg sm:text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <SlidersHorizontal className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                            <span>Pengaturan Modul Kepegawaian</span>
                        </h1>
                        <p className="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                            Atur profil instansi, logo, identitas kop surat, footer dokumen resmi, serta konfigurasi tarif lembur dinamis & tunjangan makan.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href={route('hcm.dashboard.index')}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 bg-white border border-zinc-300 rounded-lg hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 dark:hover:bg-zinc-750 transition"
                        >
                            Ke Dashboard HCM
                        </Link>
                    </div>
                </div>

                {/* Tab Navigation Navigation */}
                <div className="flex items-center space-x-1 border-b border-zinc-200 dark:border-zinc-800 pb-px overflow-x-auto">
                    <button
                        type="button"
                        onClick={() => setActiveTab('profile')}
                        className={`flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap ${
                            activeTab === 'profile'
                                ? 'border-red-600 text-red-600 dark:border-red-500 dark:text-red-400'
                                : 'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200'
                        }`}
                    >
                        <Building2 className="h-4 w-4" />
                        Profil Divisi & Kop Surat Resmi
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTab('overtime')}
                        className={`flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap ${
                            activeTab === 'overtime'
                                ? 'border-red-600 text-red-600 dark:border-red-500 dark:text-red-400'
                                : 'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200'
                        }`}
                    >
                        <Clock className="h-4 w-4" />
                        Biaya Lembur Dinamis
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTab('meal')}
                        className={`flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap ${
                            activeTab === 'meal'
                                ? 'border-red-600 text-red-600 dark:border-red-500 dark:text-red-400'
                                : 'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200'
                        }`}
                    >
                        <UtensilsCrossed className="h-4 w-4" />
                        Uang Makan & Cut-Off Payroll
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTab('storage')}
                        className={`flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap ${
                            activeTab === 'storage'
                                ? 'border-red-600 text-red-600 dark:border-red-500 dark:text-red-400'
                                : 'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200'
                        }`}
                    >
                        <Cloud className="h-4 w-4" />
                        Google Drive & Cloud Storage
                    </button>
                </div>

                {/* TAB 1: PROFIL DIVISI, INSTANSI, PENANDATANGAN OTOMATIS & KOP SURAT BAKU */}
                {activeTab === 'profile' && (
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <div className="lg:col-span-7 space-y-6">
                            <form onSubmit={submitProfile} className="space-y-6">
                                {/* CARD 1: IDENTITAS DIVISI HCM & PERUSAHAAN */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <Building2 className="h-4 w-4 text-red-600" />
                                            Profil Divisi HCM & Identitas Entitas
                                        </CardTitle>
                                        <CardDescription>
                                            Identitas resmi divisi kepegawaian dan perusahaan yang dicantumkan pada kop surat, formulir, dan berkas administrasi legal.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="division_name">Nama Divisi Kepegawaian (HCM) *</Label>
                                                <Input
                                                    id="division_name"
                                                    value={profileForm.data.division_name}
                                                    onChange={(e) => profileForm.setData('division_name', e.target.value)}
                                                    placeholder="Contoh: Divisi Human Capital Management"
                                                    required
                                                />
                                                {profileForm.errors.division_name && (
                                                    <p className="text-xs text-red-500">{profileForm.errors.division_name}</p>
                                                )}
                                            </div>

                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="company_name">Nama Perusahaan / Holding Legal *</Label>
                                                <Input
                                                    id="company_name"
                                                    value={profileForm.data.company_name}
                                                    onChange={(e) => profileForm.setData('company_name', e.target.value)}
                                                    placeholder="Contoh: NIS Group / PT Natural Indah Sukses"
                                                    required
                                                />
                                                {profileForm.errors.company_name && (
                                                    <p className="text-xs text-red-500">{profileForm.errors.company_name}</p>
                                                )}
                                            </div>

                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="company_tagline">Tagline / Sub-Identitas</Label>
                                                <Input
                                                    id="company_tagline"
                                                    value={profileForm.data.company_tagline}
                                                    onChange={(e) => profileForm.setData('company_tagline', e.target.value)}
                                                    placeholder="Contoh: People, Culture & Organizational Development"
                                                />
                                            </div>

                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="company_address">Alamat Lengkap Kantor / Pabrik *</Label>
                                                <Textarea
                                                    id="company_address"
                                                    rows={2}
                                                    value={profileForm.data.company_address}
                                                    onChange={(e) => profileForm.setData('company_address', e.target.value)}
                                                    placeholder="Jl. Raya Utama Kawasan Industri, RT/RW, Kecamatan, Kabupaten/Kota"
                                                    required
                                                />
                                                {profileForm.errors.company_address && (
                                                    <p className="text-xs text-red-500">{profileForm.errors.company_address}</p>
                                                )}
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="company_city">Kota Domisili Surat</Label>
                                                <Input
                                                    id="company_city"
                                                    value={profileForm.data.company_city}
                                                    onChange={(e) => profileForm.setData('company_city', e.target.value)}
                                                    placeholder="Contoh: Klaten"
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="company_phone">No. Telepon / Hotline HRD</Label>
                                                <Input
                                                    id="company_phone"
                                                    value={profileForm.data.company_phone}
                                                    onChange={(e) => profileForm.setData('company_phone', e.target.value)}
                                                    placeholder="Contoh: 0812-3456-7890"
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="company_email">Email Resmi Divisi HCM</Label>
                                                <Input
                                                    id="company_email"
                                                    type="email"
                                                    value={profileForm.data.company_email}
                                                    onChange={(e) => profileForm.setData('company_email', e.target.value)}
                                                    placeholder="Contoh: hrd@nisgroup.co.id"
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="company_website">Website Resmi</Label>
                                                <Input
                                                    id="company_website"
                                                    value={profileForm.data.company_website}
                                                    onChange={(e) => profileForm.setData('company_website', e.target.value)}
                                                    placeholder="Contoh: https://nisgroup.co.id"
                                                />
                                            </div>

                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="company_socials">Akun Media Sosial / Saluran Komunikasi</Label>
                                                <Input
                                                    id="company_socials"
                                                    value={profileForm.data.company_socials}
                                                    onChange={(e) => profileForm.setData('company_socials', e.target.value)}
                                                    placeholder="Contoh: @nisgroup.apparel (IG) | LinkedIn: NIS Group"
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* CARD 2: PEJABAT PENANDATANGAN RESMI & TTD DIGITAL (AUTOMATIC SIGNER) */}
                                <Card className="border-indigo-200 dark:border-indigo-900/50 bg-gradient-to-b from-indigo-50/20 to-transparent">
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center gap-2 text-indigo-950 dark:text-indigo-200">
                                            <PenTool className="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                            Pejabat Penandatangan Resmi & Tanda Tangan Digital
                                        </CardTitle>
                                        <CardDescription>
                                            Nama pejabat penandatangan otomatis tercetak di lembar pengesahan (Paklaring, Surat Rekomendasi, Laporan Presensi, dsb).
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="signer_name">Nama Lengkap Pejabat Penandatangan *</Label>
                                                <Input
                                                    id="signer_name"
                                                    value={profileForm.data.signer_name}
                                                    onChange={(e) => profileForm.setData('signer_name', e.target.value)}
                                                    placeholder="Contoh: Ahmad Fauzi, S.Psi., CHRP"
                                                    required
                                                />
                                                <p className="text-[11px] text-zinc-500">
                                                    Nama akan dicetak tebal dengan garis bawah resmi: <strong><u>Ahmad Fauzi, S.Psi., CHRP</u></strong>
                                                </p>
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="signer_role">Jabatan Resmi Pejabat *</Label>
                                                <Input
                                                    id="signer_role"
                                                    value={profileForm.data.signer_role}
                                                    onChange={(e) => profileForm.setData('signer_role', e.target.value)}
                                                    placeholder="Contoh: Head of Human Capital Management"
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="signer_nik">NIK / NIP Pejabat</Label>
                                                <Input
                                                    id="signer_nik"
                                                    value={profileForm.data.signer_nik}
                                                    onChange={(e) => profileForm.setData('signer_nik', e.target.value)}
                                                    placeholder="Contoh: HCM-2021-001"
                                                />
                                            </div>
                                        </div>

                                        {/* UPLOAD TTD DIGITAL & STEMPEL */}
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                                            {/* UPLOAD TTD DIGITAL */}
                                            <div className="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <Label className="text-xs font-semibold flex items-center gap-1.5">
                                                        <PenTool className="h-3.5 w-3.5 text-indigo-600" />
                                                        Tanda Tangan Digital (PNG)
                                                    </Label>
                                                    <Badge variant="outline" className="text-[10px]">
                                                        {signaturePreview ? 'Aktif' : 'Kosong'}
                                                    </Badge>
                                                </div>

                                                <div className="h-20 border border-dashed border-zinc-300 dark:border-zinc-700 rounded-md flex items-center justify-center bg-zinc-50 dark:bg-zinc-950 overflow-hidden">
                                                    {signaturePreview ? (
                                                        <img
                                                            src={signaturePreview}
                                                            alt="TTD Preview"
                                                            className="max-h-16 max-w-full object-contain p-1"
                                                        />
                                                    ) : (
                                                        <span className="text-[11px] text-zinc-400 italic">
                                                            Belum ada TTD digital
                                                        </span>
                                                    )}
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <input
                                                        type="file"
                                                        id="signature_file"
                                                        accept="image/*"
                                                        onChange={handleSignatureChange}
                                                        className="hidden"
                                                    />
                                                    <label
                                                        htmlFor="signature_file"
                                                        className="flex-1 text-center py-1.5 px-2 text-xs font-medium bg-zinc-800 hover:bg-zinc-900 text-white rounded cursor-pointer transition"
                                                    >
                                                        Upload TTD (PNG)
                                                    </label>
                                                    {signaturePreview && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={handleRemoveSignature}
                                                            className="text-xs text-red-600 hover:bg-red-50"
                                                        >
                                                            Hapus
                                                        </Button>
                                                    )}
                                                </div>
                                                <p className="text-[10px] text-zinc-500">
                                                    Disarankan format PNG transparan tanpa background.
                                                </p>
                                            </div>

                                            {/* UPLOAD STEMPEL CAP */}
                                            <div className="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <Label className="text-xs font-semibold flex items-center gap-1.5">
                                                        <Stamp className="h-3.5 w-3.5 text-indigo-600" />
                                                        Stempel / Cap Resmi Divisi (PNG)
                                                    </Label>
                                                    <Badge variant="outline" className="text-[10px]">
                                                        {stampPreview ? 'Aktif' : 'Kosong'}
                                                    </Badge>
                                                </div>

                                                <div className="h-20 border border-dashed border-zinc-300 dark:border-zinc-700 rounded-md flex items-center justify-center bg-zinc-50 dark:bg-zinc-950 overflow-hidden">
                                                    {stampPreview ? (
                                                        <img
                                                            src={stampPreview}
                                                            alt="Stempel Preview"
                                                            className="max-h-16 max-w-full object-contain p-1"
                                                        />
                                                    ) : (
                                                        <span className="text-[11px] text-zinc-400 italic">
                                                            Belum ada stempel cap
                                                        </span>
                                                    )}
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <input
                                                        type="file"
                                                        id="stamp_file"
                                                        accept="image/*"
                                                        onChange={handleStampChange}
                                                        className="hidden"
                                                    />
                                                    <label
                                                        htmlFor="stamp_file"
                                                        className="flex-1 text-center py-1.5 px-2 text-xs font-medium bg-zinc-800 hover:bg-zinc-900 text-white rounded cursor-pointer transition"
                                                    >
                                                        Upload Cap (PNG)
                                                    </label>
                                                    {stampPreview && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={handleRemoveStamp}
                                                            className="text-xs text-red-600 hover:bg-red-50"
                                                        >
                                                            Hapus
                                                        </Button>
                                                    )}
                                                </div>
                                                <p className="text-[10px] text-zinc-500">
                                                    Cap akan dirender natural berdampingan dengan TTD.
                                                </p>
                                            </div>
                                        </div>

                                        {/* TOGGLE OPTIONS */}
                                        <div className="space-y-2 pt-2">
                                            <label className="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={profileForm.data.show_signature_on_pdf}
                                                    onChange={(e) => profileForm.setData('show_signature_on_pdf', e.target.checked)}
                                                    className="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span>Tampilkan Tanda Tangan Digital secara otomatis pada berkas PDF</span>
                                            </label>

                                            <label className="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={profileForm.data.show_stamp_on_pdf}
                                                    onChange={(e) => profileForm.setData('show_stamp_on_pdf', e.target.checked)}
                                                    className="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span>Tampilkan Stempel / Cap Resmi secara otomatis pada berkas PDF</span>
                                            </label>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* CARD 3: KOP SURAT & FOOTER */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <FileText className="h-4 w-4 text-red-600" />
                                            Teks Kop Surat & Footer Dokumen Legal
                                        </CardTitle>
                                        <CardDescription>
                                            Kustomisasi teks header kop surat dan klausul catatan kaki dokumen cetak PDF.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="kop_header_line1">Baris Judul Kop Dokumen (Line 1)</Label>
                                            <Input
                                                id="kop_header_line1"
                                                value={profileForm.data.kop_header_line1}
                                                onChange={(e) => profileForm.setData('kop_header_line1', e.target.value)}
                                                placeholder="Contoh: DIVISI HUMAN CAPITAL & MANAJEMEN OPERASIONAL"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="kop_header_line2">Sub-Baris Legalitas / KBLI / Izin (Line 2)</Label>
                                            <Input
                                                id="kop_header_line2"
                                                value={profileForm.data.kop_header_line2}
                                                onChange={(e) => profileForm.setData('kop_header_line2', e.target.value)}
                                                placeholder="Contoh: No. Izin KBLI 14111 / 14120 - Manajemen SDM Terpadu"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="document_footer_text">Catatan Kaki Dokumen (Footer Line)</Label>
                                            <Textarea
                                                id="document_footer_text"
                                                rows={2}
                                                value={profileForm.data.document_footer_text}
                                                onChange={(e) => profileForm.setData('document_footer_text', e.target.value)}
                                                placeholder="Contoh: Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi."
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="document_footer_disclaimer">Klausul Disclaimer Keabsahan Dokumen</Label>
                                            <Textarea
                                                id="document_footer_disclaimer"
                                                rows={2}
                                                value={profileForm.data.document_footer_disclaimer}
                                                onChange={(e) => profileForm.setData('document_footer_disclaimer', e.target.value)}
                                                placeholder="Contoh: Keabsahan dokumen dapat diverifikasi langsung melalui portal HCM atau QR code tertera."
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* CARD 4: LOGO RESMI */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <UploadCloud className="h-4 w-4 text-red-600" />
                                            Logo Resmi Kop Dokumen HCM
                                        </CardTitle>
                                        <CardDescription>
                                            Upload file logo perusahaan (PNG, JPG, SVG, WebP, maks. 3MB) untuk disematkan di kop surat dan berkas PDF.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="flex flex-col sm:flex-row items-center gap-5 p-4 border border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl bg-zinc-50/50 dark:bg-zinc-900/50">
                                            <div className="w-24 h-24 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                                {logoPreview ? (
                                                    <img
                                                        src={logoPreview}
                                                        alt="Logo Preview"
                                                        className="w-full h-full object-contain p-2"
                                                    />
                                                ) : (
                                                    <span className="text-2xl font-bold text-zinc-400">
                                                        {profileForm.data.company_name?.charAt(0) || 'N'}
                                                    </span>
                                                )}
                                            </div>

                                            <div className="space-y-2 flex-1 text-center sm:text-left">
                                                <input
                                                    type="file"
                                                    id="logo"
                                                    accept="image/*"
                                                    onChange={handleLogoChange}
                                                    className="hidden"
                                                />
                                                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                                    <label
                                                        htmlFor="logo"
                                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white rounded-lg cursor-pointer transition shadow-sm"
                                                    >
                                                        <UploadCloud className="h-3.5 w-3.5" />
                                                        Pilih File Logo
                                                    </label>

                                                    {logoPreview && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={handleRemoveLogo}
                                                            className="text-xs text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5 mr-1" />
                                                            Hapus Logo
                                                        </Button>
                                                    )}
                                                </div>
                                                <p className="text-xs text-zinc-500">
                                                    Format disarankan: PNG transparan dengan resolusi minimal 300x120px untuk hasil cetak tajam.
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <div className="flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={profileForm.processing}
                                        className="bg-red-600 hover:bg-red-700 text-white min-w-44 shadow-sm"
                                    >
                                        <Save className="h-4 w-4 mr-2" />
                                        {profileForm.processing ? 'Menyimpan...' : 'Simpan Profil & Penandatangan'}
                                    </Button>
                                </div>
                            </form>
                        </div>

                        {/* LIVE PREVIEW KOP SURAT BAKU & BLOK TANDA TANGAN */}
                        <div className="lg:col-span-5 space-y-4">
                            <div className="sticky top-6">
                                <Card className="border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-md">
                                    <CardHeader className="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                                        <CardTitle className="text-sm font-semibold flex items-center justify-between">
                                            <span className="flex items-center gap-1.5 text-zinc-800 dark:text-zinc-200">
                                                <Eye className="h-4 w-4 text-emerald-600" />
                                                Live Preview Surat & TTD Resmi
                                            </span>
                                            <Badge variant="outline" className="text-[10px] uppercase font-mono">
                                                Standar Baku Indonesia
                                            </Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-5 space-y-6 text-zinc-800 dark:text-zinc-200 font-sans">
                                        {/* Mock Paper Surat Resmi */}
                                        <div className="p-4 bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-800 rounded-lg shadow-sm">
                                            {/* KOP SURAT BAKU DENGAN GARIS GANDA */}
                                            <div className="pb-2">
                                                <table className="w-full border-collapse">
                                                    <tbody>
                                                        <tr>
                                                            <td className="w-14 align-middle pr-3">
                                                                <div className="w-12 h-12 rounded border border-zinc-200 dark:border-zinc-700 flex items-center justify-center bg-zinc-50 dark:bg-zinc-900 overflow-hidden">
                                                                    {logoPreview ? (
                                                                        <img
                                                                            src={logoPreview}
                                                                            alt="Logo"
                                                                            className="w-full h-full object-contain p-1"
                                                                        />
                                                                    ) : (
                                                                        <span className="text-lg font-bold text-zinc-600">
                                                                            {profileForm.data.company_name?.charAt(0) || 'N'}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="align-middle text-center">
                                                                <h3 className="font-extrabold text-xs uppercase tracking-wider text-zinc-900 dark:text-zinc-100">
                                                                    {profileForm.data.company_name || 'NAMA PERUSAHAAN LEGAL'}
                                                                </h3>
                                                                <p className="text-[10.5px] font-bold text-indigo-900 dark:text-indigo-400 uppercase tracking-tight mt-0.5">
                                                                    {profileForm.data.division_name || 'DIVISI HUMAN CAPITAL MANAGEMENT'}
                                                                </p>
                                                                <p className="text-[9px] text-zinc-600 dark:text-zinc-400 line-clamp-2 mt-0.5">
                                                                    {profileForm.data.company_address || 'Alamat Lengkap Perusahaan / Kawasan Pabrik'}
                                                                </p>
                                                                <p className="text-[8px] text-zinc-500 dark:text-zinc-500 mt-0.5">
                                                                    {[
                                                                        profileForm.data.company_phone && `Tel: ${profileForm.data.company_phone}`,
                                                                        profileForm.data.company_email && `Email: ${profileForm.data.company_email}`,
                                                                        profileForm.data.company_website && `Web: ${profileForm.data.company_website}`,
                                                                    ].filter(Boolean).join(' | ')}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>

                                                {/* GARIS GANDA BAKU RESMI INDONESIA */}
                                                <div className="w-full mt-2.5">
                                                    <div className="border-t-[2.5px] border-zinc-900 dark:border-zinc-100 w-full"></div>
                                                    <div className="border-t-[0.8px] border-zinc-900 dark:border-zinc-100 w-full mt-0.5"></div>
                                                </div>
                                            </div>

                                            {/* DUMMY BADAN SURAT */}
                                            <div className="py-3 space-y-1.5 text-center border-b border-zinc-100 dark:border-zinc-800">
                                                <span className="text-[10.5px] font-extrabold uppercase tracking-wider text-zinc-800 dark:text-zinc-200 underline underline-offset-2">
                                                    SURAT KETERANGAN PENGALAMAN KERJA
                                                </span>
                                                <p className="text-[8.5px] text-zinc-500 font-mono">
                                                    Nomor: SKP/042/10/{new Date().getFullYear()}
                                                </p>
                                                <div className="py-3 px-2 text-[9px] text-zinc-500 italic text-left bg-zinc-50/60 dark:bg-zinc-900/40 rounded">
                                                    Yang bertanda tangan di bawah ini menerangkan bahwa nama karyawan yang bersangkutan telah bekerja dengan dedikasi dan integritas yang baik...
                                                </div>
                                            </div>

                                            {/* BLOK TANDA TANGAN OTOMATIS */}
                                            <div className="flex justify-end text-right pt-3">
                                                <div className="text-center min-w-[190px]">
                                                    <p className="text-[9px] text-zinc-600 dark:text-zinc-400">
                                                        {profileForm.data.company_city || 'Klaten'}, {new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                                                    </p>
                                                    <p className="text-[9px] font-semibold text-zinc-700 dark:text-zinc-300 mt-0.5">
                                                        {profileForm.data.signer_role || 'Head of Human Capital Management'}
                                                    </p>

                                                    {/* WADAH TTD & STEMPEL */}
                                                    <div className="relative h-14 w-40 mx-auto my-1 flex items-center justify-center">
                                                        {/* Stempel Cap Preview */}
                                                        {profileForm.data.show_stamp_on_pdf && stampPreview && (
                                                            <div className="absolute left-1 top-0 w-14 h-14 z-0 opacity-80 pointer-events-none">
                                                                <img
                                                                    src={stampPreview}
                                                                    alt="Stempel"
                                                                    className="w-full h-full object-contain"
                                                                />
                                                            </div>
                                                        )}

                                                        {/* TTD Digital Preview */}
                                                        {profileForm.data.show_signature_on_pdf && signaturePreview ? (
                                                            <div className="relative z-10 max-h-12 max-w-full">
                                                                <img
                                                                    src={signaturePreview}
                                                                    alt="TTD"
                                                                    className="max-h-12 max-w-36 object-contain"
                                                                />
                                                            </div>
                                                        ) : (
                                                            <div className="h-10"></div>
                                                        )}
                                                    </div>

                                                    {/* Nama Pejabat Bergaris Bawah Baku */}
                                                    <p className="text-[9.5px] font-extrabold text-zinc-900 dark:text-zinc-100 underline decoration-zinc-900 dark:decoration-zinc-100">
                                                        {profileForm.data.signer_name || '( ________________________ )'}
                                                    </p>
                                                    {profileForm.data.signer_nik && (
                                                        <p className="text-[8px] text-zinc-500 mt-0.5">
                                                            NIK. {profileForm.data.signer_nik}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            {/* FOOTER CATATAN KAKI */}
                                            <div className="mt-4 pt-2 border-t border-zinc-200 dark:border-zinc-800 text-[7.5px] text-zinc-400 text-center space-y-0.5">
                                                <p>{profileForm.data.document_footer_text || 'Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi.'}</p>
                                                <p className="italic text-zinc-500">
                                                    {profileForm.data.document_footer_disclaimer || 'Keabsahan dokumen dapat diverifikasi langsung melalui portal HCM.'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="text-xs text-zinc-500 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800/50 p-3 rounded-lg flex items-start gap-2">
                                            <Info className="h-4 w-4 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" />
                                            <p>
                                                Format surat di atas memenuhi kaidah standar tata naskah dinas resmi Republik Indonesia dengan garis ganda (*official double-border*) serta tanda tangan sah pejabat HCM.
                                            </p>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                )}

                {/* TAB 2: BIAYA LEMBUR DINAMIS */}
                {activeTab === 'overtime' && (
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <div className="lg:col-span-7 space-y-6">
                            <form onSubmit={submitOvertime} className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <Clock className="h-4 w-4 text-red-600" />
                                            Konfigurasi Tarif Lembur Dinamis
                                        </CardTitle>
                                        <CardDescription>
                                            Atur nominal tarif dasar lembur per jam dan tarif tier 30 menit pertama untuk hari kerja maupun hari libur akhir pekan.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-5">
                                        <div className="p-3.5 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 rounded-lg text-xs text-blue-800 dark:text-blue-300 flex items-start gap-2">
                                            <Info className="h-4 w-4 text-blue-600 shrink-0 mt-0.5" />
                                            <p>
                                                Formula: Lembur &lt; 0.5 jam = Rp 0. Lembur tepat 0.5 jam = Tarif 30 Menit Pertama. Lembur &gt; 0.5 jam = Jam &times; Tarif Per Jam.
                                            </p>
                                        </div>

                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            {/* Hari Kerja */}
                                            <div className="space-y-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                                <div className="flex items-center gap-2">
                                                    <span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                                    <h4 className="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                                        Lembur Hari Kerja (Weekday)
                                                    </h4>
                                                </div>

                                                <div className="space-y-1.5">
                                                    <Label htmlFor="weekday_hourly_rate">Tarif Dasar Per Jam (Rp) *</Label>
                                                    <Input
                                                        id="weekday_hourly_rate"
                                                        type="number"
                                                        min="0"
                                                        step="500"
                                                        value={overtimeForm.data.weekday_hourly_rate}
                                                        onChange={(e) => overtimeForm.setData('weekday_hourly_rate', e.target.value)}
                                                        required
                                                    />
                                                </div>

                                                <div className="space-y-1.5">
                                                    <Label htmlFor="weekday_first_half_rate">Tarif 30 Menit Pertama (Rp) *</Label>
                                                    <Input
                                                        id="weekday_first_half_rate"
                                                        type="number"
                                                        min="0"
                                                        step="500"
                                                        value={overtimeForm.data.weekday_first_half_rate}
                                                        onChange={(e) => overtimeForm.setData('weekday_first_half_rate', e.target.value)}
                                                        required
                                                    />
                                                </div>
                                            </div>

                                            {/* Hari Libur */}
                                            <div className="space-y-3 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                                <div className="flex items-center gap-2">
                                                    <span className="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                                    <h4 className="text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                                        Lembur Akhir Pekan / Libur
                                                    </h4>
                                                </div>

                                                <div className="space-y-1.5">
                                                    <Label htmlFor="weekend_hourly_rate">Tarif Dasar Per Jam (Rp) *</Label>
                                                    <Input
                                                        id="weekend_hourly_rate"
                                                        type="number"
                                                        min="0"
                                                        step="500"
                                                        value={overtimeForm.data.weekend_hourly_rate}
                                                        onChange={(e) => overtimeForm.setData('weekend_hourly_rate', e.target.value)}
                                                        required
                                                    />
                                                </div>

                                                <div className="space-y-1.5">
                                                    <Label htmlFor="weekend_first_half_rate">Tarif 30 Menit Pertama (Rp) *</Label>
                                                    <Input
                                                        id="weekend_first_half_rate"
                                                        type="number"
                                                        min="0"
                                                        step="500"
                                                        value={overtimeForm.data.weekend_first_half_rate}
                                                        onChange={(e) => overtimeForm.setData('weekend_first_half_rate', e.target.value)}
                                                        required
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="pt-2 border-t border-zinc-200 dark:border-zinc-800 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div className="space-y-1.5">
                                                <Label htmlFor="coa_code">Kode COA Akuntansi Beban Lembur *</Label>
                                                <Input
                                                    id="coa_code"
                                                    value={overtimeForm.data.coa_code}
                                                    onChange={(e) => overtimeForm.setData('coa_code', e.target.value)}
                                                    placeholder="Contoh: 5-50100"
                                                    required
                                                />
                                            </div>

                                            <div className="space-y-1.5">
                                                <Label htmlFor="coa_name">Nama Akun Akuntansi (COA)</Label>
                                                <Input
                                                    id="coa_name"
                                                    value={overtimeForm.data.coa_name}
                                                    onChange={(e) => overtimeForm.setData('coa_name', e.target.value)}
                                                    placeholder="Contoh: Beban Upah Lembur Karyawan Pabrik"
                                                />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <div className="flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={overtimeForm.processing}
                                        className="bg-red-600 hover:bg-red-700 text-white min-w-44 shadow-sm"
                                    >
                                        <Save className="h-4 w-4 mr-2" />
                                        {overtimeForm.processing ? 'Menyimpan...' : 'Simpan Konfigurasi Lembur'}
                                    </Button>
                                </div>
                            </form>
                        </div>

                        {/* LIVE OVERTIME SIMULATOR */}
                        <div className="lg:col-span-5 space-y-4">
                            <Card className="border-zinc-300 dark:border-zinc-700 shadow-md">
                                <CardHeader className="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                                    <CardTitle className="text-sm font-semibold flex items-center justify-between">
                                        <span className="flex items-center gap-1.5 text-zinc-800 dark:text-zinc-200">
                                            <Calculator className="h-4 w-4 text-blue-600" />
                                            Simulasi Real-Time Kalkulator Lembur
                                        </span>
                                        <Badge className="bg-blue-600 text-white text-[10px]">
                                            Uji Coba Formula
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="p-5 space-y-5">
                                    <div className="space-y-2">
                                        <div className="flex justify-between items-center text-xs font-medium">
                                            <span>Durasi Lembur yang Diuji:</span>
                                            <span className="font-bold text-sm text-red-600 dark:text-red-400">
                                                {simHours} Jam
                                            </span>
                                        </div>
                                        <input
                                            type="range"
                                            min="0"
                                            max="12"
                                            step="0.5"
                                            value={simHours}
                                            onChange={(e) => setSimHours(parseFloat(e.target.value))}
                                            className="w-full accent-red-600 cursor-pointer"
                                        />
                                        <div className="flex justify-between text-[10px] text-zinc-400">
                                            <span>0 jam</span>
                                            <span>4 jam</span>
                                            <span>8 jam</span>
                                            <span>12 jam</span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={!simIsWeekend ? 'default' : 'outline'}
                                            onClick={() => setSimIsWeekend(false)}
                                            className={`flex-1 text-xs ${!simIsWeekend ? 'bg-zinc-900 text-white' : ''}`}
                                        >
                                            Hari Kerja (Weekday)
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={simIsWeekend ? 'default' : 'outline'}
                                            onClick={() => setSimIsWeekend(true)}
                                            className={`flex-1 text-xs ${simIsWeekend ? 'bg-amber-600 text-white' : ''}`}
                                        >
                                            Hari Libur / Weekend
                                        </Button>
                                    </div>

                                    {/* Hasil Kalkulasi Simulasi */}
                                    <div className="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 space-y-2 text-center">
                                        <div className="text-xs text-zinc-500">Estimasi Upah Lembur Karyawan:</div>
                                        <div className="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight font-mono">
                                            Rp {calculatedSimAmount.toLocaleString('id-ID')}
                                        </div>
                                        <div className="text-[11px] text-zinc-400">
                                            {simHours < 0.5
                                                ? 'Di bawah batas minimum 30 menit (Rp 0)'
                                                : simHours === 0.5
                                                ? 'Menggunakan tarif khusus 30 menit pertama'
                                                : `${simHours} jam × Rp ${(simIsWeekend ? overtimeForm.data.weekend_hourly_rate : overtimeForm.data.weekday_hourly_rate).toLocaleString('id-ID')} / jam`}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}

                {/* TAB 3: UANG MAKAN & CUT-OFF PAYROLL */}
                {activeTab === 'meal' && (
                    <div className="max-w-4xl space-y-6">
                        <form onSubmit={submitMeal} className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base flex items-center gap-2">
                                        <UtensilsCrossed className="h-4 w-4 text-emerald-600" />
                                        Aturan Uang Makan Bulanan & Disiplin Presensi
                                    </CardTitle>
                                    <CardDescription>
                                        Tentukan plafon uang makan standar per bulan serta besaran penalti kehadiran (alpha, mangkir, setengah hari).
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-5">
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div className="space-y-1.5 sm:col-span-3">
                                            <Label htmlFor="monthly_rate">Plafon Uang Makan Bulanan Standar (Rp) *</Label>
                                            <Input
                                                id="monthly_rate"
                                                type="number"
                                                min="0"
                                                step="5000"
                                                value={mealForm.data.monthly_rate}
                                                onChange={(e) => mealForm.setData('monthly_rate', e.target.value)}
                                                required
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="alpha_deduction_rate">Potongan Alpha / Mangkir (Rp) *</Label>
                                            <Input
                                                id="alpha_deduction_rate"
                                                type="number"
                                                min="0"
                                                step="1000"
                                                value={mealForm.data.alpha_deduction_rate}
                                                onChange={(e) => mealForm.setData('alpha_deduction_rate', e.target.value)}
                                                required
                                            />
                                            <p className="text-[10px] text-zinc-400">Dipotongkan per 1 hari absen tanpa keterangan.</p>
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="half_day_deduction_rate">Potongan Setengah Hari (Rp) *</Label>
                                            <Input
                                                id="half_day_deduction_rate"
                                                type="number"
                                                min="0"
                                                step="1000"
                                                value={mealForm.data.half_day_deduction_rate}
                                                onChange={(e) => mealForm.setData('half_day_deduction_rate', e.target.value)}
                                                required
                                            />
                                            <p className="text-[10px] text-zinc-400">Dipotongkan jika izin pulang lebih awal.</p>
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="max_late_tolerance">Toleransi Terlambat (Kali) *</Label>
                                            <Input
                                                id="max_late_tolerance"
                                                type="number"
                                                min="0"
                                                value={mealForm.data.max_late_tolerance}
                                                onChange={(e) => mealForm.setData('max_late_tolerance', e.target.value)}
                                                required
                                            />
                                            <p className="text-[10px] text-zinc-400">Maks. keterlambatan sebelum status hold.</p>
                                        </div>
                                    </div>

                                    <div className="pt-2 border-t border-zinc-200 dark:border-zinc-800 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="meal_coa_code">Kode COA Akuntansi Beban Uang Makan *</Label>
                                            <Input
                                                id="meal_coa_code"
                                                value={mealForm.data.coa_code}
                                                onChange={(e) => mealForm.setData('coa_code', e.target.value)}
                                                placeholder="Contoh: 5-50200"
                                                required
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="meal_coa_name">Nama Akun Akuntansi (COA)</Label>
                                            <Input
                                                id="meal_coa_name"
                                                value={mealForm.data.coa_name}
                                                onChange={(e) => mealForm.setData('coa_name', e.target.value)}
                                                placeholder="Contoh: Beban Uang Makan Karyawan Pabrik"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base flex items-center gap-2">
                                        <Calendar className="h-4 w-4 text-amber-600" />
                                        Batas Tanggal Cut-Off Penggajian (Payroll Cycle)
                                    </CardTitle>
                                    <CardDescription>
                                        Batas tanggal penutupan absensi bulanan untuk proses kalkulasi gaji dan tunjangan.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="max-w-xs space-y-1.5">
                                        <Label htmlFor="cutoff_day">Tanggal Cut-Off Bulanan (1 - 31) *</Label>
                                        <Input
                                            id="cutoff_day"
                                            type="number"
                                            min="1"
                                            max="31"
                                            value={mealForm.data.cutoff_day}
                                            onChange={(e) => mealForm.setData('cutoff_day', parseInt(e.target.value) || 25)}
                                            required
                                        />
                                        <p className="text-xs text-zinc-500">
                                            Contoh: Tanggal 25 berarti periode payroll dihitung dari tanggal 26 bulan lalu s/d 25 bulan berjalan.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={mealForm.processing}
                                    className="bg-red-600 hover:bg-red-700 text-white min-w-44 shadow-sm"
                                >
                                    <Save className="h-4 w-4 mr-2" />
                                    {mealForm.processing ? 'Menyimpan...' : 'Simpan Uang Makan & Cut-Off'}
                                </Button>
                            </div>
                        </form>
                    </div>
                )}

                {/* TAB 4: GOOGLE DRIVE & CLOUD STORAGE INTEGRATION */}
                {activeTab === 'storage' && (
                    <div className="space-y-6">
                        {/* Status Card Banner */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Card className="border-l-4 border-l-emerald-500">
                                <CardContent className="p-4 flex items-center justify-between">
                                    <div>
                                        <p className="text-xs text-zinc-500">Status Integrasi</p>
                                        <p className="text-sm font-semibold flex items-center gap-1.5 mt-0.5">
                                            {storage.drive_sync_enabled ? (
                                                <span className="text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                    <CheckCircle2 className="h-4 w-4" /> Sinkronisasi Aktif
                                                </span>
                                            ) : (
                                                <span className="text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                                    <AlertTriangle className="h-4 w-4" /> Mode Lokal (Nonaktif)
                                                </span>
                                            )}
                                        </p>
                                    </div>
                                    <Cloud className={`h-8 w-8 ${storage.drive_sync_enabled ? 'text-emerald-500' : 'text-zinc-400'}`} />
                                </CardContent>
                            </Card>

                            <Card className="border-l-4 border-l-indigo-500">
                                <CardContent className="p-4 flex items-center justify-between">
                                    <div>
                                        <p className="text-xs text-zinc-500">Service Account Google</p>
                                        <p className="text-sm font-semibold truncate max-w-[210px] mt-0.5">
                                            {storage.has_service_account ? (
                                                <span className="text-indigo-600 dark:text-indigo-400" title={storage.service_account_email}>
                                                    {storage.service_account_email || 'Terkonfigurasi'}
                                                </span>
                                            ) : (
                                                <span className="text-zinc-400">Belum Ada Kredensial</span>
                                            )}
                                        </p>
                                    </div>
                                    <Key className="h-8 w-8 text-indigo-500" />
                                </CardContent>
                            </Card>

                            <Card className="border-l-4 border-l-blue-500">
                                <CardContent className="p-4 flex items-center justify-between">
                                    <div>
                                        <p className="text-xs text-zinc-500">Optimalisasi Hosting</p>
                                        <p className="text-sm font-semibold mt-0.5">
                                            {storage.auto_unlink_local ? (
                                                <span className="text-blue-600 dark:text-blue-400">0 MB Local Storage Waste</span>
                                            ) : (
                                                <span className="text-zinc-500">Simpan Salinan Lokal</span>
                                            )}
                                        </p>
                                    </div>
                                    <HardDrive className="h-8 w-8 text-blue-500" />
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            {/* Form Konfigurasi Google Drive */}
                            <div className="lg:col-span-7 space-y-6">
                                <form onSubmit={submitStorage} className="space-y-6">
                                    <Card>
                                        <CardHeader>
                                            <CardTitle className="text-base flex items-center gap-2">
                                                <Cloud className="h-4 w-4 text-red-600" />
                                                Pengaturan Akun & Root Folder
                                            </CardTitle>
                                            <CardDescription>
                                                Dokumen digital HCM (Kontrak PKWT, Bukti Sakit, Surat, SOP, CV Pelamar) akan dialirkan otomatis ke Google Drive perusahaan.
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-5">
                                            {/* Toggle Sinkronisasi */}
                                            <div className="flex items-center justify-between p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                                <div>
                                                    <Label htmlFor="drive_sync_enabled" className="font-semibold text-sm cursor-pointer">
                                                        Aktifkan Sinkronisasi Google Drive Otomatis
                                                    </Label>
                                                    <p className="text-xs text-zinc-500 mt-0.5">
                                                        Jika aktif, file unggahan akan otomatis dialirkan ke subfolder Google Drive terkait.
                                                    </p>
                                                </div>
                                                <input
                                                    type="checkbox"
                                                    id="drive_sync_enabled"
                                                    checked={storageForm.data.drive_sync_enabled}
                                                    onChange={(e) => storageForm.setData('drive_sync_enabled', e.target.checked)}
                                                    className="h-5 w-5 rounded border-zinc-300 text-red-600 focus:ring-red-500"
                                                />
                                            </div>

                                            {/* Toggle Auto-Unlink */}
                                            <div className="flex items-center justify-between p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                                <div>
                                                    <Label htmlFor="auto_unlink_local" className="font-semibold text-sm cursor-pointer">
                                                        Hapus File Lokal Sementara (Auto-Unlink)
                                                    </Label>
                                                    <p className="text-xs text-zinc-500 mt-0.5">
                                                        Menghapus file sementara di hosting setelah sukses terunggah ke Google Drive (menjaga kapasitas server tetap 0 MB).
                                                    </p>
                                                </div>
                                                <input
                                                    type="checkbox"
                                                    id="auto_unlink_local"
                                                    checked={storageForm.data.auto_unlink_local}
                                                    onChange={(e) => storageForm.setData('auto_unlink_local', e.target.checked)}
                                                    className="h-5 w-5 rounded border-zinc-300 text-red-600 focus:ring-red-500"
                                                />
                                            </div>

                                            {/* Input Root Folder ID */}
                                            <div className="space-y-1.5">
                                                <Label htmlFor="root_folder_id">Root Folder ID Google Drive (Opsional)</Label>
                                                <Input
                                                    id="root_folder_id"
                                                    value={storageForm.data.root_folder_id}
                                                    onChange={(e) => storageForm.setData('root_folder_id', e.target.value)}
                                                    placeholder="Contoh: 1a2B3c4D5e6F_gHiJkLmNoPqRsTuVw"
                                                />
                                                <p className="text-xs text-zinc-500">
                                                    Dapat disalin dari URL folder Google Drive browser Anda (setelah bagian <code className="bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">/folders/</code>). Kosongkan jika ingin menggunakan My Drive root.
                                                </p>
                                            </div>

                                            {/* Upload Kredensial Service Account JSON */}
                                            <div className="space-y-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                                                <Label htmlFor="service_account_file">
                                                    Berkas Kredensial Google Service Account (JSON)
                                                </Label>
                                                
                                                {storage.has_service_account && (
                                                    <div className="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs space-y-1">
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-semibold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                                                <CheckCircle2 className="h-4 w-4" /> Kredensial Aktif Tersimpan Aman
                                                            </span>
                                                            <label className="flex items-center gap-1.5 cursor-pointer text-red-600 hover:text-red-700">
                                                                <input
                                                                    type="checkbox"
                                                                    checked={storageForm.data.clear_service_account}
                                                                    onChange={(e) => storageForm.setData('clear_service_account', e.target.checked)}
                                                                    className="rounded text-red-600 focus:ring-red-500"
                                                                />
                                                                <span>Hapus Kredensial</span>
                                                            </label>
                                                        </div>
                                                        <p className="text-emerald-700 dark:text-emerald-400 font-mono text-[11px] truncate">
                                                            Email: {storage.service_account_email}
                                                        </p>
                                                    </div>
                                                )}

                                                <Input
                                                    id="service_account_file"
                                                    type="file"
                                                    accept=".json"
                                                    onChange={(e) => storageForm.setData('service_account_file', e.target.files[0] || null)}
                                                    className="cursor-pointer"
                                                />
                                                <p className="text-xs text-zinc-500">
                                                    Unggah berkas JSON Service Account yang diunduh dari Google Cloud Console (IAM & Admin &gt; Service Accounts &gt; Keys).
                                                </p>
                                                {storageForm.errors.service_account_file && (
                                                    <p className="text-xs text-red-500">{storageForm.errors.service_account_file}</p>
                                                )}
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            disabled={storageForm.processing}
                                            className="bg-red-600 hover:bg-red-700 text-white min-w-44 shadow-sm"
                                        >
                                            <Save className="h-4 w-4 mr-2" />
                                            {storageForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan Cloud'}
                                        </Button>
                                    </div>
                                </form>
                            </div>

                            {/* Panel Uji Koneksi & Arsitektur Subfolder */}
                            <div className="lg:col-span-5 space-y-6">
                                {/* Uji Koneksi Langsung */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center justify-between">
                                            <span className="flex items-center gap-2">
                                                <RefreshCw className="h-4 w-4 text-indigo-600" />
                                                Uji Koneksi API Riil
                                            </span>
                                        </CardTitle>
                                        <CardDescription>
                                            Verifikasi autentikasi JWT token dan perizinan akses folder langsung ke server Google Drive.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <Button
                                            type="button"
                                            onClick={handleTestConnection}
                                            disabled={testState.loading}
                                            variant="outline"
                                            className="w-full border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50"
                                        >
                                            <RefreshCw className={`h-4 w-4 mr-2 ${testState.loading ? 'animate-spin' : ''}`} />
                                            {testState.loading ? 'Menguji Koneksi...' : 'Tes Koneksi Google Drive Sekarang'}
                                        </Button>

                                        {testState.result && (
                                            <div
                                                className={`p-3.5 rounded-lg border text-xs space-y-2 ${
                                                    testState.result.success
                                                        ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300'
                                                        : 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-300'
                                                }`}
                                            >
                                                <div className="flex items-center gap-2 font-semibold">
                                                    {testState.result.success ? (
                                                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                                    ) : (
                                                        <AlertTriangle className="h-4 w-4 text-red-600" />
                                                    )}
                                                    <span>{testState.result.message}</span>
                                                </div>

                                                {testState.result.success && (
                                                    <div className="space-y-1 pt-1 text-[11px] font-mono border-t border-emerald-200/50 dark:border-emerald-800/50">
                                                        <div>Akun: {testState.result.service_account_email}</div>
                                                        <div>Root Folder: {testState.result.root_folder_name}</div>
                                                        {testState.result.storage_quota?.limit && (
                                                            <div>
                                                                Kapasitas: {(testState.result.storage_quota.usage / 1024 / 1024 / 1024).toFixed(2)} GB / {(testState.result.storage_quota.limit / 1024 / 1024 / 1024).toFixed(2)} GB
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                {/* Blueprint Subfolder Terstruktur */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <FolderTree className="h-4 w-4 text-blue-600" />
                                            Struktur Subfolder Otomatis
                                        </CardTitle>
                                        <CardDescription>
                                            Sistem otomatis membuat dan mengarahkan file ke 5 subfolder standar Google Drive:
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-2.5 text-xs">
                                        <div className="p-2.5 rounded bg-zinc-100 dark:bg-zinc-800/60 font-mono text-[11px] space-y-1.5 text-zinc-700 dark:text-zinc-300">
                                            <div className="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                                <FolderTree className="h-3.5 w-3.5 text-amber-500" />
                                                📁 [Root] NISGroup HCM/
                                            </div>
                                            <div className="pl-4">├── 📁 01_Dokumen_Internal (SOP, Kebijakan, SK)</div>
                                            <div className="pl-4">├── 📁 02_Surat_Masuk_Keluar (Agenda Persuratan)</div>
                                            <div className="pl-4">├── 📁 03_Kontrak_PKWT (Scan Kontrak Karyawan)</div>
                                            <div className="pl-4">├── 📁 04_Surat_Dokter_Presensi (Bukti Izin/Sakit)</div>
                                            <div className="pl-4">└── 📁 05_Rekrutmen_Pelamar (CV/Resume Pelamar)</div>
                                        </div>
                                        <p className="text-xs text-zinc-500 pt-1">
                                            💡 Setiap berkas yang diunggah otomatis diberikan hak akses baca (*anyone with link = reader*) sehingga preview dokumen in-app dapat dibuka tanpa hambatan autentikasi.
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
