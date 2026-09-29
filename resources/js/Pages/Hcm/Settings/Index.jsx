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
} from 'lucide-react';

export default function HcmSettingsIndex({ profile = {}, overtime = {}, meal_allowance = {}, payroll = {} }) {
    const [activeTab, setActiveTab] = useState('profile');
    const [logoPreview, setLogoPreview] = useState(profile.logo_url || null);

    // Form 1: Profil Instansi & Kop Dokumen
    const profileForm = useForm({
        company_name: profile.company_name || '',
        company_tagline: profile.company_tagline || '',
        company_address: profile.company_address || '',
        company_city: profile.company_city || '',
        company_email: profile.company_email || '',
        company_phone: profile.company_phone || '',
        company_website: profile.company_website || '',
        company_socials: profile.company_socials || '',
        kop_header_line1: profile.kop_header_line1 || '',
        kop_header_line2: profile.kop_header_line2 || '',
        document_footer_text: profile.document_footer_text || '',
        document_footer_disclaimer: profile.document_footer_disclaimer || '',
        logo: null,
        remove_logo: false,
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

    return (
        <AppLayout title="Pengaturan Kepegawaian (HCM)">
            <Head title="Pengaturan Kepegawaian (HCM) - Profil & Biaya Lembur" />

            <div className="space-y-6">
                {/* Header Sub-Modul */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-800 pb-5">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300">
                                Sub-Modul HCM
                            </span>
                            <span className="text-xs text-zinc-400">&bull;</span>
                            <span className="text-xs text-zinc-500 dark:text-zinc-400">Konfigurasi Sentral Kepegawaian</span>
                        </div>
                        <h1 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <SlidersHorizontal className="h-6 w-6 text-red-600 dark:text-red-500" />
                            Pengaturan Modul Kepegawaian
                        </h1>
                        <p className="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
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
                        Profil Instansi & Kop Dokumen
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
                </div>

                {/* TAB 1: PROFIL INSTANSI, KOP SURAT, MEDSOS & FOOTER */}
                {activeTab === 'profile' && (
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <div className="lg:col-span-7 space-y-6">
                            <form onSubmit={submitProfile} className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <Building2 className="h-4 w-4 text-red-600" />
                                            Identitas Legal Perusahaan & Kontak
                                        </CardTitle>
                                        <CardDescription>
                                            Identitas ini otomatis disematkan pada seluruh berkas resmi HCM (Paklaring, Profil Karyawan, Slip Lembur, Rekap Presensi).
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="company_name">Nama Perusahaan / Instansi Legal *</Label>
                                                <Input
                                                    id="company_name"
                                                    value={profileForm.data.company_name}
                                                    onChange={(e) => profileForm.setData('company_name', e.target.value)}
                                                    placeholder="Contoh: PT. NIS KONVEKSI INDONESIA"
                                                    required
                                                />
                                                {profileForm.errors.company_name && (
                                                    <p className="text-xs text-red-500">{profileForm.errors.company_name}</p>
                                                )}
                                            </div>

                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="company_tagline">Tagline / Slogan Sub-Identitas</Label>
                                                <Input
                                                    id="company_tagline"
                                                    value={profileForm.data.company_tagline}
                                                    onChange={(e) => profileForm.setData('company_tagline', e.target.value)}
                                                    placeholder="Contoh: Human Capital & Apparel Manufacturing Industry"
                                                />
                                            </div>

                                            <div className="space-y-1.5 sm:col-span-2">
                                                <Label htmlFor="company_address">Alamat Lengkap Domisili / Pabrik *</Label>
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
                                                <Label htmlFor="company_city">Kota / Domisili Tanda Tangan</Label>
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
                                                <Label htmlFor="company_email">Email Resmi Korespondensi HCM</Label>
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

                                <Card>
                                    <CardHeader>
                                        <CardTitle className="text-base flex items-center gap-2">
                                            <FileText className="h-4 w-4 text-red-600" />
                                            Kop Surat & Footer Dokumen Resmi
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
                                        {profileForm.processing ? 'Menyimpan...' : 'Simpan Profil & Kop Surat'}
                                    </Button>
                                </div>
                            </form>
                        </div>

                        {/* LIVE PREVIEW KOP SURAT */}
                        <div className="lg:col-span-5 space-y-4">
                            <div className="sticky top-6">
                                <Card className="border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-md">
                                    <CardHeader className="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                                        <CardTitle className="text-sm font-semibold flex items-center justify-between">
                                            <span className="flex items-center gap-1.5 text-zinc-800 dark:text-zinc-200">
                                                <Eye className="h-4 w-4 text-emerald-600" />
                                                Live Preview Kop Surat & Footer
                                            </span>
                                            <Badge variant="outline" className="text-[10px] uppercase font-mono">
                                                Simulasi PDF
                                            </Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-5 space-y-6 text-zinc-800 dark:text-zinc-200 font-sans">
                                        {/* Mock Paper Kop */}
                                        <div className="p-4 bg-zinc-50 dark:bg-zinc-950/70 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-inner">
                                            <div className="flex items-start gap-3 border-b-2 border-zinc-900 dark:border-zinc-100 pb-3 mb-3">
                                                <div className="w-14 h-14 rounded border border-zinc-300 dark:border-zinc-700 flex items-center justify-center shrink-0 bg-white dark:bg-zinc-800 overflow-hidden">
                                                    {logoPreview ? (
                                                        <img
                                                            src={logoPreview}
                                                            alt="Logo"
                                                            className="w-full h-full object-contain p-1"
                                                        />
                                                    ) : (
                                                        <span className="text-xl font-bold text-zinc-500">
                                                            {profileForm.data.company_name?.charAt(0) || 'N'}
                                                        </span>
                                                    )}
                                                </div>

                                                <div className="flex-1 min-w-0">
                                                    <h3 className="font-extrabold text-sm uppercase tracking-wide leading-tight text-zinc-900 dark:text-zinc-100 truncate">
                                                        {profileForm.data.company_name || 'NAMA PERUSAHAAN'}
                                                    </h3>
                                                    <p className="text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mt-0.5 leading-snug">
                                                        {profileForm.data.kop_header_line1 || 'Divisi Human Capital & SDM'}
                                                    </p>
                                                    <p className="text-[10px] text-zinc-500 dark:text-zinc-400 line-clamp-2 mt-0.5">
                                                        {profileForm.data.company_address || 'Alamat Perusahaan'}
                                                    </p>
                                                    <div className="text-[9px] text-zinc-400 dark:text-zinc-500 mt-1 flex flex-wrap gap-x-2">
                                                        {profileForm.data.company_phone && (
                                                            <span>Tel: {profileForm.data.company_phone}</span>
                                                        )}
                                                        {profileForm.data.company_email && (
                                                            <span>Email: {profileForm.data.company_email}</span>
                                                        )}
                                                        {profileForm.data.company_socials && (
                                                            <span>Medsos: {profileForm.data.company_socials}</span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Dummy Document Body */}
                                            <div className="py-4 space-y-2 text-center">
                                                <span className="text-[11px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 underline underline-offset-4">
                                                    SURAT KETERANGAN / VOUCHER RESMI
                                                </span>
                                                <p className="text-[9px] text-zinc-400 font-mono">
                                                    No: DOC/HCM/{new Date().getFullYear()}/001
                                                </p>
                                                <div className="h-10 border border-dashed border-zinc-200 dark:border-zinc-800 rounded flex items-center justify-center text-[10px] text-zinc-400 italic">
                                                    [ Konten Lembar Dokumen / Tabel Detail Karyawan ]
                                                </div>
                                            </div>

                                            {/* Mock TTD */}
                                            <div className="flex justify-end text-right text-[10px] pt-2 border-t border-zinc-200 dark:border-zinc-800">
                                                <div>
                                                    <p className="text-zinc-500">
                                                        {profileForm.data.company_city || 'Klaten'}, {new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                                                    </p>
                                                    <p className="font-semibold mt-0.5">Pimpinan / HR Manager</p>
                                                    <div className="h-8"></div>
                                                    <p className="font-bold underline text-zinc-800 dark:text-zinc-200">
                                                        ( Manajemen HCM )
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Mock Footer Note */}
                                            <div className="mt-4 pt-2 border-t border-zinc-200 dark:border-zinc-800 text-[8px] text-zinc-400 text-center space-y-0.5">
                                                <p>{profileForm.data.document_footer_text || 'Dokumen diterbitkan resmi oleh sistem.'}</p>
                                                <p className="italic text-zinc-500">
                                                    {profileForm.data.document_footer_disclaimer || 'Keabsahan dokumen terverifikasi.'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="text-xs text-zinc-500 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 p-3 rounded-lg flex items-start gap-2">
                                            <Info className="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                                            <p>
                                                Format di atas akan tercetak langsung pada file PDF Paklaring, Buku Dossier Profil Karyawan, Voucher Lembur, dan Rekap Uang Makan.
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
            </div>
        </AppLayout>
    );
}
