import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { 
    Briefcase, MapPin, Clock, Calendar, Users, CheckCircle2, 
    FileText, User, Phone, Mail, GraduationCap, Award, 
    Upload, AlertCircle, Sparkles, Send, ArrowLeft,
    Home, Copy, Check, ArrowUp, ChevronRight
} from 'lucide-react';

export default function CareerApply({ job, company = {}, flash = {} }) {
    const { data, setData, post, processing, errors, reset, wasSuccessful } = useForm({
        name: '',
        nickname: '',
        gender: 'Laki Laki',
        birth_place: '',
        birth_date: '',
        phone_number: '',
        email: '',
        education: 'S1',
        major: '',
        address: '',
        nik_ktp: '',
        marital_status: 'Belum Menikah',
        shirt_size: 'L',
        expected_salary: '',
        available_start_date: '',
        experience_summary: '',
        skills: '',
        resume_file: null,
        ktp_file: null,
        photo_file: null,
        portfolio_url: '',
    });

    const [activeTab, setActiveTab] = useState('job'); // 'job' or 'apply'
    const [copied, setCopied] = useState(false);

    const handleCopyLink = () => {
        if (typeof window !== 'undefined') {
            navigator.clipboard.writeText(window.location.href);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const scrollToTop = () => {
        if (typeof window !== 'undefined') {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    const shareUrl = typeof window !== 'undefined' ? window.location.href : '';
    const whatsappShareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(`Halo! Cek lowongan kerja ${job?.title || 'ini'} di ${company?.company_name || 'perusahaan'}: ${shareUrl}`)}`;

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('career.apply', job.slug), {
            forceFormData: true,
            onSuccess: () => {
                reset();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        });
    };

    return (
        <div className="min-h-screen bg-slate-50 text-slate-800 font-sans pb-20 sm:pb-0">
            <Head title={`${job.title} - Karir & Lowongan Kerja`} />

            {/* Header Brand */}
            <header className="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                    <Link 
                        href="/karir" 
                        className="flex items-center space-x-3 group hover:opacity-90 transition"
                        title="Kembali ke Beranda Karir"
                    >
                        {company?.logo_url ? (
                            <img 
                                src={company.logo_url} 
                                alt={company.division_name || 'Logo HRIS'} 
                                className="w-10 h-10 rounded-xl object-contain bg-white border border-slate-200 p-1 shadow-sm group-hover:scale-105 transition-transform" 
                            />
                        ) : (
                            <div 
                                style={{ backgroundColor: company?.theme_color || '#a8001c' }}
                                className="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-md font-bold text-sm tracking-wider group-hover:scale-105 transition-transform"
                            >
                                {company?.logo_initial || 'HCM'}
                            </div>
                        )}
                        <div>
                            <h1 className="text-base font-bold text-slate-900 tracking-tight leading-none group-hover:text-red-700 transition">
                                {company?.portal_title || 'PORTAL KARIR & REKRUTMEN'}
                            </h1>
                            <p 
                                style={{ color: company?.theme_color || '#a8001c' }} 
                                className="text-xs font-semibold mt-1"
                            >
                                {company?.division_name || 'Divisi Human Capital Management'}
                            </p>
                        </div>
                    </Link>
                    <div className="flex items-center gap-2.5">
                        {job.is_active && job.status === 'Aktif' ? (
                            <button
                                type="button"
                                onClick={() => {
                                    setActiveTab('apply');
                                    setTimeout(() => {
                                        const tabsEl = document.getElementById('career-tabs');
                                        if (tabsEl) {
                                            tabsEl.scrollIntoView({ behavior: 'smooth' });
                                        }
                                    }, 50);
                                }}
                                style={{ backgroundColor: company?.theme_color || '#a8001c' }}
                                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-white text-xs sm:text-sm font-bold shadow-sm hover:opacity-90 active:scale-95 transition cursor-pointer"
                            >
                                <Send className="w-3.5 h-3.5" />
                                <span>Lamar Sekarang</span>
                            </button>
                        ) : (
                            <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <span className="w-2 h-2 rounded-full bg-rose-500"></span>
                                Lowongan Ditutup
                            </span>
                        )}
                    </div>
                </div>
            </header>

            {/* Hero Job Banner */}
            <div className="bg-gradient-to-b from-slate-900 via-slate-850 to-slate-800 text-white py-12 px-4 sm:px-6">
                <div className="max-w-6xl mx-auto">
                    <div className="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-white/10 text-sky-300 text-xs font-medium backdrop-blur-sm mb-4">
                        <Briefcase className="w-3.5 h-3.5" />
                        <span>Kode Lowongan: {job.job_code}</span>
                    </div>

                    <h1 className="text-2xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">
                        {job.title}
                    </h1>

                    <div className="flex flex-wrap gap-y-2 gap-x-4 text-xs sm:text-sm text-slate-300 mb-6">
                        <div className="flex items-center gap-1.5">
                            <Briefcase className="w-4 h-4 text-sky-400" />
                            <span>{job.department} {job.position ? `• ${job.position}` : ''}</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                            <MapPin className="w-4 h-4 text-rose-400" />
                            <span>{job.location} ({job.job_type})</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                            <Users className="w-4 h-4 text-indigo-400" />
                            <span>Kebutuhan: {job.quota} Orang</span>
                        </div>
                    </div>

                    {/* Quick Info Badges */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-white/5 border border-white/10 rounded-xl p-4 backdrop-blur-sm text-xs">
                        <div>
                            <div className="text-slate-400">Pendidikan Min.</div>
                            <div className="font-semibold text-white mt-0.5">{job.min_education || 'Semua Jenjang'}</div>
                        </div>
                        <div>
                            <div className="text-slate-400">Pengalaman Min.</div>
                            <div className="font-semibold text-white mt-0.5">{job.min_experience_years ? `${job.min_experience_years} Tahun` : 'Fresh Graduate / Umum'}</div>
                        </div>
                        <div>
                            <div className="text-slate-400">Batas Lamaran</div>
                            <div className="font-semibold text-white mt-0.5">{job.deadline ? new Date(job.deadline).toLocaleDateString('id-ID', { dateStyle: 'medium' }) : 'Terbuka Selama Kuota Ada'}</div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Navigation Tabs */}
            <div id="career-tabs" className="bg-white border-b border-slate-200 sticky top-16 z-20 shadow-xs">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 flex gap-4">
                    <button
                        onClick={() => setActiveTab('job')}
                        style={activeTab === 'job' ? { borderColor: company?.theme_color || '#a8001c', color: company?.theme_color || '#a8001c' } : {}}
                        className={`py-3.5 px-4 font-semibold text-sm border-b-2 transition flex items-center gap-2 ${
                            activeTab === 'job' 
                                ? '' 
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        <FileText className="w-4 h-4" />
                        Rincian & Kualifikasi Lowongan
                    </button>
                    <button
                        onClick={() => setActiveTab('apply')}
                        style={activeTab === 'apply' ? { borderColor: company?.theme_color || '#a8001c', color: company?.theme_color || '#a8001c' } : {}}
                        className={`py-3.5 px-4 font-semibold text-sm border-b-2 transition flex items-center gap-2 ${
                            activeTab === 'apply' 
                                ? '' 
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        <Send className="w-4 h-4" />
                        Formulir Lamaran Online
                    </button>
                </div>
            </div>

            {/* Main Content Area */}
            <main className="max-w-6xl mx-auto px-4 sm:px-6 py-10">
                {/* Flash Success Notification */}
                {flash.success && (
                    <div className="mb-8 p-6 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-4 shadow-sm animate-fade-in">
                        <div className="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-md">
                            <CheckCircle2 className="w-7 h-7" />
                        </div>
                        <div>
                            <h3 className="text-lg font-bold text-emerald-900">Lamaran Terkirim Berhasil!</h3>
                            <p className="text-sm text-emerald-800 mt-1 whitespace-pre-line leading-relaxed">
                                {flash.success}
                            </p>
                            <p className="text-xs text-emerald-600 mt-2 font-medium">
                                Silakan simpan nomor kode pelamar Anda untuk verifikasi saat tes wawancara.
                            </p>
                        </div>
                    </div>
                )}

                {/* Tab 1: Job Description & Qualifications */}
                {activeTab === 'job' && (
                    <div className="space-y-6">
                        <div className="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                            <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                                <Sparkles className="w-5 h-5 text-sky-500" />
                                Deskripsi Pekerjaan
                            </h2>
                            <div className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                {job.description || 'Tidak ada deskripsi spesifik.'}
                            </div>
                        </div>

                        {job.requirements && (
                            <div className="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                                <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                                    <CheckCircle2 className="w-5 h-5 text-indigo-500" />
                                    Persyaratan & Kualifikasi
                                </h2>
                                <div className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                    {job.requirements}
                                </div>
                            </div>
                        )}

                        {job.benefits && (
                            <div className="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                                <h2 className="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                                    <Award className="w-5 h-5 text-emerald-500" />
                                    Fasilitas & Keuntungan (Benefit)
                                </h2>
                                <div className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                    {job.benefits}
                                </div>
                            </div>
                        )}

                        <div className="flex justify-end pt-4">
                            {job.is_active && job.status === 'Aktif' ? (
                                <button
                                    onClick={() => setActiveTab('apply')}
                                    style={{ backgroundColor: company?.theme_color || '#a8001c' }}
                                    className="px-6 py-3 rounded-xl text-white font-semibold text-sm shadow-md hover:opacity-90 transition flex items-center gap-2"
                                >
                                    <Send className="w-4 h-4" />
                                    Siap Bergabung? Lamar Posisi Ini Sekarang
                                </button>
                            ) : (
                                <div className="px-5 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 font-medium text-xs flex items-center gap-2">
                                    <AlertCircle className="w-4 h-4 text-amber-500" />
                                    Pendaftaran untuk posisi ini telah ditutup.
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Tab 2: Application Form */}
                {activeTab === 'apply' && (
                    (!job.is_active || job.status !== 'Aktif') ? (
                        <div className="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center space-y-4 shadow-xs">
                            <div className="w-14 h-14 bg-rose-50 text-rose-500 border border-rose-100 rounded-full flex items-center justify-center mx-auto">
                                <AlertCircle className="w-7 h-7" />
                            </div>
                            <h2 className="text-xl font-bold text-slate-900">Pendaftaran Lowongan Sedang Ditutup</h2>
                            <p className="text-sm text-slate-600 max-w-lg mx-auto leading-relaxed">
                                Mohon maaf, saat ini pendaftaran untuk posisi <strong>{job.title}</strong> telah ditutup atau kuota pemenuhan kandidat telah terpenuhi.
                            </p>
                            <div className="pt-2 flex items-center justify-center gap-3">
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('job')}
                                    className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                                >
                                    <ArrowLeft className="w-4 h-4" />
                                    Lihat Rincian Lowongan
                                </button>
                                <Link
                                    href="/karir"
                                    className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold shadow-sm transition"
                                >
                                    Cari Posisi Lainnya
                                </Link>
                            </div>
                        </div>
                    ) : (
                    <form onSubmit={handleSubmit} className="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs space-y-8">
                        <div>
                            <h2 className="text-xl font-bold text-slate-900 tracking-tight">Formulir Pendaftaran Calon Karyawan</h2>
                            <p className="text-xs text-slate-500 mt-1">
                                Mohon isi seluruh informasi dengan data yang sebenarnya dan valid.
                            </p>
                        </div>

                        {/* Bagian 1: Data Identitas Pribadi */}
                        <div className="border-t border-slate-100 pt-6">
                            <h3 className="text-sm font-bold text-sky-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <User className="w-4 h-4" />
                                1. Data Identitas Pribadi
                            </h3>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Sesuai KTP *</label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="Contoh: Achmad Pratama"
                                    />
                                    {errors.name && <p className="text-xs text-rose-500 mt-1">{errors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Nama Panggilan</label>
                                    <input
                                        type="text"
                                        value={data.nickname}
                                        onChange={(e) => setData('nickname', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="Contoh: Achmad"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin *</label>
                                    <select
                                        value={data.gender}
                                        onChange={(e) => setData('gender', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                    >
                                        <option value="Laki Laki">Laki-Laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">NIK (Nomor KTP)</label>
                                    <input
                                        type="text"
                                        value={data.nik_ktp}
                                        onChange={(e) => setData('nik_ktp', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="16 digit NIK KTP"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Tempat Lahir</label>
                                    <input
                                        type="text"
                                        value={data.birth_place}
                                        onChange={(e) => setData('birth_place', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="Kota Kelahiran"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Tanggal Lahir</label>
                                    <input
                                        type="date"
                                        value={data.birth_date}
                                        onChange={(e) => setData('birth_date', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Status Pernikahan</label>
                                    <select
                                        value={data.marital_status}
                                        onChange={(e) => setData('marital_status', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                    >
                                        <option value="Belum Menikah">Belum Menikah (Lajang)</option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Cerai Hidup">Cerai Hidup</option>
                                        <option value="Cerai Mati">Cerai Mati</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Ukuran Baju Seragam</label>
                                    <select
                                        value={data.shirt_size}
                                        onChange={(e) => setData('shirt_size', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                    >
                                        <option value="S">S</option>
                                        <option value="M">M</option>
                                        <option value="L">L</option>
                                        <option value="XL">XL</option>
                                        <option value="XXL">XXL</option>
                                        <option value="XXXL">XXXL</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {/* Bagian 2: Kontak & Alamat */}
                        <div className="border-t border-slate-100 pt-6">
                            <h3 className="text-sm font-bold text-sky-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <Phone className="w-4 h-4" />
                                2. Kontak & Alamat Domisili
                            </h3>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp / HP Aktif *</label>
                                    <input
                                        type="tel"
                                        value={data.phone_number}
                                        onChange={(e) => setData('phone_number', e.target.value)}
                                        required
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="081234567890"
                                    />
                                    {errors.phone_number && <p className="text-xs text-rose-500 mt-1">{errors.phone_number}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Alamat Email Aktif</label>
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="nama@email.com"
                                    />
                                    {errors.email && <p className="text-xs text-rose-500 mt-1">{errors.email}</p>}
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap Domisili Saat Ini</label>
                                    <textarea
                                        rows="2"
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="Jl. Mawar No. 123, Kelurahan, Kecamatan, Kota"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        {/* Bagian 3: Pendidikan & Keahlian */}
                        <div className="border-t border-slate-100 pt-6">
                            <h3 className="text-sm font-bold text-sky-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <GraduationCap className="w-4 h-4" />
                                3. Pendidikan & Keahlian
                            </h3>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Jenjang Pendidikan Terakhir *</label>
                                    <select
                                        value={data.education}
                                        onChange={(e) => setData('education', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                    >
                                        <option value="SMA / SMK">SMA / SMK</option>
                                        <option value="D3">Diploma 3 (D3)</option>
                                        <option value="D4 / S1">Sarjana (D4 / S1)</option>
                                        <option value="S2">Magister (S2)</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Program Studi / Jurusan</label>
                                    <input
                                        type="text"
                                        value={data.major}
                                        onChange={(e) => setData('major', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="Contoh: Teknik Informatika / Manajemen"
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Ringkasan Pengalaman Kerja</label>
                                    <textarea
                                        rows="3"
                                        value={data.experience_summary}
                                        onChange={(e) => setData('experience_summary', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="Ceritakan singkat posisi sebelumnya, durasi, dan tugas utama yang pernah diemban..."
                                    ></textarea>
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Keahlian & Kemampuan Utama (Skills)</label>
                                    <input
                                        type="text"
                                        value={data.skills}
                                        onChange={(e) => setData('skills', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="Contoh: PHP, Laravel, React, Public Speaking, Microsoft Excel"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Bagian 4: Ketersediaan & Ekspektasi */}
                        <div className="border-t border-slate-100 pt-6">
                            <h3 className="text-sm font-bold text-sky-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <Award className="w-4 h-4" />
                                4. Ketersediaan Mulai Bekerja, Ekspektasi Gaji & Portofolio
                            </h3>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Tanggal Siap Mulai Bekerja</label>
                                    <input
                                        type="date"
                                        value={data.available_start_date}
                                        onChange={(e) => setData('available_start_date', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                    />
                                    {errors.available_start_date && <p className="text-xs text-rose-500 mt-1">{errors.available_start_date}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Ekspektasi Gaji (Rp / Bulan)</label>
                                    <div className="relative">
                                        <span className="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-slate-400">Rp</span>
                                        <input
                                            type="number"
                                            min="0"
                                            step="50000"
                                            value={data.expected_salary}
                                            onChange={(e) => setData('expected_salary', e.target.value)}
                                            className="w-full pl-9 pr-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                            placeholder="Contoh: 3500000"
                                        />
                                    </div>
                                    {errors.expected_salary && <p className="text-xs text-rose-500 mt-1">{errors.expected_salary}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 mb-1">Tautan Portofolio / LinkedIn (Opsional)</label>
                                    <input
                                        type="url"
                                        value={data.portfolio_url}
                                        onChange={(e) => setData('portfolio_url', e.target.value)}
                                        className="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"
                                        placeholder="https://linkedin.com/in/... atau https://github.com/..."
                                    />
                                    {errors.portfolio_url && <p className="text-xs text-rose-500 mt-1">{errors.portfolio_url}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Bagian 5: Upload Berkas Dokumen & Pasfoto */}
                        <div className="border-t border-slate-100 pt-6">
                            <h3 className="text-sm font-bold text-sky-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <Upload className="w-4 h-4" />
                                5. Lampiran Berkas Dokumen & Pasfoto (Maksimal 3–5MB)
                            </h3>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div className="p-4 border-2 border-dashed border-slate-200 rounded-xl hover:border-emerald-400 transition bg-slate-50">
                                    <label className="block text-xs font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                        <User className="w-4 h-4 text-emerald-600" />
                                        Upload Pasfoto Resmi (Foto Wajah)
                                    </label>
                                    <p className="text-[11px] text-slate-500 mb-3">Format JPG, JPEG, PNG, WebP (Maks 3MB)</p>
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        onChange={(e) => setData('photo_file', e.target.files[0])}
                                        className="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
                                    />
                                    {errors.photo_file && <p className="text-xs text-rose-500 mt-1">{errors.photo_file}</p>}
                                </div>
                                <div className="p-4 border-2 border-dashed border-slate-200 rounded-xl hover:border-sky-400 transition bg-slate-50">
                                    <label className="block text-xs font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                        <FileText className="w-4 h-4 text-sky-600" />
                                        Upload Curriculum Vitae / Resume
                                    </label>
                                    <p className="text-[11px] text-slate-500 mb-3">Format PDF, DOC, atau DOCX</p>
                                    <input
                                        type="file"
                                        accept=".pdf,.doc,.docx,.jpg,.png"
                                        onChange={(e) => setData('resume_file', e.target.files[0])}
                                        className="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer"
                                    />
                                    {errors.resume_file && <p className="text-xs text-rose-500 mt-1">{errors.resume_file}</p>}
                                </div>
                                <div className="p-4 border-2 border-dashed border-slate-200 rounded-xl hover:border-indigo-400 transition bg-slate-50">
                                    <label className="block text-xs font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                        <FileText className="w-4 h-4 text-indigo-600" />
                                        Upload Foto KTP (Opsional)
                                    </label>
                                    <p className="text-[11px] text-slate-500 mb-3">Format JPG, PNG, atau PDF</p>
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        onChange={(e) => setData('ktp_file', e.target.files[0])}
                                        className="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                                    />
                                    {errors.ktp_file && <p className="text-xs text-rose-500 mt-1">{errors.ktp_file}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Submit Button */}
                        <div className="border-t border-slate-100 pt-6 flex items-center justify-between">
                            <button
                                type="button"
                                onClick={() => setActiveTab('job')}
                                className="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1.5"
                            >
                                <ArrowLeft className="w-4 h-4" />
                                Kembali ke Detail Lowongan
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                style={{ backgroundColor: company?.theme_color || '#a8001c' }}
                                className="px-6 py-3 rounded-xl text-white font-bold text-sm shadow-md hover:opacity-90 transition flex items-center gap-2 disabled:opacity-50"
                            >
                                <Send className="w-4 h-4" />
                                {processing ? 'Sedang Mengirim Lamaran...' : 'Kirim Lamaran Sekarang'}
                            </button>
                        </div>
                    </form>
                    )
                )}
            </main>

            {/* Footer */}
            <footer className="bg-white border-t border-slate-200 mt-12 py-8 text-center text-xs text-slate-500">
                <div className="max-w-6xl mx-auto px-4 sm:px-6">
                    <p>{company?.footer_text || `© ${new Date().getFullYear()} ${company?.name || 'Perusahaan'}. Seluruh Hak Cipta Dilindungi.`}</p>
                    <p className="mt-1 text-[11px] text-slate-400">Human Capital Management & E-Recruitment Portal.</p>
                </div>
            </footer>

            {/* Sticky Bottom Navigation - Mobile Only */}
            <nav className="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200 px-3 py-2 shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
                <div className="grid grid-cols-4 items-center justify-items-center gap-1">
                    {/* Home */}
                    <Link 
                        href="/karir" 
                        className="flex flex-col items-center justify-center text-slate-600 hover:text-slate-900 active:scale-95 transition w-full py-1"
                    >
                        <Home className="w-5 h-5 text-slate-700" />
                        <span className="text-[10px] font-semibold mt-0.5 text-slate-700">Home</span>
                    </Link>

                    {/* Salin Link */}
                    <button
                        type="button"
                        onClick={handleCopyLink}
                        className="flex flex-col items-center justify-center text-slate-600 hover:text-slate-900 active:scale-95 transition w-full py-1"
                    >
                        {copied ? (
                            <Check className="w-5 h-5 text-emerald-600" />
                        ) : (
                            <Copy className="w-5 h-5 text-slate-700" />
                        )}
                        <span className={`text-[10px] font-semibold mt-0.5 ${copied ? 'text-emerald-600 font-bold' : 'text-slate-700'}`}>
                            {copied ? 'Tersalin' : 'Salin Link'}
                        </span>
                    </button>

                    {/* WhatsApp */}
                    <a
                        href={whatsappShareUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex flex-col items-center justify-center text-emerald-600 hover:text-emerald-700 active:scale-95 transition w-full py-1"
                    >
                        <div className="w-5 h-5 flex items-center justify-center text-emerald-600">
                            <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                                <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1" />
                            </svg>
                        </div>
                        <span className="text-[10px] font-semibold mt-0.5 text-emerald-700">WhatsApp</span>
                    </a>

                    {/* Back to Top */}
                    <button
                        type="button"
                        onClick={scrollToTop}
                        className="flex flex-col items-center justify-center text-slate-600 hover:text-slate-900 active:scale-95 transition w-full py-1"
                    >
                        <ArrowUp className="w-5 h-5 text-slate-700" />
                        <span className="text-[10px] font-semibold mt-0.5 text-slate-700">Ke Atas</span>
                    </button>
                </div>
            </nav>
        </div>
    );
}
