import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { 
    Briefcase, MapPin, Clock, Calendar, Users, CheckCircle2, 
    FileText, User, Phone, Mail, GraduationCap, Award, 
    Upload, AlertCircle, Sparkles, Send, ArrowLeft
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
        <div className="min-h-screen bg-slate-50 text-slate-800">
            <Head title={`${job.title} - Karir & Lowongan Kerja`} />

            {/* Header Brand */}
            <header className="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        {company?.logo_url ? (
                            <img src={company.logo_url} alt={company.name || 'Logo'} className="w-10 h-10 rounded-xl object-contain bg-white border border-slate-200 p-1 shadow-sm" />
                        ) : (
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-sky-500/20 font-bold text-lg">
                                {company?.name ? company.name.substring(0, 3).toUpperCase() : 'HCM'}
                            </div>
                        )}
                        <div>
                            <h1 className="text-base font-bold text-slate-900 tracking-tight leading-none">PORTAL KARIR & REKRUTMEN</h1>
                            <p className="text-xs text-slate-500 mt-0.5">{company?.name || 'Human Capital Management System'}</p>
                        </div>
                    </div>
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Lowongan Aktif
                    </span>
                </div>
            </header>

            {/* Hero Job Banner */}
            <div className="bg-gradient-to-b from-slate-900 to-slate-800 text-white py-10 px-4 sm:px-6">
                <div className="max-w-5xl mx-auto">
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
            <div className="bg-white border-b border-slate-200 sticky top-16 z-20 shadow-xs">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 flex gap-4">
                    <button
                        onClick={() => setActiveTab('job')}
                        className={`py-3.5 px-4 font-semibold text-sm border-b-2 transition flex items-center gap-2 ${
                            activeTab === 'job' 
                                ? 'border-sky-600 text-sky-600' 
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        <FileText className="w-4 h-4" />
                        Rincian & Kualifikasi Lowongan
                    </button>
                    <button
                        onClick={() => setActiveTab('apply')}
                        className={`py-3.5 px-4 font-semibold text-sm border-b-2 transition flex items-center gap-2 ${
                            activeTab === 'apply' 
                                ? 'border-sky-600 text-sky-600' 
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        <Send className="w-4 h-4" />
                        Formulir Lamaran Online
                    </button>
                </div>
            </div>

            {/* Main Content Area */}
            <main className="max-w-5xl mx-auto px-4 sm:px-6 py-8">
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
                            <button
                                onClick={() => setActiveTab('apply')}
                                className="px-6 py-3 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 text-white font-semibold text-sm shadow-md hover:from-sky-700 hover:to-indigo-700 transition flex items-center gap-2"
                            >
                                <Send className="w-4 h-4" />
                                Siap Bergabung? Lamar Posisi Ini Sekarang
                            </button>
                        </div>
                    </div>
                )}

                {/* Tab 2: Application Form */}
                {activeTab === 'apply' && (
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
                                className="px-6 py-3 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 text-white font-bold text-sm shadow-md hover:from-sky-700 hover:to-indigo-700 transition flex items-center gap-2 disabled:opacity-50"
                            >
                                <Send className="w-4 h-4" />
                                {processing ? 'Sedang Mengirim Lamaran...' : 'Kirim Lamaran Sekarang'}
                            </button>
                        </div>
                    </form>
                )}
            </main>

            {/* Footer */}
            <footer className="bg-white border-t border-slate-200 py-6 mt-12 text-center text-xs text-slate-500">
                <p>{company?.footer_text || `© ${new Date().getFullYear()} ${company?.name || 'Human Capital Management System'} - All Rights Reserved.`}</p>
            </footer>
        </div>
    );
}
