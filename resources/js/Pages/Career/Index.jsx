import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { 
    Briefcase, MapPin, Clock, Calendar, Users, 
    Search, ArrowRight, Sparkles, Building2, ChevronRight, CheckCircle2,
    Home, Copy, Check, ArrowUp, Share2
} from 'lucide-react';

export default function CareerIndex({ jobs = [], departments = [], company = {} }) {
    const [search, setSearch] = useState('');
    const [selectedDept, setSelectedDept] = useState('ALL');
    const [copied, setCopied] = useState(false);

    const filteredJobs = jobs.filter((job) => {
        const matchesSearch = 
            job.title.toLowerCase().includes(search.toLowerCase()) ||
            (job.department && job.department.toLowerCase().includes(search.toLowerCase())) ||
            (job.location && job.location.toLowerCase().includes(search.toLowerCase()));
        
        const matchesDept = selectedDept === 'ALL' || job.department === selectedDept;

        return matchesSearch && matchesDept;
    });

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
    const whatsappShareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(`Halo! Cek informasi lowongan kerja terbaru di ${company?.company_name || 'Portal Karir'}: ${shareUrl}`)}`;

    return (
        <div className="min-h-screen bg-slate-50 text-slate-800 font-sans pb-20 sm:pb-0">
            <Head title="Portal Karir & Rekrutmen Terbuka" />

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

                    <div className="hidden sm:flex items-center gap-2 text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        {jobs.filter(j => j.is_active).length} Lowongan Tersedia
                    </div>
                </div>
            </header>

            {/* Hero Section */}
            <section className="bg-gradient-to-b from-slate-900 via-slate-850 to-slate-800 text-white py-14 px-4 sm:px-6">
                <div className="max-w-4xl mx-auto text-center space-y-4">
                    <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-rose-200 text-xs font-medium backdrop-blur-sm">
                        <Sparkles className="w-3.5 h-3.5 text-amber-300" />
                        <span>Peluang Karir & Tumbuh Bersama Kami</span>
                    </div>
                    <h2 className="text-2xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
                        Wujudkan Potensi Terbaikmu Bersama Tim Kami
                    </h2>
                    <p className="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
                        Temukan berbagai posisi yang sesuai dengan keahlian, minat, dan ambisimu. Proses seleksi transparan, profesional, dan setara.
                    </p>

                    {/* Search & Filter Bar */}
                    <div className="pt-4 max-w-2xl mx-auto flex flex-col sm:flex-row gap-2.5">
                        <div className="relative flex-1">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="Cari posisi pekerjaan, keahlian, atau departemen..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 shadow-md placeholder:text-slate-400"
                            />
                        </div>
                        <select
                            value={selectedDept}
                            onChange={(e) => setSelectedDept(e.target.value)}
                            className="px-4 py-2.5 rounded-xl bg-white text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 shadow-md font-medium"
                        >
                            <option value="ALL">Semua Departemen</option>
                            {departments.map((dept) => (
                                <option key={dept} value={dept}>{dept}</option>
                            ))}
                        </select>
                    </div>
                </div>
            </section>

            {/* List Lowongan */}
            <main className="max-w-6xl mx-auto px-4 sm:px-6 py-10">
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h3 className="text-lg font-bold text-slate-900">Daftar Lowongan Pekerjaan</h3>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Menampilkan {filteredJobs.length} posisi yang sesuai
                        </p>
                    </div>
                </div>

                {filteredJobs.length === 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-3">
                        <div className="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                            <Briefcase className="w-6 h-6" />
                        </div>
                        <h4 className="text-base font-bold text-slate-800">Tidak ada lowongan ditemukan</h4>
                        <p className="text-xs text-slate-500 max-w-sm mx-auto">
                            Silakan coba ubah kata kunci pencarian atau pilih kategori departemen yang lain.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {filteredJobs.map((job) => (
                            <div 
                                key={job.id} 
                                className="bg-white rounded-2xl border border-slate-200/80 hover:border-red-200 p-5 sm:p-6 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 flex flex-col justify-between group"
                            >
                                <div className="space-y-3">
                                    <div className="flex items-start justify-between gap-3">
                                        <div 
                                            style={{ color: company?.theme_color || '#a8001c' }}
                                            className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-red-50/80 border border-red-200/60"
                                        >
                                            <Building2 className="w-3 h-3" />
                                            {job.department || 'Umum'}
                                        </div>

                                        {job.is_active && job.status === 'Aktif' ? (
                                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Aktif
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                                Ditutup
                                            </span>
                                        )}
                                    </div>

                                    <div>
                                        <Link 
                                            href={`/karir/${job.slug}`}
                                            className="text-base sm:text-lg font-bold text-slate-900 group-hover:text-[#a8001c] transition leading-snug line-clamp-2"
                                        >
                                            {job.title}
                                        </Link>
                                        <p className="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                                            {job.description || 'Klik untuk melihat rincian kualifikasi dan persyaratan lengkap.'}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap gap-y-1.5 gap-x-4 pt-2 text-xs text-slate-600">
                                        <span className="inline-flex items-center gap-1 text-slate-500">
                                            <MapPin className="w-3.5 h-3.5 text-slate-400" />
                                            {job.location || 'Kantor Pusat'}
                                        </span>
                                        <span className="inline-flex items-center gap-1 text-slate-500">
                                            <Clock className="w-3.5 h-3.5 text-slate-400" />
                                            {job.job_type || 'Penuh Waktu'}
                                        </span>
                                        <span className="inline-flex items-center gap-1 text-slate-500">
                                            <Users className="w-3.5 h-3.5 text-slate-400" />
                                            Kuota: {job.quota || 1} org
                                        </span>
                                    </div>
                                </div>

                                <div className="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                                    <div className="text-xs font-semibold text-slate-700">
                                        {job.salary_range ? (
                                            <span className="text-emerald-700 font-bold">{job.salary_range}</span>
                                        ) : (
                                            <span className="text-slate-400 font-normal">Gaji Kompetitif</span>
                                        )}
                                    </div>

                                    <Link
                                        href={`/karir/${job.slug}`}
                                        style={{ color: company?.theme_color || '#a8001c' }}
                                        className="inline-flex items-center gap-1 text-xs font-bold hover:opacity-85 group-hover:translate-x-0.5 transition"
                                    >
                                        Lihat Rincian & Daftar
                                        <ChevronRight className="w-4 h-4" />
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
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
